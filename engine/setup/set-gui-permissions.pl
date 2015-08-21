#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

newDebug('imscp-set-gui-permissions.log');

# Initialize command line options
$main::execmode = 'backend';

iMSCP::Getopt->parseNoDefault(sprintf("Usage: perl %s [OPTION]...", basename($0)) . qq {

PURPOSE
	Script which set i-MSCP frontEnd permissions.

OPTIONS
 -s,    --setup         Setup mode.
 -v,    --verbose       Enable verbose mode},
 'setup|s' => sub { $main::execmode = 'setup' },
 'verbose|v' => sub { setVerbose(@_); }
);

iMSCP::Bootstrapper->getInstance()->boot({ norequirements => 1, nolock => 1, nodatabase => 1, nokeys => 1 });

my @toProcess = ();

for my $srv(iMSCP::Servers->getInstance()->getFull()) {
	eval "require $srv" or die(sprintf('Could not load %s package: %s', $srv, $@));
	my $obj = $srv->factory();
	if($obj->can('setGuiPermissions')) {
		push @toProcess, [ $srv, $obj ];
	}
}

for my $pkg(iMSCP::Packages->getInstance()->getFull()) {
	eval "require $pkg" or die(sprintf('Could not load %s package: %s', $pkg, $@));
	my $obj = $pkg->getInstance();
	if($obj->can('setGuiPermissions')) {
		push @toProcess, [ $pkg, $obj ];
	}
}

my $totalItems = @toProcess;
my $counter = 1;
my $rs = 0;

for my $item(@toProcess) {
	my ($package, $instance) = @{$item};
	debug("Setting $package (frontEnd) permissions");
	print "Setting $package (frontEnd) permissions\t$totalItems\t$counter\n" if $main::execmode eq 'setup';
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
		iMSCP::Mail->new()->errmsg($msg);
	}
}

exit $rs;
