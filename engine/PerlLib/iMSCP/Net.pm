=head1 NAME

 iMSCP::Net - Package allowing to manage network devices and IP addresses

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
use autouse 'Data::Validate::IP' => qw(is_ipv4 is_ipv6);
use Carp;
use iMSCP::Execute;
use Net::IP qw(:PROC);
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Package allowing to manage network devices and IP addresses.

=head1 PUBLIC METHODS

=over 4

=item getAddresses()

 Get addresses list

 Return array|string List of IP addresses

=cut

sub getAddresses
{
    my $self = shift;
    wantarray ? keys %{$self->{'addresses'}} : join ' ', keys %{$self->{'addresses'}};
}

=item addAddr($addr, $dev)

 Add the given IP to the given network device

 Param string $addr IP address
 Param string $dev Network device name
 Return int 0 on success, croak on failure

=cut

sub addAddr
{
    my ($self, $addr, $dev) = @_;
    $self->isValidAddr( $addr ) or croak( sprintf( 'Invalid IP address: %s', $addr ) );
    $self->isKnownDevice( $dev ) or croak( sprintf( 'Unknown network device: %s', $dev ) );
    my $cidr = ip_is_ipv4( $addr ) ? 32 : 64; # TODO should be configurable
    my ($stdout, $stderr);
    execute( "ip addr add $addr/$cidr dev $dev", \$stdout, \$stderr ) == 0 or croak( sprintf(
            'Could not add the %s IP address to the %s network device: %s', $addr, $dev, $stderr || 'Unknown error'
        ) );
    $self->{'addresses'}->{$addr} = {
        prefix_length => $cidr,
        version       => $self->getAddrVersion( $addr ),
        device        => $dev
    };
    0;
}

=item delAddr($addr)

 Delete the given IP

 Param string $addr IP address
 Return int 0 on success, croak on failure

=cut

sub delAddr
{
    my ($self, $addr) = @_;

    return 0 unless $self->isKnownAddr( $addr );

    my $dev = $self->{'addresses'}->{$addr}->{'device'};
    my $cidr = $self->{'addresses'}->{$addr}->{'prefix_length'};
    my ($stdout, $stderr);
    execute( "ip addr del $addr/$cidr dev $dev", \$stdout, \$stderr ) == 0 or croak( sprintf(
            'Could not delete the %s IP address from the %s network device: %s', $addr, $dev, $stderr || 'Unknown error'
        ) );
    delete $self->{'addresses'}->{$addr};
    0;
}

=item getAddrVersion($addr)

 Get version of the given IP (ipv4|ipv6)

 Param string $addr IP address
 Return string IP version, croak in case the given IP is invalid

=cut

sub getAddrVersion
{
    my ($self, $addr) = @_;
    $self->isValidAddr( $addr ) or croak( sprintf( 'Invalid IP address: %s', $addr ) );
    my $version = ip_get_version( $addr ) or croak( sprint( 'Could not guess version of the %s IP address', $addr ) );
    ip_get_version( $addr ) == 4 ? 'ipv4' : 'ipv6';
}

=item getAddrType($addr)

 Get type of the given IP (PUBLIC, PRIVATE, RESERVED...)

 Param string $addr IP address
 Return string IP type, croak in case the given IP is invalid

=cut

sub getAddrType
{
    my ($self, $addr) = @_;
    my $version = $self->getAddrVersion( $addr ) eq 'ipv4' ? 4 : 6;
    ip_iptype( ip_iptobin( ip_expand_address( $addr, $version ), $version ), $version ) or croak( sprintf(
            'Could not guess type of the %s IP address', $addr
        ) );
}

=item getAddrDevice($addr)

 Return the network device name to which the given IP belong to

 Param string $addr IP address
 Return string Network device name, croak if the given IP is either invalid or not known by this module

=cut

sub getAddrDevice
{
    my ($self, $addr) = @_;
    $self->isKnownAddr( $addr ) or croak( sprintf( 'Unknown IP address: %s', $addr ) );
    $self->{'addresses'}->{$addr}->{'device'};
}

=item getAddrDeviceLabel($addr)

 Return the network device label (if any) to which the given IP belong to

 Param string $addr IP address
 Return string Network device label, croak if the given IP is either invalid or not known by this module

=cut

sub getAddrDeviceLabel
{
    my ($self, $addr) = @_;
    $self->isKnownAddr( $addr ) or croak( sprintf( 'Unknown IP address: %s', $addr ) );
    $self->{'addresses'}->{$addr}->{'device_label'};
}

=item isKnownAddr($addr)

 Is the given IP known?

 Param string $addr IP address
 Return bool TRUE if the given IP is known, FALSE otherwise

=cut

sub isKnownAddr
{
    my ($self, $addr) = @_;
    exists $self->{'addresses'}->{$addr};
}

=item isValidAddr($addr)

 Check whether or not the given IP is valid

 Param string $addr IP address
 Return bool TRUE if valid, FALSE otherwise

=cut

sub isValidAddr
{
    my ($self, $addr) = @_;
    is_ipv4( $addr ) || is_ipv6( $addr );
}

=item normalizeAddr($addr)

 Normalize the given IP

 Param string $addr IP address
 Return string Normalized IP on success, croak on failure

=cut

sub normalizeAddr
{
    my ($self, $addr) = @_;
    $self->isValidAddr( $addr ) or croak( sprintf( 'Invalid IP address: %s', $addr ) );
    return $addr unless $self->getAddrVersion( $addr ) eq 'ipv6';
    ip_compress_address( $addr, 6 ) or croak( sprintf( 'Could not normalize the %s IP address', $addr ) );
}

