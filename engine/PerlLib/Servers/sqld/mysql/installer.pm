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
use iMSCP::Crypt qw/ encryptRijndaelCBC decryptRijndaelCBC randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute escapeShell /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use iMSCP::TemplateParser 'process';
use iMSCP::Umask;
use Net::LibIDN qw/ idn_to_ascii idn_to_unicode /;
use Servers::sqld::mysql;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    if ( ::setupGetQuestion( 'SQL_SERVER' ) ne 'remote_server'
        && $::imscpOldConfig{'SQL_SERVER'} eq 'remote_server'
        && -f "$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/imscp.cnf"
    ) {
        # When switching from a remote SQL server to a local SQL server
        # we need remove the imscp.cnf file
        my $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/imscp.cnf"
        )->delFile();
        return $rs if $rs;
    }

    $events->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForSqlServerHostname( @_ ); },
            sub { $self->_dialogForSqlServerPort( @_ ); },
            sub { $self->_dialogForMasterSqlUsername( @_ ); },
            sub { $self->_dialogForMasterSqlUserPassword( @_ ); },
            sub { $self->_dialogForDatabaseName( @_ ); },
            sub { $self->_dialogForSqlUserHost( @_ ); },
            sub { $self->_dialogForCustomerSqlDatabasesAndUsersPrefixSuffix( @_ ); };
        0;
    } );
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
    $rs ||= $self->_updateCustomerSqlUsers();
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

    $self->{'sqld'} = Servers::sqld::mysql->getInstance();
    $self->{'events'} = $self->{'sqld'}->{'events'};
    $self->{'cfgDir'} = $self->{'sqld'}->{'cfgDir'};
    $self->{'config'} = $self->{'sqld'}->{'config'};
    $self;
}

