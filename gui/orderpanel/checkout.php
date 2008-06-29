<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
* Functions start
*/

function gen_checkout(&$tpl, &$sql, $user_id, $plan_id) {
    $date = time();
    $domain_name = $_SESSION['domainname'];
    $fname = $_SESSION['fname'];
    $lname = $_SESSION['lname'];

    if (isset($_SESSION['firm'])) {
        $firm = $_SESSION['firm'];
    } else {
        $firm = '';
    }

    $zip = $_SESSION['zip'];
    $city = $_SESSION['city'];
    $country = $_SESSION['country'];
    $email = $_SESSION['email'];
    $phone = $_SESSION['phone'];

    if (isset($_SESSION['fax'])) {
        $fax = $_SESSION['fax'];
    } else {
        $fax = '';
    }

    $street1 = $_SESSION['street1'];

    if (isset($_SESSION['street2'])) {
        $street2 = $_SESSION['street2'];
    } else {
        $street2 = '';
    }

    $status = "new";

    $query = <<<SQL_QUERY
              insert into
			  		orders
					(user_id,
					plan_id,
					date,
					domain_name,
					fname,
					lname,
					firm,
					zip,
					city,
					country,
					email,
					phone,
					fax,
					street1,
					street2,
					status)
              values
                 (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id, $plan_id, $date, $domain_name, $fname, $lname, $firm, $zip, $city, $country, $email, $phone, $fax, $street1, $street2, $status));
//     print $sql->ErrorMsg();
    $order_id = $sql->Insert_ID();
    send_order_emails($user_id, $domain_name, $fname, $lname, $email, $order_id);

    if (isset($_SESSION['details']))
        unset($_SESSION['details']);

    if (isset($_SESSION['domainname']))
        unset($_SESSION['domainname']);

    if (isset($_SESSION['fname']))
        unset($_SESSION['fname']);

    if (isset($_SESSION['lname']))
        unset($_SESSION['lname']);

    if (isset($_SESSION['email']))
        unset($_SESSION['email']);

    if (isset($_SESSION['firm']))
        unset($_SESSION['firm']);

    if (isset($_SESSION['zip']))
        unset($_SESSION['zip']);

    if (isset($_SESSION['city']))
        unset($_SESSION['city']);

    if (isset($_SESSION['country']))
        unset($_SESSION['country']);

    if (isset($_SESSION['street1']))
        unset($_SESSION['street1']);

    if (isset($_SESSION['street2']))
        unset($_SESSION['street2']);

    if (isset($_SESSION['phone']))
        unset($_SESSION['phone']);

    if (isset($_SESSION['fax']))
        unset($_SESSION['fax']);

    if (isset($_SESSION['plan_id']))
        unset($_SESSION['plan_id']);
}

/*
* Functions end
*/

/*
*
* static page messages.
*
*/

if (isset($_SESSION['user_id']) && $_SESSION['plan_id']) {
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
    array('CHECK_OUT' => tr('Check Out'),
        'THANK_YOU_MESSAGE' => tr('<b>Thank You for purchasing</b><br>You will receive an email with more details and information'),
        'THEME_CHARSET' => tr('encoding'),
        )
    );

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

?>