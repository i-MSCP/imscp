#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

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
	my $self = iMSCP::Stepper->new();

	$self->{'title'} = "\n\\ZbPerforming step %s from total of %s\\Zn\n\n%s";
	$self->{'all'} = [];
	$self->{'last'} = '';

	$self;
}

sub startDetail
{
	my $self = iMSCP::Stepper->new();

	push (@{$self->{all}}, $self->{'last'});

	0;
}

sub endDetail
{
	my $self = iMSCP::Stepper->new();

	$self->{'last'} = pop (@{$self->{'all'}});

	0;
}

sub step($ $ $ $)
{
	my $self = iMSCP::Stepper->new();

	my ($code, $text, $steps, $index, $exit) = (@_);

	$self->{last} = sprintf ($self->{'title'}, $index, $steps, $text);

	my $msg = join ("\n", @{$self->{'all'}}) . "\n" . $self->{'last'};

	iMSCP::Dialog->factory()->startGauge($msg, int($index * 100 / $steps)) if iMSCP::Dialog->factory()->needGauge();
	iMSCP::Dialog->factory()->setGauge(int($index * 100 / $steps), $msg);

	my $rs = &{$code}() if (ref $code eq 'CODE');

	if($rs) {
		iMSCP::Dialog->factory()->endGauge() if iMSCP::Dialog->factory()->needGauge();
		iMSCP::Dialog->factory()->msgbox(
			"\n
				\\Z1[ERROR]\\Zn

				Error while performing step:

				$text

				Error was:

				\\Z1".($rs =~ /^-?\d+$/ ? getLastError() : $rs)."\\Zn\n

				To obtain help please use http://i-mscp.net/forum/

			"
		);

		return $rs;
	}

	0;
}

1;
