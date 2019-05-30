=head1 NAME

 Servers::server::local::installer - i-MSCP local server implementation

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

package Servers::server::local::installer;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Database;
use DateTime::TimeZone;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation qw/
    $LAST_VALIDATION_ERROR
    isValidHostname isValidIpAddr isValidTimezone
/;
use iMSCP::Execute 'execute';
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Net;
use Net::LibIDN qw/ idn_to_ascii idn_to_unicode /;
use LWP::Simple 'get';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP local server implementation

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    # Must be done early because installers can rely on this configuration
    # parameter
    ::setupSetQuestion( 'IPV6_SUPPORT', -f '/proc/net/if_inet6' ? 1 : 0 );

    $events->register(
        'beforeSetupDialog',
        sub {
            push @{ $_[0] },
                sub { $self->dialogForServerHostname( @_ ) },
                sub { $self->dialogForBaseServerIP( @_ ) },
                sub { $self->dialogForBaseServerPublicIP( @_ ) },
                sub { $self->dialogForServerTimezone( @_ ) };
            0;
        },
        # We register these dialogs with a highest priority to show them
        # before any other server/package dialog
        999
    );
}

=item dialogForServerHostname( \%dialog )

 Dialog for server hostname

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub dialogForServerHostname
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'SERVER_HOSTNAME', ( `hostname --fqdn 2>/dev/null` || '' ) =~ s/\n+$//r
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ local_server system_hostname hostnames all / )
        && isValidHostname( $value )
    ) {
        ::setupSetQuestion( 'SERVER_HOSTNAME', $value );
        return 20;
    }

    chomp( $value = $value || `hostname --fqdn 2>/dev/null` || '' );
    $value = idn_to_unicode( $value, 'utf-8' );

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string( <<"EOF", $value );
${msg}Please enter your server hostname (FQHN):
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;
            $msg = isValidHostname( $value ) ? '' : $LAST_VALIDATION_ERROR;
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'SERVER_HOSTNAME', idn_to_ascii( $value, 'utf-8' ));
    0;
}

=item dialogForBaseServerIP( \%dialog )

 Dialog for base server IP

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub dialogForBaseServerIP
{
    my ( undef, $dialog ) = @_;

    my @ipList = grep ( isValidIpAddr(
        $_, qr/(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)/ ),
        iMSCP::Net->getInstance()->getAddresses()
    );
    @ipList or die(
        "Couldn't retrieve list of server IP addresses. At least one IP address must be configured."
    );

    my $value = ::setupGetQuestion( 'BASE_SERVER_IP' );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ local server primary_ip all / )
        && isValidIpAddr( $value )
        && ( $value eq '0.0.0.0' || grep( $_ eq $value, @ipList) )
    ) {
        return 20;
    }

    my %choices;
    @{choices}{ @ipList, 'None' } = ( @ipList, '0.0.0.0' );

    ( my $ret, $value ) = $dialog->select(
        <<"EOF", \%choices, ( grep ( $_ eq $value, @ipList, '0.0.0.0' ) )[0] // $ipList[0] );
Please select your server primary IP address:

The \\Zb'None'\\Zn option means that the services will be configured to listen on all interfaces.
This options is more suitable for Cloud computing services such as Scaleway and Amazon EC2.
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BASE_SERVER_IP', $value );
    0;
}

