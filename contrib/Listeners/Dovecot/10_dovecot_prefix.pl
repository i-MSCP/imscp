# i-MSCP Listener::Dovecot::Prefix
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
## Listener file that remove the INBOX. prefix in the dovecot configuration file
#

package Listener::Dovecot::Prefix;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register('beforePoBuildConf', sub {
	my ($cfgTpl, $tplName) = @_;

	$$cfgTpl =~ s/(prefix\s=)\sINBOX\./$1/ if index($tplName, 'dovecot.conf') != -1;
});

1;
__END__
