# i-MSCP Listener::Postfix::PFS listener file
# Copyright (C) 2017-2018 Laurent Declercq <l.declercq@nuxwin.com>
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
## Adds self-generated EDH parameter files for Perfect Forward Secrecy (PFS).
##
## First, you must create the files before activating this listener:
##
##   cd /etc/postfix
##   umask 022
##   openssl dhparam -out dh512.tmp 512 && mv dh512.tmp dh512.pem
##   openssl dhparam -out dh2048.tmp 2048 && mv dh2048.tmp dh2048.pem
##   chmod 644 dh512.pem dh2048.pem
#

package Listener::Postfix::PFS;

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use version;

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 60_postfix_pfs.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'afterPostfixBuildConf',
    sub {
        return 0 unless -f '/etc/postfix/dh2048.pem' && -f '/etc/postfix/dh512.pem';

        Servers::mta->factory()->postconf(
            (
                smtpd_tls_dh1024_param_file => {
                    action => 'replace',
                    values => [ '/etc/postfix/dh2048.pem' ]
                },
                smtpd_tls_dh512_param_file  => {
                    action => 'replace',
                    values => [ '/etc/postfix/dh512.pem' ]
                }
            )
        );
    },
    -99
);

1;
__END__
