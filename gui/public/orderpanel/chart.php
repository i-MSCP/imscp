<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Orderpanel
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Script functions
 */

/**
 * Generates chart.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $user_id User unique identifier
 * @param int $plan_id Plan unique identifier
 * @return void
 */
function generateChart($tpl, $user_id, $plan_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";
		$stmt = exec_query($query, $plan_id);
	} else {
		$query = "SELECT * FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";
		$stmt = exec_query($query, array($user_id, $plan_id));
	}

	if ($stmt->recordCount() == 0) {
		redirectTo('index.php');
	} else {
		$price = $stmt->fields['price'];
		$setup_fee = $stmt->fields['setup_fee'];
		$total = $price + $setup_fee;

		if ($price == 0 || $price == '') {
			$price = tr('free of charge');
		} else {
			$price .= ' ' . tohtml($stmt->fields['value']) . ' ' .
				tohtml($stmt->fields['payment']);
		}

		if ($setup_fee == 0 || $setup_fee == '') {
			$setup_fee = tr('free of charge');
		} else {
			$setup_fee .= ' ' . tohtml($stmt->fields['value']);
		}

		if ($total == 0) {
			$total = tr('free of charge');
		} else {
			$total .= ' ' . tohtml($stmt->fields['value']);
		}

		$tpl->assign(
			array(
				'PRICE' => $price,
				'SETUP' => $setup_fee,
				'TOTAL' => $total,
				'TR_PACKAGE_NAME' => tohtml($stmt->fields['name'])));

		if ($stmt->fields['tos'] != '') {
			$tpl->assign(
				array(
					'TR_TOS_PROPS' => tr('Term of Service'),
					'TR_TOS_ACCEPT' => tr('I Accept The Term of Service'),
					'TOS' => tohtml($stmt->fields['tos'])));

			$_SESSION['order_panel_tos'] = true;
		} else {
			$tpl->assign('TOS_FIELD', '');
			$_SESSION['order_panel_tos'] = false;
		}
	}
}

/**
 * Genetates user personal data.
 *
 * @param iMSCP_pTemplate $tpl Template engine.
 *
 * @return void
 */
function generateUserPersonalData($tpl)
{
	$first_name = (isset($_SESSION['order_panel_fname'])) ? $_SESSION['order_panel_fname'] : '';
	$last_name = (isset($_SESSION['order_panel_lname'])) ? $_SESSION['order_panel_lname'] : '';
	$company = (isset($_SESSION['order_panel_firm'])) ? $_SESSION['order_panel_firm'] : '';
	$postal_code = (isset($_SESSION['order_panel_zip'])) ? $_SESSION['order_panel_zip'] : '';
	$city = (isset($_SESSION['order_panel_city'])) ? $_SESSION['order_panel_city'] : '';
	$state = (isset($_SESSION['order_panel_state'])) ? $_SESSION['order_panel_state'] : '';
	$country = (isset($_SESSION['order_panel_country'])) ? $_SESSION['order_panel_country'] : '';
	$street1 = (isset($_SESSION['order_panel_street1'])) ? $_SESSION['order_panel_street1'] : '';
	$street2 = (isset($_SESSION['order_panel_street2'])) ? $_SESSION['order_panel_street2'] : '';
	$phone = (isset($_SESSION['order_panel_phone'])) ? $_SESSION['order_panel_phone'] : '';
	$fax = (isset($_SESSION['order_panel_fax'])) ? $_SESSION['order_panel_fax'] : '';
	$email = (isset($_SESSION['order_panel_email'])) ? $_SESSION['order_panel_email'] : '';
	$gender = (isset($_SESSION['order_panel_gender']))
		? get_gender_by_code($_SESSION['order_panel_gender']) : get_gender_by_code('');

	$tpl->assign(
		array(
			'VL_USR_NAME' => tohtml($first_name),
			'VL_LAST_USRNAME' => tohtml($last_name),
			'VL_USR_FIRM' => tohtml($company),
			'VL_USR_POSTCODE' => tohtml($postal_code),
			'VL_USR_GENDER' => tohtml($gender),
			'VL_USRCITY' => tohtml($city),
			'VL_USRSTATE' => tohtml($state),
			'VL_COUNTRY' => tohtml($country),
			'VL_STREET1' => tohtml($street1),
			'VL_STREET2' => tohtml($street2),
			'VL_PHONE' => tohtml($phone),
			'VL_FAX' => tohtml($fax),
			'VL_EMAIL' => tohtml($email)));
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_SESSION['order_panel_user_id']) && isset($_SESSION['order_panel_plan_id'])) {
	$userId = $_SESSION['order_panel_user_id'];
	$hostingPlanId = $_SESSION['order_panel_plan_id'];
} else {
	throw new iMSCP_Exception_Production(tr('You do not have permission to access this interface.'));
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($userId)));

$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/chart.tpl',
		'page_message' => 'page', // Must be in page here
		'tos_field' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Order Panel / Chart'),
		'YOUR_CHART' => tr('Your Chart'),
		'TR_COSTS' => tr('Costs'),
		'TR_PACKAGE_PRICE' => tr('Price'),
		'TR_PACKAGE_SETUPFEE' => tr('Setup fee'),
		'TR_TOTAL' => tr('Total'),
		'TR_CONTINUE' => tr('Purchase'),
		'TR_CHANGE' => tr('Change'),
		'TR_FIRSTNAME' => tr('First name'),
		'TR_LASTNAME' => tr('Last name'),
		'TR_GENDER' => tr('Gender'),
		'TR_COMPANY' => tr('Company'),
		'TR_POST_CODE' => tr('Zip/Postal code'),
		'TR_CITY' => tr('City'),
		'TR_STATE' => tr('State/Province'),
		'TR_COUNTRY' => tr('Country'),
		'TR_STREET1' => tr('Street 1'),
		'TR_STREET2' => tr('Street 2'),
		'TR_EMAIL' => tr('Email'),
		'TR_PHONE' => tr('Phone'),
		'TR_FAX' => tr('Fax'),
		'TR_EMAIL' => tr('Email'),
		'TR_PERSONAL_DATA' => tr('Personal Data'),
		'TR_CAPCODE' => tr('Security code'),
		'TR_IMGCAPCODE_DESCRIPTION' => tr('To avoid abuse, we ask you to write the combination of letters on the above picture.'),
		'TR_IMGCAPCODE' => '<img src="/imagecode.php" width="' .
			$cfg->LOSTPASSWORD_CAPTCHA_WIDTH . '" height="' .
			$cfg->LOSTPASSWORD_CAPTCHA_HEIGHT . '" border="0" alt="captcha image" />',
		'THEME_CHARSET' => tr('encoding')));

generateChart($tpl, $userId, $hostingPlanId);
generateUserPersonalData($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
