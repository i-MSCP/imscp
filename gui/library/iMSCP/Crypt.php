<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace iMSCP;

/**
 * Class Crypt
 *
 * Library that provides functions for passwords hashing, verification and data encryption.
 *
 * @package iMSCP
 */
class Crypt
{
	const BASE64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	const ALPHA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	const ALPHA64_PERL_COMPAT = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

	/**
	 * Generates a secure random string
	 *
	 * @throws \InvalidArgumentException|\RuntimeException
	 * @param int $length Expected string length
	 * @param string $charList character list to use for string generation (default is Base 64 character set)
	 * @return string
	 */
	static public function randomStr($length, $charList = self::BASE64)
	{
		if (!extension_loaded('mcrypt')) {
			throw new \RuntimeException('Mcrypt extension is not available');
		}

		$length = (int)$length;
		if ($length <= 0) {
			throw new \InvalidArgumentException('Length parameter value must be >= 1');
		}

		$listLen = strlen($charList);
		if ($listLen == 1) {
			return str_repeat($charList, $length);
		}

		$bytes = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
		$pos = 0;
		$str = '';

		for ($i = 0; $i < $length; $i++) {
			$pos = ($pos + ord($bytes[$i])) % $listLen;
			$str .= $charList[$pos];
		}

		return $str;
	}

	/**
	 * Create a hash of the given password using the MD5 algorithm
	 *
	 * @throws \InvalidArgumentException
	 * @param string $password The password to be hashed
	 * @param string $salt An optional salt string to base the hashing on
	 * @return string
	 * @deprecated As of 2012-6-7, this algorithm is "no longer considered safe" by its author. Use bcrypt instead.
	 */
	static public function md5($password, $salt = null)
	{
		$salt = (string)$salt;

		if ($salt !== '') {
			if (strlen($salt) < 8) {
				throw new \InvalidArgumentException('The salt length must be at least 8 bytes long');
			}
		} else {
			$salt = static::randomStr(8);
		}

		return crypt($password, '$1$' . $salt);
	}

	/**
	 * Create a hash of the given password using the SHA-256 algorithm
	 *
	 * @throws \InvalidArgumentException
	 * @param string $password Password to be hashed
	 * @param int $rounds A numeric value  used to indicate how many times the hashing loop should be executed
	 * @param string $salt An optional salt string to base the hashing on
	 * @return string
	 */
	static public function sha256($password, $rounds = 5000, $salt = null)
	{
		$rounds = (int)$rounds;
		$salt = (string)$salt;

		if ($rounds < 1000 || $rounds > 5000) {
			throw new \InvalidArgumentException('The rounds parameter must be in range 1000-5000');
		}

		$rounds = sprintf('%1$04d', $rounds);

		if ($salt !== '') {
			if (strlen($salt) < 16) {
				throw new \InvalidArgumentException('The salt length must be at least 16 bytes long');
			}
		} else {
			$salt = static::randomStr(16);
		}

		return crypt($password, '$5$rounds=' . $rounds . '$' . $salt);
	}

	/**
	 * Create a hash of the given password using the SHA-512 algorithm
	 *
	 * @throws \InvalidArgumentException
	 * @param string $password The password to be hashed
	 * @param int $rounds A numeric value  used to indicate how many times the hashing loop should be executed
	 * @param string $salt An optional salt string to base the hashing on
	 * @return string
	 */
	static public function sha512($password, $rounds = 5000, $salt = null)
	{
		$rounds = (int)$rounds;
		$salt = (string)$salt;

		if ($rounds < 1000 || $rounds > 5000) {
			throw new \InvalidArgumentException('The rounds parameter must be in range 1000-5000');
		}

		$rounds = sprintf('%1$04d', $rounds);

		if ($salt !== '') {
			if (strlen($salt) < 16) {
				throw new \InvalidArgumentException('The salt length must be at least 16 bytes long');
			}
		} else {
			$salt = static::randomStr(16);
		}

		return crypt($password, '$6$rounds=' . $rounds . '$' . $salt);
	}

	/**
	 * Create a hash of the given password using the bcrypt algorithm
	 *
	 * @throws \InvalidArgumentException|\RuntimeException
	 * @param string $password The password to be hashed
	 * @param int $cost Base-2 logarithm of the iteration count
	 * @param string $salt An optional salt string to base the hashing on
	 * @return string
	 */
	static public function bcrypt($password, $cost = 10, $salt = null)
	{
		$cost = (int)$cost;
		$salt = (string)$salt;

		if ($cost < 4 || $cost > 31) {
			throw new \InvalidArgumentException('The cost parameter must be in range 04-31');
		}

		$cost = sprintf('%1$02d', $cost);

		if ($salt !== '') {
			if (strlen($salt) < 16) {
				throw new \InvalidArgumentException('The salt length must be at least 16 bytes long');
			}
		} else {
			$salt = static::randomStr(16);
		}

		# Perl Crypt::Eksblowfish::Bcrypt::en_base64 compatible base64 string
		$salt = substr(strtr(base64_encode($salt), static::BASE64, static::ALPHA64_PERL_COMPAT), 0, 22);
		$hash = crypt($password, '$2a$' . $cost . '$' . $salt);

		if (strlen($hash) < 13) {
			throw new \RuntimeException('Error during the bcrypt generation');
		}

		return $hash;
	}

