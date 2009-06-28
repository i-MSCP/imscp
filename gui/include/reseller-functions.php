<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		$ID$
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

/* for mail types */
define('MT_NORMAL_MAIL', 'normal_mail');
define('MT_NORMAL_FORWARD', 'normal_forward');
define('MT_ALIAS_MAIL', 'alias_mail');
define('MT_ALIAS_FORWARD', 'alias_forward');
define('MT_SUBDOM_MAIL', 'subdom_mail');
define('MT_SUBDOM_FORWARD', 'subdom_forward');
define('MT_ALSSUB_MAIL', 'alssub_mail');
define('MT_ALSSUB_FORWARD', 'alssub_forward');
define('MT_NORMAL_CATCHALL', 'normal_catchall');
define('MT_SUBDOM_CATCHALL', 'subdom_catchall');
define('MT_ALIAS_CATCHALL', 'alias_catchall');
define('MT_ALSSUB_CATCHALL', 'alssub_catchall');

function gen_reseller_mainmenu(&$tpl, $menu_file) {
	$sql = Database::getInstance();

	$tpl->define_dynamic('menu', $menu_file);
	$tpl->define_dynamic('isactive_support', 'menu');
	$tpl->define_dynamic('custom_buttons', 'menu');

	$tpl->assign(
		array(
			'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
			'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
			'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
			'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
			'TR_MENU_ADD_HOSTING' => tr('Add hosting plan'),
			'TR_MENU_MANAGE_USERS' => tr('Manage users'),
			'TR_MENU_ADD_USER' => tr('Add user'),
			'TR_MENU_E_MAIL_SETUP' => tr('Email setup'),
			'TR_MENU_CIRCULAR' => tr('Email marketing'),
			'TR_MENU_MANAGE_DOMAINS' => tr('Manage domains'),
			'TR_MENU_DOMAIN_ALIAS' => tr('Domain alias'),
			'TR_MENU_SUBDOMAINS' => tr('Subdomains'),
			'TR_MENU_DOMAIN_STATISTICS' => tr('Domain statistics'),
			'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
			'TR_MENU_NEW_TICKET' => tr('New ticket'),
			'TR_MENU_LAYOUT_SETTINGS' => tr('Layout settings'),
			'TR_MENU_LOGOUT' => tr('Logout'),
			'TR_MENU_OVERVIEW' => tr('Overview'),
			'TR_MENU_LANGUAGE' => tr('Language'),
			'SUPPORT_SYSTEM_PATH' => Config::get('ISPCP_SUPPORT_SYSTEM_PATH'),
			'SUPPORT_SYSTEM_TARGET' => Config::get('ISPCP_SUPPORT_SYSTEM_TARGET'),
			'TR_MENU_ORDERS' => tr('Manage Orders'),
			'TR_MENU_ORDER_SETTINGS' => tr('Order settings'),
			'TR_MENU_ORDER_EMAIL' => tr('Order email setup'),
			'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup')
		)
	);

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'reseller'
		OR
			`menu_level` = 'all'
SQL_QUERY;

	$rs = exec_query($sql, $query, array());
	if ($rs->RecordCount() == 0) {
		$tpl->assign('CUSTOM_BUTTONS', '');
	} else {
		global $i;
		$i = 100;

		while (!$rs->EOF) {
			$menu_name = $rs->fields['menu_name'];
			$menu_link = get_menu_vars($rs->fields['menu_link']);
			$menu_target = $rs->fields['menu_target'];

			if ($menu_target !== "") {
				$menu_target = 'target="' . $menu_target . '"';
			}

			$tpl->assign(
				array(
					'BUTTON_LINK' => $menu_link,
					'BUTTON_NAME' => $menu_name,
					'BUTTON_TARGET' => $menu_target,
					'BUTTON_ID' => $i,
				)
			);

			$tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
			$rs->MoveNext();
			$i++;
		} // end while
	} // end else
	if (!Config::get('ISPCP_SUPPORT_SYSTEM')) {
		$tpl->assign('ISACTIVE_SUPPORT', '');
	}

	$tpl->parse('MAIN_MENU', 'menu');
} // end of gen_reseller_menu()

/**
 * Function to generate the menu data for reseller
 */
function gen_reseller_menu(&$tpl, $menu_file) {
	$sql = Database::getInstance();

	$tpl->define_dynamic('menu', $menu_file);

	$tpl->define_dynamic('custom_buttons', 'menu');

	$tpl->assign(
		array(
			'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
			'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
			'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
			'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
			'TR_MENU_ADD_HOSTING' => tr('Add hosting plan'),
			'TR_MENU_MANAGE_USERS' => tr('Manage users'),
			'TR_MENU_ADD_USER' => tr('Add user'),
			'TR_MENU_E_MAIL_SETUP' => tr('Email setup'),
			'TR_MENU_CIRCULAR' => tr('Email marketing'),
			'TR_MENU_MANAGE_DOMAINS' => tr('Manage domains'),
			'TR_MENU_DOMAIN_ALIAS' => tr('Domain alias'),
			'TR_MENU_SUBDOMAINS' => tr('Subdomains'),
			'TR_MENU_DOMAIN_STATISTICS' => tr('Domain statistics'),
			'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
			'TR_MENU_NEW_TICKET' => tr('New ticket'),
			'TR_MENU_LAYOUT_SETTINGS' => tr('Layout settings'),
			'TR_MENU_LOGOUT' => tr('Logout'),
			'TR_MENU_OVERVIEW' => tr('Overview'),
			'TR_MENU_LANGUAGE' => tr('Language'),
			'SUPPORT_SYSTEM_PATH' => Config::get('ISPCP_SUPPORT_SYSTEM_PATH'),
			'SUPPORT_SYSTEM_TARGET' => Config::get('ISPCP_SUPPORT_SYSTEM_TARGET'),
			'TR_MENU_ORDERS' => tr('Manage Orders'),
			'TR_MENU_ORDER_SETTINGS' => tr('Order settings'),
			'TR_MENU_ORDER_EMAIL' => tr('Order email setup'),
			'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup'),
			'VERSION' => Config::get('Version'),
			'BUILDDATE' => Config::get('BuildDate'),
			'CODENAME' => Config::get('CodeName')
		)
	);

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'reseller'
		OR
			`menu_level` = 'all'
SQL_QUERY;

	$rs = exec_query($sql, $query, array());
	if ($rs->RecordCount() == 0) {
		$tpl->assign('CUSTOM_BUTTONS', '');
	} else {
		global $i;
		$i = 100;

		while (!$rs->EOF) {
			$menu_name = $rs->fields['menu_name'];
			$menu_link = get_menu_vars($rs->fields['menu_link']);
			$menu_target = $rs->fields['menu_target'];

			if ($menu_target !== "") {
				$menu_target = 'target="' . $menu_target . '"';
			}

			$tpl->assign(
				array(
					'BUTTON_LINK' => $menu_link,
					'BUTTON_NAME' => $menu_name,
					'BUTTON_TARGET' => $menu_target,
					'BUTTON_ID' => $i,
				)
			);

			$tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
			$rs->MoveNext();
			$i++;
		} // end while
	} // end else
	if (!Config::get('ISPCP_SUPPORT_SYSTEM')) {
		$tpl->assign('ISACTIVE_SUPPORT', '');
	}
	if (Config::exists('HOSTING_PLANS_LEVEL') && strtolower(Config::get('HOSTING_PLANS_LEVEL')) === 'admin') {
		$tpl->assign('HP_MENU_ADD', '');
	}

	$tpl->parse('MENU', 'menu');
} // end of gen_reseller_menu()

