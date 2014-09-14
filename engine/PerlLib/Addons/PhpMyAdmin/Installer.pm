#!/usr/bin/perl

=head1 NAME

Addons::PhpMyAdmin::Installer - i-MSCP PhpMyAdmin addon installer

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

package Addons::PhpMyAdmin::Installer;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use Addons::PhpMyAdmin;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use iMSCP::Addons::ComposerInstaller;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::File;
use File::Basename;
use JSON;
use version;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP PhpMyAdmin addon installer

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

	$eventManager->register('beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0; });
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $dbUser = main::setupGetQuestion('PHPMYADMIN_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'pma';
	my $dbPass = main::setupGetQuestion('PHPMYADMIN_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'};

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ ['sqlmanager', 'all', 'forced'] ||
		$dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/ || $dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/
	) {
		# Ask for the PhpMyAdmin restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->getInstance()->inputbox(
				"\nPlease enter an username for the restricted phpmyadmin SQL user:$msg", $dbUser
			);

			if($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			} elsif(length $dbUser > 16) {
				$msg = "\n\n\\Z1SQL user names can be up to 16 characters long.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			} elsif($dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/) {
				$msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			$msg = '';

			do {
				# Ask for the PhpMyAdmin restricted SQL user password
				($rs, $dbPass) = $dialog->passwordbox(
					"\nPlease, enter a password for the restricted phpmyadmin SQL user (blank for autogenerate):$msg", $dbPass
				);

				if($dbPass ne '' && $dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/) {
					$msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease, try again:";
					$dbPass = '';
				} else {
					$msg = '';
				}
			} while($rs != 30 && $msg);

			if($rs != 30) {
				if(! $dbPass) {
					my @allowedChr = map { chr } (0x21..0x5b, 0x5d..0x7e);
					$dbPass = '';
					$dbPass .= $allowedChr[rand @allowedChr] for 1..16;
				}

				$dialog->msgbox("\nPassword for the restricted phpmyadmin SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('PHPMYADMIN_SQL_USER', $dbUser);
		main::setupSetQuestion('PHPMYADMIN_SQL_PASSWORD', $dbPass);
	}

	$rs;
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $version = undef;

	if($main::imscpConfig{'SQL_SERVER'} ne 'remote_server') {
		$version = $1 if($main::imscpConfig{'SQL_SERVER'} =~ /([0-9]+\.[0-9]+)$/);
	} else {
		$version = iMSCP::Database->factory()->doQuery(1, 'SELECT VERSION()');
		unless(ref $version eq 'HASH') {
			error($version);
			return 1;
		}

		$version = $1 if(((keys %{$version})[0]) =~ /^([0-9]+\.[0-9]+)/);
	}

	unless(defined $version) {
		error('Unable to find MySQL server version');
		return 1;
	}

	my $pmaBranch = (qv("v$version") >= qv('v5.5')) ? '0.3.0' : '0.2.0';

	iMSCP::Addons::ComposerInstaller->getInstance()->registerPackage('imscp/phpmyadmin', "$pmaBranch.*\@dev");
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	# Backup current configuration file if it exists (only relevant when running imscp-setup)
	my $rs = $self->_backupConfigFile(
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'PHPMYADMIN_CONF_DIR'}/config.inc.php"
	);
	return $rs if $rs;

	# Install phpmyadmin files from local addon packages repository
	$rs = $self->_installFiles();
	return $rs if $rs;

	# Setup phpmyadmin database
	$rs = $self->_setupDatabase();
	return $rs if $rs;

	# Setup phpmyadmin restricted SQL user
	$rs = $self->_setupSqlUser();
	return $rs if $rs;

	# Generate Blowfish secret
	$rs = $self->_generateBlowfishSecret();
	return $rs if $rs;

	# Build new configuration files
	$rs = $self->_buildConfig();
	return $rs if $rs;

	# Set new phpMyAdmin version
	$rs = $self->_setVersion();
	return $rs if $rs;

	# Save configuration
	$self->_saveConfig();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $panelUName =
	my $panelGName =
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	setRights(
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Addons::PhpMyAdmin::Installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'phpmyadmin'} = Addons::PhpMyAdmin->getInstance();
	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'cfgDir'} = $self->{'phpmyadmin'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = $self->{'phpmyadmin'}->{'config'};

	# Merge old config file with new config file
	my $oldConf = "$self->{'cfgDir'}/phpmyadmin.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', 'fileName' => $oldConf;

		for(keys %oldConfig) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $oldConfig{$_};
			}
		}
	}

	$self;
}

