#!/usr/bin/perl

=head1 NAME

 Servers::noserver - i-MSCP No Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::noserver;

use strict;
use warnings;

use iMSCP::Debug;

use vars qw/$AUTOLOAD/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP no server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory()

 Create and return no server instance

 Return Servers::noserver

=cut

sub factory
{
	__PACKAGE__->getInstance();
}


=item AUTOLOAD()

 Catch any call to unexistent method

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
