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

newDebug('imscp-set-engine-permissions.log');

# Initialize command line options
$main::execmode = 'backend';

iMSCP::Getopt->parseNoDefault(sprintf("Usage: perl %s [OPTION]...", basename($0)) . qq {

Script which set i-MSCP backend permissions.

OPTIONS:
 -s,    --setup         Setup mode.
 -v,    --verbose       Enable verbose mode.},
 'setup|s' => sub { $main::execmode = 'setup'; },
 'verbose|v' => sub { setVerbose(@_); }
);

iMSCP::Bootstrapper->getInstance()->boot({ norequirements => 1, nolock => 1, nodatabase => 1, nokeys => 1 });

my @toProcess = ();

for my $srv(iMSCP::Servers->getInstance()->getFull()) {
	eval "require $srv" or die(sprintf('Could not load %s package: %s', $srv, $@));
	my $obj = $srv->factory();
	if($obj->can('setEnginePermissions')) {
		push @toProcess, [ $srv, $obj ];
	}
}

for my $pkg(iMSCP::Packages->getInstance()->getFull()) {
	eval "require $pkg" or die(sprintf('Could not load %s package: %s', $pkg, $@));
	my $obj = $pkg->getInstance();
	if($obj->can('setEnginePermissions')) {
		push @toProcess, [ $pkg, $obj ];
	}
}

my $totalItems = @toProcess + 1;
my $counter = 1;
my $rs = 0;

debug('Setting base (backend) permissions');
print "Setting base (backend) permissions\t$totalItems\t$counter\n" if $main::execmode eq 'setup';

my $rootUName = $main::imscpConfig{'ROOT_USER'};
my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
my $confDir = $main::imscpConfig{'CONF_DIR'};
my $rootDir = $main::imscpConfig{'ROOT_DIR'};

setRights($main::imscpConfig{'CONF_DIR'}, {
	user => $rootUName, group => $imscpGName, dirmode => '0750', filemode => '0640', recursive => 1
});
setRights($rootDir, { user => $rootUName, group => $rootGName, mode => '0755' });
setRights("$rootDir/engine", { user => $rootUName, group => $imscpGName, mode => '0750', recursive => 1 });
setRights($main::imscpConfig{'USER_WEB_DIR'}, { user => $rootUName, group => $rootGName, mode => '0755' });
setRights($main::imscpConfig{'LOG_DIR'}, { user => $rootUName, group => $imscpGName, mode => '0750'} );
setRights($main::imscpConfig{'CACHE_DATA_DIR'}, { user => $rootUName, group => $rootGName, mode => '0750' });
setRights($main::imscpConfig{'VARIABLE_DATA_DIR'}, { user => $rootUName, group => $rootGName, mode => '0750' });

$counter++;

for my $item(@toProcess) {
	my ($package, $instance) = @{$item};
	debug("Setting $package (backend) permissions");
	print "Setting $package (backend) permissions\t$totalItems\t$counter\n" if $main::execmode eq 'setup';
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
		iMSCP::Mail->new()->errmsg($msg);
	}
}

exit $rs;
