=head1 NAME

 Modules::Htpasswd - i-MSCP Htusers module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Modules::Htpasswd;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug qw/ error getMessageByType /;
use Try::Tiny;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Htpasswd module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'Htpasswd';
}

=item process( \%data )

 Process module

 Param hashref \%data Htuser data
 Return int 0 on success, die on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    $self->_loadData( $data->{'id'} );

    my @sql;
    if ( $self->{'status'} =~ /^to(?:add|change|enable)$/ ) {
        @sql = (
            'UPDATE htaccess_users SET status = ? WHERE id = ?', undef,
            ( $self->add() ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' : 'ok' ), $data->{'id'}
        );
    } elsif ( $self->{'status'} eq 'todisable' ) {
        @sql = (
            'UPDATE htaccess_users SET status = ? WHERE id = ?', undef,
            ( $self->disable() ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' : 'disabled' ), $data->{'id'}
        );
    } else {
        @sql = $self->delete() ? (
            'UPDATE htaccess_users SET status = ? WHERE id = ?', undef,
            getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error', $data->{'id'}
        ) : ( 'DELETE FROM htaccess_users WHERE id = ?', undef, $data->{'id'} );
    }

    $self->{'_conn'}->run( fixup => sub { $_->do( @sql ); } );
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData( $htuserId )

 Load data

 Param int $htuserId Htuser unique identifier
 Return void, die on failure

=cut

sub _loadData
{
    my ( $self, $htuserId ) = @_;

    my $row = $self->{'_conn'}->run( fixup => sub {
        $_->selectrow_hashref(
            '
                SELECT t1.uname, t1.upass, t1.status, t1.id, t2.domain_name, t2.domain_admin_id, t2.web_folder_protection
                FROM htaccess_users AS t1
                JOIN domain AS t2 ON (t1.dmn_id = t2.domain_id)
                WHERE t1.id = ?
            ',
            undef, $htuserId
        );
    } );
    $row or die( sprintf( 'Data not found for htuser (ID %d)', $htuserId ));
    %{ $self } = ( %{ $self }, %{ $row } );
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data

=cut

sub _getData
{
    my ( $self, $action ) = @_;

    $self->{'_data'} = do {
        my $ug = $::imscpConfig{'SYSTEM_USER_PREFIX'} . ( $::imscpConfig{'SYSTEM_USER_MIN_UID'}+$self->{'domain_admin_id'} );
        {
            STATUS                => $self->{'status'},
            DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
            USER                  => $ug,
            GROUP                 => $ug,
            WEB_DIR               => "$::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}",
            HTUSER_NAME           => $self->{'uname'},
            HTUSER_PASS           => $self->{'upass'},
            HTUSER_DMN            => $self->{'domain_name'},
            WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
        }
    } unless %{ $self->{'_data'} };

    $self->{'_data'}->{'ACTION'} = $action;
    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
