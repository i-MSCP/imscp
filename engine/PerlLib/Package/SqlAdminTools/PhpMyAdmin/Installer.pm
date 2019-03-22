=head1 NAME

 Package::SqlAdminTools::PhpMyAdmin::Installer - SqlAdminTools package installer

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

package Package::SqlAdminTools::PhpMyAdmin::Installer;

use strict;
use warnings;
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Composer;
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
use Package::FrontEnd;
use Package::SqlAdminTools::PhpMyAdmin::PhpMyAdmin;
use Servers::sqld;
use JSON;
use version;
use parent 'Common::SingletonClass';

%::sqlUsers = () unless %::sqlUsers;

=head1 DESCRIPTION

 SqlAdminTools package installer.

=head1 PUBLIC METHODS

=over 4

=item showDialog( \%dialog )

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub showDialog
{
    my ( $self, $dialog ) = @_;

    my $masterSqlUser = ::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = ::setupGetQuestion( 'PHPMYADMIN_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'imscp_srv_user' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = ::setupGetQuestion(
        'PHPMYADMIN_SQL_PASSWORD', ( ( iMSCP::Getopt->preseed ) ? randomStr( 16, iMSCP::Crypt::ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    if ( $::reconfigure =~ /^(?:sql_admin_tool_packages|addons|all|forced)$/ || !isValidUsername( $dbUser )
        || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' ) || !isValidPassword( $dbPass )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my ( $rs, $msg ) = ( 0, '' );

        do {
            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the PhpMyAdmin SQL user:$msg
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

Please enter a password for the PhpMyAdmin SQL user:$msg
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

    ::setupSetQuestion( 'PHPMYADMIN_SQL_USER', $dbUser );
    ::setupSetQuestion( 'PHPMYADMIN_SQL_PASSWORD', $dbPass );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

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
            ->require( 'imscp/phpmyadmin', '^1.0' )
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

    my $rs = $self->_backupConfigFile( "$::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'PHPMYADMIN_CONF_DIR'}/config.inc.php" );
    $rs ||= $self->_installFiles();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_setupSqlUser();
    $rs ||= $self->_generateBlowfishSecret();
    $rs ||= $self->_buildConfig();
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
            . "    include imscp_pma.conf;\n"
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

 Return Package::PhpMyAdmin::Installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'phpmyadmin'} = Package::SqlAdminTools::PhpMyAdmin->getInstance();
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = $self->{'phpmyadmin'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'phpmyadmin'}->{'config'};
    $self;
}

=item _backupConfigFile( )

 Backup the given configuration file

 Return int 0

=cut

sub _backupConfigFile
{
    my ( $self, $cfgFile ) = @_;

    return 0 unless -f $cfgFile && -d $self->{'bkpDir'};
    iMSCP::File->new( filename => $cfgFile )->copyFile( $self->{'bkpDir'} . '/' . fileparse( $cfgFile ) . '.' . time, { preserve => 'no' } );
}

=item _installFiles( )

 Install files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my $packageDir = "$::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/phpmyadmin";

    unless ( -d $packageDir ) {
        error( "Couldn't find the imscp/phpmyadmin package into the packages cache directory" );
        return 1;
    }

    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma" )->remove();
    iMSCP::Dir->new( dirname => "$packageDir" )->rcopy( "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma", { preserve => 'no' } );
}

=item _setupSqlUser( )

 Setup restricted SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
    my ( $self ) = @_;

    my $phpmyadminDbName = ::setupGetQuestion( 'DATABASE_NAME' ) . '_pma';
    my $dbUser = ::setupGetQuestion( 'PHPMYADMIN_SQL_USER' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = ::setupGetQuestion( 'PHPMYADMIN_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    local $@;
    eval {
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

        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;

        # Give required privileges to this SQL user

        $dbh->do( 'GRANT USAGE ON mysql.* TO ?@?', undef, $dbUser, $dbUserHost );
        $dbh->do( 'GRANT SELECT ON mysql.db TO ?@?', undef, $dbUser, $dbUserHost );
        $dbh->do(
            '
                GRANT SELECT (Host, User, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv,
                    Reload_priv, Shutdown_priv, Process_priv, File_priv, Grant_priv, References_priv, Index_priv,
                    Alter_priv, Show_db_priv, Super_priv, Create_tmp_table_priv, Lock_tables_priv, Execute_priv,
                    Repl_slave_priv, Repl_client_priv)
                ON mysql.user
                TO ?@?
            ',
            undef, $dbUser, $dbUserHost
        );

        # Check for mysql.host table existence (as for MySQL >= 5.6.7, the mysql.host table is no longer provided)
        if ( $dbh->selectrow_hashref( "SHOW tables FROM mysql LIKE 'host'" ) ) {
            $dbh->do( 'GRANT SELECT ON mysql.user TO ?@?', undef, $dbUser, $dbUserHost );
            $dbh->do( 'GRANT SELECT (Host, Db, User, Table_name, Table_priv, Column_priv) ON mysql.tables_priv TO?@?', undef, $dbUser, $dbUserHost );
        }

        ( my $quotedDbName = $dbh->quote_identifier( $phpmyadminDbName ) ) =~ s/([%_])/\\$1/g;
        $dbh->do( "GRANT ALL PRIVILEGES ON $quotedDbName.* TO ?\@?", undef, $dbUser, $dbUserHost );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    0;
}

=item _setupDatabase( )

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my $phpmyadminDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma";
    my $phpmyadminDbName = ::setupGetQuestion( 'DATABASE_NAME' ) . '_pma';

    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;
        # Drop previous database
        # FIXME: Find a better way to handle upgrade
        $dbh->do( "DROP DATABASE IF EXISTS " . $dbh->quote_identifier( $phpmyadminDbName ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Create database

    my $schemaFilePath = "$phpmyadminDir/sql/create_tables.sql";

    my $file = iMSCP::File->new( filename => $schemaFilePath );
    my $fileContentRef = $file->getAsRef();
    return 1 unless defined $fileContentRef;

    ${ $fileContentRef } =~ s/^(-- Database :) `phpmyadmin`/$1 `$phpmyadminDbName`/im;
    ${ $fileContentRef } =~ s/^(CREATE DATABASE IF NOT EXISTS) `phpmyadmin`/$1 `$phpmyadminDbName`/im;
    ${ $fileContentRef } =~ s/^(USE) phpmyadmin;/$1 `$phpmyadminDbName`;/im;

    my $rs = $file->save();
    return $rs if $rs;

    $rs = execute( "cat $schemaFilePath | mysql", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _buildHttpdConfig( )

 Build Httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
    my $frontEnd = Package::FrontEnd->getInstance();
    $frontEnd->buildConfFile(
        "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/PhpMyAdmin/config/nginx/imscp_pma.nginx",
        { GUI_PUBLIC_DIR => $::imscpConfig{'GUI_PUBLIC_DIR'} },
        { destination => "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_pma.conf" }
    );
}

=item _setVersion( )

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ( $self ) = @_;

    my $json = iMSCP::File->new( filename => "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/composer.json" )->get();
    return 1 unless defined $json;

    $json = decode_json( $json );
    debug( sprintf( 'Set new phpMyAdmin version to %s', $json->{'version'} ));
    $self->{'config'}->{'PHPMYADMIN_VERSION'} = $json->{'version'};
    0;
}

=item _generateBlowfishSecret( )

 Generate blowfish secret

 Return int 0

=cut

sub _generateBlowfishSecret
{
    $_[0]->{'config'}->{'BLOWFISH_SECRET'} = randomStr( 32, iMSCP::Crypt::ALNUM );
    0;
}

=item _buildConfig( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
    my ( $self ) = @_;

    my $panelUName = my $panelGName = $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $confDir = "$::imscpConfig{'GUI_PUBLIC_DIR'}/$self->{'config'}->{'PHPMYADMIN_CONF_DIR'}";
    my $dbName = ::setupGetQuestion( 'DATABASE_NAME' ) . '_pma';
    ( my $dbUser = ::setupGetQuestion( 'PHPMYADMIN_SQL_USER' ) ) =~ s%('|\\)%\\$1%g;
    my $dbHost = ::setupGetQuestion( 'DATABASE_HOST' );
    my $dbPort = ::setupGetQuestion( 'DATABASE_PORT' );
    ( my $dbPass = ::setupGetQuestion( 'PHPMYADMIN_SQL_PASSWORD' ) ) =~ s%('|\\)%\\$1%g;
    ( my $blowfishSecret = $self->{'config'}->{'BLOWFISH_SECRET'} ) =~ s%('|\\)%\\$1%g;

    my $data = {
        PMA_DATABASE => $dbName,
        PMA_USER     => $dbUser,
        PMA_PASS     => $dbPass,
        HOSTNAME     => $dbHost,
        PORT         => $dbPort,
        UPLOADS_DIR  => "$::imscpConfig{'GUI_ROOT_DIR'}/data/uploads",
        TMP_DIR      => "$::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
        BLOWFISH     => $blowfishSecret
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'phpmyadmin', 'imscp.config.inc.php', \my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$confDir/imscp.config.inc.php" )->get();
        return 1 unless defined $cfgTpl;
    }

    $cfgTpl = process( $data, $cfgTpl );

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/config.inc.php" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $panelUName, $panelGName );
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->copyFile( "$confDir/config.inc.php" );
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpMyAdminCleanup' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/phpmyadmin.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/phpmyadmin.old.data" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterPhpMyAdminCleanup' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
