#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Debug;

use strict;
use warnings;

use vars qw/@ISA @EXPORT_OK @EXPORT %EXPORT_TAGS/;
use Exporter;
use Common::SingletonClass;
use Log::Message;
use Carp;

@ISA = ('Common::SingletonClass', 'Exporter');
@EXPORT = qw/debug warning error fatal newDebug endDebug getMessage getLastError getMessageByType silent verbose debugRegCallBack/;

BEGIN{

	$SIG{__DIE__} = sub {
		if(defined $^S && !$^S){
			debug("Developer dump:");
			fatal("@_");
		}
	};

	$SIG{__WARN__} = sub{
		if(defined $^S && !$^S){
			debug("Developer dump:");
			error("@_");
		}
	};

}

sub _init{
	my $self					= shift;
	$self->{log}				= {};
	$self->{logLevels}			= ();
	$self->{lastLog}			= Log::Message->new( private => 1);
	$self->{log}->{'discard'}	= $self->{lastLog};
	$self->{silent}				= 0;
	$self->{verbose}			= 1;
}

sub newDebug{
	my $debug	= shift || '';
	push(@{iMSCP::Debug->new()->{logLevels}}, iMSCP::Debug->new()->{lastLog});
	iMSCP::Debug->new()->{lastLog} = Log::Message->new( private => 1 );
	iMSCP::Debug->new()->{log}->{$debug} = iMSCP::Debug->new()->{lastLog};
}

sub endDebug{
	my $self = iMSCP::Debug->new();
	#my @warnings	= getMessageByType('WARNING');
	#my @errors		= getMessageByType('ERROR');

	if($self->{logLevels} && (@{$self->{logLevels}} > 0) ){
		$self->{lastLog} = pop(@{$self->{logLevels}});
	}
	#$self->{lastLog}->store(message => join("\n", @warnings), tag => 'WARNING', level => 'log') if(@warnings > 0);
	#$self->{lastLog}->store(message => join("\n", @errors), tag => 'ERROR', level => 'log') if(@errors > 0);
}

sub silent{
	iMSCP::Debug->new()->{silent} = shift || 0;
	debug("Enter silent mode") if iMSCP::Debug->new()->{silent};
}

sub verbose{
	my $verbose = shift || 0;
	unless($verbose){
		getMessageByType( 'DEBUG', {remove => 1});
		debug("Debug messages off");
	}
	iMSCP::Debug->new()->{verbose}= $verbose;
}

sub debug{
	return unless iMSCP::Debug->new()->{verbose};
	my $message		= shift || '';
	my $self		= iMSCP::Debug->new();
	my $caller		= (caller(1))[3] ? (caller(1))[3] : 'main';

	$self->{lastLog}->store(message => "$caller: $message", tag => 'DEBUG', level => 'log');
}

sub warning{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self		= iMSCP::Debug->new();
	my $caller		= (caller(1))[3] ? (caller(1))[3] : 'main';

	$self->{lastLog}->store(message => "$caller: $message", tag => 'WARNING', level => $verbosity ? 'cluck' : 'log');
	print STDERR output("$caller: $message", {mode=>'warning'}) unless $self->{silent};
}

sub error{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self		= iMSCP::Debug->new();
	my $caller		= (caller(1))[3] ? (caller(1))[3] : 'main';

	$self->{lastLog}->store(message => "$caller: $message", tag => 'ERROR', level => $verbosity ? 'cluck' : 'log');
	print STDERR output("$caller: $message", {mode=>'error'}) unless $self->{silent};
}

sub fatal{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self		= iMSCP::Debug->new();
	my $caller 		= (caller(1))[3] ? (caller(1))[3] : 'main';

	$self->{lastLog}->store(message => "$caller: $message", tag => 'FATAL ERROR', level => $verbosity ? 'cluck' : 'log');

	print STDERR output("$caller: $message", {mode=>'fatal'});
	exit 1;
}

sub getLastError{
	my $self = iMSCP::Debug->new();
	my $last = getMessageByType('ERROR', {amount => 1, chrono => 0});
	return $last;
}

