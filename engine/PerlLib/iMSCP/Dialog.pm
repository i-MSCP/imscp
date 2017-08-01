=head1 NAME

 iMSCP::Dialog - i-MSCP Dialog

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Class that wrap dialog and cdialog programs.

=head1 PUBLIC METHODS

=over 4

=item resetLabels( )

 Reset labels to their default values

 Return int 0

=cut

sub resetLabels
{
    my %defaultLabels = (
        exit   => 'Abort',
        ok     => 'Ok',
        yes    => 'Yes',
        no     => 'No',
        cancel => 'Back',
        help   => 'Help',
        extra  => undef
    );
    $_[0]->{'_opts'}->{"$_-label"} = $defaultLabels{$_} for keys %defaultLabels;
    0;
}

=item fselect( $file )

 Show file selection dialog

 Param string $file File path
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub fselect
{
    my ($self, $file) = @_;

    $self->{'lines'} = $self->{'lines'}-8;
    my ($ret, $output) = $self->_execute( $file, undef, 'fselect' );
    $self->{'lines'} = $self->{'lines'}+8;
    wantarray ? ( $ret, $output ) : $output;
}

=item radiolist( $text, \@choices [, $default = '' ] )

 Show radio list dialog

 Param string $text Text to show
 Param array \@choices List of choices
 Param string $default Default choice
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub radiolist
{
    my ($self, $text, $choices, $default) = @_;

    my (@init, %choices);
    $choices{s/_/ /gr} = $_ for @{$choices};
    ( $default ||= '' ) =~ s/_/ /g;
    push @init, ( escapeShell( $_ ), "''", $default eq $_ ? 'on' : 'off' ) for sort keys %choices;
    my ($ret, $output) = $self->_textbox( $text, 'radiolist', scalar @{$choices} . " @init" );
    wantarray ? ( $ret, $choices{$output} ) : $choices{$output};
}

=item checkbox( $text, \$choices [, @defaults = ( ) ] )

 Show check list dialog

 Param string $text Text to show
 Param array \@choices Reference to an array containing list of choices
 Param array @default Default choices
 Return array An array of choices or array containing both dialog exit code and array of choices

=cut

sub checkbox
{
    my ($self, $text, $choices, @defaults) = @_;

    my (@init, %choices);
    $choices{s/_/ /gr} = $_ for @{$choices};
    my %defaults = map { s/_/ /gr => 1 } @defaults;
    push @init, ( escapeShell( $_ ), "''", $defaults{$_} ? 'on' : 'off' ) for sort keys %choices;
    my ($ret, $output) = $self->_textbox( $text, 'checklist', scalar @{$choices} . " @init" );
    @{$choices} = ();
    push @{$choices}, $choices{$_} = $_ for split /\n/, $output;
    wantarray ? ( $ret, $choices ) : $choices;
}

=item tailbox( $file )

 Show tail dialog

 Param string $file File path
 Return int Dialog exit code

=cut

sub tailbox
{
    my ($self, $file) = @_;

    ( $self->_execute( $file, undef, 'tailbox' ) )[0];
}

=item editbox( $file )

 Show edit dialog

 Param string $file File path
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub editbox
{
    my ($self, $file) = @_;

    $self->_execute( $file, undef, 'editbox' );
}

=item dselect( $directory )

 Show directory select dialog box

 Param string $directory
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub dselect
{
    my ($self, $directory) = @_;

    $self->{'lines'} = $self->{'lines'}-8;
    my ($ret, $output) = $self->_execute( $directory, undef, 'dselect' );
    $self->{'lines'} = $self->{'lines'}+8;
    wantarray ? ( $ret, $output ) : $output;
}

=item msgbox( $text )

 Show message dialog

 Param string $text Text to show in message dialog box
 Return int Dialog exit code

=cut

sub msgbox
{
    my ($self, $text) = @_;

    ( $self->_textbox( $text, 'msgbox' ) )[0];
}

=item yesno( $text [, $defaultno =  FALSE ] )

 Show boolean dialog box

 Param string $text Text to show
 Param string bool defaultno Set the default value of the box to 'No'
 Return int Dialog exit code

=cut

