<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @link		http://isp-control.net
 * @author		ispCP Team
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

function check_gd() {
	return function_exists('imagecreatetruecolor');
}

/**
 * @todo use file_exists in try-catch block
 */
function captcha_fontfile_exists() {
	return file_exists(Config::get('LOSTPASSWORD_CAPTCHA_FONT'));
}

function createImage($strSessionVar) {
	$rgBgColor = Config::get('LOSTPASSWORD_CAPTCHA_BGCOLOR');
	$rgTextColor = Config::get('LOSTPASSWORD_CAPTCHA_TEXTCOLOR');

	$x = Config::get('LOSTPASSWORD_CAPTCHA_WIDTH');
	$y = Config::get('LOSTPASSWORD_CAPTCHA_HEIGHT');

	$font = Config::get('LOSTPASSWORD_CAPTCHA_FONT');

	$iRandVal = strrand(8, $strSessionVar);

	$im = imagecreate($x, $y) or die("Cannot initialize new GD image stream.");

	$background_color = imagecolorallocate($im, $rgBgColor[0],
		$rgBgColor[1],
		$rgBgColor[2]);

	$text_color = imagecolorallocate($im, $rgTextColor[0],
		$rgTextColor[1],
		$rgTextColor[2]);

	$white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);

	imagettftext($im, 34, 0, 5, 50,
		$text_color,
		$font,
		$iRandVal);
	// some obfuscation
	for ($i = 0; $i < 3; $i++) {
		$x1 = mt_rand(0, $x - 1);

		$y1 = mt_rand(0, round($y / 10, 0));

		$x2 = mt_rand(0, round($x / 10, 0));

		$y2 = mt_rand(0, $y - 1);

		imageline($im, $x1, $y1, $x2, $y2, $white);

		$x1 = mt_rand(0, $x - 1);

		$y1 = $y - mt_rand(1, round($y / 10, 0));

		$x2 = $x - mt_rand(1, round($x / 10, 0));

		$y2 = mt_rand(0, $y - 1);

		imageline($im, $x1, $y1, $x2, $y2, $white);
	}
	// send Header
	header("Content-type: image/png");
	// create and send PNG image
	imagepng($im);
	// destroy image from server
	imagedestroy($im);
}

function strrand($length, $strSessionVar) {
	$str = "";

	while (strlen($str) < $length) {
		$random = mt_rand(48, 122);

		if (preg_match('/[2-47-9A-HKMNPRTWUYa-hkmnp-rtwuy]/', chr($random))) {
			$str .= chr($random);
		}
	}

	$_SESSION[$strSessionVar] = $str;

	return $_SESSION[$strSessionVar];
}

function removeOldKeys($ttl) {
	$sql = Database::getInstance();

	$boundary = date('Y-m-d H:i:s', time() - $ttl * 60);

	$query = <<<SQL_QUERY
		UPDATE
			`admin`
		SET
			`uniqkey` = NULL,
			`uniqkey_time` = NULL
		WHERE
			`uniqkey_time` < ?
SQL_QUERY;

	exec_query($sql, $query, array($boundary));
}

function setUniqKey($admin_name, $uniqkey) {
	$sql = Database::getInstance();

	$timestamp = date('Y-m-d H:i:s', time());

	$query = <<<SQL_QUERY
		UPDATE
			`admin`
		SET
			`uniqkey` = ?,
			`uniqkey_time` = ?
		WHERE
			`admin_name` = ?
SQL_QUERY;

	exec_query($sql, $query, array($uniqkey, $timestamp, $admin_name));
}

function setPassword($uniqkey, $upass) {
	$sql = Database::getInstance();

	if ($uniqkey == '') { exit; }

	$query = <<<SQL_QUERY
		UPDATE
			`admin`
		SET
			`admin_pass` = ?
		WHERE
			`uniqkey` = ?
SQL_QUERY;

	exec_query($sql, $query, array(crypt_user_pass($upass), $uniqkey));
}

function uniqkeyexists($uniqkey) {
	$sql = Database::getInstance();

	$query = <<<SQL_QUERY
		SELECT
			`uniqkey`
		FROM
			`admin`
		WHERE
			`uniqkey` = ?
SQL_QUERY;

	$res = exec_query($sql, $query, array($uniqkey));

	return ($res->RecordCount() != 0) ? true : false;
}

/**
 * @todo use more secure hash algorithm (see PHP mcrypt extension)
 */
function uniqkeygen() {
	$uniqkey = '';

	while ((uniqkeyexists($uniqkey)) || (!$uniqkey)) {
		$uniqkey = md5(uniqid(mt_rand()));
	}

	return $uniqkey;
}