/**
 * Get data for page of reseller
 */
function get_reseller_default_props(&$sql, $reseller_id) {
	// Make sql query
	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
SQL_QUERY;
	// send sql query
	$rs = exec_query($sql, $query, array($reseller_id));

	if (0 == $rs->RowCount()) {
		return NULL;
	}

	return array(
		$rs->fields['current_dmn_cnt'],
		$rs->fields['max_dmn_cnt'],
		$rs->fields['current_sub_cnt'],
		$rs->fields['max_sub_cnt'],
		$rs->fields['current_als_cnt'],
		$rs->fields['max_als_cnt'],
		$rs->fields['current_mail_cnt'],
		$rs->fields['max_mail_cnt'],
		$rs->fields['current_ftp_cnt'],
		$rs->fields['max_ftp_cnt'],
		$rs->fields['current_sql_db_cnt'],
		$rs->fields['max_sql_db_cnt'],
		$rs->fields['current_sql_user_cnt'],
		$rs->fields['max_sql_user_cnt'],
		$rs->fields['current_traff_amnt'],
		$rs->fields['max_traff_amnt'],
		$rs->fields['current_disk_amnt'],
		$rs->fields['max_disk_amnt']
	);
} // end of get_reseller_default_props()

/**
 * Making users props
 */
function generate_reseller_user_props($reseller_id) {
	$sql = Database::getInstance();
	// Init with empty variables
	$rdmn_current = 0;
	$rdmn_max = 0;
	$rdmn_uf = '_off_';
	$rsub_current = 0;
	$rsub_max = 0;
	$rsub_uf = '_off_';
	$rals_current = 0;
	$rals_max = 0;
	$rals_uf = '_off_';
	$rmail_current = 0;
	$rmail_max = 0;
	$rmail_uf = '_off_';
	$rftp_current = 0;
	$rftp_max = 0;
	$rftp_uf = '_off_';
	$rsql_db_current = 0;
	$rsql_db_max = 0;
	$rsql_db_uf = '_off_';
	$rsql_user_current = 0;
	$rsql_user_max = 0;
	$rsql_user_uf = '_off_';
	$rtraff_current = 0;
	$rtraff_max = 0;
	$rtraff_uf = '_off_';
	$rdisk_current = 0;
	$rdisk_max = 0;
	$rdisk_uf = '_off_';

	$query = <<<SQL_QUERY
		SELECT
			`admin_id`
		FROM
			`admin`
		WHERE
			`created_by` = ?
SQL_QUERY;

	$res = exec_query($sql, $query, array($reseller_id));

	if ($res->RowCount() == 0) {
		return array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}
	// Process all users of this group
	while ($data = $res->FetchRow()) {
		$admin_id = $data['admin_id'];

		$query = <<<SQL_QUERY
			SELECT
				`domain_id`
			FROM
				`domain`
			WHERE
				`domain_admin_id` = ?
SQL_QUERY;

		$dres = exec_query($sql, $query, array($admin_id));

		$ddata = $dres->FetchRow();

		$user_id = $ddata['domain_id'];

		list($sub_current, $sub_max,
			$als_current, $als_max,
			$mail_current, $mail_max,
			$ftp_current, $ftp_max,
			$sql_db_current, $sql_db_max,
			$sql_user_current, $sql_user_max,
			$traff_max, $disk_max
		) = get_user_props($user_id);

		list($tmpval1,
			$tmpval2,
			$tmpval3,
			$tmpval4,
			$tmpval5,
			$tmpval16,
			$traff_current,
			$disk_current,
			$tmpval7,
			$tmpval8
		) = generate_user_traffic($user_id);

		$rdmn_current += 1;

		if ($sub_max != -1) {
			if ($sub_max == 0) $rsub_uf = '_on_';

			$rsub_current += $sub_current;
			$rsub_max += $sub_max;
		}

		if ($als_max != -1) {
			if ($als_max == 0) $rals_uf = '_on_';

			$rals_current += $als_current;
			$rals_max += $als_max;
		}

		if ($mail_max != -1) {
			if ($mail_max == 0) $rmail_uf = '_on_';

			$rmail_current += $mail_current;
			$rmail_max += $mail_max;
		}

		if ($ftp_max != -1) {
			if ($ftp_max == 0) $rftp_uf = '_on_';

			$rftp_current += $ftp_current;
			$rftp_max += $ftp_max;
		}

		if ($sql_db_max != -1) {
			if ($sql_db_max == 0) $rsql_db_uf = '_on_';

			$rsql_db_current += $sql_db_current;
			$rsql_db_max += $sql_db_max;
		}

		if ($sql_user_max != -1) {
			if ($sql_user_max == 0) $rsql_user_uf = '_on_';

			$rsql_user_current += $sql_user_current;
			$rsql_user_max += $sql_user_max;
		}

		if ($traff_max == 0) $rtraff_uf = '_on_';

		$rtraff_current += $traff_current;
		$rtraff_max += $traff_max;
		if ($disk_max == 0) $rdisk_uf = '_on_';

		$rdisk_current += $disk_current;
		$rdisk_max += $disk_max;
	}

	return array($rdmn_current, $rdmn_max, $rdmn_uf,
		$rsub_current, $rsub_max, $rsub_uf,
		$rals_current, $rals_max, $rals_uf,
		$rmail_current, $rmail_max, $rmail_uf,
		$rftp_current, $rftp_max, $rftp_uf,
		$rsql_db_current, $rsql_db_max, $rsql_db_uf,
		$rsql_user_current, $rsql_user_max, $rsql_user_uf,
		$rtraff_current, $rtraff_max, $rtraff_uf,
		$rdisk_current, $rdisk_max, $rdisk_uf);
} // end of generate_reseller_user_props()

/**
 * Get traffic information for user
 */
