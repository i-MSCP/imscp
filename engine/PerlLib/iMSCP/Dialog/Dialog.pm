=head1 NAME

 iMSCP::Dialog::Dialog - Dialog frontEnd

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

package iMSCP::Dialog::Dialog;

use strict;
use warnings;
use Carp 'croak';
use iMSCP::Boolean;
use iMSCP::Execute 'execute';
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use iMSCP::Dialog::TextFormatter qw/ wrap width /;
use parent 'iMSCP::Dialog::Resizable';

=head1 DESCRIPTION

 Dialog frontEnd
 
 Support both WHIPTAIL(1) and DIALOG(1)

=head1 PUBLIC METHODS/FUNCTIONS

=over 4

=item select( $text, \%choices [, $defaultTag = '' ] )

 See iMSCP::Dialog::FrontEndInterface::select()

=cut

sub select
{
    my ( $self, $text, $choices, $defaultTag ) = @_;

    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );
    ref $choices eq 'HASH' && %{ $choices } or croak(
        '\%choices parameter is undefined or invalid.'
    );
    ref \$defaultTag eq 'SCALAR' or croak(
        '$defaultTag parameter is invalid.'
    );

    # Figure out how much space in the dialog box the prompt will take.
    # The -2 tells _makePrompt to leave at least two lines to use to
    # display the list.
    ( $text, my $lines, my $columns ) = $self->_makePrompt( "\n" . $text, -2 );

    my $screenLines = $self->{'screenHeight'}-$self->{'spacer'};

    # Figure out how many lines of the screen should be used to
    # scroll the list. Look at how much free screen real estate
    # we have after putting the text at the top. If there's
    # too little, the list will need to scroll.
    my $countChoices = keys %{ $choices };
    my $menuHeight = $countChoices;
    $menuHeight++ if $self->{'program'} eq 'dialog';
    if ( $lines+$countChoices+2 >= $screenLines ) {
        $menuHeight = $screenLines-$lines-4;
    }

    $lines = $lines+$menuHeight+$self->{'spacer'};

    # Set status of each choice

    my @init;
    if ( $self->{'program'} ne 'whiptail'
        || $self->_getWhiptailVersion() > '05218'
    ) {
        for my $choice ( sort keys %{ $choices } ) {
            push @init, $choice, $choices->{$choice}, $choice eq $defaultTag
                ? 'on' : 'off';

            # Choices wider than the description text?
            if ( $columns < ( my $minColumns = width(
                $choice . ' ' . $choices->{$choice} )+$self->{'selectSpacer'}
            ) ) {
                $columns = $minColumns;
            }
        }
    } else {
        # WHIPTAIL(1) specific
        # The '--notags' option isn't working despite what the man page say.
        # This is a bug which has been fixed in newt library version 0.52.19.
        # See https://bugs.launchpad.net/ubuntu/+source/newt/+bug/1647762
        # We workaround the issue by using items as tags and by providing
        # empty items. Uniqueness of items is assumed here.
        for my $choice ( sort keys %{ $choices } ) {
            push @init, $choices->{$choice}, '', $choice eq $defaultTag
                ? 'on' : 'off';
            
            # Choices wider than the description text?
            if ( $columns < ( my $minColumns = width(
                $choice . ' ' . $choices->{$choice} )+$self->{'selectSpacer'}
            ) ) {
                $columns = $minColumns;
            }
        }
    }

    # Hide the tags in the dialog.
    # The '--notags' options is specific to WHIPTAIL(1).
    # This option is mapped to the '--no-tags' by DIALOG(1)
    local $self->{'_opts'}->{'notags'} = '';

    my ( $ret, $tag ) = $self->_showDialog(
        'radiolist', $text, $lines, $columns, $menuHeight, @init
    );

    if ( $self->{'program'} eq 'whiptail'
        && $self->_getWhiptailVersion() < '05219'
    ) {
        # WHIPTAIL(1) specific
        # See the above comment for the explanation
        # We need retrieve tag associated with selected item
        my %choices = reverse( %{ $choices } );
        $tag = $choices{$tag};
    }

    wantarray ? ( $ret, $tag ) : $tag;
}