sub getMessageByType{
	my $self	= iMSCP::Debug->new();
	my $mode	= uc(shift);
	my $opts	= shift;
	$opts = {} unless ref $opts eq 'HASH';

	$mode = 'ERROR' unless( defined($mode) && $mode =~ /DEBUG|WARNING|ERROR|FATAL ERROR/i);

	$opts->{amount}	= 0 unless (defined($opts->{amount}) && $opts->{amount} =~ /\d+/);
	$opts->{chrono}	= 1 unless (defined($opts->{chrono}) && $opts->{chrono} =~ /0|1/);
	$opts->{remove}	= 0 unless (defined($opts->{remove}) && $opts->{remove} =~ /0|1/);

	my @log		= $self->{lastLog}->retrieve(
		tag		=> qr/^$mode$/i,
		amount	=> $opts->{amount},
		chrono	=> $opts->{chrono},
		remove	=> $opts->{remove}
	);
	my @result = ();
	push @result, $_->message foreach(@log);
	return (wantarray ? @result : join "\n", @result);
}

sub getMessage{
	my $log		= shift;
	my $file	= shift;
	my $line	= '';
	my $self	= iMSCP::Debug->new();

	if ($log){
		$line = _getMessageLevel($log);
	} else {
		foreach my $key (keys %{$self->{log}}){
			$line .= _getMessageLevel($key);
		}
	}

	if($file){
		if(open(FH, '>', $file)){
			print FH $line;
			close (FH);
		} else {
			print STDERR 'Can\'t save file '.$file.'!';
		}
	}

	return  $line;
}

sub _getMessageLevel{
	my $log = shift;

	my $self = iMSCP::Debug->new();

	my $line = '';

	if ( $self->{log}->{$log} ){
		foreach my $log ($self->{log}->{$log}->flush()){
			$line  .= "[".$log->tag."] [".$log->when."] ".$log->message."\n" if $log;
		}
	}

	return  $line;
}

sub output{

	my $text	= shift;
	my $options	= shift;

	$options = {} if ref $options ne 'HASH';

	if ($options->{mode} && lc($options->{mode}) eq 'fatal') {
		return "[ \033[0;31m".($options->{text} ? uc($options->{text}) : 'FATAL ERROR')."\033[0m ] ${text}\n";
	} elsif ($options->{mode} && lc($options->{mode}) eq 'error') {
		return "[ \033[0;31m".($options->{text} ? uc($options->{text}) : 'ERROR')."\033[0m ] ${text}\n";
	} elsif ($options->{mode} && lc($options->{mode}) eq 'warning'){
		return "[ \033[0;33m".($options->{text} ? uc($options->{text}) : 'WARNING')."\033[0m ] ${text}\n";
	} elsif ($options->{mode} && lc($options->{mode}) eq 'ok'){
		return "[ \033[0;32m".($options->{text} ? uc($options->{text}) : 'OK')."\033[0m ] ${text}\n";
	} else {
		return "$text\n";
	}
}

sub debugRegCallBack{
	my $self	= iMSCP::Debug->new();
	my $code	= shift;
	push @{$self->{callBacks}}, $code;
}

END{
	my $exitCode = $?;

	my $self	= iMSCP::Debug->new();
	my $logdir	= $main::imscpConfig{LOG_DIR} || $main::defaultConf{LOG_DIR} || '/tmp';
	my $msg;

	&$_ foreach (@{$self->{callBacks}});

	if($exitCode){
		error("Exit code is $exitCode!");
	} else {
		debug("Exit code is $exitCode");
	}

	system 'clear';

	for (keys %{$self->{log}}) {
			next if $_ eq 'discard';
			my @warnings	= getMessageByType('WARNING');
			my @errors		= getMessageByType('ERROR');
			my @fatals		= getMessageByType('FATAL ERROR');

			$msg	 = "\n".output("", {text=> 'WARNINGS', mode => 'warning'})."\n"		. join("\n", @warnings)	. "\n" if @warnings > 0;
			$msg	.= "\n".output("", {text=> 'ERRORS', mode => 'error'})."\n"			. join("\n", @errors)	. "\n" if @errors > 0;
			$msg	.= "\n".output("", {text=> 'FATAL ERRORS', mode => 'error'})."\n"	. join("\n", @fatals)	. "\n" if @fatals > 0;
			getMessage($_, "$logdir/$_");
	}

	print STDERR $msg if $msg;

	$? = $exitCode;
}

1;
