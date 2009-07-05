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
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/domain_delete.tpl');

$tpl->define_dynamic('mail_list', 'page');
$tpl->define_dynamic('ftp_list', 'page');
$tpl->define_dynamic('als_list', 'page');
$tpl->define_dynamic('sub_list', 'page');
$tpl->define_dynamic('db_list', 'page');

$tpl->define_dynamic('mail_item', 'mail_list');
$tpl->define_dynamic('sub_item', 'sub_list');
$tpl->define_dynamic('als_item', 'als_list');
$tpl->define_dynamic('ftp_item', 'ftp_list');
$tpl->define_dynamic('db_item', 'db_list');

$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('ispCP - Delete Domain'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
	)
);

if (isset($_GET['domain_id']) && is_numeric($_GET['domain_id'])) {
	validate_domain_deletion(intval($_GET['domain_id']));
} else if (isset($_POST['domain_id']) && is_numeric($_POST['domain_id'])
	&& isset($_POST['delete']) && $_POST['delete'] == 1) {
	delete_domain($_POST['domain_id']);
} else {
	set_page_message(tr('Wrong domain ID!'));
	user_goto('users.php');
}

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_users_manage.tpl');

gen_logged_from($tpl);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}

/**
 * Delete domain with all sub items
 * @param integer $domain_id
 */
function delete_domain($domain_id) {
	global $sql;

	$reseller_id = $_SESSION['user_id'];
	// Get uid and gid of domain user
	$res = exec_query($sql,  "SELECT `domain_uid`, `domain_gid`, `domain_admin_id`, `domain_name`"
							." FROM `domain` WHERE `domain_id` = ? AND `domain_created_id` = ?",
						array($domain_id, $reseller_id));
	$data = $res->FetchRow();
	if (empty($data['domain_uid']) || empty($data['domain_admin_id'])) {
		set_page_message(tr('Wrong domain ID!'));
		user_goto('users.php');
	}

	$domain_admin_id = $data['domain_admin_id'];
	$domain_name = $data['domain_name'];
	$domain_uid = $data['domain_uid'];
	$domain_gid = $data['domain_gid'];

	$delete_status = Config::get('ITEM_DELETE_STATUS');

	// Mail users:
	exec_query($sql, "UPDATE `mail_users` SET `status` = '" . $delete_status . "' WHERE `domain_id` = ?", array($domain_id));

	// Protected areas:
	$query = "UPDATE `htaccess` SET `status` = '$delete_status' WHERE `dmn_id` = ?";
	exec_query($sql, $query, array($domain_id));

	// Protected area groups:
	$query = "UPDATE `htaccess_groups` SET `status` = '$delete_status' WHERE `dmn_id` = ?";
	exec_query($sql, $query, array($domain_id));

	// Protected area users
	$query = "UPDATE `htaccess_users` SET `status` = '$delete_status' WHERE `dmn_id` = ?";
	exec_query($sql, $query, array($domain_id));

	// Delete subdomain aliases:
	$alias_a = array();
	$query = "SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, array($domain_id));
	while (!$res->EOF) {
		$alias_a[] = $res->fields['alias_id'];
		$res->MoveNext();
	}
	if (count($alias_a) > 0) {
		$query = "UPDATE `subdomain_alias` SET `subdomain_alias_status` = '" . $delete_status . "' WHERE `alias_id` IN (";
		$query .= implode(',', $alias_a);
		$query .= ")";
		exec_query($sql, $query);
	}

	// Delete SQL databases and users
	$query = "SELECT `sqld_id` FROM `sql_database` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, array($domain_id));
	while (!$res->EOF) {
		delete_sql_database($sql, $domain_id, $res->fields['sqld_id']);
		$res->MoveNext();
	}

	// Domain aliases:
	exec_query($sql, "UPDATE `domain_aliasses` SET `alias_status` = '" . $delete_status . "' WHERE `domain_id` = ?", array($domain_id));

	// Remove domain traffic
	$query = "DELETE FROM `domain_traffic` WHERE `domain_id` = ?";
	exec_query($sql, $query, array($domain_id));

	// Delete domain DNS entries
	$query = "DELETE FROM `domain_dns` WHERE `domain_id` = ?";
	exec_query($sql, $query, array($domain_id));

	// Set domain deletion status
	$query = "UPDATE `domain` SET `domain_status` = 'delete' WHERE `domain_id` = ?";
	exec_query($sql, $query, array($domain_id));

	// --- Activate daemon ---
	send_request();

	// Delete FTP users:
	$query = "DELETE FROM `ftp_users` WHERE `uid` = ?";
	exec_query($sql, $query, array($domain_uid));

	// Delete FTP groups:
	$query = "DELETE FROM `ftp_group` WHERE `gid` = ?";
	exec_query($sql, $query, array($domain_gid));

	// Delete ispcp login:
	$query = "DELETE FROM `admin` WHERE `admin_id` = ?";
	exec_query($sql, $query, array($domain_admin_id));

	// Delete the quota section:
	$query = "DELETE FROM `quotalimits` WHERE `name` = ?";
	exec_query($sql, $query, array($domain_admin_id));

	// Remove support tickets:
	$query = "DELETE FROM `tickets` WHERE ticket_from = ? OR ticket_to = ?";
	exec_query($sql, $query, array($domain_admin_id, $domain_admin_id));

	write_log($_SESSION['user_logged'] .": deletes domain " . $domain_name);

	update_reseller_c_props($_SESSION['user_id']);

	$_SESSION['ddel'] = '_yes_';
	user_goto('users.php');
}

/**
 * Validate domain deletion, display all items to delete
 * @param integer $domain_id
 */
