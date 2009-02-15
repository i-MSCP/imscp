<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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

require '../include/ispcp-lib.php';

check_login(__FILE__);

if (isset($_GET['id']) && $_GET['id'] !== '') {

	$id = $_GET['id'];
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

	// ltes see the status of this thing
	$query = <<<SQL_QUERY
        select
            status
        from
            htaccess
      where
         	id = ?
		 and
		 	dmn_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($id, $dmn_id));
	$status = $rs->fields['status'];
	$ok_status = Config::get('ITEM_OK_STATUS');
	if ($status !== $ok_status) {
		set_page_message(tr('Protected area status should be OK if you want to delete it!'));
		header( "Location: protected_areas.php");
		die();
	}

	$query = <<<SQL_QUERY
      update
          htaccess
      set
          status = '$delete_status'
      where
         	id = ?
		 and
		 	dmn_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($id, $dmn_id));
	check_for_lock_file();
	send_request();

	write_log($_SESSION['user_logged'].": deletes protected area with ID: ".$_GET['id']);
	set_page_message(tr('Protected area deleted successfully!'));
	user_goto('protected_areas.php');
} else {
  set_page_message(tr('Permission deny!'));
  user_goto('protected_areas.php');
}

?>