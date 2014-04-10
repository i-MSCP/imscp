<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if (isset($_GET['delete_id'])) {
	$deleteIpId = clean_input($_GET['delete_id']);

	$query = "SELECT `reseller_ips` FROM `reseller_props`";
	$stmt = execute_query($query);

	while (!$stmt->EOF) {
		if (in_array($deleteIpId, explode(';', $stmt->fields['reseller_ips']))) {
			set_page_message(tr("The IP address you're trying to remove is assigned to a reseller."), 'error');
			redirectTo('ip_manage.php');
		}

		$stmt->moveNext();
	}

	$query = "SELECT count(`ip_id`) `ipsTotalCount` FROM `server_ips`";
	$stmt = execute_query($query);

	if ($stmt->fields['ipsTotalCount'] < 2) {
		set_page_message(tr('You cannot delete the last active IP address.'), 'error');
		redirectTo('ip_manage.php');
	}

	write_log("{$_SESSION['user_logged']}: deleted IP address {$stmt->fields['ipNumber']}", E_USER_NOTICE);

	$query = "UPDATE `server_ips` SET `ip_status` = ? WHERE `ip_id` = ?";
	$stmt = exec_query($query, array('todelete', $deleteIpId));

	send_request();

	set_page_message(tr('IP address successfully scheduled for deletion.'), 'success');

	redirectTo('ip_manage.php');
}

showBadRequestErrorPage();
