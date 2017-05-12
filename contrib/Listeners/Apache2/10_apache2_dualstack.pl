# i-MSCP Listener::Apache2::DualStack listener file
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
## Provides dual stack support for Apache2.
#

package Listener::Apache2::DualStack;

use iMSCP::EventManager;

#
## Configuration variables
#

# Parameter that allows to add one or many IPs to all Apache2 vhosts files
# Please replace the entries below by your own entries
my @GLOBAL_IPS = (
    'IP1',
    'IP2'
);

# Parameter that allows to add one or many IPs to the Apache2 vhost file of the specified domains
# Please replace the entries below by your own entries
my %PER_DMN_IPS = (
    'domain1.tld' => [ 'IP1', 'IP2' ],
    'domain2.tld' => [ 'IP1', 'IP2' ]
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'onAddHttpdVhostIps',
    sub {
        my ($data, $domainIps) = @_;

        push @{$domainIps}, @GLOBAL_IPS if @GLOBAL_IPS;
        push @{$domainIps}, @{$PER_DMN_IPS{$data->{'DOMAIN_NAME'}}} if $PER_DMN_IPS{$data->{'DOMAIN_NAME'}};
        0;
    }
);

1;
__END__
