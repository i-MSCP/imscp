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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2015 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('protected_areas') or showBadRequestErrorPage();

/**
 * @todo check queries if any of them use db prepared statements
 */

if (isset($_GET['id']) && $_GET['id'] !== '') {

	$id = $_GET['id'];
	$delete_status = 'todelete';
	$dmn_id = get_user_domain_id($_SESSION['user_id']);

	// let's see the status of this thing
	$query = "SELECT `status` FROM `htaccess` WHERE `id` = ? AND `dmn_id` = ?";

	$rs = exec_query($query, array($id, $dmn_id));
	$status = $rs->fields['status'];
	$ok_status = 'ok';

	if ($status !== $ok_status) {
		set_page_message(tr("Protected area status should be 'OK' if you want to delete it."), 'error');
		redirectTo('protected_areas.php');
	}

	// TODO use prepared statement for $delete_status
	$query = "
        UPDATE
            `htaccess`
        SET
            `status` = '$delete_status'
        WHERE
            `id` = ?
        AND
            `dmn_id` = ?
    ";

	$rs = exec_query($query, array($id, $dmn_id));
	send_request();

	write_log($_SESSION['user_logged'].": deleted protected area with ID: ".$_GET['id'], E_USER_NOTICE);
	set_page_message(tr('Protected area successfully scheduled for deletion.'), 'success');
	redirectTo('protected_areas.php');
} else {
	set_page_message(tr('You do not have sufficient permissions to perform this operation.'), 'error');
	redirectTo('protected_areas.php');
}
