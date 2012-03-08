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
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

$resellerId = $_SESSION['user_id'];

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	$orderId = $_GET['order_id'];
} else {
	set_page_message(tr('Wrong order ID.'), 'error');
	redirectTo('orders.php');
	exit; // Useless but avoid IDE warning about possible undefined variable
}

$query = "SELECT `id` FROM `orders` WHERE `id` = ? AND `user_id` = ?";
$stmt = exec_query($query, array($orderId, $resellerId));

if (!$stmt->rowCount()) {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('orders.php');
}

// delete all FTP Accounts
$query = "DELETE FROM `orders` WHERE `id` = ?";
$stmt = exec_query($query, $orderId);

set_page_message(tr('Customer order sucessfully removed.'), 'success');

write_log($_SESSION['user_logged'] . ": deleted a customer order.", E_USER_NOTICE);
redirectTo('orders.php');
