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

function check_for_lock_file($wait_lock_timeout = 500000) {

    global $cfg;

	set_time_limit(0);
    while(file_exists($cfg['MR_LOCK_FILE'])) {

		usleep($wait_lock_timeout);
        clearstatcache();
        // and send header to keep connection
        header( "Cache-Control: no-store, no-cache, must-revalidate" );
    }
}

function read_line(&$socket) {
    $ch = '';
    $line = '';
    do {
        $ch = socket_read($socket,1);
        $line = $line . $ch;
    } while($ch != "\r" && $ch != "\n");
    return $line;
}

function send_request() {

	global $Version;

	$code = 999;

    @$socket = socket_create (AF_INET, SOCK_STREAM, 0);
    if ($socket < 0) {
        $errno =  "socket_create() failed.\n";
        return $errno;
    }

    @$result = socket_connect ($socket, '127.0.0.1', 9876);
    if ($result == FALSE) {
        $errno =  "socket_connect() failed.\n";
        return $errno;
    }

    /* read one line with welcome string */
    $out = read_line($socket);

    list($code) = explode(' ', $out);
    if ($code == 999) {
        return $out;
    }

    /* send hello query */
    $query = "helo  $Version\r\n";
    socket_write ($socket, $query, strlen ($query));

    /* read one line with helo answer */
    $out = read_line($socket);

    list($code) = explode(' ', $out);
    if ($code == 999) {
        return $out;
    }

    /* send reg check query */
    $query = "execute query\r\n";
    socket_write ($socket, $query, strlen ($query));
    /* read one line key replay */
    $execute_reply = read_line($socket);

    list($code) = explode(' ', $execute_reply);
    if ($code == 999) {
        return $out;
    }

    /* send quit query */
    $quit_query = "bye\r\n";
    socket_write ($socket, $quit_query, strlen ($quit_query));
    /* read quit answer */
    $quit_reply = read_line($socket);

    list($code) = explode(' ', $quit_reply);
    if ($code == 999) {
        return $out;
    }

    list($answer) = explode(' ', $execute_reply);

    socket_close ($socket);

    return $answer;

}


function update_user_props ( $user_id, $props ) {

    global $sql, $cfg;

    list (
           $sub_current, $sub_max,
           $als_current, $als_max,
           $mail_current, $mail_max,
           $ftp_current, $ftp_max,
           $sql_db_current, $sql_db_max,
           $sql_user_current, $sql_user_max,
           $traff_max, $disk_max,
		   $domain_php, $domain_cgi) = explode (";", $props);
		   //$domain_ip_id, $domain_php, $domain_cgi) = explode (";", $props);

		 //have to check if PHP and/or CGI and/or IP change
		 $domain_last_modified =  time();

	$query = <<<SQL_QUERY
        select
            domain_name
        from
            domain
        where
            domain_id  = ?
          and
            domain_php = ?
          and
            domain_cgi = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id, $domain_php, $domain_cgi));

	if ($rs -> RecordCount() == 0) {
		// mama mia, we have to rebuild the system entry for this domain
		// and also all domain alias and subdomains

		$update_status = $cfg['ITEM_CHANGE_STATUS'];

		// check if we have to wait some system update
		check_for_lock_file();
		// ... and go update

		// update the domain
		$query = <<<SQL_QUERY
        update
            domain
        set
            domain_last_modified = ?,
            domain_mailacc_limit = ?,
            domain_ftpacc_limit = ?,
            domain_traffic_limit = ?,
            domain_sqld_limit = ?,
            domain_sqlu_limit = ?,
            domain_status = ?,
            domain_alias_limit = ?,
            domain_subd_limit = ?,
            domain_disk_limit = ?,
            domain_php = ?,
            domain_cgi = ?
        where
            domain_id  = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($domain_last_modified,
                                         $mail_max,
                                         $ftp_max,
                                         $traff_max,
                                         $sql_db_max,
                                         $sql_user_max,
                                         $update_status,
                                         $als_max,
                                         $sub_max,
                                         $disk_max,
                                         $domain_php,
                                         $domain_cgi,
                                         $user_id));

		// lets update all alias domains for this domain

	$query = <<<SQL_QUERY
        update
            domain_aliasses
        set
            alias_status = ?
        where
            domain_id  = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($update_status, $user_id));

    while (!$rs -> EOF) {
			$rs -> MoveNext();
		}

		// lets update all subdomains for this domain

	$query = <<<SQL_QUERY
        update
            subdomain
        set
            subdomain_status = ?
        where
            domain_id  = ?