=item dialogForBaseServerPublicIP( \%dialog )

 Dialog for base server public IP

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub dialogForBaseServerPublicIP
{
    my ( undef, $dialog ) = @_;

    my $baseServerIp = ::setupGetQuestion( 'BASE_SERVER_IP' );
    my $value = ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );

    # Try to guess the WAN IP (default value) in case BASE_SERVER_IP is not a
    # public IP.
    if ( !length $value
        && !isValidIpAddr( $baseServerIp, qr/(?:PUBLIC|GLOBAL-UNICAST)/ )
    ) {
        chomp( $value = get( 'https://ipinfo.io/ip' )
            || get( 'https://api.ipify.org/?format=txt' ) || ''
        );

        # If the WAN IP has been guessed and the user didn't asked for
        # reconfiguration, we skip dialog
        if ( length $value
            && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ local_server primary_ip all / )
        ) {
            ::setupSetQuestion( 'BASE_SERVER_PUBLIC_IP', $value );
            return 20;
        }
    }

    # If user didn't asked for reconfiguration and the server public IP is
    # equal to the base server IP, but not equal to the INADDR_ANY IP, we
    # skip the dialog for the server public IP
    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ local_server primary_ip all / )
        && ( $value eq $baseServerIp && $baseServerIp ne '0.0.0.0' )
    ) {
        return 20;
    }

    my $wanNotSetOrInsidePrivateRange = !length $value || !isValidIpAddr(
        $value, qr/(?:PUBLIC|GLOBAL-UNICAST)/
    );

    # IP inside private IP range?
    if ( $dialog->executeRetval == 30
        ||  $wanNotSetOrInsidePrivateRange
        || grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ local_server primary_ip all / )
    ) {
        chomp( $value = get( 'https://ipinfo.io/ip' )
            || get( 'https://api.ipify.org/?format=txt' ) || ''
        ) unless length $value || grep (
            $_ eq iMSCP::Getopt->reconfigure, qw/ local_server primary_ip all /
        );

        my ( $ret, $msg ) = ( 0, '' );
        do {
            ( $ret, $value ) = $dialog->string( <<"EOF", $value );
${msg}Please enter your public IP address (WAN IP), or leave blank to force usage of the private IP address.

If you're behind a NAT router, you MUST not forget to forward the UDP/TCP ports for the various services:

 - DNS : 53 UDP/TCP ports
 - FTP : 20, 21 TCP ports, including passive TCP port range which is 32800..33800 (default) 
 - HTTP: 80, 443 TCP ports, including TCP ports for the control panel which are 8880 and 8443 (default)
 - IMAP: 143, 993 TCP ports
 - POP3: 110, 995 TCP ports
 - SMTP: 25, 465, 587 TCP ports
EOF
            if ( $ret != 30 ) {
                $value =~ s/^\s+|\s+$//g;

                if ( length $value
                    && $value ne $baseServerIp
                    && !isValidIpAddr( $value, qr/(?:PUBLIC|GLOBAL-UNICAST)/ )
                ) {
                    $msg = $LAST_VALIDATION_ERROR;
                } elsif ( !length $value ) {
                    $value = $baseServerIp;
                }

                if ( $value eq '0.0.0.0' ) {
                    $msg = "\\Z1Invalid or unauthorized IP address.\\Zn\n\n";
                } else {
                    $msg = '';
                }
            }
        } while $ret != 30 && length $msg;
        return 30 if $ret == 30;
    }

    ::setupSetQuestion( 'BASE_SERVER_PUBLIC_IP', $value );
    0;
}

=item dialogForServerTimezone( \%dialog )

 Dialog for server timezone

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub dialogForServerTimezone
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'TIMEZONE', DateTime::TimeZone->new( name => 'local' )->name()
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ local_server timezone all / )
        && isValidTimezone( $value )
    ) {
        ::setupSetQuestion( 'TIMEZONE', $value );
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string(
            <<"EOF", length $value ? $value : DateTime::TimeZone->new( name => 'local' )->name());
${msg}Please enter the server timezone:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;
            $msg = isValidTimezone( $value ) ? '' : $LAST_VALIDATION_ERROR;
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'TIMEZONE', $value );
    0;
}

