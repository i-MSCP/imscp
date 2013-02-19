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
 * Generates checkout.
 *
 * @param  int $userId User unique identifier
 * @param int $planId Plan unique identifier
 * @return void
 */
function generateCheckout($userId, $planId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$date = time();
	$domain_name = $_SESSION['order_panel_domainname'];
	$fname = $_SESSION['order_panel_fname'];
	$lname = $_SESSION['order_panel_lname'];
	$gender = $_SESSION['order_panel_gender'];
	$firm = (isset($_SESSION['order_panel_firm'])) ? $_SESSION['order_panel_firm'] : '';
	$zip = $_SESSION['order_panel_zip'];
	$city = $_SESSION['order_panel_city'];
	$state = $_SESSION['order_panel_state'];
	$country = $_SESSION['order_panel_country'];
	$email = $_SESSION['order_panel_email'];
	$phone = $_SESSION['order_panel_phone'];
	$fax = (isset($_SESSION['order_panel_fax'])) ? $_SESSION['order_panel_fax'] : '';
	$street1 = $_SESSION['order_panel_street1'];
	$street2 = (isset($_SESSION['order_panel_street2'])) ? $_SESSION['order_panel_street2'] : '';

	$query = "
		INSERT INTO `orders` (
            `user_id`, `plan_id`, `date`, `domain_name`, `fname`, `lname`, `gender`, `firm`,`zip`, `city`, `state`,
            `country`, `email`, `phone`, `fax`, `street1`, `street2`, `status`
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
	";
	exec_query(
		$query,
		array(
			$userId, $planId, $date, $domain_name, $fname, $lname, $gender, $firm, $zip, $city, $state, $country, $email,
			$phone, $fax, $street1, $street2, $cfg->ITEM_ORDER_UNCONFIRMED_STATUS
		)
	);

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');
	send_order_emails($userId, $domain_name, $fname, $lname, $email, $db->insertId());

	// Remove useless data
	unset(
		$_SESSION['order_panel_details'], $_SESSION['order_panel_domainname'], $_SESSION['order_panel_fname'],
		$_SESSION['order_panel_lname'], $_SESSION['order_panel_gender'], $_SESSION['order_panel_email'],
		$_SESSION['order_panel_firm'], $_SESSION['order_panel_zip'], $_SESSION['order_panel_city'],
		$_SESSION['order_panel_state'], $_SESSION['order_panel_country'], $_SESSION['order_panel_street1'],
		$_SESSION['order_panel_street2'], $_SESSION['order_panel_phone'], $_SESSION['order_panel_fax'],
		$_SESSION['order_panel_plan_id'], $_SESSION['order_panel_image'], $_SESSION['order_panel_tos']
	);
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
	$planId = $_SESSION['order_panel_plan_id'];
} else {
	showBadRequestErrorPage();
}

if (!isset($_POST['capcode']) || $_POST['capcode'] != $_SESSION['image']) {
	set_page_message(tr('Security code is incorrect.'), 'error');
	redirectTo('chart.php');
}

// If terms of service field was set (not empty value)
if (isset($_SESSION['order_panel_tos']) && $_SESSION['order_panel_tos'] == true) {
	if (!isset($_POST['tosAccept']) || $_POST['tosAccept'] != 1) {
		set_page_message(tr('You have to accept the Terms of Service.'), 'error');
		redirectTo('chart.php');
	}
}

if (
	(isset($_SESSION['order_panel_fname']) && $_SESSION['order_panel_fname'] != '') &&
	(isset($_SESSION['order_panel_lname']) && $_SESSION['order_panel_lname'] != '') &&
	(isset($_SESSION['order_panel_email']) && $_SESSION['order_panel_email'] != '') &&
	(isset($_SESSION['order_panel_zip']) && $_SESSION['order_panel_zip'] != '') &&
	(isset($_SESSION['order_panel_city']) && $_SESSION['order_panel_city'] != '') &&
	(isset($_SESSION['order_panel_country']) && $_SESSION['order_panel_country'] != '') &&
	(isset($_SESSION['order_panel_street1']) && $_SESSION['order_panel_street1'] != '') &&
	(isset($_SESSION['order_panel_phone']) && $_SESSION['order_panel_phone'] != '')
) {
	generateCheckout($userId, $planId);
} else {
	redirectTo('index.php?user_id=' . $userId);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($userId)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/checkout.tpl',
		'page_message' => 'page' // Must be in page here
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Order Panel / Checkout'),
		'CHECK_OUT' => tr('Check Out'),
		'THANK_YOU_MESSAGE' => tr("<strong>Thank you for purchasing.</strong><br /><br />An email has been sent to your email address for confirmation. Do not forget to click on the link in email to validate your order."),
		'THEME_CHARSET' => tr('encoding')
	)
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
