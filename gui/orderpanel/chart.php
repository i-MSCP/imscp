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
$tpl->define_dynamic('page', Config::getInstance()->get('PURCHASE_TEMPLATE_PATH') . '/chart.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('purchase_header', 'page');
$tpl->define_dynamic('tos_field', 'page');
$tpl->define_dynamic('purchase_footer', 'page');


/*
 * functions start
 */

function gen_chart(&$tpl, &$sql, $user_id, $plan_id) {
	if (Config::getInstance()->exists('HOSTING_PLANS_LEVEL')
		&& Config::getInstance()->get('HOSTING_PLANS_LEVEL') === 'admin') {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($plan_id));
	} else {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`reseller_id` = ?
			AND
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($user_id, $plan_id));
	}

	if ($rs->RecordCount() == 0) {
		user_goto('index.php');
	} else {
		$price = $rs->fields['price'];
		$setup_fee = $rs->fields['setup_fee'];
		$total = $price + $setup_fee;


		if ($price == 0 || $price == '') {
			$price = tr('free of charge');
		} else {
			$price .= ' ' . $rs->fields['value'] . ' ' . $rs->fields['payment'];
		}

		if ($setup_fee == 0 || $setup_fee == '') {
			$setup_fee = tr('free of charge');
		} else {
			$setup_fee .= ' ' . $rs->fields['value'];
		}

		if ($total == 0) {
			$total = tr('free of charge');
		} else {
			$total .= ' ' . $rs->fields['value'];
		}

		$tpl->assign(
			array(
				'PRICE' => $price,
				'SETUP' => $setup_fee,
				'TOTAL' => $total,
				'TR_PACKAGE_NAME' => $rs->fields['name'],
			)
		);

		if($rs->fields['tos'] != '') {
			$tpl->assign(
				array(
					'TR_TOS_PROPS'	=> tr('Term of Service'),
					'TR_TOS_ACCEPT' => tr('I Accept The Term of Service'),
					'TOS'	=> $rs->fields['tos']
				)
			);

			$_SESSION['tos'] = true;
		} else {
			$tpl->assign(array('TOS_FIELD' => ''));
			$_SESSION['tos'] = false;
		}
	}
}

function gen_personal_data(&$tpl) {

	$first_name		= (isset($_SESSION['fname'])) ? $_SESSION['fname'] : '';
	$last_name		= (isset($_SESSION['lname'])) ? $_SESSION['lname'] : '';
	$company		= (isset($_SESSION['firm'])) ? $_SESSION['firm'] : '';
	$postal_code	= (isset($_SESSION['zip'])) ? $_SESSION['zip'] : '';
	$city			= (isset($_SESSION['city'])) ? $_SESSION['city'] : '';
	$state			= (isset($_SESSION['state'])) ? $_SESSION['state'] : '';
	$country		= (isset($_SESSION['country'])) ? $_SESSION['country'] : '';
	$street1		= (isset($_SESSION['street1'])) ? $_SESSION['street1'] : '';
	$street2		= (isset($_SESSION['street2'])) ? $_SESSION['street2'] : '';
	$phone			= (isset($_SESSION['phone'])) ? $_SESSION['phone'] : '';
	$fax			= (isset($_SESSION['fax'])) ? $_SESSION['fax'] : '';
	$email			= (isset($_SESSION['email'])) ? $_SESSION['email'] : '';
	$gender			= (isset($_SESSION['gender'])) ? get_gender_by_code($_SESSION['gender']) : get_gender_by_code('');

	$tpl->assign(
		array(
			'VL_USR_NAME'		=> $first_name,
			'VL_LAST_USRNAME'	=> $last_name,
			'VL_USR_FIRM'		=> $company,
			'VL_USR_POSTCODE'	=> $postal_code,
			'VL_USR_GENDER'		=> $gender,
			'VL_USRCITY'		=> $city,
			'VL_USRSTATE'		=> $state,
			'VL_COUNTRY'		=> $country,
			'VL_STREET1'		=> $street1,
			'VL_STREET2'		=> $street2,
			'VL_PHONE'			=> $phone,
			'VL_FAX'			=> $fax,
			'VL_EMAIL'			=> $email,
		)
	);
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

gen_purchase_haf($tpl, $sql, $user_id);
gen_chart($tpl, $sql, $user_id, $plan_id);
gen_personal_data($tpl);

gen_page_message($tpl);

$tpl->assign(
	array(
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
		'TR_IMGCAPCODE_DESCRIPTION' => tr('(To avoid abuse, we ask you to write the combination of letters on the above picture into the field "Security code")'),
		'TR_IMGCAPCODE' => '<img src="/imagecode.php" width="' . Config::getInstance()->get('LOSTPASSWORD_CAPTCHA_WIDTH') . '" height="' . Config::getInstance()->get('LOSTPASSWORD_CAPTCHA_HEIGHT') . '" border="0" alt="captcha image" />',
		'THEME_CHARSET' => tr('encoding'),
	)
);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}

unset_messages();
