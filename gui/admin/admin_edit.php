<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
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
	user_goto('manage_users.php');
}

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/admin_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'THEME_COLOR_PATH'	=> "../themes/$theme_color",
		'THEME_CHARSET'		=> tr('encoding'),
		'ISP_LOGO'			=> get_logo($_SESSION['user_id']),
	)
);

function update_data(&$sql) {
	global $edit_id;

	if (isset($_POST['Submit']) && isset($_POST['uaction']) && $_POST['uaction'] === 'edit_user') {
		if (check_user_data()) {
			$user_id	= $_SESSION['user_id'];
			$fname		= clean_input($_POST['fname'], true);
			$lname		= clean_input($_POST['lname'], true);
			$firm		= clean_input($_POST['firm'], true);
			$gender		= clean_input($_POST['gender'], true);
			$zip		= clean_input($_POST['zip'], true);
			$city		= clean_input($_POST['city'], true);
			$state		= clean_input($_POST['state'], true);
			$country	= clean_input($_POST['country'], true);
			$email		= clean_input($_POST['email'], true);
			$phone		= clean_input($_POST['phone'], true);
			$fax		= clean_input($_POST['fax'], true);
			$street1	= clean_input($_POST['street1'], true);
			$street2	= clean_input($_POST['street2'], true);

			if (empty($_POST['pass'])) {
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
				$rs = exec_query($sql, $query, array($fname,
						$lname,
						$firm,
						$zip,
						$city,
						$state,
						$country,
						$email,
						$phone,
						$fax,
						$street1,
						$street2,
						$gender,
						$edit_id));
			} else {
				$edit_id = $_POST['edit_id'];

				if ($_POST['pass'] != $_POST['pass_rep']) {
					set_page_message(tr("Entered passwords do not match!"));

					user_goto('admin_edit.php?edit_id=' . $edit_id);
				}

				if (!chk_password($_POST['pass'])) {
					if (Config::get('PASSWD_STRONG')) {
						set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS')));
					} else {
						set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS')));
					}

					user_goto('admin_edit.php?edit_id=' . $edit_id);
				}

				$upass = crypt_user_pass($_POST['pass']);

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
						`gender` = ?
					WHERE
						`admin_id` = ?
				";

				$rs = exec_query($sql, $query, array($upass,
						$fname,
						$lname,
						$firm,
						$zip,
						$city,
						$state,
						$country,
						$email,
						$phone,
						$fax,
						$street1,
						$street2,
						$gender,
						$edit_id));

				// Kill any existing session of the edited user

				$admin_name = get_user_name($edit_id);
				$query = "
					DELETE FROM
						`login`
					WHERE
						`user_name` = ?
				";

				$rs = exec_query($sql, $query, array($admin_name));
				if ($rs->RecordCount() != 0) {
					set_page_message(tr('User session was killed!'));
					write_log($_SESSION['user_logged'] . " killed " . $admin_name . "'s session because of password change");
				}
			}

			$edit_username = clean_input($_POST['edit_username']);

			$user_logged = $_SESSION['user_logged'];

			write_log("$user_logged: changes data/password for $edit_username!");

			if (isset($_POST['send_data']) && !empty($_POST['pass'])) {
				$query = "SELECT admin_type FROM admin WHERE admin_id='" . addslashes(htmlspecialchars($edit_id)) . "'";

				$res = exec_query($sql, $query, array());

				if ($res->fields['admin_type'] == 'admin') {
					$admin_type = tr('Administrator');
				} else if ($res->fields['admin_type'] == 'reseller') {
					$admin_type = tr('Reseller');
				} else {
					$admin_type = tr('Domain account');
				}

				send_add_user_auto_msg ($user_id,
					$edit_username,
					clean_input($_POST['pass']),
					clean_input($_POST['email']),
					clean_input($_POST['fname']),
					clean_input($_POST['lname']),
					tr($admin_type),
					$gender);
			}

			$_SESSION['user_updated'] = 1;

			user_goto('manage_users.php');
		}
	}
}

function check_user_data() {
	if (!chk_email($_POST['email'])) {
		set_page_message(tr("Incorrect email length or syntax!"));

		return false;
	}

	return true;
}

if ($edit_id == $_SESSION['user_id']) {
	user_goto('personal_change.php');
}

/*
 *
 * static page messages.
 *
 */

$query = "
	SELECT
		`admin_name`,
		`admin_type`,
		`fname`,
		`lname`,
		`firm`,
		`zip`,
		`city`,
		`state`,
		`country`,
		`phone`,
		`fax`,
		`street1`,
		`street2`,
		`email`,
		`gender`
	FROM
		`admin`
	WHERE
		`admin_id` = ?
