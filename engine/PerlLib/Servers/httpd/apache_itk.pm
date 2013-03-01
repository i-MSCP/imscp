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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l;declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_itk;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Dir;
use Data::Dumper;
use iMSCP::Execute;
use File::Basename;
use iMSCP::File;
use iMSCP::Templator;
use iMSCP::Rights;
use POSIX;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforeHttpdInit', $self, 'apache_itk');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	my $conf = "$self->{'cfgDir'}/apache.data";
	tie %self::apacheConfig, 'iMSCP::Config','fileName' => $conf;

	$self->{'tplValues'}->{$_} = $self::apacheConfig{$_} for keys %self::apacheConfig;

	$self->{'hooksManager'}->trigger('afterHttpdInit', $self, 'apache_itk');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;
	my $rs = 0;

	$rs = $hooksManager->trigger('beforeHttpdRegisterSetupHooks', $hooksManager, 'apache_itk');

	require Servers::httpd::apache_itk::installer;

	$rs |= Servers::httpd::apache_itk::installer->new()->registerSetupHooks($hooksManager);

	$rs |= $hooksManager->trigger('afterHttpdRegisterSetupHooks', $hooksManager, 'apache_itk');

	$rs;
}

sub preinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdPreInstall', 'apache_itk');

	$rs |= $self->stop();

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdPreInstall', 'apache_itk');

	$rs;
}

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdInstall', 'apache_itk');

	require Servers::httpd::apache_itk::installer;

	$rs |= Servers::httpd::apache_itk::installer->new()->install();

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_itk');

	$rs;
}

sub uninstall
{
	my $self = shift;
	my $rs = 0;

	$rs |= $self->stop();

	$rs |= $self->{'hooksManager'}->trigger('beforeHttpdUninstall', 'apache_itk');

	require Servers::httpd::apache_itk::uninstaller;

	$rs |= Servers::httpd::apache_itk::uninstaller->new()->uninstall();

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdUninstall', 'apache_itk');

	$rs |= $self->start();

	$rs;
}

sub postinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdPostInstall', 'apache_itk');

	$self->{'start'} = 'yes';

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdPostInstall', 'apache_itk');

	$rs;
}

sub setGuiPermissions
{
	my $self = shift;

	require Servers::httpd::apache_itk::installer;

	Servers::httpd::apache_itk::installer->new()->setGuiPermissions();
}

