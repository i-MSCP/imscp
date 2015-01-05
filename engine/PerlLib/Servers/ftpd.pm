#!/usr/bin/perl

=head1 NAME

 Servers::ftpd - i-MSCP Ftpd Server implementation

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

package Servers::ftpd;

use strict;
use warnings;

use iMSCP::Debug;

=head1 DESCRIPTION

 i-MSCP Ftpd server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory([ $sName = $main::imscpConfig{'FTPD_SERVER'} || 'no' ])

 Create and return Ftpd server instance

 Param string $sName OPTIONAL Name of Ftpd server implementation to instantiate
 Return Ftpd server instance

=cut

sub factory
{
	my ($self, $sName) = @_;

	$sName ||= $main::imscpConfig{'FTPD_SERVER'} || 'no';

	my $package = ($sName eq 'no') ? 'Servers::noserver' : "Servers::ftpd::$sName";

	eval "require $package";

	fatal($@) if $@;

	$package->getInstance();
}

END
{
	unless($main::execmode && $main::execmode eq 'setup') {
		my $ftpd = __PACKAGE__->factory();
		my $rs = 0;

		if($ftpd->{'start'}) {
			$rs = $ftpd->start();
		} elsif($ftpd->{'restart'}) {
			$rs = $ftpd->restart();
		}

		$? ||= $rs;
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
