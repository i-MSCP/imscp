=head1 NAME

 iMSCP::Provider::NetworkInterface - Facade to network interface providers

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2015-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Provider::NetworkInterface;

use strict;
use warnings;
use Carp;
use iMSCP::EventManager;
use iMSCP::LsbRelease;
use Module::Load::Conditional qw/ check_install can_load /;
use Scalar::Util 'blessed';
use parent qw/ Common::SingletonClass iMSCP::Provider::NetworkInterface::Interface /;

$Module::Load::Conditional::FIND_VERSION = 0;

=head1 DESCRIPTION

 Facade to network interface providers.

=head1 PUBLIC METHODS

=over 4

=item addIpAddr(\%data)

 Add an IP address

 Param hash \%data IP address data:
   id: int IP address unique identifier
   ip_card: string Network card to which the IP address must be added
   ip_address: string Either an IPv4 or IPv6 address
   netmask: OPTIONAL string Netmask (default: auto)
   broadcast: OPTIONAL string Broadcast (default: auto)
   gateway: OPTIONAL string Gateway (default: auto)
 Return iMSCP::Provider::NetworkInterface, die on failure

=cut

sub addIpAddr
{
    my ($self, $data) = @_;

    $self->{'eventManager'}->trigger('beforeAddIpAddr', $data);
    $self->getProvider()->addIpAddr($data);
    $self->{'eventManager'}->trigger('afterAddIpAddr', $data);
    $self;
}

=item removeIpAddr(\%data)

 Remove an IP address

 Param hash \%data IP address data:
   id: int IP address unique identifier
   ip_card: string Network card from which the IP address must be removed
   ip_address: string Either an IPv4 or IPv6 address
 Return iMSCP::Provider::NetworkInterface, die on failure

=cut

sub removeIpAddr
{
    my ($self, $data) = @_;

    $self->{'eventManager'}->trigger('beforeRemoveIpAddr', $data);
    $self->getProvider()->removeIpAddr($data);
    $self->{'eventManager'}->trigger('afterRemoveIpAddr', $data);
    $self;
}

=item getProvider

 Get network interface provider

 Return iMSCP::Provider::NetworkInterface::Interface, croak on failure

=cut

sub getProvider
{
    my $self = shift;

    return $self->{'_provider'} if $self->{'_provider'};

    my $provider = 'iMSCP::Provider::NetworkInterface::'.iMSCP::LsbRelease->getInstance->getId('short');

    can_load(modules => { $provider => undef }) or croak(sprintf(
        'Could not load %s network interface provider: %s', $provider, $Module::Load::Conditional::ERROR
    ));

    $self->setProvider($provider->new());
}

=item setProvider($provider)

 Set network interface provider

 Param iMSCP::Provider::NetworkInterface::Interface $provider
 Return iMSCP::Provider::NetworkInterface::Interface, croak on failure

=cut

sub setProvider
{
    my ($self, $provider) = @_;

    blessed($provider) && $provider->isa('iMSCP::Provider::NetworkInterface::Interface') or croak(
        '$provider parameter is either not defined or not an iMSCP::Provider::NetworkInterface::Interface object'
    );

    $self->{'_provider'} = $provider;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Provider::NetworkInterface

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