sub enableSite
{
	my $self = shift;
	my $sites = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableSite', \$sites);

	for(split(' ', $sites)){
		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"){
			$rs |= execute("a2ensite $_", \$stdout, \$stderr);
			debug("stdout $stdout") if($stdout);
			error("stderr $stderr") if($stderr);
			return $rs if $rs;
		} else {
			warning("Site $_ doesn't exists");
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdEnableSite', $sites);

	$rs;
}

sub disableSite
{
	my $self = shift;
	my $sites = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableSite', \$sites);

	for(split(' ', $sites)){
		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"){
			$rs |= execute("a2dissite $_", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr;
			return $rs if $rs;
		} else {
			warning("Site $_ doesn't exists");
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDisableSite', $sites);

	$rs;
}

sub enableMod
{
	my $self = shift;
	my $mod = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdEnableMod', \$mod);

	my ($stdout, $stderr);
	$rs |= execute("a2enmod $mod", \$stdout, \$stderr);
	debug($stdout) if($stdout);
	error($stderr) if($stderr);

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdEnableMod', $mod);

	$rs;
}

sub disableMod
{
	my $self = shift;
	my $mod = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdDisableMod', \$mod);

	my ($stdout, $stderr);
	$rs |= execute("a2dismod $mod", \$stdout, \$stderr);
	debug($stdout) if($stdout);
	error($stderr) if($stderr);

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDisableMod', $mod);

	$rs;
}

sub forceRestart
{
	my $self = shift;

	$self->{'forceRestart'} = 'yes';

	0;
}

sub start
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStart');

	my ($stdout, $stderr);
	$rs |= execute("$self->{'tplValues'}->{'CMD_HTTPD'} start", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && !$rs;
	error($stderr) if $stderr && $rs;
	error("Error while starting Apache") if $rs && !$stderr;

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdStart');

	$rs;
}

sub stop
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdStop');

	my ($stdout, $stderr);
	$rs |= execute("$self->{'tplValues'}->{'CMD_HTTPD'} stop", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && !$rs;
	error($stderr) if $stderr && $rs;
	error("Error while stoping") if $rs && !$stderr;

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdStop');

	$rs;
}

sub restart
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdRestart');

	my ($stdout, $stderr);
	$rs |= execute("$self->{'tplValues'}->{'CMD_HTTPD'} ".($self->{'forceRestart'} ? 'restart' : 'reload'), \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && !$rs;
	error($stderr) if $stderr && $rs;
	error("Error while restarting") if $rs && !$stderr;

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdRestart');

	$rs;
}

sub buildConf($ $ $)
{
	my $self = shift;
	my $cfgTpl = shift;
	my $filename = shift || '';

	error('Empty config template...') unless $cfgTpl;
	return undef unless $cfgTpl;

	$self->{'tplValues'}->{$_} = $self->{'data'}->{$_} for keys %{$self->{'data'}};
	warning('Nothing to do...') unless keys %{$self->{'tplValues'}} > 0;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildConf', \$cfgTpl, $filename);

	$cfgTpl = process($self->{'tplValues'}, $cfgTpl);
	return undef if ! $cfgTpl;

    $self->{'hooksManager'}->trigger('afterHttpdBuildConf', \$cfgTpl, $filename);

	$cfgTpl;
}

sub buildConfFile
{
	my $self = shift;
	my $file = shift;
	my $option = shift;

	$option = {} if ref $option ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$file = "$self->{'cfgDir'}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new('filename' => $file);
	my $cfgTpl = $fileH->get();
	error("Empty config template $file...") unless $cfgTpl;
	return 1 unless $cfgTpl;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, "$filename$suffix") and return 1;

	$cfgTpl = $self->buildConf($cfgTpl, "$filename$suffix");
	return 1 if (! $cfgTpl);

	$cfgTpl =~ s/\n{2,}/\n\n/g; # Remove duplicate blank lines

	$self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, "$filename$suffix") and return 1;

	$fileH = iMSCP::File->new(
		filename => ($option->{'destination'} ? $option->{'destination'} : "$self->{'wrkDir'}/$filename$suffix")
	);
	$fileH->set($cfgTpl) and return 1;
	$fileH->save() and return 1;
	$fileH->mode($option->{'mode'} ? $option->{'mode'} : 0644) and return 1;
	$fileH->owner(
		$option->{'user'} ? $option->{'user'} : $main::imscpConfig{'ROOT_USER'},
		$option->{'group'} ? $option->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	) and return 1;

	0;
}

sub installConfFile
{
	my $self = shift;
	my $file = shift;
	my $option = shift;

	$option = {} if ref $option ne 'HASH';

	my ($filename, $directories, $suffix) = fileparse($file);

	$self->{'hooksManager'}->trigger('beforeHttpdInstallConfFile', "$filename$suffix") and return 1;

	$file = "$self->{'wrkDir'}/$file" unless -d $directories && $directories ne './';

	my $fileH = iMSCP::File->new('filename' => $file);

	$fileH->mode($option->{'mode'} ? $option->{'mode'} : 0644) and return 1;
	$fileH->owner(
			$option->{'user'} ? $option->{'user'} : $main::imscpConfig{'ROOT_USER'},
			$option->{'group'} ? $option->{'group'} : $main::imscpConfig{'ROOT_GROUP'}
	) and return 1;

	$fileH->copyFile(
		$option->{'destination'} ? $option->{'destination'} : "$self::apacheConfig{'APACHE_SITES_DIR'}/$filename$suffix"
	);

	$self->{'hooksManager'}->trigger('afterHttpdInstallConfFile', "$filename$suffix");
}

sub setData
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdSetData', $data) and return 1;

	$data = {} if ref $data ne 'HASH';
	$self->{'data'} = $data;

	$self->{'hooksManager'}->trigger('afterHttpdSetData');
}

sub getRunningUser
{
	my $self = shift;

	$self::apacheConfig{'APACHE_USER'};
}

sub getRunningGroup
{
	my $self = shift;

	$self::apacheConfig{'APACHE_GROUP'};
}

sub removeSection
{
	my $self = shift;
	my $sectionName = shift;
	my $cfgTpl = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdRemoveSection', $sectionName, $cfgTpl);

	my $bTag = "# SECTION $sectionName BEGIN.\n";
	my $eTag = "# SECTION $sectionName END.\n";

	debug("Removing useless section: $sectionName");

	$$cfgTpl = replaceBloc($bTag, $eTag, '', $$cfgTpl, undef);

	$self->{'hooksManager'}->trigger('afterHttpdRemoveSection', $sectionName, $cfgTpl);

	0;
}

