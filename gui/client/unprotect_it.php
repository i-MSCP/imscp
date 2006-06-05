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


/* do we have a proper cdir? */
if (!isset($_GET['cdir'])) {
	header( "Location: protected_areas.php" );
	die();
}
$domain_name = $_SESSION['user_logged'];
$cdir = $_GET['cdir'];
global $cfg;
$homedir = $cfg['FTP_HOMEDIR'];

unlink($cfg['FTP_HOMEDIR'].'/'.$domain_name.$cdir.'.htaccess');

set_page_message( tr('Protected area was deleted successful!'));

header( "Location: protected_areas.php?cur_dir=$cdir" );
die();



if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();
?>