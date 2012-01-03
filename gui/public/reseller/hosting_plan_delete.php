<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

if (isset($_GET['hpid']) && is_numeric($_GET['hpid']))
	$hpid = $_GET['hpid'];
else {
	$_SESSION['hp_deleted'] = '_no_';
	redirectTo('hosting_plan.php');
}

// Check if there is no order for this plan
$res = exec_query("SELECT COUNT(`id`) FROM `orders` WHERE `plan_id` = ?", $hpid);
$data = $res->fetchRow();

if ($data['0'] > 0) {
	$_SESSION['hp_deleted_ordererror'] = '_yes_';
	redirectTo('hosting_plan.php');
}

// Try to delete hosting plan from db
$query = "DELETE FROM `hosting_plans` WHERE `id` = ? AND `reseller_id` = ?";
$res = exec_query($query, array($hpid, $_SESSION['user_id']));

$_SESSION['hp_deleted'] = '_yes_';

redirectTo('hosting_plan.php');
