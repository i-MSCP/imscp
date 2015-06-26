=head1 NAME

 iMSCP::Execute - Allows to execute external commands

=cut

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

package iMSCP::Execute;

use strict;
use warnings;
use iMSCP::Debug;
use IO::Select;
use IPC::Open3;
use Symbol 'gensym';
use File::Basename ();
use Cwd ();

my $vendorLibDir;

BEGIN { $vendorLibDir = Cwd::realpath(File::Basename::dirname(__FILE__) . '/../../PerlVendor'); }

use lib $vendorLibDir;
use Capture::Tiny ':all';
use parent 'Exporter';

our @EXPORT = qw/execute executeNoWait escapeShell getExitCode/;

=head1 DESCRIPTION

 This package provides a set of functions allowing to execute external commands.

=head1 FUNCTIONS

=over 4

=item execute($command [, \$stdout = undef [, \$stderr = undef]])

 Execute the given external command

 Param string $command Ccommand to execute
 Param string \$stdout OPTIONAL Command stdout
 Param string \$stderr OPTIONAL Command stderr
 Return int External command exit code or die on failure

=cut

sub execute($;$$)
{
	my ($command, $stdout, $stderr) = @_;

	if($stdout) {
		fatal('$stdout must be a scalar reference') unless ref $stdout eq 'SCALAR';
		$$stdout = '';
	}

	if($stderr) {
		fatal('$stderr must be a scalar reference') unless ref $stderr eq 'SCALAR';
		$$stderr = '';
	}

	debug("Executing command: $command");

	if($stdout && $stderr) {
		($$stdout, $$stderr) = capture { system($command); };
		chomp($$stdout);
		chomp($$stderr);
	} elsif ($stdout) {
		$$stdout = capture_stdout { system($command); };
		chomp($$stdout);
	} elsif($stderr) {
		$$stderr = capture_stderr { system($command); };
		chomp($stderr);
	} else {
		die("Unable to execute command: $!") if system($command) == -1;
	}

	getExitCode();
}

=item executeNoWait($command, $stdoutCallback, $stderrCallback)

 Execute the given external command without wait

 Param string $command Command to execute
 Param subref Callback responsible to process command stdout
 Param subref Callback responsible to process command sterrr
 Return int External command exit code or die on failure

=cut

sub executeNoWait($$$)
{
	my ($command, $stdoutCallback, $stderrCallback) = @_;

	my $stderr = gensym;
	my $pid = open3(my $stdin, my $stdout, $stderr, $command);
	close $stdin;

	my $sel = new IO::Select();
	$sel->add($stdout);
	$sel->add($stderr);

	my %buffers = ( $stdout => '', $stderr => '' );

	while($sel->count()) {
		for my $fh ($sel->can_read()) {
			my $ret = sysread($fh, $buffers{$fh}, 4096, length($buffers{$fh}));

			die $! unless defined $ret;

			if ($ret == 0) {
				$sel->remove($fh);
				close($fh);
				next;
			}

			if ($fh == $stderr) {
				$stderrCallback->(\$buffers{$stderr});
			} else {
				$stdoutCallback->(\$buffers{$stdout});
			}
		}
	}

	waitpid($pid, 0) if $pid > 0;

	#close $stdout;
	#close $stderr;

	getExitCode();
}

=item escapeShell($string)

 Escape the given string

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
 Return int exit code or die on failure

=cut

sub getExitCode(;$)
{
	my $exitValue = $_[0] // $?;

	if ($exitValue == -1) {
		die("Failed to execute external command: $!");
	} elsif ($exitValue & 127) {
		die(
			sprintf(
				"External command died with signal %d, %s coredump", ($exitValue & 127), ($? & 128) ? 'with' : 'without'
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

=cut

1;
__END__
