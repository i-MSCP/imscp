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
use iMSCP::Dialog;
use iMSCP::Debug;
use parent 'Common::SingletonClass', 'Exporter';

use vars qw/@EXPORT_OK @EXPORT %EXPORT_TAGS/;

@EXPORT = qw/step startDetail endDetail/;

sub _init
{
	my $self = $_[0];

	$self->{'dialog'} = iMSCP::Dialog->getInstance();

	$self->{'title'} = "\n\\ZbStep %s of %s\\Zn\n\n%s";
	$self->{'all'} = [];
	$self->{'last'} = '';

	$self;
}

sub startDetail
{
	my $self = iMSCP::Stepper->getInstance();

	$self->{'dialog'}->endGauge(); # Needed to ensure refresh (first item)

	push (@{$self->{'all'}}, $self->{'last'});

	0;
}

sub endDetail
{
	my $self = iMSCP::Stepper->getInstance();

	$self->{'last'} = pop(@{$self->{'all'}});

	0;
}

sub step
{
	my $self = iMSCP::Stepper->getInstance();

	my ($code, $text, $steps, $index) = @_;

	$self->{'last'} = sprintf($self->{'title'}, $index, $steps, $text);

	my $msg = '';
	$msg = join("\n", @{$self->{'all'}}) . "\n" if @{$self->{'all'}};
	$msg .= $self->{'last'};

	$self->{'dialog'}->startGauge($msg, int($index * 100 / $steps));
	$self->{'dialog'}->setGauge(int($index * 100 / $steps), $msg);

	my $rs = 0;
	local $@;
	eval { $rs = &{$code}() if ref $code eq 'CODE'; };

	if($@) {
		error($@) if $@;
		$rs = 1;
	}

	if($rs) {
		return $rs if $rs == 50; # 50 is returned when ESC is preseed (dialog)

		my $errorMessage = $rs =~ /^-?\d+$/ ? getLastError() : $rs;

		# Make error message free of any ANSI color and end of line codes
		$errorMessage =~ s/\x1B\[([0-9]{1,3}((;[0-9]{1,3})*)?)?[m|K]//g;
		$errorMessage = 'An unexpected error occurred...' unless $errorMessage;

		$self->{'dialog'}->endGauge();
		$self->{'dialog'}->msgbox(<<EOF);

\\Z1[ERROR]\\Zn

Error while performing step:

$text

Error was:

\\Z1$errorMessage \\Zn

Please have a look at http://i-mscp.net/forum if you need help.
EOF

		return $rs;
	}

	0;
}

1;
__END__
