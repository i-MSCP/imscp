=head1 NAME

 Servers::po - i-MSCP PO Server implementation

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

package Servers::po;

use strict;
use warnings;

use iMSCP::Debug;

our $instance;

=head1 DESCRIPTION

 i-MSCP po server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory()

 Create and return po server instance

 Param string $sName OPTIONAL Name of PO server implementation to instantiate
 Return PO server instance

=cut

sub factory
{
	unless(defined $instance) {
		my $sName = $main::imscpConfig{'PO_SERVER'} || 'no';
		my $package = ($sName eq 'no') ? 'Servers::noserver' : "Servers::po::$sName";
		eval "require $package";
		fatal($@) if $@;
		$instance = $package->getInstance();
	}

	$instance;
}

=item can($method)

 Checks if the po server class provide the given method

 Return subref|undef

=cut

sub can
{
	my $sName = $main::imscpConfig{'PO_SERVER'} || undef;

	if($sName && $sName ne 'no' && $sName ne 'external_server') {
		my $package = "Servers::po::$sName";
		eval "require $package";
		fatal($@) if $@;
		$package->can($_[1]);
	} else {
		undef;
	}
}

END
{
	unless(!$Servers::po::instance || ( $main::execmode && $main::execmode eq 'setup' )) {
		my $rs = 0;

		if($Servers::po::instance->{'restart'}) {
			$rs = $Servers::po::instance->restart();
		}

		$? ||= $rs;
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
