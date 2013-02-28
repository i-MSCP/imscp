#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm::installer - i-MSCP Apache PHP-FPM Server installer

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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_php_fpm::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::Dir;
use iMSCP::File;
use Modules::SystemGroup;
use Modules::SystemUser;
use Servers::httpd::apache_php_fpm;
use Net::LibIDN qw/idn_to_ascii/;
use File::Basename;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache PHP-FPM Server installer.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks()

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;
	my $rs = 0;

	# Add installer dialog in setup dialog stack
	$rs = $hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askForPhpFpmPoolsLevel(@_) }); 0; }
	);

	# Fix error_reporting values into the database
	$rs |= $hooksManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });

	$rs;
}

=item askForPhpFpmPoolsLevel($dialog)

 Ask user for PHP FPM pools level to use

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askForPhpFpmPoolsLevel
{
	my $self = shift;
	my $dialog = shift;
	my $rs = 0;
	my $poolsLevel = $main::preseed{'PHP_FPM_POOLS_LEVEL'} || $self::phpfpmConfig{'PHP_FPM_POOLS_LEVEL'} ||
		$self::phpfpmOldConfig{'PHP_FPM_POOLS_LEVEL'} || '';

	if(
		$main::reconfigure ~~ ['httpd', 'servers', 'all', 'forced'] ||
		$poolsLevel !~ /^per_user|per_domain|per_site$/
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
			$poolsLevel ne 'per domain' && $poolsLevel ne 'per site' ? 'per user' : $poolsLevel
		);
	}

	if($rs != 30) {
		$poolsLevel =~ s/ /_/g;
		$self::phpfpmConfig{'PHP_FPM_POOLS_LEVEL'} = $poolsLevel;
	}

	$rs;
}

=item install()

 Process installation.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs =0;

	# Apache (main config)
	$rs |= $self->_makeDirs();
	$rs |= $self->_buildFastCgiConfFiles();
	$rs |= $self->_buildApacheConfFiles();
	$rs |= $self->_installApacheLogrotateFile();

	# Php-Fpm (main config)
	$rs |= $self->_buildPhpConfFiles();
	$rs |= $self->_installPhpFpmLogrotateFile();
	$rs |= $self->_installPhpFpmInitScript();

	# Panel (Apache and PHP-FPM config)
	$rs |= $self->_AddUsersAndGroups();
	$rs |= $self->_buildMasterVhostFiles();
	$rs |= $self->_buildMasterPhpFpmPoolFile();
	$rs |= $self->setGuiPermissions();

	# Save both the apache.data and the phpfpm.data configuration files
	$rs |= $self->saveConf();

	$rs;
}

