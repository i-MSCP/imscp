<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            	|
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





function check_for_lock_file()
{

    global $lock_file_name, $wait_lock_timeout, $cfg;

	$wait_lock_timeout = 500000;

	$lock_file_name = $cfg['MR_LOCK_FILE'];

	set_time_limit(0);
    while(file_exists($lock_file_name)) {

	   // let's write some stupid text and stop the GUI
	   //
	    /* nope ... we dont wannt let loosers browsing empty page 10000x hours */
		//ooooops :(
        usleep($wait_lock_timeout);
        clearstatcache();
        // and send header to keep connection
        header( "Cache-Control: no-store, no-cache, must-revalidate" );
    }
	//system_message(tr('<b>Routine maintenance!</b><br><br>VHCS is not available for system changes<br><br>Pleas contact your hosting service provider for more information<br><br>We apologize for any inconvenience and thank you for understanding'));
}

function read_line($socket) {
    $ch = '';
    $line = '';
    do{
        $ch = socket_read($socket,1);
        $line = $line . $ch;
    } while($ch != "\r");
    return $line;
}

function send_request() {


	global $Version, $VersionH, $BuildDate;

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

    /* send hello query */
    $query = "helo  $Version\r\n";
    socket_write ($socket, $query, strlen ($query));

    /* read one line with helo answer */
    $out = read_line($socket);

    /* send reg check query */
    $query = "execute query\r\n";
    socket_write ($socket, $query, strlen ($query));
    /* read one line key replay */
    $execute_replay = read_line($socket);

    /* send quit query */
    $quit_query = "bye\r\n";
    socket_write ($socket, $quit_query, strlen ($quit_query));
    /* read quit answer */
    $quit_replay = read_line($socket);

    /* analyze key replay */
    $answer = $execute_replay;

    /* close socket */
    socket_close ($socket);

    /* return function result */
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
function decode_idna(&$input)
{

	$IDN = new idna_convert();

	$output = $IDN->decode($input);

	$output = utf8_decode($output);

	if ($output == FALSE){

		return $input;
	}
	else{

		return $output;
	}
}

function get_punny(&$input)
{

	$IDN = new idna_convert();

	$output = utf8_encode($input);

	$output = $IDN->encode($output);

	return $output;

}

function strip_html(&$input)
{
	$output = htmlspecialchars($input, ENT_QUOTES, "UTF-8");
	return $output;
}

function is_number(&$integer) {
	if (preg_match('/^[0-9]+$/', $integer)) {
		return true;
	}
	return false;
}

function is_basicString(&$sting) {
	if (preg_match('=^[a-zA-Z0-9_-]+$=', $sting)) {
		return true;
	}
	return false;
}

function unset_messages ()
{

	if (isset($GLOBALS['user_page_message']))

        unset($GLOBALS['user_page_message']);

	if (isset($GLOBALS['user_updated']))

        unset($GLOBALS['user_updated']);

	if (isset($GLOBALS['dmn_tpl']))

        unset($GLOBALS['dmn_tpl']);


	if (isset($GLOBALS['chtpl']))

        unset($GLOBALS['chtpl']);


	if (isset($GLOBALS['step_one']))

        unset($GLOBALS['step_one']);


	if (isset($GLOBALS['step_two_data']))

        unset($GLOBALS['step_two_data']);


	if (isset($GLOBALS['ch_hpprops']))

        unset($GLOBALS['ch_hpprops']);

	if (isset($GLOBALS['rau3_added']))

        unset($GLOBALS['rau3_added']);

	if (isset($GLOBALS['user_has_domain']))

        unset($GLOBALS['user_has_domain']);

	if (isset($GLOBALS['local_data']))

        unset($GLOBALS['local_data']);

	if (isset($GLOBALS['reseller_added']))

        unset($GLOBALS['reseller_added']);

	if (isset($GLOBALS['user_added']))

        unset($GLOBALS['user_added']);

	if (isset($GLOBALS['aladd']))

        unset($GLOBALS['aladd']);

	if (isset($GLOBALS['edit_ID']))

        unset($GLOBALS['edit_ID']);

	if (isset($GLOBALS['hp_added']))

        unset($GLOBALS['hp_added']);

	if (isset($GLOBALS['aldel']))

        unset($GLOBALS['aldel']);

	if (isset($GLOBALS['hpid']))

        unset($GLOBALS['hpid']);

	if (isset($GLOBALS['user_deleted']))

        unset($GLOBALS['user_deleted']);

	if (isset($GLOBALS['hdomain']))

        unset($GLOBALS['hdomain']);

	if (isset($GLOBALS['aledit']))

        unset($GLOBALS['aledit']);

 	if (isset($GLOBALS['acreated_by']))

        unset($GLOBALS['acreated_by']);

 	if (isset($GLOBALS['dhavesub']))

        unset($GLOBALS['dhavesub']);

 	if (isset($GLOBALS['ddel']))

        unset($GLOBALS['ddel']);

 	if (isset($GLOBALS['dhavealias']))

        unset($GLOBALS['dhavealias']);

 	if (isset($GLOBALS['dadel']))

        unset($GLOBALS['dadel']);

	if (isset($GLOBALS['local_data']))

        unset($GLOBALS['local_data']);

//

	if (isset($_SESSION['reseller_added']))

        unset($_SESSION['reseller_added']);

	if (isset($_SESSION['dmn_name']))

        unset($_SESSION['dmn_name']);

	if (isset($_SESSION['rau3_added']))

        unset($_SESSION['rau3_added']);

	if (isset($_SESSION['user_has_domain']))

        unset($_SESSION['user_has_domain']);

	if (isset($_SESSION['user_added']))

        unset($_SESSION['user_added']);

	if (isset($_SESSION['aladd']))

        unset($_SESSION['aladd']);

	if (isset($_SESSION['edit_ID']))

        unset($_SESSION['edit_ID']);

	if (isset($_SESSION['hp_added']))

        unset($_SESSION['hp_added']);

	if (isset($_SESSION['aldel']))

        unset($_SESSION['aldel']);

	if (isset($_SESSION['aldel']))

        unset($_SESSION['aldel']);

	if (isset($_SESSION['hpid']))

        unset($_SESSION['hpid']);

	if (isset($_SESSION['user_deleted']))

        unset($_SESSION['user_deleted']);

	if (isset($_SESSION['hdomain']))

        unset($_SESSION['hdomain']);

	if (isset($_SESSION['aledit']))

        unset($_SESSION['aledit']);

 	if (isset($_SESSION['acreated_by']))

        unset($_SESSION['acreated_by']);

 	if (isset($_SESSION['dhavesub']))

        unset($_SESSION['dhavesub']);

 	if (isset($_SESSION['ddel']))

        unset($_SESSION['ddel']);

 	if (isset($_SESSION['dhavealias']))

        unset($_SESSION['dhavealias']);

 	if (isset($_SESSION['dadel']))

        unset($_SESSION['dadel']);

	if (isset($_SESSION['local_data']))

        unset($_SESSION['local_data']);


}

?>
