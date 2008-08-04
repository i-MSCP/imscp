<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
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

function init_login() {
	// just make sure to expire counters in case BRUTEFORCE is turned off
	unblock(Config::get('BRUTEFORCE_BLOCK_TIME'));

	if (Config::get('BRUTEFORCE')) {
		is_ipaddr_blocked(null, 'bruteforce', true);
	}
}

function register_user($uname, $upass) {
    $sql = Database::getInstance();

    check_ipaddr();

    if (!username_exists($uname)) {
        write_log("Login error, <b><i>".$uname."</i></b> unknown username");
        return false;
    }

    $udata = array();
    $udata = get_userdata($uname);

  	if (Config::get('MAINTENANCEMODE') && $udata['admin_type'] != 'admin') {
		write_log("Login error, <b><i>".$uname."</i></b> system currently in maintenance mode");
  		system_message(tr('System is currently under maintenance! Only administrators can login.'));
		return false;
	}

	if (crypt($upass, $udata['admin_pass']) == $udata['admin_pass'] || md5($upass) == $udata['admin_pass']) {

	    if (isset($_SESSION['user_logged'])) {
	        write_log(tr("%s user already logged or session sharing problem! Aborting...", $uname));
	        system_message(tr('User already logged or session sharing problem! Aborting...'));
	        unset_user_login_data();
	        return false;
	    }

	    if (!is_userdomain_ok($uname)) {
	        write_log(tr("%s's account status is not ok!", $uname));
	        system_message(tr("%s's account status is not ok!", $uname));
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
	    $_SESSION['user_email'] = $udata['email'];
	    $_SESSION['user_created_by'] = $udata['created_by'];
	    $_SESSION['user_login_time'] = time();

	    write_log($uname." logged in.");
	    return true;
	} else {
		$backButtonDestination = 'http://' . Config::get('BASE_SERVER_VHOST');

		write_log($uname . ' entered incorrect password.');
		system_message(tr('You entered an incorrect password.'), $backButtonDestination);
  		return false;
	}

}

function check_user_login() {
    $sql = Database::getInstance();

    $sess_id = session_id();
    /* kill timed out sessions */
    do_session_timeout();
    $user_logged = isset($_SESSION['user_logged']) ? $_SESSION['user_logged'] : false;

    if (!$user_logged) {
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

    if ($rs->RecordCount() != 1) {
        write_log("Detected session manipulation on $user_logged's session!");
        unset_user_login_data();
        return false;
    }

    if (Config::get('MAINTENANCEMODE') && $user_type != 'admin') {
        unset_user_login_data(true);
        write_log("System is currently in maintenance mode. Logging out <b><i>".$user_logged."</i></b>");
        header("Location: /index.php");
        die();
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
    return true;
}

function check_login($fName = null, $checkReferer = true) {

    // session-type check:
    if (!check_user_login()) {
        header("Location: /index.php");
        die();
    }

    if ($fName != null) {
        $levels = explode('/', realpath(dirname($fName)));
        $level = $levels[count($levels) - 1];

        switch ($level) {
            case 'user':
                $level = 'client';
            case 'admin':
            case 'reseller':
                if ($level != $_SESSION['user_type']) {
                    write_log('Warning! user |'.$_SESSION['user_logged'].'| requested |'.$_SERVER["REQUEST_URI"].'| with REQUEST_METHOD |'.$_SERVER["REQUEST_METHOD"].'|');
                    header("Location: /index.php");
                    die();
                }
                break;
        }
    }

    if ($checkReferer) {
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {

            $info = parse_url($_SERVER['HTTP_REFERER']);
            if (isset($info['host']) && !empty($info['host'])) {
                if ($info['host'] != $_SERVER['HTTP_HOST'] || $info['host'] != $_SERVER['SERVER_NAME']) {
                    set_page_message(tr('Request from foreign host was blocked!'));
                if(!(substr($_SERVER['SCRIPT_FILENAME'], (int)-strlen($_SERVER['REDIRECT_URL']), strlen($_SERVER['REDIRECT_URL'])) === $_SERVER['REDIRECT_URL']))
                    redirect_to_level_page();
                }
            }

        }
    }
}

function change_user_interface($from_id, $to_id) {
    $sql = Database::getInstance();

    $index = null;
    while (1) { //used to easily exit
        $query   = 'select admin_id, admin_name, admin_pass, admin_type, email, created_by from admin where binary admin_id = ?';

        $rs_from = exec_query($sql, $query, array($from_id));
        $rs_to   = exec_query($sql, $query, array($to_id));

        if (($rs_from -> RecordCount()) != 1 || ($rs_to -> RecordCount()) != 1)  {
            set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
            break;
        }

        $from_udata = $rs_from->FetchRow();
        $to_udata   = $rs_to->FetchRow();

        if (!is_userdomain_ok($to_udata['admin_name'])) {
            set_page_message(tr("%s's account status is not ok!", decode_idna($to_udata['admin_name'])));
            break;
        }

        $to_admin_type   = strtolower($to_udata['admin_type']);
        $from_admin_type = strtolower($from_udata['admin_type']);

        $allowed_changes = array();

        $allowed_changes['admin']['admin']         = 'manage_users.php';
        $allowed_changes['admin']['BACK']          = 'manage_users.php';
        $allowed_changes['admin']['reseller']      = 'index.php';
        $allowed_changes['admin']['user']          = 'index.php';
        $allowed_changes['reseller']['user']       = 'index.php';
        $allowed_changes['reseller']['BACK']       = 'users.php';

        if (!isset($allowed_changes[$from_admin_type][$to_admin_type]) ||
           ($to_admin_type == $from_admin_type && $from_admin_type != 'admin')) {

            if (isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] == $to_id) {
                $index = $allowed_changes[$to_admin_type]['BACK'];
            } else {
                set_page_message(tr('You do not have permission to access this interface!'));
                break;
            }
        }

        $index = $index ? $index : $allowed_changes[$from_admin_type][$to_admin_type];

	unset_user_login_data();

        if (($to_admin_type != 'admin' &&
            ((isset($_SESSION['logged_from_id']) && $_SESSION['logged_from_id'] != $to_id) ||
              !isset($_SESSION['logged_from_id'])))
            || ($from_admin_type == 'admin' && $to_admin_type == 'admin')) {

            $_SESSION['logged_from'] = $from_udata['admin_name'];
            $_SESSION['logged_from_id'] = $from_udata['admin_id'];

        }
	if ($from_admin_type == 'user') { // Ticket 830 - remove the 'logged_from' if back from user
	    unset($_SESSION['logged_from']);  // maybe integrated in the construction above...
	    unset($_SESSION['logged_from_id']);
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
        $_SESSION['user_email'] = $to_udata['email'];
        $_SESSION['user_created_by'] = $to_udata['created_by'];
        $_SESSION['user_login_time'] = time();

        $query = 'INSERT INTO login (session_id, user_name, lastaccess) VALUES (?, ?, ?) ';

        exec_query($sql, $query, array(session_id(), $to_udata['admin_name'], $_SESSION['user_login_time']));

        write_log(sprintf("%s changes into %s's interface", decode_idna($from_udata['admin_name']), decode_idna($to_udata['admin_name'])));
        break;
    }

    redirect_to_level_page($index);
}

function unset_user_login_data ($ignorePreserve = false) {
	$sql = Database::getInstance();

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

	$preserve_list = array('user_def_lang', 'user_theme');
    $preserve_vals = array();

    if (!$ignorePreserve) {
        foreach ($preserve_list as $p) {
            if (isset($_SESSION[$p])) {
                $preserve_vals[$p] = $_SESSION[$p];
            }
        }
    }

    $_SESSION = array();

    foreach ($preserve_list as $p) {
        if (isset($preserve_vals[$p])) {
            $_SESSION[$p] = $preserve_vals[$p];
        }
    }

}

function redirect_to_level_page($file = null, $force = false) {

    if (!isset($_SESSION['user_type']) && !$force)
    	return false;

    if (!$file) {
        $file = 'index.php';
    }

    $user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

    switch ($user_type) {
        case 'user':
            $user_type = 'client';
        case 'admin':
        case 'reseller':
            header('Location: /' . $user_type . '/' . $file);
            break;
        case '':
            header('Location: /index.php');
            break;
        default:
            die("FIXME! " . __FILE__ . ":" . __LINE__);
            break;
    }
    exit();
}

?>