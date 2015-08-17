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

my $salt2Bytes = '12';
my $salt8Bytes = '12345678';
my $salt16Bytes = '1234567890123456';
my $cost = 10;
my $rounds = 3000;
my $key32Bytes = '12345678901234561234567890123456';
my $key56Bytes = '12345678901234123456789012341234567890123412345678901234';
my $iv8Bytes = '12345678';
my $iv16Bytes = '1234567890123456';
my $data = 'test';

# All values below were generated using an external library (PHP iMSCP::Crypt library)
my $md5Hash = '$1$12345678$oEitTZYQtRHfNGmsFvTBA/';
my $sha256Hash = '$5$rounds=3000$1234567890123456$QbKVVusZIUPuF5/r2gGBHnXuEMJWdx41RydQzYgodo8';
my $sha512Hash = '$6$rounds=3000$1234567890123456$61O.usmUiHWRQULRqkGw6rK988RdV2jq2g64x13QpIhWd3K/OW6Z1.rfwlErRN0GI.UbksyypY8u1wTitsWG7/';
my $bcryptHash = '$2a$10$KRGxLBS0Lxe3KBCwKxOzLexLDeu0ZfqJAKTubOfy7O/yL2hjimw3u';
my $htpasswdCryptHash = '126D8rSh5sjUE';
my $htpasswdMD5hash = '$apr1$12345678$e74Lvsv64yfuPhXCxCD7n1';
my $htpasswdSHA1hash = '{SHA}qUqP5cyxm6YcTAhz05Hph5gvu9M=';
my $blowfishEncData = 'Z/sxtQcaorY=';
my $rijndaelEncData = 'bdSkq12ehfOLJaqF+V/uew==';

sub randomStrCroakOnBadLengthParameter
{
	local $@;

	eval { randomStr('foo') };
	my $err1 = $@;
	undef $@;

	eval { randomStr(0) };
	my $err2 = $@;

	($err1 && $err1 =~ /Bad length parameter. Numeric value expected/) &&
	($err2 && $err2 =~ /Length parameter value must be >= 1/);
}

sub randomStrReturnExpectedStringLength
{
	my $ret = 1;
	for(my $length = 1; $length < 512; $length++) {
		my $rand = randomStr($length);
		length $rand == $length or $ret = 0, last;
	}

	$ret;
}

sub randomStrReturnExpectedBase64String
{
	my $ret = 1;
	for(my $length = 1; $length < 512; $length++) {
		my $rand = randomStr($length);
		$rand =~ /^[0-9a-zA-Z+\/]+$/ or $ret = 0, last;
	}

	$ret;
}

sub randomStrReturnExpectedStringWithCharlist
{
	my $ret = 1;
	for(my $length = 1; $length < 512; $length++) {
		my $rand = randomStr($length, '0123456789abcdef');
		$rand =~ /^[0-9a-f]+$/ or $ret = 0, last;
	}

	$ret;
}

sub md5ReturnExpectedHashWithRandomSalt
{
	my $hash = md5($data);
	length $hash == 34;
}

sub md5CroakOnBadSaltLength
{
	local $@;
	eval { md5($data, 'foo') };
	$@ && $@ =~ /The salt length must be at least 8 bytes long/;
}

sub md5ReturnExpectedHashWithSalt
{
	my $hash = md5($data, $salt8Bytes);
	$hash eq $md5Hash;
}

sub md5ReturnExpectedHashWith8bitCharacter
{
	my $data = $data . chr(128);
	my $hash = md5($data, $salt8Bytes);
	$hash eq '$1$12345678$xiaxUDAWDux.jVGj0HOhg0';
}

sub sha256ReturnExpectedHashWithRandomSalt
{
	my $hash = sha256($data);
	length $hash == 75;
}

