<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2009 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

function calc_bars($crnt, $max, $bars_max) {
	if($max != 0) {
		$percent_usage = (100*$crnt)/$max;
	} else {
		$percent_usage = 0;
	}

	$bars = ($percent_usage * $bars_max)/100;

	if ($bars > $bars_max) $bars = $bars_max;

	return array(
		sprintf("%.2f", $percent_usage),
		sprintf("%d", $bars)
	);
}

function sizeit($bytes, $from = 'B') {

	switch ($from) {
		case 'PB':
			$bytes = $bytes * pow(1024, 5);
			break;
		case 'TB':
			$bytes = $bytes * pow(1024, 4);
			break;
		case 'GB':
			$bytes = $bytes * pow(1024, 3);
			break;
		case 'MB':
			$bytes = $bytes * pow(1024, 2);
			break;
		case 'KB':
			$bytes = $bytes * pow(1024, 1);
			break;
		case 'B':
			break;
		default:
			die('FIXME: ' . __FILE__ . ':' . __LINE__);
			break;
	}

	if ($bytes == '' || $bytes < 0 ) {
		$bytes = 0;
	}

	if ($bytes > pow(1024, 5)) {
		$bytes = $bytes/pow(1024, 5);
		$ret   = tr('%.2f PB', $bytes);
	} else if ($bytes > pow(1024, 4)) {
		$bytes = $bytes/pow(1024, 4);
		$ret   = tr('%.2f TB', $bytes);
	} else if ($bytes > pow(1024, 3)) {
		$bytes = $bytes/pow(1024, 3);
		$ret   = tr('%.2f GB', $bytes);
	} else if ($bytes > pow(1024, 2) ) {
		$bytes = $bytes/pow(1024, 2);
		$ret   = tr('%.2f MB', $bytes);
	} else if ($bytes > pow(1024, 1)) {
		$bytes = $bytes/pow(1024, 1);
		$ret   = tr('%.2f KB', $bytes);
	} else {
		$ret   = tr('%d B', $bytes);
	}

	return $ret;
}

//
// some password management.
//

/**
 * Generates a random salt for passwords.
 *
 * @param int $min minimum ASCII char
 * @param int $max maximum ASCII char
 * @return string Salt for password
 */
function generate_rand_salt($min = 46, $max = 126) {
	if (CRYPT_BLOWFISH == 2) {
		$length	= 13;
		$pre	= '$2$';
	} elseif (CRYPT_MD5 == 1) {
		$length	= 9;
		$pre	= '$1$';
	} elseif (CRYPT_EXT_DES == 1) {
		$length	= 9;
		$pre	= '';
	} elseif (CRYPT_STD_DES == 1) {
		$length	= 2;
		$pre	= '';
	}

	$salt = $pre;

	for($i = 0; $i < $length; $i++) {
		$salt .= chr(mt_rand($min, $max));
	}

	return $salt;
}

function get_salt_from($data) {
	$salt = substr($data, 0, 2);
	return $salt;
}

function crypt_user_pass($data) {
	$res = md5($data);
	return $res;
}

/**
 * Encrypts the FTP user password.
 *
 * @param string $data the password in clear text
 * @return string the password encrypted with salt
 */
function crypt_user_pass_with_salt($data) {
	$res = crypt($data, generate_rand_salt());
	return $res;
}

function check_user_pass($crdata, $data ) {
	$salt = get_salt_from($crdata);
	$udata = crypt($data, $salt);
	return ($udata == $crdata);
}

function _passgen() {
	$pw = '';

	for ($i = 0; $i <= Config::get('PASSWD_CHARS'); $i++) {
		$z = 0;

		do {
			$z = mt_rand(42, 123);
		} while($z >= 91 && $z <= 96);
		$pw .= chr($z);
	}
	return $pw;
}

function passgen() {
	$pw = null;

	while ($pw == null || !chk_password($pw, 50, "/[<>]/")) {
		$pw = _passgen();
	}

	return $pw;
}

function translate_limit_value($value, $autosize = false) {
	if ($value == -1) {
		return tr('disabled');
	} else if ($value == 0) {
		return tr('unlimited');
	} else {
		return (!$autosize) ? $value : sizeit($value, 'MB');
	}
}

?>
