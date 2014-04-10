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
 * Generate action
 *
 * @param int $mailId
 * @param string $mailStatus Mail account status
 * @return array|null
 */
function client_generateAction($mailId, $mailStatus)
{

	if ($mailStatus == 'toadd') {
		return array(tr('N/A'), '#');
	} else if ($mailStatus == 'ok') {
		return array(tr('Delete CatchAll'), "mail_catchall_delete.php?id=$mailId");
	} else if ($mailStatus == 'tochange') {
		return array(tr('N/A'), '#');
	} else if ($mailStatus == 'todelete') {
		return array(tr('N/A'), '#');
	} else {
		return null;
	}
}

/**
 * Generate catchall item
 *
 * @param iMSCP_pTemplate $tpl
 * @param string $action Action
 * @param int $dmnId Domain unique identifier
 * @param string $dmnName Domain name
 * @param int $mailId Mail unique identifier
 * @param string $mailAcc Mail account
 * @param string $mailStatus Mail account status
 * @param string $catchallType Catchall type
 * @return void
 */
function client_generateCatchallItem($tpl, $action, $dmnId, $dmnName, $mailId, $mailAcc, $mailStatus, $catchallType)
{
	$showDmnName = decode_idna($dmnName);

	if ($action == 'create') {
		$tpl->assign(
			array(
				'CATCHALL_DOMAIN' => tohtml($showDmnName),
				'CATCHALL_ACC' => tr('None'),
				'TR_CATCHALL_STATUS' => tr('N/A'),
				'TR_CATCHALL_ACTION' => tr('Create catch all'),
				'CATCHALL_ACTION' => $action,
				'CATCHALL_ACTION_SCRIPT' => "mail_catchall_add.php?id=$dmnId;$catchallType",
				'DEL_ICON' => ''
			)
		);
	} else {
		list($catchallAction, $catchallActionScript) = client_generateAction($mailId, $mailStatus);

		$showDmnName = decode_idna($dmnName);
		$showMailAcc = decode_idna($mailAcc);

		$tpl->assign(
			array(
				'CATCHALL_DOMAIN' => tohtml($showDmnName),
				'CATCHALL_ACC' => tohtml($showMailAcc),
				'TR_CATCHALL_STATUS' => translate_dmn_status($mailStatus),
				'TR_CATCHALL_ACTION' => $catchallAction,
				'CATCHALL_ACTION' => $catchallAction,
				'CATCHALL_ACTION_SCRIPT' => $catchallActionScript
			)
		);

		if($catchallActionScript == '#') {
			$tpl->assign('DEL_ICON', '');
		}
	}
}

/**
 * Generate catchall list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dmnId Domain unique identifier
 * @param string $dmnName Domain Name
 */
