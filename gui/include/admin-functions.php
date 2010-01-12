<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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

/**
 * encode string to be valid as mail header
 *
 * @source	php.net/manual/en/function.mail.php
 *
 * @param	string	$in_str		string to be encoded [should be in the $charset charset]
 * @param	string	$charset	charset in that string will be encoded
 * @return	string	encoded string
 *
 * @todo need to check emails with ? and space in subject - some probs can occur
 */
function encode($in_str, $charset = 'UTF-8') {
	$out_str = $in_str;
	if ($out_str && $charset) {
		// define start delimimter, end delimiter and spacer
		$end = "?=";
		$start = "=?" . $charset . "?B?";
		$spacer = $end . "\r\n " . $start;
		// determine length of encoded text within chunks
		// and ensure length is even
		$length = 75 - strlen($start) - strlen($end);
		$length = floor($length / 4) * 4;
		// encode the string and split it into chunks
		// with spacers after each chunk
		$out_str = base64_encode($out_str);
		$out_str = chunk_split($out_str, $length, $spacer);
		// remove trailing spacer and
		// add start and end delimiters
		$spacer = preg_quote($spacer);
		$out_str = preg_replace("/" . $spacer . "$/", "", $out_str);
		$out_str = $start . $out_str . $end;
	}
	return $out_str;
}

function gen_admin_mainmenu(&$tpl, $menu_file) {
	$sql = Database::getInstance();

	$tpl->define_dynamic('menu', $menu_file);
	$tpl->define_dynamic('isactive_support', 'menu');
	$tpl->define_dynamic('custom_buttons', 'menu');
	$tpl->assign(
		array(
			'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
			'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
			'TR_MENU_SYSTEM_TOOLS' => tr('System tools'),
			'TR_MENU_MANAGE_USERS' => tr('Manage users'),
			'TR_MENU_STATISTICS' => tr('Statistics'),
			'SUPPORT_SYSTEM_PATH' => Config::get('ISPCP_SUPPORT_SYSTEM_PATH'),
			'SUPPORT_SYSTEM_TARGET' => Config::get('ISPCP_SUPPORT_SYSTEM_TARGET'),
			'TR_MENU_SUPPORT_SYSTEM' => tr('Support system'),
			'TR_MENU_SETTINGS' => tr('Settings'),
			'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
			'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
			'TR_MENU_SYSTEM_TOOLS' => tr('System tools'),
			'TR_MENU_MANAGE_USERS' => tr('Manage users'),
			'TR_MENU_STATISTICS' => tr('Statistics'),
			'SUPPORT_SYSTEM_PATH' => Config::get('ISPCP_SUPPORT_SYSTEM_PATH'),
			'SUPPORT_SYSTEM_TARGET' => Config::get('ISPCP_SUPPORT_SYSTEM_TARGET'),
			'TR_MENU_SUPPORT_SYSTEM' => tr('Support system'),
			'TR_MENU_SETTINGS' => tr('Settings'),
			'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
			'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
			'TR_MENU_ADD_ADMIN' => tr('Add admin'),
			'TR_MENU_ADD_RESELLER' => tr('Add reseller'),
			'TR_MENU_RESELLER_ASIGNMENT' => tr('Reseller assignment'),
			'TR_MENU_USER_ASIGNMENT' => tr('User assignment'),
			'TR_MENU_EMAIL_SETUP' => tr('Email setup'),
			'TR_MENU_CIRCULAR' => tr('Email marketing'),
			'TR_MENU_ADD_HOSTING' => tr('Add hosting plan'),
			'TR_MENU_RESELLER_STATISTICS' => tr('Reseller statistics'),
			'TR_MENU_SERVER_STATISTICS' => tr('Server statistics'),
			'TR_MENU_ADMIN_LOG' => tr('Admin log'),
			'TR_MENU_MANAGE_IPS' => tr('Manage IPs'),
			'TR_MENU_SYSTEM_INFO' => tr('System info'),
			'TR_MENU_I18N' => tr('Internationalisation'),
			'TR_MENU_LANGUAGE' => tr('Language'),
			'TR_MENU_LAYOUT_TEMPLATES' => tr('Layout'),
			'TR_MENU_LOGOUT' => tr('Logout'),
			'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
			'TR_MENU_SERVER_TRAFFIC_SETTINGS' => tr('Server traffic settings'),
			'TR_MENU_SERVER_STATUS' => tr('Server status'),
			'TR_MENU_ISPCP_UPDATE' => tr('ispCP updates'),
			'TR_MENU_ISPCP_DATABASE_UPDATE' => tr('ispCP database updates'),
			'TR_MENU_ISPCP_DEBUGGER' => tr('ispCP debugger'),
			'TR_CUSTOM_MENUS' => tr('Custom menus'),
			'TR_MENU_OVERVIEW' => tr('Overview'),
			'TR_MENU_MANAGE_SESSIONS' => tr('User sessions'),
			'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup'),
			'TR_MAINTENANCEMODE' => tr('Maintenance mode'),
			'TR_GENERAL_SETTINGS' => tr('General settings'),
			'TR_SERVERPORTS' => tr('Server ports')
		)
	);

	$query = "
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'admin'
	";

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

			if ($menu_target !== '') {
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

	if (strtolower(Config::get('HOSTING_PLANS_LEVEL')) != 'admin') {
		$tpl->assign('HOSTING_PLANS', '');
	}

	$tpl->parse('MAIN_MENU', 'menu');
}

function gen_admin_menu(&$tpl, $menu_file) {
	$sql = Database::getInstance();

	$tpl->define_dynamic('menu', $menu_file);
	$tpl->define_dynamic('custom_buttons', 'menu');

	$tpl->assign(
		array(
			'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
			'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
			'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
			'TR_MENU_MANAGE_USERS' => tr('Manage users'),
			'TR_MENU_ADD_ADMIN' => tr('Add admin'),
			'TR_MENU_ADD_RESELLER' => tr('Add reseller'),
			'TR_MENU_RESELLER_ASIGNMENT' => tr('Reseller assignment'),
			'TR_MENU_USER_ASIGNMENT' => tr('User assignment'),
			'TR_MENU_EMAIL_SETUP' => tr('Email setup'),
			'TR_MENU_CIRCULAR' => tr('Email marketing'),
			'TR_MENU_HOSTING_PLANS' => tr('Manage hosting plans'),
			'TR_MENU_ADD_HOSTING' => tr('Add hosting plan'),
			'TR_MENU_ROOTKIT_LOG' => tr('Rootkit Log'),
			'TR_MENU_RESELLER_STATISTICS' => tr('Reseller statistics'),
			'TR_MENU_SERVER_STATISTICS' => tr('Server statistics'),
			'TR_MENU_ADMIN_LOG' => tr('Admin log'),
			'TR_MENU_MANAGE_IPS' => tr('Manage IPs'),
			'TR_MENU_SUPPORT_SYSTEM' => tr('Support system'),
			'TR_MENU_SYSTEM_INFO' => tr('System info'),
			'TR_MENU_I18N' => tr('Internationalisation'),
			'TR_MENU_LANGUAGE' => tr('Language'),
			'TR_MENU_LAYOUT_TEMPLATES' => tr('Layout'),
			'TR_MENU_LOGOUT' => tr('Logout'),
			'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
			'TR_MENU_STATISTICS' => tr('Statistics'),
			'TR_MENU_SYSTEM_TOOLS' => tr('System tools'),
			'TR_MENU_SERVER_TRAFFIC_SETTINGS' => tr('Server traffic settings'),
			'TR_MENU_SERVER_STATUS' => tr('Server status'),
			'TR_MENU_ISPCP_UPDATE' => tr('ispCP updates'),
			'TR_MENU_ISPCP_DEBUGGER' => tr('ispCP debugger'),
			'TR_CUSTOM_MENUS' => tr('Custom menus'),
			'TR_MENU_OVERVIEW' => tr('Overview'),
			'TR_MENU_MANAGE_SESSIONS' => tr('User sessions'),
			'SUPPORT_SYSTEM_PATH' => Config::get('ISPCP_SUPPORT_SYSTEM_PATH'),
			'SUPPORT_SYSTEM_TARGET' => Config::get('ISPCP_SUPPORT_SYSTEM_TARGET'),
			'TR_MENU_LOSTPW_EMAIL' => tr('Lostpw email setup'),
			'TR_MAINTENANCEMODE' => tr('Maintenance mode'),
			'TR_MENU_SETTINGS' => tr('Settings'),
			'TR_GENERAL_SETTINGS' => tr('General settings'),
			'TR_SERVERPORTS' => tr('Server ports'),
			'VERSION' => Config::get('Version'),
			'BUILDDATE' => Config::get('BuildDate'),
			'CODENAME' => Config::get('CodeName')
		)
	);
	$query = "
		SELECT
			*
		FROM
			`custom_menus`
		WHERE
			`menu_level` = 'admin1'
";

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

			if ($menu_target !== '') {
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
		$tpl->assign('SUPPORT_SYSTEM', '');
	}

	if (strtolower(Config::get('HOSTING_PLANS_LEVEL')) != 'admin') {
		$tpl->assign('HOSTING_PLANS', '');
	}

	$tpl->parse('MENU', 'menu');
}

