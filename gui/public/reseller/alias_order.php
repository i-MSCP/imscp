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
 * @subpackage  Reseller
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

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

resellerHasFeature('domain_aliases') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['action']) && $_GET['action'] == "delete") {

	if (isset($_GET['del_id'])) {
		$alsId = clean_input($_GET['del_id']);

		$query = "DELETE FROM `domain_aliasses` WHERE `alias_id` = ? AND `alias_status` = ?";
		$stmt = exec_query($query, array($alsId, 'ordered'));

		if($stmt->rowCount()) {
			set_page_message('Order successfully deleted.', 'success');
			redirectTo('alias.php');
		}
	}
} elseif (isset($_GET['action']) && $_GET['action'] == "activate") {
	if (isset($_GET['act_id'])) {
		$alsId = clean_input($_GET['act_id']);

		$query = "SELECT `alias_name`, `domain_id` FROM `domain_aliasses` WHERE `alias_id` = ? AND `alias_status` = ?";
		$stmt = exec_query($query, array($alsId, 'ordered'));

		if ($stmt->rowCount()) {
			$alsName = $stmt->fields['alias_name'];
			$mainDmnId = $stmt->fields['domain_id'];

			/** @var $db iMSCP_Database */
			$db = iMSCP_Database::getInstance();

			try {
				iMSCP_Events_Aggregator::getInstance()->dispatch(
					iMSCP_Events::onBeforeAddDomainAlias,
					array(
						'domainId' => $mainDmnId,
						'domainAliasName' => $alsName
					)
				);

				$db->beginTransaction();

				$stmt = exec_query(
					'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ? AND alias_status = ?',
					array('toadd', $alsId, 'ordered')
				);

				if($stmt->rowCount()) {
					// Create default email addresses if needed
					if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
						$query = '
							SELECT
								email
							FROM
								admin
							LEFT JOIN
								domain ON(admin.admin_id = domain.domain_admin_id)
							WHERE
								domain.domain_id = ?
						';
						$stmt = exec_query($query, $mainDmnId);

						if ($stmt->rowCount()) {
							client_mail_add_default_accounts(
								$mainDmnId, $stmt->fields['email'], $alsName, 'alias', $alsId
							);
						}
					}
				}

				$db->commit();

				iMSCP_Events_Aggregator::getInstance()->dispatch(
					iMSCP_Events::onAfterAddDomainAlias,
					array(
						'domainId' => $mainDmnId,
						'domainAliasName' => $alsName,
						'domainAliasId' => $alsId
					)
				);
			
				send_request();
				set_page_message(tr('Order successfully processed.'), 'success');
				redirectTo('alias.php');
			} catch(iMSCP_Exception_Database $e) {
				$db->rollBack();
				throw $e;
			}
		}
	}
}

showBadRequestErrorPage();
