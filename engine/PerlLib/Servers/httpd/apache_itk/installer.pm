#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_itk::installer - i-MSCP Apache FCGI Server implementation

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
use File::Basename;
use Servers::httpd::apache_itk;
use version;
use Net::LibIDN qw/idn_to_ascii/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache ITK Server implementation.

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

	my $rs = $hooksManager->trigger('beforeHttpdRegisterSetupHooks', $hooksManager, 'apache_itk');
	return $rs if $rs;

	# Fix error_reporting value into the database
	$rs = $hooksManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
	return $rs if $rs;

	$hooksManager->trigger('afterHttpdRegisterSetupHooks', $hooksManager, 'apache_itk');
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstall', 'apache_itk');
	return $rs if $rs;

	# Saving all system configuration files if they exists
	for (
		"$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache",
		"$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf"
	) {
		$rs = $self->_bkpConfFile($_);
		return $rs if $rs;
	}

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

	$rs = $self->_saveConf();
	return $rs if $rs;

	$self->_oldEngineCompatibility();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_itk');
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
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', 'recursive' => 1 }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdSetGuiPermissions');
}

=item setEnginePermissions

 Set engine permissions.

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions()
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdSetEnginePermissions');
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdSetEnginePermissions');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return Servers::httpd::apache_itk::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'httpd'} = Servers::httpd::apache_itk->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeHttpdInitInstaller', $self, 'apache_itk'
	) and fatal('apache_itk - beforeHttpdInitInstaller hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self::apacheConfig = $self->{'apacheConfig'};

	my $oldConf = "$self->{'cfgDir'}/apache.old.data";

	if(-f $oldConf) {
		tie %self::apacheOldConfig, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %self::apacheOldConfig) {
			if(exists $self::apacheConfig{$_}) {
				$self::apacheConfig{$_} = $self::apacheOldConfig{$_};
			}
		}
	}

	$self->{'hooksManager'}->trigger(
		'afterHttpdInitInstaller', $self, 'apache_itk'
	) and fatal('apache_itk - afterHttpdInitInstaller hook has failed');

	$self;
}

=item _bkpConfFile($cfgFile)

 Backup the given file.

 Param string $cfgFile File to backup
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile($$)
{
	my $self = shift;
	my $cfgFile = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', $cfgFile);
	return $rs if $rs;

	my $timestamp = time;

	if(-f $cfgFile) {
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

	# Creating panel group
	$rs = iMSCP::SystemGroup->new('groupname' => $groupName)->addSystemGroup();
	return $rs if $rs;

	# Creating panel user
	my $panelUName = iMSCP::SystemUser->new(
		'username' => $userName,
		'comment' => 'iMSCP master virtual user',
		'home' => $main::imscpConfig{'GUI_ROOT_DIR'},
		'skipCreateHome' => 'yes',
		'group' => $groupName
	);

	$rs = $panelUName->addSystemUser();
	return $rs if $rs;

	# Adding panel user in the i-MSCP master group
	$rs = $panelUName->addToGroup($main::imscpConfig{'MASTER_GROUP'});
	return $rs if $rs;

	# Adding Apache user in panel user group
	my $apacheUName = iMSCP::SystemUser->new('username' => $self::apacheConfig{'APACHE_USER'});
	$rs = $apacheUName->addToGroup($groupName);
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
		[$self::apacheConfig{'APACHE_USERS_LOG_DIR'}, $rootUName, $rootUName, 0750],
		[$self::apacheConfig{'APACHE_BACKUP_LOG_DIR'}, $rootUName, $rootGName, 0750]
	) {
		$rs = iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3]}
		);
		return $rs if $rs;
	}

	$rs = iMSCP::Dir->new(
		'dirname' => $self::apacheConfig{'PHP_STARTER_DIR'}
	)->remove() if -d $self::apacheConfig{'PHP_STARTER_DIR'};
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdMakeDirs');
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
		$self->{'cfgDir'} . '/parts/php' . $self::apacheConfig{'PHP_VERSION'} . '.itk.ini',
		{ 'destination' => "$self->{'wrkDir'}/php.ini", 'mode' => 0644, 'user' => $rootUName, 'group' => $rootGName }
	);
	return $rs if $rs;

	# Install new file in production directory
	$rs = iMSCP::File->new(
		'filename' => "$self->{'wrkDir'}/php.ini"
	)->copyFile(
		$self::apacheConfig{"ITK_PHP$self::apacheConfig{'PHP_VERSION'}_PATH"}
	);
	return $rs if $rs;

	# TODO PHP Browser Capabilities support file

	# Disable un-needed Apache modules
	for('suexec', 'fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php_fpm_imscp', 'php4') {
		$rs = $self->{'httpd'}->disableMod($_) if -f "$self::apacheConfig{'APACHE_MODS_DIR'}/$_.load";
		return $rs if $rs;
	}

	# Enable needed Apache modules
	$rs = $self->{'httpd'}->enableMod('php5');
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

