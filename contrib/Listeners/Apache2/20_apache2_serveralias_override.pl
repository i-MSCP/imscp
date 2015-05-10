# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2014 by Sascha Bay
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

#
## Listener file that allows to override Apache 2 ServerAlias directive value.
#

package Listener::Apache2::ServerAlias::Override;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::EventManager;

#
## Configuration variables
#

my $searchDomain = 'example.com';
my $addServerAlias = 'example'; # Add more than one alias (example example-2 example-3.com)

#
## Please, don't edit anything below this line
#

sub overrideServerAlias
{
	my ($tplFileContent, $tplFileName, $data) = @_;

	my $domainName = (defined $data->{'DOMAIN_NAME'}) ? $data->{'DOMAIN_NAME'} : undef;

	if(
		$domainName && $domainName eq $searchDomain &&
		$tplFileName ~~ [ 'domain_redirect.tpl', 'domain.tpl', 'domain_redirect_ssl.tpl', 'domain_ssl.tpl' ]
	) {
		$$tplFileContent =~ s/^(\s+ServerAlias.*)/$1 $addServerAlias/m;
	}

	0;
}

iMSCP::EventManager->getInstance()->register('afterHttpdBuildConf', \&overrideServerAlias);

1;
__END__
