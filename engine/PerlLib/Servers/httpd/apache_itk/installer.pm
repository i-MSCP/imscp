#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_itk::installer - i-MSCP Apache2/ITK Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l;declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_itk::installer;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::EventManager;
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

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
	my ($self, $eventManager) = @_;

	# Fix error_reporting value into the database
	$eventManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdInstall', 'apache_itk');
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

	$rs = $self->_buildPhpConfFiles();
	return $rs if $rs;

	$rs = $self->_buildApacheConfFiles();
	return $rs if $rs;

	$rs = $self->_buildMasterVhostFiles();
	return $rs if $rs;

	$rs = $self->_installLogrotate();
	return $rs if $rs;

	$rs = $self->_setupVlogger();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$self->_oldEngineCompatibility();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdInstall', 'apache_itk');
}

=item setGuiPermissions

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = $_[0];

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $guiRootDir = $main::imscpConfig{'GUI_ROOT_DIR'};

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdSetGuiPermissions');
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

	$self->{'eventManager'}->trigger('afterHttpdSetGuiPermissions');
}

=item setEnginePermissions

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = $_[0];

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdSetEnginePermissions');
	return $rs if $rs;

	$rs = setRights('/usr/local/sbin/vlogger', { 'user' => $rootUName, 'group' => $rootGName, mode => '0750' });
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdSetEnginePermissions');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::httpd::apache_itk::installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'httpd'} = Servers::httpd::apache_itk->getInstance();

	$self->{'eventManager'}->trigger(
		'beforeHttpdInitInstaller', $self, 'apache_itk'
	) and fatal('apache_itk - beforeHttpdInitInstaller has failed');

	$self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";

	$self->{'config'} = $self->{'httpd'}->{'config'};

	# Merge old config file with new config file
	my $oldConf = "$self->{'apacheCfgDir'}/apache.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', 'fileName' => $oldConf;

		for(keys %oldConfig) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $oldConfig{$_};
			}
		}
	}

	$self->{'eventManager'}->trigger(
		'afterHttpdInitInstaller', $self, 'apache_itk'
	) and fatal('apache_itk - afterHttpdInitInstaller has failed');

	$self;
}

=item _bkpConfFile($cfgFile)

 Backup the given file

 Param string $cfgFile File to backup
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdBkpConfFile', $cfgFile);
	return $rs if $rs;

	my $timestamp = time;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new('filename' => $cfgFile);
		my $filename = fileparse($cfgFile);

		if(! -f "$self->{'apacheBkpDir'}/$filename.system") {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$filename.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'apacheBkpDir'}/$filename.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBkpConfFile', $cfgFile);
}

=item _setApacheVersion

 Set Apache version

 Return in 0 on success, other on failure

=cut

sub _setApacheVersion
{
	my $self = $_[0];

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
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdAddUser');
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

	$self->{'eventManager'}->trigger('afterHttpdAddUser');
}

=item _makeDirs()

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdMakeDirs');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	for (
		[$self->{'config'}->{'APACHE_LOG_DIR'}, $rootUName, $rootUName, 0755],
		["$self->{'config'}->{'APACHE_LOG_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}", $rootUName, $rootUName, 0750],
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

	$self->{'eventManager'}->trigger('afterHttpdMakeDirs');
}

