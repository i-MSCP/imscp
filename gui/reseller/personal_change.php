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
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/personal_change.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE'	=> tr('ispCP - Reseller/Change Personal Data'),
		'THEME_COLOR_PATH'							=> "../themes/$theme_color",
		'THEME_CHARSET'								=> tr('encoding'),
		'ISP_LOGO'									=> get_logo($_SESSION['user_id']),
	)
);

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_data') {
	update_reseller_personal_data($sql, $_SESSION['user_id']);
}

gen_reseller_personal_data($tpl, $sql, $_SESSION['user_id']);


function gen_reseller_personal_data(&$tpl, &$sql, $user_id) {
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
			'FIRST_NAME' 	=> (($rs->fields['fname'] == null) 		? '' : $rs->fields['fname']),
			'LAST_NAME' 	=> (($rs->fields['lname'] == null) 		? '' : $rs->fields['lname']),
			'FIRM' 			=> (($rs->fields['firm'] == null) 		? '' : $rs->fields['firm']),
			'ZIP' 			=> (($rs->fields['zip'] == null) 		? '' : $rs->fields['zip']),
			'CITY' 			=> (($rs->fields['city'] == null) 		? '' : $rs->fields['city']),
			'STATE' 		=> (($rs->fields['state'] == null) 		? '' : $rs->fields['state']),
			'COUNTRY' 		=> (($rs->fields['country'] == null) 	? '' : $rs->fields['country']),
			'STREET_1' 		=> (($rs->fields['street1'] == null) 	? '' : $rs->fields['street1']),
			'STREET_2' 		=> (($rs->fields['street2'] == null) 	? '' : $rs->fields['street2']),
			'EMAIL' 		=> (($rs->fields['email'] == null) 		? '' : $rs->fields['email']),
			'PHONE' 		=> (($rs->fields['phone'] == null) 		? '' : $rs->fields['phone']),
			'FAX' 			=> (($rs->fields['fax'] == null) 		? '' : $rs->fields['fax']),
			'VL_MALE' 		=> (($rs->fields['gender'] == 'M') 		? 'selected="selected"' : ''),
			'VL_FEMALE' 	=> (($rs->fields['gender'] == 'F') 		? 'selected="selected"' : ''),
			'VL_UNKNOWN' 	=> ((($rs->fields['gender'] == 'U') || (empty($rs->fields['gender']))) ? 'selected="selected"' : '')
			)
		);
}

function update_reseller_personal_data(&$sql, $user_id) {
	$fname		= clean_input($_POST['fname'], true);
	$lname		= clean_input($_POST['lname'], true);
	$gender		= $_POST['gender'];
	$firm		= clean_input($_POST['firm'], true);
	$zip		= clean_input($_POST['zip'], true);
	$city		= clean_input($_POST['city'], true);
	$state		= clean_input($_POST['state'], true);
	$country	= clean_input($_POST['country'], true);
	$street1	= clean_input($_POST['street1'], true);
	$street2	= clean_input($_POST['street2'], true);
	$email		= clean_input($_POST['email'], true);
	$phone		= clean_input($_POST['phone'], true);
	$fax		= clean_input($_POST['fax'], true);

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
			`gender` = ?
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($fname, $lname, $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1, $street2, $gender, $user_id));

	set_page_message(tr('Personal data updated successfully!'));
}

/*
 *
 * static page messages.
 *
 */

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_general_information.tpl');

gen_logged_from($tpl);

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
		'TR_UPDATE_DATA'			=> tr('Update data'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>
