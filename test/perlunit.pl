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
# This script is automatically run on every commit and/or pull request. Any pull request causing a build failure won't
# be accepted. You can run this script manually as follow: perl ./test/perlunit.pl
#

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/Perl", "$FindBin::Bin/../engine/PerlLib", "$FindBin::Bin/../engine/PerlVendor";
use Test::More import => [ 'done_testing' ];
use File::Find;

my $assetDir = "$FindBin::Bin/Perl/TestAsset";
my $nbTests = 0;

chdir($FindBin::Bin . '/Perl');
find { wanted => sub {
		if(-f) {
			s%/%::%g;
			$_ = substr($_, 0, -3);

			eval "require $_";
			unless($@) {
				if(my $function = $_->can('runTests')) {
					print STDOUT sprintf("Running unit tests from %s...\n", $_);
					my $rs = $function->($assetDir);
					if(!$rs || $rs =~ /[^\d]/) {
						print STDERR sprintf("%s::runTests() must return number of tests that must be run.\n", $_);
						exit 1;
					}
					$nbTests += $rs;
				} else {
					print STDERR sprintf("%s package must implement the runTests() function.\n", $_);
				}
			} else {
				print STDERR sprintf('Could not load %s package: %s.', $_, $@);
				exit 1;
			}

			chdir($FindBin::Bin . '/Perl'); # Cover case where a unit tests package run chdir
		}
	},
	no_chdir => 1
}, 'Test';

done_testing($nbTests);

1;
__END__
