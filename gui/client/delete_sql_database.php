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

if (isset($_GET['id'])) {

    $db_id = $_GET['id'];



} else {

    user_goto('manage_sql.php');

}




$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

check_db_sql_perms($sql, $db_id);

delete_sql_database($sql, $dmn_id, $db_id);

set_page_message(tr('SQL database was removed successfully!'));

user_goto('manage_sql.php');

?>
