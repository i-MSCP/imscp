# i-MSCP Listener::Dovecot::Service::Login listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2016-2017 Sven Jantzen <info@svenjantzen.de>
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
## Allows to modify default service-login configuration options.
## This listener file requires dovecot version 2.1.0 or newer.
#

package Listener::Dovecot::Service::Login;

use iMSCP::EventManager;

#
## Configuration parameters
#

# Service ports
# Note: Setting a port to 0 will close it
my $pop3Port = 110;
my $pop3sPort = 995;
my $imapPort = 143;
my $imapsPort = 993;

# Space separated list of IP addresses/hostnames to listen on.
# For instance:
#	- with 'localhost' as value the service-login will listen on localhost only
#	- with '*' as value, the service-login will listen on all IPv4 addresses
#	- with '::' as value, the servicel-login will listen on all IPv6 addresses
#	- with '*, ::' as value, the service-login will listen on all IPv4/IPv6 addresses
my $imapListenAddr = '* ::';
my $imapsListenAddr = '* ::';
my $pop3ListenAddr = '* ::';
my $pop3sListenAddr = '* ::';

# Number of connections to handle before starting a new process. Typically
# the only useful values are 0 (unlimited) or 1. 1 is more secure, but 0
# is faster.
my $imapServiceCount = 0;
my $popServiceCount = 0;

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforePoBuildConf',
    sub {
        my ($cfgTpl, $tplName) = @_;

        return 0 unless $tplName eq 'dovecot.conf';

        ${$cfgTpl} .= <<"EOF";

# Begin Listener::Dovecot::Service::Login
service imap-login {
	inet_listener imap {
		port = $imapPort
		address = $imapListenAddr
	}

	inet_listener imaps {
		port = $imapsPort
		address = $imapsListenAddr
		ssl = yes
	}

	service_count = $imapServiceCount
}

service pop3-login {
	inet_listener pop3 {
		port = $pop3Port
		address = $pop3ListenAddr
	}

	inet_listener pop3s {
		port = $pop3sPort
		address = $pop3sListenAddr
		ssl = yes
	}

	service_count = $popServiceCount
}
# Ending Listener::Dovecot::Service::Login
EOF
        0;
    }
);

1;
__END__
