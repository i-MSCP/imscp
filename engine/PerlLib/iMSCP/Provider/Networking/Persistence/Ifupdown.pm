=head1 NAME

 iMSCP::Provider::Networking::Persistence::Ifupdown - Ifupdown networking configuration provider (persistence layer).

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

package iMSCP::Provider::Networking::Persistence::Ifupdown;

use strict;
use warnings;
use Carp 'croak';
use iMSCP::Boolean;
use iMSCP::Debug 'getMessageByType';
use iMSCP::File;
use iMSCP::Net;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use iMSCP::TemplateParser 'replaceBloc';
use parent qw/ Common::Object iMSCP::Provider::Networking::Interface /;

=head1 DESCRIPTION

 Ifupdown networking configuration provider (persistence layer).
 
 This provider is responsible for the ifupdown networking configuration
 persistence by adding/removing configuration stanzas the INTERFACES(5) file.

=head1 CLASS METHODS

=over 4

=item checkForOperability

 See iMSCP::Provider::Networking::Interface::checkForOperability

=cut

sub checkForOperability
{
    my ( $self ) = @_;

    return FALSE unless length iMSCP::ProgramFinder::find( 'ifup' ) && length iMSCP::ProgramFinder::find( 'ifdown' );

    my $srvMngr = iMSCP::Service->getInstance();
    $srvMngr->hasService( 'networking' ) && $srvMngr->isEnabled( 'networking' );
}

=back

=head1 PUBLIC METHODS

=over 4

=item addIpAddress( \%data )

 See iMSCP::Provider::Networking::Interface::addIpAddress

=cut

sub addIpAddress
{
    my ( $self, $data ) = @_;

    $self->_updateConfig( $data->{'ip_config_mode'} eq 'auto' ? 'add' : 'remove', $data );
    $self;
}

=item removeIpAddr( \%data )

 See iMSCP::Provider::Networking::Interface::removeIpAddress

=cut

sub removeIpAddress
{
    my ( $self, $data ) = @_;

    $self->_updateConfig( 'remove', $data );
    $self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _updateConfig( $action, \%data )

 Add or remove configuration for the given IP address

 Param string $action Action to perform (add|remove)
 Param hashref \%data IP data
 Return void, die on failure

=cut

sub _updateConfig
{
    my ( $self, $action, $data ) = @_;

    my $file = iMSCP::File->new( filename => '/etc/network/interfaces' );
    defined( my $fileCR = $file->getAsRef()) or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));

    my $ipID = $data->{'ip_id'}+1000;

    # Remove previous entry
    #  - We search also by ip_id for backward compatibility
    #  - Tag ending dot has been added lately, hence the optional match
    ${ $fileCR } = replaceBloc(
        qr/(?:^\n)?# i-MSCP \[(?:.*\Q:$ipID\E|\Q$data->{'ip_address'}\E)\] entry BEGIN\.?\n/m,
        qr/# i-MSCP \[(?:.*\Q:$ipID\E|\Q$data->{'ip_address'}\E)\] entry ENDING\.?\n/,
        '',
        ${ $fileCR }
    );

    if ( $action eq 'add' ) {
        my $addrVersion = iMSCP::Net->getInstance()->getAddrVersion( $data->{'ip_address'} );
        ${ $fileCR } .= <<"EOF";

# i-MSCP [$data->{'ip_address'}] entry BEGIN.
iface $data->{'ip_card'} @{ [ $addrVersion eq 'ipv4' ? 'inet' : 'inet6' ] } manual
  up   ip @{ [ $addrVersion eq 'ipv4' ? '-4' : '-6' ] } addr add $data->{'ip_address'}/$data->{'ip_netmask'} dev \$IFACE
  down ip addr del $data->{'ip_address'}/$data->{'ip_netmask'} dev \$IFACE
# i-MSCP [$data->{'ip_address'}] entry ENDING.
EOF
    }

    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
