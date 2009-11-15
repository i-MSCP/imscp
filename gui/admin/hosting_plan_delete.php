<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

if (strtolower(Config::get('HOSTING_PLANS_LEVEL')) != 'admin') {
	user_goto('index.php');
}


if (isset($_GET['hpid']) && is_numeric($_GET['hpid'])) {
	$hpid = $_GET['hpid'];
} else {
	$_SESSION['hp_deleted'] = '_no_';
	user_goto('hosting_plan.php');
}

// Check if there is no order for this plan
$res = exec_query($sql, "SELECT COUNT(`id`) FROM `orders` WHERE `plan_id` = ? AND `status` = 'new'", array($hpid));
$data = $res->FetchRow();

if ($data['0'] > 0) {
	$_SESSION['hp_deleted_ordererror'] = '_yes_';
	user_goto('hosting_plan.php');
}

// Try to delete hosting plan from db
$query = 'DELETE FROM `hosting_plans` WHERE `id` = ?';
$res = exec_query($sql, $query, array($hpid));

$_SESSION['hp_deleted'] = '_yes_';

user_goto('hosting_plan.php');
