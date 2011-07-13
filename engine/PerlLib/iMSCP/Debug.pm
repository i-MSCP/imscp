#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 by internet Multi Server Control Panel
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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
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
@EXPORT = qw/debug warning error fatal newDebug endDebug getMessage getLastError getMessageByType silent/;


BEGIN{

	$SIG{__DIE__} = sub {
		debug("Developer dump:");
		fatal("@_");
	};

	$SIG{__WARN__} = sub{
		debug("Developer dump:");
		error("@_");
	};

}

sub _init{
	my $self					= shift;
	$self->{log}				= {};
	$self->{logLevels}			= ();
	$self->{lastLog}			= Log::Message->new( private => 1);
	$self->{log}->{'default'}	= $self->{lastLog};
	$self->{silent}				= 0;
}

sub newDebug{
	my $debug	= shift || '';
	push(@{iMSCP::Debug->new()->{logLevels}}, iMSCP::Debug->new()->{lastLog});
	iMSCP::Debug->new()->{lastLog} = Log::Message->new( private => 1 );
	iMSCP::Debug->new()->{log}->{$debug} = iMSCP::Debug->new()->{lastLog};
}

sub endDebug{
	my $debug = iMSCP::Debug->new();
	if($debug->{logLevels} && (@{$debug->{logLevels}} > 0) ){
		$debug->{lastLog} = pop(@{$debug->{logLevels}});
		return 0;
	}
	1;
}

sub silent{
	iMSCP::Debug->new()->{silent} = shift || 0;
}

sub debug{
	my $message		= shift || '';
	iMSCP::Debug->new()->{lastLog}->store(message => $message, tag => 'DEBUG', level => 'log');
}

sub warning{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self = iMSCP::Debug->new();
	$self->{lastLog}->store(message => $message, tag => 'WARNING', level => $verbosity ? 'cluck' : 'log');
	print STDERR output("$message", {mode=>'warning'}) unless $self->{silent};
}

sub error{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self = iMSCP::Debug->new();
	$self->{lastLog}->store(message => $message, tag => 'ERROR', level => $verbosity ? 'cluck' : 'log');
	print STDERR output("$message", {mode=>'error'}) unless $self->{silent};
}

sub fatal{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self = iMSCP::Debug->new();
	$self->{lastLog}->store(message => $message, tag => 'FATAL ERROR', level => $verbosity ? 'cluck' : 'log');
	while(!$self->endDebug()){};
	print STDERR output("$message", {mode=>'fatal'});
	exit 1;
}

sub getLastError{
	my $self = iMSCP::Debug->new();
	my $last = $self->getMessageByType('ERROR', {amount => 1, chrono => 0});
	return $last;
}

sub getMessageByType{
	my $self	= iMSCP::Debug->new();
	my $mode	= uc(shift);
	my $opts	= shift;
	$opts = {} unless ref $opts eq 'HASH';

	$mode = 'ERROR' unless( defined($mode) && $mode =~ 'DEBUG|WARNING|ERROR');

	$opts->{amount}	= 0 unless( defined($opts->{amount}) && $opts->{amount} =~ /\d+/);
	$opts->{chrono}	= 1 unless (defined($opts->{chrono}) && $opts->{chrono} =~ /0|1/);
	$opts->{remove}	= 0 unless (defined($opts->{remove}) && $opts->{remove} =~ /0|1/);

	my $amount	= shift || 1;
	my @log		= $self->{lastLog}->retrieve(
		tag		=> qr/$mode$/i,
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

	return _getMessageLevel($log) if ( $log );
	foreach my $key (keys %{$self->{log}}){
		$line .= _getMessageLevel($key);
	}

	if($file){
		my $opened = open(DEBUG, '>:utf8', $file);
		print STDERR 'Can\'t save file '.$file.'!' if(!$opened);
		print DEBUG $line if($opened);
		close(DEBUG) if($opened);
	}

	return  $line;
}

sub _getMessageLevel{
	my $log = shift;

	my $self = iMSCP::Debug->new();

	my $line = '';

	if ( $self->{log}->{$log} ){
		foreach my $log ($self->{log}->{$log}->flush()){
			$line  .= "[".$log->tag."] [".$log->when."] ".$log->message."\n";
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

1;

