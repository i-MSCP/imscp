# i-MSCP Listener::Named::OverrideNsRecords listener file
# Copyright (C) 2015-2016 Arthur Mayer <mayer.arthur@gmail.com>
# Copyright (C) 2016 Laurent Declercq <l.declercq@nuxwin.com>
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
## Listener that allows overriding of default NS DNS resource records.
#

package Listener::Named::OverrideDefaultNsRecords;

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
## Please don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'afterNamedAddDmnDb',
    sub {
        my ($wrkFile, $data) = @_;

        # Remove default nameservers records
        $$wrkFile =~ s/^(?:\@(?:\s+\d+)?\s+IN\s+NS|ns[0-9]\s+IN)\s+[^\n]+\n//gm;
        # Update SOA record
        $$wrkFile =~ s/
            ^\@\s+IN\s+SOA\s+ns1\Q.$data->{'DOMAIN_NAME'}.\E\s+hostmaster\Q.$data->{'DOMAIN_NAME'}.\E
            /\@\tIN\tSOA\t$nameservers[0]\. hostmaster\.$nameservers[0]\./gmx;
        # Add out-of-zone nameservers
        $$wrkFile .= "$data->{'DOMAIN_NAME'}.\tIN\tNS\t$_.\n" for @nameservers;
        0;
    }
);

1;
__END__