sub addUser
{
	my $self = shift;
	my $data = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddUser') and return 1;

	my $hDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my ($rs, $stdout, $stderr);

	my $errmsg = {
		'USER' => 'You must supply user name!',
		'BWLIMIT' => 'You must supply a bandwidth limit!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless exists $data->{$_};
		return 1 unless exists $data->{$_};
	}

	$self->{'data'} = $data;

	########################## START MOD CBAND SECTION ##############################

	$rs |= iMSCP::File->new(
			filename => "$self->{'cfgDir'}/00_modcband.conf"
	)->copyFile(
		"$self->{'bkpDir'}/00_modcband.conf." . time
	) if (-f "$self->{'cfgDir'}/00_modcband.conf");

	my $filename = (
		-f "$self->{'wrkDir'}/00_modcband.conf"
			? "$self->{'wrkDir'}/00_modcband.conf" : "$self->{'cfgDir'}/00_modcband.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content	= $file->get();

	unless($content){
		error("Cannot read $filename");
		$rs = 1;
	} else {
		my $bTag = "## SECTION {USER} BEGIN.\n";
		my $eTag = "## SECTION {USER} END.\n";
		my $bUTag = "## SECTION $data->{'USER'} BEGIN.\n";
		my $eUTag = "## SECTION $data->{'USER'} END.\n";

		my $entry	= getBloc($bTag, $eTag, $content);
		chomp($entry);
		$entry =~ s/#//g;

		$content = replaceBloc($bUTag, $eUTag, '', $content, undef);
		chomp($content);

		$self->{'data'}->{'BWLIMIT_DISABLED'} = ($data->{'BWLIMIT'} ? '' : '#');

		$entry = $self->buildConf($bTag.$entry.$eTag);
		$content = replaceBloc($bTag, $eTag, $entry, $content, 'yes');

		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_modcband.conf");
		$file->set($content);
		$rs = 1 if $file->save();

		$rs |= $self->installConfFile('00_modcband.conf');
		$rs |= $self->enableSite('00_modcband.conf');

		unless( -f "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"){
			$rs	|=	iMSCP::File->new(
				filename => "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"
			)->save();
		}
	}

	########################### END MOD CBAND SECTION ###############################

	##################### START COMMON FILES IN USER FOLDER #########################

	# ERROR DOCS
	$rs |= execute(
		"$main::imscpConfig{'CMD_CP'} -vnRT $main::imscpConfig{'GUI_ROOT_DIR'}/public/errordocs $hDir/errors",
		\$stdout, \$stderr
	);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs |= setRights(
		"$hDir/errors",
		{
			user => $data->{'USER'},
			group => $apacheGroup,
			filemode => '0640',
			dirmode	 => '0750',
			recursive => 'yes'
		}
	);

	for("$hDir/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}", "$hDir/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}") {
		my $fileH =	iMSCP::File->new('filename' => $_);
		$rs |= $fileH->save() unless( -f $_);
		$rs |= $fileH->mode(0640);
	}

	###################### END COMMON FILES IN USER FOLDER ##########################

	$self->{'restart'} = 'yes';

	$rs |= $self->{'hooksManager'}->trigger('beforeHttpdAddUser');

	$rs;
}

sub delUser
{
	my $self = shift;
	my $data = shift;
	my $hDir = $data->{'HOME_DIR'};
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdDelUser') and return 1;

	my $errmsg = { 'USER' => 'You must supply user name!' };

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless exists $data->{$_};
		return 1 unless exists $data->{$_};
	}

	$self->{'data'} = $data;

	########################## START MOD CBAND SECTION ##############################

	$rs |= iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/00_modcband.conf"
	)->copyFile(
		"$self->{'bkpDir'}/00_modcband.conf." . time
	) if (-f "$self->{'cfgDir'}/00_modcband.conf");

	my $filename = (
		-f "$self->{'wrkDir'}/00_modcband.conf"
			? "$self->{'wrkDir'}/00_modcband.conf" : "$self->{'cfgDir'}/00_modcband.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content	= $file->get();

	unless($content){
		error("Can not read $filename");
		$rs = 1;
	} else {
		my $bUTag = "## SECTION $data->{'USER'} BEGIN.\n";
		my $eUTag = "## SECTION $data->{'USER'} END.\n";

		$content = replaceBloc($bUTag, $eUTag, '', $content, undef);

		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_modcband.conf");
		$file->set($content);

		$rs |= $file->save();
		$rs |= $self->installConfFile('00_modcband.conf');
		$rs |= $self->enableSite('00_modcband.conf');

		if( -f "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"){
			$rs |= iMSCP::File->new(
				filename => "$self::apacheConfig{'SCOREBOARDS_DIR'}/$data->{'USER'}"
			)->delFile();
		}
	}
	########################### END MOD CBAND SECTION ###############################

	$self->{'restart'} = 'yes';

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelUser');

	$rs;
}

