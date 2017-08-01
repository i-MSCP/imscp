=head1 NAME

 iMSCP::Execute - Allows to execute external commands

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use autouse 'Capture::Tiny' => qw/ capture capture_stdout capture_stderr /;
use Errno qw/ EINTR /;
use iMSCP::Debug qw/ debug error /;
use IO::Select;
use IPC::Open3;
use Symbol 'gensym';
use parent 'Exporter';

our @EXPORT = qw/ execute executeNoWait escapeShell getExitCode /;

=head1 DESCRIPTION

 This package provides a set of functions allowing to execute external commands.

=head1 FUNCTIONS

=over 4

=item execute( $command [, \$stdout = undef [, \$stderr = undef ] ] )

 Execute the given command

 Param string|array $command Command to execute
 Param string \$stdout OPTIONAL Variable for capture of STDOUT
 Param string \$stderr OPTIONAL Variable for capture of STDERR
 Return int Command exit code or die on failure

=cut

sub execute( $;$$ )
{
    my ($command, $stdout, $stderr) = @_;

    defined( $command ) or die( 'Missing $command parameter' );

    if ( $stdout ) {
        ref $stdout eq 'SCALAR' or die( "Expects a scalar reference as second parameter for capture of STDOUT" );
        ${$stdout} = '';
    }

    if ( $stderr ) {
        ref $stderr eq 'SCALAR' or die( "Expects a scalar reference as third parameter for capture of STDERR" );
        ${$stderr} = '';
    }

    my $list = ref $command eq 'ARRAY';
    debug( $list ? "@{$command}" : $command );

    if ( $stdout && $stderr ) {
        ( ${$stdout}, ${$stderr} ) = capture sub { system( $list ? @{$command} : $command ); };
        chomp( ${$stdout}, ${$stderr} );
    } elsif ( $stdout ) {
        ${$stdout} = capture_stdout sub { system( $list ? @{$command} : $command ); };
        chomp( ${$stdout} );
    } elsif ( $stderr ) {
        ${$stderr} = capture_stderr sub { system( $list ? @{$command} : $command ); };
        chomp( $stderr );
    } else {
        system( $list ? @{$command} : $command ) != -1 or die( sprintf( "Couldn't execute command: %s", $! ));
    }

    getExitCode();
}

=item executeNoWait( $command [, $subSTDOUT = CODE [, $subSTDERR = CODE ] ] )

 Execute the given command without wait, processing command STDOUT|STDERR line by line

 Param string|array $command Command to execute
 Param CODE OPTIONAL Subroutine for processing of command STDOUT line by line (default: print to STDOUT)
 Param CODE OPTIONAL Subroutine for processing of command STDERR (line by line) (default: print to STDERR)
 Return int Command exit code or die on failure

=cut

sub executeNoWait( $;$$ )
{
    my ($command, $subSTDOUT, $subSTDERR) = @_;

    $subSTDOUT ||= $subSTDOUT = sub { print STDOUT @_ };
    ref $subSTDOUT eq 'CODE' or die( 'Expects CODE as second parameter for STDOUT processing' );

    $subSTDERR ||= $subSTDERR = sub { print STDERR @_ };
    ref $subSTDERR eq 'CODE' or die( 'Expects CODE as third parameter for STDERR processing' );

    my $list = ref $command eq 'ARRAY';
    debug( $list ? "@{$command}" : $command );

    my $pid = open3( my $stdin, my $stdout, my $stderr = gensym, $list ? @{$command} : $command );
    close $stdin;

    my %buffers = ( $stdout => '', $stderr => '' );
    my $sel = IO::Select->new( $stdout, $stderr );

    while ( my @ready = $sel->can_read ) {
        for my $fh ( @ready ) {
            # Read 1 byte at a time to avoid ending with multiple lines
            my $ret = sysread( $fh, my $nextbyte, 1 );

            next if $!{'EINTR'}; # Ignore signal interrupt

            defined $ret or die( $! ); # Something is going wrong; Best is to abort early

            if ( $ret == 0 ) {
                # EOL
                $sel->remove( $fh );
                close( $fh );
                next;
            }

            $buffers{$fh} .= $nextbyte;

            next unless $buffers{$fh} =~ /\n\z/;
            $fh == $stdout ? $subSTDOUT->( $buffers{$fh} ) : $subSTDERR->( $buffers{$fh} );
            $buffers{$fh} = ''; # Reset buffer for next line
        }
    }

    waitpid( $pid, 0 );
    getExitCode();
}

=item escapeShell( $string )

 Escape the given string

 Param string $string String to escape
 Return string Escaped string

=cut

sub escapeShell( $ )
{
    my $string = shift;

    return $string if $string eq '' || $string =~ /^[a-zA-Z0-9_\-]+\z/;
    $string =~ s/'/'\\''/g;
    "'$string'";
}

=item getExitCode( [ $ret = $? ] )

 Return human exit code

 Param int $ret Raw exit code
 Return int exit code or die on failure

=cut

sub getExitCode( ;$ )
{
    my ($ret) = @_;
    $ret //= $?;

    if ( $ret == -1 ) {
        debug( "Couldn't execute command" );
        return 1;
    }

    if ( $ret & 127 ) {
        debug( sprintf( 'Command died with signal %d, %s coredump', ( $ret & 127 ),
                ( $? & 128 ) ? 'with' : 'without' ));
        return $ret;
    }

    $ret >> 8;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
