<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
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

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/personal_change.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_CHANGE_PERSONAL_DATA_PAGE_TITLE' => tr('ispCP - Admin/Change Personal Data'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
		)
	);

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {
	update_admin_personal_data($sql, $_SESSION['user_id']);
}

gen_admin_personal_data($tpl, $sql, $_SESSION['user_id']);

function gen_admin_personal_data(&$tpl, &$sql, $user_id) {
	$query = <<<SQL_QUERY
        SELECT
            `fname`,
            `lname`,
            `gender`,
            `firm`,
            `zip`,
            `city`,
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
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));

	$tpl->assign(
			array(
				'FIRST_NAME' => empty($rs->fields['fname'])?'':$rs->fields['fname'],
				'LAST_NAME' => empty($rs->fields['lname'])?'':$rs->fields['lname'],
				'FIRM' => empty($rs->fields['firm'])?'':$rs->fields['firm'],
				'ZIP' => empty($rs->fields['zip'])?'':$rs->fields['zip'],
				'CITY' => empty($rs->fields['city'])?'':$rs->fields['city'],
				'COUNTRY' => empty($rs->fields['country'])?'':$rs->fields['country'],
				'STREET_1' => empty($rs->fields['street1'])?'':$rs->fields['street1'],
				'STREET_2' => empty($rs->fields['street2'])?'':$rs->fields['street2'],
				'EMAIL' => empty($rs->fields['email'])?'':$rs->fields['email'],
				'PHONE' => empty($rs->fields['phone'])?'':$rs->fields['phone'],
				'FAX' => empty($rs->fields['fax'])?'':$rs->fields['fax'],
				'VL_MALE' => (($rs->fields['gender'] == 'M') ? 'selected="selected"' : ''),
				'VL_FEMALE' => (($rs->fields['gender'] == 'F') ? 'selected="selected"' : ''),
				'VL_UNKNOWN' => ((($rs->fields['gender'] == 'U') || (empty($rs->fields['gender']))) ? 'selected="selected"' : ''),
			)
		);
}

function update_admin_personal_data(&$sql, $user_id) {
	$fname = clean_input($_POST['fname']);
	$lname = clean_input($_POST['lname']);
	$gender = $_POST['gender'];
	$firm = clean_input($_POST['firm']);
	$zip = clean_input($_POST['zip']);
	$city = clean_input($_POST['city']);
	$country = clean_input($_POST['country']);
	$street1 = clean_input($_POST['street1']);
	$street2 = clean_input($_POST['street2']);
	$email = clean_input($_POST['email']);
	$phone = clean_input($_POST['phone']);
	$fax = clean_input($_POST['fax']);

	$query = <<<SQL_QUERY
        UPDATE
            `admin`
        SET
            `fname` = ?,
            `lname` = ?,
            `firm` = ?,
            `zip` = ?,
            `city` = ?,
            `country` = ?,
            `street1` = ?,
            `street2` = ?,
            `email` = ?,
            `phone` = ?,
            `fax` = ?,
            `gender` = ?
        WHERE
            `admin_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($fname,
			$lname,
			$firm,
			$zip,
			$city,
			$country,
			$street1,
			$street2,
			$email,
			$phone,
			$fax,
			$gender,
			$user_id));

	set_page_message(tr('Personal data updated successfully!'));
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_general_information.tpl');

$tpl->assign(
	array(
		'TR_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
		'TR_PERSONAL_DATA' => tr('Personal data'),
		'TR_FIRST_NAME' => tr('First name'),
		'TR_LAST_NAME' => tr('Last name'),
		'TR_COMPANY' => tr('Company'),
		'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
		'TR_CITY' => tr('City'),
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
		'TR_UPDATE_DATA' => tr('Update data'),
		)
	);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>
