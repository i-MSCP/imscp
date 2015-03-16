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
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('custom_dns_records') or showBadRequestErrorPage();

if (isset($_GET['id'])) {
	$dnsRecordId = $_GET['id'];
	$mainDomainId = get_user_domain_id($_SESSION['user_id']);

	$stmt = exec_query(
		'UPDATE domain_dns SET domain_dns_status = ? WHERE domain_dns_id = ? AND domain_id = ?',
		array('todelete', $dnsRecordId, $mainDomainId)
	);

	if($stmt->rowCount()) {
		send_request();

		write_log(
			$_SESSION['user_logged'] . ": scheduled deletion of custom DNS record with ID $dnsRecordId", E_USER_NOTICE
		);

		set_page_message(tr('Custom DNS record successfully scheduled for deletion.'), 'success');

		redirectTo('domains_manage.php');
	}
}

showBadRequestErrorPage();
