#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l;declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_fcgi::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::Rights;
use Modules::SystemGroup;
use Modules::SystemUser;
use iMSCP::Dir;
use iMSCP::File;
use File::Basename;
use Servers::httpd::apache_fcgi;
use version;
use Net::LibIDN qw/idn_to_ascii/;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforeHttpdInitInstaller', $self, 'apache_fcgi');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/apache.data";
	my $oldConf = "$self->{'cfgDir'}/apache.old.data";

	tie %self::apacheConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if(-f $oldConf) {
		tie %self::apacheOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::apacheConfig = (%self::apacheConfig, %self::apacheOldConfig);
	}

	$self->{'hooksManager'}->trigger('afterHttpdInitInstaller', $self, 'apache_fcgi');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;
	my $rs = 0;

	$rs = $hooksManager->trigger('beforeHttpdRegisterSetupHooks', $hooksManager, 'apache_fcgi');
	return $rs if $rs;

	# Add installer dialog in setup dialog stack
	$rs = $hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askCgiModule(@_) }); 0; }
	);
	return $rs if $rs;

	# Fix error_reporting value into the database
	$rs = $hooksManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
	return $rs if $rs;

	$hooksManager->trigger('afterHttpdRegisterSetupHooks', $hooksManager, 'apache_fcgi');
}

sub askCgiModule
{
	my $self = shift;
	my $dialog = shift;
	my $rs = 0;
	my $cgiModule = $main::preseed{'PHP_FASTCGI'} || $self::apacheConfig{'PHP_FASTCGI'} ||
		$self::apacheOldConfig{'PHP_FASTCGI'} || '';

	if($main::reconfigure ~~ ['httpd', 'servers', 'all', 'forced'] || $cgiModule !~ /^fcgid|fastcgi$/) {
		($rs, $cgiModule) = $dialog->radiolist(
			"\nPlease, select the fastCGI Apache module you want use:",
			['fcgid', 'fastcgi'],
			$cgiModule ne 'fastcgi' ? 'fcgid' : 'fastcgi'
		);
	}

	$self::apacheConfig{'PHP_FASTCGI'} = $cgiModule if $rs != 30;

	$rs;
}

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdInstall', 'apache_fcgi');
	return $rs if $rs;

	# Saving all system configuration files if they exists
	for (
		"$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache",
		"$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf"
	) {
		$rs = $self->bkpConfFile($_);
		return $rs if $rs;
	}

	$rs = $self->addUsersAndGroups();
	return $rs if $rs;

	$rs = $self->makeDirs();
	return $rs if $rs;

	$rs = $self->buildFastCgiConfFiles();
	return $rs if $rs;

	$rs = $self->buildPhpConfFiles();
	return $rs if $rs;

	$rs = $self->buildApacheConfFiles();
	return $rs if $rs;

	$rs = $self->buildMasterVhostFiles();
	return $rs if $rs;

	$rs = $self->installLogrotate();
	return $rs if $rs;

	$rs = $self->saveConf();
	return $rs if $rs;

	$rs = $self->setGuiPermissions();
	return $rs if $rs;

	$rs = $self->oldEngineCompatibility();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_fcgi');
}

# Fix PHP error_reporting value according PHP version
#
# This rustine fix the error_reporting integer values in the iMSCP databse according the PHP version installed on
# the system.
#
# This hook function acts on the 'afterSetupCreateDatabase' hook.
#
# Return int - 0 on success, 1 on failure
#
sub _fixPhpErrorReportingValues
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr);
	my ($database, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	if(! $database) {
		error("Unable to connect to SQL Server: $errStr");
		return 1;
	}

	$rs = execute('php -v', \$stdout, \$stderr);
	return $rs if $rs;

	my $phpVersion = $1 if $stdout =~ /^PHP\s([0-9.]{3})/;

	if(defined $phpVersion and ($phpVersion eq '5.3' || $phpVersion eq '5.4')) {
		my %errorReportingValues = (
			'5.3' => {
				32759 => 30711,	# E_ALL & ~E_NOTICE
				32767 => 32767,	# E_ALL | E_STRICT
				24575 => 22527	# E_ALL & ~E_DEPRECATED
			},
			'5.4' => {
				30711 => 32759,	# E_ALL & ~E_NOTICE
				32767 => 32767,	# E_ALL | E_STRICT
				22527 => 24575	# E_ALL & ~E_DEPRECATED
			}
		);

		for(keys %{$errorReportingValues{$phpVersion}}) {
			my $from = $_;
			my $to = $errorReportingValues{$phpVersion}->{$_};

			$rs = $database->doQuery(
				'dummy',
				"UPDATE `config` SET `value` = ? WHERE `name` = 'PHPINI_ERROR_REPORTING' AND `value` = ?",
				$to,
				$from
			);
			return 1 if ref $rs ne 'HASH';

			$rs = $database->doQuery(
				'dummy',
				'UPDATE `php_ini` SET `error_reporting` = ? WHERE `error_reporting` = ?',
				$to,
				$from
			);
			return 1 if ref $rs ne 'HASH';
		}
	} else {
		error('Unable to find PHP version');
		return 1;
	}

	0;
}

