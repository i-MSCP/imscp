# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 by internet Multi Server Control Panel
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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Execute;

use strict;
use warnings;

use vars qw/@ISA @EXPORT/;
use Exporter;

use iMSCP::Debug;
use iMSCP::STDCapture;

@ISA = ('Exporter');
@EXPORT = qw/execute/;

sub execute{
	debug((caller(0))[3].': Starting...');
	my ($code, $output, $error) = @_;
	my $rv;
	if (ref $output && ref $error){
		$rv = _execCaptureBoth($code, $output, $error);
	} elsif(ref $output){
		$rv = _execCaptureOutput($code, $output);
	} elsif(ref $error){
		$rv = _execCaptureError($code, $error);
	} else {
		$rv = _execCode($code);
	}
	debug((caller(0))[3].': Ending...');
	$rv;
}

sub _execCaptureBoth {
	debug((caller(0))[3].': Starting...');
	my ($code, $output, $error) = @_;
	my $out = new iMSCP::STDCapture('STDOUT', $output);
	my $err = new iMSCP::STDCapture('STDERR', $error);
	debug((caller(0))[3].": Execute $code");
	system($code);
	debug((caller(0))[3].': Ending...');
	return _getExitCode($?);
}

sub _execCaptureOutput {
	my ($code, $output) = @_;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Execute $code");
	$$output = `$code 2>/dev/null`;
	debug((caller(0))[3].': Ending...');
	return _getExitCode($?);
}
sub _execCaptureError {
	my ($code, $error) = @_;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Execute $code");
	$$error = `$code 2>&1 1>/dev/null`;
	debug((caller(0))[3].': Ending...');
	return _getExitCode($?);
}
sub _execCode {
	my $code = shift;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Execute $code");
	system($code);
	debug((caller(0))[3].': Ending...');
	return _getExitCode($?);
}

sub _getExitCode {
	debug((caller(0))[3].': Starting...');
	my $exitValue = shift;
	if ($exitValue == -1) {
		error((caller(0))[3].": Failed to execute external command: $!");
	} elsif ($exitValue & 127) {
		error((caller(0))[3].': '.
			(
				sprintf "External command died with signal %d, %s coredump",
				($exitValue & 127), ($? & 128) ? 'with' : 'without'
			)
		);
	} else {
		$exitValue = $exitValue >> 8;
		debug((caller(0))[3].": External command exited with value $exitValue");
	}
	debug((caller(0))[3].': Ending...');
	$exitValue;
}
1;