function get_cnt_of_user(&$sql, $user_type) {
	$query = "SELECT COUNT(*) AS cnt FROM `admin` WHERE `admin_type` = ?";

	$rs = exec_query($sql, $query, array($user_type));

	$admin_cnt = $rs->fields['cnt'];

	return $admin_cnt;
}

function get_sql_user_count($sql) {
	$query = "SELECT DISTINCT `sqlu_name` FROM `sql_user`";

	$rs = exec_query($sql, $query, false);

	return $rs->RecordCount();
}

function get_admin_general_info(&$tpl, &$sql) {
	$tpl->assign(
		array(
			'TR_GENERAL_INFORMATION' => tr('General information'),
			'TR_ACCOUNT_NAME' => tr('Account name'),
			'TR_ADMIN_USERS' => tr('Admin users'),
			'TR_RESELLER_USERS' => tr('Reseller users'),
			'TR_NORMAL_USERS' => tr('Normal users'),
			'TR_DOMAINS' => tr('Domains'),
			'TR_SUBDOMAINS' => tr('Subdomains'),
			'TR_DOMAINS_ALIASES' => tr('Domain aliases'),
			'TR_MAIL_ACCOUNTS' => tr('Mail accounts'),
			'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
			'TR_SQL_DATABASES' => tr('SQL databases'),
			'TR_SQL_USERS' => tr('SQL users'),
			'TR_SYSTEM_MESSAGES' => tr('System messages'),
			'TR_NO_NEW_MESSAGES' => tr('No new messages'),
			'TR_SERVER_TRAFFIC' => tr('Server traffic')
		)
	);

	// If COUNT_DEFAULT_EMAIL_ADDRESSES = false, admin total emails show [total - default_emails]/[total_emails]
	$retrieve_total_emails = records_count('mail_users', '', '');
	if (Config::get('COUNT_DEFAULT_EMAIL_ADDRESSES')) {
		$show_total_emails = $retrieve_total_emails;
	} else {
		$retrieve_total_default_emails = records_count('mail_users', 'mail_acc', 'abuse');
		$retrieve_total_default_emails += records_count('mail_users', 'mail_acc', 'webmaster');
		$retrieve_total_default_emails += records_count('mail_users', 'mail_acc', 'postmaster');
		$show_total_emails = ($retrieve_total_emails-$retrieve_total_default_emails)."/".$retrieve_total_emails;
	}

	$tpl->assign(
		array(
			'ACCOUNT_NAME' => $_SESSION['user_logged'],
			'ADMIN_USERS' => records_count('admin', 'admin_type', 'admin'),
			'RESELLER_USERS' => records_count('admin', 'admin_type', 'reseller'),
			'NORMAL_USERS' => records_count('admin', 'admin_type', 'user'),
			'DOMAINS' => records_count('domain', '', ''),
			'SUBDOMAINS' => records_count('subdomain', '', '') + records_count('subdomain_alias', 'subdomain_alias_id', '', ''),
			'DOMAINS_ALIASES' => records_count( 'domain_aliasses', '', ''),
			'MAIL_ACCOUNTS' => $show_total_emails,
			'FTP_ACCOUNTS' => records_count('ftp_users', '', ''),
			'SQL_DATABASES' => records_count('sql_database', '', ''),
			'SQL_USERS' => get_sql_user_count($sql)
		)
	);
}

function gen_admin_list(&$tpl, &$sql) {
	$query = "
			SELECT
				t1.`admin_id`, t1.`admin_name`, t1.`domain_created`,
				IFNULL(t2.`admin_name`, '') AS created_by
			FROM
				`admin` AS t1
			LEFT JOIN
				`admin` AS t2 ON t1.created_by = t2.`admin_id`
			WHERE
				t1.`admin_type` = 'admin'
			ORDER BY
				t1.`admin_name`
			ASC
	";

	$rs = exec_query($sql, $query, array());

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'ADMIN_MESSAGE' => tr('Administrators list is empty!'),
				'ADMIN_LIST' => ''
			)
		);

		$tpl->parse('ADMIN_MESSAGE', 'admin_message');
	} else {
		$tpl->assign(
			array(
				'TR_ADMIN_USERNAME' => tr('Username'),
				'TR_ADMIN_CREATED_ON' => tr('Creation date'),
				'TR_ADMIN_CREATED_BY' => tr('Created by'),
				'TR_ADMIN_OPTIONS' => tr('Options'),
			)
		);

		$i = 0;
		while (!$rs->EOF) {
			$tpl->assign(
				array(
					'ADMIN_CLASS' => ($i % 2 == 0) ? 'content' : 'content2',
				)
			);

			$admin_created = $rs->fields['domain_created'];

			if ($admin_created == 0) {
				$admin_created = tr('N/A');
			} else {
				$date_formt = Config::get('DATE_FORMAT');
				$admin_created = date($date_formt, $admin_created);
			}

			if ($rs->fields['created_by'] == '' || $rs->fields['admin_id'] == $_SESSION['user_id']) {
				$tpl->assign(
					array('ADMIN_DELETE_LINK' => '')
				);
				$tpl->parse('ADMIN_DELETE_SHOW', 'admin_delete_show');
			} else {
				$tpl->assign(
					array(
						'ADMIN_DELETE_SHOW' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE_ADMIN' => "user_delete.php?delete_id=" . $rs->fields['admin_id'] . "&amp;delete_username=" . $rs->fields['admin_name'],
						'ADMIN_USERNAME' => $rs->fields['admin_name'],
					)
				);
				$tpl->parse('ADMIN_DELETE_LINK', 'admin_delete_link');
			}

			$tpl->assign(
				array(
					'ADMIN_USERNAME' => $rs->fields['admin_name'],
					'ADMIN_CREATED_ON' => $admin_created,
					'ADMIN_CREATED_BY' => ($rs->fields['created_by'] != null) ? $rs->fields['created_by'] : tr("System"),
					'URL_EDIT_ADMIN' => "admin_edit.php?edit_id=" . $rs->fields['admin_id'],
				)
			);

			$tpl->parse('ADMIN_ITEM', '.admin_item');
			$rs->MoveNext();

			$i++;
		}

		$tpl->parse('ADMIN_LIST', 'admin_list');
		$tpl->assign('ADMIN_MESSAGE', '');
	}
}

