=head1 NAME

 iMSCP::Stepper - i-MSCP stepper

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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

package iMSCP::Stepper;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::Getopt;
use Scalar::Defer;
use parent 'Exporter';

our @EXPORT = qw/ startDetail endDetail step /;

my @all = ();
my $last = '';
my $dialog = lazy { iMSCP::Dialog->getInstance(); };
my $step = lazy { iMSCP::Getopt->noprompt ? \&_callback : \&_step; };

=head1 DESCRIPTION

 i-MSCP stepper

=head1 PUBLIC FUNCTIONS

=over 4

=item startDetail( )

 Start new steps group details

 Return int 0

=cut

sub startDetail
{
    return 0 if iMSCP::Getopt->noprompt;
    $dialog->endGauge();
    push @all, $last;
    0;
}

=item endDetail( )

 End step group details

 Return int 0

=cut

sub endDetail
{
    return 0 if iMSCP::Getopt->noprompt;
    $last = pop @all;
    0;
}

=item step( $callback, $text, $nSteps, $nStep )

 Process a step

 Param callback|undef $callback Callback
 Param string $text Step description
 Param int $nSteps Total number of steps (for a group of steps)
 Param int $nStep Current step number
 Return 0 on success, other on failure

=cut

sub step
{
    $step->( @_ );
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _callback( )

 Execute the given callback

 Param callback $callback Callback to execute
 Return int 0 on success, other on failure

=cut

sub _callback
{
    my ($callback) = @_;

    return 0 unless defined $callback;

    local $@;
    my $rs = eval { $callback->() };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item _dialogstep
 
 See step( )
 
=cut

sub _step
{
    my ($callback, $text, $nSteps, $nStep) = @_;

    $last = sprintf( "\n\\ZbStep %s of %s\\Zn\n\n%s", $nStep, $nSteps, $text );
    my $msg = @all ? join( "\n", @all ) . "\n" . $last : $last;

    use integer;
    my $percent = $nStep * 100 / $nSteps;

    $dialog->hasGauge ? $dialog->setGauge( $percent, $msg ) : $dialog->startGauge( $msg, $percent );
    my $rs = _callback( $callback );

    return $rs unless defined $callback;
    return $rs unless $rs && $rs != 50;

    # Make error message free of any ANSI color and end of line codes
    ( my $errorMessage = getLastError() ) =~ s/\x1B\[([0-9]{1,3}((;[0-9]{1,3})*)?)?[m|K]//g;
    $errorMessage = 'An unexpected error occurred...' unless $errorMessage;
    $errorMessage =~ s/\n+$//;

    $dialog->endGauge();
    $dialog->msgbox( <<"EOF" );
\\Z1[ERROR]\\Zn

Error while performing step:

$text

Error was:

\\Z1$errorMessage\\Zn

Please have a look at http://i-mscp.net/forum if you need help.
EOF

    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