=item setGuiPermissions

 Set i-MSCP Gui files and directories permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $apacheGName = $self::apacheConfig{'APACHE_GROUP'};
	my $rootDir = $main::imscpConfig{'ROOT_DIR'};
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeHttpdSetGuiPermissions');

	$rs |= setRights(
		"$rootDir/gui/public",
		{ 'user' => $panelUName, 'group' => $apacheGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 'yes' }
	);

	$rs |= setRights(
		"$rootDir/gui/themes",
		{ 'user' => $panelUName, 'group' => $apacheGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 'yes' }
	);

	$rs |= setRights(
		"$rootDir/gui/library",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0500', 'filemode' => '0400', 'recursive' => 'yes' }
	);

	$rs |= setRights(
		"$rootDir/gui/data",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', recursive => 'yes' }
	);

	$rs |= setRights("$rootDir/gui/data", { 'user' => $panelUName, 'group' => $apacheGName, 'mode' => '0550'});

	$rs |= setRights(
		"$rootDir/gui/data/ispLogos",
		{ 'user' => $panelUName, 'group' => $apacheGName, 'dirmode' => '0750', 'filemode' => '0640', recursive => 'yes' }
	);

	$rs |= setRights(
		"$rootDir/gui/i18n",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', recursive => 'yes' }
	);

	$rs |= setRights(
		"$rootDir/gui/plugins",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0700', 'filemode' => '0600', recursive => 'yes' }
	);

	$rs |= setRights("$rootDir/gui/plugins", { 'user' => $panelUName, 'group' => $apacheGName, 'mode' => '0550' });
	$rs |= setRights("$rootDir/gui", { 'user' => $panelUName, 'group' => $apacheGName, 'mode' => '0550' });
	$rs |= setRights($rootDir, { 'user' => $panelUName, group => $apacheGName, 'mode' => '0555' });
	$rs |= $self->{'hooksManager'}->trigger('afterHttpdSetGuiPermissions');

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

 Return Servers::httpd::apache_php_fpm::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();
	$self->{'httpd'} = Servers::httpd::apache_php_fpm->new();

	$self->{'hooksManager'}->trigger(
		'beforeHttpdInitInstaller', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - beforeHttpdInitInstaller hook has failed');

	$self->{'apacheCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";

	# Load i-MSCP apache.data configuration file
	my $conf = "$self->{'apacheCfgDir'}/apache.data";
	my $oldConf = "$self->{'apacheCfgDir'}/apache.old.data";

	tie %self::apacheConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if(-f $oldConf) {
		tie %self::apacheOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::apacheConfig = (%self::apacheConfig, %self::apacheOldConfig);
	}

	$self->{'phpfpmCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php-fpm";
	$self->{'phpfpmBkpDir'} = "$self->{'phpfpmCfgDir'}/backup";
	$self->{'phpfpmWrkDir'} = "$self->{'phpfpmCfgDir'}/working";

	# Load i-MSCP php-fpm.data configuration file
	$conf = "$self->{'phpfpmCfgDir'}/phpfpm.data";
	$oldConf = "$self->{'phpfpmCfgDir'}/phpfpm.old.data";

	tie %self::phpfpmConfig, 'iMSCP::Config','fileName' => $conf, 'noerrors' => 1;

	if(-f $oldConf) {
		tie %self::phpfpmOldConfig, 'iMSCP::Config','fileName' => $oldConf, 'noerrors' => 1;
		%self::phpfpmConfig = (%self::phpfpmConfig, %self::phpfpmOldConfig);
	}

	$self->{'hooksManager'}->trigger(
		'afterHttpdInitInstaller', $self, 'apache_php_fpm'
	) and fatal('apache_php_fpm - afterHttpdInitInstaller hook has failed'); ;

	$self;
}

=item _makeDirs()

 Create user and backup log directories for Apache.

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdMakeDirs') and return 1;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $apacheUName = $self::apacheConfig{'APACHE_USER'};
	my $apacheGName = $self::apacheConfig{'APACHE_GROUP'};

	for (
		[$self::apacheConfig{'APACHE_USERS_LOG_DIR'}, $apacheUName, $apacheGName, 0755],
		[$self::apacheConfig{'APACHE_BACKUP_LOG_DIR'}, $rootUName, $rootGName, 0755],
	) {
		iMSCP::Dir->new(
			'dirname' => $_->[0])->make({ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
		) and return 1;
	}

	$self->{'hooksManager'}->trigger('afterHttpdMakeDirs');
}

=item

 Build Fastcgi configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildFastCgiConfFiles
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildFastCgiConfFiles') and return 1;

	# Backup, build, store and install the php_fpm_imscp.conf file

	$self->{'httpd'}->phpfpmBkpConfFile("$self::apacheConfig{'APACHE_MODS_DIR'}/php_fpm_imscp.conf") and return 1;

	$self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php_fpm_imscp.conf",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php_fpm_imscp.conf" },
	) and return 1;

	$self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php_fpm_imscp.conf",
		{ 'destination' => "$self::apacheConfig{'APACHE_MODS_DIR'}/php_fpm_imscp.conf" }
	) and return 1;

	# Backup, build, store and install the php_fpm_imscp.load file

	$self->{'httpd'}->phpfpmBkpConfFile("$self::apacheConfig{'APACHE_MODS_DIR'}/php_fpm_imscp.load") and return 1;

	$self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php_fpm_imscp.load",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php_fpm_imscp.load" },
	) and return 1;

	$self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php_fpm_imscp.load",
		{ 'destination' => "$self::apacheConfig{'APACHE_MODS_DIR'}/php_fpm_imscp.load" }
	) and return 1;

	# Disable un-needed apache modules
	for('suexec', 'fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php4', 'php5') {
		$self->{'httpd'}->disableMod($_) and return 1 if -f "$self::apacheConfig{'APACHE_MODS_DIR'}/$_.load";
	}

	# Enable needed apache modules
	$self->{'httpd'}->enableMod('actions php_fpm_imscp') and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdBuildFastCgiConfFiles');
}

