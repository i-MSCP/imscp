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
## Setup Postfix whilelist tables for policy servers.
#

package Listener::Postfix::Policy::Whitelist;

use strict;
use warnings;
use iMSCP::EventManager;
use Servers::mta;

#
## Configuration variables
#

my $POLICY_CLIENT_WHITELIST_TABLE_PATH = '/etc/postfix/policy_client_whitelist';
my $POLICY_RECIPIENT_WHITELIST_TABLE_PATH = '/etc/postfix/policy_recipient_whitelist';

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'afterMtaBuildConf',
    sub
    {
        my $mta = Servers::mta->factory();

        if ( length $POLICY_CLIENT_WHITELIST_TABLE_PATH ) {
            my $rs = $mta->addMapEntry( $POLICY_CLIENT_WHITELIST_TABLE_PATH );
            $rs ||= $mta->postconf( (
                smtpd_recipient_restrictions => {
                    action => 'add',
                    before => qr/permit/,
                    values => [
                        "check_client_access hash:$POLICY_CLIENT_WHITELIST_TABLE_PATH",
                    ]
                }
            ));
            return $rs if $rs;
        }

        return 0 unless length $POLICY_RECIPIENT_WHITELIST_TABLE_PATH;

        my $rs = $mta->addMapEntry( $POLICY_RECIPIENT_WHITELIST_TABLE_PATH );
        $rs ||= $mta->postconf( (
            smtpd_recipient_restrictions => {
                action => 'add',
                before => qr/permit/,
                values => [
                    "check_recipient_access hash:$POLICY_RECIPIENT_WHITELIST_TABLE_PATH"
                ]
            }
        ));
    },
    -99
);

1;
__END__
