=head1 NAME

 Servers::ftpd::vsftpd::installer - i-MSCP VsFTPd Server implementation

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

package Servers::ftpd::vsftpd::installer;

use strict;
use warnings;
use Cwd;
use File::Basename;
use iMSCP::Config;
use iMSCP::Crypt qw/ randomStr /;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dialog::InputValidation;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser;
use iMSCP::Stepper;
use iMSCP::Umask;
use Servers::ftpd::vsftpd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 Installer for the i-MSCP VsFTPd Server implementation.

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
            push @{$_[0]}, sub { $self->sqlUserDialog( @_ ) }, sub { $self->passivePortRangeDialog( @_ ) };
            0;
        }
    );
}

=item sqlUserDialog( \%dialog )

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub sqlUserDialog
{
    my ($self, $dialog) = @_;

    my $masterSqlUser = main::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = main::setupGetQuestion( 'FTPD_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'imscp_srv_user' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion(
        'FTPD_SQL_PASSWORD',
        ( ( iMSCP::Getopt->preseed ) ? randomStr( 16, iMSCP::Crypt::ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    if ( $main::reconfigure =~ /^(?:ftpd|servers|all|forced)$/
        || !isValidUsername( $dbUser )
        || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' )
        || !isValidPassword( $dbPass )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my ($rs, $msg) = ( 0, '' );

        do {
            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the VsFTPd SQL user:$msg
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

        unless ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            do {
                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass || randomStr( 16, iMSCP::Crypt::ALNUM ));

Please enter a password for the VsFTPd SQL user:$msg
EOF
                $msg = isValidPassword( $dbPass ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;

            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
        } else {
            $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
        }
    } elsif ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
        $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
    } else {
        $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
    }

    main::setupSetQuestion( 'FTPD_SQL_USER', $dbUser );
    main::setupSetQuestion( 'FTPD_SQL_PASSWORD', $dbPass );
    0;
}

=item passivePortRangeDialog( \%dialog )

 Ask for VsFTPd port range to use for passive data transfers

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub passivePortRangeDialog
{
    my ($self, $dialog) = @_;

    my $passivePortRange = main::setupGetQuestion(
        'FTPD_PASSIVE_PORT_RANGE', $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'}
    );
    my ($startOfRange, $endOfRange);

    if ( !isValidNumberRange( $passivePortRange, \$startOfRange, \$endOfRange )
        || !isNumberInRange( $startOfRange, 32768, 60999 )
        || !isNumberInRange( $endOfRange, $startOfRange, 60999 )
        || $main::reconfigure =~ /^(?:ftpd|servers|all|forced)$/
    ) {
        $passivePortRange = '32768 60999' unless $startOfRange && $endOfRange;
        my ($rs, $msg) = ( 0, '' );

        do {
            ( $rs, $passivePortRange ) = $dialog->inputbox( <<"EOF", $passivePortRange );

\\Z4\\Zb\\ZuVsFTPd passive port range\\Zn

Please, choose the passive port range for VsFTPd.

Note that if you're behind a NAT, you must forward those ports to this server.$msg
EOF
            $msg = '';
            if ( !isValidNumberRange( $passivePortRange, \$startOfRange, \$endOfRange )
                || !isNumberInRange( $startOfRange, 32768, 60999 )
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
    my ($self) = @_;

    my $rs = $self->_setVersion();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfigFile();
    $rs ||= $self->_oldEngineCompatibility();
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
    my ($self) = @_;

    $self->{'ftpd'} = Servers::ftpd::vsftpd->getInstance();
    $self->{'eventManager'} = $self->{'ftpd'}->{'eventManager'};
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'config'} = $self->{'ftpd'}->{'config'};
    $self;
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ($self) = @_;

    # Version is print through STDIN (see: strace vsftpd -v)
    my $rs = execute( 'vsftpd -v 0>&1', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m%([\d.]+)% ) {
        error( "Couldn't find VsFTPd version from `vsftpd -v 0>&1` command output." );
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
    my ($self) = @_;

    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $dbUser = main::setupGetQuestion( 'FTPD_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = main::setupGetQuestion( 'FTPD_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    $self->{'eventManager'}->trigger( 'beforeFtpdSetupDb', $dbUser, $dbPass );

    local $@;
    eval {
        my $sqlServer = Servers::sqld->factory();

        # Drop old SQL user if required
        for my $sqlUser ( $dbOldUser, $dbUser ) {
            next unless $sqlUser;

            for my $host( $dbUserHost, $oldDbUserHost ) {
                next if !$host
                    || exists $main::sqlUsers{$sqlUser . '@' . $host} && !defined $main::sqlUsers{$sqlUser . '@' . $host};
                $sqlServer->dropUser( $sqlUser, $host );
            }
        }

        # Create SQL user if required
        if ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ));
            $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = undef;
        }

        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'} = 1;

        # Give required privileges to this SQL user
        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        my $quotedDbName = $dbh->quote_identifier( $dbName );
        $dbh->do( "GRANT SELECT ON $quotedDbName.ftp_users TO ?\@?", undef, $dbUser, $dbUserHost );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    $self->{'eventManager'}->trigger( 'afterFtpSetupDb', $dbUser, $dbPass );
}

=item _buildConfigFile( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
    my ($self) = @_;

    # Make sure to start with clean user configuration directory
    unlink glob "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/*";

    my ($passvMinPort, $passvMaxPort) = split( /\s+/, $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} );
    my $data = {
        IPV4_ONLY              => main::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'NO' : 'YES',
        IPV6_SUPPORT           => main::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'YES' : 'NO',
        DATABASE_NAME          => main::setupGetQuestion( 'DATABASE_NAME' ),
        DATABASE_HOST          => main::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT          => main::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER          => $self->{'config'}->{'DATABASE_USER'},
        DATABASE_PASS          => $self->{'config'}->{'DATABASE_PASSWORD'},
        FTPD_BANNER            => $self->{'config'}->{'FTPD_BANNER'},
        FRONTEND_USER_SYS_NAME => $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
        PASSV_ENABLE           => $self->{'config'}->{'PASSV_ENABLE'},
        PASSV_MIN_PORT         => $passvMinPort,
        PASSV_MAX_PORT         => $passvMaxPort,
        FTP_MAX_CLIENTS        => $self->{'config'}->{'FTP_MAX_CLIENTS'},
        MAX_PER_IP             => $self->{'config'}->{'MAX_PER_IP'},
        LOCAL_MAX_RATE         => $self->{'config'}->{'LOCAL_MAX_RATE'},
        USER_WEB_DIR           => $main::imscpConfig{'USER_WEB_DIR'},
        FTPD_USER_CONF_DIR     => $self->{'config'}->{'FTPD_USER_CONF_DIR'}
    };

    # vsftpd main configuration file

    my $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'vsftpd', 'vsftpd.conf', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.conf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s file", "$self->{'cfgDir'}/vsftpd.conf" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBuildConf', \$cfgTpl, 'vsftpd.conf' );
    return $rs if $rs;

    if ( $self->_isVsFTPdInsideCt() ) {
        $cfgTpl .= <<'EOF';

# VsFTPd run inside unprivileged VE
# See http://youtrack.i-mscp.net/issue/IP-1503
seccomp_sandbox=NO
EOF
    }

    my $baseServerPublicIp = main::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );
    if ( $main::imscpConfig{'BASE_SERVER_IP'} ne $baseServerPublicIp ) {
        $cfgTpl .= <<"EOF";

# Server behind NAT - Advertise public IP address
pasv_address=$baseServerPublicIp
pasv_promiscuous=YES
EOF
    }

    if ( main::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes' ) {
        $cfgTpl .= <<"EOF";

# SSL support
ssl_enable=YES
force_local_data_ssl=NO
force_local_logins_ssl=NO
ssl_sslv2=NO
ssl_sslv3=NO
ssl_tlsv1=YES
require_ssl_reuse=NO
ssl_ciphers=HIGH
rsa_cert_file=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem
rsa_private_key_file=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem
EOF
    }

    $cfgTpl = iMSCP::TemplateParser::process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterFtpdBuildConf', \$cfgTpl, 'vsftpd.conf' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_CONF_FILE'} );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    # VsFTPd pam-mysql configuration file
    undef $cfgTpl;

    local $UMASK = 027; # vsftpd.pam file must not be created/copied world-readable

    $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_PAM_CONF_FILE'} );
    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'vsftpd', 'vsftpd.pam', \$cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.pam" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s file", "$self->{'cfgDir'}/vsftpd.pam" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBuildConf', \$cfgTpl, 'vsftpd.pam' );
    return $rs if $rs;

    $cfgTpl = iMSCP::TemplateParser::process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterFtpdBuildConf', \$cfgTpl, 'vsftpd.pam' );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_PAM_CONF_FILE'} );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
}

=item _bkpConfFile( )

 Backup file

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ($self, $cfgFile) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBkpConfFile', $cfgFile );
    return $rs if $rs;

    if ( -f $cfgFile ) {
        my $file = iMSCP::File->new( filename => $cfgFile );
        my $basename = basename( $cfgFile );

        unless ( -f "$self->{'bkpDir'}/$basename.system" ) {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$basename.system", { preserve => 'no' } );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$basename." . time, { preserve => 'no' } );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterFtpdBkpConfFile', $cfgFile );
}

=item _isVsFTPdInsideCt( )

 Does the VsFTPd server is run inside an unprivileged VE (OpenVZ container)

 Return bool TRUE if the VsFTPd server is run inside an OpenVZ container, FALSE otherwise

=cut

sub _isVsFTPdInsideCt
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

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdOldEngineCompatibility' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/vsftpd.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.old.data" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
