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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Dialog::Whiptail;

use strict;
use warnings;

use FileHandle;
use iMSCP::Debug;
use iMSCP::Execute qw/execute/;
use Exporter;

use vars qw/@ISA @EXPORT/;
@ISA = ('Common::SingletonClass', 'iMSCP::Dialog::Dialog');
use Common::SingletonClass;
use iMSCP::Dialog::Dialog;

sub _init{
	my $self	= shift;
	debug('Starting...');

	$self->{autosize} = undef;

	$self->{'_opts'}->{'clear'}					= '';
	$self->{'_opts'}->{'defaultno'}				= undef;
	$self->{'_opts'}->{'default-item'}			= undef;
	$self->{'_opts'}->{'fb'}					= undef;
	$self->{'_opts'}->{'nocancel'}				= '';
	$self->{'_opts'}->{'yes-button'}			= undef;
	$self->{'_opts'}->{'no-button'}				= undef;
	$self->{'_opts'}->{'ok-button'}				= undef;
	$self->{'_opts'}->{'cancel-button'}			= undef;
	$self->{'_opts'}->{'noitem'}				= undef;
	$self->{'_opts'}->{'separate-output'}		= undef;
	$self->{'_opts'}->{'output-fd'}				= undef;
	$self->{'_opts'}->{'title'}					= undef;
	$self->{'_opts'}->{'backtitle'}				= undef;
	$self->{'_opts'}->{'scrolltext'}			= '';
	$self->{'_opts'}->{'topleft'}				= undef;

	$self->_find_bin('whiptail');
	$self->_getConsoleSize();

	debug('Ending...');
	0;
}

sub _getConsoleSize{
	my $self = shift;
	debug('Starting...');
	$self->{'lines'}	= 23;
	$self->{'columns'}	= 79;
	debug("Lines->$self->{'lines'}");
	debug("Columns->$self->{'columns'}");
	debug('Ending...');
}
sub _find_bin {
	debug('Starting...');

	my ($self, $variant)	= (shift, shift);
	my ($rs, $stdout, $stderr);
	$rs = execute("which $variant", \$stdout, \$stderr);
	debug("Found $stdout") if $stdout;
	fatal(": Can't find whiptail binary $stderr") if $stderr;

	$self->{'bin'} = $stdout if $stdout;
	fatal(': Can`t find whiptail binary '.$variant) unless (-x $self->{'bin'});

	debug('Ending...');
}

sub _execute{
	my ($self, $text, $init, $mode, $background) = (shift, shift, shift, shift, shift || 0);
	debug('Starting...');

	$self->endGauge();

	$text = $self->_strip_formats($text) unless( exists $self->{'_opts'}->{'colors'} );

	my $command = $self->_buildCommand();
	$text = $self->_clean($text);
	$init = $init ? $init : '';

	my $height = defined $self->{'autosize'} ? 0 : ($self->{'lines'});
	my $width = defined $self->{'autosize'} ? 0 : ($self->{'columns'});

	my ($return, $rv);
	$rv = execute("export TERM=linux;$self->{'bin'} $command --$mode '$text' $height $width $init", undef, \$return);

	debug('Returned text: '.$return) if($return);

	$self->_init() if($self->{'autoreset'});

	debug('Ending...');
	wantarray ? return ($rv, $return) : $return;
}


########################################################################################


sub radiolist{
	debug('Starting...');

	my $self = shift;
	my $text = shift;

	my @init = (@_);

	my $opts = '';
	$opts .= "'$_' '' ".($opts ? " off " : "on ") foreach (@init);

	debug('Ending...');
	return $self->_textbox($text, 'radiolist', (@init +1)." $opts");
}

sub passwordbox{
	debug('Starting...');

	my $self = shift;
	my $text = shift;
	my $init = shift || '';

	$self->{'_opts'}->{'insecure'} = undef;

	debug('Ending...');
	return $self->_textbox($text, 'passwordbox', "'$init'");
}

sub startGauge{
	my $self = shift;
	my $text = shift;
	my $init = shift || 0; #initial value

	debug('Starting...');

	$self->{'gauge'} ||= {};
	return(0) if (defined $self->{'gauge'}->{'FH'});

	$text = $self->_clean($text);
	$init = $init ? " $init" : 0;

	my $height = $self->{'autosize'} ? 0 : ($self->{'lines'});
	my $width = $self->{'autosize'} ? 0 : ($self->{'columns'});

	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;

	my $command = $self->_buildCommand();

	$command = "export TERM=linux;$self->{'bin'} $command --gauge '$text' $height $width $init";

	$self->{'_opts'}->{'begin'} = $begin;

	debug("$command");

	$self->{'gauge'}->{'FH'} = new FileHandle;
	$self->{'gauge'}->{'FH'}->open("| $command") || error("Can`t start gauge!");
	$SIG{PIPE} = \&endGauge;
	my $rv = $? >> 8;
	$self->{'gauge'}->{'FH'}->autoflush(1);
	debug("Returned value $rv");
	debug('Ending...');
	$rv;
}

sub needGauge{
	debug('Starting...');

	my $self	= shift;

	debug('Ending...');

	return 0 if $self->{'gauge'}->{'FH'};
	1;
}

sub setGauge{
	my $self	= shift;
	my $value	= shift;
	my $text	= shift || undef;

	debug('Starting...');

	return 0 unless $self->{'gauge'}->{'FH'};

	if($text){
		$text = "XXX\n$value\n".$self->_clean($text)."\nXXX\n$value\n" ;
	} else {
		$text = "$value\n";
	}

	debug("$text");

	my $fh = $self->{'gauge'}->{'FH'};

	print $fh $text;
	$SIG{PIPE} = \&endGauge;

	debug('Ending...');

	return(((defined $self->{'gauge'}->{'FH'}) ? 1 : 0));

}

sub endGauge{
	my $self = iMSCP::Dialog->factory();

	debug('Starting...');

	return 0 unless ref $self->{'gauge'}->{'FH'};
	$self->{'gauge'}->{'FH'}->close();
	delete($self->{'gauge'});

	debug('Ending...');
	0;
}

#sub textbox <file> <height> <width> # todo
#sub menu <text> <height> <width> <listheight> [tag item] ... # todo

sub fselect{fatal(': Not supported');}
sub tailbox{fatal(': Not supported');}
sub editbox{fatal(': Not supported');}
sub dselect{fatal(': Not supported');}

1;

__END__
