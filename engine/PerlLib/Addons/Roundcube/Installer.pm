#!/usr/bin/perl

=head1 NAME

Addons::Roundcube::Installer - i-MSCP Roundcube addon installer

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
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::Roundcube::Installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Addons::ComposerInstaller;
use iMSCP::File;
use File::Basename;
use parent 'Common::SingletonClass';

our $VERSION = '0.3.0';

=head1 DESCRIPTION

 This is the installer for the i-MSCP Roundcube addon.

 See Addons::Roundcube for more information.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(\%hooksManager)

 Register Roundcube setup hook functions.

 Param iMSCP::HooksManager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->showDialog(@_) }); 0; }
	);
}

=item showDialog(\%dialog)

 Show Roundcube installer questions.

 Param iMSCP::Dialog::Dialog|iMSCP::Dialog::Whiptail $dialog
 Return int 0 or 30

=cut

sub showDialog($$)
{
	my ($self, $dialog) = @_;

	my $dbType = main::setupGetQuestion('DATABASE_TYPE');
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');

	my $dbUser = main::setupGetQuestion('ROUNDCUBE_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'roundcube_user';
	my $dbPass = main::setupGetQuestion('ROUNDCUBE_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if($main::reconfigure ~~ ['webmail', 'all', 'forced'] || ! ($dbUser && $dbPass)) {
		# Ask for the roundcube restricted SQL username
		do{

			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter a username for the restricted Roundcube SQL user:$msg", $dbUser
			);

			if($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			} elsif(length $dbUser > 16) {
				$msg = "\n\n\\Z1MySQL user names can be up to 16 characters long.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			# Ask for the roundcube restricted SQL user password
			($rs, $dbPass) = $dialog->passwordbox(
				'\nPlease, enter a password for the restricted roundcube SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if(! $dbPass) {
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');

					$dbPass = '';
					$dbPass .= $allowedChars[rand @allowedChars] for 1..16;
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted roundcube SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		$self->{'config'}->{'DATABASE_USER'} = $dbUser;
		$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
	}

	$rs;
}

=item preinstall()

 Register Roundcube addon package for installation.

 Return int 0

=cut

sub preinstall
{
	iMSCP::Addons::ComposerInstaller->getInstance()->registerPackage('imscp/roundcube', "$VERSION.*\@dev");
}

=item install()

 Process Roundcube addon install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = 0;

	# Backup current configuration files if they exists (only relevant when running imscp-setup)
	for (
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'ROUNDCUBE_CONF_DIR'}/db.inc.php",
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'ROUNDCUBE_CONF_DIR'}/main.inc.php",
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'ROUNDCUBE_PWCHANGER_DIR'}/config.inc.php"
	) {
		$rs = $self->_backupConfigFile($_);
		return $rs if $rs;
	}

	# Install Roundcube files from local addon packages repository
	$rs = $self->_installFiles();
	return $rs if $rs;

	# Setup Roundcube database (database, user)
	$rs = $self->_setupDatabase();
	return $rs if $rs;

	# Generate Roundcube DES key
	$rs = $self->_generateDESKey();
	return $rs if $rs;

	# Build new Roundcube configuration files
	$rs = $self->_buildConfig();
	return $rs if $rs;

	# Update Roundcube database if needed (should be done after roundcube config files generation)
	$rs = $self->_updateDatabase() unless $self->{'newInstall'};
	return $rs if $rs;

	# Set new Roundcube version
	$rs = $self->_setVersion();
	return $rs if $rs;

	# Save Roundcube addon configuration file
	$self->_saveConfig();
}

=item setGuiPermissions()

 Set Roundcube files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

	require iMSCP::Rights;
	iMSCP::Rights->import();

	my $rs = setRights(
		"$guiPublicDir/tools/webmail",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
	return $rs if $rs;

	setRights(
		"$guiPublicDir/tools/webmail/logs",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 1 }
	);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize Roundcube addon installer instance.

 Return Addons::Roundcube::Installer

=cut

sub _init
{
	my $self = shift;

	$self->{'roundcube'} = Addons::Roundcube->getInstance();

	$self->{'cfgDir'} = $self->{'roundcube'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'newInstall'} = 1;

	$self->{'config'} = $self->{'roundcube'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/roundcube.old.data";

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

=item _backupConfigFile($cfgFile)

 Backup the given Roundcube configuration file.

 Param string $cfgFile Path of file to backup
 Return int 0, other on failure

=cut

sub _backupConfigFile($$)
{
	my ($self, $cfgFile) = @_;

	if(-f $cfgFile) {
		my $filename = fileparse($cfgFile);
		my $file = iMSCP::File->new('filename' => $cfgFile);
		my $rs = $file->copyFile("$self->{'bkpDir'}/$filename" . time);

		return $rs if $rs;
	}

	0;
}

=item _installFiles()

 Install Roundcube files in production directory.

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $repoDir = $main::imscpConfig{'ADDON_PACKAGES_CACHE_DIR'};
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/roundcube") {
		my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

		require iMSCP::Execute;
		iMSCP::Execute->import();

		my ($stdout, $stderr);
		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -fR $repoDir/vendor/imscp/roundcube $guiPublicDir/tools/webmail",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute("$main::imscpConfig{'CMD_RM'} -rf $guiPublicDir/tools/webmail/.git", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	} else {
		error("Couldn't find the imscp/roundcube package into the local repository");
		$rs = 1;
	}

	$rs;
}

