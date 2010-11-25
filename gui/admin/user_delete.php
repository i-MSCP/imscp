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

require '../include/imscp-lib.php';

check_login(__FILE__);

$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/user_delete.tpl');

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

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Delete Domain'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
	)
);

if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
	if (validate_user_deletion(intval($_GET['delete_id']))) {
		delete_user(intval($_GET['delete_id']));
	} else {
		user_goto('manage_users.php');
	}
} else if (isset($_GET['domain_id']) && is_numeric($_GET['domain_id'])) {
	validate_domain_deletion(intval($_GET['domain_id']));
} else if (isset($_POST['domain_id']) && is_numeric($_POST['domain_id'])
	&& isset($_POST['delete']) && $_POST['delete'] == 1) {
	delete_domain((int)$_POST['domain_id'], 'manage_users.php');
} else {
	set_page_message(tr('Wrong domain ID!'));
	user_goto('manage_users.php');
}

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

/**
 * Delete user
 * @param integer $user_id User ID to delete
 */
function delete_user($user_id) {

	global $sql;
	$cfg = iMSCP_Registry::get('Config');

	$query = "
		SELECT
			a.`admin_type`,
			b.`logo`
		FROM `admin` AS a
		LEFT JOIN
			`user_gui_props` AS b ON b.`user_id` = a.`admin_id`
		WHERE
			`admin_id` = ?";
	$res = exec_query($sql, $query, $user_id);
	$data = $res->fetchRow();
	$type = $data['admin_type'];
	if (empty($type) || $type == 'user') {
		set_page_message(tr('Invalid user id!'));
		user_goto('manage_users.php');
	}

	if ($type == 'reseller') {
		$reseller_logo = $data['logo'];
		// delete reseller props
		$query = "DELETE FROM `reseller_props` WHERE `reseller_id` = ?";
		exec_query($sql, $query, $user_id);
		// delete hosting plans
		$query = "DELETE FROM `hosting_plans` WHERE `reseller_id` = ?";
		exec_query($sql, $query, $user_id);
		// delete all software
		delete_reseller_software($user_id);
		$query = "DELETE FROM `web_software` WHERE `reseller_id` = ?";
		exec_query($sql, $query, array($user_id));
		// delete reseller logo if exists
		if (!empty($reseller_logo) && $reseller_logo !== 0) {
			try {
				unlink($cfg->IPS_LOGO_PATH . '/' . $reseller_logo);
			} catch(Exception $e) {
				set_page_message(tr('Logo could not be deleted:') . " " . $e->getMessage());
			}
		}
	}
	
	// Delete i-mscp login:
	$query = "DELETE FROM `admin` WHERE `admin_id` = ?";
	exec_query($sql, $query, $user_id);

	write_log($_SESSION['user_logged'] .": deletes user " . $user_id);

	$_SESSION['ddel'] = '_yes_';
	user_goto('manage_users.php');
}

/**
 * Delete reseller software pakets
 * @param integer $user_id Reseller ID to delete software pakets
 */
function delete_reseller_software($user_id) {
	global $sql, $cfg;

	$query = "
		SELECT
			`software_id`,
			`software_archive`
		FROM
			`web_software`
		WHERE
			`reseller_id` = ?
	";
	$res = exec_query($sql, $query, array($user_id));
	if ($res->RecordCount() > 0) {
		while (!$res ->EOF) {
			$del_path = $cfg->GUI_SOFTWARE_DIR."/".$user_id."/".$res->fields['software_archive']."-".$res->fields['software_id'].".tar.gz";
			@unlink($del_path);
			$res->MoveNext();
		}
	}
	$del_dir = $cfg->GUI_SOFTWARE_DIR."/".$user_id."/";
	if(is_dir($del_dir)) @rmdir($del_dir);
}

/**
 * Validate if delete process is valid
 * @param integer $user_id User-ID to delete
 * @return boolean true = deletion can be done
 */
function validate_user_deletion($user_id) {

	global $sql;

	$result = false;

	// check if there are domains created by user
	$query = "SELECT COUNT(`domain_id`) AS `num_domains` FROM `domain` WHERE `domain_created_id` = ?";
	$res = exec_query($sql, $query, $user_id);
	$data = $res->fetchRow();
	if ($data['num_domains'] == 0) {
		$query = "SELECT `admin_type` FROM `admin` WHERE `admin_id` = ?";
		$res = exec_query($sql, $query, $user_id);
		$data = $res->fetchRow();
		$type = $data['admin_type'];
		if ($type == 'admin' || $type == 'reseller') {
			$result = true;
		} else {
			set_page_message(tr('Invalid user id!'));
		}
	} else {
		set_page_message(tr('There are active domains of reseller/admin!'));
	}

	return $result;
}

/**
 * Validate domain deletion, display all items to delete
 * @param integer $domain_id
 */
