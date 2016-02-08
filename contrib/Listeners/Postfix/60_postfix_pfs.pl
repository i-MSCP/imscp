# i-MSCP Listener::Postfix::PFS listener file
# Copyright (C) 2015-2016 Rene Schuster <mail@reneschuster.de>
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
## Listener file to add the self generated EDH parameter files for Perfect 
## Forward Secrecy (PFS). First create the files before activating this listener:
##
##   cd /etc/postfix
##   umask 022
##   openssl dhparam -out dh512.tmp 512 && mv dh512.tmp dh512.pem
##   openssl dhparam -out dh2048.tmp 2048 && mv dh2048.tmp dh2048.pem
##   chmod 644 dh512.pem dh2048.pem
#

package Listener::Postfix::PFS;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register('afterMtaBuildMainCfFile', sub {
	my $content = shift;

	my $cfgSnippet = <<EOF;
# BEGIN Listener::Postfix::PFS
smtpd_tls_dh1024_param_file = /etc/postfix/dh2048.pem
smtpd_tls_dh512_param_file = /etc/postfix/dh512.pem
# END Listener::Postfix::PFS
EOF

	$$content =~ s/^(# TLS parameters\n)/$1$cfgSnippet/m;

	0;
});

1;
__END__