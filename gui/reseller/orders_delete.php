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

$reseller_id = $_SESSION['user_id'];


if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	$order_id = $_GET['order_id'];
} else {
	set_page_message(tr('Wrong order ID!'));
	user_goto('orders.php');
}

$query = <<<SQL_QUERY
	SELECT
		`id`
	FROM
		`orders`
	WHERE
		`id` = ?
	AND
		`user_id` = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($order_id, $reseller_id));

if ($rs->RecordCount() == 0) {
	set_page_message(tr('Permission deny!'));
	user_goto('orders.php');
}

// delete all FTP Accounts
$query = <<<SQL_QUERY
	DELETE FROM
		`orders`
	WHERE
		`id` = ?
SQL_QUERY;
$rs = exec_query($sql, $query, array($order_id));

set_page_message(tr('Customer order was removed successful!'));

write_log($_SESSION['user_logged'].": deletes customer order.");
user_goto('orders.php');
