#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_fcgi::installer - i-MSCP Apache FCGI Server implementation

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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_fcgi::installer;

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
use File::Basename;
use Servers::httpd::apache_fcgi;
use version;
use Net::LibIDN qw/idn_to_ascii/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache FCGI Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks()

 Register setup hook functions

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	my $rs = $hooksManager->trigger('beforeHttpdRegisterSetupHooks', $hooksManager, 'apache_fcgi');
	return $rs if $rs;

	# Add installer dialog in setup dialog stack
	$rs = $hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askCgiModule(@_) }); 0; }
	);
	return $rs if $rs;

	$rs = $hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askForPhpIniLevel(@_) }); 0; }
	);
	return $rs if $rs;

	# Fix error_reporting value into the database
	$rs = $hooksManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
	return $rs if $rs;

	$hooksManager->trigger('afterHttpdRegisterSetupHooks', $hooksManager, 'apache_fcgi');
}

=item askCgiModule($dialog)

 Ask user the fastCGI Apache module to use

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askCgiModule($$)
{
	my ($self, $dialog) = @_;

	my $rs = 0;
	my $cgiModule = main::setupGetQuestion('PHP_FASTCGI') || $self->{'config'}->{'PHP_FASTCGI'} || '';

	if($main::reconfigure ~~ ['httpd', 'servers', 'all', 'forced'] || $cgiModule !~ /^fcgid|fastcgi$/) {
		($rs, $cgiModule) = $dialog->radiolist(
			"\nPlease, select the fastCGI Apache module you want use:",
			['fcgid', 'fastcgi'],
			$cgiModule ne 'fastcgi' ? 'fcgid' : 'fastcgi'
		);
	}

	$self->{'config'}->{'PHP_FASTCGI'} = $cgiModule if $rs != 30;

	$rs;
}

=item askForPhpIniLevel($dialog)

 Ask user for PHP INI level to use

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askForPhpIniLevel($$)
{
	my ($self, $dialog) = @_;

	my $rs = 0;
	my $phpiniLevel = main::setupGetQuestion('INI_LEVEL') || $self->{'config'}->{'INI_LEVEL'} || '';

	if(
		$main::reconfigure ~~ ['httpd', 'php', 'servers', 'all', 'forced'] ||
		not $phpiniLevel ~~ ['per_user', 'per_domain', 'per_site']
	) {
		$phpiniLevel =~ s/_/ /g;

		($rs, $phpiniLevel) = $dialog->radiolist(
"
\\Z4\\Zb\\ZuPHP INI Level\\Zn

Please, choose the PHP INI level you want use for PHP. Available levels are:

\\Z4Per user:\\Zn Each customer will have only one php.ini file
\\Z4Per domain:\\Zn Each domain / domain alias will have its own php.ini file
\\Z4Per site:\\Zn Each site will have its own php.ini file

",
			['per user', 'per domain', 'per site'],
			$phpiniLevel ne 'per site' && $phpiniLevel ne 'per domain' ? 'per user' : $phpiniLevel
		);
	}

	if($rs != 30) {
		$phpiniLevel =~ s/ /_/g;
		$self->{'config'}->{'INI_LEVEL'} = $phpiniLevel;
	}

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstall', 'apache_fcgi');
	return $rs if $rs;

	# Saving all system configuration files if they exists
	for ("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf") {
		$rs = $self->_bkpConfFile($_);
		return $rs if $rs;
	}

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

	$rs = $self->_buildApacheConfFiles();
	return $rs if $rs;

	$rs = $self->_buildMasterVhostFiles();
	return $rs if $rs;

	$rs = $self->_installLogrotate();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$rs = $self->_oldEngineCompatibility();
	return $rs if $rs;

	$rs = $self->setEnginePermissions();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_fcgi');
}

=item setGuiPermissions

 Set gui permissions

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

