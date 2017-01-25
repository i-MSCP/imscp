=head1 NAME

Package::Webmail::Roundcube::Installer - i-MSCP Roundcube package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use File::Basename;
use iMSCP::Composer;
use iMSCP::Config;
use iMSCP::Crypt qw/ randomStr /;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::TemplateParser;
use JSON;
use Package::FrontEnd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

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

    my $masterSqlUser = main::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = main::setupGetQuestion('ROUNDCUBE_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'roundcube_user');
    my $dbPass = main::setupGetQuestion( 'ROUNDCUBE_SQL_PASSWORD', $self->{'config'}->{'DATABASE_PASSWORD'} );

    if ($main::reconfigure =~ /^(?:webmails|all|forced)$/
        || !isValidUsername($dbUser)
        || !isStringNotInList($dbUser, 'root', 'debian-sys-maint',$masterSqlUser)
        || !isValidPassword($dbPass)
    ) {
        my ($rs, $msg) = (0, '');

        do {
            ($rs, $dbUser) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the RoundCube SQL user:$msg
EOF
            $msg = '';
            if (!isValidUsername($dbUser)
                || !isStringNotInList($dbUser, 'root', 'debian-sys-maint', $masterSqlUser)
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        if (isStringNotInList($dbUser, keys %main::sqlUsers)) {
            do {
                ($rs, $dbPass) = $dialog->inputbox( <<"EOF", $dbPass || randomStr(16, iMSCP::Crypt::ALNUM) );

Please enter a password for the RoundCube SQL user:$msg
EOF
                $msg = (isValidPassword($dbPass)) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;
        } else {
            $dbPass = $main::sqlUsers{$dbUser};
        }
    }

    main::setupSetQuestion( 'ROUNDCUBE_SQL_USER', $dbUser );
    main::setupSetQuestion( 'ROUNDCUBE_SQL_PASSWORD', $dbPass );
    $main::sqlUsers{$dbUser} = $dbPass;
    0;
}

=item preinstall()

 Process preinstall tasks

 Return int 0

=cut

sub preinstall
{
    my $self = shift;

    my $rs = iMSCP::Composer->getInstance()->registerPackage( 'imscp/roundcube', '1.2.x' );
    $rs ||= $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_backupConfigFile( "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php" );
    $rs ||= $self->_installFiles();
    $rs ||= $self->_mergeConfig();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildRoundcubeConfig();
    $rs ||= $self->_updateDatabase() unless $self->{'newInstall'};
    $rs ||= $self->_buildHttpdConfig();
    $rs ||= $self->_setVersion();
    $rs ||= $self->_saveConfig();
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

    return 0 unless $tplName =~ /^00_master(?:_ssl)?\.conf$/;

    $$tplContent = replaceBloc(
        "# SECTION custom BEGIN.\n",
        "# SECTION custom END.\n",
        "    # SECTION custom BEGIN.\n".
            getBloc(
                "# SECTION custom BEGIN.\n",
                "# SECTION custom END.\n",
                $$tplContent
            ).
            "    include imscp_roundcube.conf;\n".
            "    # SECTION custom END.\n",
        $$tplContent
    );
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

=item _getPhpVersion()

 Get PHP version

 Return int PHP version on success, die on failure

=cut

sub _getPhpVersion
{
    my $rs = execute( 'php -d date.timezone=UTC -v', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $stdout =~ /PHP\s+([\d.]+)/ or die(
        sprintf( 'Could not find PHP version from `php -v` command output: %s', $stdout )
    );
    $1;
}

=item _backupConfigFile($cfgFile)

 Backup the given configuration file

 Param string $cfgFile Path of file to backup
 Return int 0, other on failure

=cut

sub _backupConfigFile
{
    my ($self, $cfgFile) = @_;

    return 0 unless -f $cfgFile && -d $self->{'bkpDir'};

    iMSCP::File->new( filename => $cfgFile )->copyFile( $self->{'bkpDir'}.'/'.fileparse( $cfgFile ).'.'.time );
}

=item _installFiles()

 Install files

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my $self = shift;

    my $packageDir = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/roundcube";

    unless (-d $packageDir) {
        error( "Couldn't find the imscp/roundcube package into the packages cache directory" );
        return 1;
    }

    my $destDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
    local $@;
    eval {
        iMSCP::Dir->new( dirname => $destDir )->remove();
        iMSCP::Dir->new( dirname => "$packageDir/iMSCP/config" )->rcopy( $self->{'cfgDir'} );
        iMSCP::Dir->new( dirname => "$packageDir/src" )->rcopy( $destDir );
    };
    if ($@) {
        error($@);
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

    if (%{$self->{'config'}}) {
        my %oldConfig = %{$self->{'config'}};

        tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data";

        while(my ($key, $value) = each(%oldConfig)) {
            next unless exists $self->{'config'}->{$key};
            $self->{'config'}->{$key} = $value;
        }

        return 0;
    }

    tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data";
    0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory();
    my $roundcubeDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
    my $imscpDbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $roundcubeDbName = $imscpDbName.'_roundcube';
    my $dbUser = main::setupGetQuestion( 'ROUNDCUBE_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'} || '';
    my $dbPass = main::setupGetQuestion( 'ROUNDCUBE_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};
    my $db = iMSCP::Database->factory();
    my $quotedDbName = $db->quoteIdentifier( $roundcubeDbName );

    my $rs = $db->doQuery( '1', 'SHOW DATABASES LIKE ?', $roundcubeDbName );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }

    if (%{$rs}) {
        $rs = $db->doQuery( '1', "SHOW TABLES FROM $quotedDbName" );
        unless (ref $rs eq 'HASH') {
            error( $rs );
            return 1;
        }
    }

    unless (%{$rs}) {
        $rs = $db->doQuery(
            'c', "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci"
        );
        unless (ref $rs eq 'HASH') {
            error( sprintf( 'Could not create SQL database: %s', $rs ) );
            return 1;
        }

        my $oldDatabase = $db->useDatabase($roundcubeDbName);
        $rs = main::setupImportSqlSchema( $db, "$roundcubeDir/SQL/mysql.initial.sql" );
        return $rs if $rs;

        $db->useDatabase($oldDatabase);
    } else {
        $self->{'newInstall'} = 0;
    }

    for my $sqlUser ($dbOldUser, $dbUser) {
        next if !$sqlUser || grep($_ eq "$sqlUser\@$dbUserHost", @main::createdSqlUsers);
        for my $host($dbUserHost, $oldDbUserHost) {
            next unless $host;
            $sqlServer->dropUser( $sqlUser, $host );
        }
    }

    # Create SQL user if not already created by another server/package installer
    unless (grep($_ eq "$dbUser\@$dbUserHost", @main::createdSqlUsers)) {
        debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ) );
        $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
        push @main::createdSqlUsers, "$dbUser\@$dbUserHost";
    }

    # Give needed privileges to this SQL user

    $quotedDbName =~ s/([%_])/\\$1/g;
    $rs = $db->doQuery( 'g', "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?\@?", $dbUser, $dbUserHost );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
    $quotedDbName = $db->quoteIdentifier( $imscpDbName );
    $rs = $db->doQuery(
        'g', "GRANT SELECT (mail_addr, mail_pass), UPDATE (mail_pass) ON $quotedDbName.mail_users TO ?\@?",
        $dbUser, $dbUserHost
    );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    0;
}

=item _buildRoundcubeConfig()

 Build roundcube configuration file

 Return int 0 on success, other on failure

=cut

sub _buildRoundcubeConfig
{
    my $self = shift;

    my $panelUName =
        my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' ).'_roundcube';
    my $dbHost = main::setupGetQuestion( 'DATABASE_HOST' );
    my $dbPort = main::setupGetQuestion( 'DATABASE_PORT' );
    (my $dbUser = main::setupGetQuestion( 'ROUNDCUBE_SQL_USER' )) =~ s%(')%\\$1%g;
    (my $dbPass = main::setupGetQuestion( 'ROUNDCUBE_SQL_PASSWORD' )) =~ s%(')%\\$1%g;

    my $data = {
        BASE_SERVER_VHOST => main::setupGetQuestion( 'BASE_SERVER_VHOST' ),
        DB_NAME           => $dbName,
        DB_HOST           => $dbHost,
        DB_PORT           => $dbPort,
        DB_USER           => $dbUser,
        DB_PASS           => $dbPass,
        TMP_PATH          => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
        DES_KEY           => randomStr(24, iMSCP::Crypt::ALNUM)
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'roundcube', 'config.inc.php', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/config.inc.php" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s file', "$self->{'cfgDir'}/config.inc.php" ) );
            return 1;
        }
    }

    $cfgTpl = process( $data, $cfgTpl );

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/config.inc.php" );
    $rs = $file->set( $cfgTpl );
    $rs ||= $file->save();
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->owner( $panelUName, $panelGName );
    $rs ||= $file->copyFile( "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php" );
}

=item _updateDatabase()

 Update database

 Return int 0 on success other on failure

=cut

sub _updateDatabase
{
    my $self = shift;

    my $roundcubeDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
    my $roundcubeDbName = main::setupGetQuestion( 'DATABASE_NAME' ).'_roundcube';
    my $fromVersion = $self->{'config'}->{'ROUNDCUBE_VERSION'} || '0.8.4';

    my $rs = execute(
        "php $roundcubeDir/bin/updatedb.sh --version=$fromVersion --dir=$roundcubeDir/SQL --package=roundcube",
        \my $stdout, \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    # Ensure tha users.mail_host entries are set with expected hostname (default to `localhost')
    my $db = iMSCP::Database->factory();
    my $oldDatabase = $db->useDatabase($roundcubeDbName);
    my $hostname = 'localhost';
    $rs = $self->{'eventManager'}->trigger('beforeUpdateRoundCubeMailHostEntries', \$hostname);
    return $rs if $rs;
    $rs = $db->doQuery( 'u', 'UPDATE IGNORE users SET mail_host = ?', $hostname );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }
    $rs = $db->doQuery( 'd', 'DELETE FROM users WHERE mail_host <> ?', $hostname );
    unless (ref $rs eq 'HASH') {
        error( $rs );
        return 1;
    }

    $db->useDatabase($oldDatabase);
    0;
}

=item _setVersion()

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my $self = shift;

    my $repoDir = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/roundcube";
    my $json = iMSCP::File->new( filename => "$repoDir/composer.json" )->get();
    unless (defined $json) {
        error( sprintf( 'Could not read %s file', "$repoDir/composer.json" ) );
        return 1;
    }

    $json = decode_json( $json );
    debug( sprintf( 'Set new roundcube version to %s', $json->{'version'} ) );
    $self->{'config'}->{'ROUNDCUBE_VERSION'} = $json->{'version'};
    0;
}

=item _buildHttpdConfig()

 Build Httpd configuration

=cut

sub _buildHttpdConfig
{
    my $self = shift;

    if (-f "$self->{'wrkDir'}/imscp_roundcube.conf") {
        my $rs = iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_roundcube.conf" )->copyFile(
            "$self->{'bkpDir'}/imscp_roundcube.conf.".time
        );
        return $rs if $rs;
    }

    my $frontEnd = Package::FrontEnd->getInstance();
    my $rs = $frontEnd->buildConfFile(
        "$self->{'cfgDir'}/nginx/imscp_roundcube.conf",
        {
            WEB_DIR => $main::imscpConfig{'GUI_ROOT_DIR'}
        },
        {
            destination => "$self->{'wrkDir'}/imscp_roundcube.conf"
        }
    );
    $rs ||= iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_roundcube.conf" )->copyFile(
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

    (tied %{$self->{'config'}})->flush();

    iMSCP::File->new( filename => "$self->{'cfgDir'}/roundcube.data" )->copyFile(
        "$self->{'cfgDir'}/roundcube.old.data"
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
