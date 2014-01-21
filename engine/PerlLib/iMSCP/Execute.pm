#!/usr/bin/perl

=head1 NAME

 iMSCP::Execute - Allows to execute external commands

=cut

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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Execute;

use strict;
use warnings;

use iMSCP::Debug;
use IPC::Open3;
use IO::Select;
use Scalar::Util qw(openhandle);
use parent 'Exporter';

our @EXPORT = qw/execute escapeShell getExitCode/;

=head1 DESCRIPTION

 This class provides set of functions allowing to execute external commands. It's also possible to capture STDOUT and
 and/or STDERR.

=head1 FUNCTIONS

=over 4

=item execute($command, [\$stdout = undef], [\$stderr = undef])

 Execute the given external command.

 Param string String representing external command to execute
 Param scalar_ref $stdout OPTIONAL Scalar reference referring to command stdout
 Param scalar_ref $stderr OPTIONAL Scalar reference referring to command stderr
 Return int External command exit code

=cut

sub execute($;$$)
{
	my ($command, $stdout, $stderr) = @_;

	fatal('$stdout must be a scalar reference') if $stdout && ref $stdout ne 'SCALAR';
	fatal('$stderr must be a scalar reference') if $stderr && ref $stderr ne 'SCALAR';

	debug("Executing command: $command");

	my $sel = IO::Select->new();
	my $pid;

	if($stdout && $stderr) {
		eval { $pid = open3(*IN, *OUT, *ERR, $command); };
		fatal("Unable to execute command: $@") if $@;
		$sel->add(*OUT, *ERR);
	} elsif($stdout) {
		eval { $pid = open3(*IN, *OUT, ">&STDERR", $command); };
		fatal("Unable to execute command: $@") if $@;
		$sel->add(*OUT);
	} elsif($stderr) {
		eval { $pid = open3(*IN, ">&STDOUT", *ERR, $command); };
		fatal("Unable to execute command: $@") if $@;
		$sel->add(*ERR);
	} else {
		fatal("Unable to execute command: $!") if system($command) == -1;
		return getExitCode($?);
	}

	close IN;

	$$stdout = '';
	$$stderr = '';

	while (my @ready = $sel->can_read()) {
		foreach my $fh (@ready) {
			if (openhandle(*ERR) && fileno($fh) == fileno(ERR)) {
				$$stderr .= scalar <ERR>;
 			} else {
 				$$stdout .= scalar <OUT>;
 			}

			$sel->remove($fh) if eof($fh);
		}
	}

	close OUT;
	close ERR;

	waitpid($pid, 0) if $pid > 0;

	chomp($$stdout);
	chomp($$stderr);

	getExitCode();
}

=item escapeShell($string)

 Escape the given string.

 Param string $string String to escape
 Return string Escaped string

=cut

sub escapeShell($)
{
	return $_[0] if $_[0] eq '' || $_[0] =~ /^[a-zA-Z0-9_\-]+\z/;
	my $s = $_[0];
	$s =~ s/'/'\\''/g;

	"'$s'";
}

=item getExitCode([$exitValue = $?])

 Return human exit code

 Param int $exitValue Raw exit code (default to $?)
 Return int exit code

=cut

sub getExitCode(;$)
{
	my $exitValue = $_[0] // $?;

	if ($exitValue == -1) {
		error("Failed to execute external command: $!");
	} elsif ($exitValue & 127) {
		error(''.
			(
				sprintf "External command died with signal %d, %s coredump",
				($exitValue & 127), ($? & 128) ? 'with' : 'without'
			)
		);
	} else {
		$exitValue = $exitValue >> 8;
		debug("External command exited with value $exitValue");
	}

	$exitValue;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Daniel Andreca <sci2tech@gmail.com>

=cut

1;