=item _setupDatabase()

 Setup Roundcube database.

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
	my $self = shift;

	my $roundcubeDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
	my $imscpDbName = $main::imscpConfig{'DATABASE_NAME'};
	my $roundcubeDbName = $imscpDbName . '_roundcube';

	my $dbUser = $self->{'config'}->{'DATABASE_USER'};
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'};

	my $dbOldUser = $self->{'oldConfig'}->{'DATABASE_USER'} || '';

	# Get SQL connection with full privileges
	my ($database, $errStr) = main::setupGetSqlConnect();
	if(! $database) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	# Check for Roundcube database existence
	my $rs = $database->doQuery('1', 'SHOW DATABASES LIKE ?', $roundcubeDbName);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	# The Roundcube database doesn't exist, create it
	unless(%$rs) {
		my $qdbName = $database->quoteIdentifier($roundcubeDbName);
		$rs = $database->doQuery('dummy', "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
		unless(ref $rs eq 'HASH') {
			error("Unable to create the Roundcube '$roundcubeDbName' SQL database: $rs");
			return 1;
		}

		# Connect to newly created Roundcube database
		$database->set('DATABASE_NAME', $roundcubeDbName);

		$rs = $database->connect();
		if($rs) {
			error("Unable to connect to the Roundcube '$roundcubeDbName' SQL database: $rs");
			return $rs if $rs;
		}

		# Import Roundcube database schema
		$rs = main::setupImportSqlSchema($database, "$roundcubeDir/SQL/mysql.initial.sql");
		return $rs if $rs;
	} else {
		$self->{'newInstall'} = 0;
	}

	# Remove any old roundcube SQL user (including privileges)
	for my $sqlUser ($dbOldUser, $dbUser) {
		next if ! $sqlUser;

		for($dbUserHost, $main::imscpOldConfig{'DATABASE_HOST'}, $main::imscpOldConfig{'BASE_SERVER_IP'}) {
			next if ! $_;

			$rs = main::setupDeleteSqlUser($sqlUser, $_);
			error("Unable to remove '$sqlUser\@$_' SQL user or one of its privileges") if $rs;
			return 1 if $rs;
		}
	}

	# Add new Roundcube restricted SQL user with needed privileges

	$rs = $database->doQuery(
		'dummy', "GRANT ALL PRIVILEGES ON `$roundcubeDbName`.* TO ?@? IDENTIFIED BY ?;",  $dbUser, $dbUserHost, $dbPass
	);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privileges on the '$roundcubeDbName' database tables for the Roundcube Roundcube '$dbUser\@$dbUserHost' SQL user: $rs");
		return 1;
	}

	# Needed for password changer plugin
	$rs = $database->doQuery(
		'dummy',
		"GRANT SELECT,UPDATE ON `$imscpDbName`.`mail_users` TO ?@? IDENTIFIED BY ?;",
		$dbUser, $dbUserHost, $dbPass
	);
	unless(ref $rs eq 'HASH') {
		error(
			"Unable to add privileges on the '$imscpDbName.mail_users' table for the '$dbUser\@$dbUserHost' SQL user: $rs"
		);
		return 1;
	}

	0;
}

=item _generateDESKey()

 Generate DES key for Roundcube.

 Return int 0

=cut

sub _generateDESKey
{
	my $self = shift;

	my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_', '+', '-', '^', '=', '*', '{', '}', '~');

	my $desKey = '';
	$desKey .= $allowedChars[rand @allowedChars] for 1..24;

	$self->{'config'}->{'DES_KEY'} = $desKey;

	0;
}

=item _buildConfig()

 Process Roundcube addon install tasks.

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
	my $self = shift;

	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $confDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'ROUNDCUBE_CONF_DIR'}";
	my $imscpDbName = $main::imscpConfig{'DATABASE_NAME'};
	my $roundcubeDbName = $imscpDbName . '_roundcube';
	my $dbUser = $self->{'config'}->{'DATABASE_USER'};
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'};
	my $rs = 0;

	my $cfg = {
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		DB_HOST => $dbUserHost,
		DB_USER => $dbUser,
		DB_PASS => $dbPass,
		TMP_PATH => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
		DES_KEY => $self->{'config'}->{'DES_KEY'},
		PLUGINS => $self->{'config'}->{'PLUGINS'}
	};

	my $cfgFiles = {
		'imscp.db.inc.php' => "$confDir/db.inc.php",
		'imscp.main.inc.php' => "$confDir/main.inc.php",
		'imscp.pw.changer.inc.php' => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'ROUNDCUBE_PWCHANGER_DIR'}/config.inc.php"
	};

	for (keys %{$cfgFiles}) {
		my $cfgTpl = iMSCP::File->new('filename' => "$confDir/$_")->get();
		unless(defined $cfgTpl) {
			error("Unable to read $cfgTpl");
			return 1;
		}

		# The password changer plugin act on the i-MSCP database
		$cfg->{'DB_NAME'} = ($_ eq 'imscp.pw.changer.inc.php') ? $imscpDbName : $roundcubeDbName;

		$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
		unless(defined $cfgTpl) {
			error("Unable to process template: $confDir/$_");
			return 1;
		}

		# Store new file in working directory
		my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$_");

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $file->owner($panelUName, $panelGName);
		return $rs if $rs;

		# Install new file in production directory
		$rs = $file->copyFile($cfgFiles->{$_});
		return $rs if $rs;

		# Remove template file from production directory
		$rs = iMSCP::File->new('filename' => "$confDir/$_")->delFile();
		return $rs if $rs;
	}

	0;
}

