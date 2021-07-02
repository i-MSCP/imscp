=head1 NAME

 Servers::ftpd::vsftpd::installer - i-MSCP VsFTPd Server implementation

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

package Servers::ftpd::vsftpd::installer;

use strict;
use warnings;
use Cwd;
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Crypt qw/ ALPHA64 decryptRijndaelCBC encryptRijndaelCBC randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation qw/
    $LAST_VALIDATION_ERROR
    isValidNumberRange isNumberInRange
/;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Service;
use iMSCP::TemplateParser 'process';
use iMSCP::Umask '$UMASK';
use Servers::ftpd::vsftpd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP VsFTPd Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::events \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $em ) = @_;

    $em->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForPassivePortRange( @_ ); };
        0;
    } );
}

=item preinstall( )

 Pre-installation task

 Return int 0 on success, other on failure
 
=cut

sub preinstall
{
    my ( $self ) = @_;

    $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} = ::setupGetQuestion(
        'FTPD_PASSIVE_PORT_RANGE'
    );

    #$self->{'ftpd'}->stop();
    0;
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_setVersion();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfigFile();
    $rs ||= $self->_oldEngineCompatibility();
}

=item postinstall( )

 Post-installation tasks

 Return int 0 on success, die on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    local $@;
    eval { iMSCP::Service->getInstance()->enable(
        $self->{'config'}->{'FTPD_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [ sub { $self->{'ftpd'}->restart() }, 'VsFTPd server' ];
            0
        },
        4
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::ftpd::vsftpd::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'ftpd'} = Servers::ftpd::vsftpd->getInstance();
    $self->{'events'} = $self->{'ftpd'}->{'events'};
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'config'} = $self->{'ftpd'}->{'config'};
    $self;
}

