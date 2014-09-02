#!/usr/bin/perl

=head1 NAME

 iMSCP::Debug - Debug library

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
	debug warning error fatal newDebug endDebug getMessage getLastError getMessageByType setVerbose setDebug
	debugRegisterCallBack output
/;

BEGIN
{
	# Handler which trap uncaught exceptions
	$SIG{__DIE__} = sub { fatal(@_) if defined $^S && ! $^S };

	# Handler which trap warns
	$SIG{__WARN__} = sub { warning(@_); };
}

my $self = {
	'debug' => 0,
	'verbose' => 0,
	'debugCallBacks' => [],
	'targets' => [ iMSCP::Log->new('id' => 'screen') ]
};

$self->{'screen'} = $self->{'target'} = $self->{'targets'}->[0];

=head1 DESCRIPTION

 Debug library

=head1 CLASS METHODS

=over 4

=item setDebug($debug)

 Enable or disable debug mode

 Param bool $debug Enable verbose mode if true, disable otherwise
 Return undef

=cut

sub setDebug
{
	if(shift) {
		$self->{'debug'} = 1;
	} else {
		# Remove any debug message from the current target
		getMessageByType('debug', { remove => 1 });
		debug('Debug mode is disabled');
		$self->{'debug'} = 0;
	}

	undef;
}

=item setVerbose()

 Enable or disable verbose mode

 Param bool $debug Enable debug mode if true, disable otherwise
 Return undef

=cut

sub setVerbose
{
	$self->{'verbose'} = shift // 0;

	undef;
}

=item newDebug($logfile)

 Create a new log object for the given logfile and set it as current target for new messages

 Param string $logfile Logfile unique identifier
 Return int 0

=cut

sub newDebug
{
	my $logfile = shift || '';

	fatal("logfile name expected") if ref $logfile || $logfile eq '';

	$self->{'target'} = iMSCP::Log->new('id' => $logfile);

	push @{$self->{'targets'}}, $self->{'target'};

	0;
}

=item endDebug()

 Write current logfile and set the target for new messages to the previous log object

 Return int 0

=cut

