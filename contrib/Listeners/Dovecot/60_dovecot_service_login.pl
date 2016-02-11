# i-MSCP Listener::Dovecot::Service::Login listener file
# Copyright (C) 2015-2016 Sven Jantzen <info@svenjantzen.de>
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
## Set dovecot service login optins for imap, imaps, pop3 and pop3s.
## Listener require dovecot version 2.1.0 or newer
#

package Listener::Dovecot::Service::Login;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use version;
use iMSCP::EventManager;


#Set Options
#########################
#Port
#set port to 0 means port is close

#Host
#Space separated list of IP addresses / host names to listen on. "*" means all IPv4 addresses, "::" means all IPv6 addresses.
#eg "* ::" activate service-login for localhost all IPv4 ans all IPv6 address
#eg "localhost" activate service-login only localhost

##########pop3###########
my $pop3_port = 110;
my $pop3_host = "localhost";
##########pop3s##########
my $pop3s_port = 995;
my $pop3s_host = "* ::";
##########imap###########
my $imap_port = 143;
my $imap_host = "localhost";
##########imaps##########
my $imaps_port = 993;
my $imaps_host = "* ::";
my $service_count = 1;
#########################


#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('beforePoBuildConf', sub {

	execute("dovecot --version", \my $stdout, \my $stderr);
	
	if( version->new($stdout) < version->new('2.1.0') ) {
		error("This Listener require dovecot version 2.1.x or newer. - Your version is: $stdout");
		return 1;
	}
	else {

		my ($cfgTpl, $tplName) = @_;

		return 0 unless index($tplName, 'dovecot.conf') != -1;

		$$cfgTpl .= <<EOF;

# Begin Listener::Dovecot::Service::Login
service imap-login {
	inet_listener imap {
		port = $imap_port
		address = $imap_host
	}
	
	inet_listener imaps {
		port = $imaps_port
		address = $imaps_host
		ssl = yes
	}
	
	service_count = $service_count
}
	
service pop3-login {
	inet_listener pop3 {
		port = $pop3_port
		address = $pop3_host
	}
		
	inet_listener pop3s {
		port = $pop3s_port
		address = $pop3s_host
		ssl = yes
	}
}
# Ending Listener::Dovecot::Service::Login
EOF

		0;
	}
});

1;
__END__