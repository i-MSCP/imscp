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

package Test::iMSCP::Dialog::Whiptail;

use strict;
use warnings;
use Test::More;
use Expect;

my $assetDir;

sub whiptailYesnoReturnExpectedYesResult
{
	my $exp = Expect->spawn('perl', "$assetDir/Dialog/yesno.pl", 'Whiptail') or die "Cannot spawn command: $!\n";
	$exp->log_stdout(0);
	$exp->expect(1) ;
	$exp->send("\r");
	$exp->expect(undef);
	$exp->exitstatus() >> 8 == 0;
}

sub whiptailYesnoReturnExpectedNoResult
{
	my $exp = Expect->spawn('perl', "$assetDir/Dialog/yesno.pl", 'Whiptail') or die "Cannot spawn command: $!\n";
	$exp->log_stdout(0);
	$exp->expect(1) ;
	$exp->send("\t");
	$exp->expect(1) ;
	$exp->send("\r");
	$exp->expect(undef);
	$exp->exitstatus() >> 8 == 1;
}

sub runUnitTests
{
	$assetDir = shift;
	plan tests => 0;  # Number of tests planned for execution
#
#	if(require_ok('iMSCP::Dialog::Whiptail')) {
#		eval {
#			ok whiptailYesnoReturnExpectedYesResult, "iMSCP::Dialog::Whiptail::yesno() return expected 'yes' result";
#			ok whiptailYesnoReturnExpectedNoResult, "iMSCP::Dialog::Whiptail::yesno() return expected 'no' result";
#		};
#
#		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
#	}
}

1;
__END__