function get_user_traffic($user_id) {

	$sql = Database::getInstance();
	global $crnt_month, $crnt_year;

	$query = <<<SQL_QUERY
		SELECT
			`domain_id`,
			IFNULL(`domain_disk_usage`, 0) AS domain_disk_usage,
			IFNULL(`domain_traffic_limit`, 0) AS domain_traffic_limit,
			IFNULL(`domain_disk_limit`,0) AS domain_disk_limit,
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		ORDER BY
			`domain_id`
SQL_QUERY;

	$res = exec_query($sql, $query, array($user_id));

	if ($res->RowCount() == 0 || $res->RowCount() > 1) {
		// write_log("TRAFFIC WARNING: >$user_id< manages incorrect number of domains >".$res->RowCount()."<");
		return array('n/a', 0, 0, 0, 0, 0, 0, 0, 0, 0);
	} else {
		$data = $res->FetchRow();

		$domain_id = $data['domain_id'];

		$domain_disk_usage = $data['domain_disk_usage'];

		$domain_traff_limit = $data['domain_traffic_limit'];

		$domain_disk_limit = $data['domain_disk_limit'];

		$domain_name = $data['domain_name'];

		$query = <<<SQL_QUERY
			SELECT
				SUM(`dtraff_web`) AS web,
				SUM(`dtraff_ftp`) AS ftp,
				SUM(`dtraff_mail`) AS smtp,
				SUM(`dtraff_pop`) AS pop,
				SUM(`dtraff_web`) +
				SUM(`dtraff_ftp`) +
				SUM(`dtraff_mail`) +
				SUM(`dtraff_pop`) AS total
			FROM
				`domain_traffic`
			WHERE
				`domain_id` = ?
SQL_QUERY;

		$res = exec_query($sql, $query, array($domain_id));

		$data = $res->FetchRow();

		return array($domain_name,
			$domain_id,
			$data['web'],
			$data['ftp'],
			$data['smtp'],
			$data['pop'],
			$data['total'],
			$domain_disk_usage,
			$domain_traff_limit,
			$domain_disk_limit
		);
	}
} // end of get_user_traffic()

/**
 * Get user's probs info from db via sql
 */
function get_user_props($user_id) {

	$sql = Database::getInstance();

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`domain`
		WHERE
			`domain_id` = ?
SQL_QUERY;

	$res = exec_query($sql, $query, array($user_id));

	if ($res->RowCount() == 0) {
		return array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}

	$data = $res->FetchRow();

	$sub_current = get_domain_running_sub_cnt($sql, $user_id);
	$sub_max = $data['domain_subd_limit'];

	$als_current = records_count('alias_id', 'domain_aliasses', 'domain_id', $user_id);
	$als_max = $data['domain_alias_limit'];

	if (Config::get('COUNT_DEFAULT_EMAIL_ADDRESSES')) {
		$mail_current = records_count('mail_id', 'mail_users', 'domain_id', $user_id);
	} else {
		$where = "`mail_acc` != 'abuse'
		AND `mail_acc` != 'postmaster'
		AND `mail_acc` != 'webmaster'
		AND `domain_id`";
		$mail_current = records_count('mail_id', 'mail_users', $where, $user_id);
	}
	$mail_max = $data['domain_mailacc_limit'];

	$ftp_current = sub_records_rlike_count('domain_name', 'domain', 'domain_id', $user_id,
		'userid', 'ftp_users', 'userid', '@', ''
		);

	$ftp_current += sub_records_rlike_count('subdomain_name', 'subdomain', 'domain_id', $user_id,
		'userid', 'ftp_users', 'userid', '@', ''
		);

	$ftp_current += sub_records_rlike_count('alias_name', 'domain_aliasses', 'domain_id', $user_id,
		'userid', 'ftp_users', 'userid', '@', ''
		);

	$ftp_max = $data['domain_ftpacc_limit'];

	$sql_db_current = records_count('sqld_id', 'sql_database', 'domain_id', $user_id);
	$sql_db_max = $data['domain_sqld_limit'];

	$sql_user_current = get_domain_running_sqlu_acc_cnt($sql, $user_id);

	$sql_user_max = $data['domain_sqlu_limit'];

	$traff_max = $data['domain_traffic_limit'];

	$disk_max = $data['domain_disk_limit'];
	// Make return data
	return array($sub_current, $sub_max,
		$als_current, $als_max,
		$mail_current, $mail_max,
		$ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max,
		$sql_user_current, $sql_user_max,
		$traff_max, $disk_max
	);
} // end of get_user_props();

function rsl_full_domain_check($data) {
	$data .= '.';
	$match = array();
	$last_match = array();

	$res = preg_match_all("/([^\.]*\.)/",
		$data,
		$match,
		PREG_PATTERN_ORDER
		);

	if ($res == 0) return 0;

	$last = $res - 1;

	for ($i = 0; $i < $last; $i++) {
		$token = chop($match[0][$i], ".");

		if (!check_dn_rsl_token($token)) {
			return 0;
		}
	}

	$res = preg_match("/^[A-Za-z][A-Za-z0-9]*[A-Za-z]\.$/",
		$match[0][$last],
		$last_match
		);

	return ($res == 0) ? 0 : 1;
} // end of full_domain_check()

/**
 * Generate IP list
 */
function generate_ip_list(&$tpl, &$reseller_id) {
	$sql = Database::getInstance();
	global $domain_ip;

	$query = <<<SQL_QUERY
		SELECT
			`reseller_ips`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
SQL_QUERY;

	$res = exec_query($sql, $query, array($reseller_id));

	$data = $res->FetchRow();

	$reseller_ips = $data['reseller_ips'];

	$query = "SELECT * FROM `server_ips`";

	$res = exec_query($sql, $query, array());

	while ($data = $res->FetchRow()) {
		$ip_id = $data['ip_id'];

		if (preg_match("/$ip_id;/", $reseller_ips) == 1) {
			$selected = ($domain_ip === $ip_id) ? 'selected="selected"' : '';

			$tpl->assign(
				array(
					'IP_NUM' => $data['ip_number'],
					'IP_NAME' => $data['ip_domain'],
					'IP_VALUE' => $ip_id,
					'IP_SELECTED' => $selected
				)
			);

			$tpl->parse('IP_ENTRY', '.ip_entry');
		}
	} // end loop
} // end of generate_ip_list()

/**
 * Check validity of input data
 *
 * @todo check if we can remove out commented code block
 */
