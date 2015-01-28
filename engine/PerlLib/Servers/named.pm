=head1 NAME

 Servers::named - i-MSCP Named Server implementation

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

package Servers::named;

use strict;
use warnings;

use iMSCP::Debug;

our $instance;

=head1 DESCRIPTION

 i-MSCP named server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory()

 Create and return named server instance

 Also trigger uninstallation of old named server when needed.

 Return Named server instance

=cut

sub factory
{
	unless(defined $instance) {
		my $sName = $main::imscpConfig{'NAMED_SERVER'} || 'no';

		my $package = undef;

		if($sName eq 'no') {
			$package = 'Servers::noserver';
		} elsif($sName eq 'external_server') {
			my $oldSname = $main::imscpOldConfig{'NAMED_SERVER'} || 'no';

			unless($oldSname eq 'external_server' || $oldSname eq 'no') {
				$package = "Servers::named::$oldSname";

				eval "require $package";

				fatal($@) if $@;

				my $rs = $package->getInstance()->uninstall();
				fatal("Unable to uninstall $oldSname server") if $rs;
			}

			$package = 'Servers::noserver';
		} else {
			$package = "Servers::named::$sName";
		}

		eval "require $package";

		fatal($@) if $@;

		$instance = $package->getInstance();
	}

	$instance;
}

END
{
	unless(
		!$Servers::named::instance || $main::imscpConfig{'NAMED_SERVER'} eq 'external_server' ||
		( $main::execmode && $main::execmode eq 'setup' )
	) {
		my $rs = 0;

		if($Servers::named::instance->{'restart'}) {
			$rs = $Servers::named::instance->restart();
		}

		$? ||= $rs;
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
