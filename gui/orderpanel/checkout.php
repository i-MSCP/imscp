<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2009 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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

require '../include/ispcp-lib.php';

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('PURCHASE_TEMPLATE_PATH') . '/checkout.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('purchase_header', 'page');
$tpl->define_dynamic('purchase_footer', 'page');

/*
 * functions start
 */

function gen_checkout(&$tpl, &$sql, $user_id, $plan_id) {
	$date = time();
	$domain_name = $_SESSION['domainname'];
	$fname = $_SESSION['fname'];
	$lname = $_SESSION['lname'];

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

	$status = 'new';

	$query = "
		INSERT INTO
			`orders`
				(`user_id`,
				`plan_id`,
				`date`,
				`domain_name`,
				`fname`,
				`lname`,
				`firm`,
				`zip`,
				`city`,
				`state`,
				`country`,
				`email`,
				`phone`,
				`fax`,
				`street1`,
				`street2`,
				`status`)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	";

	$rs = exec_query($sql, $query, array($user_id, $plan_id, $date, $domain_name, $fname, $lname, $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1, $street2, $status));
	//print $sql->ErrorMsg();
	$order_id = $sql->Insert_ID();
	send_order_emails($user_id, $domain_name, $fname, $lname, $email, $order_id);

	unset($_SESSION['details']);
	unset($_SESSION['domainname']);
	unset($_SESSION['fname']);
	unset($_SESSION['lname']);
	unset($_SESSION['email']);
	unset($_SESSION['firm']);
	unset($_SESSION['zip']);
	unset($_SESSION['city']);
	unset($_SESSION['state']);
	unset($_SESSION['country']);
	unset($_SESSION['street1']);
	unset($_SESSION['street2']);
	unset($_SESSION['phone']);
	unset($_SESSION['fax']);
	unset($_SESSION['plan_id']);
}

/*
 * functions end
 */

/*
 *
 * static page messages.
 *
 */

if (isset($_SESSION['user_id']) && isset($_SESSION['plan_id'])) {
	$user_id = $_SESSION['user_id'];
	$plan_id = $_SESSION['plan_id'];
} else {
	system_message(tr('You do not have permission to access this interface!'));
}

if (
	(isset($_SESSION['fname']) && $_SESSION['fname'] != '') and
		(isset($_SESSION['lname']) && $_SESSION['lname'] != '') and
		(isset($_SESSION['email']) && $_SESSION['email'] != '') and
		(isset($_SESSION['zip']) && $_SESSION['zip'] != '') and
		(isset($_SESSION['city']) && $_SESSION['city'] != '') and
		(isset($_SESSION['state']) && $_SESSION['state'] != '') and
		(isset($_SESSION['country']) && $_SESSION['country'] != '') and
		(isset($_SESSION['street1']) && $_SESSION['street1'] != '') and
		(isset($_SESSION['phone']) && $_SESSION['phone'] != '')
	) {
	gen_checkout($tpl, $sql, $user_id, $plan_id);
} else {
	header("Location: index.php?user_id=$user_id");
	die();
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_page_message($tpl);

$tpl->assign(
	array(
		'CHECK_OUT' => tr('Check Out'),
		'THANK_YOU_MESSAGE' => tr('<strong>Thank you for purchasing.</strong><br />You will receive an e-mail with more details and information.'),
		'THEME_CHARSET' => tr('encoding'),
	)
);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

?>