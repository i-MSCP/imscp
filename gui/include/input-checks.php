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
 *
 **/


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
				$message = "Possible hacking attempt. Script terminated.";
				write_log($message);
				system_message(tr($message));
				die();
			}

		}

	}

}

function clean_html($input = '') {

	$suche = array ('@<script[^>]*?>.*?</script>@si',  // JavaScript entfernen
               '@<[\/\!]*?[^<>]*?>@si',          // HTML-Tags entfernen
               '@([\r\n])[\s]+@',                // Leerr�ume entfernen
               '@&(quot|#34);@i',                // HTML-Entit�ten ersetzen
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
	//and second one...
	$text = strip_tags($text);

	return $text;

}

function clean_input($input) {

	if ((strpos($input, "{") == 0) && (strpos($input, "}") == strlen($input)-1)) {

		$input = trim($input, "{..}");

	}

	return htmlentities(stripslashes($input), ENT_QUOTES, "UTF-8");

}

function ispcp_password_check ( $data, $num) {

	global $cfg;

  $len = strlen($data);

  if ($len < $cfg['PASSWD_CHARS'] || $len > $num ) return false;

	if ($cfg['PASSWD_STRONG'] == 1) {

		return (preg_match("/[0-9]/", $data) && preg_match("/[a-zA-Z]/", $data));

	} else {

		return true;

	}

}

/* check for valid username  */
function chk_username( $username ) {

    if ( ispcp_username_check($username,50) == 0 ) {
        return 1;
    }

    /* seems ok ! */
    return 0;

}

/* check for valid password  */
function chk_password( $password ) {

	if ( ispcp_password_check($password, 50) == 0 ) {
        return 1;
    }

    /* seems ok ! */
    return 0;
}

function ispcp_username_check($data, $num) {

    $res = preg_match("/^[-A-Za-z0-9\.-_]*[A-Za-z0-9]$/", $data);

    if ($res == 0) return 0;

    $res = preg_match("/(\.\.)|(\-\-)|(\_\_)/", $data);

    if ($res == 1) return 0;

    $res = preg_match("/(\.\-)|(\-\.)/", $data);

    if ($res == 1) return 0;

    $res = preg_match("/(\.\_)|(\_\.)/", $data);

    if ($res == 1) return 0;

    $res = preg_match("/(\-\_)|(\_\-)/", $data);

    if ($res == 1) return 0;

    $len = strlen($data);

    if ( $len > $num ) return 0;

	return 1;
}


function ispcp_email_check($email, $num) {
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

function ispcp_check_local_part($email, $num="50") {
	// RegEx begin

	$nonascii      = "\x80-\xff"; # Non-ASCII-Chars are not allowed

	$nqtext        = "[^\\\\$nonascii\015\012\"]";
	$qchar         = "\\\\[^$nonascii]";

	$normuser      = '[a-zA-Z0-9][a-zA-Z0-9_.-]*';
	$quotedstring  = "\"(?:$nqtext|$qchar)+\"";
	$user_part     = "(?:$normuser|$quotedstring)";

	$regex         = "$user_part";
	// RegEx end

	if (!preg_match("/^$regex$/",$email)) return 0;

	if (strlen($email) > $num) return 0;

	return 1;

}


function chk_email($email) {

    if ( ispcp_email_check($email, 50) == 0 ) {
        return 1;
    }

    /* seems ok ! */
    return 0;

}


function full_domain_check($data) {

	$data .= ".";
	$match = array();

    $res = preg_match_all("/([^\.]*\.)/", $data, $match, PREG_PATTERN_ORDER);

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


    $res = preg_match("/^[A-Za-z][A-Za-z0-9]*[A-Za-z]\.$/", $match[0][$last]);

    if ($res == 0) {
		return 0;
	}
    return 1;
}


function check_dn_token($data) {

	$match = array();
    $res = preg_match("/^([A-Za-z0-9])([A-Za-z0-9\-]*)([A-Za-z0-9])$/",	$data, $match);

    if ($res == 0) {
		return 0;
	}

    //$res = preg_match("/\-\-/", $match[2], $minus_match);
    //if ($res == 1) return 0;

    return 1;
}

	/**********************************************************************
	*
 	*Description:
	*
	*	Function for checking ispcp 'username' field syntax. This function
	*	will also be used in ispcp_email_check() function;
	*
	*Input:
	*
	*	$data - ispcp 'username' field data;
	*
	*	$num - username maximum length;
	*
	*Output:
	*
	*	0 - incorrect syntax;
	*
	*	1 - correct syntax;
	*
	**********************************************************************/
function ispcp_name_check ( $data, $num ) {

	$res = preg_match("/^[A-Za-z][A-Za-z0-9\.\-\_]*[A-Za-z0-9]$/", $data);

	if ($res == 0) return 0;

	$res = preg_match("/(\.\.)|(\-\-)|(\_\_)/", $data);

	if ($res == 1) return 0;

	$res = preg_match("/(\.\-)|(\-\.)/", $data);

	if ($res == 1) return 0;

	$res = preg_match("/(\.\_)|(\_\.)/", $data);

	if ($res == 1) return 0;

	$res = preg_match("/(\-\_)|(\_\-)/", $data);

	if ($res == 1) return 0;

	$len = strlen($data);

	if ( $len > $num ) return 0;

	return 1;
}// End of ispcp_name_check()




	/**********************************************************************
	*
	*Description:
	*
	*	Function for checking ispcp limits. The correct values for this
	*	limits are in ranges -1, 0, [1, $num].
	*
	*Input:
	*
	*$data - ispcp 'limit' field data;
	*
	*Output:
	*
	*	0 - incorrect syntax (ranges);
	*
	*	1 - correct syntax (ranges);
	*
	**********************************************************************/
function ispcp_limit_check($data, $num) {

	$res = preg_match("/^(-1|0|[1-9][0-9]*)$/", $data);

	if ($res == 0)
		return 0;

	if ($data > $num)
		return 0;

	return 1;
}// End of ispcp_limit_check()

	/**********************************************************************
	*
	* Description:
	*
 	*Function for checking domain name tokens; Internel function, for>
    * usage in ispcp_* functions;
	* Input:
	*
	* $data - token data. Without '\n' at the end;
	*
	* Output:
 	*
    * 0 - incorrect syntax;
 	*
    * 1 - correct syntax;
	**********************************************************************/
function check_dn_rsl_token($data) {

	$match = array();
	$res = preg_match("/^([[^a-z0-9^A-Z^������\-]*)([A-Za-z0-9])$/", $data,	$match);
	if ($res == 0) return 0;

	$res = preg_match("/\-\-/", $match[2]);
	if ($res == 1) return 0;

	return 1;
}// End of check_dn_rsl_token()





	/**********************************************************************
	*
	* Description:
	*
    *Function for checking ISPCP domains syntax. Here domains are limited
    *to {dname}.{ext} parts.
	*
	*Input:
	*
    * $data - ispcp domain data;
	*
	* Output:
	*
    * 0 - incorrect syntax;
	*
    * 1 - correct syntax;
	**********************************************************************/
function ispcp_domain_check($data) {

	$res = rsl_full_domain_check($data);

	if ($res == 0) return 0;

	$res = preg_match_all("/\./", $data, $match, PREG_PATTERN_ORDER);

	if ($res <= 0) return 0;

	return 1;

}// End of ispcp_domain_check()




	/**********************************************************************
	*Description:
	*
	*	Function for checking full domain names syntax. /In ISPCP domains
	*	are limited to domain and subdomain parts.
	*
	*Input:
	*
	*	$data - domain name data;
	*
	*Output:
	*
	*	0 - incorrect syntax;
	*
	*	1 - correct syntax;
	*
	**********************************************************************/

	/* check for valid domain name  */
function chk_dname( $dname ) {

    if ( ispcp_domain_check($dname) == 0 ) {
        return 1;
    }

    /* seems ok */
    return 0;

}


/* check for valid url addres  */
function chk_url($url) {

    if ( ispcp_url_check($url) == 0 ) {
        return 1;
    }

    /* seems ok ! */
    return 0;

}


function ispcp_url_check ($data) {

    $data = "$data\n";

    $res = preg_match("/^(http|https|ftp)\:\/\/[^\n]+\n$/",	$data);

    if ($res == 0) return 0;

    return 1;
}



function ispcp_mountpt_check($data, $num) {

	$res = !preg_match("/^\/(.*)$/D", $data);

	if ($res == 1) return 0;

    $res = preg_match("/^\/htdocs$/D", $data);

    if ($res == 1) return 0;

	 $res = preg_match("/^\/backups$/D", $data);

    if ($res == 1) return 0;

	$res = preg_match("/^\/cgi-bin$/D", $data);

    if ($res == 1) return 0;

	$res = preg_match("/^\/errors$/D", $data);

    if ($res == 1) return 0;

	$res = preg_match("/^\/logs$/D", $data);

    if ($res == 1) return 0;

	$res = explode("/", trim($data));
	$cnt_res = count($res);
	if ($cnt_res > 2) return 0;

	$match = array();
	$res = preg_match_all("(\/[^\/]*)", $data, $match, PREG_PATTERN_ORDER);

    if ($res == 0) {
		return 0;
	}

    $count = $res;

    for ($i = 0; $i < $count; $i++) {

        $token = substr($match[0][$i], 1);

        $res = ispcp_username_check($token, $num);

        if ($res == 0) {
			return 0;
		}
    }

    return 1;
}


/* check for valid mount point  */
function chk_mountp($mountp) {

    if ( ispcp_mountpt_check($mountp,50) == 0) {
        return 1;
    }

    /* seems ok ! */
    return 0;

}

/* return mail for a a id */
function trans_mailid_to_mail(&$sql, $mailid) {

	$query = <<<SQL_QUERY

	select *
		from
			mail_users
		where
			mail_id = ?
		limit 1
SQL_QUERY;

	$res = exec_query($sql, $query, array($mailid));

	if ($res -> RowCount() == 1) {

		$data = $res -> FetchRow();
		$mail_type = $data['mail_type'];

		if ($mail_type === 'normal_mail') {

        	$local_part = $data['mail_acc'];
			$domain_query = "select domain_name from domain as t1, mail_users as t2  where t2.domain_id=t1.domain_id and t2.mail_id = ?";
			$res = exec_query($sql, $domain_query, array($mailid));
		    $domain_name = $res->fields['domain_name'];

		    return $local_part."@".$domain_name;

    	} else if ($mail_type === 'normal_forward') {

        	$local_part = $data['mail_acc'];
        	$domain_query = "select domain_name from domain as t1, mail_users as t2  where t2.domain_id=t1.domain_id and t2.mail_id = ?";
			$res = exec_query($sql, $domain_query, array($mailid));
		    $domain_name = $res->fields['domain_name'];

		    return $local_part."@".$domain_name;

    	} else if ($mail_type === 'alias_mail') {

        	$local_part = $data['mail_acc'];
        	$domain_query = "select alias_name from domain as t1, mail_users as t2, domain_aliasses as t3  where t2.domain_id=t1.domain_id and t3.alias_id = t2.sub_id and t2.mail_id = ?";
			$res = exec_query($sql, $domain_query, array($mailid));
		    $domain_name = $res->fields['alias_name'];

		    return $local_part."@".$domain_name;

    	} else if ($mail_type === 'alias_forward') {

        	$local_part = $data['mail_acc'];
        	$domain_query = "select alias_name from domain as t1, mail_users as t2, domain_aliases as t3  where t2.domain_id=t1.domain_id and t3.alias_id = t2.sub_id and t2.mail_id = ?";
			$res = exec_query($sql, $domain_query, array($mailid));
		    $domain_name = $res->fields['alias'];

		    return $local_part."@".$domain_name;


    	} else if ($mail_type === 'subdom_mail') {

        	$local_part = $data['mail_acc'];
        	$domain_query = "select subdomain_name from domain as t1, mail_users as t2, subdomain as t3  where t2.domain_id=t1.domain_id and t3.subdomain_id = t2.sub_id and t2.mail_id = ?";

    	} else if ($mail_type === 'subdom_forward') {

        	$local_part = $data['mail_acc'];
        	$domain_query = "select subdomain_name from domain as t1, mail_users as t2, subdomain as t3  where t2.domain_id=t1.domain_id and t3.subdomain_id = t2.sub_id and t2.mail_id = ?";
			$res = exec_query($sql, $domain_query, array($mailid));
		    $domain_name = $res->fields['subdomain_name'];

		    return $local_part."@".$domain_name;

    	} else if ($mail_type === 'normal_catchall') {

        	return tr('Catchall account');

    	} else if ($mail_type === 'alias_catchall') {

        	return tr('Catchall account');

    	} else {

        	return tr('Unknown type');

    	}

	}

}

function get_post($value) {

	if(isset($_POST[$value])) {

		return $_POST[$value];

	} else {

		return null;

	}

}

function get_get($value) {

	if(isset($_GET[$value])) {

		return $_GET[$value];

	} else {

		return null;

	}

}

function get_session($value) {

	if(isset($_SESSION[$value])) {

		return $_SESSION[$value];

	} else {

		return null;

	}

}

function get_cookie($value) {

	if(isset($_COOKIE[$value])) {

		return $_COOKIE[$value];

	} else {

		return null;

	}

}

function get_server($value) {

	if(isset($_SERVER[$value])) {

		return $_SERVER[$value];

	} else {

		return null;

	}

}

?>