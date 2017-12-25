# i-MSCP Listener::Bind9::Localnets listener file
# Copyright (C) 2013-2018 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

#
## Allows to setup Bind9 for local network.
#

package Listener::Bind9::Localnets;

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use version;

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 10_named_localnets.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'afterBind9BuildConf',
    sub {
        my ($tplContent, $tplName) = @_;

        return 0 unless $tplName eq 'named.conf.options';

        ${$tplContent} =~ s/^(\s*allow-(?:recursion|query-cache|transfer)).*$/$1 { localnets; };/gm;
        0;
    }
);

1;
__END__
