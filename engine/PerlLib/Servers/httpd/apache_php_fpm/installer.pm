#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm::installer - i-MSCP Apache PHP-FPM Server implementation

=cut

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
# @category    i-MSCPuse iMSCP::Execute;332
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_php_fpm::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use Servers::httpd::apache_php_fpm;
use Net::LibIDN qw/idn_to_ascii/;
use File::Basename;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache PHP-FPM Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks()

 Register setup hook functions.

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	my $rs = $hooksManager->trigger('beforeHttpdRegisterSetupHooks', $hooksManager, 'apache_php_fpm');
	return $rs if $rs;
	
	# Add installer dialog in setup dialog stack
	$rs = $hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askForPhpFpmPoolsLevel(@_) }); 0; }
	);
	return $rs if $rs;

	# Fix error_reporting values into the database
	$rs = $hooksManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
	return $rs if $rs;
	
	$hooksManager->trigger('afterHttpdRegisterSetupHooks', $hooksManager, 'apache_php_fpm');
}

=item askForPhpFpmPoolsLevel($dialog)

 Ask user for PHP FPM pools level to use.

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askForPhpFpmPoolsLevel
{
	my $self = shift;
	my $dialog = shift;

	my $rs = 0;
	my $poolsLevel = main::setupGetQuestion('PHP_FPM_POOLS_LEVEL') ||
		$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_LEVEL'} || '';

	if(
		$main::reconfigure ~~ ['httpd', 'php', 'servers', 'all', 'forced'] ||
		not $poolsLevel ~~ ['per_user', 'per_domain', 'per_site']
	) {
		$poolsLevel =~ s/_/ /g;

		($rs, $poolsLevel) = $dialog->radiolist(
"
\\Z4\\Zb\\ZuPHP FPM Pools Level\\Zn

Please, choose the pools level you want use for PHP. Available levels are:

\\Z4Per user:\\Zn Each customer will have only one pool
\\Z4Per domain:\\Zn Each domain / domain alias will have its own pool
\\Z4Per site:\\Zn Each site will have its own pool

Note: PHP FPM use a global php.ini configuration file but you can override any settings per pool.
",
			['per user', 'per domain', 'per site'],
			$poolsLevel ne 'per site' && $poolsLevel ne 'per domain' ? 'per user' : $poolsLevel
		);
	}

	if($rs != 30) {
		$poolsLevel =~ s/ /_/g;
		$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_LEVEL'} = $poolsLevel;
	}

	$rs;
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_php_fpm');
	return $rs if $rs;

	$rs = $self->_setApacheVersion();
	return $rs if $rs;

	$rs = $self->_addUser();
	return $rs if $rs;

	$rs = $self->_makeDirs();
	return $rs if $rs;

	$rs = $self->_buildFastCgiConfFiles();
	return $rs if $rs;

	$rs = $self->_buildPhpConfFiles();
	return $rs if $rs;

	$rs = $self->_buildMasterPhpFpmPoolFile();
	return $rs if $rs;

	$rs = $self->_buildApacheConfFiles();
	return $rs if $rs;

	$rs = $self->_buildMasterVhostFiles();
	return $rs if $rs;

	$rs = $self->_installLogrotate();
	return $rs if $rs;

	$rs = $self->_installPhpFpmInitScript();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$rs = $self->_oldEngineCompatibility();
	return $rs if $rs;

	$rs = $self->setEnginePermissions();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_php_fpm');
}

=item setGuiPermissions

 Set gui permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $guiRootDir = $main::imscpConfig{'GUI_ROOT_DIR'};

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdSetGuiPermissions');
	return $rs if $rs;

	$rs = setRights(
		$guiRootDir,
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
	return $rs if $rs;

	$rs = setRights(
		"$guiRootDir/themes",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
	return $rs if $rs;

	$rs = setRights(
		"$guiRootDir/data",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', 'recursive' => 1 }
	);
	return $rs if $rs;

	$rs = setRights(
		"$guiRootDir/data/persistent",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 1 }
	);
	return $rs if $rs;

	$rs = setRights("$guiRootDir/data", { 'user' => $panelUName, 'group' => $panelGName, 'mode' => '0550' });
	return $rs if $rs;

	$rs = setRights(
		"$guiRootDir/i18n",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', 'recursive' => 1 }
	);
	return $rs if $rs;

	$rs = setRights(
		"$guiRootDir/plugins",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 1 }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdSetGuiPermissions');
}

