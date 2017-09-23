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

# Provides IPv4/IPv6 Dual Stack support for bind9.
#
# The intent of this listener was first to provide Dual Stack support, that is,
# making administrator able to add an IPv6 address to default DNS names.
# However, it can also be used to turn default DNS names into round-robin DNS
# names, or even add your own default DNS names.
#
# The listener can act globally or on a per zone basis.

package Listener::Bind9::DualStack;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Net;
use iMSCP::TemplateParser qw/ getBloc replaceBloc /;
use List::MoreUtils qw/ uniq /;
use version;

#
## Configuration variables
#

# List of default DNS names
#
# You can either remove DNS name(s) or add your owns.
#
# Note that DNS names should be relatives. Those are automagically prepended
# with $ORIGIN for which value can be either a domain name or subdomain,
# depending on the context. 
our @DEFAULT_DNS_NAMES = (
    '@', 'ftp', 'mail', 'imap', 'pop', 'pop3', 'relay', 'smtp'
);

# TTL in second for DNS names added by this listener file.
our $DNS_TTL = '3600';

# Zone definitions
#
# Zone definitions allow to add IPs address(es) for default DNS name(s), for
# all or specific DNS zone(s).
#
# Warning: Adding two IP address of the same type (IPv4, IPv6) for the same DNS
# name will turn it into round-robin DNS name.
#
# Be aware that invalid IP addresses are ignored silently.
#
# Please replace the zone definitions below by your owns.
our %ZONE_DEFS = (
    # The wildcard zone definition allows to target all zones.
    # There can only be one wildcard zone definition.
    '*'           => {
        # The wildcard DNS name allows to add IP address(es) for all default
        # DNS names (names listed in @DEFAULT_DNS_NAMES).
        # There can only be one wildcard DNS name per zone definition.
        '*'   => [ 'fd35:4509:90e9:291b::1' ],

        # A named DNS name allows to add IP address(es) for a specific default
        # DNS name (a name listed in @DEFAULT_DNS_NAMES).
        # A named DNS name take precendence over the wildcard name.
        mail  => [ 'fd35:4509:90e9:291b::2', 'fd35:4509:90e9:291b::3' ],

        # An empty named DNS name allows to discard it from processing.
        relay => [
            # DNS name discarded from processing.
        ]
    },

    # A named zone definition allows to target a specific zone. A named zone
    # definition take precedence over the wildcard zone definition.
    'domain1.tld' => {
        # The wildcard DNS name allows to add IP address(es) for all default
        # DNS names (names listed in @DEFAULT_DNS_NAMES).
        # There can only be one wildcard DNS name per zone definition.
        '*'   => [ 'fd35:4509:90e9:291b::4' ],

        # A named DNS name allows to add IP address(es) for a specific default
        # DNS name (a name listed in @DEFAULT_DNS_NAMES).
        # A named DNS name take precendence over the wildcard name.
        mail  => [ 'fd35:4509:90e9:291b::4', 'fd35:4509:90e9:291b::5' ],

        # An empty named DNS name allows to discard it from processing.
        relay => [
            # DNS name discarded from processing.
        ]
    },

    # An empty named zone definition allows to discard a zone from processing.
    'domain.tld3' => {
        # Zone discarded from processing.
    }
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    [ 'afterNamedAddDmnDb', 'afterNamedAddSub' ],
    sub {
        my ($tplContent, $data) = @_;

        my $zone = $ZONE_DEFS{$data->{'REAL_PARENT_DOMAIN_NAME'}
            || $data->{'PARENT_DOMAIN_NAME'}} || $ZONE_DEFS{'*'} || undef;

        return 0 unless defined $zone && %{$zone};

        my $net = iMSCP::Net->getInstance();
        my @names = ();

        local @DEFAULT_DNS_NAMES = @DEFAULT_DNS_NAMES;

        if ( $data->{'REAL_PARENT_DOMAIN_NAME'}
            && $data->{'REAL_PARENT_DOMAIN_NAME'} ne $data->{'PARENT_DOMAIN_NAME'}
        ) {
            # When adding entry for the alternative URLs feature we do have
            # interest only in `@' DNS name
            @DEFAULT_DNS_NAMES = ( '@' ) if grep('@' eq $_, @DEFAULT_DNS_NAMES);
            return 0 unless @DEFAULT_DNS_NAMES;
        }

        for ( @DEFAULT_DNS_NAMES ) {
            my $name = $zone->{$_} || $zone->{'*'} || undef;
            next unless defined $name && ref $name eq 'ARRAY' && @{$name};

            for my $ipAddr( @{$name} ) {
                next unless $net->isValidAddr( $ipAddr );
                push @names, <<"EOT";
$_\t$DNS_TTL\t@{[ $net->getAddrVersion( $ipAddr ) eq 'ipv4' ? 'A' : 'AAAA']}\t@{[ $net->normalizeAddr( $ipAddr ) ]}
EOT
            }
        }

        return 0 unless @names;

        if ( grep($data->{'DOMAIN_TYPE'} eq $_, 'dmn', 'als') ) {
            if ( getBloc( "; dualstack DNS entries BEGIN\n", "; dualstack DNS entries END\n", ${$tplContent} ) ) {
                ${$tplContent} = replaceBloc(
                    "; dualstack DNS entries BEGIN\n",
                    "; dualstack DNS entries END\n",
                    <<"EOT",
; dualstack DNS entries BEGIN
\$ORIGIN $data->{'DOMAIN_NAME'}.
@{[ join( "\n", uniq @names ) ]}
; dualstack DNS entries END
EOT
                    ${$tplContent}
                );

                return 0;
            }

            ${$tplContent} .= <<"EOT",
; dualstack DNS entries BEGIN
\$ORIGIN $data->{'DOMAIN_NAME'}.
@{[ join( "\n", uniq @names ) ]}
; dualstack DNS entries END
EOT
                return 0;
        }

        ${$tplContent} = replaceBloc(
            "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
            "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
            "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n"
                . getBloc(
                "; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
                "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
                ${$tplContent}
            )
                . "\$ORIGIN $data->{'DOMAIN_NAME'}.\n"
                . join( "\n", uniq @names )
                . "\n"
                . "; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
            ${$tplContent}
        );

        0;
    }
) unless version->parse( "$main::imscpConfig{'PluginApi'}" ) < version->parse( '1.5.1' );

1;
__END__
