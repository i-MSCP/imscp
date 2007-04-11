<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
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


include '../include/ispcp-lib.php';

check_login();

// lets back to admin interfase - am i admin or what ? :-)

if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id']) && isset($_GET['action']) && $_GET['action'] == "go_back") {

	$from_id = $_SESSION['user_id'];

	$to_id = $_SESSION['logged_from_id'];

	$dest = change_user_interface($from_id, $to_id);

	if ($dest == false){

		header('Location: index.php');

		die();

	} else {

		header("Location: $dest");

		die();

	}

}

// lets go to User interface - we have to check if this reseller can access thes user
else if (isset($_SESSION['user_id']) && isset($_GET['to_id'])) {

	$to_id = $_GET['to_id'];

	if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {

		$from_id = $_SESSION['logged_from_id'];

	} else {

		$from_id = $_SESSION['user_id'];

		$query = <<<SQL_QUERY
    		    	SELECT
            		admin_id
        			FROM
            		admin
        			WHERE
            		admin_id = ?
          		AND
            		created_by = ?
SQL_QUERY;

		$rs = exec_query($sql, $query, array($to_id, $from_id));

		//lets che if user who we want to crack exist
    if ($rs -> RowCount() == 0) {

			set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

			header('Location: users.php');

			die();

		}

	}

	$dest = change_user_interface($from_id, $to_id);

	if ($dest == false) {

		header('Location: users.php');

		die();

	} else {

		header("Location: $dest");

		die();

	}

} else {

	header('Location: index.php');

	die();

}

?>