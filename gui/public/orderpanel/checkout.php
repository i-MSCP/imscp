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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Orderpanel
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-msCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

/************************************************************************************
 * Script functions
 */

/**
 * Generates checkout.
 *
 * @param $user_id User unique identifier
 * @param $plan_id Plan unique identifier
 * @return void
 */
function generateCheckout($user_id, $plan_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$date = time();
	$domain_name = $_SESSION['domainname'];
	$fname = $_SESSION['fname'];
	$lname = $_SESSION['lname'];
	$gender = $_SESSION['gender'];
	$firm = (isset($_SESSION['firm'])) ? $_SESSION['firm'] : '';
	$zip = $_SESSION['zip'];
	$city = $_SESSION['city'];
	$state = $_SESSION['state'];
	$country = $_SESSION['country'];
	$email = $_SESSION['email'];
	$phone = $_SESSION['phone'];
	$fax = (isset($_SESSION['fax'])) ? $_SESSION['fax'] : '';
	$street1 = $_SESSION['street1'];
	$street2 = (isset($_SESSION['street2'])) ? $_SESSION['street2'] : '';

	$query = "
		INSERT INTO `orders` (
            `user_id`, `plan_id`, `date`, `domain_name`, `fname`, `lname`, `gender`,
            `firm`,`zip`, `city`, `state`, `country`, `email`, `phone`, `fax`,
            `street1`, `street2`, `status`
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
	";
	exec_query($query, array($user_id, $plan_id, $date, $domain_name, $fname, $lname,
		$gender, $firm, $zip, $city, $state, $country, $email,
		$phone, $fax, $street1, $street2,
		$cfg->ITEM_ORDER_UNCONFIRMED_STATUS));

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');
	send_order_emails($user_id, $domain_name, $fname, $lname, $email, $db->insertId());

	// Remove useless data
	unset($_SESSION['details'], $_SESSION['domainname'], $_SESSION['fname'],
	$_SESSION['lname'], $_SESSION['gender'], $_SESSION['email'], $_SESSION['firm'],
	$_SESSION['zip'], $_SESSION['city'], $_SESSION['state'], $_SESSION['country'],
	$_SESSION['street1'], $_SESSION['street2'], $_SESSION['phone'], $_SESSION['fax'],
	$_SESSION['plan_id'], $_SESSION['image'], $_SESSION['tos']);
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_SESSION['user_id']) && isset($_SESSION['plan_id'])) {
	$user_id = $_SESSION['user_id'];
	$plan_id = $_SESSION['plan_id'];
} else {
	throw new iMSCP_Exception_Production(tr('You do not have permission to access this interface.'));
}

if (!isset($_POST['capcode']) || $_POST['capcode'] != $_SESSION['image']) {
	set_page_message(tr('Security code is incorrect.'), 'error');
	redirectTo('chart.php');
}

// If term of service field was set (not empty value)
if (isset($_SESSION['tos']) && $_SESSION['tos'] == true) {
	if (!isset($_POST['tosAccept']) || $_POST['tosAccept'] != 1) {
		set_page_message(tr('You have to accept the Term of Service.'), 'error');
		redirectTo('chart.php');
	}
}

if ((isset($_SESSION['fname']) && $_SESSION['fname'] != '') && (isset($_SESSION['lname']) && $_SESSION['lname'] != '')
	&& (isset($_SESSION['email']) && $_SESSION['email'] != '') && (isset($_SESSION['zip']) && $_SESSION['zip'] != '')
	&& (isset($_SESSION['city']) && $_SESSION['city'] != '') && (isset($_SESSION['country']) && $_SESSION['country'] != '')
	&& (isset($_SESSION['street1']) && $_SESSION['street1'] != '') && (isset($_SESSION['phone']) && $_SESSION['phone'] != '')
) {
	generateCheckout($user_id, $plan_id);
} else {
	redirectTo('index.php?user_id=' . $user_id);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($user_id)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/checkout.tpl',
		'page_message' => 'page'));

$tpl->assign(
	array(
		'TR_ORDER_PANEL_PAGE_TITLE' => tr('Order Panel / Checkout'),
		'CHECK_OUT' => tr('Check Out'),
		'THANK_YOU_MESSAGE' => tr("<strong>Thank you for purchasing.</strong><br /><br />An email has been sent to your email address for confirmation. Do not forget to click on the link in email to validate your order."),
		'THEME_CHARSET' => tr('encoding')));

generatePageMessage($tpl);

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
