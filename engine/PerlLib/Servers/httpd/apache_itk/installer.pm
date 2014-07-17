#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_itk::installer - i-MSCP Apache2/ITK Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l;declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_itk::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::TemplateParser;
use File::Basename;
use Servers::httpd::apache_itk;
use version;
use Net::LibIDN qw/idn_to_ascii/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache2/ITK Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks()

 Register setup hook functions

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks
{
	my ($self, $hooksManager) = @_;

	my $rs = $hooksManager->trigger('beforeHttpdRegisterSetupHooks', $hooksManager, 'apache_itk');
	return $rs if $rs;

	# Fix error_reporting value into the database
	$rs = $hooksManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
	return $rs if $rs;

	$hooksManager->trigger('afterHttpdRegisterSetupHooks', $hooksManager, 'apache_itk');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstall', 'apache_itk');
	return $rs if $rs;

	# Saving all system configuration files if they exists
	for ("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf") {
		$rs = $self->_bkpConfFile($_);
		return $rs if $rs;
	}

	$rs = $self->_setApacheVersion();
	return $rs if $rs;

	$rs = $self->_makeDirs();
	return $rs if $rs;

	$rs = $self->_buildPhpConfFiles();
	return $rs if $rs;

	$rs = $self->_buildApacheConfFiles();
	return $rs if $rs;

	$rs = $self->_installLogrotate();
	return $rs if $rs;

	$rs = $self->_setupVlogger();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$self->_oldEngineCompatibility();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_itk');
}

=item setEnginePermissions

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions()
{
	my $self = $_[0];

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdSetEnginePermissions');
	return $rs if $rs;

	$rs = setRights('/usr/local/sbin/vlogger', { 'user' => $rootUName, 'group' => $rootGName, mode => '0750' });
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdSetEnginePermissions');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance

 Return Servers::httpd::apache_itk::installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'httpd'} = Servers::httpd::apache_itk->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeHttpdInitInstaller', $self, 'apache_itk'
	) and fatal('apache_itk - beforeHttpdInitInstaller hook has failed');

	$self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";

	$self->{'config'} = $self->{'httpd'}->{'config'};

	my $oldConf = "$self->{'apacheCfgDir'}/apache.old.data";

	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %oldConfig) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $oldConfig{$_};
			}
		}
	}

	$self->{'hooksManager'}->trigger(
		'afterHttpdInitInstaller', $self, 'apache_itk'
	) and fatal('apache_itk - afterHttpdInitInstaller hook has failed');

	$self;
}

=item _bkpConfFile($cfgFile)

 Backup the given file

 Param string $cfgFile File to backup
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile($$)
{
	my ($self, $cfgFile) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $cfgFile);
	return $rs if $rs;

	my $timestamp = time;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new('filename' => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(! -f "$self->{'apacheBkpDir'}/$filename$suffix.system") {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$filename$suffix.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$filename$suffix.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', $cfgFile);
}

=item _setApacheVersion

 Set Apache version

 Return in 0 on success, other on failure

=cut

sub _setApacheVersion()
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute("$self->{'config'}->{'CMD_APACHE2CTL'} -v", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to find Apache version') if $rs && ! $stderr;
	return $rs if $rs;

	if($stdout =~ m%Apache/([\d.]+)%) {
		$self->{'config'}->{'HTTPD_VERSION'} = $1;
		debug("Apache version set to: $1");
	} else {
		error('Unable to parse Apache version from Apache version string');
		return 1;
	}

	0;
}

=item _makeDirs()

 Create needed directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdMakeDirs');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	for (
		[$self->{'config'}->{'HTTPD_LOG_DIR'}, $rootUName, $rootUName, 0755],
		["$self->{'config'}->{'HTTPD_LOG_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}", $rootUName, $rootUName, 0750],
	) {
		$rs = iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3]}
		);
		return $rs if $rs;
	}

	# Todo move this statement into the httpd apache_fcgid server implementation (uninstaller) when it will be ready for
	# call when switching to another httpd server implementation.
	$rs = iMSCP::Dir->new('dirname' => $self->{'config'}->{'PHP_STARTER_DIR'})->remove();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdMakeDirs');
}