=item _dialogForSqlServerHostname( )

 Dialog for the SQL server hostname

 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForSqlServerHostname
{
    my ( undef, $dialog ) = @_;

    my $isRemoteSqlSrv = ::setupGetQuestion( 'SQL_SERVER' ) eq 'remote_server';
    my $dbHost = ::setupGetQuestion(
        'DATABASE_HOST', $isRemoteSqlSrv ? '' : 'localhost'
    );

    # In case of a remote SQL server, none of 'localhost', '127.0.0.1', and
    # '::1' entries are valid.
    $dbHost = '' if $isRemoteSqlSrv && grep (
        $dbHost eq $_, qw/ localhost 127.0.0.1 ::1 /
    );

    if ( !grep ( $::reconfigure eq $_, qw/ sql servers all / )
        && isNotEmpty( $dbHost )
        && ( $dbHost eq 'localhost'
        || isValidHostname( $dbHost )
        || !isValidIpAddr( $dbHost )
    )
    ) {
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );

    do {
        ( $ret, $dbHost ) = $dialog->string( <<"EOF", $dbHost );
${msg}Please enter your@{ [ $isRemoteSqlSrv ? ' remote ' : ' local ' ] }SQL server hostname:
EOF
        if ( $ret != 30 ) {
            $dbHost =~ s/^\s+|\s+$//g;

            if ( $isRemoteSqlSrv &&
                grep ( $dbHost eq $_, qw/ localhost 127.0.0.1 ::1 /)
            ) {
                $msg = sprintf(
                    "\\Z1The '%s' hostname isn't valid when using a remote SQL server.\\Zn\n\n",
                    $dbHost
                );
            } elsif ( $dbHost ne 'localhost'
                && !isValidHostname( $dbHost )
                && !isValidIpAddr( $dbHost )
            ) {
                $msg = sprintf(
                    "\\Z1The '%s' hostname isn't valid.\\Zn\n\n", $dbHost
                );
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'DATABASE_HOST', idn_to_ascii( $dbHost, 'utf-8' ));
    0;
}

=item _dialogForSqlServerPort( )

 Dialog for the SQL server port

 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForSqlServerPort
{
    my ( undef, $dialog ) = @_;

    my $isRemoteSqlSrv = ::setupGetQuestion( 'SQL_SERVER' ) eq 'remote_server';
    my $dbPort = ::setupGetQuestion( 'DATABASE_PORT' );

    if ( !grep ( $::reconfigure eq $_, qw/ sql servers all / )
        && isNumber( $dbPort )
        && isNumberInRange( $dbPort, 1025, 65535 )
    ) {
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );

    do {
        ( $ret, $dbPort ) = $dialog->string( <<"EOF", length $dbPort ? $dbPort : 3306 );
${msg}Please enter your@{ [ $isRemoteSqlSrv ? ' remote ' : ' local ' ] }SQL server port:
EOF
        if ( $ret != 30 ) {
            $dbPort =~ s/^\s+|\s+$//g;

            if ( !isNumber( $dbPort )
                || !isNumberInRange( $dbPort, 1025, 65535 )
            ) {
                $msg = $LAST_VALIDATION_ERROR;
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'DATABASE_PORT', $dbPort );
    0;
}

=item _dialogForMasterSqlUsername( \%dialog )

 Dialog for the master SQL username

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForMasterSqlUsername
{
    my ( undef, $dialog ) = @_;

    my $dbUser = ::setupGetQuestion( 'DATABASE_USER', 'imscp_user' );

    if ( !grep ( $::reconfigure eq $_, qw/ sql servers all / )
        && isValidUsername( $dbUser )
        && isStringNotInList( $dbUser, qw/ debian-sys-maint mysql.user root / )
    ) {
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $dbUser ) = $dialog->string( <<"EOF", $dbUser );
${msg}Please enter a username for the master i-MSCP SQL user:
EOF
        if ( $ret != 30 ) {
            $dbUser =~ s/^\s+|\s+$//g;

            if ( !isValidUsername( $dbUser )
                || !isStringNotInList( $dbUser, qw/ debian-sys-maint mysql.user root / )
            ) {
                $msg = $LAST_VALIDATION_ERROR;
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'DATABASE_USER', $dbUser );
    0;
}

=item _dialogForMasterSqlUserPassword( \%dialog )

 Dialog for the master SQL user password

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForMasterSqlUserPassword
{
    my ( $self, $dialog ) = @_;

    my $dbHost = ::setupGetQuestion( 'DATABASE_HOST' );
    my $dbPort = ::setupGetQuestion( 'DATABASE_PORT' );
    my $dbUser = ::setupGetQuestion( 'DATABASE_USER' );
    my $dbPasswd = ::setupGetQuestion(
        'DATABASE_PASSWORD',
        iMSCP::Getopt->preseed ? randomStr( 16, iMSCP::Crypt::ALNUM ) : ''
    );

    if ( length $dbPasswd && !iMSCP::Getopt->preseed ) {
        $dbPasswd = decryptRijndaelCBC(
            $::imscpDBKey, $::imscpDBiv, $dbPasswd
        );
    }

    # If user didn't asked for reconfiguration, and if the currently set data
    # make us able to connect, we skip dialog.
    if ( !grep ( $::reconfigure eq $_, qw/ sql servers all / )
        && isValidPassword( $dbPasswd )
        && !$self->_tryDbConnect( $dbHost, $dbPort, $dbUser, $dbPasswd )
    ) {
        ::setupSetQuestion( 'DATABASE_PASSWORD', encryptRijndaelCBC(
            $::imscpDBKey, $::imscpDBiv, $dbPasswd
        ));
        return 20;
    }

    PASSWORD_DIALOG:
    $dbPasswd = isValidPassword( $dbPasswd ) ? $dbPasswd : '';
    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $dbPasswd ) = $dialog->string(
            <<"EOF", $dbPasswd || randomStr( 16, iMSCP::Crypt::ALNUM ));
${msg}Please enter a password for the master i-MSCP SQL user:
EOF
        if ( $ret != 30 ) {
            $dbPasswd =~ s/^\s+|\s+$//g;
            $msg = isValidPassword( $dbPasswd ) ? '' : $LAST_VALIDATION_ERROR;
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'DATABASE_PASSWORD', encryptRijndaelCBC(
        $::imscpDBKey, $::imscpDBiv, $dbPasswd
    ));

    # Ask for SQL root password if needed.
    $ret = $self->_dialogForSqlRootUser(
        $dialog, $dbHost, $dbPort, $dbUser, $dbPasswd
    );
    goto PASSWORD_DIALOG if $ret == 30;

    0;
}

=item _dialogForDatabaseName( \%dialog )

 Dialog for i-MSCP database name

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForDatabaseName
{
    my ( $self, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'DATABASE_NAME', 'imscp' );

    if ( !grep ( $::reconfigure eq $_, qw/ sql servers all /)
        && isValidDbName( $value )
        && ( !$self->_setupIsDatabase( $value )
        || $self->_setupIsImscpDatabase( $value )
        || $self->_setupIsEmptyDatabase( $value )
    )
    ) {
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );

    if ( iMSCP::Getopt->preseed ) {
        $msg = "\\Z1The '$value' database exists but doesn't looks like an i-MSCP database.\\Zn\n\n";
    }

    do {
        ( $ret, $value ) = $dialog->string( <<"EOF", $value );
${msg}Please enter a database name for i-MSCP:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;

            if ( !isValidDbName( $value ) ) {
                $msg = $LAST_VALIDATION_ERROR;
            } elsif ( $self->_setupIsDatabase( $value )
                && !$self->_setupIsImscpDatabase( $value )
                && $self->_setupIsEmptyDatabase( $value )
            ) {
                $msg = "\\Z1The '$value' database exists but doesn't looks like an i-MSCP database.\\Zn\n\n"
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    my $oldValue = ::setupGetQuestion( 'DATABASE_NAME' );
    if ( length $oldValue
        && $value ne $oldValue
        && $self->_setupIsImscpDatabase( $oldValue )
    ) {
        if ( $dialog->boolean( <<"EOF", TRUE ) > 0 ) {
A database '$oldValue' for i-MSCP already exists.

Are you sure you want to create the new '$value' database for i-MSCP?
Keep in mind that the new database will be free of any data.

\\Z4Note:\\Zn If the '$value' database already exists and looks like an i-MSCP database, data won't be overridden.
EOF
            goto &{_dialogForDatabaseName};
        }
    }

    ::setupSetQuestion( 'DATABASE_NAME', $value );
    0;
}

=item _dialogForSqlUserHost( \%dialog )

 Dialog for SQL user host

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForSqlUserHost
{
    my ( undef, $dialog ) = @_;

    my $isRemoteSqlSrv = $::imscpConfig{'SQL_SERVER'} eq 'remote_server';
    my $value = ::setupGetQuestion( 'DATABASE_USER_HOST', $isRemoteSqlSrv
        ? ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' ) : 'localhost'
    );

    # In case of a remote SQL server, none of 'localhost', '127.0.0.1',
    # and '::1' entries are valid.
    $value = '' if $isRemoteSqlSrv && grep (
        $value eq $_, qw/ localhost 127.0.0.1 ::1 /
    );

    if ( !grep ( $::reconfigure eq $_, qw/ sql servers all / )
        && isValidSqlUserHostname( $value )
    ) {
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string(
            <<"EOF", length $value ? idn_to_unicode( $value, 'utf-8' ) : ( $isRemoteSqlSrv ? ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' ) : 'localhost' ));
${msg}Please enter the hostname from which the SQL users created by i-MSCP can connect to the SQL server.

For a local SQL server, the value should be '\\Zblocalhost\\ZB' while for a remote SQL server, the value should be the server IP or hostname from which the users are connecting, generally the WAN IP.

You can also make use of a wildcard entry such as: '\\Zb192.168.1.%\\ZB'. In that case, and assuming that the SQL server is running on the same network, SQL users will be able to connect to the remote SQL server from any IP in the network '\\Zb192.168.1.0\\ZB'.

Note that the installer update existing SQL users automatically when the value of this parameter is changed. You must not forget to inform your users about that change, else, their PHP scripts won't longer be able to connect to the SQL server.
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;
            $msg = isValidSqlUserHostname( $value )
                ? '' : $LAST_VALIDATION_ERROR;
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion(
        'DATABASE_USER_HOST', idn_to_ascii( $value, 'utf-8' )
    );

    0;
}

=item _dialogForCustomerSqlDatabasesAndUsersPrefixSuffix( \%dialog )

 Dialog for customer SQL databases and users prefix/suffix

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForCustomerSqlDatabasesAndUsersPrefixSuffix
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'MYSQL_PREFIX' );

    if ( !grep ( $::reconfigure eq $_, qw/ sql servers all / )
        && grep ( $value eq $_, qw/ behind infront none /)
    ) {
        return 20;
    }

    my %choices = (
        None   => 'none',
        Prefix => 'infront',
        Suffix => 'behind'
    );

    ( my $ret, $value ) = $dialog->select(
        <<"EOF", \%choices, ( grep ( $value eq $_, qw/ behind infront / ) )[0] // 'none' );
Do you want enable the automatic addition of a prefix or suffix for customer SQL users and databases?

If you choose 'None', the choice will be left to customers.
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'MYSQL_PREFIX', $value );
    0;
}

=item _dialogForSqlRootUser( \%dialog, $dbHost, $dbPort, $dbUser, $dbPasswd )

 Dialog for SQL root user

 Param iMSCP::Dialog \%dialog
 Param string $dbHost SQL server hostname
 Param int $dbPort SQL server port
 Param $dbUser i-MSCP master SQL username
 Param $dbPasswd i-MSCP master SQL user password
 Return int 0 (Next), 20 (Skip), 30 (back)

=cut

sub _dialogForSqlRootUser
{
    my ( $self, $dialog, $dbHost, $dbPort, $dbUser, $dbPasswd ) = @_;

    # We first try to connect with the i-MSCP master SQL user account
    # credentials. If the connection succeed, we skip dialog as usage of SQL
    # root user isn't needed.
    return 20 if !$self->_tryDbConnect( $dbHost, $dbPort, $dbUser, $dbPasswd );

    my $isRemoteSqlSrv = ::setupGetQuestion( 'SQL_SERVER' ) eq 'remote_server';
    my $dbRootUser = ::setupGetQuestion( 'SQL_ROOT_USER' );
    my $dbRootPasswd = ::setupGetQuestion( 'SQL_ROOT_PASSWORD' );

    # We try to connect with the current SQL root user account credentials. If
    # the connection succeed, we skip dialog. This cover the preseeding feature
    # and unix_socket authentication.
    goto CONNECT_CHECK if TRUE;

    my ( $ret, $msg ) = ( 0, '' );

    USERNAME_DIALOG:
    do {
        ( $ret, $dbRootUser ) = $dialog->string(
            <<"EOF", length $dbRootUser ? $dbRootUser : 'root' );
Please enter the SQL root username:

This user must have full privileges on the@{ [ $isRemoteSqlSrv ? ' remote ' : ' local ' ] }SQL server.

The installer only make use of that user while installation.
EOF
        $dbRootUser =~ s/^\s+|\s+$//g if $ret != 30;
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    do {
        ( $ret, $dbRootPasswd ) = $dialog->password( <<"EOF" );
Please enter the SQL root user password:
EOF
        $dbRootPasswd =~ s/^\s+|\s+$//g if $ret != 30;
    } while $ret != 30 && length $msg;
    goto USERNAME_DIALOG if $ret == 30;

    CONNECT_CHECK:
    if ( my $connectError = $self->_tryDbConnect(
        idn_to_ascii( $dbHost, 'utf-8' ), $dbPort, $dbRootUser, $dbRootPasswd
    ) ) {
        chomp( $connectError );

        # Override default button label
        local $dialog->{'_opts'}->{
            $dialog->{'program'} eq 'dialog' ? 'ok-label' : 'ok-button'
        } = 'Ok';

        $ret = $dialog->error( <<"EOF" );
The i-MSCP installer can't connect to the@{ [ $isRemoteSqlSrv ? ' remote ' : ' local '] }SQL server using the following data:

\\Z4Hostname:\\Zn $dbHost
\\Z4Port    :\\Zn $dbPort
\\Z4Username:\\Zn $dbRootUser
\\Z4Password:\\Zn $dbRootPasswd

Error was: \\Z1$connectError\\Zn
EOF
        goto USERNAME_DIALOG;
    }

    ::setupSetQuestion( 'SQL_ROOT_USER', $dbUser );
    ::setupSetQuestion( 'SQL_ROOT_PASSWORD', $dbPasswd );
    0;
}

=item _setTypeAndVersion( )

 Set SQL server type and version

 Return 0 on success, other on failure

=cut

sub _setTypeAndVersion
{
    my ( $self ) = @_;

    local $@;
    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        my $row = $dbh->selectrow_hashref(
            'SELECT @@version, @@version_comment'
        ) or die(
            "Could't find SQL server type and version"
        );

        my $type = 'mysql';
        if ( index( lc $row->{'@@version'}, 'mariadb' ) != -1 ) {
            $type = 'mariadb';
        } elsif ( index( lc $row->{'@@version_comment'}, 'percona' ) != -1 ) {
            $type = 'percona';
        }

        my ( $version ) = $row->{'@@version'} =~ /^([0-9]+(?:\.[0-9]+){1,2})/;
        unless ( defined $version ) {
            error( "Couldn't find SQL server version" );
            return 1;
        }

        debug( sprintf( 'SQL server type set to: %s', $type ));
        $self->{'config'}->{'SQLD_TYPE'} = $type;

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
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeSqldBuildConf' );
    return $rs if $rs;

    # Make sure that the conf.d directory exists
    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => "$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d"
        )->make( {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $::imscpConfig{'ROOT_GROUP'},
            mode  => 0755
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Create the /etc/mysql/my.cnf file if missing
    unless ( -f "$self->{'config'}->{'SQLD_CONF_DIR'}/my.cnf" ) {
        $rs = $self->{'events'}->trigger(
            'onLoadTemplate', 'mysql', 'my.cnf', \my $cfgTpl, {}
        );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = "!includedir $self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n";
        } elsif ( $cfgTpl !~ m%^!includedir\s+$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n%m ) {
            $cfgTpl .= "!includedir $self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n";
        }

        my $file = iMSCP::File->new(
            filename => "$self->{'config'}->{'SQLD_CONF_DIR'}/my.cnf"
        );
        $file->set( $cfgTpl );

        $rs = $file->save();
        $rs ||= $file->owner(
            $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
        );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    $rs ||= $self->{'events'}->trigger(
        'onLoadTemplate', 'mysql', 'imscp.cnf', \my $cfgTpl, {}
    );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        return 1 unless defined(
            $cfgTpl = iMSCP::File->new(
                filename => "$self->{'cfgDir'}/imscp.cnf"
            )->get()
        );
    }

    $cfgTpl .= <<'EOF';
[mysqld]
performance_schema = 0
max_connections = 500
max_allowed_packet = 500M
EOF

    ( my $dbUser = ::setupGetQuestion( 'DATABASE_USER' ) ) =~ s/"/\\"/g;
    ( my $dbPasswd = decryptRijndaelCBC( $::imscpDBKey, $::imscpDBiv,
        ::setupGetQuestion( 'DATABASE_PASSWORD' )
    ) ) =~ s/"/\\"/g;
    my $variables = {
        DATABASE_HOST     => ::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT     => ::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER     => $dbUser,
        DATABASE_PASSWORD => $dbPasswd,
        SQLD_SOCK_DIR     => $self->{'config'}->{'SQLD_SOCK_DIR'}
    };

    if ( version->parse( "$self->{'config'}->{'SQLD_VERSION'}" )
        >= version->parse( '5.5.0' )
    ) {
        my $innoDbUseNativeAIO = $self->_isInsideContainer() ? '0' : '1';
        $cfgTpl .= "innodb_use_native_aio = $innoDbUseNativeAIO\n";
    }

    # Fix For: The 'INFORMATION_SCHEMA.SESSION_VARIABLES' feature is disabled;
    # see the documentation for 'show_compatibility_56' (3167) - Occurs when
    # executing mysqldump with Percona server 5.7.x
    if ( $::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::percona'
        && version->parse( "$self->{'config'}->{'SQLD_VERSION'}" )
        >= version->parse( '5.7.6' )
    ) {
        $cfgTpl .= "show_compatibility_56 = 1\n";
    }

    # For backward compatibility - We will review this in later version
    # TODO Handle mariadb case when ready. See https://mariadb.atlassian.net/browse/MDEV-7597
    if ( version->parse( "$self->{'config'}->{'SQLD_VERSION'}" )
        >= version->parse( '5.7.4' )
        && $::imscpConfig{'SQL_PACKAGE'} ne 'Servers::sqld::mariadb'
    ) {
        $cfgTpl .= "default_password_lifetime = 0\n";
    }

    $cfgTpl .= "event_scheduler = DISABLED\n";
    $cfgTpl = process( $variables, $cfgTpl );

    local $UMASK = 027; # imscp.cnf file must not be created world-readable

    my $file = iMSCP::File->new(
        filename => "$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/imscp.cnf"
    );
    $file->set( $cfgTpl );

    $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'SQLD_GROUP'}
    );
    $rs ||= $file->mode( 0640 );
    $rs ||= $self->{'events'}->trigger( 'afterSqldBuildConf' );
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

    if ( iMSCP::ProgramFinder::find( 'dpkg' )
        && iMSCP::ProgramFinder::find( 'mysql_upgrade' )
    ) {
        my $rs = execute(
            '/usr/bin/dpkg -l mysql-community* percona-server-* | '
                . "cut -d' ' -f1 | grep -q 'ii'",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        debug( $stderr ) if $stderr;

        # Upgrade server system tables
        # See #IP-1482 for further details.
        unless ( $rs ) {
            # Filter all "duplicate column", "duplicate key" and "unknown column"
            # errors as the command is designed to be idempotent.
            $rs = execute(
                '/usr/bin/mysql_upgrade 2>&1 | '
                    . " egrep -v '^(1|\@had|ERROR (1054|1060|1061))'",
                \$stdout
            );
            error( sprintf(
                "Couldn't upgrade SQL server system tables: %s", $stdout
            )) if $rs;
            return $rs if $rs;
            debug( $stdout ) if $stdout;
        }
    }

    if ( ( $::imscpConfig{'SQL_PACKAGE'} ne 'Servers::sqld::mariadb'
        || version->parse( "$self->{'config'}->{'SQLD_VERSION'}" )
        < version->parse( '10.0' ) )
        || version->parse( "$self->{'config'}->{'SQLD_VERSION'}" )
        < version->parse( '5.6.6' )
    ) {
        return 0;
    }

    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();

        # Disable unwanted plugins (bc reasons)
        for my $plugin ( qw/
            cracklib_password_check simple_password_check validate_password
        / ) {
            next unless $dbh->selectrow_hashref(
                "SELECT `name` FROM `mysql`.`plugin` WHERE `name` = '$plugin'"
            );
            $dbh->do( "UNINSTALL PLUGIN $plugin" )
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
    my ( $self ) = @_;

    my $dbUser = ::setupGetQuestion( 'DATABASE_USER' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPasswd = decryptRijndaelCBC(
        $::imscpDBKey, $::imscpDBiv, ::setupGetQuestion( 'DATABASE_PASSWORD' )
    );
    my $oldDbUser = $::imscpOldConfig{'DATABASE_USER'};

    # Remove old user if any
    for my $user ( $oldDbUser, $dbUser ) {
        next unless length $user;

        for my $host ( $dbUserHost, $oldDbUserHost ) {
            next unless length $host;
            $self->{'sqld'}->dropUser( $user, $host );
        }
    }

    # Create user
    $self->{'sqld'}->createUser( $dbUser, $dbUserHost, $dbPasswd );

    # Grant all privileges to that user with GRANT option
    local $@;
    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        $dbh->do(
            'GRANT ALL PRIVILEGES ON *.* TO ?@? WITH GRANT OPTION',
            undef,
            $dbUser,
            $dbUserHost
        );
    };
    if ( $@ ) {
        error( sprintf(
            "Couldn't grant privileges to the master i-MSCP SQL user: %s", $@
        ));
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
    my ( $self ) = @_;

    my $rs = $self->{'events'}->getInstance()->trigger(
        'beforeSetupSecureSqlInstallation'
    );
    return $rs if $rs;

    eval {
        my $db = iMSCP::Database->factory();
        my $oldDbName = $db->useDatabase( 'mysql' );
        my $dbh = $db->getRawDb();

        # Remove anonymous users
        $dbh->do( "DELETE FROM `user` WHERE `User` = ''" );
        # Remove test database if any
        $dbh->do( 'DROP DATABASE IF EXISTS `test`' );
        # Remove privileges on test database
        $dbh->do( "DELETE FROM `db` WHERE `Db` = 'test' OR `Db` = 'test\\_%'" );

        # Disallow remote root login
        if ( $::imscpConfig{'SQL_SERVER'} ne 'remote_server' ) {
            $dbh->do(
                "
                    DELETE FROM `user`
                    WHERE `User` = 'root'
                    AND `Host` NOT IN ('localhost', '127.0.0.1', '::1')
                "
            );
        }

        $dbh->do( 'FLUSH PRIVILEGES' );
        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->getInstance()->trigger(
        'afterSetupSecureSqlInstallation'
    );
}

=item _setupDatabase( )

 Setup database
 
 Return 0 on success, other on failure

=cut

sub _setupDatabase
{
    my ( $self ) = @_;

    my $dbName = ::setupGetQuestion( 'DATABASE_NAME' );

    unless ( $self->_setupIsImscpDatabase( $dbName ) ) {
        my $rs = $self->{'events'}->getInstance()->trigger(
            'beforeSetupDatabase', \$dbName
        );
        return $rs if $rs;

        my $db = iMSCP::Database->factory();

        eval {
            my $dbh = $db->getRawDb();
            my $qdbName = $dbh->quote_identifier( $dbName );
            $dbh->do(
                "
                    CREATE DATABASE $qdbName IF NOT EXISTS
                    CHARACTER SET utf8 COLLATE utf8_unicode_ci;
                "
            );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        $rs = execute(
            '/usr/bin/mysql ' . escapeShell( $dbName ) . ' < '
                . "$::imscpConfig{'CONF_DIR'}/database/database.sql",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if length $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;

        # Connect to the newly created database
        $db->set( 'DATABASE_NAME', $dbName );
        if ( $db->connect() ) {
            error( "Couldn't connect to SQL server" );
            return 1;
        }

        $self->{'events'}->getInstance()->trigger(
            'afterSetupDatabase', \$dbName
        );
    }

    # In all cases, we process database update. This is important because
    # sometime some developer forget to update the  database revision in
    # the main database.sql file.
    my $rs = $self->{'events'}->getInstance()->trigger(
        'beforeSetupUpdateDatabase'
    );
    $rs ||= execute(
        [
            '/usr/bin/php',
            '-d', 'date.timezone=UTC',
            "$::imscpConfig{'ROOT_DIR'}/engine/setup/imscp-update-db.php"
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    $::imscpConfig{'DATABASE_LAST_OPTIMIZATION'} = time() unless $rs;

    $rs ||= $self->{'events'}->getInstance()->trigger(
        'afterSetupUpdateDatabase'
    );
}

=item _updateCustomerSqlUsers

 Update host part of customer SQL users

 Return int 0 on success, 1 on failure

=cut

sub _updateCustomerSqlUsers
{
    my $newHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldHost = $::imscpOldConfig{'DATABASE_USER_HOST'};

    return 0 unless length $oldHost && $newHost ne $oldHost;

    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();

        # Get all customer SQL users
        my $rows = $dbh->selectall_hashref(
            'SELECT `sqlu_id`, `sqlu_name`, `sqlu_host` FROM `sql_user`',
            'sqlu_id'
        );

        for my $sqluID ( keys %{ $rows } ) {
            # Skip update of the SQL user hostname which value was other than
            # the old default hostname. The frontEnd allows a custom host for
            # SQL users.
            next unless $rows->{$sqluID}->{'sqlu_host'} eq $oldHost;

            eval {
                $dbh->begin_work();

                # Update hostname in i-MSCP sql_user database table
                $dbh->do(
                    'UPDATE `sql_user` SET `sqlu_host` = ? WHERE `sqlu_id` = ?',
                    undef, $newHost, $sqluID
                );
                # Update hostname in SQL server privilege tables
                $dbh->do(
                    'UPDATE `mysql`.`user` SET `Host` = ? WHERE `User` = ?',
                    undef, $newHost, $rows->{$sqluID}->{'sqlu_name'}
                );
                $dbh->do(
                    'UPDATE `mysql`.`db` SET `Host` = ? WHERE `User` = ?',
                    undef, $newHost, $rows->{$sqluID}->{'sqlu_name'}
                );
                # Not needed as customer SQL users privileges are set on a per
                # database basis
                #$dbh->do(
                #    'UPDATE `mysql`.`tables_priv` SET `Host` = ? WHERE `User` = ?',
                #    undef, $newHost, $rows->{$sqluID}->{'sqlu_name'}
                #);
                # Not needed as customer SQL users privileges are set on a per
                # database basis
                #$dbh->do(
                #    'UPDATE `mysql`.`columns_priv` SET `Host` = ? WHERE `User` = ?',
                #    undef, $newHost, $rows->{$sqluID}->{'sqlu_name'}
                #);

                $dbh->commit();
            };
            if ( $@ ) {
                $dbh->rollback();
                die;
            }
        }

        # Flush privileges to make changes effective
        $dbh->do( 'FLUSH PRIVILEGES' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _isInsideContainer( )

 Is the SQL server running inside an unprivileged VE (OpenVZ container)

 Return bool TRUE if the SQL server is run inside an OpenVZ container,
             FALSE otherwise
=cut

sub _isInsideContainer
{
    return 0 unless -f '/proc/user_beancounters';

    my $rs = execute(
        '/usr/bin/cat /proc/1/status | grep --color=never envID',
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
    debug( $stderr ) if $rs && length $stderr;

    return $stdout =~ /envID:\s+(\d+)/ && $1 > 0 ? TRUE : FALSE;
}

=item _setupIsDatabase( $dbName )

 Is the given database an existent database?

 Param string $dbName Database name
 Return bool TRUE if database exists, FALSE otherwise, die on failure
=cut

sub _setupIsDatabase
{
    my ( undef, $dbName ) = @_;

    ref \$dbName eq 'SCALAR' && length $dbName or die(
        '$dbName parameter is missing or invalid'
    );

    return FALSE unless iMSCP::Database
        ->factory()
        ->getRawDb()
        ->selectrow_hashref( 'SHOW DATABASES LIKE ?', undef, $dbName );

    TRUE;
}

=item _setupIsImscpDatabase( $dbName )

 Is the given database an i-MSCP database?

 Param string $dbName Database name
 Return bool TRUE if database exists and look like an i-MSCP database, FALSE
             otherwise, die on failure
=cut

sub _setupIsImscpDatabase
{
    my ( $self, $dbName ) = @_;

    ref \$dbName eq 'SCALAR' && length $dbName or die(
        '$dbName parameter is missing or invalid'
    );

    return FALSE unless $self->_setupIsDatabase( $dbName );

    my $tables = iMSCP::Database->factory()->getDbTables( $dbName );
    return FALSE unless @{ $tables };

    for my $table ( qw/ server_ips user_gui_props reseller_props / ) {
        return FALSE unless grep ( $_ eq $table, @{ $tables } );
    }

    TRUE;
}

=item _setupIsEmptyDatabase( $dbName )

 Is the given database an empty database?

 Param string $dbName Database name
 Return bool TRUE if database exists and is empty, FALSE
             otherwise, die on failure
=cut

sub _setupIsEmptyDatabase
{
    my ( $self, $dbName ) = @_;

    ref \$dbName eq 'SCALAR' && length $dbName or die(
        '$dbName parameter is missing or invalid'
    );

    return FALSE unless $self->_setupIsDatabase( $dbName );

    my $tables = iMSCP::Database->factory()->getDbTables( $dbName );

    return @{ $tables } ? FALSE : TRUE;
}

=item _tryDbConnect( [ $hostname = 'localhost' [, $port = 3306 [, $username = undef [, $password = undef ] ] ] ] )

 Try database connection

 Param string $hostname SQL server hostname
 Param int $port SQL server port
 Param string $username SQL username
 Param string $password SQL user password
 Return int 0 on success, string on failure

=cut

sub _tryDbConnect
{
    my ( undef, $hostname, $port, $username, $password ) = @_;
    $hostname //= 'localhost';
    $port //= '3306';

    ref \$hostname eq 'SCALAR' && length $hostname or die(
        "The '\$hostname' parameter is not defined or is empty."
    );
    ref \$port eq 'SCALAR' && $port =~ /^\d+$/ or die(
        "The '\$port' parameter is not defined or is empty."
    );
    ref \$username eq 'SCALAR' or die(
        "The '\$username' parameter isn't a string."
    );
    ref \$password eq 'SCALAR' or die(
        "The '\$password' parameter isn't a string"
    );

    my $db = iMSCP::Database->factory();
    $db->set( 'DATABASE_HOST', idn_to_ascii( $hostname, 'utf-8' ));
    $db->set( 'DATABASE_PORT', $port );
    $db->set( 'DATABASE_USER', $username );
    $db->set( 'DATABASE_PASSWORD', $password );
    $db->connect();
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ( $self ) = @_;

    return 0 unless -f "$self->{'cfgDir'}/mysql.old.data";

    iMSCP::File->new(
        filename => "$self->{'cfgDir'}/mysql.old.data"
    )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
