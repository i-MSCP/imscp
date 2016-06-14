# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2016 by Laurent Declercq
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

my $relayhost = '[smtp.host.tld]';
my $relayport = '587';
my $saslAuthUser = '';
my $saslAuthPasswd = '';
my $saslPasswdMapsPath = '/etc/postfix/relay_passwd';
my $tlsCAfile = '/etc/ssl/certs/ca-certificates.crt';

#
## Please, don't edit anything below this line unless you known what you're doing
#

my $em = iMSCP::EventManager->getInstance();
$em->register(
    'beforeInstallPackages',
    sub {
        push @{$_[0]}, 'libsasl2-modules';
        0;
    }
);
$em->register(
    'afterMtaBuildConf',
    sub {
        my $mta = Servers::mta->factory();
        my $rs = $mta->addMapEntry( $saslPasswdMapsPath, "$relayhost:$relayport\t$saslAuthUser:$saslAuthPasswd" );
        $rs ||= $mta->postconf(
            (
                # Relay parameter
                relayhost                  => { action => 'replace', values => [ "$relayhost:$relayport" ] },
                # smtp SASL parameters
                smtp_sasl_type             => { action => 'replace', values => [ 'cyrus' ] },
                smtp_sasl_auth_enable      => { action => 'replace', values => [ 'yes' ] },
                smtp_sasl_password_maps    => { action => 'add', values => [ "hash:$saslPasswdMapsPath" ] },
                smtp_sasl_security_options => { action => 'replace', values => [ 'noanonymous' ] },
                # smtp TLS parameters (opportunistic)
                smtp_tls_security_level    => { action => 'replace', values => [ 'may' ] },
                smtp_tls_ciphers           => { action => 'replace', values => [ 'high' ] },
                smtp_tls_exclude_ciphers   => { action => 'replace', values => [ 'aNULL', 'MD5' ] },
                smtp_tls_protocols         => { action => 'replace', values => [ '!SSLv2', '!SSLv3' ] },
                smtp_tls_loglevel          => { action => 'replace', values => [ '0' ] },
                smtp_tls_CAfile            => { action => 'replace', values => [ $tlsCAfile ] }
            )
        );
    }
);

1;
__END__