function validate_domain_deletion($domain_id) {
	global $tpl, $sql;

	$reseller = $_SESSION['user_id'];

	/* check for domain owns */
	$query = "SELECT `domain_id`, `domain_name` FROM `domain` WHERE `domain_id` = ? AND `domain_created_id` = ?";
	$res = exec_query($sql, $query, array($domain_id, $reseller));
	$data = $res->FetchRow();
	if ($data['domain_id'] == 0) {
		set_page_message(tr('Wrong domain ID!'));
		user_goto('users.php');
	}

	$tpl->assign(array(
		'TR_DELETE_DOMAIN'=>tr('Delete domain'),
		'TR_DOMAIN_SUMMARY'=>tr('Domain summary:'),
		'TR_DOMAIN_EMAILS'=>tr('Domain e-mails:'),
		'TR_DOMAIN_FTPS'=>tr('Domain FTP accounts:'),
		'TR_DOMAIN_ALIASES'=>tr('Domain aliases:'),
		'TR_DOMAIN_SUBS'=>tr('Domain subdomains:'),
		'TR_DOMAIN_DBS'=>tr('Domain databases:'),
		'TR_REALLY_WANT_TO_DELETE_DOMAIN'=>tr('Do you really want to delete the entire domain? This operation can not be undone!'),
		'TR_BUTTON_DELETE'=>tr('Delete domain'),
		'TR_YES_DELETE_DOMAIN'=>tr('Yes, delete the domain.'),
		'DOMAIN_NAME'=>$data['domain_name'],
		'DOMAIN_ID'=>$data['domain_id']
	));

	/* check for mail acc in MAIN domain */
	$query = "SELECT * FROM `mail_users` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, array($domain_id));
	if (!$res->EOF) {
		while (!$res->EOF) {

			// Create mail type's text
			$mail_types = explode(',', $res->fields['mail_type']);
			$mdisplay_a = array();
			foreach ($mail_types as $mtype) {
				$mdisplay_a[] = user_trans_mail_type($mtype);
			}
			$mdisplay_txt = implode(', ', $mdisplay_a);

			$tpl->assign(array(
				'MAIL_ADDR'=>$res->fields['mail_addr'],
				'MAIL_TYPE'=>$mdisplay_txt
			));

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$res->MoveNext();
		}
	} else {
		$tpl->assign('MAIL_LIST', '');
	}

	/* check for ftp acc in MAIN domain */
	$query = "SELECT `ftp_users`.* FROM `ftp_users`, `domain` WHERE `domain`.`domain_id` = ? AND `ftp_users`.`uid` = `domain`.`domain_uid`";
	$res = exec_query($sql, $query, array($domain_id));
	if (!$res->EOF) {
		while (!$res->EOF) {

			$tpl->assign(array(
				'FTP_USER'=>$res->fields['userid'],
				'FTP_HOME'=>$res->fields['homedir']
			));

			$tpl->parse('FTP_ITEM', '.ftp_item');
			$res->MoveNext();
		}
	} else {
		$tpl->assign('FTP_LIST', '');
	}

	/* check for alias domains */
	$alias_a = array();
	$query = "SELECT * FROM `domain_aliasses` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, array($domain_id));
	if (!$res->EOF) {
		while (!$res->EOF) {
			$alias_a[] = $res->fields['alias_id'];

			$tpl->assign(array(
				'ALS_NAME'=>$res->fields['alias_name'],
				'ALS_MNT'=>$res->fields['alias_mount']
			));

			$tpl->parse('ALS_ITEM', '.als_item');
			$res->MoveNext();
		}
	} else {
		$tpl->assign('ALS_LIST', '');
	}

	/* check for subdomains */
	$any_sub_found = false;
	$query = "SELECT * FROM `subdomain` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, array($domain_id));
	while (!$res->EOF) {
		$any_sub_found = true;
		$tpl->assign(array(
			'SUB_NAME'=>$res->fields['subdomain_name'],
			'SUB_MNT'=>$res->fields['subdomain_mount']
		));

		$tpl->parse('SUB_ITEM', '.sub_item');
		$res->MoveNext();
	}

	if (!$any_sub_found) {
		$tpl->assign('SUB_LIST', '');
	}

	// Check subdomain_alias
	if (count($alias_a) > 0) {
		$query = "SELECT * FROM `subdomain_alias` WHERE `alias_id` IN (";
		$query .= implode(',', $alias_a);
		$query .= ")";
		$res = exec_query($sql, $query, array($domain_id));
		while (!$res->EOF) {
			$any_sub_found = true;
			$tpl->assign(array(
				'SUB_NAME'=>$res->fields['subdomain_alias_name'],
				'SUB_MNT'=>$res->fields['subdomain_alias_mount']
			));

			$tpl->parse('SUB_ITEM', '.sub_item');
			$res->MoveNext();
		}
	}

	/* Check for databases and -users */
	$query = "SELECT * FROM `sql_database` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, array($domain_id));
	if (!$res->EOF) {

		while (!$res->EOF) {

			$query = "SELECT * FROM `sql_user` WHERE `sqld_id` = ?";
			$ures = exec_query($sql, $query, array($res->fields['sqld_id']));

			$users_a = array();
			while (!$ures->EOF) {
				$users_a[] = $ures->fields['sqlu_name'];
				$ures->MoveNext();
			}
			$users_txt = implode(', ', $users_a);

			$tpl->assign(array(
				'DB_NAME'=>$res->fields['sqld_name'],
				'DB_USERS'=>$users_txt
			));

			$tpl->parse('DB_ITEM', '.db_item');
			$res->MoveNext();
		}
	} else {
		$tpl->assign('DB_LIST', '');
	}

}
