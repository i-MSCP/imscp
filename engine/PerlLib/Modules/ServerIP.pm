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
use iMSCP::Debug;
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

    my $rs = $self->_loadData( $ipId );
    return $rs if $rs;

    my @sql;
    if ($self->{'ip_status'} =~ /^to(?:add|change)$/) {
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
        error( sprintf( 'Unknown action requested for server IP with ID %s', $ipId ) );
        return 1;
    }

    my $qrs = iMSCP::Database->factory( )->doQuery( 'dummy', @sql );
    unless (ref $qrs eq 'HASH') {
        error( $qrs );
        return 1;
    }

    0;
}

=item add( )

 Add a server IP

 Return int 0 on success, other on failure

=cut

sub add
{
    my ($self) = @_;

    my $nicProvider = iMSCP::Provider::NetworkInterface->getInstance( );

    local $@;
    eval {
        $nicProvider->addIpAddr(
            {
                ip_id          => $self->{'ip_id'},
                ip_card        => $self->{'ip_card'},
                ip_address     => $self->{'ip_number'},
                ip_netmask     => $self->{'ip_netmask'},
                ip_config_mode => $self->{'ip_config_mode'}
            }
        );
        iMSCP::Net->getInstance( )->resetInstance( );
    };
    if ($@) {
        error( $@ );
        return 1;
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

    my $nicProvider = iMSCP::Provider::NetworkInterface->getInstance( );

    local $@;
    eval {
        $nicProvider->removeIpAddr(
            {
                ip_id          => $self->{'ip_id'},
                ip_card        => $self->{'ip_card'},
                ip_address     => $self->{'ip_number'},
                ip_netmask     => $self->{'ip_netmask'},
                ip_config_mode => $self->{'ip_config_mode'}
            }
        );
        iMSCP::Net->getInstance( )->resetInstance( );
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->SUPER::delete( );
}

=back

=head1 PRIVATES METHODS

=over 4

=item _loadData( $ipId )

 Load data

 Param int $ipId Server IP unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $ipId) = @_;

    my $rdata = iMSCP::Database->factory( )->doQuery( 'ip_id', 'SELECT * FROM server_ips WHERE ip_id = ?', $ipId );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }
    unless ($rdata->{$ipId}) {
        error( sprintf( 'Server IP with ID %s has not been found in database', $ipId ) );
        return 1;
    }

    %{$self} = (%{$self}, %{$rdata->{$ipId}});

    0;
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data, die on failure

=cut

sub _getData
{
    my ($self, $action) = @_;

    $self->{'_data'} = do {
        {
            ACTION                => $action,
            SERVER_IP_ID          => $self->{'ip_id'},
            SERVER_IP_CARD        => $self->{'ip_card'},
            SERVER_IP_ADDRESS     => $self->{'ip_number'},
            SERVER_IP_NETMASK     => $self->{'ip_netmask'},
            SERVER_IP_CONFIG_MODE => $self->{'ip_config_mode'}
        }
    } unless %{$self->{'_data'}};

    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
