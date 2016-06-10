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
## Allows to configure Postfix as smarthost with SASL authentication.
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

my $relayhost = 'smtp.host.tld';
my $relayport = '587';
my $saslAuthUser = '';
my $saslAuthPasswd = '';
my $saslPasswdMapsPath = '/etc/postfix/relay_passwd';

#
## Please, don't edit anything below this line
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
    'afterMtaBuildMainCfFile',
    sub {
        my $saslPasswdMapsFile = iMSCP::File->new( filename => $saslPasswdMapsPath );
        my $rs = $saslPasswdMapsFile->set( "$relayhost:$relayport\t$saslAuthUser:$saslAuthPasswd" );
        $rs ||= $saslPasswdMapsFile->save();
        $rs ||= $saslPasswdMapsFile->mode( 0600 );
        return $rs if $rs;

        my $mta = Servers::mta->factory();
        local $@;
        eval {
            $rs = $mta->postconf(
                (
                    relayhost                  => { action => 'replace', values => [ "$relayhost:$relayport" ] },
                    smtp_sasl_type             => { action => 'replace', values => [ 'cyrus' ] },
                    smtp_sasl_auth_enable      => { action => 'replace', values => [ 'yes' ] },
                    smtp_sasl_password_maps    => { action => 'add', values => [ $saslPasswdMapsPath ] },
                    smtp_sasl_security_options => { action => 'add', values => [ 'noanonymous' ] }
                )
            );
            return $rs if $rs;
        };
        if ($@) {
            error( 'Could not configure smarthost: %s', $@ );
            return 1;
        }

        $mta->{'postmap'}->{$saslPasswdMapsPath} = 1;
        0;
    }
);

1;
__END__
