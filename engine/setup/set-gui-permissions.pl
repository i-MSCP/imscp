#!/usr/bin/perl

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
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
use iMSCP::Getopt;
use File::Basename;

$ENV{'LC_MESSAGES'} = 'C';
use open ':locale';

newDebug('imscp-set-gui-permissions.log');

# Initialize command line options
$main::execmode = '';

# Parse command line options
iMSCP::Getopt->parseNoDefault(sprintf("Usage: perl %s [OPTION]...", basename($0)) . qq {

PURPOSE
	Script which set i-MSCP frontEnd permissions.

OPTIONS
 -s,    --setup         Setup mode.
 -v,    --verbose       Enable verbose mode},
 'setup|s' => sub { $main::execmode = 'setup' },
 'verbose|v' => sub { setVerbose(@_); }
);

iMSCP::Bootstrapper->getInstance()->boot(
	{ 'norequirements' => 'yes', 'nolock' => 'yes', 'nodatabase' => 'yes', 'nokeys' => 'yes' }
);

my $rs = 0;
my @toProcess = ();

for(iMSCP::Servers->getInstance()->get()) {
	my $package = "Servers::$_";
	eval "require $package";

	unless($@) {
		my $package = $package->factory();
		push @toProcess, [ $_, $package ] if $package->can('setGuiPermissions');;
	} else {
		error($@);
		$rs = 1;
	}
}

for(iMSCP::Packages->getInstance()->get()) {
	my $package = "Package::$_";
	eval "require $package";

	unless($@) {
		my $package = $package->getInstance();
		push @toProcess, [ $_, $package ] if $package->can('setGuiPermissions');
	} else {
		error($@);
		$rs = 1;
	}
}

my $totalItems = @toProcess;
my $counter = 1;

for(@toProcess) {
	my ($package, $instance) = @{$_};

	debug("Setting $package ( frontEnd ) permissions");

	if ($main::execmode eq 'setup') {
		print "Setting $package ( frontEnd ) permissions\t$totalItems\t$counter\n";
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

exit $rs;
