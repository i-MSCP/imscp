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
## Listener file that allows to turn SymLinksIfOwnerMatch option into Symlinks option in domain vhost files
#

package Listener::Apache2::FollowSymlinks;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::EventManager;

#
## Configuration variables
#

# Enter the list of domains for which the SymLinksIfOwnerMatch option must be overriden. For instance:
# my @searchDomains = ('example.com', 'sub.example.com', 'example2.com' );
# Not that domains name must be in ASCII form

my @searchDomains = ( );

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('beforeHttpdBuildConf', sub {
	my ($cfgTpl, $tplName, $data) = @_;

	if($tplName =~ /^domain(?:_ssl)?\.tpl$/ && $data->{'DOMAIN_NAME'} ~~ @searchDomains) {
		$$cfgTpl =~ s/SymLinksIfOwnerMatch/FollowSymlinks/g;
	}

	0;
});

1;
__END__
