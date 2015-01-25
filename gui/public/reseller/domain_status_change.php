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
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2015 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (isset($_GET['domain_id'])) {
	$domainId = intval($_GET['domain_id']);
	$resellerId = intval($_SESSION['user_id']);

	$stmt = exec_query(
		'
			SELECT
				admin_id, created_by, domain_status
			FROM
				domain
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			WHERE
				domain_id = ?
			AND
				created_by = ?
		',
		array($domainId, $resellerId)
	);

	if ($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		if ($row['domain_status'] == 'ok') {
			change_domain_status($row['admin_id'], 'deactivate');
		} elseif ($row['domain_status'] == 'disabled') {
			change_domain_status($row['admin_id'], 'activate');
		} else {
			showBadRequestErrorPage();
		}

		redirectTo('users.php');
	}
}

showBadRequestErrorPage();
