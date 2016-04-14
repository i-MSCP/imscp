# i-MSCP Listener::Named::Tuning2 listener file
# Copyright (C) 2015-2016 Arthur Mayer <mayer.arthur@gmail.com>
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
## Overwrites the default nameservers with out-of-zone nameservers.
#

package Listener::Named::Tuning2;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser;

#
## Configuration parameters
#

# Define here your out-of-zone nameservers
my @nameservers = (
    'ns1.mycompany.tld',
    'ns2.mycompany.tld',
    'ns3.mycompany.tld'
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'afterNamedAddDmnDb',
    sub {
        my ($wrkFile, $data) = @_;

        # Remove default nameservers (IN A and IN NS section)
        $$wrkFile =~ s/ns[0-9]\tIN\tA\t([0-9]{1,3}[\.]){3}[0-9]{1,3}\n//g;
        $$wrkFile =~ s/\@\t\tIN\tNS\tns[0-9]\n//g;

        # Add out-of-zone nameservers
        for my $nameserver(@nameservers) {
            $$wrkFile .= "@         IN      NS      $nameserver.\n";
        }

        # Fix SOA record according new nameservers
        $$wrkFile =~ s/IN\tSOA\tns1\.$data->{'DOMAIN_NAME'}\. hostmaster\.$data->{'DOMAIN_NAME'}\./IN\tSOA\t$nameservers[0]\. hostmaster\.$data->{'DOMAIN_NAME'}\./g;
        0;
    }
);

1;
__END__