sub sha256CroakOnBadRoundsParameter
{
	local $@;

	eval { sha256($data, 'foo') };
	my $err1 = $@;
	undef $@;

	eval { sha256($data, 500) };
	my $err2 = $@;
	undef $@;

	eval { sha256($data, 6000) };
	my $err3 = $@;
	undef $@;

	($err1 && $err1 =~ /Bad rounds parameter. Numeric value expected/) &&
	($err2 && $err2 =~ /The rounds parameter must be in range 1000-5000/) &&
	($err3 && $err3 =~ /The rounds parameter must be in range 1000-5000/);
}

sub sha256CroakOnBadSaltLength
{
	local $@;
	eval { sha256($data, $rounds, 'foo') };
	$@ && $@ =~ /The salt length must be at least 16 bytes long/;
}

sub sha256ReturnExpectedHashWithSalt
{
	my $hash = sha256($data, $rounds, $salt16Bytes);
	$hash eq $sha256Hash;
}

sub sha256ReturnExpectedHashWith8bitCharacter
{
	my $data = $data . chr(128);
	my $hash = sha256($data, $rounds, $salt16Bytes);
	$hash eq '$5$rounds=3000$1234567890123456$Xt0Bcg4pHAVBQ15O1Coma83wOBMinqRUe6PZbv6nUD0';
}

sub sha512ReturnExpectedHashWithRandomSalt
{
	my $hash = sha512($data);
	length $hash == 118;
}

sub sha512CroakOnBadRoundsParameter
{
	local $@;

	eval { sha512($data, 'foo') };
	my $err1 = $@;
	undef $@;

	eval { sha512($data, 500) };
	my $err2 = $@;
	undef $@;

	eval { sha512($data, 6000) };
	my $err3 = $@;
	undef $@;

	($err1 && $err1 =~ /Bad rounds parameter. Numeric value expected/) &&
	($err2 && $err2 =~ /The rounds parameter must be in range 1000-5000/) &&
	($err3 && $err3 =~ /The rounds parameter must be in range 1000-5000/);
}

sub sha512CroakOnBadSaltLength
{
	local $@;
	eval { sha512($data, $rounds, 'foo') };
	$@ && $@ =~ /The salt length must be at least 16 bytes long/;
}

sub sha512ReturnExpectedHashWithSalt
{
	my $hash = sha512($data, $rounds, $salt16Bytes);
	$hash eq $sha512Hash;
}

sub sha512ReturnExpectedHashWith8bitCharacter
{
	my $data = $data . chr(128);
	my $hash = sha512($data, $rounds, $salt16Bytes);
	$hash eq '$6$rounds=3000$1234567890123456$Daeo5V0dnI.vz0fRgV3wC2a1SU2GQ6AMO5oJujsf2Bb2cJy6ZY9kgNSwWcSBYCS64wtzAtWJSvRhM/yCo9mTT0';
}

sub bcryptReturnExpectedHashWithRandomSalt
{
	my $hash = bcrypt($data);
	length $hash == 60;
}

sub bcryptCroakOnBadCostParameter
{
	local $@;

	eval { bcrypt($data, 'foo') };
	my $err1 = $@;
	undef $@;

	eval { bcrypt($data, 500) };
	my $err2 = $@;
	undef $@;

	eval { bcrypt($data, 6000) };
	my $err3 = $@;
	undef $@;

	($err1 && $err1 =~ /Bad cost parameter. Numeric value expected/) &&
	($err2 && $err2 =~ /The cost parameter must be in range 04-31/) &&
	($err3 && $err3 =~ /The cost parameter must be in range 04-31/);
}

sub bcryptCroakOnBadSaltLength
{
	local $@;
	eval { bcrypt($data, $cost, 'foo') };
	$@ && $@ =~ /The salt length must be at least 16 bytes long/;
}

sub bcryptReturnExpectedHashWithSalt
{
	my $hash = bcrypt($data, $cost, $salt16Bytes);
	$hash eq $bcryptHash;
}

