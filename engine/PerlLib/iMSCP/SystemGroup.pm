=head1 NAME

 iMSCP::SystemGroup - i-MSCP library allowing to add and delete UNIX groups

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
use iMSCP::Debug;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP library allowing to add and delete UNIX groups.

=head1 PUBLIC METHODS

=over 4

=item addSystemGroup($groupname [, systemgroup = 0 ])

 Add group

 Param string $groupname Group name
 Param int systemgroup Whether or not a system group must be created
 Return int 0 on success, other on failure

=cut

sub addSystemGroup
{
    my ($self, $groupname, $systemgroup) = @_;

    unless (defined $groupname) {
        error( '$groupname parameter is not defined' );
        return 1;
    }

    return 0 if getgrnam( $groupname );

    $systemgroup = $systemgroup ? '-r' : '';

    my @cmd = ('groupadd', $^O !~ /bsd$/ ? $systemgroup : '', escapeShell( $groupname ));
    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    debug( $stderr ) if $stderr && !$rs;
    $rs;
}

=item delSystemGroup($groupname)

 Delete group

 Param string $groupname Group name
 Return int 0 on success, other on failure

=cut

sub delSystemGroup
{
    my ($self, $groupname) = @_;

    unless (defined $groupname) {
        error( '$groupname parameter is not defined' );
        return 1;
    }

    return unless getgrnam( $groupname );

    my $rs = execute( 'groupdel '.escapeShell( $groupname ), \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    debug( $stderr ) if $stderr && !$rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