";

$rs = exec_query($sql, $query, array($edit_id));

if ($rs->RecordCount() <= 0) {
	user_goto('manage_users.php');
}

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_users_manage.tpl');

update_data($sql);

$admin_name = decode_idna($rs->fields['admin_name']);

if (isset($_POST['genpass'])) {
	$tpl->assign('VAL_PASSWORD', passgen());
} else {
	$tpl->assign('VAL_PASSWORD', '');
}

$tpl->assign(
	array(
		'TR_ADMIN_EDIT_USER_PAGE_TITLE'	=> ($rs->fields['admin_type'] == 'admin' ? tr('ispCP - Admin/Manage users/Edit Administrator') : tr('ispCP - Admin/Manage users/Edit User')),
		'TR_EMPTY_OR_WORNG_DATA'		=> tr('Empty data or wrong field!'),
		'TR_PASSWORD_NOT_MATCH'			=> tr("Passwords don't match!"),
		'TR_EDIT_ADMIN'					=> ($rs->fields['admin_type'] == 'admin' ? tr('Edit admin') : tr('Edit user')),
		'TR_CORE_DATA'					=> tr('Core data'),
		'TR_USERNAME'					=> tr('Username'),
		'TR_PASSWORD'					=> tr('Password'),
		'TR_PASSWORD_REPEAT'			=> tr('Repeat password'),
		'TR_EMAIL'						=> tr('Email'),
		'TR_ADDITIONAL_DATA'			=> tr('Additional data'),
		'TR_FIRST_NAME'					=> tr('First name'),
		'TR_LAST_NAME'					=> tr('Last name'),
		'TR_COMPANY'					=> tr('Company'),
		'TR_ZIP_POSTAL_CODE'			=> tr('Zip/Postal code'),
		'TR_CITY'						=> tr('City'),
		'TR_STATE_PROVINCE'				=> tr('State/Province'),
		'TR_COUNTRY'					=> tr('Country'),
		'TR_STREET_1'					=> tr('Street 1'),
		'TR_STREET_2'					=> tr('Street 2'),
		'TR_PHONE'						=> tr('Phone'),
		'TR_FAX'						=> tr('Fax'),
		'TR_PHONE'						=> tr('Phone'),
		'TR_GENDER'						=> tr('Gender'),
		'TR_MALE'						=> tr('Male'),
		'TR_FEMALE'						=> tr('Female'),
		'TR_UNKNOWN'					=> tr('Unknown'),
		'TR_UPDATE'						=> tr('Update'),
		'TR_SEND_DATA'					=> tr('Send new login data'),
		'TR_PASSWORD_GENERATE'			=> tr('Generate password'),
		'FIRST_NAME'					=> empty($rs->fields['fname']) ? '' : $rs->fields['fname'],
		'LAST_NAME'						=> empty($rs->fields['lname']) ? '' : $rs->fields['lname'],
		'FIRM'							=> empty($rs->fields['firm']) ? '' : $rs->fields['firm'],
		'ZIP'							=> empty($rs->fields['zip']) ? '' : $rs->fields['zip'],
		'CITY'							=> empty($rs->fields['city']) ? '' : $rs->fields['city'],
		'STATE_PROVINCE'				=> empty($rs->fields['state']) ? '' : $rs->fields['state'],
		'COUNTRY'						=> empty($rs->fields['country']) ? '' : $rs->fields['country'],
		'STREET_1'						=> empty($rs->fields['street1']) ? '' : $rs->fields['street1'],
		'STREET_2'						=> empty($rs->fields['street2']) ? '' : $rs->fields['street2'],
		'PHONE'							=> empty($rs->fields['phone']) ? '' : $rs->fields['phone'],
		'FAX'							=> empty($rs->fields['fax']) ? '' : $rs->fields['fax'],
		'USERNAME'						=> $admin_name,
		'EMAIL'							=> $rs->fields['email'],
		'VL_MALE'						=> (($rs->fields['gender'] === 'M') ? 'selected="selected"' : ''),
		'VL_FEMALE'						=> (($rs->fields['gender'] === 'F') ? 'selected="selected"' : ''),
		'VL_UNKNOWN'					=> ((($rs->fields['gender'] === 'U') || (empty($rs->fields['gender']))) ? 'selected="selected"' : ''),
		'EDIT_ID'						=> $edit_id
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
