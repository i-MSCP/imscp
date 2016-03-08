# i-MSCP Listener::Apache2::DualStack listener file
# Copyright (C) 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
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
## Provides dual stack support for Apache2.
#

package Listener::Apache2::DualStack;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::Net;
use List::MoreUtils qw(uniq);

#
## Configuration variables
#

# Port to use for http
my $httpPort = 80;

# Port to use for https
my $httpsPort = 443;

# Parameter that allows to add one or many IPs to the Apache2 vhost file of the specified domains
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my %perDomainAdditionalIPs = (
	'<domain1.tld>' => [ '<IP1>', '<IP2>' ],
	'<domain2.tld>' => [ '<IP1>', '<IP2>' ]
);

# Parameter that allows to add one or many IPs to all Apache2 vhosts files
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my @additionalIPs = ( '<IP1>', '<IP2>' );

#
## Please, don't edit anything below this line
#

my @IPS = ();
my @SSL_IPS = ();

# Listener responsible to add additional IPs in Apache2 vhost files, once they was built by i-MSCP
sub addIPs
{
	my ($cfgTpl, $tplName, $data) = @_;

	return 0 unless exists $data->{'DOMAIN_NAME'} && $tplName =~ /^domain(?:_(?:disabled|redirect))?(_ssl)?\.tpl$/;

	my $sslVhost = defined $1;
	my $port = $sslVhost ? $httpsPort : $httpPort;

	my $net = iMSCP::Net->getInstance();

	# All vhost IPs and per domain IPS
	my @ipList = uniq map $net->normalizeAddr($_), grep {
		my $__ = $_;
		$net->isValidAddr($__) && grep($_ eq $net->getAddrType($__), ( 'PRIVATE', 'UNIQUE-LOCAL-UNICAST', 'PUBLIC', 'GLOBAL-UNICAST' ))
	} @additionalIPs, $perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}} ? @{$perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}} : ();

	return 0 unless @ipList;

	my @formattedIPs = ();
	for my $ip(@ipList) {
		if($net->getAddrVersion($ip) eq 'ipv6') {
			push @formattedIPs, "[$ip]:$port";
		} else {
			push @formattedIPs, "$ip:$port";
		}
	}

	$$cfgTpl =~ s/(<VirtualHost.*?)>/$1 @formattedIPs>/;
	undef @formattedIPs;

	unless($sslVhost) {
		@IPS = uniq(@IPS, @ipList);
	} else {
		@SSL_IPS = uniq(@SSL_IPS, @ipList);
	}

	0;
}

# Listener responsible to make the Httpd server implementation aware of additional IPs
sub addIPList
{
	my $data = $_[1];
	@{$data->{'IPS'}} = uniq( @{$data->{'IPS'}}, @IPS );
	@{$data->{'SSL_IPS'}} = uniq( @{$data->{'SSL_IPS'}}, @SSL_IPS );
	0;
}

my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register('afterHttpdBuildConfFile', \&addIPs);
$eventManager->register('beforeHttpdAddIps', \&addIPList);

1;
__END__
