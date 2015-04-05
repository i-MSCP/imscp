=head1 NAME

 iMSCP::Dialog - i-MSCP Dialog

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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

package iMSCP::Dialog;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::ProgramFinder;
use FileHandle;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Class that wrap dialog and cdialog programs.

=head1 PUBLIC METHODS

=over 4

=item resetLabels()

 Reset labels to their default values

 Return int 0

=cut

sub resetLabels
{
	my %defaultLabels = (
		'exit' => 'Abort', 'ok' => 'Ok', 'yes' => 'Yes', 'no' => 'No', 'cancel' => 'Back', 'help' => 'Help',
		'extra' => undef
	);

	$_[0]->{'_opts'}->{"$_-label"} = $defaultLabels{$_} for keys %defaultLabels;

	0;
}

=item fselect($file)

 Show file selection dialog

 Param string $file File path
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub fselect
{
	my $self = $_[0];

	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;
	$self->{'lines'} = $self->{'lines'} - 8;

	my ($ret, $output) = $self->_execute($_[1], undef, 'fselect');

	$self->{'_opts'}->{'begin'} = $begin;
	$self->{'lines'} = $self->{'lines'} + 8;

	wantarray ? ($ret, $output) : $output;
}

=item radiolist($text, \@choices, $default = '')

 Show radio list dialog

 Param string $text Text to show
 Param array \@choices List of choices
 Param string $default OPTIONAL Default choice
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub radiolist
{
	my ($self, $text, $choices, $default) = @_;

	$default ||= '';

	my @init = ();

	s/_/ /g for (@{$choices}, $default); # Humanize

	push @init, (escapeShell($_), "''", $default eq $_ ? 'on' : 'off') for @{$choices};

	my ($ret, $choice) = $self->_textbox($text, 'radiolist', @{$choices} . " @init");

	$choice =~ s/ /_/g; # Normalize

	wantarray ? ($ret, $choice) : $choice;
}

=item checkbox($text, \$choices, @defaults = ())

 Show check list dialog

 Param string $text Text to show
 Param array \@choices Reference to an array containing list of choices
 Param array @default OPTIONAL Default choices
 Return array An array of choices or array containing both dialog exit code and array of choices

=cut

sub checkbox
{
	my ($self, $text, $choices, @defaults) = @_;

	my %values = map { $_ => 1 } @defaults;
	my @init = ();

	s/_/ /g for (@{$choices}, @defaults); # Humanize

	push @init, (escapeShell($_), "''", $values{$_} ? 'on' : 'off') for @{$choices};

	my ($ret, $output) = $self->_textbox($text, 'checklist', @{$choices} . " @init");

	@{$choices} = split /\n/, $output;

	s/ /_/g for (@{$choices}, @defaults); # Normalize

	wantarray ? ($ret, $choices) : $choices;
}

=item tailbox($file)

 Show tail dialog

 Param string $file File path
 Return int Dialog exit code

=cut

sub tailbox
{
	($_[0]->_execute($_[1], undef, 'tailbox'))[0];
}

=item editbox($file)

 Show edit dialog

 Param string $file File path
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub editbox
{
	$_[0]->_execute($_[1], undef, 'editbox');
}

=item dselect($dir)

 Show directory select dialog box

 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub dselect
{
	my $self = $_[0];

	$self->{'lines'} = $self->{'lines'} - 8;

	my ($ret, $output) = $self->_execute($_[1], undef, 'dselect');

	$self->{'lines'} = $self->{'lines'} + 8;

	wantarray ? ($ret, $output) : $output;
}

=item msgbox($text)

 Show message dialog

 Param string $text Text to show in message dialog box
 Return int Dialog exit code

=cut

sub msgbox
{
	($_[0]->_textbox($_[1], 'msgbox'))[0];
}

=item yesno($text)

 Show boolean dialog box

 Param string $text Text to show
 Return int Dialog exit code

=cut

sub yesno
{
	($_[0]->_textbox($_[1], 'yesno'))[0];
}

=item inputbox($text, $init = '')

 Show input dialog

 Param string $text Text to show
 Param string $init OPTIONAL Default string value
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub inputbox
{
	my ($self, $text, $init) = @_;

	$init ||= '';

	$self->_textbox($text, 'inputbox', escapeShell($init));
}

=item passwordbox($text, $init = '')

 Show password dialog

 Param string $text Text to show
 Param string $init OPTIONAL Default password value
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub passwordbox
{
	my ($self, $text, $init) = @_;

	$init ||= '';

	$self->{'_opts'}->{'insecure'} = '';

	$self->_textbox($text, 'passwordbox', escapeShell($init));
}

=item infobox($text)

 Show info dialog

 Param string $text Text to show
 Return int Dialog exit code

=cut

