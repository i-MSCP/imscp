=head1 NAME

 iMSCP::Stepper - i-MSCP stepper

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
use parent 'Exporter';

our @EXPORT = qw/startDetail endDetail step/;

# Package variables
my @all = ();
my $last = '';

=head1 DESCRIPTION

 i-MSCP stepper.

=head1 PUBLIC METHODS

=over 4

=item startDetail()

 Start new steps group details

 Return int 0

=cut

sub startDetail
{
    return 0 if iMSCP::Getopt->noprompt;
    iMSCP::Dialog->getInstance()->endGauge(); # Needed to ensure refresh (first item)
    push @all, $last;
    0;
}

=item endDetail()

 End step group details

 Return int 0

=cut

sub endDetail
{
    return 0 if iMSCP::Getopt->noprompt;
    $last = pop @all;
    0;
}

=item step($code, $text, $nSteps, $nStep)

 Process a step
 Param coderef|undef $code Step code
 Param string $text Step description
 Param int $nSteps Total number of steps (for a group of steps)
 Param int $nStep Current step number
 Return 0 on success, die on failure

=cut

sub step
{
    my ($code, $text, $nSteps, $nStep) = @_;

    unless(iMSCP::Getopt->noprompt) {
        $last = sprintf( "\n\\ZbStep %s of %s\\Zn\n\n%s", $nStep, $nSteps, $text );
        my $msg = @all ? join( "\n", @all )."\n".$last : $last;
        iMSCP::Dialog->getInstance()->hasGauge()
            ? iMSCP::Dialog->getInstance()->setGauge(int($nStep * 100 / $nSteps), $msg )
            : iMSCP::Dialog->getInstance()->startGauge($msg, int($nStep * 100 / $nSteps));
    }

    return 0 unless defined $code;

    local $@;
    my $rs = eval { &{$code} };
    if ($@) {
        error($@) if $@;
        $rs = 1;
    }

    return $rs unless $rs && $rs != 50;

    # Make error message free of any ANSI color and end of line codes
    (my $errorMessage = getLastError()) =~ s/\x1B\[([0-9]{1,3}((;[0-9]{1,3})*)?)?[m|K]//g;
    $errorMessage = 'An unexpected error occurred...' unless $errorMessage;

    unless(iMSCP::Getopt->noprompt) {
        iMSCP::Dialog->getInstance()->endGauge();
        iMSCP::Dialog->getInstance()->msgbox(<<EOF);

\\Z1[ERROR]\\Zn

Error while performing step:

$text

Error was:

\\Z1$errorMessage\\Zn

Please have a look at http://i-mscp.net/forum if you need help.
EOF
    }

    die($errorMessage);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
