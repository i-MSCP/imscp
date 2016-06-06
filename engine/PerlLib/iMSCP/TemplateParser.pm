=head1 NAME

 iMSCP::TemplateParser - i-MSCP Template parser

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

package iMSCP::TemplateParser;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Exporter';

our @EXPORT = qw/ process getBloc replaceBloc /;

=head1 DESCRIPTION

 The template parser allow to parse pseudo-variables within i-MSCP template files. It can parse simple variables
 or variable tag pairs

=head1 PUBLIC METHODS

=over 4

=item process(\%data, $template)

 Replace placeholders in the given template

 Param hash \%data A hash of data where the keys are the pseudo-variable names and the values, the replacement values
 Param string $template The template content to be parsed
 Return string Parsed template content

=cut

sub process
{
    my ($data, $template) = @_;
    return $template unless ref $data eq 'HASH';
    while (my ($placeholder, $value) = each( %{$data} )) {
        next unless defined $value;
        $template =~ s/\Q{$placeholder}\E/$value/gim
    }
    $template;
}

=item getBloc($beginTag, $endingTag, $template [, $includeTags = false ])

 Get a bloc within the given template

 Param string $beginTag Bloc begin tag
 Param string $endingTag Bloc ending tag
 param string $template Template content which contain the bloc to return
 Param bool $includeTags OPTIONAL Whether or not begin and ending tag should be included in result
 Return string Bloc content, including or not the begin and ending tags

=cut

sub getBloc
{
    my ($beginTag, $endingTag, $template, $includeTags) = @_;
    my $reg = "[\t ]*\Q$beginTag\E(.*?)[\t ]*\Q$endingTag\E";
    $includeTags ? $template =~ m/($reg)/gims : $template =~ m/$reg/gims;
    $1 // '';
}

=item replaceBloc($beginTag, $endingTag, $repl, $template [, $preserveTags = false ])

 Replace a bloc within the given template

 Param string $beginTag Bloc begin tag
 Param string $endingTag Bloc ending tag
 Param string $repl Bloc replacement string
 param string $template Template
 Param bool $preserveTags OPTIONAL Whether or not begin and ending tag should be preverved
 Return string Parsed template content

=cut

sub replaceBloc
{
    my ($beginTag, $endingTag, $repl, $template, $preserveTags) = @_;
    my $reg = "([\t ]*\Q$beginTag\E.*?\Q$endingTag\E)";
    $preserveTags ? $template =~ s/$reg/$repl$1/gisr : $template =~ s/$reg/$repl/gisr;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
