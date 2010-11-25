<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

// avaiable mail types
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

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

	$tpl->define_dynamic('menu', $menu_file);
	$tpl->define_dynamic('isactive_support', 'menu');
	$tpl->define_dynamic('custom_buttons', 'menu');
	$tpl->define_dynamic('t_software_menu', 'menu');

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
			'SUPPORT_SYSTEM_PATH' => $cfg->IMSCP_SUPPORT_SYSTEM_PATH,
			'SUPPORT_SYSTEM_TARGET' => $cfg->IMSCP_SUPPORT_SYSTEM_TARGET,
			'TR_MENU_ORDERS' => tr('Manage Orders'),
			'TR_MENU_ORDER_SETTINGS' => tr('Order settings'),
			'TR_MENU_ORDER_EMAIL' => tr('Order email setup'),
			'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup')
		)
	);

	$query = "
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'reseller'
		OR
			`menu_level` = 'all'
	";

	$rs = exec_query($sql, $query);
	if ($rs->recordCount() == 0) {
		$tpl->assign('CUSTOM_BUTTONS', '');
	} else {
		global $i;
		$i = 100;

		while (!$rs->EOF) {
			$menu_name = $rs->fields['menu_name'];
			$menu_link = get_menu_vars($rs->fields['menu_link']);
			$menu_target = $rs->fields['menu_target'];

			if ($menu_target !== "") {
				$menu_target = 'target="' . tohtml($menu_target) . '"';
			}

			$tpl->assign(
				array(
					'BUTTON_LINK' => tohtml($menu_link),
					'BUTTON_NAME' => tohtml($menu_name),
					'BUTTON_TARGET' => $menu_target,
					'BUTTON_ID' => $i,
				)
			);

			$tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
			$rs->moveNext();
			$i++;
		} // end while
	} // end else
	$query = "
	SELECT
		`support_system`
	FROM
		`reseller_props`
	WHERE
		`reseller_id` = ?
	";

	$rs = exec_query($sql, $query, $_SESSION['user_id']);

	if (!$cfg->IMSCP_SUPPORT_SYSTEM || $rs->fields['support_system'] == 'no') {
		$tpl->assign('ISACTIVE_SUPPORT', '');
 	}

	$tpl->parse('MAIN_MENU', 'menu');
} // end of gen_reseller_menu()

/**
 * Function to generate the menu data for reseller
 */
function gen_reseller_menu(&$tpl, $menu_file) {

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

	$tpl->define_dynamic('menu', $menu_file);

	$tpl->define_dynamic('custom_buttons', 'menu');
	$tpl->define_dynamic('alias_menu', 'page');

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
			'ALIAS_MENU' => (!check_reseller_permissions($_SESSION['user_id'], 'alias'))
				? '' : $tpl->parse('ALIAS_MENU', '.alias_menu'),
			'SUPPORT_SYSTEM_PATH' => $cfg->IMSCP_SUPPORT_SYSTEM_PATH,
			'SUPPORT_SYSTEM_TARGET' => $cfg->IMSCP_SUPPORT_SYSTEM_TARGET,
			'TR_MENU_ORDERS' => tr('Manage Orders'),
			'TR_MENU_ORDER_SETTINGS' => tr('Order settings'),
			'TR_MENU_ORDER_EMAIL' => tr('Order email setup'),
			'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup'),
			'TR_MENU_IP_USAGE' => tr('IP usage'),
			'TR_SOFTWARE_MENU' => tr('Application management'),
			'VERSION' => $cfg->Version,
			'BUILDDATE' => $cfg->BuildDate,
			'CODENAME' => $cfg->CodeName
		)
	);

	$query = "
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'reseller'
		OR
			`menu_level` = 'all'
	";

	$rs = exec_query($sql, $query);
	if ($rs->recordCount() == 0) {
		$tpl->assign('CUSTOM_BUTTONS', '');
	} else {
		global $i;
		$i = 100;

		while (!$rs->EOF) {
			$menu_name = $rs->fields['menu_name'];
			$menu_link = get_menu_vars($rs->fields['menu_link']);
			$menu_target = $rs->fields['menu_target'];

			if ($menu_target !== "") {
				$menu_target = 'target="' . tohtml($menu_target) . '"';
			}

			$tpl->assign(
				array(
					'BUTTON_LINK' => tohtml($menu_link),
					'BUTTON_NAME' => tohtml($menu_name),
					'BUTTON_TARGET' => $menu_target,
					'BUTTON_ID' => $i,
				)
			);

			$tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
			$rs->moveNext();
			$i++;
		} // end while
	} // end else
	$query = "
	SELECT
		`support_system`
	FROM
		`reseller_props`
	WHERE
		`reseller_id` = ?
	";

	$rs = exec_query($sql, $query, $_SESSION['user_id']);

	if (!$cfg->IMSCP_SUPPORT_SYSTEM || $rs->fields['support_system'] == 'no') {
		$tpl->assign('ISACTIVE_SUPPORT', '');
	}
	if (isset($cfg->HOSTING_PLANS_LEVEL) && strtolower($cfg->HOSTING_PLANS_LEVEL) === 'admin') {
		$tpl->assign('HP_MENU_ADD', '');
	}
	
	$query = "
		SELECT
			`software_allowed`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";
	$rs = exec_query($sql, $query, $_SESSION['user_id']);
	if ($rs->fields('software_allowed') == 'yes') {
		$tpl->assign(array('SOFTWARE_MENU' => tr('yes')));
		$tpl->parse('T_SOFTWARE_MENU', '.t_software_menu');
	} else {
		$tpl->assign('T_SOFTWARE_MENU', '');
	}

	$tpl->parse('MENU', 'menu');
} // end of gen_reseller_menu()

