# i-MSCP Listener::Dovecot::Namespace listener file
# Copyright (C) 2017-2018 Laurent Declercq <l.declercq@nuxwin.com>
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

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::File;
use Servers::po;
use version;

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 30_dovecot_namespace.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->registerOne(
    'afterDovecotBuildConf',
    sub {
        my $dovecotConfdir = Servers::po->factory()->{'config'}->{'DOVECOT_CONF_DIR'};
        my $file = iMSCP::File->new( filename => "$dovecotConfdir/imscp.d/30_dovecot_namespace_listener.conf" );
        $file->set( <<'EOT' );
namespace inbox {
    separator = /
    prefix =
}

namespace compat {
    separator = .
    prefix = INBOX.
    inbox = no
    hidden = yes
    list = no
    alias_for =
}
EOT
        $file->save();
    }
);

1;
__END__
