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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/personal_change.tpl');
$tpl->define_dynamic('page_message', 'layout');

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Profile / Personal Data'),
		'ISP_LOGO' => layout_getUserLogo()));

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {
	update_user_personal_data($_SESSION['user_id']);
}

gen_user_personal_data($tpl, $_SESSION['user_id']);

/**
 * @param iMSCP_pTemplate $tpl
 * @param $user_id
 * @return void
 */
function gen_user_personal_data($tpl, $user_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`fname`, `lname`, `gender`, `firm`, `zip`, `city`, `state`, `country`,
			`street1`, `street2`, `email`, `phone`, `fax`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($query, $user_id);
	$tpl->assign(
		array(
			 'FIRST_NAME' => empty($rs->fields['fname']) ? '' : tohtml($rs->fields['fname']),
			 'LAST_NAME' => empty($rs->fields['lname']) ? '' : tohtml($rs->fields['lname']),
			 'FIRM' => empty($rs->fields['firm']) ? '' : tohtml($rs->fields['firm']),
			 'ZIP' => empty($rs->fields['zip']) ? '' : tohtml($rs->fields['zip']),
			 'CITY' => empty($rs->fields['city']) ? '' : tohtml($rs->fields['city']),
			 'STATE' => empty($rs->fields['state']) ? '' : tohtml($rs->fields['state']),
			 'COUNTRY' => empty($rs->fields['country']) ? '' : tohtml($rs->fields['country']),
			 'STREET_1' => empty($rs->fields['street1']) ? '' : tohtml($rs->fields['street1']),
			 'STREET_2' => empty($rs->fields['street2']) ? '' : tohtml($rs->fields['street2']),
			 'EMAIL' => empty($rs->fields['email']) ? '' : tohtml($rs->fields['email']),
			 'PHONE' => empty($rs->fields['phone']) ? '' : tohtml($rs->fields['phone']),
			 'FAX' => empty($rs->fields['fax']) ? '' : tohtml($rs->fields['fax']),
			 'VL_MALE' => (($rs->fields['gender'] == 'M') ? $cfg->HTML_SELECTED : ''),
			 'VL_FEMALE' => (($rs->fields['gender'] == 'F') ? $cfg->HTML_SELECTED : ''),
			 'VL_UNKNOWN' => ((($rs->fields['gender'] == 'U') || (empty($rs->fields['gender']))) ? $cfg->HTML_SELECTED : '')));
}

/**
 * @param $user_id
 * @return void
 */
function update_user_personal_data($user_id) {

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditUser, array('userId' => $user_id));

	$fname = clean_input($_POST['fname']);
	$lname = clean_input($_POST['lname']);
	$gender = $_POST['gender'];
	$firm = clean_input($_POST['firm']);
	$zip = clean_input($_POST['zip']);
	$city = clean_input($_POST['city']);
	$state = clean_input($_POST['state']);
	$country = clean_input($_POST['country']);
	$street1 = clean_input($_POST['street1']);
	$street2 = clean_input($_POST['street2']);
	$email = clean_input($_POST['email']);
	$phone = clean_input($_POST['phone']);
	$fax = clean_input($_POST['fax']);

	$query = "
		UPDATE
			`admin`
		SET
			`fname` = ?, `lname` = ?, `firm` = ?, `zip` = ?, `city` = ?, `state` = ?,
			`country` = ?, `street1` = ?, `street2` = ?, `email` = ?, `phone` = ?,
			`fax` = ?, `gender` = ?
		WHERE
			`admin_id` = ?
	";
	exec_query($query, array($fname, $lname, $firm, $zip, $city, $state, $country,
                            $street1, $street2, $email, $phone, $fax, $gender,
                            $user_id));

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditUser, array('userId' => $user_id));

	write_log($_SESSION['user_logged'] . ": update personal data", E_USER_NOTICE);
	set_page_message(tr('Personal data successfully updated.'), 'success');
}

generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_TITLE_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
		 'TR_PERSONAL_DATA' => tr('Personal data'),
		 'TR_FIRST_NAME' => tr('First name'),
		 'TR_LAST_NAME' => tr('Last name'),
		 'TR_COMPANY' => tr('Company'),
		 'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
		 'TR_CITY' => tr('City'),
		 'TR_STATE' => tr('State/Province'),
		 'TR_COUNTRY' => tr('Country'),
		 'TR_STREET_1' => tr('Street 1'),
		 'TR_STREET_2' => tr('Street 2'),
		 'TR_EMAIL' => tr('Email'),
		 'TR_PHONE' => tr('Phone'),
		 'TR_FAX' => tr('Fax'),
		 'TR_GENDER' => tr('Gender'),
		 'TR_MALE' => tr('Male'),
		 'TR_FEMALE' => tr('Female'),
		 'TR_UNKNOWN' => tr('Unknown'),
		 'TR_UPDATE_DATA' => tr('Change')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