=item setEnginePermissions

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions()
{
	my $self = shift;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $fcgiDir = $self->{'config'}->{'PHP_STARTER_DIR'};

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdSetEnginePermissions');
	return $rs if $rs;

	$rs = setRights($fcgiDir, { 'user' => $rootUName, 'group' => $rootGName, mode => '0555' });
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

 Called by getInstance(). Initialize instance

 Return Servers::httpd::apache_fcgi::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'httpd'} = Servers::httpd::apache_fcgi->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeHttpdInitInstaller', $self, 'apache_fcgi'
	) and fatal('apache_fcgi - beforeHttpdInitInstaller hook has failed');

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
		'afterHttpdInitInstaller', $self, 'apache_fcgi'
	) and fatal('apache_fcgi - afterHttpdInitInstaller hook has failed');

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

	my $timestamp = time;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile){
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

 Add panel user

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
		'
			SELECT
				`admin_sys_name`, `admin_sys_uid`, `admin_sys_gname`
			FROM
				`admin`
			WHERE
				`admin_type` = ? AND `created_by` = ?
			LIMIT 1
		',
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

	my $adminSysName = $rdata->{(%{$rdata})[0]}->{'admin_sys_name'};
	my $adminSysUid = $rdata->{(%{$rdata})[0]}->{'admin_sys_uid'};
	my $adminSysGname = $rdata->{(%{$rdata})[0]}->{'admin_sys_gname'};

	my ($oldUserName, undef, $userUid, $userGid) = getpwuid($adminSysUid);

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
			escapeShell($adminSysName) # Old username
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
			escapeShell($adminSysGname) # Current group name
		);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr && $rs;
		$rs = execute("@cmd", \$stdout, \$stderr);
		return $rs if $rs;
	}

	# Updating admin.admin_sys_name, admin.admin_sys_uid, admin.admin_sys_gname and admin.admin_sys_gid columns
	$rdata = $database->doQuery(
		'dummy',
		'
			UPDATE
				`admin`
			SET
				`admin_sys_name` = ?, `admin_sys_uid` = ?, `admin_sys_gname` = ?, `admin_sys_gid` = ?
			WHERE
				`admin_type` = ?
		',
		$userName, $userUid, $groupName, $userGid, 'admin'
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

 Create needed directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdMakeDirs');
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $phpdir = $self->{'config'}->{'PHP_STARTER_DIR'};

	for (
		[$self->{'config'}->{'APACHE_USERS_LOG_DIR'}, $rootUName, $rootUName, 0750],
		[$self->{'config'}->{'APACHE_BACKUP_LOG_DIR'}, $rootUName, $rootGName, 0750],
		[$phpdir, $rootUName, $rootGName, 0555],
		["$phpdir/master", $panelUName, $panelGName, 0550],
		["$phpdir/master/php5", $panelUName, $panelGName, 0550]
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

=item _buildFastCgiConfFiles()

 Build FastCGI configuration files

 Return int 0 on success, other on failure

=cut

sub _buildFastCgiConfFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildFastCgiConfFiles');

	# Save current production files if any
	for ('fastcgi_imscp.conf', 'fastcgi_imscp.load', 'fcgid_imscp.conf', 'fcgid_imscp.load') {
		$rs = $self->_bkpConfFile("$self->{'config'}->{'APACHE_MODS_DIR'}/$_");
		return $rs if $rs;
	}

	# Build, store and install new files

	# Set needed data
	$self->{'httpd'}->setData(
		{
			SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
			SYSTEM_USER_MIN_UID => $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
			PHP_STARTER_DIR => $self->{'config'}->{'PHP_STARTER_DIR'},
			PHP_VERSION => $self->{'config'}->{'PHP_VERSION'}
		}
	);

	# fastcgi_imscp.conf / fcgid_imscp.conf
	for ('fastcgi', 'fcgid') {
		# Load template from /etc/imscp/apache directory

		$rs = $self->{'httpd'}->buildConfFile("$self->{'apacheCfgDir'}/${_}_imscp.conf", {});
		return $rs if $rs;

		my $file = iMSCP::File->new('filename' => "$self->{'apacheWrkDir'}/${_}_imscp.conf");

		$rs = $file->copyFile($self->{'config'}->{'APACHE_MODS_DIR'});
		return $rs if $rs;

		next if(! -f "$self->{'config'}->{'APACHE_MODS_DIR'}/$_.load");

		# Load system configuration file
		$file = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_MODS_DIR'}/$_.load");

		my $cfgTpl = $file->get();
		unless(defined $cfgTpl) {
			error("Unable to read $file->{'filename'}");
			return 1;
		}

		# Build new configuration file and store it in working directory
		$file = iMSCP::File->new('filename' => "$self->{'apacheWrkDir'}/${_}_imscp.load");

		$cfgTpl = "<IfModule !mod_$_.c>\n" . $cfgTpl . "</IfModule>\n";

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		# Store new file
		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Install new file in production directory
		$rs = $file->copyFile($self->{'config'}->{'APACHE_MODS_DIR'});
		return $rs if $rs;
	}

	# Disable/Enable Apache modules

	my @toDisableModules = (
		'fastcgi', 'fcgid', 'php4', 'php5', 'php5_cgi', 'php5filter', 'php_fpm_imscp',
		($self->{'config'}->{'PHP_FASTCGI'} eq 'fastcgi' ? 'fcgid_imscp' : 'fastcgi_imscp')
	);

	my @toEnableModules = (
		'actions',
		$self->{'config'}->{'PHP_FASTCGI'} eq 'fastcgi' ? 'fastcgi_imscp' : 'fcgid_imscp',
	);

	if((version->new("v$self->{'config'}->{'APACHE_VERSION'}") >= version->new('v2.4.0'))) {
		push (@toDisableModules, ('mpm_event', 'mpm_itk', 'mpm_prefork'));
		push (@toEnableModules, 'mpm_worker', 'authz_groupfile');
	}

	for(@toDisableModules) {
		$rs = $self->{'httpd'}->disableMod($_) if -f "$self->{'config'}->{'APACHE_MODS_DIR'}/$_.load";
		return $rs if $rs;
	}

	$rs = $self->{'httpd'}->enableMod("@toEnableModules");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildFastCgiConfFiles');
}

=item _buildPhpConfFiles()

 Build PHP configuration files

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildPhpConfFiles');
	return $rs if $rs;

	my ($cfgTpl, $file);
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/fcgi";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	my $timestamp = time;

	# Backup any current file
	for ('php5-fcgid-starter', 'php5-fastcgi-starter', 'php5/php.ini', 'php5/browscap.ini') {
		if(-f "$self->{'config'}->{'PHP_STARTER_DIR'}/master/$_") {
			my (undef, $name) = split('/');
			$name = $_ if ! defined $name;

			my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'PHP_STARTER_DIR'}/master/$_");
			$rs = $file->copyFile("$bkpDir/master.$name.$timestamp");
			return $rs if $rs;
		}
	}

	# Build fcgi wrapper

	# Set needed data
	$self->{'httpd'}->setData(
		{
			PHP_STARTER_DIR => $self->{'config'}->{'PHP_STARTER_DIR'},
			PHP_VERSION => $self->{'config'}->{'PHP_VERSION'},
			PHP5_FASTCGI_BIN => $self->{'config'}->{'PHP5_FASTCGI_BIN'},
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			DOMAIN_NAME => 'master'
		}
	);

	my $panelUname = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $wrkFcgidStarter = "$wrkDir/master.php5-fcgid-starter";
	my $prodFcgidStarter = "$self->{'config'}->{'PHP_STARTER_DIR'}/master/php5-fcgid-starter";

	my $wrkFastCgiStarter = "$wrkDir/master.php5-fastcgi-starter";
	my $prodFastCgiStarter = "$self->{'config'}->{'PHP_STARTER_DIR'}/master/php5-fastcgi-starter";

	if($self->{'config'}->{'PHP_FASTCGI'} eq 'fcgid') { # PHP5 Starter script (fcgid)
		if(-f $wrkFastCgiStarter) {
			$rs = iMSCP::File->new('filename' => $wrkFastCgiStarter)->delFile();
			return $rs if $rs;
		}

		if(-f $prodFastCgiStarter) {
			$rs = iMSCP::File->new('filename' => $prodFastCgiStarter)->delFile();
			return $rs if $rs;
		}

		$rs = $self->{'httpd'}->buildConfFile(
			"$cfgDir/parts/master/php5-fcgid-starter.tpl",
			{},
			{ 'destination' => $wrkFcgidStarter, 'mode' => 0550, 'user' => $panelUname, 'group' => $panelGName }
		);
		return $rs if $rs;

		# Install new file in production directory
		$rs = iMSCP::File->new('filename' => $wrkFcgidStarter)->copyFile($prodFcgidStarter);
		return $rs if $rs;
	 } elsif($self->{'config'}->{'PHP_FASTCGI'} eq 'fastcgi') { # PHP5 Starter script (fastcgi)
		if(-f $wrkFcgidStarter) {
			$rs = iMSCP::File->new('filename' => $wrkFcgidStarter)->delFile();
			return $rs if $rs;
		}

		if(-f $prodFcgidStarter) {
			$rs = iMSCP::File->new('filename' => $prodFcgidStarter)->delFile();
			return $rs if $rs;
		}

		$rs = $self->{'httpd'}->buildConfFile(
			"$cfgDir/parts/master/php5-fastcgi-starter.tpl",
			{},
			{ 'destination' => $wrkFastCgiStarter, 'mode' => 0550, 'user' => $panelUname, 'group' => $panelGName }
		);
		return $rs if $rs;

		# Install new file in production directory
		$rs = iMSCP::File->new('filename' => $wrkFastCgiStarter)->copyFile($prodFastCgiStarter);
		return $rs if $rs;
	} else {
		error("Unknown fastCGI module: $self->{'config'}->{'PHP_FASTCGI'}");
		return 1;
	}

	# Build php.ini file

	# Set needed data
	$self->{'httpd'}->setData(
		{
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			DOMAIN => $main::imscpConfig{'BASE_SERVER_VHOST'},
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			RKHUNTER_LOG => $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG => $main::imscpConfig{'CHKROOTKIT_LOG'},
			OTHER_ROOTKIT_LOG => ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '')
				? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : '',
			PHP_TIMEZONE => $main::imscpConfig{'PHP_TIMEZONE'},
			PHP_STARTER_DIR => $self->{'config'}->{'PHP_STARTER_DIR'}
		}
	);

	# Build file using template from fcgi/parts/master/php5
	$rs = $self->{'httpd'}->buildConfFile(
		"$cfgDir/parts/master/php5/php.ini",
		{},
		{ 'destination' => "$wrkDir/master.php.ini", 'mode' => 0440, 'user' => $panelUname, 'group' => $panelGName }
	);
	return $rs if $rs;

	# Install new file in production directory
	$rs = iMSCP::File->new(
		'filename' => "$wrkDir/master.php.ini"
	)->copyFile(
		"$self->{'config'}->{'PHP_STARTER_DIR'}/master/php5/php.ini"
	);
	return $rs if $rs;

	# PHP Browser Capabilities support file

	# Store new file in working directory
	$rs = iMSCP::File->new('filename' => "$cfgDir/parts/master/php5/browscap.ini")->copyFile("$wrkDir/browscap.ini");
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$wrkDir/browscap.ini");

	$rs = $file->mode(0440);
	return $rs if $rs;

	$rs = $file->owner($panelUname, $panelGName);
	return $rs if $rs;

	# Install new file in production directory
	$rs = $file->copyFile("$self->{'config'}->{'PHP_STARTER_DIR'}/master/php5/browscap.ini");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

