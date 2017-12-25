=head1 NAME

 Package::Webmail::Roundcube::Installer - i-MSCP Roundcube package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
use File::chmod qw/ chmod /;
use File::Find qw/ find /;
use iMSCP::Composer;
use iMSCP::Config;
use iMSCP::Crypt qw/ randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dialog::InputValidation qw/ isAvailableSqlUser isStringNotInList isValidPassword isValidUsername /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::TemplateParser qw/ getBlocByRef processByRef replaceBlocByRef /;
use JSON;
use Package::FrontEnd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

our $VERSION = '~1.0.0';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 This is the installer for the i-MSCP Roundcube package.

 See Package::Webmail::Roundcube::Roundcube for more information.

=head1 PUBLIC METHODS

=over 4

=item showDialog( \%dialog )

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my $masterSqlUser = main::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = main::setupGetQuestion(
        'ROUNDCUBE_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || ( iMSCP::Getopt->preseed ? 'imscp_srv_user' : '' )
    );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion(
        'ROUNDCUBE_SQL_PASSWORD',
        ( ( iMSCP::Getopt->preseed ) ? randomStr( 16, iMSCP::Crypt::ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    if ( $main::reconfigure =~ /^(?:webmails|all|forced)$/
        || !isValidUsername( $dbUser )
        || !isStringNotInList( lc $dbUser, 'root', 'debian-sys-maint', lc $masterSqlUser, 'vlogger_user' )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my $rs = 0;

        do {
            if ( $dbUser eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $dbUser = 'imscp_srv_user';
            }

            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a username for the Roundcube SQL user (leave empty for default):
\\Z \\Zn
EOF
        } while $rs < 30
            && ( !isValidUsername( $dbUser )
            || !isStringNotInList( lc $dbUser, 'root', 'debian-sys-maint', lc $masterSqlUser, 'vlogger_user' )
            || !isAvailableSqlUser( $dbUser )
        );

        return $rs unless $rs < 30;
    }

    main::setupSetQuestion( 'ROUNDCUBE_SQL_USER', $dbUser );

    if ( $main::reconfigure =~ /^(?:webmails|all|forced)$/ || !isValidPassword( $dbPass ) ) {
        unless ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            my $rs = 0;

            do {
                if ( $dbPass eq '' ) {
                    $iMSCP::Dialog::InputValidation::lastValidationError = '';
                    $dbPass = randomStr( 16, iMSCP::Crypt::ALNUM );
                }

                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a password for the Roundcube SQL user (leave empty for autogeneration):
\\Z \\Zn
EOF
            } while $rs < 30 && !isValidPassword( $dbPass );

            return $rs unless $rs < 30;

            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
        } else {
            $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
        }
    } elsif ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
        $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
    } else {
        $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
    }

    main::setupSetQuestion( 'ROUNDCUBE_SQL_PASSWORD', $dbPass );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0

=cut

sub preinstall
{
    my ($self) = @_;

    $self->{'frontend'}->getComposer()->requirePackage( 'imscp/roundcube', $VERSION );
    $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->_backupConfigFile( "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php" );
    $rs ||= $self->_installFiles();
    $rs ||= $self->_mergeConfig();
    $rs ||= $self->_buildRoundcubeConfig();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildHttpdConfig();
    $rs ||= $self->_cleanup();
}

=item setGuiPermissions( )

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    return 0 unless -d "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";

    # Set executable bit on *.sh scripts
    eval {
        $File::chmod::UMASK = 0; # Stick to system CHMOD(1) behavior
        find(
            sub {
                return unless substr( $_, -3 ) eq '.sh' && !-l;
                chmod( 'u+x', $_ ) or die( sprintf( "Couldn't set executable bit on the %s file: %s", $File::Find::name, $! ));
            },
            "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail"
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterFrontEndBuildConfFile( \$tplContent, $filename )

 Include httpd configuration into frontEnd vhost files

 Param string \$tplContent Reference to template file content
 Param string $tplName Template name
 Return int 0 on success, other on failure

=cut

sub afterFrontEndBuildConfFile
{
    my ($tplContent, $tplName) = @_;

    return 0 unless ( $tplName eq '00_master.nginx' && main::setupGetQuestion( 'BASE_SERVER_VHOST_PREFIX' ) ne 'https://' )
        || $tplName eq '00_master_ssl.nginx';

    replaceBlocByRef( "# SECTION custom BEGIN.\n", "# SECTION custom END.\n", <<"EOF", $tplContent );
    # SECTION custom BEGIN.
@{ [ getBlocByRef( "# SECTION custom BEGIN.\n", "# SECTION custom END.\n", $tplContent ) ] } 
    include imscp_roundcube.conf;
    # SECTION custom END.
EOF
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::Webmail::Roundcube::Installer

=cut

sub _init
{
    my ($self) = @_;

    $self->{'roundcube'} = Package::Webmail::Roundcube::Roundcube->getInstance();
    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = $self->{'roundcube'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
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
    my ($self, $cfgFile) = @_;

    return 0 unless -f $cfgFile && -d $self->{'bkpDir'};

    iMSCP::File->new( filename => $cfgFile )->copyFile( $self->{'bkpDir'} . '/' . fileparse( $cfgFile ) . '.' . time, { preserve => 'no' } );
}

=item _installFiles( )

 Install files

 Return int 0 on success, 1 on failure

=cut

sub _installFiles
{
    my ($self) = @_;

    eval {
        my $packageDir = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/roundcube";
        -d $packageDir or die( "Couldn't find the imscp/roundcube package into the packages cache directory" );
        my $destDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";

        iMSCP::Dir->new( dirname => $destDir )->clear ( undef, qr/^logs$/, 'inverse_matching' ) if -d $destDir;
        iMSCP::Dir->new( dirname => "$packageDir/iMSCP/config" )->rcopy( $self->{'cfgDir'}, { preserve => 'no' } );
        iMSCP::Dir->new( dirname => "$packageDir/src" )->rcopy( $destDir, { preserve => 'no' } );

        my $usergroup = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

        for ( 'cron.d', 'logrotate.d' ) {
            next unless -f "$packageDir/iMSCP/$_/imscp_roundcube";

            my $fileContent = iMSCP::File->new( filename => "$packageDir/iMSCP/$_/imscp_roundcube" )->get();
            defined $fileContent or die( sprintf( "Couldn't read the %s file", "$packageDir/iMSCP/$_/imscp_roundcube.conf" ));

            processByRef(
                {
                    GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'},
                    PANEL_USER     => $usergroup,
                    PANEL_GROUP    => $usergroup
                },
                \$fileContent
            );

            my $file = iMSCP::File->new( filename => "/etc/$_/imscp_roundcube" );
            my $rs = $file->set( $fileContent );
            $rs ||= $file->save();
            $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
        }

        # Set permissions -- Needed at this stage to make scripts from the bin/
        # directory executable
        $self->setGuiPermissions() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _mergeConfig( )

 Merge old config if any

 Return int 0

=cut

sub _mergeConfig
{
    my ($self) = @_;

    if ( %{$self->{'config'}} ) {
        my %oldConfig = %{$self->{'config'}};

        tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data", nodeferring => 1;

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $self->{'config'}->{$key};
            $self->{'config'}->{$key} = $value;
        }

        return 0;
    }

    tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data", nodeferring => 1;
    0;
}

=item _buildRoundcubeConfig( )

 Build roundcube configuration file

 Return int 0 on success, other on failure

=cut

sub _buildRoundcubeConfig
{
    my ($self) = @_;

    my $usergroup = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' ) . '_roundcube';
    my $dbHost = main::setupGetQuestion( 'DATABASE_HOST' );
    my $dbPort = main::setupGetQuestion( 'DATABASE_PORT' );
    ( my $dbUser = main::setupGetQuestion( 'ROUNDCUBE_SQL_USER' ) ) =~ s%(')%\\$1%g;
    ( my $dbPass = main::setupGetQuestion( 'ROUNDCUBE_SQL_PASSWORD' ) ) =~ s%(')%\\$1%g;
    my $data = {
        BASE_SERVER_VHOST => main::setupGetQuestion( 'BASE_SERVER_VHOST' ),
        DB_NAME           => $dbName,
        DB_HOST           => $dbHost,
        DB_PORT           => $dbPort,
        DB_USER           => $dbUser,
        DB_PASS           => $dbPass,
        TMP_PATH          => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
        DES_KEY           => randomStr( 24, iMSCP::Crypt::ALNUM )
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'roundcube', 'config.inc.php', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/config.inc.php" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/config.inc.php" ));
            return 1;
        }
    }

    processByRef( $data, \$cfgTpl );

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/config.inc.php" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $usergroup, $usergroup );
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->copyFile( "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php" );
}

=item _setupDatabase( )

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my ($self) = @_;

    my $rcDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
    my $imscpDbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $rcDbName = $imscpDbName . '_roundcube';
    my $dbUser = main::setupGetQuestion( 'ROUNDCUBE_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = main::setupGetQuestion( 'ROUNDCUBE_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    eval {
        my $sqlServer = Servers::sqld->factory();

        # Drop old SQL user if needed
        for my $sqlUser ( $dbOldUser, $dbUser ) {
            next unless $sqlUser;

            for my $host( $dbUserHost, $oldDbUserHost ) {
                next if !$host || ( exists $main::sqlUsers{$sqlUser . '@' . $host} && !defined $main::sqlUsers{$sqlUser . '@' . $host} );
                $sqlServer->dropUser( $sqlUser, $host );
            }
        }

        # Create SQL user if required
        if ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ));
            $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = undef;
        }

        my $db = iMSCP::Database->getInstance();
        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'} = 1;

        # Give required privileges on Roundcube database to SQL user
        # According https://dev.mysql.com/doc/refman/5.7/en/grant.html,
        # we can grant privileges on databases that doesn't exist yet.
        my $quotedRcDbName = $dbh->quote_identifier( $rcDbName );
        $dbh->do( "GRANT ALL PRIVILEGES ON @{[ $quotedRcDbName =~ s/([%_])/\\$1/gr ]}.*TO ?\@?", undef, $dbUser, $dbUserHost );

        # Give required privileges on the imscp.mail table
        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        $dbh->do(
            "GRANT SELECT (mail_addr, mail_pass), UPDATE (mail_pass) ON @{[ $dbh->quote_identifier( $imscpDbName ) ]}.mail_users TO ?\@?",
            undef, $dbUser, $dbUserHost
        );

        # Create/Update Roundcube database

        if ( !$dbh->selectrow_hashref( 'SHOW DATABASES LIKE ?', undef, $rcDbName )
            || !$dbh->selectrow_hashref( "SHOW TABLES FROM $quotedRcDbName" )
        ) {
            $dbh->do( "CREATE DATABASE IF NOT EXISTS $quotedRcDbName CHARACTER SET utf8 COLLATE utf8_unicode_ci" );

            # Create Roundcube database
            my $rs = execute( [ "$rcDir/bin/initdb.sh", "--dir", "$rcDir/SQL", '--package', 'roundcube' ], \ my $stdout, \ my $stderr );
            debug( $stdout ) if $stdout;
            die( $stderr || 'Unknown error' ) if $rs;
        } else {
            # Update Roundcube database
            my $rs = execute( [ "$rcDir/bin/updatedb.sh", '--dir', "$rcDir/SQL", '--package', 'roundcube' ], \ my $stdout, \ my $stderr );
            debug( $stdout ) if $stdout;
            die( $stderr || 'Unknown error' ) if $rs;

            eval {
                # Ensure tha users.mail_host entries are set with expected hostname (default to `localhost')
                my $hostname = 'localhost';
                $self->{'eventManager'}->trigger( 'beforeUpdateRoundCubeMailHostEntries', \$hostname ) == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                );

                my $oldDbName = $db->useDatabase( $rcDbName );

                $dbh->begin_work();
                $dbh->do( 'UPDATE IGNORE users SET mail_host = ?', undef, $hostname );
                $dbh->do( 'DELETE FROM users WHERE mail_host <> ?', undef, $hostname );
                $dbh->commit();

                $db->useDatabase( $oldDbName ) if $oldDbName;
            };
            if ( $@ ) {
                $dbh->rollback() if $dbh->{'BegunWork'};
                die( $@ );
            }
        }

        $self->{'config'}->{'DATABASE_USER'} = $dbUser;
        $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _buildHttpdConfig( )

 Build Httpd configuration

=cut

sub _buildHttpdConfig
{
    my ($self) = @_;

    if ( -f "$self->{'wrkDir'}/imscp_roundcube.conf" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp_roundcube.conf" )->copyFile(
            "$self->{'bkpDir'}/imscp_roundcube.conf." . time, { preserve => 'no' }
        );
        return $rs if $rs;
    }

    my $frontEnd = Package::FrontEnd->getInstance();
    my $rs = $frontEnd->buildConfFile(
        "$self->{'cfgDir'}/nginx/imscp_roundcube.conf",
        { GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'} },
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
    my ($self) = @_;

    return 0 unless -f "$self->{'cfgDir'}/roundcube.old.data";

    iMSCP::File->new( filename => "$self->{'cfgDir'}/roundcube.old.data" )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
