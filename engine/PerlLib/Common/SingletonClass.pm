=head1 NAME

 Common::SingletonClass - Base class implementing Singleton Design Pattern

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Common::SingletonClass;

use strict;
no strict 'refs';
use warnings;

=head1 DESCRIPTION

 Base class implementing Singleton Design Pattern.

=head1 PUBLIC METHODS

=over 4

=item getInstance([ %args ])

 Implement singleton design pattern. Return instance of this class

 Param hash|hash_ref OPTIONAL hash representing class attributes
 Return Common::SingletonClass

=cut

sub getInstance
{
	my $class = shift;
	return $class if ref $class;

	my $self = \${"${class}::_instance"};

	unless(defined $$self) {
		$$self = bless { @_ && ref $_[0] eq 'HASH' ? %{$_[0]} : @_ }, $class;
		$$self->_init();
	}

	$$self;
}

=item hasInstance()

 Whether an instance already exists

 Return Common::SingletonClass

=cut

sub hasInstance
{
	my $self = shift;

	$self = ref $self || $self;
	${"${self}::_instance"};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance

 Return Common::SingletonClass

=cut

sub _init
{
	shift;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
