# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2017-2018 Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Listener::Postfix::BCC::Map;

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::Servers::Mta;
use version;

#
## Configuration variables
#

my $postfixRecipientBccMap = '/etc/postfix/recipient_bcc_map';
my $postfixSenderBccMap = '/etc/postfix/sender_bcc_map';

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 30_postfix_bcc_maps.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'afterPostfixBuildConf',
    sub {
        my $mta = iMSCP::Servers::Mta->factory();
        my $rs = $mta->addMapEntry( $postfixRecipientBccMap );
        $rs ||= $mta->addMapEntry( $postfixSenderBccMap );
        $rs ||= $mta->postconf(
            (
                recipient_bcc_maps => {
                    action => 'add',
                    values => [ "hash:$postfixRecipientBccMap" ]
                },
                sender_bcc_maps    => {
                    action => 'add',
                    values => [ "hash:$postfixSenderBccMap" ]
                }
            )
        );
    },
    -99
);

1;
__END__