function gen_reseller_list(&$tpl, &$sql) {
	$query = "
		SELECT
			t1.`admin_id`, t1.`admin_name`, t1.`domain_created`,
			IFNULL(t2.`admin_name`, '') AS created_by
		FROM
			`admin` AS t1
		LEFT JOIN
			`admin` AS t2 ON t1.created_by = t2.`admin_id`
		WHERE
			t1.`admin_type` = 'reseller'
		ORDER BY
			t1.`admin_name`
		ASC
	";

	$rs = exec_query($sql, $query, array());

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'RSL_MESSAGE' => tr('Resellers list is empty!'),
				'RSL_LIST' => ''
			)
		);

		$tpl->parse('RSL_MESSAGE', 'rsl_message');
	} else {
		$tpl->assign(
			array(
				'TR_RSL_USERNAME' => tr('Username'),
				'TR_RSL_CREATED_BY' => tr('Created by'),
				'TR_RSL_OPTIONS' => tr('Options'),
			)
		);

		$i = 0;
		while (!$rs->EOF) {
			$tpl->assign(
				array(
					'RSL_CLASS' => ($i % 2 == 0) ? 'content' : 'content2',
				)
			);

			if ($rs->fields['created_by'] == '') {
				$tpl->assign(
					array(
						'TR_DELETE' => tr('Delete'),
						'RSL_DELETE_LINK' => '',
					)
				);
				$tpl->parse('RSL_DELETE_SHOW', 'rsl_delete_show');
			} else {
				$tpl->assign(
					array(
						'RSL_DELETE_SHOW' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE_RSL' => "user_delete.php?delete_id=" . $rs->fields['admin_id'] . "&amp;delete_username=" . $rs->fields['admin_name'],
						'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
						'GO_TO_USER_INTERFACE' => tr('Switch'),
						'URL_CHANGE_INTERFACE' => "change_user_interface.php?to_id=" . $rs->fields['admin_id'],
					)
				);
				$tpl->parse('RSL_DELETE_LINK', 'rsl_delete_link');
			}

			$reseller_created = $rs->fields['domain_created'];

			if ($reseller_created == 0) {
				$reseller_created = tr('N/A');
			} else {
				$date_formt = Config::get('DATE_FORMAT');
				$reseller_created = date($date_formt, $reseller_created);
			}

			$tpl->assign(
				array(
					'RSL_USERNAME' => $rs->fields['admin_name'],
					'RESELLER_CREATED_ON' => $reseller_created,
					'RSL_CREATED_BY' => $rs->fields['created_by'],
					'URL_EDIT_RSL' => "reseller_edit.php?edit_id=" . $rs->fields['admin_id'],
				)
			);

			$tpl->parse('RSL_ITEM', '.rsl_item');
			$rs->MoveNext();

			$i++;
		}

		$tpl->parse('RSL_LIST', 'rsl_list');
		$tpl->assign('RSL_MESSAGE', '');
	}
}

function gen_user_list(&$tpl, &$sql) {
	$start_index = 0;

	$rows_per_page = Config::get('DOMAIN_ROWS_PER_PAGE');

	if (isset($_GET['psi'])) $start_index = $_GET['psi'];
	// Search request generated ?!
	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {
		$_SESSION['search_for'] = trim(clean_input($_POST['search_for']));
		$_SESSION['search_common'] = $_POST['search_common'];
		$_SESSION['search_status'] = $_POST['search_status'];

		$start_index = 0;
	} else {
		if (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
			// He have not got scroll through patient records.
			unset($_SESSION['search_for']);
			unset($_SESSION['search_common']);
			unset($_SESSION['search_status']);
		}
	}

	$search_query = '';
	$count_query = '';
	if (isset($_SESSION['search_for'])) {
		gen_admin_domain_query($search_query,
			$count_query,
			$start_index,
			$rows_per_page,
			$_SESSION['search_for'],
			$_SESSION['search_common'],
			$_SESSION['search_status']);

		gen_admin_domain_search_options($tpl, $_SESSION['search_for'], $_SESSION['search_common'], $_SESSION['search_status']);
		$rs = exec_query($sql, $count_query, array());
	} else {
		gen_admin_domain_query($search_query,
			$count_query,
			$start_index,
			$rows_per_page,
			'n/a',
			'n/a',
			'n/a');
		gen_admin_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');
		$rs = exec_query($sql, $count_query, array());
	}

	$records_count = $rs->fields['cnt'];
	$rs = execute_query($sql, $search_query);

	$i = 0;
	if ($rs->RecordCount() == 0) {
		if (isset($_SESSION['search_for'])) {
			$tpl->assign(
				array(
					'USR_MESSAGE' => tr('Not found user records matching the search criteria!'),
					'USR_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'TR_VIEW_DETAILS' => tr('view aliases'),
					'SHOW_DETAILS' => "show",
				)
			);

			unset($_SESSION['search_for']);
			unset($_SESSION['search_common']);
			unset($_SESSION['search_status']);
		} else {
			$tpl->assign(
				array(
					'USR_MESSAGE' => tr('Users list is empty!'),
					'USR_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'TR_VIEW_DETAILS' => tr('view aliases'),
					'SHOW_DETAILS' => "show",
				)
			);
		}

		$tpl->parse('USR_MESSAGE', 'usr_message');
	} else {
		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prev_si
				)
			);
		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $next_si
				)
			);
		}

		$tpl->assign(
			array(
				'TR_USR_USERNAME' => tr('Username'),
				'TR_USR_CREATED_BY' => tr('Created by'),
				'TR_USR_OPTIONS' => tr('Options'),
				'TR_USER_STATUS' => tr('Status'),
				'TR_DETAILS' => tr('Details'),
			)
		);
		while (!$rs->EOF) {
			$tpl->assign(
				array(
					'USR_CLASS' => ($i % 2 == 0) ? 'content' : 'content2',
				)
			);

			// user status icon
			$domain_created_id = $rs->fields['domain_created_id'];

			$query = "
				SELECT
					`admin_id`,
					`admin_name`
				FROM
					`admin`
				WHERE
					`admin_id` = ?
				ORDER BY
					`admin_name`
				ASC
			";

			$rs2 = exec_query($sql, $query, array($domain_created_id));

			if ($rs2->fields['admin_name'] == '') {
				$tpl->assign(
					array(
						'TR_DELETE' => tr('Delete'),
						'USR_DELETE_LINK' => '',
					)
				);
				$tpl->parse('USR_DELETE_SHOW', 'usr_delete_show');
			} else {
				// Get disk usage by user
				$traffic = get_user_traffic($rs->fields['domain_id']);

				$tpl->assign(
					array(
						'USR_DELETE_SHOW' => '',
						'DOMAIN_ID' => $rs->fields['domain_id'],
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE_USR' => "user_delete.php?domain_id=" . $rs->fields['domain_id'],
						'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
						'GO_TO_USER_INTERFACE' => tr('Switch'),
						'URL_CHANGE_INTERFACE' => "change_user_interface.php?to_id=" . $rs->fields['domain_admin_id'],
						'USR_USERNAME' => $rs->fields['domain_name'],
						'TR_EDIT_DOMAIN' => tr('Edit domain'),
						'TR_EDIT_USR' => tr('Edit user')
					)
				);
				$tpl->parse('USR_DELETE_LINK', 'usr_delete_link');
			}

			if ($rs->fields['domain_status'] == Config::get('ITEM_OK_STATUS')) {
				$status_icon = "ok.png";
				$status_url = "domain_status_change.php?domain_id=" . $rs->fields['domain_id'];
			} else if ($rs->fields['domain_status'] == Config::get('ITEM_DISABLED_STATUS')) {
				$status_icon = "disabled.png";
				$status_url = "domain_status_change.php?domain_id=" . $rs->fields['domain_id'];
			} else if ($rs->fields['domain_status'] == Config::get('ITEM_ADD_STATUS') || $rs->fields['domain_status'] == Config::get('ITEM_RESTORE_STATUS') || $rs->fields['domain_status'] == Config::get('ITEM_CHANGE_STATUS') || $rs->fields['domain_status'] == Config::get('ITEM_TOENABLE_STATUS') || $rs->fields['domain_status'] == Config::get('ITEM_TODISABLED_STATUS') || $rs->fields['domain_status'] == Config::get('ITEM_DELETE_STATUS')) {
				$status_icon = "reload.png";
				$status_url = "#";
			} else {
				$status_icon = "error.png";
				$status_url = "domain_details.php?domain_id=" . $rs->fields['domain_id'];
			}

			$tpl->assign(
				array(
					'STATUS_ICON' => $status_icon,
					'URL_CHANGE_STATUS' => $status_url,
				)
			);
			// end of user status icon
			$admin_name = decode_idna($rs->fields['domain_name']);

			$domain_created = $rs->fields['domain_created'];

			if ($domain_created == 0) {
				$domain_created = tr('N/A');
			} else {
				$date_formt = Config::get('DATE_FORMAT');
				$domain_created = date($date_formt, $domain_created);
			}

			$domain_expires = $rs->fields['domain_expires'];

			if ($domain_expires == 0) {
				$domain_expires = tr('N/A');
			} else {
				$date_formt = Config::get('DATE_FORMAT');
				$domain_expires = date($date_formt, $domain_expires);
			}

			$tpl->assign(
				array(
					'USR_USERNAME' => $admin_name,
					'USER_CREATED_ON' => $domain_created,
					'USER_EXPIRES_ON' => $domain_expires,
					'USR_CREATED_BY' => $rs2->fields['admin_name'],
					'USR_OPTIONS' => '',
					'URL_EDIT_USR' => "admin_edit.php?edit_id=" . $rs->fields['domain_admin_id'],
					'TR_MESSAGE_CHANGE_STATUS' => tr('Are you sure you want to change the status of domain account?', true),
					'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
				)
			);

			gen_domain_details($tpl, $sql, $rs->fields['domain_id']);

			$tpl->parse('USR_ITEM', '.usr_item');

			$rs->MoveNext();

			$i++;
		}

		$tpl->parse('USR_LIST', 'usr_list');
		$tpl->assign('USR_MESSAGE', '');
	}
}

