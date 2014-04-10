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
 * @category    iMSCP
 * @package     Client_Mail
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
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('mail') or showBadRequestErrorPage();

if (isset($_GET['id'])) {
	$catchallId = clean_input($_GET['id']);

	$query = "SELECT `mail_id` FROM `mail_users` WHERE `domain_id` = ? AND `mail_id` = ?";

	$stmt = exec_query($query, array(get_user_domain_id($_SESSION['user_id']), $catchallId));

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$query = "UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?";
	exec_query($query, array('todelete', $catchallId));

	send_request();

	write_log($_SESSION['user_logged'] . ': deletes email catch all!', E_USER_NOTICE);
	set_page_message(tr('Catch all successfully scheduled for deletion.'), 'success');
	redirectTo('mail_catchall.php');
}

showBadRequestErrorPage();
