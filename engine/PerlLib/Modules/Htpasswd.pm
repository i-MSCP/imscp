=head1 NAME

 Modules::Htpasswd - i-MSCP Htusers module

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

package Modules::Htpasswd;

use strict;
use warnings;
use iMSCP::Debug qw/ error getLastError warning /;
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

=item process( $htuserId )

 Process module

 Param int $htuserId Htuser unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $htuserId) = @_;

    my $rs = $self->_loadData( $htuserId );
    return $rs if $rs;

    my @sql;
    if ( $self->{'status'} =~ /^to(?:add|change|enable)$/ ) {
        $rs = $self->add();
        @sql = ( 'UPDATE htaccess_users SET status = ? WHERE id = ?', undef,
            ( $rs ? getLastError( 'error' ) || 'Unknown error' : 'ok' ), $htuserId );
    } elsif ( $self->{'status'} eq 'todisable' ) {
        $rs = $self->disable();
        @sql = ( 'UPDATE htaccess_users SET status = ? WHERE id = ?', undef,
            ( $rs ? getLastError( 'error' ) || 'Unknown error' : 'disabled' ), $htuserId );
    } elsif ( $self->{'status'} eq 'todelete' ) {
        $rs = $self->delete();
        @sql = $rs
            ? ( 'UPDATE htaccess_users SET status = ? WHERE id = ?', undef,
                getLastError( 'error' ) || 'Unknown error', $htuserId )
            : ( 'DELETE FROM htaccess_users WHERE id = ?', undef, $htuserId );
    } else {
        warning( sprintf( 'Unknown action (%s) for htuser (ID %d)', $self->{'status'}, $htuserId ));
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

=item _loadData( $htuserId )

 Load data

 Param int $htuserId Htuser unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $htuserId) = @_;

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        my $row = $self->{'_dbh'}->selectrow_hashref(
            '
                SELECT t1.uname, t1.upass, t1.status, t1.id, t2.domain_name, t2.domain_admin_id,
                    t2.web_folder_protection
                FROM htaccess_users AS t1
                JOIN domain AS t2 ON (t1.dmn_id = t2.domain_id)
                WHERE t1.id = ?
            ',
            undef, $htuserId
        );
        $row or die( sprintf( 'Data not found for htuser (ID %d)', $htuserId ));
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
        my $groupName = my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
            ( $main::imscpConfig{'SYSTEM_USER_MIN_UID'}+$self->{'domain_admin_id'} );

        {
            ACTION                => $action,
            STATUS                => $self->{'status'},
            DOMAIN_ADMIN_ID       => $self->{'domain_admin_id'},
            USER                  => $userName,
            GROUP                 => $groupName,
            WEB_DIR               => "$main::imscpConfig{'USER_WEB_DIR'}/$self->{'domain_name'}",
            HTUSER_NAME           => $self->{'uname'},
            HTUSER_PASS           => $self->{'upass'},
            HTUSER_DMN            => $self->{'domain_name'},
            WEB_FOLDER_PROTECTION => $self->{'web_folder_protection'}
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