function validate_domain_deletion($domain_id) {

	global $tpl, $sql;

	// check for domain owns
	$query = "SELECT `domain_id`, `domain_name`, `domain_created_id` FROM `domain` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	$data = $res->fetchRow();
	if ($data['domain_id'] == 0) {
		set_page_message(tr('Wrong domain ID!'));
		user_goto('manage_users.php');
	}

	$reseller = $data['domain_created_id'];

	$tpl->assign(
		array(
			'TR_DELETE_DOMAIN'	=> tr('Delete domain'),
			'TR_DOMAIN_SUMMARY'	=> tr('Domain summary:'),
			'TR_DOMAIN_EMAILS'	=> tr('Domain e-mails:'),
			'TR_DOMAIN_FTPS'	=> tr('Domain FTP accounts:'),
			'TR_DOMAIN_ALIASES'	=> tr('Domain aliases:'),
			'TR_DOMAIN_SUBS'	=> tr('Domain subdomains:'),
			'TR_DOMAIN_DBS'		=> tr('Domain databases:'),
			'TR_REALLY_WANT_TO_DELETE_DOMAIN'	=> tr('Do you really want to delete the entire domain? This operation cannot be undone!'),
			'TR_BUTTON_DELETE'	=> tr('Delete domain'),
			'TR_YES_DELETE_DOMAIN'	=> tr('Yes, delete the domain.'),
			'DOMAIN_NAME'		=> tohtml($data['domain_name']),
			'DOMAIN_ID'			=> $data['domain_id']
		)
	);

	// check for mail acc in MAIN domain
	$query = "SELECT * FROM `mail_users` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {
		while (!$res->EOF) {

			// Create mail type's text
			$mail_types = explode(',', $res->fields['mail_type']);
			$mdisplay_a = array();
			foreach ($mail_types as $mtype) {
				$mdisplay_a[] = user_trans_mail_type($mtype);
			}
			$mdisplay_txt = implode(', ', $mdisplay_a);

			$tpl->assign(
				array(
					'MAIL_ADDR' => tohtml($res->fields['mail_addr']),
					'MAIL_TYPE' => $mdisplay_txt
				)
			);

			$tpl->parse('MAIL_ITEM', '.mail_item');
			$res->moveNext();
		}
	} else {
		$tpl->assign('MAIL_LIST', '');
	}

	// check for ftp acc in MAIN domain
	$query = "SELECT `ftp_users`.* FROM `ftp_users`, `domain` WHERE `domain`.`domain_id` = ? AND `ftp_users`.`uid` = `domain`.`domain_uid`";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {
		while (!$res->EOF) {

			$tpl->assign(
				array(
					'FTP_USER' => tohtml($res->fields['userid']),
					'FTP_HOME' => tohtml($res->fields['homedir'])
				)
			);

			$tpl->parse('FTP_ITEM', '.ftp_item');
			$res->moveNext();
		}
	} else {
		$tpl->assign('FTP_LIST', '');
	}

	// check for alias domains
	$alias_a = array();
	$query = "SELECT * FROM `domain_aliasses` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {
		while (!$res->EOF) {
			$alias_a[] = $res->fields['alias_id'];

			$tpl->assign(
				array(
					'ALS_NAME' => tohtml($res->fields['alias_name']),
					'ALS_MNT' => tohtml($res->fields['alias_mount'])
				)
			);

			$tpl->parse('ALS_ITEM', '.als_item');
			$res->moveNext();
		}
	} else {
		$tpl->assign('ALS_LIST', '');
	}

	// check for subdomains
	$any_sub_found = false;
	$query = "SELECT * FROM `subdomain` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	while (!$res->EOF) {
		$any_sub_found = true;
		$tpl->assign(
			array(
				'SUB_NAME' => tohtml($res->fields['subdomain_name']),
				'SUB_MNT' => tohtml($res->fields['subdomain_mount'])
			)
		);

		$tpl->parse('SUB_ITEM', '.sub_item');
		$res->moveNext();
	}

	if (!$any_sub_found) {
		$tpl->assign('SUB_LIST', '');
	}

	// Check subdomain_alias
	if (count($alias_a) > 0) {
		$query = "SELECT * FROM `subdomain_alias` WHERE `alias_id` IN (";
		$query .= implode(',', $alias_a);
		$query .= ")";
		$res = exec_query($sql, $query);
		while (!$res->EOF) {
			$any_sub_found = true;
			$tpl->assign(
				array(
					'SUB_NAME' => tohtml($res->fields['subdomain_alias_name']),
					'SUB_MNT' => tohtml($res->fields['subdomain_alias_mount'])
				)
			);

			$tpl->parse('SUB_ITEM', '.sub_item');
			$res->moveNext();
		}
	}

	// Check for databases and -users
	$query = "SELECT * FROM `sql_database` WHERE `domain_id` = ?";
	$res = exec_query($sql, $query, $domain_id);
	if (!$res->EOF) {

		while (!$res->EOF) {

			$query = "SELECT * FROM `sql_user` WHERE `sqld_id` = ?";
			$ures = exec_query($sql, $query, $res->fields['sqld_id']);

			$users_a = array();
			while (!$ures->EOF) {
				$users_a[] = $ures->fields['sqlu_name'];
				$ures->moveNext();
			}
			$users_txt = implode(', ', $users_a);

			$tpl->assign(
				array(
					'DB_NAME' => tohtml($res->fields['sqld_name']),
					'DB_USERS' => tohtml($users_txt)
				)
			);

			$tpl->parse('DB_ITEM', '.db_item');
			$res->moveNext();
		}
	} else {
		$tpl->assign('DB_LIST', '');
	}
}
