=head1 NAME

 Package::WebmailClients::Roundcube::Installer - Roundcube package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::WebmailClients::Roundcube::Installer;

use strict;
use warnings;
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Composer;
use iMSCP::Config;
use iMSCP::Crypt 'randomStr';
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser;
use JSON;
use Package::FrontEnd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

%::sqlUsers = () unless %::sqlUsers;

=head1 DESCRIPTION

 Roundcube package installer.

=head1 PUBLIC METHODS

=over 4

=item showDialog( \%dialog )

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ( $self, $dialog ) = @_;

    my $masterSqlUser = ::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = ::setupGetQuestion( 'ROUNDCUBE_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'imscp_srv_user' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = ::setupGetQuestion(
        'ROUNDCUBE_SQL_PASSWORD', ( ( iMSCP::Getopt->preseed ) ? randomStr( 16, iMSCP::Crypt::ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    if ( $::reconfigure =~ /^(?:webmail_client_packages|addons|all|forced)$/ || !isValidUsername( $dbUser )
        || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' ) || !isValidPassword( $dbPass )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my ( $rs, $msg ) = ( 0, '' );

        do {
            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the RoundCube SQL user:$msg
EOF
            $msg = '';
            if ( !isValidUsername( $dbUser ) || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' )
                || !isAvailableSqlUser( $dbUser )
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        unless ( defined $::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            do {
                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass || randomStr( 16, iMSCP::Crypt::ALNUM ));

Please enter a password for the RoundCube SQL user:$msg
EOF
                $msg = isValidPassword( $dbPass ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;

            $::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
        } else {
            $dbPass = $::sqlUsers{$dbUser . '@' . $dbUserHost};
        }
    } elsif ( defined $::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
        $dbPass = $::sqlUsers{$dbUser . '@' . $dbUserHost};
    } else {
        $::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
    }

    ::setupSetQuestion( 'ROUNDCUBE_SQL_USER', $dbUser );
    ::setupSetQuestion( 'ROUNDCUBE_SQL_PASSWORD', $dbPass );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0

=cut

sub preinstall
{
    my ( $self ) = @_;

    eval {
        iMSCP::Composer->new(
            user          => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
            composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
            composer_json => 'composer.json' 
        )
            ->require( 'imscp/roundcube', '^1.0' )
            ->dumpComposerJson();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_backupConfigFile( "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php" );
    $rs ||= $self->_installFiles();
    $rs ||= $self->_mergeConfig();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildRoundcubeConfig();
    $rs ||= $self->_updateDatabase() unless $self->{'newInstall'};
    $rs ||= $self->_buildHttpdConfig();
    $rs ||= $self->_setVersion();
    $rs ||= $self->_cleanup();
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterFrontEndBuildConfFile( \$tplContent, $filename )

 Include httpd configuration into frontEnd vhost files

 Param string \$tplContent Template file tplContent
 Param string $tplName Template name
 Return int 0 on success, other on failure

=cut

sub afterFrontEndBuildConfFile
{
    my ( $tplContent, $tplName ) = @_;

    return 0 unless grep ($_ eq $tplName, '00_master.nginx', '00_master_ssl.nginx');

    ${ $tplContent } = replaceBloc(
        "# SECTION custom BEGIN.\n",
        "# SECTION custom END.\n",
        "    # SECTION custom BEGIN.\n"
            . getBloc( "# SECTION custom BEGIN.\n", "# SECTION custom END.\n", ${ $tplContent } )
            . "    include imscp_roundcube.conf;\n"
            . "    # SECTION custom END.\n",
        ${ $tplContent }
    );

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::WebmailClients::Roundcube::Installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'roundcube'} = Package::WebmailClients::Roundcube::Roundcube->getInstance();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = $self->{'roundcube'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'newInstall'} = 1;
    $self->{'config'} = $self->{'roundcube'}->{'config'};
    $self;
}

=item _backupConfigFile( $cfgFile )

 Backup the given configuration file

 Param string $cfgFile Path of file to backup
 Return int 0, other on failure

=cut

sub _backupConfigFile
{
    my ( $self, $cfgFile ) = @_;

    return 0 unless -f $cfgFile && -d $self->{'bkpDir'};

    iMSCP::File->new( filename => $cfgFile )->copyFile( $self->{'bkpDir'} . '/' . fileparse( $cfgFile ) . '.' . time, { preserve => 'no' } );
}

=item _installFiles( )

 Install files

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my ( $self ) = @_;

    my $packageDir = "$::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/roundcube";

    unless ( -d $packageDir ) {
        error( "Couldn't find the imscp/roundcube package into the packages cache directory" );
        return 1;
    }

    my $destDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";

    iMSCP::Dir->new( dirname => $destDir )->remove();
    iMSCP::Dir->new( dirname => "$packageDir/iMSCP/config" )->rcopy( $self->{'cfgDir'}, { preserve => 'no' } );
    iMSCP::Dir->new( dirname => "$packageDir/src" )->rcopy( $destDir, { preserve => 'no' } );
}

=item _mergeConfig( )

 Merge old config if any

 Return int 0

=cut

sub _mergeConfig
{
    my ( $self ) = @_;

    if ( %{ $self->{'config'} } ) {
        my %oldConfig = %{ $self->{'config'} };

        tie %{ $self->{'config'} }, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data", nodeferring => 1;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $self->{'config'}->{$key};
            $self->{'config'}->{$key} = $value;
        }

        return 0;
    }

    tie %{ $self->{'config'} }, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data", nodeferring => 1;
    0;
}

=item _setupDatabase( )

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my ( $self ) = @_;

    my $roundcubeDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
    my $imscpDbName = ::setupGetQuestion( 'DATABASE_NAME' );
    my $roundcubeDbName = $imscpDbName . '_roundcube';
    my $dbUser = ::setupGetQuestion( 'ROUNDCUBE_SQL_USER' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = ::setupGetQuestion( 'ROUNDCUBE_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    local $@;
    eval {
        my $db = iMSCP::Database->factory();
        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;

        my $quotedDbName = $dbh->quote_identifier( $roundcubeDbName );

        if ( !$dbh->selectrow_hashref( 'SHOW DATABASES LIKE ?', undef, $roundcubeDbName )
            || !$dbh->selectrow_hashref( "SHOW TABLES FROM $quotedDbName" )
        ) {
            $dbh->do( "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci" );

            my $oldDbName = $db->useDatabase( $roundcubeDbName );
            ::setupImportSqlSchema( $db, "$roundcubeDir/SQL/mysql.initial.sql" ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
            $db->useDatabase( $oldDbName ) if $oldDbName;
        } else {
            $self->{'newInstall'} = FALSE;
        }

        my $sqlServer = Servers::sqld->factory();

        # Drop old SQL user if required
        for my $sqlUser ( $dbOldUser, $dbUser ) {
            next unless $sqlUser;
            for my $host ( $dbUserHost, $oldDbUserHost ) {
                next if !$host || exists $::sqlUsers{$sqlUser . '@' . $host} && !defined $::sqlUsers{$sqlUser . '@' . $host};
                $sqlServer->dropUser( $sqlUser, $host );
            }
        }

        # Create SQL user if required
        if ( defined $::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ));
            $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
            $::sqlUsers{$dbUser . '@' . $dbUserHost} = undef;
        }

        # Give required privileges to this SQL user
        $quotedDbName =~ s/([%_])/\\$1/g;
        $dbh->do( "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?\@?", undef, $dbUser, $dbUserHost );

        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        $quotedDbName = $dbh->quote_identifier( $imscpDbName );
        $dbh->do( "GRANT SELECT (mail_addr, mail_pass), UPDATE (mail_pass) ON $quotedDbName.mail_users TO ?\@?", undef, $dbUser, $dbUserHost );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    0;
}

=item _buildRoundcubeConfig( )

 Build roundcube configuration file

 Return int 0 on success, other on failure

=cut

sub _buildRoundcubeConfig
{
    my ( $self ) = @_;

    my $panelUName =
        my $panelGName = $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $dbName = ::setupGetQuestion( 'DATABASE_NAME' ) . '_roundcube';
    my $dbHost = ::setupGetQuestion( 'DATABASE_HOST' );
    my $dbPort = ::setupGetQuestion( 'DATABASE_PORT' );
    ( my $dbUser = ::setupGetQuestion( 'ROUNDCUBE_SQL_USER' ) ) =~ s%(')%\\$1%g;
    ( my $dbPass = ::setupGetQuestion( 'ROUNDCUBE_SQL_PASSWORD' ) ) =~ s%(')%\\$1%g;

    my $data = {
        BASE_SERVER_VHOST => ::setupGetQuestion( 'BASE_SERVER_VHOST' ),
        DB_NAME           => $dbName,
        DB_HOST           => $dbHost,
        DB_PORT           => $dbPort,
        DB_USER           => $dbUser,
        DB_PASS           => $dbPass,
        TMP_PATH          => "$::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
        DES_KEY           => randomStr( 24, iMSCP::Crypt::ALNUM )
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'roundcube', 'config.inc.php', \my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/config.inc.php" )->get();
        return 1 unless defined $cfgTpl;
    }

    $cfgTpl = process( $data, $cfgTpl );

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/config.inc.php" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $panelUName, $panelGName );
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->copyFile( "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php" );
}

=item _updateDatabase( )

 Update database

 Return int 0 on success other on failure

=cut

sub _updateDatabase
{
    my ( $self ) = @_;

    my $roundcubeDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
    my $roundcubeDbName = ::setupGetQuestion( 'DATABASE_NAME' ) . '_roundcube';
    my $fromVersion = $self->{'config'}->{'ROUNDCUBE_VERSION'} || '0.8.4';

    my $rs = execute(
        "php $roundcubeDir/bin/updatedb.sh --version=$fromVersion --dir=$roundcubeDir/SQL --package=roundcube", \my $stdout, \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    my $db = iMSCP::Database->factory();
    my $dbh = $db->getRawDb();

    local $@;
    eval {
        # Ensure tha users.mail_host entries are set with expected hostname (default to `localhost')
        my $hostname = 'localhost';
        $self->{'eventManager'}->trigger( 'beforeUpdateRoundCubeMailHostEntries', \$hostname ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
        );

        my $oldDbName = $db->useDatabase( $roundcubeDbName );

        {
            local $dbh->{'RaiseError'} = TRUE;

            $dbh->begin_work();
            $dbh->do( 'UPDATE IGNORE users SET mail_host = ?', undef, $hostname );
            $dbh->do( 'DELETE FROM users WHERE mail_host <> ?', undef, $hostname );
            $dbh->commit();
        }

        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        $dbh->rollback();
        error( $@ );
        return 1;
    }

    0;
}

=item _setVersion( )

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ( $self ) = @_;

    my $repoDir = "$::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/roundcube";
    my $json = iMSCP::File->new( filename => "$repoDir/composer.json" )->get();
    return 1 unless defined $json;

    $json = decode_json( $json );
    debug( sprintf( 'Set new roundcube version to %s', $json->{'version'} ));
    $self->{'config'}->{'ROUNDCUBE_VERSION'} = $json->{'version'};
    0;
}

=item _buildHttpdConfig( )

 Build Httpd configuration

=cut

sub _buildHttpdConfig
{
    my ( $self ) = @_;

    if ( -f "$self->{'wrkDir'}/imscp_roundcube.conf" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_roundcube.conf" )->copyFile(
            "$self->{'bkpDir'}/imscp_roundcube.conf." . time,, { preserve => 'no' }
        );
        return $rs if $rs;
    }

    my $frontEnd = Package::FrontEnd->getInstance();
    my $rs = $frontEnd->buildConfFile(
        "$self->{'cfgDir'}/nginx/imscp_roundcube.conf",
        { WEB_DIR => $::imscpConfig{'GUI_ROOT_DIR'} },
        { destination => "$self->{'wrkDir'}/imscp_roundcube.conf" }
    );
    $rs ||= iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_roundcube.conf" )->copyFile(
        "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf", { preserve => 'no' }
    );
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeRoundcubeCleanup' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/roundcube.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/roundcube.old.data" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterRoundcubeCleanup' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
