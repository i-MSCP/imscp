# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2016 by Sascha Bay
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
## Setup Postfix whilelist tables for policyd-weight policy daemon.
#

package Listener::Postfix::Policyd::Whitelist;

use strict;
use warnings;
use iMSCP::EventMaanger;
use Servers::mta;

#
## Configuration variables
#

my $policydWeightClientWhitelistTable = '/etc/postfix/policyd_weight_client_whitelist';
my $policydWeightRecipientWhitelistTable = '/etc/postfix/policyd_weight_recipient_whitelist';

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'afterMtaBuildConf',
    sub {
        my $mta = Servers::mta->factory();
        my $rs = $mta->addMapEntry( $policydWeightClientWhitelist );
        $rs ||= $mta->addMapEntry( $policydWeightRecipientWhitelist );
        $rs ||= $mta->postconf(
            (
                smtpd_recipient_restrictions => {
                    action => 'add',
                    before => qr/check_policy_service\s+.*/,
                    values => [
                        "check_client_access hash:$policydWeightClientWhitelistTable",
                        "check_recipient_access hash:$policydWeightRecipientWhitelistTable"
                    ]
                }
            )
        );
    }
);

1;
__END__