sub yesno
{
    my ($self, $text, $defaultno) = @_;

    $self->{'_opts'}->{'defaultno'} = $defaultno ? '' : undef;
    my $ret = ( $self->_textbox( $text, 'yesno' ) )[0];
    $self->{'_opts'}->{'defaultno'} = undef;
    $ret;
}

=item inputbox( $text [, $init = '' ] )

 Show input dialog

 Param string $text Text to show
 Param string $init Default string value
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub inputbox
{
    my ($self, $text, $init) = @_;

    $init //= '';
    $self->_textbox( $text, 'inputbox', escapeShell( $init ));
}

=item passwordbox( $text [, $init = '' ])

 Show password dialog

 Param string $text Text to show
 Param string $init Default password value
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub passwordbox
{
    my ($self, $text, $init) = @_;

    $init //= '';
    $self->{'_opts'}->{'insecure'} = '';
    $self->_textbox( $text, 'passwordbox', escapeShell( $init ));
}

=item infobox( $text )

 Show info dialog

 Param string $text Text to show
 Return int Dialog exit code

=cut

sub infobox
{
    my ($self, $text) = @_;

    my $clear = $self->{'_opts'}->{'clear'};
    $self->{'_opts'}->{'clear'} = undef;

    my ($ret) = $self->_textbox( $text, 'infobox' );

    $self->{'_opts'}->{'clear'} = $clear;
    $ret;
}

=item startGauge( $text [, $percent = 0 ] )

 Start a gauge

 Param string $text Text to show
 Param int $percent OPTIONAL Initial percentage show in the meter
 Return 0

=cut

sub startGauge
{
    my ($self, $text, $percent) = @_;

    return 0 if iMSCP::Getopt->noprompt || $self->{'gauge'};

    defined $_[0] or die( '$text parameter is undefined' );

    open $self->{'gauge'}, '|-',
        $self->{'bin'}, $self->_buildCommonCommandOptions( 'noEscape' ),
        '--gauge', $text,
        ( ( $self->{'autosize'} ) ? 0 : $self->{'lines'} ),
        ( ( $self->{'autosize'} ) ? 0 : $self->{'columns'} ),
        $percent // 0 or die( "Couldn't start gauge" );

    $self->{'gauge'}->autoflush( 1 );
    debugRegisterCallBack( sub { $self->endGauge(); } );
    $SIG{'PIPE'} = sub { $self->endGauge(); };
    0;
}

=item setGauge( $percent, $text )

 Set new percentage and optionaly new text to show

 Param int $percent New percentage to show in gauge dialog box
 Param string $text New text to show in gauge dialog box
 Return int 0

=cut

sub setGauge
{
    my ($self, $percent, $text) = @_;

    return 0 if iMSCP::Getopt->noprompt || !$self->{'gauge'};

    print { $self->{'gauge'} } sprintf( "XXX\n%d\n%s\nXXX\n", $percent, $text );
    0
}

=item endGauge( )

 Terminate gauge dialog box

 Return int 0

=cut

sub endGauge
{
    my ($self) = @_;

    return 0 if iMSCP::Getopt->noprompt || !$self->{'gauge'};

    $self->{'gauge'}->close();
    undef $self->{'gauge'};
    0;
}

=item hasGauge( )

 Does a gauge is currently running?

 Return int 1 if gauge is running 0 otherwise

=cut

sub hasGauge
{
    my ($self) = @_;

    return 0 if iMSCP::Getopt->noprompt;

    ( $self->{'gauge'} ) ? 1 : 0;
}

=item set( $option, $value )

 Set dialog option

 Param string $param Option name
 Param string $value Option value
 Return string|undef Old option value if exists, undef otherwise

=cut

