#!/usr/bin/perl

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

use strict;
use warnings;

use FindBin;
use lib "$FindBin::Bin/..", "$FindBin::Bin/../PerlLib", "$FindBin::Bin/../PerlVendor";

use iMSCP::Debug;
use iMSCP::Bootstrapper;
use iMSCP::Servers;
use iMSCP::Packages;

# Turn off localisation features to force any command output to be in english
$ENV{'LC_MESSAGES'} = 'C';

# Do not clear screen at end of script
$ENV{'IMSCP_CLEAR_SCREEN'} = 0;

# Mode in which the script is triggered
# For now, this variable is only used by i-MSCP installer/setup scripts
$main::execmode = shift || '';

umask(027);

newDebug('imscp-set-gui-permissions.log');

silent(1);

iMSCP::Bootstrapper->getInstance()->boot(
	{ 'norequirements' => 'yes', 'nolock' => 'yes', 'nodatabase' => 'yes', 'nokeys' => 'yes' }
);

sub run
{
	my $rs = 0;

	my @toProcess = ();

	for(iMSCP::Servers->getInstance()->get()) {
		next if $_ eq 'noserver';

		my $package = "Servers::$_";

		eval "require $package";

		unless($@) {
			my $instance = $package->factory();
			push @toProcess, [$_, $instance] if $instance->can('setGuiPermissions');
		} else {
			error($@);
			$rs = 1;
		}
	}

	for(iMSCP::Packages->getInstance()->get()) {
		my $package = "Package::$_";

		eval "require $package";

		unless($@) {
			my $instance = $package->getInstance();
			push @toProcess, [$_, $instance] if $instance->can('setGuiPermissions');
		} else {
			error($@);
			$rs = 1;
		}
	}

	my $totalItems = @toProcess;
	my $counter = 1;

	for(@toProcess) {
		my ($package, $instance) = @{$_};

		debug("Setting $_ package frontEnd permissions");

		if ($main::execmode eq 'setup') {
			print "Setting frontEnd permissions for the $package package\t$totalItems\t$counter\n";
		}

		$rs |= $instance->setGuiPermissions();

		$counter++;
	}

	unless($main::execmode eq 'setup') {
		my @warnings = getMessageByType('warn');
		my @errors = getMessageByType('error');
		my $msg = "\nWARNINGS:\n" . join("\n", @warnings) . "\n" if @warnings > 0;
		$msg .= "\nERRORS:\n" . join("\n", @errors) . "\n" if @errors > 0;

		if($msg) {
			require iMSCP::Mail;
			$rs |= iMSCP::Mail->new()->errmsg($msg);
		}
	}

	$rs;
}

exit run();