sub bcryptReturnExpectedHashWith8bitCharacter
{
	my $data = $data . chr(128);
	my $hash = bcrypt($data, $cost, $salt16Bytes);
	$hash eq '$2a$10$KRGxLBS0Lxe3KBCwKxOzLe7OpCtUspJHS1/R2zlIYxO75iTJyxTyu';
}

sub htpasswdCroakOnBadFormat
{
	local $@;
	eval { htpasswd('foo', undef, undef, 'bar') };
	$@ && $@ =~ /The .*? format is not valid. The supported formats are:/;
}

sub htpasswdReturnExpectedHashWithDefaultFormat
{
	my $hash = htpasswd($data, undef, $salt8Bytes);
	$hash eq $htpasswdMD5hash;
}

sub htpasswdMD5returnExpectedHashWithRandomSalt
{
	my $hash = htpasswd($data, undef, undef, 'md5');
	length $hash == 37;
}

sub htpasswdCryptReturnExpectedHashWithRandomSalt
{
	my $hash = htpasswd($data, undef, undef, 'crypt');
	length $hash == 13;
}

sub htpasswdSHA1returnExpectedHash
{
	my $hash = htpasswd($data, undef, undef, 'sha1');
	$hash eq $htpasswdSHA1hash;
}

sub htpasswdMD5croakOnBadSaltLength
{
	local $@;

	eval { htpasswd($data, undef, 'foobar', 'md5') };
	my $ret1 = $@;
	undef $@;

	eval { htpasswd($data, undef, 'foobarbaz', 'md5') };
	my $ret2 = $@;
	undef $@;

	$ret1 && $ret1 =~ /\QThe salt length for md5 (APR1) algorithm must be 8 bytes long/ &&
	$ret2 && $ret2 =~ /\QThe salt length for md5 (APR1) algorithm must be 8 bytes long/;
}

sub htpasswdMD5croakOnBadSalt
{
	local $@;
	eval { htpasswd($data, undef, 'foo#bar%', 'md5') };
	$@ && $@ =~ m%The salt must be a string in the alphabet "./0-9A-Za-z"%;
}

sub htpasswdMD5returnExpectedHashWithSalt
{
	my $hash = htpasswd($data, undef, $salt8Bytes, 'md5');
	$hash eq $htpasswdMD5hash;
}

sub htpasswdCryptCroakOnBadSaltLength
{
	local $@;

	eval { htpasswd($data, undef, 'f', 'crypt') };
	my $ret1 = $@;
	undef $@;

	eval { htpasswd($data, undef, 'foo', 'crypt') };
	my $ret2 = $@;
	undef $@;

	$ret1 && $ret1 =~ /The salt length must be 2 bytes long/ &&
	$ret2 && $ret2 =~ /The salt length must be 2 bytes long/;
}

sub htpasswdCryptCroakOnBadSalt
{
	local $@;
	eval { htpasswd($data, undef, 'f%', 'crypt') };
	$@ && $@ =~ m%The salt must be a string in the alphabet "./0-9A-Za-z"%;
}

sub htpasswdCryptreturnExpectedHashWithSalt
{
	my $hash = htpasswd($data, undef, $salt2Bytes, 'crypt');
	$hash eq $htpasswdCryptHash;
}

sub htpasswdMD5returnExpectedHashWith8bitCharacter
{
	my $data = $data . chr(128);
	my $hash = htpasswd($data, undef, $salt8Bytes, 'md5');
	$hash eq '$apr1$12345678$jfOZlcRz4A7AlodxzmxOE1';
}

sub htpasswdCryptreturnExpectedHashWith8bitCharacter
{
	my $data = $data . chr(128);
	my $hash = htpasswd($data, undef, $salt2Bytes, 'crypt');
	$hash eq '126D8rSh5sjUE';
}

sub htpasswdSHA1returnExpectedHashWith8bitCharacter
{
	my $data = $data . chr(128);
	my $hash = htpasswd($data, undef, undef, 'sha1');
	$hash eq '{SHA}kHlMOJM25c8L7Q4gfioFJXzKURc=';
}

