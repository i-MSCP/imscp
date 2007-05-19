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

function init_login() {

	global $cfg;

	if ($cfg['BRUTEFORCE'] != 1) return;

	unblock($cfg['BRUTEFORCE_BLOCK_TIME']);

	$ipaddr = getipaddr();

	if (is_ipaddr_blocked($ipaddr)) {

		block_ipaddr($ipaddr);

	}
}

function register_user($uname, $upass) {

  global $sql, $cfg;

	$ipaddr = getipaddr();

	check_ipaddr($ipaddr);

	if (!username_exists($uname)) {

		write_log("Login error, <b><i>".htmlspecialchars($uname, ENT_QUOTES, "UTF-8")."</i></b> unknown username");

    return false;

  }

	$udata = array();

  $udata = get_userdata($uname);

  if ($cfg['SERVICEMODE'] == 1 AND $udata['admin_type'] != 'admin') {

		write_log("Login error, <b><i>".htmlspecialchars($uname, ENT_QUOTES, "UTF-8")."</i></b> system currently in servicemode");

  	system_message(tr('System is currently in servicemode! At time only administrators can login.'));

		return false;

	}

  if (crypt($upass, $udata['admin_pass']) == $udata['admin_pass'] || md5($upass) == $udata['admin_pass']) {

  	if (isset($_SESSION['user_logged'])) {

			write_log(htmlspecialchars($uname, ENT_QUOTES, "UTF-8")." user already logged or session sharing problem! Aborting...");

      system_message(tr('User already logged or session sharing problem! Aborting...'));

   		return false;

   	}

		if (!is_userdomain_ok($uname)) {

			write_log(htmlspecialchars($uname, ENT_QUOTES, "UTF-8")." Domain status is not OK - user can not login");

      system_message(tr('Domain status is not OK - Login aborted.'));

			return false;

		}

		$sess_id = session_id();

		$query = <<<SQL_QUERY
			update
				login
			set
				user_name = ?,
				lastaccess = ?
			where
				session_id = ?
SQL_QUERY;

		exec_query($sql, $query, array($uname, time(), $sess_id));

    $_SESSION['user_logged'] = $udata['admin_name'];

    $_SESSION['user_pass'] = $udata['admin_pass'];

    $_SESSION['user_type'] = $udata['admin_type'];

    $_SESSION['user_id'] = $udata['admin_id'];

    $_SESSION['user_created_by'] = $udata['created_by'];

    $_SESSION['user_login_time'] = time();

		write_log(htmlspecialchars($uname, ENT_QUOTES, "UTF-8")." user logged in.");

    return true;

	} else {

		write_log( htmlspecialchars($uname, ENT_QUOTES, "UTF-8")." bad password login data.");

  	return false;

	}

}

function check_user_login() {

	global $cfg, $sql;

  $sess_id = session_id();

	/* kill timedout sessions */
	do_session_timeout();

	$user_logged = isset($_SESSION['user_logged'])? $_SESSION['user_logged'] : false;


  if (!$user_logged) {

//		write_log(htmlspecialchars($user_logged, ENT_QUOTES, "UTF-8") . " bad session data.");

   	 	return false;
  	}

  $user_pass = $_SESSION['user_pass'];

  $user_type = $_SESSION['user_type'];

  $user_id = $_SESSION['user_id'];

		// verify sessiondata with database
		$query = <<<SQL_QUERY
    	select
	   		*
      from
	   		admin, login
      where
      	admin.admin_name = ?
			and
				admin.admin_pass = ?
      and
      	admin.admin_type = ?
      and
      	admin.admin_id = ?
      and
      	login.session_id = ?
SQL_QUERY;

  	$rs = exec_query($sql, $query, array($user_logged, $user_pass, $user_type, $user_id, $sess_id));

  	if ($rs -> RecordCount() != 1) {

    	write_log(htmlspecialchars($user_logged, ENT_QUOTES, "UTF-8") . " session manipulating detected !");

      return false;
 		}

  	if ($cfg['SERVICEMODE'] == 1 AND $user_type != 'admin') {

  	    unset_user_login_data();

			write_log("<b><i>".htmlspecialchars($user_logged, ENT_QUOTES, "UTF-8")."</i></b> system currently in servicemode. User logged out...");

			header("Location: ../index.php");

			return false;

		}
		/* userlogindata correct - update session and lastaccess */
		$_SESSION['user_login_time'] = time();

		$query = <<<SQL_QUERY
    	update
      	login
      set
      	lastaccess = ?
      where
      	session_id = ?
SQL_QUERY;

			exec_query($sql, $query, array(time(), $sess_id));

      goto_user_location();

      return true;

}

function goto_user_location() {

	$path = explode("/", $_SERVER['SCRIPT_NAME']);
  	$found = false;

  	for($i = count($path) - 2 ; $i < count($path); $i++) {
  		if($path[$i] == $_SESSION['user_type']){
    		$found= true;
    	}
		else if ($_SESSION['user_type'] == 'user' && $path[$i] == 'client') {
    		$found= true;
  		}
	}

	if(!$found) {
		if ($_SESSION['user_type'] == 'admin') {
    		header("Location: ../admin/manage_users.php");
	    	die();
    	}
		else if ($_SESSION['user_type'] == 'reseller') {
	    	header("Location: ../reseller/index.php");
		    die();
	    }
		else if ($_SESSION['user_type'] == 'user') {
	    	header("Location: ../client/index.php");
		    die();
	    }
  	}
}

function check_login () {

    if (!check_user_login()) { //check_user_login already performs all the check
        header("Location: ../index.php");
        die();
    }
}

