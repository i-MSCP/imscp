# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq
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
## Configure Postfix to route all mails to a smarthost using SASL authentication.
#

package Listener::Postfix::Smarthost;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::File;
use Servers::mta;

#
## Configuration variables
#

my $RELAY_HOST = '[smtp.host.tld]';
my $RELAY_PORT = '587';
my $SASL_AUTH_USER = '';
my $SASL_AUTH_PASSWD = '';
my $SASL_PASSWD_MAP_PATH = '/etc/postfix/relay_passwd';

#
## Please, don't edit anything below this line unless you known what you're doing
#

# Add the libsasl2-modules distribution package to the list of distribution
# packages to install
iMSCP::EventManager->getInstance()->registerOne(
    'beforeInstallPackages',
    sub {
        push @{ $_[0] }, 'libsasl2-modules';
        0;
    }
);

# We must prevent uninstallation of the libsasl2-modules distribution package
iMSCP::EventManager->getInstance()->registerOne(
    'beforeUninstallPackages',
    sub {
        @{ $_[0] } = grep { $_ ne 'libsasl2-modules' } @{ $_[0] };
        0;
    }
);

iMSCP::EventManager->getInstance()->register(
    'afterMtaBuildConf',
    sub
    {
        my $mta = Servers::mta->factory();
        my $rs = $mta->addMapEntry(
            $SASL_PASSWD_MAP_PATH,
            "$RELAY_HOST:$RELAY_PORT\t$SASL_AUTH_USER:$SASL_AUTH_PASSWD"
        );
        $rs ||= $mta->postconf( (
            # Relay parameter
            relayhost                  => {
                action => 'replace',
                values => [ "$RELAY_HOST:$RELAY_PORT" ]
            },
            # smtp SASL parameters
            smtp_sasl_type             => {
                action => 'replace',
                values => [ 'cyrus' ]
            },
            smtp_sasl_auth_enable      => {
                action => 'replace',
                values => [ 'yes' ]
            },
            smtp_sasl_password_maps    => {
                action => 'add',
                values => [ "hash:$SASL_PASSWD_MAP_PATH" ]
            },
            smtp_sasl_security_options => {
                action => 'replace',
                values => [ 'noanonymous' ]
            }
        ));
    },
    -99
);

1;
__END__