=item _backupConfigFile()

 Backup the given configuration file

 Return int 0

=cut

sub _backupConfigFile
{
	my ($self, $cfgFile) = @_;

	if(-f $cfgFile) {
		my $filename = fileparse($cfgFile);

		my $file = iMSCP::File->new('filename' => $cfgFile);
		my $rs = $file->copyFile("$self->{'bkpDir'}/$filename." . time);

		return $rs if $rs;
	}

	0;
}

=item _installFiles()

 Install files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $repoDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/addons";
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/phpmyadmin") {
		my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

		my ($stdout, $stderr);
		$rs = execute("$main::imscpConfig{'CMD_RM'} -fR $guiPublicDir/tools/pma", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -R $repoDir/vendor/imscp/phpmyadmin $guiPublicDir/tools/pma",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	} else {
		error("Couldn't find the imscp/phpmyadmin package into the local repository");
		$rs = 1;
	}

	$rs;
}

=item _saveConfig()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConfig
{
	my $self = $_[0];

	iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/phpmyadmin.data"
	)->copyFile(
		"$self->{'cfgDir'}/phpmyadmin.old.data"
	);
}

=item _setupSqlUser()

 Setup restricted SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
	my $self = $_[0];

	my $phpmyadminDbName = main::setupGetQuestion('DATABASE_NAME') . '_pma';

	my $dbUser = main::setupGetQuestion('PHPMYADMIN_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = main::setupGetQuestion('PHPMYADMIN_SQL_PASSWORD');

	my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

	# Removing any old SQL user (including privileges)
	for my $sqlUser ($dbOldUser, $dbUser) {
		next unless $sqlUser;

		for my $host(
			$dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, $main::imscpOldConfig{'DATABASE_HOST'},
			$main::imscpOldConfig{'BASE_SERVER_IP'}
		) {
			next unless $host;

			if(main::setupDeleteSqlUser($sqlUser, $host)) {
				error('Unable to remove SQL user or one of its privileges.');
				return 1;
			}
		}
	}

	# Getting SQL connection with full privileges
	my ($db, $errStr) = main::setupGetSqlConnect();
	fatal("Unable to connect to SQL server: $errStr") unless $db;

	# Adding new SQL user with needed privileges

	my $rs = $db->doQuery('dummy', 'GRANT USAGE ON `mysql`.* TO ?@? IDENTIFIED BY ?', $dbUser, $dbUserHost, $dbPass);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privilege: $rs");
		return 1;
	}

	$rs = $db->doQuery('dummy', 'GRANT SELECT ON `mysql`.`db` TO ?@?', $dbUser, $dbUserHost);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privilege: $rs");
		return 1;
	}

	$rs = $db->doQuery(
		'dummy',
		'
			GRANT SELECT (Host, User, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv,
				Reload_priv, Shutdown_priv, Process_priv, File_priv, Grant_priv, References_priv, Index_priv,
				Alter_priv, Show_db_priv, Super_priv, Create_tmp_table_priv, Lock_tables_priv, Execute_priv,
				Repl_slave_priv, Repl_client_priv)
			ON `mysql`.`user`
			TO ?@?
		',
		$dbUser, $dbUserHost
	);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privilege: $rs");
		return 1;
	}

	# Check for mysql.host table existence (as for MySQL >= 5.6.7, the mysql.host table is no longer provided)
	$rs = $db->doQuery('1', "SHOW tables FROM mysql LIKE 'host'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	} elsif(%{$rs}) {
		$rs = $db->doQuery('dummy', 'GRANT SELECT ON `mysql`.`host` TO ?@?', $dbUser, $dbUserHost);
		unless(ref $rs eq 'HASH') {
			error("Unable to add privilege: $rs");
			return 1;
		}

		$rs = $db->doQuery(
			'dummy',
			'
				GRANT SELECT (`Host`, `Db`, `User`, `Table_name`, `Table_priv`, `Column_priv`)
				ON `mysql`.`tables_priv`
				TO?@?
			',
			$dbUser,
			$dbUserHost
		);
		unless(ref $rs eq 'HASH') {
			error("Unable to add privilege: $rs");
			return 1;
		}
	}

	$rs = $db->doQuery('dummy', "GRANT ALL PRIVILEGES ON `$phpmyadminDbName`.* TO ?@?;",  $dbUser, $dbUserHost);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privilege: $rs");
		return 1;
	}

	# Store database user and password in config file
	$self->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;

	0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
	my $self = $_[0];

	my $phpmyadminDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma";
	my $phpmyadminDbName = main::setupGetQuestion('DATABASE_NAME') . '_pma';

	# Getting SQL connection with full privileges
	my ($db, $errStr) = main::setupGetSqlConnect();
	fatal("Unable to connect to SQL server: $errStr") if ! $db;

	my $quotedDbName = $db->quoteIdentifier($phpmyadminDbName);

	# Check for database existence
	my $rs = $db->doQuery('1', 'SHOW DATABASES LIKE ?', $phpmyadminDbName);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	} elsif(%{$rs}) {
		# Ensure that the database has tables (recovery case)
		$rs = $db->doQuery('1', "SHOW TABLES FROM $quotedDbName");
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}
	}

	# Database doesn't exist or doesn't have any table
	unless(%{$rs}) {
		$rs = $db->doQuery(
			'dummy', "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;"
		);
		unless(ref $rs eq 'HASH') {
			error("Unable to create the PhpMyAdmin '$phpmyadminDbName' SQL database: $rs");
			return 1;
		}
	}

	# In any case (new install / upgrade) we execute queries from the create_tables.sql file. On upgrade, this will
	# create the missing tables

	# Connecting to newly created database
	($db, $errStr) = main::setupGetSqlConnect($phpmyadminDbName);
	fatal("Unable to connect to SQL server: $errStr") if ! $db;

	# Import database schema
	my $schemaFile = iMSCP::File->new('filename' => "$phpmyadminDir/examples/create_tables.sql")->get();
	unless(defined $schemaFile) {
		error("Unable to read $phpmyadminDir/examples/create_tables.sql");
		return 1;
	}

	$schemaFile =~ s/^(--[^\n]{0,})?\n//gm;

	for ((split /;\n/, $schemaFile)) {
		# The PhpMyAdmin script contains the creation of the database as well
		# We ignore this part as the database has already been created
		if ($_ !~ /^CREATE DATABASE/ and $_ !~ /^USE/) {
			$rs = $db->doQuery('dummy', $_);

			unless(ref $rs eq 'HASH') {
				error("Unable to execute SQL query: $rs");
				return 1;
			}
		}
	}

	0;
}

