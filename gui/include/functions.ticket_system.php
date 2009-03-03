<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2009 by ispCP | http://isp-control.net
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

/**
 * Gets the last modifikation date of a ticket.
 *
 * @author		ispCP Team
 * @author		Benedikt Heintel
 * @copyright 	2006-2009 by ispCP | http://isp-control.net
 * @version		1.0
 *
 * @access	public
 * @param 	reference	$sql		reference to sql connection
 * @param	int			$ticket_id	ticket to get last date for
 * @return 	date					last date
 */
function ticketGetLastDate(&$sql, $ticket_id) {
	$query = <<<SQL_QUERY
		SELECT
			`ticket_date`
		FROM
			`tickets`
		WHERE
			`ticket_id` = ?
		OR
			`ticket_reply` = ?
		ORDER BY
			`ticket_date` DESC
SQL_QUERY;

	$rs = exec_query($sql, $query, array($ticket_id, $ticket_id));

	$date_formt = Config::get('DATE_FORMAT');
	$last_date = date($date_formt, $rs->fields['ticket_date']);
	return $last_date;
}

?>