function check_ruser_data(&$tpl, $noPass) {
	global $dmn_name, $hpid , $dmn_user_name;
	global $user_email, $customer_id, $first_name;
	global $last_name, $firm, $zip, $gender;
	global $city, $state, $country, $street_one;
	global $street_two, $mail, $phone;
	global $fax, $inpass, $domain_ip;

	$user_add_error = '_off_';
	$inpass_re = '';
	// Get data for fields from previous page
	if (isset($_POST['userpassword']))
		$inpass = $_POST['userpassword'];

	if (isset($_POST['userpassword_repeat']))
		$inpass_re = $_POST['userpassword_repeat'];

	if (isset($_POST['domain_ip']))
		$domain_ip = $_POST['domain_ip'];

	if (isset($_POST['useremail']))
		$user_email = $_POST['useremail'];

	if (isset($_POST['useruid']))
		$customer_id = $_POST['useruid'];

	if (isset($_POST['userfname']))
		$first_name = $_POST['userfname'];

	if (isset($_POST['userlname']))
		$last_name = $_POST['userlname'];

	if (isset($_POST['userfirm']))
		$firm = $_POST['userfirm'];

	if (isset($_POST['userzip']))
		$zip = $_POST['userzip'];

	if (isset($_POST['usercity']))
		$city = $_POST['usercity'];

	if (isset($_POST['userstate']))
		$state = $_POST['userstate'];

	if (isset($_POST['usercountry']))
		$country = $_POST['usercountry'];

	if (isset($_POST['userstreet1']))
		$street_one = $_POST['userstreet1'];

	if (isset($_POST['userstreet2']))
		$street_two = $_POST['userstreet2'];

	if (isset($_POST['useremail']))
		$mail = $_POST['useremail'];

	if (isset($_POST['userphone']))
		$phone = $_POST['userphone'];

	if (isset($_POST['userfax']))
		$fax = $_POST['userfax'];

	if (isset($_POST['gender'])
		&& get_gender_by_code($_POST['gender'], true) !== null) {
		$gender = $_POST['gender'];
	} else {
		$gender = '';
	}
	//if (isset($_SESSION['local_data']))
	//	list($dmn_name, $hpid, $dmn_user_name) = explode(";", $_SESSION['local_data']);
	// Begin checking...
	if ('_no_' == $noPass) {
		if (('' === $inpass_re) || ('' === $inpass)) {
			$user_add_error = tr('Please fill up both data fields for password!');
		} else if ($inpass_re !== $inpass) {
			$user_add_error = tr("Passwords don't match!");
		} else if (!chk_password($inpass)) {
			if (Config::get('PASSWD_STRONG')) {
				$user_add_error = sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), Config::get('PASSWD_CHARS'));
			} else {
				$user_add_error = sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), Config::get('PASSWD_CHARS'));
			}
		}
	}

	if ($user_email == NULL) {
		$user_add_error = tr('Incorrect email length or syntax!');
	}
	/* we don't want to validate Customer ID, First and Second name and also ZIP

	  else if (!ispcp_limit_check($customer_id)) {
		$user_add_error = tr('Incorrect customer ID syntax!');
	} else if (!chk_username($first_name, 40)) {

		$user_add_error = tr('Incorrect first name length or syntax!');
	} else if (!chk_username($last_name, 40)) {

		$user_add_error = tr('Incorrect second name length or syntax!');
	} else if (!ispcp_limit_check($zip)) {

		$user_add_error = tr('Incorrect post code length or syntax!');
	} */

	if ($user_add_error == '_off_') {
		// send data through session
		$_SESSION['Message'] = NULL;

		return true;
	} else {
		$_SESSION['Message'] = $user_add_error;

		return false;
	}
} // end of check_ruser_data()

/**
 * Translate domain status
 */
function translate_dmn_status($status) {
	switch ($status) {
		case Config::get('ITEM_OK_STATUS'): return tr('OK');
		case Config::get('ITEM_ADD_STATUS'): return tr('Addition in progress');
		case Config::get('ITEM_CHANGE_STATUS'): return tr('Modification in progress');
		case Config::get('ITEM_DELETE_STATUS'): return tr('Deletion in progress');
		case Config::get('ITEM_DISABLED_STATUS'): return tr('Suspended');
		case Config::get('ITEM_TOENABLE_STATUS'): return tr('Being enabled');
		case Config::get('ITEM_TODISABLED_STATUS'): return tr('Being suspended');
		case Config::get('ITEM_ORDERED_STATUS'): return tr('Awaiting for approval');
		default: return tr('Unknown error');
	}
} // end of translate_dmn_status()

/**
 * Check if the domain already exist
 */
function ispcp_domain_exists($domain_name, $reseller_id) {
	$sql = Database::getInstance();
	// query to check if the domain name exist in the table for domains/accounts
	$query_domain = <<<SQL_QUERY
		SELECT
			COUNT(`domain_id`) AS cnt
		FROM
			`domain`
		WHERE
			`domain_name` = ?
SQL_QUERY;

	$res_domain = exec_query($sql, $query_domain, array($domain_name));
	// query to check if the domain name exists in the table for domain aliases
	$query_alias = <<<SQL_QUERY
		SELECT
			COUNT(t1.`alias_id`) AS cnt
		FROM
			`domain_aliasses` AS t1, `domain` AS t2
		WHERE
			t1.`domain_id` = t2.`domain_id`
		AND
			t1.`alias_name` = ?
SQL_QUERY;

	$res_aliases = exec_query($sql, $query_alias, array($domain_name));
	// redefine query to check in the table domain/acounts if 3rd level for this reseller is allowed
	$query_domain = <<<SQL_QUERY
		SELECT
			COUNT(`domain_id`) AS cnt
		FROM
			`domain`
		WHERE
			`domain_name` = ?
		AND
			`domain_created_id` <> ?
SQL_QUERY;
	// redefine query to check in the table aliases if 3rd level for this reseller is allowed
	$query_alias = <<<SQL_QUERY
		SELECT
			COUNT(t1.`alias_id`) AS cnt
		FROM
			`domain_aliasses` AS t1, `domain` AS t2
		WHERE
			t1.`domain_id` = t2.`domain_id`
		AND
			t1.`alias_name` = ?
		AND
			t2.`domain_created_id` <> ?
SQL_QUERY;
	// here we split the domain name by point separator
	$split_domain = explode(".", trim($domain_name));
	$dom_cnt = strlen(trim($domain_name));
	$dom_part_cnt = 0;
	$error = 0;
	// here starts a loop to check if the splitted domain is available for other resellers
	for ($i = 0, $cnt_split_domain = count($split_domain) - 1; $i < $cnt_split_domain; $i++) {
		$dom_part_cnt = $dom_part_cnt + strlen($split_domain[$i]) + 1;
		$idom = substr($domain_name, $dom_part_cnt);
		// execute query the redefined queries for domains/accounts and aliases tables
		$res2 = exec_query($sql, $query_domain, array($idom, $reseller_id));
		$res3 = exec_query($sql, $query_alias, array($idom, $reseller_id));
		// do we have available record. id yes => the variable error get value different 0
		if ($res2->fields['cnt'] > 0 || $res3->fields['cnt'] > 0) {
			$error++;
		}
	}
	// if we have :
	// db entry in the tables domain
	// AND
	// no problem with 3rd level domains
	// AND
	// enduser (no reseller)
	// => the function returns OK => domain can be added
	if ($res_domain->fields['cnt'] == 0
		&& $res_aliases->fields['cnt'] == 0
		&& $error == 0 && $reseller_id == 0) {
		return false;
	}
	// if we have domain add one by end user
	// OR
	// some error
	// => the funcion returns ERROR
	if ($reseller_id == 0 || $error) {
		return true;
	}
	// ok we do not have end user and we do not have error => the fun goes on :-)
	// query to check if the domain does not exist as subdomain
	$query_build_subdomain = <<<SQL_QUERY
		SELECT
			t1.`subdomain_name`, t2.`domain_name`
		FROM
			`subdomain` AS t1, `domain` AS t2
		WHERE
			t1.`domain_id` = t2.`domain_id`
		AND
			t2.`domain_created_id` = ?
SQL_QUERY;

	$subdomains = array();
	$res_build_sub = exec_query($sql, $query_build_subdomain, array($reseller_id));
	while (!$res_build_sub->EOF) {
		$subdomains[] = $res_build_sub->fields['subdomain_name'] . "." . $res_build_sub->fields['domain_name'];
		$res_build_sub->MoveNext();
	}

	if ($res_domain->fields['cnt'] == 0 && $res_aliases->fields['cnt'] == 0 && !in_array($domain_name, $subdomains)) {
		return false;
	} else {
		return true;
	}
} // end of ispcp_domain_exists()

