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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Debug;

use strict;
use warnings;

use iMSCP::Log;
use parent 'Common::SingletonClass', 'Exporter';

our @EXPORT = qw/
	debug warning error fatal newDebug endDebug getMessage getLastError getMessageByType silent verbose debugRegCallBack
/;

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
		debug("Debug messages off");
	}

	__PACKAGE__->getInstance()->{'verbose'} = $verbose;

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
			$line .= "[$_->{'when'}] [$_->{'tag'}] $_->{'message'}\nTraces: $_->{'longmess'}\n\n";
		}
	}
	$line;
}

sub output
{
	my $text = shift;
	my $options	= shift;

	$options = {} if ref $options ne 'HASH';

	if ($options->{'mode'} && lc($options->{'mode'}) eq 'fatal') {
		return "[ \033[0;31m" . ($options->{'text'} ? uc($options->{'text'}) : 'FATAL ERROR') . "\033[0m ] ${text}\n";
	} elsif ($options->{'mode'} && lc($options->{'mode'}) eq 'error') {
		return "[ \033[0;31m" . ($options->{'text'} ? uc($options->{'text'}) : 'ERROR') . "\033[0m ] ${text}\n";
	} elsif ($options->{'mode'} && lc($options->{'mode'}) eq 'warn'){
		return "[ \033[0;33m" . ($options->{'text'} ? uc($options->{'text'}) : 'WARN') . "\033[0m ] ${text}\n";
	} elsif ($options->{'mode'} && lc($options->{'mode'}) eq 'ok'){
		return "[ \033[0;32m" . ($options->{'text'} ? uc($options->{'text'}) : 'ok') . "\033[0m ] ${text}\n";
	} else {
		return "$text\n";
	}
}

sub debugRegCallBack
{
	my $self = __PACKAGE__->getInstance();
	my $code = shift;
	push @{$self->{'callBacks'}}, $code;
}

sub _init
{
	my $self = shift;

	$self->{'logLevels'} = [];
	$self->{'log'} = {};
	$self->{'lastLog'} = iMSCP::Log->new();
	$self->{'silent'} = 0;
	$self->{'verbose'} = 0;
	$self->{'callBacks'} = [];

	$self;
}

END
{
	my $exitCode = $?;

	my $self = __PACKAGE__->getInstance();
	my $logdir = $main::imscpConfig{'LOG_DIR'} || '/tmp';
	my $msg;

	&$_ for @{$self->{'callBacks'}};

	my $clearScreen = 1;
	#use iMSCP::HooksManager;
	iMSCP::HooksManager->getInstance()->trigger('beforeExit', \$exitCode, \$clearScreen);

	if($exitCode) {
		error("Exit code is $exitCode!");
	} else {
		debug("Exit code is $exitCode");
	}

	system 'clear' if $clearScreen;

	for (keys %{$self->{'log'}}) {
		next if $_ eq 'discard';

		my @warnings = getMessageByType('warn');
		my @errors = getMessageByType('error');
		my @fatals = getMessageByType('fatal error');

		$msg = "\n" . output('', { 'text' => 'WARNINGS', 'mode' => 'warn'}) . "\n" . join("\n", @warnings) . "\n\n" if @warnings > 0;
		$msg .= "\n" . output('', { 'text' => 'ERRORS', 'mode' => 'error'}) . "\n" . join("\n", @errors) . "\n\n" if @errors > 0;
		$msg .= "\n".output('', { 'text' => 'FATAL ERRORS', 'mode' => 'error'}) . "\n" . join("\n", @fatals) . "\n\n" if @fatals > 0;

		writeLogs($_, "$logdir/$_");
	}

	print STDERR $msg if $msg;

	$? = $exitCode;
}

1;
