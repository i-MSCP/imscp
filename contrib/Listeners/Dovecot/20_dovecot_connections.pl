# i-MSCP Listener::Dovecot::Connections listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::File;
use Servers::po;
use version;

#
## Configuration parameters
#

# Max connection per IP
my $maxConnections = 50;

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->registerOne(
    'afterPoBuildConf',
    sub {
        version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
            sprintf( "The 20_dovecot_connection.pl listener file version %s requires i-MSCP >= 1.5.2", $VERSION )
        );

        my $dovecotConfdir = Servers::po->factory()->{'config'}->{'DOVECOT_CONF_DIR'};
        my $file = iMSCP::File->new( filename => "$dovecotConfdir/imscp.d/20_dovecot_connection_listener.conf" );
        $file->set( <<"EOT" );
mail_max_userip_connections = $maxConnections
EOT
        $file->save();
    }
);

1;
__END__