=item _dialogForPassivePortRange( \%dialog )

 Dialog for passive port range

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForPassivePortRange
{
    my ( $self, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'FTPD_PASSIVE_PORT_RANGE',
        length $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'}
            ? $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'}
            : '32800 33800'
    );
    my ( $startOfRange, $endOfRange );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ ftpd servers all / )
        && length $value
        && isValidNumberRange( $value, \$startOfRange, \$endOfRange )
        && isNumberInRange( $startOfRange, 32768, 60999 )
        && isNumberInRange( $endOfRange, $startOfRange, 60999 )
    ) {
        ::setupSetQuestion( 'FTPD_PASSIVE_PORT_RANGE', $value );
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string( <<"EOF", length $value ? $value : '32800 33800' );
${msg}Please choose the passive TCP port range for the FTP server.

If the FTP server is behind a NAT, you \\ZbMUST\\Zb not forget to forward these ports.
EOF
        if ( $ret != 30 ) {
            if ( !isValidNumberRange( $value, \$startOfRange, \$endOfRange )
                || !isNumberInRange( $startOfRange, 32768, 60999 )
                || !isNumberInRange( $endOfRange, $startOfRange, 60999 )
            ) {
                $msg = $LAST_VALIDATION_ERROR;
            } else {
                $msg = ''
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'FTPD_PASSIVE_PORT_RANGE', "$startOfRange $endOfRange" );
    0;
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ( $self ) = @_;

    # Version is print through STDIN (see: strace vsftpd -v)
    my $rs = execute( 'vsftpd -v 0>&1', \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m%([\d.]+)% ) {
        error( "Couldn't find VsFTPd version from 'vsftpd -v 0>&1' command output." );
        return 1;
    }

    $self->{'config'}->{'VSFTPD_VERSION'} = $1;
    debug( sprintf( 'VsFTPd version set to: %s', $1 ));
    0;
}

=item _setupDatabase( )

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my ( $self ) = @_;

    my $rs = eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        my %config = @{ $dbh->selectcol_arrayref(
            "
                SELECT `name`, `value`
                FROM `config`
                WHERE `name` LIKE 'VSFTPD_SQL_%'
            ",
            { Columns => [ 1, 2 ] }
        ) };

        if ( length $config{'VSFTPD_SQL_USER'} ) {
            $config{'VSFTPD_SQL_USER'} = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $config{'VSFTPD_SQL_USER'}
            );
        } else {
            $config{'PROFTPD_SQL_USER'} = 'vsftpd_' . randomStr( 9, ALPHA64 );
        }

        if ( length $config{'VSFTPD_SQL_USER_PASSWD'} ) {
            $config{'VSFTPD_SQL_USER_PASSWD'} = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $config{'VSFTPD_SQL_USER_PASSWD'}
            );
        } else {
            $config{'VSFTPD_SQL_USER_PASSWD'} = randomStr( 16, ALPHA64 );
        }

        ( $self->{'_vsftpd_sql_user'}, $self->{'_vsftpd_sql_user_passwd'} ) = (
            $config{'VSFTPD_SQL_USER'}, $config{'VSFTPD_SQL_USER_PASSWD'}
        );

        $dbh->do(
            '
                INSERT INTO `config` (`name`,`value`)
                VALUES (?,?),(?,?)
                ON DUPLICATE KEY UPDATE `name` = `name`
            ',
            undef,
            'VSFTPD_SQL_USER',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'VSFTPD_SQL_USER'}
            ),
            'VSFTPD_SQL_USER_PASSWD',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'VSFTPD_SQL_USER_PASSWD'}
            )
        );

        my $sqlServer = Servers::sqld->factory();

        for my $host (
            $::imscpOldConfig{'DATABASE_USER_HOST'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' )
        ) {
            next unless length $host;
            for my $user (
                $config{'VSFTPD_SQL_USER'},
                $self->{'ftpd'}->{'oldConfig'}->{'DATABASE_USER'} # Transitional
            ) {
                next unless length $user;
                $sqlServer->dropUser( $user, $host );
            }
        }

        $sqlServer->createUser(
            $config{'VSFTPD_SQL_USER'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' ),
            $config{'VSFTPD_SQL_USER_PASSWD'},
        );

        for my $table ( 'ftp_users', 'ftp_group' ) {
            $dbh->do(
                "
                    GRANT SELECT
                    ON `@{ [ ::setupGetQuestion( 'DATABASE_NAME' ) ] }`.`$table`
                    TO ?\@?
                ",
                undef,
                $config{'VSFTPD_SQL_USER'},
                ::setupGetQuestion( 'DATABASE_USER_HOST' )
            );
        }

        for my $table ( 'quotalimits', 'quotatallies' ) {
            $dbh->do(
                "
                    GRANT SELECT, INSERT, UPDATE
                    ON `@{ [ ::setupGetQuestion( 'DATABASE_NAME' ) ] }`.`$table`
                    TO ?\@?
                ",
                undef,
                $config{'VSFTPD_SQL_USER'},
                ::setupGetQuestion( 'DATABASE_USER_HOST' )
            );
        }

        0;
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item _buildConfigFile( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
    my ( $self ) = @_;

    # Make sure to start with clean user configuration directory
    unlink glob "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/*";

    my ( $passvMinPort, $passvMaxPort ) = split(
        /\s+/, $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'}
    );
    my $data = {
        IPV4_ONLY              => ::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'NO' : 'YES',
        IPV6_SUPPORT           => ::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'YES' : 'NO',
        DATABASE_NAME          => ::setupGetQuestion( 'DATABASE_NAME' ),
        DATABASE_HOST          => ::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT          => ::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER          => $self->{'_vsftpd_sql_user'},
        DATABASE_PASS          => $self->{'_vsftpd_sql_user_passwd'},
        FTPD_BANNER            => $self->{'config'}->{'FTPD_BANNER'},
        FRONTEND_USER_SYS_NAME => $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
        PASSV_ENABLE           => $self->{'config'}->{'PASSV_ENABLE'},
        PASSV_MIN_PORT         => $passvMinPort,
        PASSV_MAX_PORT         => $passvMaxPort,
        FTP_MAX_CLIENTS        => $self->{'config'}->{'FTP_MAX_CLIENTS'},
        MAX_PER_IP             => $self->{'config'}->{'MAX_PER_IP'},
        LOCAL_MAX_RATE         => $self->{'config'}->{'LOCAL_MAX_RATE'},
        USER_WEB_DIR           => $::imscpConfig{'USER_WEB_DIR'},
        FTPD_USER_CONF_DIR     => $self->{'config'}->{'FTPD_USER_CONF_DIR'}
    };

    # vsftpd main configuration file

    my $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    $rs ||= $self->{'events'}->trigger(
        'onLoadTemplate', 'vsftpd', 'vsftpd.conf', \my $cfgTpl, $data
    );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        return 1 unless defined(
            $cfgTpl = iMSCP::File->new(
                filename => "$self->{'cfgDir'}/vsftpd.conf"
            )->get()
        );
    }

    $rs = $self->{'events'}->trigger(
        'beforeFtpdBuildConf', \$cfgTpl, 'vsftpd.conf'
    );
    return $rs if $rs;

    if ( $self->_isVsFTPdInsideCt() ) {
        $cfgTpl .= <<'EOF';

# VsFTPd run inside unprivileged VE
# See http://youtrack.i-mscp.net/issue/IP-1503
seccomp_sandbox=NO
EOF
    }

    my $baseServerPublicIp = ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );
    if ( $::imscpConfig{'BASE_SERVER_IP'} ne $baseServerPublicIp ) {
        $cfgTpl .= <<"EOF";

# Server behind NAT - Advertise public IP address
pasv_address=$baseServerPublicIp
pasv_promiscuous=YES
EOF
    }

    if ( ::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes' ) {
        $cfgTpl .= <<"EOF";

# SSL support
ssl_enable=YES
force_local_data_ssl=NO
force_local_logins_ssl=NO
ssl_sslv2=NO
ssl_sslv3=NO
ssl_tlsv1=NO
ssl_tlsv1_1=NO
ssl_TLSv1_2=YES
require_ssl_reuse=NO
ssl_ciphers=HIGH
rsa_cert_file=$::imscpConfig{'CONF_DIR'}/imscp_services.pem
rsa_private_key_file=$::imscpConfig{'CONF_DIR'}/imscp_services.pem
EOF
    }

    $cfgTpl = iMSCP::TemplateParser::process( $data, $cfgTpl );

    $rs = $self->{'events'}->trigger(
        'afterFtpdBuildConf', \$cfgTpl, 'vsftpd.conf'
    );
    return $rs if $rs;

    my $file = iMSCP::File->new(
        filename => $self->{'config'}->{'FTPD_CONF_FILE'}
    );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
    );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    # VsFTPd pam-mysql configuration file
    undef $cfgTpl;

    local $UMASK = 027; # vsftpd.pam file must not be created/copied world-readable

    $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_PAM_CONF_FILE'} );
    $rs ||= $self->{'events'}->trigger(
        'onLoadTemplate', 'vsftpd', 'vsftpd.pam', \$cfgTpl, $data
    );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        return 1 unless defined(
            $cfgTpl = iMSCP::File->new(
                filename => "$self->{'cfgDir'}/vsftpd.pam"
            )->get()
        );
    }

    $rs = $self->{'events'}->trigger(
        'beforeFtpdBuildConf', \$cfgTpl, 'vsftpd.pam'
    );
    return $rs if $rs;

    $cfgTpl = iMSCP::TemplateParser::process( $data, $cfgTpl );

    $rs = $self->{'events'}->trigger(
        'afterFtpdBuildConf', \$cfgTpl, 'vsftpd.pam'
    );
    return $rs if $rs;

    $file = iMSCP::File->new(
        filename => $self->{'config'}->{'FTPD_PAM_CONF_FILE'}
    );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
    );
    $rs ||= $file->mode( 0640 );
}

