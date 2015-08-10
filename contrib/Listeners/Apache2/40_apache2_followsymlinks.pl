# i-MSCP Listener::Apache2::FollowSymlinks
# Copyright (C) 2015 Christoph Keßler <info@it-kessler.de>
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
## i-MSCP listener file to edit the Symlinks OPtions in domain config files
#

package Listener::Apache2::FollowSymlinks;

use iMSCP::EventManager;

## Enter your domains where to change the Symlinks OPtions
## my $searchDomains = "example.com,sub.example.com,example2.com";

my $searchDomains = "";


sub changeSymlinks
{
	my ($cfgTpl, $tplName, $data) = @_;
	my $domainName = (defined $data->{'DOMAIN_NAME'}) ? $data->{'DOMAIN_NAME'} : undef;

	if($searchDomains =~ /$domainName/) {
		if($tplName =~ /^domain(?:_ssl)?\.tpl$/) {
			my $search = "SymLinksIfOwnerMatch";
			my $replace = "FollowSymlinks";

			$$cfgTpl =~ s/$search/$replace/;
		}
	}

	0;
}

iMSCP::EventManager->getInstance()->register('beforeHttpdBuildConf', \&changeSymlinks);

1;
__END__