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

package iMSCP::Dialog::Dialog;

use strict;
use warnings;

use FileHandle;
use iMSCP::Debug;
use iMSCP::Execute qw/execute/;
use Exporter;
use Common::SingletonClass;

use vars qw/@ISA @EXPORT/;
@ISA = ('Common::SingletonClass');

sub _init{
	my $self	= shift;

	$self->{'autosize'}						= undef;
	$self->{'autoreset'}					= '';
	$self->{'lines'}						= undef;
	$self->{'columns'}						= undef;

	$self->{'_opts'}->{'title'}				= $self->{'args'}->{'title'} || undef;
	$self->{'_opts'}->{'backtitle'}			= $self->{'args'}->{'backtitle'} || undef;

	$self->{'_opts'}->{'colors'}			= '';
	$self->{'_opts'}->{'begin'}				= [1, 0];

	$self->{'_opts'}->{'exit-label'}		= $self->{'args'}->{'exit-label'} || undef;
	$self->{'_opts'}->{'no-label'}			= $self->{'args'}->{'no-label'} || undef;
	$self->{'_opts'}->{'ok-label'}			= $self->{'args'}->{'ok-label'} || undef;
	$self->{'_opts'}->{'cancel-label'}		= $self->{'args'}->{'cancel-label'} || undef;
	$self->{'_opts'}->{'help-label'}		= $self->{'args'}->{'help-label'} || undef;
	$self->{'_opts'}->{'extra-label'}		= $self->{'argsextra-label'} || undef;
	$self->{'_opts'}->{'yes-label'}			= $self->{'args'}->{'yes-label'} || undef;

	$self->{'_opts'}->{'extra-button'}		= $self->{'args'}->{'extra-button'} || undef;
	$self->{'_opts'}->{'help-button'}		= $self->{'args'}->{'help-button'} || undef;

	$self->{'_opts'}->{'defaultno'}			= $self->{'args'}->{'defaultno'} || undef;
	$self->{'_opts'}->{'default-item'}		= $self->{'args'}->{'default-item'} || undef;

	$self->{'_opts'}->{'no-cancel'}			= $self->{'args'}->{'no-cancel'} || undef;
	$self->{'_opts'}->{'no-ok'}				= $self->{'args'}->{'no-ok'} || undef;
	$self->{'_opts'}->{'clear'}				= '';

	$self->{'_opts'}->{'column-separator'}	= undef;

	$self->{'_opts'}->{'cr-wrap'}			= undef;
	$self->{'_opts'}->{'no-collapse'}		= undef;
	$self->{'_opts'}->{'trim'}				= undef;
	$self->{'_opts'}->{'date-format'}		= undef;

	$self->{'_opts'}->{'help-status'}		= undef;
	$self->{'_opts'}->{'insecure'}			= undef;
	$self->{'_opts'}->{'item-help'}			= undef;
	$self->{'_opts'}->{'max-input'}			= undef;
	$self->{'_opts'}->{'no-shadow'}			= undef;
	$self->{'_opts'}->{'shadow'}			= undef;
	$self->{'_opts'}->{'single-quoted'}		= undef;
	$self->{'_opts'}->{'tab-correct'}		= undef;
	$self->{'_opts'}->{'tab-len'}			= undef;
	$self->{'_opts'}->{'time-out'}			= undef;

	$self->{'_opts'}->{'height'}			= undef;
	$self->{'_opts'}->{'width'}				= undef;
	$self->{'_opts'}->{'aspect'}			= undef;

	$self->_find_bin($^O	=~ /bsd$/ ? 'cdialog' : 'dialog');
	$self->_determine_dialog_variant();
	$self->_getConsoleSize();
}

sub _determine_dialog_variant {
	my $self = shift;
	my $str = `$self->{'bin'} --help 2>&1`;
	if ($str =~ /cdialog\s\(ComeOn\sDialog\!\)\sversion\s\d+\.\d+\-(.{4})/ && $1 >= 2003) {
		debug('Have colors!');
	} else {
		delete $self->{'_opts'}->{'colors'};
		debug('No colors!');
		if ($str =~ /version\s0\.[34]/m) {
			$self->{'_opts'}->{'force-no-separate-output'} = '';
			debug('No separate output!');
		}
	}
}

