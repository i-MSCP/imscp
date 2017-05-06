# i-MSCP Listener::ProFTPd::Auth::Unix listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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
## Enable unix authentication
## See:
##  - http://www.proftpd.org/docs/modules/mod_auth_pam.html
##  - http://www.proftpd.org/docs/modules/mod_auth_unix.html
#

package Listener::ProFTPd::Auth::Unix;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register(
    'afterFtpdBuildConf',
    sub {
        my ($tplContent, $tplName) = @_;

        return 0 unless $tplName eq 'proftpd.conf';
        ${$tplContent} =~ s/(AuthOrder\s+.*)/$1 mod_auth_unix.c/im;
        ${$tplContent} =~ s/(<\/Global>)/\n  PersistentPasswd         off\n$1/im;
        0;
    }
);

1;
__END__