sub infobox
{
	my $self = $_[0];

	my $clear = $self->{'_opts'}->{'clear'};
	$self->{'_opts'}->{'clear'} = undef;

	my ($ret) = $self->_textbox($_[1], 'infobox');

	$self->{'_opts'}->{'clear'} = $clear;

	$ret;
}

=item startGauge($text, $percent = 0)

 Start a gauge

 Param string $text Text to show
 Param int $percent OPTIONAL Initial percentage show in the meter
 Return int Dialog exit code

=cut

sub startGauge
{
	my $self = shift;

	return 0 if $main::noprompt || $self->{'gauge'};

	my ($text, $percent) = @_;

	$text = escapeShell($text);
	$percent ||= 0;

	$percent = $percent ? " $percent" : 0;

	my $height = $self->{'autosize'} ? 0 : $self->{'lines'};
	my $width = $self->{'autosize'} ? 0 : $self->{'columns'};

	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;

	my $command = $self->_buildCommandOptions();

	$command = "$self->{'bin'} $command --gauge $text $height $width $percent";

	$self->{'_opts'}->{'begin'} = $begin;

	$self->{'gauge'} = new FileHandle;
	$self->{'gauge'}->autoflush(1);
	$self->{'gauge'}->open("| $command") || fatal("Unable to start gauge");

	debugRegisterCallBack(sub { $self->endGauge(); });

	$SIG{'PIPE'} = sub { $self->endGauge(); };

	getExitCode($?);
}

=item setGauge($value, $text = '')

 Set new percentage and optionaly new text to show

 Param int $percent New percentage to show in gauge dialog box
 Param string $text OPTIONAL New text to show in gauge dialog box
 Return in 0 on success, 1 on failure (eg. when SIGPIPE has been received for any reason)

=cut

sub setGauge
{
	my $self = shift;

	return 0 if $main::noprompt || ! $self->{'gauge'};

	my ($percent, $text) = @_;

	$text ||= '';

	print {$self->{'gauge'}} (defined $text)
		? sprintf("XXX\n%d\n%s\nXXX\n", $percent, $text) : sprintf("%d\n", $percent);

	($self->{'gauge'}) ? 1 : 0;
}

=item endGauge()

 Terminate gauge dialog box

 Return int 0

=cut

sub endGauge
{
	my $self = shift;

	return 0 if $main::noprompt || ! $self->{'gauge'};

	$self->{'gauge'}->close();

	undef $self->{'gauge'};

	0;
}

=item hasGauge()

 Does a gauge is currently running?

 Return int 1 if gauge is running 0 otherwise

=cut

sub hasGauge
{
	return 0 if $main::noprompt;

	($_[0]->{'gauge'}) ? 1 : 0;
}

=item set($option, $value)

 Set dialog option

 Param string $param Option name
 Param string $value Option value
 Return string|undef Old option value if exists, undef otherwise

=cut

