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

require '../include/ispcp-lib.php';

check_login(__FILE__);

if (isset($_GET['edit_id'])) {
	$edit_id = $_GET['edit_id'];
} else if (isset($_POST['edit_id'])) {
	$edit_id = $_POST['edit_id'];
} else {
	user_goto('users.php');
}

$tpl = new pTemplate();

$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/user_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('ip_entry', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_EDIT_USER_PAGE_TITLE'	=> tr('ispCP - Users/Edit'),
		'THEME_COLOR_PATH'			=> "../themes/$theme_color",
		'THEME_CHARSET'				=> tr('encoding'),
		'ISP_LOGO'					=> get_logo($_SESSION['user_id']),
	)
);

/*
 *
 * static page messages.
 *
 */
$tpl->assign(
	array(
		'TR_EDIT_USER'			=> tr('Edit user'),
		'TR_CORE_DATA'			=> tr('Core data'),
		'TR_USERNAME'			=> tr('Username'),
		'TR_PASSWORD'			=> tr('Password'),
		'TR_REP_PASSWORD'		=> tr('Repeat password'),
		'TR_DMN_IP'				=> tr('Domain IP'),
		'TR_USREMAIL'			=> tr('Email'),
		'TR_ADDITIONAL_DATA'	=> tr('Additional data'),
		'TR_CUSTOMER_ID'		=> tr('Customer ID'),
		'TR_FIRSTNAME'			=> tr('First name'),
		'TR_LASTNAME'			=> tr('Last name'),
		'TR_COMPANY'			=> tr('Company'),
		'TR_POST_CODE'			=> tr('Zip/Postal code'),
		'TR_CITY'				=> tr('City'),
		'TR_STATE'				=> tr('State/Province'),
		'TR_COUNTRY'			=> tr('Country'),
		'TR_STREET1'			=> tr('Street 1'),
		'TR_STREET2'			=> tr('Street 2'),
		'TR_MAIL'				=> tr('Email'),
		'TR_PHONE'				=> tr('Phone'),
		'TR_FAX'				=> tr('Fax'),
		'TR_GENDER'				=> tr('Gender'),
		'TR_MALE'				=> tr('Male'),
		'TR_FEMALE'				=> tr('Female'),
		'EDIT_ID'				=> $edit_id,
		'TR_BTN_ADD_USER'		=> tr('Submit changes')
	)
);

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_users_manage.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_USERS'		=> tr('Manage users'),
		'TR_USERS'				=> tr('Users'),
		'TR_NO'					=> tr('No.'),
		'TR_USERNAME'			=> tr('Username'),
		'TR_ACTION'				=> tr('Action'),
		'TR_BACK'				=> tr('Back'),
		'TR_TITLE_BACK'			=> tr('Return to previous menu'),
		'TR_TABLE_NAME'			=> tr('Users list'),
		'TR_SEND_DATA'			=> tr('Send new login data'),
		'TR_PASSWORD_GENERATE'	=> tr('Generate password')
	)
);

if (isset($_POST['genpass'])) {
	$tpl->assign('VAL_PASSWORD', passgen());
} else {
	$tpl->assign('VAL_PASSWORD', '');
}

if (isset($_POST['Submit']) && isset($_POST['uaction']) && ('save_changes' === $_POST['uaction'])) {
	// Process data

	if (isset($_SESSION['edit_ID'])) {
		$hpid = $_SESSION['edit_ID'];
	} else {
		$_SESSION['edit'] = '_no_';
		header('Location: users.php');
		die();
	}

	if (isset($_SESSION['user_name'])) {
		$dmn_user_name = $_SESSION['user_name'];
	} else {
		$_SESSION['edit'] = '_no_';
		header('Location: users.php');
		die();
	}

	if (check_ruser_data($tpl, '_yes_')) { // Save data to db
		update_data_in_db($hpid);
	}

} else {
	// Get user id that comes for edit
	$hpid = $edit_id;
	load_user_data_page($hpid);
	$_SESSION['edit_ID'] = $hpid;

}
gen_edituser_page($tpl);
gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();
if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

//unset_messages();

/*
 * Begin function block
 */

/**
 * Load data from sql
 */
function load_user_data_page($user_id) {
	$sql = Database::getInstance();
	global $dmn_user_name;
	global $user_email, $customer_id, $first_name;
	global $last_name, $firm, $zip, $gender;
	global $city, $state, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax;

	$reseller_id = $_SESSION['user_id'];

	$query = "
		SELECT
			`admin_name`, `created_by`, `fname`, `lname`, `firm`, `zip`,
			`city`, `state`, `country`, `email`, `phone`, `fax`, `street1`,
			`street2`, `customer_id`, `gender`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		  AND
			`created_by` = ?
	";

	$res  = exec_query($sql, $query, array($user_id, $reseller_id));
	$data = $res->FetchRow();

	if ($res->RecordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));
		header('Location: users.php');
		die();
	} else {
	// Get data from sql
		$_SESSION['user_name'] = $data['admin_name'];

		$dmn_user_name	= $data['admin_name'];
		$user_email		= $data['email'];
		$customer_id	= $data['customer_id'];
		$first_name		= $data['fname'];
		$last_name		= $data['lname'];
		$gender			= $data['gender'];
		$firm			= $data['firm'];
		$zip			= $data['zip'];
		$city			= $data['city'];
		$state			= $data['state'];
		$country		= $data['country'];
		$street_one		= $data['street1'];
		$street_two		= $data['street2'];
		$mail			= $data['email'];
		$phone			= $data['phone'];
		$fax			= $data['fax'];
	}

} // End of gen_load_ehp_page()


/**
 * Show user data
 */