sub setGuiPermissions
{
	my $self = shift;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheUName = $self::apacheConfig{'APACHE_USER'};
	my $apacheGName = $self::apacheConfig{'APACHE_GROUP'};
	my $phpDir = $self::apacheConfig{'PHP_STARTER_DIR'};
	my $rootDir = $main::imscpConfig{'ROOT_DIR'};
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdSetGuiPermissions');
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/public",
		{ user => $panelUName, group => $apacheGName, dirmode => '0550', filemode => '0440', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/themes",
		{ user => $panelUName, group => $apacheGName, dirmode => '0550', filemode => '0440', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/library",
		{ user => $panelUName, group => $panelGName, dirmode => '0500', filemode => '0400', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/data",
		{ user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/data",
		{ user => $panelUName, group => $apacheGName, mode => '0550' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/data/ispLogos",
		{ user => $panelUName, group => $apacheGName, dirmode => '0750', filemode => '0640', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/i18n",
		{ user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/plugins",
		{ user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui/plugins",
		{ user => $panelUName, group => $apacheGName, mode => '0550' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$rootDir/gui",
		{ user => $panelUName, group => $apacheGName, mode => '0550' }
	);
	return $rs if $rs;

	$rs = setRights(
		$rootDir,
		{ user => $panelUName, group => $apacheGName, mode => '0555' }
	);
	return $rs if $rs;

	$rs = setRights(
		$phpDir,
		{ user => $rootUName, group => $rootGName, mode => '0555' }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdSetGuiPermissions');
}

sub addUsersAndGroups
{
	my $self = shift;
	my ($panelGName, $panelUName);
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdAddUsersAndGroups');
	return $rs if $rs;

	# Panel group

	$panelGName = Modules::SystemGroup->new();
	$rs = $panelGName->addSystemGroup(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
	return $rs if $rs;

	## Panel user
	$panelUName = Modules::SystemUser->new();
	$panelUName->{'skipCreateHome'} = 'yes';
	$panelUName->{'comment'} = 'iMSCP master virtual user';
	$panelUName->{'home'} = $main::imscpConfig{'GUI_ROOT_DIR'};
	$panelUName->{'group'} = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	$rs = $panelUName->addSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
	return $rs if $rs;

	$rs = $panelUName->addToGroup($main::imscpConfig{'MASTER_GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddUsersAndGroups');
}

sub makeDirs
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdMakeDirs');
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheUName = $self::apacheConfig{'APACHE_USER'};
	my $apacheGName = $self::apacheConfig{'APACHE_GROUP'};
	my $phpdir = $self::apacheConfig{'PHP_STARTER_DIR'};

	for (
		[$self::apacheConfig{'APACHE_USERS_LOG_DIR'}, $apacheUName, $apacheGName, 0755],
		[$self::apacheConfig{'APACHE_BACKUP_LOG_DIR'}, $rootUName, $rootGName, 0755],
		[$phpdir, $rootUName, $rootGName, 0755],
		["$phpdir/master", $panelUName, $panelGName, 0755],
		["$phpdir/master/php5", $panelUName, $panelGName, 0755]
	) {
		$rs = iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3]}
		);
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdMakeDirs');
}

sub bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $timestamp = time;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile){
		my $file = iMSCP::File->new('filename' => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(! -f "$self->{'bkpDir'}/$filename$suffix.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $cfgFile);
}

sub saveConf
{
	my $self = shift;
	my $rs = 0;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/apache.data");
	my $cfg = $file->get() or return 1;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', \$cfg, "$self->{'cfgDir'}/apache.data");
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/apache.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', "$self->{'cfgDir'}/apache.data");
}


sub oldEngineCompatibility
{
	my $self = shift;

	my $httpd = Servers::httpd::apache_fcgi->getInstance();
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdOldEngineCompatibility');
	return $rs if $rs;

	if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf"){
		$rs = $httpd->disableSite('imscp.conf');
		return $rs if $rs;

		$rs = iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf")->delFile();
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdOldEngineCompatibility');
}

sub buildFastCgiConfFiles
{
	my $self = shift;
	my $cfgTpl;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildFastCgiConfFiles');

	my $httpd = Servers::httpd::apache_fcgi->getInstance();

	# Saving the current production file if they exists
	for ('fastcgi_imscp.conf', 'fastcgi_imscp.load', 'fcgid_imscp.conf', 'fcgid_imscp.load') {
		$rs = $self->bkpConfFile("$self::apacheConfig{'APACHE_MODS_DIR'}/$_");
		return $rs if $rs;
	}

	# Building, storage and installation of new files

	# fastcgi_imscp.conf / fcgid_imscp.conf
	for ('fastcgi', 'fcgid') {
		# Loading the template from the /etc/imscp/apache directory
		$httpd->setData(
			{
				SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
				SYSTEM_USER_MIN_UID	=> $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
				PHP_VERSION => $main::imscpConfig{'PHP_VERSION'}
			}
		);

		$rs = $httpd->buildConfFile("$self->{'cfgDir'}/${_}_imscp.conf");
		return $rs if $rs;

		my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/${_}_imscp.conf");

		$rs = $file->copyFile($self::apacheConfig{'APACHE_MODS_DIR'});
		return $rs if $rs;

		next if(! -f "$self::apacheConfig{'APACHE_MODS_DIR'}/$_.load");

		# Loading the system configuration file
		$file = iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_MODS_DIR'}/$_.load");

		$cfgTpl = $file->get();
		return 1 if ! defined $cfgTpl;

		# Building the new configuration file
		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/${_}_imscp.load");

		$cfgTpl = "<IfModule !mod_$_.c>\n" . $cfgTpl . "</IfModule>\n";

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		# Store the new file
		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Install the new file
		$rs = $file->copyFile($self::apacheConfig{'APACHE_MODS_DIR'});
		return $rs if $rs;
	}

	# Ensures that the unused i-MSCP fcgid module loader is disabled
	my $enable = $self::apacheConfig{'PHP_FASTCGI'} eq 'fastcgi' ? 'fastcgi_imscp' : 'fcgid_imscp';
	my $disable = $self::apacheConfig{'PHP_FASTCGI'} eq 'fastcgi' ? 'fcgid_imscp' : 'fastcgi_imscp';

	## Enable required modules and disable unused

	# try to disable but do not fail if do not exists
	for('fastcgi', 'fcgid', 'php4', 'php5', 'php_fpm_imscp', $disable) {
		$rs = $httpd->disableMod($_) if -f "$self::apacheConfig{'APACHE_MODS_DIR'}/$_.load";
		return $rs if $rs;
	}

	$rs = $httpd->enableMod("actions $enable");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildFastCgiConfFiles');
}

sub buildApacheConfFiles
{
	my $self = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildApacheConfFiles');
	return $rs if $rs;

	my $httpd = Servers::httpd::apache_fcgi->getInstance();

	if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/00_nameserver.conf") {
		$rs = iMSCP::File->new(
			filename => "$self::apacheConfig{'APACHE_SITES_DIR'}/00_nameserver.conf"
		)->copyFile("$self->{'bkpDir'}/00_nameserver.conf.". time);
		return $rs if $rs;
	}

	if(-f "$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf") {

		# Loading the file
		my $file = iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf");
		my $rdata = $file->get();
		return $rdata if ! defined $rdata;

		$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$rdata, 'ports.conf');
		return $rs if $rs;

		$rdata =~ s/^NameVirtualHost \*:80/#NameVirtualHost \*:80/gmi;

		$rs = $self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$rdata, 'ports.conf');
		return $rs if $rs;

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the Shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(`$self::apacheConfig{'CMD_HTTPD_CTL'} -v` =~ m!Apache/([\d.]+)! && version->new($1) >= version->new('2.2.12')) {
		$pipeSyntax .= '|';
	}

	# Set needed data
	$httpd->setData(
		{
			APACHE_WWW_DIR => $main::imscpConfig{'USER_HOME_DIR'},
			ROOT_DIR => $main::imscpConfig{'ROOT_DIR'},
			PIPE => $pipeSyntax
		}
	);

	$rs = $httpd->buildConfFile(
		"$self->{'cfgDir'}/00_nameserver.conf", { 'destination' => "$self->{'wrkDir'}/00_nameserver.conf" }
	);
	return $rs if $rs;

	# Installing the new file in production directory
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_nameserver.conf");
	$rs = $file->copyFile($self::apacheConfig{'APACHE_SITES_DIR'});
	return $rs if $rs;

	# Enable required apache modules
	$rs = $httpd->enableMod('cgid rewrite suexec proxy proxy_http ssl');
	return $rs if $rs;

	# Enable 00_nameserver.conf file
	$rs = $httpd->enableSite('00_nameserver.conf');
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

sub buildPhpConfFiles
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildPhpConfFiles');

	my $httpd = Servers::httpd::apache_fcgi->getInstance();
	my ($cfgTpl, $file);
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/fcgi";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	my $timestamp = time;

	# Saving files if they exists
	for ('php5-fcgid-starter', 'php5-fastcgi-starter', 'php5/php.ini', 'php5/browscap.ini') {
		if(-f "$self::apacheConfig{'PHP_STARTER_DIR'}/master/$_") {
			my (undef, $name) = split('/');
			$name = $_ if !defined $name;

			my $file = iMSCP::File->new('filename' => "$self::apacheConfig{'PHP_STARTER_DIR'}/master/$_");
			$rs = $file->copyFile("$bkpDir/master.$name.$timestamp");
			return $rs if $rs;
		}
	}


	my $panelUname = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	## PHP5 Starter script (fcgid)

	# Loading the template from /etc/imscp/fcgi/parts/master
	$httpd->setData(
		{
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			DMN_NAME => 'master'
		}
	);

	$rs = $httpd->buildConfFile(
		"$cfgDir/parts/master/php5-fcgid-starter.tpl",
		{
			destination	=> "$wrkDir/master.php5-fcgid-starter",
			mode => 0755,
			user => $panelUname,
			group => $panelGName
		}
	);
	return $rs if $rs;

	# Install the new file
	$file = iMSCP::File->new('filename' => "$wrkDir/master.php5-fcgid-starter");

	$rs = $file->copyFile("$self::apacheConfig{'PHP_STARTER_DIR'}/master/php5-fcgid-starter");
	return $rs if $rs;

	## PHP5 Starter script (fastcgi)

	# Loading the template from /etc/imscp/fcgi/parts/master
	$httpd->setData(
		{
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			DMN_NAME => 'master'
		}
	);

	$rs = $httpd->buildConfFile(
		"$cfgDir/parts/master/php5-fastcgi-starter.tpl",
		{
			destination => "$wrkDir/master.php5-fastcgi-starter",
			mode => 0755,
			user => $panelUname,
			group => $panelGName
		}
	);
	return $rs if $rs;

	# Install the new file
	$file = iMSCP::File->new('filename' => "$wrkDir/master.php5-fastcgi-starter");

	$rs = $file->copyFile("$self::apacheConfig{'PHP_STARTER_DIR'}/master/php5-fastcgi-starter");
	return $rs if $rs;

	## PHP5 php.ini file

	# Loading the template from /etc/imscp/fcgi/parts/master/php5
	$httpd->setData(
		{
			WWW_DIR	 => $main::imscpConfig{'ROOT_DIR'},
			DMN_NAME => 'gui',
			MAIL_DMN => $main::imscpConfig{'BASE_SERVER_VHOST'},
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			MR_LOCK_FILE => $main::imscpConfig{'MR_LOCK_FILE'},
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			RKHUNTER_LOG => $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG => $main::imscpConfig{'CHKROOTKIT_LOG'},
			OTHER_ROOTKIT_LOG => ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '')
				? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : '',
			PHP_TIMEZONE => $main::imscpConfig{'PHP_TIMEZONE'}
		}
	);

	$rs = $httpd->buildConfFile(
		"$cfgDir/parts/master/php5/php.ini",
		{
			destination	=> "$wrkDir/master.php.ini",
			mode => 0644,
			user => $panelUname,
			group => $panelGName
		}
	);
	return $rs if $rs;

	# Install the new file in production directory
	$file = iMSCP::File->new('filename' => "$wrkDir/master.php.ini");

	$rs = $file->copyFile("$self::apacheConfig{'PHP_STARTER_DIR'}/master/php5/php.ini");
	return $rs if $rs;


	# PHP Browser Capabilities support file

	# Store the new file in working directory
	$rs = iMSCP::File->new('filename' => "$cfgDir/parts/master/php5/browscap.ini")->copyFile("$wrkDir/browscap.ini");
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$wrkDir/browscap.ini");

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($panelUname, $panelGName);
	return $rs if $rs;

	# Install the new file
	$rs = $file->copyFile("$self::apacheConfig{'PHP_STARTER_DIR'}/master/php5/browscap.ini");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

sub buildMasterVhostFiles
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildMasterVhostFiles');
	return $rs if $rs;

	my $httpd = Servers::httpd::apache_fcgi->getInstance();

	my $adminEmailAddress = $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'};
	my ($user, $domain) = split /@/, $adminEmailAddress;

	$adminEmailAddress = "$user@" . idn_to_ascii($domain, 'utf-8');

	$httpd->setData(
		{
			BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DEFAULT_ADMIN_ADDRESS => $adminEmailAddress,
			ROOT_DIR => $main::imscpConfig{'ROOT_DIR'},
			SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
			SYSTEM_USER_MIN_UID => $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
			PHP_VERSION => $main::imscpConfig{'PHP_VERSION'},
			GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
			SERVER_HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'}
		}
	);

	# Build 00_master.conf file

	# Schedule useless itk sections deletion
	$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('itk', @_) });
	return $rs if $rs;

	if($self::apacheConfig{'PHP_FASTCGI'} eq 'fastcgi') {
		# Schedule useless fcgid section deletion
		$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('fcgid', @_) });
		return $rs if $rs;
	} else {
		# Schedule useless fastcgi section deletion
		$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('fastcgi', @_) });
		return $rs if $rs;
	}

	# Schedule useless php_fpm sections deletion
	$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('php_fpm', @_) });
	return $rs if $rs;

	$rs = $httpd->buildConfFile("$self->{'cfgDir'}/00_master.conf");
	return $rs if $rs;

	$rs = iMSCP::File->new(
		'filename' => "$self->{'wrkDir'}/00_master.conf"
	)->copyFile(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/00_master.conf"
	);
	return $rs if $rs;

	# Build 00_master_ssl.conf file

	# Schedule useless itk sections deletion
	$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('itk', @_) });
	return $rs if $rs;

	if($self::apacheConfig{'PHP_FASTCGI'} eq 'fastcgi') {
		# Schedule useless fcgid section deletion
		$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('fcgid', @_) });
		return $rs if $rs;
	} else {
		# Schedule useless fastcgi section deletion
		$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('fastcgi', @_) });
		return $rs if $rs;
	}

	# Schedule useless php_fpm sections deletion
	$rs = $self->{'hooksManager'}->register('beforeHttpdBuildConfFile', sub { $httpd->removeSection('php_fpm', @_) });
	return $rs if $rs;

	$rs = $httpd->buildConfFile("$self->{'cfgDir'}/00_master_ssl.conf");
	return $rs if $rs;

	iMSCP::File->new(
		'filename' => "$self->{'wrkDir'}/00_master_ssl.conf"
	)->copyFile(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/00_master_ssl.conf"
	);
	return $rs if $rs;

	# Enable and disable vhost files
	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes') {
		$rs = $httpd->enableSite('00_master.conf 00_master_ssl.conf');
		return $rs if $rs;
	} else {
		$rs = $httpd->enableSite('00_master.conf');
		return $rs if $rs;

		$rs = $httpd->disableSite('00_master_ssl.conf');
		return $rs if $rs;
	}

	# Disable defaults sites if exists
    $rs = $httpd->disableSite('default') if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/default";
    return $rs if $rs;

    $rs = $httpd->disableSite('default-ssl') if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/default-ssl";
    return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildMasterVhostFiles');
}

sub installLogrotate
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'apache2');
	return $rs if $rs;

	my $httpd = Servers::httpd::apache_fcgi->getInstance();

	$rs = $httpd->buildConfFile('logrotate.conf');
	return $rs if $rs;

	$rs = $httpd->installConfFile(
		'logrotate.conf', { 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'apache2');
}

1;
