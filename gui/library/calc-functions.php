<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * @param  $crnt
 * @param  $max
 * @param  $bars_max
 * @return array
 */
function calc_bars($crnt, $max, $bars_max)
{
	if ($max != 0) {
		$percent_usage = (100 * $crnt) / $max;
	} else {
		$percent_usage = 0;
	}

	$bars = ($percent_usage * $bars_max) / 100;

	if ($bars > $bars_max) {
		$bars = $bars_max;
	}

	return array(
		sprintf("%.2f", $percent_usage),
		sprintf("%d", $bars)
	);
}

/**
 * Turns byte counts to human readable format.
 *
 * If you feel like a hard-drive manufacturer, you can start counting bytes by power
 * of 1000 (instead of the generous 1024). Just set power to 1000.
 *
 * But if you are a floppy disk manufacturer and want to start counting in units of
 * 1024 (for your "1.44 MB" disks ?) let the default value for power.
 *
 * The units for power 1000 are: ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 *
 * Those for power 1024 are: ('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB')
 *
 * with the horrible names: bytes, kibibytes, mebibytes, etc.
 *
 * @see http://physics.nist.gov/cuu/Units/binary.html
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception if power or unit value is unknown
 * @param int|float $bytes Bytes value to convert
 * @param string $unit OPTIONAL Unit to calculate to
 * @param int $decimals OPTIONAL Number of decimal to be show
 * @param int $power OPTIONAL Power to use for conversion (1024 or 1000)
 * @return string
 */
function bytesHuman($bytes, $unit = null, $decimals = 2, $power = 1024)
{
	if ($power == 1000) {
		$units = array(
			'B' => 0, 'kB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8
		);
	} elseif ($power == 1024) {
		$units = array(
			'B' => 0, 'kiB' => 1, 'MiB' => 2, 'GiB' => 3, 'TiB' => 4, 'PiB' => 5, 'EiB' => 6, 'ZiB' => 7, 'YiB' => 8
		);
	} else {
		throw new iMSCP_Exception('Unknown power value');
	}

	$value = 0;

	if ($bytes > 0) {
		if (!array_key_exists($unit, $units)) {
			if(null === $unit) {
				$pow = floor(log($bytes) / log($power));
				$unit = array_search($pow, $units);
			} else {
				throw new iMSCP_Exception('Unknown unit value');
			}
		}

		$value = ($bytes / pow($power, floor($units[$unit])));
	} else {
		$unit = 'B';
	}

	// If decimals is not numeric or decimals is less than 0
	// then set default value
	if (!is_numeric($decimals) || $decimals < 0) {
		$decimals = 2;
	}

	// units Translation
	switch ($unit) {
		case 'B':
			$unit = tr('B');
			break;
		case 'kB':
			$unit = tr('kB');
			break;
		case 'kiB':
			$unit = tr('kiB');
			break;
		case 'MB':
			$unit = tr('MB');
			break;
		case 'MiB':
			$unit = tr('MiB');
			break;
		case 'GB':
			$unit = tr('GB');
			break;
		case 'GiB':
			$unit = tr('GiB');
			break;
		case 'TB':
			$unit = tr('TB');
			break;
		case 'TiB':
			$unit = tr('TiB');
			break;
		case 'PB':
			$unit = tr('PB');
			break;
		case 'PiB':
			$unit = tr('PiB');
			break;
		case 'EB':
			$unit = tr('EB');
			break;
		case 'EiB':
			$unit = tr('EiB');
			break;
		case 'ZB':
			$unit = tr('ZB');
			break;
		case 'ZiB':
			$unit = tr('ZiB');
			break;
		case 'YB':
			$unit = tr('YB');
			break;
		case 'YiB':
			$unit = tr('YiB');
			break;
	}

	return sprintf('%.' . $decimals . 'f ' . $unit, $value);
}

/**
 * Humanize a mebibyte value.
 *
 * @author Laurent Declercs <l.declercq@nuxwin.com>
 * @param int $value mebibyte value
 * @param string $unit OPTIONAL Unit to calculate to ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 * @return string
 */
function mebibyteHuman($value, $unit = null)
{
	return bytesHuman($value * 1048576, $unit);
}

/**
 * Translates '-1', 'no', 'yes', '0' or mebibyte value string into human readable string.
 *
 * @param int $value variable to be translated
 * @param bool $autosize calculate value in different unit (default false)
 * @param string $to OPTIONAL Unit to calclulate to ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 * @return String
 */
