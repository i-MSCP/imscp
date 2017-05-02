# i-MSCP Listener::Bind9::DualStack listener file
# Copyright (C) 2010-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
## Provides dual stack support for bind9.
#

package Listener::Bind9::DualStack;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use iMSCP::Net;
use List::MoreUtils qw/ uniq /;

#
## Configuration variables
#

# Parameter that allows to add one or many IPs to the bind9 db_sub file of the specified domains
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my %perDomainAdditionalIPs = (
    'domain1.tld' => [ 'IP1', 'IP2' ],
    'domain2.tld' => [ 'IP1', 'IP2' ]
);

# Parameter that allows to add one or many IPs to all bind9 db files
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my @additionalIPs = ( 'IP1', 'IP2' );

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance( )->register(
    'afterNamedAddDmnDb',
    sub {
        my ($tplDbFileContent, $data) = @_;

        my $net = iMSCP::Net->getInstance( );
        my @ipList = uniq(
            map $net->normalizeAddr( $_ ), grep {

                $net->getAddrType( $_ ) =~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)$/
            } (
                @additionalIPs,
                ($perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}
                        && ref $perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}} eq 'ARRAY'
                    ? @{$perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}}
                    : ()
                )
            )
        );

        return 0 unless @ipList;

        my @formattedEntries = ( );

        for my $ip(@ipList) {
            for my $name('@', 'ftp', 'mail', 'imap', 'pop', 'pop3', 'relay', 'smtp') {
                push @formattedEntries, $net->getAddrVersion( $ip ) eq 'ipv6'
                        ? "$name\tIN\tAAAA\t$ip\n" : "$name\tIN\tA\t$ip\n";
            }
        }

        ${$tplDbFileContent} = replaceBloc(
            "; custom DNS entries BEGIN\n",
            "; custom DNS entries ENDING\n",
            "; dualstack DNS entries BEGIN\n"
                .join( '', @formattedEntries )
                ."; dualstack DNS entries END\n",
            ${$tplDbFileContent},
            'PreserveTags'
        );

        0;
    }
);

iMSCP::EventManager->getInstance( )->register(
    'afterNamedAddSub',
    sub {
        my ($wrkDbFileContent, $data) = @_;

        my $net = iMSCP::Net->getInstance( );
        my @ipList = uniq(
            map $net->normalizeAddr( $_ ), grep {
                $net->getAddrType( $_ ) =~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)$/
            } (
                @additionalIPs,
                ($perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}
                        && ref $perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}} eq 'ARRAY'
                    ? @{$perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}}
                    : ()
                )
            )
        );

        return 0 unless @ipList;

        my @formattedEntries = ( );

        for my $ip(@ipList) {
            for my $name('@', 'ftp', 'mail', 'imap', 'pop', 'pop3', 'relay', 'smtp') {
                push @formattedEntries, $net->getAddrVersion( $ip ) eq 'ipv6'
                        ? "$name\tIN\tAAAA\t$ip\n" : "$name\tIN\tA\t$ip\n";
            }
        }

        ${$wrkDbFileContent} = replaceBloc(
            "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
            "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
            "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n"
                .getBloc(
                "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
                "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
                ${$wrkDbFileContent}
            )
                ."; dualstack DNS entries BEGIN\n"
                .join( '', @formattedEntries )
                ."; dualstack DNS entries END\n"
                ."; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
            ${$wrkDbFileContent}
        );

        0;
    }
);

1;
__END__
