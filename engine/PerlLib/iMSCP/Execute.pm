=head1 NAME

 iMSCP::Execute - Library that provides functions for executing external commands and capturing output.

=cut

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

package iMSCP::Execute;

use strict;
use warnings;
use Carp;
use iMSCP::Debug qw(debug);
use IO::Select;
use IPC::Open3;
use Symbol 'gensym';
use File::Basename ();
use IO::Handle;
use Cwd ();

autoflush STDOUT 1;
autoflush STDERR 1;

my $vendorLibDir;

BEGIN { $vendorLibDir = Cwd::realpath(File::Basename::dirname(__FILE__) . '/../../PerlVendor'); }

use lib $vendorLibDir;
use Capture::Tiny ':all';
use parent 'Exporter';

our @EXPORT = qw/execute executeNoWait escapeShell getExitCode/;

=head1 DESCRIPTION

 Library that provides functions for executing external commands and capturing output.

=head1 FUNCTIONS

=over 4

=item execute($command [, \$stdout = undef [, \$stderr = undef ] ])

 Execute the given command

 Param string $command Command to execute
 Param string \$stdout OPTIONAL Variable for capture of STDOUT
 Param string \$stderr OPTIONAL Variable for capture of STDERR
 Return int Command exit code or croak on failure

=cut

sub execute($;$$)
{
	my ($command, $stdout, $stderr) = @_;

	if($stdout) {
		ref $stdout eq 'SCALAR' or croak('Expects a scalar reference as second parameter for capture of STDOUT');
		$$stdout = '';
	}

	if($stderr) {
		ref $stderr eq 'SCALAR' or croak('Expects a scalar reference as third parameter for capture of STDERR');
		$$stderr = '';
	}

	debug($command);

	if($stdout && $stderr) {
		($$stdout, $$stderr) = capture { system($command); };
		chomp($$stdout, $$stderr);
	} elsif ($stdout) {
		$$stdout = capture_stdout { system($command); };
		chomp($$stdout);
	} elsif($stderr) {
		$$stderr = capture_stderr { system($command); };
		chomp($stderr);
	} else {
		croak(sprintf('Could not execute command: %s', $!)) if system($command) == -1;
	}

	getExitCode();
}

=item executeNoWait($command, $stdoutHandler, $stderrHandler)

 Execute the given command without wait

 Param string $command Command to execute
 Param coderef $stdoutHandler STDOUT handler
 Param coderef $stderrHandler STDERR handler
 Return int Command exit code or croak or die on failure

=cut

sub executeNoWait($$$)
{
	my ($command, $stdoutHandler, $stderrHandler) = @_;

	ref $stdoutHandler eq 'CODE' or croak('Wrong STDOUT handler: Not a code reference');
	ref $stderrHandler eq 'CODE' or croak('Wrong STDERR handler: Not a code reference');

	debug($command);

	my $pid = open3(my $stdin, my $stdout, my $stderr = gensym, $command);
	close $stdin;
	$stdout->autoflush(1);
	$stderr->autoflush(1);

	my %buffers = ( $stdout => '', $stderr => '' );
	my $select = new IO::Select($stdout, $stderr);

	while($select->count()) {
		for my $fh ($select->can_read()) {
			my $ret = sysread($fh, $buffers{$fh}, 4096, length($buffers{$fh}));

			defined $ret or die($!);
			if ($ret == 0) {
				$select->remove($fh);
				close($fh);
				next;
			}

			$fh == $stderr ? $stderrHandler->(\$buffers{$stderr}) : $stdoutHandler->(\$buffers{$stdout});
		}
	}

	waitpid($pid, 0);
	getExitCode();
}

=item escapeShell($string)

 Escape the given string

 Param string $string String to escape
 Return string Escaped string

=cut

sub escapeShell($)
{
	my $string = shift;

	return $string if $string eq '' || $string =~ /^[a-zA-Z0-9_\-]+\z/;
	$string =~ s/'/'\\''/g;
	"'$string'";
}

=item getExitCode([ $ret = $? ])

 Return human exit code

 Param int $ret Raw exit code (default to $?)
 Return int exit code or croak on failure

=cut

sub getExitCode(;$)
{
	my $ret = shift // $?;

	if ($ret == -1) {
		croak(sprintf('Could not execute command: %s', $!));
	} elsif ($ret & 127) {
		croak(sprintf('Command died with signal %d, %s coredump', ($ret & 127), ($? & 128) ? 'with' : 'without'));
	} else {
		$ret = $ret >> 8;
	}

	$ret;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