function get_admin_manage_users(&$tpl, &$sql) {
	$tpl->assign(
		array(
			'TR_MANAGE_USERS' => tr('Manage users'),
			'TR_ADMINISTRATORS' => tr('Administrators'),
			'TR_RESELLERS' => tr('Resellers'),
			'TR_USERS' => tr('Users'),
			'TR_SEARCH' => tr('Search'),
			'TR_CREATED_ON' => tr('Creation date'),
			'TR_EXPIRES_ON' => tr('Expire date'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
			'TR_EDIT' => tr("Edit")
		)
	);

	gen_admin_list($tpl, $sql);

	gen_reseller_list($tpl, $sql);

	gen_user_list($tpl, $sql);
}

function generate_reseller_props($reseller_id) {
	$sql = Database::getInstance();

	$query = "SELECT * FROM `reseller_props` WHERE `reseller_id` = ?";

	$rs = exec_query($sql, $query, array($reseller_id));

	if ($rs->RowCount() == 0) {
		return array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}

	return array($rs->fields['current_dmn_cnt'],
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
}

function generate_reseller_users_props($reseller_id) {
	$sql = Database::getInstance();

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

	$query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?";

	$rs = exec_query($sql, $query, array($reseller_id));

	if ($rs->RowCount() == 0) {
		return array(
			$rdmn_current, $rdmn_max, $rdmn_uf,
			$rsub_current, $rsub_max, $rsub_uf,
			$rals_current, $rals_max, $rals_uf,
			$rmail_current, $rmail_max, $rmail_uf,
			$rftp_current, $rftp_max, $rftp_uf,
			$rsql_db_current, $rsql_db_max, $rsql_db_uf,
			$rsql_user_current, $rsql_user_max, $rsql_user_uf,
			$rtraff_current, $rtraff_max, $rtraff_uf,
			$rdisk_current, $rdisk_max, $rdisk_uf
		);
	}
	while (!$rs->EOF) {
		$admin_id = $rs->fields['admin_id'];

		$query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";

		$dres = exec_query($sql, $query, array($admin_id));

		$user_id = $dres->fields['domain_id'];

		list($sub_current, $sub_max,
			$als_current, $als_max,
			$mail_current, $mail_max,
			$ftp_current, $ftp_max,
			$sql_db_current, $sql_db_max,
			$sql_user_current, $sql_user_max,
			$traff_max, $disk_max
		) = generate_user_props($user_id);

		list($a,
			$b,
			$c,
			$d,
			$e,
			$f,
			$traff_current,
			$disk_current,
			$g,
			$h
		) = generate_user_traffic($user_id);

		$rdmn_current += 1;

		if ($sub_max != -1) {
			if ($sub_max == 0)
				$rsub_uf = '_on_';

			$rsub_current += $sub_current;
			$rsub_max += ($sub_max > 0) ? $sub_max : 0;
		}

		if ($als_max != -1) {
			if ($als_max == 0)
				$rals_uf = '_on_';

			$rals_current += $als_current;
			$rals_max += ($als_max > 0) ? $als_max : 0;
		}

		if ($mail_max == 0)
			$rmail_uf = '_on_';

		$rmail_current += $mail_current;
		$rmail_max += ($mail_max > 0) ? $mail_max : 0;

		if ($ftp_max == 0)
			$rftp_uf = '_on_';

		$rftp_current += $ftp_current;
		$rftp_max += ($ftp_max > 0) ? $ftp_max : 0;

		if ($sql_db_max != -1) {
			if ($sql_db_max == 0)
				$rsql_db_uf = '_on_';

			$rsql_db_current += $sql_db_current;
			$rsql_db_max += ($sql_db_max > 0) ? $sql_db_max : 0;
		}

		if ($sql_user_max != -1) {
			if ($sql_user_max == 0)
				$rsql_user_uf = '_on_';

			$rsql_user_current += $sql_user_current;
			$rsql_user_max += ($sql_user_max > 0) ? $sql_user_max : 0;
		}

		if ($traff_max == 0)
			$rtraff_uf = '_on_';

		$rtraff_current += $traff_current;
		$rtraff_max += $traff_max;

		if ($disk_max == 0)
			$rdisk_uf = '_on_';

		$rdisk_current += $disk_current;
		$rdisk_max += $disk_max;

		$rs->MoveNext();
	}

	return array(
		$rdmn_current, $rdmn_max, $rdmn_uf,
		$rsub_current, $rsub_max, $rsub_uf,
		$rals_current, $rals_max, $rals_uf,
		$rmail_current, $rmail_max, $rmail_uf,
		$rftp_current, $rftp_max, $rftp_uf,
		$rsql_db_current, $rsql_db_max, $rsql_db_uf,
		$rsql_user_current, $rsql_user_max, $rsql_user_uf,
		$rtraff_current, $rtraff_max, $rtraff_uf,
		$rdisk_current, $rdisk_max, $rdisk_uf
	);
}

/**
 * @todo explain or replace the hack
 */
function generate_user_props($user_id) {
	$sql = Database::getInstance();

	$query = "SELECT * FROM `domain` WHERE `domain_id` = ?";

	$rs = exec_query($sql, $query, array($user_id));

	if ($rs->RowCount() == 0) {
		return array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
	}

	$sub_current = records_count('subdomain', 'domain_id', $user_id);
	$sub_max = $rs->fields['domain_subd_limit'];

	$als_current = records_count('domain_aliasses', 'domain_id', $user_id);
	$als_max = $rs->fields['domain_alias_limit'];
	// Sorry for the strange hack, but it works - RatS
	$mail_current = records_count('mail_users', 'mail_type NOT RLIKE \'_catchall\' AND domain_id', $user_id);
	$mail_max = $rs->fields['domain_mailacc_limit'];

	$ftp_current = sub_records_rlike_count(	'domain_name', 'domain', 'domain_id', $user_id,
											'userid', 'ftp_users', 'userid', '@', '');

	$ftp_current += sub_records_rlike_count('subdomain_name', 'subdomain', 'domain_id', $user_id,
											'userid', 'ftp_users', 'userid', '@', '');

	$ftp_current += sub_records_rlike_count('alias_name', 'domain_aliasses', 'domain_id', $user_id,
											'userid', 'ftp_users', 'userid', '@', '');

	$ftp_max = $rs->fields['domain_ftpacc_limit'];

	$sql_db_current = records_count('sql_database', 'domain_id', $user_id);
	$sql_db_max = $rs->fields['domain_sqld_limit'];

	$sql_user_current = sub_records_count(	'sqld_id', 'sql_database', 'domain_id', $user_id,
											'sqlu_id', 'sql_user', 'sqld_id', '', '');

	$sql_user_max = $rs->fields['domain_sqlu_limit'];

	$traff_max = $rs->fields['domain_traffic_limit'];

	$disk_max = $rs->fields['domain_disk_limit'];

	return array(
		$sub_current, $sub_max, $als_current, $als_max,
		$mail_current, $mail_max, $ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max, $sql_user_current,
		$sql_user_max, $traff_max, $disk_max
	);
}

/**
 * @todo implement check for dynamic table/row in SQL query
 */
function records_count($table, $where, $value) {
	$sql = Database::getInstance();

	if ($where != '') {
		$query = "SELECT COUNT(*) AS cnt FROM $table WHERE $where = ?";
		$rs = exec_query($sql, $query, array($value));
	} else {
		$query = "SELECT COUNT(*) AS cnt FROM $table";
		$rs = exec_query($sql, $query, array());
	}

	return $rs->fields['cnt'];
}

/**
 * @todo implement check for dynamic table/row in SQL query
 */
function records_rlike_count($field, $table, $where, $value, $a, $b) {
	$sql = Database::getInstance();

	if ($where != '') {
		$query = "SELECT COUNT(*) AS cnt FROM $table WHERE $where RLIKE ?";
		$rs = exec_query($sql, $query, array($a . $value . $b));
	} else {
		$query = "SELECT COUNT(*) AS cnt FROM $table";
		$rs = exec_query($sql, $query, array());
	}

	return $rs->fields['cnt'];
}

/**
 * @todo implement check for dynamic table/row in SQL query
 */
function sub_records_count($field, $table, $where, $value, $subfield, $subtable, $subwhere) {
	$sql = Database::getInstance();

	if ($where != '') {
		$query = "SELECT $field AS field FROM $table WHERE $where = ?";
		$rs = exec_query($sql, $query, array($value));
	} else {
		$query = "SELECT $field AS field FROM $table";
		$rs = exec_query($sql, $query, array());
	}

	$result = 0;

	if ($rs->RowCount() == 0) {
		return $result;
	}
	while (!$rs->EOF) {
		$contents = $rs->fields['field'];

		if ($subwhere != '') {
			$query = "SELECT COUNT(*) AS cnt FROM $subtable WHERE $subwhere = ?";
		} else {
			return $result;
		}

		$subres = exec_query($sql, $query, array($contents));
		$result += $subres->fields['cnt'];
		$rs->MoveNext();
	}

	return $result;
}

function generate_user_traffic($user_id) {
	$sql = Database::getInstance();
	global $crnt_month, $crnt_year;

	$from_timestamp = mktime(0, 0, 0, $crnt_month, 1, $crnt_year);

	if ($crnt_month == 12) {
		$to_timestamp = mktime(0, 0, 0, 1, 1, $crnt_year + 1);
	} else {
		$to_timestamp = mktime(0, 0, 0, $crnt_month + 1, 1, $crnt_year);
	}

	$query = "
		SELECT
			`domain_id`,
			IFNULL(`domain_disk_usage`, 0) AS domain_disk_usage,
			IFNULL(`domain_traffic_limit`, 0) AS domain_traffic_limit,
			IFNULL(`domain_disk_limit`, 0) AS domain_disk_limit,
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		ORDER BY
			`domain_name`
	";

	$rs = exec_query($sql, $query, array($user_id));

	if ($rs->RowCount() == 0 || $rs->RowCount() > 1) {
		write_log("TRAFFIC WARNING: " . $rs->fields['domain_name'] . " manages incorrect number of domains: " . $rs->RowCount());

		return array('n/a', 0, 0, 0, 0, 0, 0, 0, 0, 0);
	} else {
		$domain_id = $rs->fields['domain_id'];
		$domain_disk_usage = $rs->fields['domain_disk_usage'];
		$domain_traff_limit = $rs->fields['domain_traffic_limit'];
		$domain_disk_limit = $rs->fields['domain_disk_limit'];
		$domain_name = $rs->fields['domain_name'];

		$query = "
			SELECT
				IFNULL(SUM(`dtraff_web`), 0) AS web,
				IFNULL(SUM(`dtraff_ftp`), 0) AS ftp,
				IFNULL(SUM(`dtraff_mail`), 0) AS smtp,
				IFNULL(SUM(`dtraff_pop`), 0) AS pop,
				IFNULL(SUM(`dtraff_web`), 0) +
				IFNULL(SUM(`dtraff_ftp`), 0) +
				IFNULL(SUM(`dtraff_mail`), 0) +
				IFNULL(SUM(`dtraff_pop`), 0) AS total
			FROM
				`domain_traffic`
			WHERE
				`domain_id` = ?
			AND
				`dtraff_time` >= ?
			AND
				`dtraff_time` < ?
		";

		$rs1 = exec_query($sql, $query, array($domain_id, $from_timestamp, $to_timestamp));

		return array(
			$domain_name,
			$domain_id,
			$rs1->fields['web'],
			$rs1->fields['ftp'],
			$rs1->fields['smtp'],
			$rs1->fields['pop'],
			$rs1->fields['total'],
			$domain_disk_usage,
			$domain_traff_limit,
			$domain_disk_limit
		);
	}
}

function make_usage_vals($current, $max) {
	if ($max == 0) {
		$max = 1024 * 1024 * 1024 * 1024; // 1 TeraByte Limit ;) for Unlimited Value
	}

	$percent = 100 * $current / $max;

	$percent = sprintf("%.2f", $percent);

	$red = (int) $percent;

	return ($red > 100) ? array($percent, 100, 0) : array($percent, $red, 100 - $red);
}

/**
 * @todo implement check for dynamic table/row in SQL query
 */
function sub_records_rlike_count($field, $table, $where, $value, $subfield, $subtable, $subwhere, $a, $b) {
	$sql = Database::getInstance();

	if ($where != '') {
		$query = "SELECT $field AS field FROM $table WHERE $where = ?";
		$rs = exec_query($sql, $query, array($value));
	} else {
		$query = "SELECT $field AS field FROM $table";
		$rs = exec_query($sql, $query, array());
	}

	$result = 0;

	if ($rs->RowCount() == 0) {
		return $result;
	}
	while (!$rs->EOF) {
		$contents = $rs->fields['field'];

		if ($subwhere != '') {
			$query = "SELECT COUNT(*) AS cnt FROM $subtable WHERE $subwhere RLIKE ?";
		} else {
			return $result;
		}

		$subres = exec_query($sql, $query, array($a . $contents . $b));

		$result += $subres->fields['cnt'];

		$rs->MoveNext();
	}
	return $result;
}

function gen_select_lists(&$tpl, $user_month, $user_year) {
	global $crnt_month, $crnt_year;

	if (!$user_month == '' || !$user_year == '') {
		$crnt_month = $user_month;
		$crnt_year = $user_year;
	} else {
		$crnt_month = date("m");
		$crnt_year = date("Y");
	}

	for ($i = 1; $i <= 12; $i++) {
		$selected = ($i == $crnt_month) ? 'selected="selected"' : '';

		$tpl->assign(
			array(
				'OPTION_SELECTED' => $selected,
				'MONTH_VALUE' => $i
			)
		);

		$tpl->parse('MONTH_LIST', '.month_list');
	}

	for ($i = $crnt_year - 1; $i <= $crnt_year + 1; $i++) {
		$selected = ($i == $crnt_year) ? 'selected="selected"' : '';

		$tpl->assign(
			array(
				'OPTION_SELECTED' => $selected,
				'YEAR_VALUE' => $i
			)
		);

		$tpl->parse('YEAR_LIST', '.year_list');
	}
}

function get_user_name($user_id) {
	$sql = Database::getInstance();

	$query = "SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?";

	$rs = exec_query($sql, $query, array($user_id));

	return $rs->fields('admin_name');
}

function get_logo($user_id) {
	$sql = Database::getInstance();
	// check which logo we should return:
	$query = "
		SELECT
			`admin_id`, `created_by`, `admin_type`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($user_id));

	if ($rs->fields['admin_type'] == 'admin') {
		return get_admin_logo($user_id);
	} else {
		if(get_admin_logo($rs->fields['created_by']) === Config::get('IPS_LOGO_PATH').'/isp_logo.gif') {
			return get_admin_logo($user_id);
		} else {
			return get_admin_logo($rs->fields['created_by']);
		}
	}
}

function get_own_logo($user_id) {
	return get_admin_logo($user_id);
}

/**
 * @todo logo path shouldn't be hardcoded in this function, use a config file and/or global variable
 * hardcoded path is changed - TheCry
 */
function get_admin_logo($user_id) {
	$sql = Database::getInstance();

	$query = "SELECT `logo` FROM `user_gui_props` WHERE `user_id`= ?";

	$rs = exec_query($sql, $query, array($user_id));

	$user_logo = $rs->fields['logo'];

	if (empty($user_logo)) { // default logo
		return Config::get('IPS_LOGO_PATH') . '/isp_logo.gif';
	} else {
		return Config::get('IPS_LOGO_PATH') . '/' . $user_logo;
	}
}

function calc_bar_value($value, $value_max , $bar_width) {
	if ($value_max == 0) {
		return 0;
	} else {
		$ret_value = ($value * $bar_width) / $value_max;

		return ($ret_value > $bar_width) ? $bar_width : $ret_value;
	}
}

/**
 * log function
 */
function write_log($msg, $level = E_USER_WARNING) {

	$sql = Database::getInstance();
	global $send_log_to;

	if (isset($_SERVER['REMOTE_ADDR'])) {
		$client_ip = $_SERVER['REMOTE_ADDR'];
	} else {
		$client_ip = "unknown";
	}

	$msg = replace_html($msg . "<br /><small>User IP: " . $client_ip . "</small>", ENT_COMPAT, tr('encoding'));

	$query = "INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?)";

	exec_query($sql, $query, $msg, false);

	$msg = strip_tags(str_replace('<br />', "\n", $msg));

	$send_log_to = Config::get('DEFAULT_ADMIN_ADDRESS');

	// now send email if DEFAULT_ADMIN_ADDRESS != ''
	if ($send_log_to != '' && $level <= Config::get('LOG_LEVEL')) {
		global $default_hostname, $default_base_server_ip, $Version, $BuildDate, $admin_login;

		$admin_email = Config::get('DEFAULT_ADMIN_ADDRESS');
		$default_hostname = Config::get('SERVER_HOSTNAME');
		$default_base_server_ip = Config::get('BASE_SERVER_IP');
		$Version = Config::get('Version');
		$BuildDate = Config::get('BuildDate');

		$subject = "ispCP $Version on $default_hostname ($default_base_server_ip)";

		$to = $send_log_to;

		$message = <<<AUTO_LOG_MSG

ispCP Log

Server: $default_hostname ($default_base_server_ip)
Version: ispCP $Version ($BuildDate)

Message: ----------------[BEGIN]--------------------------

$msg

Message: ----------------[END]----------------------------

AUTO_LOG_MSG;

		$headers = "From: \"ispCP Logging Daemon\" <" . $admin_email . ">\n";

		$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";

		$headers .= "X-Mailer: ispCP $Version Logging Mailer";

		$mail_result = mail($to, $subject, $message, $headers);
		// reduce admin log entries by only logging email notification if not successful
		if (!$mail_result) {
			$mail_status = ($mail_result) ? 'OK' : 'NOT OK';

			$log_message = "$admin_login: Logging Daemon Mail To: |$to|, From: |$admin_email|, Status: |$mail_status|!";

			exec_query($sql, "INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?)", $log_message, false);
		}
	}
}

function send_add_user_auto_msg($admin_id, $uname, $upass, $uemail, $ufname, $ulname, $utype, $gender = '') {
	$admin_login = $_SESSION['user_logged'];

	$data = get_welcome_email($admin_id);

	$from_name = $data['sender_name'];
	$from_email = $data['sender_email'];
	$message = $data['message'];

	$base_vhost = Config::get('BASE_SERVER_VHOST');

	if ($from_name) {
		$from = '"' . encode($from_name) . "\" <" . $from_email . ">";
	} else {
		$from = $from_email;
	}

	if ($ufname && $ulname) {
		$to = '"' . encode($ufname . ' ' . $ulname) . "\" <" . $uemail . ">";
		$name = "$ufname $ulname";
	} else {
		$name = $uname;
		$to = $uemail;
	}

	$username = $uname;
	$password = $upass;
	$subject = $data['subject'];

	$search = array();
	$replace = array();

	$search [] = '{USERNAME}';
	$replace[] = decode_idna($username);
	$search [] = '{USERTYPE}';
	$replace[] = $utype;
	$search [] = '{NAME}';
	$replace[] = decode_idna($name);
	$search [] = '{PASSWORD}';
	$replace[] = $password;
	$search [] = '{BASE_SERVER_VHOST}';
	$replace[] = $base_vhost;
	$search [] = '{BASE_SERVER_VHOST_PREFIX}';
	$replace[] = Config::get('BASE_SERVER_VHOST_PREFIX');

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$subject = encode($subject);

	$headers = "From: " . $from . "\n";

	$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n";

	$headers .= "X-Mailer: ispCP " . Config::get('Version') . " Service Mailer";

	$mail_result = mail($to, $subject, $message, $headers);

	$mail_status = ($mail_result) ? 'OK' : 'NOT OK';

	write_log("$admin_login: Auto Add User To: |$name <$uemail>|, From: |$from_name <$from_email>|, Status: |$mail_status|!");
}

function update_reseller_props($reseller_id, $props) {
	$sql = Database::getInstance();

	if ($props == '') {
		return;
	}

	list($dmn_current, $dmn_max,
		$sub_current, $sub_max,
		$als_current, $als_max,
		$mail_current, $mail_max,
		$ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max,
		$sql_user_current, $sql_user_max,
		$traff_current, $traff_max,
		$disk_current, $disk_max
	) = explode (";", $props);

	$query = "
		UPDATE
			`reseller_props`
		SET
			`current_dmn_cnt` = ?,
			`max_dmn_cnt` = ?,
			`current_sub_cnt` = ?,
			`max_sub_cnt` = ?,
			`current_als_cnt` = ?,
			`max_als_cnt` = ?,
			`current_mail_cnt` = ?,
			`max_mail_cnt` = ?,
			`current_ftp_cnt` = ?,
			`max_ftp_cnt` = ?,
			`current_sql_db_cnt` = ?,
			`max_sql_db_cnt` = ?,
			`current_sql_user_cnt` = ?,
			`max_sql_user_cnt` = ?,
			`current_traff_amnt` = ?,
			`max_traff_amnt` = ?,
			`current_disk_amnt` = ?,
			`max_disk_amnt` = ?
		WHERE
			`reseller_id` = ?
	";

	$res = exec_query($sql, $query, array($dmn_current,
			$dmn_max,
			$sub_current,
			$sub_max,
			$als_current,
			$als_max,
			$mail_current,
			$mail_max,
			$ftp_current,
			$ftp_max,
			$sql_db_current,
			$sql_db_max,
			$sql_user_current,
			$sql_user_max,
			$traff_current,
			$traff_max,
			$disk_current,
			$disk_max,
			$reseller_id));
	return $res;
}

function gen_logged_from(&$tpl) {
	if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
		$tpl->assign(
			array(
				'YOU_ARE_LOGGED_AS' => tr('%1$s you are now logged as %2$s', $_SESSION['logged_from'], decode_idna($_SESSION['user_logged'])),
				'TR_GO_BACK' => tr('Go back')
			)
		);

		$tpl->parse('LOGGED_FROM', '.logged_from');
	} else {
		$tpl->assign('LOGGED_FROM', '');
	}
}