=item preinstall( )

 Pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeSetupKernel' );
    return $rs if $rs;

    if ( -f "$::imscpConfig{'SYSCTL_CONF_DIR'}/imscp.conf" ) {
        # Don't catch any error here to avoid permission denied error on some
        # vps due to restrictions set by provider
        $rs = execute(
            [
                $::imscpConfig{'CMD_SYSCTL'},
                '-p', "$::imscpConfig{'SYSCTL_CONF_DIR'}/imscp.conf"
            ],
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if length $stdout;
        debug( $stderr ) if length $stderr;
    }

    $self->{'events'}->trigger( 'afterSetupKernel' );

    0;
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_setupHostname();
    $rs ||= $self->_setupPrimaryIP();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::server::local::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _setupHostname( )

 Setup server hostname

 Return int 0 on success, other on failure

=cut

sub _setupHostname
{
    my ( $self ) = @_;

    my $hostname = ::setupGetQuestion( 'SERVER_HOSTNAME' );
    my $lanIP = ::setupGetQuestion( 'BASE_SERVER_IP' );

    my $rs = $self->{'events'}->trigger(
        'beforeSetupServerHostname', \$hostname, \$lanIP
    );
    return $rs if $rs;

    my @labels = split /\./, $hostname;
    my $host = shift @labels;
    my $hostnameLocal = "$hostname.local";

    my $file = iMSCP::File->new( filename => '/etc/hosts' );
    $rs = $file->copyFile( '/etc/hosts.bkp' ) unless -f '/etc/hosts.bkp';
    return $rs if $rs;

    my $content = <<"EOF";
127.0.0.1   $hostnameLocal   localhost
$lanIP  $hostname   $host

# The following lines are desirable for IPv6 capable hosts
::1 localhost  ip6-localhost   ip6-loopback
fe00::0 ip6-localnet
ff00::0 ip6-mcastprefix
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters
ff02::3 ip6-allhosts
EOF
    $file->set( $content );

    $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
    );
    $rs ||= $file->mode( 0644 );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => '/etc/hostname' );
    $file->set( $host );

    $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
    );
    $rs ||= $file->mode( 0644 );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => '/etc/mailname' );
    $file->set( $hostname );

    $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
    );
    $rs ||= $file->mode( 0644 );
    return $rs if $rs;

    $rs = execute( 'hostname -F /etc/hostname', \my $stdout, \my $stderr );
    debug( $stdout ) if length $stdout;
    error( $stderr || "Couldn't set server hostname" ) if $rs;
    $rs ||= $self->{'events'}->trigger( 'afterSetupServerHostname' );
}

=item _setupPrimaryIP( )

 Setup server primary IP

 Return int 0 on success, other on failure

=cut

sub _setupPrimaryIP
{
    my ( $self ) = @_;

    my $primaryIP = ::setupGetQuestion( 'BASE_SERVER_IP' );
    my $rs = $self->{'events'}->trigger( 'beforeSetupPrimaryIP', $primaryIP );
    return $rs if $rs;

    local $@;
    eval {
        my $netCard = ( $primaryIP eq '0.0.0.0' )
            ? 'any' : iMSCP::Net->getInstance()->getAddrDevice( $primaryIP
        );
        defined $netCard or die( sprintf(
            "Couldn't find network card for the '%s' IP address", $primaryIP
        ));

        my $db = iMSCP::Database->factory();
        my $oldDbName = $db->useDatabase(
            ::setupGetQuestion( 'DATABASE_NAME' )
        );

        my $dbh = $db->getRawDb();
        $dbh->selectrow_hashref(
            'SELECT 1 FROM server_ips WHERE ip_number = ?', undef, $primaryIP
        ) ? $dbh->do(
            'UPDATE server_ips SET ip_card = ? WHERE ip_number = ?',
            undef,
            $netCard,
            $primaryIP
        ) : $dbh->do(
            '
                INSERT INTO server_ips (
                    ip_number, ip_card, ip_config_mode, ip_status
                ) VALUES(
                    ?, ?, ?, ?
                )
            ',
            undef,
            $primaryIP,
            $netCard,
            'manual',
            'ok'
        );

        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterSetupPrimaryIP', $primaryIP );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
