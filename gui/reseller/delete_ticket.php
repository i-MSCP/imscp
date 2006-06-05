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

if (isset($_GET['ticket_id']) && $_GET['ticket_id'] !== '') {

$ticket_id = $_GET['ticket_id'];

$user_id = $_SESSION['user_id'];

$query = <<<SQL_QUERY
    	select
            ticket_status
      from
            tickets
      where
            ticket_id = ?
        and
            (ticket_from = ? or ticket_to = ?)
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ticket_id, $user_id, $user_id));

	if ($rs -> RecordCount() == 0) {

			header('Location: support_system.php');
			die();
		}
	$ticket_status = $rs -> fields['ticket_status'];

	if($ticket_status == 0){
		$back_url = "ss_closed.php";
	}
	else{
		$back_url = "support_system.php";
	}


    global $cfg;

    $ticket_id = $_GET['ticket_id'];


		$query = <<<SQL_QUERY
    	delete from
        	tickets
      where
        	ticket_id = ?
        or
          ticket_reply = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($ticket_id, $ticket_id));

	while (!$rs -> EOF)
	{
		$rs -> MoveNext();
	}

	set_page_message(tr('Support ticket deleted successfully!'));

    user_goto($back_url);

}
elseif (isset($_GET['delete']) && $_GET['delete'] == 'open') {

	$user_id = $_SESSION['user_id'];

  $query = <<<SQL_QUERY
    	delete from
        	tickets
      where
        	(ticket_from = ? or ticket_to = ?)
        and
          ticket_status != '0'
SQL_QUERY;

		$rs = exec_query($sql, $query, array($user_id, $user_id));

		while (!$rs -> EOF) {

		$rs -> MoveNext();
		}
		set_page_message(tr('All open support tickets deleted successfully!'));

    user_goto('support_system.php');

}
elseif (isset($_GET['delete']) && $_GET['delete'] == 'closed') {

	$user_id = $_SESSION['user_id'];

  $query = <<<SQL_QUERY
    	delete from
        	tickets
      where
        	(ticket_from = ? or ticket_to = ?)
        and
          (ticket_status = '0' or ticket_status = '2' or ticket_status = '4')
SQL_QUERY;
		$rs = exec_query($sql, $query, array($user_id, $user_id));

		while (!$rs -> EOF) {

		$rs -> MoveNext();
		}
		set_page_message(tr('All closed support tickets deleted successfully!'));

    user_goto('ss_closed.php');

}



else {

    user_goto('support_system.php');

}


?>