function change_domain_status(&$sql, $domain_id, $domain_name, $action, $location) {

	if ($action == 'disable') {
		$new_status = Config::get('ITEM_TODISABLED_STATUS');
	} else if ($action == 'enable') {
		$new_status = Config::get('ITEM_TOENABLE_STATUS');
	} else {
		return;
	}

	$query = "
		SELECT
			`mail_id`,
			`mail_pass`,
			`mail_type`
		FROM
			`mail_users`
		WHERE
			`domain_id` = ?
	";

	$rs = exec_query($sql, $query, array($domain_id));

	while (!$rs->EOF) {

		$mail_id = $rs->fields['mail_id'];
		$mail_pass = $rs->fields['mail_pass'];
		$mail_type = $rs->fields['mail_type'];

		if (Config::get('HARD_MAIL_SUSPENSION')) {
			$mail_status = $new_status;
		} else {
			if ($action == 'disable') {
				$timestamp = time();
				$pass_prefix = substr(md5($timestamp), 0, 4);
				if (preg_match("/^".MT_NORMAL_MAIL."/", $mail_type)
					|| preg_match("/^".MT_ALIAS_MAIL."/", $mail_type)
					|| preg_match("/^".MT_SUBDOM_MAIL."/", $mail_type)
					|| preg_match("/^".MT_ALSSUB_MAIL."/", $mail_type)) {
					$mail_pass = decrypt_db_password($mail_pass);
					$mail_pass = $pass_prefix . $mail_pass;
					$mail_pass = encrypt_db_password($mail_pass);
				}
			} else if ($action == 'enable') {
				if (preg_match("/^".MT_NORMAL_MAIL."/",$mail_type)
					|| preg_match("/^".MT_ALIAS_MAIL."/", $mail_type)
					|| preg_match("/^".MT_SUBDOM_MAIL."/", $mail_type)
					|| preg_match("/^".MT_ALSSUB_MAIL."/", $mail_type)) {
					$mail_pass = decrypt_db_password($mail_pass);
					$mail_pass = substr($mail_pass, 4, 50);
					$mail_pass = encrypt_db_password($mail_pass);
				}
			} else {
				return;
			}
			$mail_status = Config::get('ITEM_CHANGE_STATUS');
		}

		$query = "UPDATE `mail_users` SET `mail_pass` = ?, `status` = ? WHERE `mail_id` = ?";

		$rs2 = exec_query($sql, $query, array($mail_pass, $mail_status, $mail_id));

		$rs->MoveNext();
	}
	$query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?";

	$rs = exec_query($sql, $query, array($new_status, $domain_id));

	send_request();
	// let's get back to user overview after the system changes are finished
	$user_logged = $_SESSION['user_logged'];

	update_reseller_c_props(get_reseller_id($domain_id));

	if ($action == 'disable') {
		write_log("$user_logged: suspended domain: $domain_name");

		$_SESSION['user_disabled'] = 1;
	} else if ($action == 'enable') {
		write_log("$user_logged: enabled domain: $domain_name");

		$_SESSION['user_enabled'] = 1;
	} else {
		return;
	}

	if ($location == 'admin') {
		header("Location: manage_users.php");
	} else if ($location == 'reseller') {
		header("Location: users.php");
	}

	die();
}

