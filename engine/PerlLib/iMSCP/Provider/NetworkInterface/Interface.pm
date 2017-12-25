=head1 NAME

 iMSCP::Provider::NetworkInterface::Interface - Interface for network interface providers

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

package iMSCP::Provider::NetworkInterface::Interface;

use strict;
use warnings;
use Carp;

=head1 DESCRIPTION

 Interface for network interface provider.

=head1 PUBLIC METHODS

=over 4

=item addNetworkCard( \%data )

 Add a network card

 Param hash \%data Network card data
 Return iMSCP::Provider::NetworkInterface::Interface, die on failure

=cut

sub addNetworkCard
{
    confess 'not implemented';
}

=item removeNetworkCard( \%data )

 Remove a network card

 Param hash \%data Network card data
 Return iMSCP::Provider::NetworkInterface::Interface, die on failure

=cut

sub removeNetworkCard
{
    confess 'not implemented';
}

=item addIpAddr( \%data )

 Add an IP address

 Param hash \%data IP address data:
   ip_id          : IP address unique identifier
   ip_card        : Network card to which the IP address must be added
   ip_address     : Either an IPv4 or IPv6 address
   ip_netmask     : OPTIONAL Netmask (default: 32 for IPv4, 128 for IPv6)
   ip_config_mode : IP configuration mode (auto|manual)
 Return iMSCP::Provider::NetworkInterface::Interface, die on failure

=cut

sub addIpAddr
{
    confess 'not implemented';
}

=item removeIpAddr( \%data )

 Remove an IP address

 Param hash \%data IP address data:
   ip_id          : IP address unique identifier
   ip_card        : Network card from which the IP address must be removed
   ip_address     : Either an IPv4 or IPv6 address
   ip_netmask     : OPTIONAL Netmask (default: 32 for IPv4, 128 for IPv6)
   ip_config_mode : IP configuration mode (auto|manual)
 Return iMSCP::Provider::NetworkInterface::Interface, die on failure

=cut

sub removeIpAddr
{
    confess 'not implemented';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