sub _getConsoleSize{
	my $self = shift;
	my ($output, $error);
	execute($self->{'bin'} . ' --print-maxsize', \$output, \$error);
	$error =~ /MaxSize:\s(\d+),\s(\d+)/;
	$self->{'lines'}	= (defined($1) && $1 != 0) ? $1-3 : 23;
	$self->{'columns'}	= (defined($2) && $2 != 0) ? $2-2 : 79;
	error("$error") unless (!$?);
	debug("Lines->$self->{'lines'}");
	debug("Columns->$self->{'columns'}");
}

sub _find_bin {
	my ($self, $variant)	= (shift, shift);
	my ($rs, $stdout, $stderr);
	$rs = execute("which $variant", \$stdout, \$stderr);
	debug("Found $stdout") if $stdout;
	fatal(": Can't find $variant binary $stderr") if $stderr;

	$self->{'bin'} = $stdout if $stdout;
	fatal('Can`t find dialog binary '.$variant) unless ($self->{'bin'} && -x $self->{'bin'});
}

sub _strip_formats {
	my ($self, $text)	= (shift, shift);
	$text =~ s!\\Z[0-9bBuUrRn]!!gmi;
	return($text);
}

sub _buildCommand {
	my $self = shift;
	my $command = '';
	foreach my $prop (keys %{$self->{'_opts'}}){
		if(
			defined($self->{'_opts'}->{$prop})
		){
			$command .= " --$prop ";
			if (ref $self->{'_opts'}->{$prop} eq 'ARRAY') {
				$command .= $self->_clean("@{$self->{'_opts'}->{$prop}}")
			}elsif($self->{'_opts'}->{$prop} !~ /^\d+$/ && $self->{'_opts'}->{$prop}){
					$command .= '\''.$self->_clean($self->{'_opts'}->{$prop}).'\'';
			} elsif($self->{'_opts'}->{$prop} =~ /^\d+$/){
				$command .= $self->{'_opts'}->{$prop};
			}
		}
	}
	return $command;
}

sub _clean{
	my ($self, $text) = (shift, shift);
	$text =~ s!'!"!g;
	#$text =~ s!\\!\\\\!g;
	return $text;
}

sub _restoreDefaults{
	my $self = shift;
	foreach my $prop (keys %{$self->{'_opts'}}){
		if(!(grep $_ eq $prop, qw/title backtitle colors begin exitOnPrompt/)){
			$self->{'_opts'}->{$prop} = undef;
		}
	}
	$self->{'_opts'}->{'begin'} = [1,0];
}

sub _execute{
	my ($self, $text, $init, $mode, $background) = (shift, shift, shift, shift, shift || 0);

	$self->endGauge();

	$text = $self->_strip_formats($text) unless( exists $self->{'_opts'}->{'colors'} );

	my $command = $self->_buildCommand();
	$text = $self->_clean($text);
	$init = $init ? $init : '';

	my $height = defined $self->{'autosize'} ? 0 : ($self->{'lines'});
	my $width = defined $self->{'autosize'} ? 0 : ($self->{'columns'});

	my ($return, $rv);
	$rv = execute("$self->{'bin'} $command --$mode '$text' $height $width $init", undef, \$return);

	debug('Returned text: '.$return) if($return);

	$self->_init() if($self->{'autoreset'});

	wantarray ? return ($rv, $return) : $return;
}

sub fselect{
	my $self = shift;
	my $file = shift;

	exit 1 if $main::noprompt;

	$self->{'lines'} = $self->{'lines'} - 8;
	my $rv = $self->_execute($file, undef, "fselect");
	$self->{'lines'} = $self->{'lines'} + 8;
	return $rv;
}

sub _textbox{
	my $self = shift;
	my $text = shift;
	my $mode = shift;
	my $init = shift || 0;
	my $autosize = $self->{'autosize'};
	$self->{'autosize'} = undef;
	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;
	my ($rv, $rs) = $self->_execute($text, $init, $mode);
	$self->{'_opts'}->{'begin'} = $begin;
	$self->{'autosize'} = $autosize;
	wantarray ? return ($rv, $rs) : $rs;
}

sub radiolist{
	my $self = shift;
	my $text = shift;
	my @init = (@_);
	my $opts = '';

	exit 1 if $main::noprompt;

	for my $init (@init){
		if(!$opts){
			$opts = "'$init' '' on ";
		} else {
			$opts .= "'$init' '' off ";
		}
	}
	return $self->_textbox($text, 'radiolist', (@init +1)." $opts");
}

