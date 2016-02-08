# i-MSCP Listener::ProFTP::Tuning listener file
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
## Listener file that removes the ServerIdent information, and forces a TLS 
## connection for non local networks.
#

package Listener::ProFTP::Tuning;

use strict;
use warnings;
use iMSCP::EventManager;

# Configure the list of local networks to allow non TLS connection 
# my @localNetworks = ( '127.0.0.1', '192.168.1.1', '172.16.12.0/24' );
my @localNetworks = ('127.0.0.1');

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('afterFtpdBuildConf', sub {
	my ($tplContent, $tplName) = @_;

	my $cfgSnippet = <<EOF;
  # Don't require FTPS from local clients
  <IfClass local>
    TLSRequired            off
  </IfClass>
  # Require FTPS from remote/non-local clients
  <IfClass !local>
    TLSRequired            on
  </IfClass>
EOF

	my $cfgNetworks;
	for my $networks(@localNetworks) {
		$cfgNetworks .= "\n  From $networks";
	}

	if ($tplName eq 'proftpd.conf') {
		# disable the message displayed on connect
		$$tplContent =~ s/^(ServerType.*)/$1\nServerIdent                off/m;

		# remove TLSRequired
		$$tplContent =~ s/^\s+TLSRequired.*\n//m;

		# insert $cfgSnippet
		$$tplContent =~ s/^(<IfModule mod_tls\.c>$)/$1\n$cfgSnippet/m;

		# insert class local
		$$tplContent .= "\n<Class local>$cfgNetworks\n</Class>";
	}

	0;
});

1;
__END__
