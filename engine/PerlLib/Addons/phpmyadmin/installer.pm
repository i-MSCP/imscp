#!/usr/bin/perl

=head1 NAME

Addons::phpmyadmin::installer - i-MSCP PhpMyAdmin addon installer

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

package Addons::phpmyadmin::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::HooksManager;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the installer for the i-MSCP PhpMyAdmin addon.

 See Addons::phpmyadmin for more information.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(HooksManager)

 Register PhpMyAdmin setup hook functions.

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	# Add phpmyadmin installer dialog in setup dialog stack
	$hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askPhpmyadmin(@_) }); 0; }
	);
}

=item preinstall()

 Register PhpMyAdmin composer package for installation.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	require iMSCP::Addons::ComposerInstaller;
	iMSCP::Addons::ComposerInstaller->getInstance()->registerPackage('imscp/phpmyadmin');
}

=item install()

 Process PhpMyAdmin addon install tasks.

 Return int 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;
	my $rs	= 0;

	# Backup current configuration file if it exists (only relevant when running imscp-setup)
	$rs |= $self->_backupConfigFile(
		"$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::phpmyadminConfig{'PHPMYADMIN_CONF_DIR'}/config.inc.php"
	);

	$rs |= $self->_installFiles();				# Install phpmyadmin files from local addon packages repository
	$rs |= $self->setGuiPermissions();			# Set phpmyadmin permissions
	$rs |= $self->_setupSqlUser();				# Setup phpmyadmin restricted SQL user
	$rs |= $self->_generateBlowfishSecret();	# Generate Blowfish secret
	$rs |= $self->_buildConfig();				# Build new configuration files
	$rs |= $self->_saveConfig();				# Save configuration

	$rs;
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item askPhpmyadmin()

 Show PhpMyAdmin questions.

 Hook function responsible to show PhpMyAdmin installer questions.

 Param iMSCP::Dialog
 Return int 0 or 30

=cut

sub askPhpmyadmin
{
	my $self = shift;
	my $dialog = shift;

	my $dbType = main::setupGetQuestion('DATABASE_TYPE');
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	my $dbName = main::setupGetQuestion('DATABASE_NAME');

	my $dbUser = $main::preseed{'PHPMYADMIN_SQL_USER'} || $self::phpmyadminConfig{'DATABASE_USER'} ||
		$self::phpmyadminOldConfig{'DATABASE_USER'} || 'pma';

	my $dbPass = $main::preseed{'PHPMYADMIN_SQL_PASSWORD'} || $self::phpmyadminConfig{'DATABASE_PASSWORD'} ||
		$self::phpmyadminOldConfig{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ ['phpmyadmin', 'all', 'forced'] ||
		(
			! $main::preseed{'PHPMYADMIN_SQL_USER'} &&
			main::setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)
		)
	) {
		# Ask for the phpmyadmin restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter an username for the restricted phpmyadmin SQL user:", $dbUser
			);

			# i-MSCP SQL user cannot be reused
			if($dbUser eq $main::imscpConfig{'DATABASE_USER'}){
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			# Ask for the phpmyadmin restricted SQL user password
			($rs, $dbPass) = $dialog->inputbox(
				'\nPlease, enter a password for the restricted phpmyadmin SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if(! $dbPass) {
					$dbPass = '';
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');
					$dbPass .= $allowedChars[rand()*($#allowedChars + 1)]for (1..16);
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted phpmyadmin SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		$self::phpmyadminConfig{'DATABASE_USER'} = $dbUser;
		$self::phpmyadminConfig{'DATABASE_PASSWORD'} = $dbPass;
	}

	$rs;
}

=item setGuiPermissions()

 Set PhpMyAdmin files permissions.

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $self = shift;
	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $rootDir = $main::imscpConfig{'ROOT_DIR'};
	my $rs = 0;

	require Servers::httpd;
	my $http = Servers::httpd->factory();
	my $apacheGName = $http->can('getRunningGroup') ? $http->getRunningGroup() : $main::imscpConfig{'ROOT_GROUP'};

	require iMSCP::Rights;
	iMSCP::Rights->import();

	$rs |= setRights(
		"$rootDir/gui/public/tools/pma",
		{ 'user' => $panelUName, 'group' => $apacheGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 'yes' }
	);

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize PhpMyAdmin addon installer instance.

 Return Addons::phpmyadmin::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/pma";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/phpmyadmin.data";
	my $oldConf	= "$self->{'cfgDir'}/phpmyadmin.old.data";

	tie %self::phpmyadminConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if(-f $oldConf) {
		tie %self::phpmyadminOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::phpmyadminConfig = (%self::phpmyadminConfig, %self::phpmyadminOldConfig);
	}

	$self;
}

=item _backupConfigFile()

 Backup the given PhpMyAdmin configuration file.

 Return int 0

=cut

sub _backupConfigFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $timestamp = time;

	require File::Basename;
	File::Basename->import();

	my ($name, $path, $suffix) = fileparse($cfgFile);

	if(-f $cfgFile) {
		require iMSCP::File;

		my $file = iMSCP::File->new(filename => $cfgFile);
		$file->copyFile("$self->{'bkpDir'}/$name$suffix.$timestamp") and return 1;
	}

	0;
}

