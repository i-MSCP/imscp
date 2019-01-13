=head1 NAME

 Modules::FtpUser - i-MSCP FtpUser module

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

package Modules::FtpUser;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug qw/ error getMessageByType /;
use Try::Tiny;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP FtpUser module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'FtpUser';
}

=item process( \%data )

 Process module

 Param hashref \%data Ftp user data
 Return int 0 on success, die on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    $self->_loadData( $data->{'id'} );

    my ( @sql );
    if ( $self->{'status'} =~ /^to(?:add|change|enable)$/ ) {
        @sql = (
            'UPDATE ftp_users SET status = ? WHERE userid = ?', undef,
            ( $self->add() ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' : 'ok' ), $data->{'id'}
        );
    } elsif ( $self->{'status'} eq 'todisable' ) {
        @sql = (
            'UPDATE ftp_users SET status = ? WHERE userid = ?', undef,
            ( $self->disable() ? getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error' : 'disabled' ), $data->{'id'}
        );
    } else {
        @sql = $self->delete() ? (
            'UPDATE ftp_users SET status = ? WHERE userid = ?', undef,
            getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error', $data->{'id'}
        ) : ( 'DELETE FROM ftp_users WHERE userid = ?', undef, $data->{'id'} );
    }

    $self->{'_conn'}->run( fixup => sub { $_->do( @sql ); } );
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData( $ftpUserId )

 Load data

 Param int $ftpUserId Ftp user unique identifier
 Return void, die on failure

=cut

sub _loadData
{
    my ( $self, $ftpUserId ) = @_;

    my $row = $self->{'_conn'}->run( fixup => sub { $_->selectrow_hashref( 'SELECT * FROM ftp_users WHERE userid = ?', undef, $ftpUserId ); } );
    $row or die( sprintf( 'Data not found for ftp user (ID %d)', $ftpUserId ));
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
        my $ug = $::imscpConfig{'SYSTEM_USER_PREFIX'} . ( $::imscpConfig{'SYSTEM_USER_MIN_UID'}+$self->{'admin_id'} );
        {
            ACTION         => $action,
            STATUS         => $self->{'status'},
            OWNER_ID       => $self->{'admin_id'},
            USERNAME       => $self->{'userid'},
            PASSWORD_CRYPT => $self->{'passwd'},
            PASSWORD_CLEAR => $self->{'rawpasswd'},
            SHELL          => $self->{'shell'},
            HOMEDIR        => $self->{'homedir'},
            USER_SYS_GID   => $self->{'uid'},
            USER_SYS_GID   => $self->{'gid'},
            USER_SYS_NAME  => $ug,
            USER_SYS_GNAME => $ug
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
