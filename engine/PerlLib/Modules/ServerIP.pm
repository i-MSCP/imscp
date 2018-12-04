=head1 NAME

 Modules::ServerIP - i-MSCP ServerIP module

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Modules::ServerIP;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug qw/ error getLastError getMessageByType warning /;
use iMSCP::Networking;
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
    my ( $self, $ipId ) = @_;

    my $rs = $self->_loadData( $ipId );
    return $rs if $rs;

    my @sql;
    if ( $self->{'_data'}->{'ip_status'} =~ /^to(?:add|change)$/ ) {
        $rs = $self->add();
        @sql = ( 'UPDATE server_ips SET ip_status = ? WHERE ip_id = ?', undef, $rs ? getLastError( 'error' ) || 'Unknown error' : 'ok', $ipId );
    } elsif ( $self->{'_data'}->{'ip_status'} eq 'todelete' ) {
        $rs = $self->delete();
        @sql = $rs
            ? ( 'UPDATE server_ips SET ip_status = ? WHERE ip_id = ?', undef, getLastError( 'error' ) || 'Unknown error', $ipId )
            : ( 'DELETE FROM server_ips WHERE ip_id = ?', undef, $ipId );
    } else {
        warning( sprintf( 'Unknown action (%s) for server IP with ID %s', $self->{'_data'}->{'ip_status'}, $ipId ));
        return 0;
    }

    eval {
        local $self->{'_dbh'}->{'RaiseError'} = TRUE;
        $self->{'_dbh'}->do( @sql );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs;
}

=item add( )

 Add (or update) a server IP address

 Return int 0 on success, other on failure

=cut

sub add
{
    my ( $self ) = @_;

    eval {
        my $ret = $self->{'eventManager'}->trigger( 'beforeAddIpAddr', $self->{'_data'} );

        unless ( $ret || $self->{'_data'}->{'ip_card'} eq 'any' || $self->{'_data'}->{'ip_address'} eq '0.0.0.0' ) {
            iMSCP::Networking->getInstance()->addIpAddress( $self->{'_data'} );
        }

        $ret ||= $self->SUPER::add();
        $ret ||= $self->{'eventManager'}->trigger( 'afterAddIpAddr', $self->{'_data'} );
        $ret == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item delete( )

 Delete a server IP address

 Return int 0 on success, other on failure

=cut

sub delete
{
    my ( $self ) = @_;

    eval {
        my $ret = $self->{'eventManager'}->trigger( 'beforeRemoveIpAddr', $self->{'_data'} );

        unless ( $ret || $self->{'_data'}->{'ip_card'} eq 'any' || $self->{'_data'}->{'ip_address'} eq '0.0.0.0' ) {
            iMSCP::Networking->getInstance()->removeIpAddress( $self->{'_data'} );
        }

        $ret ||= $self->SUPER::delete();
        $ret ||= $self->{'eventManager'}->trigger( 'afterRemoveIpAddr', $self->{'_data'} );
        $ret == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
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
    my ( $self, $ipId ) = @_;

    eval {
        local $self->{'_dbh'}->{'RaiseError'} = TRUE;
        $self->{'_data'} = $self->{'_dbh'}->selectrow_hashref(
            'SELECT ip_id, ip_card, ip_number AS ip_address, ip_netmask, ip_config_mode, ip_status FROM server_ips WHERE ip_id = ?', undef, $ipId
        );
        $self->{'_data'} or die( sprintf( 'Data not found for server IP address (ID %d)', $ipId ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data

=cut

sub _getData
{
    my ( $self, $action ) = @_;

    $self->{'_data'}->{'action'} = $action;
    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
