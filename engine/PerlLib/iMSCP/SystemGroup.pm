=head1 NAME

 iMSCP::SystemGroup - i-MSCP library for management of UNIX groups

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package iMSCP::SystemGroup;

use strict;
use warnings;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Execute 'execute';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 iMSCP::SystemGroup - i-MSCP library for management of UNIX groups.

=head1 PUBLIC METHODS

=over 4

=item addSystemGroup( $groupName [, isSystemGroup = FALSE ] )

 Add group

 Param string $groupName Group name
 Param bool $isSystemGroup Flag indication whether or not $groupname must be created as a system group
 Return int 0 on success, other on failure

=cut

sub addSystemGroup
{
    my ( undef, $groupName, $isSystemGroup ) = @_;

    unless ( length $groupName ) {
        error( 'Missing or invalid $groupName parameter' );
        return 1;
    }

    if ( $groupName eq $::imscpConfig{'ROOT_GROUP'} ) {
        error( sprintf( '%s group is prohibited', $::imscpConfig{'ROOT_GROUP'} ));
        return 1;
    }

    my $rs = execute( [ '/usr/sbin/groupadd', '-f', ( $isSystemGroup ? '-r' : () ), $groupName ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item delSystemGroup( $groupname )

 Delete group

 Param string $groupname Group name
 Return int 0 on success, other on failure

=cut

sub delSystemGroup
{
    my ( undef, $groupName ) = @_;

    unless ( length $groupName ) {
        error( 'Missing or invalid $groupName parameter' );
        return 1;
    }

    if ( $groupName eq $::imscpConfig{'ROOT_GROUP'} ) {
        error( sprintf( '%s group deletion is prohibited', $::imscpConfig{'ROOT_GROUP'} ));
        return 1;
    }

    my $rs = execute( [ '/usr/sbin/groupdel', $groupName ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    unless ( grep ( $_ == $rs, 0, 6 ) ) {
        error( $stderr || 'Unknown error' );
        return $rs;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