=item multiselect( $text, \%choices [, \@defaultTags = [] ] )

 See iMSCP::Dialog::FrontEndInterface::multiselect()

=cut

sub multiselect
{
    my ( $self, $text, $choices, $defaultTags ) = @_;
    $defaultTags //= [];

    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );
    ref $choices eq 'HASH' && %{ $choices } or croak(
        '\%choices parameter is undefined or invalid.'
    );
    ref $defaultTags eq 'ARRAY' or croak(
        '\@defaultTags parameter is invalid.'
    );

    # Figure out how much space in the dialog box the prompt will take.
    # The -2 tells _makePrompt to leave at least two lines to use to
    # display the list.
    ( $text, my $lines, my $columns ) = $self->_makePrompt( "\n" . $text, -2 );

    my $screenLines = $self->{'screenHeight'}-$self->{'spacer'};

    # Figure out how many lines of the screen should be used to
    # scroll the list. Look at how much free screen real estate
    # we have after putting the text at the top. If there's
    # too little, the list will need to scroll.
    my $countChoices = keys %{ $choices };
    my $menuHeight = $countChoices;
    $menuHeight++ if $self->{'program'} eq 'dialog';
    if ( $lines+$countChoices+2 >= $screenLines ) {
        $menuHeight = $screenLines-$lines-4;
    }

    $lines = $lines+$menuHeight+$self->{'spacer'};

    my @init;
    if ( $self->{'program'} eq 'dialog'
        || $self->_getWhiptailVersion() > '05218'
    ) {
        for my $choice ( sort keys %{ $choices } ) {
            push @init, $choice, $choices->{$choice},
                grep ( $choice eq $_, @{ $defaultTags } ) ? 'on' : 'off';

            # Choices wider than the description text?
            if ($columns < ( my $minColumns = width(
                $choice . ' ' . $choices->{$choice} )+$self->{'selectSpacer'}
            ) ) {
                $columns = $minColumns;
            }
        }
    } else {
        # WHIPTAIL(1) specific
        # The '--notags' option isn't working despite what the man page say.
        # This is a bug which has been fixed in newt library version 0.52.19.
        # See https://bugs.launchpad.net/ubuntu/+source/newt/+bug/1647762
        # We workaround the issue by using items as tags and by providing
        # empty items. Uniqueness of items is assumed here.
        for my $choice ( sort keys %{ $choices } ) {
            push @init, $choices->{$choice}, '', grep (
                $choice eq $_, @{ $defaultTags }
            ) ? 'on' : 'off';

            # Choices wider than the description text?
            if ($columns < ( my $minColumns = width(
                $choice . ' ' . $choices->{$choice} )+$self->{'selectSpacer'}
            ) ) {
                $columns = $minColumns;
            }
        }
    }

    # Hide the tags in the dialog.
    # The '--notags' options is specific to WHIPTAIL(1).
    # This option is mapped to the '--no-tags' by DIALOG(1)
    #
    # The 'separate-output' option is common to both WHIPTAIL(1) and DIALOG(1).
    # This make the output result one line at a time, with no quoting.
    local @{ $self->{'_opts'} }{qw/ notags separate-output /} = ( '', '' );

    my ( $ret, $tags ) = $self->_showDialog(
        'checklist', $text, $lines, $columns, $menuHeight, @init
    );

    my @tags = split /\n/, $tags;
    if ( $self->{'program'} eq 'whiptail'
        && $self->_getWhiptailVersion() < '05219'
    ) {
        # WHIPTAIL(1) specific
        # See the above comment for explanation. We need retrieve tags
        # associated with selected items.
        my %choices = reverse( %{ $choices } );
        @tags = map { $choices{$_} } @tags;
    }

    wantarray ? ( $ret, \@tags ) : \@tags;
}

=item boolean( $text [, $defaultno =  FALSE ] )

 See iMSCP::Dialog::FrontEndInterface::boolean()

 Note: When the backup feature is enabled, and if the DIALOG(1) program is
       used, we make use of the 'extra' button to make user able to go back.
       When the WHIPTAIL(1) program is used, there is no way to setup an
       additional button. Instead, user can go back by pressing the ESC key.

=cut

