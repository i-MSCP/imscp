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

package Servers::httpd::apache_itk::installer;

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
use Servers::httpd::apache_itk;
use version;
use Net::LibIDN qw/idn_to_ascii/;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforeHttpdInitInstaller', $self, 'apache_itk');

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

	$self->{'hooksManager'}->trigger('afterHttpdInitInstaller', $self, 'apache_itk');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	# Fix error_reporting value into the database
	$hooksManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
}

sub install
{
	my $self = shift;
	my $rs = 0;

	# Saving all system configuration files if they exists
	for (
		"$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache",
		"$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf"
	) {
		$rs |= $self->bkpConfFile($_);
	}

	$rs |= $self->addUsersAndGroups();
	$rs |= $self->makeDirs();
	$rs |= $self->buildPhpConfFiles();
	$rs |= $self->buildApacheConfFiles();
	$rs |= $self->buildMasterVhostFiles();
	$rs |= $self->installLogrotate();
	$rs |= $self->saveConf();
	$rs |= $self->setGuiPermissions();
	$rs |= $self->oldEngineCompatibility();

	$rs;
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
	my ($rs, $stdout, $stderr);
	my ($database, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	fatal('Unable to connect to SQL Server: $errStr') if ! $database;

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
		error('Unable to retrieve your PHP version');
		return 1;
	}

	0;
}

sub setGuiPermissions
{
	my $self = shift;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheUName = $self::apacheConfig{'APACHE_USER'};
	my $apacheGName = $self::apacheConfig{'APACHE_GROUP'};
	my $ROOT_DIR = $main::imscpConfig{'ROOT_DIR'};
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdSetGuiPermissions');

	$rs |= setRights(
		"$ROOT_DIR/gui/public",
		{ user => $panelUName, group => $apacheGName, dirmode => '0550', filemode => '0440', recursive => 'yes' }
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/themes",
		{ user => $panelUName, group => $apacheGName, dirmode => '0550', filemode => '0440', recursive => 'yes' }
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/library",
		{ user => $panelUName, group => $panelGName, dirmode => '0500', filemode => '0400', recursive => 'yes' }
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/data",
		{ user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 'yes' }
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/data",
		{ user => $panelUName, group => $apacheGName, mode => '0550'}
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/data/ispLogos",
		{ user => $panelUName, group => $apacheGName, dirmode => '0750', filemode => '0640', recursive => 'yes' }
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/i18n",
		{ user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 'yes' }
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/plugins",
		{ user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 'yes' }
	);

	$rs |= setRights(
		"$ROOT_DIR/gui/plugins",
		{ user => $panelUName, group => $apacheGName, mode => '0550' });

	$rs |= setRights(
		"$ROOT_DIR/gui",
		{ user => $panelUName, group => $apacheGName, mode => '0550' }
	);

	$rs |= setRights(
		$ROOT_DIR,
		{ user => $panelUName, group => $apacheGName, mode => '0555' }
	);

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdSetGuiPermissions');

	$rs;
}

sub addUsersAndGroups
{
	my $self = shift;
	my $rs = 0;
	my ($panelGName, $panelUName);

	$self->{'hooksManager'}->trigger('beforeHttpdAddUsersAndGroups') and return 1;

	# Panel group
	$panelGName = Modules::SystemGroup->new();
	$rs = $panelGName->addSystemGroup($main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'});
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

	$self->{'hooksManager'}->trigger('beforeHttpdMakeDirs') and return 1;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheUName = $self::apacheConfig{'APACHE_USER'};
	my $apacheGName = $self::apacheConfig{'APACHE_GROUP'};

	my $rs = 0;

	for (
		[$self::apacheConfig{'APACHE_USERS_LOG_DIR'}, $apacheUName, $apacheGName, 0755],
		[$self::apacheConfig{'APACHE_BACKUP_LOG_DIR'}, $rootUName, $rootGName, 0755]
	) {
		$rs |= iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
		);
	}

	$rs |= iMSCP::Dir->new('dirname' => $self::apacheConfig{'PHP_STARTER_DIR'})->remove()
		if -d $self::apacheConfig{'PHP_STARTER_DIR'};

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdMakeDirs');
}

sub bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $cfgFile) and return 1;

	my $timestamp = time;
	my $rs = 0;

	if(-f $cfgFile){
		my $file = iMSCP::File->new('filename' => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(!-f "$self->{'bkpDir'}/$filename$suffix.system") {
			$rs |= $file->copyFile("$self->{'bkpDir'}/$filename$suffix.system");
		} else {
			$rs |= $file->copyFile("$self->{'bkpDir'}/$filename$suffix.$timestamp");
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $cfgFile);

	$rs;
}

sub saveConf
{
	my $self = shift;

	my $rs = 0;
	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/apache.data");
	my $cfg = $file->get() or return 1;

	$self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', \$cfg, "$self->{'cfgDir'}/apache.data") and return 1;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/apache.old.data");

	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', "$self->{'cfgDir'}/apache.data");

	$rs;
}

sub oldEngineCompatibility
{
	my $self = shift;

	my $httpd = Servers::httpd::apache_itk->new();
	my $rs = 0;

	$self->{'hooksManager'}->trigger('beforeHttpdOldEngineCompatibility') and return 1;

	if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf"){
		$rs |= $httpd->disableSite('imscp.conf');
		$rs |= iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf")->delFile();
	}

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdOldEngineCompatibility');

	$rs;
}