=item _buildPhpConfFiles()

 Build PHP configuration files

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildPhpConfFiles');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	# Build php.ini file

	# Set needed data
	$self->{'httpd'}->setData(
		{
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			PHP_TIMEZONE => $main::imscpConfig{'PHP_TIMEZONE'}
		}
	);

	# Build file using template from apache/parts/php5.itk.ini
	$rs = $self->{'httpd'}->buildConfFile(
		$self->{'apacheCfgDir'} . '/parts/php5.itk.ini',
		{ },
		{
			'destination' => "$self->{'apacheWrkDir'}/php.ini",
			'mode' => 0644,
			'user' => $rootUName,
			'group' => $rootGName
		}
	);
	return $rs if $rs;

	# Install new file in production directory
	$rs = iMSCP::File->new(
		'filename' => "$self->{'apacheWrkDir'}/php.ini"
	)->copyFile(
		$self->{'config'}->{"ITK_PHP5_PATH"}
	);
	return $rs if $rs;

	# Disable/Enable Apache modules

	# Transitional: fastcgi_imscp
	my @toDisableModules = (
		'fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php_fpm_imscp', 'php4', 'php5_cgi', 'suexec'
	);
	my @toEnableModules = ('php5');

	if(qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.0')) {
		# MPM management is a mess in Jessie. We so disable all and re-enable only needed MPM
		push (@toDisableModules, ('mpm_itk', 'mpm_prefork', 'mpm_event', 'mpm_prefork', 'mpm_worker'));
		push(@toEnableModules, 'mpm_itk', 'authz_groupfile');
	}

	for(@toDisableModules) {
		$rs = $self->{'httpd'}->disableModules($_) if -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_.load";
		return $rs if $rs;
	}

	$rs = $self->{'httpd'}->enableModules("@toEnableModules");
	return $rs if $rs;

	# Quick fix (Ubuntu PHP modules not enabled after fresh installation)
	if(-x '/usr/sbin/php5enmod' && -d '/etc/php5/mods-available') {
		for('imap', 'mcrypt') {
			if(! -s "/etc/php5/conf.d/20-$_.ini" && -f "/etc/php5/conf.d/$_.ini") {
				my($stdout, $stderr);
				$rs = execute("$main::imscpConfig{'CMD_MV'} /etc/php5/conf.d/$_.ini /etc/php5/mods-available/$_.ini");
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}

			if(-f "/etc/php5/mods-available/$_.ini") {
				my($stdout, $stderr);
				$rs = execute("/usr/sbin/php5enmod $_", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

=item _buildApacheConfFiles()

 Build Apache configuration files

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildApacheConfFiles');
	return $rs if $rs;

	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf") {
		# Load template

		my $cfgTpl;
		$rs = $self->{'hooksManager'}->trigger('onLoadTemplate', 'apache_itk', 'ports.conf', \$cfgTpl, {});
		return $rs if $rs;

		unless(defined $cfgTpl) {
			$cfgTpl = iMSCP::File->new('filename' => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf")->get();
			unless(defined $cfgTpl) {
				error("Unable to read $self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf");
				return 1;
			}
		}

		# Build file

		$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, 'ports.conf');
		return $rs if $rs;

		$cfgTpl =~ s/^(NameVirtualHost\s+\*:80)/#$1/gmi;

		$rs = $self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, 'ports.conf');
		return $rs if $rs;

		# Store file

		my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf");

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Turn off default log
	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/other-vhosts-access-log") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/other-vhosts-access-log"
		)->delFile();
		return $rs if $rs;
	}

	# Remove default log
	if(-f "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log"
		)->delFile();
		return $rs if $rs;
	}

	# Backup, build, store and install 00_nameserver.conf file

	if(-f "$self->{'apacheWrkDir'}/00_nameserver.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'apacheWrkDir'}/00_nameserver.conf"
		)->copyFile("$self->{'apacheBkpDir'}/00_nameserver.conf." . time);
		return $rs if $rs;
	}

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.2.12')) {
		$pipeSyntax .= '|';
	}

	my $apache24 = (qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.0'));

	# Set needed data
	$self->{'httpd'}->setData(
		{
			HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
			HTTPD_ROOT_DIR => $self->{'config'}->{'HTTPD_ROOT_DIR'},
			AUTHZ_DENY_ALL => $apache24 ? 'Require all denied' : 'Deny from all',
			AUTHZ_ALLOW_ALL => $apache24 ? 'Require all granted' : 'Allow from all',
			CMD_VLOGGER => $self->{'config'}->{'CMD_VLOGGER'},
			PIPE => $pipeSyntax,
			VLOGGER_CONF => "$self->{'apacheWrkDir'}/vlogger.conf"
		}
	);

	# Build new file
	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'apacheCfgDir'}/00_nameserver.conf",
		{},
		{ 'destination' => "$self->{'apacheWrkDir'}/00_nameserver.conf" }
	);
	return $rs if $rs;

	# Install new file in production directory
	my $file = iMSCP::File->new('filename' => "$self->{'apacheWrkDir'}/00_nameserver.conf");
	$rs = $file->copyFile($self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'});
	return $rs if $rs;

	# Enable required apache modules
	$rs = $self->{'httpd'}->enableModules('cgid proxy proxy_http rewrite ssl');
	return $rs if $rs;

	# Enbale 00_nameserver.conf file
	$rs = $self->{'httpd'}->enableSites('00_nameserver.conf');
	return $rs if $rs;

	# Disable defaults sites if any
	#
	# default, default-ssl (Debian < Jessie)
	# 000-default.conf, default-ssl.conf' : (Debian >= Jessie)
	for('default', 'default-ssl', '000-default.conf', 'default-ssl.conf') {
		$rs = $self->{'httpd'}->disableSites($_) if -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

=item _installLogrotate()

 Build and install Apache logrotate file

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'apache2');
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile('logrotate.conf', {});
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		'logrotate.conf', { 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'apache2');
}

