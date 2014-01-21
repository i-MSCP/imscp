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

package iMSCP::Debug;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Log;
use Text::Wrap;
use parent 'Exporter';

$Text::Wrap::columns = 80;
$Text::Wrap::break = qr/[\s\n\|]/;

our @EXPORT = qw/
	debug warning error fatal newDebug endDebug getMessage getLastError getMessageByType silent verbose
	debugRegisterCallBack output
/;

BEGIN
{
	$SIG{__DIE__} = sub {
		if(defined $^S && !$^S) {
			debug('Developer dump:');
			fatal(@_);
		}
	};

	$SIG{__WARN__} = sub {
		if(defined $^S && !$^S) {
			debug('Developer dumps:');
			error(@_);
		}
	};
}

my $self = {
	'silent' => 0,
	'verbose' => 1,
	'debugCallBacks' => []
};

$self->{'prevLog'} = $self->{'curLog'} = $self->{'logs'}->{'default'} = iMSCP::Log->new();

=item newDebug($logName)

 Create a new log object using the given name and set it as current log

 Return int 0

=cut

sub newDebug
{
	my $logName = $_[0];

	$self->{'logs'}->{$logName} = iMSCP::Log->new();
	$self->{'prevLog'} = $self->{'curLog'};
	$self->{'curLog'} = $self->{'logs'}->{$logName};

	0;
}

=item endDebug()

 Set current log to the previous

 Return int 0

=cut

sub endDebug
{
	$self->{'curLog'} = $self->{'prevLog'};

	0;
}

=item silent()

 Enter in silent mode

 Return int 0

=cut

sub silent
{
	$self->{'silent'} = int(shift || 0);
	debug("Entering in silent mode") if $self->{'silent'};

	0;
}

=item verbose()

 set verbose

 Return int 0

=cut

sub verbose
{
	my $verbose = shift || 0;

	unless($verbose) {
		# Remove any debug message from the current log
		getMessageByType('debug', { remove => 1 });
		debug('Debug mode off');
	}

	$self->{'verbose'} = $verbose;

	0;
}

=item debug($message)

 Log a debug message in the current log

 Return int 0

=cut

sub debug
{
	if($self->{'verbose'}) {
		my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
		my $message = shift || '';

		$self->{'curLog'}->store(message => "$caller: $message", tag => 'debug');
	}

	0;
}

=item warning($message)

 Log an error message in the current log and print it on STDERR if not in silent mode

 Return int 0

=cut

sub warning
{
	my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
	my $message = shift || '';
	my $verbosity = shift or 1;

	$self->{'curLog'}->store(message => "$caller: $message", tag => 'warn');

	print STDERR output("$caller: $message", 'warn') unless $self->{'silent'};

	0;
}

=item error($message)

 Log an error message in the current log and print it on STDERR if not in silent mode

 Return int 0

=cut

sub error
{
	my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
	my $message = shift || '';
	my $verbosity = shift or 1;

	$self->{'curLog'}->store(message => "$caller: $message", tag => 'error');

	print STDERR output("$caller: $message", 'error') unless $self->{'silent'};

	0;
}

=item fatal($message)

 Log a fatal error message in the current log, print it on STDERR if not in silent mode and exit

 Return void
=cut

sub fatal
{
	my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
	my $message = shift || '';
	my $verbosity = shift or 1;

	$self->{'curLog'}->store(message => "$caller: $message", tag => 'fatal error');

	print STDERR output("$caller: $message", 'fatal');

	exit 1;
}

sub getLastError
{
	getMessageByType('error');
}

