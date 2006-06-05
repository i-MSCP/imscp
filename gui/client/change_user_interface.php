<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware	|
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



include '../include/vhcs-lib.php';

check_login();

// lets back to admin or reseller interfase - am i admin/reseller or what ? :-)

if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id']) && isset($_GET['action']) && $_GET['action'] == "go_back") {

	// SESSIONS are OK -> so lets go back

	$from_id = $_SESSION['user_id'];
	
	$to_id = $_SESSION['logged_from_id'];

	// SESSIONS are OK -> so lets go back
	$dest = change_user_interface($from_id, $to_id);
	
	if ($dest == false){
	
		//dumpass - don't try to change your interface
		header('Location: index.php');
		die();
	
	} else {
	// ------------------------------------------				
	// ------------------------------------------		
		if (isset($_SESSION['logged_from']))
		
			unset($_SESSION['logged_from']);
				
		if (isset($_SESSION['logged_from_id']))
		
			unset($_SESSION['logged_from_id']);
	// ------------------------------------------				
	// ------------------------------------------
		if (isset($GLOBALS['logged_from']))
		
			unset($GLOBALS['logged_from']);
				
		if (isset($GLOBALS['logged_from_id']))
		
			unset($GLOBALS['logged_from_id']);
	// ------------------------------------------				
	// ------------------------------------------
		
        header("Location: $dest");
	
	}
        die();

}
//dumpass - don't try to change your interface
else {
	header('Location: index.php');
	die();

}

?>