# i-MSCP Listener::ProFTP::Tuning listener file
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
## Removes the ServerIdent information, and enforces TLS connection
#

package Listener::ProFTP::Tuning;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register(
    'afterFtpdBuildConf',
    sub {
        my ($tplContent, $tplName) = @_;

        return 0 unless $tplName eq 'proftpd.conf';

        # Disable the message displayed on connect
        unless ($$tplContent =~ /^ServerIdent/m) {
            $$tplContent =~ s/^(ServerType.*)/$1\nServerIdent                off/m;
        } else {
            $$tplContent =~ s/^ServerIdent.*/ServerIdent                off/m;
        }

        # Enforce TLS connection
        $$tplContent =~ s/^(\s+TLSRequired.*)off$/$1on/m;
        0;
    }
);

1;
__END__
