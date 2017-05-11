# i-MSCP Listener::Named::OverrideDefaultRecords listener file
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
# Listener that allows overriding of default DNS records with custom DNS records
# - @   IN {IP_TYPE} {DOMAIN_IP}
# - www IN CNAME     @

package Listener::Named::OverrideDefaultRecords;

use strict;
use warnings;
use iMSCP::Net;
use iMSCP::EventManager;

# Listener that is responsible to replace following default DNS records:
# - @   IN {IP_TYPE} {DOMAIN_IP}
# - www IN CNAME     @
iMSCP::EventManager->getInstance()->register(
    'beforeNamedAddCustomDNS',
    sub {
        my ($wrkDbFileContent, $data) = @_;

        return 0 unless @{$data->{'DNS_RECORDS'}};

        my $domainIP = iMSCP::Net->getInstance( )->isRoutableAddr( $data->{'DOMAIN_IP'} )
            ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};

        for(@{$data->{'DNS_RECORDS'}}) {
            my ($name, $class, $type, $rdata) = @{$_};
            if ($name =~ /^\Q$data->{'DOMAIN_NAME'}.\E(?:\s+\d+)?/
                && $class eq 'IN'
                && ($type eq 'A' || $type eq 'AAAA')
                && $rdata ne $domainIP
            ) {
                # Remove default A or AAAA record for $data->{'DOMAIN_NAME'}
                ${$wrkDbFileContent} =~ s/
                    ^(?:\@|\Q$data->{'DOMAIN_NAME'}.\E)(?:\s+\d+)?\s+IN\s+$type\s+\Q$domainIP\E\n
                    //gmx;

                next;
            };

            if ($name =~ /^www\Q.$data->{'DOMAIN_NAME'}.\E(?:\s+\d+)?/
                && $class eq 'IN'
                && $type eq 'CNAME'
                && $rdata ne $data->{'DOMAIN_NAME'}
            ) {
                # Delete default www CNAME record for $data->{'DOMAIN_NAME'}
                ${$wrkDbFileContent} =~ s/
                    ^www(?:\Q.$data->{'DOMAIN_NAME'}.\E)?\s+IN\s+CNAME\s+(?:\@|\Q$data->{'DOMAIN_NAME'}.\E)\n
                    //gmx;
            }
        }

        0;
    }
);

# Listener that is responsible to re-add the default DNS records when needed.
# i-MSCP Bind9 server impl. will not do it unless the domain is being fully
# reconfigured
iMSCP::EventManager->getInstance()->register(
    'afterNamedAddCustomDNS',
    sub {
        my ($wrkDbFileContent, $data) = @_;

        my $net = iMSCP::Net->getInstance();
        my $domainIP = $net->isRoutableAddr( $data->{'DOMAIN_IP'} )
            ? $data->{'DOMAIN_IP'} : $data->{'BASE_SERVER_PUBLIC_IP'};
        my $rrType = $net->getAddrVersion( $domainIP ) eq 'ipv4' ? 'A' : 'AAAA';

        # Re-add default A or AAAA record for $data->{'DOMAIN_NAME'}
        if (${$wrkDbFileContent} !~ /^\Q$data->{'DOMAIN_NAME'}.\E(?:\s+\d+)?\s+IN\s+$rrType\s+/m) {
            ${$wrkDbFileContent} .= "$data->{'DOMAIN_NAME'}.\t\tIN\t$rrType\t$domainIP\n";
        }

        # Re-add default www CNAME record for $data->{'DOMAIN_NAME'}
        if (${$wrkDbFileContent} !~ /^www\Q.$data->{'DOMAIN_NAME'}.\E(?:\s+\d+)?\s+IN\s+CNAME\s+/m) {
            ${$wrkDbFileContent} .= "www.$data->{'DOMAIN_NAME'}.\t\tIN\tCNAME\t$data->{'DOMAIN_NAME'}.\n";
        }

        0;
    }
);

1;
__END__
