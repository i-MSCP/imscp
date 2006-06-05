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

if(isset($_GET['id'])) {
  $usid = $_GET['id'];
} else {
  $_SESSION['user_deleted'] = '_no_';
  Header("Location: users.php");
  die();
}

$reseller_id = $_SESSION['user_id'];

$query = <<<SQL_QUERY
    select
        domain_id
    from
        domain
    where
        domain_admin_id = ?
      and
        domain_created_id = ?
SQL_QUERY;
  $res = exec_query($sql, $query, array($usid, $reseller_id));

  if ($res -> RowCount() !== 1) {
    Header("Location: users.php");
    die();
  } else {
    // delete the user
    rm_rf_user_account ($usid);
    check_for_lock_file();
    send_request();
    set_page_message(tr('User terminated!'));
    header("Location: users.php");
    die();
  }

?>
