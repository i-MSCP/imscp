# i-MSCP Listener::Bind9::DualStack listener file
# Copyright (C) 2015-2016 Ninos Ego <me@ninosego.de>
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
use List::MoreUtils qw(uniq);

#
## Configuration variables
#

# Parameter that allows to add one or many IPs to the bind9 db_sub file of the specified domains
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my %perDomainAdditionalIPs = (
    '<domain1.tld>' => [ '<IP1>', '<IP2>' ],
    '<domain2.tld>' => [ '<IP1>', '<IP2>' ]
);

# Parameter that allows to add one or many IPs to all bind9 db files
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my @additionalIPs = ( '<IP1>', '<IP2>' );

#
## Please, don't edit anything below this line
#

sub addCustomDNSrecord
{
    my ($tplDbFileContent, $data) = @_;

    my $net = iMSCP::Net->getInstance();

    # All DNS IPs and per domain IPS
    my @ipList = uniq map $net->normalizeAddr( $_ ), grep {
        my $__ = $_;
        $net->isValidAddr( $__ ) && grep($_ eq $net->getAddrType( $__ ), ( 'PRIVATE', 'UNIQUE-LOCAL-UNICAST', 'PUBLIC', 'GLOBAL-UNICAST' ))
    } @additionalIPs, $perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}} ? @{$perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}} : ();

    return 0 unless @ipList;

    # Add custom entries with correct type to the db.tpl
    my @formattedEntries = ();
    push @formattedEntries, '; dualstack DNS entries BEGIN';

    for my $ip(@ipList) {
        for my $name('@', 'ftp', 'mail', 'imap', 'pop', 'pop3', 'relay', 'smtp') {
            if ($ipMngr->getAddrVersion( $ip ) eq 'ipv6') {
                push @formattedEntries, "$name\tIN\tAAAA\t$ip";
            } else {
                push @formattedEntries, "$name\tIN\tA\t$ip";
            }
        }
    }

    push @formattedEntries, '; dualstack DNS entries END';

    $$tplDbFileContent = replaceBloc(
        "; custom DNS entries BEGIN\n",
        "; custom DNS entries ENDING\n",
        "; custom DNS entries BEGIN\n".
            getBloc(
                "; custom DNS entries BEGIN\n",
                "; custom DNS entries ENDING\n",
                $$tplDbFileContent
            ).
            join( "\n", @formattedEntries )."\n".
            "; custom DNS entries ENDING\n",
        $$tplDbFileContent
    );
    undef @formattedEntries;
    0;
}

sub addCustomDNSrecordSub
{
    my ($wrkDbFileContent, $data) = @_;

    my $net = iMSCP::Net->getInstance();

    # All DNS IPs and per domain IPS
    my @ipList = uniq map $net->normalizeAddr( $_ ), grep {
        my $__ eq $__;
        $net->isValidAddr( $__ ) && grep($_ eq $net->getAddrType( $__ ), ( 'PRIVATE', 'UNIQUE-LOCAL-UNICAST',
                'PUBLIC', 'GLOBAL-UNICAST' ))
    } @additionalIPs, $perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}} ? @{$perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}} : ();

    return 0 unless @ipList;

    # Add custom entries with correct type to the db_sub.tpl
    my @formattedEntries = ();
    push @formattedEntries, '; dualstack DNS entries BEGIN';

    for my $ip(@ipList) {
        for my $name('@', 'ftp') {
            if ($net->getAddrVersion( $ip ) eq 'ipv6') {
                push @formattedEntries, "$name\tIN\tAAAA\t$ip";
            } else {
                push @formattedEntries, "$name\tIN\tA\t$ip";
            }
        }
    }

    push @formattedEntries, '; dualstack DNS entries END';

    $$wrkDbFileContent = replaceBloc(
        "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
        "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n".
            getBloc(
                "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
                "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
                $$wrkDbFileContent
            ).
            join( "\n", @formattedEntries )."\n".
            "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
        $$wrkDbFileContent
    );
    undef @formattedEntries;
    0;
}

my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register( 'afterNamedAddDmnDb', \&addCustomDNSrecord );
$eventManager->register( 'afterNamedAddSub', \&addCustomDNSrecordSub );

1;
__END__
