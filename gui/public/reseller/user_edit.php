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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2015 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Load user data
 *
 * @param int $adminId Customer unique identifier
 * @return void
 */
function reseller_loadUserData($adminId)
{
	global $adminName, $email, $customerId, $firstName, $lastName, $firm, $zip, $gender, $city, $state, $country,
		$street1, $street2, $phone, $fax;

	$stmt = exec_query(
		'
			SELECT
				admin_name, created_by, fname, lname, firm, zip, city, state, country, email, phone, fax, street1,
				street2, customer_id, gender
			FROM
				admin
			WHERE
				admin_id = ?
			AND
				created_by = ?
		',
		array($adminId, $_SESSION['user_id'])
	);

	if($stmt->rowCount()) {
		$data = $stmt->fetchRow();

		$adminName = $data['admin_name'];
		$email = $data['email'];
		$customerId = $data['customer_id'];
		$firstName = $data['fname'];
		$lastName = $data['lname'];
		$gender = $data['gender'];
		$firm = $data['firm'];
		$zip = $data['zip'];
		$city = $data['city'];
		$state = $data['state'];
		$country = $data['country'];
		$street1 = $data['street1'];
		$street2 = $data['street2'];
		$phone = $data['phone'];
		$fax = $data['fax'];
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function reseller_generatePage($tpl)
{
	global $adminName, $email, $customerId, $firstName, $lastName, $firm, $zip, $gender, $city, $state, $country,
		$street1, $street2, $phone, $fax;

	$cfg = iMSCP_Registry::get('config');

	$tpl->assign(
		array(
			'VL_USERNAME' => tohtml(decode_idna($adminName)),
			'VL_MAIL' => tohtml($email),
			'VL_USR_ID' => tohtml($customerId),
			'VL_USR_NAME' => tohtml($firstName),
			'VL_LAST_USRNAME' => tohtml($lastName),
			'VL_USR_FIRM' => tohtml($firm),
			'VL_USR_POSTCODE' => tohtml($zip),
			'VL_USRCITY' => tohtml($city),
			'VL_USRSTATE' => tohtml($state),
			'VL_COUNTRY' => tohtml($country),
			'VL_STREET1' => tohtml($street1),
			'VL_STREET2' => tohtml($street2),
			'VL_MALE' => ($gender == 'M') ? $cfg['HTML_SELECTED'] : '',
			'VL_FEMALE' => ($gender == 'F') ? $cfg['HTML_SELECTED'] : '',
			'VL_UNKNOWN' => ($gender == 'U') ? $cfg['HTML_SELECTED'] : '',
			'VL_PHONE' => tohtml($phone),
			'VL_FAX' => tohtml($fax)
		)
	);
}

/**
 * Function to update changes into db
 *
 * @param int $adminId Customer unique identifier
 * @return void
 */
function reseller_updateUserData($adminId)
{
	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $adminId));

	global $adminName, $email, $customerId, $firstName, $lastName, $firm, $zip, $gender, $city, $state, $country,
		$street1, $street2, $phone, $fax, $password, $passwordRepeat;

	$resellerId = intval($_SESSION['user_id']);

	if($password === '' && $passwordRepeat === '') { // Save without password
		exec_query(
			'
				UPDATE
					admin
				SET
					fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?, email = ?, phone = ?,
					fax = ?, street1 = ?, street2 = ?, gender = ?, customer_id = ?
				WHERE
					admin_id = ?
				AND
					created_by = ?
			',
			array(
				$firstName, $lastName, $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1,  $street2,
				$gender, $customerId, $adminId, $resellerId
			)
		);
	} else { // Change password
		if($password != $passwordRepeat) {
			set_page_message(tr("Passwords do not match."), 'error');
			redirectTo('user_edit.php?edit_id=' . $adminId);
		}

		if(!checkPasswordSyntax($password)) {
			redirectTo('user_edit.php?edit_id=' . $adminId);
		}

		$encryptedPassword = cryptPasswordWithSalt($password);

		exec_query(
			'
				UPDATE
					admin
				SET
					admin_pass = ?, fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?, email = ?,
					phone = ?, fax = ?, street1 = ?, street2 = ?, gender = ?, customer_id = ?
				WHERE
					admin_id = ?
				AND
					created_by = ?
			',
			array(
				$encryptedPassword, $firstName, $lastName, $firm, $zip, $city, $state, $country, $email, $phone, $fax,
				$street1, $street2, $gender, $customerId, $adminId, $resellerId
			)
		);

		$adminName = get_user_name($adminId);

		exec_query('DELETE FROM login WHERE user_name = ?', $adminName);
	}

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $adminId));

	set_page_message(tr('User data successfully updated'), 'success');
	write_log("{$_SESSION['user_logged']} updated data for $adminName.", E_USER_NOTICE);

	if(isset($_POST['send_data']) && $password !== '') {
		send_add_user_auto_msg($resellerId, $adminName, $password, $email, $firstName, $lastName, tr('Customer', true));
	}

	redirectTo('users.php');
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('reseller');

if(isset($_REQUEST['edit_id'])) {
	$adminId = intval($_GET['edit_id']);

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'reseller/user_edit.tpl',
			'page_message' => 'layout',
			'ip_entry' => 'page'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Reseller / Customers / Overview / Edit Customer'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_CORE_DATA' => tr('Core data'),
			'TR_USERNAME' => tr('Username'),
			'TR_PASSWORD' => tr('Password'),
			'TR_REP_PASSWORD' => tr('Repeat password'),
			'TR_USREMAIL' => tr('Email'),
			'TR_ADDITIONAL_DATA' => tr('Additional data'),
			'TR_CUSTOMER_ID' => tr('Customer ID'),
			'TR_FIRSTNAME' => tr('First name'),
			'TR_LASTNAME' => tr('Last name'),
			'TR_COMPANY' => tr('Company'),
			'TR_POST_CODE' => tr('Zip'),
			'TR_CITY' => tr('City'),
			'TR_STATE' => tr('State/Province'),
			'TR_COUNTRY' => tr('Country'),
			'TR_STREET1' => tr('Street 1'),
			'TR_STREET2' => tr('Street 2'),
			'TR_PHONE' => tr('Phone'),
			'TR_FAX' => tr('Fax'),
			'TR_GENDER' => tr('Gender'),
			'TR_MALE' => tr('Male'),
			'TR_FEMALE' => tr('Female'),
			'TR_UNKNOWN' => tr('Unknown'),
			'EDIT_ID' => $adminId,
			'TR_UPDATE' => tr('Update'),
			'TR_SEND_DATA' => tr('Send new login data')
		)
	);

	reseller_loadUserData($adminId);

	if(isset($_POST['uaction']) && $_POST['uaction'] === 'save_changes' && check_ruser_data(true)) {
		reseller_updateUserData($adminId); // Save data to db
	}

	generateNavigation($tpl);
	reseller_generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