=item _buildApacheConfFiles

 Build main Apache configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildApacheConfFiles') and return 1;

	# Backup, build, store and install ports.conf file if exists
	if(-f "$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf") {

		$self->{'httpd'}->apacheBkpConfFile("$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf", '', 1) and return 1;

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

	# Backup, build, store and install 00_nameserver.conf file
	$self->{'httpd'}->apacheBkpConfFile("$self::apacheConfig{'APACHE_SITES_DIR'}/00_nameserver.conf") and return 1;

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the Shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(`$self::apacheConfig{'CMD_HTTPD_CTL'} -v` =~ m!Apache/([\d.]+)! && version->new($1) >= version->new('2.2.12')) {
		$pipeSyntax .= '|';
	}

	# Set needed data
	$self->{'httpd'}->setData(
		{
			APACHE_WWW_DIR => $main::imscpConfig{'USER_HOME_DIR'},
			ROOT_DIR => $main::imscpConfig{'ROOT_DIR'},
			PIPE => $pipeSyntax
		}
	);

	$self->{'httpd'}->buildConfFile('00_nameserver.conf') and return 1;

	$self->{'httpd'}->installConfFile('00_nameserver.conf') and return 1;

	# Enable required apache modules
	$self->{'httpd'}->enableMod('cgid rewrite proxy proxy_http ssl') and return 1;

	# Enable 00_nameserver.conf file
	$self->{'httpd'}->enableSite('00_nameserver.conf') and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

=item

 Build and install Apache logrotate file.

 Return int 0 on success, other on failure

=cut

sub _installApacheLogrotateFile
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeHttpdInstallLogrotate', 'apache2') and return 1;

	$self->{'httpd'}->apacheBkpConfFile("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", '', 1) and return 1;

	$self->{'httpd'}->buildConfFile('logrotate.conf') and return 1;

	$self->{'httpd'}->installConfFile(
		'logrotate.conf',
		{ 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
	) and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterHttpdInstallLogrotate', 'apache2');
}

=item

 Build main PHP FPM configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildPhpConfFiles') and return 1;

	# Backup, build, store and install php.ini

	$self->{'httpd'}->phpfpmBkpConfFile("$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini", '', 1) and return 1;

	$self->{'httpd'}->setData({ 'PHP_TIMEZONE' => $main::imscpConfig{'PHP_TIMEZONE'} });

	$self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/parts/php$self::apacheConfig{'PHP_VERSION'}.ini",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php.ini" }
	) and return 1;

	$self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php.ini",
		{ 'destination' => "$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini" }
	) and return 1;

	# Backup, build, store and install main php-fpm.conf configuration file

	$self->{'httpd'}->phpfpmBkpConfFile("$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php-fpm.conf", '', 1) and return 1;

	$self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php-fpm.conf",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php-fpm.conf" }
	) and return 1;

	$self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php-fpm.conf",
		{ 'destination' => "$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php-fpm.conf" }
	) and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

=item

 Build and install PHP FPM logrotate file.

 Return int 0 on success, other on failure

=cut

sub _installPhpFpmLogrotateFile
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'php5-fpm') and return 1;

	$self->{'httpd'}->phpfpmBkpConfFile("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm", 'logrotate.');

	$self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/logrotate.conf",
		{'destination' => "$self->{'phpfpmWrkDir'}/logrotate.conf" }
	) and return 1;

	$self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/logrotate.conf",
		{ 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" }
	) and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'php5-fpm');
}

=item

 Install PHP FPM init script.

 Note: We provide our own init script since the one provided in older Debian/Ubuntu versions doesnt provide the