=item _setupVlogger()

 Setup vlogger

 Return int 0 on success, other on failure

=cut

sub _setupVlogger
{
	my $self = $_[0];

	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	# vlogger is chrooted so we force connection to MySQL server through TCP
	$dbHost = ($dbHost eq 'localhost') ? '127.0.0.1' : $dbHost;
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	my $dbName = main::setupGetQuestion('DATABASE_NAME');
	my $tableName = 'httpd_vlogger';
	my $dbUser = 'vlogger_user';
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	$dbUserHost = ($dbUserHost eq '127.0.0.1') ? 'localhost' : $dbUserHost;

	my @allowedChr = map { chr } (0x21..0x5b, 0x5d..0x7e);
	my $dbPassword = '';
	$dbPassword .= $allowedChr[rand @allowedChr] for 1..16;

	# Getting SQL connection with full privileges
	my ($db, $errStr) = main::setupGetSqlConnect($dbName);
	fatal("Unable to connect to SQL Server: $errStr") if ! $db;

	# Creating database table
	if(-f "$self->{'apacheCfgDir'}/vlogger.sql") {
		my $rs = main::setupImportSqlSchema($db, "$self->{'apacheCfgDir'}/vlogger.sql");
		return $rs if $rs;
	} else {
		error("File $self->{'apacheCfgDir'}/vlogger.sql not found.");
		return 1;
	}

	# Removing any old SQL user (including privileges)
	for($dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, '127.0.0.1') {
		next if ! $_;

		if(main::setupDeleteSqlUser($dbUser, $_)) {
			error("Unable to remove SQL user or one of its privileges");
			return 1;
		}
	}

	my @dbUserHosts = ($dbUserHost);

	if($dbUserHost ~~ ['localhost', '127.0.0.1']) {
		push @dbUserHosts, ($dbUserHost eq '127.0.0.1') ? 'localhost' : '127.0.0.1';
	}

	for(@dbUserHosts) {
		# Adding new SQL user with needed privileges
		my $rs = $db->doQuery(
			'dummy',
			"GRANT SELECT, INSERT, UPDATE ON `$main::imscpConfig{'DATABASE_NAME'}`.`$tableName` TO ?@? IDENTIFIED BY ?",
			$dbUser,
			$_,
			$dbPassword
		);
		unless(ref $rs eq 'HASH') {
			error("Unable to add privileges: $rs");
			return 1;
		}
	}

	# Building configuration file
	$self->{'httpd'}->setData(
		{
			DATABASE_NAME => $dbName,
			DATABASE_HOST => $dbHost,
			DATABASE_PORT => $dbPort,
			DATABASE_USER => $dbUser,
			DATABASE_PASSWORD => $dbPassword
		}
	);
	$self->{'httpd'}->buildConfFile(
		"$self->{'apacheCfgDir'}/vlogger.conf.tpl", {}, { 'destination' => "$self->{'apacheWrkDir'}/vlogger.conf" }
	);
}

