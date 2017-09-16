#!/bin/sh
# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

export DEBIAN_FRONTEND=noninteractive
export LANG=C.UTF-8

# Install pre-required packages
apt-get --assume-yes --no-install-recommends install locales-all \
ca-certificates libnet-ip-perl libdata-validate-ip-perl perl

# Create i-MSCP preseed file
head -n -1 /vagrant/docs/imscp_preseed.pl > /tmp/imscp_preseed.pl
cat <<'EOT' >> /tmp/imscp_preseed.pl
use iMSCP::Debug qw/ output error /;
use iMSCP::Net;

my $net = iMSCP::Net->getInstance();
my @serverIPs = sort grep {
    $net->getAddrDevice( $_ ) eq 'eth1'
        && $net->getAddrVersion( $_ ) ne 'ipv6'
        && $net->getAddrType( $_ ) =~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)$/o;
} $net->getAddresses();
unless ( @serverIPs ) {
    error( "Couldn't get list of server IP addresses. At least one IP address must be configured." );
    exit 1;
}

$main::questions{'BASE_SERVER_IP'} = $main::questions{'BASE_SERVER_PUBLIC_IP'} = $serverIPs[0];

output("VM IP address has been set to: $main::questions{'BASE_SERVER_IP'}", 'info');

1;
EOT

# Install i-MSCP
perl /vagrant/imscp-autoinstall --debug --noprompt --verbose --preseed /tmp/imscp_preseed.pl
