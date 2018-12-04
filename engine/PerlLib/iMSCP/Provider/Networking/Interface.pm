=head1 NAME

 iMSCP::Provider::Networking::Interface - Interface for networking configuration providers

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

package iMSCP::Provider::Networking::Interface;

use strict;
use warnings;

=head1 DESCRIPTION

 Interface for networking configuration providers

=head1 CLASS METHODS

=over 4

=item checkForOperability( )

 Check for netwokring configuration provider operability

 Return boolean TRUE if the provider can operate, FALSE otherwise

=cut

sub checkForOperability
{
    my ( $self ) = @_;

    die( sprintf( 'The %s class must implement the checkForOperability() class method', $self ));
}

=back

=head1 PUBLIC METHODS

=over 4

=item addIpAddress( \%data )

 Add an IP address

 Param hashref \%data IP address data:
   ip_id          : IP address unique identifier
   ip_card        : Network card to which the IP address must be added
   ip_address     : Either an IPv4 or IPv6 address
   ip_netmask     : OPTIONAL Netmask (default: 24 for IPv4, 64 for IPv6)
   ip_config_mode : IP configuration mode (auto|manual)
 Return iMSCP::Provider::Networking::Interface, die on failure

=cut

sub addIpAddress
{
    my ( $self ) = @_;

    die( sprintf( 'The %s class must implement the addIpAddress() method', ref $self ));
}

=item removeIpAddr( \%data )

 Remove an IP address

 Param hashref \%data IP address data:
   ip_id          : IP address unique identifier
   ip_card        : Network card from which the IP address must be removed
   ip_address     : Either an IPv4 or IPv6 address
   ip_netmask     : OPTIONAL Netmask (default: 24 for IPv4, 64 for IPv6)
   ip_config_mode : IP configuration mode (auto|manual)
 Return iMSCP::Provider::Networking::Interface, die on failure

=cut

sub removeIpAddress
{
    my ( $self ) = @_;

    die( sprintf( 'The %s class must implement the removeIpAddress() method', ref $self ));
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