sub endDebug
{
	my $target = pop @{$self->{'targets'}};
	my $targetId = $target->getId();

	if($targetId ne 'screen') {
		my @firstItems = (@{$self->{'targets'}} == 1) ? $self->{'screen'}->flush() : ();

		# Retrieve any log which must be printed to screen and store them in the appropriate log object
		for my $item($target->retrieve( tag => qr/^(?:warn|error|fatal)/i ), @firstItems) {
			$self->{'screen'}->store( when => $item->{'when'}, message => $item->{'message'}, tag => $item->{'tag'} );
		}

		my $logDir = $main::imscpConfig{'LOG_DIR'} || '/tmp';

		unless(-d $main::imscpConfig{'LOG_DIR'}) {
			require iMSCP::Dir;
			my $rs = iMSCP::Dir->new('dirname', $logDir)->make(
				{
					'user' => $main::imscpConfig{'ROOT_USER'},
					'group' => $main::imscpConfig{'ROOT_GROUP'},
					'mode' => 0750
				}
			);
			$logDir = '/tmp' if $rs;
		}

		# Write logfile
		_writeLogfile($target, "$logDir/$targetId");

		# Set previous log object as target for new messages
		$self->{'target'} = @{$self->{'targets'}}[$#{$self->{'targets'}}];
	} else {
		push @{$self->{'targets'}}, $target;
		$self->{'target'} = $self->{'screen'};
	}

	0;
}

=item debug($message)

 Log a debug message

 Param string $message Debug message
 Return int undef

=cut

sub debug
{
	my $message = shift;
	my $caller = (caller(1))[3] || 'main';

	$self->{'target'}->store( message => "$caller: $message", tag => 'debug' ) if $self->{'debug'};

	print STDOUT output("$caller: $message", 'debug') if $self->{'verbose'};

	undef;
}

=item warning($message)

 Log an error message and print it on STDERR if not in silent mode

 Param string $message Warning message
 Return int undef

=cut

sub warning
{
	my $message = shift;
	my $caller = (caller(1))[3] || 'main';

	$self->{'target'}->store( message => "$caller: $message", tag => 'warn' );

	undef;
}

=item error($message)

 Log an error message and print it on STDERR if not in silent mode

 Param string $message Error message
 Return int undef

=cut

sub error
{
	my $message = shift;
	my $caller = (caller(1))[3] || 'main';

	$self->{'target'}->store( message => "$caller: $message", tag => 'error' );

	0;
}

=item fatal($message)

 Log a fatal error message and print it on STDERR if not in silent mode and exit

 Param string $message Fatal message
 Return void

=cut

sub fatal
{
	my $message = shift;
	my $caller = (caller(1))[3] || 'main';

	$self->{'target'}->store( message => "$caller: $message", tag => 'fatal' );

	exit ($? ||= 255);
}

=item getLastError()

 Get last error message

 Return string last error message

=cut

sub getLastError
{
	getMessageByType('error');
}

=item getMessageByType($type = 'error', [ %option | \%options ])

 Get message by type

 Param string $type Type or regexp
 Param hash %option|\%options Hash containing options (amount, chrono, remove)
 Return array|string Either an array containing messages or a string representing concatenation of messages

=cut

sub getMessageByType
{
	my $type = shift || '';

	my %options = (@_ && ref $_[0] eq 'HASH') ? %{$_[0]} : @_;

	my @messages = map { $_->{'message'} } $self->{'target'}->retrieve(
		'tag' => (ref $type eq 'Regexp') ? $type : qr/^$type$/i,
		'amount' => $options{'amount'},
		'chrono' => $options{'chrono'} // 1,
		'remove' => $options{'remove'} // 0
	);

	wantarray ? @messages : join "\n", @messages;
}

=item output($text, $level)

 Prepare the given text to be show on the console according the given level

 Return string Formatted message

=cut

sub output
{
	my ($text, $level) = @_;

	my $output = '';

	if($level) {
		if ($level eq 'fatal') {
			$output = "\n[\033[0;31mFATAL\033[0m]\n\n$text\n";
		} elsif ($level eq 'error') {
			$output = "\n[\033[0;31mERROR\033[0m]\n\n$text\n";
		} elsif ($level eq 'warn'){
			$output = "\n[\033[0;33mWARN\033[0m]\n\n$text\n";
		} elsif ($level eq 'ok'){
			$output = "\n[\033[0;32mOK\033[0m] $text\n";
		} else {
			$output = "$text\n";
		}
	} else {
		$output = "\n$text\n\n";
	}

	wrap('', '', $output);
}

=item debugRegisterCallBack($callback)

 Register the given callback, which will be triggered before log processing

 Param callback Callback to register
 Return int 0;

=cut

sub debugRegisterCallBack
{
	my $callback = shift;

	push @{$self->{'debugCallBacks'}}, $callback;

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _writeLogfile($logObject, $logfilePath)

 Write all messages for the given log

 Param iMSCP::Log $logObject iMSCP::Log object representing a logfile
 Param string $logfilePath Logfile path

 Return int 0

=cut

sub _writeLogfile
{
	my ($logObject, $logfilePath) = @_;

	# Make error message free of any ANSI color and end of line codes
	(my $messages = _getMessages($logObject)) =~ s/\x1B\[([0-9]{1,3}((;[0-9]{1,3})*)?)?[m|K]//g;

	if(open(FH, '>utf8', $logfilePath)) {
		print FH $messages;
		close FH;
	} else {
		print STDERR "Unable to open log file $logfilePath for writting: $!";
	}

	0;
}

=item _getMessages($logObject)

 Flush and return all messages for the given log object as a string

 Param iMSCP::Log $logObject iMSCP::Log object representing a logfile
 Return string String representing concatenation of all messages found in the given log object

=cut

sub _getMessages
{
	my $logObject = shift;

	my $bf;

	for($logObject->flush()) {
		$bf .= "[$_->{'when'}] [$_->{'tag'}] $_->{'message'}\n";
	}

	$bf;
}

END
{
	my $exitCode = $?;

	&$_ for @{$self->{'debugCallBacks'}};

	if($exitCode && $exitCode ne 50) { # 50 is returned when ESC is pressed (dialog)
		$self->{'target'}->store( message => "Exit code: $exitCode", tag => 'fatal' );
	}

	#system('tput clear') if defined $ENV{'TERM'} && (!defined $ENV{'IMSCP_CLEAR_SCREEN'} || $ENV{'IMSCP_CLEAR_SCREEN'});

	endDebug() for @{$self->{'targets'}};

	my @logs = $self->{'screen'}->retrieve( tag => qr/^(?:warn|error|fatal)$/ );

	if(@logs) {
		my @messages;
		for my $level('warn', 'error', 'fatal') {
			my @wrkLogs = @logs;
			my @items = grep { ($_->{'tag'} eq $level) ? $_ = $_->{'message'} : 0 } @wrkLogs;
			push @messages, output(join("\n", @items), $level) if @items;
		}

		print STDERR "@messages";
	}

	$? = $exitCode;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
