# i-MSCP Listener::Bind9::DualStack listener file
# Copyright (C) 2015 Ninos Ego <me@ninosego.de>
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
## Listener file which provides dual stack support for bind9.
#

package Listener::Bind9::DualStack;

use iMSCP::EventManager;
use iMSCP::TemplateParser;
use List::MoreUtils qw(uniq);

#
## Configuration variables
#

# Parameter which allow to add one or many IPs to the bind9 db_sub file of specified domains
# Please replace the entries below by your own entries
my %perDomainAdditionalIPs = (
	'<domain1.tld>' => [ '<IP1>', '<IP2>' ],
	'<domain2.tld>' => [ '<IP1>', '<IP2>' ]
);

# Parameter which allow to add one or many IPs to all bind9 db files
# Please replace the entries below by your own entries
my @additionalIPs = ( '<IP1>', '<IP2>' );

#
## Please, don't edit anything below this line
#

sub addCustomDNSrecord
{
	my ($tplDbFileContent, $data) = @_;

	# All dns IPs
	my @ipList = @additionalIPs;

	# Per domain IPs
	if(exists $perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}) {
		@ipList = uniq( @ipList, @{$perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}} );
	}

	if(@ipList) {
		# Add custom entries with correct type to the db.tpl
		my $ipMngr = iMSCP::Net->getInstance();
		my @formattedEntries = ();

		push @formattedEntries, '; dualstack DNS entries BEGIN';

		for my $ip(@ipList) {
			
			if($ipMngr->getAddrVersion($ip) eq 'ipv6') {
				push @formattedEntries, '@ IN AAAA ' . $ipMngr->normalizeAddr($ip);
				push @formattedEntries, 'ftp IN	AAAA ' . $ipMngr->normalizeAddr($ip);
			} else {
				push @formattedEntries, '@ IN A ' . $ipMngr->normalizeAddr($ip);
				push @formattedEntries, 'ftp IN	A ' . $ipMngr->normalizeAddr($ip);
			}
		}

		push @formattedEntries, '; dualstack DNS entries END';

		$$tplDbFileContent = replaceBloc(
			"; custom DNS entries BEGIN\n",
			"; custom DNS entries ENDING\n",
			"; custom DNS entries BEGIN\n" .
				getBloc(
					"; custom DNS entries BEGIN\n",
					"; custom DNS entries ENDING\n",
					$$tplDbFileContent
				) .
				join("\n", @formattedEntries) . "\n" .
			"; custom DNS entries ENDING\n",
			$$tplDbFileContent
		);
		undef @formattedEntries;
	}

	0;
}

sub addCustomDNSrecordSub
{
	my ($wrkDbFileContent, $data) = @_;

	# All dns IPs
	my @ipList = @additionalIPs;

	# Per domain IPs
	if(exists $perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}) {
		@ipList = uniq( @ipList, @{$perDomainAdditionalIPs{$data->{'DOMAIN_NAME'}}} );
	}

	if(@ipList) {
		# Add custom entries with correct type to the db_sub.tpl
		my $ipMngr = iMSCP::Net->getInstance();
		my @formattedEntries = ();

		push @formattedEntries, '; dualstack DNS entries BEGIN';

		for my $ip(@ipList) {
			
			if($ipMngr->getAddrVersion($ip) eq 'ipv6') {
				push @formattedEntries, '@	IN	AAAA	' . $ipMngr->normalizeAddr($ip);
				push @formattedEntries, 'ftp IN	AAAA	' . $ipMngr->normalizeAddr($ip);
			} else {
				push @formattedEntries, '@ IN	A	' . $ipMngr->normalizeAddr($ip);
				push @formattedEntries, 'ftp IN	A	' . $ipMngr->normalizeAddr($ip);
			}
		}

		push @formattedEntries, '; dualstack DNS entries END';

		$$wrkDbFileContent = replaceBloc(
			"; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n",
			"; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
			"; sub [$data->{'DOMAIN_NAME'}] entry BEGIN\n" .
				getBloc(
					"; sub [$subdomainName] entry BEGIN\n",
					"; sub [$subdomainName] entry ENDING\n",
					$$wrkDbFileContent
				) .
				join("\n", @formattedEntries) . "\n" .
			"; sub [$data->{'DOMAIN_NAME'}] entry ENDING\n",
			$$wrkDbFileContent
		);
		undef @formattedEntries;
	}

	0;
}

my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register('afterNamedAddDmnDb', \&addCustomDNSrecord);
$eventManager->register('afterNamedAddSub', \&addCustomDNSrecordSub);

1;
__END__
