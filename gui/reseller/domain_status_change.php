<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
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

if (!isset($_GET['domain_id'])) {
	user_goto('users.php');
}

if (!is_numeric($_GET['domain_id'])) {
	user_goto('users.php');
}

// so we have domain id and let's disable or enable it
$domain_id = $_GET['domain_id'];

// check status to know if have to disable or enable it
$query = <<<SQL_QUERY
	SELECT
		`domain_name`,
		`domain_status`,
		`domain_created_id`
	FROM
		`domain`
	WHERE
		`domain_id` = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($domain_id));

// let's check if this reseller has rights to disable/enable this domain
if ($rs->fields['domain_created_id'] != $_SESSION['user_id']) {
	user_goto('users.php');
}

$location = 'reseller';

if ($rs->fields['domain_status'] == Config::get('ITEM_OK_STATUS')) {

	//disable_domain($sql, $domain_id, $rs->fields['domain_name']);
	$action = "disable";
	change_domain_status(&$sql, &$domain_id, $rs->fields['domain_name'], $action, $location);
} else if ($rs->fields['domain_status'] == Config::get('ITEM_DISABLED_STATUS')) {

	//enable_domain($sql, $domain_id, $rs->fields['domain_name']);
	$action = "enable";
	change_domain_status(&$sql, &$domain_id, $rs->fields['domain_name'], $action, $location);

} else {
	user_goto('users.php');
}
