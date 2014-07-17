#!/usr/bin/perl

=head1 NAME

Package::FrontEnd::Installer - i-MSCP FrontEnd package installer

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
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::FrontEnd::Installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use iMSCP::SystemUser;
use Package::FrontEnd;
use File::Basename;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package installer

=head1 PUBLIC METHODS

=item install()

Process install tasks

Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_setHttpdVersion();
	return $rs if $rs;

	$rs = $self->_addMasterWebUser();
	return $rs if $rs;

	$rs = $self->_makeDirs();
	return $rs if $rs;

	$rs = $self->_buildPhpConfig();
	return $rs if $rs;

	$rs = $self->_buildHttpdConfig();
	return $rs if $rs;

	$rs = $self->_saveConfig();
}

=item setGuiPermissions()

 Set frontEnd (GUI) permissions

Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndSetGuiPermissions');
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $guiRootDir = $main::imscpConfig{'GUI_ROOT_DIR'};

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

	$self->{'hooksManager'}->trigger('afterFrontEndSetGuiPermissions');
}

=item setEnginePermissions()

 Set frontEnd (engine) permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndSetEnginePermissions');
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	$rs = setRights(
		$self->{'config'}->{'HTTPD_CONF_DIR'},
		{ 'user' => $rootUName, 'group' => $rootGName, 'dirmode' => '0755', 'filemode' => '0644', 'recursive' => 1 }
	);
	return $rs if $rs;

	$rs = setRights(
		$self->{'config'}->{'HTTPD_LOG_DIR'},
			{ 'user' => $rootUName, 'group' => $rootGName, 'dirmode' => '0755', 'filemode' => '0640', 'recursive' => 1 }
	);
	return $rs if $rs;

	$rs = setRights(
		"$self->{'config'}->{'PHP_STARTER_DIR'}/master",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0640', 'recursive' => 1 }
	);
	return $rs if $rs;


	$self->{'hooksManager'}->trigger('afterFrontEndSetEnginePermissions');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::FrontEnd::Installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'frontend'} = Package::FrontEnd->getInstance();
	$self->{'hooksManager'} = $self->{'frontend'}->{'hooksManager'};

	$self->{'cfgDir'} = $self->{'frontend'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'frontend'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/nginx.old.data";

	if(-f $oldConf) {
		tie %{$self->{'oldConfig'}}, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %{$self->{'oldConfig'}}) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $self->{'oldConfig'}->{$_};
			}
		}
	}

	$self;
}

=item _setNginxVersion()

 Set httpd version

 Return in 0 on success, other on failure

=cut

sub _setHttpdVersion()
{
	my $self = $_[0];

	my ($stderr);
	my $rs = execute("$self->{'config'}->{'CMD_NGINX'} -v", undef, \$stderr);
	debug($stderr) if $stderr;
	error($stderr) if $stderr && $rs;
	error('Unable to find Nginx version') if $rs;
	return $rs if $rs;

	if($stderr =~ m%nginx/([\d.]+)%) {
		$self->{'config'}->{'HTTPD_VERSION'} = $1;
		debug("Nginx version set to: $1");
	} else {
		error("Unable to parse Nginx version from Nginx version string: $stderr");
		return 1;
	}

	0;
}

=item _addMasterWebUser()

 Add master Web user

 Return int 0 on success, other on failure

=cut

sub _addMasterWebUser
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndAddUser');
	return $rs if $rs;

	my $userName =
	my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my ($db, $errStr) = main::setupGetSqlConnect($main::imscpConfig{'DATABASE_NAME'});
	unless($db) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	my $rdata = $db->doQuery(
		'admin_sys_uid',
		'
			SELECT
				admin_sys_name, admin_sys_uid, admin_sys_gname
			FROM
				admin
			WHERE
				admin_type = ? AND created_by = ?
			LIMIT
				1
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
		# Modify existents i-MSCP Master Web user
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

		# Modify existents i-MSCP Master Web group
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

	# Update the admin.admin_sys_name, admin.admin_sys_uid, admin.admin_sys_gname and admin.admin_sys_gid columns
	$rdata = $db->doQuery(
		'dummy',
		'
			UPDATE
				admin
			SET
				admin_sys_name = ?, admin_sys_uid = ?, admin_sys_gname = ?, admin_sys_gid = ?
			WHERE
				admin_type = ?
		',
		$userName,
		$userUid,
		$groupName,
		$userGid,
		'admin'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# Add the i-MSCP Master Web user into the i-MSCP group
	$rs = iMSCP::SystemUser->new('username' => $userName)->addToGroup($main::imscpConfig{'IMSCP_GROUP'});
	return $rs if $rs;

	# Add the httpd user into i-MSCP Master Web group
	$rs = iMSCP::SystemUser->new('username' => $self->{'config'}->{'HTTPD_USER'})->addToGroup($groupName);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterHttpdAddUser');
}

=item _makeDirs()

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndMakeDirs');
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $phpdir = $self->{'config'}->{'PHP_STARTER_DIR'};

	for (
		[$self->{'config'}->{'HTTPD_CONF_DIR'}, $rootUName, $rootUName, 0755],
		[$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}, $rootUName, $rootUName, 0755],
		[$self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}, $rootUName, $rootUName, 0755],
		[$self->{'config'}->{'HTTPD_LOG_DIR'}, $rootUName, $rootUName, 0755],
		[$phpdir, $rootUName, $rootGName, 0555],
		["$phpdir/master", $panelUName, $panelGName, 0550],
		["$phpdir/master/php5", $panelUName, $panelGName, 0550]
	) {
		$rs = iMSCP::Dir->new('dirname' => $_->[0])->make({ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] });
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterFrontEndMakeDirs');
}

