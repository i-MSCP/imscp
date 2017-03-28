# i-MSCP Listener::Dovecot::Namespace listener file
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
## Creates the INBOX. as a compatibility name, so old clients can continue using it while new clients will use the
## empty prefix namespace.
#

package Listener::Dovecot::Namespace;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register(
    'beforePoBuildConf',
    sub {
        my ($cfgTpl, $tplName) = @_;

        return 0 unless $tplName eq 'dovecot.conf';

        my $cfgSnippet = <<EOF;

# BEGIN Listener::Dovecot::Namespace
namespace compat {
	separator = .
	prefix = INBOX.
	inbox = no
	hidden = yes
	list = no
	alias_for =
}
# END Listener::Dovecot::Namespace
EOF

        $$cfgTpl =~ s/(separator\s+=\s+)\./$1\//;
        $$cfgTpl =~ s/(prefix\s+=\s+)INBOX\./$1/;
        $$cfgTpl =~ s/^(namespace\s+inbox\s+\{.*?^\}\n)/$1$cfgSnippet/sm;
        0;
    }
);

1;
__END__
