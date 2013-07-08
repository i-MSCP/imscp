#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Debug;

use strict;
use warnings;

use iMSCP::Log;
use Text::Wrap;
use parent 'Common::SingletonClass', 'Exporter';

$Text::Wrap::columns = 80;
$Text::Wrap::break = qr/[\s\n\|]/;

our @EXPORT = qw/
	debug warning error fatal newDebug endDebug getMessage getLastError getMessageByType silent verbose
	backtrace debugRegisterCallBack output
/;

=item
BEGIN
{
	$SIG{__DIE__} = sub {
		if(defined $^S && !$^S) {
			debug('Developer dump:');
			fatal("@_");
		}
	};

	$SIG{__WARN__} = sub {
		if(defined $^S && !$^S) {
			debug('Developer dumps:');
			error("@_");
		}
	};
}
=cut

sub newDebug
{
	my $self = __PACKAGE__->getInstance();
	my $logFilePath = shift;

	push(@{$self->{'logLevels'}}, $self->{'lastLog'});
	$self->{'lastLog'} = iMSCP::Log->new();
	$self->{'log'}->{$logFilePath} = $self->{'lastLog'};

	undef;
}

sub endDebug
{
	my $self = __PACKAGE__->getInstance();

	if(@{$self->{'logLevels'}}) {
		$self->{'lastLog'} = pop @{$self->{'logLevels'}};
	}

	undef;
}

sub silent
{
	my $self = __PACKAGE__->getInstance();

	my $silent = shift || 0;

	$self->{'silent'} = int($silent);
	debug("Entering in silent mode") if $silent;

	undef;
}

sub verbose
{
	my $verbose = shift || 0;

	unless($verbose) {
		getMessageByType('debug', { 'remove' => 1 });
		debug("Debug mode off");
	}

	__PACKAGE__->getInstance()->{'verbose'} = $verbose;

	undef;
}

sub backtrace
{
	my $backtrace = shift || 0;

	__PACKAGE__->getInstance()->{'backtrace'} = $backtrace;

	undef;
}


sub debug
{
	my $self = __PACKAGE__->getInstance();

	if($self->{'verbose'}) {
		my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
		my $message = shift || '';

		$self->{'lastLog'}->store(message => "$caller: $message", tag => 'debug', level => 'log');
	}

	undef;
}

sub warning
{
	my $self = __PACKAGE__->getInstance();
	my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
	my $message = shift || '';
	my $verbosity = shift or 1;

	$self->{'lastLog'}->store(
		message => "$caller: $message", tag => 'warn', level => $verbosity ? 'cluck' : 'log'
	);

    print STDERR output("$caller: $message", { mode => 'warn' }) unless $self->{'silent'};

    undef;
}

sub error
{
	my $self = __PACKAGE__->getInstance();
	my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
	my $message = shift || '';
	my $verbosity = shift or 1;

	$self->{'lastLog'}->store(
		message => "$caller: $message", tag => 'error', level => $verbosity ? 'cluck' : 'log'
	);

    print STDERR output("$caller: $message", { mode => 'error' }) unless $self->{'silent'};

    undef;
}

sub fatal
{
	my $self = __PACKAGE__->getInstance();
	my $caller = (caller(1))[3] ? (caller(1))[3] : 'main';
	my $message = shift || '';
	my $verbosity = shift or 1;

	$self->{'lastLog'}->store(
		message => "$caller: $message", tag => 'fatal error', level => $verbosity ? 'cluck' : 'log'
	);

    print STDERR output("$caller: $message", { mode => 'fatal' });

    exit 1;
}

sub getLastError
{
	getMessageByType('error');
}

