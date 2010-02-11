<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$theme_color = Config::get('USER_INITIAL_THEME');

if (isset($_GET['id'])) {
	$usid = $_GET['id'];
} else {
	$_SESSION['user_deleted'] = '_no_';
	user_goto('users.php');
}

$reseller_id = $_SESSION['user_id'];

$query = <<<SQL_QUERY
	SELECT
		`domain_id`
	FROM
		`domain`
	WHERE
		`domain_admin_id` = ?
	AND
		`domain_created_id` = ?
SQL_QUERY;
$res = exec_query($sql, $query, array($usid, $reseller_id));

if ($res->RowCount() !== 1) {
	user_goto('users.php');
} else {
	// delete the user
	rm_rf_user_account($usid);
	send_request();
	set_page_message(tr('User terminated!'));
	user_goto('users.php');
}
