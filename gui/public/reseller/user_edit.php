<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login(__FILE__);

if (isset($_GET['edit_id'])) {
	$edit_id = $_GET['edit_id'];
} else if (isset($_POST['edit_id'])) {
	$edit_id = $_POST['edit_id'];
} else {
	redirectTo('users.php?psi=last');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_edit.tpl',
		'page_message' => 'layout',
		'ip_entry' => 'page'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Users/Edit'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),));

$tpl->assign(
	array(
		 'TR_EDIT_USER' => tr('Edit user'),
		 'TR_CORE_DATA' => tr('Core data'),
		 'TR_USERNAME' => tr('Username'),
		 'TR_PASSWORD' => tr('Password'),
		 'TR_REP_PASSWORD' => tr('Repeat password'),
		 'TR_DMN_IP' => tr('Domain IP'),
		 'TR_USREMAIL' => tr('Email'),
		 'TR_ADDITIONAL_DATA' => tr('Additional data'),
		 'TR_CUSTOMER_ID' => tr('Customer ID'),
		 'TR_FIRSTNAME' => tr('First name'),
		 'TR_LASTNAME' => tr('Last name'),
		 'TR_COMPANY' => tr('Company'),
		 'TR_POST_CODE' => tr('Zip/Postal code'),
		 'TR_CITY' => tr('City'),
		 'TR_STATE' => tr('State/Province'),
		 'TR_COUNTRY' => tr('Country'),
		 'TR_STREET1' => tr('Street 1'),
		 'TR_STREET2' => tr('Street 2'),
		 'TR_MAIL' => tr('Email'),
		 'TR_PHONE' => tr('Phone'),
		 'TR_FAX' => tr('Fax'),
		 'TR_GENDER' => tr('Gender'),
		 'TR_MALE' => tr('Male'),
		 'TR_FEMALE' => tr('Female'),
		 'TR_UNKNOWN' => tr('Unknown'),
		 'EDIT_ID' => $edit_id,
		 'TR_BTN_ADD_USER' => tr('Submit changes')));

generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_MANAGE_USERS' => tr('Manage users'),
		 'TR_USERS' => tr('Users'),
		 'TR_NO' => tr('No.'),
		 'TR_USERNAME' => tr('Username'),
		 'TR_ACTION' => tr('Action'),
		 'TR_BACK' => tr('Back'),
		 'TR_TITLE_BACK' => tr('Return to previous menu'),
		 'TR_TABLE_NAME' => tr('Users list'),
		 'TR_SEND_DATA' => tr('Send new login data'),
		 'TR_PASSWORD_GENERATE' => tr('Generate password')));

if (isset($_POST['genpass'])) {
	$tpl->assign('VAL_PASSWORD', passgen());
} else {
	$tpl->assign('VAL_PASSWORD', '');
}