sub set
{
	my($self, $option, $value) = @_;

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

 Initialize instance

 Return iMSCP::Dialog::Dialog

=cut

sub _init
{
	my $self = $_[0];

	# Return specific exit status when ESC is pressed
	$ENV{'DIALOG_ESC'} = 50;

	# We want get 30 as exit code when CANCEL button is pressed
	$ENV{'DIALOG_CANCEL'} = 30;

	# Force usage of graphic lines (UNICODE values) when using putty (See #540)
	$ENV{'NCURSES_NO_UTF8_ACS'} = '1';

	$self->{'autosize'} = undef;
	$self->{'autoreset'} = 0;
	$self->{'lines'} = undef;
	$self->{'columns'} = undef;

	$self->{'_opts'}->{'backtitle'} ||= "i-MSCP - internet Multi Server Control Panel ($main::imscpConfig{'Version'})";
	$self->{'_opts'}->{'title'} ||= 'i-MSCP Setup Dialog';

	$self->{'_opts'}->{'colors'} = '';
	$self->{'_opts'}->{'begin'} = [1, 0];

	$self->{'_opts'}->{'ok-label'} ||= 'Ok';
	$self->{'_opts'}->{'yes-label'} ||= 'Yes';
	$self->{'_opts'}->{'no-label'} ||= 'No';
	$self->{'_opts'}->{'cancel-label'} ||= 'Back';
	$self->{'_opts'}->{'exit-label'} ||= 'Abort';
	$self->{'_opts'}->{'help-label'} ||= 'Help';
	$self->{'_opts'}->{'extra-label'} ||= undef;

	$self->{'_opts'}->{'extra-button'} //= undef;
	$self->{'_opts'}->{'help-button'} //= undef;

	$self->{'_opts'}->{'defaultno'} //= undef;
	$self->{'_opts'}->{'default-item'} ||= undef;

	$self->{'_opts'}->{'no-cancel'} //= undef;
	$self->{'_opts'}->{'no-ok'} //= undef;
	$self->{'_opts'}->{'clear'} //= undef;

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

	$self->{'_opts'}->{'separate-output'} = undef;

	$self->_findBin($^O =~ /bsd$/ ? 'cdialog' : 'dialog');
	#$self->_determineDialogVariant();
	$self->_determineConsoleSize();

	$self;
}

#=item _determineDialogVariant()
#
# Determine dialog variant.
#
# Return iMSCP::Dialog::Dialog
#
#=cut
#
#sub _determineDialogVariant
#{
#	my $self = $_[0];
#
#	my $str = `$self->{'bin'} --help 2>&1`;
#
#	if ($str =~ /cdialog\s\(ComeOn\sDialog\!\)\sversion\s\d+\.\d+\-(\d{4})/ && $1 >= 2003) {
#		$self->{'_opts'}->{'colors'} = '';
#	} elsif ($str =~ /version\s0\.[34]/m) {
#		$self->{'_opts'}->{'force-no-separate-output'} = '';
#	}
#
#	$self;
#}

=item _determineConsoleSize()

 Determine console size

 Return iMSCP::Dialog::Dialog

=cut

sub _determineConsoleSize
{
	my $self = $_[0];

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

 Find dialog variant (dialog|cdialog)

 Return iMSCP::Dialog::Dialog

=cut

sub _findBin
{
	my ($self, $variant) = @_;

	my $bindPath = iMSCP::ProgramFinder::find($variant);

	fatal("Unable to find dialog program: $variant") unless $bindPath;

	$self->{'bin'} = $bindPath;

	$self;
}

=item _stripFormats($string)

 Strip out any format characters (\Z sequences) from the given string

 Param string $string String from which any format character must be stripped
 Return string String stripped out of any format character

=cut

sub _stripFormats
{
	my ($self, $string) = @_;

	$string =~ s/\\Z[0-9bBuUrRn]//gmi;

	$string;
}

=item _buildCommandOptions()

 Build dialog command options

 Return string Dialog command

=cut

sub _buildCommandOptions
{
	my $self = $_[0];

	my @options = ();

	for my $option(keys %{$self->{'_opts'}}) {
		if(defined $self->{'_opts'}->{$option}) {
			# Add option
			push @options, '--' . $option;

			# Add option arguments if any
			if($self->{'_opts'}->{$option}) {
				if(ref $self->{'_opts'}->{$option} ne 'array') { # Only one argument
					push @options, escapeShell($self->{'_opts'}->{$option});
				} else { # Many arguments
					push @options, escapeShell($_) for @{$self->{'_opts'}->{$option}};
				}
			}
		}
	}

	"@options";
}

=item _restoreDefaults()

 Restore default options

 Return iMSCP::Dialog::Dialog

=cut

sub _restoreDefaults
{
	my $self = $_[0];

	for my $prop (keys %{$self->{'_opts'}}) {
		$self->{'_opts'}->{$prop} = undef unless grep $_ eq $prop, qw/title backtitle colors begin/;
	}

	$self->{'_opts'}->{'begin'} = [1, 0];

	$self;
}

=item _execute($text, $init, $type, [$background])

 Wrap execution of dialog commands (except gauge dialog commands)

 Param string $text Dialog text
 Param string $init Default value
 Param string $type Dialog box type

 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub _execute
{
	my ($self, $text, $init, $type) = @_;

	$self->endGauge(); # Ensure that no gauge is currently running...

	if($main::noprompt) {
		exit 5 if $type ne 'infobox' && $type ne 'msgbox';
		return 0;
	}

	$text = $self->_stripFormats($text) unless defined $self->{'_opts'}->{'colors'};

	$self->{'_opts'}->{'separate-output'} = '' if $type eq 'checklist';

	my $command = $self->_buildCommandOptions();

	$text = escapeShell($text);
	$init = $init ? $init : '';

	my $height = $self->{'autosize'} ? 0 : $self->{'lines'};
	my $width = $self->{'autosize'} ? 0 : $self->{'columns'};

	my ($ret, $output);

	$ret = execute("$self->{'bin'} $command --$type $text $height $width $init", undef, \$output);

	$self->{'_opts'}->{'separate-output'} = undef;

	$self->_init() if $self->{'autoreset'};

	wantarray ? ($ret, $output) : $output;
}

=item _textbox($text, $type, $init = 0)

 Wrap execution of several dialog box

 Param string $text Text to show
 Param string $mode Text dialog box type (radiolist|checklist|msgbox|yesno|inputbox|passwordbox|infobox)
 Param string $init Default value
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub _textbox
{
	my($self, $text, $type, $init) = @_;

	$init ||= 0;

	my $autosize = $self->{'autosize'};

	$self->{'autosize'} = undef;
	my $begin = $self->{'_opts'}->{'begin'};
	$self->{'_opts'}->{'begin'} = undef;

	my ($ret, $output) = $self->_execute($text, $init, $type);

	$self->{'_opts'}->{'begin'} = $begin;
	$self->{'autosize'} = $autosize;

	wantarray ? ($ret, $output) : $output;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
