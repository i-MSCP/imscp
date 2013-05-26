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
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
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
		$domainProperties = get_domain_default_props($_SESSION['user_id']);

		$query = '
			SELECT
				`t1`.*, t2.`domain_id`
			FROM
				`mail_users` `t1`, `domain` `t2`
			WHERE
				`t1`.`mail_id` = ?
			AND
				`t2`.`domain_id` = t1.`domain_id`
			AND
				`t2`.`domain_name` = ?
		';
		$stmt = exec_query($query, array($mailAccountId, $domainProperties['domain_name']));

		if ($stmt->rowCount()) {
			$mailAccountData = $stmt->fetchRow();
		} else {
			set_page_message(tr('Mail account not found.'), 'error');
			redirectTo('mail_accounts.php');
		}

		if (strpos($mailAccountData['mail_type'], '_mail') === false) {
			set_page_message(tr('This type of account does not have quota.'), 'error');
                        redirectTo('mail_accounts.php');
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
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg =iMSCP_Registry::get('config');

	// Quota data validation
	$quotaValue = 0;
	$quotaUpdate = 0;
	if (!empty($_POST['quota']) || $_POST['quota'] == 0) {
		if($_POST['quota'] != floor($mailAccountData['quota'] / 1024 /1024)) {
			if(is_numeric($_POST['quota'])) {
				$quotaUpdate = 1;
				$quotaValue = $_POST['quota'] * 1024 * 1024;
			} else {
				set_page_message(tr('Quota must be number'), 'error');
			}
		}
	} else {
		set_page_message(tr('Quota must have a value'), 'error');
	}

	if ((!Zend_Session::namespaceIsset('pageMessages')) && ($quotaUpdate!=0)) {
		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onBeforeEditMail, array('mailId' => $mailAccountData['mail_id'])
		);

		$query = "
			UPDATE
				`mail_users`
			SET
				`quota` = ?
			WHERE
				`mail_id` = ?
		";
		exec_query($query, array(
				$quotaValue,
				$mailAccountData['mail_id']));

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterEditMail, array('mailId' => $mailAccountData['mail_id'])
		);

		set_page_message(tr('Mail account quota updated.'), 'success');
		write_log("{$_SESSION['user_logged']}: updated mail quota: {$mailAccountData['mail_addr']}", E_USER_NOTICE);
		return true;
	} else {
		set_page_message(tr("Nothing has been changed."), 'info');
		return true;
	}

	return false;
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
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg =iMSCP_Registry::get('config');

	$htmlChecked = $cfg->HTML_CHECKED;

	$tpl->assign(
		array(
			 'MAIL_ID_VAL' => $mailAccountData['mail_id'],
			 'MAIL_ADDRESS_VAL' => tohtml($mailAccountData['mail_addr']),
			 'TR_MAIL_ACCOUNT' => tr('Mail account'),
			));
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('mail') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if(isset($_GET['id'])) {
	$mailAccountData = client_getMailAccountData((int) $_GET['id']);
} else {
	showBadRequestErrorPage();
}

if(!empty($_POST) && client_updateMailAccount($mailAccountData)) {
	redirectTo('mail_accounts.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		 'page' => 'client/mail_quota.tpl',
		 'page_message' => 'layout',
		 'logged_frm' => 'page',
		 'quota_frm' => 'page'));

client_generateQuotaForm($tpl, $mailAccountData);
//We have the data in db in MB, hence the conversion
$quotaValue=floor($mailAccountData['quota'] / 1024 / 1024);

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Client / Mail / Overview /  Edit Mail Quota'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_QUOTA' => tr('Quota in MB (0 unlimited)'),
		 'QUOTA' => $quotaValue,
		 'TR_HELP' => tr('help'),
		 'TR_UPDATE' => tr('Update'),
		 'TR_CANCEL' => tr('Cancel')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
