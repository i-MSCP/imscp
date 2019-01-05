=head1 NAME

 Modules::Htgroup - i-MSCP Htgroup module

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

package Modules::Htgroup;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug qw/ error getMessageByType /;
use Try::Tiny;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Htgroup module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'Htgroup';
}

=item process( \%data )

 Process module

 Param hashref \%data Htgroup data
 Return int 0 on success, other on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    try {
        $self->_loadData( $data->{'id'} );

        my ( @sql, $rs );
        if ( $self->{'status'} =~ /^to(?:add|change|enable)$/ ) {
            $rs = $self->add();
            @sql = (
                'UPDATE htaccess_groups SET status = ? WHERE id = ?', undef,
                ( $rs ? getMessageByType( 'error', { amount => 1 } ) || 'Unknown error' : 'ok' ), $data->{'id'}
            );
        } elsif ( $self->{'status'} eq 'todisable' ) {
            $rs = $self->disable();
            @sql = (
                'UPDATE htaccess_groups SET status = ? WHERE id = ?', undef,
                ( $rs ? getMessageByType( 'error', { amount => 1 } ) || 'Unknown error' : 'disabled' ), $data->{'id'}
            );
        } else {
            $rs = $self->delete();
            @sql = $rs ? (
                'UPDATE htaccess_groups SET status = ? WHERE id = ?', undef,
                getMessageByType( 'error', { amount => 1 } ) || 'Unknown error', $data->{'id'}
            ) : ( 'DELETE FROM htaccess_groups WHERE id = ?', undef, $data->{'id'} );
        }
        $self->{'_conn'}->run( fixup => sub { $_->do( @sql ); } );
        $rs;
    } catch {
        error( $_ );
        1;
    };
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData( $htgroupId )

 Load data

 Param int $htgroupId $Htgroup unique identifier
 Return void, die on failure

=cut

sub _loadData
{
    my ( $self, $htgroupId ) = @_;

    my $row = $self->{'_conn'}->run( fixup => sub {
        $_->selectrow_hashref(
            "
                SELECT t2.id, t2.ugroup, t2.status, t2.users, t3.domain_name, t3.domain_admin_id, t3.web_folder_protection
                FROM (SELECT * from htaccess_groups, (SELECT IFNULL(
                    (
                        SELECT group_concat(uname SEPARATOR ' ')
                        FROM htaccess_users
                        WHERE id regexp (CONCAT('^(', (SELECT REPLACE((SELECT members FROM htaccess_groups WHERE id = ?), ',', '|')), ')\$'))
                        GROUP BY dmn_id
                    ), '') AS users) AS t1
                ) AS t2
                JOIN domain AS t3 ON (t2.dmn_id = t3.domain_id)
                WHERE id = ?
            ",
            undef, $htgroupId, $htgroupId
        );
    } );
    $row or die( sprintf( 'Data not found for htgroup (ID %d)', $htgroupId ));
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
            ACTION                => $action,
            STATUS                => $self->{'status'},
            DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
            USER                  => $ug,
            GROUP                 => $ug,
            WEB_DIR               => "$::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}",
            HTGROUP_NAME          => $self->{'ugroup'},
            HTGROUP_USERS         => $self->{'users'},
            HTGROUP_DMN           => $self->{'domain_name'},
            WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
        }
    } unless %{ $self->{'_data'} };

    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
