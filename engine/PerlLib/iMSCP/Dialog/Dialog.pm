#!/usr/bin/perl

=head1 NAME

 iMSCP::Dialog::Dialog - i-MSCP Dialog

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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Dialog::Dialog;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use FileHandle;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Class that wrap dialog and cdialog programs.

=head1 PUBLIC METHODS

=over 4

=item resetLabels()

 Reset labels to their default values.

 Return INT 0

=cut

sub resetLabels
{
	my $self = shift;

	$self->{'_opts'}->{"$_-label"} = undef for (qw/ok yes no cancel extra help/);

	0;
}

=item fselect($file)

 Show file selection dialog box.

 Param STRING $file File path
 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub fselect
{
	my $self = shift;

	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;
	$self->{'lines'} = $self->{'lines'} - 8;

	my ($exitCode, $output) = $self->_execute(shift, undef, 'fselect');

	$self->{'_opts'}->{'begin'} = $begin;
	$self->{'lines'} = $self->{'lines'} + 8;

	wantarray ? ($exitCode, $output) : $output;
}

=item radiolist($text, \$choices, $default = '')

 Show radio list dialog box.

 Param STRING $text - Text to show
 Param ARRAY REFERENCE \$choices Reference to an array containing list of choices
 Param STRING $default OPTIONAL Default choice
 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub radiolist
{
	my $self = shift;
	my $text = shift;
	my @choices = @{(shift)};
	my $default = shift || '';

	my @init = ();
	push @init, (escapeShell($_), "''", $default eq $_ ? 'on' : 'off') for @choices;

	$self->_textbox($text, 'radiolist', @choices . " @init");
}

=item checkbox($text, \$choices, $defaults = ())

 Show check list dialog box.

 Param STRING $text - Text to show
 Param ARRAY REFERENCE \$choices Reference to an array containing list of choices
 Param STRING $default OPTIONAL Default choices
 Return array_ref Reference to an array of choices or array containing both dialog exit code and array of choices

=cut

sub checkbox
{
	my $self = shift;
	my $text = shift;
	my @choices = @{(shift)};
	my @defaults = (@_);

	my %values = map { $_ => 1 } @defaults;
	my @init = ();

	push @init, (escapeShell($_), "''", $values{$_} ? 'on' : 'off') for @choices;

	my ($exitCode, $choices) = $self->_textbox($text, 'checklist', @choices . " @init");
	$choices =~ s/"//g;
	@choices = split ' ', $choices;

	wantarray ? ($exitCode, \@choices) : \@choices;
}

=item tailbox($file)

 Show tail dialog box.

 Param SCALAR $file - File path
 Return INT Dialog exit code

=cut

sub tailbox
{
	my $self = shift;

	my ($exitCode) = $self->_execute(shift, undef, 'tailbox');

	$exitCode;
}

=item editbox($file)

 Show edit dialog box.

 Param STRING $file - File path
 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub editbox
{
	my $self = shift;

	$self->_execute(shift, undef, 'editbox');
}

=item dselect($dir)

 Show directory select dialog box.

 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub dselect
{
	my $self = shift;

	$self->{'lines'} = $self->{'lines'} - 8;
	my ($exitCode, $output) = $self->_execute(shift, undef, 'dselect');
	$self->{'lines'} = $self->{'lines'} + 8;

	wantarray ? ($exitCode, $output) : $output;
}

=item msgbox($text)

 Show message dialog box.

 Param STRING $text Text to show in message dialog box
 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub msgbox
{
	my $self = shift;

	$self->_textbox(shift, 'msgbox');
}

=item yesno($text)

 Show boolean dialog box.

 Param STRING $text Text to show
 Return INT - Dialog exit code

=cut

sub yesno
{
	my $self = shift;

	my ($exitCode) = $self->_textbox(shift, 'yesno');

	$exitCode;
}

=item inputbox($text, $init = '')

 Show string input dialog box.

 Param STRING $text Text to show
 Param STRING $init OPTIONAL Default string value
 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub inputbox
{
	my $self = shift;
	my $text = shift;
	my $init = shift || '';

	$self->_textbox($text, 'inputbox', escapeShell($init));
}

=item passwordbox($text, $init = '')

 Show password dialog box.

 Param STRING $text Text to show
 Param STRING $init OPTIONAL Default password value
 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub passwordbox
{
	my $self = shift;
	my $text = shift;
	my $init = shift || '';

	$self->{'_opts'}->{'insecure'} = '';

	$self->_textbox($text, 'passwordbox', escapeShell($init));
}

