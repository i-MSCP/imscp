=head1 NAME

 iMSCP::Provider::NetworkInterface - High-level interface for network interface providers

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

package iMSCP::Provider::NetworkInterface;

use strict;
use warnings;
use iMSCP::LsbRelease;
use Module::Load::Conditional qw/ can_load /;
use Scalar::Util 'blessed';
use parent qw/ Common::SingletonClass iMSCP::Provider::NetworkInterface::Interface /;

$Module::Load::Conditional::FIND_VERSION = 0;

=head1 DESCRIPTION

 High-level interface for network interface providers.

=head1 PUBLIC METHODS

=over 4

=item addIpAddr( \%data )

 See iMSCP::Provider::NetworkInterface::Interface

=cut

sub addIpAddr
{
    my ($self, $data) = @_;

    $self->getProvider()->addIpAddr( $data );
    $self;
}

=item removeIpAddr( \%data )

 See iMSCP::Provider::NetworkInterface::Interface

=cut

sub removeIpAddr
{
    my ($self, $data) = @_;

    $self->getProvider()->removeIpAddr( $data );
    $self;
}

=item getProvider( )

 Get network interface provider

 Return iMSCP::Provider::NetworkInterface, die on failure

=cut

sub getProvider
{
    my ($self) = @_;

    $self->{'_provider'} ||= do {
        my $provider = __PACKAGE__ . '::' . iMSCP::LsbRelease->getInstance->getId( 'short' );
        can_load( modules => { $provider => undef } ) or die(
            sprintf( "Couldn't load `%s' network interface provider: %s", $provider, $Module::Load::Conditional::ERROR )
        );
        $provider = $provider->new();
        $self->setProvider( $provider );
        $provider;
    };
}

=item setProvider( $provider )

 Set network interface provider

 Param iMSCP::Provider::NetworkInterface::Interface $provider
 Return iMSCP::Provider::NetworkInterface, die on failure

=cut

sub setProvider
{
    my ($self, $provider) = @_;

    blessed( $provider ) && $provider->isa( 'iMSCP::Provider::NetworkInterface::Interface' ) or die(
        '$provider parameter is either not defined or not an iMSCP::Provider::NetworkInterface::Interface object'
    );
    $self->{'_provider'} = $provider;
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