sub boolean
{
    my ( $self, $text, $defaultno ) = @_;

    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );

    $text = "\n" . $text if $self->{'program'} eq 'dialog' && length $text;

    # DIALOG(1) specific
    # When the backup feature is enabled, we make use of the 'extra' button to
    # make the user able to go back. By default, the 'yesno' dialog make use of
    # the 'yes' and 'no' buttons, but when we make use of the 'extra' button,
    # the dialog make use of the 'ok', 'extra' and 'cancel' buttons, in this
    # order. Thus, we process as follows:
    #
    # - We set the code of the 'extra' button to 1
    # - We set the code of the 'cancel' button to 30 (done in _init())
    # - We set the label of the 'ok' button to 'Yes' if not overridden
    # - We set the label of the extra button to 'No' (done in _init())
    # - We set the label of the 'cancel' button to 'Back' (done in _init())
    # - We set the 'extra' button as default button if $defaultno is TRUE
    #
    # So 'Yes' = 0, 'No' = 1, 'Back' = 30
    local $ENV{'DIALOG_EXTRA'} = 1 if $self->backup()
        && $self->{'program'} eq 'dialog';
    local @{ $self->{'_opts'} }{qw/ default-button extra-button ok-label /} = (
        $defaultno ? 'extra' : undef,
        '',
        $self->{'_opts'}->{'ok-label'} eq 'Next'
            ? 'Yes' : $self->{'_opts'}->{'ok-label'}
    ) if $self->backup() && $self->{'program'} eq 'dialog';

    # Note 1 is passed in, because we can squeeze on one more line
    # in a 'yesno' dialog than in other types.
    ( $text, my $lines, my $columns ) = $self->_makePrompt( $text, 1 );

    # We need set the '--defaultno' option only when the backup feature is
    # disabled, else, DIALOG(1) would set the 'cancel' button as default
    # button and this would overlap with the '--default-button' options.
    local $self->{'_opts'}->{'defaultno'} = '' if $defaultno
        && ( $self->{'program'} eq 'whiptail' || !$self->backup() );

    ( $self->_showDialog( 'yesno', $text, $lines, $columns ) )[0];
}

=item text( $text )

 See iMSCP::Dialog::FrontEndInterface::text()
 
 When the backup feature is enabled, and if the DIALOG(1) program is used, we
 make use of the 'help' button to make user able to go back. When the
 WHIPTAIL(1) program is used, there is no way to setup an 'extra' button.
 Instead, user can go back by pressing the ESC key.

=cut

sub text
{
    my ( $self, $text ) = @_;

    # DIALOG(1) specific
    #
    # When the backup feature is enabled, we make use of the 'help' button to
    # make user able to go back.
    #
    # - We set the code of the 'help' button to 30 (done in _init())
    # - We set the label of the help button to 'Back' (done in _init())
    #
    # So 'Ok' = 0, 'Back' = 30
    local $ENV{'DIALOG_HELP'} = 30 if $self->{'program'} eq 'dialog';
    local $self->{'_opts'}->{'help-button'} = '' if $self->backup()
        && $self->{'program'} eq 'dialog';

    $self->_showText( $text );
}

=item note( $text )

 See iMSCP::Dialog::FrontEndInterface::note()

 When the backup feature is enabled, and if the DIALOG(1) program is used, we
 make use of the 'help' button to make user able to go back. When the
 WHIPTAIL(1) program is used, there is no way to setup an 'extra' button.
 Instead, user can go back by pressing the ESC key.

=cut

sub note
{
    my ( $self, $text ) = @_;

    # DIALOG(1) specific
    #
    # When the backup feature is enabled, we make use of the 'help' button to
    # make user able to go back.
    #
    # - We set the code of the 'help' button to 30 (done in _init())
    # - We set the label of the help button to 'Back' (done in _init())
    #
    # So 'Ok' = 0, 'Back' = 30
    local $ENV{'DIALOG_HELP'} = 30 if $self->{'program'} eq 'dialog';
    local $self->{'_opts'}->{'help-button'} = '' if $self->backup()
        && $self->{'program'} eq 'dialog';

    $self->_showText( $text );
}

=item error( $text )

 See iMSCP::Dialog::FrontEndInterface::error()

=cut

