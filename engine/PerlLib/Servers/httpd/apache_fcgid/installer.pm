=head1 NAME

 Servers::httpd::apache_fcgid::installer - i-MSCP Apache2/FastCGI Server implementation

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

package Servers::httpd::apache_fcgid::installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Crypt 'randomStr';
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::TemplateParser;
use iMSCP::ProgramFinder;
use File::Basename;
use Servers::httpd;
use version;
use Net::LibIDN qw/idn_to_ascii/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache2/FastCGI Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, die on failure

=cut

sub registerSetupListeners
{
	my ($self, $eventManager) = @_;

	$eventManager->register('beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0 });
	$eventManager->register('afterSetupCreateDatabase', sub { $self->_fixPhpErrorReportingValues(@_) });
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $rs = 0;
	my $phpiniLevel = main::setupGetQuestion('INI_LEVEL') || $self->{'config'}->{'INI_LEVEL'};

	if(
		$main::reconfigure ~~ [ 'httpd', 'php', 'servers', 'all', 'forced' ] ||
		not $phpiniLevel ~~ [ 'per_user', 'per_domain', 'per_site' ]
	) {
		$phpiniLevel =~ s/_/ /;

		($rs, $phpiniLevel) = $dialog->radiolist(
"
\\Z4\\Zb\\ZuPHP INI Level\\Zn

Please, choose the PHP INI level you want use for PHP. Available levels are:

\\Z4Per user:\\Zn Each customer will have only one php.ini file
\\Z4Per domain:\\Zn Each domain / domain alias will have its own php.ini file
\\Z4Per site:\\Zn Each site will have its own php.ini file

",
			[ 'per user', 'per domain', 'per site' ],
			$phpiniLevel ne 'per site' && $phpiniLevel ne 'per domain' ? 'per user' : $phpiniLevel
		);
	}

	($self->{'config'}->{'INI_LEVEL'} = $phpiniLevel) =~ s/ /_/ unless $rs == 30;

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_bkpConfFile("$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf");
	return $rs if $rs;

	$rs = $self->_setApacheVersion();
	return $rs if $rs;

	$rs = $self->_makeDirs();
	return $rs if $rs;

	$rs = $self->_buildFastCgiConfFiles();
	return $rs if $rs;

	$rs = $self->_buildApacheConfFiles();
	return $rs if $rs;

	$rs = $self->_setupVlogger();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$self->_oldEngineCompatibility();
}

=item setEnginePermissions

 Set engine permissions

 Return int 0 on success, die on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $fcgiDir = $self->{'config'}->{'PHP_STARTER_DIR'};

	setRights($fcgiDir, { user => $rootUName, group => $rootGName, mode => '0555' });
	setRights("$main::imscpConfig{'TOOLS_ROOT_DIR'}/vlogger", {
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ADM_GROUP'}, mode => '0750' }
	);
	setRights($self->{'config'}->{'HTTPD_LOG_DIR'}, {
		user => $main::imscpConfig{'ROOT_USER'},
		group => $main::imscpConfig{'ADM_GROUP'},
		dirmode => '0755',
		filemode => '0644',
		recursive => 1
	});
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::httpd::apache_fcgid::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'httpd'} = Servers::httpd->factory();
	$self->{'eventManager'} = $self->{'httpd'}->{'eventManager'};
	$self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";
	$self->{'config'} = $self->{'httpd'}->{'config'};

	my $oldConf = "$self->{'apacheCfgDir'}/apache.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;
		for my $param(keys %oldConfig) {
			if(exists $self->{'config'}->{$param}) {
				$self->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	}

	$self;
}

=item _bkpConfFile($cfgFile)

 Backup the given file

 Param string $cfgFile File to backup
 Return int 0 on success, other or die on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	$self->{'eventManager'}->trigger('beforeHttpdBkpConfFile', $cfgFile);

	if(-f $cfgFile){
		my $file = iMSCP::File->new( filename => $cfgFile );
		my $basename = basename($cfgFile);

		unless(-f "$self->{'apacheBkpDir'}/$basename.system") {
			$file->copyFile("$self->{'apacheBkpDir'}/$basename.system");
		} else {
			$file->copyFile("$self->{'apacheBkpDir'}/$basename." . time());
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBkpConfFile', $cfgFile);
}

=item _setApacheVersion()

 Set Apache version

 Return int 0 on success, other on failure

=cut

sub _setApacheVersion
{
	my $self = shift;

	my $rs = execute('apache2ctl -v', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to find Apache version') if $rs && ! $stderr;
	return $rs if $rs;

	if($stdout =~ m%Apache/([\d.]+)%) {
		$self->{'config'}->{'HTTPD_VERSION'} = $1;
		debug("Apache version set to: $1");
	} else {
		error('Unable to parse Apache version');
		return 1;
	}

	0;
}

=item _makeDirs()

 Create directories

 Return int 0 on success, die on failure

=cut

sub _makeDirs
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdMakeDirs');

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $phpdir = $self->{'config'}->{'PHP_STARTER_DIR'};

	# Remove any older fcgi directory (prevent possible orphaned file when switching to another ini level)
	iMSCP::Dir->new( dirname => $self->{'config'}->{'PHP_STARTER_DIR'} )->remove();

	for my $dir(
		[ $self->{'config'}->{'HTTPD_LOG_DIR'}, $rootUName, $rootUName, 0755 ],
		[ $phpdir, $rootUName, $rootGName, 0555 ],
	) {
		iMSCP::Dir->new( dirname => $dir->[0] )->make({ user => $dir->[1], group => $dir->[2], mode => $dir->[3] });
	}

	$self->{'eventManager'}->trigger('afterHttpdMakeDirs');
}

=item _buildFastCgiConfFiles()

 Build FastCGI configuration files

 Return int 0 on success, other or die on failure

=cut

sub _buildFastCgiConfFiles
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdBuildFastCgiConfFiles');

	for my $filename('fcgid_imscp.conf', 'fcgid_imscp.load') {
		my $rs = $self->_bkpConfFile("$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$filename");
		return $rs if $rs;
	}

	my $version = $self->{'config'}->{'HTTPD_VERSION'};
	my $apache24 = (version->parse($version) >= version->parse('2.4.0'));

	$self->{'httpd'}->setData({
		SYSTEM_USER_PREFIX => $main::imscpConfig{'SYSTEM_USER_PREFIX'},
		SYSTEM_USER_MIN_UID => $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
		PHP_STARTER_DIR => $self->{'config'}->{'PHP_STARTER_DIR'},
		AUTHZ_ALLOW_ALL => ($apache24) ? 'Require all granted' : 'Allow from all'
	});

	my $rs = $self->{'httpd'}->buildConfFile("$self->{'apacheCfgDir'}/fcgid_imscp.conf");
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'apacheWrkDir'}/fcgid_imscp.conf" );
	$file->copyFile($self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'});

	$file = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid.load");

	my $cfgTpl = $file->get();

	$file = iMSCP::File->new( filename => "$self->{'apacheWrkDir'}/fcgid_imscp.load" );

	$cfgTpl = "<IfModule !mod_fcgid.c>\n" . $cfgTpl . "</IfModule>\n";

	$file->set($cfgTpl);
	$file->save();
	$file->mode(0644);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$file->copyFile($self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'});

	# # Transitional: fastcgi_imscp
	my @toDisableModules = (
		'fastcgi', 'fcgid', 'php4', 'php5', 'php5_cgi', 'php5filter', 'php_fpm_imscp', 'fastcgi_imscp'
	);

	my @toEnableModules = ('actions', 'fcgid_imscp');

	if(version->parse($version) >= version->parse('2.4.0')) {
		push (@toDisableModules, ('mpm_event', 'mpm_itk', 'mpm_prefork'));
		push (@toEnableModules, 'mpm_worker', 'authz_groupfile');
	}

	for my $module(@toDisableModules) {
		if(-l "$self->{'config'}->{'HTTPD_MODS_ENABLED_DIR'}/$module.load") {
			$rs = $self->{'httpd'}->disableModules($module);
			return $rs if $rs;
		}
	}

	for my $module(@toEnableModules) {
		if(-f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$module.load") {
			$rs = $self->{'httpd'}->enableModules($module);
			return $rs if $rs;
		}
	}

	# Make sure that PHP modules are enabled
	if(iMSCP::ProgramFinder::find('php5enmod')) {
		for my $extension(
			'apc', 'curl', 'gd', 'imap', 'intl', 'json', 'mcrypt', 'mysqlnd/10', 'mysqli', 'mysql', 'opcache', 'pdo/10',
			'pdo_mysql'
		) {
			$rs = execute("php5enmod $extension", \my $stdout, \my $stderr);
			debug($stdout) if $stdout;
			unless($rs ~~ [0, 2]) {
				error($stderr) if $stderr;
				return $rs;
			}
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBuildFastCgiConfFiles');
}

=item _buildApacheConfFiles()

 Build Apache configuration files

 Return int 0 on success, other or die on failure

=cut

sub _buildApacheConfFiles
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdBuildApacheConfFiles');

	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf") {
		$self->{'eventManager'}->trigger('onLoadTemplate', 'apache_fcgid', 'ports.conf', \my $cfgTpl, { });

		unless(defined $cfgTpl) {
			$cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" )->get();
		}

		$self->{'eventManager'}->trigger('beforeHttpdBuildConfFile', \$cfgTpl, 'ports.conf');
		$cfgTpl =~ s/^(NameVirtualHost\s+\*:80)/#$1/gmi;
		$self->{'eventManager'}->trigger('afterHttpdBuildConfFile', \$cfgTpl, 'ports.conf');

		my $file = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" );
		$file->set($cfgTpl);
		$file->mode(0644);
		$file->save();
	}

	# Turn off default access log provided by Debian package
	if(-d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available") {
		my $rs = $self->{'httpd'}->disableConfs('other-vhosts-access-log.conf');
		return $rs if $rs;
	} elsif(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/other-vhosts-access-log") {
		iMSCP::File->new(filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/other-vhosts-access-log")->delFile();
	}

	# Remove default access log file provided by Debian package
	if(-f "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log") {
		iMSCP::File->new(filename => "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log")->delFile();
	}

	my $version = $self->{'config'}->{'HTTPD_VERSION'};

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the shell (from Apache 2.2.12)
	my $pipeSyntax = '|';
	if(version->parse($version) >= version->parse('2.2.12')) {
		$pipeSyntax .= '|';
	}

	my $apache24 = (version->parse($version) >= version->parse('2.4.0'));

	$self->{'httpd'}->setData({
		HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
		HTTPD_ROOT_DIR => $self->{'config'}->{'HTTPD_ROOT_DIR'},
		AUTHZ_DENY_ALL => ($apache24) ? 'Require all denied' : 'Deny from all',
		AUTHZ_ALLOW_ALL => ($apache24) ? 'Require all granted' : 'Allow from all',
		PIPE => $pipeSyntax,
		TOOLS_ROOT_DIR => $main::imscpConfig{'TOOLS_ROOT_DIR'},
		VLOGGER_CONF => "$self->{'apacheWrkDir'}/vlogger.conf"
	});

	my $rs = $self->{'httpd'}->buildConfFile('00_nameserver.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile('00_nameserver.conf');
	return $rs if $rs;

	$self->{'httpd'}->setData({ HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} });

	$rs = $self->{'httpd'}->buildConfFile('00_imscp.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile('00_imscp.conf', {
		destination => (-d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available")
			? "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available"
			: "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d"
	});
	return $rs if $rs;

	$rs = $self->{'httpd'}->enableModules('cgid proxy proxy_http rewrite ssl headers suexec');
	return $rs if $rs;

	$rs = $self->{'httpd'}->enableSites('00_nameserver.conf');
	return $rs if $rs;

	$rs = $self->{'httpd'}->enableConfs('00_imscp.conf');
	return $rs if $rs;

	# Disable defaults sites if any
	# default, default-ssl (Debian < Jessie)
	# 000-default.conf, default-ssl.conf' : (Debian >= Jessie)
	for my $site('default', 'default-ssl', '000-default.conf', 'default-ssl.conf') {
		if (-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site") {
			$rs = $self->{'httpd'}->disableSites($site);
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterHttpdBuildApacheConfFiles');
}

=item _setupVlogger()

 Setup vlogger

 Return int 0 on success, other on failure

=cut

sub _setupVlogger
{
	my $self = shift;

	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	$dbHost = ($dbHost eq 'localhost') ? '127.0.0.1' : $dbHost;
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	my $dbName = main::setupGetQuestion('DATABASE_NAME');
	my $dbUser = 'vlogger_user';
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	$dbUserHost = ($dbUserHost eq '127.0.0.1') ? 'localhost' : $dbUserHost;
	my $dbPass = randomStr(16);

	my ($db, $errStr) = main::setupGetSqlConnect($dbName);
	fatal("Unable to connect to SQL server: $errStr") unless $db;

	if(-f "$self->{'apacheCfgDir'}/vlogger.sql") {
		my $rs = main::setupImportSqlSchema($db, "$self->{'apacheCfgDir'}/vlogger.sql");
		return $rs if $rs;
	} else {
		error("File $self->{'apacheCfgDir'}/vlogger.sql not found.");
		return 1;
	}

	for my $host($dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, '127.0.0.1') {
		next unless $host;

		if(main::setupDeleteSqlUser($dbUser, $host)) {
			error('Unable to remove SQL user or one of its privileges');
			return 1;
		}
	}

	my @dbUserHosts = ($dbUserHost);

	if($dbUserHost ~~ [ 'localhost', '127.0.0.1' ]) {
		push @dbUserHosts, ($dbUserHost eq '127.0.0.1') ? 'localhost' : '127.0.0.1';
	}

	my $quotedDbName = $db->quoteIdentifier($dbName);

	for my $host(@dbUserHosts) {
		my $rs = $db->doQuery(
			'g',
			"GRANT SELECT, INSERT, UPDATE ON $quotedDbName.httpd_vlogger TO ?@? IDENTIFIED BY ?",
			$dbUser,
			$host,
			$dbPass
		);
		unless(ref $rs eq 'HASH') {
			error(sprintf('Unable to add SQL privileges: %s', $rs));
			return 1;
		}
	}

	$self->{'httpd'}->setData({
		DATABASE_NAME => $dbName,
		DATABASE_HOST => $dbHost,
		DATABASE_PORT => $dbPort,
		DATABASE_USER => $dbUser,
		DATABASE_PASSWORD => $dbPass
	});

	$self->{'httpd'}->buildConfFile(
		"$self->{'apacheCfgDir'}/vlogger.conf.tpl", { }, { destination => "$self->{'apacheWrkDir'}/vlogger.conf" }
	);
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	iMSCP::File->new( filename => "$self->{'apacheCfgDir'}/apache.data" )->copyFile(
		"$self->{'apacheCfgDir'}/apache.old.data"
	);
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other or die on failure

=cut

sub _oldEngineCompatibility
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeHttpdOldEngineCompatibility');

	for my $site('imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf') {
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site") {
			my $rs = $self->{'httpd'}->disableSites($site);
			return $rs if $rs;

			iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site" )->delFile();
		}
	}

	if(-d $self->{'config'}->{'PHP_STARTER_DIR'}) {
		my $rs = execute(
			"rm -f $self->{'config'}->{'PHP_STARTER_DIR'}/*/php5-fastcgi-starter", \my $stdout, \my $stderr
		);
		return $rs if $rs;
	}

	for my $dir(
		$self->{'config'}->{'APACHE_BACKUP_LOG_DIR'}, $self->{'config'}->{'HTTPD_USERS_LOG_DIR'},
		$self->{'config'}->{'APACHE_SCOREBOARDS_DIR'}
	) {
		iMSCP::Dir->new( dirname => $dir )->remove();
	}

	# Remove customer's logs file if any (no longer needed since we are now using bind mount)
	my $rs = execute("rm -f $main::imscpConfig{'USER_WEB_DIR'}/*/logs/*.log", \my $stdout, \my $stderr);
	error($stderr) if $rs && $stderr;
	return $rs if $rs;

	if(-f '/usr/local/sbin/vlogger') {
		iMSCP::File->new( filename => '/usr/local/sbin/vlogger')->delFile();
	}

	$self->{'eventManager'}->trigger('afterHttpdOldEngineCompatibility');
}

=item _fixPhpErrorReportingValues()

 Fix PHP error reporting values according current PHP version

 Return int 0 on success, other on failure

=cut

sub _fixPhpErrorReportingValues
{
	my $self = shift;

	my ($database, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	unless($database) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	my $rs = execute('php -v', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $phpVersion = $1 if $stdout =~ /^PHP\s([\d.]{3})/;

	if(defined $phpVersion) {
		my %errorReportingValues;

		if($phpVersion == 5.3) {
			%errorReportingValues = (
				32759 => 30711, # E_ALL & ~E_NOTICE
				32767 => 32767, # E_ALL | E_STRICT
				24575 => 22527  # E_ALL & ~E_DEPRECATED
			)
		} elsif($phpVersion >= 5.4) {
			%errorReportingValues = (
				30711 => 32759, # E_ALL & ~E_NOTICE
				32767 => 32767, # E_ALL | E_STRICT
				22527 => 24575  # E_ALL & ~E_DEPRECATED
			);
		} else {
			error("Unsupported PHP version: $phpVersion");
			return 1;
		}

		while(my ($from, $to) = each(%errorReportingValues)) {
			$rs = $database->doQuery(
				'u',
				"UPDATE `config` SET `value` = ? WHERE `name` = 'PHPINI_ERROR_REPORTING' AND `value` = ?",
				$to, $from
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}

			$rs = $database->doQuery(
				'u', 'UPDATE `php_ini` SET `error_reporting` = ? WHERE `error_reporting` = ?', $to, $from
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		}
	} else {
		error('Could not find PHP version');
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