/**
 * @todo see inline comment, about the messed up code
 * @todo use db prepared statements
 */
function gen_manage_domain_query(&$search_query, &$count_query,
	$reseller_id,
	$start_index,
	$rows_per_page,
	$search_for,
	$search_common,
	$search_status) {
	// IMHO, this code is an unmaintainable mess and should be replaced - Cliff
	if ($search_for === 'n/a' && $search_common === 'n/a'
		&& $search_status === 'n/a') {

		// We have pure list query;
		$count_query = <<<SQL_QUERY
			SELECT
				COUNT(`domain_id`) AS cnt
			FROM
				`domain`
			WHERE
				`domain_created_id` = '$reseller_id'
SQL_QUERY;

		$search_query = <<<SQL_QUERY
			SELECT
				*
			FROM
				`domain`
			WHERE
				`domain_created_id` = '$reseller_id'
			ORDER BY
				`domain_name` ASC
			LIMIT
				$start_index, $rows_per_page
SQL_QUERY;
	} else if ($search_for === '' && $search_status != '') {
		if ($search_status === 'all') {
			$add_query = <<<SQL_QUERY
				`domain_created_id` = '$reseller_id'
SQL_QUERY;
		} else {
			$add_query = <<<SQL_QUERY
					`domain_created_id` = '$reseller_id'
				AND
					`domain_status` = '$search_status'
SQL_QUERY;
		}

		$count_query = <<<SQL_QUERY
			SELECT
				COUNT(`domain_id`) AS cnt
			FROM
				`domain`
			WHERE
				$add_query
SQL_QUERY;

		$search_query = <<<SQL_QUERY
			SELECT
				*
			FROM
				`domain`
			WHERE
				$add_query
			ORDER BY
				`domain_name` ASC
			LIMIT
				$start_index, $rows_per_page
SQL_QUERY;
	} else if ($search_for != '') {
		if ($search_common === 'domain_name') {
			$add_query = "WHERE `admin_name` RLIKE '" . addslashes($search_for) . "' %s";
		} else if ($search_common === 'customer_id') {
			$add_query = "WHERE `customer_id` RLIKE '" . addslashes($search_for) . "' %s";
		} else if ($search_common === 'lname') {
			$add_query = "WHERE (`lname` RLIKE '" . addslashes($search_for) . "' OR `fname` RLIKE '" . addslashes($search_for) . "') %s";
		} else if ($search_common === 'firm') {
			$add_query = "WHERE `firm` RLIKE '" . addslashes($search_for) . "' %s";
		} else if ($search_common === 'city') {
			$add_query = "WHERE `city` RLIKE '" . addslashes($search_for) . "' %s";
		} else if ($search_common === 'state') {
			$add_query = "WHERE `state` RLIKE '" . addslashes($search_for) . "' %s";
		} else if ($search_common === 'country') {
			$add_query = "WHERE `country` RLIKE '" . addslashes($search_for) . "' %s";
		}

		if ($search_status != 'all') {
			$add_query = sprintf($add_query, " AND t1.`created_by` = '$reseller_id' AND t2.`domain_status` = '$search_status'");
			$count_query = <<<SQL_QUERY
				SELECT
					COUNT(`admin_id`) AS cnt
				FROM
					`admin` AS t1,
					`domain` AS t2
				$add_query
				AND
					t1.`admin_id` = t2.`domain_admin_id`
SQL_QUERY;
		} else {
			$add_query = sprintf($add_query, " AND `created_by` = '$reseller_id'");
			$count_query = <<<SQL_QUERY
				SELECT
					COUNT(`admin_id`) AS cnt
				FROM
					`admin`
				$add_query
SQL_QUERY;
		}

		$search_query = <<<SQL_QUERY
			SELECT
				t1.`admin_id`, t2.*
			FROM
				`admin` AS t1,
				`domain` AS t2
			$add_query
			AND
				t1.`admin_id` = t2.`domain_admin_id`
			ORDER BY
				t2.`domain_name` ASC
			LIMIT
				$start_index, $rows_per_page
SQL_QUERY;
	}
}

function gen_manage_domain_search_options(&$tpl,
	$search_for,
	$search_common,
	$search_status) {
	if ($search_for === 'n/a' && $search_common === 'n/a'
		&& $search_status === 'n/a') {
		// we have no search and let's genarate search fields empty
		$domain_selected = 'selected="selected"';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';

		$all_selected = 'selected="selected"';
		$ok_selected = '';
		$suspended_selected = '';
	}
	if ($search_common === 'domain_name') {
		$domain_selected = 'selected="selected"';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'customer_id') {
		$domain_selected = '';
		$customerid_selected = 'selected="selected"';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'lname') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = 'selected="selected"';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'firm') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = 'selected="selected"';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'city') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = 'selected="selected"';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'state') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = 'selected="selected"';
		$country_selected = '';
	} else if ($search_common === 'country') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = 'selected="selected"';
	}
	if ($search_status === 'all') {
		$all_selected = 'selected="selected"';
		$ok_selected = '';
		$suspended_selected = '';
	} else if ($search_status === 'ok') {
		$all_selected = '';
		$ok_selected = 'selected="selected"';
		$suspended_selected = '';
	} else if ($search_status === 'disabled') {
		$all_selected = '';
		$ok_selected = '';
		$suspended_selected = 'selected="selected"';
	}

	if ($search_for === "n/a" || $search_for === '') {
		$tpl->assign(
			array('SEARCH_FOR' => "")
		);
	} else {
		$tpl->assign(
			array('SEARCH_FOR' => stripslashes($search_for))
		);
	}

	$tpl->assign(
		array(
			'M_DOMAIN_NAME' => tr('Domain name'),
			'M_CUSTOMER_ID' => tr('Customer ID'),
			'M_LAST_NAME' => tr('Last name'),
			'M_COMPANY' => tr('Company'),
			'M_CITY' => tr('City'),
			'M_STATE' => tr('State/Province'),
			'M_COUNTRY' => tr('Country'),

			'M_ALL' => tr('All'),
			'M_OK' => tr('OK'),
			'M_SUSPENDED' => tr('Suspended'),
			'M_ERROR' => tr('Error'),
			// selected area
			'M_DOMAIN_NAME_SELECTED' => $domain_selected,
			'M_CUSTOMER_ID_SELECTED' => $customerid_selected,
			'M_LAST_NAME_SELECTED' => $lastname_selected,
			'M_COMPANY_SELECTED' => $company_selected,
			'M_CITY_SELECTED' => $city_selected,
			'M_STATE_SELECTED' => $state_selected,
			'M_COUNTRY_SELECTED' => $country_selected,

			'M_ALL_SELECTED' => $all_selected,
			'M_OK_SELECTED' => $ok_selected,
			'M_SUSPENDED_SELECTED' => $suspended_selected,
		)
	);
}