sub error
{
    my ( $self, $text ) = @_;

    local $self->{'_errorBox'} = TRUE;
    local $self->{'_opts'}->{
        $self->{'program'} eq 'dialog' ? 'ok-label' : 'ok-button'
    } = 'Abort' unless ( $self->{'program'} eq 'dialog' && $self->{'_opts'}->{'ok-label'} ne 'Next');

    $self->_showText( $text );
    1;
}
=item string( $text [, $default = '' ] )

 See iMSCP::Dialog::FrontEndInterface::string()

=cut

sub string
{
    my ( $self, $text, $default ) = @_;
    $default //= '';

    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );
    ref \$default eq 'SCALAR' or croak( '$default parameter is invalid.' );

    ( $text, my $lines, my $columns ) = $self->_makePrompt( "\n" . $text );

    $self->_showDialog(
        'inputbox', $text, $lines+$self->{'spacer'}, $columns, $default
    );
}

=item password( $text [, $default = '' ] )

 See iMSCP::Dialog::FrontEndInterface::password()

=cut

sub password
{
    my ( $self, $text, $default ) = @_;
    $default //= '';

    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );
    ref \$default eq 'SCALAR' or croak( '$default parameter is invalid.' );

    ( $text, my $lines, my $columns ) = $self->_makePrompt( "\n" . $text );

    # By default, DIALOG(1) doesn't show asterisks for password characters
    # which make the dialog less friendly. We change that behavior by setting
    # the '--insecure' option.
    local $self->{'_opts'}->{'insecure'} = '' if $self->{'program'} eq 'dialog';

    my ( $ret, $output ) = $self->_showDialog(
        'passwordbox', $text, $lines+$self->{'spacer'}, $columns, $default
    );

    wantarray ? ( $ret, $output ) : $output;
}

=item startGauge( $text [, $percent = 0 ] )

 See iMSCP::Dialog::FrontEndInterface::startGauge()

=cut

sub startGauge
{
    my ( $self, $text, $percent ) = @_;

    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );
    ref \$percent eq 'SCALAR' && $percent =~ /^[\d]+$/ or croak(
        '$percent parameter is invalid.'
    );

    $self->endGauge();

    ( $text, my $lines, my $columns ) = $self->_formatText( "\n" . $text );

    # Force progress bar to full available width, to avoid windows
    # flashing.
    if ( $self->{'screenWidth'}-$self->{'columnSpacer'} > $columns ) {
        $columns = $self->{'screenWidth'}-$self->{'columnSpacer'}
    }

    open $self->{'_gauge'}, '|-', $self->{'program'},
        $self->_getDialogOptions( 'gauge' ),
        $text,
        $lines+$self->{'spacer'},
        $columns,
        $percent or croak( "Couldn't start gauge" );

    $self->{'_gauge'}->autoflush();
    $self->{'_lines'} = $lines;
    $self->{'_columns'} = $columns;
}

=item setGauge( $percent [ , $text ] )

 See iMSCP::Dialog::FrontEndInterface::setGauge()

=cut

sub setGauge
{
    my ( $self, $percent, $text ) = @_;

    ref \$percent eq 'SCALAR' && $percent =~ /^[\d]+$/ or croak(
        '$percent parameter is invalid.'
    );
    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );

    unless ( $self->hasGauge() ) {
        $self->startGauge( $text, $percent );
        return;
    }

    unless ( length $text ) {
        print { $self->{'_gauge'} } $percent . "\n";
        return;
    }

    ( $text, my $lines, my $columns ) = $self->_formatText( "\n" . $text );

    if ( $lines > $self->{'_lines'} || $columns > $self->{'_columns'} ) {
        # start a new, bigger dialog box if the current won't fit
        $self->startGauge( $text =~ s/^\n//r, $percent );
        return;
    }

    # The line immediately following the marker should be a new
    # percentage, but whiptail (as of 0.51.6-17) looks for a percentage
    # in the wrong buffer and fails to refresh the display as a result.
    # To work around this bug, we give it the current percentage again
    # afterwards to force a refresh.
    print { $self->{'_gauge'} } sprintf(
        "XXX\n%d\n%s\nXXX\n%d\n", $percent, $text, $percent
    );
}

=item endGauge( )

 See iMSCP::Dialog::FrontEndInterface::endGauge()

