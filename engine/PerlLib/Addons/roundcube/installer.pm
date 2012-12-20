#!/usr/bin/perl

=head1 NAME

Addons::roundcube::installer - i-MSCP Roundcube addon installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::roundcube::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Addons::ComposerInstaller;
use iMSCP::Rights;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the installer for the i-MSCP Roundcube addon.

 See Addons::roundcube for more information.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(HooksManager)

 Register Roundcube setup hook functions.

 Param iMSCP::HooksManager instance
 Return int - 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	# Add roundcube installer dialog in setup dialog stack
	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askRoundcube(@_) }); 0; }
	);

	0;
}

=item preinstall()

 Register Roundcube composer package for installation.

 Return int - 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	iMSCP::Addons::ComposerInstaller->getInstance()->registerPackage('imscp/roundcube');
}

=item install()

 Process Roundcube addon install tasks.

 Return int - 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;
	my $rs	= 0;

	$self->{'httpd'} = Servers::httpd->factory();

	$self->{'user'} = $self->{'httpd'}->can('getRunningUser')
		? $self->{'httpd'}->getRunningUser() : $main::imscpConfig{'ROOT_USER'};

	$self->{'group'} = $self->{'httpd'}->can('getRunningGroup')
		? $self->{'httpd'}->getRunningGroup() : $main::imscpConfig{'ROOT_GROUP'};

	# Backup current configuration files if they exists (only relevant when running imscp-setup)
	for (
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::roundcubeConfig{'ROUNDCUBE_CONF_DIR'}/db.inc.php",
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::roundcubeConfig{'ROUNDCUBE_CONF_DIR'}/main.inc.php",
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::roundcubeConfig{'ROUNDCUBE_PWCHANGER_DIR'}/config.inc.php"
	) {
		$rs |= $self->_backupConfigFile($_);
	}

	$rs |= $self->_installFiles();		# Install roundcube files from local addon packages repository
	$rs |= $self->_setPermissions();	# Set roundcube permissions
	$rs |= $self->_createDatabase();	# Create/update roundcube database
	$rs |= $self->_setupDatabase();		# Setup roundcube database
	$rs |= $self->_generateDESKey();	# Generate DES key
	$rs |= $self->_savePlugins();		# Save plugins
	$rs |= $self->_buildConfig();		# Build new configuration files
	$rs |= $self->_saveConfig();		# Save configuration

	$rs;
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item askRoundcube()

 Show roundcube questions.

 Hook function responsible to show Roundcube installer questions.

 Param iMSCP::Dialog
 Return int - 0 or 30

=cut