/**
 * @todo implement use of more secure dynamic table in SQL query
 */
function gen_def_language(&$tpl, &$sql, &$user_def_language) {
	$matches = array();
	$languages = array();
	$query = "SHOW TABLES";

	$rs = exec_query($sql, $query, array());

	while (!$rs->EOF) {
		$lang_table = $rs->fields[0];

		if (preg_match("/lang_([A-Za-z0-9][A-Za-z0-9]+)/", $lang_table, $matches)) {
			$query = <<<SQL_QUERY
				SELECT
					`msgstr`
				FROM
					$lang_table
				WHERE
					`msgid` = 'ispcp_language'
SQL_QUERY;

			$res2 = exec_query($sql, $query, array());

			$query = <<<SQL_QUERY
				SELECT
					`msgstr`
				FROM
					$lang_table
				WHERE
					`msgid` = 'ispcp_languageSetlocaleValue'
SQL_QUERY;

			$res3 = exec_query($sql, $query, array());

			if ($res2->RecordCount() == 0 || $res3->RecordCount() == 0) {
				$language_name = tr('Unknown');
			} else {
				$tr_langcode = tr($res3->fields['msgstr']);
				if ($res3->fields['msgstr'] == $tr_langcode) { // no translation found
					$language_name = $res2->fields['msgstr'];
				} else { // found translation
					$language_name = $tr_langcode;
				}
			}

			$selected = ($matches[0] === $user_def_language) ? 'selected="selected"' : '';

			array_push($languages, array($matches[0], $selected, $language_name));
		}

		$rs->MoveNext();
	}

	asort($languages[0], SORT_STRING);
	foreach ($languages as $lang) {
		$tpl->assign(
			array(
				'LANG_VALUE'	=> $lang[0],
				'LANG_SELECTED' => $lang[1],
				'LANG_NAME'		=> $lang[2]
			)
		);
		$tpl->parse('DEF_LANGUAGE', '.def_language');
	}
}

