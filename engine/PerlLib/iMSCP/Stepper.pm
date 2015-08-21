=head1 NAME

 iMSCP::Stepper - i-MSCP stepper

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

package iMSCP::Stepper;

use strict;
use warnings;
use Carp;
use iMSCP::Debug;
use iMSCP::Dialog;
use parent 'Common::SingletonClass', 'Exporter';

use vars qw/@EXPORT_OK @EXPORT %EXPORT_TAGS/;

@EXPORT = qw/step startDetail endDetail/;

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
	my $self = iMSCP::Stepper->getInstance();

	$self->{'dialog'}->endGauge(); # Needed to ensure refresh (first item)
	push @{$self->{'all'}}, $self->{'last'};
	0;
}

=item endDetail()

 End step group details

 Return int 0

=cut

sub endDetail
{
	my $self = iMSCP::Stepper->getInstance();

	$self->{'last'} = pop @{$self->{'all'}};
	0;
}

=item step($code, $text, $nSteps, $nStep)

 Process a step
 Param coderef $code Step code
 Param string $text Step description
 Param int $nSteps Total number of steps (for a group of steps)
 Param int $nStep Current step number
 Return 0 on success, die on failure

=cut

sub step
{
	my ($self, $code, $text, $nSteps, $nStep) = (iMSCP::Stepper->getInstance(), @_);

	$self->{'last'} = sprintf("\n\\ZbStep %s of %s\\Zn\n\n%s", $nStep, $nSteps, $text);

	my $msg = @{$self->{'all'}} ? join("\n", @{$self->{'all'}}) . "\n" . $self->{'last'} : $self->{'last'};

	unless ($self->{'dialog'}->hasGauge()) {
		$self->{'dialog'}->startGauge($msg, int($nStep * 100 / $nSteps));
	} else {
		$self->{'dialog'}->setGauge(int($nStep * 100 / $nSteps), $msg);
	}

	local $@;
	my $rs = eval { &$code };
	if($@) {
		error($@) if $@;
		$rs = 1;
	}

	if($rs) {
		return $rs if $rs == 50; # 50 is returned when ESC is preseed (dialog)
		#my $errorMessage = $rs =~ /^-?\d+$/ ? getLastError() : $rs;
		my $errorMessage = getLastError();

		# Make error message free of any ANSI color and end of line codes
		$errorMessage =~ s/\x1B\[([0-9]{1,3}((;[0-9]{1,3})*)?)?[m|K]//g;
		$errorMessage = 'An unexpected error occurred...' unless $errorMessage;

		$self->{'dialog'}->endGauge();
		$self->{'dialog'}->msgbox(<<EOF);

\\Z1[ERROR]\\Zn

Error while performing step:

$text

Error was:

\\Z1$errorMessage\\Zn

Please have a look at http://i-mscp.net/forum if you need help.
EOF

		croak($errorMessage);
	}

	0;
}

=item _init()

 Initialize instance

 Return iMSCP::Stepper

=cut

sub _init
{
	my $self = shift;

	$self->{'dialog'} = iMSCP::Dialog->getInstance();
	$self->{'all'} = [];
	$self->{'last'} = '';
	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