sub askRoundcube
{
	my $self = shift;
	my $dialog = shift;

	my $dbType = $main::imscpConfig{'DATABASE_TYPE'};
    my $dbHost = $main::imscpConfig{'DATABASE_HOST'};
    my $dbPort = $main::imscpConfig{'DATABASE_PORT'};
    my $dbName = $main::imscpConfig{'DATABASE_NAME'};

	my $dbUser = $main::preseed{'ROUNDCUBE_SQL_USER'} || $self::roundcubeConfig{'DATABASE_USER'} ||
		$self::roundcubeOldConfig{'DATABASE_USER'} || 'roundcube_user';

	my $dbPass = $main::preseed{'ROUNDCUBE_SQL_PASSWORD'} || $self::roundcubeConfig{'DATABASE_PASSWORD'} ||
		$self::roundcubeOldConfig{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if($main::reconfigure || main::setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)) {
		# Ask for the roundcube restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter an username for the restricted roundcube SQL user:", $dbUser
			);

			# i-MSCP SQL user cannot be reused
			if($dbUser eq $main::imscpConfig{'DATABASE_USER'}){
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			# Ask for the roundcube restricted SQL user password
			($rs, $dbPass) = $dialog->inputbox(
				'\nPlease, enter a password for the restricted roundcube SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if(! $dbPass) {
					$dbPass = '';
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');
					$dbPass .= $allowedChars[rand()*($#allowedChars + 1)]for (1..16);
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted roundcube SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		$self::roundcubeConfig{'DATABASE_USER'} = $dbUser;
    	$self::roundcubeConfig{'DATABASE_PASSWORD'} = $dbPass;
    }

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize Roundcube addon installer instance.

 Return Addons::roundcube::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/roundcube";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";
	$self->{'forceDbSetup'} = '';

	my $conf = "$self->{cfgDir}/roundcube.data";
	my $oldConf	= "$self->{cfgDir}/roundcube.old.data";

	tie %self::roundcubeConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if($oldConf) {
		tie %self::roundcubeOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::roundcubeConfig = (%self::roundcubeConfig, %self::roundcubeOldConfig);
	}

	$self;
}

=item _backupConfigFile()

 Backup the given Roundcube configuration file.

 Return int - 0

=cut

sub _backupConfigFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $timestamp = time;

	use File::Basename;

	my ($name, $path, $suffix) = fileparse($cfgFile);

	if(-f $cfgFile) {
		my $file = iMSCP::File->new(filename => $cfgFile);
		$file->copyFile("$self->{bkpDir}/$name$suffix.$timestamp") and return 1;
	}

	0;
}

=item _installFiles()

 Install Roundcube files in production directory.

 Return int - 0 on success, other on failure

=cut

sub _installFiles
{
	my $self = shift;
	my $repoDir = $main::imscpConfig{'ADDON_PACKAGES_CACHE_DIR'};
	my ($stdout, $stderr) = (undef, undef);
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/roundcube") {
		$rs = execute(
			"$main::imscpConfig{CMD_CP} -rTf $repoDir/vendor/imscp/roundcube $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;

		$rs |= execute(
			"$main::imscpConfig{CMD_RM} -rf $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/.git",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
	} else {
		error("Couldn't find the imscp/roundcube package into the local repository");
		$rs = 1;
	}

	$rs;
}

=item _setPermissions()

 Set Roundcube files permissions.

 Return int - 0 on success, other on failure

=cut

sub _setPermissions
{
	my $self = shift;
	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $panelUName;
	my $rootDir = $main::imscpConfig{'ROOT_DIR'};
	my $apacheGName = $self->{'group'};
	my $rs = 0;

	$rs |= setRights(
		"$rootDir/gui/public/tools/webmail",
		{ user => $panelUName, group => $apacheGName, dirmode => '0550', filemode => '0440', recursive => 'yes' }
	);

	$rs |= setRights(
		"$rootDir/gui/public/tools/webmail/logs",
		{ user => $panelUName, group => $panelGName, dirmode => '0750', filemode => '0640', recursive => 'yes' }
	);

	$rs;
}

=item _saveConfig()

 Save Roundcube configuration.

 Return int - 0 on success, 1 on failure

=cut

sub _saveConfig
{
	my $self = shift;
	my $rootUsr = $main::imscpConfig{'ROOT_USER'};
	my $rootGrp = $main::imscpConfig{'ROOT_GROUP'};
	my $rs = 0;

	use iMSCP::File;

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/roundcube.data");
	my $cfg = $file->get();
	return 1 unless $cfg;

	$rs |= $file->mode(0640);
	$rs |= $file->owner($rootUsr, $rootGrp);

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/roundcube.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($rootUsr, $rootGrp);

	$rs;
}

=item _createDatabase()

 Create or update Roundcube database

 Return int - 0 on success other on failure

=cut

sub _createDatabase
{
	my $self = shift;
	my $roundcubeDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
	my $dbName = 'imscp_roundcube';

	my ($database, $errStr) = main::setupGetSqlConnect();
	fatal('Unable to connect to SQL Server: $errStr') if ! $database;

	# Check for database existence
	my $rs = $database->doQuery('1', 'SHOW DATABASES LIKE ?', $dbName);
	fatal('SQL query failed: $rs') if ref $rs ne 'HASH';

	if(! %$rs) {
		my $qdbName = $database->quoteIdentifier($dbName);
		my $rs = $database->doQuery('dummy', "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
		fatal("Unable to create the '$dbName' SQL database: $rs") if ref $rs ne 'HASH';

		$database->set('DATABASE_NAME', $dbName);
		$rs = $database->connect();
		return $rs if $rs;

		$rs = main::setupImportSqlSchema($database, "$roundcubeDir/SQL/mysql.initial.sql");
		return $rs if $rs;

		$self->{'forceDbSetup'} = 'true';
	}

	if(! $self->{'forceDbSetup'}) {
		my $fromVersion = $self->_parseVersion($self::roundcubeOldConfig{'ROUNDCUBE_VERSION'} || '0.8.4');
		my $newVersion = $self->_parseVersion($self::roundcubeConfig{'ROUNDCUBE_VERSION'});
		my $needUpdate = `$main::imscpConfig{'CMD_PHP'} -r "print (version_compare('$fromVersion', '$newVersion', '>'));"`;

		if($fromVersion && $needUpdate) {
			open my($file), '<', "$roundcubeDir/SQL/mysql.update.sql"
				or die "Couldn't found the Roundcube database schema update file : $!\n";

			my $from = 0;
			my $sql = '';

			while(my $line = <$file>) {
				chomp($line);
				next if ! $line; # skip empty line
				my $isComment = (index($line, '--') == 0);

				if(! $from && $isComment && $line =~ /from version\s([0-9.]+[a-z-]*)/) {
					my $fileVersion = $self->_parseVersion($1);

					if(
						$fileVersion eq $fromVersion ||
						`$main::imscpConfig{'CMD_PHP'} -r "print (version_compare('$fromVersion', '$fileVersion', '<='));"`
					) {
						$from = 1;
					}
				}

				$sql .= $line . "\n" if $from && ! $isComment;
			}

			# Save roundcube db update file in temporary directory
			#$file = iMSCP::File->new(filename => '/tmp/roundcube_update.sql');
			#$file->set($sql) and return 1;
			#$file->save() and return 1;

			$database->set('DATABASE_NAME', $dbName);
			$rs = $database->connect();
			return $rs if $rs;

			# Update roundcube database
			$rs = $database->doQuery('dummy', $sql);
			error("Query failed: $rs") if $rs;

			#$rs = main::setupImportSqlSchema($database, '/tmp/roundcube_update.sql');
			#return $rs if $rs;

			# Remove temporary file
			#$file->delFile() and return 1;
		}
	}

	0;
}

=item _parseVersion()

 Parse Roundcube version.

 Return string

=cut

sub _parseVersion( $ $)
{
	my $self = shift;
	my $version = shift;

	$version =~ s/-stable/.0/; # stable is lower than rc, beta and alpha
	$version =~ s/-git/.99/; # git is greater than stable, rc, beta and alpha

	lc($version);
}

=item _setupDatabase()

 Setup Roundcube database.

 Return int - 0 on success, 1 on failure

=cut

sub _setupDatabase
{
	my $self = shift;
	my $dbUser = $self::roundcubeConfig{'DATABASE_USER'};
	my $dbOldUser = $self::roundcubeOldConfig{'DATABASE_USER'} || '';
	my $dbPass = $self::roundcubeConfig{'DATABASE_PASSWORD'};
	my $dbOldPass = $self::roundcubeOldConfig{'DATABASE_PASSWORD'} || '';
	my $rs = 0;

	if($dbUser ne $dbOldUser || $dbPass ne $dbOldPass || $self->{'forceDbSetup'}) {

		# Remove old proftpd restricted SQL user and all it privileges (if any)
		$rs = main::setupDeleteSqlUser($dbOldUser);
		error("Unable to remove the old roundcube '$dbOldUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;

		# Ensure new proftpd user do not already exists by removing it
		$rs = main::setupDeleteSqlUser($dbUser);
		error("Unable to delete the roundcube '$dbUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;

		# Get SQL connection with full privileges
		my $database = main::setupGetSqlConnect();

		# Add new roundcube restricted SQL user with needed privilegess

		$rs = $database->doQuery(
			'dummy',
			"GRANT ALL PRIVILEGES ON `imscp\_roundcube`.* TO ?@? IDENTIFIED BY ?;",
			$dbUser,
			$main::imscpConfig{'DATABASE_HOST'},
			$dbPass
		);
		if(ref $rs ne 'HASH') {
			error("Unable to add privileges on the 'imscp_roundcube.$_' table for the '$dbUser' SQL user: $rs");
			return 1;
		}

		$rs = $database->doQuery(
			'dummy',
			"GRANT SELECT,UPDATE ON `$main::imscpConfig{'DATABASE_NAME'}`.`mail_users` TO ?@? IDENTIFIED BY ?;",
			$dbUser,
			$main::imscpConfig{'DATABASE_HOST'},
			$dbPass
		);

		if(ref $rs ne 'HASH') {
			error(
				"Unable to add privileges on the '$main::imscpConfig{'DATABASE_NAME'}.mail_users' table for the" .
				" '$dbUser' SQL user: $rs"
			);
			return 1;
		}
	}

	0;
}

=item _generateDESKey()

 Generate DES key for Roundcube.

 Return int - 0

=cut

sub _generateDESKey
{
	my $self = shift;

	$self::roundcubeConfig{'DES_KEY'} = $self::roundcubeOldConfig{'DES_KEY'}
		if ! $self::roundcubeConfig{'DES_KEY'} && $self::roundcubeOldConfig{'DES_KEY'};

	unless($self::roundcubeConfig{'DES_KEY'}) {
		my $DESKey = '';
		my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');

		$DESKey .= $allowedChars[rand()*($#allowedChars + 1)] for (1..24);
		$self::roundcubeConfig{'DES_KEY'} = $DESKey;
	}

	0;
}

=item _savePlugins()

 Save Roundcube plugins.

 Return int - 0

=cut

sub _savePlugins
{
	my $self = shift;

	$self::roundcubeConfig{'PLUGINS'} = $self::roundcubeOldConfig{'PLUGINS'}
		if ! $self::roundcubeConfig{'PLUGINS'} && $self::roundcubeOldConfig{'PLUGINS'};

	0;
}

=item _buildConfig()

 Process Roundcube addon install tasks.

 Return int - 0 on success, 1 on failure

=cut

sub _buildConfig
{
	my $self = shift;
	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rs = 0;

	use Servers::mta;

	my $cfg = {
		DB_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DB_USER => $self::roundcubeConfig{'DATABASE_USER'},
		DB_PASS => $self::roundcubeConfig{'DATABASE_PASSWORD'},
		DB_NAME => 'imscp_roundcube',
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		TMP_PATH => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
		DES_KEY => $self::roundcubeConfig{'DES_KEY'},
		PLUGINS => $self::roundcubeConfig{'PLUGINS'},
	};

	my $cfgFiles = {
		'db.inc.php' => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::roundcubeConfig{'ROUNDCUBE_CONF_DIR'}/db.inc.php",
		'main.inc.php' => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::roundcubeConfig{'ROUNDCUBE_CONF_DIR'}/main.inc.php",
		'config.inc.php' => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::roundcubeConfig{'ROUNDCUBE_PWCHANGER_DIR'}/config.inc.php"
	};

	for (keys %{$cfgFiles}) {
		my $file = iMSCP::File->new(filename => "$self->{cfgDir}/$_");
		my $cfgTpl = $file->get();

		if (! $cfgTpl) {
			$rs = 1;
			next;
		}

		$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);

		if (! $cfgTpl) {
			$rs = 1;
			next;
		}

		# store file in working directory
		$file = iMSCP::File->new(filename => "$self->{wrkDir}/$_");
		$rs |= $file->set($cfgTpl);
		$rs |= $file->save();
		$rs |= $file->mode(0640);
		$rs |= $file->owner($panelUName, $panelGName);

		# Install new file in production directory
		$rs |= $file->copyFile($cfgFiles->{$_});
	}

	0;
}

=back

=head1 AUTHORS

 - Daniel Andreca <sci2tech@gmail.com>
 - Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