/**
 * Get data for page of reseller
 */
function get_reseller_default_props(&$sql, $reseller_id) {
	// Make sql query
	$query = "
		SELECT
			*
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";
	// send sql query
	$rs = exec_query($sql, $query, $reseller_id);

	if (0 == $rs->rowCount()) {
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
		$rs->fields['max_disk_amnt'],
		$rs->fields['software_allowed']
	);
} // end of get_reseller_default_props()

/**
 * Making users props
 */
function generate_reseller_user_props($reseller_id) {
	$sql = iMSCP_Registry::get('Db');
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

	$query = "
		SELECT
			`admin_id`
		FROM
			`admin`
		WHERE
			`created_by` = ?
	";

	$res = exec_query($sql, $query, $reseller_id);

	if ($res->rowCount() == 0) {
		return array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}
	// Process all users of this group
	while ($data = $res->fetchRow()) {
		$admin_id = $data['admin_id'];

		$query = "
			SELECT
				`domain_id`
			FROM
				`domain`
			WHERE
				`domain_admin_id` = ?
		";

		$dres = exec_query($sql, $query, $admin_id);

		$ddata = $dres->fetchRow();

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

		// We always have to count aliases, because a reseller can add aliase for an user wheter the alias function for the user is disabled - TheCry
		/*if ($als_max != -1) {
			if ($als_max == 0) $rals_uf = '_on_';

			$rals_current += $als_current;
			$rals_max += $als_max;
		}*/
		if ($als_max == 0) $rals_uf = '_on_';

		$rals_current += $als_current;
		$rals_max += $als_max;


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

	$sql = iMSCP_Registry::get('Db');
	global $crnt_month, $crnt_year;

	$query = "
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
	";

	$res = exec_query($sql, $query, $user_id);

	if ($res->rowCount() == 0 || $res->rowCount() > 1) {
		// write_log("TRAFFIC WARNING: >$user_id< manages incorrect number of
		// domains >".$res->RowCount()."<");
		return array('n/a', 0, 0, 0, 0, 0, 0, 0, 0, 0);
	} else {
		$data = $res->fetchRow();

		$domain_id = $data['domain_id'];

		$domain_disk_usage = $data['domain_disk_usage'];

		$domain_traff_limit = $data['domain_traffic_limit'];

		$domain_disk_limit = $data['domain_disk_limit'];

		$domain_name = $data['domain_name'];

		$query = "
			SELECT
				YEAR(FROM_UNIXTIME(`dtraff_time`)) AS `tyear`,
				MONTH(FROM_UNIXTIME(`dtraff_time`)) AS `tmonth`,
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
			GROUP BY
				`tyear`, `tmonth`
		";

		$res = exec_query($sql, $query, $domain_id);

		$max_traffic_month =
		$data['web'] = $data['ftp'] = $data['smtp'] =
		$data['pop'] = $data['total'] = 0;

		while ($row = $res->fetchRow()) {
			$data['web'] += $row['web'];
			$data['ftp'] += $row['ftp'];
			$data['smtp'] += $row['smtp'];
			$data['pop'] += $row['total'];
			if ($row['total'] > $max_traffic_month) {
				$max_traffic_month = $row['total'];
			}
		}

		return array($domain_name,
			$domain_id,
			$data['web'],
			$data['ftp'],
			$data['smtp'],
			$data['pop'],
			$data['total'],
			$domain_disk_usage,
			$domain_traff_limit,
			$domain_disk_limit,
			$max_traffic_month
		);
	}
} // end of get_user_traffic()

/**
 * Get user's properties from Database
 *
 * @param int		$user_id	user's ID
 * @return Array				user's properies
 */
function get_user_props($user_id) {

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

	$query = "
		SELECT
			*
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$res = exec_query($sql, $query, $user_id);

	if ($res->rowCount() == 0) {
		return array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}

	$data = $res->fetchRow();

	$sub_current = get_domain_running_sub_cnt($sql, $user_id);
	$sub_max = $data['domain_subd_limit'];

	$als_current = records_count('domain_aliasses', 'domain_id', $user_id);
	$als_max = $data['domain_alias_limit'];

	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
		// Catch all is not a mailbox and haven't to be count
		$mail_current = records_count('mail_users', 'mail_type NOT RLIKE \'_catchall\' AND domain_id', $user_id);
	} else {
		$where = "`mail_acc` != 'abuse'
		AND `mail_acc` != 'postmaster'
		AND `mail_acc` != 'webmaster'
		AND `mail_type` NOT RLIKE '_catchall'
		AND `domain_id`";
		$mail_current = records_count('mail_users', $where, $user_id);
	}
	$mail_max = $data['domain_mailacc_limit'];

	$ftp_current = sub_records_rlike_count('domain_name', 'domain', 'domain_id', $user_id,
		'userid', 'ftp_users', 'userid', '@', ''
		);

	//We don't need this query, because we don't have ftpusers for a subdomain! Otherwise the counters count wrong - TheCry
	/*$ftp_current += sub_records_rlike_count('subdomain_name', 'subdomain', 'domain_id', $user_id,
		'userid', 'ftp_users', 'userid', '@', ''
		);*/

	$ftp_current += sub_records_rlike_count('alias_name', 'domain_aliasses', 'domain_id', $user_id,
		'userid', 'ftp_users', 'userid', '@', ''
		);

	$ftp_max = $data['domain_ftpacc_limit'];

	$sql_db_current = records_count('sql_database', 'domain_id', $user_id);
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

/**
 * Generate IP list
 */
function generate_ip_list(&$tpl, &$reseller_id) {

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');
	global $domain_ip;

	$query = "
		SELECT
			`reseller_ips`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";

	$res = exec_query($sql, $query, $reseller_id);

	$data = $res->fetchRow();

	$reseller_ips = $data['reseller_ips'];

	$query = "SELECT * FROM `server_ips`";

	$res = exec_query($sql, $query);

	while ($data = $res->fetchRow()) {
		$ip_id = $data['ip_id'];

		if (preg_match("/$ip_id;/", $reseller_ips) == 1) {
			$selected = ($domain_ip === $ip_id) ? $cfg->HTML_SELECTED : '';

			$tpl->assign(
				array(
					'IP_NUM' => $data['ip_number'],
					'IP_NAME' => tohtml($data['ip_domain']),
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

	$cfg = iMSCP_Registry::get('Config');

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
			if ($cfg->PASSWD_STRONG) {
				$user_add_error = sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS);
			} else {
				$user_add_error = sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS);
			}
		}
	}

	if ($user_email == NULL) {
		$user_add_error = tr('Incorrect email length or syntax!');
	}
	/* we don't want to validate Customer ID, First and Second name and also ZIP

	  else if (!imscp_limit_check($customer_id)) {
		$user_add_error = tr('Incorrect customer ID syntax!');
	} else if (!chk_username($first_name, 40)) {

		$user_add_error = tr('Incorrect first name length or syntax!');
	} else if (!chk_username($last_name, 40)) {

		$user_add_error = tr('Incorrect second name length or syntax!');
	} else if (!imscp_limit_check($zip)) {

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

	$cfg = iMSCP_Registry::get('Config');

	switch ($status) {
		case $cfg->ITEM_OK_STATUS:
			return tr('OK');
		case $cfg->ITEM_ADD_STATUS:
			return tr('Addition in progress');
		case $cfg->ITEM_CHANGE_STATUS:
			return tr('Modification in progress');
		case $cfg->ITEM_DELETE_STATUS:
			return tr('Deletion in progress');
		case $cfg->ITEM_DISABLED_STATUS:
			return tr('Suspended');
		case $cfg->ITEM_TOENABLE_STATUS:
			return tr('Being enabled');
		case $cfg->ITEM_TODISABLED_STATUS:
			return tr('Being suspended');
		case $cfg->ITEM_ORDERED_STATUS:
			return tr('Awaiting approval');
		default:
			return tr('Unknown error');
	}
} // end of translate_dmn_status()

/**
 * Check if the domain already exist
 */
function imscp_domain_exists($domain_name, $reseller_id) {
	$sql = iMSCP_Registry::get('Db');
	// query to check if the domain name exist in the table for domains/accounts
	$query_domain = "
		SELECT
			COUNT(`domain_id`) AS cnt
		FROM
			`domain`
		WHERE
			`domain_name` = ?
	";

	$res_domain = exec_query($sql, $query_domain, $domain_name);
	// query to check if the domain name exists in the table for domain aliases
	$query_alias = "
		SELECT
			COUNT(t1.`alias_id`) AS cnt
		FROM
			`domain_aliasses` AS t1, `domain` AS t2
		WHERE
			t1.`domain_id` = t2.`domain_id`
		AND
			t1.`alias_name` = ?
	";

	$res_aliases = exec_query($sql, $query_alias, $domain_name);
	// redefine query to check in the table domain/acounts if 3rd level for this reseller is allowed
	$query_domain = "
		SELECT
			COUNT(`domain_id`) AS cnt
		FROM
			`domain`
		WHERE
			`domain_name` = ?
		AND
			`domain_created_id` <> ?
	";
	// redefine query to check in the table aliases if 3rd level for this reseller is allowed
	$query_alias = "
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
	";
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
	$query_build_subdomain = "
		SELECT
			t1.`subdomain_name`, t2.`domain_name`
		FROM
			`subdomain` AS t1, `domain` AS t2
		WHERE
			t1.`domain_id` = t2.`domain_id`
		AND
			t2.`domain_created_id` = ?
	";

	$subdomains = array();
	$res_build_sub = exec_query($sql, $query_build_subdomain, $reseller_id);
	while (!$res_build_sub->EOF) {
		$subdomains[] = $res_build_sub->fields['subdomain_name'] . "." . $res_build_sub->fields['domain_name'];
		$res_build_sub->moveNext();
	}

	if ($res_domain->fields['cnt'] == 0 && $res_aliases->fields['cnt'] == 0 && !in_array($domain_name, $subdomains)) {
		return false;
	} else {
		return true;
	}
} // end of iMSCP_domain_exists()

/**
 * @todo see inline comment, about the messed up code
 * @todo use db prepared statements (min. with placeholders like ":reseller_id")
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
		$count_query = "
			SELECT
				COUNT(`domain_id`) AS cnt
			FROM
				`domain`
			WHERE
				`domain_created_id` = '$reseller_id'
		";

		$search_query = "
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
		";
	} else if ($search_for === '' && $search_status != '') {
		if ($search_status === 'all') {
			$add_query = "
				`domain_created_id` = '$reseller_id'
			";
		} else {
			$add_query = "
					`domain_created_id` = '$reseller_id'
				AND
					`domain_status` = '$search_status'
			";
		}

		$count_query = "
			SELECT
				COUNT(`domain_id`) AS cnt
			FROM
				`domain`
			WHERE
				$add_query
		";

		$search_query = "
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
		";
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
			$count_query = "
				SELECT
					COUNT(`admin_id`) AS cnt
				FROM
					`admin` AS t1,
					`domain` AS t2
				$add_query
				AND
					t1.`admin_id` = t2.`domain_admin_id`
			";
		} else {
			$add_query = sprintf($add_query, " AND `created_by` = '$reseller_id'");
			$count_query = "
				SELECT
					COUNT(`admin_id`) AS cnt
				FROM
					`admin`
				$add_query
			";
		}

		$search_query = "
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
		";
	}
}

function gen_manage_domain_search_options(&$tpl, $search_for, $search_common,
	$search_status) {

	$cfg = iMSCP_Registry::get('Config');

	if ($search_for === 'n/a' && $search_common === 'n/a'
		&& $search_status === 'n/a') {
		// we have no search and let's genarate search fields empty
		$domain_selected = $cfg->HTML_SELECTED;
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';

		$all_selected = $cfg->HTML_SELECTED;
		$ok_selected = '';
		$suspended_selected = '';
	}
	if ($search_common === 'domain_name') {
		$domain_selected = $cfg->HTML_SELECTED;
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'customer_id') {
		$domain_selected = '';
		$customerid_selected = $cfg->HTML_SELECTED;
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'lname') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = $cfg->HTML_SELECTED;
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'firm') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = $cfg->HTML_SELECTED;
		$city_selected = '';
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'city') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = $cfg->HTML_SELECTED;
		$state_selected = '';
		$country_selected = '';
	} else if ($search_common === 'state') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = $cfg->HTML_SELECTED;
		$country_selected = '';
	} else if ($search_common === 'country') {
		$domain_selected = '';
		$customerid_selected = '';
		$lastname_selected = '';
		$company_selected = '';
		$city_selected = '';
		$state_selected = '';
		$country_selected = $cfg->HTML_SELECTED;
	}
	if ($search_status === 'all') {
		$all_selected = $cfg->HTML_SELECTED;
		$ok_selected = '';
		$suspended_selected = '';
	} else if ($search_status === 'ok') {
		$all_selected = '';
		$ok_selected = $cfg->HTML_SELECTED;
		$suspended_selected = '';
	} else if ($search_status === 'disabled') {
		$all_selected = '';
		$ok_selected = '';
		$suspended_selected = HTML_SELECTED;
	}

	if ($search_for === "n/a" || $search_for === '') {
		$tpl->assign(
			array('SEARCH_FOR' => "")
		);
	} else {
		$tpl->assign(
			array('SEARCH_FOR' => tohtml($search_for))
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
function gen_def_language(&$tpl, &$sql, $user_def_language) {

	$cfg = iMSCP_Registry::get('Config');
	$languages = array();
	$htmlSelected = $cfg->HTML_SELECTED;
	$query = "SHOW TABLES LIKE 'lang_%'";

	$stmt = exec_query($sql, $query);

	$stmt->setFetchStyle(PDO::FETCH_NUM);

	while (!$stmt->EOF) {
		$lang_table = $stmt->fields[0];

		$query = "
			SELECT
				`msgstr`
			FROM
				`$lang_table`
			WHERE
				`msgid` = 'iMSCP_language'
			;
		";

		$stmt2 = exec_query($sql, $query);

		$query = "
			SELECT
				`msgstr`
			FROM
				`$lang_table`
			WHERE
				`msgid` = 'iMSCP_languageSetlocaleValue'
			;
		";

		$stmt3 = exec_query($sql, $query);

		if ($stmt2->recordCount() == 0 || $stmt3->recordCount() == 0) {
			$language_name = tr('Unknown');
		} else {
			$tr_langcode = tr($stmt3->fields['msgstr']);

			if ($stmt3->fields['msgstr'] == $tr_langcode) { // no translation found
				$language_name = $stmt2->fields['msgstr'];
			} else { // found translation
				$language_name = $tr_langcode;
			}
		}

		$selected = ($lang_table === $user_def_language) ? $htmlSelected : '';

		array_push($languages, array($lang_table, $selected, $language_name));

		$stmt->moveNext();
	}

	asort($languages[0], SORT_STRING);
	foreach ($languages as $lang) {
		$tpl->assign(
			array(
				'LANG_VALUE' => $lang[0],
				'LANG_SELECTED' => $lang[1],
				'LANG_NAME' => tohtml($lang[2])
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

		$alias_query = "
			SELECT
				`alias_id`, `alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = ?
			ORDER BY
				`alias_id` DESC
		";
		$alias_rs = exec_query($sql, $alias_query, $domain_id);

		if ($alias_rs->recordCount() == 0) {
			$tpl->assign('USER_DETAILS', '');
		} else {
			while (!$alias_rs->EOF) {
				$alias_name = $alias_rs->fields['alias_name'];

				$tpl->assign('ALIAS_DOMAIN', tohtml(decode_idna($alias_name)));
				$tpl->parse('USER_DETAILS', '.user_details');

				$alias_rs->moveNext();
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

function reseller_limits_check(&$sql, &$err_msg, $reseller_id, $hpid, $newprops = "") {
	$error = false;

	if (empty($newprops)) {
		// this hosting plan exists
		if (isset($_SESSION["ch_hpprops"])) {
			$props = $_SESSION["ch_hpprops"];
		} else {
			$query = "
				SELECT
					`props`
				FROM
					`hosting_plans`
				WHERE
					`id` = ?
			";

			$res = exec_query($sql, $query, $hpid);
			$data = $res->fetchRow();
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

	$query = "
		SELECT
			*
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";

	$res = exec_query($sql, $query, $reseller_id);
	$data = $res->fetchRow();
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
			set_page_message(tr('You have reached your domains limit.<br />You cannot add more domains!'));
			$error = true;
		}
	}

	if ($sub_max != 0) {
		if ($sub_new != -1) {
			if ($sub_new == 0) {
				set_page_message(tr('You have a subdomains limit!<br />You cannot add an user with unlimited subdomains!'));
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
				set_page_message(tr('You have an aliases limit!<br />You cannot add an user with unlimited aliases!'));
				$error = true;
			} else if ($als_current + $als_new > $als_max) {
				set_page_message(tr('You Are Exceeding Your Alias Limit!'));
				$error = true;
			}
		}
	}

	if ($mail_max != 0) {
		if ($mail_new == 0) {
			set_page_message(tr('You have a mail accounts limit!<br />You cannot add an user with unlimited mail accounts!'));
			$error = true;
		} else if ($mail_current + $mail_new > $mail_max) {
			set_page_message(tr('You are exceeding your mail accounts limit!'));
			$error = true;
		}
	}

	if ($ftp_max != 0) {
		if ($ftp_new == 0) {
			set_page_message(tr('You have a FTP accounts limit!<br />You cannot add an user with unlimited FTP accounts!'));
			$error = true;
		} else if ($ftp_current + $ftp_new > $ftp_max) {
			set_page_message(tr('You are exceeding your FTP accounts limit!'));
			$error = true;
		}
	}

	if ($sql_db_max != 0) {
		if ($sql_db_new != -1) {
			if ($sql_db_new == 0) {
				set_page_message(tr('You have a SQL databases limit!<br />You cannot add an user with unlimited SQL databases!'));
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
				set_page_message(tr('You have an SQL users limit!<br />You cannot add an user with unlimited SQL users!'));
				$error = true;
			} else if ($sql_db_new == -1) {
				set_page_message(tr('You have disabled SQL databases for this user!<br />You cannot have SQL users here!'));
				$error = true;
			} else if ($sql_user_current + $sql_user_new > $sql_user_max) {
				set_page_message(tr('You are exceeding your SQL database limit!'));
				$error = true;
			}
		}
	}

	if ($traff_max != 0) {
		if ($traff_new == 0) {
			set_page_message(tr('You have a traffic limit!<br />You cannot add an user with unlimited traffic!'));
			$error = true;
		} else if ($traff_current + $traff_new > $traff_max) {
			set_page_message(tr('You are exceeding your traffic limit!'));
			$error = true;
		}
	}

	if ($disk_max != 0) {
		if ($disk_new == 0) {
			set_page_message(tr('You have a disk limit!<br />You cannot add an user with unlimited disk!'));
			$error = true;
		} else if ($disk_current + $disk_new > $disk_max) {
			set_page_message(tr('You are exceeding your disk limit!'));
			$error = true;
		}
	}

	if ($error === true) {
		return false;
	}

	return true;
}

function send_order_emails($admin_id, $domain_name, $ufname, $ulname, $uemail,
	$order_id) {

	$cfg = iMSCP_Registry::get('Config');

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

	$activate_link = $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST;
	$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';
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
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";
	$headers .= "X-Mailer: i-MSCP " . $cfg->Version . " Service Mailer";

	mail($to, $subject, $message, $headers);
}

function send_alias_order_email($alias_name) {

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

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
	$replace[] = $cfg->BASE_SERVER_VHOST;
	$search [] = '{BASE_SERVER_VHOST_PREFIX}';
	$replace[] = $cfg->BASE_SERVER_VHOST_PREFIX;

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$subject = encode($subject);

	$headers = "From: ". $from ."\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\n";
	$headers .= "Content-Transfer-Encoding: 8bit\n";
	$headers .= "X-Mailer: i-MSCP {$cfg->Version} Service Mailer";

	$mail_result = mail($to, $subject, $message, $headers);

	$mail_status = ($mail_result) ? 'OK' : 'NOT OK';

}

/**
 * add the 3 mail accounts/forwardings to a new domain...
 */
function client_mail_add_default_accounts($dmn_id, $user_email, $dmn_part,
	$dmn_type = 'domain', $sub_id = 0) {

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

	if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {

		$forward_type = ($dmn_type == 'alias') ? 'alias_forward' : 'normal_forward';

		// prepare SQL
		$query = "
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
		";

		// create default forwarder for webmaster@domain.tld to the account's owner
		$rs = exec_query(
			$sql, $query,
			array(
				'webmaster', '_no_', $user_email, $dmn_id, $forward_type,
				$sub_id, $cfg->ITEM_ADD_STATUS, '_no_', 10485760,
				'webmaster@' . $dmn_part
			)
		);

		// create default forwarder for postmaster@domain.tld to the account's reseller
		$rs = exec_query(
			$sql, $query,
			array(
				'postmaster', '_no_', $_SESSION['user_email'], $dmn_id,
				$forward_type, $sub_id, $cfg->ITEM_ADD_STATUS, '_no_',
				10485760, 'postmaster@' . $dmn_part
			)
		);

		// create default forwarder for abuse@domain.tld to the account's reseller
		$rs = exec_query(
			$sql, $query,
			array(
				'abuse', '_no_', $_SESSION['user_email'], $dmn_id,
				$forward_type, $sub_id, $cfg->ITEM_ADD_STATUS, '_no_',
				10485760, 'abuse@' . $dmn_part
			)
		);

	}

} // end client_mail_add_default_accounts

/**
 * Recalculate current_ properties of reseller
 *
 * @param int $reseller_id unique reseller identifiant
 * @return array list of properties
 */
function recalc_reseller_c_props($reseller_id) {

	$cfg = iMSCP_Registry::get('Config');
	$sql = iMSCP_Registry::get('Db');

	$delstatus = $cfg->ITEM_DELETE_STATUS;

	// Get all users of reseller:
	$query = "
		SELECT
			COUNT(`domain_id`) AS crn_domains,
			IFNULL(SUM(IF(`domain_subd_limit` >= 0, `domain_subd_limit`, 0)), 0) AS current_sub_cnt,
			IFNULL(SUM(IF(`domain_alias_limit` >= 0, `domain_alias_limit`, 0)), 0) AS current_als_cnt,
			IFNULL(SUM(IF(`domain_mailacc_limit` >= 0, `domain_mailacc_limit`, 0)), 0) AS current_mail_cnt,
			IFNULL(SUM(IF(`domain_ftpacc_limit` >= 0, `domain_ftpacc_limit`, 0)), 0) AS current_ftp_cnt,
			IFNULL(SUM(IF(`domain_sqld_limit` >= 0, `domain_sqld_limit`, 0)), 0) AS current_sql_db_cnt,
			IFNULL(SUM(IF(`domain_sqlu_limit` >= 0, `domain_sqlu_limit`, 0)), 0) AS current_sql_user_cnt,
			IFNULL(SUM(`domain_disk_limit`), 0) AS current_disk_amnt,
			IFNULL(SUM(`domain_traffic_limit`), 0) AS current_traff_amnt
		FROM
			`domain`
		WHERE
			`domain_created_id` = ?
		AND
			`domain_status` != ?
	";
	$res = exec_query($sql, $query, array($reseller_id, $delstatus));

	$current_dmn_cnt = $res -> fields['crn_domains'];

	if ($current_dmn_cnt > 0) {
		$current_sub_cnt = $res -> fields['current_sub_cnt'];
		$current_als_cnt = $res -> fields['current_als_cnt'];
		$current_mail_cnt = $res -> fields['current_mail_cnt'];
		$current_ftp_cnt = $res -> fields['current_ftp_cnt'];
		$current_sql_db_cnt = $res->fields['current_sql_db_cnt'];
		$current_sql_user_cnt = $res->fields['current_sql_user_cnt'];
		$current_disk_amnt  = $res->fields['current_disk_amnt'];
		$current_traff_amnt = $res->fields['current_traff_amnt'];
	} else {
		$current_sub_cnt = 0;
		$current_als_cnt = 0;
		$current_mail_cnt = 0;
		$current_ftp_cnt = 0;
		$current_sql_db_cnt = 0;
		$current_sql_user_cnt = 0;
		$current_disk_amnt  = 0;
		$current_traff_amnt = 0;
	}

	return array(
		$current_dmn_cnt,
		$current_sub_cnt,
		$current_als_cnt,
		$current_mail_cnt,
		$current_ftp_cnt,
		$current_sql_db_cnt,
		$current_sql_user_cnt,
		$current_disk_amnt,
		$current_traff_amnt
	);
}

/**
 * Recalculate current_ properties of reseller
 *
 * @param int $reseller_id unique reseller identifiant
 * @return void
 */
function update_reseller_c_props($reseller_id) {
	global $sql;

	$query = "
		UPDATE
			`reseller_props`
		SET
			`current_dmn_cnt` = ?,
			`current_sub_cnt` = ?,
			`current_als_cnt` = ?,
			`current_mail_cnt` = ?,
			`current_ftp_cnt` = ?,
			`current_sql_db_cnt` = ?,
			`current_sql_user_cnt` = ?,
			`current_disk_amnt` = ?,
			`current_traff_amnt` = ?
		WHERE
			`reseller_id` = ?
	";


	$props = recalc_reseller_c_props($reseller_id);
	$props[] = $reseller_id;

	exec_query($sql, $query, $props);
}

/**
 * Get the reseller id of a domain
 * moved from admin/domain_edit.php to reseller-functions.php
 *
 * @param int $domain_id unique domain identifiant
 * @return int unique reseller identifiant or 0 in on error
 */
function get_reseller_id($domain_id) {

	$sql = iMSCP_Registry::get('Db');

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

	$rs = exec_query($sql, $query, $domain_id);

	if ($rs->recordCount() == 0) {
		return 0;
	}

	$data = $rs->fetchRow();
	return $data['created_by'];
}

/**
 * Checks if a reseller has the rights to an option
 *
 * @param int $reseller_id unique reseller identifiant
 * @return boolean option permissions or array with all options
 */
function check_reseller_permissions($reseller_id, $permission) {

	$sql = iMSCP_Registry::get('Db');

	list($rdmn_current, $rdmn_max,
			$rsub_current, $rsub_max,
			$rals_current, $rals_max,
			$rmail_current, $rmail_max,
			$rftp_current, $rftp_max,
			$rsql_db_current, $rsql_db_max,
			$rsql_user_current, $rsql_user_max,
			$rtraff_current, $rtraff_max,
			$rdisk_current, $rdisk_max
		) = get_reseller_default_props($sql, $reseller_id);

	if ($permission == "all_permissions") {
		return array(
			$rsub_max,
			$rals_max,
			$rmail_max,
			$rftp_max,
			$rsql_db_max,
			$rsql_user_max
		);
	} else if ($permission == "subdomain" && $rsub_max == "-1") {
		return false;
	} elseif ($permission == "alias" && $rals_max == "-1") {
		return false;
	} else if ($permission == "mail" && $rmail_max == "-1") {
		return false;
	} else if ($permission == "ftp" && $rftp_max == "-1") {
		return false;
	} else if ($permission == "sql_db" && $rsql_db_max == "-1") {
		return false;
	} else if ($permission == "sql_user" && $rsql_user_max == "-1") {
		return false;
	}

	return true;
}

/**
 * Convert datepicker date to Unix-Timestamp
 *
 * @author Peter Ziergoebel <info@fisa4.de>
 * @since 1.0.0 (i-MSCP)
 * @param string $time A date/time string
 * @return int Unix timestamp
 */
function datepicker_reseller_convert($time){
    return strtotime($time);
}


function send_new_sw_upload($reseller_id, $file_name, $sw_id) {
	global $cfg, $sql;

	$query = "
		SELECT
			`admin_name` as reseller,
			`created_by`,
			`email` as res_email
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$res = exec_query($sql, $query, $reseller_id);

	$from_name = $res->fields['reseller'];
	$from_email = $res->fields['res_email'];
	$admin_id = $res->fields['created_by'];
	
	$query = "
		SELECT
			`email` as adm_email,
			`admin_name` as admin
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$res = exec_query($sql, $query, $admin_id);

	$to_name = $res->fields['admin'];
	$to_email = $res->fields['adm_email'];

	if ($from_name) {
		$from = "\"" . encode($from_name) . "\" <" . $from_email . ">";
	} else {
		$from = $from_email;
	}

	$search = array();
	$replace = array();
	$search [] = '{ADMIN}';
	$replace[] = $to_name;
	$search [] = '{SOFTWARE}';
	$replace[] = $file_name;
	$search [] = '{SOFTWARE_ID}';
	$replace[] = $sw_id;
	$search [] = '{RESELLER}';
	$replace[] = $from_name;
	$search [] = '{RESELLER_ID}';
	$replace[] = $reseller_id;

	$headers = "From: ". $from . "\n";
	$headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" . "Content-Transfer-Encoding: 8bit\n" . "X-Mailer: i-MSCP " . $cfg['Version'] . " Service Mailer";

	$subject = tr('{RESELLER} uploaded a new software package');
	$message = tr('Dear {ADMIN},
	{RESELLER} has uploaded a new software package.

	Details:
	Reseller ID: {RESELLER_ID}
	Package Name: {SOFTWARE}
	Package ID: {SOFTWARE_ID}

	Please login into your i-MSCP control panel for more details.', true);

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);
	$subject = encode($subject);
	$mail_result = mail($to_email, $subject, $message, $headers);
}
