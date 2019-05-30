# i-MSCP - internet Multi Server Control Panel
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

#
## Setup Postfix recipient and sender bbc maps.
#

package Listener::Postfix::BCC::Map;

use strict;
use warnings;
use iMSCP::EventManager;
use Servers::mta;

#
## Configuration variables
#

my $RECIPIENT_BBC_MAP_PATH = '/etc/postfix/recipient_bcc_map';
my $SENDER_BBC_MAP_PATH = '/etc/postfix/sender_bcc_map';

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'afterMtaBuildConf',
    sub
    {
        my $mta = Servers::mta->factory();

        if ( length $RECIPIENT_BBC_MAP_PATH ) {
            my $rs = $mta->addMapEntry( $RECIPIENT_BBC_MAP_PATH );
            $rs ||= $mta->postconf( (
                recipient_bcc_maps => {
                    action => 'add',
                    values => [ "hash:$RECIPIENT_BBC_MAP_PATH" ]
                },
            ));
            return $rs if $rs;
        }

        return 0 unless length $SENDER_BBC_MAP_PATH;

        my $rs = $mta->addMapEntry( $SENDER_BBC_MAP_PATH );
        $rs ||= $mta->postconf( (
            sender_bcc_maps => {
                action => 'add',
                values => [ "hash:$SENDER_BBC_MAP_PATH" ]
            }
        ));
    },
    -99
);

1;
__END__
