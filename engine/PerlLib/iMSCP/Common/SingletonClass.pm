=head1 NAME

 iMSCP::Common::SingletonClass - Base class implementing Singleton Design Pattern

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Common::SingletonClass;

use strict;
use warnings;

my %_INSTANCES = ();

=head1 DESCRIPTION

 Base class implementing Singleton Design Pattern.

=head1 CLASS METHODS

=over 4

=item hasInstance( [ %attrs ] )

 Return the current instance if it exists

 Return iMSCP::Common::SingletonClass|undef

=cut

sub hasInstance
{
    my $class = shift;

    $_INSTANCES{ref $class || $class};
}

=back

=head1 PUBLIC METHODS

=over 4

=item getInstance( [ %attrs ] )

 Implement singleton design pattern. Return instance of this class

 Param hash|hashref OPTIONAL hash representing class attributes
 Return iMSCP::Common::SingletonClass

=cut

sub getInstance
{
    my ($class, @attrs) = @_;

    # Already got an object
    return $class if ref $class;

    # We store the instance against the $class key of %_INSTANCES
    unless ( defined $_INSTANCES{$class} ) {
        $_INSTANCES{$class} = bless { @attrs && ref $attrs[0] eq 'HASH' ? %{$attrs[0]} : @attrs }, $class;
        $_INSTANCES{$class}->_init();
    }

    $_INSTANCES{$class};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Called by getInstance( ). Initialize instance

 Return iMSCP::Common::SingletonClass

=cut

sub _init
{
    my ($self) = @_;

    $self;
}

=back

=head1 SHUTDOWN tasks

=over 4

=item END( )

 Explicitly destroy all iMSCP::Common::SingletonClass objects

=cut

sub END
{
    undef( %_INSTANCES );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
