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


// wee need to check only if all vars are OK
// admin can walk over all interfaces :-)

if (isset($_SESSION['user_id']) && isset($_GET['to_id'])) {

	$from_id = $_SESSION['user_id'];

	$to_id = $_GET['to_id'];

	//lets che if user who we want to crack exist

	  $query = <<<SQL_QUERY
        select
            admin_id
        from
            admin
        where
            admin_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($to_id));

    if ($rs -> RowCount() == 0) {

		set_page_message(tr('User does not exist!'));
		header('Location: manage_users.php');
		die();
	}


	$dest = change_user_interface($from_id, $to_id);

	if ($dest == false){

		header('Location: manage_users.php');
		die();

	} else {

        header("Location: $dest");
        die();
	}

}
else {
	header('Location: manage_users.php');
	die();

}
?>