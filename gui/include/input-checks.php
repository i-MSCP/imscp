<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2004 be moleSoftware		            		|
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



//-- check if they are trying to hack
$CHECK_VARS = array();
$CHECK_VARS[] = "/wget /i";
$CHECK_VARS[] = "/chmod /i";
$CHECK_VARS[] = "/chown /i";
$CHECK_VARS[] = "/lnyx /i";
$CHECK_VARS[] = "/curl /i";
$CHECK_VARS[] = "/fopen /i";
$CHECK_VARS[] = "/mkdir /i";
$CHECK_VARS[] = "/passwd /i";
$CHECK_VARS[] = "/http:/i";
$CHECK_VARS[] = "/ftp:/i";

$CHECK_VARS[] = "/content-type:/i";
$CHECK_VARS[] = "/content-transfer-encoding:/i";
$CHECK_VARS[] = "/mime-version:/i";
$CHECK_VARS[] = "/subject:/i";
$CHECK_VARS[] = "/to:/i";
$CHECK_VARS[] = "/cc:/i";
$CHECK_VARS[] = "/bcc:/i";
$CHECK_VARS[] = "/\r/";
$CHECK_VARS[] = "/\n/";
$CHECK_VARS[] = "/%0a/";
$CHECK_VARS[] = "/%0d/";

function check_input($value = '') {

	global $CHECK_VARS;

	if (!empty($value)) {

		$value = strtolower($value);

		foreach($CHECK_VARS as $VAR) {

			if (preg_match($VAR, $value) > 0) {

				write_log("Possible hacking attempt. Script terminated.");

				system_message("Possible hacking attempt. Script terminated.");

				die();
			}

		}

	}

}

function clean_html($input = '') {
	
	$suche = array ('@<script[^>]*?>.*?</script>@si',  // JavaScript entfernen
               '@<[\/\!]*?[^<>]*?>@si',          // HTML-Tags entfernen
               '@([\r\n])[\s]+@',                // Leerräume entfernen
               '@&(quot|#34);@i',                // HTML-Entitäten ersetzen
               '@&(amp|#38);@i',
               '@&(lt|#60);@i',
               '@&(gt|#62);@i',
               '@&(nbsp|#160);@i',
               '@&(iexcl|#161);@i',
               '@&(cent|#162);@i',
               '@&(pound|#163);@i',
               '@&(copy|#169);@i',
               '@&#(\d+);@e');                    // als PHP auswerten

	$ersetze = array ('',
                 '',
                 '\1',
                 '"',
                 '&',
                 '<',
                 '>',
                 ' ',
                 chr(161),
                 chr(162),
                 chr(163),
                 chr(169),
                 'chr(\1)');

	$text = preg_replace($suche, $ersetze, $input);

return $text;	
	
}

/* check for valid username  */
function chk_username( $username ) {

    if ( vhcs_username_check($username,50) == 0 ) {
        return 1;
    }

    /* seems ok ! */
    return 0;

}

/* check for valid password  */
function chk_password( $password ) {

	if ( vhcs_password_check($password, 50) == 0 ) {
        return 1;
    }

    /* seems ok ! */
    return 0;
}

function vhcs_username_check ( $data, $num ) {

    $res = preg_match(
    					"/^[-A-Za-z0-9\.-_]*[A-Za-z0-9]$/",
                        $data,
                        $match
    				);

    if ($res == 0) return 0;

    $res = preg_match("/(\.\.)|(\-\-)|(\_\_)/", $data, $match);

    if ($res == 1) return 0;

    $res = preg_match("/(\.\-)|(\-\.)/", $data, $match);

    if ($res == 1) return 0;

    $res = preg_match("/(\.\_)|(\_\.)/", $data, $match);

    if ($res == 1) return 0;

    $res = preg_match("/(\-\_)|(\_\-)/", $data, $match);

    if ($res == 1) return 0;

    $len = strlen($data);

    if ( $len > $num ) return 0;

	return 1;
}

function vhcs_email_check($email, $num) {
  // RegEx begin
  
  $nonascii      = "\x80-\xff"; # Non-ASCII-Chars are not allowed

  $nqtext        = "[^\\\\$nonascii\015\012\"]";
  $qchar         = "\\\\[^$nonascii]";

  $normuser      = '[a-zA-Z0-9][a-zA-Z0-9_.-]*';
  $quotedstring  = "\"(?:$nqtext|$qchar)+\"";
  $user_part     = "(?:$normuser|$quotedstring)";

  $dom_mainpart  = '[a-zA-Z0-9][a-zA-Z0-9._-]*\\.';
  $dom_subpart   = '(?:[a-zA-Z0-9][a-zA-Z0-9._-]*\\.)*';
  $dom_tldpart   = '[a-zA-Z]{2,5}';
  $domain_part   = "$dom_subpart$dom_mainpart$dom_tldpart";

  $regex         = "$user_part\@$domain_part";
  // RegEx end
  
  if (!preg_match("/^$regex$/",$email)) return 0;
  	
  if (strlen($email) > $num) return 0;
  	
  return 1;
  
}



function chk_email( $email ) {

    if ( vhcs_email_check($email, 50) == 0 ) {
        return 1;
    }

    /* seems ok ! */
    return 0;

}


function full_domain_check ( $data ) {

	$data = "$data.";

    $res = preg_match_all(
    						"/([^\.]*\.)/",
                            $data,
                            $match,
                            PREG_PATTERN_ORDER
    					);

    if ($res == 0) {
		return 0;
	}

    $last = $res - 1;

    for ($i = 0; $i < $last ; $i++) {

        $token = chop($match[0][$i], ".");

        $res = check_dn_token($token);

        if ($res == 0) {
			return 0;
		}
    }


    $res = preg_match(
    					"/^[A-Za-z][A-Za-z0-9]*[A-Za-z]\.$/",
                        $match[0][$last],
                        $last_match
    				);

    if ($res == 0) {

		return 0;
	}


    return 1;
}


function check_dn_token ( $data ) {

    $res = preg_match(
    					"/^([A-Za-z0-9])([A-Za-z0-9\-]*)([A-Za-z0-9])$/",
						$data,
                        $match
    				);

    if ($res == 0) {
		return 0;
	}

    $res = preg_match("/\-\-/", $match[2], $minus_match);

    //if ($res == 1) return 0;

    return 1;
}
