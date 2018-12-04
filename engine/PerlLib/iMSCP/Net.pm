=head1 NAME

 iMSCP::Net - Package allowing to manage network devices and IP addresses

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

package iMSCP::Net;

use strict;
use warnings;
use autouse 'Data::Validate::IP' => qw/ is_ipv4 is_ipv6 /;
use iMSCP::Execute qw/ execute /;
use Net::IP qw/ :PROC /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Package allowing to manage network devices and IP addresses.

=head1 PUBLIC METHODS

=over 4

=item getAddresses( )

 Get addresses list

 Return array|string List of IP addresses

=cut

sub getAddresses
{
    my ( $self ) = @_;
    wantarray ? keys %{ $self->{'addresses'} } : join ' ', keys %{ $self->{'addresses'} };
}

=item addAddr( $addr, $cidr, $dev [, $label ] )

 Add the given IP to the given network device

 Param string $addr IP address
 Param string $cidr CIDR (subnet mask)
 Param string $dev Network device name
 Param string $label OPTIONAL address label string (preserve compatibility with Linux-2.0 net aliases)
 Return int 0 on success, die on failure

=cut

sub addAddr
{
    my ( $self, $addr, $cidr, $dev, $label ) = @_;

    $self->isValidAddr( $addr ) or die( sprintf( 'Invalid IP address: %s', $addr ));
    $self->isValidNetmask( $addr, $cidr ) or die( sprintf( 'Invalid CIDR (subnet mask): %s', $cidr ));
    $self->isKnownDevice( $dev ) or die( sprintf( 'Unknown network device: %s', $dev ));

    return 0 if $self->isKnownAddr( $addr );

    my ( $stdout, $stderr );
    execute(
        [
            'ip', $self->getAddrVersion( $addr ) eq 'ipv4' ? '-4' : '-6', 'addr', 'add', "$addr/$cidr", 'dev', $dev,
            length $label ? ( 'label', $label ) : ()
        ],
        \$stdout, \$stderr
    ) == 0 or die( sprintf( "Couldn't add the %s IP address: %s", $addr, $dev, $stderr || 'Unknown error' ));
    $self->{'addresses'}->{$addr} = {
        addr_label    => $label,
        device        => $dev,
        prefix_length => $cidr,
        version       => $self->getAddrVersion( $addr )
    };
    0;
}

=item delAddr( $addr )

 Delete the given IP

 Param string $addr IP address
 Return int 0 on success, die on failure

=cut

sub delAddr
{
    my ( $self, $addr ) = @_;

    $addr = $self->normalizeAddr( $addr );

    return 0 unless $self->isKnownAddr( $addr );

    my $dev = $self->{'addresses'}->{$addr}->{'device'};
    my $cidr = $self->{'addresses'}->{$addr}->{'prefix_length'};
    my ( $stdout, $stderr );
    execute( [ 'ip', 'addr', 'del', "$addr/$cidr", 'dev', $dev ], \$stdout, \$stderr ) == 0 or die(
        sprintf( "Couldn't delete the %s IP address: %s", $addr, $stderr || 'Unknown error' )
    );
    delete $self->{'addresses'}->{$addr};
    0;
}

=item getAddrVersion( $addr )

 Get version of the given IP (ipv4|ipv6)

 Param string $addr IP address
 Return string IP version, die in case the given IP is invalid

=cut

sub getAddrVersion
{
    my ( $self, $addr ) = @_;

    $self->isValidAddr( $addr ) or die( sprintf( 'Invalid IP address: %s', $addr ));
    my $version = ip_get_version( $addr ) or die( sprint( "Couldn't guess version of the %s IP address", $addr ));
    $version == 4 ? 'ipv4' : 'ipv6';
}

=item getAddrType( $addr )

 Get type of the given IP (PUBLIC, PRIVATE, RESERVED...)

 Param string $addr IP address
 Return string IP type, die in case the given IP is invalid

=cut

sub getAddrType
{
    my ( $self, $addr ) = @_;

    my $version = $self->getAddrVersion( $addr ) eq 'ipv4' ? 4 : 6;
    ip_iptype( ip_iptobin( ip_expand_address( $addr, $version ), $version ), $version ) or die(
        sprintf( "Couldn't guess type of the %s IP address", $addr )
    );
}