SQL_QUERY;

    	$rs = exec_query($sql, $query, array($update_status, $user_id));
		while (!$rs -> EOF) {
			$rs -> MoveNext();
		}

		// and now we start the daemon
		send_request();


    } else {

		// we do not have IP and/or PHP and/or CGI changes
		// we have to update only the domain props and not
		// to rebuild system entries

		 $query = <<<SQL_QUERY
        update
            domain
        set
            domain_subd_limit = ?,
            domain_alias_limit = ?,
            domain_mailacc_limit = ?,
            domain_ftpacc_limit = ?,
            domain_sqld_limit = ?,
            domain_sqlu_limit = ?,
            domain_traffic_limit = ?,
            domain_disk_limit = ?
        where
            domain_id = ?

SQL_QUERY;

    	$rs = exec_query($sql, $query, array($sub_max,
                                           $als_max,
                                           $mail_max,
                                           $ftp_max,
                                           $sql_db_max,
                                           $sql_user_max,
                                           $traff_max,
                                           $disk_max,
                                           $user_id));
  }

}

/* end */

function escape_user_data ( $data ) {

    $res_one = preg_replace("/\\\\/", "", $data);

    $res = preg_replace("/'/", "\\\'", $res_one);

    return $res;

}

function make_hr($bytes){
    if ($bytes == '' || $bytes < 0 ) {
        $ret = '0&nbsp;B';
    }
    else if ($bytes > 1073741824){
        /* g bytes */
        $bytes = $bytes/1073741824;
        $ret   = sprintf("%.2f", $bytes) . "&nbsp;GB";
    }
    else if ($bytes > 1048576 ) {
        /* m bytes */
        $bytes = $bytes/1048576;
        $ret   = sprintf("%.2f", $bytes) . "&nbsp;MB";
    }
    else if ($bytes > 1024) {
        /* k bytes */
        $bytes = $bytes/1024;
        $ret   = sprintf("%.2f", $bytes) . "&nbsp;KB";
    }
    else {
        /* bytes */
        $ret = $bytes . "&nbsp;B";
    }

    return $ret;

}

/*
fuction to decode punny to normal
$input -> string get in punny or not punny
$output -> normal string get out

*/
function decode_idna($input)
{
	$IDN = new idna_convert();
	$output = $IDN->decode($input);

    if ($output == FALSE){
		return $input;
	} else {
		return $output;
	}
}

function get_punny($input)
{
	$IDN = new idna_convert();
	$output = $IDN->encode($input);
	return $output;
}

function strip_html($input)
{
	$output = htmlspecialchars($input, ENT_QUOTES, "UTF-8");
	return $output;
}

function is_number($integer) {
	if (preg_match('/^[0-9]+$/', $integer)) {
		return true;
	}
	return false;
}

function is_basicString($sting) {
	if (preg_match('=^[a-zA-Z0-9_-]+$=', $sting)) {
		return true;
	}
	return false;
}

function unset_messages () {

	$glToUnset = array();
	$glToUnset[] = 'user_page_message';
	$glToUnset[] = 'user_updated';
	$glToUnset[] = 'user_updated';
	$glToUnset[] = 'dmn_tpl';
	$glToUnset[] = 'chtpl';
	$glToUnset[] = 'step_one';
	$glToUnset[] = 'step_two_data';
	$glToUnset[] = 'ch_hpprops';
	$glToUnset[] = 'rau3_added';
	$glToUnset[] = 'user_has_domain';
	$glToUnset[] = 'local_data';
	$glToUnset[] = 'reseller_added';
	$glToUnset[] = 'user_added';
	$glToUnset[] = 'aladd';
	$glToUnset[] = 'edit_ID';
	$glToUnset[] = 'hp_added';
	$glToUnset[] = 'aldel';
	$glToUnset[] = 'hpid';
	$glToUnset[] = 'user_deleted';
	$glToUnset[] = 'hdomain';
	$glToUnset[] = 'aledit';
	$glToUnset[] = 'acreated_by';
	$glToUnset[] = 'dhavesub';
	$glToUnset[] = 'ddel';
	$glToUnset[] = 'dhavealias';
	$glToUnset[] = 'dhavealias';
	$glToUnset[] = 'dadel';
	$glToUnset[] = 'local_data';

	foreach ($glToUnset as $toUnset) {
		if (isset($GLOBALS[$toUnset]))
			unset($GLOBALS[$toUnset]);
	}

	$sessToUnset = array();
	$sessToUnset[] = 'reseller_added';
	$sessToUnset[] = 'dmn_name';
	$sessToUnset[] = 'rau3_added';
	$sessToUnset[] = 'user_has_domain';
	$sessToUnset[] = 'user_added';
	$sessToUnset[] = 'aladd';
	$sessToUnset[] = 'edit_ID';
	$sessToUnset[] = 'hp_added';
	$sessToUnset[] = 'aldel';
	$sessToUnset[] = 'hpid';
	$sessToUnset[] = 'user_deleted';
	$sessToUnset[] = 'hdomain';
	$sessToUnset[] = 'aledit';
	$sessToUnset[] = 'acreated_by';
	$sessToUnset[] = 'dhavesub';
	$sessToUnset[] = 'ddel';
	$sessToUnset[] = 'dhavealias';
	$sessToUnset[] = 'dadel';
	$sessToUnset[] = 'local_data';

	foreach ($sessToUnset as $toUnset) {
		if (isset($_SESSION[$toUnset]))
			unset($_SESSION[$toUnset]);
	}
}

?>