=item _updateDatabase()

 Update Roundcube database

 Return int 0 on success other on failure

=cut

sub _updateDatabase
{
	my $self = shift;

	my $roundcubeDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
	my $roundcubeDbName = $main::imscpConfig{'DATABASE_NAME'} . '_roundcube';
	my $fromVersion = $self->{'config'}->{'ROUNDCUBE_VERSION'} || '0.8.4';

	require iMSCP::Execute;
	iMSCP::Execute->import();

	# Check on suhosin.session.encrypt=off will be removed in next roundcube version
	# See http://trac.roundcube.net/ticket/1489044
	my ($stdout, $stderr);
	my $rs = execute(
		"$main::imscpConfig{'CMD_PHP'} -d suhosin.session.encrypt=off $roundcubeDir/bin/updatedb.sh " .
		"--version=$fromVersion --dir=$roundcubeDir/SQL --package=roundcube",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to update roundcube database schema.') if $rs && ! $stderr;

	# Since the updatedb.sh script can exit with 0 on error, we made additional checks to be sure the db schema has been
	# correctly updated (These checks will be removed when http://trac.roundcube.net/ticket/1489044 will be fixed)

	my ($database, $errStr) = main::setupGetSqlConnect($roundcubeDbName);
	if(! $database) {
		error("Unable to connect to the '$roundcubeDbName' SQL database: $errStr");
		return 1;
	}

	my $rdata = $database->doQuery('1', 'SHOW TABLES LIKE ?', 'system');
	unless(ref $rdata eq 'HASH') {
		error("SQL query failed: $rs");
		return 1;
	}

	unless(%$rdata) {
		error("Roundcube database schema update failed: 'system' table not found.");
		return 1
	}

	$rdata = $database->doQuery('name', 'SELECT * FROM `system` WHERE `name` = ?', 'roundcube-version');
	unless(ref $rdata eq 'HASH') {
		error("SQL query failed: $rs");
		return 1;
	}

	if(%$rdata) {
		require iMSCP::Dir;

		my @updateFiles = iMSCP::Dir->new('dirname' => "$roundcubeDir/SQL/mysql", 'fileType' => '.sql')->getFiles();
		unless(@updateFiles) {
			error('Unable to get list of available database updates.');
			return 1;
		}

		s/.sql// for @updateFiles;
		@updateFiles = sort { $a <=> $b } @updateFiles;
		my $lastAvailableUpdate = pop @updateFiles;

		if($rdata->{'roundcube-version'}->{'value'} < $lastAvailableUpdate) {
			error(
				'Roundcube database schema update failed: ' .
				"roundcube-version value ($rs->{'roundcube-version'}->{'value'}) is smaller than the last available " .
				"database update version ($lastAvailableUpdate)"
			);
			return 1
		}
	} else {
		error("Roundcube database schema update failed: roundcube-version value not found in 'system' table.");
		return 1
	}

	$rs;
}

=item _setVersion()

 Set Roundcube version.

 Return int 0 on success, 1 on failure

=cut

sub _setVersion
{
	my $self = shift;

	my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

	require iMSCP::File;
	require JSON;
	JSON->import();

	my $json = iMSCP::File->new('filename' => "$guiPublicDir/tools/webmail/composer.json")->get();
	unless(defined $json) {
		error("Unable to read $guiPublicDir/tools/webmail/composer.json");
		return 1;
	}

	$json = decode_json($json);
	debug("Set new roundcube version to $json->{'version'}");
	$self->{'config'}->{'ROUNDCUBE_VERSION'} = $json->{'version'};

	0;
}

=item _saveConfig()

 Save Roundcube configuration.

 Return int 0 on success, other on failure

=cut

sub _saveConfig
{
	my $self = shift;

	my $rootUname = $main::imscpConfig{'ROOT_USER'};
	my $rootGname = $main::imscpConfig{'ROOT_GROUP'};

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/roundcube.data");

	my $rs = $file->owner($rootUname, $rootGname);
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/roundcube.data");
		return 1;
	}

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/roundcube.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$file->owner($rootUname, $rootGname);
	return $rs if $rs;

	$file->mode(0640);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
