# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2017-2019 by Laurent Declercq <l.declercq@nnuxwin.com>
# Copyright (C) 2013-2017 by Sascha Bay
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
## Allows to add additional server aliases in the given Apache2 vhosts.
#

package Listener::Apache2::ServerAlias::Override;

use strict;
use warnings;
use iMSCP::EventManager;
use Servers::httpd;

#
## Configuration variables
#

# Map Apache2 vhosts (domains) to additional server aliases 
my %SERVER_ALIASES = (
    # Add example1.in and example1.br server aliases to example1.com vhost
    'example1.com' => 'example1.in example1.br',
    # Add example2.in and example2.br server aliases to example2.com vhost
    'example2.com' => 'example2.in example2.br'
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforeHttpdBuildConfFile',
    sub
    {
        my ( undef, $tplName, $data ) = @_;

        return 0 unless $tplName eq 'domain.tpl'
            && length $SERVER_ALIASES{$data->{'DOMAIN_NAME'}};

        my $httpd = Servers::httpd->factory();
        my $serverData = $httpd->getData();

        $httpd->setData( {
            SERVER_ALIASES => length $serverData->{'SERVER_ALIASES'}
                ? $serverData->{'SERVER_ALIASES'} . ' '
                    . $SERVER_ALIASES{$data->{'DOMAIN_NAME'}}
                : $SERVER_ALIASES{$data->{'DOMAIN_NAME'}}
        } );
    }
);

1;
__END__