sub buildPhpConfFiles
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildPhpConfFiles') and return 1;

	my $httpd = Servers::httpd::apache_itk->new();
	my $rs = 0;
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	## PHP php.ini file

	# Loading the template from /etc/imscp/apache2/parts/php{version}.itk.ini
	$httpd->setData({ PHP_TIMEZONE => $main::imscpConfig{'PHP_TIMEZONE'} });

	$httpd->buildConfFile(
		$self->{'cfgDir'} . '/parts/php' . $self::apacheConfig{'PHP_VERSION'} . '.itk.ini',
		{ 'destination' => "$self->{'wrkDir'}/php.ini", mode => 0644, user => $rootUName, group => $rootGName }
	);

	# Install the new file
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/php.ini");
	$rs |= $file->copyFile($self::apacheConfig{'ITK_PHP' . $self::apacheConfig{'PHP_VERSION'} . '_PATH'});

	# Disable un-needed apache modules
	for('suexec', 'fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php_fpm_imscp', 'php4') {
		$rs |= $httpd->disableMod($_) if( -e "$self::apacheConfig{'APACHE_MODS_DIR'}/$_.load");
	}

	# Enable needed apache modules
	$rs |= $httpd->enableMod('php5');

	$rs |= $self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');

	$rs;
}

sub buildApacheConfFiles
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildApacheConfFiles') and return 1;

	my $httpd = Servers::httpd::apache_itk->new();

	if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/00_nameserver.conf") {
		iMSCP::File->new(
			filename => "$self::apacheConfig{'APACHE_SITES_DIR'}/00_nameserver.conf"
		)->copyFile("$self->{'bkpDir'}/00_nameserver.conf.". time) and return 1;
	}

	## Building, storage and installation of new file
	if(-f "$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf") {
		# Loading the file
		my $file = iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf");
		my $rdata = $file->get();
		return $rdata if ! $rdata;

		$self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$rdata, 'ports.conf') and return 1;

		$rdata =~ s/^NameVirtualHost \*:80/#NameVirtualHost \*:80/gmi;

		$self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$rdata, 'ports.conf') and return 1;

		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the Shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(`$self::apacheConfig{'CMD_HTTPD_CTL'} -v` =~ m!Apache/([\d.]+)! &&
		version->new($1) >= version->new('2.2.12')) {
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

	$httpd->buildConfFile(
		"$self->{'cfgDir'}/00_nameserver.conf", { 'destination' => "$self->{'wrkDir'}/00_nameserver.conf" }
	) and return 1;

	# Installing the new file in production directory
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_nameserver.conf");
	$file->copyFile($self::apacheConfig{'APACHE_SITES_DIR'}) and return 1;

	# Enable required modules
	$httpd->enableMod('cgi rewrite proxy proxy_http ssl') and return 1;

	# Enable 00_nameserver.conf file
	$httpd->enableSite('00_nameserver.conf') and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

sub buildMasterVhostFiles
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildMasterVhostFiles') and return 1;

	my $httpd = Servers::httpd::apache_itk->new();

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
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			MR_LOCK_FILE => $main::imscpConfig{'MR_LOCK_FILE'},
			RKHUNTER_LOG => $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG => $main::imscpConfig{'CHKROOTKIT_LOG'},
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			OTHER_ROOTKIT_LOG => ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '')
				? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : '',
			GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
			SERVER_HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'}
		}
	);

	# Build 00_master.conf file

	# Schedule useless suexec section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('suexec', @_)}
	) and return 1;

	# Schedule useless fcgid section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('fcgid', @_)}
	) and return 1;

	# Schedule useless fastcgi section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('fastcgi', @_)}
	) and return 1;

	# Schedule useless php_fpm sections deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('php_fpm', @_) }
	) and return 1;

	$httpd->buildConfFile("$self->{'cfgDir'}/00_master.conf") and return 1;

	iMSCP::File->new(
		filename => "$self->{'wrkDir'}/00_master.conf"
	)->copyFile(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/00_master.conf"
	) and return 1;

	# Build 00_master_ssl.conf file

	# Schedule useless suexec section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('suexec', @_) }
	) and return 1;

	# Schedule useless fcgid section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('fcgid', @_) }
	) and return 1;

	# Schedule useless fastcgi section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('fastcgi', @_) }
	) and return 1;

	# Schedule useless php_fpm sections deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $httpd->removeSection('php_fpm', @_) }
	) and return 1;

	$httpd->buildConfFile("$self->{'cfgDir'}/00_master_ssl.conf") and return 1;

	iMSCP::File->new(
		filename => "$self->{'wrkDir'}/00_master_ssl.conf"
	)->copyFile(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/00_master_ssl.conf"
	) and return 1;

	# Enable and disable vhost files
	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes') {
		$httpd->enableSite('00_master.conf 00_master_ssl.conf') and return 1;
	} else {
		$httpd->enableSite('00_master.conf') and return 1;
		$httpd->disableSite('00_master_ssl.conf') and return 1;
	}

	# Disable defaults sites if exists
	$httpd->disableSite('default') and return 1 if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/default";
	$httpd->disableSite('default-ssl') and return 1 if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/default-ssl";

	$self->{'hooksManager'}->trigger('afterHttpdBuildMasterVhostFiles');
}

sub installLogrotate
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'apache2') and return 1;

	my $httpd = Servers::httpd::apache_itk->new();

	my $rs = $httpd->buildConfFile('logrotate.conf');
	return $rs if $rs;

	$rs = $httpd->installConfFile(
		'logrotate.conf', { 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'apache2');
}

1;