function client_generateCatchallList($tpl, $dmnId, $dmnName)
{
	$statusOk = 'ok';

	$query = "
		SELECT
			`mail_id`, `mail_acc`, `status`
		FROM
			`mail_users`
		WHERE
			`domain_id` = ?
		AND
			`sub_id` = ?
		AND
			`mail_type` = ?
	";
	$stmt = exec_query($query, array($dmnId, 0, 'normal_catchall'));

	if (!$stmt->rowCount()) {
		client_generateCatchallItem($tpl, 'create', $dmnId, $dmnName, '', '', '', 'normal');
	} else {
		client_generateCatchallItem(
			$tpl, 'delete', $dmnId, $dmnName, $stmt->fields['mail_id'], $stmt->fields['mail_acc'],
			$stmt->fields['status'], 'normal'
		);
	}

	$tpl->parse('CATCHALL_ITEM', 'catchall_item');

	$query = "SELECT `alias_id`, `alias_name` FROM `domain_aliasses` WHERE `domain_id` = ? AND `alias_status` = ?";
	$stmt = exec_query($query, array($dmnId, $statusOk));

	while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		$alsId = $data['alias_id'];
		$alsName = $data['alias_name'];

		$query = "
			SELECT
				`mail_id`, `mail_acc`, `status`
			FROM
				`mail_users`
			WHERE
				`domain_id` = ?
			AND
				`sub_id` = ?
			AND
				`mail_type` = ?
		";
		$stmtAls = exec_query($query, array($dmnId, $alsId, 'alias_catchall'));

		if (!$stmtAls->rowCount()) {
			client_generateCatchallItem($tpl, 'create', $alsId, $alsName, '', '', '', 'alias');
		} else {
			client_generateCatchallItem(
				$tpl, 'delete', $alsId, $alsName, $stmtAls->fields['mail_id'], $stmtAls->fields['mail_acc'],
				$stmtAls->fields['status'], 'alias'
			);
		}

		$tpl->parse('CATCHALL_ITEM', '.catchall_item');
	}

	$query = "
		SELECT
			`t1`.`subdomain_alias_id`, CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`) AS `subdomain_name`
		FROM
			`subdomain_alias` AS `t1`, `domain_aliasses` AS `t2`
		WHERE
			`t2`.`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
		AND
			`t1`.`alias_id` = `t2`.`alias_id`
		AND
			`t1`.`subdomain_alias_status` = ?
	";
	$stmt = exec_query($query, array($dmnId, $statusOk));

	while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		$alsId = $data['subdomain_alias_id'];
		$alsName = $data['subdomain_name'];

		$query = "
			SELECT
				`mail_id`, `mail_acc`, `status`
			FROM
				`mail_users`
			WHERE
				`domain_id` = ?
			AND
				`sub_id` = ?
			AND
				`mail_type` = ?
		";
		$stmtAls = exec_query($query, array($dmnId, $alsId, 'alssub_catchall'));

		if (!$stmtAls->rowCount()) {
			client_generateCatchallItem($tpl, 'create', $alsId, $alsName, '', '', '', 'alssub');
		} else {
			client_generateCatchallItem(
				$tpl, 'delete', $alsId, $alsName, $stmtAls->fields['mail_id'], $stmtAls->fields['mail_acc'],
				$stmtAls->fields['status'], 'alssub'
			);
		}

		$tpl->parse('CATCHALL_ITEM', '.catchall_item');
	}

	$query = "
		SELECT
			`t1`.`subdomain_id`, CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `subdomain_name`
		FROM
			`subdomain` AS `t1`, `domain` AS `t2`
		WHERE
			`t1`.`domain_id` = ?
		AND
			`t1`.`domain_id` = `t2`.`domain_id`
		AND
			`t1`.`subdomain_status` = ?
	";
	$stmt = exec_query($query, array($dmnId, $statusOk));

	while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		$alsId = $data['subdomain_id'];
		$alsName = $data['subdomain_name'];

		$query = "
			SELECT
				`mail_id`, `mail_acc`, `status`
			FROM
				`mail_users`
			WHERE
				`domain_id` = ?
			AND
				`sub_id` = ?
			AND
				`mail_type` = ?
		";
		$stmtAls = exec_query($query, array($dmnId, $alsId, 'subdom_catchall'));

		if (!$stmtAls->rowCount()) {
			client_generateCatchallItem($tpl, 'create', $alsId, $alsName, '', '', '', 'subdom');
		} else {
			client_generateCatchallItem($tpl,
				'delete', $alsId, $alsName, $stmtAls->fields['mail_id'], $stmtAls->fields['mail_acc'],
				$stmtAls->fields['status'], 'subdom'
			);
		}

		$tpl->parse('CATCHALL_ITEM', '.catchall_item');
	}
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 */
function client_generatePage($tpl)
{
	$domainProps = get_domain_default_props($_SESSION['user_id']);
	$dmnId = $domainProps['domain_id'];
	$dmnName = $domainProps['domain_name'];

	client_generateCatchallList($tpl, $dmnId, $dmnName);
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('mail') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/mail_catchall.tpl',
		'page_message' => 'layout',
		'catchall_item' => 'page',
		'del_icon' => 'catchall_item'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Email / Catchall'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_STATUS' => tr('Status'),
		'TR_ACTION' => tr('Action'),
		'TR_TITLE_CATCHALL_MAIL_USERS' => tr('Catch all'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_CATCHALL' => tr('Catch all'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s catch all?', true, '%s'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_CANCEL' => tr('Cancel')
	)
);

client_generatePage($tpl);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
