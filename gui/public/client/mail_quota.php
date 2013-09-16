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

/***********************************************************************************************************************
 * script functions
 */

/**
 * Gets mail account data.
 *
 * @param int $mailAccountId Mail account unique identifier
 * @return array Mail account data, FALSE if account doens't have quota
 */
function client_getMailAccountData($mailAccountId)
{
	static $mailAccountData = NULL;

	if (null === $mailAccountData) {
		$dmnProps = get_domain_default_props($_SESSION['user_id']);

		$query = '
			SELECT
				`t1`.*, t2.`domain_id`
			FROM
				`mail_users` AS `t1`, `domain` AS `t2`
			WHERE
				`t1`.`mail_id` = ?
			AND
				`t2`.`domain_id` = t1.`domain_id`
			AND
				`t2`.`domain_id` = ?
		';
		$stmt = exec_query($query, array($mailAccountId, $dmnProps['domain_id']));

		if ($stmt->rowCount()) {
			$mailAccountData = $stmt->fetchRow();
		} else {
			showBadRequestErrorPage();
		}

		if (strpos($mailAccountData['mail_type'], '_mail') === false) {
			showBadRequestErrorPage();
		} else {
			return $mailAccountData;
		}
	}

	return false;
}

/**
 * Update mail account quota.
 *
 * @param array $mailAccountData Mail account data
 * @return bool TRUE on success, FALSE otherwise
 */
function client_UpdateMailAccount($mailAccountData)
{
	// Quota data validation
	$quotaValue = 0;
	$quotaUpdate = 0;

	if (!empty($_POST['quota']) || $_POST['quota'] == 0) {
		if(is_numeric($_POST['quota'])) {
			if ($_POST['quota'] != floor($mailAccountData['quota'] / 1024 / 1024)) {
				$quotaUpdate = 1;
				$quotaValue = $_POST['quota'] * 1024 * 1024;
			}
		} else {
			set_page_message(tr('Quota must be a number'), 'error');
			return false;
		}
	} else {
		set_page_message(tr('Missing value for quota field'), 'error');
		return false;
	}

	if ($quotaUpdate) {
		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onBeforeEditMail, array('mailId' => $mailAccountData['mail_id'])
		);

		$query = "UPDATE `mail_users` SET `quota` = ? WHERE `mail_id` = ?";
		exec_query($query, array($quotaValue, $mailAccountData['mail_id']));

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterEditMail, array('mailId' => $mailAccountData['mail_id'])
		);

		set_page_message(tr('Quota successfully updated.'), 'success');
		write_log("{$_SESSION['user_logged']} updated quota for: {$mailAccountData['mail_addr']}", E_USER_NOTICE);
		return true;
	}

	set_page_message(tr("Nothing has been changed."), 'info');
	return true;
}

/**
 * Generates quota edit form.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param array $mailAccountData Mail account data
 * @return void
 */
function client_generateQuotaForm($tpl, $mailAccountData)
{
	$tpl->assign(
		array(
			'MAIL_ID_VAL' => $mailAccountData['mail_id'],
			'MAIL_ADDRESS_VAL' => tohtml($mailAccountData['mail_addr']),
			'TR_MAIL_ACCOUNT' => tr('Email account')
		)
	);
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('mail') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['id'])) {
	$mailAccountData = client_getMailAccountData(clean_input($_GET['id']));

	if (!empty($_POST) && client_updateMailAccount($mailAccountData)) {
		redirectTo('mail_accounts.php');
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/mail_quota.tpl',
			'page_message' => 'layout'
		)
	);

	client_generateQuotaForm($tpl, $mailAccountData);
	if($mailAccountData['quota'] != 0) {
		// Quota are stored in bytes in database. Convert to MiB
		$quotaValue = floor($mailAccountData['quota'] / 1024 / 1024);
	} else {
		$quotaValue = $mailAccountData['quota'];
	}

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Email / Overview / Edit Email Quota'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_QUOTA' => tr('Quota in MiB (0 for unlimited)'),
			'QUOTA' => $quotaValue,
			'TR_UPDATE' => tr('Update'),
			'TR_CANCEL' => tr('Cancel')
		)
	);

	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