function sendpassword($uniqkey) {
	$sql = Database::getInstance();

	$query = <<<SQL_QUERY
		SELECT
			`admin_name`, `created_by`, `fname`, `lname`, `email`
		FROM
			`admin`
		WHERE
			`uniqkey` = ?
SQL_QUERY;

	$res = exec_query($sql, $query, array($uniqkey));

	if ($res->RecordCount() == 1) {
		$admin_name = $res->fields['admin_name'];

		$created_by = $res->fields['created_by'];

		$admin_fname = $res->fields['fname'];

		$admin_lname = $res->fields['lname'];

		$to = $res->fields['email'];

		$upass = passgen();

		setPassword($uniqkey, $upass);

		write_log("Lostpassword: " . $admin_name . ": password updated");

		$query = <<<SQL_QUERY
			UPDATE
				`admin`
			SET
				`uniqkey` = ?,
				`uniqkey_time` = ?
			WHERE
				`uniqkey` = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array('', '', $uniqkey));

		if ($created_by == 0) $created_by = 1;

		$data = get_lostpassword_password_email($created_by);

		$from_name = $data['sender_name'];

		$from_email = $data['sender_email'];

		$subject = $data['subject'];

		$message = $data['message'];

		$base_vhost = Config::get('BASE_SERVER_VHOST');

		$base_vhost_prefix = Config::get('BASE_SERVER_VHOST_PREFIX');

		if ($from_name) {
			$from = "\"" . $from_name . "\" <" . $from_email . ">";
		} else {
			$from = $from_email;
		}

		$search = array();
		$replace = array();

		$search [] = '{USERNAME}';
		$replace[] = $admin_name;
		$search [] = '{NAME}';
		$replace[] = $admin_fname . " " . $admin_lname;
		$search [] = '{PASSWORD}';
		$replace[] = $upass;
		$search [] = '{BASE_SERVER_VHOST}';
		$replace[] = $base_vhost;
		$search [] = '{BASE_SERVER_VHOST_PREFIX}'; 
		$replace[] = $base_vhost_prefix;	
		
		$subject = str_replace($search, $replace, $subject);
		$message = str_replace($search, $replace, $message);

		$headers = "From: " . $from . "\n";

		$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";

		$headers .= "X-Mailer: ispCP lostpassword mailer";

		$mail_result = mail($to, $subject, $message, $headers);

		$mail_status = ($mail_result) ? 'OK' : 'NOT OK';

		write_log("Lostpassword activated: To: |$to|, From: |$from|, Status: |$mail_status| !", E_USER_NOTICE);

		return true;
	}

	return false;
}

function requestpassword($admin_name) {
	$sql = Database::getInstance();

	$query = <<<SQL_QUERY
		SELECT
			`created_by`, `fname`, `lname`, `email`
		FROM
			`admin`
		WHERE
			`admin_name` = ?
SQL_QUERY;

	$res = exec_query($sql, $query, array($admin_name));

	if ($res->RecordCount() == 0) {
		return false;
	}

	$created_by = $res->fields['created_by'];
	$admin_fname = $res->fields['fname'];
	$admin_lname = $res->fields['lname'];
	$to = $res->fields['email'];

	$uniqkey = uniqkeygen();

	setUniqKey($admin_name, $uniqkey);

	write_log("Lostpassword: " . $admin_name . ": uniqkey created", E_USER_NOTICE);

	if ($created_by == 0) { $created_by = 1; }

	$data = get_lostpassword_activation_email($created_by);

	$from_name = $data['sender_name'];
	$from_email = $data['sender_email'];
	$subject = $data['subject'];
	$message = $data['message'];

	$base_vhost = Config::get('BASE_SERVER_VHOST');
	$base_vhost_prefix = Config::get('BASE_SERVER_VHOST_PREFIX');

	if ($from_name) {
		$from = '"' . $from_name . "\" <" . $from_email . ">";
	} else {
		$from = $from_email;
	}

	$prot = isset($_SERVER['https']) ? 'https' : 'http';

	$link = $prot . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?key=" . $uniqkey;

	$search = array();
	$replace = array();

	$search [] = '{USERNAME}';
	$replace[] = $admin_name;
	$search [] = '{NAME}';
	$replace[] = $admin_fname . " " . $admin_lname;
	$search [] = '{LINK}';
	$replace[] = $link;
	$search [] = '{BASE_SERVER_VHOST}';
	$replace[] = $base_vhost;
	$search [] = '{BASE_SERVER_VHOST_PREFIX}';
	$replace[] = $base_vhost_prefix;

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$headers = "From: " . $from . "\n";

	$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n";

	$headers .= "X-Mailer: ispCP lostpassword mailer";

	$mail_result = mail($to, encode($subject), $message, $headers);

	$mail_status = ($mail_result) ? 'OK' : 'NOT OK';

	write_log("Lostpassword send: To: |$to|, From: |$from|, Status: |$mail_status| !", E_USER_NOTICE);

	return true;
}

?>