=item _buildPhpConfFiles()

 Build PHP configuration files

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdBuildPhpConfFiles');
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
		{},
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

	# TODO PHP Browser Capabilities support file

	# Disable/Enable Apache modules

	# Transitional: fastcgi_imscp
	my @toDisableModules = (
		'fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php_fpm_imscp', 'php4', 'php5_cgi', 'suexec'
	);
	my @toEnableModules = ('php5');

	if(qv("v$self->{'config'}->{'APACHE_VERSION'}") >= qv('v2.4.0')) {
		# MPM management is a mess in Jessie. We so disable all and re-enable only needed MPM
		push (@toDisableModules, ('mpm_itk', 'mpm_prefork', 'mpm_event', 'mpm_prefork', 'mpm_worker'));
		push(@toEnableModules, 'mpm_itk', 'authz_groupfile');
	}

	for(@toDisableModules) {
		$rs = $self->{'httpd'}->disableMod($_) if -l "$self->{'config'}->{'APACHE_MODS_ENABLED_DIR'}/$_.load";
		return $rs if $rs;
	}

	for(@toEnableModules) {
		$rs = $self->{'httpd'}->enableMod($_) if -f "$self->{'config'}->{'APACHE_MODS_DIR'}/$_.load";
		return $rs if $rs;
	}

	# Make sure that PHP modules are enabled
	if(-x '/usr/sbin/php5enmod') {
		my($stdout, $stderr);
		$rs = execute('php5enmod gd imap intl json mcrypt mysqlnd/10 mysql mysqli pdo/10 pdo_mysql', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		unless($rs ~~ [0, 2]) {
			error($stderr) if $stderr;
			return $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

=item _buildApacheConfFiles()

 Build Apache configuration files

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdBuildApacheConfFiles');
	return $rs if $rs;

	if(-f "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf") {
		# Load template

		my $cfgTpl;
		$rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'apache_itk', 'ports.conf', \$cfgTpl, {});
		return $rs if $rs;

		unless(defined $cfgTpl) {
			$cfgTpl = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf")->get();
			unless(defined $cfgTpl) {
				error("Unable to read $self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf");
				return 1;
			}
		}

		# Build file

		$rs = $self->{'eventManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, 'ports.conf');
		return $rs if $rs;

		$cfgTpl =~ s/^(NameVirtualHost\s+\*:80)/#$1/gmi;

		$rs = $self->{'eventManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, 'ports.conf');
		return $rs if $rs;

		# Store file

		my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf");

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Turn off default log
	if(-f "$self->{'config'}->{'APACHE_CONF_DIR'}/conf.d/other-vhosts-access-log") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'APACHE_CONF_DIR'}/conf.d/other-vhosts-access-log"
		)->delFile();
		return $rs if $rs;
	}

	# Remove default log
	if(-f "$self->{'config'}->{'APACHE_LOG_DIR'}/other_vhosts_access.log") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'APACHE_LOG_DIR'}/other_vhosts_access.log"
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

	if(qv("v$self->{'config'}->{'APACHE_VERSION'}") >= qv('v2.2.12')) {
		$pipeSyntax .= '|';
	}

	my $apache24 = (qv("v$self->{'config'}->{'APACHE_VERSION'}") >= qv('v2.4.0'));

	# Set needed data
	$self->{'httpd'}->setData(
		{
			APACHE_LOG_DIR => $self->{'config'}->{'APACHE_LOG_DIR'},
			APACHE_ROOT_DIR => $self->{'config'}->{'APACHE_ROOT_DIR'},
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
	$rs = $file->copyFile($self->{'config'}->{'APACHE_SITES_DIR'});
	return $rs if $rs;

	# Enable required apache modules
	$rs = $self->{'httpd'}->enableMod('cgid proxy proxy_http rewrite ssl');
	return $rs if $rs;

	# Enbale 00_nameserver.conf file
	$rs = $self->{'httpd'}->enableSite('00_nameserver.conf');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

=item _buildMasterVhostFiles()

 Build master vhost files

 Return int 0 on success, other on failure

=cut

sub _buildMasterVhostFiles
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdBuildMasterVhostFiles');
	return $rs if $rs;

	my $adminEmailAddress = $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'};
	my ($user, $domain) = split /@/, $adminEmailAddress;

	$adminEmailAddress = "$user@" . idn_to_ascii($domain, 'utf-8');

	$self->{'httpd'}->setData(
		{
			APACHE_LOG_DIR => $self->{'config'}->{'APACHE_LOG_DIR'},
			BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DEFAULT_ADMIN_ADDRESS => $adminEmailAddress,
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
			SYSTEM_USER_MIN_UID => $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			RKHUNTER_LOG => $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG => $main::imscpConfig{'CHKROOTKIT_LOG'},
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			OTHER_ROOTKIT_LOG => ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '')
				? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : '',
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			AUTHZ_ALLOW_ALL => (qv("v$self->{'config'}->{'APACHE_VERSION'}") >= qv('v2.4.0'))
				? 'Require all granted' : 'Allow from all',
			DISTRO_CA_BUNDLE => $main::imscpConfig{'DISTRO_CA_BUNDLE'}
		}
	);

	# Build 00_master.conf file

	# Force HTTPS if needed
	if($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'https://') {
		$rs = $self->{'eventManager'}->register(
			'afterHttpdBuildConf',
			sub {
				my ($cfgTpl, $tplName) = @_;

				if($tplName eq '00_master.conf') {
					$$cfgTpl = replaceBloc(
						"# SECTION custom BEGIN.\n",
						"# SECTION custom END.\n",

						"    # SECTION custom BEGIN.\n" .
						getBloc(
							"# SECTION custom BEGIN.\n",
							"# SECTION custom END.\n",
							$$cfgTpl
						) .
						"    RewriteEngine On\n" .
						"    RewriteRule .* https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]\n" .
						"    # SECTION custom END.\n",
						$$cfgTpl
					);
				}

				0;
			}
		);
		return $rs if $rs;
	}

	# Build file using apache/00_master.conf template
	$rs = $self->{'httpd'}->buildConfFile('00_master.conf', { CGI_SUPPORT => 'no', PHP_SUPPORT => 'yes' });
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

	if($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
		# Build 00_master_ssl.conf file

		$rs = $self->{'httpd'}->buildConfFile('00_master_ssl.conf', { CGI_SUPPORT => 'no', PHP_SUPPORT => 'yes' });
		return $rs if $rs;

		$rs = iMSCP::File->new(
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

	$self->{'eventManager'}->trigger('afterHttpdBuildMasterVhostFiles');
}

=item _installLogrotate()

 Install Apache logrotate file

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdInstallLogrotate', 'apache2');
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile('logrotate.conf', { });
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		'logrotate.conf', { 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
	);
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterHttpdInstallLogrotate', 'apache2');
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
	fatal("Unable to connect to SQL server: $errStr") unless $db;

	# Creating database table
	if(-f "$self->{'apacheCfgDir'}/vlogger.sql") {
		my $rs = main::setupImportSqlSchema($db, "$self->{'apacheCfgDir'}/vlogger.sql");
		return $rs if $rs;
	} else {
		error("File $self->{'apacheCfgDir'}/vlogger.sql not found.");
		return 1;
	}

	# Removing any old SQL user (including privileges)
	for my $host ($dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, '127.0.0.1') {
		next unless $host;

		if(main::setupDeleteSqlUser($dbUser, $host)) {
			error('Unable to remove SQL user or one of its privileges');
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

	iMSCP::File->new(
		'filename' => "$self->{'apacheCfgDir'}/apache.data"
	)->copyFile(
		"$self->{'apacheCfgDir'}/apache.old.data"
	);
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeHttpdOldEngineCompatibility');
	return $rs if $rs;

	for('imscp.conf', '00_modcband.conf') {
		if(-f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_") {
			$rs = $self->{'httpd'}->disableSite($_);
			return $rs if $rs;

			$rs = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_SITES_DIR'}/$_")->delFile();
			return $rs if $rs;
		}
	}

	# Removing directories no longer needed (since 1.1.0)
	for(
		$self->{'config'}->{'APACHE_BACKUP_LOG_DIR'}, $self->{'config'}->{'APACHE_USERS_LOG_DIR'},
		$self->{'config'}->{'APACHE_SCOREBOARDS_DIR'}
	) {
		$rs = iMSCP::Dir->new('dirname' => $_)->remove();
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterHttpdOldEngineCompatibility');
}

=item _fixPhpErrorReportingValues()

 Fix PHP error reporting values according current PHP version

 Return int 0 on success, other on failure

=cut

sub _fixPhpErrorReportingValues
{
	my $self = $_[0];

	my ($database, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	unless($database) {
		error("Unable to connect to SQL server: $errStr");
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
