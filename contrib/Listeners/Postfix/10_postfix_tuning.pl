# i-MSCP Listener::Postfix::Tuning listener file
# Copyright (C) 2015 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## Listener file that allows to tune Postfix configuration.
#

package Listener::Postfix::Tuning;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::ProgramFinder;
use iMSCP::EventManager;
use iMSCP::Execute;

#
## Configuration variables
#

# Path to Postfix configuration directory
my $postfixConfigDir = '/etc/postfix';

# Postfix main.cf ( see http://www.postfix.org/postconf.5.html )
# Hash where each pair of key/value correspond to a postfix parameter
# Please replace the entries below by your own entries
my %mainCfParameters = (
	'inet_protocols' => 'ipv4,ipv6',
	'inet_interfaces' => '127.0.0.1, 192.168.2.5, [2001:db8:0:85a3::ac1f:8001]',
	'smtp_bind_address' => '192.168.2.5',
	'smtp_bind_address6' => '',
	'relayhost' => '192.168.1.5:125'
);

# Postfix master.cf ( see http://www.postfix.org/master.5.html )
# Array where each entry correspond to a postfix service. Entries are added at bottom.
# Please replace the entries below by your own entries
my @masterCfParameters = (
	'125       inet  n       -       -       -       -       smtpd'
);

#
## Please, don't edit anything below this line
#

my $eventManager = iMSCP::EventManager->getInstance();

$eventManager->register('afterMtaBuildConf', sub {
	if(%mainCfParameters && iMSCP::ProgramFinder::find('postconf')) {
		my @cmd = ('postconf', '-e', '-c', escapeShell($postfixConfigDir));
		push @cmd, ($_ . '=' . escapeShell($mainCfParameters{$_})) for keys %mainCfParameters;

		my $rs = execute("@cmd", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
});

$eventManager->register('afterMtaBuildMasterCfFile', sub {
	my $cfgTpl = shift;

	if(@masterCfParameters) {
		$$cfgTpl .= join("\n", @masterCfParameters) . "\n";
	}

	0;
});

1;
__END__
