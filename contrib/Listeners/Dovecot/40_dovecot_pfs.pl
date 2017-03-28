# i-MSCP Listener::Dovecot::PFS listener file
# Copyright (C) 2016-2017 Rene Schuster <mail@reneschuster.de>
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
## Activates the Perfect Forward Secrecy logging.
#

package Listener::Dovecot::PFS;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register(
    'beforePoBuildConf',
    sub {
        my ($cfgTpl, $tplName) = @_;

        return 0 unless $tplName eq 'dovecot.conf';

        $$cfgTpl .= <<EOF;

# BEGIN Listener::Dovecot::PFS
login_log_format_elements = user=<%u> method=%m rip=%r lip=%l mpid=%e %c %k session=<%{session}>
# END Listener::Dovecot::PFS
EOF
        0;
    }
);

1;
__END__
