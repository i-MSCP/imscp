=head1 NAME

 Servers::ftpd::proftpd::installer - i-MSCP Proftpd Server implementation

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

package Servers::ftpd::proftpd::installer;

use strict;
use warnings;
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
use Servers::ftpd::proftpd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Poftpd Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::events \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    $events->registerOne( 'beforeSetupDialog', sub {
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

    $self->{'ftpd'}->stop();
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    $rs ||= $self->_setVersion();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfigFile();
    $rs ||= $self->_oldEngineCompatibility();
}

=item postinstall( )

 Post-installation tasks

 Return int 0 on success, other on failure

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
            push @{ $_[0] }, [ sub { $self->{'ftpd'}->start(); }, 'ProFTPD' ];
            0;
        },
        4
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::ftpd::proftpd::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'ftpd'} = Servers::ftpd::proftpd->getInstance();
    $self->{'events'} = $self->{'ftpd'}->{'events'};
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
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
        my ( $filename, undef, $suffix ) = fileparse( $cfgFile );

        unless ( -f "$self->{'bkpDir'}/$filename$suffix.system" ) {
            $rs = $file->copyFile(
                "$self->{'bkpDir'}/$filename$suffix.system",
                { preserve => 'no' }
            );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile(
                "$self->{'bkpDir'}/$filename$suffix." . time,
                { preserve => 'no' }
            );
            return $rs if $rs;
        }
    }

    $self->{'events'}->trigger( 'afterFtpdBkpConfFile', $cfgFile );
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ( $self ) = @_;

    my $rs = execute( 'proftpd -v', \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m%([\d.]+)% ) {
        error( "Couldn't find ProFTPD version from 'proftpd -v' command output." );
        return 1;
    }

    $self->{'config'}->{'PROFTPD_VERSION'} = $1;
    debug( "ProFTPD version set to: $1" );
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
                WHERE `name` LIKE 'PROFTPD_SQL_%'
            ",
            { Columns => [ 1, 2 ] }
        ) };

        ( $config{'PROFTPD_SQL_USER'} = decryptRijndaelCBC(
            $::imscpDBKey,
            $::imscpDBiv,
            $config{'PROFTPD_SQL_USER'} // ''
        ) && $config{'PROFTPD_SQL_USER'} || 'proftpd_' . randomStr( 8, ALPHA64 ) );

        ( $config{'PROFTPD_SQL_USER_PASSWD'} = decryptRijndaelCBC(
            $::imscpDBKey,
            $::imscpDBiv,
            $config{'PROFTPD_SQL_USER_PASSWD'} // ''
        ) || randomStr( 16, ALPHA64 ) );

        (
            $self->{'_proftpd_sql_user'},
            $self->{'_proftpd_sql_user_passwd'}
        ) = (
            $config{'PROFTPD_SQL_USER'}, $config{'PROFTPD_SQL_USER_PASSWD'}
        );

        $dbh->do(
            '
                INSERT INTO `config` (`name`,`value`)
                VALUES (?,?),(?,?)
                ON DUPLICATE KEY UPDATE `name` = `name`
            ',
            undef,
            'PROFTPD_SQL_USER',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'PROFTPD_SQL_USER'}
            ),
            'PROFTPD_SQL_USER_PASSWD',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'PROFTPD_SQL_USER_PASSWD'}
            )
        );

        my $sqlServer = Servers::sqld->factory();

        for my $host (
            $::imscpOldConfig{'DATABASE_USER_HOST'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' )
        ) {
            next unless length $host;
            for my $user (
                $config{'PROFTPD_SQL_USER'},
                $self->{'ftpd'}->{'oldConfig'}->{'DATABASE_USER'} # Transitional
            ) {
                next unless length $user;
                $sqlServer->dropUser( $user, $host );
            }
        }

        $sqlServer->createUser(
            $config{'PROFTPD_SQL_USER'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' ),
            $config{'PROFTPD_SQL_USER_PASSWD'},
        );

        for my $table ( 'ftp_users', 'ftp_group' ) {
            $dbh->do(
                "
                    GRANT SELECT
                    ON `@{ [ ::setupGetQuestion( 'DATABASE_NAME' ) ] }`.`$table`
                    TO ?\@?
                ",
                undef,
                $config{'PROFTPD_SQL_USER'},
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
                $config{'PROFTPD_SQL_USER'},
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

    # Escape any double-quotes and backslash (see #IP-1330)
    ( my $dbUser = $self->{'_proftpd_sql_user'} ) =~ s%("|\\)%\\$1%g;
    ( my $dbPass = $self->{'_proftpd_sql_user_passwd'} ) =~ s%("|\\)%\\$1%g;

    my $data = {
        IPV6_SUPPORT            => ::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'on' : 'off',
        HOSTNAME                => ::setupGetQuestion( 'SERVER_HOSTNAME' ),
        DATABASE_NAME           => ::setupGetQuestion( 'DATABASE_NAME' ),
        DATABASE_HOST           => ::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT           => ::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER           => qq/"$dbUser"/,
        DATABASE_PASS           => qq/"$dbPass"/,
        FTPD_MIN_UID            => $self->{'config'}->{'MIN_UID'},
        FTPD_MIN_GID            => $self->{'config'}->{'MIN_GID'},
        FTPD_PASSIVE_PORT_RANGE => $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'},
        CONF_DIR                => $::imscpConfig{'CONF_DIR'},
        CERTIFICATE             => 'imscp_services',
        SERVER_IDENT_MESSAGE    => '"[' . ::setupGetQuestion( 'SERVER_HOSTNAME' ) . '] i-MSCP FTP server."',
        TLSOPTIONS              => join( ' ', (
            # The 'NoCertRequest' option is deprecated since v1.3.6rc2.
            # See http://www.proftpd.org/docs/RELEASE_NOTES-1.3.6rc2
            version->parse( $self->{'config'}->{'PROFTPD_VERSION'} )
                < version->parse( '1.3.6' ) ? 'NoCertRequest ' : (),
            'NoSessionReuseRequired'
        )),
        MAX_INSTANCES           => $self->{'config'}->{'MAX_INSTANCES'},
        MAX_CLIENT_PER_HOST     => $self->{'config'}->{'MAX_CLIENT_PER_HOST'}
    };

    my $rs = $self->{'events'}->trigger(
        'onLoadTemplate', 'proftpd', 'proftpd.conf', \my $cfgTpl, $data
    );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        return 1 unless defined(
            $cfgTpl = iMSCP::File->new(
                filename => "$self->{'cfgDir'}/proftpd.conf"
            )->get()
        );
    }

    $rs = $self->{'events'}->trigger(
        'beforeFtpdBuildConf', \$cfgTpl, 'proftpd.conf'
    );
    return $rs if $rs;

    if ( ::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes' ) {
        $cfgTpl .= <<'EOF';

# SSL configuration
<Global>
<IfModule mod_tls.c>
  TLSEngine                on
  TLSRequired              off
  TLSLog                   /var/log/proftpd/ftp_ssl.log
  TLSOptions               {TLSOPTIONS}
  TLSRSACertificateFile    {CONF_DIR}/{CERTIFICATE}.pem
  TLSRSACertificateKeyFile {CONF_DIR}/{CERTIFICATE}.pem
  TLSVerifyClient          off
</IfModule>
</Global>
<IfModule mod_tls.c>
  TLSProtocol TLSv1
</IfModule>
EOF
    }

    my $baseServerIp = ::setupGetQuestion( 'BASE_SERVER_IP' );
    my $baseServerPublicIp = ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );

    if ( $baseServerIp ne $baseServerPublicIp ) {
        my @virtualHostIps = grep ($_ ne '0.0.0.0', (
            '127.0.0.1',
            ( ::setupGetQuestion( 'IPV6_SUPPORT' ) ? '::1' : () ),
            $baseServerIp
        ));
        $cfgTpl .= <<"EOF";

# Server behind NAT - Advertise public IP address
MasqueradeAddress $baseServerPublicIp

# VirtualHost for local access (No IP masquerading)
<VirtualHost @virtualHostIps>
    ServerName "{HOSTNAME}.local"
</VirtualHost>
EOF
    }

    $cfgTpl = process( $data, $cfgTpl );

    $rs = $self->{'events'}->trigger(
        'afterFtpdBuildConf', \$cfgTpl, 'proftpd.conf'
    );
    return $rs if $rs;

    local $UMASK = 027; # proftpd.conf file must not be created/copied world-readable

    my $file = iMSCP::File->new(
        filename => "$self->{'wrkDir'}/proftpd.conf"
    );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
    );
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->copyFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" ) {
        $file = iMSCP::File->new(
            filename => "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf"
        );
        return 1 unless defined( my $cfgTplRef = $file->getAsRef());

        ${ $cfgTplRef } =~ s/^(LoadModule\s+mod_tls_memcache.c)/#$1/m;
        $rs ||= $file->save();
    }

    $rs;
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ( $self ) = @_;

    return 0 unless -f "$self->{'cfgDir'}/proftpd.old.data";

    iMSCP::File->new(
        filename => "$self->{'cfgDir'}/proftpd.old.data"
    )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
