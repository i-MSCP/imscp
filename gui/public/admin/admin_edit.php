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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/******************************************************************************
 * Script functions
 */

/**
 * Update user personal data.
 *
 * @param int $userId Customer unique identifier
 * @return void
 */
function admin_updateUserPersonalData($userId)
{
	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $userId));

	$fname = isset($_POST['fname']) ? clean_input($_POST['fname']) : '';
	$lname = isset($_POST['lname']) ? clean_input($_POST['lname']) : '';
	$firm = isset($_POST['firm']) ? clean_input($_POST['firm']) : '';
	$gender = isset($_POST['gender']) ? clean_input($_POST['gender']) : '';
	$zip = isset($_POST['zip']) ? clean_input($_POST['zip']) : '';
	$city = isset($_POST['city']) ? clean_input($_POST['city']) : '';
	$state = isset($_POST['state']) ? clean_input($_POST['state']) : '';
	$country = isset($_POST['country']) ? clean_input($_POST['country']) : '';
	$email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
	$phone = isset($_POST['phone']) ? clean_input($_POST['phone']) : '';
	$fax = isset($_POST['fax']) ? clean_input($_POST['fax']) : '';
	$street1 = isset($_POST['street1']) ? clean_input($_POST['street1']) : '';
	$street2 = isset($_POST['street2']) ? clean_input($_POST['street2']) : '';
	$userName = get_user_name($userId);

	if(empty($_POST['pass'])) {
		$query = "
			UPDATE
				`admin`
			SET
				`fname` = ?, `lname` = ?, `firm` = ?, `zip` = ?, `city` = ?, `state` = ?, `country` = ?, `email` = ?,
				`phone` = ?, `fax` = ?, `street1` = ?, `street2` = ?, `gender` = ?
			WHERE
				`admin_id` = ?
		";
		exec_query(
			$query,
			array(
				$fname, $lname, $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1, $street2,
				$gender, $userId
			)
		);
	} else {
		$query = "
			UPDATE
				`admin`
			SET
				`admin_pass` = ?, `fname` = ?, `lname` = ?, `firm` = ?, `zip` = ?, `city` = ?, `state` = ?,
				`country` = ?, `email` = ?, `phone` = ?, `fax` = ?, `street1` = ?, `street2` = ?, `gender` = ?
			WHERE
				`admin_id` = ?
		";
		exec_query(
			$query,
			array(
				crypt_user_pass($_POST['pass']), $fname, $lname, $firm, $zip, $city, $state, $country, $email,
				$phone, $fax, $street1, $street2, $gender, $userId
			)
		);

		$query = "DELETE FROM `login` WHERE `user_name` = ?";
		$stmt = exec_query($query, $userName);

		if ($stmt->rowCount()) {
			set_page_message(tr('User session successfully killed for password change.'), 'success');
		}
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $userId));

	if (isset($_POST['send_data']) && !empty($_POST['pass'])) {
		$query = 'SELECT `admin_type` FROM `admin` WHERE `admin_id` = ?';
		$stmt = exec_query($query, $userId);

		if ($stmt->fields['admin_type'] == 'admin') {
			$admin_type = tr('Administrator');
		} elseif ($stmt->fields['admin_type'] == 'reseller') {
			$admin_type = tr('Reseller');
		} else {
			$admin_type = tr('Domain account');
		}

		send_add_user_auto_msg(
			$userId, $userName, $_POST['pass'], $_POST['email'], $_POST['fname'], $_POST['lname'], tr($admin_type),
			$gender
		);

		set_page_message(tr('Login data successfully sent to %s.', $userName), 'success');
	}

	//$_SESSION['user_updated'] = 1;
}