=item _installFiles()

 Install PhpMyAdmin files in production directory.

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $self = shift;
	my $repoDir = $main::imscpConfig{'ADDON_PACKAGES_CACHE_DIR'};
	my ($stdout, $stderr) = (undef, undef);
	my $rs = 0;

	if(-d "$repoDir/vendor/imscp/phpmyadmin") {

		require iMSCP::Execute;
		iMSCP::Execute->import();

		$rs = execute(
			"$main::imscpConfig{'CMD_CP'} -rTf $repoDir/vendor/imscp/phpmyadmin $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;

		$rs |= execute(
			"$main::imscpConfig{'CMD_RM'} -rf $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/.git",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
	} else {
		error("Couldn't find the imscp/phpmyadmin package into the local repository");
		$rs = 1;
	}

	$rs;
}

=item _saveConfig()

 Save PhpMyAdmin configuration.

 Return int 0 on success, 1 on failure

=cut

sub _saveConfig
{
	my $self = shift;
	my $rootUsr = $main::imscpConfig{'ROOT_USER'};
	my $rootGrp = $main::imscpConfig{'ROOT_GROUP'};
	my $rs = 0;

	require iMSCP::File;

	my $file = iMSCP::File->new(filename => "$self->{'cfgDir'}/phpmyadmin.data");
	my $cfg = $file->get();
	return 1 unless $cfg;

	$rs |= $file->mode(0640);
	$rs |= $file->owner($rootUsr, $rootGrp);

	$file = iMSCP::File->new(filename => "$self->{'cfgDir'}/phpmyadmin.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($rootUsr, $rootGrp);

	$rs;
}

=item _setupSqlUser()

 Setup PhpMyAdmin restricted SQL user.

 Return int 0 on success, 1 on failure

=cut

sub _setupSqlUser
{
	my $self = shift;
	my $dbHost = $main::imscpConfig{'DATABASE_HOST'};
	my $dbUser = $self::phpmyadminConfig{'DATABASE_USER'};
	my $dbOldUser = $self::phpmyadminOldConfig{'DATABASE_USER'} || '';
	my $dbPass = $self::phpmyadminConfig{'DATABASE_PASSWORD'};
	my $dbUserHost = $main::imscpConfig{'SQL_SERVER'} ne 'remote_server'
		? $main::imscpConfig{'DATABASE_HOST'} : $main::imscpConfig{'BASE_SERVER_IP'};
	my $rs = 0;

	# Remove old phpmyadmin restricted SQL user and all it privileges (if any)
	for($main::imscpOldConfig{'DATABASE_HOST'} || '', $main::imscpOldConfig{'BASE_SERVER_IP'} || '') {
		next if $_ eq '' || $dbOldUser eq '';
		$rs = main::setupDeleteSqlUser($dbOldUser, $_);
		error("Unable to remove the old phpmyadmin '$dbOldUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;
	}

	# Ensure new phpmyadmin user do not already exists by removing it
	$rs = main::setupDeleteSqlUser($dbUserHost);
	error("Unable to delete the phpmyadmin '$dbUser' restricted SQL user: $rs") if $rs;
	return 1 if $rs;

	# Get SQL connection with full privileges
	my ($database, $errStr) = main::setupGetSqlConnect();
	fatal('Unable to connect to SQL Server: $errStr') if ! $database;

	# Add new phpmyadmin restricted SQL user with needed privileges

	# Add USAGE privilege on the mysql database (also create PhpMyAdmin user)
	$rs = $database->doQuery('dummy', 'GRANT USAGE ON `mysql`.* TO ?@? IDENTIFIED BY ?', $dbUser, $dbUserHost, $dbPass);
	if(ref $rs ne 'HASH') {
		error("Failed to add USAGE privilege on the 'mysql' database for the '$dbUser' SQL user: $rs");
		return 1;
	}

	# Add SELECT privilege on the mysql.db table
	$rs = $database->doQuery('dummy', 'GRANT SELECT ON `mysql`.`db` TO ?@?', $dbUser, $dbUserHost);
	if(ref $rs ne 'HASH') {
		error("Failed to add SELECT privilege on the 'mysql.db' table for the '$dbUser' SQL user: $rs");
		return 1;
	}

	# Add SELECT privilege on many columns of the mysql.user table
	$rs = $database->doQuery(
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
	if(ref $rs ne 'HASH') {
		error("Failed to add SELECT privileges on columns of the 'mysql.user' table for the '$dbUser' SQL user: $rs");
		return 1;
	}

	# Add SELECT privilege on the mysql.host table
	$rs = $database->doQuery('dummy', 'GRANT SELECT ON `mysql`.`host` TO ?@?', $dbUser, $dbUserHost);
	if(ref $rs ne 'HASH') {
		error("Failed to add SELECT privilege on the 'mysql.host' table for the '$dbUser' SQL user: $rs");
		return 1;
	}

	# Add SELECT privilege on many columns of the mysql.tables_priv table
	$rs = $database->doQuery(
		'dummy',
		'
			GRANT SELECT (`Host`, `Db`, `User`, `Table_name`, `Table_priv`, `Column_priv`)
			ON `mysql`.`tables_priv`
			TO?@?
		',
		$dbUser,
		$dbUserHost
	);
	if(ref $rs ne 'HASH') {
		error("Failed to add SELECT privilege on columns of the 'mysql.tables_priv' table for the '$dbUser' SQL user: $rs");
		return 1;
	}

	0;
}

=item _generateBlowfishSecret()

 Generate blowfish secret for PhpMyAdmin.

 Return int 0

=cut

sub _generateBlowfishSecret
{
	my $self = shift;

	$self::phpmyadminConfig{'BLOWFISH_SECRET'} = $self::phpmyadminOldConfig{'BLOWFISH_SECRET'}
		if ! $self::phpmyadminConfig{'BLOWFISH_SECRET'} && $self::phpmyadminOldConfig{'BLOWFISH_SECRET'};

	unless($self::phpmyadminConfig{'BLOWFISH_SECRET'}) {
		my $blowfishSecret = '';
		my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');

		$blowfishSecret .= $allowedChars[rand()*($#allowedChars + 1)] for (1..31);
		$self::phpmyadminConfig{'BLOWFISH_SECRET'} = $blowfishSecret;
	}

	0;
}

=item _buildConfig()

 Build PhpMyAdmin configuration file.

 Return int 0 on success, 1 on failure

=cut

sub _buildConfig
{
	my $self = shift;
	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $confDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/$self::phpmyadminConfig{'PHPMYADMIN_CONF_DIR'}";
	my $rs = 0;

	my $cfg = {
		PMA_USER => $self::phpmyadminConfig{'DATABASE_USER'},
		PMA_PASS => $self::phpmyadminConfig{'DATABASE_PASSWORD'},
		HOSTNAME => $main::imscpConfig{'DATABASE_HOST'},
		PORT => $main::imscpConfig{'DATABASE_PORT'},
		UPLOADS_DIR	=> "$main::imscpConfig{'GUI_ROOT_DIR'}/data/uploads",
		TMP_DIR => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
		BLOWFISH => $self::phpmyadminConfig{'BLOWFISH_SECRET'},
	};

	require iMSCP::File;

	my $file = iMSCP::File->new(filename => "$confDir/imscp.config.inc.php");
	my $cfgTpl = $file->get();
	return 1 if ! $cfgTpl;

	require iMSCP::Templator;

	$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
	return 1 if ! $cfgTpl;

	# store file in working directory
	$file = iMSCP::File->new(filename => "$self->{'wrkDir'}/$_");
	$rs = $file->set($cfgTpl);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($panelUName, $panelGName);

	# Install new file in production directory
	$rs |= $file->copyFile("$confDir/config.inc.php");

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
