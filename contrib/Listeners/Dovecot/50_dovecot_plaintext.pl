# i-MSCP Listener::Dovecot::Plaintext listener file
# Copyright (C) 2016-2016 Rene Schuster <mail@reneschuster.de>
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
## Disables plaintext logins and enforce TLS. Also remove the cram-md5 and digest-md5 authentication mechanisms that are
## no longer supported in i-MSCP 1.3.x
#

package Listener::Dovecot::Plaintext;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register('beforePoBuildConf', sub {
	my ($cfgTpl, $tplName) = @_;

	return 0 unless index($tplName, 'dovecot.conf') != -1;

	$$cfgTpl =~ s/\s+cram-md5\s+digest-md5//;
	$$cfgTpl =~ s/^(disable_plaintext_auth\s+=\s+).*/$1yes/m;
	0;
});

1;
__END__