=item _buildApacheConfFiles()

 Build Apache configuration files

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildApacheConfFiles');
	return $rs if $rs;

	# Backup, build, store and install ports.conf file if exists

	if(-f "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf") {

		# Load file
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

	if(-f "$self->{'apacheWrkDir'}/00_nameserver.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'apacheWrkDir'}/00_nameserver.conf"
		)->copyFile("$self->{'apacheBkpDir'}/00_nameserver.conf." . time);
		return $rs if $rs;
	}

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

	# Build new file
	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'apacheCfgDir'}/00_nameserver.conf",
		{},
		{ 'destination' => "$self->{'apacheWrkDir'}/00_nameserver.conf" }
	);
	return $rs if $rs;

	# Install new file in production directory
	my $file = iMSCP::File->new('filename' => "$self->{'apacheWrkDir'}/00_nameserver.conf");
	$rs = $file->copyFile($self->{'config'}->{'APACHE_SITES_DIR'});
	return $rs if $rs;

	# Enable required apache modules
	$rs = $self->{'httpd'}->enableMod('cgid proxy proxy_http rewrite ssl suexec');
	return $rs if $rs;

	# Enbale 00_nameserver.conf file
	$rs = $self->{'httpd'}->enableSite('00_nameserver.conf');
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

=item _buildMasterVhostFiles()

 Build Master vhost files.

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
			SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
			SYSTEM_USER_MIN_UID => $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
			PEAR_DIR => $main::imscpConfig{'PEAD_DIR'},
			GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
			SERVER_HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'},
			PHP_STARTER_DIR => $self->{'config'}->{'PHP_STARTER_DIR'},
			PHP_VERSION => $self->{'config'}->{'PHP_VERSION'},
			AUTHZ_ALLOW_ALL => (version->new("v$self->{'config'}->{'APACHE_VERSION'}") >= version->new('v2.4.0'))
				? 'Require all granted' : "Order allow,deny\n    Allow from all"
		}
	);

	# Build 00_master.conf file

	# Schedule deletion of useless itk sections
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('itk', @_) }
	);
	return $rs if $rs;

	if($self->{'config'}->{'PHP_FASTCGI'} eq 'fastcgi') {
		# Schedule deletion of useless fcgid section
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fcgid', @_) }
		);
		return $rs if $rs;
	} else {
		# Schedule deletion of useless fastcgi section
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fastcgi', @_) }
		);
		return $rs if $rs;
	}

	# Schedule deletion of useless php_fpm sections
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('php_fpm', @_) }
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

	# Build file using apache/00_master.conf template
	$rs = $self->{'httpd'}->buildConfFile("$self->{'apacheCfgDir'}/00_master.conf", {});
	return $rs if $rs;

	# Install new file in production directory
	$rs = iMSCP::File->new(
		'filename' => "$self->{'apacheWrkDir'}/00_master.conf"
	)->copyFile(
		"$self->{'config'}->{'APACHE_SITES_DIR'}/00_master.conf"
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->enableSite('00_master.conf');
	return $rs if $rs;

	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes') {
		# Build 00_master_ssl.conf file

		# Schedule deletion of useless itk sections
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('itk', @_) }
		);
		return $rs if $rs;

		if($self->{'config'}->{'PHP_FASTCGI'} eq 'fastcgi') {
			# Schedule deletion of useless fcgid section
			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fcgid', @_) }
			);
			return $rs if $rs;
		} else {
			# Schedule deletion of useless fastcgi section
			$rs = $self->{'hooksManager'}->register(
				'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fastcgi', @_) }
			);
			return $rs if $rs;
		}

		# Schedule deletion of useless php_fpm sections
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('php_fpm', @_) }
		);
		return $rs if $rs;

		$rs = $self->{'httpd'}->buildConfFile("$self->{'apacheCfgDir'}/00_master_ssl.conf", {});
		return $rs if $rs;

		iMSCP::File->new(
			'filename' => "$self->{'apacheWrkDir'}/00_master_ssl.conf"
		)->copyFile(
			"$self->{'config'}->{'APACHE_SITES_DIR'}/00_master_ssl.conf"
		);
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

 Build and install Apache logrotate file

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
	my $self = shift;

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

=item _saveConf()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

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

 Fix PHP error reporting values according current PHP version

 Return int 0 on success, other on failure

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

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