=item infobox($text)

 Show info dialog box.

 Param STRING $text Text to show
 Return INT - Dialog exit code

=cut

sub infobox
{
	my $self = shift;

	my $clear = $self->{'_opts'}->{'clear'};
	$self->{'_opts'}->{'clear'} = undef;

	my ($exitCode) = $self->_textbox(shift, 'infobox');
	$self->{'_opts'}->{'clear'}	= $clear;

	$exitCode;
}

=item startGauge($text, $percent = 0)

 Start gauge dialog box.

 Param STRING $text Text to show
 Param INT $percent OPTIONAL Initial percentage show in the meter
 Return INT Dialog exit code

=cut

sub startGauge
{
	return 0 if $main::noprompt;

	my $self = shift;
	my $text = escapeShell(shift);
	my $percent = shift || 0;

	$self->{'gauge'} ||= {};
	return 0 if defined $self->{'gauge'}->{'FH'};

	$percent = $percent ? " $percent" : 0;

	my $height = $self->{'autosize'} ? 0 : ($self->{'lines'});
	my $width = $self->{'autosize'} ? 0 : ($self->{'columns'});

	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;

	my $command = $self->_buildCommandOptions();

	$command = "$self->{'bin'} $command --gauge $text $height $width $percent";

	$self->{'_opts'}->{'begin'} = $begin;

	debug($self->_stripFormats($command));

	$self->{'gauge'}->{'FH'} = new FileHandle;
	$self->{'gauge'}->{'FH'}->open("| $command") || error("Unable to start gauge");
	debugRegisterCallBack(\&endGauge);
	$SIG{'PIPE'} = \&endGauge;
	$self->{'gauge'}->{'FH'}->autoflush(1);

	getExitCode($?);
}

=item hasGauge()

 Determine if gauge has been already started testing file handle existence.

 Return int 1 if gauge has been already started, 0 otherwise

=cut

sub hasGauge
{
	return 0 if $main::noprompt;

	my $self = shift;

	(exists $self->{'gauge'}->{'FH'}) ? 1 : 0;
}

=item setGauge($value, $text = '')

 Set new percentage and optionaly new text to show

 Param INT $percent New percentage to show in gauge dialog box
 Param STRING $text OPTIONAL New text to show in gauge dialog box
 Return INT 0 on success, 1 on failure (when SIGPIPE  has been received for any reason)

=cut

sub setGauge
{
	return 0 if $main::noprompt;

	my $self = shift;
	my $percent = shift;
	my $text = shift || '';

	return 0 unless $self->{'gauge'}->{'FH'};

	$text = $text ? "XXX\n$percent\n$text\nXXX\n" : "$percent\n";

	debug($self->_stripFormats($text));

	print {$self->{'gauge'}->{'FH'}} $text;
	$SIG{'PIPE'} = \&endGauge;

	defined $self->{'gauge'}->{'FH'} ? 1 : 0;
}

=item endGauge()

 Terminate gauge dialog box.

 Return INT 0

=cut

sub endGauge
{
	return 0 if $main::noprompt;

	my $self = iMSCP::Dialog->factory();

	return 0 unless ref $self->{'gauge'}->{'FH'};

	$self->{'gauge'}->{'FH'}->close();
	delete($self->{'gauge'});

	0;
}

=item set($option, $value)

 Set dialog option.

 Param STRING $param Option name
 Param STRING $value Option value
 Return STRING|undef Old option value if exists, undef otherwise

=cut