reopen-logs function.

 Return int 0 on success, other on failure

=cut

sub _installPhpFpmInitScript
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdInstallPhpFpmInitScript');

	$self->{'httpd'}->phpfpmBkpConfFile($self::phpfpmConfig{'CMD_PHP_FPM'}, 'init.', 1) and return 1;

	my ($rs, $stdout, $stderr);

	if (-f $self::phpfpmConfig{'CMD_PHP_FPM'}) {
		$rs = execute('update-rc.d -f php5-fpm remove', \$stdout, \$stderr);
		debug ($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs
	}

	$self->{'httpd'}->installConfFile(
		"$self->{'phpfpmCfgDir'}/init.d/php5-fpm",
		{ 'destination' => "$self::phpfpmConfig{'CMD_PHP_FPM'}", 'mode' => 0755 }
	) and return 1;

	$rs = execute('update-rc.d php5-fpm defaults', \$stdout, \$stderr);
	debug ($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallPhpFpmInitScript');
}

=item

 Add i-MSCP panel user and group.

 Return int 0 on success, other on failure

=cut

sub _AddUsersAndGroups
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdAddUsersAndGroups') and return 1;

	my ($panelGName, $panelUName);

	# Create panel group
	$panelGName = Modules::SystemGroup->new();
	$panelGName->addSystemGroup(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	) and return 1;

	# Create panel user
	$panelUName = Modules::SystemUser->new();
	$panelUName->{'skipCreateHome'} = 'yes';
	$panelUName->{'comment'} = 'iMSCP master virtual user';
	$panelUName->{'home'} = $main::imscpConfig{'GUI_ROOT_DIR'};
	$panelUName->{'group'} = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	$panelUName->addSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	) and return 1;

	# Add panel user into the i-MSCP master group
	$panelUName->addToGroup($main::imscpConfig{'MASTER_GROUP'}) and return 1;

	$self->{'hooksManager'}->trigger('afterHttpdAddUsersAndGroups');
}

=item _buildMasterVhostFiles

 Build Master vhost files (panel vhost files).

 Return int 0 on success, other on failure

=cut

sub _buildMasterVhostFiles
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdBuildMasterVhostFiles') and return 1;

	my $adminEmailAddress = $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'};
	my ($user, $domain) = split /@/, $adminEmailAddress;

	$adminEmailAddress = "$user@" . idn_to_ascii($domain, 'utf-8');

	# Set needed data
	$self->{'httpd'}->setData(
		{
			BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'},
			BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
			DEFAULT_ADMIN_ADDRESS => $adminEmailAddress,
			ROOT_DIR => $main::imscpConfig{'ROOT_DIR'},
			GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
			SERVER_HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'}
		}
	);

	# Build 00_master.conf file

	$self->{'httpd'}->apacheBkpConfFile("$self::apacheConfig{'APACHE_SITES_DIR'}/00_master.conf") and return 1;

	# Schedule useless suexec section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('suexec', @_) }
	) and return 1;

	# Schedule usless fcgid section
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fcgid', @_) }
	) and return 1;

	# Schedule useless fastcgi section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fastcgi', @_) }
	) and return 1;

	# Schedule useless itk sections deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('itk', @_) }
	) and return 1;

	$self->{'httpd'}->buildConfFile('00_master.conf') and return 1;

	$self->{'httpd'}->installConfFile('00_master.conf') and return 1;

	# Build 00_master_ssl.conf file

	$self->{'httpd'}->apacheBkpConfFile("$self::apacheConfig{'APACHE_SITES_DIR'}/00_master_ssl.conf") and return 1;

	# Schedule useless suexec section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('suexec', @_) }
	) and return 1;

	# Schedule useless fcgid section deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fcgid', @_) }
	) and return 1;

	# Schedule useless fastcgi section suexec
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('fastcgi', @_) }
	) and return 1;

	# Schedule useless itk sections deletion
	$self->{'hooksManager'}->register(
		'beforeHttpdBuildConfFile', sub { $self->{'httpd'}->removeSection('itk', @_) }
	) and return 1;

	$self->{'httpd'}->buildConfFile('00_master_ssl.conf') and return 1;

	$self->{'httpd'}->installConfFile('00_master_ssl.conf') and return 1;

	# Enable and disable needed i-MSCP vhost files
	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes') {
		$self->{'httpd'}->enableSite('00_master.conf 00_master_ssl.conf') and return 1;
	} else {
		$self->{'httpd'}->enableSite('00_master.conf') and return 1;
		$self->{'httpd'}->disableSite('00_master_ssl.conf') and return 1;
	}

	# Disable defaults sites if they exists
	for('default', 'default-ssl') {
		$self->{'httpd'}->disableSite($_) and return 1 if -f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_";
	}

	$self->{'hooksManager'}->trigger('afterHttpdBuildMasterVhostFiles');
}

