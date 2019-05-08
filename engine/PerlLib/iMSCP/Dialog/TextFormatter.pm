=head1 NAME

 iMSCP::Dialog::TextFormatter - Text formatter

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

package iMSCP::Dialog::TextFormatter;

use strict;
use warnings;

BEGIN {
    {
        no warnings;
        eval q{ use Text::WrapI18N; use Text::CharWidth };
    }

    if ( !$@ && Text::CharWidth::mblen( 'a' ) == 1 ) {
        *wrap = *Text::WrapI18N::wrap;
        *columns = *Text::WrapI18N::columns;
        *width = *Text::CharWidth::mbswidth;
    } else {
        require Text::Wrap;
        require Text::Tabs;
        sub _wrap { Text::Tabs::expand( Text::Wrap::wrap( @_ )); }
        *wrap = *_wrap;
        *columns = *Text::Wrap::columns;
        sub _dumbwidth { length $_[0]; }
        *width = *_dumbwidth;
    }
}

use Exporter 'import';
our @EXPORT_OK = qw/ wrap $columns width /;

=head1 DESCRIPTION

 This module provides facilities to format text

 iMSCP::Dialog::Encoding::wrap is a word-wrapping function, with the same
 interface as the one in Text::Wrap (except it doesn't gratuitously unexpand
 tabs). If Text::WrapI18N is available, it will be used for proper wrapping of
 multibyte encodings, combining and fullwidth characters, and languages that
 do not use whitespace between words.

 $iMSCP::Dialog::Encoding::columns is used to set the number of columns text is
 wrapped to by iMSCP::Dialog::Encoding::wrap

 iMSCP::Dialog::Encoding::width returns the number of columns required to
 display the given string. If available, Text::CharWidth is used to determine
 the width, to support combining and fullwidth characters.

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
