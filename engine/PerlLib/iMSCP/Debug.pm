=head1 NAME

 iMSCP::Debug - Debug library

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

package iMSCP::Debug;

use strict;
use warnings;
use File::Spec;
use iMSCP::Log;
use parent 'Exporter';

our @EXPORT = qw/ debug warning error fatal newDebug endDebug getMessage getLastError getMessageByType setVerbose
    setDebug debugRegisterCallBack output silent /;

BEGIN {
    $SIG{'__DIE__'} = sub {
        fatal( @_, ( caller( 1 ) )[3] || 'main' ) if defined $^S && !$^S
    };
    $SIG{'__WARN__'} = sub {
        warning( @_, ( caller( 1 ) )[3] || 'main' );
    };
}

my $self;
$self = {
    debug           => 0,
    verbose         => 0,
    debug_callbacks => [],
    loggers         => [ iMSCP::Log->new( id => 'default' ) ],
    logger          => sub { $self->{'loggers'}->[$#{$self->{'loggers'}}] }
};

=head1 DESCRIPTION

 Debug library

=head1 CLASS METHODS

=over 4

=item setDebug( $debug )

 Enable or disable debug mode

 Param bool $debug Enable verbose mode if true, disable otherwise
 Return undef

=cut

sub setDebug
{
    if ( $_[0] ) {
        $self->{'debug'} = 1;
        return;
    }

    for( @{$self->{'loggers'}} ) { # Remove any debug log message from all loggers
        $_->retrieve( tag => 'debug', remove => 1 );
    }

    $self->{'debug'} = 0;
    undef;
}

=item setVerbose( $verbose )

 Enable or disable verbose mode

 Param bool $verbose Enable debug mode if true, disable otherwise
 Return undef

=cut

sub setVerbose
{
    $self->{'verbose'} = $_[0] // 0;
    undef;
}

=item silent( )

 Method kept for backward compatibility with plugins

 Return undef

=cut

sub silent
{
    undef;
}

=item newDebug( $logfileId )

 Create a new logger for the given log file identifier. New logger will becomes the current logger

 Param string $logfile Log file unique identifier (log file name)
 Return int 0

=cut

sub newDebug
{
    my ($logfileId) = @_;

    fatal( "A log file unique identifier is expected" ) unless $logfileId;

    for( @{$self->{'loggers'}} ) {
        die( "A logger with same identifier already exists" ) if $_->getId() eq $logfileId;
    }

    push @{$self->{'loggers'}}, iMSCP::Log->new( id => $logfileId );
    0;
}

=item endDebug( )

 Write all log messages from the current logger and remove it from loggers stack (unless it is the default logger)

 Return int 0

=cut

sub endDebug
{
    my $logger = $self->{'logger'}();

    return 0 if $logger->getId() eq 'default';

    pop @{$self->{'loggers'}}; # Remove logger from loggers stack

    # warn, error and fatal log messages must be always stored in default logger for later processing
    for( $logger->retrieve( tag => qr/(?:warn|error|fatal)/ ) ) {
        $self->{'loggers'}->[0]->store( %{$_} );
    }

    my $logDir = $main::imscpConfig{'LOG_DIR'} || '/tmp';
    if ( $logDir ne '/tmp' && !-d $logDir ) {
        require iMSCP::Dir;
        local $@;
        eval {
            iMSCP::Dir->new( dirname => $logDir )->make(
                {
                    user  => $main::imscpConfig{'ROOT_USER'},
                    group => $main::imscpConfig{'ROOT_GROUP'},
                    mode  => 0750
                }
            );
        };
        $logDir = '/tmp' if $@;
    }

    _writeLogfile( $logger, File::Spec->catfile( $logDir, $logger->getId()));
}

=item debug( $message [, $caller ] )

 Log a debug message in the current logger

 Param string $message Debug message
 Param string $caller OPTIONAL Caller
 Return undef

=cut

sub debug
{
    my ($message, $caller) = @_;
    $caller //= ( caller( 1 ) )[3] || 'main';

    $self->{'logger'}()->store( message => "$caller: $message", tag => 'debug' ) if $self->{'debug'};
    print STDOUT output( "$caller: $message", 'debug' ) if $self->{'verbose'};
    undef;
}

=item warning( $message [, $caller ] )

 Log a warning message in the current logger

 Param string $message Warning message
 Param string $caller OPTIONAL Caller
 Return undef

=cut

sub warning
{
    my ($message, $caller) = @_;
    $caller //= ( caller( 1 ) )[3] || 'main';

    $self->{'logger'}()->store( message => "$caller: $message", tag => 'warn' );
    undef;
}

=item error( $message [, $caller ] )

 Log an error message in the current logger

 Param string $message Error message
 Param string $caller OPTIONAL Caller
 Return undef

=cut

sub error
{
    my ($message, $caller) = @_;
    $caller //= ( caller( 1 ) )[3] || 'main';

    $self->{'logger'}()->store( message => "$caller: $message", tag => 'error' );
    undef;
}

=item fatal( $message [, $caller ] )

 Log a fatal message in the current logger and exit with status 255

 Param string $message Fatal message
 Param string $caller OPTIONAL Caller
 Return void

=cut

sub fatal
{
    my ($message, $caller) = @_;
    $caller //= ( caller( 1 ) )[3] || 'main';

    $self->{'logger'}()->store( message => "$caller: $message", tag => 'fatal' );
    exit 255;
}

=item getLastError()

 Get last error messages from the current logger as a string

 Return string Last error messages

=cut

sub getLastError
{
    scalar getMessageByType( 'error' );
}

=item getMessageByType( $type [, \%options ] )

 Get message by type from current logger, according given options

 Param string $type Type or regexp
 Param hash %option|\%options Hash containing options (amount, chrono, remove)
 Return array|string An array of messages or a string of messages

=cut

sub getMessageByType
{
    my ($type, $options) = @_;
    $options ||= {};

    my @messages = map { $_->{'message'} } $self->{'logger'}()->retrieve(
        tag    => ref $type eq 'Regexp' ? $type : qr/$type/i,
        amount => $options->{'amount'},
        chrono => $options->{'chrono'} // 1,
        remove => $options->{'remove'} // 0
    );
    wantarray ? @messages : join "\n", @messages;
}

=item output( $text [, $level ] )

 Prepare the given text to be show on the console according the given level

 Param string $text Text to format
 Param string $level OPTIONAL Format level
 Return string Formatted message

=cut

sub output
{
    my ($text, $level) = @_;

    return "$text\n" unless $level;

    my $output = '';

    if ( $level eq 'debug' ) {
        $output = "[\033[0;34mDEBUG\033[0m] $text\n";
    } elsif ( $level eq 'info' ) {
        $output = "[\033[0;34mINFO\033[0m]  $text\n";
    } elsif ( $level eq 'warn' ) {
        $output = "[\033[0;33mWARN\033[0m]  $text\n";
    } elsif ( $level eq 'error' ) {
        $output = "[\033[0;31mERROR\033[0m] $text\n";
    } elsif ( $level eq 'fatal' ) {
        $output = "[\033[0;31mFATAL\033[0m] $text\n";
    } elsif ( $level eq 'ok' ) {
        $output = "[\033[0;32mDONE\033[0m]  $text\n";
    } else {
        $output = "$text\n";
    }

    $output;
}

=item debugRegisterCallBack( $callback )

 Register the given debug callback

 Param callback Callback to register
 Return int 0

=cut

sub debugRegisterCallBack
{
    my ($callback) = @_;

    push @{$self->{'debug_callbacks'}}, $callback;
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _writeLogfile($logger, $logfilePath)

 Write all log messages from the given logger

 Param iMSCP::Log $logger Logger
 Param string $logfilePath Logfile path in which log messages must be writen

 Return int 0

=cut

sub _writeLogfile
{
    my ($logger, $logfilePath) = @_;

    # Make error message free of any ANSI color and end of line codes
    ( my $messages = _getMessages( $logger ) ) =~ s/\x1B\[([0-9]{1,3}((;[0-9]{1,3})*)?)?[m|K]//g;

    return 0 if $messages eq '';

    if ( open( my $fh, '>', $logfilePath ) ) {
        print { $fh } $messages;
        close $fh;
        return 0;
    }

    print output( sprintf( "Couldn't open log file `%s' for writing: %s", $logfilePath, $! ), 'error' );
    0;
}

=item _getMessages( $logger )

 Flush and return all log messages from the given logger as a string

 Param Param iMSCP::Log $logger Logger
 Return string String representing concatenation of all messages found in the given log object

=cut

sub _getMessages
{
    my ($logger) = @_;

    my $bf = '';
    for( $logger->flush() ) {
        $bf .= "[$_->{'when'}] [$_->{'tag'}] $_->{'message'}\n";
    }
    $bf;
}

=item END

 Process ending tasks and print warn, error and fatal log messages to STDERR if any

=cut

END {
    my $exitCode = $?;

    &{$_} for @{$self->{'debug_callbacks'}};

    my $countLoggers = scalar @{$self->{'loggers'}};
    while ( $countLoggers > 0 ) {
        endDebug();
        $countLoggers--;
    }

    for( $self->{'logger'}()->retrieve( tag => qr/(?:warn|error|fatal)/, remove => 1 ) ) {
        print STDERR output( $_->{'message'}, $_->{'tag'} );
    }

    $? = $exitCode;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