sub setEnginePermissions()
{
	my $self = shift;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdSetEnginePermissions');
	return $rs if $rs;

	# eg. /var/www/imscp/engine/imscp-apache-logger
	# FIXME: This is a quick fix
	$rs = setRights(
		"$main::imscpConfig{'ROOT_DIR'}/engine/imscp-apache-logger", {
			'user' => $rootUName, 'group' => $rootGName, 'mode' => '0750'
		}
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdSetEnginePermissions');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return Servers::httpd::apache_php_fpm::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'httpd'} = Servers::httpd::apache_php_fpm->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeHttpdInitInstaller', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - beforeHttpdInitInstaller hook has failed');

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

	$self->{'phpfpmCfgDir'} = $self->{'httpd'}->{'phpfpmCfgDir'};
	$self->{'phpfpmBkpDir'} = "$self->{'phpfpmCfgDir'}/backup";
	$self->{'phpfpmWrkDir'} = "$self->{'phpfpmCfgDir'}/working";

	$self->{'phpfpmConfig'} = $self->{'httpd'}->{'phpfpmConfig'};

	$oldConf = "$self->{'phpfpmCfgDir'}/phpfpm.old.data";

	if(-f $oldConf) {
		tie my %phpfpmOldConfig, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %phpfpmOldConfig) {
			if(exists $self->{'phpfpmConfig'}->{$_}) {
				$self->{'phpfpmConfig'}->{$_} = $phpfpmOldConfig{$_};
			}
		}
	}

	$self->{'hooksManager'}->trigger(
		'afterHttpdInitInstaller', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - afterHttpdInitInstaller hook has failed');

	$self;
}

=item _setApacheVersion

 Set Apache version

 Return in 0 on success, other on failure

=cut

sub _setApacheVersion()
{
	my $self = shift;

	my ($stdout, $stderr);
	my $rs = execute("$self->{'config'}->{'CMD_HTTPD_CTL'} -v", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to find Apache version') if $rs && ! $stderr;
	return $rs if $rs;

	if($stdout =~ m%Apache/([\d.]+)%) {
		$self->{'config'}->{'APACHE_VERSION'} = $1;
		debug("Apache version set to: $1");
	} else {
		error('Unable to parse Apache version from Apache version string');
		return 1;
	}

	0;
}

=item _addUser()

 Add panel user.

 Return int 0 on success, other on failure

=cut

sub _addUser
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdAddUser');
	return $rs if $rs;

	my $userName =
	my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my ($database, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	if(! $database) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	my $rdata = $database->doQuery(
		'admin_sys_uid',
		'SELECT `admin_sys_uid`, `admin_sys_gid` FROM `admin` WHERE `admin_type` = ? AND `created_by` = ? LIMIT 1',
		'admin',
		'0'
	);

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	} elsif(! %{$rdata}) {
		error('Unable to find admin user in database');
		return 1;
	}

	my ($oldUserName, undef, $userUid, $userGid) = getpwuid($rdata->{(%{$rdata})[0]}->{'admin_sys_uid'});

	if(! $oldUserName || $userUid == 0) {
		# Creating i-MSCP Master Web user
		$rs = iMSCP::SystemUser->new(
			'username' => $userName,
			'comment' => 'i-MSCP Master Web User',
			'home' => $main::imscpConfig{'GUI_ROOT_DIR'},
			'skipCreateHome' => 1
		)->addSystemUser();
		return $rs if $rs;

		$userUid = getpwnam($userName);
		$userGid = getgrnam($groupName);
	} else {
		# Modifying existents i-MSCP Master Web user
		my @cmd = (
			"$main::imscpConfig{'CMD_PKILL'} -KILL -u", escapeShell($oldUserName), ';',
			"$main::imscpConfig{'CMD_USERMOD'}",
			'-c', escapeShell('i-MSCP Master Web User'), # New comment
			'-d', escapeShell($main::imscpConfig{'GUI_ROOT_DIR'}), # New homedir
			'-l', escapeShell($userName), # New login
			'-m', # Move current homedir content to new homedir
			escapeShell($oldUserName) # Old username
		);
		my($stdout, $stderr);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr && $rs;
		return $rs if $rs;

		# Modifying existents i-MSCP Master Web group
		@cmd = (
			$main::imscpConfig{'CMD_GROUPMOD'},
			'-n', escapeShell($groupName), # New group name
			escapeShell(getgrgid($userGid)) # Current group name
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Updating admin.admin_sys_uid and admin.admin_sys_gid columns
	$rdata = $database->doQuery(
		'update',
		'UPDATE `admin` SET `admin_sys_uid` = ?, `admin_sys_gid` = ? WHERE `admin_type` = ?',
		$userUid, $userGid, 'admin'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# Adding i-MSCP Master Web user into i-MSCP group
	$rs = iMSCP::SystemUser->new('username' => $userName)->addToGroup($main::imscpConfig{'IMSCP_GROUP'});
	return $rs if $rs;

	# Adding Apache user in i-MSCP Master Web group
	$rs = iMSCP::SystemUser->new('username' => $self->{'config'}->{'APACHE_USER'})->addToGroup($groupName);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddUser');
}

=item _makeDirs()

 Create needed directories.

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdMakeDirs');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	for (
		[$self->{'config'}->{'APACHE_USERS_LOG_DIR'}, $rootUName, $rootUName, 0750],
		[$self->{'config'}->{'APACHE_BACKUP_LOG_DIR'}, $rootUName, $rootGName, 0750]
	) {
		$rs = iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3]}
		);
		return $rs if $rs;
	}

	$rs = iMSCP::Dir->new('dirname' => $self->{'config'}->{'PHP_STARTER_DIR'})->remove();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdMakeDirs');
}

