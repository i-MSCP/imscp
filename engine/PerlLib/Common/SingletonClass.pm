#!/usr/bin/perl

=head1 NAME

 Common::SingletonClass - Base class implementing Singleton Design Pattern

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
#
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Common::SingletonClass;

use strict;
use warnings;

=head1 DESCRIPTION

 Base class implementing Singleton Design Pattern.

=head1 PUBLIC METHODS

=over 4

=item getInstance()

 Implement singleton design pattern. Return instance of this class.

 Return Common::SingletonClass

=cut

sub getInstance
{
    my $class = shift;

    return $class if ref $class;

    no strict 'refs';
    my $instance = \${ "$class\::_instance" };

    defined $$instance ? $$instance : ($$instance = $class->_newInstance(@_));
}

=item hasInstance()

 Whether an instance already exists.

 Return Common::SingletonClass

=cut

sub hasInstance
{
    my $class = shift;

    $class = ref $class || $class;
    no strict 'refs';

    return ${"$class\::_instance"};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _newInstance()

 Implement singleton design pattern. Return instance of this class.

 Return Common::SingletonClass

=cut

sub _newInstance
{
    my $class = shift;
    my %args  = @_ && ref $_[0] eq 'HASH' ? %{ $_[0] } : @_;
    $class = bless { %args }, $class;

    $class->_init;

    $class;
}

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