=item getAddrDevice( $addr )

 Return the network device name to which the given IP belong to

 Param string $addr IP address
 Return string Network device name, die if the given IP is either invalid or not known by this module

=cut

sub getAddrDevice
{
    my ( $self, $addr ) = @_;

    $self->isKnownAddr( $addr ) or die( sprintf( 'Unknown IP address: %s', $addr ));
    $self->{'addresses'}->{$addr}->{'device'};
}

=item getAddrLabel( $addr )

 Return the addr label

 Param string $addr IP address
 Return string Addr label, die if the given IP is either invalid or not known by this module

=cut

sub getAddrLabel
{
    my ( $self, $addr ) = @_;

    $self->isKnownAddr( $addr ) or die( sprintf( 'Unknown IP address: %s', $addr ));
    $self->{'addresses'}->{$addr}->{'device_label'};
}

=item getAddrNetmask( $addr )

 Return the addr netmask

 Param string $addr IP address
 Return string Addr netmask, die if the given IP is either invalid or not known by this module

=cut

sub getAddrNetmask
{
    my ( $self, $addr ) = @_;

    $self->isKnownAddr( $addr ) or die( sprintf( 'Unknown IP address: %s', $addr ));
    $self->{'addresses'}->{$self->normalizeAddr( $addr )}->{'prefix_length'};
}

=item isKnownAddr( $addr )

 Is the given IP address known?

 Param string $addr IP address
 Return bool TRUE if the given IP is known, FALSE otherwise

=cut

sub isKnownAddr
{
    my ( $self, $addr ) = @_;

    exists $self->{'addresses'}->{$self->normalizeAddr( $addr )};
}

=item isValidAddr( $addr )

 Is the given IP address valid?

 Param string $addr IP address
 Return bool TRUE if valid, FALSE otherwise

=cut

sub isValidAddr
{
    my ( undef, $addr ) = @_;

    is_ipv4( $addr ) || is_ipv6( $addr );
}

=item isRoutableAddr( $addr )

 Is the given IP address routable?

 Return bool TRUE if the given IP address is routable, FALSE otherwise

=cut

sub isRoutableAddr
{
    my ( $self, $addr ) = @_;

    return 1 if $self->isValidAddr( $addr ) && $self->getAddrType( $addr ) =~ /^(?:PUBLIC|GLOBAL-UNICAST)$/;
    0;
}

=item isValidNetmask( $addr, $cidr )

 Check whether or not the given netmask for the given IP is valid

 Param string $addr IP address
 Param string $cidr CIDR (subnet mask)
 Return bool TRUE if valid, FALSE otherwise

=cut

sub isValidNetmask
{
    my ( undef, $addr, $cidr ) = @_;

    return 0 if $cidr !~ /\d/;

    my $addrVersion = ip_get_version( $addr );

    if ( $cidr < 1 || ( $addrVersion eq 'ipv4' && $cidr > 32 ) || $cidr > 128 ) {
        return 0;
    }

    1;
}

=item normalizeAddr( $addr )

 Normalize the given IP

 Param string $addr IP address
 Return string Normalized IP on success, die on failure

=cut

sub normalizeAddr
{
    my ( $self, $addr ) = @_;

    $self->isValidAddr( $addr ) or die( sprintf( 'Invalid IP address: %s', $addr ));
    return $addr unless $self->getAddrVersion( $addr ) eq 'ipv6';
    ip_compress_address( $addr, 6 ) or die( sprintf( "Couldn't normalize the %s IP address", $addr ));
}

=item expandAddr( $addr )

 Expand the given IP

 Param string $addr IP address
 Return string Expanded IP on success, die on failure

=cut

sub expandAddr
{
    my ( $self, $addr ) = @_;

    $self->isValidAddr( $addr ) or die( sprintf( 'Invalid IP address: %s', $addr ));
    return $addr unless $self->getAddrVersion( $addr ) eq 'ipv6';
    ip_expand_address( $addr, 6 ) or die( sprintf( "Couldn't expand the %s IP address", $addr ));
}

=item getDevices( )

 Get network devices list

 Return array|string List of devices

=cut

sub getDevices
{
    my ( $self ) = @_;

    wantarray ? keys %{ $self->{'devices'} } : join ' ', keys %{ $self->{'devices'} };
}