sub set
{
    my ($self, $option, $value) = @_;

    return undef unless $option && exists $self->{'_opts'}->{$option};

    my $return = $self->{'_opts'}->{$option};
    $self->{'_opts'}->{$option} = $value;
    $return;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Dialog::Dialog

=cut

sub _init
{
    my ($self) = @_;

    # These environment variable screws up at least whiptail with the
    # way we call it. Posix does not allow safe arg passing like
    # whiptail needs.
    delete $ENV{'POSIXLY_CORRECT'} if exists $ENV{'POSIXLY_CORRECT'};
    delete $ENV{'POSIX_ME_HARDER'} if exists $ENV{'POSIX_ME_HARDER'};

    # Detect all the ways people have managed to screw up their
    # terminals (so far...)
    if ( !exists $ENV{'TERM'} || !defined $ENV{'TERM'} || $ENV{'TERM'} eq '' ) {
        fatal ( 'TERM is not set, so the dialog frontend is not usable.' );
    } elsif ( $ENV{'TERM'} =~ /emacs/i ) {
        fatal ( 'Dialog frontend is incompatible with emacs shell buffers' );
    } elsif ( $ENV{'TERM'} eq 'dumb' || $ENV{'TERM'} eq 'unknown' ) {
        fatal ( 'Dialog frontend will not work on a dumb terminal, an emacs shell buffer, or without a controlling terminal.' );
    }

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
    $self->{'_opts'}->{'title'} ||= 'i-MSCP Installer Dialog';
    $self->{'_opts'}->{'colors'} = '';
    $self->{'_opts'}->{'ok-label'} ||= 'Ok';
    $self->{'_opts'}->{'yes-label'} ||= 'Yes';
    $self->{'_opts'}->{'no-label'} ||= 'No';
    $self->{'_opts'}->{'cancel-label'} ||= 'Back';
    $self->{'_opts'}->{'exit-label'} ||= 'Abort';
    $self->{'_opts'}->{'help-label'} ||= 'Help';
    $self->{'_opts'}->{'extra-label'} ||= undef;
    $self->{'_opts'}->{'extra-button'} //= undef;
    $self->{'_opts'}->{'help-button'} //= undef;
    $self->{'_opts'}->{'defaultno'} ||= undef;
    $self->{'_opts'}->{'default-item'} ||= undef;
    $self->{'_opts'}->{'no-cancel'} ||= undef;
    $self->{'_opts'}->{'no-ok'} ||= undef;
    $self->{'_opts'}->{'clear'} ||= undef;
    $self->{'_opts'}->{'column-separator'} = undef;
    $self->{'_opts'}->{'cr-wrap'} = undef;
    $self->{'_opts'}->{'no-collapse'} = undef;
    $self->{'_opts'}->{'trim'} = undef;
    $self->{'_opts'}->{'date-format'} = undef;
    $self->{'_opts'}->{'help-status'} = undef;
    $self->{'_opts'}->{'insecure'} = undef;
    $self->{'_opts'}->{'item-help'} = undef;
    $self->{'_opts'}->{'max-input'} = undef;
    $self->{'_opts'}->{'no-shadow'} = '';
    $self->{'_opts'}->{'shadow'} = undef;
    $self->{'_opts'}->{'single-quoted'} = undef;
    $self->{'_opts'}->{'tab-correct'} = undef;
    $self->{'_opts'}->{'tab-len'} = undef;
    $self->{'_opts'}->{'timeout'} = undef;
    $self->{'_opts'}->{'height'} = undef;
    $self->{'_opts'}->{'width'} = undef;
    $self->{'_opts'}->{'aspect'} = undef;
    $self->{'_opts'}->{'separate-output'} = undef;
    $self->_findBin( $^O =~ /bsd$/ ? 'cdialog' : 'dialog' );
    $self->_resize();
    $SIG{'WINCH'} = sub { $self->_resize(); };
    $self;
}

=item _resize( )

 This method is called whenever the tty is resized, and probes to determine the new screen size.

=cut

sub _resize
{
    my ($self) = @_;

    my $lines;
    if ( exists $ENV{'LINES'} ) {
        $self->{'lines'} = $ENV{'LINES'};
    } else {
        ( $lines ) = `stty -a 2>/dev/null` =~ /rows (\d+)/s;
        $lines ||= 24;
    }

    my $cols;
    if ( exists $ENV{'COLUMNS'} ) {
        $cols = $ENV{'COLUMNS'};
    } else {
        ( $cols ) = `stty -a 2>/dev/null` =~ /columns (\d+)/s;
        $cols ||= 80;
    }

    if ( $lines < 24 || $cols < 80 ) {
        fatal ( 'A screen at least 24 lines tall and 80 columns wide is required. Please enlarge your screen.' );
    }

    $self->{'lines'} = $lines-2;
    $self->{'columns'} = $cols-2;

    $self->endGauge();
}

=item _findBin( $variant )

 Find dialog variant (dialog|cdialog)

 Return iMSCP::Dialog::Dialog

=cut

sub _findBin
{
    my ($self, $variant) = @_;

    my $bindPath = iMSCP::ProgramFinder::find( $variant ) or die(
        sprintf( "Couldn't find dialog program: %s", $variant )
    );
    $self->{'bin'} = $bindPath;
    $self;
}

=item _stripFormats( $string )

 Strip out any format characters (\Z sequences) from the given string

 Param string $string String from which any format character must be stripped
 Return string String stripped out of any format character

=cut

sub _stripFormats
{
    my (undef, $string) = @_;

    $string =~ s/\\Z[0-9bBuUrRn]//gmi;
    $string;
}

=item _buildCommonCommandOptions( [ $noEscape = false ] )

 Build common dialog command options

 Param bool $noEscape Whether or not option values must be escaped
 Return string|list Dialog command options

=cut

sub _buildCommonCommandOptions
{
    my ($self, $noEscape) = @_;

    my @options = map {
        defined $self->{'_opts'}->{$_}
            ? ( "--$_", ( $noEscape )
                ? ( $self->{'_opts'}->{$_} eq '' ? () : $self->{'_opts'}->{$_} )
                : ( $self->{'_opts'}->{$_} eq '' ? () : escapeShell( $self->{'_opts'}->{$_} ) ) )
            : ()
    } keys %{$self->{'_opts'}};

    wantarray ? @options : "@options";
}

=item _restoreDefaults( )

 Restore default options

 Return iMSCP::Dialog::Dialog

=cut

sub _restoreDefaults
{
    my ($self) = @_;

    for my $prop ( keys %{$self->{'_opts'}} ) {
        $self->{'_opts'}->{$prop} = undef unless $prop =~ /^(?:title|backtitle|colors)$/;
    }

    $self;
}

=item _execute( $text, $init, $type )

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

    if ( iMSCP::Getopt->noprompt ) {
        if ( $type ne 'infobox' && $type ne 'msgbox' ) {
            error( sprintf( 'Failed dialog: %s', $text ));
            exit 5
        }

        return 0;
    }

    $text = $self->_stripFormats( $text ) unless defined $self->{'_opts'}->{'colors'};
    $self->{'_opts'}->{'separate-output'} = '' if $type eq 'checklist';

    my $command = $self->_buildCommonCommandOptions();

    $text = escapeShell( $text );
    $init = $init ? $init : '';

    my $height = ( $self->{'autosize'} ) ? 0 : $self->{'lines'};
    my $width = ( $self->{'autosize'} ) ? 0 : $self->{'columns'};

    my $ret = execute( "$self->{'bin'} $command --$type $text $height $width $init", undef, \ my $output );

    $self->{'_opts'}->{'separate-output'} = undef;
    $self->_init() if $self->{'autoreset'};

    # The exit status returned when pressing the "No" button matches the exit status returned for the "Cancel" button.
    # Internally, no distinction is made... Therefore, for the "yesno" dialog box, we map exit status 30 to 1
    # and we make the backup feature available through the ESC keystroke. This necessarily means that user cannot abort
    # through a "yesno" dialog box
    if ( $ret == 50 && $type eq 'yesno' ) {
        $ret = 30;
    } elsif ( $ret == 30 && $type eq 'yesno' ) {
        $ret = 1;
    }

    wantarray ? ( $ret, $output ) : $output;
}

=item _textbox( $text, $type [, $init = 0 ])

 Wrap execution of several dialog box

 Param string $text Text to show
 Param string $mode Text dialog box type (radiolist|checklist|msgbox|yesno|inputbox|passwordbox|infobox)
 Param string $init Default value
 Return string|array Dialog output or array containing both dialog exit code and dialog output

=cut

sub _textbox
{
    my ($self, $text, $type, $init) = @_;

    $init //= 0;
    my $autosize = $self->{'autosize'};
    $self->{'autosize'} = undef;
    my ($ret, $output) = $self->_execute( $text, $init, $type );
    $self->{'autosize'} = $autosize;
    wantarray ? ( $ret, $output ) : $output;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
