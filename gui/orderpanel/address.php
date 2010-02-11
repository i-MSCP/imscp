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
$tpl->define_dynamic('page', Config::get('PURCHASE_TEMPLATE_PATH') . '/address.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('purchase_header', 'page');
$tpl->define_dynamic('purchase_footer', 'page');

/*
 * functions start
 */

function gen_address(&$tpl, &$sql, $user_id, $plan_id) {
	if (isset($_POST['fname'])) {
		$first_name = clean_input($_POST['fname'], true);
	} else if (isset($_SESSION['fname'])) {
		$first_name = $_SESSION['fname'];
	} else {
		$first_name = '';
	}

	if (isset($_POST['lname'])) {
		$last_name = clean_input($_POST['lname'], true);
	} else if (isset($_SESSION['lname'])) {
		$last_name = $_SESSION['lname'];
	} else {
		$last_name = '';
	}

	if (isset($_POST['email'])) {
		$email = clean_input($_POST['email'], true);
	} else if (isset($_SESSION['email'])) {
		$email = $_SESSION['email'];
	} else {
		$email = '';
	}

	if (isset($_POST['gender']) && (in_array($_POST['gender'], array('M', 'F', 'U')))) {
		$gender = $_POST['gender'];
	} else if (isset($_SESSION['gender'])) {
		$gender = $_SESSION['gender'];
	} else {
		$gender = 'U';
	}

	if (isset($_POST['firm'])) {
		$company = clean_input($_POST['firm'], true);
	} else if (isset($_SESSION['firm'])) {
		$company = $_SESSION['firm'];
	} else {
		$company = '';
	}

	if (isset($_POST['zip'])) {
		$postal_code = clean_input($_POST['zip'], true);
	} else if (isset($_SESSION['zip'])) {
		$postal_code = $_SESSION['zip'];
	} else {
		$postal_code = '';
	}

	if (isset($_POST['city'])) {
		$city = clean_input($_POST['city'], true);
	} else if (isset($_SESSION['city'])) {
		$city = $_SESSION['city'];
	} else {
		$city = '';
	}

	if (isset($_POST['state'])) {
		$state = clean_input($_POST['state'], true);
	} else if (isset($_SESSION['state'])) {
		$state = $_SESSION['state'];
	} else {
		$state = '';
	}

	if (isset($_POST['country'])) {
		$country = clean_input($_POST['country'], true);
	} else if (isset($_SESSION['country'])) {
		$country = $_SESSION['country'];
	} else {
		$country = '';
	}

	if (isset($_POST['street1'])) {
		$street1 = clean_input($_POST['street1'], true);
	} else if (isset($_SESSION['street1'])) {
		$street1 = $_SESSION['street1'];
	} else {
		$street1 = '';
	}

	if (isset($_POST['street2'])) {
		$street2 = clean_input($_POST['street2'], true);
	} else if (isset($_SESSION['street2'])) {
		$street2 = $_SESSION['street2'];
	} else {
		$street2 = '';
	}

	if (isset($_POST['phone'])) {
		$phone = clean_input($_POST['phone'], true);
	} else if (isset($_SESSION['phone'])) {
		$phone = $_SESSION['phone'];
	} else {
		$phone = '';
	}

	if (isset($_POST['fax'])) {
		$fax = clean_input($_POST['fax'], true);
	} else if (isset($_SESSION['fax'])) {
		$fax = $_SESSION['fax'];
	} else {
		$fax = '';
	}

	$tpl->assign(
		array(
			'VL_USR_NAME'		=> $first_name,
			'VL_LAST_USRNAME'	=> $last_name,
			'VL_EMAIL'			=> $email,
			'VL_USR_FIRM'		=> $company,
			'VL_USR_POSTCODE'	=> $postal_code,
			'VL_USRCITY'		=> $city,
			'VL_USRSTATE'		=> $state,
			'VL_COUNTRY'		=> $country,
			'VL_STREET1'		=> $street1,
			'VL_STREET2'		=> $street2,
			'VL_PHONE'			=> $phone,
			'VL_FAX'			=> $fax,
			'VL_MALE'			=> (($gender === 'M') ? 'selected="selected"' : ''),
			'VL_FEMALE'			=> (($gender === 'F') ? 'selected="selected"' : ''),
			'VL_UNKNOWN'		=> (($gender == 'U') ? 'selected="selected"' : '')
		)
	);
}

function check_address_data(&$tpl) {

	unset($_GET['edit']); // @todo why unset GET['edit']?
	if ((isset($_POST['fname']) && $_POST['fname'] != '')
		&& (isset($_POST['email']) && $_POST['email'] != '')
		&& chk_email($_POST['email'])
		&& (isset($_POST['lname']) && $_POST['lname'] != '')
		&& (isset($_POST['zip']) && $_POST['zip'] != '')
		&& (isset($_POST['city']) && $_POST['city'] != '')
		&& (isset($_POST['state']) && $_POST['state'] != '')
		&& (isset($_POST['country']) && $_POST['country'] != '')
		&& (isset($_POST['street1']) && $_POST['street1'] != '')
		&& (isset($_POST['phone']) && $_POST['phone'] != '')
		) {
		$_SESSION['fname']		= clean_input($_POST['fname'], true);
		$_SESSION['lname']		= clean_input($_POST['lname'], true);
		$_SESSION['email']		= clean_input($_POST['email'], true);
		$_SESSION['zip']		= clean_input($_POST['zip'], true);
		$_SESSION['city']		= clean_input($_POST['city'], true);
		$_SESSION['state']		= clean_input($_POST['state'], true);
		$_SESSION['country']	= clean_input($_POST['country'], true);
		$_SESSION['street1']	= clean_input($_POST['street1'], true);
		$_SESSION['phone']		= clean_input($_POST['phone'], true);

		if (isset($_POST['firm']) && $_POST['firm'] != '') {
			$_SESSION['firm'] = clean_input($_POST['firm'], true);
		}

		if (isset($_POST['gender'])
			&& get_gender_by_code($_POST['gender'], true) !== null) {
			$_SESSION['gender'] = $_POST['gender'];
		} else {
			$_SESSION['gender'] = '';
		}

		if (isset($_POST['street2']) && $_POST['street2'] != '') {
			$_SESSION['street2'] = clean_input($_POST['street2'], true);
		}

		if (isset($_POST['fax']) && $_POST['fax'] != '') {
			$_SESSION['fax'] = clean_input($_POST['fax'], true);
		}

		user_goto('chart.php');
	} else {
		set_page_message(tr('Please fill out all needed fields!'));
		$_GET['edit'] = "yes";
	}
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

if (isset($_POST['uaction']) && $_POST['uaction'] == 'address')
	check_address_data($tpl);

if ((isset($_SESSION['fname']) && $_SESSION['fname'] != '')
	&& (isset($_SESSION['email']) && $_SESSION['email'] != '')
	&& (isset($_SESSION['lname']) && $_SESSION['lname'] != '')
	&& (isset($_SESSION['zip']) && $_SESSION['zip'] != '')
	&& (isset($_SESSION['city']) && $_SESSION['city'] != '')
	&& (isset($_SESSION['state']) && $_SESSION['state'] != '')
	&& (isset($_SESSION['country']) && $_SESSION['country'] != '')
	&& (isset($_SESSION['street1']) && $_SESSION['street1'] != '')
	&& (isset($_SESSION['phone']) && $_SESSION['phone'] != '')
	&& !isset($_GET['edit'])
	) {
	user_goto('chart.php');
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_address($tpl, $sql, $user_id, $plan_id);

gen_page_message($tpl);

$tpl->assign(
	array(
		'TR_ADDRESS'	=> tr('Enter Address'),
		'TR_FIRSTNAME'	=> tr('First name'),
		'TR_LASTNAME'	=> tr('Last name'),
		'TR_COMPANY'	=> tr('Company'),
		'TR_POST_CODE'	=> tr('Zip/Postal code'),
		'TR_CITY'		=> tr('City'),
		'TR_STATE'		=> tr('State/Province'),
		'TR_COUNTRY'	=> tr('Country'),
		'TR_STREET1'	=> tr('Street 1'),
		'TR_STREET2'	=> tr('Street 2'),
		'TR_EMAIL'		=> tr('Email'),
		'TR_PHONE'		=> tr('Phone'),
		'TR_GENDER'		=> tr('Gender'),
		'TR_MALE'		=> tr('Male'),
		'TR_FEMALE'		=> tr('Female'),
		'TR_UNKNOWN'	=> tr('Unknown'),
		'TR_FAX'		=> tr('Fax'),
		'TR_CONTINUE'	=> tr('Continue'),
		'NEED_FILLED'	=> tr('* denotes mandatory field.'),
		'THEME_CHARSET'	=> tr('encoding')
	)
);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