sub checkbox{
	my $self = shift;
	my $text = shift;
	my @init = (@_);
	my $opts = '';

	exit 1 if $main::noprompt;

	$opts .= "$_ '' on " foreach(@init);

	return $self->_textbox($text, 'checklist', (@init +1)." $opts");
}

sub tailbox{
	my $self = shift;
	my $file = shift;

	exit 1 if $main::noprompt;

	return $self->_execute($file, undef, 'tailbox');
}

sub editbox{
	my $self = shift;
	my $file = shift;

	exit 1 if $main::noprompt;

	return $self->_execute($file, undef, 'editbox');
}

sub dselect{
	my $self = shift;
	my $file = shift;

	exit 1 if $main::noprompt;

	$self->{'lines'} = $self->{'lines'} - 8;
	my $rv = $self->_execute($file, undef, 'dselect');
	$self->{'lines'} = $self->{'lines'} + 8;
	return $rv;
}

sub msgbox{
	my $self = shift;
	my $text = shift;

	exit 1 if $main::noprompt;

	return $self->_textbox($text, 'msgbox');
}

sub yesno{
	my $self = shift;
	my $text = shift;

	exit 1 if $main::noprompt;

	my ($rv, undef) = ($self->_textbox($text, 'yesno'));
	return $rv;
}

sub inputbox{
	my $self = shift;
	my $text = shift;
	my $init = shift || '';

	exit 1 if $main::noprompt;

	return $self->_textbox($text, 'inputbox', $init);
}

sub infobox{
	my $self = shift;
	my $text = shift;

	exit 1 if $main::noprompt;

	my $clear					= $self->{'_opts'}->{'clear'};
	$self->{'_opts'}->{'clear'}	= undef;
	my $rs						= $self->_textbox($text, 'infobox');
	$self->{'_opts'}->{'clear'}	= $clear;
	$rs;
}

sub passwordbox{
	my $self = shift;
	my $text = shift;
	my $init = shift || '';

	exit 1 if $main::noprompt;

	$self->{'_opts'}->{'insecure'} = '';
	return $self->_textbox($text, 'passwordbox', "'$init'");
}

sub startGauge{
	my $self = shift;
	my $text = shift;
	my $init = shift || 0; #initial value

	$self->{'gauge'} ||= {};
	return(0) if (defined $self->{'gauge'}->{'FH'});

	$text = $self->_clean($text);
	$init = $init ? " $init" : 0;

	my $height = $self->{'autosize'} ? 0 : ($self->{'lines'});
	my $width = $self->{'autosize'} ? 0 : ($self->{'columns'});

	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;

	my $command = $self->_buildCommand();

	$command = "$self->{'bin'} $command --gauge '$text' $height $width $init";

	$self->{'_opts'}->{'begin'} = $begin;

	debug("$command");

	$self->{'gauge'}->{'FH'} = new FileHandle;
	$self->{'gauge'}->{'FH'}->open("| $command") || error("Can`t start gauge!");
	debugRegCallBack(\&endGauge);
	$SIG{PIPE} = \&endGauge;
	my $rv = $? >> 8;
	$self->{'gauge'}->{'FH'}->autoflush(1);
	debug("Returned value $rv");
	$rv;
}

sub needGauge{

	my $self	= shift;

	return 0 if $self->{'gauge'}->{'FH'};
	1;
}

sub setGauge{
	my $self	= shift;
	my $value	= shift;
	my $text	= shift || undef;

	return 0 unless $self->{'gauge'}->{'FH'};

	if($text){
		$text = "XXX\n$value\n".$self->_clean($text)."\nXXX\n" ;
	} else {
		$text = "$value\n";
	}

	debug("$text");

	my $fh = $self->{'gauge'}->{'FH'};

	print $fh $text;
	$SIG{PIPE} = \&endGauge;

	return(((defined $self->{'gauge'}->{'FH'}) ? 1 : 0));

}

sub endGauge{
	my $self = iMSCP::Dialog->factory();

	return 0 unless ref $self->{'gauge'}->{'FH'};
	$self->{'gauge'}->{'FH'}->close();
	delete($self->{'gauge'});

	0;
}

sub set{
	my $self	= shift;
	my $param	= shift;
	my $value	= shift;
	my $return	= undef;
	if($param  && exists $self->{'_opts'}->{$param}){
		$return						= $self->{'_opts'}->{$param};
		$self->{'_opts'}->{$param}	= $value;
	}
	$return;
}

1;
