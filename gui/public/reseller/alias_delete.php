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
 * @package        iMSCP_Core
 * @subpackage    Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// TODO: Check for protected areas - seem that for now, all protected areas are always linked to main domain, but
// protected area paths can be linked to domain aliases or one of its child (subdomain)

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

resellerHasFeature('domain_aliases') or showBadRequestErrorPage();

$cfg = iMSCP_Registry::get('config');

if (isset($_GET['del_id']))
	$domainAliasId = $_GET['del_id'];
else {
	showBadRequestErrorPage();
}

$resellerId = $_SESSION['user_id'];

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
$stmt = exec_query($query, array($domainAliasId, $resellerId));

if ($stmt->rowCount()) {
	showBadRequestErrorPage();
}

$domainAliasName = $stmt->fields['alias_name'];

/** @var $db iMSCP_Database */
$db = iMSCP_Registry::get('db');

try {
	$db->beginTransaction();

	// Delete any FTP account linked to $domainAliasId
	$query = "DELETE `userid` FROM `ftp_users` WHERE `userid` LIKE ?";
	$stmt = exec_query($query, "%@$domainAliasName");

	// Delete any custom DNS and external mail server record which have $domainAliasId as parent
	$query = "DELETE FROM `domain_dns` WHERE `alias_id` = ?";
	exec_query($query, $domainAliasId);

	// Schedule deletion of any mail account, which are directly or indirectly linked to $domainAliasId
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
	exec_query($query, array($cfg->ITEM_DELETE_STATUS, $domainAliasId, '%alias_%', $domainAliasId, '%alssub_%'));

	# Schedule deletion of any SSL certificat linked to subdomain, which have $domainAliasId as parent
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
	exec_query($query, array($cfg->ITEM_DELETE_STATUS, 'alssub', $domainAliasId));

	# Schedule deletion of any SSL certificate linked to this domain alias
	$query = "UPDATE `ssl_certs` SET `status` = ? WHERE `type` = ? AND `id` = ?";
	exec_query($query, array($cfg->ITEM_DELETE_STATUS, 'als', $domainAliasId));

	# Schedule deletion of any subdomain, which have $domainAliasId alias as parent
	$query = "UPDATE `subdomain_alias` SET `subdomain_alias_status` = ? WHERE `alias_id` = ?";
	exec_query($query, array($cfg->ITEM_DELETE_STATUS, $domainAliasId));

	# Schedule domain alias deletion
	$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?";
	exec_query($query, array($cfg->ITEM_DELETE_STATUS, $domainAliasId));

	$db->commit();

	send_request();
	set_page_message(tr('Domain alias successfully scheduled for deletion.'), 'success');
	write_log(sprintf("{$_SESSION['user_logged']}: scheduled deletion of the %s domain alias: ", $domainAliasName), E_USER_NOTICE);
} catch (iMSCP_Exception_Database $e) {
	$db->rollBack();
	set_page_message(tr('Unable to delete domain alias with id %d', $domainAliasId), 'error');
	write_log(
		sprintf("Unable to delete domain alias with Id %d for the following reason: %s", $domainAliasId, $e->getMessage()),
		E_USER_ERROR
	);
}

redirectTo('alias.php');
