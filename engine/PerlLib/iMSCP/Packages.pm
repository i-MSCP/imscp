=head1 NAME

 iMSCP::Packages - Library for loading and retrieval of i-MSCP packages

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

package iMSCP::Packages;

use strict;
use warnings;
use File::Basename 'dirname';
use iMSCP::Cwd '$CWD';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Library for loading and retrieval of i-MSCP packages.

=head1 PUBLIC METHODS

=over 4

=item getList( )

 Get list of packages sorted in descending order of priority

 Return List of packages

=cut

sub getList
{
    @{ $_[0]->{'__packages__'} };
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance
 
 Return iMSCP::Packages

=cut

sub _init
{
    my ( $self ) = @_;

    local $CWD = dirname( __FILE__ ) . '/../Package/';

    s/(.*)\.pm$/Package::$1/ for @{ $self->{'__packages__'} } = grep (
        !/^AbstractPackageCollection\.pm$/, <*.pm>
    );

    eval "require $_; 1" or die( sprintf(
        "Couldn't load %s package class: %s", $_, $@
    )) for @{ $self->{'__packages__'} };

    @{ $self->{'__packages__'} } = sort {
        $b->getPriority() <=> $a->getPriority()
    } @{ $self->{'__packages__'} };

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
