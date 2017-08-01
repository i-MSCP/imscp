=head1 NAME

 Common::Object - Object base class

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Common::Object;

use strict;
use warnings;

=head1 DESCRIPTION

 Object base class.

=head1 PUBLIC METHODS

=over 4

=item new( [ %attrs ] )

 Constructor

 Param hash|hashref OPTIONAL hash representing class attributes
 Return Common::Object

=cut

sub new
{
    my ($class, @attrs) = @_;

    my $self = bless { @attrs && ref $attrs[0] eq 'HASH' ? %{$attrs[0]} : @attrs }, $class;
    $self->_init();
    $self;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Common::Object

=cut

sub _init
{
    my ($self) = @_;

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
