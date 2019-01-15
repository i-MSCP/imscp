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
use iMSCP::Config;
use iMSCP::Crypt qw/ ALNUM randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser 'process';
use iMSCP::Umask '$UMASK';
use Servers::ftpd::proftpd;
use Servers::sqld;
use Try::Tiny;
use parent 'Common::SingletonClass';

%::SQL_USERS = () unless %::SQL_USERS;

=head1 DESCRIPTION

 Installer for the i-MSCP Poftpd Server implementation.

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
            sub { $self->sqlUserDialog( @_ ) },
            sub { $self->passivePortRangeDialog( @_ ) };
        0;
    } );
}

=item sqlUserDialog( \%dialog )

 Ask for ProFTPD SQL user

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub sqlUserDialog
{
    my ( $self, $dialog ) = @_;

    my $masterSqlUser = ::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = ::setupGetQuestion( 'FTPD_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'imscp_srv_user' );
    my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = ::setupGetQuestion(
        'FTPD_SQL_PASSWORD', ( iMSCP::Getopt->preseed ? randomStr( 16, ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    if ( $::reconfigure =~ /^(?:ftpd|servers|all|forced)$/ || !isValidUsername( $dbUser )
        || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' ) || !isValidPassword( $dbPass )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my ( $rs, $msg ) = ( 0, '' );

        do {
            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the ProFTPD SQL user:$msg
EOF
            $msg = '';
            if ( !isValidUsername( $dbUser )
                || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' )
                || !isAvailableSqlUser( $dbUser )
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        unless ( defined $::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            do {
                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass || randomStr( 16, ALNUM ));

Please enter a password for the ProFTPD SQL user:$msg
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

    ::setupSetQuestion( 'FTPD_SQL_USER', $dbUser );
    ::setupSetQuestion( 'FTPD_SQL_PASSWORD', $dbPass );
    0;
}

=item passivePortRangeDialog( \%dialog )

 Ask for ProtFTPD port range to use for passive data transfers

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub passivePortRangeDialog
{
    my ( $self, $dialog ) = @_;

    my $passivePortRange = ::setupGetQuestion( 'FTPD_PASSIVE_PORT_RANGE', $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} );
    my ( $startOfRange, $endOfRange );

    if ( !isValidNumberRange( $passivePortRange, \$startOfRange, \$endOfRange ) || !isNumberInRange( $startOfRange, 32768, 60999 )
        || !isNumberInRange( $endOfRange, $startOfRange, 60999 ) || $::reconfigure =~ /^(?:ftpd|servers|all|forced)$/
    ) {
        $passivePortRange = '32768 60999' unless $startOfRange && $endOfRange;
        my ( $rs, $msg ) = ( 0, '' );

        do {
            ( $rs, $passivePortRange ) = $dialog->inputbox( <<"EOF", $passivePortRange );

\\Z4\\Zb\\ZuProFTPD passive port range\\Zn

Please choose the passive port range for ProFTPD.

Note that if you're behind a NAT, you must forward those ports to this server.$msg
EOF
            $msg = '';
            if ( !isValidNumberRange( $passivePortRange, \$startOfRange, \$endOfRange ) || !isNumberInRange( $startOfRange, 32768, 60999 )
                || !isNumberInRange( $endOfRange, $startOfRange, 60999 )
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        $passivePortRange = "$startOfRange $endOfRange";
    }

    $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} = $passivePortRange;
    0;
}

=item install( )

 Process install tasks

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

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'ftpd'} = Servers::ftpd::proftpd->getInstance();
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'ftpd'}->{'config'};
    $self;
}

=item _bkpConfFile( )

 Backup file

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ( $self, $cfgFile ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBkpConfFile', $cfgFile );
    return $rs if $rs;

    if ( -f $cfgFile ) {
        my $file = iMSCP::File->new( filename => $cfgFile );
        my ( $filename, undef, $suffix ) = fileparse( $cfgFile );

        unless ( -f "$self->{'bkpDir'}/$filename$suffix.system" ) {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$filename$suffix.system", { preserve => 'no' } );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$filename$suffix." . time, { preserve => 'no' } );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterFtpdBkpConfFile', $cfgFile );
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ( $self ) = @_;

    my $rs = execute( [ 'proftpd', '-v' ], \my $stdout, \my $stderr );
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

    try {
        my $dbName = ::setupGetQuestion( 'DATABASE_NAME' );
        my $dbUser = ::setupGetQuestion( 'FTPD_SQL_USER' );
        my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
        my $dbPass = ::setupGetQuestion( 'FTPD_SQL_PASSWORD' );

        if ( length $self->{'config'}->{'DATABASE_USER'} && length $::imscpOldConfig{'DATABASE_USER_HOST'}
            && $dbUser . $dbUserHost ne $self->{'config'}->{'DATABASE_USER'} . $::imscpOldConfig{'DATABASE_USER_HOST'}
            && !exists $::SQL_USERS{$self->{'config'}->{'DATABASE_USER'} . $::imscpOldConfig{'DATABASE_USER_HOST'}}
        ) {
            Servers::sqld->factory()->dropUser( $self->{'config'}->{'DATABASE_USER'}, $::imscpOldConfig{'DATABASE_USER_HOST'} );
        }

        unless ( exists $::SQL_USERS{$dbUser . $dbUserHost} ) {
            Servers::sqld->factory()->createUser( $dbUser, $dbUserHost, $dbPass );
            undef $::SQL_USERS{$dbUser . $dbUserHost};
        }

        iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            my ( $dbh ) = @_;
            for my $table ( 'ftp_users', 'ftp_group' ) {
                # Backslash in database name must not be escaped. See https://bugs.mysql.com/bug.php?id=18660
                $dbh->do( "GRANT SELECT ON @{ [ $dbh->quote_identifier( $dbName ) ] }.$table TO ?\@?", undef, $dbUser, $dbUserHost );
            }

            for my $table ( 'quotalimits', 'quotatallies' ) {
                # Backslash in database name must not be escaped. See https://bugs.mysql.com/bug.php?id=18660
                $dbh->do( "GRANT SELECT, INSERT, UPDATE ON @{ [ $dbh->quote_identifier( $dbName ) ] }.$table TO ?\@?", undef, $dbUser, $dbUserHost );
            }
        } );

        @{ $self->{'config'} }{qw/ DATABASE_NAME DATABASE_PASSWORD /} = ( $dbUser, $dbPass );
        0;
    } catch {
        error( $_ );
        1;
    };
}

=item _buildConfigFile( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
    my ( $self ) = @_;

    # Escape any double-quotes and backslash. see #IP-1330
    ( my $dbUser = $self->{'config'}->{'DATABASE_USER'} ) =~ s%("|\\)%\\$1%g;
    ( my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'} ) =~ s%("|\\)%\\$1%g;

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
        TLSOPTIONS              => 'NoCertRequest NoSessionReuseRequired',
        MAX_INSTANCES           => $self->{'config'}->{'MAX_INSTANCES'},
        MAX_CLIENT_PER_HOST     => $self->{'config'}->{'MAX_CLIENT_PER_HOST'}
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'proftpd', 'proftpd.conf', \my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.conf" )->get();
        return 1 unless defined $cfgTpl;
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBuildConf', \$cfgTpl, 'proftpd.conf' );
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
        my @virtualHostIps = grep (
            $_ ne '0.0.0.0',
            ( '127.0.0.1', ( ::setupGetQuestion( 'IPV6_SUPPORT' ) ? '::1' : () ), $baseServerIp )
        );
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

    $rs = $self->{'eventManager'}->trigger( 'afterFtpdBuildConf', \$cfgTpl, 'proftpd.conf' );
    return $rs if $rs;

    local $UMASK = 027;
    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/proftpd.conf" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->copyFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" ) {
        $file = iMSCP::File->new( filename => "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" );
        my $cfgTplRef = $file->getAsRef();
        return 1 unless defined $cfgTplRef;

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

    iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.old.data" )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