=item expandAddr($addr)

 Expand the given IP

 Param string $addr IP address
 Return string Expanded IP on success, croak on failure

=cut

sub expandAddr
{
    my ($self, $addr) = @_;
    $self->isValidAddr( $addr ) or croak( sprintf( 'Invalid IP address: %s', $addr ) );
    return $addr unless $self->getAddrVersion( $addr ) eq 'ipv6';
    ip_expand_address( $addr, 6 ) or croak( sprintf( 'Could not expand the %s IP address', $addr ) );
}

=item getDevices()

 Get network devices list

 Return array|string List of devices

=cut

sub getDevices
{
    my $self = shift;
    wantarray ? keys %{$self->{'devices'}} : join ' ', keys %{$self->{'devices'}};
}

=item isKnownDevice($dev)

 Is the given network device known?

 Param string $dev Network device name
 Return bool TRUE if the network device is known, FALSE otherwise

=cut

sub isKnownDevice
{
    my ($self, $dev) = @_;
    exists( $self->{'devices'}->{$dev} ) ? 1 : 0;
}

=item upDevice($dev)

 Bring the given network device up

 Param string $dev Network device name
 Return int 0 on success, croak on failure

=cut

sub upDevice
{
    my ($self, $dev) = @_;
    $self->isKnownDevice( $dev ) or croak( sprintf( 'Unknown network device: %s', $dev ) );
    my ($stdout, $stderr);
    execute( "ip link set dev $dev up", \$stdout, \$stderr ) == 0 or die( sprintf(
            'Could not bring the %s network device up: %s', $dev, $stderr || 'Unknown error'
        ) );
    0;
}

=item downDevice($dev)

 Bring the given network device down

 Param string $dev Network device name
 Return int 0 on success, die/croak on failure

=cut

sub downDevice
{
    my ($self, $dev) = @_;
    $self->isKnownDevice( $dev ) or croak( sprintf( 'Unknown network device: %s', $dev ) );
    my ($stdout, $stderr);
    execute( "ip link set dev $dev down", \$stdout, \$stderr ) == 0 or die( sprintf(
            'Could not bring the %s network device down: %s', $dev, $stderr || 'Unknown error'
        ) );
    0;
}

=item isDeviceUp($dev)

 Is the given network device up?

 Param string $dev Network device name
 Return bool TRUE if the given device is known and up, FALSE otherwise

=cut

sub isDeviceUp
{
    my ($self, $dev) = @_;
    $self->{'devices'}->{$dev}->{'flags'} =~ /^(?:.*,)?UP(?:,.*)?$/ ? 1 : 0;
}

=item isDeviceDown($dev)

 Is the given device down?

 Param string $dev Network device name
 Return bool TRUE if the given device is known and down, FALSE otherwise

=cut

sub isDeviceDown
{
    my ($self, $dev) = @_;
    $self->{'devices'}->{$dev}->{'flags'} =~ /^(?:.*,)?UP(?:,.*)?$/ ? 0 : 1;
}

=item resetInstance

 Reset instance

 Return int 0 on success, die on failure

=cut

sub resetInstance
{
    my $self = shift;
    $self->_init();
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Net, die on failure

=cut

sub _init
{
    my $self = shift;
    $self->{'devices'} = $self->_extractDevices();
    $self->{'addresses'} = $self->_extractAddresses();
    $self;
}

=item _extractDevices()

 Extract network devices data

 Return hash A hash describing each device found, die on failure

=cut

sub _extractDevices
{
    my $self = shift;
    my ($stdout, $stderr);
    execute( 'ip -o link show', \$stdout, \$stderr ) == 0 or die( sprintf(
            'Could not extract network devices data: %s', $stderr || 'Unknown error'
        ) );
    my $devices = { };
    # Note: The (?:\@[^\s]+)? sub-pattern matches suffixes of interface names (@xxx) as they are displayed in the LXC
    # containers when using macvlan interfaces (and maybe some other interface types).
    # ATM, we discard those suffixes to be consistent with the frontEnd which use ifconfig to get interface names
    # FIXME: Does we should show full interface names in control panel instead?
    $devices->{$1}->{'flags'} = $2 while $stdout =~ /
        ^
        [^\s]+       # identifier
        :
        \s+
        (.*?)        # device name
        (?:@[^\s]+)? # device name prefix
        :
        \s+
        <(.*)>       # flags
    /gmx;
    $devices;
}

=item _extractAddresses()

 Extract addresses data

 Return hash A hash describing each IP found, die on failure

=cut

sub _extractAddresses
{
    my $self = shift;
    my ($stdout, $stderr);
    execute( 'ip -o addr show', \$stdout, \$stderr ) == 0 or die( sprintf(
            'Could not extract network devices data: %s', $stderr || 'Unknown error'
        ) );

    my $addresses = { };
    $addresses->{$3} = {
        device        => $1,
        version       => $2 eq 'inet' ? 'ipv4' : 'ipv6',
        prefix_length => $4,
        device_label  => $5 // ''
    } while ($stdout =~ /
        ^
        [^\s]+                    # identifier
        :
        \s+
        ([^\s]+)                  # device name
        \s+
        ([^\s]+)                  # protocol family identifier
        \s+
        (?:
            ([^\s]+)              # IP address
            (?:\s+peer\s+[^\s]+)? # peer address (pointopoint interfaces)
            \/
            ([\d]+)               # netmask in CIDR notation
        )
        \s+
        (?:
            .*?                   # optional broadcast address, scope information
            (\1(?::\d+)?)         # optional label
            \\
        )?
        /gmx);
    $addresses;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
