<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
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

	$status = 'unconfirmed';

	$query = "
		INSERT INTO
			`orders`
				(`user_id`,
				`plan_id`,
				`date`,
				`domain_name`,
				`fname`,
				`lname`,
				`gender`,
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
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	";

	$rs = exec_query($sql, $query, array($user_id, $plan_id, $date, $domain_name, $fname, $lname, $gender, $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1, $street2, $status));

	$order_id = $sql->Insert_ID();
	send_order_emails($user_id, $domain_name, $fname, $lname, $email, $order_id);

	// Remove useless data
	unset($_SESSION['details']);
	unset($_SESSION['domainname']);
	unset($_SESSION['fname']);
	unset($_SESSION['lname']);
	unset($_SESSION['gender']);
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
	unset($_SESSION['image']);
	unset($_SESSION['tos']);
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

if (!isset($_POST['capcode']) || $_POST['capcode'] != $_SESSION['image']) {
	set_page_message(tr('Security code was incorrect!'));
	user_goto('chart.php');
}


// If term of service field was set (not empty value)
if(isset($_SESSION['tos']) && $_SESSION['tos'] == true) {
	if(!isset($_POST['tosAccept']) or $_POST['tosAccept'] != 1 ){
		set_page_message(tr('You have to accept the Term of Service!'));
		user_goto('chart.php');
	}
}

if ((isset($_SESSION['fname']) && $_SESSION['fname'] != '')
	&& (isset($_SESSION['lname']) && $_SESSION['lname'] != '')
	&& (isset($_SESSION['email']) && $_SESSION['email'] != '')
	&& (isset($_SESSION['zip']) && $_SESSION['zip'] != '')
	&& (isset($_SESSION['city']) && $_SESSION['city'] != '')
	&& (isset($_SESSION['state']) && $_SESSION['state'] != '')
	&& (isset($_SESSION['country']) && $_SESSION['country'] != '')
	&& (isset($_SESSION['street1']) && $_SESSION['street1'] != '')
	&& (isset($_SESSION['phone']) && $_SESSION['phone'] != '')
	) {
	gen_checkout($tpl, $sql, $user_id, $plan_id);
} else {
	user_goto('index.php?user_id=' . $user_id);
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

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
