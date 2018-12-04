=head1 NAME

 iMSCP::Networking - High-level interface for networking configuration

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

package iMSCP::Networking;

use strict;
use warnings;
use Carp 'croak';
use File::Basename qw/ basename dirname /;
use iMSCP::Boolean;
use iMSCP::Net;
use Module::Load::Conditional 'can_load';
use parent qw/ Common::SingletonClass iMSCP::Provider::Networking::Interface /;

$Module::Load::Conditional::FIND_VERSION = 0;

=head1 DESCRIPTION

 High-level interface for networking configuration.

=head1 CLASS METHODS

=over 4

=item checkForOperability( )

 See iMSCP::Providers::Networking::Interface::checkForOperability

=cut

sub checkForOperability
{
    my ( $self ) = @_;

    TRUE;
}

=back

=head1 PUBLIC METHODS

=over 4

=item addIpAddr( \%data )

 See iMSCP::Providers::Networking::Interface::addIpAddress

=cut

sub addIpAddress
{
    my ( $self, $data ) = @_;

    defined $data && ref $data eq 'HASH' or croak( '$data parameter is not defined or invalid' );

    for my $param ( qw/ ip_id ip_card ip_address ip_config_mode / ) {
        defined $data->{$param} or croak( sprintf( 'The %s parameter is not defined', $param ));
    }

    $data->{'ip_id'} =~ /^\d+$/ or croak( 'ip_id parameter must be an integer' );
    $self->{'net'}->isValidAddr( $data->{'ip_address'} ) or croak( sprintf( 'The %s IP address is not valid', $data->{'ip_address'} ));
    $self->{'net'}->isKnownDevice( $data->{'ip_card'} ) or croak( sprintf( 'The %s network interface is unknown', $data->{'ip_card'} ));
    $self->{'net'}->upDevice( $data->{'ip_card'} ) if $self->{'net'}->isDeviceDown( $data->{'ip_card'} );

    local $data->{'ip_address'} = $self->{'net'}->normalizeAddr( $data->{'ip_address'} );

    my $addrVersion = $self->{'net'}->getAddrVersion( $data->{'ip_address'} );
    $data->{'ip_netmask'} //= $addrVersion eq 'ipv4' ? 24 : 64;

    if ( $data->{'ip_config_mode'} eq 'auto' ) {
        $self->{'net'}->delAddr( $data->{'ip_address'} ); # Cover update
        $self->{'net'}->addAddr( $data->{'ip_address'}, $data->{'ip_netmask'}, $data->{'ip_card'} );
    }

    $_->addIpAddress( $data ) for $self->_getProviders();

    $self;
}

=item removeIpAddress( \%data )

 See iMSCP::Providers::Networking::Interface::removeIpAddress

=cut

sub removeIpAddress
{
    my ( $self, $data ) = @_;

    defined $data && ref $data eq 'HASH' or croak( '$data parameter is not defined or invalid' );

    for my $param ( qw/ ip_id ip_card ip_address ip_config_mode / ) {
        defined $data->{$param} or croak( sprintf( 'The %s parameter is not defined', $param ));
    }

    $data->{'ip_id'} =~ /^\d+$/ or croak( 'ip_id parameter must be an integer' );

    local $data->{'ip_address'} = $self->{'net'}->normalizeAddr( $data->{'ip_address'} );

    $self->{'net'}->delAddr( $data->{'ip_address'} ) if $data->{'ip_config_mode'} eq 'auto';

    $_->removeIpAddress( $data ) for $self->_getProviders();

    $self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 See Common::Object

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'net'} = iMSCP::Net->getInstance();
    $self;
}

=item _getProviders( )

 Get networking configuration providers (persistence layer)

 Return List of networking configuration provider instances, die on failure

=cut

sub _getProviders
{
    CORE::state @providers;

    return @providers if @providers;

    for my $providerName ( map { basename( $_, '.pm' ) } glob( dirname( __FILE__ ) . '/Provider/Networking/Persistence/*.pm' ) ) {
        my $provider = "iMSCP::Provider::Networking::Persistence::${providerName}";
        can_load( modules => { $provider => undef } ) or die(
            sprintf( "Couldn't load the '%s' networking configuration provider: %s", $provider, $Module::Load::Conditional::ERROR )
        );
        next unless $provider->checkForOperability();
        push @providers, $provider->new();
    }

    @providers or die( 'No networking configuration provider (persistence layer) can operate on the system.' );
    @providers;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
