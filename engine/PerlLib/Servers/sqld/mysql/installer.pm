=head1 NAME

 Servers::sqld::mysql::installer - i-MSCP MySQL server installer implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::sqld::mysql::installer;

use strict;
use warnings;
use File::Temp;
use iMSCP::Crypt qw/ encryptRijndaelCBC decryptRijndaelCBC randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser qw/ processByRef /;
use Net::LibIDN qw/ idn_to_ascii idn_to_unicode /;
use Servers::sqld::mysql;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]},
                sub { $self->masterSqlUserDialog( @_ ) },
                sub { $self->sqlUserHostDialog( @_ ) },
                sub { $self->databaseNameDialog( @_ ) },
                sub { $self->databasePrefixDialog( @_ ) };
            0;
        },
    );
}

=item masterSqlUserDialog( \%dialog )

 Ask for i-MSCP master SQL user

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub masterSqlUserDialog
{
    my ($self, $dialog) = @_;

    my $rs = 0;
    $rs = $self->_askSqlRootUser( $dialog ) if iMSCP::Getopt->preseed;
    return $rs if $rs;

    my $hostname = main::setupGetQuestion( 'DATABASE_HOST' );
    my $port = main::setupGetQuestion( 'DATABASE_PORT' );
    my $user = main::setupGetQuestion( 'DATABASE_USER', iMSCP::Getopt->preseed ? 'imscp_user' : '' );
    $user = 'imscp_user' if lc( $user ) eq 'root'; # Handle upgrade case
    my $pwd = main::setupGetQuestion( 'DATABASE_PASSWORD', iMSCP::Getopt->preseed ? randomStr( 16, iMSCP::Crypt::ALNUM ) : '' );

    if ( $pwd ne '' && !iMSCP::Getopt->preseed ) {
        $pwd = decryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, $pwd );
        $pwd = '' unless isValidPassword( $pwd ); # Handle case of badly decrypted password
    }

    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    if ( $main::reconfigure =~ /(?:sql|servers|all|forced)$/
        || !isNotEmpty( $hostname )
        || !isNotEmpty( $port )
        || !isNotEmpty( $user )
        || !isStringNotInList( lc $user, 'debian-sys-maint', 'imscp_srv_user', 'mysql.user', 'root', 'vlogger_user' )
        || !isNotEmpty( $pwd )
        || ( !iMSCP::Getopt->preseed && $self->_tryDbConnect( $hostname, $port, $user, $pwd ) )
    ) {
        $rs = $self->_askSqlRootUser( $dialog ) unless iMSCP::Getopt->preseed;
        return $rs unless $rs < 30;

        $iMSCP::Dialog::InputValidation::lastValidationError = '';

        do {
            if ( $user eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $user = 'imscp_user';
            }

            ( $rs, $user ) = $dialog->inputbox( <<"EOF", $user );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a username for the i-MSCP master SQL user (leave empty for default):
\\Z \\Zn
EOF
        } while $rs < 30
            && ( !isValidUsername( $user )
            || !isStringNotInList( lc $user, 'debian-sys-maint', 'imscp_srv_user', 'mysql.user', 'root',
            'vlogger_user' )
        );

        return $rs unless $rs < 30;

        do {
            if ( $pwd eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $pwd = randomStr( 16, iMSCP::Crypt::ALNUM );
            }

            ( $rs, $pwd ) = $dialog->inputbox( <<"EOF", $pwd );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a password for the master i-MSCP SQL user (leave empty for autogeneration):
\\Z \\Zn
EOF
        } while $rs < 30
            && !isValidPassword( $pwd );

        return $rs unless $rs < 30;
    }

    main::setupSetQuestion( 'DATABASE_USER', $user );
    main::setupSetQuestion( 'DATABASE_PASSWORD', encryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, $pwd ));
    0;
}

