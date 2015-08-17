#!/usr/bin/perl
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

#
# This script is automatically run by Travis CI on every commit and/or pull request made on our GitHub repository.
# Any pull request causing a build failure won't be accepted.
#
# You can run this script manually as follow: perl ./test/perlunit.pl
#

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/Perl", "$FindBin::Bin/../engine/PerlLib", "$FindBin::Bin/../engine/PerlVendor";
use Test::More import => [ 'diag', 'done_testing', 'fail', 'subtest' ];
use File::Find;
use POSIX qw(locale_h);
use Symbol 'delete_package';
use locale;

setlocale(LC_MESSAGES, 'C.UTF-8');

$ENV{'LANG'} = 'C.UTF-8';
$ENV{'PATH'} = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

chdir "$FindBin::Bin/Perl";

find { wanted => sub {
	if(-f) {
		(my $package = substr($_, 0, -3)) =~ s|/|::|g;

		local $@;
		eval { require $_ } or do { fail sprintf("%s: Could not run unit tests\n%s", $package, $@); return };

		if(my $function = $package->can('runUnitTests')) {
			diag "\nRunning unit tests from $package package...\n\n";
			subtest "$package unit tests", sub { $function->("$FindBin::Bin/Perl/TestAsset") };
			delete_package $package; # Mitigate memory consumption by wiping out the whole test package namespace
		} else {
			fail sprintf('%s::runUnitTests() not implemented.', $package);
		}

		chdir "$FindBin::Bin/Perl";
	}},
	no_chdir => 1
}, 'Test';

done_testing;