=item _buildFastCgiConfFiles()

 Build FastCGI configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildFastCgiConfFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildFastCgiConfFiles');
	return $rs if $rs;

	# Backup, build, store and install the php_fpm_imscp.conf file

	# Set needed data
	$self->{'httpd'}->setData({ PHP_VERSION => $self->{'config'}->{'PHP_VERSION'} });

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'config'}->{'APACHE_MODS_DIR'}/php_fpm_imscp.conf");
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php_fpm_imscp.conf",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php_fpm_imscp.conf" },
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php_fpm_imscp.conf",
		{ 'destination' => "$self->{'config'}->{'APACHE_MODS_DIR'}/php_fpm_imscp.conf" }
	);
	return $rs if $rs;

	# Backup, build, store and install the php_fpm_imscp.load file

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'config'}->{'APACHE_MODS_DIR'}/php_fpm_imscp.load");
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php_fpm_imscp.load",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php_fpm_imscp.load" },
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php_fpm_imscp.load",
		{ 'destination' => "$self->{'config'}->{'APACHE_MODS_DIR'}/php_fpm_imscp.load" }
	);
	return $rs if $rs;

	# Disable/Enable Apache modules

	my @toDisableModules = (
		'fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php4', 'php5', 'php5_cgi', 'php5filter'
	);
	my @toEnableModules = ('actions', 'php_fpm_imscp', 'suexec');

	if((version->new("v$self->{'config'}->{'APACHE_VERSION'}") >= version->new('v2.4.0'))) {
		push (@toDisableModules, ('mpm_event', 'mpm_itk', 'mpm_prefork'));
		push (@toEnableModules, 'mpm_worker', 'authz_groupfile');
	}

	for(@toDisableModules) {
		$rs = $self->{'httpd'}->disableMod($_) if -f "$self->{'config'}->{'APACHE_MODS_DIR'}/$_.load";
		return $rs if $rs;
	}

	# Enable needed Apache modules
	$rs = $self->{'httpd'}->enableMod("@toEnableModules");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildFastCgiConfFiles');
}