function gen_domain_details(&$tpl, &$sql, $domain_id) {
	$tpl->assign('USER_DETAILS', '');

	if (isset($_SESSION['details']) && $_SESSION['details'] == 'hide') {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('view aliases'),
				'SHOW_DETAILS' => "show",
			)
		);

		return;
	} else if (isset($_SESSION['details']) && $_SESSION['details'] === "show") {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('hide aliases'),
				'SHOW_DETAILS' => "hide",
			)
		);

		$alias_query = <<<SQL_QUERY
			SELECT
				`alias_id`, `alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = ?
			ORDER BY
				`alias_id` DESC
SQL_QUERY;
		$alias_rs = exec_query($sql, $alias_query, array($domain_id));

		if ($alias_rs->RecordCount() == 0) {
			$tpl->assign('USER_DETAILS', '');
		} else {
			while (!$alias_rs->EOF) {
				$alias_name = $alias_rs->fields['alias_name'];

				$tpl->assign('ALIAS_DOMAIN', decode_idna($alias_name));
				$tpl->parse('USER_DETAILS', '.user_details');

				$alias_rs->MoveNext();
			}
		}
	} else {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('view aliases'),
				'SHOW_DETAILS' => "show",
			)
		);

		return;
	}
}

function add_domain_extras(&$dmn_id, &$admin_id, &$sql) {
	$query = <<<SQL_QUERY
		INSERT INTO `domain_extras`
			(`dmn_id`,
			`admin_id`,
			`frontpage`,
			`htaccess`,
			`supportsystem`,
			`backup`,
			`errorpages`,
			`webmail`,
			`filemanager`,
			`installer`)
		VALUES
			(?,
			?,
			'0',
			'1',
			'1',
			'1',
			'1',
			'1',
			'1',
			'0')
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $admin_id));
}

function reseller_limits_check(&$sql, &$err_msg, $reseller_id, $hpid, $newprops = "") {
	$error = false;

	if (empty($newprops)) {
		// this hosting plan exists
		if (isset($_SESSION["ch_hpprops"])) {
			$props = $_SESSION["ch_hpprops"];
		} else {
			$query = <<<SQL_QUERY
				SELECT
					`props`
				FROM
					`hosting_plans`
				WHERE
					`id` = ?
SQL_QUERY;

			$res = exec_query($sql, $query, array($hpid));
			$data = $res->FetchRow();
			$props = $data['props'];
		}
	} else {
		// we want to check _before_ inserting
		$props = $newprops;
	}

	list($php_new, $cgi_new, $sub_new,
		$als_new, $mail_new, $ftp_new,
		$sql_db_new, $sql_user_new,
		$traff_new, $disk_new) = explode(";", $props);

	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
SQL_QUERY;

	$res = exec_query($sql, $query, array($reseller_id));
	$data = $res->FetchRow();
	$dmn_current = $data['current_dmn_cnt'];
	$dmn_max = $data['max_dmn_cnt'];

	$sub_current = $data['current_sub_cnt'];
	$sub_max = $data['max_sub_cnt'];

	$als_current = $data['current_als_cnt'];
	$als_max = $data['max_als_cnt'];

	$mail_current = $data['current_mail_cnt'];
	$mail_max = $data['max_mail_cnt'];

	$ftp_current = $data['current_ftp_cnt'];
	$ftp_max = $data['max_ftp_cnt'];

	$sql_db_current = $data['current_sql_db_cnt'];
	$sql_db_max = $data['max_sql_db_cnt'];

	$sql_user_current = $data['current_sql_user_cnt'];
	$sql_user_max = $data['max_sql_user_cnt'];

	$traff_current = $data['current_traff_amnt'];
	$traff_max = $data['max_traff_amnt'];

	$disk_current = $data['current_disk_amnt'];
	$disk_max = $data['max_disk_amnt'];

	if ($dmn_max != 0) {
		if ($dmn_current + 1 > $dmn_max) {
			set_page_message(tr('You have reached your domains limit.<br>You cannot add more domains!'));
			$error = true;
		}
	}

	if ($sub_max != 0) {
		if ($sub_new != -1) {
			if ($sub_new == 0) {
				set_page_message(tr('You have a subdomains limit!<br>You cannot add an user with unlimited subdomains!'));
				$error = true;
			} else if ($sub_current + $sub_new > $sub_max) {
				set_page_message(tr('You are exceeding your subdomains limit!'));
				$error = true;
			}
		}
	}

	if ($als_max != 0) {
		if ($als_new != -1) {
			if ($als_new == 0) {
				set_page_message(tr('You have an aliases limit!<br>You cannot add an user with unlimited aliases!'));
				$error = true;
			} else if ($als_current + $als_new > $als_max) {
				set_page_message(tr('You Are Exceeding Your Alias Limit!'));
				$error = true;
			}
		}
	}

	if ($mail_max != 0) {
		if ($mail_new == 0) {
			set_page_message(tr('You have a mail accounts limit!<br>You cannot add an user with unlimited mail accounts!'));
			$error = true;
		} else if ($mail_current + $mail_new > $mail_max) {
			set_page_message(tr('You are exceeding your mail accounts limit!'));
		}
	}

	if ($ftp_max != 0) {
		if ($ftp_new == 0) {
			set_page_message(tr('You have a FTP accounts limit!<br>You cannot add an user with unlimited FTP accounts!'));
			$error = true;
		} else if ($ftp_current + $ftp_new > $ftp_max) {
			set_page_message(tr('You are exceeding your FTP accounts limit!'));
			$error = true;
		}
	}

	if ($sql_db_max != 0) {
		if ($sql_db_new != -1) {
			if ($sql_db_new == 0) {
				set_page_message(tr('You have a SQL databases limit!<br>You cannot add an user with unlimited SQL databases!'));
				$error = true;
			} else if ($sql_db_current + $sql_db_new > $sql_db_max) {
				set_page_message(tr('You are exceeding your SQL databases limit!'));
				$error = true;
			}
		}
	}

	if ($sql_user_max != 0) {
		if ($sql_user_new != -1) {
			if ($sql_user_new == 0) {
				set_page_message(tr('You have an SQL users limit!<br>You cannot add an user with unlimited SQL users!'));
				$error = true;
			} else if ($sql_db_new == -1) {
				set_page_message(tr('You have disabled SQL databases for this user!<br>You cannot have SQL users here!'));
				$error = true;
			} else if ($sql_user_current + $sql_user_new > $sql_user_max) {
				set_page_message(tr('You are exceeding your SQL database limit!'));
				$error = true;
			}
		}
	}

	if ($traff_max != 0) {
		if ($traff_new == 0) {
			set_page_message(tr('You have a traffic limit!<br>You cannot add an user with unlimited traffic!'));
			$error = true;
		} else if ($traff_current + $traff_new > $traff_max) {
			set_page_message(tr('You are exceeding your traffic limit!'));
			$error = true;
		}
	}

	if ($disk_max != 0) {
		if ($disk_new == 0) {
			set_page_message(tr('You have a disk limit!<br>You cannot add an user with unlimited disk!'));
			$error = true;
		} else if ($disk_current + $disk_new > $disk_max) {
			set_page_message(tr('You are exceeding your disk limit!'));
			$error = true;
		}
	}

	if ($error == true) {
		return false;
	}

	return true;
}


function send_order_emails($admin_id, $domain_name, $ufname, $ulname, $uemail, $order_id) {
	$data = get_order_email($admin_id);

	$from_name = $data['sender_name'];
	$from_email = $data['sender_email'];
	$subject = $data['subject'];
	$message = $data['message'];

	if ($from_name) {
		$from = '"' . encode($from_name) . "\" <" . $from_email . ">";
	} else {
		$from = $from_email;
	}

	if ($ufname && $ulname) {
		$name = "$ufname $ulname";
		$to = '"' . encode($name) . "\" <" . $uemail . ">";
	} else {
		if ($ufname) {
			$name = $ufname;
		} else if ($ulname) {
			$name = $ulname;
		} else {
			$name = $uemail;
		}
		$to = $uemail;
	}

	$activate_link = Config::get('BASE_SERVER_VHOST_PREFIX').Config::get('BASE_SERVER_VHOST');
	$coid = Config::exists('CUSTOM_ORDERPANEL_ID') ? Config::get('CUSTOM_ORDERPANEL_ID'): '';
	$key = sha1($order_id.'-'.$domain_name.'-'.$admin_id.'-'.$coid);
	$activate_link .= '/orderpanel/activate.php?id='.$order_id.'&k='.$key;

	$search = array();
	$replace = array();

	$search [] = '{DOMAIN}';
	$replace[] = $domain_name;
	$search [] = '{MAIL}';
	$replace[] = $uemail;
	$search [] = '{NAME}';
	$replace[] = $name;
	$search [] = '{ACTIVATE_LINK}';
	$replace[] = $activate_link;

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);
	$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
	$subject = encode($subject);

	$headers = "From: ". $from . "\n";
	$headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" . "Content-Transfer-Encoding: 8bit\n" . "X-Mailer: ispCP " . Config::get('Version') . " Service Mailer";

	mail($to, $subject, $message, $headers);
}

function send_alias_order_email($alias_name) {
	$sql = Database::getInstance();

	$user_id = $_SESSION['user_id'];

	$reseller_id = who_owns_this($user_id, 'user');

	$query = 'SELECT `fname`, `lname` FROM `admin` WHERE `admin_id` = ?';
	$rs = exec_query($sql, $query, $user_id);
	$ufname = $rs->fields['fname'];
	$ulname = $rs->fields['lname'];
	$uemail = $_SESSION['user_email'];

	$data = get_alias_order_email($reseller_id);
	$to_name = $data['sender_name'];
	$to_email = $data['sender_email'];
	$subject = $data['subject'];
	$message = $data['message'];

	// to
	$to = ($to_name) ? '"' . encode($to_name) . "\" <" . $to_email . ">" : $to_email;

	// from
	if ($ufname && $ulname) {
		$from_name = "$ufname $ulname";
		$from = '"' . encode($from_name) . "\" <" . $uemail . ">";
	} else {
		if ($ufname) {
			$from_name = $ufname;
		} else if ($ulname) {
			$from_name = $ulname;
		} else {
			$from_name = $uemail;
		}
		$from = $uemail;
	}
	$search = array();
	$replace = array();

	$search [] = '{RESELLER}';
	$replace[] = $to_name;
	$search [] = '{CUSTOMER}';
	$replace[] = $from_name;
	$search [] = '{ALIAS}';
	$replace[] = $alias_name;
	$search [] = '{BASE_SERVER_VHOST}';
	$replace[] = Config::get('BASE_SERVER_VHOST');
	$search [] = '{BASE_SERVER_VHOST_PREFIX}';
	$replace[] = Config::get('BASE_SERVER_VHOST_PREFIX');

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$subject = encode($subject);

	$headers = "From: ". $from ."\n";
	$headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" . "Content-Transfer-Encoding: 8bit\n" . "X-Mailer: ispCP " . Config::get('Version') . " Service Mailer";

	$mail_result = mail($to, $subject, $message, $headers);

	$mail_status = ($mail_result) ? 'OK' : 'NOT OK';

}

/**
 * add the 3 mail accounts/forwardings to a new domain...
 */
function client_mail_add_default_accounts($dmn_id, $user_email, $dmn_part, $dmn_type = 'domain', $sub_id = 0) {

	$sql = Database::getInstance();

	if (Config::get('CREATE_DEFAULT_EMAIL_ADDRESSES')) {

		$forward_type = ($dmn_type == 'alias') ? 'alias_forward' : 'normal_forward';

		// prepare SQL
		$query = <<<SQL_QUERY
	INSERT INTO mail_users
		(`mail_acc`,
		 `mail_pass`,
		 `mail_forward`,
		 `domain_id`,
		 `mail_type`,
		 `sub_id`,
		 `status`,
		 `mail_auto_respond`,
		 `quota`,
		 `mail_addr`)
	VALUES
		(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

		// create default forwarder for webmaster@domain.tld to the account's owner
		$rs = exec_query($sql, $query,
			array('webmaster',
				'_no_',
				$user_email,
				$dmn_id,
				$forward_type,
				$sub_id,
				Config::get('ITEM_ADD_STATUS'),
				'_no_',
				10485760,
				'webmaster@'.$dmn_part
			)
		);

		// create default forwarder for postmaster@domain.tld to the account's reseller
		$rs = exec_query($sql, $query,
			array('postmaster',
				'_no_',
				$_SESSION['user_email'],
				$dmn_id,
				$forward_type,
				$sub_id,
				Config::get('ITEM_ADD_STATUS'),
				'_no_',
				10485760,
				'postmaster@'.$dmn_part
			)
		);

		// create default forwarder for abuse@domain.tld to the account's reseller
		$rs = exec_query($sql, $query,
			array('abuse',
				'_no_',
				$_SESSION['user_email'],
				$dmn_id,
				$forward_type,
				$sub_id,
				Config::get('ITEM_ADD_STATUS'),
				'_no_',
				10485760,
				'abuse@'.$dmn_part
			)
		);

	}

} // end client_mail_add_default_accounts

/**
 * Get count from table by given domain_id's 
 * @param $tablename string database table name
 * @param $ua array domain_ids
 * @return integer count
 */
function get_reseller_detail_count($tablename, $ua) {
	global $sql;
	
	$delstatus = Config::get('ITEM_DELETE_STATUS');
	
	$query = "SELECT COUNT(*) AS cnt FROM `".$tablename;
	if ($tablename == 'ftp_users') {
		$fieldname = 'uid';
	} else {
		$fieldname = 'domain_id';
	}
	$query .= "` WHERE `".$fieldname."` IN (".implode(',', $ua).")";
	if ($tablename == 'mail_users') {
		$query .= " AND `mail_acc` != 'abuse'
			AND `mail_acc` != 'postmaster'
			AND `mail_acc` != 'webmaster'
			AND `mail_type` NOT RLIKE '_catchall'";
		$query .= " AND status != '".$delstatus."'";
	} else if ($tablename == 'subdomain') {
		$query .= " AND subdomain_status != '".$delstatus."'";
	} else if ($tablename == 'domain_aliasses') {
		$query .= " AND alias_status != '".$delstatus."'";
	}
	$res = exec_query($sql, $query);

	return $res->fields['cnt'];
}