=cut

sub endGauge
{
    my ( $self ) = @_;

    return unless $self->hasGauge();

    $self->{'_gauge'}->close();
    undef $self->{'_gauge'};
}

=item hasGauge( )

 See iMSCP::Dialog::FrontEndInterface::hasGauge()

=cut

sub hasGauge
{
    my ( $self ) = @_;

    !!$self->{'_gauge'}
}

=item backup( $enabled = TRUE )

 See iMSCP::Dialog::FrontEndInterface::backup()

=cut

sub backup
{
    my ( $self, $enabled ) = @_;

    return $self->{'_backup'} unless defined $enabled;

    if ( $enabled ) {
        if ( $self->{'program'} eq 'dialog' ) {
            # DIALOG(1) specific
            # Backup capability. Change status codes accordingly
            @ENV{qw/ DIALOG_CANCEL DIALOG_ESC DIALOG_HELP /} = ( 30, 30, 30 );
        }

        return $self->{'_backup'} = TRUE;
    }

    if ( $self->{'program'} eq 'dialog' ) {
        # DIALOG(1) specific
        # No backup capability. Change status codes accordingly
        @ENV{qw/ DIALOG_CANCEL DIALOG_ESC DIALOG_HELP /} = ( 1, 0, 2 );
    }

    $self->{'_backup'} = FALSE;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 See iMSCP::Common::Singleton::_init()

=cut

sub _init
{
    my $self = shift;

    $self->SUPER::_init( @_ );

    # These environment variable screws up at least whiptail with the
    # way we call it. Posix does not allow safe arg passing like
    # whiptail needs.
    delete $ENV{'POSIXLY_CORRECT'} if exists $ENV{'POSIXLY_CORRECT'};
    delete $ENV{'POSIX_ME_HARDER'} if exists $ENV{'POSIX_ME_HARDER'};

    # Detect all the ways people have managed to screw up their
    # terminals (so far...)
    if ( !exists $ENV{'TERM'} || !defined $ENV{'TERM'} || $ENV{'TERM'} eq '' ) {
        die( 'TERM is not set, so the dialog frontend is not usable.' );
    } elsif ( $ENV{'TERM'} =~ /emacs/i ) {
        die( 'Dialog frontend is incompatible with emacs shell buffers' );
    } elsif ( $ENV{'TERM'} eq 'dumb' || $ENV{'TERM'} eq 'unknown' ) {
        die( 'Dialog frontend will not work on a dumb terminal, an emacs shell buffer, or without a controlling terminal.' );
    }

    if ( iMSCP::ProgramFinder::find( 'dialog' )
        && ( !$ENV{'IMSCP_DIALOG_FORCE_WHIPTAIL'} ||
        !iMSCP::ProgramFinder::find( 'whiptail' )
    ) ) {
        # Force usage of graphic lines (UNICODE values) when using putty (See #540)
        $ENV{'NCURSES_NO_UTF8_ACS'} = TRUE;

        @{ $self }{qw/
            program borderWidth borderHeight spacer titleSpacer columnSpacer
            selectSpacer
        /} = (
            'dialog', 7, 5, 1, 4, 5,
            0
        );

        # DIALOG(1) dialog options.
        # Only relevant options are listed.
        @{ $self->{'_opts'} }{qw/
            ok-label yes-label no-label cancel-label extra-label help-label
            colors no-collapse
        /} = (
            'Next', 'Yes', 'No', 'Back', 'No', 'Back',
            '', ''
        );
    } elsif ( iMSCP::ProgramFinder::find( 'whiptail' ) ) {
        @{ $self }{qw/
            program borderWidth borderHeight spacer titleSpacer columnSpacer
            selectSpacer
        /} = (
            'whiptail', 5, 6, 1, 10, 3,
            13
        );

        # WHIPTAIL(1) options.
        # Only relevant options are listed.
        @{ $self->{'_opts'} }{qw/
            ok-button yes-button no-button cancel-button
        /} = (
            'Next', 'Yes', 'No', 'Back'
        );
    } else {
        die( 'No usable dialog-like program is installed.' );
    }

    # WHIPTAIL(1)/DIALOG(1) common options.
    # Only relevant options are listed.
    @{ $self->{'_opts'} }{qw/
        backtitle title
        clear
    /} = (
        'i-MSCP - internet Multi Server Control Panel', 'Installer Dialog'
    );

    # Whiptail and dialog can't deal with very small screens.
    if ( $self->{'screenHeight'} < 13 || $self->{'screenWidth'} < 31 ) {
        die( "A screen at least 13 lines tall and 31 columns wide is required.\n" );
    }

    # Enable backup feature by default
    $self->backup( TRUE );

    $self;
}

=item _getDialogOptions( $dialogType )

 Get dialog options

 Param string $dialogType Dialog type (radiolist,checklist,inputbox,passwordbox,msgbox,gauge)
 Return List Dialog options

=cut

sub _getDialogOptions
{
    my ( $self, $dialogType ) = @_;

    my %opts = %{ $self->{'_opts'} };

    # Remove unwanted WHIPTAIL(1)/DIALOG(1) options according dialog type
    if ( $dialogType eq 'msgbox' ) {
        if ( $self->backup() && !$self->{'_errorBox'} ) {
            delete @{opts}{qw/
                yes-label no-label cancel-label extra-label
                yes-button no-button cancel-button
            /};
        } else {
            delete @{opts}{qw/
                yes-label no-label cancel-label extra-label help-label
                yes-button no-button cancel-button
            /};
        }
    } elsif ( $dialogType eq 'gauge' ) {
        delete @{opts}{qw/
            ok-label yes-label no-label cancel-label extra-label help-label
            ok-button yes-button no-button cancel-button
        /};
    } elsif ( $dialogType eq 'yesno' ) {
        if ( $self->backup() ) {
            delete @{opts}{qw/
                yes-label no-label help-label
                ok-button cancel-button
            /};
        } else {
            delete @{opts}{qw/
                ok-label cancel-label extra-label help-label
                ok-button cancel-button
            /};
        }
    } elsif ( grep ( $dialogType eq $_, qw/ inputbox passwordbox radiolist checklist /) ) {
        if ( $self->backup() ) {
            delete @{opts}{qw/
                yes-label no-label extra-label help-label
                yes-button no-button
            /};
        } else {
            delete @{opts}{qw/
                yes-label no-label cancel-label extra-label help-label
                yes-button no-button cancel-button
            /};

            # WHIPTAIL(1) '--nocancel' option, mapped to '--no-cancel' by
            # DIALOG(1)
            $opts{'nocancel'} = '';
        }
    }

    # Prepare options
    ( ( map { defined $opts{$_}
        ? ( '--' . $_, ( $opts{$_} eq '' ? () : $opts{$_} ) ) : ()
    } keys %opts ), "--$dialogType", '--' );
}

=item _showDialog( $dialogType, @dialogOptions )

 Display a dialog

 Param string $dialogType Dialog type (radiolist|checklist|inputbox|passwordbox|msgbox)
 Param list @dialogOptions Dialog options 
 Return string|list Dialog output in scalar context, an array containing both
        dialog return code and dialog output in list context, croak on failure

=cut

sub _showDialog
{
    my ( $self, $dialogType, @dialogOptions ) = @_;

    $self->endGauge();

    my $ret = execute(
        [
            $self->{'program'},
            $self->_getDialogOptions( $dialogType ),
            map &_hideEscape, @dialogOptions
        ],
        undef,
        \my $output
    );

    if ( $self->{'program'} eq 'whiptail' ) {
        if ( $self->backup() ) {
            # We need return 30 when user hit escape or cancel button
            $ret = 30 if $ret == 255 || ( $ret == 1 && $dialogType ne 'yesno' );
        } elsif ( $ret == 255 ) {
            $ret = 0;
        }
    }

    # Both dialog output and dialog errors goes to STDERR. We need catch errors
    !length $output or croak $output if $ret == 255;

    wantarray ? ( $ret, $output ) : $output;
}

=item _stripEmbeddedSequences( $text )

 Strip out any DIALOG(1) embedded '\Z' sequences from the given text

 Because this class is the parent class of the iMSCP::Dialog::Dialog frontEnd,
 we need strip off any DIALOG(1) embedded '\Z' sequences since those are not
 interpreted by WHIPTAIL(1).

 Param string $text Text from which DIALOG(1) embedded '\Z' sequences must be stripped off
 Return string Text stripped off of any DIALOG(1) embedded '\Z' sequences

=cut

sub _stripEmbeddedSequences
{
    my ( $self, $text ) = @_;

    return $text if $self->{'program'} eq 'dialog';

    $text =~ s/\\Z[0-7bBrRuUn]//gmr;
}

=item _hideEscape( $line )

 Used to hide escaped characters in input text from processing.

 Param string $line to process
 Return string Processed line

=cut

sub _hideEscape
{
    s/\\n/\\\xe2\x81\xa0n/gr;
}

=item _getWhiptailVersion

 Get whiptail version

 Return string version (stripped of any dot)

=cut

sub _getWhiptailVersion
{
    my ( $self ) = @_;

    $self->{'_whiptail_version'} //= do {
        my ( $stdout, $stderr );
        execute(
            [ $self->{'program'}, '--version' ],
            \$stdout, \$stderr
        ) == 0 or die(
            "Couldn't get whiptail version: $stderr"
        );

        $stdout =~ /([\d.]+)/i or die(
            "Couldn't retrieve whiptail version in version string"
        );
        $1 =~ s/\.//gr;
    };
}

