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

set -e

export DEBIAN_FRONTEND=noninteractive
export LANG=C.UTF-8

# Install pre-required packages
apt-get --assume-yes --no-install-recommends install ca-certificates \
libnet-ip-perl libdata-validate-ip-perl perl

# Create i-MSCP preseed file
if [ -f /vagrant/preseed.pl ]; then
    head -n -1 /vagrant/preseed.pl > /tmp/preseed.pl
    cat <<'EOT' >> /tmp/preseed.pl

unless($main::questions{'BASE_SERVER_IP'} eq 'None') {
    require iMSCP::Net;
    my $net = iMSCP::Net->getInstance();
    my @serverIPs = reverse sort grep {
        $net->getAddrVersion( $_ ) ne 'ipv6' && $net->getAddrType( $_ ) =~ /^(?:PRIVATE|PUBLIC)$/o;
    } $net->getAddresses();

    @serverIPs or die( "Couldn't get list of server IP addresses. At least one IP address must be configured." );
    $main::questions{'BASE_SERVER_IP'} = $serverIPs[0];
}

1;
EOT
else
 echo "The i-MSCP preseed.pl file has not been found. Please create it first."
 exit 1
fi

# Run i-MSCP installer using preconfiguration file
perl /usr/local/src/imscp/imscp-autoinstall --debug --verbose --preseed /tmp/preseed.pl