/**
 * @todo use db prepared statements
 * @todo cleanup and/or comment confusing query salad
 */
function gen_admin_domain_query(&$search_query, &$count_query, $start_index,
	$rows_per_page, $search_for, $search_common, $search_status) {
	if ($search_for === 'n/a' && $search_common === 'n/a' && $search_status === 'n/a') {
		// We have pure list query;
		$count_query = "SELECT COUNT(*) AS cnt FROM `domain`";

		$search_query = "SELECT * FROM `domain` ORDER BY `domain_name` ASC LIMIT $start_index, $rows_per_page";
	} else if ($search_for === '' && $search_status != '') {
		if ($search_status === 'all') {
			$add_query = '';
		} else {
			$add_query = "WHERE `domain_status` = '$search_status'";
		}

		$count_query = "SELECT COUNT(*) AS cnt FROM `domain` $add_query";

		$search_query = "SELECT * FROM `domain` $add_query ORDER BY `domain_name` ASC LIMIT $start_index, $rows_per_page";

	} else if ($search_for != '') {
		if ($search_common === 'domain_name') {
			$add_query = "WHERE `admin_name` RLIKE '$search_for' %s";
		} else if ($search_common === 'customer_id') {
			$add_query = "WHERE `customer_id` RLIKE '$search_for' %s";
		} else if ($search_common === 'lname') {
			$add_query = "WHERE (`lname` RLIKE '$search_for' OR `fname` RLIKE '$search_for') %s";
		} else if ($search_common === 'firm') {
			$add_query = "WHERE `firm` RLIKE '$search_for' %s";
		} else if ($search_common === 'city') {
			$add_query = "WHERE `city` RLIKE '$search_for' %s";
		} else if ($search_common === 'state') {
			$add_query = "WHERE `state` RLIKE '$search_for' %s";
		} else if ($search_common === 'country') {
			$add_query = "WHERE `country` RLIKE '$search_for' %s";
		}

		if ($search_status != 'all') {
			// $add_query = sprintf($add_query, " AND t1.created_by = '$reseller_id' AND t2.domain_status = '$search_status'");
			$add_query = sprintf($add_query, " AND t2.`domain_status` = '$search_status'");

			$count_query = "
				SELECT
					COUNT(*) AS cnt
				FROM
					`admin` AS t1,
					`domain` AS t2
				$add_query
				AND
					t1.`admin_id` = t2.`domain_admin_id`
			";
		} else {
			$add_query = sprintf($add_query, " ");

			$count_query = "
				SELECT
					COUNT(*) AS cnt
				FROM
					`admin`
				$add_query
			";
		}

		$search_query = "
			SELECT
				t1.`admin_id`, t2.*
			FROM
				`admin` as t1,
				`domain` as t2
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

function gen_admin_domain_search_options(&$tpl, $search_for, $search_common,
	$search_status) {
	if ($search_for === 'n/a' && $search_common === 'n/a' && $search_status === 'n/a') {
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

function rm_rf_user_account($id_user) {
	$sql = Database::getInstance();

	// get domain user data
	$query = "
		SELECT
			`domain_id`,
			`domain_name`,
			`domain_gid`,
			`domain_created_id`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";
	$rs = exec_query($sql, $query, array($id_user));

	$domain_id = $rs->fields['domain_id'];
	$domain_name = $rs->fields['domain_name'];
	$domain_gid = $rs->fields['domain_gid'];
	$domain_created_id = $rs->fields['domain_created_id'];
	// we have all needed user data - let's delete all data for this user
	substract_from_reseller_props($domain_created_id, $domain_id);
	// BEGIN - DELETE ALL SYSTEM ENTRIES FOR THIS USER
	// first we'll delete all FTP accounts
	// delete all FTP accounts
	$query = "DELETE FROM `ftp_users` WHERE `gid` = ?";
	$rs = exec_query($sql, $query, array($domain_gid));

	// delete the group
	$query = "DELETE FROM `ftp_group` WHERE `gid` = ?";
	$rs = exec_query($sql, $query, array($domain_gid));

	// let's delete all subdomains for this user
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$query = "UPDATE `subdomain` SET `subdomain_status` = ? WHERE `domain_id` = ?";
	$rs = exec_query($sql, $query, array($delete_status, $domain_id));

	// let's delete all alises subdomains for this user
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$query = "UPDATE `subdomain_alias` SET `subdomain_alias_status` = ? WHERE `alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)";
	$rs = exec_query($sql, $query, array($delete_status, $domain_id));

	// let's delete all domain aliases for this user
	$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ?";
	$rs = exec_query($sql, $query, array($delete_status, $domain_id));

	// let's delete all mail accounts for this user
	$query = "UPDATE `mail_users` SET `status` = ? WHERE `domain_id` = ?";
	$rs = exec_query($sql, $query, array($delete_status, $domain_id));

	// delete all htaccess entries for this user
	$query = "DELETE FROM `htaccess` WHERE `dmn_id` = ?";
	$rs = exec_query($sql, $query, array($domain_id));

	$query = "DELETE FROM `htaccess_groups` WHERE `dmn_id` = ?";
	$rs = exec_query($sql, $query, array($domain_id));

	$query = "DELETE FROM `htaccess_users` WHERE `dmn_id` = ?";
	$rs = exec_query($sql, $query, array($domain_id));
	// end of delete htaccess entires

	// delete asociated traffic
	$query = "DELETE FROM `domain_traffic` WHERE `domain_id` = ?";
	$rs = exec_query($sql, $query, array($domain_id));

	// delete error pages
	$query = "DELETE FROM `error_pages` WHERE `user_id` = ?";
	$rs = exec_query($sql, $query, array($id_user));

	// delete quotalimits
	$query = "DELETE FROM `quotalimits` WHERE `name` = ?";
	$rs = exec_query($sql, $query, array($domain_name));

	// delete quotatallies
	$query = "DELETE FROM `quotatallies` WHERE `name` = ?";
	$rs = exec_query($sql, $query, array($domain_name));

	// let's delete SQL DBs and Users
	$query = "SELECT `sqld_id` FROM `sql_database` WHERE `domain_id` = ?";
	$rs = exec_query($sql, $query, array($domain_id));

	while (!$rs->EOF) {
		$db_id = $rs->fields['sqld_id'];
		delete_sql_database($sql, $domain_id, $db_id);

		$rs->MoveNext();
	}
	// END - DELETE ALL SYSTEM ENTRIES FOR THIS USER
	// BEGIN - DELETE ALL GUI ENTRIES FOR THIS USER
	// delete the layout settings

	// delete all tickets for this user
	$query = "DELETE FROM `tickets` WHERE `ticket_from` = ? OR `ticket_to` = ?";
	$rs = exec_query($sql, $query, array($id_user, $id_user));

	// let's delete the main domain for this user
	$query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_admin_id` = ?";
	$rs = exec_query($sql, $query, array($delete_status, $id_user));

	update_reseller_c_props($domain_created_id);

	remove_users_common_properties($id_user);
}

function remove_users_common_properties($id_user) {
	$sql = Database::getInstance();

	$query = "DELETE FROM `admin` WHERE `admin_id` = ?";
	$rs = exec_query($sql, $query, array($id_user));

	$query = "DELETE FROM `user_gui_props` WHERE `user_id` = ?";
	$rs = exec_query($sql, $query, array($id_user));
}

function substract_from_reseller_props($reseller_id, $domain_id) {
	// function update reseller props before deleting account
	list($rdmn_current, $rdmn_max,
		$rsub_current, $rsub_max,
		$rals_current, $rals_max,
		$rmail_current, $rmail_max,
		$rftp_current, $rftp_max,
		$rsql_db_current, $rsql_db_max,
		$rsql_user_current, $rsql_user_max,
		$rtraff_current, $rtraff_max,
		$rdisk_current, $rdisk_max
	) = generate_reseller_props($reseller_id);

	list($sub_current, $sub_max,
		$als_current, $als_max,
		$mail_current, $mail_max,
		$ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max,
		$sql_user_current, $sql_user_max,
		$traff_max, $disk_max
	) = generate_user_props($domain_id);

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
	) = generate_user_traffic($domain_id);

	$rdmn_current -= 1;

	$rsub_current -= $sub_current;
	$rals_current -= $als_current;
	$rmail_current -= $mail_current;
	$rftp_current -= $ftp_current;
	$rsql_db_current -= $sql_db_current;
	$rsql_user_current -= $sql_user_current;
	$rtraff_current -= $traff_current;
	$rdisk_current -= $disk_current;

	$rprops = "$rdmn_current;$rdmn_max;";
	$rprops .= "$rsub_current;$rsub_max;";
	$rprops .= "$rals_current;$rals_max;";
	$rprops .= "$rmail_current;$rmail_max;";
	$rprops .= "$rftp_current;$rftp_max;";
	$rprops .= "$rsql_db_current;$rsql_db_max;";
	$rprops .= "$rsql_user_current;$rsql_user_max;";
	$rprops .= "$rtraff_current;$rtraff_max;";
	$rprops .= "$rdisk_current;$rdisk_max;";

	update_reseller_props($reseller_id, $rprops);
}

/**
 * @todo use template(s) instead of hardcoded (X)HTML
 * @todo possible SESSION hijackin for $_SESSION['user_theme']
 */
function gen_purchase_haf(&$tpl, &$sql, $user_id, $encode = false) {
	$query = "
		SELECT
			`header`, `footer`
		FROM
			`orders_settings`
		WHERE
			`user_id` = ?
	";

	if (isset($_SESSION['user_theme'])) {
		$theme = $_SESSION['user_theme'];
	} else {
		$theme = Config::get('USER_INITIAL_THEME');
	}

	$rs = exec_query($sql, $query, array($user_id));

	if ($rs->RecordCount() == 0) {
		$title = tr("ispCP - Order Panel");
		$header = <<<RIC
<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{$title}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<link href="../themes/{$theme}/css/ispcp_orderpanel.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<div align="center">
			<table width="100%" style="height:95%">
				<tr>
					<td align="center">
RIC;

		$footer = <<<RIC
					</td>
				</tr>
			</table>
		</div>
	</body>
</html>
RIC;
	} else {
		$header = $rs->fields['header'];
		$footer = $rs->fields['footer'];

		$header = str_replace ('\\', '', $header);
		$footer = str_replace ('\\', '', $footer);
	}

	if ($encode) {
		$header = htmlentities($header, ENT_COMPAT, 'UTF-8');
		$footer = htmlentities($footer, ENT_COMPAT, 'UTF-8');
	}

	$tpl->assign('PURCHASE_HEADER', $header);
	$tpl->assign('PURCHASE_FOOTER', $footer);
}

function setConfig_Value($name, $value) {
	$sql = Database::getInstance();

	$query = "SELECT `name` FROM `config` WHERE `name`= ?";

	$res = exec_query($sql, $query, array($name));

	if ($res->RecordCount() == 0) {
		$query = "INSERT INTO `config` (`name`, `value`) VALUES (?, ?)";

		exec_query($sql, $query, array($name, $value));
	} else {
		$query = "UPDATE `config` SET `value` = ? WHERE `name`= ?";

		$res = exec_query($sql, $query, array($value, $name));
	}

	Config::set($name, $value);

	return true;
}

/**
 * Get language dependend priority string
 *
 * @param integer $ticket_urgency values from 1 to 4
 * @return string language dependend priority string
 */
function get_ticket_urgency($ticket_urgency) {
	switch ($ticket_urgency) {
		case 1:
			$result = tr("Low");
			break;
		case 2:
			$result = tr("Medium");
			break;
		case 3:
			$result = tr("High");
			break;
		case 4:
			$result = tr("Very high");
			break;
		default:
			$result = tr("Medium");
			break;
	}
	return $result;
}
