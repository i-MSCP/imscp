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
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Check input data.
 *
 * @return void
 */
function reseller_checkData()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['dmn_name']) && $_POST['dmn_name'] != '') {
		$dmnName = strtolower(trim($_POST['dmn_name']));
	} else {
		set_page_message(tr('Domain name cannot be empty.'), 'error');
		return;
	}

	global $dmnNameValidationErrMsg;

	if (!isValidDomainName($dmnName)) {
		set_page_message($dmnNameValidationErrMsg, 'error');
		return;
	}

	$asciiDmnName = encode_idna($dmnName);

	if (imscp_domain_exists($asciiDmnName, $_SESSION['user_id']) || $asciiDmnName == $cfg->BASE_SERVER_VHOST) {
		set_page_message(tr('Domain %s is unavailable.', "<strong>$dmnName</strong>"), 'error');
		return;
	}

	if (isset($_POST['datepicker']) && !empty($_POST['datepicker'])) {
		if (($dmnExpire = strtotime($_POST['datepicker'])) === false) {
			set_page_message(tr('Invalid expiration date.'), 'error');
			return;
		}
	} elseif (isset($_POST['never_expire'])) {
		$dmnExpire = 0;
	} else {
		set_page_message(tr('Domain expiration date must be filled.'), 'error');
		return;
	}

	// Get hosting plan name if one is set
	if (isset($_POST['dmn_tpl'])) {
		$hpId = clean_input($_POST['dmn_tpl']);
	} else {
		$hpId = 0;
	}

	// Whether or not reseller want customize hosting plan
	if ((isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin')) {
		$customizeHp = '_no_';
	} elseif (!isset($_POST['chtpl'])) {
		$customizeHp = '_no_';
	} else {
		$customizeHp = $_POST['chtpl'];
	}

	// Reseller want customise hosting plan or not hosting plan is provided
	if (!$hpId || $customizeHp == '_yes_') {
		$_SESSION['dmn_name'] = $asciiDmnName;
		$_SESSION['dmn_expire'] = $dmnExpire;
		$_SESSION['dmn_tpl'] = $hpId;
		$_SESSION['chtpl'] = '_yes_';
		$_SESSION['step_one'] = '_yes_';

		redirectTo('user_add2.php');
	} else {
		if (reseller_limits_check($_SESSION['user_id'], $hpId)) {
			$_SESSION['dmn_name'] = $asciiDmnName;
			$_SESSION['dmn_expire'] = $dmnExpire;
			$_SESSION['dmn_tpl'] = $hpId;
			$_SESSION['chtpl'] = $customizeHp;
			$_SESSION['step_one'] = '_yes_';

			redirectTo('user_add3.php');
		} else {
			set_page_message(tr('Hosting plan limits exceed reseller limits.'), 'error');
		}
	}
}

/**
 * Show first page of add user with data.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function reseller_generatePage($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl->assign(
		array(
			'DOMAIN_NAME_VALUE' => isset($_POST['dmn_name']) ? tohtml($_POST['dmn_name']) : '',
			'DATEPICKER_VALUE' => isset($_POST['datepicker']) ? tohtml($_POST['datepicker']) : '',
			'CHTPL1_VAL' => (isset($_POST['chtpl']) && $_POST['chtpl'] == '_yes_') ? $cfg->HTML_CHECKED : '',
			'CHTPL2_VAL' => (isset($_POST['chtpl']) && $_POST['chtpl'] == '_yes_') ? '' : $cfg->HTML_CHECKED
		)
	);
}

/**
 * Generate hosting plan list.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function reseller_generateHostingPlanList($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "
			SELECT
				`t1`.`id`, `t1`.`name`
			FROM
				`hosting_plans` AS `t1`
			LEFT JOIN
				`admin` AS `t2` ON(`t1`.`reseller_id` = `t2`.`admin_id`)
			WHERE
				`t2`.`admin_type` = ?
			AND
				`t1`.`status` = ?
			ORDER BY
				`t1`.`name`
		";
		$stmt = exec_query($query, array('admin', '1'));

		$tpl->assign('CUSTOMIZE_HOSTING_PLAN_BLOCK', ''); // Hosting plan created by administrators can't be customized

		if (!$stmt->rowCount()) {
			set_page_message(tr('No hosting plan available. Please contact your administrator.'), 'error');
			$tpl->assign('ADD_CUSTOMER_BLOCK', '');
		}
	} else {
		$query = "SELECT `id`, `name` FROM `hosting_plans` WHERE `reseller_id` = ? AND `status` = ? ORDER BY `name`";
		$stmt = exec_query($query, array($resellerId, '1'));
	}

	if ($stmt->rowCount()) {
		while (($data = $stmt->fetchRow())) {
			$hpId = isset($_POST['dmn_tpl']) ? $_POST['dmn_tpl'] : '';
			$tpl->assign(
				array(
					'HP_NAME' => tohtml($data['name']),
					'HP_ID' => $data['id'],
					'HP_SELECTED' => ($data['id'] == $hpId) ? $cfg->HTML_SELECTED : ''
				)
			);

			$tpl->parse('HOSTING_PLAN_ENTRY_BLOCK', '.hosting_plan_entry_block');
		}
	} else {
		$tpl->assign('HOSTING_PLAN_ENTRIES_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_POST['uaction']) && $_POST['uaction'] == 'user_add_next') {
	reseller_checkData();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_add1.tpl',
		'page_message' => 'layout',
		'add_customer_block' => 'page',
		'hosting_plan_entries_block' => 'add_customer_block',
		'hosting_plan_entry_block' => 'hosting_plan_entries_block',
		'customize_hosting_plan_block' => 'hosting_plan_entries_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Customers / Add Customer'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ADD_USER' => tr('Add user'),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_DOMAIN_EXPIRE' => tr('Domain expiration date'),
		'TR_EXPIRE_CHECKBOX' => tr('Never'),
		'TR_CHOOSE_HOSTING_PLAN' => tr('Choose hosting plan'),
		'TR_PERSONALIZE_TEMPLATE' => tr('Personalise template'),
		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),
		'TR_NEXT_STEP' => tr('Next step'),
		'TR_DMN_HELP' => tr("You must omit 'www'. It will be added automatically.")
	)
);

generateNavigation($tpl);
reseller_generatePage($tpl);
reseller_generateHostingPlanList($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
