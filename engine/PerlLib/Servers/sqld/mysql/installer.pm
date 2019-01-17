=head1 NAME

 Servers::sqld::mysql::installer - i-MSCP MySQL server installer implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::sqld::mysql::installer;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Crypt qw/ ALNUM encryptRijndaelCBC decryptRijndaelCBC randomStr /;
use iMSCP::Database '$DATABASE';
use iMSCP::Database::MySQL;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser 'process';
use iMSCP::Umask '$UMASK';
use Net::LibIDN qw/ idn_to_ascii idn_to_unicode /;
use Servers::sqld::mysql;
use Try::Tiny;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $em ) = @_;

    $em->register( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->masterSqlUserDialog( @_ ) },
            sub { $self->sqlUserHostDialog( @_ ) },
            sub { $self->databaseNameDialog( @_ ) },
            sub { $self->databasePrefixDialog( @_ ) };
        0;
    } );
}

=item masterSqlUserDialog( \%dialog )

 Ask for i-MSCP master SQL user

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub masterSqlUserDialog
{
    my ( $self, $dialog ) = @_;

    my $hostname = ::setupGetQuestion( 'DATABASE_HOST' );
    my $port = ::setupGetQuestion( 'DATABASE_PORT' );
    my $user = ::setupGetQuestion( 'DATABASE_USER', 'imscp_user' );
    $user = 'imscp_user' if lc( $user ) eq 'root'; # Handle upgrade case
    my $enc = iMSCP::Database::Encryption->getInstance();
    my $pwd = ::setupGetQuestion( 'DATABASE_PASSWORD', iMSCP::Getopt->preseed ? randomStr( 16, ALNUM ) : '' );
    $pwd = decryptRijndaelCBC( $enc->getKey(), $enc->getIV(), $pwd ) unless $pwd eq '' || iMSCP::Getopt->preseed;
    my $rs = 0;

    $rs = $self->_askSqlRootUser( $dialog ) if iMSCP::Getopt->preseed;
    return $rs if $rs;

    if ( iMSCP::Getopt->reconfigure =~ /(?:sql|servers|all|forced)$/ || !isNotEmpty( $hostname ) || !isNotEmpty( $port ) || !isNotEmpty( $user )
        || !isStringNotInList( $user, 'debian-sys-maint', 'imscp_srv_user', 'mysql.user', 'root', 'vlogger_user' )
        || !isNotEmpty( $pwd ) || ( !iMSCP::Getopt->preseed && !$self->_tryDbConnect( $hostname, $port, $user, $pwd ) )
    ) {
        getMessageByType( 'error', { amount => 1, remove => TRUE } ); # Remove possible SQL connect error
        $rs = $self->_askSqlRootUser( $dialog ) unless iMSCP::Getopt->preseed;
        return $rs if $rs >= 30;

        my $msg = '';
        do {
            ( $rs, $user ) = $dialog->inputbox( <<"EOF", $user );

Please enter a username for the master i-MSCP SQL user:$msg
EOF
            $msg = '';
            if ( !isValidUsername( $user )
                || !isStringNotInList( $user, 'debian-sys-maint', 'imscp_srv_user', 'mysql.user', 'root', 'vlogger_user' )
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        $pwd = isValidPassword( $pwd ) ? $pwd : '';
        do {
            ( $rs, $pwd ) = $dialog->inputbox( <<"EOF", $pwd || randomStr( 16, iMSCP::Crypt::ALNUM ));

Please enter a password for the master i-MSCP SQL user:$msg
EOF
            $msg = isValidPassword( $pwd ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'DATABASE_USER', $user );
    ::setupSetQuestion( 'DATABASE_PASSWORD', encryptRijndaelCBC( $enc->getKey(), $enc->getIV(), $pwd ));
    # Substitute SQL root user data with i-MSCP master user data if needed
    ::setupSetQuestion( 'SQL_ROOT_USER', ::setupGetQuestion( 'SQL_ROOT_USER', $user ));
    ::setupSetQuestion( 'SQL_ROOT_PASSWORD', ::setupGetQuestion( 'SQL_ROOT_PASSWORD', $pwd ));
    0;
}

=item sqlUserHostDialog( \%dialog )

 Ask for i-MSCP database name

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub sqlUserHostDialog
{
    my ( undef, $dialog ) = @_;

    if ( $::imscpConfig{'SQL_PACKAGE'} ne 'Servers::sqld::remote' ) {
        ::setupSetQuestion( 'DATABASE_USER_HOST', 'localhost' );
        return 0;
    }

    my $hostname = ::setupGetQuestion( 'DATABASE_USER_HOST', ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' ));
    if ( grep ($hostname eq $_, ( 'localhost', '127.0.0.1', '::1' )) ) {
        $hostname = ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );
    }

    if ( iMSCP::Getopt->reconfigure =~ /^(?:sql|servers|all|forced)$/ ||
        ( $hostname ne '%' && !isValidHostname( $hostname ) && !isValidIpAddr( $hostname, qr/^(?:PUBLIC|GLOBAL-UNICAST)$/ ) )
    ) {
        my ( $rs, $msg ) = ( 0, '' );
        do {
            ( $rs, $hostname ) = $dialog->inputbox( <<"EOF", idn_to_unicode( $hostname, 'utf-8' ));

Please enter the host from which SQL users created by i-MSCP must be allowed to connect:$msg
EOF
            $msg = '';
            if ( $hostname ne '%' && !isValidHostname( $hostname ) && !isValidIpAddr( $hostname, qr/^(?:PUBLIC|GLOBAL-UNICAST)$/ ) ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'DATABASE_USER_HOST', idn_to_ascii( $hostname, 'utf-8' ));
    0;
}

=item databaseNameDialog( \%dialog )

 Ask for i-MSCP database name

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub databaseNameDialog
{
    my ( undef, $dialog ) = @_;

    my $dbName = ::setupGetQuestion( 'DATABASE_NAME', 'imscp' );
    my $db = iMSCP::Database->factory();

    if ( iMSCP::Getopt->reconfigure =~ /^(?:sql|servers|all|forced)$/ || (
        ( !$db->isDatabase( $dbName ) || !$db->databaseHasTables( $dbName, qw/ server_ips user_gui_props reseller_props / ) )
            && !iMSCP::Getopt->preseed
    ) ) {
        my ( $rs, $msg ) = ( 0, '' );
        do {
            ( $rs, $dbName ) = $dialog->inputbox( <<"EOF", $dbName );

Please enter a database name for i-MSCP:$msg
EOF
            $msg = '';
            if ( !isValidDbName( $dbName ) ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            } else {

                if ( $db->isDatabase( $dbName ) && !$db->databaseHasTables( $dbName, qw/ server_ips user_gui_props reseller_props / ) ) {
                    $msg = "\n\n\\Z1Database '$dbName' exists but doesn't look like an i-MSCP database.\\Zn\n\nPlease try again:";
                }
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        my $oldDbName = ::setupGetQuestion( 'DATABASE_NAME' );
        if ( $oldDbName && $dbName ne $oldDbName && $db->isDatabase( $oldDbName ) &&
            $db->databaseHasTables( $oldDbName, qw/ server_ips user_gui_props reseller_props / )
        ) {
            if ( $dialog->yesno( <<"EOF", TRUE ) ) {
A database '$::imscpConfig{'DATABASE_NAME'}' for i-MSCP already exists.

Are you sure you want to create a new database for i-MSCP?
Keep in mind that the new database will be free of any reseller and customer data.

\\Z4Note:\\Zn If the database you want to create already exists, nothing will happen.
EOF
                goto &{ databaseNameDialog };
            }
        }
    }

    ::setupSetQuestion( 'DATABASE_NAME', $dbName );
    0;
}

=item databasePrefixDialog( \%dialog )

 Ask for database prefix

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub databasePrefixDialog
{
    my ( undef, $dialog ) = @_;

    my $prefix = ::setupGetQuestion( 'MYSQL_PREFIX' );

    if ( iMSCP::Getopt->reconfigure =~ /^(?:sql|servers|all|forced)$/ || $prefix !~ /^(?:behind|infront|none)$/ ) {
        ( my $rs, $prefix ) = $dialog->radiolist( <<"EOF", [ 'infront', 'behind', 'none' ], $prefix =~ /^(?:behind|infront)$/ ? $prefix : 'none' );

\\Z4\\Zb\\ZuMySQL Database Prefix/Suffix\\Zn

Do you want use a prefix or suffix for customer's SQL databases?

\\Z4Infront:\\Zn A numeric prefix such as '1_' will be added to each customer
         SQL user and database name.
 \\Z4Behind:\\Zn A numeric suffix such as '_1' will be added to each customer
         SQL user and database name.
   \\Z4None\\Zn: Choice will be let to customer.
EOF
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'MYSQL_PREFIX', $prefix );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->_setTypeAndVersion();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_updateServerConfig();
    $rs ||= $self->_setupMasterSqlUser();
    $rs ||= $self->_setupSecureInstallation();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_oldEngineCompatibility();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::sqld::mysql:installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'sqld'} = Servers::sqld::mysql->getInstance();
    $self->{'cfgDir'} = $self->{'sqld'}->{'cfgDir'};
    $self->{'config'} = $self->{'sqld'}->{'config'};
    $self;
}

=item _askSqlRootUser( )

 Ask for SQL root user

=cut

sub _askSqlRootUser
{
    my ( $self, $dialog ) = @_;

    my $hostname = ::setupGetQuestion( 'DATABASE_HOST', $::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::remote' ? '' : 'localhost' );
    if ( $::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::remote' && grep { $hostname eq $_ } ( 'localhost', '127.0.0.1', '::1' ) ) {
        $hostname = '';
    }
    my $port = ::setupGetQuestion( 'DATABASE_PORT', 3306 );
    my $user = ::setupGetQuestion( 'SQL_ROOT_USER', 'root' );
    my $pwd = ::setupGetQuestion( 'SQL_ROOT_PASSWORD' );

    if ( $hostname eq 'localhost' ) {
        # If authentication is made through unix socket, password is normally not required.
        # We try a connect without password with 'root' as user and we return on success
        for my $host ( 'localhost', '127.0.0.1' ) {
            next if $self->_tryDbConnect( $host, $port, $user, $pwd );
            ::setupSetQuestion( 'DATABASE_HOST', $_ );
            ::setupSetQuestion( 'DATABASE_PORT', $port );
            ::setupSetQuestion( 'SQL_ROOT_USER', $user );
            ::setupSetQuestion( 'SQL_ROOT_PASSWORD', $pwd );
            return 0;
        }
    }

    my ( $rs, $msg ) = ( 0, '' );

    do {
        ( $rs, $hostname ) = $dialog->inputbox( <<"EOF", $hostname );

Please enter your SQL server hostname or IP address:$msg
EOF
        $msg = '';
        if ( $hostname ne 'localhost' && !isValidHostname( $hostname ) && !isValidIpAddr( $hostname ) ) {
            $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
        }
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    do {
        ( $rs, $port ) = $dialog->inputbox( <<"EOF", $port );

Please enter your SQL server port:$msg
EOF
        $msg = '';
        if ( !isNumber( $port ) || !isNumberInRange( $port, 1025, 65535 ) ) {
            $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
        }
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    do {
        ( $rs, $user ) = $dialog->inputbox( <<"EOF", $user );

Please enter your SQL root username:$msg

Note that this user must have full privileges on the SQL server.
i-MSCP only uses that user while installation or reconfiguration.
EOF
        $msg = isNotEmpty( $user ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    do {
        ( $rs, $pwd ) = $dialog->passwordbox( <<"EOF" );

Please enter your SQL root user password:$msg
EOF
        $msg = isNotEmpty( $pwd ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    unless ( $self->_tryDbConnect( $hostname, $port, $user, $pwd ) ) {
        chomp( my $error = getMessageByType( 'error', { amount => 1, remove => TRUE } ));
        $rs = $dialog->msgbox( <<"EOF" );

\\Z1Connection to SQL server failed\\Zn

i-MSCP installer couldn't connect to SQL server using the following data:

\\Z4Host:\\Zn $hostname
\\Z4Port:\\Zn $port
\\Z4Username:\\Zn $user
\\Z4Password:\\Zn $pwd

Error was: \\Z1$error\\Zn

Please try again.
EOF
        goto &{ _askSqlRootUser };
    }

    ::setupSetQuestion( 'DATABASE_HOST', idn_to_ascii( $hostname, 'utf-8' ));
    ::setupSetQuestion( 'DATABASE_PORT', $port );
    ::setupSetQuestion( 'SQL_ROOT_USER', $user );
    ::setupSetQuestion( 'SQL_ROOT_PASSWORD', $pwd );
    0;
}

=item _setTypeAndVersion( )

 Set SQL server type and version

 Return 0 on success, other on failure

=cut

sub _setTypeAndVersion
{
    my ( $self ) = @_;

    try {
        my $row = iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            $_->selectrow_hashref( 'SELECT @@version, @@version_comment' );
        } ) or die( "Could't find SQL server type and version" );

        my $type;
        if ( index( lc $row->{'@@version'}, 'mariadb' ) != -1 ) {
            $type = 'mariadb';
        } elsif ( index( lc $row->{'@@version_comment'}, 'percona' ) != -1 ) {
            $type = 'percona';
        } else {
            $type = 'mysql';
        }

        my ( $version ) = $row->{'@@version'} =~ /^([0-9]+(?:\.[0-9]+){1,2})/;
        defined $version or die( "Couldn't find SQL server version" );

        debug( sprintf( 'SQL server type set to: %s', $type ));
        $self->{'config'}->{'SQLD_TYPE'} = $type;
        debug( sprintf( 'SQL server version set to: %s', $version ));
        $self->{'config'}->{'SQLD_VERSION'} = $version;
        0;
    } catch {
        error( $_ );
        1;
    };
}

=item _buildConf( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldBuildConf' );
    return $rs if $rs;

    my $rootUName = $::imscpConfig{'ROOT_USER'};
    my $rootGName = $::imscpConfig{'ROOT_GROUP'};
    my $mysqlGName = $self->{'config'}->{'SQLD_GROUP'};
    my $confDir = $self->{'config'}->{'SQLD_CONF_DIR'};

    # Make sure that the conf.d directory exists
    iMSCP::Dir->new( dirname => "$confDir/conf.d" )->make( {
        user  => $rootUName,
        group => $rootGName,
        mode  => 0755
    } );

    # Create the /etc/mysql/my.cnf file if missing
    unless ( -f "$confDir/my.cnf" ) {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'my.cnf', \my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = "!includedir $confDir/conf.d/\n";
        } elsif ( $cfgTpl !~ m%^!includedir\s+$confDir/conf.d/\n%m ) {
            $cfgTpl .= "!includedir $confDir/conf.d/\n";
        }

        my $file = iMSCP::File->new( filename => "$confDir/my.cnf" );
        $file->set( $cfgTpl );

        $rs = $file->save();
        $rs ||= $file->owner( $rootUName, $rootGName );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'imscp.cnf', \my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp.cnf" )->get();
        return 1 unless defined $cfgTpl;
    }

    $cfgTpl .= <<'EOF';
[mysqld]
performance_schema = 0
max_connections = 500
max_allowed_packet = 500M
EOF

    ( my $user = ::setupGetQuestion( 'DATABASE_USER' ) ) =~ s/"/\\"/g;
    my $enc = iMSCP::Database::Encryption->getInstance();
    ( my $pwd = decryptRijndaelCBC( $enc->getKey(), $enc->getIV(), ::setupGetQuestion( 'DATABASE_PASSWORD' )) ) =~ s/"/\\"/g;
    my $variables = {
        DATABASE_HOST     => ::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT     => ::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER     => $user,
        DATABASE_PASSWORD => $pwd,
        SQLD_SOCK_DIR     => $self->{'config'}->{'SQLD_SOCK_DIR'}
    };

    if ( version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.5.0' ) ) {
        $cfgTpl .= "innodb_use_native_aio = @{ [ $self->isInsideContainer() ? 0 : 1 ] }\n";
    }

    # Fix For: The 'INFORMATION_SCHEMA.SESSION_VARIABLES' feature is disabled; see the documentation for
    # 'show_compatibility_56' (3167) - Occurs when executing mysqldump with Percona server 5.7.x
    if ( $::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::percona'
        && version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.7.6' )
    ) {
        $cfgTpl .= "show_compatibility_56 = 1\n";
    }

    # For backward compatibility - We will review this in later version
    # TODO Handle mariadb case when ready. See https://mariadb.atlassian.net/browse/MDEV-7597
    if ( version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.7.4' )
        && $::imscpConfig{'SQL_PACKAGE'} ne 'Servers::sqld::mariadb'
    ) {
        $cfgTpl .= "default_password_lifetime = 0\n";
    }

    $cfgTpl .= "event_scheduler = DISABLED\n";
    $cfgTpl = process( $variables, $cfgTpl );

    local $UMASK = 027; # imscp.cnf file must not be created world-readable

    my $file = iMSCP::File->new( filename => "$confDir/conf.d/imscp.cnf" );
    $file->set( $cfgTpl );

    $rs = $file->save();
    $rs ||= $file->owner( $rootUName, $mysqlGName );
    $rs ||= $file->mode( 0640 );
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldBuildConf' );
}

=item _updateServerConfig( )

 Update server configuration

  - Upgrade MySQL system tables if necessary
  - Disable unwanted plugins

 Return 0 on success, other on failure

=cut

sub _updateServerConfig
{
    my ( $self ) = @_;

    try {
        if ( iMSCP::ProgramFinder::find( 'dpkg' ) && iMSCP::ProgramFinder::find( 'mysql_upgrade' ) ) {
            my $rs = execute( "dpkg -l mysql-community* percona-server-* | cut -d' ' -f1 | grep -q 'ii'", \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;
            debug( $stderr ) if $stderr;

            # Upgrade server system tables
            # See #IP-1482 for further details.
            unless ( $rs ) {
                # Filter all "duplicate column", "duplicate key" and "unknown column"
                # errors as the command is designed to be idempotent.
                $rs = execute( "mysql_upgrade 2>&1 | egrep -v '^(1|\@had|ERROR (1054|1060|1061))'", \$stdout );
                error( sprintf( "Couldn't upgrade SQL server system tables: %s", $stdout )) if $rs;
                return $rs if $rs;
                debug( $stdout ) if $stdout;
            }
        }

        if ( !( $::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::mariadb'
            && version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '10.0' ) )
            && !( version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.6.6' ) )
        ) {
            return 0;
        }

        iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            my ( $dbh ) = @_;
            # Disable unwanted plugins (bc reasons)
            for my $plugin ( qw/ cracklib_password_check simple_password_check validate_password / ) {
                $dbh->do( "UNINSTALL PLUGIN $plugin" ) if $dbh->selectrow_hashref( "SELECT name FROM mysql.plugin WHERE name = '$plugin'" );
            }
        } );
        0;
    } catch {
        error( $_ );
        1;
    };
}

=item _setupMasterSqlUser( )

 Setup master SQL user
 
 Return 0 on success, other on failure

=cut

sub _setupMasterSqlUser
{
    my ( $self ) = @_;

    try {
        my $dbUser = ::setupGetQuestion( 'DATABASE_USER' );
        my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
        my $enc = iMSCP::Database::Encryption->getInstance();
        my $dbPass = decryptRijndaelCBC( $enc->getKey(), $enc->getIV(), ::setupGetQuestion( 'DATABASE_PASSWORD' ));

        if ( length $::imscpOldConfig{'DATABASE_USER'} && length $::imscpOldConfig{'DATABASE_USER_HOST'}
            && $dbUser . $dbUserHost ne $::imscpOldConfig{'DATABASE_USER'} . $::imscpOldConfig{'DATABASE_USER_HOST'}
        ) {
            $self->{'sqld'}->dropUser( $self->{'config'}->{'DATABASE_USER'}, $::imscpOldConfig{'DATABASE_USER_HOST'} );
        }

        $self->{'sqld'}->createUser( $dbUser, $dbUserHost, $dbPass );

        iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            $_->do( 'GRANT ALL PRIVILEGES ON *.* TO ?@? WITH GRANT OPTION', undef, $dbUser, $dbUserHost );
        } );
        0;
    } catch {
        error( $_ );
        1;
    };
}

=item _setupSecureInstallation( )

 Secure Installation
 
 Basically, this method do same job as the mysql_secure_installation script
  - Remove anonymous users
  - Remove remote sql root user (only for local server)
  - Remove test database if any
  - Reload privileges tables
  
  Return 0 on success, other on failure

=cut

sub _setupSecureInstallation
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->getInstance()->trigger( 'beforeSetupSecureSqlInstallation' );
        return $rs if $rs;

        {
            local $DATABASE = 'mysql';
            iMSCP::Database->factory()->getConnector()->run( fixup => sub {
                # Remove anonymous users
                $_->do( "DELETE FROM user WHERE User = ''" );

                # Remove test database if any
                $_->do( 'DROP DATABASE IF EXISTS `test`' );

                # Remove privileges on test database
                $_->do( "DELETE FROM db WHERE Db = 'test' OR Db = 'test\\_%'" );

                # Disallow remote root login
                if ( $::imscpConfig{'SQL_PACKAGE'} ne 'Servers::sqld::remote' ) {
                    $_->do( "DELETE FROM user WHERE User = 'root' AND Host NOT IN ('localhost', '127.0.0.1', '::1')" );
                }

                $_->do( 'FLUSH PRIVILEGES' );
            } );
        }

        $self->{'eventManager'}->getInstance()->trigger( 'afterSetupSecureSqlInstallation' );
    } catch {
        error( $_ );
        1;
    };
}

=item _setupDatabase( )

 Setup database
 
 Return 0 on success, other on failure

=cut

sub _setupDatabase
{
    my ( $self ) = @_;

    try {
        my $dbName = ::setupGetQuestion( 'DATABASE_NAME' );
        my $db = iMSCP::Database->factory();

        unless ( $db->isDatabase( $dbName ) && $db->databaseHasTables( $dbName, qw/ server_ips user_gui_props reseller_props / ) ) {
            my $rs = $self->{'eventManager'}->getInstance()->trigger( 'beforeSetupDatabase', \$dbName );
            return $rs if $rs;

            $db->getConnector()->run( fixup => sub {
                $_->do( "CREATE DATABASE IF NOT EXISTS @{ [ $_->quote_identifier( $dbName ) ] } CHARACTER SET utf8 COLLATE utf8_unicode_ci;" );
            } );
            $db->useDatabase( $dbName );

            $rs = ::setupImportSqlSchema( $db, "$::imscpConfig{'CONF_DIR'}/database/database.sql" );
            $rs ||= $self->{'eventManager'}->getInstance()->trigger( 'afterSetupDatabase', \$dbName );
            return $rs if $rs;
        }

        # In all cases, we process database update. This is important because sometime some developer forget to update the
        # database revision in the main database.sql file.
        my $rs = $self->{'eventManager'}->getInstance()->trigger( 'beforeSetupUpdateDatabase' );
        $rs ||= execute( "$::imscpConfig{'ROOT_DIR'}/engine/bin/imscp-update-db.php", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;

        $::imscpConfig{'DATABASE_LAST_OPTIMIZATION'} = time() unless $rs;

        $rs ||= $self->{'eventManager'}->getInstance()->trigger( 'afterSetupUpdateDatabase' );
    } catch {
        error( $_ );
        1;
    };
}

=item isInsideContainer( )

 Does the Mysql server is run inside an unprivileged VE (OpenVZ container)

 Return bool TRUE if the Mysql server is run inside an OpenVZ container, FALSE otherwise

=cut

sub isInsideContainer
{
    return 0 unless -f '/proc/user_beancounters';

    my $rs = execute( 'cat /proc/1/status | grep --color=never envID 2>/dev/null', \my $stdout );
    return FALSE if $rs || !length $stdout;
    return $1 > 0 ? TRUE : FALSE if $stdout =~ /envID:\s+(\d+)/;
    FALSE;
}

=item _tryDbConnect

 Try database connection

 Return TRUE if connection was successfull, FALSE otherwise and raise an error

=cut

sub _tryDbConnect
{
    my ( undef, $host, $port, $user, $pwd ) = @_;

    length $host or die( 'Missing or invalid $host parameter' );
    length $port or die( 'Missing or invalid $port parameter' );
    length $user or die( 'Missing or invalid $user parameter' );
    length $pwd or die( 'Missing or invalid $pwd parameter' );

    try {
        iMSCP::Database::MySQL->new(
            DATABASE_HOST     => idn_to_ascii( $host, 'utf-8' ),
            DATABASE_PORT     => $port,
            DATABASE_USER     => $user,
            DATABASE_PASSWORD => $pwd
        )->getConnector();
        TRUE;
    } catch {
        error( $_ );
        FALSE;
    };
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ( $self ) = @_;

    return 0 unless -f "$self->{'cfgDir'}/mysql.old.data";

    iMSCP::File->new( filename => "$self->{'cfgDir'}/mysql.old.data" )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