=item sqlUserHostDialog( \%dialog )

 Ask for SQL user hostname

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub sqlUserHostDialog
{
    my (undef, $dialog) = @_;

    if ( $main::imscpConfig{'SQL_PACKAGE'} ne 'Servers::sqld::remote' ) {
        main::setupSetQuestion( 'DATABASE_USER_HOST', 'localhost' );
        return 0;
    }

    my $hostname = main::setupGetQuestion( 'DATABASE_USER_HOST', main::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' ));

    if ( grep($hostname eq $_, ( 'localhost', '127.0.0.1', '::1' )) ) {
        $hostname = main::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );
    }

    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    if ( $main::reconfigure =~ /^(?:sql|servers|all|forced)$/
        || ( $hostname ne '%'
        && !isValidHostname( $hostname )
        && !isValidIpAddr( $hostname, qr/^(?:PUBLIC|GLOBAL-UNICAST)$/ ) )
    ) {
        my $rs = 0;

        do {
            ( $rs, $hostname ) = $dialog->inputbox( <<"EOF", idn_to_unicode( $hostname, 'utf-8' ) // '' );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter the host from which SQL users created by i-MSCP must be allowed to connect:
\\Z \\Zn
EOF
        } while $rs < 30
            && ( $hostname ne '%'
            && !isValidHostname( $hostname )
            && !isValidIpAddr( $hostname, qr/^(?:PUBLIC|GLOBAL-UNICAST)$/ )
        );

        return unless $rs < 30;
    }

    main::setupSetQuestion( 'DATABASE_USER_HOST', idn_to_ascii( $hostname, 'utf-8' ));
    0;
}

=item databaseNameDialog( \%dialog )

 Ask for i-MSCP database name

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub databaseNameDialog
{
    my ($self, $dialog) = @_;

    my $dbName = main::setupGetQuestion( 'DATABASE_NAME', iMSCP::Getopt->preseed ? 'imscp' : '' );

    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    if ( $main::reconfigure =~ /^(?:sql|servers|all|forced)$/
        || ( !$self->_setupIsImscpDb( $dbName ) && !iMSCP::Getopt->preseed )
    ) {
        my $rs = 0;

        do {
            if ( $dbName eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $dbName = 'imscp';
            }

            ( $rs, $dbName ) = $dialog->inputbox( <<"EOF", $dbName );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a database name for i-MSCP:
\\Z \\Zn
EOF
            if ( isValidDbName( $dbName ) ) {
                my $db = iMSCP::Database->factory();
                eval { $db->useDatabase( $dbName ); };
                if ( !$@ && !$self->_setupIsImscpDb( $dbName ) ) {
                    $iMSCP::Dialog::InputValidation::lastValidationError = <<"EOF";
\\Z1Database '$dbName' exists but doesn't look like an i-MSCP database.\\Zn
EOF
                }
            }
        } while $rs < 30 && $iMSCP::Dialog::InputValidation::lastValidationError;

        return $rs unless $rs < 30;

        my $oldDbName = main::setupGetQuestion( 'DATABASE_NAME' );

        if ( $oldDbName
            && $dbName ne $oldDbName
            && $self->setupIsImscpDb( $oldDbName )
        ) {
            if ( $dialog->yesno( <<"EOF", 1 ) ) {
A database '$main::imscpConfig{'DATABASE_NAME'}' for i-MSCP already exists.

Are you sure you want to create a new database for i-MSCP?
Keep in mind that the new database will be free of any reseller and customer data.

\\Z4Note:\\Zn If the database you want to create already exists, nothing will happen.
EOF
                goto &{databaseNameDialog};
            }
        }
    }

    main::setupSetQuestion( 'DATABASE_NAME', $dbName );
    0;
}

=item databasePrefixDialog( \%dialog )

 Ask for database prefix

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub databasePrefixDialog
{
    my (undef, $dialog) = @_;

    my $value = main::setupGetQuestion( 'MYSQL_PREFIX', iMSCP::Getopt->preseed ? 'none' : '' );
    my %choices = ( 'behind', 'Behind', 'infront', 'Infront', 'none', 'None' );

    if ( isStringInList( $main::reconfigure, 'sql', 'servers', 'all', 'forced' )
        || !isStringInList( $value, keys %choices )
    ) {
        ( my $rs, $value ) = $dialog->radiolist( <<"EOF", \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'none' );
\\Z4\\Zb\\ZuMySQL Database Prefix/Suffix\\Zn

Do you want to use a prefix or suffix for customer's SQL databases?

\\Z4Infront:\\Zn A numeric prefix such as '1_' will be added to each SQL user and database name.
 \\Z4Behind:\\Zn A numeric suffix such as '_1' will be added to each SQL user and database name.
   \\Z4None\\Zn: Choice will be let to customer.
\\Z \\Zn
EOF
        return $rs unless $rs < 30;
    }

    main::setupSetQuestion( 'MYSQL_PREFIX', $value );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->_setType();
    $rs ||= $self->_setVersion();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_updateServerConfig();
    $rs ||= $self->_setupMasterSqlUser();
    $rs ||= $self->_setupSecureInstallation();
    $rs ||= $self->_setupDatbase();
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
    my ($self) = @_;

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
    my ($self, $dialog) = @_;

    my $hostname = main::setupGetQuestion(
        'DATABASE_HOST', $main::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::remote' ? '' : 'localhost'
    );

    if ( $main::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::remote'
        && grep { $hostname eq $_ } ( 'localhost', '127.0.0.1', '::1' )
    ) {
        $hostname = '';
    }

    my $port = main::setupGetQuestion( 'DATABASE_PORT', 3306 );
    my $user = main::setupGetQuestion( 'SQL_ROOT_USER', 'root' );
    my $pwd = main::setupGetQuestion( 'SQL_ROOT_PASSWORD' );

    if ( $hostname eq 'localhost' ) {
        for( 'localhost', '127.0.0.1' ) {
            next if $self->_tryDbConnect( $_, $port, $user, $pwd );
            main::setupSetQuestion( 'DATABASE_HOST', $_ );
            main::setupSetQuestion( 'DATABASE_PORT', $port );
            main::setupSetQuestion( 'SQL_ROOT_USER', $user );
            main::setupSetQuestion( 'SQL_ROOT_PASSWORD', $pwd );
            return 0;
        }
    }

    my $rs = 0;
    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    do {
        ( $rs, $hostname ) = $dialog->inputbox( <<"EOF", $hostname );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter your SQL server hostname or IP address:
\\Z \\Zn
EOF
    } while $rs < 30 && ( $hostname ne 'localhost'
        && !isValidHostname( $hostname )
        && !isValidIpAddr( $hostname )
    );

    main::setupSetQuestion( 'DATABASE_HOST', idn_to_ascii( $hostname, 'utf-8' ) // '' );
    return $rs if $rs >= 30;

    do {
        ( $rs, $port ) = $dialog->inputbox( <<"EOF", $port );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter your SQL server port:
\\Z \\Zn
EOF
    } while $rs < 30
        && !isNumber( $port )
        || !isNumberInRange( $port, 1025, 65535 );

    main::setupSetQuestion( 'DATABASE_PORT', $port );
    return $rs if $rs >= 30;

    do {
        ( $rs, $user ) = $dialog->inputbox( <<"EOF", $user );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter your SQL root username:

Note that this user must have full privileges on the SQL server.
i-MSCP only uses that user while installation or reconfiguration.
\\Z \\Zn
EOF
    } while $rs < 30 && !isNotEmpty( $user );

    main::setupSetQuestion( 'SQL_ROOT_USER', $user );
    return $rs if $rs >= 30;

    do {
        ( $rs, $pwd ) = $dialog->passwordbox( <<"EOF" );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter your SQL root user password:
\\Z \\Zn
EOF
    } while $rs < 30 && !isNotEmpty( $pwd );

    main::setupSetQuestion( 'SQL_ROOT_PASSWORD', $pwd );
    return $rs if $rs >= 30;

    if ( my $connectError = $self->_tryDbConnect( $hostname, $port, $user, $pwd ) ) {
        chomp( $connectError );

        $rs = $dialog->msgbox( <<"EOF" );
\\Z1Connection to SQL server failed\\Zn

i-MSCP installer couldn't connect to SQL server using the following data:

\\Z4Host:\\Zn $hostname
\\Z4Port:\\Zn $port
\\Z4Username:\\Zn $user
\\Z4Password:\\Zn $pwd

Error was: \\Z1$connectError\\Zn
EOF
        goto &{_askSqlRootUser};
    }

    0;
}

=item _setType( )

 Set SQL server type

 Return 0 on success, other on failure

=cut

sub _setType
{
    my ($self) = @_;

    debug( sprintf( 'SQL server type set to: %s', 'mysql' ));
    $self->{'config'}->{'SQLD_TYPE'} = 'mysql';
    0;
}

=item _setVersion( )

 Set SQL server version

 Return 0 on success, other on failure

=cut

sub _setVersion
{
    my ($self) = @_;

    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();

        local $dbh->{'RaiseError'} = 1;
        my $row = $dbh->selectrow_hashref( 'SELECT @@version' ) or die( "Could't find SQL server version" );
        my ($version) = $row->{'@@version'} =~ /^([0-9]+(?:\.[0-9]+){1,2})/;
        unless ( defined $version ) {
            error( "Couldn't find SQL server version" );
            return 1;
        }

        debug( sprintf( 'SQL server version set to: %s', $version ));
        $self->{'config'}->{'SQLD_VERSION'} = $version;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _buildConf( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldBuildConf' );
    return $rs if $rs;

    my $rootUName = $main::imscpConfig{'ROOT_USER'};
    my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
    my $confDir = $self->{'config'}->{'SQLD_CONF_DIR'};

    # Make sure that the conf.d directory exists
    iMSCP::Dir->new( dirname => "$confDir/conf.d" )->make( {
            user  => $rootUName,
            group => $rootGName,
            mode  => 0755
        } );

    # Create the /etc/mysql/my.cnf file if missing
    unless ( -f "$confDir/my.cnf" ) {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'my.cnf', \ my $cfgTpl, {} );
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

    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'imscp.cnf', \ my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp.cnf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s", "$self->{'cfgDir'}/imscp.cnf" ));
            return 1;
        }
    }

    $cfgTpl .= <<'EOF';
[mysqld]
performance_schema = 0
max_connections = 500
max_allowed_packet = 500M
sql_mode =
EOF

    $cfgTpl .= "innodb_use_native_aio = @{[ $self->_isMysqldInsideCt() ? 0 : 1 ]}\n";

    # For backward compatibility - We will review this in later version
    if ( version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.7.4' ) ) {
        $cfgTpl .= "default_password_lifetime = 0\n";
    }

    $cfgTpl .= "event_scheduler = DISABLED\n";

    processByRef( { SQLD_SOCK_DIR => $self->{'config'}->{'SQLD_SOCK_DIR'} }, \$cfgTpl );

    my $file = iMSCP::File->new( filename => "$confDir/conf.d/imscp.cnf" );
    $file->set( $cfgTpl );

    $rs = $file->save();
    $rs ||= $file->owner( $rootUName, $rootGName );
    $rs ||= $file->mode( 0644 );
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
    my ($self) = @_;

    # Upgrade MySQL tables if necessary.

    # Need to ignore SIGHUP, as otherwise a SIGHUP can sometimes abort the upgrade
    # process in the middle.
    {
        local $SIG{'HUP'} = 'IGNORE';

        my $mysqlConffile = File::Temp->new();
        print $mysqlConffile <<"EOF";
[mysql_upgrade]
host = @{[ main::setupGetQuestion( 'DATABASE_HOST' ) ]}
port = @{[ main::setupGetQuestion( 'DATABASE_PORT' ) ]}
user = "@{ [ main::setupGetQuestion( 'DATABASE_USER' ) =~ s/"/\\"/gr ] }"
password = "@{ [ decryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, main::setupGetQuestion( 'DATABASE_PASSWORD' )) =~ s/"/\\"/gr ] }"
EOF
        $mysqlConffile->flush();

        # Filter all "duplicate column", "duplicate key" and "unknown column"
        # errors as the command is designed to be idempotent.
        my $rs = execute( "/usr/bin/mysql_upgrade --defaults-file=$mysqlConffile 2>&1 | egrep -v '^(1|\@had|ERROR (1054|1060|1061))'", \my $stdout );
        error( sprintf( "Couldn't upgrade SQL server system tables: %s", $stdout )) if $rs;
        return $rs if $rs;
        debug( $stdout ) if $stdout;
    }

    # Disable unwanted plugins

    return 0 unless version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.6.6' );

    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'};

        # Disable unwanted plugins (bc reasons)
        for ( qw/ cracklib_password_check simple_password_check validate_password / ) {
            $dbh->do( "UNINSTALL PLUGIN $_" ) if $dbh->selectrow_hashref( "SELECT name FROM mysql.plugin WHERE name = '$_'" );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _setupMasterSqlUser( )

 Setup master SQL user
 
 Return 0 on success, other on failure

=cut

sub _setupMasterSqlUser
{
    my ($self) = @_;

    my $user = main::setupGetQuestion( 'DATABASE_USER' );
    my $userHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $pwd = decryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, main::setupGetQuestion( 'DATABASE_PASSWORD' ));
    my $oldUser = $main::imscpOldConfig{'DATABASE_USER'};

    # Remove old user if any
    for my $sqlUser ( $oldUser, $user ) {
        next unless $sqlUser;
        for my $host( $userHost, $oldUserHost ) {
            next unless $host;
            $self->{'sqld'}->dropUser( $sqlUser, $host );
        }
    }

    # Create user
    $self->{'sqld'}->createUser( $user, $userHost, $pwd );

    # Grant all privileges to that user (including GRANT otpion)
    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'};
        $dbh->do( 'GRANT ALL PRIVILEGES ON *.* TO ?@? WITH GRANT OPTION', undef, $user, $userHost );
    };
    if ( $@ ) {
        error( sprintf( "Couldn't grant privileges to master i-MSCP SQL user: %s", $@ ));
        return 1;
    }

    0;
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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->getInstance()->trigger( 'beforeSetupSecureSqlInstallation' );
    return $rs if $rs;

    eval {
        my $db = iMSCP::Database->factory();
        my $oldDbName = $db->useDatabase( 'mysql' );

        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'};

        # Remove anonymous users
        $dbh->do( "DELETE FROM user WHERE User = ''" );
        # Remove test database if any
        $dbh->do( 'DROP DATABASE IF EXISTS `test`' );
        # Remove privileges on test database
        $dbh->do( "DELETE FROM db WHERE Db = 'test' OR Db = 'test\\_%'" );

        # Disallow remote root login
        if ( $main::imscpConfig{'SQL_PACKAGE'} ne 'Servers::sqld::remote' ) {
            $dbh->do( "DELETE FROM user WHERE User = 'root' AND Host NOT IN ('localhost', '127.0.0.1', '::1')" );
        }

        $dbh->do( 'FLUSH PRIVILEGES' );
        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->getInstance()->trigger( 'afterSetupSecureSqlInstallation' );
}

=item _setupDatbase( )

 Setup database
 
 Return 0 on success, other on failure

=cut

sub _setupDatbase
{
    my ($self) = @_;

    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );

    unless ( $self->_setupIsImscpDb( $dbName ) ) {
        my $rs = $self->{'eventManager'}->getInstance()->trigger( 'beforeSetupDatabase', \$dbName );
        return $rs if $rs;

        my $db = iMSCP::Database->factory();

        eval {
            my $dbh = $db->getRawDb();
            local $dbh->{'RaiseError'} = 1;
            my $qdbName = $dbh->quote_identifier( $dbName );
            $dbh->do( "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;" );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        $db->set( 'DATABASE_NAME', $dbName );

        if ( $db->connect() ) {
            error( "Couldn't connect to SQL server" );
            return 1;
        }

        $rs = main::setupImportSqlSchema( $db, "$main::imscpConfig{'CONF_DIR'}/database/database.sql" );
        $rs ||= $self->{'eventManager'}->getInstance()->trigger( 'afterSetupDatabase', \$dbName );
        return $rs if $rs;
    }

    # In all cases, we process database update. This is important because sometime some developer forget to update the
    # database revision in the main database.sql file.
    my $rs = $self->{'eventManager'}->getInstance()->trigger( 'beforeSetupUpdateDatabase' );
    $rs ||= execute( "/usr/bin/php7.1 -d date.timezone=UTC $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs ||= $self->{'eventManager'}->getInstance()->trigger( 'afterSetupUpdateDatabase' );
}

=item _isMysqldInsideCt( )

 Does the Mysql server is run inside an unprivileged VE (OpenVZ container)

 Return int 1 if the Mysql server is run inside an OpenVZ container, 0 otherwise

=cut

sub _isMysqldInsideCt
{
    return 0 unless -f '/proc/user_beancounters';

    my $rs = execute( 'cat /proc/1/status | grep --color=never envID', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $rs && $stderr;
    return $rs if $rs;

    if ( $stdout =~ /envID:\s+(\d+)/ ) {
        return ( $1 > 0 ) ? 1 : 0;
    }

    0;
}

=item _setupIsImscpDb( $dbName )

 Is the given database an i-MSCP database?

 Return bool TRUE if database exists and look like an i-MSCP database, FALSE otherwise, die on failure

=cut

sub _setupIsImscpDb
{
    my (undef, $dbName) = @_;

    return 0 unless defined $dbName && $dbName ne '';

    my $db = iMSCP::Database->factory();
    my $dbh = $db->getRawDb();

    local $dbh->{'RaiseError'} = 1;
    return 0 unless $dbh->selectrow_hashref( 'SHOW DATABASES LIKE ?', undef, $dbName );

    my $tables = $db->getDbTables( $dbName );
    ref $tables eq 'ARRAY' or die( $tables );

    for my $table( qw/ server_ips user_gui_props reseller_props / ) {
        return 0 unless grep( $_ eq $table, @{$tables} );
    }

    1;
}

=item _tryDbConnect

 Try database connection

 Return int 0 on success, other on failure
=cut

sub _tryDbConnect
{
    my (undef, $host, $port, $user, $pwd) = @_;

    defined $host or die( '$host parameter is not defined' );
    defined $port or die( '$port parameter is not defined' );
    defined $user or die( '$user parameter is not defined' );
    defined $pwd or die( '$pwd parameter is not defined' );

    my $db = iMSCP::Database->factory();
    $db->set( 'DATABASE_HOST', idn_to_ascii( $host, 'utf-8' ) // '' );
    $db->set( 'DATABASE_PORT', $port );
    $db->set( 'DATABASE_USER', $user );
    $db->set( 'DATABASE_PASSWORD', $pwd );
    $db->connect();
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldOldEngineCompatibility' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/mysql.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/mysql.old.data" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterSqldOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
