<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/



function check_gd() {

	if (function_exists('imagecreatetruecolor'))

		return true;

	else

		return false;

}

function captcha_fontfile_exists() {

	global $cfg;

	if (file_exists($cfg['LOSTPASSWORD_CAPTCHA_FONT']))

		return true;

	else

		return false;

}

function createImage($strSessionVar) {

	global $cfg;

	$rgBgColor = $cfg['LOSTPASSWORD_CAPTCHA_BGCOLOR'];

	$rgTextColor = $cfg['LOSTPASSWORD_CAPTCHA_TEXTCOLOR'];

	$x = $cfg['LOSTPASSWORD_CAPTCHA_WIDTH'];

	$y = $cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'];

	$font = "../" . $cfg['LOSTPASSWORD_CAPTCHA_FONT'];

  	$iRandVal = strrand(8, $strSessionVar);

	$im = imagecreate($x,$y) or die("Cannot Initialize new GD image stream");

 	$background_color = imagecolorallocate($im, $rgBgColor[0],
                                              $rgBgColor[1],
                                              $rgBgColor[2]);

  	$text_color = imagecolorallocate($im,  $rgTextColor[0],
                                         $rgTextColor[1],
                                         $rgTextColor[2]);

	$white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);

  	imagettftext($im, 34, 0, 5, 50,
               $text_color,
               $font,
               $iRandVal);

	//some obfuscation
  	for ($i=0; $i<3; $i++) {

	  	$x1 = rand(0, $x - 1);

	    $y1 = rand(0, round($y / 10, 0));

	    $x2 = rand(0, round($x / 10, 0));

	    $y2 = rand(0, $y - 1);

	    imageline($im, $x1, $y1, $x2, $y2, $white);

	    $x1 = rand(0, $x - 1);

	    $y1 = $y - rand(1, round($y / 10, 0));

	    $x2 = $x - rand(1, round($x / 10, 0));

	    $y2 = rand(0, $y - 1);

	    imageline($im, $x1, $y1, $x2, $y2, $white);

	}

	// Header schicken
  header("Content-type: image/png");

  // PNG Bild erzeugen und senden
	imagepng($im);

  // Bild auf dem Server loeschen
  imagedestroy($im);

}

function strrand($length, $strSessionVar) {

	$str = "";

	while(strlen($str) < $length){

	  	$random = rand(48, 122);

  		if (ereg('[2-47-9A-HKMNPRTWUYa-hkmnp-rtwuy]', chr($random))) {

  			$str .= chr($random);

   		}

	}

	$_SESSION[$strSessionVar] = $str;

  return $_SESSION[$strSessionVar];

}

function removeOldKeys($ttl) {

	global $sql;

	$boundary = date('Y-m-d H:i:s', time() - $ttl * 60);

	$query = <<<SQL_QUERY
  					UPDATE
            	admin
						SET
							uniqkey = NULL,
							uniqkey_time = NULL
						WHERE
							uniqkey_time < ?
SQL_QUERY;

	exec_query($sql, $query, array($boundary));

}

function setUniqKey($admin_name, $uniqkey) {

	global $sql;

	$timestamp = date('Y-m-d H:i:s', time());

	$query = <<<SQL_QUERY
  					UPDATE
            	admin
            SET
              uniqkey = ?,
              uniqkey_time = ?
            WHERE
              admin_name = ?
SQL_QUERY;

	exec_query($sql, $query, array($uniqkey, $timestamp, $admin_name));

}

function setPassword($uniqkey, $upass) {

	global $sql;

	if ($uniqkey == '') exit;

	$query = <<<SQL_QUERY
           	UPDATE
              admin
            SET
              admin_pass = ?
            WHERE
              uniqkey = ?
SQL_QUERY;

	exec_query($sql, $query, array(crypt_user_pass($upass), $uniqkey));

}

function uniqkeyexists($uniqkey) {

	global $sql;

  $query = <<<SQL_QUERY
        		SELECT
            	uniqkey
        		FROM
            	admin
        		WHERE
            	uniqkey = ?
SQL_QUERY;

  $res = exec_query($sql, $query, array($uniqkey));

  if ( $res -> RecordCount() != 0 )

  	return true;

  else

  	return false;

}

function uniqkeygen() {

	$uniqkey = '';

	while ( (uniqkeyexists($uniqkey)) || (!$uniqkey)) {

		$uniqkey = md5(uniqid(rand()));

	}

	return $uniqkey;
}

