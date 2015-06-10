# i-MSCP Listener::Named::Tuning2 listener file
# Copyright (C) 2015 Arthur Mayer <mayer.arthur@gmail.com>
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
## i-MSCP listener file modifies the zone files, removes default nameservers and adds custom out-of-zone nameservers
#

package Listener::Named::Tuning2;

use iMSCP::EventManager;
use iMSCP::TemplateParser;

sub replaceDefaultNameservers
{
        # Define here your out-of-zone nameservers
        my @nameservers = ("ns1.mycompany.tld", "ns2.mycompany.tld", "ns3.mycompany.tld");

        my ($wrkFile, $data) = @_;

        # remove default nameservers (IN A and IN NS section)
        $$wrkFile =~ s/ns[0-9]\tIN\tA\t([0-9]{1,3}[\.]){3}[0-9]{1,3}\n//g;
        $$wrkFile =~ s/\@\t\tIN\tNS\tns[0-9]\n//g;

        # add out-of-zone nameservers
        foreach my $nameserver(@nameservers) {
                $$wrkFile .= "@         IN      NS      $nameserver.\n";
        }

        # fix SOA record according new nameservers
        $$wrkFile =~ s/IN\tSOA\tns1\.$data->{'DOMAIN_NAME'}\. postmaster\.$data->{'DOMAIN_NAME'}\./IN\tSOA\t$nameservers[0]\. hostmaster\.$data->{'DOMAIN_NAME'}\./g;

        0;
}

my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register('afterNamedAddDmnDb', \&replaceDefaultNameservers);

1;
__END__