=item _formatText( $text )

 Format the given text to be displayed in a dialog box according current
 display properties

 Param string $text Text to format
 Return list conting formatted text and required box height and width to
        print the formatted text according the current display properties

=cut

sub _formatText
{
    my ( $self, $text ) = @_;

    $text = $self->_stripEmbeddedSequences( $text );

    $iMSCP::Dialog::TextFormatter::columns = $self->{'screenWidth'}
        -$self->{'borderWidth'}
        -$self->{'columnSpacer'};

    $text = wrap( '', '', $text );
    my @lines = split /\n/, $text;

    my $boxWidth = length $self->{'_opts'}->{'title'}
        ? width( $self->{'_opts'}->{'title'} )+$self->{'titleSpacer'}
        : 0;

    map {
        my $width = width( $_ );
        $boxWidth = $width if $width > $boxWidth
    } @lines;

    (
        $text,
        $#lines+1+$self->{'borderHeight'},
        $boxWidth+$self->{'borderWidth'}
    );
}

=item _makePrompt( $text [, $requiredFreeLines ] )

 Helper function for some dialog boxes.

 Param string $text
 Param int $requiredFreeLines How many lines must be free on sreen
 Return list A list containing formatted text, required screen lines and columns

=cut

sub _makePrompt
{
    my ( $self, $text, $requiredFreeLines ) = @_;
    my $freeLines = $self->{'screenHeight'}-$self->{'borderHeight'}+1;
    $freeLines += $requiredFreeLines if $requiredFreeLines;

    ( $text, my $lines, my $columns ) = $self->_formatText( $text );

    if ( $lines > $freeLines ) {
        local $self->{'_opts'}->{'ok-button'} = 'Retry';
        $self->_showText( <<"EOF" );
Your terminal screen is too small to display the dialog box.
Please enlarge it.
EOF
        goto &{_makePrompt};
    }

    ( $text, $lines, $columns );
}

=item _showText

 Display the given text in a dialog box
 
 If the text is too long, it will be displayed in a scrollable dialog box.

 Param string $text Text to display
 Return int 0 (Ok), 30 (Backup), croak on failure

=cut

sub _showText
{
    my ( $self, $text ) = @_;

    ref \$text eq 'SCALAR' or croak( '$text parameter is invalid.' );

    $text = "\n" . $text if $self->{'program'} eq 'dialog';
    ( $text, my $lines, my $columns ) = $self->_formatText( $text );

    local $self->{'_opts'}->{'scrolltext'};
    if ( $lines > ( my $maxLines = $self->{'screenHeight'}-$self->{'borderHeight'} ) ) {
        $lines = $maxLines;
        $self->{'_opts'}->{'scrolltext'} = '';
    }

    ( $self->_showDialog( 'msgbox', $text, $lines, $columns ) )[0];
}

=item DESTROY()

 Destroy dialog object

=cut

sub DESTROY
{
    $_[0]->endGauge();
    $SIG{'WINCH'} = 'DEFAULT';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
