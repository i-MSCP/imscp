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
 * @subpackage    Orderpanel
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Script functions
 */

/**
 * Add domain in session for later use
 *
 * @param string $dmnName Domain name
 * @return void
 */
function addon_domain($dmnName)
{
	if (!validates_dname($dmnName)) {
		global $validation_err_msg;
		set_page_message(tr($validation_err_msg), 'error');
		return;
	}

	// Should be done after domain name validation
	$dmnName = encode_idna(strtolower($dmnName));

	if (imscp_domain_exists($dmnName, 0) ||
		$dmnName == iMSCP_Registry::get('config')->BASE_SERVER_VHOST
	) {
		set_page_message(tr('Domain already registered in our database.'));
		return;
	}

	$_SESSION['order_panel_domainname'] = $dmnName;
	redirectTo('address.php');
}

/**
 * Check whether or not a plan is available.
 *
 * @param int $planId Plan unique identifier
 * @param int $userId user unique identifier
 * @return bool
 */
function is_plan_available($planId, $userId)
{
	$cfg = iMSCP_Registry::get('config');

	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";
		$stmt = exec_query($query, $planId);
	} else {
		$query = "SELECT * FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";
		$stmt = exec_query($query, array($userId, $planId));
	}

	return $stmt->recordCount() > 0 && $stmt->fields['status'] != 0;
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_SESSION['order_panel_user_id'])) {
	$userId = $_SESSION['order_panel_user_id'];

	if (isset($_SESSION['order_panel_plan_id'])) {
		$planId = $_SESSION['order_panel_plan_id'];
	} elseif (isset($_GET['id'])) {
		$planId = $_GET['id'];

		if (is_plan_available($planId, $userId)) {
			$_SESSION['order_panel_plan_id'] = $planId;
		} else {
			showBadRequestErrorPage();
		}
	} else {
		showBadRequestErrorPage();
	}
} else {
	showBadRequestErrorPage();
}

if (isset($_SESSION['order_panel_domainname'])) {
	redirectTo('address.php');
}

if (isset($_POST['domainname'])) {
	addon_domain($_POST['domainname']);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($userId)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/addon.tpl',
		'page_message' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Order Panel / Domain name'),
		'DOMAIN_ADDON' => tr('Add On A Domain'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_CANCEL' => tr('Cancel'),
		'CANCEL_URI' => $_SESSION['order_panel_cancel_uri'],
		'TR_CONTINUE' => tr('Continue'),
		'TR_EXAMPLE' => tr('(e.g. domain-of-your-choice.com)'),
		'THEME_CHARSET' => tr('encoding')
	)
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