sub set
{
	my $self = shift;
	my $option = shift;
	my $value = shift;
	my $return = undef;

	if($option && exists $self->{'_opts'}->{$option}) {
		$return = $self->{'_opts'}->{$option};
		$self->{'_opts'}->{$option} = $value;
	}

	$return;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

 Return iMSCP::Dialog::Dialog

=cut

sub _init
{
	my $self = shift;

	# Force usage of graphic lines (UNICODE values) when using putty (See #540)
	$ENV{'NCURSES_NO_UTF8_ACS'} = '1';

	$self->{'autosize'} = undef;
	$self->{'autoreset'} = 0;
	$self->{'lines'} = undef;
	$self->{'columns'} = undef;

	$self->{'_opts'}->{'title'} = $self->{'args'}->{'title'} || undef;
	$self->{'_opts'}->{'backtitle'} = $self->{'args'}->{'backtitle'} || undef;

	$self->{'_opts'}->{'colors'} = '';
	$self->{'_opts'}->{'begin'} = [1, 0];

	$self->{'_opts'}->{'exit-label'} = $self->{'args'}->{'exit-label'} || undef;
	$self->{'_opts'}->{'no-label'} = $self->{'args'}->{'no-label'} || undef;
	$self->{'_opts'}->{'ok-label'} = $self->{'args'}->{'ok-label'} || undef;
	$self->{'_opts'}->{'cancel-label'} = $self->{'args'}->{'cancel-label'} || undef;
	$self->{'_opts'}->{'help-label'} = $self->{'args'}->{'help-label'} || undef;
	$self->{'_opts'}->{'extra-label'} = $self->{'args'}->{'extra-label'} || undef;
	$self->{'_opts'}->{'yes-label'} = $self->{'args'}->{'yes-label'} || undef;

	$self->{'_opts'}->{'extra-button'} = $self->{'args'}->{'extra-button'} || undef;
	$self->{'_opts'}->{'help-button'} = $self->{'args'}->{'help-button'} || undef;

	$self->{'_opts'}->{'defaultno'} = $self->{'args'}->{'defaultno'} || undef;
	$self->{'_opts'}->{'default-item'} = $self->{'args'}->{'default-item'} || undef;

	$self->{'_opts'}->{'no-cancel'} = $self->{'args'}->{'no-cancel'} || undef;
	$self->{'_opts'}->{'no-ok'} = $self->{'args'}->{'no-ok'} || undef;
	$self->{'_opts'}->{'clear'} = $self->{'args'}->{'clear'} || undef;

	$self->{'_opts'}->{'column-separator'} = undef;

	$self->{'_opts'}->{'cr-wrap'} = undef;
	$self->{'_opts'}->{'no-collapse'} = undef;
	$self->{'_opts'}->{'trim'} = undef;
	$self->{'_opts'}->{'date-format'} = undef;

	$self->{'_opts'}->{'help-status'} = undef;
	$self->{'_opts'}->{'insecure'} = undef;
	$self->{'_opts'}->{'item-help'} = undef;
	$self->{'_opts'}->{'max-input'} = undef;
	$self->{'_opts'}->{'no-shadow'} = undef;
	$self->{'_opts'}->{'shadow'} = undef;
	$self->{'_opts'}->{'single-quoted'} = undef;
	$self->{'_opts'}->{'tab-correct'} = undef;
	$self->{'_opts'}->{'tab-len'} = undef;
	$self->{'_opts'}->{'timeout'} = undef;

	$self->{'_opts'}->{'height'} = undef;
	$self->{'_opts'}->{'width'} = undef;
	$self->{'_opts'}->{'aspect'} = undef;

	$self->_findBin($^O =~ /bsd$/ ? 'cdialog' : 'dialog');
	$self->_determineDialogVariant();
	$self->_determineConsoleSize();

	$self;
}

=item _determineDialogVariant()

 Determine dialog variant.

 Return iMSCP::Dialog::Dialog

=cut

sub _determineDialogVariant
{
	my $self = shift;
	my $str = `$self->{'bin'} --help 2>&1`;

	if ($str =~ /cdialog\s\(ComeOn\sDialog\!\)\sversion\s\d+\.\d+\-(.{4})/ && $1 >= 2003) {
		debug('Dialog color support enabled');
	} else {
		delete $self->{'_opts'}->{'colors'};
		debug('Dialog color support disabled (not supported)');

		if ($str =~ /version\s0\.[34]/m) {
			$self->{'_opts'}->{'force-no-separate-output'} = '';
			debug('No separate output!');
		}
	}

	$self;
}

=item _determineConsoleSize()

 Determine console size.

 Return iMSCP::Dialog::Dialog

=cut

sub _determineConsoleSize
{
	my $self = shift;
	my ($output, $error);

	execute($self->{'bin'} . ' --print-maxsize', \$output, \$error);
	$error =~ /MaxSize:\s(\d+),\s(\d+)/;
	$self->{'lines'} = (defined($1) && $1 != 0) ? $1 - 3 : 23;
	$self->{'columns'} = (defined($2) && $2 != 0) ? $2 - 2 : 79;
	error($error) unless ! $?;
	debug("Lines->$self->{'lines'}");
	debug("Columns->$self->{'columns'}");

	$self;
}

=item _findBin($variant)

 Find dialog variant (dialog|cdialog).

 Return iMSCP::Dialog::Dialog

=cut

sub _findBin
{
	my ($self, $variant) = (shift, shift);
	my ($rs, $stdout, $stderr);

	$rs = execute("which $variant", \$stdout, \$stderr);
	debug("Found $stdout") if $stdout;
	fatal("Can't find $variant binary: $stderr") if $stderr;

	$self->{'bin'} = $stdout if $stdout;
	fatal("Can`t find dialog binary: $variant") unless ($self->{'bin'} && -x $self->{'bin'});

	$self;
}

=item _stripFormats($string)

 Strip out any format characters (\Z sequences) from the given string.

 Param STRING $string String from which any format character must be stripped
 Return STRING String stripped out of any format character

=cut

sub _stripFormats
{
	my ($self, $string) = (shift, shift);

	$string =~ s/\\Z[0-9bBuUrRn]//gmi;

	$string;
}

=item _buildCommandOptions()

 Build dialog command options.

 Return STRING Dialog command

=cut

sub _buildCommandOptions
{
	my $self = shift;
	my $commandOptions = '';

	for(keys %{$self->{'_opts'}}){
		if(defined $self->{'_opts'}->{$_}) {
			$commandOptions .= " --$_ ";

			if (ref $self->{'_opts'}->{$_} eq 'ARRAY') {
				for(@{$self->{'_opts'}->{$_}}) {
					$commandOptions .=  escapeShell($_) . ' ';
				}
			} elsif($self->{'_opts'}->{$_} !~ /^\d+$/ && $self->{'_opts'}->{$_}) {
				$commandOptions .= escapeShell($self->{'_opts'}->{$_});
			} elsif($self->{'_opts'}->{$_} =~ /^\d+$/){
				$commandOptions .= $self->{'_opts'}->{$_};
			}
		}
	}

	$commandOptions;
}

=item _restoreDefaults()

 Restore default options.

 Return iMSCP::Dialog::Dialog

=cut

sub _restoreDefaults
{
	my $self = shift;

	for my $prop (keys %{$self->{'_opts'}}) {
		$self->{'_opts'}->{$prop} = undef if ! grep $_ eq $prop, qw/title backtitle colors begin/;
	}

	$self->{'_opts'}->{'begin'} = [1, 0];

	$self;
}

=item _execute($text, $init, $type, [$background])

 Wrap execution of dialog commands (except gauge dialog commands).

 Param STRING $text Dialog text
 Param STRING $init Default value
 Param STRING $type Dialog box type

 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub _execute
{
	my ($self, $text, $init, $type) = @_;

	if($main::noprompt) {
		exit 5 if $type ne 'infobox' && $type ne 'msgbox';
		return 0;
	}

	$self->endGauge();

	$text = $self->_stripFormats($text) unless( exists $self->{'_opts'}->{'colors'} );

	my $command = $self->_buildCommandOptions();

	$text = escapeShell($text);
	$init = $init ? $init : '';

	my $height = defined $self->{'autosize'} ? 0 : $self->{'lines'};
	my $width = defined $self->{'autosize'} ? 0 : $self->{'columns'};

	my ($output, $exitCode);

	$exitCode = execute("$self->{'bin'} $command --$type $text $height $width $init", undef, \$output);

	debug('Returned text: ' . $output) if $output;

	$self->_init() if $self->{'autoreset'};

	wantarray ? ($exitCode, $output) : $output;
}

=item _textbox($text, $type, $init = 0)

 Wrap execution of several dialog box.

 Param STRING $text Text to show
 Param STRING $mode Text dialog box type (radiolist|checklist|msgbox|yesno|inputbox|passwordbox|infobox)
 Param STRING $init Default value
 Return STRING|ARRAY Dialog output or array containing both dialog exit code and dialog output

=cut

sub _textbox
{
	my $self = shift;
	my $text = shift;
	my $type = shift;
	my $init = shift || 0;
	my $autosize = $self->{'autosize'};

	$self->{'autosize'} = undef;
	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;

	my ($exitCode, $output) = $self->_execute($text, $init, $type);

	$self->{'_opts'}->{'begin'} = $begin;
	$self->{'autosize'} = $autosize;

	wantarray ? ($exitCode, $output) : $output;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
