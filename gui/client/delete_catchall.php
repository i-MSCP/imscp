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



require '../include/ispcp-lib.php';

check_login(__FILE__);

if (isset($_GET['id']) && $_GET['id'] !== '') {
  $mail_id = $_GET['id'];
  $item_delete_status = Config::get('ITEM_DELETE_STATUS');
  $dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

  $query = <<<SQL_QUERY
        select
             mail_id
        from
            mail_users
        where
            domain_id = ?
          and
            mail_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($dmn_id, $mail_id));

  if ($rs -> RecordCount() == 0) {
    user_goto('catchall.php');
  }

  check_for_lock_file();

  $query = <<<SQL_QUERY
        update
            mail_users
        set
            status = ?
        where
            mail_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($item_delete_status, $mail_id));

  send_request();
  write_log($_SESSION['user_logged'].": delete email catch all!");
  set_page_message(tr('Catch all account scheduled for deletion!'));
  user_goto('catchall.php');

} else {
  user_goto('catchall.php');
}

?>