	/**
	 * Create an htpasswd password hash of the given password using the given algorithm
	 *
	 * See http://httpd.apache.org/docs/2.4/misc/password_encryptions.html
	 *
	 * @throws \InvalidArgumentException
	 * @param string $password The password to be hashed
	 * @param int $cost Base-2 logarithm of the iteration count (only relevant for bcrypt format)
	 * @param string $salt An optional salt string to base the hashing on (only relevant for bcrypt, crypt and md5 formats)
	 * @param string $format Format in which the password must be hashed (bcrypt|crypt|md5|sha1) -  Default is md5 (APR1)
	 * @return string
	 */
	static public function htpasswd($password, $cost = null, $salt = null, $format = 'md5')
	{
		$salt = (string)$salt;

		switch ($format) {
			case 'bcrypt':
				return static::bcrypt($password, $cost, $salt);
			case 'crypt':
				if ($salt !== '') {
					if (strlen($salt) != 2) {
						throw new \InvalidArgumentException('The salt length must be 2 bytes long');
					}

					for ($i = 0; $i < 8; $i++) {
						if (strpos(static::ALPHA64, $salt[$i]) === false) {
							throw new \InvalidArgumentException(
								'The salt must be a string in the alphabet "./0-9A-Za-z"'
							);
						}
					}
				} else {
					$salt = static::randomStr(2, static::ALPHA64);
				}

				return crypt($password, $salt);
			case 'sha1':
				return '{SHA}' . base64_encode(sha1($password, true));
			case 'md5':
				return static::apr1Md5($password, $salt);
			default:
				throw new \InvalidArgumentException(sprintf(
					'The %s format is not valid. The supported formats are: %s', $format, 'bcrypt, crypt, md5, sha1'
				));
		}
	}

	/**
	 * Verify the given password against the given hash
	 *
	 * @throws \InvalidArgumentException
	 * @param string $password The password to be checked
	 * @param string $hash The hash to be checked against
	 * @return bool
	 */
	static public function verify($password, $hash)
	{
		if (substr($hash, 0, 5) === '{SHA}') { // htpasswd sha1 hashed paswords
			return static::hashEqual($hash, '{SHA}' . base64_encode(sha1($password, true)));
		}

		if (substr($hash, 0, 6) === '$apr1$') { // htpasswd md5 hashed paswords
			$token = explode('$', $hash);

			if (empty($token[2])) {
				throw new \InvalidArgumentException('APR1 password format is not valid');
			}

			return static::hashEqual($hash, static::apr1Md5($password, $token[2]));
		}

		return static::hashEqual($hash, crypt($password, $hash));
	}

	/**
	 * Timing attack safe string comparison
	 *
	 * @param string $knownString The string of known length to compare against
	 * @param string $userString The user-supplied string
	 * @return bool
	 */
	static public function hashEqual($knownString, $userString)
	{
		$knownString = (string)$knownString;
		$userString = (string)$userString;

		if (function_exists('hash_equals')) {
			return hash_equals($knownString, $userString);
		}

		$lenExpected = strlen($knownString);
		$lenActual = strlen($userString);
		$len = min($lenExpected, $lenActual);
		$result = 0;

		for ($i = 0; $i < $len; $i++) {
			$result |= ord($knownString[$i]) ^ ord($userString[$i]);
		}

		$result |= $lenExpected ^ $lenActual;

		return ($result === 0);
	}

	/**
	 * Encrypt the given data in in CBC mode using the Blowfish algorithm
	 *
	 * @param string $key Encryption key (56 bytes long)
	 * @param string $iv Initialization vector (8 bytes long)
	 * @param string $data Data to encrypt
	 * @return string A base64 string representing encrypted data
	 */
	static function encryptBlowfishCBC($key, $iv, $data)
	{
		return static::encryptCBC(MCRYPT_BLOWFISH, $key, $iv, $data);
	}

	/**
	 * Decrypt the given data in CBC mode using Blowfish algorithm
	 *
	 * @param string $key Decryption key (56 bytes long)
	 * @param string $iv Initialization vector (8 bytes long)
	 * @param string $data A base64 encoded string representing encrypted data
	 * @return string
	 */
	static function decryptBlowfishCBC($key, $iv, $data)
	{
		return static::decryptCBC(MCRYPT_BLOWFISH, $key, $iv, $data);
	}