sub hashEqualResults
{
	hashEqual($md5Hash, $md5Hash) && ! hashEqual($md5Hash, $sha256Hash) && ! hashEqual($md5Hash, undef);
}

sub verifyReturnExpectedResults
{
	verify($data, $md5Hash) && ! verify($data, substr($md5Hash, 0, -1)) &&
	verify($data, $sha256Hash) && ! verify($data, substr($sha256Hash, 0,  -1)) &&
	verify($data, $sha512Hash) && ! verify($data, substr($sha512Hash, 0, -1)) &&
	verify($data, $bcryptHash) && ! verify($data, substr($bcryptHash, 0, -1)) &&
	verify($data, $htpasswdMD5hash) && ! verify($data, substr($htpasswdMD5hash, 0, -1)) &&
	verify($data, $htpasswdCryptHash) && ! verify($data, substr($htpasswdCryptHash, 0, -1)) &&
	verify($data, $htpasswdSHA1hash) && ! verify($data, substr($htpasswdSHA1hash, 0, -1));
}

sub encryptBlowfishCBCreturnExpectedEncryptedData
{
	my $encData = encryptBlowfishCBC($key56Bytes, $iv8Bytes, $data);
	$encData eq $blowfishEncData;
}

sub decryptBlowfishCBCreturnExpectedDecryptedData
{
	my $decData = decryptBlowfishCBC($key56Bytes, $iv8Bytes, $blowfishEncData);
	$decData eq $data;
}

sub encryptRijndaelCBCreturnExpectedEncryptedData
{
	my $encData = encryptRijndaelCBC($key32Bytes, $iv16Bytes, $data);
	$encData eq $rijndaelEncData;
}

sub decryptRijndaelCBCreturnExpectedDecryptedData
{
	my $decData = decryptRijndaelCBC($key32Bytes, $iv16Bytes, $rijndaelEncData);
	$decData eq $data;
}

