=head1 NAME

 iMSCP::TemplateParser - i-MSCP Template parser

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by internet Multi Server Control Panel
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
use parent 'Exporter';

our @EXPORT = qw/ process processByRef getBloc getBlocByRef replaceBloc replaceBlocByRef /;

=head1 DESCRIPTION

 The template parser allow to parse pseudo-variables within i-MSCP template
 files. It can parse simple variables or variable tag pairs

=head1 PUBLIC METHODS

=over 4

=item processByRef( \%data, \$template )

 Replace placeholders in the given template

 Param hashref \%data A hash of data where the keys are the pseudo-variable
                      names (composed of a-zA-Z0-9_ characters) and the values,
                      the replacement
 values
 Param scalaref \$template Reference to template content
 Return void

=cut

sub processByRef( $$ )
{
    my ($data, $template) = @_;

    ref $data eq 'HASH' or die( 'Invalid $data parameter. Hash reference expected.' );
    ref $template eq 'SCALAR' or die( 'Invalid $template parameter. Scalar reference expected.' );

    # Process twice to cover cases where there are placeholders defining other placeholder(s)
    ${$template} =~ s#(?<!%)\{([a-zA-Z0-9_]+)\}#$data->{$1} // "{$1}"#ge for 0 .. 1;
}

=item process( \%data, $template )

 Replace placeholders in the given template

 Param hashref \%data A hash of data where the keys are the pseudo-variable
                      names and the values, the replacement values
 Param string $template Template content
 Return string Processed template content

=cut

sub process( $$ )
{
    my ($data, $template) = @_;

    processByRef( $data, \$template );
    $template;
}

=item getBlocByRef( $beginTag, $endingTag, \$template [, $includeTags = false ] )

 Get the first block matching the given begin and ending tags within the given template

 Param string $beginTag Bloc begin tag
 Param string $endingTag Bloc ending tag
 param string \$template Reference to template content
 Param bool $includeTags OPTIONAL Whether or not begin and ending tag should be
                         included in result
 Return string Bloc content, including or not the begin and ending tags

=cut

sub getBlocByRef( $$$;$ )
{
    my ($beginTag, $endingTag, $template, $includeTags) = @_;

    ref $template eq 'SCALAR' or die( 'Invalid $template parameter. Scalar reference expected.' );

    $beginTag = "\Q$beginTag\E" unless ref $beginTag eq 'Regexp';
    $endingTag = "\Q$endingTag\E" unless ref $endingTag eq 'Regexp';
    ( $includeTags ? ${$template} =~ /([\t ]*$beginTag.*?[\t ]*$endingTag)/s : ${$template} =~ /[\t ]*$beginTag(.*?)[\t ]*$endingTag/s ) ? $1 : '';
}

=item getBloc( $beginTag, $endingTag, $template [, $includeTags = false ] )

 Get the first block matching the given begin and ending tags within the given
 template

 Param string $beginTag Bloc begin tag
 Param string $endingTag Bloc ending tag
 param string $template Template content
 Param bool $includeTags OPTIONAL Whether or not begin and ending tag should be
                         included in result
 Return string Bloc content, including or not the begin and ending tags

=cut

sub getBloc( $$$;$ )
{
    my ($beginTag, $endingTag, $template, $includeTags) = @_;

    getBlocByRef( $beginTag, $endingTag, \$template, $includeTags );
}

=item replaceBlocByRef( $beginTag, $endingTag, $repl, $template [, $preserveTags = false ] )

 Replace all blocs matching the given begin and ending tags within the given
 template
 
 Note that when passing Regexp for begin or ending tags and that you want
 preserve tags, you're responsible for adding capturing parentheses.

 Param string|Regexp $beginTag Bloc begin tag
 Param string|Regexp $endingTag Bloc ending tag
 Param string $repl Bloc replacement string
 param scalaref $template Reference to template content
 Param bool $preserveTags OPTIONAL Whether or not begin and ending tags must be
                          preverved
 Return void

=cut

sub replaceBlocByRef( $$$$;$ )
{
    my ($beginTag, $endingTag, $repl, $template, $preserveTags) = @_;

    ref $template eq 'SCALAR' or die( 'Invalid $template parameter. Scalar expected.' );

    if ( $preserveTags ) {
        $beginTag = "(\Q$beginTag\E)" unless ref $beginTag eq 'Regexp';
        $endingTag = "(\Q$endingTag\E)" unless ref $endingTag eq 'Regexp';
        ${$template} =~ s/[\t ]*$beginTag.*?[\t ]*$endingTag/$repl$1$2/gs;
        return;
    }

    $beginTag = "\Q$beginTag\E" unless ref $beginTag eq 'Regexp';
    $endingTag = "\Q$endingTag\E" unless ref $endingTag eq 'Regexp';
    ${$template} =~ s/[\t ]*$beginTag.*?[\t ]*$endingTag/$repl/gs;
}

=item replaceBloc( $beginTag, $endingTag, $repl, $template [, $preserveTags = false ] )

 Replace all blocs matching the given begin and ending tags within the given
 template
 
 Note that when passing Regexp for begin or ending tags and that you want
 preserve tags, you're responsible for adding capturing parentheses.

 Param string|Regexp $beginTag Bloc begin tag
 Param string|Regexp $endingTag Bloc ending tag
 Param string $repl Bloc replacement string
 param string $template Template content
 Param bool $preserveTags OPTIONAL Whether or not begin and ending tags must be
                          preverved
 Return string Template content

=cut

sub replaceBloc( $$$$;$ )
{
    my ($beginTag, $endingTag, $repl, $template, $preserveTags) = @_;

    replaceBlocByRef( $beginTag, $endingTag, $repl, \$template, $preserveTags );
    $template;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