=item isKnownDevice( $dev )

 Is the given network device known?

 Param string $dev Network device name
 Return bool TRUE if the network device is known, FALSE otherwise

=cut

sub isKnownDevice
{
    my ( $self, $dev ) = @_;

    exists( $self->{'devices'}->{$dev} );
}

=item upDevice( $dev )

 Bring the given network device up

 Param string $dev Network device name
 Return int 0 on success, die on failure

=cut

sub upDevice
{
    my ( $self, $dev ) = @_;

    $self->isKnownDevice( $dev ) or die( sprintf( 'Unknown network device: %s', $dev ));
    my ( $stdout, $stderr );
    execute( "ip link set dev $dev up", \$stdout, \$stderr ) == 0 or die(
        sprintf( "Couldn't bring the %s network device up: %s", $dev, $stderr || 'Unknown error' )
    );
    0;
}

=item downDevice( $dev )

 Bring the given network device down

 Param string $dev Network device name
 Return int 0 on success, die on failure

=cut

sub downDevice
{
    my ( $self, $dev ) = @_;

    $self->isKnownDevice( $dev ) or die( sprintf( 'Unknown network device: %s', $dev ));
    my ( $stdout, $stderr );
    execute( "ip link set dev $dev down", \$stdout, \$stderr ) == 0 or die(
        sprintf( "Couldn't bring the %s network device down: %s", $dev, $stderr || 'Unknown error' )
    );
    0;
}

=item isDeviceUp( $dev )

 Is the given network device up?

 Param string $dev Network device name
 Return bool TRUE if the given device is known and up, FALSE otherwise

=cut

sub isDeviceUp
{
    my ( $self, $dev ) = @_;

    $self->{'devices'}->{$dev}->{'flags'} =~ /^(?:.*,)?UP(?:,.*)?$/ ? 1 : 0;
}

=item isDeviceDown( $dev )

 Is the given device down?

 Param string $dev Network device name
 Return bool TRUE if the given device is known and down, FALSE otherwise

=cut

sub isDeviceDown
{
    my ( $self, $dev ) = @_;

    $self->{'devices'}->{$dev}->{'flags'} =~ /^(?:.*,)?UP(?:,.*)?$/ ? 0 : 1;
}

=item resetInstance( )

 Reset instance

 Return iMSCP::Net, die on failure

=cut

sub resetInstance
{
    my ( $self ) = @_;

    $self->_init();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Net, die on failure

=cut

sub _init
{
    my ( $self ) = @_;

    @{ $self }{qw/ devices addresses /} = ( $self->_extractDevices(), $self->_extractAddresses() );
    $self;
}

=item _extractDevices( )

 Extract network devices

 Return hashref Reference to a hash containing device data, die on failure

=cut

sub _extractDevices
{
    my ( $stdout, $stderr );
    execute( [ 'ip', '-o', 'link', 'show' ], \$stdout, \$stderr ) == 0 or die(
        sprintf( "Couldn't extract network devices: %s", $stderr || 'Unknown error' )
    );
    my $devices = {};
    $devices->{$1}->{'flags'} = $2 while $stdout =~ /^[^\s]+:\s+(.*?)(?:\@[^\s]+)?:\s+<(.*)>/gm;
    $devices;
}

=item _extractAddresses( )

 Extract addresses

 Return hashref Reference to a hash containing IP addresses data, die on failure

=cut

sub _extractAddresses
{
    my ( $self ) = @_;

    my ( $stdout, $stderr );
    execute( [ 'ip', '-o', 'addr', 'show' ], \$stdout, \$stderr ) == 0 or die(
        sprintf( "Couldn't extract network addresses: %s", $stderr || 'Unknown error' )
    );

    my $addresses = {};
    $addresses->{$self->normalizeAddr( $3 )} = {
        device        => $1,
        version       => $2 eq 'inet' ? 'ipv4' : 'ipv6',
        prefix_length => $4,
        addr_label    => $5 // $1
    } while $stdout =~ /^[^\s]+:\s+([^\s]+)\s+([^\s]+)\s+(?:([^\s]+)(?:\s+peer\s+[^\s]+)?\/([\d]+))\s+(?:.*?(\1(?::\d+)?)\\)?/gm;
    $addresses;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
