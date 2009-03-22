<?php
/**
 * ispCP ? (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);


if (!isset($_GET['domain_id'])) {
	header( "Location: manage_users.php" );
	die();
}

if (!is_numeric($_GET['domain_id'])) {
	header( "Location: manage_users.php" );
	die();
}

// so we have domain id and lets disable or enable it
$domain_id = $_GET['domain_id'];

// check status to know if have to disable or enable it
$query = <<<SQL_QUERY
	SELECT
		domain_name,
		domain_status
	FROM
		domain
	WHERE
		domain_id = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($domain_id));

$location = 'admin';

if ($rs->fields['domain_status'] == Config::get('ITEM_OK_STATUS')) {

		//disable_domain($sql, $domain_id, $rs->fields['domain_name']);
		$action = 'disable';
		change_domain_status(&$sql, $domain_id, $rs->fields['domain_name'], $action, $location);

} else if ($rs->fields['domain_status'] == Config::get('ITEM_DISABLED_STATUS')) {

	//enable_domain($sql, $domain_id, $rs->fields['domain_name']);
	$action = 'enable';
	change_domain_status(&$sql, $domain_id, $rs->fields['domain_name'], $action, $location);

} else {
	header('Location: manage_users.php's);
	die();
}
?>