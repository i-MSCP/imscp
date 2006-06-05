<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            		|
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

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

if(isset($_GET['hpid']) && is_numeric($_GET['hpid']))
	$hpid = $_GET['hpid'];
else{
	$_SESSION['hp_deleted'] = '_no_';
	Header("Location: hp.php");
	die();
}

// Try to delete hosting plan from db
//
$query = "delete from hosting_plans where id=?";
$res = exec_query($sql, $query, array($hpid));

$_SESSION['hp_deleted'] = '_yes_';

Header("Location: hp.php");

die();

?>
