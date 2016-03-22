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
## Removes the ServerIdent information, and enforces TLS connections for non-local networks.
#

package Listener::ProFTP::Tuning;

use strict;
use warnings;
use iMSCP::EventManager;

#
## Configuration parameters
#

# Configure the list of local networks to allow for non TLS connection
# For instance: my @localNetworks = ('127.0.0.1', '192.168.1.1', '172.16.12.0/24');
my @localNetworks = ('127.0.0.1', '::1');

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('afterFtpdBuildConf', sub {
	my ($tplContent, $tplName) = @_;

	return 0 unless $tplName eq 'proftpd.conf';

	my $cfgNetworks;
	for my $network(@localNetworks) {
		$cfgNetworks .= "\n  From $network";
	}

	# Disable the message displayed on connect
	unless($$tplContent =~ /^ServerIdent/m) {
		$$tplContent =~ s/^(ServerType.*)/$1\nServerIdent                off/m;
	} else {
		$$tplContent =~ s/^ServerIdent.*/ServerIdent                off/m;
	}

	# Enforce TLS connections for non-local networks
	$$tplContent =~ s/^(<IfModule mod_tls\.c>$)/$1\n  <IfClass !local>/m;
	$$tplContent =~ s/^(\s+TLSRequired.*)off$/$1on/m;
	$$tplContent =~ s/^(\s+TLS.*$)/  $1/gm;
	$$tplContent =~ s/^(\s+TLS.*\n)(<\/IfModule>)/$1  <\/IfClass>\n$2/gm;

	# Insert class local
	$$tplContent .= "\n<Class local>$cfgNetworks\n</Class>";
	0;
});

1;
__END__
