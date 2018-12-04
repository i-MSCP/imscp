=head1 NAME

 iMSCP::Provider::Persistence::Networking::Netplan - Netplan networking configuration provider (persistence layer).

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

package iMSCP::Provider::Networking::Persistence::Netplan;

use strict;
use warnings;
use Carp 'croak';
use iMSCP::Boolean;
use iMSCP::Debug 'getMessageByType';
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use parent qw/ Common::Object iMSCP::Provider::Networking::Interface /;

=head1 DESCRIPTION

 Netplan networking configuration provider (persistence layer).

 This provider is responsible for the netplan(5) networking configuration
 persistence by adding/removing netplan(5) configuration files, and by
 generating the underlying SYSTEMD-NETWORKD.SERVICE(8) configuration.
 
 See:
    https://wiki.ubuntu.com/Netplan
    https://netplan.io/

=head1 CLASS METHODS

=over 4

=item checkForOperability

 See iMSCP::Provider::Networking::Interface::checkForOperability

=cut

sub checkForOperability
{
    my ( $self ) = @_;

    return FALSE unless length iMSCP::ProgramFinder::find( 'netplan' );

    my $srvMngr = iMSCP::Service->getInstance();
    $srvMngr->hasService( 'systemd-networkd' ) && $srvMngr->isEnabled( 'systemd-networkd' );
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

=item removeIpAddress( \%data )

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
    my ( undef, $action, $data ) = @_;

    my $file = iMSCP::File->new( filename => "/etc/netplan/99-imscp-$data->{'ip_id'}.yaml" );

    if ( $action eq 'remove' ) {
        if ( -f $file->{'filename'} ) {
            $file->delFile() == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
        }
    } else {
        $file->set( <<"CONFIG" );
network:
  version: 2
  renderer: networkd
  ethernets:
    $data->{'ip_card'}:
      addresses:
       - $data->{'ip_address'}/$data->{'ip_netmask'}
CONFIG

        $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
    }

    my ( $stdout, $stderr );
    execute( [ 'netplan', 'generate' ], \$stdout, \$stderr ) == 0 or die( $stderr || 'Unknown error ' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
