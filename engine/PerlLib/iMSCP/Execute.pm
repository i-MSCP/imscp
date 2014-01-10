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
use Symbol qw/gensym/;
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

	debug("Execute $command");

	my $sel = IO::Select->new();
	my $pid;

	if(defined $stdout && defined $stderr) {
		$pid = open3(gensym, *CATCHOUT, *CATCHERR, $command);
	$sel->add(*CATCHOUT, *CATCHERR);
		_readIO($sel, $stdout, $stderr);
	} elsif(defined $stdout) {
		$pid = open3(gensym, *CATCHOUT, ">&STDERR", $command);
		$sel->add(*CATCHOUT);
		_readIO($sel, $stdout);
	} elsif(defined $stderr) {
		$pid = open3(gensym, ">&STDOUT", *CATCHERR, $command);
		$sel->add(*CATCHERR);
		_readIO($sel, undef, $stderr);
	} else {
		system($command);
	}

	waitpid($pid, 0) if $pid;

	close(CATCHOUT) if defined $stdout;
	close(CATCHERR) if defined $stderr;

	chomp($$stdout ||= '');
	chomp($$stderr ||= '');

	getExitCode($?);
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
	my $exitValue = shift // $?;

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

sub _readIO
{
	my ($sel, $stdout, $stderr) = @_;

	while (my @ready = $sel->can_read(3)) {
		if(@ready) {
			foreach my $fh (@ready) {
				if ($stderr && fileno($fh) == fileno(CATCHERR)) {
					$$stderr = do { local $/; <CATCHERR> };
				} elsif($stdout) {
					$$stdout = do { local $/; <CATCHOUT> };
				}

				$sel->remove($fh) if eof($fh);
			}
		} else {
			last;
		}
	}
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Daniel Andreca <sci2tech@gmail.com>

=cut

1;