function change_user_interface($form_id, $to_id) {

	global $sql, $cfg;

	if ($cfg['DB_TYPE'] === 'mysql') {

		$query_from = "select admin_id, admin_name, admin_pass, admin_type, created_by from admin where binary admin_id = ?";

    	$query_to = "select admin_id, admin_name, admin_pass, admin_type, created_by from admin where binary admin_id = ?";

	}
	else {
		die ("Other Databases than MySQL are not supported");
	}

  	$rs_from = exec_query($sql, $query_from, array($form_id));

  	$rs_to = exec_query($sql, $query_to, array($to_id));

  	if (($rs_from -> RecordCount()) != 1 || ($rs_to -> RecordCount()) != 1)  {

		write_log("Change interface error => unknown from or to username");

    	return false;

	}


  	$from_udata = $rs_from -> FetchRow();

  	$to_udata = $rs_to -> FetchRow();

	if (!is_userdomain_ok($to_udata['admin_name'])) {

		write_log("Domain ID: ".$to_udata['admin_id']." - domain status PROBLEM -");

		return false;

	}

	if ($from_udata['admin_type'] === 'admin' && $to_udata['admin_type'] === 'reseller') {

  	$header = "../reseller/index.php";

	} else if ($from_udata['admin_type'] === 'admin' && ($to_udata['admin_type'] != 'admin' || $to_udata['admin_type'] != 'reseller')) {

  		$header = "../client/index.php";

	} else if ($from_udata['admin_type'] === 'reseller' && ($to_udata['admin_type'] != 'admin' || $to_udata['admin_type'] != 'reseller')) {

  		$header = "../client/index.php";

	// lets check and go from bottom to top User -> Reseller -> Admin
	// there is SESSION 'logged from' -> we can go from Buttom to TOP
	} else if (isset($_SESSION['logged_from'])) {

		if ($from_udata['admin_type'] === 'reseller' && $to_udata['admin_type'] == 'admin') {

			$header = "../admin/manage_users.php";
		// user to admin
		}	else if (($from_udata['admin_type'] != 'admin' || $from_udata['admin_type'] != 'reseller') && $to_udata['admin_type'] === 'admin') {

			$header = "../admin/manage_users.php";
		// user reseller
		} else if (($from_udata['admin_type'] != 'admin' || $from_udata['admin_type'] != 'reseller') && $to_udata['admin_type'] === 'reseller') {

			$header = "../reseller/users.php";

		}	else {

			write_log("change interface error from: ".$from_udata['admin_name']." to: ".$to_udata['admin_name']);

			return false;

		}

	} else {

		write_log("change interface error from: ".$from_udata['admin_name']." to: ".$to_udata['admin_name']);

		return false;
	}

	// lets save layout and language from admin/reseler - they don't wannt to read user interface on china or arabic language
	$user_language = $_SESSION['user_def_lang'];

	$user_layout = $_SESSION['user_theme'];


	// delete all sessions and globals data and set new one with SESSION logged_from
	unset_user_login_data();
// start new session here
// session_start();

	if ($to_udata['admin_type'] != 'admin') {

		$_SESSION['logged_from'] = $from_udata['admin_name'];

		$_SESSION['logged_from_id'] = $from_udata['admin_id'];

	}

	// we gonna kill all sessions and globals if user get back to admin level
	if (isset($_SESSION['admin_name']))

		unset($_SESSION['admin_name']);

	if (isset($_SESSION['admin_id']))

		unset($_SESSION['admin_id']);

	if (isset($GLOBALS['admin_name']))

		unset($GLOBALS['admin_name']);

	if (isset($GLOBALS['admin_id']))

		unset($GLOBALS['admin_id']);
		// no more sessions and globals to kill - they were always killed - rest in peace

	$_SESSION['user_logged'] = $to_udata['admin_name'];

	$_SESSION['user_pass'] = $to_udata['admin_pass'];

	$_SESSION['user_type'] = $to_udata['admin_type'];

	$_SESSION['user_id'] = $to_udata['admin_id'];

	$_SESSION['user_created_by'] = $to_udata['created_by'];

	$_SESSION['user_login_time'] = time();

	$_SESSION['user_def_lang'] = $user_language;

	$_SESSION['user_theme'] = $user_layout;

	$user_login_time = time();

	$sess_id = session_id();

	$new_user_name = $to_udata['admin_name'];

	$query = <<<SQL_QUERY
  	insert into login
    	(session_id, user_name, lastaccess)
    values
    	(?, ?, ?)
SQL_QUERY;

	$rs = exec_query($sql, $query, array($sess_id, $new_user_name, $user_login_time));

 	write_log($from_udata['admin_name']." change into interface from ".$to_udata['admin_name']);

	return $header;

}

function unset_user_login_data () {
	global $cfg, $sql;

	if (isset($_SESSION['user_logged'])) {

		$sess_id = session_id();

		$admin_name = $_SESSION['user_logged'];

		$query = <<<SQL_QUERY
			delete from
		  	login
		  where
		  	session_id = ?
		  and
		  	user_name = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($sess_id, $admin_name));

	}

	$lang = $_SESSION['user_def_lang'];

	$_SESSION = array();

	$_SESSION['user_def_lang'] = $lang;

}

function redirect_to_level_page()
{
    if (!isset($_SESSION['user_type']))
    return false;

    $user_type = $_SESSION['user_type'];

    switch ($user_type) {
        case 'user':
            $user_type = 'client';
        case 'admin':
        case 'reseller':
            header('Location: ' . $user_type . '/index.php');
            break;
        default:
            die("FIX ME! " . __FILE__ . ":" . __LINE__);
            break;
    }
    exit;
}

?>