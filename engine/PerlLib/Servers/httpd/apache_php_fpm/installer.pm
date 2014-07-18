#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm::installer - i-MSCP Apache2/PHP5-FPM Server implementation

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
# @category    i-MSCPuse iMSCP::Execute;332
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_php_fpm::installer;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::TemplateParser;
use Servers::httpd::apache_php_fpm;
use Net::LibIDN qw/idn_to_ascii/;
use Cwd;
use File::Basename;
use File::Temp;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache2/PHP5-FPM Server implementation

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

 Ask user for PHP FPM pools level to use

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askForPhpFpmPoolsLevel
{
	my ($self, $dialog) = @_;

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

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_php_fpm');
	return $rs if $rs;

	$rs = $self->_setApacheVersion();
	return $rs if $rs;

	$rs = $self->_makeDirs();
	return $rs if $rs;

	$rs = $self->_buildHttpdModules();
	return $rs if $rs;

	$rs = $self->_buildFastCgiConfFiles();
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

	$rs = $self->_oldEngineCompatibility();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstall', 'apache_php_fpm');
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

 Return Servers::httpd::apache_php_fpm::installer

=cut

sub _init
{
	my $self = $_[0];

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

	for ([ $self->{'config'}->{'HTTPD_LOG_DIR'}, $rootUName, $rootUName, 0755 ],) {
		$rs = iMSCP::Dir->new('dirname' => $_->[0])->make({ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] });
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdMakeDirs');
}

=item _buildHttpdModules()

 Build modules for Apache

 Return int 0 on success, other on failure

=cut

sub _buildHttpdModules
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildModules');
	return $rs if $rs;

	if(qv("v$self->{'config'}->{'HTTPD_VERSION'}") == qv('v2.4.9')) {
		my $prevDir = getcwd();
		my $buildDir = File::Temp->newdir();

		unless(chdir $buildDir) {
			error("Unable to change dir to $buildDir");
			return 1;
		}

		$rs = iMSCP::File->new(
			'filename' => "$self->{'apacheCfgDir'}/modules/proxy_handler/mod_proxy_handler.c"
		)->copyFile(
			$buildDir
		);

		unless($rs) {
			my($stdout, $stderr);
			$rs = execute("$self->{'config'}->{'CMD_APXS2'} -i -a -c mod_proxy_handler.c", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
		}

		unless(chdir $prevDir) {
			error("Unable to change dir to $prevDir");
			$rs |= 1;
		}

		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdBuildModules');
}

=item _buildFastCgiConfFiles()

 Build FastCGI configuration files

 Return int 0 on success, other on failure

=cut

sub _buildFastCgiConfFiles
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdBuildFastCgiConfFiles');
	return $rs if $rs;

	# Backup, build, store and install the php_fpm_imscp.conf file

	# Set needed data
	$self->{'httpd'}->setData(
		{
			AUTHZ_ALLOW_ALL => (qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.0'))
				? 'Require env REDIRECT_STATUS' : "Order allow,deny\n        Allow from env=REDIRECT_STATUS"
		}
	);

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/php_fpm_imscp.conf");
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php_fpm_imscp.conf",
		{ },
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php_fpm_imscp.conf" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php_fpm_imscp.conf",
		{ 'destination' => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/php_fpm_imscp.conf" }
	);
	return $rs if $rs;

	# Backup, build, store and install the php_fpm_imscp.load file

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/php_fpm_imscp.load");
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php_fpm_imscp.load",
		{ },
		{ 'destination' => "$self->{'phpfpmWrkDir'}/php_fpm_imscp.load" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php_fpm_imscp.load",
		{ 'destination' => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/php_fpm_imscp.load" }
	);
	return $rs if $rs;

	# Disable/Enable Apache modules

	# Transitional: fastcgi_imscp
	my @toDisableModules = (
		'fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php4', 'php5', 'php5_cgi', 'php5filter'
	);
	my @toEnableModules = ('actions', 'suexec', 'version');

	if(qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.0')) {
		push @toDisableModules, ('mpm_event', 'mpm_itk', 'mpm_prefork');
		push @toEnableModules, ('mpm_worker', 'authz_groupfile');
	}

	if(qv("v$self->{'config'}->{'HTTPD_VERSION'}") >= qv('v2.4.9')) {
		push @toDisableModules, ('php_fpm_imscp');
		push @toEnableModules, ('setenvif', 'proxy_fcgi', 'proxy_handler');
	} else {
		push @toDisableModules, ('proxy_fcgi', 'proxy_handler');
		push @toEnableModules, 'php_fpm_imscp';
	}

	for(@toDisableModules) {
		$rs = $self->{'httpd'}->disableModules($_) if -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_.load";
		return $rs if $rs;
	}

	# Enable needed Apache modules
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

	$self->{'hooksManager'}->trigger('afterHttpdBuildFastCgiConfFiles');
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
		"$self->{'phpfpmCfgDir'}/parts/php5.ini",
		{ },
		{
			'destination' => "$self->{'phpfpmWrkDir'}/php.ini",
			'mode' => 0644,
			'user' => $rootUName,
			'group' => $rootGName
		}
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php.ini", { 'destination' => "$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini" }
	);
	return $rs if $rs;

	# Backup, build, store and install main php-fpm.conf configuration file

	$rs = $self->{'httpd'}->phpfpmBkpConfFile("$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php-fpm.conf", '', 1);
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile(
		"$self->{'phpfpmCfgDir'}/php-fpm.conf", { }, { 'destination' => "$self->{'phpfpmWrkDir'}/php-fpm.conf" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/php-fpm.conf",
		{ 'destination' => "$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php-fpm.conf" }
	);
	return $rs if $rs;

	# Disable default pool configuration file if exists
	if(-f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf") {
		my $file = iMSCP::File->new('filename' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf");
		$rs = $file->moveFile("$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled");
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdBuildPhpConfFiles');
}

=item _buildApacheConfFiles

 Build main Apache configuration files

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
		$rs = $self->{'hooksManager'}->trigger('onLoadTemplate', 'apache_php_fpm', 'ports.conf', \$cfgTpl, {});
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

	$rs = $self->{'httpd'}->apacheBkpConfFile("$self->{'apacheWrkDir'}/00_nameserver.conf");
	return $rs if $rs;

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

	$rs = $self->{'httpd'}->buildConfFile('00_nameserver.conf', {});
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	# Enabling required apache modules
	$rs = $self->{'httpd'}->enableModules('cgid rewrite proxy proxy_http ssl');
	return $rs if $rs;

	# Enabling 00_nameserver.conf file
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

 Build and install both Apache and PHP-FPM logrotate files

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
	my $self = $_[0];

	# Apache logrotate file

	my $rs = $self->{'hooksManager'}->trigger('beforeHttpdInstallLogrotate', 'apache2');
	return $rs if $rs;

	$rs = $self->{'httpd'}->apacheBkpConfFile("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", '', 1);
	return $rs if $rs;

	$rs = $self->{'httpd'}->buildConfFile('logrotate.conf', { });
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		'logrotate.conf', { 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" }
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
		"$self->{'phpfpmCfgDir'}/logrotate.conf", { }, {'destination' => "$self->{'phpfpmWrkDir'}/logrotate.conf" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile(
		"$self->{'phpfpmWrkDir'}/logrotate.conf",
		{ 'destination' => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdInstallLogrotate', 'php5-fpm');
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

	my @allowedChr = map { chr } (0x21..0x5b, 0x5d..0x7e);;
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

 Save both i-MSCP apache.data and i-MSCP php-fpm.data configuration files

 Return int 0 on success, 1 on failure

=cut

sub _saveConf
{
	my $self = $_[0];

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

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility()
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

	for(
		$self->{'config'}->{'APACHE_BACKUP_LOG_DIR'}, $self->{'config'}->{'HTTPD_USERS_LOG_DIR'},
		$self->{'config'}->{'APACHE_SCOREBOARDS_DIR'}
	) {
		$rs = iMSCP::Dir->new('dirname' => $_)->remove();
		return $rs if $rs;
	}

	if(-f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/master.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/master.conf"
		)->delFile();
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterHttpdOldEngineCompatibility');
}

=item _fixPhpErrorReportingValues()

 Fix PHP error_reporting value according PHP version

 This rustine fix the error_reporting integer values in the iMSCP databse according the PHP version installed on the
system.

 This is an hook function acting on the 'afterSetupCreateDatabase' hook.

 Return int 0 on success, 1 on failure

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

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