sub addDmn
{
	my $self = shift;
	my $data = shift;

	$self->{'mode'} = 'dmn';

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$self->{'hooksManager'}->trigger('beforeHttpdAddDmn') and return 1;

	$self->{'data'} = $data;

	my $rs = $self->addCfg($data);
	$rs |= $self->addFiles($data) if $data->{'FORWARD'} eq 'no';

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddDmn');

	$rs;
}

sub addCfg
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;
	my $certPath = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";
	my $certFile = "$certPath/$data->{'DMN_NAME'}.pem";

	$self->{'hooksManager'}->trigger('beforeHttpdAddCfg') and return 1;

	$self->{'data'} = $data;

	# Disable and backup Apache sites if any
	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf"){
		$rs |= $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";

		$rs |= iMSCP::File->new(
			'filename' => "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"
		)->copyFile(
			"$self->{'bkpDir'}/$_." . time
		) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
	}

	# Remove previous Apache sites if any
	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}_ssl.conf"
	){
		$rs |= iMSCP::File->new('filename' => $_)->delFile() if -f $_;
	}

	# Build Apache vhost files - Begin
	my %configs;
	$configs{"$data->{'DMN_NAME'}.conf"} = { 'redirect' => 'domain_redirect.tpl', 'normal' => 'domain.tpl'};

	if($data->{'have_cert'}) {
		$configs{"$data->{'DMN_NAME'}_ssl.conf"} = {
			'redirect' => 'domain_redirect_ssl.tpl',
			'normal' => 'domain_ssl.tpl'
		};

		$self->{'data'}->{'CERT'} = $certFile;
	}

	for(keys %configs) {
		# Schedule deletion of useless sections if needed
		if($data->{'FORWARD'} eq 'no') {

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('suexec', @_) }
			) and return 1;

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('cgi_support', @_) }
			) and return 1 unless ($data->{'have_cgi'} eq 'yes');

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_enabled', @_) }
			) and return 1 unless ($data->{'have_php'} eq 'yes');

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_disabled', @_) }
			) and return 1 if ($data->{'have_php'} eq 'yes');

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('fcgid', @_) }
			) and return 1;

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('fastcgi', @_) }
			) and return 1;

			$self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->removeSection('php_fpm', @_) }
			) and return 1;

		}

		$rs |= $self->buildConfFile(
			(
				$data->{'FORWARD'} eq 'no'
					? "$self->{'tplDir'}/" . $configs{$_}->{'normal'}
					: "$self->{'tplDir'}/" . $configs{$_}->{'redirect'}
			),
			{ destination => "$self->{'wrkDir'}/$_" }
		);

		$rs |= $self->installConfFile($_);
	}
	# Build Apache vhost files - End

	# Build and install custom Apache configuration file
	$rs |=	$self->buildConfFile(
		"$self->{'tplDir'}/custom.conf.tpl",
		{ 'destination' => "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf" }
	) unless (-f "$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf");

	# Enable all Apache sites
	$rs |= $self->enableSite($_) for keys %configs;

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddCfg');

	$rs;
}

sub dmnFolders
{
	my $self = shift;
	my $data = shift;
	my $hDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my $newHtdocs = -d "$hDir/htdocs";
	my ($rs, $stdout, $stderr);

	my @folders = (
		["$hDir", $data->{'USER'}, $apacheGroup, 0710],
		["$hDir/htdocs", $data->{'USER'}, $apacheGroup, 0750],
		["$hDir/cgi-bin", $data->{'USER'}, $data->{'GROUP'}, 0751],
		["$hDir/phptmp", $data->{'USER'}, $data->{'GROUP'}, 0770]
	);

	$self->{'hooksManager'}->trigger('beforeHttpdDmnFolders', \@folders);

	push(@folders, ["$hDir/errors", $data->{'USER'}, $apacheGroup, 0710]) if $self->{'mode'} eq 'dmn';

	$self->{'hooksManager'}->trigger('afterHttpdDmnFolders', \@folders);

	@folders;
}

