# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2015 by Laurent Declercq
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
## Listener file that allows to configure Postfix as smarthost with SASL authentication.
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

#
## Please, don't edit anything below this line
#

my $eventManager = iMSCP::EventManager->getInstance();

$eventManager->register('afterMtaBuildMainCfFile', sub createRelayPasswdTable {
	my $mta = Servers::mta->factory();
	my $relayPasswdTable = "$mta->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/relay_passwd";

	iMSCP::File->new( filename => $relayPasswdTable )->save();
	$mta->addTableEntry("$relayhost:$relayport", "$saslAuthUser:$saslAuthPasswd", $relayPasswdTable, 'cdb');
});

$eventManager->register('afterMtaBuildMainCfFile', sub {
	my $fileContent = shift;

	$$fileContent .= <<EOF;

# Added by Listener::Postfix::Smarthost
relayhost=$relayhost:$relayport
smtp_sasl_auth_enable=yes
smtp_sasl_password_maps=cdb:$saslPwdTable
smtp_sasl_security_options=noanonymous
EOF

	0;
});

1;
__END__
