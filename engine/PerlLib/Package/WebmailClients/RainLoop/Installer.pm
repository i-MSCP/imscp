=head1 NAME

 Package::WebmailClients::RainLoop::Installer - i-MSCP RainLoop package installer

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

package Package::WebmailClients::RainLoop::Installer;

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
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser;
use JSON;
use Package::FrontEnd;
use Servers::sqld;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 This is the installer for the i-MSCP RainLoop package.

 See Package::WebmailClients::RainLoop::RainLoop for more information.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $em ) = @_;

    return 0 if iMSCP::Getopt->skipComposerUpdate;

    $em->registerOne( 'beforeSetupPreInstallServers', sub {
        eval {
            require iMSCP::Composer;

            iMSCP::Composer->new(
                user          => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
                composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
                composer_json => 'composer.json'
            )
                ->require( 'imscp/phpmyadmin', '^1.0' )
                ->dumpComposerJson();
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        0;
    }, 10 );
}

=item setupDialog( \%dialog )

 Setup dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub setupDialog
{
    my ( $self, $dialog ) = @_;

    my $masterSqlUser = ::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = ::setupGetQuestion( 'RAINLOOP_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'imscp_srv_user' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = ::setupGetQuestion(
        'RAINLOOP_SQL_PASSWORD', ( ( iMSCP::Getopt->preseed ) ? randomStr( 16, iMSCP::Crypt::ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    if ( $::reconfigure =~ /^(?:webmail_client_packages|addons|all|forced)$/ || !isValidUsername( $dbUser )
        || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' ) || !isValidPassword( $dbPass )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my ( $rs, $msg ) = ( 0, '' );

        do {
            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the RainLoop SQL user:$msg
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

Please enter a password for the RainLoop SQL user:$msg
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

    ::setupSetQuestion( 'RAINLOOP_SQL_USER', $dbUser );
    ::setupSetQuestion( 'RAINLOOP_SQL_PASSWORD', $dbPass );
    0;
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0

=cut

sub preinstall
{
    my ( $self ) = @_;

    $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install( 

 Process installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_installFiles();
    $rs ||= $self->_mergeConfig();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfig();
    $rs ||= $self->_buildHttpdConfig();
    $rs ||= $self->_setVersion();
    $rs ||= $self->_removeOldVersionFiles();
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
            . "    include imscp_rainloop.conf;\n"
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

 Return Package::WebmailClients::RainLoop::Installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'rainloop'} = Package::WebmailClients::RainLoop::RainLoop->getInstance();
    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = $self->{'rainloop'}->{'cfgDir'};
    $self->{'config'} = $self->{'rainloop'}->{'config'};
    $self;
}

=item _installFiles( )

 Install files

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my ( $self ) = @_;

    my $srcDir = "$::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/rainloop";

    unless ( -d $srcDir ) {
        error( "Couldn't find the imscp/rainloop package in the packages cache directory" );
        return 1;
    }

    my $destDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop";

    # Remove unwanted file to avoid hash naming convention for data directory
    if ( -f "$destDir/data/DATA.php" ) {
        my $rs = iMSCP::File->new( filename => "$destDir/data/DATA.php" )->delFile();
        return $rs if $rs;
    }

    # Handle upgrade from old rainloop data structure
    if ( -d "$destDir/data/_data_11c052c218cd2a2febbfb268624efdc1" ) {
        iMSCP::Dir->new( dirname => "$destDir/data/_data_11c052c218cd2a2febbfb268624efdc1" )->moveDir( "$destDir/data/_data_" );
    }

    # Install new files
    iMSCP::Dir->new( dirname => "$srcDir/src" )->rcopy( $destDir, { preserve => 'no' } );
    iMSCP::Dir->new( dirname => "$srcDir/iMSCP/src" )->rcopy( $destDir, { preserve => 'no' } );
    iMSCP::Dir->new( dirname => "$srcDir/iMSCP/config" )->rcopy( $self->{'cfgDir'}, { preserve => 'no' } );
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

        tie %{ $self->{'config'} }, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/rainloop.data", nodeferring => 1;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $self->{'config'}->{$key};
            $self->{'config'}->{$key} = $value;
        }

        return 0;
    }

    tie %{ $self->{'config'} }, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/rainloop.data", nodeferring => 1;
    0;
}

=item _setupDatabase( )

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my ( $self ) = @_;

    my $imscpDbName = ::setupGetQuestion( 'DATABASE_NAME' );
    my $rainLoopDbName = $imscpDbName . '_rainloop';
    my $dbUser = ::setupGetQuestion( 'RAINLOOP_SQL_USER' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = ::setupGetQuestion( 'RAINLOOP_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    local $@;
    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;

        my $quotedDbName = $dbh->quote_identifier( $rainLoopDbName );

        $dbh->do( "CREATE DATABASE IF NOT EXISTS $quotedDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci" );

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

        $quotedDbName =~ s/([%_])/\\$1/g;
        $dbh->do( "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?\@?", undef, $dbUser, $dbUserHost );

        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        $quotedDbName = $dbh->quote_identifier( $imscpDbName );
        $dbh->do( "GRANT SELECT (mail_addr, mail_pass), UPDATE (mail_pass) ON $quotedDbName.mail_users TO ?\@?", undef, $dbUser, $dbUserHost
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    0;
}

=item _buildConfig( )

 Build RainLoop configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
    my ( $self ) = @_;

    my $confDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop/data/_data_/_default_/configs";
    my $panelUName = my $panelGName =
        $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    for my $confFile ( 'application.ini', 'plugin-imscp-change-password.ini' ) {
        my $data = {
            DATABASE_NAME     => $confFile eq 'application.ini' ? ::setupGetQuestion( 'DATABASE_NAME' ) . '_rainloop' : ::setupGetQuestion( 'DATABASE_NAME' ),
            DATABASE_HOST     => ::setupGetQuestion( 'DATABASE_HOST' ),
            DATABASE_PORT     => ::setupGetQuestion( 'DATABASE_PORT' ),
            DATABASE_USER     => ::setupGetQuestion( 'RAINLOOP_SQL_USER' ),
            DATABASE_PASSWORD => ::setupGetQuestion( 'RAINLOOP_SQL_PASSWORD' ),
            DISTRO_CA_BUNDLE  => ::setupGetQuestion( 'DISTRO_CA_BUNDLE' ),
            DISTRO_CA_PATH    => ::setupGetQuestion( 'DISTRO_CA_PATH' )
        };

        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'rainloop', $confFile, \my $cfgTpl, $data );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = iMSCP::File->new( filename => "$confDir/$confFile" )->get();
            return 1 unless defined $cfgTpl;
        }

        $cfgTpl = process( $data, $cfgTpl );

        my $file = iMSCP::File->new( filename => "$confDir/$confFile" );
        $file->set( $cfgTpl );
        $rs = $file->save();
        $rs ||= $file->owner( $panelUName, $panelGName );
        $rs ||= $file->mode( 0640 );
        return $rs if $rs;
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

    my $packageDir = "$::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/rainloop";
    my $json = iMSCP::File->new( filename => "$packageDir/composer.json" )->get();
    return 1 unless defined $json;

    $json = decode_json( $json );
    debug( sprintf( 'Set new rainloop version to %s', $json->{'version'} ));
    $self->{'config'}->{'RAINLOOP_VERSION'} = $json->{'version'};
    0;
}

=item _setVersion( )

 Remove old version files if any

 Return int 0 on success, other on failure

=cut

sub _removeOldVersionFiles
{
    my ( $self ) = @_;

    my $versionsDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop/rainloop/v";

    for my $versionDir ( iMSCP::Dir->new( dirname => $versionsDir )->getDirs() ) {
        next if $versionDir eq $self->{'config'}->{'RAINLOOP_VERSION'};
        iMSCP::Dir->new( dirname => "$versionsDir/$versionDir" )->remove();
    }

    0;
}

=item _buildHttpdConfig( )

 Build Httpd configuration

=cut

sub _buildHttpdConfig
{
    my ( $self ) = @_;

    $self->{'frontend'}->buildConfFile(
        "$self->{'cfgDir'}/nginx/imscp_rainloop.conf",
        { GUI_PUBLIC_DIR => $::imscpConfig{'GUI_PUBLIC_DIR'} },
        { destination => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_rainloop.conf" }
    );
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeRainloopCleanup' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/rainloop.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/rainloop.old.data" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterRainloopCleanup' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
