# i-MSCP Listener::Named::Global::NS listener file
# Copyright (C) 2016-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
# Listener file that allows to set identical NS entries in all zones
# Requires i-MSCP 1.3.8 or newer.
#
# Warning: Don't forget to declare your slave DNS servers to i-MSCP.
# Don't forget also to activate IPv6 support if needed. All this can
# be done by reconfiguring the named service as follow:
#
#   perl /var/www/imscp/engine/setup/imscp-reconfigure -dr named
#

package Listener::Named::Global::NS;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use iMSCP::Net;

#
## Configuration variables
#

# Zone defining name servers
# Warning: For IDN, you must use the Punycode notation.
my $ZONE_NAME = 'zone.tld';

# Name servers
# Replace entries with your own data and delete those which are not needed for
# your use case. The first two entries correspond to this server.
#
# Note that the name from first entry is used as name-server in SOA RR.
#
# Warning: For IDNs, you must use the Punycode notation.
my @NAMESERVERS = (
    [ "ns1.$ZONE_NAME", '<ipv4>' ], # MASTER DNS IP (IPv4 ; this server)
    [ "ns1.$ZONE_NAME", '<ipv6>' ], # MASTER DNS IP (IPv6 ; this server)
    [ 'ns2.name.tld',   '<ipv4>' ], # SLAVE DNS 1 IP (IPv4)
    [ 'ns2.name.tld',   '<ipv6>' ], # SLAVE DNS 1 IP (IPv6)
    [ 'ns3.name.tld',   '<ipv4>' ], # SLAVE DNS 2 IP (IPv4)
    [ 'ns3.name.tld',   '<ipv6>' ]  # SLAVE DNS 2 IP (IPv6)
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforeNamedAddDmnDb',
    sub {
        my ($tpl, $data) = @_;

        # Override default SOA RR (for all zones)
        my $nameserver = (@NAMESERVERS)[0]->[0];
        ${$tpl} =~ s/\Qns1.{DOMAIN_NAME}.\E/$nameserver./gm;
        ${$tpl} =~ s/\Qhostmaster.{DOMAIN_NAME}.\E/hostmaster.$ZONE_NAME./gm;

        # Set NS and glue record entries (for all zones)
        my $nsRecordB = getBloc( "; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", ${$tpl} );
        my $glueRecordB = getBloc( "; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", ${$tpl} );
        my ($nsRecords, $glueRecords) = ('', '');
        my $net = iMSCP::Net->getInstance();

        for my $ipAddrType(qw/ ipv4 ipv6 /) {
            for my $nameserverData(@NAMESERVERS) {
                my ($name, $ipAddr) = @{$nameserverData};
                next unless $net->getAddrVersion( $ipAddr ) eq $ipAddrType;
                $nsRecords .= process(
                    {
                        NS_NAME => $name.'.'
                    },
                    $nsRecordB
                );

                # Glue RR must be set only if $data->{'DOMAIN_NAME'] is equal to $ZONE_NAME
                # Note that if $name is out-of-zone, it will be automatically ignored by the `named-compilezone'
                # command during the dump (expected behavior).
                $glueRecords .= process(
                    {
                        NS_NAME    => $name.'.',
                        NS_IP_TYPE => ($ipAddrType eq 'ipv4') ? 'A' : 'AAAA',
                        NS_IP      => $ipAddr
                    },
                    $glueRecordB
                ) unless $ZONE_NAME ne $data->{'DOMAIN_NAME'};
            }
        }

        ${$tpl} = replaceBloc("; dmn NS RECORD entry BEGIN\n", "; dmn NS RECORD entry ENDING\n", $nsRecords, ${$tpl});
        ${$tpl} = replaceBloc("; dmn NS GLUE RECORD entry BEGIN\n", "; dmn NS GLUE RECORD entry ENDING\n", $glueRecords, ${$tpl});
        0;
    }
);

1;
__END__
