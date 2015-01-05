#!/usr/bin/perl

=head1 NAME

 iMSCP::TemplateParser - i-MSCP Template parser implementation

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
#
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::TemplateParser;

use strict;
use warnings;

use iMSCP::Debug;
use parent 'Common::SingletonClass', 'Exporter';

use vars qw/@EXPORT/;
@EXPORT = qw/process getBloc replaceBloc/;

my $self = __PACKAGE__->getInstance();

=head1 DESCRIPTION

 The template parser allow to parse pseudo-variables within i-MSCP engine template files. It can parse simple variables
 or variable tag pairs

=head1 PUBLIC METHODS

=over 4

=item process(\%data, $tplContent)

 Parse the given template content

 Param hash \%data A hash of data where the keys are the pseudo-variable names and the values, the replacement values
 Param string $tplContent The template content to be parsed
 Return string Parsed template content

=cut

sub process
{
	my ($data, $tplContent) = @_;
	$data = { } if ref $data ne 'HASH';

	for (keys %{$data}) {
		next unless defined $data->{$_};

		my $regexp = sprintf('\\{%s\\}', quotemeta($_));
		$tplContent =~ s/$regexp/$data->{$_}/gim
	}

	$tplContent;
}

=item getBloc($beginTag, $endingTag, $tplContent, [$includeTags = false])

 Get a bloc within the given template content

 Param string Bloc begin tag
 Param string Bloc ending tag
 param string Template content which contain the bloc to return
 Param bool $includeTags Whether or not begin and ending tag should be included in result
 Return string Bloc content, including or not the begin and ending tags

=cut

sub getBloc
{
	my ($beginTag, $endingTag, $tplContent, $includeTags) = @_;
	my $regexp = '[\t ]*' . quotemeta($beginTag) . '(.*?)[\t ]*' . quotemeta($endingTag);
	my $ret = '';

	if($includeTags) {
		$ret = $1 if $tplContent =~ m/($regexp)/gims;
	} elsif($tplContent =~ m/$regexp/gims) {
		$ret = $1;
	}

	$ret;
}

=item replaceBloc($beginTag, $endingTag, $replacement, $tplContent, [$preserveTags = false])

 Replace a bloc within the given template content

 Param string $beginTag Bloc begin tag
 Param string $endingTag Bloc ending tag
 Param string $replacement Bloc replacement string
 param string String which contain the bloc to replace
 Param bool $preserveTags Whether or not begin and ending tag should be preverved
 Return string Parsed template content

=cut

sub replaceBloc
{
	my ($beginTag, $endingTag, $replacement, $tplContent, $preserveTags) = @_;
	my $regexp = '([\t ]*' . quotemeta($beginTag) . '.*?' . quotemeta($endingTag) . ')';

	if($preserveTags) {
		$tplContent =~ s/$regexp/$replacement$1/gis;
	} else {
		$tplContent =~ s/$regexp/$replacement/gis;
	}

	$tplContent;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
