# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2015 by Sascha Bay
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
## Listener file that allows to setup sender generic map.
#

package Listener::Postfix::Sender::Generic::Map;
 
use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;

#
## Configuration variables
#

my $postfixSmtpGenericMap = '/etc/postfix/imscp/smtp_outgoing_rewrite';
my $addSmtpGenericMap = "smtp_generic_maps = hash:/etc/postfix/imscp/smtp_outgoing_rewrite\n";

#
## Please, don't edit anything below this line
#

sub onAfterMtaBuildPostfixSmtpGenericMap($)
{
	my $tplContent = shift;

	if (-f $postfixSmtpGenericMap) {
		my ($stdout, $stderr);
		my $rs = execute("postmap $postfixSmtpGenericMap", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$$tplContent .= "$addSmtpGenericMap";
	}

	0;
}

iMSCP::HooksManager->getInstance()->register('afterMtaBuildMainCfFile', \&onAfterMtaBuildPostfixSmtpGenericMap);

1;
__END__