sub addFiles
{
	my $self = shift;
	my $data = shift;
	my $hDir = $data->{'HOME_DIR'};
	my $rootUser = $main::imscpConfig{'ROOT_USER'};
	my $rootGroup = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheGroup = $self::apacheConfig{'APACHE_GROUP'};
	my $newHtdocs = -d "$hDir/htdocs";
	my ($rs, $stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforeHttpdAddFiles') and return 1;

	for ($self->dmnFolders($data)){
		$rs |= iMSCP::Dir->new('dirname' => $_->[0])->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
		);
	}

	unless ($newHtdocs){
		my $sourceDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/domain_default_page";
		my $dstDir = "$hDir/htdocs/";
		my $fileSource =
		my $destFile = "$hDir/htdocs/index.html";

		$rs |= execute("cp -vnRT $sourceDir $dstDir", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $stderr;

		$rs |= $self->buildConfFile($fileSource, { 'destination' => $destFile });
		$rs |= setRights(
			$dstDir,
			{
				'user' => $data->{'USER'},
				'group' => $apacheGroup,
				'filemode' => '0640',
				'dirmode' => '0750',
				'recursive' => 'yes'
			}
		);
	}

	my $sourceDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/domain_disable_page";
	my $dstDir = "$hDir/domain_disable_page";
	my $fileSource =
	my $destFile = "$hDir/domain_disable_page/index.html";

	$rs |= execute("cp -vnRT $sourceDir $dstDir", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;

	$rs |= $self->buildConfFile($fileSource, { 'destination' => $destFile });

	$rs |= setRights(
		"$hDir/cgi-bin",
		{ 'user' => $data->{'USER'}, 'group' => $data->{'GROUP'}, 'recursive' => 'yes' }
	);

	$rs |= setRights(
		"$hDir/domain_disable_page",
		{ 'user' => $rootUser, 'group' => $apacheGroup, 'filemode' => '0640', 'dirmode' => '0710', 'recursive' => 'yes' }
	);

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddFiles');

	$rs;
}

sub delDmn
{
	my $self = shift;
	my $data = shift;

	error('You must supply domain name!') unless $data->{'DMN_NAME'};
	return 1 unless $data->{'DMN_NAME'};

	$self->{'hooksManager'}->trigger('beforeHttpdDelDmn') and return 1;

	my $rs = 0;

	for("$data->{'DMN_NAME'}.conf", "$data->{'DMN_NAME'}_ssl.conf") {
		$rs |= $self->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
	}

	for(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self::apacheConfig{'APACHE_SITES_DIR'}/$data->{'DMN_NAME'}_ssl.conf",
		"$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}/$data->{'DMN_NAME'}.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}.conf",
		"$self->{'wrkDir'}/$data->{'DMN_NAME'}_ssl.conf",
	){
		$rs |= iMSCP::File->new('filename' => $_)->delFile() if -f $_;
	}

	$rs |= iMSCP::Dir->new('dirname' => $data->{'HOME_DIR'})->remove() if -d $data->{'HOME_DIR'};

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelDmn');

	$rs;
}

sub disableDmn
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $errmsg = {
		'DMN_NAME' => 'You must supply domain name!',
		'DMN_IP' => 'You must supply ip for domain!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$self->{'hooksManager'}->trigger('beforeHttpdDisableDmn') and return 1;

	$self->{'data'} = $data;

	iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/$data->{'DMN_NAME'}.conf"
	)->copyFile(
		"$self->{'bkpDir'}/$data->{'DMN_NAME'}.conf" . time
	) if (-f "$self->{'cfgDir'}/$data->{'DMN_NAME'}.conf");

	$rs |= $self->buildConfFile(
		"$self->{'tplDir'}/domain_disabled.tpl",
		{ 'destination' => "$self->{'wrkDir'}/$data->{'DMN_NAME'}.conf" }
	);

	$rs |= $self->installConfFile("$data->{'DMN_NAME'}.conf");

	$rs |= $self->enableSite("$data->{'DMN_NAME'}.conf");
	return $rs if $rs;

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$self->{'hooksManager'}->trigger('afterHttpdDisableDmn');

	0;
}

sub addSub
{
	my $self = shift;
	my $data = shift;

	$self->{'mode'}	= 'sub';

	my $errmsg = {
		'DMN_NAME' => 'You must supply subdomain name!',
		'DMN_IP' => 'You must supply ip for subdomain!'
	};

	$self->{'hooksManager'}->trigger('beforeHttpdAddSub') and return 1;

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$self->{'data'} = $data;

	my $rs = $self->addCfg($data);
	$rs |= $self->addFiles($data) if $data->{'FORWARD'} eq 'no';

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddSub');

	$rs;
}

sub delSub
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDelSub') and return 1;

	my $rs = $self->delDmn(@_);

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelSub');

	$rs;
}

