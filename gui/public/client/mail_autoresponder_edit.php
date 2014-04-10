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
 * @category    iMSCP
 * @package     Client_Mail
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Checks that the given mail account is owned by current customer and its responder is active
 *
 * @param int $mailAccountId Mail account id to check
 * @return bool TRUE if the mail account is owned by the current customer, FALSE otherwise
 */
function client_checkMailAccountOwner($mailAccountId)
{
	$domainProps = get_domain_default_props($_SESSION['user_id']);

	$query = '
		SELECT
			`t1`.*, `t2`.`domain_id`, `t2`.`domain_name`
		FROM
			`mail_users` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`mail_id` = ?
		AND
			`t2`.`domain_id` = `t1`.`domain_id`
		AND
			`t2`.`domain_id` = ?
		AND
			`t1`.`mail_auto_respond` = ?
		AND
			`t1`.`status` = ?
    ';
	$stmt = exec_query($query, array($mailAccountId, $domainProps['domain_id'], 1, 'ok'));

	return (bool)$stmt->rowCount();
}

/**
 * Update autoresponder of the given mail account
 *
 * @param int $mailAccountId Mail account id
 * @param string $autoresponderMessage Auto-responder message
 * @return void
 */
function client_updateAutoresponder($mailAccountId, $autoresponderMessage)
{
	$autoresponderMessage = clean_input($autoresponderMessage);

	if ($autoresponderMessage == '') {
		set_page_message(tr('Auto-responder message cannot be empty.'), 'error');
		redirectTo("mail_autoresponder_enable.php?mail_account_id=$mailAccountId");
	} else {
		$db = iMSCP_Database::getInstance();

		try {
			$db->beginTransaction();

			$query = "SELECT `mail_addr` FROM `mail_users` WHERE `mail_id` = ?";
			$stmt = exec_query($query, $mailAccountId);

			$query = "UPDATE `mail_users` SET `status` = ?, `mail_auto_respond_text` = ? WHERE `mail_id` = ?";
			exec_query($query, array('tochange', $autoresponderMessage, $mailAccountId));

			// Purge autoreplies log entries
			delete_autoreplies_log_entries();

			$db->commit();

			// Ask iMSCP daemon to trigger engine dispatcher
			send_request();

			write_log(
				sprintf(
					"%s: Updated auto-responder for the '%s' mail account",
					$_SESSION['user_logged'],
					$stmt->fields['mail_addr']
				),
				E_USER_NOTICE
			);
			set_page_message(tr('Auto-responder successfully scheduled for update.'), 'success');
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
			throw $e;
		}
	}
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $mailAccountId Mail account id
 * @return void
 */
function client_generatePage($tpl, $mailAccountId)
{
	$query = "SELECT `mail_auto_respond_text`, `mail_acc` FROM `mail_users` WHERE `mail_id` = ?";
	$stmt = exec_query($query, $mailAccountId);
	$tpl->assign('AUTORESPONDER_MESSAGE', tohtml($stmt->fields['mail_auto_respond_text']));
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasFeature('mail') && (isset($_REQUEST['mail_account_id']) && is_numeric($_REQUEST['mail_account_id']))) {
	$mailAccountId = intval($_REQUEST['mail_account_id']);

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (client_checkMailAccountOwner($mailAccountId)) {
		if (!isset($_POST['mail_account_id'])) {
			$tpl = new iMSCP_pTemplate();
			$tpl->define_dynamic(
				array(
					'layout' => 'shared/layouts/ui.tpl',
					'page' => 'client/mail_autoresponder.tpl',
					'page_message' => 'layout'
				)
			);

			$tpl->assign(
				array(
					'TR_PAGE_TITLE' => tr('Client / Email / Overview / Edit Auto Responder'),
					'ISP_LOGO' => layout_getUserLogo(),
					'TR_AUTORESPONDER_MESSAGE' => tr('Please enter your auto-responder message below'),
					'TR_ACTION' => tr('Update'),
					'TR_CANCEL' => tr('Cancel'),
					'MAIL_ACCOUNT_ID' => $mailAccountId
				)
			);

			generateNavigation($tpl);
			client_generatePage($tpl, $mailAccountId, !isset($_POST['uaction']));
			generatePageMessage($tpl);

			$tpl->parse('LAYOUT_CONTENT', 'page');

			iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

			$tpl->prnt();

			unsetMessages();
		} elseif (isset($_POST['autoresponder_message'])) {
			client_updateAutoresponder($mailAccountId, $_POST['autoresponder_message']);
			redirectTo('mail_accounts.php');
		} else {
			showBadRequestErrorPage();
		}
	} else {
		showBadRequestErrorPage();
	}

} else {
	showBadRequestErrorPage();
}
