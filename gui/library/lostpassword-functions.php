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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP  - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2014 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/**
 * Checks if the GD library is loaded.
 *
 * @return bool TRUE if loaded, FALSE otherwise
 */
function check_gd(){
	return function_exists('imagecreatetruecolor');
}

/**
 * Checks if a captcha font file exists.
 *
 * @return bool TRUE if the file exists, FALSE otherwise
 */
function captcha_fontfile_exists(){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	return file_exists($cfg->LOSTPASSWORD_CAPTCHA_FONT);
}

/**
 * Create captcha image.
 *
 * @throws iMSCP_Exception
 * @param  $strSessionVar
 * @return void
 */
function createImage($strSessionVar){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$rgBgColor = $cfg->LOSTPASSWORD_CAPTCHA_BGCOLOR;
	$rgTextColor = $cfg->LOSTPASSWORD_CAPTCHA_TEXTCOLOR;
	$x = $cfg->LOSTPASSWORD_CAPTCHA_WIDTH;
	$y = $cfg->LOSTPASSWORD_CAPTCHA_HEIGHT;
	$font = $cfg->LOSTPASSWORD_CAPTCHA_FONT;

	$iRandVal = strRandom(10, $strSessionVar);

	if (!($image = imagecreate($x, $y))) {
		throw new iMSCP_Exception('Cannot initialize new GD image stream.');
	}

	imagecolorallocate($image, $rgBgColor[0], $rgBgColor[1], $rgBgColor[2]);

	$textColor = imagecolorallocate($image, $rgTextColor[0], $rgTextColor[1],
									$rgTextColor[2]);
	$white = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);

	imagettftext($image, 15, 0, 80, 22, $textColor, $font, $iRandVal);

	// Some obfuscation
	for ($i = 0; $i < 5; $i++) {
		$x1 = mt_rand(0, $x - 1);
		$y1 = mt_rand(0, round($y / 10, 0));
		$x2 = mt_rand(0, round($x / 10, 0));
		$y2 = mt_rand(0, $y - 1);

		imageline($image, $x1, $y1, $x2, $y2, $white);

		$x1 = mt_rand(0, $x - 1);
		$y1 = $y - mt_rand(1, round($y / 10, 0));
		$x2 = $x - mt_rand(1, round($x / 10, 0));
		$y2 = mt_rand(0, $y - 1);

		imageline($image, $x1, $y1, $x2, $y2, $white);
	}

	// send Header
	header('Content-type: image/png');

	// create and send PNG image
	imagepng($image);

	// destroy image from server
	imagedestroy($image);
}

/**
 * Generate random string.
 *
 * @param int $length Desired random string length
 * @param  string $strSessionVar
 * @return string A random string
 */
function strRandom($length, $strSessionVar){
	$str = '';

	while (strlen($str) < $length) {
		$random = mt_rand(48, 122);

		if (preg_match('/[2-47-9A-HKMNPRTWUYa-hkmnp-rtwuy]/', chr($random))) {
			$str .= chr($random);
		}
	}

	$_SESSION[$strSessionVar] = $str;

	return $_SESSION[$strSessionVar];
}

/**
 * Remove old keys.
 *
 * @param  $ttl
 * @return void
 */
function removeOldKeys($ttl){
	$boundary = date('Y-m-d H:i:s', time() - $ttl * 60);

	$query = '
		UPDATE
			`admin`
		SET
			`uniqkey` = NULL, `uniqkey_time` = NULL
		WHERE
			`uniqkey_time` < ?
	';
	exec_query($query, $boundary);
}

/**
 * Sets unique key.
 *
 * @param  $adminName
 * @param  $uniqueKey
 * @return void
 */
function setUniqKey($adminName, $uniqueKey){
	$query = '
		UPDATE
			`admin`
		SET
			`uniqkey` = ?, `uniqkey_time` = ?
		WHERE
			`admin_name` = ?
	';
	exec_query($query, array($uniqueKey, date('Y-m-d H:i:s', time()), $adminName));
}

/**
 * Set password
 *
 * @param  $uniqueKey
 * @param  $userPassword
 * @return void
 */
function setPassword($uniqueKey, $userPassword) {
	if ($uniqueKey == '') {
		exit;
	}

	$query = 'UPDATE `admin` SET `admin_pass` = ? WHERE `uniqkey` = ?';
	exec_query($query, array(cryptPasswordWithSalt($userPassword), $uniqueKey));
}

/**
 * Checks for unique key existence.
 *
 * @param  $uniqueKey
 * @return bool TRUE if the key exists, FALSE otherwise
 */
function uniqueKeyExists($uniqueKey){
	$query = 'SELECT `uniqkey` FROM `admin` WHERE `uniqkey` = ?';
	$stmt = exec_query($query, $uniqueKey);

	return (bool) $stmt->recordCount();
}


/**
 * generate unique key
 *
 * @return string Unique key
 * @todo use more secure hash algorithm (see PHP mcrypt extension)
 */
function uniqkeygen(){
	$uniqueKey = '';

	while ((uniqueKeyExists($uniqueKey)) || (!$uniqueKey)) {
		$uniqueKey = md5(uniqid(mt_rand()));
	}

	return $uniqueKey;
}

