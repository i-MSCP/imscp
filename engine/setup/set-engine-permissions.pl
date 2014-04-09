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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

use strict;
use warnings;

use FindBin;
use lib "$FindBin::Bin/..", "$FindBin::Bin/../PerlLib", "$FindBin::Bin/../PerlVendor";

use iMSCP::Debug;
use iMSCP::Bootstrapper;
use iMSCP::Rights;
use iMSCP::Servers;
use iMSCP::Addons;

# Turn off localisation features to force any command output to be in english
$ENV{'LC_MESSAGES'} = 'C';

# Do not clear screen at end of script
$ENV{'IMSCP_CLEAR_SCREEN'} = 0;

# Mode in which the script is triggered
# For now, this variable is only used by i-MSCP installer/setup scripts
$main::execmode = shift || '';

umask(027);

newDebug('imscp-set-engine-permissions.log');

silent(1);

iMSCP::Bootstrapper->getInstance()->boot(
	{ 'norequirements' => 'yes', 'nolock' => 'yes', 'nodatabase' => 'yes', 'nokeys' => 'yes' }
);

sub run
{
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
	my $confDir = $main::imscpConfig{'CONF_DIR'};
	my $rootDir = $main::imscpConfig{'ROOT_DIR'};
	my $logDir = $main::imscpConfig{'LOG_DIR'};

	my @servers = iMSCP::Servers->getInstance()->get();
	my @addons = iMSCP::Addons->getInstance()->get();
	my $totalItems = @servers + @addons + 1;
	my $counter = 1;

	# Set base permissions - begin
	debug('Setting backend base permissions');
	print "Setting backend base permissions\t$totalItems\t$counter\n" if $main::execmode eq 'setup';

	# eg. /etc/imscp/*
	my $rs = setRights(
		$confDir,
		{ 'user' => $rootUName, 'group' => $imscpGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 1 }
	);

	# eg. /var/www/imscp/engine
	$rs |= setRights(
		"$rootDir/engine", { 'user' => $rootUName, 'group' => $imscpGName, 'mode' => '0750', 'recursive' => 1 }
	);

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

	# Trigger the setEnginePermissions() method on all i-MSCP server packages implementing it

	for(@servers) {
		next if $_ eq 'noserver';

		my $package = "Servers::$_";

		eval "require $package";

		unless($@) {
			my $instance = $package->factory();

			if($instance->can('setEnginePermissions')) {
				debug("Setting $_ server backend permissions");

				if ($main::execmode eq 'setup') {
					print "Setting backend permissions for the $_ server\t$totalItems\t$counter\n";
				}

				$rs |= $instance->setEnginePermissions();
			}
		} else {
			error($@);
			$rs = 1;
		}

		$counter++;
	}

	# Trigger the setEnginePermissions() method on all i-MSCP addon packages implementing it
	for(@addons) {
		my $package = "Addons::$_";

		eval "require $package";

		unless($@) {
			my $instance = $package->getInstance();

			if($instance->can('setEnginePermissions')) {
				debug("Setting $_ addon backend permissions");

				if ($main::execmode eq 'setup') {
					print "Setting backend permissions for the $_ addon\t$totalItems\t$counter\n";
				}

				$rs |= $instance->setEnginePermissions();
			}
		} else {
			error($@);
			$rs = 1;
		}

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
