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
 * @subpackage  Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/**
 * Schedule deletion of the given mail account
 *
 * @throws iMSCP_Exception on error
 * @param int $mailId Mail unique identifier
 * @param array $dmnProps Main domain properties
 * @return void
 */
function client_deleteMail($mailId, $dmnProps)
{
	$query = "
		SELECT
			`t1`.`mail_id`, `t2`.`domain_id`, `t2`.`domain_name`
		FROM
			`mail_users` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`mail_id` = ?
		AND
			`t1`.`domain_id` = t2.`domain_id`
		AND
			`t2`.`domain_id` = ?
	";
	$stmt = exec_query($query, array($mailId, $dmnProps['domain_id']));

	if ($stmt->rowCount()) {
		// Check for catchall
		$query = "SELECT `mail_acc`, `domain_id`, `sub_id`, `mail_type` FROM `mail_users` WHERE `mail_id` = ?";
		$stmt = exec_query($query, $mailId);
		$data = $stmt->fetchRow();

		if (
			strpos($data['mail_type'], MT_NORMAL_MAIL) !== false ||
			strpos($data['mail_type'], MT_NORMAL_FORWARD) !== false
		) { // Mail to dmn
			$mailAddr = $data['mail_acc'] . '@' . $dmnProps['domain_name'];
		} elseif (
			strpos($data['mail_type'], MT_ALIAS_MAIL) !== false ||
			strpos($data['mail_type'], MT_ALIAS_FORWARD) !== false
		) { // mail to als
			$stmt = exec_query("SELECT `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?", $data['sub_id']);

			if ($stmt->rowCount()) {
				$mailAddr = $data['mail_acc'] . '@' . $stmt->fields['alias_name'];
			} else {
				throw new iMSCP_Exception('Mail account data not found', 500);
			}
		} elseif (
			strpos($data['mail_type'], MT_SUBDOM_MAIL) !== false ||
			strpos($data['mail_type'], MT_SUBDOM_FORWARD) !== false
		) { // mail to sub
			$stmt = exec_query("SELECT `subdomain_name` FROM `subdomain` WHERE `subdomain_id` = ?", $data['sub_id']);

			if ($stmt->rowCount()) {
				$mailAddr = $data['mail_acc'] . '@' . $stmt->fields['subdomain_name'] . '.' . $dmnProps['domain_name'];
			} else {
				throw new iMSCP_Exception('Mail account data not found', 500);
			}
		} elseif (
			strpos($data['mail_type'], MT_ALSSUB_MAIL) !== false ||
			strpos($data['mail_type'], MT_ALSSUB_FORWARD) !== false
		) { // mail to als
			$query = '
				SELECT
					`subdomain_alias_name`, `alias_name`
				FROM
					`subdomain_alias` AS `t1`, `domain_aliasses` AS `t2`
				WHERE
					`t1`.`alias_id` = t2.`alias_id`
				AND
					`subdomain_alias_id` = ?
			';
			$stmt = exec_query($query, $data['sub_id']);

			if ($stmt->rowCount()) {
				$mailAddr = $data['mail_acc'] . '@' . $stmt->fields['subdomain_alias_name'] . '.' . $stmt->fields['alias_name'];
			} else {
				throw new iMSCP_Exception('Mail account data not found', 500);
			}
		} else {
			throw new iMSCP_Exception(sprintf('Type of mail with ID %d has not been found.', $mailId), 500);
		}

		$query = '
			SELECT
				`mail_id`
			FROM
				`mail_users`
			WHERE
				`mail_acc` = ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ?
		';
		$stmt = exec_query($query, array($mailAddr, "$mailAddr,%", "%,$mailAddr,%", "%,$mailAddr"));

		if ($stmt->rowCount()) {
			throw new iMSCP_Exception(tr('Please first, delete all catchall linked to this email.', 403));
		}

		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteMail, array('mailId' => $mailId));

		if($cfg->PO_SERVER == 'dovecot') {
			$query = 'DELETE FROM `quota_dovecot` WHERE `username` = ?';
			exec_query($query, $mailAddr);
		}

		$query = "UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?";
		exec_query($query, array($cfg->ITEM_TODELETE_STATUS, $mailId));

		delete_autoreplies_log_entries($mailAddr);

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterDeleteMail, array('mailId' => $mailId));

		set_page_message(tr('Mail account %s successfully scheduled for deletion.', $mailAddr), 'success');
	} else {
		throw new iMSCP_Exception('Bad request.', 400);
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if(customerHasFeature('mail') && isset($_REQUEST['id'])) {
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

	$nbDeletedMails = 0;
	$mailIds = (array) $_REQUEST['id'];

	if (!empty($mailIds)) {
		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			foreach ($mailIds as $mailId) {
				$mailId = clean_input($mailId);
				client_deleteMail($mailId, $mainDmnProps);
				$nbDeletedMails++;
			}

			$db->commit();
			send_request();
			write_log(sprintf("{$_SESSION['user_logged']} deleted %d mail account(s)", $nbDeletedMails), E_USER_NOTICE);
		} catch (iMSCP_Exception $e) {
			$db->rollBack();

			if (Zend_Session::namespaceIsset('pageMessages')) {
				Zend_Session::namespaceUnset('pageMessages');
			}

			$errorMessage = $e->getMessage();
			$code = $e->getCode();

			write_log(
				sprintf(
					'An unexpected error occurred while attempting to delete mail account with ID $%d: %s',
					$mailId,
					$errorMessage
				),
				E_USER_ERROR
			);

			if ($code == 403) {
				set_page_message(tr('Operatio canceled: %s', $errorMessage), 'warning');
			} elseif ($e->getCode() == 400) {
				showBadRequestErrorPage();
			} else {
				set_page_message(tr('An unexpected error occured. Please contact your administrator'), 'error');
			}
		}
	} else {
		set_page_message(tr('You must select a least one mail account to delete.'), 'error');
	}

	if(!is_xhr()) {
		redirectTo('mail_accounts.php');
	}
} else {
	showBadRequestErrorPage();
}