sub disableSub
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdDisableSub') and return 1;

	my $rs = $self->disableDmn(@_);

	$rs |= $self->{'hooksManager'}->trigger('beforeHttpdDisableSub');

	$rs;
}

sub addHtuser
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdAddHtuser') and return 1;

	my $fileName = $self::apacheConfig{'HTACCESS_USERS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTUSER_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;

	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//img;
	$fileContent .= "$data->{'HTUSER_NAME'}:$data->{'HTUSER_PASS'}\n";

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddHtuser');

	$rs;
}

sub delHtuser
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdDelHtuser') and return 1;

	my $fileName = $self::apacheConfig{'HTACCESS_USERS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTUSER_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;
	$fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//img;

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelHtuser');

	$rs;
}

sub addHtgroup
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdAddHtgroup') and return 1;

	my $fileName = $self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTGROUP_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//img;
	$fileContent .= "$data->{'HTGROUP_NAME'}:$data->{'HTGROUP_USERS'}\n";

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddHtgroup');

	$rs;
}

sub delHtgroup
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdDelHtgroup') and return 1;

	my $fileName = $self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'};
	my $filePath = "$main::imscpConfig{'USER_HOME_DIR'}/$data->{'HTGROUP_DMN'}/$fileName";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent	= $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;
	$fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//img;

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelHtgroup');

	$rs;
}

sub addHtaccess
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdAddHtaccess') and return 1;

	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if !$fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";
	my $tag	 = "\t\tAuthType $data->{'AUTH_TYPE'}\n\t\tAuthName \"$data->{'AUTH_NAME'}\"\n".
		"\t\tAuthUserFile $fileUser\n";

	if($data->{'HTUSERS'} eq ''){
		$tag .=	"\t\tAuthGroupFile $fileGroup\n\t\tRequire group $data->{'HTGROUPS'}\n";
	} else {
		$tag .=	"\t\tRequire user $data->{'HTUSERS'}\n";
	}

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent, undef);
	$fileContent = $bTag . $tag . $eTag . $fileContent;

	$rs |= $fileH->set($fileContent);
	$rs |= $fileH->save();
	$rs |= $fileH->mode(0644);
	$rs |= $fileH->owner($data->{'USER'}, $data->{'GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddHtaccess');

	$rs;
}

sub delHtaccess
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdDelHtaccess') and return 1;

	my $fileUser = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_USERS_FILE_NAME'}";
	my $fileGroup = "$data->{'HOME_PATH'}/$self::apacheConfig{'HTACCESS_GROUPS_FILE_NAME'}";
	my $filePath = "$data->{'AUTH_PATH'}/.htaccess";
	my $fileH = iMSCP::File->new('filename' => $filePath);
	my $fileContent = $fileH->get() if -f $filePath;
	$fileContent = '' if ! $fileContent;

	my $bTag = "\t\t### START i-MSCP PROTECTION ###\n";
	my $eTag = "\t\t### END i-MSCP PROTECTION ###\n";

	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent, undef);

	if($fileContent ne '') {
		$rs |= $fileH->set($fileContent);
		$rs |= $fileH->save();
		$rs |= $fileH->mode(0644);
		$rs |= $fileH->owner($data->{'USER'}, $data->{'GROUP'});
	} else {
		$rs |= $fileH->delFile() if -f $filePath;
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelHtaccess');

	$rs;
}

