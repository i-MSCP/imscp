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
# @category		i-MSCP
# @copyright	2010-2014 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

use strict;
use warnings;

use FindBin;
use lib "$FindBin::Bin/..", "$FindBin::Bin/../PerlLib", "$FindBin::Bin/../PerlVendor";

use iMSCP::Debug;
use iMSCP::Boot;
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

newDebug('imscp-set-gui-permissions.log');

silent(1);

sub startUp
{
	iMSCP::Boot->getInstance()->boot({ 'nolock' => 'yes', 'nodatabase' => 'yes', 'nokeys' => 'yes' });

	my $rs = 0;

	unless($main::execmode eq 'setup') {
		require iMSCP::HooksManager;
		$rs = iMSCP::HooksManager->getInstance()->register(
			'beforeExit', sub { shift; my $clearScreen = shift; $$clearScreen = 0; 0; }
		)
	}

	$rs;
}

sub process
{
	my ($instance, $file, $class);
	my @servers = iMSCP::Servers->getInstance()->get();
	my @addons = iMSCP::Addons->getInstance()->get();
	my $totalItems = @servers + @addons;
	my $counter = 1;
	my $rs = 0;

	for(@servers) {
		s/\.pm//;

		$file = "Servers/$_.pm";
		$class = "Servers::$_";

		require $file;
		$instance = $class->factory();

		if($instance->can('setGuiPermissions')) {
			debug("Setting $_ server frontEnd permissions");
			print "Setting frontEnd permissions for the $_ server\t$totalItems\t$counter\n" if $main::execmode eq 'setup';
			$rs = $instance->setGuiPermissions();
			return $rs if $rs;
		}

		$counter++;
	}

	for(@addons) {
		s/\.pm//;

		$file = "Addons/$_.pm";
		$class = "Addons::$_";

		require $file;
		$instance = $class->getInstance();

		if($instance->can('setGuiPermissions')) {
			debug("Setting $_ addon frontEnd permissions");
			print "Setting frontEnd permissions for the $_ addon\t$totalItems\t$counter\n" if $main::execmode eq 'setup';
			$rs = $instance->setGuiPermissions();
			return $rs if $rs;
		}

		$counter++;
	}

	0;
}

sub shutDown
{
	unless($main::execmode eq 'setup') {
		my @warnings = getMessageByType('warn');
		my @errors = getMessageByType('error');
		my $rs = 0;

		my $msg = "\nWARNINGS:\n" . join("\n", @warnings) . "\n" if @warnings > 0;
		$msg .= "\nERRORS:\n" . join("\n", @errors) . "\n" if @errors > 0;

		if($msg) {
			require iMSCP::Mail;

			$rs = iMSCP::Mail->new()->errmsg($msg);
			return $rs if $rs;
		}
	}
}

my $rs = startUp();
$rs ||= process();
shutDown();

exit $rs;