=item _saveConf()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = $_[0];

	my $file = iMSCP::File->new('filename' => "$self->{'apacheCfgDir'}/apache.data");

	my $rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'apacheCfgDir'}/apache.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', \$cfg, "$self->{'apacheCfgDir'}/apache.data");
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'apacheCfgDir'}/apache.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', "$self->{'apacheCfgDir'}/apache.data");
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdOldEngineCompatibility');
	return $rs if $rs;

	for('imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf') {
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_") {
			$rs = $self->{'httpd'}->disableSites($_);
			return $rs if $rs;

			$rs = iMSCP::File->new('filename' => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_")->delFile();
			return $rs if $rs;
		}
	}

	# Removing directories no longer needed (since 1.1.0)
	for(
		$self->{'config'}->{'APACHE_BACKUP_LOG_DIR'}, $self->{'config'}->{'HTTPD_USERS_LOG_DIR'},
		$self->{'config'}->{'APACHE_SCOREBOARDS_DIR'}
	) {
		$rs = iMSCP::Dir->new('dirname' => $_)->remove();
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdOldEngineCompatibility');
}

=item _fixPhpErrorReportingValues()

 Fix PHP error reporting values according current PHP version

 Return int 0 on success, other on failure

=cut

sub _fixPhpErrorReportingValues
{
	my $self = $_[0];

	my ($database, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	if(! $database) {
		error("Unable to connect to SQL Server: $errStr");
		return 1;
	}

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_PHP'} -v", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $phpVersion = $1 if $stdout =~ /^PHP\s([\d.]{3})/;

	if(defined $phpVersion) {
		if($phpVersion == 5.3) {
			$phpVersion = 5.3;
		} elsif($phpVersion >= 5.4) {
			$phpVersion = 5.4
		} else {
			error("Unsupported PHP version: $phpVersion");
			return 1;
		}
	} else {
		error('Unable to find PHP version');
		return 1;
	}

	my %errorReportingValues = (
		'5.3' => {
			32759 => 30711, # E_ALL & ~E_NOTICE
			32767 => 32767, # E_ALL | E_STRICT
			24575 => 22527  # E_ALL & ~E_DEPRECATED
		},
		'5.4' => {
			30711 => 32759, # E_ALL & ~E_NOTICE
			32767 => 32767, # E_ALL | E_STRICT
			22527 => 24575  # E_ALL & ~E_DEPRECATED
		}
	);

	for(keys %{$errorReportingValues{$phpVersion}}) {
		my $from = $_;
		my $to = $errorReportingValues{$phpVersion}->{$_};

		$rs = $database->doQuery(
			'dummy',
			"UPDATE `config` SET `value` = ? WHERE `name` = 'PHPINI_ERROR_REPORTING' AND `value` = ?",
			$to, $from
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $database->doQuery(
			'dummy', 'UPDATE `php_ini` SET `error_reporting` = ? WHERE `error_reporting` = ?', $to, $from
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}
	}

	0;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
