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
use iMSCP::Rights;
use iMSCP::Servers;
use iMSCP::Packages;
use iMSCP::Getopt;
use File::Basename;

$ENV{'LANG'} = 'C.UTF-8';

newDebug('imscp-set-engine-permissions.log');

# Initialize command line options
$main::execmode = '';

# Parse command line options
iMSCP::Getopt->parseNoDefault(sprintf("Usage: perl %s [OPTION]...", basename($0)) . qq {

Script which set i-MSCP backend permissions.

OPTIONS:
 -s,    --setup         Setup mode.
 -v,    --verbose       Enable verbose mode.},
 'setup|s' => sub { $main::execmode =  'setup'; },
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
		my $instance = $package->factory();
		push @toProcess, [ $_, $instance ] if $instance->can('setEnginePermissions');
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
		push @toProcess, [ $_, $instance ] if $instance->can('setEnginePermissions');
	} else {
		error($@);
		$rs = 1;
	}
}

my $totalItems = @toProcess + 1;
my $counter = 1;

# Set base permissions - begin
debug('Setting base ( backend ) permissions');
print "Setting base ( backend ) permissions\t$totalItems\t$counter\n" if $main::execmode eq 'setup';

my $rootUName = $main::imscpConfig{'ROOT_USER'};
my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
my $confDir = $main::imscpConfig{'CONF_DIR'};
my $rootDir = $main::imscpConfig{'ROOT_DIR'};
my $userWebDir = $main::imscpConfig{'USER_WEB_DIR'};
my $logDir = $main::imscpConfig{'LOG_DIR'};

# eg. /etc/imscp/*
$rs = setRights(
	$confDir,
	{ 'user' => $rootUName, 'group' => $imscpGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 1 }
);

# eg ./var/www/imscp
$rs |= setRights($rootDir, { 'user' => $rootUName, 'group' => $rootGName, 'mode' => '0755' });

# eg. /var/www/imscp/engine
$rs |= setRights(
	"$rootDir/engine", { 'user' => $rootUName, 'group' => $imscpGName, 'mode' => '0750', 'recursive' => 1 }
);

# eg ./var/www/virtual
$rs |= setRights($userWebDir, { 'user' => $rootUName, 'group' => $rootGName, 'mode' => '0755' });

# eg. /var/log/imscp
$rs |= setRights($logDir, { 'user' => $rootUName, 'group' => $imscpGName, 'mode' => '0750'} );

# eg. /var/cache/imscp
$rs |= setRights(
	$main::imscpConfig{'CACHE_DATA_DIR'}, { 'user' => $rootUName, 'group' => $rootGName, 'mode' => '0750' }
);

# eg. /var/local/imscp
$rs |= setRights(
	$main::imscpConfig{'VARIABLE_DATA_DIR'}, { 'user' => $rootUName, 'group' => $rootGName, 'mode' => '0750' }
);

$counter++;

# Set base permissions - ending

for(@toProcess) {
	my ($package, $instance) = @{$_};

	debug("Setting $package ( backend ) permissions");

	if ($main::execmode eq 'setup') {
		print "Setting $package ( backend ) permissions\t$totalItems\t$counter\n";
	}

	$rs |= $instance->setEnginePermissions();

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