=item _setVersion()

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
	my $self = $_[0];

	my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

	my $json = iMSCP::File->new('filename' => "$guiPublicDir/tools/pma/composer.json")->get();
	unless(defined $json) {
		error("Unable to read $guiPublicDir/tools/pma/composer.json");
		return 1;
	}

	$json = decode_json($json);
	debug("Set new phpMyAdmin version to $json->{'version'}");
	$self->{'config'}->{'PHPMYADMIN_VERSION'} = $json->{'version'};

	0;
}

=item _generateBlowfishSecret()

 Generate blowfish secret

 Return int 0

=cut

sub _generateBlowfishSecret
{
	my $blowfishSecret = '';
	$blowfishSecret .= ('A'..'Z', 'a'..'z', '0'..'9', '_', '+', '-', '^', '=', '*', '{', '}', '~')[rand(70)] for 1..56;

	$_[0]->{'config'}->{'BLOWFISH_SECRET'} = $blowfishSecret;

	0;
}

=item _buildConfig()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
	my $self = $_[0];

	my $panelUName = my $panelGName =
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $confDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'PHPMYADMIN_CONF_DIR'}";

	my $dbName = main::setupGetQuestion('DATABASE_NAME') . '_pma';
	my $dbUser = main::setupGetQuestion('PHPMYADMIN_SQL_USER');
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	(my $dbPass = main::setupGetQuestion('PHPMYADMIN_SQL_PASSWORD')) =~ s%(')%\\$1%g;

	# Define data

	my $data = {
		PMA_DATABASE => $dbName,
		PMA_USER => $dbUser,
		PMA_PASS => $dbPass,
		HOSTNAME => $dbHost,
		PORT => $dbPort,
		UPLOADS_DIR => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/uploads",
		TMP_DIR => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
		BLOWFISH => $self->{'config'}->{'BLOWFISH_SECRET'}
	};

	# Load template

	my $cfgTpl;
	my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'phpmyadmin', 'imscp.config.inc.php', \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new('filename' => "$confDir/imscp.config.inc.php")->get();
		unless(defined $cfgTpl) {
			error("Unable to read file $confDir/imscp.config.inc.php");
			return 1;
		}
	}

	# Build file

	$cfgTpl = process($data, $cfgTpl);

	# Store file

	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$_");
	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($panelUName, $panelGName);
	return $rs if $rs;

	$file->copyFile("$confDir/config.inc.php");
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
