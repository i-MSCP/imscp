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

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/personal_change.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE' => tr('ispCP - Client/Change Personal Data'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {
	update_user_personal_data($sql, $_SESSION['user_id']);
}

gen_user_personal_data($tpl, $sql, $_SESSION['user_id']);

function gen_user_personal_data(&$tpl, &$sql, $user_id) {
	$query = "
		SELECT
			`fname`,
			`lname`,
			`gender`,
			`firm`,
			`zip`,
			`city`,
			`state`,
			`country`,
			`street1`,
			`street2`,
			`email`,
			`phone`,
			`fax`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($user_id));
	$tpl->assign(
		array(
			'FIRST_NAME'	=> empty($rs->fields['fname']) ? '' : $rs->fields['fname'],
			'LAST_NAME'		=> empty($rs->fields['lname']) ? '' : $rs->fields['lname'],
			'FIRM'			=> empty($rs->fields['firm']) ? '' : $rs->fields['firm'],
			'ZIP'			=> empty($rs->fields['zip']) ? '' : $rs->fields['zip'],
			'CITY'			=> empty($rs->fields['city']) ? '' : $rs->fields['city'],
			'STATE'			=> empty($rs->fields['state']) ? '' : $rs->fields['state'],
			'COUNTRY'		=> empty($rs->fields['country']) ? '' : $rs->fields['country'],
			'STREET_1'		=> empty($rs->fields['street1']) ? '' : $rs->fields['street1'],
			'STREET_2'		=> empty($rs->fields['street2']) ? '' : $rs->fields['street2'],
			'EMAIL'			=> empty($rs->fields['email']) ? '' : $rs->fields['email'],
			'PHONE'			=> empty($rs->fields['phone']) ? '' : $rs->fields['phone'],
			'FAX'			=> empty($rs->fields['fax']) ? '' : $rs->fields['fax'],
			'VL_MALE'		=> (($rs->fields['gender'] == 'M') ? 'selected="selected"' : ''),
			'VL_FEMALE'		=> (($rs->fields['gender'] == 'F') ? 'selected="selected"' : ''),
			'VL_UNKNOWN'	=> ((($rs->fields['gender'] == 'U') || (empty($rs->fields['gender']))) ? 'selected="selected"' : '')
		)
	);
}

function update_user_personal_data(&$sql, $user_id) {
	$fname = clean_input($_POST['fname'], true);
	$lname = clean_input($_POST['lname'], true);
	$gender = $_POST['gender'];
	$firm = clean_input($_POST['firm'], true);
	$zip = clean_input($_POST['zip'], true);
	$city = clean_input($_POST['city'], true);
	$state = clean_input($_POST['state'], true);
	$country = clean_input($_POST['country'], true);
	$street1 = clean_input($_POST['street1'], true);
	$street2 = clean_input($_POST['street2'], true);
	$email = clean_input($_POST['email'], true);
	$phone = clean_input($_POST['phone'], true);
	$fax = clean_input($_POST['fax'], true);

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
			`street1` = ?,
			`street2` = ?,
			`email` = ?,
			`phone` = ?,
			`fax` = ?,
			`gender` = ?
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($fname, $lname, $firm, $zip, $city, $state, $country, $street1, $street2, $email, $phone, $fax, $gender, $user_id));

	write_log($_SESSION['user_logged'] . ": update personal data");
	set_page_message(tr('Personal data updated successfully!'));
}

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_general_information.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_CHANGE_PERSONAL_DATA'	=> tr('Change personal data'),
		'TR_PERSONAL_DATA'			=> tr('Personal data'),
		'TR_FIRST_NAME'				=> tr('First name'),
		'TR_LAST_NAME'				=> tr('Last name'),
		'TR_COMPANY'				=> tr('Company'),
		'TR_ZIP_POSTAL_CODE'		=> tr('Zip/Postal code'),
		'TR_CITY'					=> tr('City'),
		'TR_STATE'					=> tr('State/Province'),
		'TR_COUNTRY'				=> tr('Country'),
		'TR_STREET_1'				=> tr('Street 1'),
		'TR_STREET_2'				=> tr('Street 2'),
		'TR_EMAIL'					=> tr('Email'),
		'TR_PHONE'					=> tr('Phone'),
		'TR_FAX'					=> tr('Fax'),
		'TR_GENDER'					=> tr('Gender'),
		'TR_MALE'					=> tr('Male'),
		'TR_FEMALE'					=> tr('Female'),
		'TR_UNKNOWN'				=> tr('Unknown'),
		'TR_UPDATE_DATA'			=> tr('Update data')
	)
);

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
