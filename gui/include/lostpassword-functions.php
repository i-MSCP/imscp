<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



function check_gd() {

	if (function_exists('imagecreatetruecolor'))

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

	$font = $cfg['LOSTPASSWORD_CAPTCHA_FONT'];

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

  	if (ereg('[2-47-9A-HKM-NP-PRTWUYa-hkm-np-rtwuy]', chr($random))) {

  		$str .= chr($random);

   	}

	}
		
	$_SESSION[$strSessionVar] = $str;

  return $_SESSION[$strSessionVar];

}

function passgen() {

	$pw = '';

	$pw_lenght = 8;

  $chars = "0,1,2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
  
  $array_b = explode(",", $chars);

  for($i=0; $i < $pw_lenght; $i++) {

  	srand((double)microtime() * 1000000);

    $z = rand(0, 25);

    $pw .= "" . $array_b[$z] . "";

  }

	return $pw;

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

	global $sql;

  $query = <<<SQL_QUERY
        		SELECT
            	admin_name, created_by, fname, lname, email
        		FROM
            	admin
        		WHERE
            	uniqkey = ?
SQL_QUERY;

  $res = exec_query($sql, $query, array($uniqkey));

	if ($res -> RecordCount() ==1 ){

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

		$data = get_email_data($created_by);
		
		$from_name = $data['sender_name'];

		$from_email = $data['sender_email'];

    $subject = $data['subject_2'];

    $message = $data['message_2'];

    if ($from_name) {

        $from = $from_name . "<" . $from_email . ">";

    } else {

        $from = $from_email;
		}

    $subject = preg_replace("/\{USERNAME\}/", $admin_name, $subject);

    $message = preg_replace("/\{USERNAME\}/", $admin_name, $message);

    $message = preg_replace("/\{NAME\}/", $admin_fname . " " . $admin_lname, $message);

    $message = preg_replace("/\{PASSWORD\}/", $upass, $message);

    $message = str_replace(chr(10), "", $message);

    $headers = "From: $from\n";

    $headers .= "Content-Type: text/plain\nContent-Transfer-Encoding: 7bit\n";

    $headers .= "X-Mailer: VHCS Pro lostpassword mailer";

    $mail_result = mail($to, $subject, $message, $headers);

    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

    write_log("Lostpassword activated: To: |$to|, From: |$from|, Status: |$mail_status| !");

		return true;

	}

	return false;

}

function requestpassword($admin_name) {

	global $sql;

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

		$data = get_email_data($created_by);
		
		$from_name = $data['sender_name'];

		$from_email = $data['sender_email'];

    $subject = $data['subject_1'];

    $message = $data['message_1'];

    if ($from_name) {

        $from = $from_name . "<" . $from_email . ">";

    } else {

        $from = $from_email;
		}

		switch( $_SERVER["SERVER_PORT"] ) {
			
			case "80": $prot = "http://";

			break;

			case "443": $prot = "https://";

			break;

		}

		$link = $prot . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?i=" . $uniqkey;

    $subject = preg_replace("/\{USERNAME\}/", $admin_name, $subject);

    $message = preg_replace("/\{NAME\}/", $admin_fname . " " . $admin_lname, $message);

    $message = preg_replace("/\{LINK\}/", $link, $message);

    $message = str_replace(chr(10), "", $message);

    $headers = "From: $from\n";

    $headers .= "Content-Type: text/plain\nContent-Transfer-Encoding: 7bit\n";

    $headers .= "X-Mailer: VHCS Pro lostpassword mailer";

    $mail_result = mail($to, $subject, $message, $headers);

    $mail_status = ($mail_result) ? 'OK' : 'NOT OK';

    write_log("Lostpassword send: To: |$to|, From: |$from|, Status: |$mail_status| !");

		return true;

	}

	return false;

}

function set_email_data($admin_id, $data) {

	global $sql;

	$query = <<<SQL_QUERY
  					SELECT
            	subject, message
            FROM
            	email_tpls
            WHERE
            	owner_id = ?
           	AND
           		name = 'lostpw-msg-1'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id));

	if ($rs ->RowCount() == 0 ) {

		$query = <<<SQL_QUERY
  						INSERT INTO email_tpls
            		(subject, message, owner_id, name)
      	  		VALUES
            		(?, ?, ?, 'lostpw-msg-1')
SQL_QUERY;

	} else {

		$query = <<<SQL_QUERY
  						UPDATE
  							email_tpls
  						SET
  							subject = ?,
          	    message = ?
	            WHERE
  	          	owner_id = ?
    	        AND
      	      	name = 'lostpw-msg-1'
SQL_QUERY;

	}

	$rs = exec_query($sql, $query, array($data['subject_1'], $data['message_1'], $admin_id));

	$query = <<<SQL_QUERY
  					SELECT
            	subject, message
            FROM
            	email_tpls
            WHERE
            	owner_id = ?
            AND
            	name = 'lostpw-msg-2'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id));

	if ($rs ->RowCount() == 0 ) {

		$query = <<<SQL_QUERY
  						INSERT INTO email_tpls
            		(subject, message, owner_id, name)
      	  		VALUES
            		(?, ?, ?, 'lostpw-msg-2')
SQL_QUERY;

	} else {

		$query = <<<SQL_QUERY
  						UPDATE
  							email_tpls
  						SET
  							subject = ?,
          	    message = ?
	            WHERE
  	          	owner_id = ?
    	        AND
      	      	name = 'lostpw-msg-2'
SQL_QUERY;

	}

	$rs = exec_query($sql, $query, array($data['subject_2'], $data['message_2'], $admin_id));

}

function get_email_data($admin_id) {

	global $sql;

	$query = <<<SQL_QUERY
         		SELECT
            	fname, lname, email
            FROM
            	admin
            WHERE
             	admin_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id));

	if ( ($rs->fields('fname') != '') && ($rs->fields('lname') != '') ) {

  	$data['sender_name'] = $rs->fields('fname') . " " . $rs->fields('lname');

	} else {

		$data['sender_name'] = '';

  }
  
  $data['sender_email'] = $rs->fields('email');

	$query = <<<SQL_QUERY
						SELECT
    	      	subject, message
      	    FROM
        	  	email_tpls
          	WHERE
          		owner_id = ?
          	AND
          		name = 'lostpw-msg-1'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id));

	if ($rs ->RowCount() == 0 ) {

		$data['subject_1'] = 'Auto message alert for lostpw ! {USERNAME}';

  	$data['message_1'] = <<<MSG

Dear {NAME},
Use this link to activate your new VHCS password:

{LINK}


Good Luck with VHCS Pro System
Hosting Provider Team

MSG;

	} else {

		$data['subject_1'] = $rs->fields['subject'];

    $data['message_1'] = $rs->fields['message'];
	}

	$query = <<<SQL_QUERY
  					SELECT
            	subject, message
            FROM
            	email_tpls
            WHERE
            	owner_id = ?
            AND
            	name = 'lostpw-msg-2'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id));

  if ($rs ->RowCount() == 0 ) {

  	$data['subject_2'] = 'Auto message alert for lostpw ! {USERNAME}';

  	$data['message_2'] = <<<MSG

Hello {NAME} !

Your VHCS login is: {USERNAME}
Your VHCS password is: {PASSWORD}


Good Luck with VHCS Pro System
Hosting Provider Team

MSG;

	} else {

    $data['subject_2'] = $rs->fields['subject'];

    $data['message_2'] = $rs->fields['message'];

	}

	return $data;

}

?>