=item _buildPhpConfFiles()

 Build PHP configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildPhpConfFiles');
	return $rs if $rs;

	# Backup, build, store and install php.ini file

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini", '', 1);
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	# Set needed data
	$self->{'httpd'}->setData(
		{
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			PHP_TIMEZONE => $main::imscpConfig{'PHP_TIMEZONE'}
		}
	);

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/parts/php$self->{'config'}->{'PHP_VERSION'}.ini",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php.ini", 'mode' => 0644, 'user' => $rootUName, 'group' => $rootGName }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php.ini", { 'destination' => "$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini" }
	);
	return $rs if $rs;

	# Backup, build, store and install main php-fpm.conf configuration file

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php-fpm.conf", '', 1);
	return $rs if $rs;

	# Set needed data
	$self->{'httpd'}->setData({ PHP_VERSION => $self->{'config'}->{'PHP_VERSION'} });

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php-fpm.conf", { 'destination' => "$self->{'phpfpmWrkDir'}/php-fpm.conf" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php-fpm.conf",
		{ 'destination' => "$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php-fpm.conf" }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

=item _buildMasterPhpFpmPoolFile()

 Build Master PHP FPM pool file.

 Return int 0 on success, other on failure

=cut

sub _buildMasterPhpFpmPoolFile
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeBuildMasterPhpFpmPoolFile');
	return $rs if $rs;

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/master.conf");
	return $rs if $rs;

	$self->{'httpd'}->setData(
		{
			PHP_VERSION => $self->{'config'}->{'PHP_VERSION'},
			BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
			SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
			SYSTEM_USER_MIN_UID => $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			RKHUNTER_LOG => $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG => $main::imscpConfig{'CHKROOTKIT_LOG'},
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			OTHER_ROOTKIT_LOG => ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '')
				? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : ''
		}
	);

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/parts/master/pool.conf",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/master.conf" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/master.conf",
		{ 'destination' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/master.conf" }
	);
	return $rs if $rs;

	# Disable default pool configuration file if exists
	if(-f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf") {
		my $file = iMSCP::File->new('filename' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf");
		$rs = $file->moveFile("$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled");
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterBuildMasterPhpFpmPoolFile');
}

=item _buildApacheConfFiles

 Build main Apache configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildApacheConfFiles');
	return $rs if $rs;

	# Backup, build, store and install ports.conf file if exists

	if(-f "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf") {

		$rs = $self->{'httpd'}->apacheBkpConfFile("$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf", '', 1);
		return $rs if $rs;

		# Loading the file
		my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf");
		my $rdata = $file->get();
		unless(defined $rdata) {
			error("Unable to read $self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf");
			return 1;
		}

		$rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildConfFile', \$rdata, 'ports.conf');
		return $rs if $rs;

		$rdata =~ s/^(NameVirtualHost\s+\*:80)/#$1/gmi;

		$rs = $self->{'hooksManager'}->trigger('afterHttpdBuildConfFile', \$rdata, 'ports.conf');
		return $rs if $rs;

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Backup, build, store and install 00_nameserver.conf file

	$rs = $self->{'httpd'}->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_nameserver.conf");
	return $rs if $rs;

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(version->new("v$self->{'config'}->{'APACHE_VERSION'}") >= version->new('v2.2.12')) {
		$pipeSyntax .= '|';
	}

	# Set needed data
	$self->{'httpd'}->setData(
		{
			BASE_SERVER_VHOST_PREFIX => $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
			BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
			ROOT_DIR => $main::imscpConfig{'ROOT_DIR'},
			PIPE => $pipeSyntax
		}
	);

	$rs = $self->{'httpd'}->buildConfFile('00_nameserver.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	# Enabling required apache modules
	$rs = $self->{'httpd'}->enableMod('cgid rewrite proxy proxy_http ssl');
	return $rs if $rs;

	# Enabling 00_nameserver.conf file
	$rs = $self->{'httpd'}->enableSite('00_nameserver.conf');
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

=item _buildMasterVhostFiles()

 Build Master vhost files (panel vhost files).

 Return int 0 on success, other on failure

=cut

sub _buildMasterVhostFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildMasterVhostFiles');
	return $rs if $rs;

	my $adminEmailAddress = $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'};
	my ($user, $domain) = split /@/, $adminEmailAddress;

	$adminEmailAddress = "$user@" . idn_to_ascii($domain, 'utf-8');

	# Set needed data
	$self->{'httpd'}->setData(
		{
			BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DEFAULT_ADMIN_ADDRESS => $adminEmailAddress,
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			ROOT_DIR => $main::imscpConfig{'ROOT_DIR'},
			PEAR_DIR => $main::imscpConfig{'PEAD_DIR'},
			GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
			SERVER_HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'},
			PHP_VERSION => $self->{'config'}->{'PHP_VERSION'},
			AUTHZ_ALLOW_ALL => (version->new("v$self->{'config'}->{'APACHE_VERSION'}") >= version->new('v2.4.0'))
				? 'Require all granted' : "Order allow,deny\n    Allow from all"
		}
	);

	# Build 00_master.conf file

	$rs = $self->{'httpd'}->apacheBkpConfFile("$self->{'config'}->{'APACHE_SITES_DIR'}/00_master.conf");
	return $rs if $rs;

	# Schedule deletion of useless suexec section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('suexec', @_) }
	);
	return $rs if $rs;

	# Schedule deletion of useless fcgid section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fcgid', @_) }
	);
	return $rs if $rs;

	# Schedule deletion of useless fastcgi section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fastcgi', @_) }
	);
	return $rs if $rs;

	# Schedule deletion of useless itk section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('itk', @_) }
	);
	return $rs if $rs;

	# Force HTTPS if needed
	if($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'https://') {
		$rs = $self->{'hooksManager'}->register(
			'afterHttpdBuildConfFile',
			sub {
				my $fileContent = shift;
				my $fileName = shift;

				if($fileName eq '00_master.conf') {
					require iMSCP::Templator;
					iMSCP::Templator->import();

					my $customTagBegin = "    # SECTION custom BEGIN.\n";
					my $customTagEnding = "    # SECTION custom END.\n";
					my $customBlock =
						$customTagBegin .
						getBloc($customTagBegin, $customTagEnding, $$fileContent) .
						"    RewriteEngine On\n" .
						"    RewriteCond %{HTTPS} off\n" .
						"    RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]\n" .
						$customTagEnding;

					$$fileContent = replaceBloc($customTagBegin, $customTagEnding, $customBlock, $$fileContent);
				}

				0;
			}
		);
		return $rs if $rs;
	}

	$rs = $self->{'httpd'}->buildConfFile('00_master.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile('00_master.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->enableSite('00_master.conf');
	return $rs if $rs;

	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes') {
		# Build 00_master_ssl.conf file

		$rs = $self->{'httpd'}->apacheBkpConfFile("$self->{'config'}->{'APACHE_SITES_DIR'}/00_master_ssl.conf");
		return $rs if $rs;

		# Schedule deletion of useless suexec section
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('suexec', @_) }
		);
		return $rs if $rs;

		# Schedule deletion of useless fcgid section
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fcgid', @_) }
		);
		return $rs if $rs;

		# Schedule deletion of useless fastcgi section
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fastcgi', @_) }
		);
		return $rs if $rs;

		# Schedule deletion of useless itk sections
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('itk', @_) }
		);
		return $rs if $rs;

		$rs = $self->{'httpd'}->buildConfFile('00_master_ssl.conf');
		return $rs if $rs;

		$rs = $self->{'httpd'}->installConfFile('00_master_ssl.conf');
		return $rs if $rs;

		$rs = $self->{'httpd'}->enableSite('00_master_ssl.conf');
		return $rs if $rs;
	} else {
		$rs = $self->{'httpd'}->disableSite(
			'00_master_ssl.conf'
		) if -f "$self->{'config'}->{'APACHE_SITES_DIR'}/00_master_ssl.conf";
		return $rs if $rs;

		for(
			"$self->{'apacheWrkDir'}/00_master_ssl.conf",
			"$self->{'config'}->{'APACHE_SITES_DIR'}/00_master_ssl.conf"
		) {
			$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
			return $rs if $rs;
		}
	}

	# Disable defaults sites if any
	#
	# default, default-ssl (Debian < Jessie)
	# 000-default.conf, default-ssl.conf' : (Debian >= Jessie)
	for('default', 'default-ssl', '000-default.conf', 'default-ssl.conf') {
		$rs = $self->{'httpd'}->disableSite($_) if -f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdBuildMasterVhostFiles');
}