function translate_limit_value($value, $autosize = false, $to = null)
{

	$trEnabled = '<span style="color:green">' . tr('Enabled') . '</span>';
	$trDisabled = '<span style="color:red">' . tr('Disabled') . '</span>';

	switch ($value) {
		case '-1':
			return tr('Disabled');
		case  '0':
			return tr('Unlimited');
		case '_yes_':
		case 'yes':
			return $trEnabled;
		case '_no_':
		case 'no':
			return $trDisabled;
		case '_full_':
			return tr('Domain and SQL databases');
		case '_dmn_':
			return tr('Web files only');
		case '_sql_':
			return tr('SQL databases only');
		default:
			return (!$autosize) ? $value : mebibyteHuman($value, $to);
	}
}

/**
 * Generates a random salt for password using the best available algorithm.
 *
 * Note: Only algorithms present in the mainline glibc >= 2.7 (Debian) are supported (SHA512, SHA256, MD5 and DES)
 *
 * @throws iMSCP_Exception in case no encryption algorithm is available
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return string Random salt
 */
function generateRandomSalt()
{
	/*if(defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH) {
		$saltLength = 21;
		if(version_compare(PHP_VERSION, '3.5.7') == -1) {
			$salt = '$2a$07$';
		} else {
			$salt = array_rand(array('$2x$07$' =>'', '$2y$07$' => ''));
		}
	} else*/
	if (defined('CRYPT_SHA512') && CRYPT_SHA512) {
		$saltLength = 16;
		$salt = '$6$rounds=' . mt_rand(1500, 5000) . '$';
	} elseif (defined('CRYPT_SHA256') && CRYPT_SHA256) {
		$saltLength = 16;
		$salt = '$5$rounds=' . mt_rand(1500, 5000) . '$';
	} elseif (defined('CRYPT_MD5') && CRYPT_MD5) {
		$saltLength = 8;
		$salt = '$1$';
	} elseif (defined('CRYPT_STD_DES') && CRYPT_STD_DES) {
		$saltLength = 2;
		$salt = '';
	} else {
		throw new iMSCP_Exception('No encryption algorithm available.');
	}

	#if ($saltLength > 2  && $saltLength < 21) {
	if ($saltLength > 2) {
		$chars = array_merge(range(0x21, 0x7e));
	} else {
		$chars = array_merge(range(0x2E, 0x2F), range(0x30, 0x39), range(0x41, 0x5a), range(0x61, 0x7a));
	}

	for ($i = 0; $i < $saltLength; $i++) {
		$salt .= chr($chars[array_rand($chars)]);
	}

	return $salt;
}

/**
 * Encrypts the given password with salt.
 *
 * @param string $password the password in clear text
 * @param string $salt OPTIONAL Salt to use
 * @return string the password encrypted with salt
 */
function cryptPasswordWithSalt($password, $salt = '')
{
	return crypt($password, ($salt) ? $salt : generateRandomSalt());
}

/**
 * Generates random password of size specified in Config Var 'PASSWD_CHARS'
 *
 * @return String password
 */
function _passgen()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$passwordLength = isset($cfg->PASSWD_CHARS) ? $cfg->PASSWD_CHARS : 6;
	$password = '';

	for ($i = 0; $i <= $passwordLength; $i++) {
		do {
			$z = mt_rand(42, 123);
		} while ($z >= 91 && $z <= 96);

		$password .= chr($z);
	}

	return $password;
}

/**
 * Generates random password matching the checkPasswordSyntax() criteria.
 *
 * @see _passgen()
 * @return String password
 */
function passgen()
{
	$password = null;

	while ($password == null || ! checkPasswordSyntax($password, '', true)) {
		$password = _passgen();
	}

	return $password;
}

/**
 * Return UNIX timestamp representing first day of $month for $year.
 *
 * @param int $month OPTIONAL a month
 * @param int $year OPTIONAL A year (two or 4 digits, whatever)
 * @return int
 */
function getFirstDayOfMonth($month = null, $year = null)
{
	$month = $month ? : date('m');
	$year = $year ? : date('y');

	return mktime(0, 0, 0, $month, 1, $year);

}

/**
 * Return UNIX timestamp representing last day of month for $year.
 *
 * @param int $month OPTIONAL a month
 * @param int $year OPTIONAL A year (two or 4 digits, whatever)
 * @return int
 */
function getLastDayOfMonth($month = null, $year = null)
{
	$month = $month ? : date('m');
	$year = $year ? : date('y');

	return mktime(1, 0, 0, $month + 1, 0, $year);
}