sub runUnitTests
{
	plan tests => 44;  # Number of tests planned for execution

	if(require_ok('iMSCP::Crypt')) {
		iMSCP::Crypt->import(qw(
			randomStr md5 sha256 sha512 bcrypt htpasswd verify hashEqual encryptBlowfishCBC decryptBlowfishCBC
			encryptRijndaelCBC decryptRijndaelCBC
		));

		eval {
			# iMSCP::Crypt::randomStr() tests
			ok randomStrCroakOnBadLengthParameter, 'randomStr() croak on bad length parameter';
			ok randomStrReturnExpectedStringLength, 'randomStr() return expected string length';
			ok randomStrReturnExpectedBase64String, 'randomStr() return expected base64 string';
			ok randomStrReturnExpectedStringWithCharlist, 'randomStr() return expected string with charlist';

			# iMSCP::Crypt::md5() tests
			ok md5ReturnExpectedHashWithRandomSalt, 'md5() return expected hash with random salt';
			ok md5CroakOnBadSaltLength, 'md5() croak on bad salt length';
			ok md5ReturnExpectedHashWithSalt, 'md5() return expected hash with salt';
			ok md5ReturnExpectedHashWith8bitCharacter, 'md5() return expected hash with 8 bit character';

			# iMSCP::Crypt::sha256 tests
			ok sha256ReturnExpectedHashWithRandomSalt, 'sha256() return expected hash with random salt';
			ok sha256CroakOnBadRoundsParameter, 'sha256() croak on bad rounds parameter';
			ok sha256CroakOnBadSaltLength, 'sha256() croak on bad salt length';
			ok sha256ReturnExpectedHashWithSalt, 'sha256() return expected hash with salt';
			ok sha256ReturnExpectedHashWith8bitCharacter, 'sha256() return expected hash with 8 bit character';

			# iMSCP::Crypt::sha512 tests
			ok sha512ReturnExpectedHashWithRandomSalt, 'sha512() return expected hash with random salt';
			ok sha512CroakOnBadRoundsParameter, 'sha512() croak on bad rounds parameter';
			ok sha512CroakOnBadSaltLength, 'sha512() croak on bad salt length';
			ok sha512ReturnExpectedHashWithSalt, 'sha512() return expected hash with salt';
			ok sha512ReturnExpectedHashWith8bitCharacter, 'sha512() return expected hash with 8 bit character';

			# iMSCP::Crypt::bcrypt tests
			ok bcryptReturnExpectedHashWithRandomSalt, 'bcrypt() return expected hash with random salt';
			ok bcryptCroakOnBadCostParameter, 'bcrypt() croak on bad cost parameter';
			ok bcryptCroakOnBadSaltLength, 'bcrypt() croak on bad salt length';
			ok bcryptReturnExpectedHashWithSalt, 'bcrypt() return expected hash with salt';
			ok bcryptReturnExpectedHashWith8bitCharacter, 'bcrypt() return expected hash with 8 bit character';

			# iMSCP::Crypt::htpasswd tests
			ok htpasswdCroakOnBadFormat, 'htpasswd() croak on bad format';
			ok htpasswdReturnExpectedHashWithDefaultFormat, 'htpasswd() return expected hash with default format';
			ok htpasswdMD5returnExpectedHashWithRandomSalt, 'htpasswd() return expected hash with md5 (APR1) format and random salt';
			ok htpasswdCryptReturnExpectedHashWithRandomSalt, 'htpasswd() return expected hash with crypt format and random salt';
			ok htpasswdSHA1returnExpectedHash, 'htpasswd() return expected hash with sha1 format';
			ok htpasswdMD5croakOnBadSaltLength, 'htpasswd() croak on bad salt length with md5 (APR1) format';
			ok htpasswdMD5croakOnBadSalt, 'htpasswd() croak on bad salt with md5 (APR1) format';
			ok htpasswdMD5returnExpectedHashWithSalt, 'htpasswd() return expected hash with md5 (APR1) format and salt';
			ok htpasswdCryptCroakOnBadSaltLength, 'htpasswd() croak on bad salt length with crypt format';
			ok htpasswdCryptCroakOnBadSalt, 'htpasswd() croak on bad salt with crypt format';
			ok htpasswdCryptreturnExpectedHashWithSalt, 'htpasswd() return expected hash with crypt format and salt';
			ok htpasswdMD5returnExpectedHashWith8bitCharacter, 'htpasswd() return expected hash with md5 (APR1) format and 8 bit character';
			ok htpasswdCryptreturnExpectedHashWith8bitCharacter, 'htpasswd() return expected hash with crypt format and 8 bit character';
			ok htpasswdSHA1returnExpectedHashWith8bitCharacter, 'htpasswd() return expected hash with sha1 format and 8 bit character';

			# iMSCP::Crypt::hashEqual tests
			ok hashEqualResults, 'hashEqual() return expected results';

			# iMSCP::Crypt::verify tests
			ok verifyReturnExpectedResults, 'verify() return expected results';

			# iMSCP::Crypt::encryptBlowfishCBC tests
			ok encryptBlowfishCBCreturnExpectedEncryptedData, 'encryptBlowfishCBC() return expected encrypted data';

			# iMSCP::Crypt::decryptBlowfishCBC tests
			ok decryptBlowfishCBCreturnExpectedDecryptedData, 'decryptBlowfishCBC() return expected decrypted data';

			# iMSCP::Crypt::encryptRijndaelCBC tests
			ok encryptRijndaelCBCreturnExpectedEncryptedData, 'encryptRijndaelCBC() return expected encrypted data';

			# iMSCP::Crypt::decryptRijndaelCBC tests
			ok decryptRijndaelCBCreturnExpectedDecryptedData, 'decryptRijndaelCBC() return expeced descrypted data';
		};

		diag sprintf('A test failed unexpectedly: %s', $@) if $@;
	}
}

1;
__END__
