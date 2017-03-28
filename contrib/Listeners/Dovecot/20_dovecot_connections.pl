# i-MSCP Listener::Dovecot::Connections listener file
# Copyright (C) 2015-2017 Rene Schuster <mail@reneschuster.de>
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
## Allows to increase the mail_max_userip_connections parameter value.
#

package Listener::Dovecot::Connections;

use strict;
use warnings;
use iMSCP::EventManager;

#
## Configuration parameters
#

# Max connection per IP
my $maxConnections = 50;

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforePoBuildConf',
    sub {
        my ($cfgTpl, $tplName) = @_;

        return 0 unless $tplName eq 'dovecot.conf';

        $$cfgTpl .= <<EOF;

# BEGIN Listener::Dovecot::Connections
mail_max_userip_connections = $maxConnections
# END Listener::Dovecot::Connections
EOF
        0;
    }
);

1;
__END__