/**
 * Send password
 *
 * @param  $uniqueKey
 * @return bool TRUE when password was sended, FALSE otherwise
 */
function sendPassword($uniqueKey){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = '
		SELECT
			`admin_name`, `created_by`, `fname`, `lname`, `email`
		FROM
			`admin`
		WHERE
			`uniqkey` = ?
	';

	$stmt = exec_query($query, $uniqueKey);

	if ($stmt->recordCount()) {
		$adminName = $stmt->fields['admin_name'];
		$createdBy = $stmt->fields['created_by'];
		$adminFirstName = $stmt->fields['fname'];
		$adminLastName = $stmt->fields['lname'];
		$to = $stmt->fields['email'];

		$userPassword = passgen();
		setPassword($uniqueKey, $userPassword);
		write_log('Lostpassword: ' . $adminName . ': password updated', E_USER_NOTICE);

		$query = '
			UPDATE
				`admin`
			SET
				`uniqkey` = ?, `uniqkey_time` = ?
			WHERE
				`uniqkey` = ?
		';
		exec_query($query, array('', '', $uniqueKey));

		if ($createdBy == 0) {
			$createdBy = 1;
		}

		$data = get_lostpassword_password_email($createdBy);

		$fromName = $data['sender_name'];
		$fromEmail = $data['sender_email'];
		$subject = $data['subject'];
		$message = $data['message'];

		$baseVhost = $cfg->BASE_SERVER_VHOST;
		$baseVhostPrefix = $cfg->BASE_SERVER_VHOST_PREFIX;

		if ($fromName) {
			$from = '"' . $fromName . '" <' . $fromEmail . '>';
		} else {
			$from = $fromEmail;
		}

		$search = array();
		$replace = array();

		$search [] = '{USERNAME}';
		$replace[] = $adminName;
		$search [] = '{NAME}';
		$replace[] = $adminFirstName . " " . $adminLastName;
		$search [] = '{PASSWORD}';
		$replace[] = $userPassword;
		$search [] = '{BASE_SERVER_VHOST}';
		$replace[] = $baseVhost;
		$search [] = '{BASE_SERVER_VHOST_PREFIX}';
		$replace[] = $baseVhostPrefix;

		$subject = str_replace($search, $replace, $subject);
		$message = str_replace($search, $replace, $message);

		$headers = 'From: ' . $from . "\n";
		$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\n";
		$headers .= "Content-Transfer-Encoding: 7bit\n";
		$headers .= 'X-Mailer: i-MSCP mailer';

		$mailResult = mail($to, $subject, $message, $headers);
		$mailStatus = ($mailResult) ? 'OK' : 'NOT OK';

		$from = tohtml($from);

		write_log("Lostpassword activated: To: |$to|, From: |$from|, Status: |$mailStatus| !", E_USER_NOTICE);

		return true;
	}

	return false;
}

/**
 * Request password.
 *
 * @param  $adminName
 * @return bool TRUE on success, FALSE otherwise
 */
function requestPassword($adminName){
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = 'SELECT `created_by`, `fname`, `lname`, `email` FROM `admin` WHERE `admin_name` = ?';
	$stmt = exec_query($query, $adminName);

	if (!$stmt->rowCount()) {
		return false;
	}

	$createdBy = $stmt->fields['created_by'];
	$adminFirstName = $stmt->fields['fname'];
	$adminLastName = $stmt->fields['lname'];
	$to = $stmt->fields['email'];

	$uniqueKey = uniqkeygen();

	setUniqKey($adminName, $uniqueKey);

	write_log('Lostpassword: ' . $adminName . ': uniqkey created', E_USER_NOTICE);

	if ($createdBy == 0) {
		$createdBy = 1;
	}

	$data = get_lostpassword_activation_email($createdBy);
	$fromName = $data['sender_name'];
	$fromEmail = $data['sender_email'];
	$subject = $data['subject'];
	$message = $data['message'];
	$baseVhost = $cfg->BASE_SERVER_VHOST;
	$baseVhostPrefix = $cfg->BASE_SERVER_VHOST_PREFIX;

	if ($fromName) {
		$from =  encode_mime_header($fromName) . " <$fromEmail>";
	} else {
		$from = $fromEmail;
	}

	$protocol = isset($_SERVER['https']) ? 'https' : 'http';
	$link = $protocol . '://' . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '?key=' . $uniqueKey;

	$search		= array();
	$replace	= array();

	$search [] = '{USERNAME}';
	$replace[] = $adminName;
	$search [] = '{NAME}';
	$replace[] = "$adminFirstName $adminLastName";
	$search [] = '{LINK}';
	$replace[] = $link;
	$search [] = '{BASE_SERVER_VHOST}';
	$replace[] = $baseVhost;
	$search [] = '{BASE_SERVER_VHOST_PREFIX}';
	$replace[] = $baseVhostPrefix;

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: 8bit\r\n";
	$headers .= 'X-Mailer: i-MSCP Mailer';

	$mailResult = mail($to, encode_mime_header($subject), $message, $headers, "-f $fromEmail");

	$mailStatus = ($mailResult) ? 'OK' : 'NOT OK';

	$from = tohtml($from);

	write_log("Lostpassword send: To: |$to|, From: |$from|, Status: |$mailStatus| !", E_USER_NOTICE);

	return true;
}