sub addIps
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdAddIps') and return 1;

	unless ($data->{'IPS'} && ref $data->{'IPS'} eq 'ARRAY') {
		error("You must provide ip list");
		return 1;
	}

	$rs |= iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/00_nameserver.conf"
	)->copyFile(
		"$self->{'bkpDir'}/00_nameserver.conf." . time
	) if (-f "$self->{'cfgDir'}/00_nameserver.conf");

	my $filename = (
		-f "$self->{'wrkDir'}/00_nameserver.conf" ?
			"$self->{'wrkDir'}/00_nameserver.conf" : "$self->{'cfgDir'}/00_nameserver.conf"
	);

	my $file = iMSCP::File->new('filename' => $filename);
	my $content = $file->get();
	$content =~ s/NameVirtualHost[^\n]+\n//gi;

	$content.= "NameVirtualHost $_:443\n" for @{$data->{'SSLIPS'}};
	$content.= "NameVirtualHost $_:80\n" for @{$data->{'IPS'}};

	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_nameserver.conf");
	$file->set($content);
	$file->save() and return 1;

	$rs = $self->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	$rs |= $self->enableSite('00_nameserver.conf');

	$self->{'restart'} = 'yes';
	delete $self->{'data'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdAddIps');

	$rs;
}

sub getTraffic
{
	my $self = shift;
	my $who = shift;
	my $traff = 0;
	my $trfDir = "$self::apacheConfig{'APACHE_LOG_DIR'}/traff";
	my ($rv, $rs, $stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforeHttpdGetTraffic') and return 0;

	unless($self->{'logDb'}) {
		$self->{'logDb'} = 1;

		$rs = execute("$main::imscpConfig{'CMD_PS'} -o pid,args -C 'imscp-apache-logger'", \$stdout, \$stderr);
		error($stderr) if $stderr;

		my $rv = iMSCP::Dir->new('dirname' => $trfDir)->moveDir("$trfDir.old") if -d $trfDir;
		if($rv){
			delete $self->{'logDb'};
			return 0;
		}

		if($rs || ! $stdout){
			error('imscp-apache-logger is not running') unless $stderr;
		} else {
			while($stdout =~ m/^\s{0,}(\d+)(?!.*error)/mg){
				$rs = execute("kill -s HUP $1", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr;
			}
		}
	}

	if(-d "$trfDir.old" && -f "$trfDir.old/$who-traf.log"){
		my $content = iMSCP::File->new('filename' => "$trfDir.old/$who-traf.log")->get();
		if($content){
			my @lines = split("\n", $content);
			$traff += $_ foreach @lines;
		} else {
			error("Cannot read $trfDir.old/$who-traf.log");
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdGetTraffic') and return 0;

	$traff;
}

sub delOldLogs
{
	my $self = shift;
	my $rs = 0;
	my $logDir = $self::apacheConfig{'APACHE_LOG_DIR'};
	my $bLogDir = $self::apacheConfig{'APACHE_BACKUP_LOG_DIR'};
	my $uLogDir = $self::apacheConfig{'APACHE_USERS_LOG_DIR'};
	my ($stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforeHttpdDelOldLogs') and return 1;

	for ($logDir, $bLogDir, $uLogDir){
		my $cmd = "nice -n 19 find $_ -maxdepth 1 -type f -name '*.log*' -mtime +365 -exec rm -v {} \\;";
		$rs |= execute($cmd, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
		error("Error while executing $cmd.\nReturned value is $rs") if !$stderr && $rs;
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelOldLogs');

	$rs;
}

sub delTmp
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforeHttpdDelTmp') and return 1;

	# panel sessions gc (since we are not using default session path)
	if(-d "/var/www/imscp/gui/data/sessions"){
		my $cmd = '[ -x /usr/lib/php5/maxlifetime ] && [ -d /var/www/imscp/gui/data/sessions ] && find /var/www/imscp/gui/data/sessions/ -type f -cmin +$(/usr/lib/php5/maxlifetime) -delete';
		$rs |= execute($cmd, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Error while executing $cmd.\nReturned value is $rs") if ! $stderr && $rs;
	}

	# Note: Customer session files are removed by distro cron task

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdDelTmp');

	$rs;
}

END
{
	my $endCode = $?;
	my $self = Servers::httpd::apache_itk->new();
	my $rs = 0;
	my $trfDir = "$self::apacheConfig{'APACHE_LOG_DIR'}/traff";

	if($self->{'start'} && $self->{'start'} eq 'yes'){
		$rs = $self->start();
	} elsif($self->{'restart'} && $self->{'restart'} eq 'yes') {
		$rs = $self->restart();
	}

	$rs |= iMSCP::Dir->new('dirname' => "$trfDir.old")->remove() if -d "$trfDir.old";

	$? = $endCode || $rs;
}

1;
