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
 * functions
 */

/**
 * Generate package list.
 *
 * @throws iMSCP_Exception_Production
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  int $user_id User unique identifier
 * @return void
 */
function gen_packages_list($tpl, $user_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// In any case, we must check that the provided $user_id is one of existent reseller
	$query = "SELECT count('admin_id') as `cnt` FROM admin where admin_id = ? and admin_type = 'reseller'";
	$stmt = exec_query($query, $user_id);

	if ($stmt->fields['cnt']) {

		if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
			$query = "
			SELECT
				`t1`.*, `t2`.`admin_id`, `t2`.`admin_type`
			FROM
				`hosting_plans` `t1`, `admin` `t2`
			WHERE
				`t2`.`admin_type` = ?
			AND
				`t1`.`reseller_id` = `t2`.`admin_id`
			AND
				`t1`.`status` = ?
			ORDER BY
				`t1`.`id`
		";
			$stmt = exec_query($query, array('admin', 1));
		} else {
			$query = "SELECT * FROM `hosting_plans` WHERE `reseller_id` = ? AND `status` = '1'";
			$stmt = exec_query($query, $user_id);
		}

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
			//throw new iMSCP_Exception_Production(tr('No hosting plan available for purchase'));
		} else {
			while (!$stmt->EOF) {
				$description = $stmt->fields['description'];

				$price = $stmt->fields['price'];
				$setupFee = $stmt->fields['setup_fee'];
				$vat = $stmt->fields['vat'];

				if ($price == 0 || $price == '') {
					$price = tr('Free of charge');
				} else {
					$price = sprintf('%.02f', round($price * (1 + $vat / 100), 2));
				}

				$tpl->assign(
					array(
						'PACK_NAME' => tohtml($stmt->fields['name']),
						'PACK_ID' => $stmt->fields['id'],
						'USER_ID' => $user_id,
						'PAYMENT_PERIOD' => (is_numeric($price)) ? '(' . tohtml($stmt->fields['payment']) . ')' : '',
						'PACK_INFO' => tohtml($description),
						'PRICE' => (is_numeric($price)) ? $price . tohtml($stmt->fields['value']) : $price
					)
				);

				if ($setupFee == 0 || $setupFee == '') {
					$tpl->assign('SETUP_FEE_BLOCK', '');
				} else {
					$tpl->assign(
						'SETUP_FEE',
						sprintf('%.02f', round($setupFee * (1 + $vat / 100), 2)) . tohtml($stmt->fields['value'])
					);
					$tpl->parse('SETUP_FEE_BLOCK', '.setup_fee_block');
				}

				$tpl->parse('PURCHASE_LIST', '.purchase_list');
				$stmt->moveNext();
			}
		}

	} else {
		showBadRequestErrorPage();
	}
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require 'imscp-lib.php';

//session_unset();

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';

if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && empty($coid) ||
	(isset($_GET['coid']) && $_GET['coid'] == $coid)
) { // Customers access using reseller order panel access URL
	$userId = $_GET['user_id'];
	$_SESSION['order_panel_user_id'] = $userId;
} elseif (isset($_SESSION['user_id'])) { // Preview access from the panel
	$userId = $_SESSION['user_id'];
	$_SESSION['order_panel_user_id'] = $userId;
} else {
	showBadRequestErrorPage();
}

// Set cancel uri
$_SESSION['order_panel_cancel_uri'] = tohtml("index.php?coid=$coid&user_id=$userId&cancel=yes");

// Handle cancel action
if (isset($_GET['cancel'])) {
	unset(
		$_SESSION['order_panel_plan_id'],
		// Force domain add on form to be show
		$_SESSION['order_panel_domainname'],
		// Force personal data form to be show
		$_SESSION['order_panel_fname'],
		$_SESSION['order_panel_email'],
		$_SESSION['order_panel_lname'],
		$_SESSION['order_panel_gender'],
		$_SESSION['order_panel_firm'],
		$_SESSION['order_panel_zip'],
		$_SESSION['order_panel_city'],
		$_SESSION['order_panel_country'],
		$_SESSION['order_panel_street1'],
		$_SESSION['order_panel_street2'],
		$_SESSION['order_panel_phone'],
		$_SESSION['order_panel_fax']
	);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($userId)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/index.tpl',
		'page_message' => 'page', // Must be in page here
		'purchase_list' => 'page',
		'setup_fee_block' => 'purchase_list'
	)
);

gen_packages_list($tpl, $userId);
generatePageMessage($tpl);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Order Panel / Choose hosting plan'),
		'THEME_CHARSET' => tr('encoding'),
		'TR_PRICE' => tr('Price'),
		'TR_SETUP_FEE' => tr('Setup Fee'),
		'TR_PURCHASE' => tr('Purchase'),
		'TR_MORE_DETAIL' => tr('More details'),
	)
);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
