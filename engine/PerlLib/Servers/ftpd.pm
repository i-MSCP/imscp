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

our $instance;

=head1 DESCRIPTION

 i-MSCP ftpd server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory()

 Create and return ftpd server instance

 Return Ftpd server instance

=cut

sub factory
{
	unless(defined $instance) {
		my $sName = $main::imscpConfig{'FTPD_SERVER'} || 'no';
		my $package = ($sName eq 'no') ? 'Servers::noserver' : "Servers::ftpd::$sName";
		eval "require $package";
		fatal($@) if $@;
		$instance = $package->getInstance();
	}

	$instance;
}

=item can($method)

 Checks if the ftpd server class provide the given method

 Return subref|undef

=cut

sub can
{
	my $sName = $main::imscpConfig{'FTPD_SERVER'} || undef;

	if($sName && $sName ne 'no') {
		my $package = "Servers::ftpd::$sName";
		eval "require $package";
		fatal($@) if $@;
		$package->can($_[1]);
	} else {
		undef;
	}
}

END
{
	unless(!$Servers::ftpd::instance || ( $main::execmode && $main::execmode eq 'setup' )) {
		my $rs = $?;

		if($Servers::ftpd::instance->{'start'}) {
			$rs ||= $Servers::ftpd::instance->start();
		} elsif($Servers::ftpd::instance->{'restart'}) {
			$rs ||= $Servers::ftpd::instance->restart();
		}

		$? = $rs;
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