=item _buildPhpConfig()

 Build PHP configuration

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfig
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEnddBuildPhpConfig');
	return $rs if $rs;

	my ($cfgTpl, $file);
	my $cfgDir = $self->{'cfgDir'};
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	my $timestamp = time;

	# Backup any current file
	for ('php5-fcgi-starter', 'php5/php.ini') {
		if(-f "$self->{'config'}->{'PHP_STARTER_DIR'}/master/$_") {
			my $fileName = basename($_);
			my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'PHP_STARTER_DIR'}/master/$_");
			$rs = $file->copyFile("$bkpDir/$fileName.$timestamp");
			return $rs if $rs;
		}
	}

	# Build PHP FCGI starter script

	my $user = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $group = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	# Set template vars
	my $tplVars = {
		PHP_STARTER_DIR => $self->{'config'}->{'PHP_STARTER_DIR'},
		DOMAIN_NAME => 'master',
		PHP_FCGI_MAX_REQUESTS => $self->{'config'}->{'PHP_FCGI_MAX_REQUESTS'},
		PHP_FCGI_CHILDREN => $self->{'config'}->{'PHP_FCGI_CHILDREN'},
		WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
		PANEL_USER => $user,
		PANEL_GROUP => $group,
		SPAWN_FCGI_BIN => $self->{'config'}->{'SPAWN_FCGI_BIN'},
		PHP_CGI_BIN => $self->{'config'}->{'PHP_CGI_BIN'}
	};

	$rs = $self->{'frontend'}->buildConfFile(
		"$cfgDir/parts/master/php5-fcgi-starter.tpl",
		$tplVars,
		{ 'destination' => "$wrkDir/master.php5-fcgi-starter", 'mode' => 0550, 'user' => $user, 'group' => $group }
	);
	return $rs if $rs;

	# Install file in production directory
	$rs = iMSCP::File->new('filename' => "$wrkDir/master.php5-fcgi-starter")->copyFile(
		"$self->{'config'}->{'PHP_STARTER_DIR'}/master/php5-fcgi-starter"
	);
	return $rs if $rs;

	# Build php.ini file

	# Set Set template vars
	$tplVars = {
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
	};

	# Build file using template from fcgi/parts/master/php5
	$rs = $self->{'frontend'}->buildConfFile(
		"$cfgDir/parts/master/php5/php.ini",
		$tplVars,
		{ 'destination' => "$wrkDir/master.php.ini", 'mode' => 0440, 'user' => $user, 'group' => $group }
	);
	return $rs if $rs;

	# Install new file in production directory
	$rs = iMSCP::File->new(
		'filename' => "$wrkDir/master.php.ini"
	)->copyFile(
		"$self->{'config'}->{'PHP_STARTER_DIR'}/master/php5/php.ini"
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndBuildPhpConfig');
}

