=head1 NAME

Package::Webmail::Roundcube::Installer - i-MSCP Roundcube package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::Webmail::Roundcube::Installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use iMSCP::Composer;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::File;
use iMSCP::Dir;
use File::Basename;
use JSON;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

our $VERSION = '0.6.0.*@dev';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 This is the installer for the i-MSCP Roundcube package.

 See Package::Webmail::Roundcube::Roundcube for more information.

=head1 PUBLIC METHODS

=over 4

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $dbUser = main::setupGetQuestion('ROUNDCUBE_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'roundcube_user';
	my $dbPass = main::setupGetQuestion('ROUNDCUBE_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ [ 'webmails', 'all', 'forced' ] ||
		(length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/) ||
		(length $dbPass < 6 || $dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/)
	) {
		do{
			($rs, $dbUser) = $dialog->inputbox(
				"\nPlease enter an username for the Roundcube SQL user:$msg", $dbUser
			);

			if($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease try again:";
				$dbUser = '';
			} elsif(length $dbUser > 16) {
				$msg = "\n\n\\Username can be up to 16 characters long.\\Zn\n\nPlease try again:";
				$dbUser = '';
			} elsif(length $dbUser < 6) {
				$msg = "\n\n\\Z1Username must be at least 6 characters long.\\Zn\n\nPlease try again:";
				$dbUser = '';
			} elsif($dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/) {
				$msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			$msg = '';

			# Ask for the roundcube SQL user password unless we reuses existent SQL user
			unless($dbUser ~~ [ keys %main::sqlUsers ]) {
				do {
					($rs, $dbPass) = $dialog->passwordbox(
						"\nPlease, enter a password for the roundcube SQL user (blank for autogenerate):$msg", $dbPass
					);

					if($dbPass ne '') {
						if(length $dbPass < 6) {
							$msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease, try again:";
							$dbPass = '';
						} elsif($dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/) {
							$msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease try again:";
							$dbPass = '';
						} else {
							$msg = '';
						}
					} else {
						$msg = '';
					}
				} while($rs != 30 && $msg);
			} else {
				$dbPass = $main::sqlUsers{$dbUser};
			}

			if($rs != 30) {
				unless($dbPass) {
					my @allowedChr = map { chr } (0x21..0x5b, 0x5d..0x7e);
					$dbPass = '';
					$dbPass .= $allowedChr[rand @allowedChr] for 1..16;
				}

				$dialog->msgbox("\nPassword for the roundcube SQL user set to: $dbPass");
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('ROUNDCUBE_SQL_USER', $dbUser);
		main::setupSetQuestion('ROUNDCUBE_SQL_PASSWORD', $dbPass);
		$main::sqlUsers{$dbUser} = $dbPass;
	}

	$rs;
}

=item preinstall()

 Process preinstall tasks

 Return int 0

=cut

sub preinstall
{
	my $self = shift;

	my $rs = iMSCP::Composer->getInstance()->registerPackage('imscp/roundcube', $VERSION);
	return $rs if $rs;

	$self->{'eventManager'}->register('afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile);
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_backupConfigFile("$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php");
	return $rs if $rs;

	$rs = $self->_installFiles();
	return $rs if $rs;

	$rs = $self->_mergeConfig();
	return $rs if $rs;

	$rs = $self->_setupDatabase();
	return $rs if $rs;

	$rs = $self->_buildRoundcubeConfig();
	return $rs if $rs;

	$rs = $self->_updateDatabase() unless $self->{'newInstall'};
	return $rs if $rs;

	$rs = $self->_buildHttpdConfig();
	return $rs if $rs;

	$rs = $self->_setVersion();
	return $rs if $rs;

	$self->_saveConfig();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

	if(-d "$guiPublicDir/tools/webmail") {
		my $panelUName =
		my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

		my $rs = setRights("$guiPublicDir/tools/webmail", {
			user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1
		});
		return $rs if $rs;

		$rs = setRights("$guiPublicDir/tools/webmail/logs", {
			user => $panelUName, group => $panelGName, dirmode => '0750', filemode => '0640', recursive => 1
		});
		return $rs if $rs;
	}

	0;
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterFrontEndBuildConfFile(\$tplContent, $filename)

 Include httpd configuration into frontEnd vhost files

 Param string \$tplContent Template file tplContent
 Param string $tplName Template name
 Return int 0 on success, other on failure

=cut

sub afterFrontEndBuildConfFile
{
	my ($tplContent, $tplName) = @_;

	if($tplName ~~ ['00_master.conf', '00_master_ssl.conf']) {
		$$tplContent = replaceBloc(
			"# SECTION custom BEGIN.\n",
			"# SECTION custom END.\n",
			"    # SECTION custom BEGIN.\n" .
			getBloc(
				"# SECTION custom BEGIN.\n",
				"# SECTION custom END.\n",
				$$tplContent
			) .
				"    include imscp_roundcube.conf;\n" .
				"    # SECTION custom END.\n",
			$$tplContent
		);
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Webmail::Roundcube::Installer

=cut

sub _init
{
	my $self = shift;

	$self->{'roundcube'} = Package::Webmail::Roundcube::Roundcube->getInstance();
	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'cfgDir'} = $self->{'roundcube'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'newInstall'} = 1;

	$self->{'config'} = $self->{'roundcube'}->{'config'};

	$self;
}

=item _backupConfigFile($cfgFile)

 Backup the given configuration file

 Param string $cfgFile Path of file to backup
 Return int 0, other on failure

=cut

sub _backupConfigFile
{
	my ($self, $cfgFile) = @_;

	if(-f $cfgFile && -d $self->{'bkpDir'}) {
		my $filename = fileparse($cfgFile);
		my $file = iMSCP::File->new( filename => $cfgFile );
		my $rs = $file->copyFile("$self->{'bkpDir'}/$filename" . time);

		return $rs if $rs;
	}

	0;
}

=item _installFiles()

 Install files

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
	my $self = shift;

	my $packageDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages/vendor/imscp/roundcube";

	if(-d $packageDir) {
		my $destDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";

		my ($stdout, $stderr);
		my $rs = execute("rm -fR $destDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute("cp -fRT $packageDir/iMSCP/config $self->{'cfgDir'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;

		$rs = execute("cp -fR $packageDir/src $destDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	} else {
		error("Couldn't find the imscp/roundcube package into the packages cache directory");
		return 1;
	}

	0;
}

=item _mergeConfig

 Merge old config if any

 Return int 0

=cut

sub _mergeConfig
{
	my $self = shift;

	if(%{$self->{'config'}}) {
		my %oldConfig = %{$self->{'config'}};

		tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data";

		for(keys %oldConfig) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $oldConfig{$_};
			}
		}
	} else {
		tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data";
	}

	0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
	my $self = shift;

	my $roundcubeDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
	my $imscpDbName = main::setupGetQuestion('DATABASE_NAME');
	my $roundcubeDbName = $imscpDbName . '_roundcube';
	my $dbUser = main::setupGetQuestion('ROUNDCUBE_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = main::setupGetQuestion('ROUNDCUBE_SQL_PASSWORD');

	my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

	my ($db, $errStr) = main::setupGetSqlConnect();
	fatal(sprintf('Unable to connect to SQL server: %s', $errStr)) unless $db;

	my $quotedDbName = $db->quoteIdentifier($roundcubeDbName);

	my $rs = $db->doQuery('1', 'SHOW DATABASES LIKE ?', $roundcubeDbName);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	} elsif(%{$rs}) {
		$rs = $db->doQuery('1', "SHOW TABLES FROM $quotedDbName");
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}
	}

	unless(%{$rs}) {
		$rs = $db->doQuery(
			'c', "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci"
		);
		unless(ref $rs eq 'HASH') {
			error(sprintf('Unable to create SQL database: %s', $rs));
			return 1;
		}

		my ($db, $errStr) = main::setupGetSqlConnect($roundcubeDbName);
		fatal(sprintf('Unable to connect to SQL server:  %s', $errStr)) unless $db;

		$rs = main::setupImportSqlSchema($db, "$roundcubeDir/SQL/mysql.initial.sql");
		return $rs if $rs;
	} else {
		$self->{'newInstall'} = 0;
	}

	for my $sqlUser ($dbOldUser, $dbUser) {
		next if ! $sqlUser || "$sqlUser\@$dbUserHost" ~~ @main::createdSqlUsers;

		for my $host(
			$dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, $main::imscpOldConfig{'DATABASE_HOST'},
			$main::imscpOldConfig{'BASE_SERVER_IP'}
		) {
			next unless $host;

			if(main::setupDeleteSqlUser($sqlUser, $host)) {
				error(sprintf('Unable to remove %s@%s SQL user or one of its privileges', $sqlUser, $host));
				return 1;
			}
		}
	}

	# Create SQL user if not already created by another server/package installer
	unless("$dbUser\@$dbUserHost" ~~ @main::createdSqlUsers) {
		debug(sprintf('Creating %s@%s SQL user', $dbUser, $dbUserHost));

		$rs = $db->doQuery('c', 'CREATE USER ?@? IDENTIFIED BY ?', $dbUser, $dbUserHost, $dbPass);
		unless(ref $rs eq 'HASH') {
			error(sprintf('Unable to create %s@%s SQL user: %s', $dbUser, $dbUserHost, $rs));
			return 1;
		}

		push @main::createdSqlUsers, "$dbUser\@$dbUserHost";
	}

	# Give needed privileges to this SQL user

	$rs = $db->doQuery('g', "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?@?",  $dbUser, $dbUserHost);
	unless(ref $rs eq 'HASH') {
		error(sprintf('Unable to add SQL privileges: %s', $rs));
		return 1;
	}

	$quotedDbName = $db->quoteIdentifier($imscpDbName);

	$rs = $db->doQuery(
		'g',
		"GRANT SELECT (mail_addr, mail_pass), UPDATE (mail_pass) ON $quotedDbName.mail_users TO ?@?",
		$dbUser,
		$dbUserHost
	);
	unless(ref $rs eq 'HASH') {
		error(sprintf('Unable to add SQL privileges: %s', $rs));
		return 1;
	}

	$self->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;

	0;
}

=item _generateDESKey()

 Generate DES key

 Return string DES key

=cut

sub _generateDESKey
{
	my $desKey = '';
	$desKey .= ('A'..'Z', 'a'..'z', '0'..'9', '_', '+', '-', '^', '=', '*', '{', '}', '~')[rand(70)] for 1..24;

	$desKey;
}

=item _buildRoundcubeConfig()

 Build roundcube configuration file

 Return int 0 on success, other on failure

=cut

sub _buildRoundcubeConfig
{
	my $self = shift;

	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $dbName = main::setupGetQuestion('DATABASE_NAME') . '_roundcube';
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	(my $dbUser = main::setupGetQuestion('ROUNDCUBE_SQL_USER')) =~ s%(')%\\$1%g;
	(my $dbPass = main::setupGetQuestion('ROUNDCUBE_SQL_PASSWORD')) =~ s%(')%\\$1%g;

	my $data = {
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		DB_NAME => $dbName,
		DB_HOST => $dbHost,
		DB_PORT => $dbPort,
		DB_USER => $dbUser,
		DB_PASS => $dbPass,
		TMP_PATH => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
		DES_KEY => $self->_generateDESKey()
	};

	my $cfgTpl;
	my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'roundcube', 'config.inc.php', \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/config.inc.php" )->get();
		unless(defined $cfgTpl) {
			error("Unable to read file $self->{'cfgDir'}/config.inc.php");
			return 1;
		}
	}

	$cfgTpl = process($data, $cfgTpl);

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/config.inc.php" );

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($panelUName, $panelGName);
	return $rs if $rs;

	$file->copyFile("$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php");
}

=item _updateDatabase()

 Update database

 Return int 0 on success other on failure

=cut

sub _updateDatabase
{
	my $self = shift;

	my $roundcubeDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
	my $roundcubeDbName = $main::imscpConfig{'DATABASE_NAME'} . '_roundcube';
	my $fromVersion = $self->{'config'}->{'ROUNDCUBE_VERSION'} || '0.8.4';

	my ($stdout, $stderr);
	my $rs = execute(
		"php $roundcubeDir/bin/updatedb.sh --version=$fromVersion --dir=$roundcubeDir/SQL --package=roundcube",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to update roundcube database schema.') if $rs && ! $stderr;
	return $rs if $rs;

	# Ensure tha users.mail_host entries are set to 'localhost'

	my ($db, $errStr) = main::setupGetSqlConnect($roundcubeDbName);
	unless($db) {
		error("Unable to connect to SQL database: $errStr");
		return 1;
	}

	$roundcubeDbName = $db->quoteIdentifier($roundcubeDbName);

	$rs = $db->doQuery('u', "UPDATE IGNORE users SET mail_host = 'localhost'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	$rs = $db->doQuery('d', "DELETE FROM users WHERE mail_host <> 'localhost'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	0;
}

=item _setVersion()

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
	my $self = shift;

	my $repoDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages/vendor/imscp/roundcube";

	my $json = iMSCP::File->new( filename => "$repoDir/composer.json" )->get();
	unless(defined $json) {
		error("Unable to read $repoDir/composer.json");
		return 1;
	}

	$json = decode_json($json);
	debug("Set new roundcube version to $json->{'version'}");
	$self->{'config'}->{'ROUNDCUBE_VERSION'} = $json->{'version'};

	0;
}

=item _buildHttpdConfig()

 Build Httpd configuration

=cut

sub _buildHttpdConfig
{
	my ($self, $tplContent, $tplName) = @_;

	if(-f "$self->{'wrkDir'}/imscp_roundcube.conf") {
		my $rs = iMSCP::File->new(
			filename => "$self->{'wrkDir'}/imscp_roundcube.conf"
		)->copyFile(
			"$self->{'bkpDir'}/imscp_roundcube.conf." . time
		);
		return $rs if $rs;
	}

	my $frontEnd = Package::FrontEnd->getInstance();

	my $rs = $frontEnd->buildConfFile(
		"$self->{'cfgDir'}/nginx/imscp_roundcube.conf",
		{ WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'} },
		{ destination => "$self->{'wrkDir'}/imscp_roundcube.conf" }
	);
	return $rs if $rs;

	iMSCP::File->new(
		filename => "$self->{'wrkDir'}/imscp_roundcube.conf"
	)->copyFile(
		"$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf"
	);
}

=item _saveConfig()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConfig
{
	my $self = shift;

	iMSCP::File->new(
		filename => "$self->{'cfgDir'}/roundcube.data"
	)->copyFile(
		"$self->{'cfgDir'}/roundcube.old.data"
	);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
