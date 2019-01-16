=head1 NAME

 Modules::Htaccess - i-MSCP Htaccess module

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

package Modules::Htaccess;

use strict;
use warnings;
use Encode 'encode_utf8';
use File::Spec;
use iMSCP::Boolean;
use iMSCP::Debug qw/ error getMessageByType /;
use Try::Tiny;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Htaccess module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'Htaccess';
}

=item process( \%data )

 Process module

 Param hashref$data Htaccess data
 Return int 0 on success, die on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    $self->_loadData( $data->{'id'} );

    my @sql;
    if ( $self->{'status'} =~ /^to(?:add|change|enable)$/ ) {
        @sql = (
            'UPDATE htaccess SET status = ? WHERE id = ?', undef,
            ( $self->add() ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' : 'ok' ), $data->{'id'}
        );
    } elsif ( $self->{'status'} eq 'todisable' ) {
        @sql = (
            'UPDATE htaccess SET status = ? WHERE id = ?', undef,
            ( $self->disable() ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' : 'disabled' ), $data->{'id'}
        );
    } else {
        @sql = $self->delete() ? (
            'UPDATE htaccess SET status = ? WHERE id = ?', undef,
            getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error', $data->{'id'}
        ) : ( 'DELETE FROM htaccess WHERE id = ?', undef, $data->{'id'} );
    }

    $self->{'_conn'}->run( fixup => sub { $_->do( @sql ); } );
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData( $htaccessId )

 Load data

 Param int $htaccessId Htaccess unique identifier
 Return void, die on failure

=cut

sub _loadData
{
    my ( $self, $htaccessId ) = @_;

    my $row = $self->{'_conn'}->run( fixup => sub {
        $_->selectrow_hashref(
            "
                SELECT t3.id, t3.auth_type, t3.auth_name, t3.path, t3.status, t3.users, t3.groups, t4.domain_name, t4.domain_admin_id
                FROM (SELECT * FROM htaccess, (SELECT IFNULL(
                    (
                        SELECT group_concat(uname SEPARATOR ' ')
                        FROM htaccess_users
                        WHERE id regexp (CONCAT('^(', (SELECT REPLACE((SELECT user_id FROM htaccess WHERE id = ?), ',', '|')), ')\$'))
                        GROUP BY dmn_id
                    ), '') AS users) AS t1, (SELECT IFNULL(
                        (
                            SELECT group_concat(ugroup SEPARATOR ' ')
                            FROM htaccess_groups
                            WHERE id regexp (CONCAT('^(', (SELECT REPLACE((SELECT group_id FROM htaccess WHERE id = ?), ',', '|')), ')\$'))
                            GROUP BY dmn_id
                        ), '') AS groups) AS t2
                    ) AS t3
                JOIN domain AS t4 ON (t3.dmn_id = t4.domain_id)
                WHERE t3.id = ?
            ",
            undef, $htaccessId, $htaccessId, $htaccessId
        );
    } );
    $row or die( sprintf( 'Data not found for htaccess (ID %d)', $htaccessId ));
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
            STATUS          => $self->{'status'},
            DOMAIN_ADMIN_ID => $self->{'domain_admin_id'},
            USER            => $ug,
            GROUP           => $ug,
            AUTH_TYPE       => $self->{'auth_type'},
            AUTH_NAME       => encode_utf8( $self->{'auth_name'} ),
            AUTH_PATH       => File::Spec->canonpath( "$::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}/$self->{'path'}" ),
            HOME_PATH       => File::Spec->canonpath( "$::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}" ),
            DOMAIN_NAME     => $self->{'domain_name'},
            HTUSERS         => $self->{'users'},
            HTGROUPS        => $self->{'groups'}
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
