=head1 NAME

 Package::NoHandler - NoHandler package

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

package Package::NoHandler;

use strict;
use warnings;
use parent 'Common::Object';

=head1 DESCRIPTION

=head1 PUBLIC METHODS

=over 4

=item can( $method )

 Whether this handler has the given method

 Return undef

=cut

sub can
{
    undef;
}

=item AUTOLOAD

 Provide autoloading

 Return int 0

=cut

sub AUTOLOAD
{
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
