=head1 NAME

 Modules::ServerIP - i-MSCP ServerIP module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Modules::ServerIP;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug qw/ error getLastError getMessageByType /;
use iMSCP::Net;
use iMSCP::Provider::NetworkInterface;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Modules::ServerIP module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'ServerIP';
}

=item process( $ipId )

 Process module

 Param string $ipId Server IP unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $ipId) = @_;

    local $@;
    eval {
        $self->{'_data'} = $self->_loadData( $ipId );

        my @sql;
        if ($self->{'_data'}->{'ip_status'} =~ /^to(?:add|change)$/) {
            @sql = (
                'UPDATE server_ips SET ip_status = ? WHERE ip_id = ?',
                ($self->add( ) ? getLastError( 'error' ) || 'Unknown error' : 'ok'),
                $ipId
            );
        } elsif ($self->{'ip_status'} eq 'todelete') {
            if ($self->delete( )) {
                @sql = (
                    'UPDATE server_ips SET ip_status = ? WHERE ip_id = ?',
                    getLastError( 'error' ) || 'Unknown error', $ipId
                );
            } else {
                @sql = ('DELETE FROM server_ips WHERE ip_id = ?', $ipId);
            }
        } else {
            die( sprintf( 'Unknown action requested for server IP with ID %s', $ipId ) );
        }

        my $qrs = iMSCP::Database->factory( )->doQuery( 'dummy', @sql );
        ref $qrs eq 'HASH' or die( $qrs );
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item add( )

 Add (or update) a server IP

 Return int 0 on success, other on failure

=cut

sub add
{
    my ($self) = @_;
    
    unless ($self->{'_data'}->{'ip_card'} eq 'any' || $self->{'_data'}->{'ip_address'} eq '0.0.0.0') {
        local $@;
        eval {
            $self->{'eventManager'}->trigger( 'beforeAddIpAddr', $self->{'_data'} ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
            iMSCP::Provider::NetworkInterface->getInstance( )->addIpAddr( $self->{'_data'} );
            $self->{'eventManager'}->trigger( 'afterAddIpAddr', $self->{'_data'} ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
            
            iMSCP::Net->getInstance( )->resetInstance( );
        };
        if ($@) {
            error( $@ );
            return 1;
        }
    }

    $self->SUPER::add( );
}

=item delete( )

 Delete a server IP

 Return int 0 on success, other on failure

=cut

sub delete
{
    my ($self) = @_;

    unless ($self->{'_data'}->{'ip_card'} eq 'any' || $self->{'_data'}->{'ip_address'} eq '0.0.0.0') {
        local $@;
        eval {
            $self->{'eventManager'}->trigger( 'beforeRemoveIpAddr', $self->{'_data'} ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
            iMSCP::Provider::NetworkInterface->getInstance( )->removeIpAddr( $self->{'_data'} );
            $self->{'eventManager'}->trigger( 'afterRemoveIpAddr', $self->{'_data'} ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
            iMSCP::Net->getInstance( )->resetInstance( );
        };
        if ($@) {
            error( $@ );
            return 1;
        }
    }

    $self->SUPER::delete( );
}

=back

=head1 PRIVATES METHODS

=over 4

=item _loadData( $ipId )

 Load data

 Param int $ipId Server IP unique identifier
 Return data on success, die on failure

=cut

sub _loadData
{
    my (undef, $ipId) = @_;

    my $qrs = iMSCP::Database->factory( )->doQuery(
        'ip_id',
        '
            SELECT ip_id, ip_card, ip_number AS ip_address, ip_netmask, ip_config_mode, ip_status
            FROM server_ips
            WHERE ip_id = ?
        ',
        $ipId
    );

    ref $qrs eq 'HASH' or die( $qrs );
    $qrs->{$ipId} or die( sprintf( 'Server IP with ID %s has not been found in database', $ipId ) );
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data, die on failure

=cut

sub _getData
{
    my ($self, $action) = @_;

    $self->{'_data'}->{'action'} = $action;
    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