/**
 * Recalculate current_ properties of reseller
 * @param $reseller_id integer ID of the reseller
 * @return array list of properties
 */
function recalc_reseller_c_props($reseller_id) {
	global $sql;

	// current_dmn_cnt = domain
	// current_sub_cnt = subdomain
	// current_als_cnt = domain_aliasses
	// current_mail_cnt = mail_users
	// current_ftp_cnt = ftp_users
	// current_sql_db_cnt = sql_database
	// current_sql_user_cnt = sql_user

	$delstatus = Config::get('ITEM_DELETE_STATUS');

	// Get all users of reseller:
	$query = <<<SQL_QUERY
		SELECT 
			`domain_id`, `domain_uid`
		FROM
			`domain`
		WHERE
			`domain_created_id` = ?
			AND `domain_status` != ?
SQL_QUERY;
	$res = exec_query($sql, $query, array($reseller_id, $delstatus));
	$user_array = $systemuser_array = array();
	while ($data = $res->FetchRow()) {
		$user_array[] = $data['domain_id'];
		$systemuser_array[] = $data['domain_uid'];
	}
	$current_dmn_cnt = count($user_array);
	if ($current_dmn_cnt > 0) {
		$current_sub_cnt = get_reseller_detail_count('subdomain', $user_array);
		$current_als_cnt = get_reseller_detail_count('domain_aliasses', $user_array);
		$current_mail_cnt = get_reseller_detail_count('mail_users', $user_array);
		$current_ftp_cnt = get_reseller_detail_count('ftp_users', $systemuser_array);
		$current_sql_db_cnt = get_reseller_detail_count('sql_database', $user_array);
	
		$query = "SELECT COUNT(*) AS cnt FROM `sql_user`";
		$query .= " WHERE `sqld_id` IN (";
		$query .= "SELECT sqld_id FROM sql_database";
		$query .= " WHERE `domain_id` IN (".implode(',', $user_array)."))";
		$res = exec_query($sql, $query);
		$current_sql_user_cnt = $res->fields['cnt'];
	} else {
		$current_sub_cnt = 
		$current_als_cnt =
		$current_mail_cnt =
		$current_ftp_cnt =
		$current_sql_db_cnt = 
		$current_sql_user_cnt = 0; 
	}

	return array($current_dmn_cnt, $current_sub_cnt, $current_als_cnt, 
		$current_mail_cnt, $current_ftp_cnt, $current_sql_db_cnt, 
		$current_sql_user_cnt);
}

/**
 * Recalculate current_ properties of reseller
 * @param $reseller_id integer ID of the reseller
 */
function update_reseller_c_props($reseller_id) {
	global $sql;

	$query = <<<SQL_QUERY
		UPDATE
			`reseller_props`
		SET
			`current_dmn_cnt` = ?,
			`current_sub_cnt` = ?,
			`current_als_cnt` = ?,
			`current_mail_cnt` = ?,
			`current_ftp_cnt` = ?,
			`current_sql_db_cnt` = ?,
			`current_sql_user_cnt` = ?
		WHERE
			`reseller_id` = ?
SQL_QUERY;

	$props = recalc_reseller_c_props($reseller_id);
	$props[] = $reseller_id;

	exec_query($sql, $query, $props);
}

/**
 * Get the reseller id of a domain
 * moved from admin/domain_edit.php to reseller-functions.php
 * @param $domain_id integer ID of domain
 * @return integer ID of reseller or 0 in case of error
 */
function get_reseller_id($domain_id) {
	$sql = Database::getInstance();

	$query = "
	SELECT
		a.`created_by`
	FROM
		`domain` d, `admin` a
	WHERE
		d.`domain_id` = ?
	AND
		d.`domain_admin_id` = a.`admin_id`
";

	$rs = exec_query($sql, $query, array($domain_id));

	if ($rs->RecordCount() == 0) {
		return 0;
	}

	$data = $rs->FetchRow();
	return $data['created_by'];
}