=item _bkpConfFile( )

 Backup file

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ( $self, $cfgFile ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFtpdBkpConfFile', $cfgFile );
    return $rs if $rs;

    if ( -f $cfgFile ) {
        my $file = iMSCP::File->new( filename => $cfgFile );
        my $basename = basename( $cfgFile );

        unless ( -f "$self->{'bkpDir'}/$basename.system" ) {
            $rs = $file->copyFile(
                "$self->{'bkpDir'}/$basename.system", { preserve => 'no' }
            );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile(
                "$self->{'bkpDir'}/$basename." . time, { preserve => 'no' }
            );
            return $rs if $rs;
        }
    }

    $self->{'events'}->trigger( 'afterFtpdBkpConfFile', $cfgFile );
}

=item _isVsFTPdInsideCt( )

 Does the VsFTPd server is run inside an unprivileged VE (OpenVZ container)

 Return bool TRUE if the VsFTPd server is run inside an OpenVZ container, FALSE otherwise

=cut

sub _isVsFTPdInsideCt
{
    return 0 unless -f '/proc/user_beancounters';

    my $rs = execute(
        'cat /proc/1/status | grep --color=never envID',
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $rs && $stderr;
    return $rs if $rs;

    if ( $stdout =~ /envID:\s+(\d+)/ ) {
        return ( $1 > 0 ) ? 1 : 0;
    }

    0;
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ( $self ) = @_;

    return 0 unless -f "$self->{'cfgDir'}/vsftpd.old.data";

    iMSCP::File->new(
        filename => "$self->{'cfgDir'}/vsftpd.old.data"
    )->delFile();

}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
