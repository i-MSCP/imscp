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

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/admin_add.tpl');
$tpl->define_dynamic('page_message', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_ADD_USER_PAGE_TITLE' => tr('ispCP - Admin/Manage users/Add User'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

function add_user(&$tpl, &$sql) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {
		if (check_user_data()) {
			$upass = crypt_user_pass($_POST['pass']);

			$user_id = $_SESSION['user_id'];

			$username = clean_input($_POST['username'], true);
			$fname = clean_input($_POST['fname'], true);
			$lname = clean_input($_POST['lname'], true);
			$gender = clean_input($_POST['gender'], true);
			$firm = clean_input($_POST['firm'], true);
			$zip = clean_input($_POST['zip'], true);
			$city = clean_input($_POST['city'], true);
			$state = clean_input($_POST['state'], true);
			$country = clean_input($_POST['country'], true);
			$email = clean_input($_POST['email'], true);
			$phone = clean_input($_POST['phone'], true);
			$fax = clean_input($_POST['fax'], true);
			$street1 = clean_input($_POST['street1'], true);
			$street2 = clean_input($_POST['street2'], true);

			if (get_gender_by_code($gender, true) === null) {
				$gender = '';
			}

			$query = "
				INSERT INTO `admin`
					(
						`admin_name`,
						`admin_pass`,
						`admin_type`,
						`domain_created`,
						`created_by`,
						`fname`,
						`lname`,
						`firm`,
						`zip`,
						`city`,
						`state`,
						`country`,
						`email`,
						`phone`,
						`fax`,
						`street1`,
						`street2`,
						`gender`
					) VALUES (
						?,
						?,
						'admin',
						unix_timestamp(),
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?,
						?
					)
			";

			$rs = exec_query($sql, $query, array($username,
					$upass,
					$user_id,
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
					$gender));

			$new_admin_id = $sql->Insert_ID();

			$user_logged = $_SESSION['user_logged'];

			write_log("$user_logged: add admin: $username");

			$user_def_lang = $_SESSION['user_def_lang'];
			$user_theme_color = $_SESSION['user_theme'];
			$user_logo = 0;

			$query = "
				INSERT INTO `user_gui_props` (
					`user_id`,
					`lang`,
					`layout`,
					`logo`
				) VALUES (?,?,?,?)
			";

			$rs = exec_query($sql, $query, array($new_admin_id,
					$user_def_lang,
					$user_theme_color,
					$user_logo));

			send_add_user_auto_msg ($user_id,
				clean_input($_POST['username']),
				clean_input($_POST['pass']),
				clean_input($_POST['email']),
				clean_input($_POST['fname']),
				clean_input($_POST['lname']),
				tr('Administrator'),
				$gender
				);

			$_SESSION['user_added'] = 1;

			header("Location: manage_users.php");
			die();
		} else { // check user data
			$tpl->assign(
				array(
					'EMAIL' => clean_input($_POST['email'], true),
					'USERNAME' => clean_input($_POST['username'], true),
					'FIRST_NAME' => clean_input($_POST['fname'], true),
					'LAST_NAME' => clean_input($_POST['lname'], true),
					'FIRM' => clean_input($_POST['firm'], true),
					'ZIP' => clean_input($_POST['zip'], true),
					'CITY' => clean_input($_POST['city'], true),
					'STATE' => clean_input($_POST['state'], true),
					'COUNTRY' => clean_input($_POST['country'], true),
					'STREET_1' => clean_input($_POST['street1'], true),
					'STREET_2' => clean_input($_POST['street2'], true),
					'PHONE' => clean_input($_POST['phone'], true),
					'FAX' => clean_input($_POST['fax'], true),
					'VL_MALE' => (($_POST['gender'] == 'M') ? 'selected="selected"' : ''),
					'VL_FEMALE' => (($_POST['gender'] == 'F') ? 'selected="selected"' : ''),
					'VL_UNKNOWN' => ((($_POST['gender'] == 'U') || (empty($_POST['gender']))) ? 'selected="selected"' : '')
				)
			);
		}
	} else {
		$tpl->assign(
			array(
				'EMAIL' => '',
				'USERNAME' => '',
				'FIRST_NAME' => '',
				'LAST_NAME' => '',
				'FIRM' => '',
				'ZIP' => '',
				'CITY' => '',
				'STATE' => '',
				'COUNTRY' => '',
				'STREET_1' => '',
				'STREET_2' => '',
				'PHONE' => '',
				'FAX' => '',
				'VL_MALE' => '',
				'VL_FEMALE' => '',
				'VL_UNKNOWN' => 'selected="selected"'
			)
		);
	} // end else
}

function check_user_data() {
	$sql = Database::getInstance();

	if (!chk_username($_POST['username'])) {
		set_page_message(tr("Incorrect username length or syntax!"));

		return false;
	}
	if (!chk_password($_POST['pass'])) {
		if (Config::get('PASSWD_STRONG')) {
			set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS')));
		} else {
			set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS')));
		}

		return false;
	}
	if ($_POST['pass'] != $_POST['pass_rep']) {
		set_page_message(tr("Entered passwords do not match!"));

		return false;
	}
	if (!chk_email($_POST['email'])) {
		set_page_message(tr("Incorrect email length or syntax!"));

		return false;
	}

	$query = "
		SELECT
			`admin_id`
		FROM
			`admin`
		WHERE
			`admin_name` = ?
";

	$username = clean_input($_POST['username']);

	$rs = exec_query($sql, $query, array($username));

	if ($rs->RecordCount() != 0) {
		set_page_message(tr('This user name already exist!'));

		return false;
	}

	return true;
}

/*
 *
 * static page messages.
 *
 */

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_users_manage.tpl');

add_user($tpl, $sql);

$tpl->assign(
	array(
		'TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field!'),
		'TR_PASSWORD_NOT_MATCH' => tr("Passwords don't match!"),
		'TR_ADD_ADMIN' => tr('Add admin'),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		'TR_EMAIL' => tr('Email'),
		'TR_ADDITIONAL_DATA' => tr('Additional data'),
		'TR_FIRST_NAME' => tr('First name'),
		'TR_LAST_NAME' => tr('Last name'),
		'TR_GENDER' => tr('Gender'),
		'TR_MALE' => tr('Male'),
		'TR_FEMALE' => tr('Female'),
		'TR_UNKNOWN' => tr('Unknown'),
		'TR_COMPANY' => tr('Company'),
		'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
		'TR_CITY' => tr('City'),
		'TR_STATE' => tr('State/Province'),
		'TR_COUNTRY' => tr('Country'),
		'TR_STREET_1' => tr('Street 1'),
		'TR_STREET_2' => tr('Street 2'),
		'TR_PHONE' => tr('Phone'),
		'TR_FAX' => tr('Fax'),
		'TR_PHONE' => tr('Phone'),
		'TR_ADD' => tr('Add'),
		'GENPAS' => passgen()
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>