function sendpassword($uniqkey) {

	global $sql, $cfg;

  	$query = <<<SQL_QUERY
        		SELECT
            	admin_name, created_by, fname, lname, email
        		FROM
            	admin
        		WHERE
            	uniqkey = ?
SQL_QUERY;

  	$res = exec_query($sql, $query, array($uniqkey));

	if ($res -> RecordCount() ==1 ) {

		$admin_name = $res -> fields['admin_name'];

		$created_by = $res -> fields['created_by'];

		$admin_fname = $res -> fields['fname'];

		$admin_lname = $res -> fields['lname'];

		$to = $res -> fields['email'];

		$upass = passgen();

		setPassword($uniqkey, $upass);

    	write_log("Lostpassword: " . $admin_name . ": password updated");

    	$query = <<<SQL_QUERY
            	UPDATE
              	admin
            	SET
              	uniqkey = ?,
              	uniqkey_time = ?
            	WHERE
              	uniqkey = ?
SQL_QUERY;

    	$rs = exec_query($sql, $query, array('', '', $uniqkey));

		if ($created_by == 0) $created_by = 1;

		$data = get_lostpassword_password_email($created_by);

		$from_name = $data['sender_name'];

		$from_email = $data['sender_email'];

    	$subject = $data['subject'];

   	 	$message = $data['message'];

		$base_vhost = $cfg['BASE_SERVER_VHOST'];

    	if ($from_name) {

       		$from = $from_name . "<" . $from_email . ">";

   		}
		else {
        	$from = $from_email;
		}

	    $subject = preg_replace("/\{USERNAME\}/", $admin_name, $subject);
	    $message = preg_replace("/\{USERNAME\}/", $admin_name, $message);
	    $message = preg_replace("/\{NAME\}/", $admin_fname . " " . $admin_lname, $message);
	    $message = preg_replace("/\{PASSWORD\}/", $upass, $message);
	    $message = preg_replace("/\{BASE_SERVER_VHOST\}/", $base_vhost, $message);

	    $headers = "From: $from\n";

	    $headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";

	    $headers .= "X-Mailer: ispCP lostpassword mailer";

	    $mail_result = mail($to, $subject, $message, $headers);

	    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

	    write_log("Lostpassword activated: To: |$to|, From: |$from|, Status: |$mail_status| !");

		return true;

	}

	return false;

}

function requestpassword($admin_name) {

	global $sql, $cfg;

 	$query = <<<SQL_QUERY
        		SELECT
            	created_by, fname, lname, email
        		FROM
            	admin
        		WHERE
            	admin_name = ?
SQL_QUERY;

  	$res = exec_query($sql, $query, array($admin_name));

	if ($res -> RecordCount() == 1) {

	$created_by = $res -> fields['created_by'];

	$admin_fname = $res -> fields['fname'];

  	$admin_lname = $res -> fields['lname'];

  	$to = $res -> fields['email'];

	$uniqkey = uniqkeygen();

	setUniqKey($admin_name, $uniqkey);

    write_log("Lostpassword: ".$admin_name.": uniqkey created");

	if ($created_by == 0) $created_by = 1;

	$data = get_lostpassword_activation_email($created_by);

	$from_name = $data['sender_name'];

	$from_email = $data['sender_email'];

    $subject = $data['subject'];

    $message = $data['message'];

    $base_vhost = $cfg['BASE_SERVER_VHOST'];

    if ($from_name) {

        $from = $from_name . "<" . $from_email . ">";

    }
	else {

        $from = $from_email;
	}

	switch( $_SERVER["SERVER_PORT"] ) {

		case "80":
			$prot = "http://";
			break;
		case "443":
			$prot = "https://";
			break;
	}

	$link = $prot . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?key=" . $uniqkey;

    $subject = preg_replace("/\{USERNAME\}/", $admin_name, $subject);
    $message = preg_replace("/\{NAME\}/", $admin_fname . " " . $admin_lname, $message);
    $message = preg_replace("/\{LINK\}/", $link, $message);
	$message = preg_replace("/\{BASE_SERVER_VHOST\}/", $base_vhost, $message);

    $headers = "From: $from\n";

    $headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";

    $headers .= "X-Mailer: ISPCP lostpassword mailer";

    $mail_result = mail($to, $subject, $message, $headers);

    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

    write_log("Lostpassword send: To: |$to|, From: |$from|, Status: |$mail_status| !");

		return true;

	}

	return false;

}

?>