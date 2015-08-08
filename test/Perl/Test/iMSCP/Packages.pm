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

package Test::iMSCP::Packages;

use strict;
use warnings;
use Test::More;
use Cwd qw/abs_path/;

sub getInstanceDieIfCannotReadDir
{
	local $@;
	eval { iMSCP::Packages->getInstance(); };
	undef $iMSCP::Packages::_instance; # Destroy singleton
	$@;
}

sub runUnitTests
{
	plan tests => 4;  # Number of tests planned for execution

	if(require_ok('iMSCP::Packages')) {
		eval {
			$main::imscpConfig{'ENGINE_ROOT_DIR'} = '/tmp/foo';
			ok getInstanceDieIfCannotReadDir, 'iMSCP::Package::getInstance die if cannot read directory';
			$main::imscpConfig{'ENGINE_ROOT_DIR'} = abs_path('../../engine');
			is_deeply
				[ sort iMSCP::Packages->getInstance()->get() ],
				[ sort qw/Webmail FrontEnd PhpMyAdmin FileManager Webstats AntiRootkits/ ],
				'iMSCP::Packages::get() return expected package list';
			is_deeply
				[ sort iMSCP::Packages->getInstance()->getFull() ],
				[
					sort 'Package::Webmail', 'Package::FrontEnd', 'Package::PhpMyAdmin', 'Package::FileManager',
					'Package::Webstats', 'Package::AntiRootkits'
				],
				'iMSCP::Packages::getFull() return expected package list';
		};

		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
	}

	undef $main::imscpConfig{'ENGINE_ROOT_DIR'};
}

1;
__END__
