# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Test::iMSCP::Servers;

use strict;
use warnings;
use Test::More;
use Cwd qw/abs_path/;

sub getInstanceDieIfCannotReadDir
{
	local $@;
	eval { iMSCP::Servers->getInstance(); };
	undef $iMSCP::Servers::_instance; # Destroy singleton
	$@;
}

sub runUnitTests
{
	plan tests => 4;  # Number of tests planned for execution

	if(require_ok('iMSCP::Servers')) {
		eval {
			$main::imscpConfig{'ENGINE_ROOT_DIR'} = '/tmp/foo';
			ok getInstanceDieIfCannotReadDir, 'getInstance() die if cannot read directory';
			$main::imscpConfig{'ENGINE_ROOT_DIR'} = abs_path('../../engine');
			is_deeply
				[ sort iMSCP::Servers->getInstance()->get() ],
				[ sort qw/po sqld httpd cron named ftpd mta/ ],
				'get() return expected server list';
			is_deeply
				[ sort iMSCP::Servers->getInstance()->getFull() ],
				[
					sort 'Servers::po', 'Servers::sqld', 'Servers::httpd', 'Servers::cron', 'Servers::named',
					'Servers::ftpd', 'Servers::mta'
				],
				'getFull() return expected server list';
		};

		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
	}

	undef $main::imscpConfig{'ENGINE_ROOT_DIR'};
}

1;
__END__
