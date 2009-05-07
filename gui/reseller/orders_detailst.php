<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

// Begin page line
require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/orders_detailst.tpl');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('ip_entry', 'page');
$tpl->define_dynamic('page_message', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_RESELLER_MAIN_INDEX_PAGE_TITLE'	=> tr('ispCP - Reseller/Order details'),
		'THEME_COLOR_PATH'					=> "../themes/$theme_color",
		'THEME_CHARSET'						=> tr('encoding'),
		'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

/*
 * Functions
 */

function gen_order_details(&$tpl, &$sql, $user_id, $order_id) {
	$query = "
		SELECT
			*
		FROM
			`orders`
		WHERE
			`id` = ?
		AND
			`user_id` = ?
	";
	$rs = exec_query($sql, $query, array($order_id, $user_id));
	if ($rs->RecordCount() == 0) {
		set_page_message(tr('Permission deny!'));
		user_goto('orders.php');
	}
	$plan_id = $rs->fields['plan_id'];

	$date_formt = Config::get('DATE_FORMAT');
	$date = date($date_formt, $rs->fields['date']);

	if (isset($_POST['uaction'])) {
		$domain_name	= $_POST['domain'];
		$customer_id	= $_POST['customer_id'];
		$fname			= $_POST['fname'];
		$lname			= $_POST['lname'];
		$gender			= $_POST['gender'];
		$firm			= $_POST['firm'];
		$zip			= $_POST['zip'];
		$city			= $_POST['city'];
		$state			= $_POST['state'];
		$country		= $_POST['country'];
		$street1		= $_POST['street1'];
		$street2		= $_POST['street2'];
		$email			= $_POST['email'];
		$phone			= $_POST['phone'];
		$fax			= $_POST['fax'];
	} else {
		$domain_name	= $rs->fields['domain_name'];
		$customer_id	= $rs->fields['customer_id'];
		$fname			= $rs->fields['fname'];
		$lname			= $rs->fields['lname'];
		$gender			= $rs->fields['gender'];
		$firm			= $rs->fields['firm'];
		$zip			= $rs->fields['zip'];
		$city			= $rs->fields['city'];
		$state			= $rs->fields['state'];
		$country		= $rs->fields['country'];
		$email			= $rs->fields['email'];
		$phone			= $rs->fields['phone'];
		$fax			= $rs->fields['fax'];
		$street1		= $rs->fields['street1'];
		$street2		= $rs->fields['street2'];
	}
	$query = "
		SELECT
			`name`, `description`
		FROM
			`hosting_plans`
		WHERE
			`id` = ?
	";
	$rs = exec_query($sql, $query, array($plan_id));
	$plan_name = $rs->fields['name'] . "<br />" . $rs->fields['description'];

	generate_ip_list($tpl, $_SESSION['user_id']);

	if ($customer_id === null) $customer_id = '';

	$tpl->assign(
		array(
			'ID'			=> $order_id,
			'DATE'			=> $date,
			'HP'			=> $plan_name,
			'DOMAINNAME'	=> $domain_name,
			'CUSTOMER_ID'	=> $customer_id,
			'FNAME'			=> $fname,
			'LNAME'			=> $lname,
			'FIRM'			=> $firm,
			'ZIP'			=> $zip,
			'CITY'			=> $city,
			'STATE'			=> $state,
			'COUNTRY'		=> $country,
			'EMAIL'			=> $email,
			'PHONE'			=> $phone,
			'FAX'			=> $fax,
			'STREET1'		=> $street1,
			'STREET2'		=> $street2,
			'VL_MALE'		=> (($gender == 'M') ? 'selected="selected"' : ''),
			'VL_FEMALE'		=> (($gender == 'F') ? 'selected="selected"' : ''),
			'VL_UNKNOWN'	=> ((($gender == 'U') || (empty($gender))) ? 'selected="selected"' : '')
		)
	);
}

function update_order_details(&$tpl, &$sql, $user_id, $order_id) {
	$domain			= strtolower($_POST['domain']);
	$domain			= encode_idna($domain);
	$customer_id	= strip_html($_POST['customer_id']);
	$fname			= strip_html($_POST['fname']);
	$lname			= strip_html($_POST['lname']);
	$gender			= in_array($_POST['gender'],array('M', 'F', 'U')) ? $_POST['gender'] : 'U';
	$firm			= strip_html($_POST['firm']);
	$zip			= strip_html($_POST['zip']);
	$city			= strip_html($_POST['city']);
	$state			= strip_html($_POST['state']);
	$country		= strip_html($_POST['country']);
	$street1		= strip_html($_POST['street1']);
	$street2		= strip_html($_POST['street2']);
	$email			= strip_html($_POST['email']);
	$phone			= strip_html($_POST['phone']);
	$fax			= strip_html($_POST['fax']);

	$query = "
		UPDATE
			`orders`
		SET
			`domain_name` = ?,
			`customer_id` = ?,
			`fname` = ?,
			`lname` = ?,
			`gender` = ?,
			`firm` = ?,
			`zip` = ?,
			`city` = ?,
			`state` = ?,
			`country` = ?,
			`email` = ?,
			`phone` = ?,
			`fax` = ?,
			`street1` = ?,
			`street2` = ?
		WHERE
			`id` = ?
		AND
			`user_id` = ?
	";
	exec_query($sql, $query, array($domain, $customer_id, $fname, $lname, $gender, $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1, $street2, $order_id, $user_id));
}

// end of functions

/*
 *
 * static page messages.
 *
 */

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	$order_id = $_GET['order_id'];
} else {
	set_page_message(tr('Wrong order ID!'));
	user_goto('orders.php');
}

