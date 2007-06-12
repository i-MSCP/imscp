<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control System
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
 *
 **/


function log_event(&$sql, $date, $user_id, $user_name, $action, $comment) {

    $query = <<<SQL
		insert into
			syslog
				(date,
				user_id,
				user_name,
				action,
				comment)
		values
				('?',
				'?',
				'?',
				'?',
				'?');
SQL;

    $rs = exec_query($sql, $query, array($date, $user_id, $user_name, $action, $comment));

    if (!$rs)
		system_message($sql -> ErrorMsg());

}

?>