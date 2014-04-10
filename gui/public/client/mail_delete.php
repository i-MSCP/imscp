<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     Client_Mail
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Schedule deletion of the given mail account
 *
 * @throws iMSCP_Exception on error
 * @param int $mailId Mail account unique identifier
 * @param array $dmnProps Main domain properties
 * @return void
 */
function client_deleteMailAccount($mailId, $dmnProps)
{
	$stmt = exec_query(
		'SELECT `mail_addr` FROM `mail_users` WHERE `mail_id` = ? AND `domain_id` = ?',
		array($mailId, $dmnProps['domain_id'])
	);

	if ($stmt->rowCount()) {
		$mailAddr = $stmt->fields['mail_addr'];
		$toDeleteStatus = 'todelete';

		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteMail, array('mailId' => $mailId));

		exec_query('UPDATE `mail_users` SET `status` = ? WHERE `mail_id` = ?', array($toDeleteStatus, $mailId));

		// Schedule deleltion of all catchall which belong to the mail account
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

		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterDeleteMail, array('mailId' => $mailId));

		set_page_message(
			tr(
				'Mail account %s successfully scheduled for deletion.',
				'<strong>' . decode_idna($mailAddr) .'</strong>'
			),
			'success'
		);
	} else {
		throw new iMSCP_Exception('Bad request.', 400);
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasFeature('mail') && isset($_REQUEST['id'])) {
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

	$nbDeletedMails = 0;
	$mailIds = (array)$_REQUEST['id'];

	if (!empty($mailIds)) {
		/** @var $db iMSCP_Database */
		$db = iMSCP_Database::getInstance();

		try {
			$db->beginTransaction();

			foreach ($mailIds as $mailId) {
				$mailId = clean_input($mailId);
				client_deleteMailAccount($mailId, $mainDmnProps);
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
					'An unexpected error occurred while attempting to delete mail account with ID %s: %s',
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

	redirectTo('mail_accounts.php');
} else {
	showBadRequestErrorPage();
}
