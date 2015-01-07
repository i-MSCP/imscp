#!/usr/bin/perl

=head1 NAME

 Servers::sqld - i-MSCP Sqld Server implementation

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

package Servers::sqld;

use strict;
use warnings;

use iMSCP::Debug;

our $instance;

=head1 DESCRIPTION

 i-MSCP Sqld server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory([ $sName = $main::imscpConfig{'SQL_SERVER'} || 'mysql' ])

 Create and return Sqld server instance

 Param string $sName OPTIONAL Name of Sqld server implementation to instantiate
 Return Sqld server instance

=cut

sub factory
{
	my ($self, $sName) = @_;

	$sName ||= $main::imscpConfig{'SQL_SERVER'} || 'mysql';

	if($sName eq 'remote_server') {
		$sName = 'mysql';
	} else {
		$sName =~ s/_\d+\.\d+$//;
	}

	my $package = "Servers::sqld::$sName";

	eval "require $package";

	fatal($@) if $@;

	$instance = $package->getInstance();
}

END
{
	unless(
		!$Servers::sqld::instance || $main::imscpConfig{'SQL_SERVER'} eq 'remote_server' ||
		$main::execmode && $main::execmode eq 'setup'
	) {
		my $rs = 0;

		if($Servers::sqld::instance->{'restart'}) {
			$rs = $Servers::sqld::instance->restart();
		}

		$? ||= $rs;
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