if (isset($_POST['uaction'])) {
	update_order_details($tpl, $sql, $_SESSION['user_id'], $order_id);

	if ($_POST['uaction'] === 'update_data') {
		set_page_message(tr('Order data updated successfully!'));
	} else if ($_POST['uaction'] === 'add_user') {
		$_SESSION['domain_ip'] = @$_POST['domain_ip'];
		user_goto('orders_add.php?order_id=' . $order_id);
	}
}

gen_order_details($tpl, $sql, $_SESSION['user_id'], $order_id);

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_orders.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_orders.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_ORDERS'			=> tr('Manage Orders'),
		'TR_DATE'					=> tr('Order date'),
		'TR_HP'						=> tr('Hosting plan'),
		'TR_HOSTING_INFO'			=> tr('Hosting details'),
		'TR_DOMAIN'					=> tr('Domain'),
		'TR_FIRST_NAME'				=> tr('First name'),
		'TR_LAST_NAME'				=> tr('Last name'),
		'TR_GENDER'					=> tr('Gender'),
		'TR_MALE'					=> tr('Male'),
		'TR_FEMALE'					=> tr('Female'),
		'TR_UNKNOWN'				=> tr('Unknown'),
		'TR_COMPANY'				=> tr('Company'),
		'TR_ZIP_POSTAL_CODE'		=> tr('Zip/Postal code'),
		'TR_CITY'					=> tr('City'),
		'TR_STATE'					=> tr('State/Province'),
		'TR_COUNTRY'				=> tr('Country'),
		'TR_STREET_1'				=> tr('Street 1'),
		'TR_STREET_2'				=> tr('Street 2'),
		'TR_EMAIL'					=> tr('Email'),
		'TR_PHONE'					=> tr('Phone'),
		'TR_FAX'					=> tr('Fax'),
		'TR_UPDATE_DATA'			=> tr('Update data'),
		'TR_ORDER_DETAILS'			=> tr('Order details'),
		'TR_CUSTOMER_DATA'			=> tr('Customer data'),
		'TR_DELETE_ORDER'			=> tr('Delete order'),
		'TR_DMN_IP'					=> tr('Domain IP'),
		'TR_CUSTOMER_ID'			=> tr('Customer ID'),
		'TR_MESSAGE_DELETE_ACCOUNT'	=> tr('Are you sure you want to delete this order?', true),
		'TR_ADD'					=> tr('Add to the system')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