=item _installLogrotate()

 Build and install both Apache and PHP-FPM logrotate files.

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
	my $self = shift;

	# Apache logrotate file

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'apache2');
	return $rs if $rs;

	$rs = $self->{'httpd'}->apacheBkpConfFile("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", '', 1);
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile('logrotate.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		'logrotate.conf',
		{ 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
	);
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'apache2');
	return $rs if $rs;

	# PHP-FPM logrotate file

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'php5-fpm');
	return $rs if $rs;

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm", 'logrotate.', 1);
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/logrotate.conf", {'destination' => "$self->{'phpfpmWrkDir'}/logrotate.conf" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/logrotate.conf",
		{ 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'php5-fpm');
}

=item _installPhpFpmInitScript()

 Install PHP FPM init script.

 Note: We provide our own init script since the one provided in older Debian/Ubuntu versions doesnt provide the
reopen-logs function.

 Return int 0 on success, other on failure

=cut

sub _installPhpFpmInitScript
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallPhpFpmInitScript');
	return $rs if $rs;

	my ($stdout, $stderr);

	if (-f $self->{'phpfpmConfig'}->{'CMD_PHP_FPM'}) {
		my $file = iMSCP::File->new('filename' => $self->{'phpfpmConfig'}->{'CMD_PHP_FPM'});

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0755);
		return $rs if $rs;

		my $service = fileparse($self->{'phpfpmConfig'}->{'CMD_PHP_FPM'});

		$rs = execute("/usr/sbin/update-rc.d -f $service remove", \$stdout, \$stderr);
		debug ($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("/usr/sbin/update-rc.d $service defaults", \$stdout, \$stderr);
		debug ($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		error("File '$self->{'phpfpmConfig'}->{'CMD_PHP_FPM'}' is missing");
		return 1;
	}

	$self->{'hooksManager'}->trigger('afterHttpdInstallPhpFpmInitScript');
}