=item _buildMasterPhpFpmPoolFile()

 Build Master PHP FPM pool file.

 Return int 0 on success, other on failure

=cut

sub _buildMasterPhpFpmPoolFile
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeBuildMasterPhpFpmPoolFile');

	$self->{'httpd'}->phpfpmBkpConfFile("$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/master.conf") and return 1;

	$self->{'httpd'}->setData(
		{
			BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
			SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
			SYSTEM_USER_MIN_UID => $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
			ROOT_DIR => $main::imscpConfig{'ROOT_DIR'},
			HOME_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			MR_LOCK_FILE => $main::imscpConfig{'MR_LOCK_FILE'},
			RKHUNTER_LOG => $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG => $main::imscpConfig{'CHKROOTKIT_LOG'},
			PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
			OTHER_ROOTKIT_LOG => ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '')
				? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : ''
		}
	);

	$self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/parts/master/pool.conf",
		{ 'destination' => "$self->{'phpfpmWrkDir'}/master.conf" }
	) and return 1;

	$self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/master.conf",
		{ 'destination' => "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/master.conf" }
	) and return 1;

	# Disable default pool configuration file if exists
	if(-f "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/www.conf") {
		my $file = iMSCP::File->new('filename' => "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/www.conf");
		$file->moveFile("$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled") and return 1;
	}

	$self->{'hooksManager'}->trigger('beforeBuildMasterPhpFpmPoolFile');
}

=item saveConf()

 Save both i-MSCP apache.data and i-MSCP php-fpm.data configuration files.

 Return int 0 on success, 1 on failure

=cut

sub saveConf
{
	my $self = shift;

	my %filesToDir = ( 'apache' => $self->{'apacheCfgDir'}, 'phpfpm' => $self->{'phpfpmCfgDir'} );

	for(keys %filesToDir) {

		my $file = iMSCP::File->new('filename' => "$filesToDir{$_}/$_.data");
		my $cfg = $file->get() or return 1;

		$self->{'hooksManager'}->trigger('beforeHttpdBkpConfFile', \$cfg, "$filesToDir{$_}/$_.data") and return 1;

		$file = iMSCP::File->new('filename' => "$filesToDir{$_}/$_.old.data");
		$file->set($cfg) and return 1;
		$file->save() and return 1;
		$file->mode(0640) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

		$self->{'hooksManager'}->trigger('afterHttpdBkpConfFile', "$filesToDir{$_}/$_.data") and return 1;
	}

	0;
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
	fatal('Unable to connect to SQL Server: $errStr') if ! $database;

	my ($rs, $stdout, $stderr);

	$rs = execute('php -v', \$stdout, \$stderr);
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $phpVersion = $1 if $stdout =~ /^PHP\s([0-9.]{3})/;

	if(defined $phpVersion && ($phpVersion eq '5.3' || $phpVersion eq '5.4')) {
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

=item

 Remove old imscp vhost file.

 Return int 0 on success, other on failure

=cut

sub oldEngineCompatibility
{
	my $self = shift;

	$self->{'hooksManager'}->trigger('beforeHttpdOldEngineCompatibility') and return 1;

	if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf") {

		$self->{'httpd'}->disableSite('imscp.conf') and return 1;

		iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_SITES_DIR'}/imscp.conf")->delFile() and return 1;
	}

	$self->{'hooksManager'}->trigger('afterHttpdOldEngineCompatibility');
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
