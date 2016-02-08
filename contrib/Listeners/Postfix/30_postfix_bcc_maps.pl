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
## Listener file that allows to setup recipient and sender bbc maps.
#

package Listener::Postfix::BCC::Map;
 
use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;

#
## Configuration variables
#

my $postfixRecipientBccMap = '/etc/postfix/imscp/recipient_bcc_map';
my $postfixSenderBccMap = '/etc/postfix/imscp/sender_bcc_map';
my $addRecipientBccMap = "recipient_bcc_maps = hash:/etc/postfix/imscp/recipient_bcc_map\n";
my $addSenderBccMap = "sender_bcc_maps = hash:/etc/postfix/imscp/sender_bcc_map\n";

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('afterMtaBuildMainCfFile', sub {
	my $tplContent = shift;

	return 0 unless -f $postfixRecipientBccMap && -f $postfixSenderBccMap;

	my ($stdout, $stderr);
	my $rs = execute("postmap $postfixRecipientBccMap", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("postmap $postfixSenderBccMap", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$$tplContent .= "$addRecipientBccMap";
	$$tplContent .= "$addSenderBccMap";

	0;
});

1;
__END__