=item _saveConf()

 Save both i-MSCP apache.data and i-MSCP php-fpm.data configuration files.

 Return int 0 on success, 1 on failure

=cut

sub _saveConf
{
	my $self = shift;
	my $rs = 0;

	my %filesToDir = ( 'apache' => $self->{'apacheCfgDir'}, 'phpfpm' => $self->{'phpfpmCfgDir'} );

	for(keys %filesToDir) {

		my $file = iMSCP::File->new('filename' => "$filesToDir{$_}/$_.data");

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		my $cfg = $file->get();
		unless(defined $cfg) {
			error("Unable to read $filesToDir{$_}/$_.data");
			return 1;
		}

		$rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', \$cfg, "$filesToDir{$_}/$_.data");
		return $rs if $rs;

		$file = iMSCP::File->new('filename' => "$filesToDir{$_}/$_.old.data");

		$rs = $file->set($cfg);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', "$filesToDir{$_}/$_.data");
		return $rs if $rs;
	}

	0;
}

=item _oldEngineCompatibility()

 Remove old files.

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility()
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdOldEngineCompatibility');
	return $rs if $rs;

	for('imscp.conf', '00_modcband.conf') {
		if(-f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_") {
			$rs = $self->{'httpd'}->disableSite($_);
			return $rs if $rs;

			$rs = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_SITES_DIR'}/$_")->delFile();
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterHttpdOldEngineCompatibility');
}

=item _fixPhpErrorReportingValues()

 Fix PHP error_reporting value according PHP version.

 This rustine fix the error_reporting integer values in the iMSCP databse according the PHP version installed on the
system.

 This is an hook function acting on the 'afterSetupCreateDatabase' hook.

 Return int - 0 on success, 1 on failure

=cut

sub _fixPhpErrorReportingValues
{
	my $self = shift;

	my ($database, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	if(! $database) {
		error("Unable to connect to SQL Server: $errStr");
		return 1;
	}

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_PHP'} -v", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $phpVersion = $1 if $stdout =~ /^PHP\s([0-9.]{3})/;

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

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