sub getMessageByType
{
	my $self = __PACKAGE__->getInstance();
	my $mode = shift;
	my $opts = shift;

	$opts = {} unless ref $opts eq 'HASH';

	$mode = 'error' unless defined $mode && $mode ~~ ['debug', 'warn', 'error', 'fatal error'];

	$opts->{'amount'} = 0 unless defined $opts->{'amount'} && $opts->{'amount'} =~ /\d+/;
	$opts->{'chrono'} = 1 unless defined $opts->{'chrono'} && $opts->{'chrono'} =~ /0|1/;
	$opts->{'remove'} = 0 unless defined $opts->{'remove'} && $opts->{'remove'} =~ /0|1/;

	my @logs = $self->{'lastLog'}->retrieve(
		'tag' => qr/^$mode$/i,
		'amount' => $opts->{'amount'},
		'chrono' => $opts->{'chrono'},
		'remove' => $opts->{'remove'}
	);

	my @messages = ();
	push @messages, $_->{'message'} for @logs;

	(wantarray ? @messages : join "\n", @messages);
}

sub writeLogs
{
	my $self = __PACKAGE__->getInstance();
	my $log = shift;
	my $file = shift;
	my $line = '';

	if ($log) {
		$line = _getMessageLevel($log);
	} else {
		for (keys %{$self->{'log'}}) {
			$line .= _getMessageLevel($_);
		}
	}

	if($file) {
		if(open(FH, '>', $file)) {
			print FH $line;
			close (FH);
		} else {
			print STDERR "Unable to save log file $file";
		}
	}

	$line;
}

sub _getMessageLevel
{
	my $self = __PACKAGE__->getInstance();
	my $log = shift;
	my $line = '';

	if ($self->{'log'}->{$log}) {
		for ($self->{'log'}->{$log}->flush()) {
			next unless defined $_;

			$line .= "[$_->{'when'}] [$_->{'tag'}] $_->{'message'}\n";
			$line .= "Traces: $_->{'longmess'}\n\n" if $self->{'backtrace'};
		}
	}

	$line;
}

sub output
{
	my $text = shift;
	my $mode = shift;

	my $output = '';

	if($mode) {
		if ($mode eq 'fatal') {
			$output = "\n[\033[0;31m FATAL ERROR \033[0m]$text\n";
		} elsif ($mode eq 'error') {
			$output = "\n[\033[0;31m ERROR \033[0m]\n\n$text\n";
		} elsif ($mode eq 'warn'){
			$output = "\n[\033[0;33m WARN \033[0m]\n\n$text\n";
		} elsif ($mode eq 'ok'){
			$output = "\n[\033[0;32m OK \033[0m]\n\n$text\n";
		}
	} else {
		$output = "\n$text\n\n"
	}

	return wrap('', '', $output);
}

sub debugRegisterCallBack
{
	my $self = __PACKAGE__->getInstance();
	my $callback = shift;

	push @{$self->{'debugCallBacks'}}, $callback;

	0;
}

sub _init
{
	my $self = shift;

	$self->{'logLevels'} = [];
	$self->{'log'} = {};
	$self->{'lastLog'} = iMSCP::Log->new();
	$self->{'silent'} = 0;
	$self->{'verbose'} = 1;
	$self->{'backtrace'} = 0;
	$self->{'debugCallBacks'} = [];

	$self;
}

END
{
	my $exitCode = $?;

	my $self = __PACKAGE__->getInstance();

	&$_ for @{$self->{'debugCallBacks'}};

	if($exitCode) {
		error("Exit code is $exitCode!");
	} else {
		debug("Exit code is $exitCode");
	}

	system('clear') if $ENV{'TERM'};

	my $logdir = $main::imscpConfig{'LOG_DIR'} || '/tmp';
	my $msg;

	for (keys %{$self->{'log'}}) {
		next if $_ eq 'discard';

		my @warnings = getMessageByType('warn');
		my @errors = getMessageByType('error');
		my @fatals = getMessageByType('fatal error');

		$msg = output(join("\n", @warnings), 'warn') if @warnings;
		$msg .= output(join("\n", @errors), 'error') if @errors;
		$msg .= output(join("\n", @fatals), 'fatal') if @fatals;

		writeLogs($_, "$logdir/$_");
	}

	print STDERR $msg if $msg;

	$? = $exitCode;
}

1;
