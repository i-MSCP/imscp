=head1 NAME

 Modules::FtpUser - i-MSCP FtpUser module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2015-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Database;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP FtpUser module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
    'FtpUser';
}

=item process($userId)

 Process module

 Param int $userId Ftp user unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $userId) = @_;

    my $rs = $self->_loadData( $userId );
    return $rs if $rs;

    my @sql;
    if (grep($_ eq $self->{'status'}, ( 'toadd', 'tochange', 'toenable' ))) {
        $rs = $self->add();
        @sql = (
            'UPDATE ftp_users SET status = ? WHERE userid = ?',
                $rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'ok', $userId
        );
    } elsif ($self->{'status'} eq 'todisable') {
        $rs = $self->disable();
        @sql = (
            'UPDATE ftp_users SET status = ? WHERE userid = ?',
                $rs ? scalar getMessageByType( 'error' ) || 'Unknown error' : 'disabled', $userId
        );
    } elsif ($self->{'status'} eq 'todelete') {
        $rs = $self->delete();
        if ($rs) {
            @sql = (
                'UPDATE ftp_users SET status = ? WHERE userid = ?',
                scalar getMessageByType( 'error' ) || 'Unknown error',
                $userId
            );
        } else {
            @sql = ('DELETE FROM ftp_users WHERE userid = ?', $userId);
        }
    }

    my $ret = iMSCP::Database->factory()->doQuery( 'dummy', @sql );
    unless (ref $ret eq 'HASH') {
        error( $ret );
        return 1;
    }

    $rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData($userId)

 Load data

 Param int $userId Ftp user unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $userId) = @_;

    my $row = iMSCP::Database->factory()->doQuery( 'userid', 'SELECT * FROM ftp_users WHERE userid = ?', $userId );
    unless (ref $row eq 'HASH') {
        error( $row );
        return 1;
    }

    unless ($row->{$userId}) {
        error( sprintf( 'Ftp user record with ID %s has not been found in database', $userId ) );
        return 1;
    }

    %{$self} = (%{$self}, %{$row->{$userId}});
    0;
}

=item _getFtpdData($action)

 Data provider method for Ftpd servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getFtpdData
{
    my ($self, $action) = @_;

    return %{$self->{'ftpd'}} if $self->{'ftpd'};

    my $userName = my $groupName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.(
        $main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $self->{'admin_id'}
    );

    $self->{'ftpd'} = {
        OWNER_ID       => $self->{'admin_id'},
        USERNAME       => $self->{'userid'},
        PASSWORD_CRYPT => $self->{'passwd'},
        PASSWORD_CLEAR => $self->{'rawpasswd'},
        SHELL          => $self->{'shell'},
        HOMEDIR        => $self->{'homedir'},
        USER_SYS_GID   => $self->{'uid'},
        USER_SYS_GID   => $self->{'gid'},
        USER_SYS_NAME  => $userName,
        USER_SYS_GNAME => $groupName
    };
    %{$self->{'ftpd'}};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
