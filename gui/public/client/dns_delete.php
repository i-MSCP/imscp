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
 * @subpackage  Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('custom_dns_records') or showBadRequestErrorPage();

if (isset($_GET['id'])) {
	$dnsRecordId = $_GET['id'];
	$mainDomainId = get_user_domain_id($_SESSION['user_id']);

	$query = "SELECT `alias_id` FROM `domain_dns` WHERE `domain_dns_id` = ? AND domain_id = ? AND `owned_by` = ?";
	$stmt = exec_query($query, array($dnsRecordId, $mainDomainId, 'custom_dns_feature'));

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$aliasId = $stmt->fields['alias_id'];

	/** @var $db iMSCP_Database */
	$db = iMSCP_Database::getInstance();

	try {
		$db->beginTransaction();

		// Delete DNS record from the database
		$query = "DELETE FROM `domain_dns` WHERE `domain_dns_id` = ?";
		exec_query($query, $dnsRecordId);

		if ($aliasId == 0) {
			$query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?";
			exec_query($query, array('tochange', $mainDomainId));
		} else {
			$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ? AND `alias_id` = ?";
			exec_query($query, array('tochange', $mainDomainId, $aliasId));
		}

		$db->commit();

		send_request();
		write_log($_SESSION['user_logged'] . ": deleted custom DNS record with ID $dnsRecordId", E_USER_NOTICE);
		set_page_message(tr('Custom DNS record scheduled for deletion.'), 'success');
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw new $e;
	}

	redirectTo('domains_manage.php');
}

showBadRequestErrorPage();