sub getMessageByType
{
	my $mode = shift;
	my $opts = shift;

	$opts = {} unless ref $opts eq 'HASH';

	$mode = 'error' unless defined $mode && $mode ~~ ['debug', 'warn', 'error', 'fatal'];

	$opts->{'amount'} = 0 unless defined $opts->{'amount'} && $opts->{'amount'} =~ /\d+/;
	$opts->{'chrono'} = 1 unless defined $opts->{'chrono'} && $opts->{'chrono'} =~ /0|1/;
	$opts->{'remove'} = 0 unless defined $opts->{'remove'} && $opts->{'remove'} =~ /0|1/;

	my @logs = $self->{'curLog'}->retrieve(
		'tag' => qr/^$mode$/i,
		'amount' => $opts->{'amount'},
		'chrono' => $opts->{'chrono'},
		'remove' => $opts->{'remove'}
	);

	my @messages = map { $_->{'message'} } @logs;

	wantarray ? @messages : join "\n", @messages;
}

=item writeLogs($logName, $logFile)

 Write all messages from the given log to into the given log file

 Return int 0

=cut

sub writeLogs
{
	my $logName = shift;
	my $logFile = shift;

	my $logs = _getMessagesFromLog($logName);

	# Make error message free of any ANSI color and end of line codes
	$logs =~ s/\x1B\[([0-9]{1,3}((;[0-9]{1,3})*)?)?[m|K]//g;

	if(open(FH, '>', $logFile)) {
		print FH $logs;
		close FH;
	} else {
		print STDERR "Unable to open log file $logFile: $!";
	}

	0;
}

=item _getMessagesFromLog($logName)

 Return all messages from the given log as a string.

 Return string

=cut

sub _getMessagesFromLog
{
	my $logName = shift;

	my $buffer = '';

	if(exists $self->{'logs'}->{$logName}) {
		for($self->{'logs'}->{$logName}->flush()) {
			$buffer .= "[$_->{'when'}] [$_->{'tag'}] $_->{'message'}\n";
		}
	}

	$buffer;
}

=item output($text, $level)

 Prepare the given text to be show on the console according the given level

 Return string

=cut

sub output
{
	my $text = shift;
	my $level = shift;

	my $output = '';

	if($level) {
		if ($level eq 'fatal') {
			$output = "\n[\033[0;31mFATAL ERROR\033[0m]\n\n$text\n";
		} elsif ($level eq 'error') {
			$output = "\n[\033[0;31mERROR\033[0m]\n\n$text\n";
		} elsif ($level eq 'warn'){
			$output = "\n[\033[0;33mWARN\033[0m]\n\n$text\n";
		} elsif ($level eq 'ok'){
			$output = "\n[\033[0;32mOK\033[0m] $text\n";
		} else {
			$output = "\n$text\n\n";
		}
	} else {
		$output = "\n$text\n\n";
	}

	return wrap('', '', $output);
}

=item debugRegisterCallBack

 Register the given callback, which will be triggered before log processing

 Return int 0;

=cut

sub debugRegisterCallBack
{
	my $callback = shift;

	push @{$self->{'debugCallBacks'}}, $callback;

	0;
}

END
{
	my $exitCode = $?;

	&$_ for @{$self->{'debugCallBacks'}};

	if(%{$self->{'logs'}}) {
		if($exitCode) {
			error("Exit code: $exitCode");
		} else {
			debug("Exit code: $exitCode");
		}

		system('clear') if defined $ENV{'TERM'} && (! defined $ENV{'IMSCP_CLEAR_SCREEN'} || $ENV{'IMSCP_CLEAR_SCREEN'});

		my $logDir = ($main::imscpConfig{'LOG_DIR'} && -d $main::imscpConfig{'LOG_DIR'})
			? $main::imscpConfig{'LOG_DIR'} : '/tmp';

		my $msg = undef;

		for(keys %{$self->{'logs'}}) {
			next if $_ eq 'default';
			$self->{'curLog'} = $self->{'logs'}->{$_};

			my @warnings = getMessageByType('warn');
			my @errors = getMessageByType('error');
			my @fatals = getMessageByType('fatal');

			$msg = output(join("\n", @warnings), 'warn') if @warnings;
			$msg .= output(join("\n", @errors), 'error') if @errors;
			$msg .= output(join("\n", @fatals), 'fatal') if @fatals;

			writeLogs($_, "$logDir/$_");
		}

		print STDERR $msg if defined $msg;
	}

	$? = $exitCode;
}

1;
