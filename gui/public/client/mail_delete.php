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
	$stmt = exec_query(
		'SELECT `mail_addr` FROM `mail_users` WHERE `mail_id` = ? AND `domain_id` = ?',
		array($mailId, $dmnProps['domain_id'])
	);

	if ($stmt->rowCount()) {
		$mailAddr = $stmt->fields['mail_addr'];

		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		$toDeleteStatus = $cfg['ITEM_TODELETE_STATUS'];

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteMail, array('mailId' => $mailId));

		exec_query('UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?', array($toDeleteStatus, $mailId));

		if (isset($cfg['PO_SERVER']) && $cfg['PO_SERVER'] == 'dovecot') {
			exec_query('DELETE FROM `quota_dovecot` WHERE `username` = ?', $mailAddr);
		}

		// Schedule deletetion of all catchall which belong to the mail account
		exec_query(
			'
				UPDATE
					`mail_users`
				SET
					`status` = ?
				WHERE
					`mail_acc` = ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ? OR `mail_acc` LIKE ?
			',
			array($toDeleteStatus, $mailAddr, "$mailAddr,%", "%,$mailAddr,%", "%,$mailAddr")
		);

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

if (customerHasFeature('mail') && isset($_REQUEST['id'])) {
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

	$nbDeletedMails = 0;
	$mailIds = (array)$_REQUEST['id'];

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
				set_page_message(tr('Operation canceled: %s', $errorMessage), 'warning');
			} elseif ($e->getCode() == 400) {
				showBadRequestErrorPage();
			} else {
				set_page_message(tr('An unexpected error occured. Please contact your reseller.'), 'error');
			}
		}
	} else {
		set_page_message(tr('You must select a least one mail account to delete.'), 'error');
	}

	if (!is_xhr()) {
		redirectTo('mail_accounts.php');
	}
} else {
	showBadRequestErrorPage();
}
