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

our $VERSION = '1.0.2';

use strict;
use warnings;
use iMSCP::EventManager;
use version;

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 10_proftpd_serverident.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'afterProftpdBuildConf',
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
