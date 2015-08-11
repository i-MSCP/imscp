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

package Test::iMSCP::Crypt;

use strict;
use warnings;
use Test::More;

my $md5 = '$1$foo$rkx5AsSJuhMbYgao0AOra.';
my $salt = 'foo';
my $key = 'C4zLDj6"E%^Qf/ZQm](?@\\63$R@[8yP#'; # 256 bits long
my $ivBlowfish = 'lKJpi/i@'; # 8 bytes long
my $ivRijndael = 'lKJpi/i@sCv+A^,l'; # 16 bytes long
my $plainData = 'foo bar baz';
my $dataBlowfish = 'Bo7vO8yyrlwVbKVYRkf+1A==';
my $dataRijndael = 'NelcblDKROjOPANXvLEO+g==';

sub randomStrDieOnMissingLengthParameter
{
	local $@;
	eval { iMSCP::Crypt::randomStr() };
	$@ && $@ =~ /\$length parameter is not defined/;
}

sub randomStrReturnExpectedStringLength
{
	length iMSCP::Crypt::randomStr(8) == 8;
}

sub randomStrDoNotReturnUnexpectedChars
{
	iMSCP::Crypt::randomStr(10000) =~ /^[\x22-\x7E]+$/; # Testing 10 000 characters should be sufficient...
}

sub md5WithSaltReturnExpectedHash
{
	iMSCP::Crypt::md5($plainData, $salt) eq $md5;
}

sub encryptBlowfishDieOnMissingKeyParameter
{
	local $@;
	eval { iMSCP::Crypt::encryptBlowfish() };
	$@ && $@ =~ /\$key parameter is not defined/;
}

sub encryptBlowfishDieOnMissingIvParameter
{
	local $@;
	eval { iMSCP::Crypt::encryptBlowfish($key) };
	$@ && $@ =~ /\$iv parameter is not defined/;
}

sub encryptBlowfishDieOnMissingDataParameter
{
	local $@;
	eval { iMSCP::Crypt::encryptBlowfish($key, $ivBlowfish) };
	$@ && $@ =~ /\$data parameter is not defined/;
}

sub encryptBlowfishReturnExpectedString
{
	iMSCP::Crypt::encryptBlowfish($key, $ivBlowfish, $plainData) eq $dataBlowfish;
}

sub decryptBlowfishDieOnMissingKeyParameter
{
	local $@;
	eval { iMSCP::Crypt::decryptBlowfish() };
	$@ && $@ =~ /\$key parameter is not defined/;
}

sub decryptBlowfishDieOnMissingIvParameter
{
	local $@;
	eval { iMSCP::Crypt::decryptBlowfish($key) };
	$@ && $@ =~ /\$iv parameter is not defined/;
}

sub decryptBlowfishDieOnMissingDataParameter
{
	local $@;
	eval { iMSCP::Crypt::decryptBlowfish($key, $ivBlowfish) };
	$@ && $@ =~ /\$data parameter is not defined/;
}

sub decryptBlowfishReturnExpectedString
{
	iMSCP::Crypt::decryptBlowfish($key, $ivBlowfish, $dataBlowfish) eq $plainData;
}

sub encryptRijndaelDieOnMissingKeyParameter
{
	local $@;
	eval { iMSCP::Crypt::encryptRijndael() };
	$@ && $@ =~ /\$key parameter is not defined/;
}

sub encryptRijndaelDieOnMissingIvParameter
{
	local $@;
	eval { iMSCP::Crypt::encryptRijndael($key) };
	$@ && $@ =~ /\$iv parameter is not defined/
}

sub encryptRijndaelDieOnMissingDataParameter
{
	local $@;
	eval { iMSCP::Crypt::encryptRijndael($key, $ivRijndael) };
	$@ && $@ =~ /\$data parameter is not defined/
}

sub encryptRijndaelReturnExpectedString
{
	iMSCP::Crypt::encryptRijndael($key, $ivRijndael, $plainData) eq $dataRijndael;
}

sub decryptRijndaelDieOnMissingKeyParameter
{
	local $@;
	eval { iMSCP::Crypt::decryptRijndael() };
	$@ && $@ =~ /\$key parameter is not defined/;
}

sub decryptRijndaelDieOnMissingIvParameter
{
	local $@;
	eval { iMSCP::Crypt::decryptRijndael($key) };
	$@ && $@ =~ /\$iv parameter is not defined/;
}

sub decryptRijndaelDieOnMissingDataParameter
{
	local $@;
	eval { iMSCP::Crypt::decryptRijndael($key, $ivRijndael) };
	$@ && $@ =~ /\$data parameter is not defined/;
}

sub decryptRijndaelReturnExpectedString
{
	iMSCP::Crypt::decryptRijndael($key, $ivRijndael, $dataRijndael) eq $plainData;
}

sub runUnitTests
{
	plan tests => 21;  # Number of tests planned for execution

	if(require_ok('iMSCP::Crypt')) {
		eval {
			ok randomStrDieOnMissingLengthParameter, 'iMSCP::Crypt::randomStr die on missing $length parameter';
			ok randomStrReturnExpectedStringLength, 'iMSCP::Crypt::randomStr return expected string length';
			ok randomStrDoNotReturnUnexpectedChars, 'iMSCP::Crypt::randomStr do not return unexpected characters';

			ok md5WithSaltReturnExpectedHash, 'iMSCP::Crypt::md5 return expected hash';

			ok encryptBlowfishDieOnMissingKeyParameter, 'iMSCP::Crypt::encryptBlowfish die on missing $key parameter';
			ok encryptBlowfishDieOnMissingIvParameter, 'iMSCP::Crypt::encryptBlowfish die on missing $iv parameter';
			ok encryptBlowfishDieOnMissingDataParameter, 'iMSCP::Crypt::encryptBlowfish die on missing $data parameter';
			ok encryptBlowfishReturnExpectedString, 'iMSCP::Crypt::encryptBlowfish return expected string';

			ok decryptBlowfishDieOnMissingKeyParameter, 'iMSCP::Crypt::decryptBlowfish die on missing $key parameter';
			ok decryptBlowfishDieOnMissingIvParameter, 'iMSCP::Crypt::decryptBlowfish die on missing $iv parameter';
			ok decryptBlowfishDieOnMissingDataParameter, 'iMSCP::Crypt::decryptBlowfish die on missing $data parameter';
			ok decryptBlowfishReturnExpectedString, 'iMSCP::Crypt::decryptBlowfish return expected string';

			ok encryptRijndaelDieOnMissingKeyParameter, 'iMSCP::Crypt::encryptRijndael die on missing $key parameter';
			ok encryptRijndaelDieOnMissingIvParameter, 'iMSCP::Crypt::encryptRijndael die on missing $iv parameter';
			ok encryptRijndaelDieOnMissingDataParameter, 'iMSCP::Crypt::encryptRijndael die on missing $data parameter';
			ok encryptRijndaelReturnExpectedString, 'iMSCP::Crypt::encryptRijndael return expected string';

			ok decryptRijndaelDieOnMissingKeyParameter, 'iMSCP::Crypt::decryptRijndael die on missing $key parameter';
			ok decryptRijndaelDieOnMissingIvParameter, 'iMSCP::Crypt::decryptRijndael die on missing $iv parameter';
			ok decryptRijndaelDieOnMissingDataParameter, 'iMSCP::Crypt::decryptRijndael die on missing $data parameter';
			ok decryptRijndaelReturnExpectedString, 'iMSCP::Crypt::decryptRijndael return expected string';
		};

		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
	}
}

1;
__END__