	/**
	 * Encrypt the given data in CBC mode using the AES (Rijndael) algorithm
	 *
	 * @param string $key Encryption key
	 * @param string $iv Initialization vector
	 * @param string $data Data to encrypt
	 * @return string A base64 encoded string representing encrypted data
	 */
	static function encryptRijndaelCBC($key, $iv, $data)
	{
		return static::encryptCBC(MCRYPT_RIJNDAEL_128, $key, $iv, $data);
	}

	/**
	 * Decrypt the given data in CBC mode using the AES (Rijndael) algorithm,
	 *
	 * @param string $key Decryption key
	 * @param string $iv Initialization vector
	 * @param string $data A base64 encoded string representing encrypted data
	 * @return string
	 */
	static function decryptRijndaelCBC($key, $iv, $data)
	{
		return static::decryptCBC(MCRYPT_RIJNDAEL_128, $key, $iv, $data);
	}

	/**
	 * Encrypt the given data in CBC mode using the given algorithm
	 *
	 * @throws \InvalidArgumentException
	 * @param int $algorithm Algorithm
	 * @param string $key Encryption key
	 * @param string $iv Initialization vector
	 * @param string $data Data to encrypt
	 * @return string A base64 encoded string representing encrypted data
	 */
	static protected function encryptCBC($algorithm, $key, $iv, $data)
	{
		if (extension_loaded('mcrypt')) {
			return base64_encode(mcrypt_encrypt($algorithm, $key, $data, MCRYPT_MODE_CBC, $iv));
		}

		throw new \RuntimeException('Mcrypt extension is not available');
	}

	/**
	 * Decrypt the given data in CBC mode using the given algorithm
	 *
	 * @throws \RuntimeException
	 * @param int $algorithm Algorithm
	 * @param string $key Decryption key
	 * @param string $iv Initialization vector
	 * @param string $data A base64 encoded string representing encrypted data
	 * @return string
	 */
	static protected function decryptCBC($algorithm, $key, $iv, $data)
	{
		if (extension_loaded('mcrypt')) {
			return mcrypt_decrypt($algorithm, $key, base64_decode($data), MCRYPT_MODE_CBC, $iv);
		}

		throw new \RuntimeException('Mcrypt extension is not available');
	}

	/**
	 * Convert a binary string using the "./0-9A-Za-z" alphabet
	 *
	 * @param  string $string String to be converted
	 * @return string
	 */
	static protected function toAlphabet64($string)
	{
		return strtr(strrev(substr(base64_encode($string), 2)), static::BASE64, static::ALPHA64);
	}

	/**
	 * APR1 MD5 algorithm (see http://svn.apache.org/viewvc/apr/apr/trunk/crypto/apr_md5.c?view=markup)
	 *
	 * @param string $password The password to be hashed
	 * @param null $salt Salt An optional salt string to base the hashing on
	 * @return string
	 */
	static protected function apr1Md5($password, $salt = null)
	{
		$salt = (string)$salt;

		if ($salt !== '') {
			if (strlen($salt) !== 8) {
				throw new \InvalidArgumentException('The salt for APR1 algorithm must be 8 characters long');
			}

			for ($i = 0; $i < 8; $i++) {
				if (strpos(static::ALPHA64, $salt[$i]) === false) {
					throw new \InvalidArgumentException('The salt must be a string in the alphabet "./0-9A-Za-z"');
				}
			}
		} else {
			$salt = static::randomStr(8, static::ALPHA64);
		}

		$len = strlen($password);
		$text = $password . '$apr1$' . $salt;
		$bin = pack('H32', md5($password . $salt . $password));

		for ($i = $len; $i > 0; $i -= 16) {
			$text .= substr($bin, 0, min(16, $i));
		}

		for ($i = $len; $i > 0; $i >>= 1) {
			$text .= ($i & 1) ? chr(0) : $password[0];
		}

		$bin = pack('H32', md5($text));

		for ($i = 0; $i < 1000; $i++) {
			$new = ($i & 1) ? $password : $bin;

			if ($i % 3) {
				$new .= $salt;
			}

			if ($i % 7) {
				$new .= $password;
			}

			$new .= ($i & 1) ? $bin : $password;
			$bin = pack('H32', md5($new));
		}

		$tmp = '';

		for ($i = 0; $i < 5; $i++) {
			$k = $i + 6;
			$j = $i + 12;

			if ($j == 16) {
				$j = 5;
			}

			$tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
		}

		return '$apr1$' . $salt . '$' . static::toAlphabet64(chr(0) . chr(0) . $bin[11] . $tmp);
	}
}
