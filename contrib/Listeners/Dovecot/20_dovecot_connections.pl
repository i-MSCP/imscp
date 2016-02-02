# i-MSCP Listener::Dovecot::Connections listener file
# Copyright (C) 2015-2016 Rene Schuster <mail@reneschuster.de>
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
## i-MSCP listener file to increase the mail_max_userip_connections
#

package Listener::Dovecot::Connections;

use strict;
use warnings;
use iMSCP::EventManager;


iMSCP::EventManager->getInstance()->register('beforePoBuildConf', sub {
	my ($cfgTpl, $tplName) = @_;

        my $cfgSnippet = <<EOF;
# BEGIN Listener::Dovecot::Connections
mail_max_userip_connections = 50
# END Listener::Dovecot::Connections
EOF

	$$cfgTpl .= "\n$cfgSnippet" if index($tplName, 'dovecot.conf') != -1;
	
	0;
});

1;
__END__