if (isset($_POST['Submit']) && isset($_POST['uaction']) && ('save_changes' === $_POST['uaction'])
) {
	// Process data

	if (isset($_SESSION['edit_ID'])) {
		$hpid = $_SESSION['edit_ID'];
	} else {
		$_SESSION['edit'] = '_no_';
		redirectTo('users.php?psi=last');
	}

	if (isset($_SESSION['user_name'])) {
		$dmn_user_name = $_SESSION['user_name'];
	} else {
		$_SESSION['edit'] = '_no_';
		redirectTo('users.php?psi=last');
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

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

/**
 * Load data from sql
 */
function load_user_data_page($user_id)
{
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

	$res = exec_query($query, array($user_id, $reseller_id));
	$data = $res->fetchRow();

	if ($res->recordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface'), 'error');
		redirectTo('users.php?psi=last');
	} else {
		// Get data from sql
		$_SESSION['user_name'] = $data['admin_name'];

		$dmn_user_name = $data['admin_name'];
		$user_email = $data['email'];
		$customer_id = $data['customer_id'];
		$first_name = $data['fname'];
		$last_name = $data['lname'];
		$gender = $data['gender'];
		$firm = $data['firm'];
		$zip = $data['zip'];
		$city = $data['city'];
		$state = $data['state'];
		$country = $data['country'];
		$street_one = $data['street1'];
		$street_two = $data['street2'];
		$mail = $data['email'];
		$phone = $data['phone'];
		$fax = $data['fax'];
	}

}

/**
 * Show user data
 */
function gen_edituser_page(&$tpl)
{
	global $dmn_user_name, $user_email, $customer_id, $first_name, $last_name,
$firm, $zip, $gender, $city, $state, $country, $street_one, $street_two,
$phone, $fax;

	$cfg = iMSCP_Registry::get('config');

	if ($customer_id == NULL) {
		$customer_id = '';
	}

	// Fill in the fields
	$tpl->assign(
		array(
			 'VL_USERNAME' => tohtml(decode_idna($dmn_user_name)),
			 'VL_MAIL' => empty($user_email) ? '' : tohtml($user_email),
			 'VL_USR_ID' => empty($customer_id) ? '' : tohtml($customer_id),
			 'VL_USR_NAME' => empty($first_name) ? '' : tohtml($first_name),
			 'VL_LAST_USRNAME' => empty($last_name) ? '' : tohtml($last_name),
			 'VL_USR_FIRM' => empty($firm) ? '' : tohtml($firm),
			 'VL_USR_POSTCODE' => empty($zip) ? '' : tohtml($zip),
			 'VL_USRCITY' => empty($city) ? '' : tohtml($city),
			 'VL_USRSTATE' => empty($state) ? '' : tohtml($state),
			 'VL_COUNTRY' => empty($country) ? '' : tohtml($country),
			 'VL_STREET1' => empty($street_one) ? '' : tohtml($street_one),
			 'VL_STREET2' => empty($street_two) ? '' : tohtml($street_two),
			 'VL_MALE' => ($gender == 'M') ? $cfg->HTML_SELECTED : '',
			 'VL_FEMALE' => ($gender == 'F') ? $cfg->HTML_SELECTED : '',
			 'VL_UNKNOWN' => ($gender == 'U') ? $cfg->HTML_SELECTED : '',
			 'VL_PHONE' => empty($phone) ? '' : tohtml($phone),
			 'VL_FAX' => empty($fax) ? '' : tohtml($fax)
		)
	);

	generate_ip_list($tpl, $_SESSION['user_id']);

} // End of gen_edituser_page()


/**
 * Function to update changes into db
 */
function update_data_in_db($hpid)
{
	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $hpid));

	global $dmn_user_name, $user_email, $customer_id, $first_name, $last_name,
$firm, $zip, $gender, $city, $state, $country, $street_one, $street_two,
$mail, $phone, $fax, $inpass, $admin_login;

	$cfg = iMSCP_Registry::get('config');

	$reseller_id = $_SESSION['user_id'];

	$first_name = clean_input($first_name);
	$last_name = clean_input($last_name);
	$firm = clean_input($firm);
	$gender = clean_input($gender);
	$zip = clean_input($zip);
	$city = clean_input($city);
	$state = clean_input($state);
	$country = clean_input($country);
	$phone = clean_input($phone);
	$fax = clean_input($fax);
	$street_one = clean_input($street_one);
	$street_two = clean_input($street_two);

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
		exec_query($query, array(
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
			if (isset($cfg->PASSWD_STRONG)) {
				set_page_message(
					sprintf(
						tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS
					), 'error'
				);
			} else {
				set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS), 'error');
			}

			redirectTo('user_edit.php?edit_id=' . $hpid);
		}

		if ($_POST['userpassword'] != $_POST['userpassword_repeat']) {

			set_page_message(tr("Passwords doesn't not matches."), 'error');

			redirectTo('user_edit.php?edit_id=' . $hpid);
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
		exec_query($query, array(
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

		$query = "DELETE FROM `login` WHERE `user_name` = ? ";
		$rs = exec_query($query, $admin_name);

		if ($rs->recordCount() != 0) {
			set_page_message(tr('User session was successfully killed for password change'), 'success');
			write_log($_SESSION['user_logged'] . " killed " . $admin_name . "'s session because of password change", E_USER_NOTICE);
		}
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $hpid));

	$admin_login = $_SESSION['user_logged'];
	write_log("$admin_login changes data/password for $dmn_user_name!", E_USER_NOTICE);

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
	redirectTo('users.php?psi=last');
} // End of update_data_in_db()
