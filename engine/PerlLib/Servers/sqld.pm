#!/usr/bin/perl

=head1 NAME

Servers::sqld - i-MSCP SQL server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::sqld;

use strict;
use warnings;

use iMSCP::Debug;

=head1 DESCRIPTION

 i-MSCP Cron server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory()

 Return an instance of sql server implementation

 Return SQL server implementation

=cut

sub factory
{
	my $self = $_[0];

	(my $server = $_[1] || $main::imscpConfig{'SQL_SERVER'}) =~ s/(?:(.*?)_(\d+\.\d+)|('remote_server'))/$1/;

	$server = 'mysql' if $server eq 'remote_server';

	my $package = "Servers::sqld::$server";

	eval "require $package";

	fatal($@) if $@;

	$package->getInstance();
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
