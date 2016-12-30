# i-MSCP Listener::Apache2::DualStack listener file
# Copyright (C) 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
use Servers::httpd;
use List::MoreUtils qw(uniq);
use Scalar::Defer;
use version;

#
## Configuration variables
#

# Parameter that allows to add one or many IPs to the Apache2 vhost file of the specified domains
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my %PER_DOMAIN_ADDITIONAL_IPS = (
    '<domain1.tld>' => [ '<IP1>', '<IP2>' ],
    '<domain2.tld>' => [ '<IP1>', '<IP2>' ]
);

# Parameter that allows to add one or many IPs to all Apache2 vhosts files
# Please replace the entries below by your own entries
# Be aware that invalid or unallowed IP addresses are ignored silently
my @ADDITIONAL_IPS = ( '<IP1>', '<IP2>' );

#
## Please, don't edit anything below this line
#

my $IS_APACHE24 = lazy {
    my $APACHE_VERSION = Servers::httpd->factory()->{'config'}->{'HTTPD_VERSION'};
    version->parse( "$APACHE_VERSION" ) >= version->parse( '2.4.0' );
};
my @IPS = ();
my @SSL_IPS = ();

# Listener that is responsible to add additional IPs in Apache2 vhost files
sub addVhostIPs
{
    my ($data, $domainIps) = @_;

    push @{$domainIps}, @ADDITIONAL_IPS;

    if (exists $PER_DOMAIN_ADDITIONAL_IPS{$data->{'DOMAIN_NAME'}}) {
        push @{$domainIps}, @{$PER_DOMAIN_ADDITIONAL_IPS{$data->{'DOMAIN_NAME'}}};
    }

    return 0 if force $IS_APACHE24;

    @IPS = uniq( @IPS, @ADDITIONAL_IPS, @{$PER_DOMAIN_ADDITIONAL_IPS{$data->{'DOMAIN_NAME'}}} );

    if ($data->{'SSL_SUPPORT'}) {
        @SSL_IPS = uniq( @SSL_IPS, @ADDITIONAL_IPS, @{$PER_DOMAIN_ADDITIONAL_IPS{$data->{'DOMAIN_NAME'}}} );
    }

    0;
}

# Listener that is responsible to make the Apache Httpd server (version < 2.4)
# aware of additional IPs
sub addIPList
{
    my $data = $_[1];
    return 0 if force $IS_APACHE24;
    @{$data->{'IPS'}} = uniq( @{$data->{'IPS'}}, @IPS );
    @{$data->{'SSL_IPS'}} = uniq( @{$data->{'SSL_IPS'}}, @SSL_IPS );
    0;
}

my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register( 'onAddHttpdVhostIps', \&addVhostIPs );
$eventManager->register( 'beforeHttpdAddIps', \&addIPList );

1;
__END__
