=head1 NAME

Package::Webmail::RainLoop::Installer - i-MSCP RainLoop package installer

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

package Package::Webmail::RainLoop::Installer;

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

our $VERSION = '0.1.0.*@dev';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 This is the installer for the i-MSCP RainLoop package.

 See Package::Webmail::RainLoop::RainLoop for more information.

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

	my $dbUser = main::setupGetQuestion('RAINLOOP_SQL_USER') || $self->{'rainloop'}->{'config'}->{'DATABASE_USER'} || 'rainloop_user';
	my $dbPass = main::setupGetQuestion('RAINLOOP_SQL_PASSWORD') || $self->{'rainloop'}->{'config'}->{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ [ 'webmails', 'all', 'forced' ] ||
		(length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x23-\x5b\x5d-\x7e\x21]+$/) ||
		(length $dbPass < 6 || $dbPass !~ /^[\x23-\x5b\x5d-\x7e\x21]+$/)
	) {
		do{
			($rs, $dbUser) = $dialog->inputbox(
				"\nPlease enter an username for the rainloop SQL user:$msg", $dbUser
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
			} elsif($dbUser !~ /^[\x23-\x5b\x5d-\x7e\x21]+$/) {
				$msg = "\n\n\\Z1Only printable ASCII characters (excepted space, double quote and backslash) are allowed.\\Zn\n\nPlease try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			$msg = '';

			# Ask for the rainloop SQL user password unless we reuses existent SQL user
			unless($dbUser ~~ [ keys %main::sqlUsers ]) {
				do {
					($rs, $dbPass) = $dialog->passwordbox(
						"\nPlease, enter a password for the rainloop SQL user (blank for autogenerate):$msg", $dbPass
					);

					if($dbPass ne '') {
						if(length $dbPass < 6) {
							$msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease, try again:";
							$dbPass = '';
						} elsif($dbPass !~ /^[\x23-\x5b\x5d-\x7e\x21]+$/) {
							$msg = "\n\n\\Z1Only printable ASCII characters (excepted space, double quote and backslash) are allowed.\\Zn\n\nPlease try again:";
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
					my @allowedChr = map { chr } (0x21, 0x23..0x5b, 0x5d..0x7e);
					$dbPass = '';
					$dbPass .= $allowedChr[rand @allowedChr] for 1..16;
				}

				$dialog->msgbox("\nPassword for the rainloop SQL user set to: $dbPass");
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('RAINLOOP_SQL_USER', $dbUser);
		main::setupSetQuestion('RAINLOOP_SQL_PASSWORD', $dbPass);
		$main::sqlUsers{$dbUser} = $dbPass;
	}

	$rs;
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, die on failure

=cut

sub preinstall
{
	my $self = shift;

	iMSCP::Composer->getInstance()->registerPackage('imscp/rainloop', $VERSION);
	$self->{'eventManager'}->register('afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile);
}

=item install()

 Process install tasks

 Return int 0 on success, other or die on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_installFiles();
	return $rs if $rs;

	$rs = $self->_mergeConfig();
	return $rs if $rs;

	$rs = $self->_setupDatabase();
	return $rs if $rs;

	$rs = $self->_buildConfig();
	return $rs if $rs;

	$rs = $self->_buildHttpdConfig();
	return $rs if $rs;

	$rs = $self->_setVersion();
	return $rs if $rs;

	$self->_saveConfig();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, die on failure

=cut

sub setGuiPermissions
{
	my $guiPublicDir = $main::imscpConfig{'GUI_PUBLIC_DIR'};

	if(-d "$guiPublicDir/tools/rainloop") {
		my $panelUName =
		my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

		setRights("$guiPublicDir/tools/rainloop", {
			user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1
		});
		setRights("$guiPublicDir/tools/rainloop/data", {
			user => $panelUName, group => $panelGName, dirmode => '0750', filemode => '0640', recursive => 1
		});
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
 Return int 0

=cut

sub afterFrontEndBuildConfFile
{
	my ($tplContent, $tplName) = @_;

	if($tplName ~~ [ '00_master.conf', '00_master_ssl.conf' ]) {
		$$tplContent = replaceBloc(
			"# SECTION custom BEGIN.\n",
			"# SECTION custom END.\n",
			"    # SECTION custom BEGIN.\n" .
			getBloc(
				"# SECTION custom BEGIN.\n",
				"# SECTION custom END.\n",
				$$tplContent
			) .
				"    include imscp_rainloop.conf;\n" .
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

 Return Package::Webmail::RainLoop::Installer

=cut

sub _init
{
	my $self = shift;

	$self->{'rainloop'} = Package::Webmail::RainLoop::RainLoop->getInstance();
	$self->{'frontend'} = Package::FrontEnd->getInstance();
	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self;
}

=item _installFiles()

 Install files

 Return int 0 on success, die on failure

=cut

sub _installFiles
{
	my $self = shift;

	my $packageDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages/vendor/imscp/rainloop";

	-d $packageDir or die('Could not find the imscp/rainloop package at %s', $packageDir);

	my $destDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop";

	# Install upstream files
	iMSCP::Dir->new( dirname => "$packageDir/src" )->rcopy("${destDir}-new");

	# Copy i-MSCP files
	iMSCP::Dir->new( dirname => "$packageDir/iMSCP/src" )->rcopy("${destDir}-new");

	if(-d $destDir) {
		my $dataSrcDir = "$destDir/data/_data_11c052c218cd2a2febbfb268624efdc1/_default_";

		# Copy files from previous installation
		if(-d "$dataSrcDir/storage") {
			iMSCP::Dir->new( dirname => "$dataSrcDir/storage" )->rcopy("${destDir}-new/storage");
		}

		# Remove files from previous installation
		iMSCP::Dir->new( dirname => $destDir )->remove();

		# Remove file which are no longer needed
		for my $file('application.ini', 'plugin-imscp-change-password.ini') {
			if(-f "$self->{'rainloop'}->{'cfgDir'}/$file") {
				iMSCP::File->new( filename => "$self->{'rainloop'}->{'cfgDir'}/$file" )->delFile();
			}
		}
	}

	iMSCP::Dir->new( dirname => "${destDir}-new" )->moveDir($destDir);

	# Copy configuration files
	iMSCP::Dir->new( dirname => "$packageDir/iMSCP/config" )->rcopy($self->{'rainloop'}->{'cfgDir'});
}

=item _mergeConfig

 Merge old config if any

 Return int 0 on success, die on failure

=cut

sub _mergeConfig
{
	my $self = shift;

	if(%{$self->{'rainloop'}->{'config'}}) {
		my %oldConfig = %{$self->{'rainloop'}->{'config'}};
		tie %{$self->{'rainloop'}->{'config'}}, 'iMSCP::Config', fileName => "$self->{'rainloop'}->{'cfgDir'}/rainloop.data";
		for my $param(keys %oldConfig) {
			if(exists $self->{'rainloop'}->{'config'}->{$param}) {
				$self->{'rainloop'}->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	} else {
		tie %{$self->{'rainloop'}->{'config'}}, 'iMSCP::Config', fileName => "$self->{'rainloop'}->{'cfgDir'}/rainloop.data";
	}

	0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, die on failure

=cut

sub _setupDatabase
{
	my $self = shift;

	my $imscpDbName = main::setupGetQuestion('DATABASE_NAME');
	my $rainLoopDbName = $imscpDbName . '_rainloop';
	my $dbUser = main::setupGetQuestion('RAINLOOP_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = main::setupGetQuestion('RAINLOOP_SQL_PASSWORD');
	my $dbOldUser = $self->{'rainloop'}->{'config'}->{'DATABASE_USER'};

	my ($db, $errStr) = main::setupGetSqlConnect();
	$db or die (sprintf('Unable to connect to SQL server: %s', $errStr));

	my $quotedDbName = $db->quoteIdentifier($rainLoopDbName);

	my $rs = $db->doQuery('c', "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci");
	ref $rs eq 'HASH' or die(sprintf('Unable to create SQL database: %s', $rs));

	for my $sqlUser ($dbOldUser, $dbUser) {
		next if ! $sqlUser || "$sqlUser\@$dbUserHost" ~~ @main::createdSqlUsers;

		for my $host(
			$dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, $main::imscpOldConfig{'DATABASE_HOST'},
			$main::imscpOldConfig{'BASE_SERVER_IP'}
		) {
			next unless $host;

			if(main::setupDeleteSqlUser($sqlUser, $host)) {
				die(sprintf('Could not remove %s@%s SQL user or one of its privileges', $sqlUser, $host));
			}
		}
	}

	# Create SQL user if not already created by another server/package installer
	unless("$dbUser\@$dbUserHost" ~~ @main::createdSqlUsers) {
		debug(sprintf('Creating %s@%s SQL user', $dbUser, $dbUserHost));

		$rs = $db->doQuery('c', 'CREATE USER ?@? IDENTIFIED BY ?', $dbUser, $dbUserHost, $dbPass);
		ref $rs eq 'HASH' or die(sprintf('Could not create the %s@%s SQL user: %s', $dbUser, $dbUserHost, $rs));

		push @main::createdSqlUsers, "$dbUser\@$dbUserHost";
	}

	# Give needed privileges to this SQL user
	$rs = $db->doQuery('g', "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?@?",  $dbUser, $dbUserHost);
	ref $rs eq 'HASH' or die(sprintf('Could not add SQL privileges: %s', $rs));

	$quotedDbName = $db->quoteIdentifier($imscpDbName);

	$rs = $db->doQuery(
		'g',
		"GRANT SELECT (mail_addr, mail_pass), UPDATE (mail_pass) ON $quotedDbName.mail_users TO ?@?",
		$dbUser,
		$dbUserHost
	);
	ref $rs eq 'HASH' or die(sprintf('Could not add SQL privileges: %s', $rs));

	$self->{'rainloop'}->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'rainloop'}->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
	0;
}

=item _buildConfig()

 Build RainLoop configuration file

 Return int 0 on success, die on failure

=cut

sub _buildConfig
{
	my $self = shift;

	my $confDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop/data/_data_11c052c218cd2a2febbfb268624efdc1/_default_/configs";
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	for my $confFile('application.ini', 'plugin-imscp-change-password.ini') {
		my $data = {
			DATABASE_NAME => $confFile eq 'application.ini'
				? main::setupGetQuestion('DATABASE_NAME') . '_rainloop' : main::setupGetQuestion('DATABASE_NAME'),
			DATABASE_HOST => main::setupGetQuestion('DATABASE_HOST'),
			DATATABASE_PORT => main::setupGetQuestion('DATABASE_PORT'),
			DATABASE_USER => main::setupGetQuestion('RAINLOOP_SQL_USER'),
			DATABASE_PASSWORD => main::setupGetQuestion('RAINLOOP_SQL_PASSWORD'),
			DISTRO_CA_BUNDLE => main::setupGetQuestion('DISTRO_CA_BUNDLE'),
			DISTRO_CA_PATH => main::setupGetQuestion('DISTRO_CA_PATH')
		};

		$self->{'eventManager'}->trigger( 'onLoadTemplate', 'rainloop', $confFile, \my $cfgTpl, $data );
		$cfgTpl = iMSCP::File->new( filename => "$confDir/$confFile" )->get() unless defined $cfgTpl;
		$cfgTpl = process($data, $cfgTpl);

		my $file = iMSCP::File->new( filename => "$confDir/$confFile" );
		$file->set($cfgTpl);
		$file->save();
		$file->mode(0640);
		$file->owner($panelUName, $panelGName);
	}

	0;
}

=item _setVersion()

 Set version

 Return int 0 on success, die on failure

=cut

sub _setVersion
{
	my $self = shift;

	my $packageDir = "$main::imscpConfig{'CACHE_DATA_DIR'}/packages/vendor/imscp/rainloop";
	my $json = iMSCP::File->new( filename => "$packageDir/composer.json" )->get();
	$json = decode_json($json);
	debug("Set new rainloop version to $json->{'version'}");
	$self->{'rainloop'}->{'config'}->{'RAINLOOP_VERSION'} = $json->{'version'};
	0;
}

=item _buildHttpdConfig()

 Build Httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
	my ($self, $tplContent, $tplName) = @_;

	$self->{'frontend'}->buildConfFile(
		"$self->{'rainloop'}->{'cfgDir'}/nginx/imscp_rainloop.conf",
		{ GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'} },
		{ destination => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_rainloop.conf" }
	);
}

=item _saveConfig()

 Save configuration

 Return int 0 on success, die on failure

=cut

sub _saveConfig
{
	my $self = shift;

	iMSCP::File->new( filename => "$self->{'rainloop'}->{'cfgDir'}/rainloop.data" )->copyFile(
		"$self->{'rainloop'}->{'cfgDir'}/rainloop.old.data"
	);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
