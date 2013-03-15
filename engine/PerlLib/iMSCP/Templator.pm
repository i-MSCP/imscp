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
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Templator;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass', 'Exporter';

use vars qw/@EXPORT/;
@EXPORT = qw/process replaceBloc getBloc/;

sub _init
{
	my $self = shift;

	$self->{'varStartTag'} = '\{';
	$self->{'varEndTag'} = '\}';
	$self->{'varRegexp'} = "$self->{varStartTag}%s$self->{varEndTag}";
	$self->{'inclusionTagStart'} = '# [(\{.*\})](.*)START\.';
	$self->{'inclusionTagEnd'} = '# [\1](.)END\.';

	$self;
}

sub set($$)
{
	my $self = iMSCP::Templator->getInstance();
	my $prop = shift;
	my $value = shift;

	debug("Setting $prop as $value");

	$self->{$prop} = $value if exists $self->{$prop};
}

sub process($$)
{
	my $self = iMSCP::Templator->getInstance();

	$self->{'vars'} = shift || ref {};
	$self->{'tContent'} = shift || '';

	$self->{'vars'} = {} if ref $self->{'vars'} ne 'HASH';

	$self->_replaceStatic();

	$self->{'args'} = {};
	$self->_init();

	$self->{'tContent'};
}

sub getBloc($$$;$)
{
	my $self = iMSCP::Templator->getInstance();
	my $startTag = shift;
	my $endTag = shift;
	my $content = shift;
	my $includeTags = shift || 0;

	my $regexp = '[\t ]*' . quotemeta($startTag) . '(.*)' . quotemeta($endTag);
	my $rs = '';

	if($includeTags) {
		$rs = $1 if $content =~ m/($regexp)/gims;
	} elsif($content =~ m/$regexp/gims) {
		$rs = $1;
	}

	$rs;
}

sub replaceBloc($$$$;$)
{
	my $self = iMSCP::Templator->getInstance();
	my $startTag = shift;
	my $endTag = shift;
	my $replacement = shift;
	my $content = shift;
	my $preserve = shift || 0;

	my $regexp = '([\t ]*' . quotemeta($startTag) . '.*?' . quotemeta($endTag) . ')';

	if($preserve) {
		$content =~ s/$regexp/$1$replacement/gis;
	} else {
		$content =~ s/$regexp/$replacement/gis;
	}

	return $content;
}

sub _replaceStatic
{
	my $self = shift;

	for (keys %{$self->{'vars'}}) {
		next unless defined $self->{'vars'}->{$_};

		my $regexp = sprintf($self->{'varRegexp'}, quotemeta($_));
		$self->{'tContent'} =~ s/$regexp/$self->{'vars'}->{$_}/gim
	}
}

1;