=item _buildHttpdConfig()

 Build httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeFrontEndBuildHttpdConfig');
	return $rs if $rs;

	# Backup, build, store and install the nginx.conf file

	# Backup file
	if(-f "$self->{'wrkDir'}/nginx.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/nginx.conf"
		)->copyFile("$self->{'bkpDir'}/nginx.conf." . time);
		return $rs if $rs;
	}

	my $nbCPUcores = $self->{'config'}->{'HTTPD_WORKER_PROCESSES'};

	if($nbCPUcores eq 'auto') {
		my ($stdout, $stderr);
		$rs = execute(
			"$main::imscpConfig{'CMD_GREP'} processor /proc/cpuinfo | $main::imscpConfig{'CMD_WC'} -l", \$stdout
		);
		debug($stdout) if $stdout;
		debug('Unable to detect number of CPU cores. nginx worker_processes value set to 2') if $rs;

		unless($rs) {
			chomp($stdout);
			$nbCPUcores = $stdout;
		} else {
			$nbCPUcores = 2;
		}
	}

	# Build file
	$rs = $self->{'frontend'}->buildConfFile(
		"$self->{'cfgDir'}/nginx.conf",
		{
			HTTPD_USER => $self->{'config'}->{'HTTPD_USER'},
			HTTPD_WORKER_PROCESSES => $nbCPUcores,
			HTTPD_WORKER_CONNECTIONS => $self->{'config'}->{'HTTPD_WORKER_CONNECTIONS'},
			HTTPD_RLIMIT_NOFILE => $self->{'config'}->{'HTTPD_RLIMIT_NOFILE'},
			HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
			HTTPD_PID_FILE => $self->{'config'}->{'HTTPD_PID_FILE'},
			HTTPD_CONF_DIR => $self->{'config'}->{'HTTPD_CONF_DIR'},
			HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
			HTTPD_SITES_ENABLED_DIR => $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}
		}
	);
	return $rs if $rs;

	# Install file
	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/nginx.conf");
	$rs = $file->copyFile("$self->{'config'}->{'HTTPD_CONF_DIR'}");

	# Backup, build, store and install the imscp_fastcgi.conf file

	# Backup file
	if(-f "$self->{'wrkDir'}/imscp_fastcgi.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/imscp_fastcgi.conf"
		)->copyFile("$self->{'bkpDir'}/imscp_fastcgi.conf." . time);
		return $rs if $rs;
	}

	# Build file
	$rs = $self->{'frontend'}->buildConfFile("$self->{'cfgDir'}/imscp_fastcgi.conf");
	return $rs if $rs;

	# Install file
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/imscp_fastcgi.conf");
	$rs = $file->copyFile("$self->{'config'}->{'HTTPD_CONF_DIR'}");
	return $rs if $rs;

	# Backup, build, store and install imscp_php.conf file

	# Backup file
	if(-f "$self->{'wrkDir'}/imscp_php.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/imscp_php.conf"
		)->copyFile("$self->{'bkpDir'}/imscp_php.conf." . time);
		return $rs if $rs;
	}

	# Build file
	$rs = $self->{'frontend'}->buildConfFile("$self->{'cfgDir'}/imscp_php.conf");
	return $rs if $rs;

	# Install file
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/imscp_php.conf");
	$rs = $file->copyFile("$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d");
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFrontEndBuildHttpdConfig');

	$rs = $self->{'hooksManager'}->trigger('beforeFrontEndBuildHttpdVhosts');
	return $rs if $rs;

	my $httpsPort = $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};

	# Set needed data
	my $tplVars = {
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'},
		BASE_SERVER_VHOST_HTTP_PORT => $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'},
		BASE_SERVER_VHOST_HTTPS_PORT => $httpsPort,
		WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'},
		CONF_DIR => $main::imscpConfig{'CONF_DIR'}
	};

	# Build http vhost file

	# Force HTTPS if needed
	if($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'https://') {
		$rs = $self->{'hooksManager'}->register(
			'afterFrontEndBuildConf',
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
						"    rewrite .* https://\$host:$httpsPort\$request_uri redirect;\n" .
						"    # SECTION custom END.\n",
						$$cfgTpl
					);
				}

				0;
			}
		);
		return $rs if $rs;
	}

	# Build file
	$rs = $self->{'frontend'}->buildConfFile('00_master.conf', $tplVars);
	return $rs if $rs;

	# Install new file
	$rs = iMSCP::File->new(
		'filename' => "$self->{'wrkDir'}/00_master.conf"
	)->copyFile(
		"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf"
	);
	return $rs if $rs;

	$rs = $self->{'frontend'}->enableSites('00_master.conf');
	return $rs if $rs;

	# Build https vhost file if SSL is enabled, remove it otherwise

	if($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
		# Build vhost
		$rs = $self->{'frontend'}->buildConfFile('00_master_ssl.conf', $tplVars);
		return $rs if $rs;

		# Install vhost in production directory
		iMSCP::File->new(
			'filename' => "$self->{'wrkDir'}/00_master_ssl.conf"
		)->copyFile(
			"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master_ssl.conf"
		);
		return $rs if $rs;

		# Enable vhost
		$rs = $self->{'frontend'}->enableSites('00_master_ssl.conf');
		return $rs if $rs;
	} else {
		# Disable vhost if any
		$rs = $self->{'frontend'}->disableSites('00_master_ssl.conf');
		return $rs if $rs;

		# Remove vhost if any
		for(
			"$self->{'wrkDir'}/00_master_ssl.conf",
			"$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master_ssl.conf"
		) {
			$rs = iMSCP::File->new('filename' => $_)->delFile() if -f $_;
			return $rs if $rs;
		}
	}

	# Disable default site if any (Nginx package as provided by Debian)
	$rs = $self->{'frontend'}->disableSites('default');
	return $rs if $rs;

	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf") { # Nginx package as provided by Nginx Team
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf"
		)->moveFile("$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled");
		return $rs if $rs;
	} else {
	}

	$self->{'hooksManager'}->trigger('afterFrontEndBuildHttpdVhosts');
}

=item _saveConfig()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConfig
{
	my $self = $_[0];

	my $rootUname = $main::imscpConfig{'ROOT_USER'};
	my $rootGname = $main::imscpConfig{'ROOT_GROUP'};

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/nginx.data");

	my $rs = $file->owner($rootUname, $rootGname);
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/nginx.data");
		return 1;
	}

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/nginx.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$file->owner($rootUname, $rootGname);
	return $rs if $rs;

	$file->mode(0640);
}

=back

=head1 AUTHORS

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