=item _buildApacheConfFiles()

 Build Apache configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildApacheConfFiles');
	return $rs if $rs;

	# Backup, build, store and install ports.conf file if exists

	if(-f "$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf") {

		# Load file
		my $file = iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf");
		my $rdata = $file->get();
		unless(defined $rdata) {
			error("Unable to read $self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf");
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

	if(-f "$self->{'wrkDir'}/00_nameserver.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/00_nameserver.conf"
		)->copyFile("$self->{'bkpDir'}/00_nameserver.conf." . time);
		return $rs if $rs;
	}

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the Shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(`$self::apacheConfig{'CMD_HTTPD_CTL'} -v` =~ m!Apache/([\d.]+)! && version->new($1) >= version->new('2.2.12')) {
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
		"$self->{'cfgDir'}/00_nameserver.conf", { 'destination' => "$self->{'wrkDir'}/00_nameserver.conf" }
	);
	return $rs if $rs;

	# Install new file in production directory
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/00_nameserver.conf");
	$rs = $file->copyFile($self::apacheConfig{'APACHE_SITES_DIR'});
	return $rs if $rs;

	# Enable required apache modules
	$rs = $self->{'httpd'}->enableMod('cgid rewrite suexec proxy proxy_http ssl');
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

	$self->{'httpd'}->setData(
		{
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
			GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
			SERVER_HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'}
		}
	);

	# Build 00_master.conf file

	# Schedule deletion of useless suexec section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('suexec', @_)}
	);
	return $rs if $rs;

	# Schedule deletion of useless fcgid section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fcgid', @_)}
	);
	return $rs if $rs;

	# Schedule deletion of useless fastcgi section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fastcgi', @_)}
	);
	return $rs if $rs;

	# Schedule deletion of useless php_fpm section
	$rs = $self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('php_fpm', @_) }
	);
	return $rs if $rs;

	# Build file using apache/00_master.conf template
	$rs = $self->{'httpd'}->buildConfFile("$self->{'cfgDir'}/00_master.conf");
	return $rs if $rs;

	# Install new file in production directory
	$rs = iMSCP::File->new(
		'filename' => "$self->{'wrkDir'}/00_master.conf"
	)->copyFile(
		"$self::apacheConfig{'APACHE_SITES_DIR'}/00_master.conf"
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->enableSite('00_master.conf');
	return $rs if $rs;

	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes') {
		# Build 00_master_ssl.conf file

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

		# Schedule deletion of useless php_fpm section
		$rs = $self->{'hooksManager'}->register(
			'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('php_fpm', @_) }
		);
		return $rs if $rs;

		$rs = $self->{'httpd'}->buildConfFile("$self->{'cfgDir'}/00_master_ssl.conf");
		return $rs if $rs;

		$rs = iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/00_master_ssl.conf"
		)->copyFile(
			"$self::apacheConfig{'APACHE_SITES_DIR'}/00_master_ssl.conf"
		);
		return $rs if $rs;

		$rs = $self->{'httpd'}->enableSite('00_master_ssl.conf');
		return $rs if $rs;
	} else {
		$rs = $self->{'httpd'}->disableSite(
			'00_master_ssl.conf'
		) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/00_master_ssl.conf";
		return $rs if $rs;

		for(
			"$self->{'wrkDir'}/00_master_ssl.conf",
			"$self::apacheConfig{'APACHE_SITES_DIR'}/00_master_ssl.conf"
		) {
			$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
			return $rs if $rs;
		}
	}

	# Disable defaults sites if any
	for('default', 'default-ssl') {
		$rs = $self->{'httpd'}->disableSite($_) if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdBuildMasterVhostFiles');
}

=item _installLogrotate()

 Build and install Apache logrotate file.

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'apache2');
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile('logrotate.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		'logrotate.conf', { 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'apache2');
}

=item _saveConf()

 Save configuration.

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/apache.data");

	my $rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/apache.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', \$cfg, "$self->{'cfgDir'}/apache.data");
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/apache.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', "$self->{'cfgDir'}/apache.data");
}

=item _oldEngineCompatibility()

 Remove old imscp.conf file if any.

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdOldEngineCompatibility');
	return $rs if $rs;

	if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf") {
		$rs = $self->{'httpd'}->disableSite('imscp.conf');
		return $rs if $rs;

		$rs = iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf")->delFile();
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdOldEngineCompatibility');
}

=item _fixPhpErrorReportingValues()

 Fix PHP error reporting values according current PHP version.

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

		return 1 if ref $rs ne 'HASH';
			$rs = $database->doQuery(
			'dummy', 'UPDATE `php_ini` SET `error_reporting` = ? WHERE `error_reporting` = ?', $to, $from
		);
		return 1 if ref $rs ne 'HASH';
	}

	0;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
