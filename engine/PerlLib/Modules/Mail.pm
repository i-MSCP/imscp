=head1 NAME

 Modules::Mail - i-MSCP Mail module

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Modules::Mail;

use strict;
use warnings;
use iMSCP::Debug qw/ error getLastError warning /;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP Mail module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'Mail';
}

=item process( $mailId )

 Process module

 Param int $mailId Mail unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $mailId) = @_;

    my $rs = $self->_loadData( $mailId );
    return $rs if $rs;

    my @sql;
    if ( $self->{'status'} =~ /^to(?:add|change|enable)$/ ) {
        $rs = $self->add();
        @sql = ( 'UPDATE mail_users SET status = ? WHERE mail_id = ?', undef,
            ( $rs ? getLastError( 'error' ) || 'Unknown error' : 'ok' ), $mailId );
    } elsif ( $self->{'status'} eq 'todelete' ) {
        $rs = $self->delete();
        @sql = $rs
            ? ( 'UPDATE mail_users SET status = ? WHERE mail_id = ?', undef,
                ( getLastError( 'error' ) || 'Unknown error' ), $mailId )
            : ( 'DELETE FROM mail_users WHERE mail_id = ?', undef, $self->{'mail_id'} );

    } elsif ( $self->{'status'} eq 'todisable' ) {
        $rs = $self->disable();
        @sql = ( 'UPDATE mail_users SET status = ? WHERE mail_id = ?', undef,
            ( $rs ? getLastError( 'error' ) || 'Unknown error' : 'disabled' ), $mailId );
    } else {
        warning( sprintf( 'Unknown action (%s) for mail user (ID %d)', $self->{'status'}, $mailId ));
        return 0;
    }

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        $self->{'_dbh'}->do( @sql );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData( $mailId )

 Load data

 Param int $mailId Mail unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $mailId) = @_;

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        my $row = $self->{'_dbh'}->selectrow_hashref(
            '
                SELECT mail_id, mail_acc, mail_pass, mail_forward, mail_type, mail_auto_respond, status, quota,
                    mail_addr
                FROM mail_users
                WHERE mail_id = ?
            ',
            undef, $mailId
        );
        $row or die( sprintf( 'Data not found for mail user (ID %d)', $mailId ));
        %{$self} = ( %{$self}, %{$row} );
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
    my ($self, $action) = @_;

    $self->{'_data'} = do {
        my ($user, $domain) = split '@', $self->{'mail_addr'};

        {
            ACTION                  => $action,
            STATUS                  => $self->{'status'},
            DOMAIN_NAME             => $domain,
            MAIL_ACC                => $user,
            MAIL_PASS               => $self->{'mail_pass'},
            MAIL_FORWARD            => $self->{'mail_forward'},
            MAIL_TYPE               => $self->{'mail_type'},
            MAIL_QUOTA              => $self->{'quota'},
            MAIL_HAS_AUTO_RESPONDER => $self->{'mail_auto_respond'},
            MAIL_STATUS             => $self->{'status'},
            MAIL_ADDR               => $self->{'mail_addr'},
            MAIL_CATCHALL           => ( index( $self->{'mail_type'}, 'catchall' ) != -1 ) ? $self->{'mail_acc'} : undef
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