function gen_edituser_page(&$tpl) {
	global $dmn_user_name;
	global $user_email, $customer_id, $first_name;
	global $last_name, $firm, $zip, $gender;
	global $city, $state, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax;

	if ($customer_id == NULL) {
		$customer_id = '';
	}

	// Fill in the fields
	$tpl->assign(array(
		'VL_USERNAME'		=> decode_idna($dmn_user_name),
		'VL_MAIL'			=> empty($user_email) ? '' : $user_email,
		'VL_USR_ID'			=> empty($customer_id) ? '' : $customer_id,
		'VL_USR_NAME'		=> empty($first_name) ? '' : $first_name,
		'VL_LAST_USRNAME'	=> empty($last_name) ? '' : $last_name,
		'VL_USR_FIRM'		=> empty($firm) ? '' : $firm,
		'VL_USR_POSTCODE'	=> empty($zip) ? '' : $zip,
		'VL_USRCITY'		=> empty($city) ? '' : $city,
		'VL_USRSTATE'		=> empty($state)?'':$state,
		'VL_COUNTRY'		=> empty($country) ? '' : $country,
		'VL_STREET1'		=> empty($street_one) ? '' : $street_one,
		'VL_STREET2'		=> empty($street_two) ? '' : $street_two,
		'VL_MALE'			=> ($gender == 'M') ? 'checked="checked"' : '',
		'VL_FEMALE'			=> ($gender == 'F') ? 'checked="checked"' : '',
		'VL_PHONE'			=> empty($phone) ? '' : $phone,
		'VL_FAX'			=> empty($fax) ? '' : $fax
	));

	generate_ip_list($tpl, $_SESSION['user_id']);

} // End of gen_edituser_page()


/**
 * Function to update changes into db
 */
function update_data_in_db($hpid) {
	$sql = Database::getInstance();
	global $dmn_user_name;
	global $user_email, $customer_id, $first_name;
	global $last_name, $firm, $zip, $gender;
	global $city, $state, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax, $inpass, $domain_ip;
	global $admin_login;

	$reseller_id = $_SESSION['user_id'];

	$first_name	= clean_input($first_name, true);
	$last_name	= clean_input($last_name, true);
	$firm		= clean_input($firm, true);
	$gender		= clean_input($gender, true);
	$zip		= clean_input($zip, true);
	$city		= clean_input($city, true);
	$state		= clean_input($state, true);
	$country	= clean_input($country, true);
	$phone		= clean_input($phone, true);
	$fax		= clean_input($fax, true);
	$street_one	= clean_input($street_one, true);
	$street_two	= clean_input($street_two, true);

	if (empty($inpass)) {
		// Save without password
		$query = "
			UPDATE
				`admin`
			SET
				`fname` = ?,
				`lname` = ?,
				`firm` = ?,
				`zip` = ?,
				`city` = ?,
				`state` = ?,
				`country` = ?,
				`email` = ?,
				`phone` = ?,
				`fax` = ?,
				`street1` = ?,
				`street2` = ?,
				`gender` = ?,
				`customer_id` = ?
			WHERE
				`admin_id` = ?
			AND
				`created_by` = ?
		";
		exec_query($sql, $query, array(
			$first_name,
			$last_name,
			$firm,
			$zip,
			$city,
			$state,
			$country,
			$mail,
			$phone,
			$fax,
			$street_one,
			$street_two,
			$gender,
			$customer_id,
			$hpid,
			$reseller_id)
		);
	} else {
		// Change password
		if (!chk_password($_POST['userpassword'])) {

			if (Config::get('PASSWD_STRONG')) {
				set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS')));
			} else {
				set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS')));
			}

			header("Location: user_edit.php?edit_id=$hpid");
			die();
		}
		
		if ($_POST['userpassword'] != $_POST['userpassword_repeat']) {

			set_page_message(tr("Entered passwords do not match!"));

			header("Location: user_edit.php?edit_id=$hpid");
			die();
		}
		$pure_user_pass = $inpass;

		$inpass = crypt_user_pass($inpass);

		$query = "
			UPDATE
				`admin`
			SET
				`admin_pass` = ?,
				`fname` = ?,
				`lname` = ?,
				`firm` = ?,
				`zip` = ?,
				`city` = ?,
				`state` = ?,
				`country` = ?,
				`email` = ?,
				`phone` = ?,
				`fax` = ?,
				`street1` = ?,
				`street2` = ?,
				`gender` = ?,
				`customer_id` = ?
			WHERE
				`admin_id` = ?
			AND
				`created_by` = ?
		";
		exec_query($sql, $query, array(
			$inpass,
			$first_name,
			$last_name,
			$firm,
			$zip,
			$city,
			$state,
			$country,
			$mail,
			$phone,
			$fax,
			$street_one,
			$street_two,
			$gender,
			$customer_id,
			$hpid,
			$reseller_id)
		);

		// Kill any existing session of the edited user
		$admin_name = get_user_name($hpid);
		$query = "
			DELETE FROM
				`login`
			WHERE
				`user_name` = ?
		";

		$rs = exec_query($sql, $query, array($admin_name));
			if ($rs->RecordCount() != 0) {
				set_page_message(tr('User session was killed!'));
				write_log($_SESSION['user_logged'] . " killed ".$admin_name."'s session because of password change");
		}
	}

	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login changes data/password for $dmn_user_name!");

	if (isset($_POST['send_data']) && !empty($inpass)) {
		send_add_user_auto_msg(
			$reseller_id,
			$dmn_user_name,
			$pure_user_pass,
			$user_email,
			$first_name,
			$last_name,
			tr('Domain account')
		);
	}

	unset($_SESSION['edit_ID']);
	unset($_SESSION['user_name']);

	$_SESSION['edit'] = "_yes_";
	header("Location: users.php");
	die();
} // End of update_data_in_db()

?>