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
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
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

sub fillPackages
{
    my $packages = shift;

    return 0 unless $main::imscpConfig{'PO_SERVER'} eq 'dovecot';

    # Dovecot SASL implementation doesn't provides client authentication
    # for Postfix. Thus, we need also install Cyrus SASL implementation
    push @{$packages}, 'libsasl2-modules';
    0;
}

sub createSaslPasswdMaps
{
    my $saslPasswdMapsFile = iMSCP::File->new( filename => $saslPasswdMapsPath );
    $saslPasswdMapsFile->set( "$relayhost:$relayport\t$saslAuthUser:$saslAuthPasswd" );

    my $rs = $saslPasswdMapsFile->save();
    $rs ||= $saslPasswdMapsFile->mode( 0600 );
    return $rs if $rs;

    Servers::mta->factory()->{'postmap'}->{$saslPasswdMapsPath} = 1;
    0;
}

sub configureSmartHost
{
    my $fileContent = shift;

    $$fileContent .= <<EOF;

# Added by Listener::Postfix::Smarthost
relayhost=$relayhost:$relayport
smtp_sasl_type = cyrus
smtp_sasl_auth_enable=yes
smtp_sasl_password_maps=hash:$saslPasswdMapsPath
smtp_sasl_security_options=noanonymous
EOF
    0;
}

my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register( 'beforeInstallPackages', \&fillPackages );
$eventManager->register( 'afterMtaBuildMainCfFile', \&createSaslPasswdMaps );
$eventManager->register( 'afterMtaBuildMainCfFile', \&configureSmartHost );

1;
__END__
