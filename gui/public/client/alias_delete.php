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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    iMSCP
 * @package     Client_Domains_Aliases
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasFeature('domain_aliases') && isset($_GET['id'])) {
	$alsId = clean_input($_GET['id']);
	$mainDmnId = get_user_domain_id($_SESSION['user_id']);

	$query = "SELECT `alias_name` FROM `domain_aliasses` WHERE `domain_id` = ? AND `alias_id` = ?";
	$stmt = exec_query($query, array($mainDmnId, $alsId));

	if ($stmt->rowCount()) {
		$alsName = $stmt->fields['alias_name'];
		$ret = false;

		// check for subdomains
		$query = "SELECT COUNT(`subdomain_alias_id`) AS `cnt` FROM `subdomain_alias` WHERE `alias_id` = ?";
		$stmt = exec_query($query, $alsId);

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has subdomains. Remove them first.'), 'error'
			);
			$ret = true;
		}

		// Check for custom dns and external mail server records
		$query = "SELECT COUNT(`domain_dns_id`) AS `cnt` FROM `domain_dns` WHERE `alias_id` = ?";
		$stmt = exec_query($query, $alsId);

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has custom DNS records. Remove them first.'), 'error'
			);
			$ret = true;
		}

		// Check for Ftp accounts
		$query = "SELECT count(`userid`) AS `cnt` FROM `ftp_users` WHERE `userid` LIKE ?";
		$stmt = exec_query($query, "%@$alsName");

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has Ftp accounts. Remove them first.'), 'error'
			);
			$ret = true;
		}

		// Check for mail accounts
		$query = "
			SELECT
				COUNT(`mail_id`) AS `cnt`
			FROM
				`mail_users`
			WHERE
				(`sub_id` = ? AND `mail_type` LIKE ?)
			OR
				(
					`sub_id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ?)
					AND `mail_type` LIKE ?
				)
		";
		$stmt = exec_query($query, array($alsId, '%alias_%', $alsId, '%alssub_%'));

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has email accounts. Remove them first.'), 'error'
			);
			$ret = true;
		}

		if (!$ret) {
			iMSCP_Events_Manager::getInstance()->dispatch(
				iMSCP_Events::onBeforeDeleteDomainAlias,
				array('domainAliasId' => $alsId, 'domainAliasName' => $alsName)
			);

			/** @var $cfg iMSCP_Config_Handler_File */
			$cfg = iMSCP_Registry::get('config');

			/** @var $db iMSCP_Database */
			$db = iMSCP_Registry::get('db');

			try {
				$db->beginTransaction();

				# Schedule deletion of any SSL certificat, which have domain alias as parent
				$query = "UPDATE `ssl_certs` SET `status` = ? WHERE `id` = ? AND `type` = ?";
				exec_query($query, array($cfg->ITEM_TODELETE_STATUS, $alsId, 'als'));

				# Schedule deletion of domain alias
				$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?";
				exec_query($query, array($cfg->ITEM_TODELETE_STATUS, $alsId));

				$db->commit();
			} catch (iMSCP_Exception_Database $e) {
				$db->rollBack();
				throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
			}

			iMSCP_Events_Manager::getInstance()->dispatch(
				iMSCP_Events::onAfterDeleteDomainAlias,
				array('domainAliasId' => $alsId, 'domainAliasName' => $alsName)
			);

			send_request();

			write_log("{$_SESSION['user_logged']} scheduled deletion of domain alias $alsName", E_USER_NOTICE);
			set_page_message(tr('Domain alias successfully scheduled for deletion.'), 'success');
		}

		redirectTo('domains_manage.php');
	}
}

showBadRequestErrorPage();
