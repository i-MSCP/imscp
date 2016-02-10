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
## Allows to setup policyd-weight whilelist maps.
#

package Listener::Postfix::Policyd::Whitelist;
 
use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventMaanger;
use iMSCP::Execute;

#
## Configuration variables
#

my $policydWeightClientWhitelist = '/etc/postfix/imscp/policyd_weight_client_whitelist';
my $policydWeightRecipientWhitelist = '/etc/postfix/imscp/policyd_weight_recipient_whitelist';
my $checkClientAccess = "\n check_client_access hash:/etc/postfix/imscp/policyd_weight_client_whitelist,";
my $checkRecipientAccess = "\n check_recipient_access hash:/etc/postfix/imscp/policyd_weight_recipient_whitelist,";
 
#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('afterMtaBuildMainCfFile', sub {
	my $tplContent = shift;

	return 0 unless -f $policydWeightClientWhitelist && -f $policydWeightRecipientWhitelist;

	my ($stdout, $stderr);
	my $rs = execute("postmap $policydWeightClientWhitelist", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("postmap $policydWeightRecipientWhitelist", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	if ($$tplContent !~ /check_client_access/m) {
		$$tplContent =~ s/(reject_non_fqdn_recipient,)/$1$checkClientAccess/;
	}

	if ($$tplContent !~ /check_recipient_access/m) {
		$$tplContent =~ s/(reject_non_fqdn_recipient,)/$1$checkRecipientAccess/;
	}

	0;
});

1;
__END__
