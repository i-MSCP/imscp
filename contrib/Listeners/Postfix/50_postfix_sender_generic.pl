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
## Allows to setup sender generic map.
#

package Listener::Postfix::Sender::Generic::Map;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;

#
## Configuration variables
#

my $postfixSmtpGenericMap = '/etc/postfix/imscp/smtp_outgoing_rewrite';
my $addSmtpGenericMap = "smtp_generic_maps = hash:/etc/postfix/imscp/smtp_outgoing_rewrite\n";

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'afterMtaBuildMainCfFile',
    sub {
        my $tplContent = shift;

        return 0 unless -f $postfixSmtpGenericMap;

        my $rs = execute( "postmap $postfixSmtpGenericMap", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;

        $$tplContent .= "$addSmtpGenericMap";
        0;
    }
);

1;
__END__
