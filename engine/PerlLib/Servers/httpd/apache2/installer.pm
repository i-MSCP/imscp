# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache2::installer;

use strict;
use warnings;
use iMSCP::Debug;
use Servers::SystemGroup;
use Servers::SystemUser;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass', 'Common::SetterClass');
use Common::SingletonClass;
use Common::SetterClass;

sub _init{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	debug((caller(0))[3].': Ending...');
	0;
}

sub setupUsers{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my ($rs, $panelGrName, $panelUName);

	## Panel user
	$panelUName = Servers::SystemUser->new();
	$panelUName->prop('home', "$self->{conf}->{'PHP_STARTER_DIR'}/master");
	$panelUName->prop('usercomment', 'iMSCP master virtual user');
	$rs = $panelUName->addSystemUser($self->{conf}->{'APACHE_SUEXEC_USER_PREF'}.$self->{conf}->{'APACHE_SUEXEC_MIN_GID'});
	return $rs if $rs;

	## Panel group
	$panelGrName = Servers::SystemGroup->new();
	$rs = $panelGrName->addSystemGroup($self->{conf}->{'APACHE_SUEXEC_USER_PREF'}.$self->{conf}->{'APACHE_SUEXEC_MIN_GID'});
	return $rs if $rs;

	$rs = $panelUName->addToGroup($self->{conf}->{'APACHE_SUEXEC_USER_PREF'}.$self->{conf}->{'APACHE_SUEXEC_MIN_GID'});
	return $rs if $rs;

	## Key group
	$rs = $panelUName->addToGroup($main::imscpConfig{'KEY_GROUP'});
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub setupFolders{

	debug((caller(0))[3].': Starting...');

	my $self = shift;
	use iMSCP::Dir;

	for (
		[$main::imscpConfig{'USER_HOME_DIR'},		$self->{conf}->{'APACHE_USER'},		$self->{conf}->{'APACHE_GROUP'}],
		[$self->{conf}->{'APACHE_USERS_LOG_DIR'},	$self->{conf}->{'APACHE_USER'},		$self->{conf}->{'APACHE_GROUP'}],
		[$self->{conf}->{'APACHE_BACKUP_LOG_DIR'},	$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
		[$self->{conf}->{'PHP_STARTER_DIR'},
													"$self->{conf}->{'APACHE_SUEXEC_USER_PREF'}$self->{conf}->{'APACHE_SUEXEC_MIN_UID'}",
																						"$self->{conf}->{'APACHE_SUEXEC_USER_PREF'}$self->{conf}->{'APACHE_SUEXEC_MIN_GID'}"
		]
	) {
		iMSCP::Dir->new(dirname => $_->[0])->make({ user => $_->[1], group => $_->[2], mode => 0755}) and return 1;
	}

	warning((caller(0))[3].': move this to own class');
	if ($main::imscpConfig{'AWSTATS_ACTIVE'} eq 'yes') {
		iMSCP::Dir->new(dirname => $main::imscpConfig{'AWSTATS_CACHE_DIR'})->make({ user => $self->{conf}->{'APACHE_USER'}, group => $self->{conf}->{'APACHE_GROUP'}, mode => 0755}) and return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

sub setupFCGI{
	debug((caller(0))[3].': Starting...');

	my $self  = shift;

	use iMSCP::File;
	use iMSCP::Dialog;
	use iMSCP::Templator;

	my ($rs, $cfgTpl, $tplValues, $err);

	# Saving the current production file if they exists
	for (qw/fastcgi_imscp.conf fastcgi_imscp.load fcgid_imscp.conf fcgid_imscp.load/) {
		if(-f "$self->{conf}->{'APACHE_MODS_DIR'}/$_") {
			iMSCP::File->new(filename => "$self->{conf}->{'APACHE_MODS_DIR'}/$_")->copyFile("$self->{bkpDir}/$_." . time) and return 1;
		}
	}

	## Building, storage and installation of new files

	foreach(keys %{$self}){
		$tplValues->{$_} = $self->{$_};
	}

	# fastcgi_imscp.conf / fcgid_imscp.conf
	for (qw/fastcgi fcgid/) {
		# Loading the template from the /etc/imscp/apache directory
		my $file = iMSCP::File->new(filename => "$self->{cfgDir}/${_}_imscp.conf");
		$cfgTpl = $file->get();
		return 1 if (!$cfgTpl);

		# Building the new configuration file
		$cfgTpl = iMSCP::Templator::process($tplValues, $cfgTpl);
		return 1 if (!$cfgTpl);

		# Storing the new file
		$file = iMSCP::File->new(filename => "$self->{wrkDir}/${_}_imscp.conf");
		$file->set($cfgTpl) and return 1;
		$file->save() and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
		$file->mode(0644) and return 1;

		# Installing the new file
		$file->copyFile("$self->{conf}->{'APACHE_MODS_DIR'}/") and return 1;
		next if(! -f "$self->{conf}->{'APACHE_MODS_DIR'}/$_.load");

		# Loading the system configuration file
		$file = iMSCP::File->new(filename => "$self->{conf}->{'APACHE_MODS_DIR'}/$_.load");
		$cfgTpl = $file->get();
		return 1 if (!$cfgTpl);

		# Building the new configuration file
		$file = iMSCP::File->new(filename => "$self->{wrkDir}/${_}_imscp.load");
		$cfgTpl = "<IfModule !mod_$_.c>\n" . $cfgTpl . "</IfModule>\n";
		$file->set($cfgTpl);

		# Store the new file
		$file->save() and return 1;
		$file->mode(0644) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

		# Install the new file
		$file->copyFile("$self->{conf}->{'APACHE_MODS_DIR'}/") and return 1;
	}

	my $httpd = Servers::httpd::apache2->new();

	$httpd->disableMod('php4');
	$httpd->disableMod('php5');
	$httpd->enableMod('actions');

	if(! -f '/etc/SuSE-release' && -f '/usr/sbin/a2enmod') {
		if($self->{conf}->{'PHP_FASTCGI'} !~ /fcgid|fastcgi/i) {
			if($self->{conf}->{'PHP_FASTCGI'} && $main::imscpConfigOld{'PHP_FASTCGI'} =~ /fcgid|fastcgi/i){
				$self->{conf}->{'PHP_FASTCGI'} = $main::imscpConfigOld{'PHP_FASTCGI'};
			} else {
				my $out;
				while (! ($out = iMSCP::Dialog->new()->radiolist("Please select a Fast CGI module: fcgid or fastcgi", 'fcgid', 'fastcgi'))){}
				$self->{conf}->{'PHP_FASTCGI'} = $out;
			}
		}

		# Ensures that the unused i-MSCP fcgid module loader is disabled
		my $enable	= $self->{conf}->{'PHP_FASTCGI'} eq 'fastcgi' ? 'fastcgi_imscp' : 'fcgid_imscp';
		my $disable	= $self->{conf}->{'PHP_FASTCGI'} eq 'fastcgi' ? 'fcgid_imscp' : 'fastcgi_imscp';

		$rs = $httpd->disableMod($disable);
		return $rs if($rs && -f "$self->{conf}->{'APACHE_MODS_DIR'}/$disable.load");

		$rs = $httpd->disableMod('fastcgi');
		return $rs if($rs && -f "$self->{conf}->{'APACHE_MODS_DIR'}/fastcgi.load");

		$rs = $httpd->disableMod('fcgid');
		return $rs if($rs && -f "$self->{conf}->{'APACHE_MODS_DIR'}/fcgid.load");
		$rs = $httpd->enableMod($enable);
		return $rs if($rs);
	}

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# i-MSCP GUI PHP configuration files - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Create the master fcgi directory
#  - Built, store and install gui php related files (starter script, php.ini...)
#
# @return int 0 on success, other on failure
#
sub setup_gui_php {

	debug((caller(0))[3].': Starting...');

	my ($rs, $cfgTpl, $file);

	my $timestamp = time;

	# Saving files if they exists
	for ('php5-fcgi-starter', 'php5/php.ini', 'php5/browscap.ini') {
		if(-f "$main::imscpConfig{'PHP_STARTER_DIR'}/master/$_") {
			my (undef, $name) = split('/');
			$name = $_ if(!defined $name);
			my $file = iMSCP::File->new(filename => "$main::imscpConfig{'PHP_STARTER_DIR'}/master/$_");
			$file->copyFile("$self->{bkpDir}/master.$name.$timestamp") and return 1;
		}
	}

	## Create the fcgi directories tree for the GUI if it doesn't exists
	my $dir = iMSCP::Dir->new(dirname => "$main::imscpConfig{'PHP_STARTER_DIR'}/master/php5");
	$dir->make({user=>$main::imscpConfig{'ROOT_USER'}, group =>$main::imscpConfig{'ROOT_GROUP'}, mode => 0755}) and return 1;

	## PHP5 Starter script

	# Loading the template from /etc/imscp/fcgi/parts/master
	$cfgTpl = iMSCP::File->new(filename => "$self->{cfgDir}/parts/master/php5-fcgi-starter.tpl")->get();
	return 1 if (!$cfgTpl);

	# Building the new file
	$cfgTpl = process(
		{
			PHP_STARTER_DIR		=> $self->{'PHP_STARTER_DIR'},
			PHP5_FASTCGI_BIN	=> $self->{'PHP5_FASTCGI_BIN'},
			GUI_ROOT_DIR		=> $self->{'GUI_ROOT_DIR'},
			DMN_NAME			=> 'master'
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	# Storing the new file in the working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/master.php5-fcgi-starter");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0755) and return 1;
	$file->owner($self->{'APACHE_SUEXEC_USER_PREF'} . $self->{'APACHE_SUEXEC_MIN_UID'},$self->{'APACHE_SUEXEC_USER_PREF'} . $self->{'APACHE_SUEXEC_MIN_GID'}) and return 1;

	# Install the new file
	$file->copyFile("$main::imscpConfig{'PHP_STARTER_DIR'}/master/php5-fcgi-starter") and return 1;

	## PHP5 php.ini file

	# Loading the template from /etc/imscp/fcgi/parts/master/php5
	$cfgTpl = iMSCP::File->new(filename => "$cfgDir/parts/master/php5/php.ini")->get();
	return 1 if (!$cfgTpl);
	askPHPTimezone();
	# Building the new file
	$cfgTpl = process(
		{
			WWW_DIR				=> $main::imscpConfig{'ROOT_DIR'},
			DMN_NAME			=> 'gui',
			MAIL_DMN			=> $main::imscpConfig{'BASE_SERVER_VHOST'},
			CONF_DIR			=> $main::imscpConfig{'CONF_DIR'},
			MR_LOCK_FILE		=> $main::imscpConfig{'MR_LOCK_FILE'},
			PEAR_DIR			=> $self->{'PEAR_DIR'},
			RKHUNTER_LOG		=> $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG		=> $main::imscpConfig{'CHKROOTKIT_LOG'},
			OTHER_ROOTKIT_LOG	=> ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '') ? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : '',
			PHP_STARTER_DIR		=> $self->{'PHP_STARTER_DIR'},
			PHP_TIMEZONE		=> $self->{'PHP_TIMEZONE'}
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	# Store the new file in working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/master.php.ini");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($self->{'APACHE_SUEXEC_USER_PREF'} . $self->{'APACHE_SUEXEC_MIN_UID'},$self->{'APACHE_SUEXEC_USER_PREF'} . $self->{'APACHE_SUEXEC_MIN_GID'}) and return 1;

	# Install the new file
	$file->copyFile("$self->{'PHP_STARTER_DIR'}/master/php5/php.ini") and return 1;


	## PHP Browser Capabilities support file

	# Store the new file in working directory
	iMSCP::File->new(filename => "$self->{cfgDir}/parts/master/php5/browscap.ini")->copyFile("$self->{wrkDir}/browscap.ini") and return 1;

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/browscap.ini");
	$file->mode(0644) and return 1;
	$file->owner($self->{'APACHE_SUEXEC_USER_PREF'} . $self->{'APACHE_SUEXEC_MIN_UID'}, $self->{'APACHE_SUEXEC_USER_PREF'} . $self->{'APACHE_SUEXEC_MIN_GID'}) and return 1;

	# Install the new file
	$file->copyFile("$self->{'PHP_STARTER_DIR'}/master/php5/browscap.ini") and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}



sub other{

	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my $file;
	use iMSCP::File;
	use iMSCP::Templator;
	use iMSCP::Execute;
	use Servers::httpd::apache2;

	my $httpd = Servers::httpd::apache2->new();

	## Disable 000-default vhost (Debian like distributions)
	$httpd->disableSite('000-default');

	# Disable the default NameVirtualHost directive (Debian like distributions)
	if(-f '/etc/apache2/ports.conf') {
		# Loading the file
		$file = iMSCP::File->new(filename => '/etc/apache2/ports.conf');
		my $rdata = $file->get();
		return $rdata if(!$rdata);

		# Disable the default NameVirtualHost directive
		$rdata =~ s/^NameVirtualHost \*:80/#NameVirtualHost \*:80/gmi;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	# Using alternative syntax for piped logs scripts when possible the alternative syntax does not involve the Shell (from Apache 2.2.12)
	my $pipeSyntax = '|';
	my ($rs, $stdout, $stderr);
	$rs = execute("$self->{conf}->{'CMD_HTTPD_CTL'} -v", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	return $rs if $rs;

	$pipeSyntax .= '|' if($stdout =~ m!Apache/([\d.]+)! && version->new($1) >= version->new('2.2.12'));

	# Building the new file
	my $cfgTpl = {
		APACHE_WWW_DIR	=> $main::imscpConfig{'USER_HOME_DIR'},
		ROOT_DIR		=> $main::imscpConfig{'ROOT_DIR'},
		PIPE			=> $pipeSyntax
	};

	$httpd->prop($cfgTpl);
	$httpd->buildConfFile('imscp.conf');

	## Enable required modules
	$httpd->enableMod('suexec');
	$httpd->enableMod('rewrite');
	$httpd->enableMod('cgid');
	$httpd->enableSite('imscp.conf');

	debug((caller(0))[3].': Ending...');
	0;
}



1;