/**
 * Check data.
 *
 * @access private
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function admin_isValidData()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!chk_email($_POST['email'])) {
		set_page_message(tr("Incorrect email length or syntax."), 'error');
	}

	if(!empty($_POST['pass']) && !empty($_POST['pass_rep'])) {
		if ($_POST['pass'] != $_POST['pass_rep']) {
			set_page_message(tr("Password doesn't matches."), 'error');
		} elseif(!chk_password($_POST['pass'])) {
			if ($cfg->PASSWD_STRONG) {
				set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
			} else {
				set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs.'), $cfg->PASSWD_CHARS), 'error');
			}
		}
	}

	if(Zend_Session::namespaceIsset('pageMessages')) {
		return false;
	}

	return true;
}

/******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['edit_id'])) {
	$userId = intval($_GET['edit_id']);
} else {
	set_page_message(tr('Wrong request.', 'error'));
	redirectTo('manage_users.php');
	exit; // Useless but avoid IDE warning about possible undefined variable
}

if(!empty($_POST) && !isset($_POST['genpass']) && admin_isValidData()) {
	admin_updateUserPersonalData($userId);
	set_page_message(tr('User personal data successfully updated.'), 'success');
	redirectTo('manage_users.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/admin_edit.tpl',
		'page_message' => 'layout',
		'hosting_plans' => 'page'));

// For admin, we redirect to it own personal change page.
if ($userId == $_SESSION['user_id']) {
	redirectTo('personal_change.php');
}
$query = "
	SELECT
		`admin_name`, `admin_type`, `fname`, `lname`, `firm`, `zip`, `city`, `state`, `country`, `phone`, `fax`,
		`street1`, `street2`, `email`, `gender`
	FROM
		`admin`
	WHERE
		`admin_id` = ?
";
$stmt = exec_query($query, $userId);

if (!$stmt->rowCount()) {
	redirectTo('manage_users.php');
}

generateNavigation($tpl);

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Edit customer'),
		'TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field.'),
		'TR_PASSWORD_NOT_MATCH' => tr("Passwords don't match!"),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'VAL_PASSWORD' => isset($_POST['genpass']) ? passgen() :  '',
		'TR_PASSWORD_REPEAT' => tr('Password confirmation'),
		'TR_EMAIL' => tr('Email'),
		'TR_ADDITIONAL_DATA' => tr('Additional data'),
		'TR_FIRST_NAME' => tr('First name'),
		'TR_LAST_NAME' => tr('Last name'),
		'TR_COMPANY' => tr('Company'),
		'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
		'TR_CITY' => tr('City'),
		'TR_STATE_PROVINCE' => tr('State/Province'),
		'TR_COUNTRY' => tr('Country'),
		'TR_STREET_1' => tr('Street 1'),
		'TR_STREET_2' => tr('Street 2'),
		'TR_PHONE' => tr('Phone'),
		'TR_FAX' => tr('Fax'),
		'TR_PHONE' => tr('Phone'),
		'TR_GENDER' => tr('Gender'),
		'TR_MALE' => tr('Male'),
		'TR_FEMALE' => tr('Female'),
		'TR_UNKNOWN' => tr('Unknown'),
		'TR_UPDATE' => tr('Update'),
		'TR_SEND_DATA' => tr('Send new login data'),
		'TR_PASSWORD_GENERATE' => tr('Generate password'),
		'FIRST_NAME' => isset($_POST['fname']) ? tohtml($_POST['fname']) : tohtml($stmt->fields['fname']),
		'LAST_NAME' => isset($_POST['lname']) ? tohtml($_POST['lname']) : tohtml($stmt->fields['lname']),
		'FIRM' => isset($_POST['firm']) ? tohtml($_POST['firm']) : tohtml($stmt->fields['firm']),
		'ZIP' => isset($_POST['zip']) ? tohtml($_POST['zip']) : tohtml($stmt->fields['zip']),
		'CITY' => isset($_POST['city']) ? tohtml($_POST['city']) : tohtml($stmt->fields['city']),
		'STATE_PROVINCE' => isset($_POST['state']) ? tohtml($_POST['state']) : tohtml($stmt->fields['state']),
		'COUNTRY' => isset($_POST['country']) ? tohtml($_POST['country']) : tohtml($stmt->fields['country']),
		'STREET_1' => isset($_POST['street1']) ? tohtml($_POST['street1']) : tohtml($stmt->fields['street1']),
		'STREET_2' => isset($_POST['street2']) ? tohtml($_POST['street2']) : tohtml($stmt->fields['street2']),
		'PHONE' => isset($_POST['phone']) ? tohtml($_POST['phone']) : tohtml($stmt->fields['phone']),
		'FAX' => isset($_POST['fax']) ? tohtml($_POST['fax']) : tohtml($stmt->fields['fax']),
		'USERNAME' => tohtml(decode_idna($stmt->fields['admin_name'])),
		'EMAIL' => isset($_POST['email']) ? tohtml($_POST['email']) : tohtml($stmt->fields['email']),
		'VL_MALE' => (isset($_POST['gender']) && $_POST['gender'] == 'M' || $stmt->fields['gender'] == 'M') ? $cfg->HTML_SELECTED : '',
		'VL_FEMALE' => (isset($_POST['gender']) && $_POST['gender'] == 'F' || $stmt->fields['gender'] == 'F') ? $cfg->HTML_SELECTED : '',
		'VL_UNKNOWN' => (isset($_POST['gender']) && $_POST['gender'] == 'U' || (!isset($_POST['gender']) && ($stmt->fields['gender'] == 'U' || empty($stmt->fields['gender'])))) ? $cfg->HTML_SELECTED : '',
		'SEND_DATA_CHECKED' => (isset($_POST['send_data'])) ? $cfg->HTML_CHECKED : '',
		'EDIT_ID' => $userId));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
