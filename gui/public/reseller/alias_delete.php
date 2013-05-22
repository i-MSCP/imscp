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
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (resellerHasFeature('domain_aliases') && isset($_GET['del_id'])) {
	$alsId = clean_input($_GET['del_id']);

	$query = "
		SELECT
			`t1`.`domain_id`, `t1`.`alias_id`, `t1`.`alias_name`, `t2`.`domain_id`, `t2`.`domain_created_id`
		FROM
			`domain_aliasses` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`alias_id` = ?
		AND
			`t1`.`domain_id` = `t2`.`domain_id`
		AND
			`t2`.`domain_created_id` = ?
	";
	$stmt = exec_query($query, array($alsId, $_SESSION['user_id']));

	if ($stmt->rowCount()) {
		$alsName = $stmt->fields['alias_name'];

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

			// Delete any FTP account linked to $alsId or one of its subdomains
			$query = "
				DELETE
					`ftp_users`
				FROM
					`ftp_users`
				LEFT JOIN
					`domain_aliasses` AS `t2` ON(`alias_id` = ?)
				LEFT JOIN
					`subdomain_alias` AS `t3` ON(`t3`.`alias_id` = `t2`.`alias_id`)
				WHERE
				(
						`userid` LIKE CONCAT('%@', `t3`.`subdomain_alias_name`, '.', `t2`.`alias_name`)
					OR
						`userid` LIKE CONCAT('%@', `t2`.`alias_name`)
				)
			";
			$stmt = exec_query($query, $alsId);

			// Delete any custom DNS and external mail server record which have $alsId as parent
			$query = "DELETE FROM `domain_dns` WHERE `alias_id` = ?";
			exec_query($query, $alsId);

			// Schedule deletion of any mail account, which are directly or indirectly linked to $alsId
			$query = "
				UPDATE
					`mail_users`
				SET
					`status` = ?
				WHERE
					(`sub_id` = ? AND `mail_type` LIKE ?)
				OR
				(
					`sub_id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ?)
				AND
					`mail_type` LIKE ?
				)
			";
			exec_query($query, array($cfg->ITEM_DELETE_STATUS, $alsId, '%alias_%', $alsId, '%alssub_%'));

			# Schedule deletion of any SSL certificat linked to subdomain, which have $alsId as parent
			$query = "
				UPDATE
					`ssl_certs`
				SET
					`status` = ?
				WHERE
					`type` = ?
				AND
					`id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ?)
			";
			exec_query($query, array($cfg->ITEM_DELETE_STATUS, 'alssub', $alsId));

			# Schedule deletion of any SSL certificate linked to this domain alias
			$query = "UPDATE `ssl_certs` SET `status` = ? WHERE `type` = ? AND `id` = ?";
			exec_query($query, array($cfg->ITEM_DELETE_STATUS, 'als', $alsId));

			# Schedule deletion of any subdomain, which have $alsId alias as parent
			$query = "UPDATE `subdomain_alias` SET `subdomain_alias_status` = ? WHERE `alias_id` = ?";
			exec_query($query, array($cfg->ITEM_DELETE_STATUS, $alsId));

			# Schedule domain alias deletion
			$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?";
			exec_query($query, array($cfg->ITEM_DELETE_STATUS, $alsId));

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

		set_page_message(tr('Domain alias successfully scheduled for deletion.'), 'success');
		write_log("{$_SESSION['user_logged']}: scheduled deletion of the $alsName domain alias.", E_USER_NOTICE);

		redirectTo('alias.php');
	}
}

showBadRequestErrorPage();
