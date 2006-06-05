<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware							|
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

if (isset($_GET['id']) && $_GET['id'] !== '') {
  $als_id = $_GET['id'];
  $dom_id = get_user_domain_id($sql, $_SESSION['user_id']);
  check_for_lock_file();
  $query = <<<SQL_QUERY
  	SELECT
	 *
	FROM
	 domain_aliasses
	WHERE
	 alias_id = ?
	AND
	 domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($als_id, $dom_id));
	$alias_name = $rs->fields['alias_name'];

	if ($rs -> RecordCount() == 0) {
		set_page_message(tr('You have no permission to disable this alias forward'));
		header("Location: manage_domains.php");
		die();
	}

  $query = <<<SQL_QUERY
        update
            domain_aliasses
        set
            url_forward = 'no',
            alias_status = 'change'
        where
            alias_id = ?
		and
			domain_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($als_id, $dom_id));

  send_request();
  write_log($_SESSION['user_logged'].": change domain alias forward: ".$alias_name);
  set_page_message(tr('Alias scheduled for modification!'));
  header('Location: manage_domains.php');
  exit(0);

} else {

  header('Location: manage_domains.php');
  exit(0);
}

?>
