# i-MSCP Listener::ProFTPd::ServerIdent listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2015-2017 Rene Schuster <mail@reneschuster.de>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## Show custom server identification message
## See See http://www.proftpd.org/docs/directives/linked/config_ref_ServerIdent.html
#

package Listener::ProFTPd::ServerIdent;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser;

#
## Configuration parameters
#

# Server identification message to display when a client connect
my $SERVER_IDENT_MESSAGE = 'i-MSCP FTP server.';

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforeFtpdBuildConf',
    sub {
        my ($tplContent, $tplName) = @_;

        return 0 unless $tplName eq 'proftpd.conf';
        $SERVER_IDENT_MESSAGE =~ s%("|\\)%\\$1%g;
        ${$tplContent} = process(
            {
                SERVER_IDENT_MESSAGE => qq/"$SERVER_IDENT_MESSAGE"/
            },
            ${$tplContent}
        );
        0;
    }
);

1;
__END__
