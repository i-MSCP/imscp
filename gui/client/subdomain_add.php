<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2008 by ispCP | http://isp-control.net
 * @version	SVN: $Id$
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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/subdomain_add.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('als_list', 'page');

// page functions.

function check_subdomain_permissions($sql, $user_id) {
	$props = get_domain_default_props($sql, $user_id, true);

	$dmn_id = $props['domain_id'];
	$dmn_name = $props['domain_name'];
	$dmn_subd_limit = $props['domain_subd_limit'];

	$sub_cnt = get_domain_running_sub_cnt($sql, $dmn_id);

	if ($dmn_subd_limit != 0 && $sub_cnt >= $dmn_subd_limit) {
		set_page_message(tr('Subdomains limit reached!'));
		header("Location: domains_manage.php");
		die();
	}

	if (@$_POST['dmn_type'] == 'als') {
		$query_alias = "
			SELECT
				`alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`alias_id` = ?
		";
		$rs = exec_query($sql, $query_alias, array($_POST['als_id']));
		return $rs->fields['alias_name'];
	}
	return $dmn_name; // Will be used in subdmn_exists()
}

function gen_user_add_subdomain_data(&$tpl, &$sql, $user_id) {
	$query = "
		SELECT
			`domain_name`,
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($user_id));
	$domainname = decode_idna($rs->fields['domain_name']);
	$tpl->assign(
		array(
			'DOMAIN_NAME'		=> '.' . $domainname,	
			'SUB_DMN_CHECKED'	=> 'checked="checked"',
			'SUB_ALS_CHECKED'	=> ''
		)
	);
	gen_dmn_als_list($tpl, $sql, $rs->fields['domain_id'], 'no');

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_subd') {
		$tpl->assign(
			array(
				'SUBDOMAIN_NAME' => clean_input($_POST['subdomain_name']),
				'SUBDOMAIN_MOUNT_POINT' => clean_input($_POST['subdomain_mnt_pt'])
			)
		);
	} else {
		$tpl->assign(
			array(
				'SUBDOMAIN_NAME' => '',
				'SUBDOMAIN_MOUNT_POINT' => ''
			)
		);
	}

	return $rs->fields['domain_name'];
}

function gen_dmn_als_list(&$tpl, &$sql, $dmn_id, $post_check) {
	$ok_status = Config::get('ITEM_OK_STATUS');

	$query = "
		SELECT
			`alias_id`, `alias_name`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = ?
		AND
			`alias_status` = ?
		ORDER BY
			`alias_name`
	";

	$rs = exec_query($sql, $query, array($dmn_id, $ok_status));
	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'ALS_ID' => '0',
				'ALS_SELECTED' => 'selected="selected"',
				'ALS_NAME' => tr('Empty list')
			)
		);
		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('TO_ALIAS_DOMAIN', '');
		$_SESSION['alias_count'] = "no";
	} else {
		$first_passed = false;
		while (!$rs->EOF) {
			if ($post_check === 'yes') {
				$als_id = (!isset($_POST['als_id'])) ? '' : $_POST['als_id'];

				$als_selected = ($als_id == $rs->fields['alias_id']) ? 'selected="selected"' : '';
			} else {
				$als_selected = (!$first_passed) ? 'selected="selected"' : '';
			}

			$alias_name = decode_idna($rs->fields['alias_name']);
			$tpl->assign(
				array(
					'ALS_ID' => $rs->fields['alias_id'],
					'ALS_SELECTED' => $als_selected,
					'ALS_NAME' => $alias_name
				)
			);
			$tpl->parse('ALS_LIST', '.als_list');
			$rs->MoveNext();

			if (!$first_passed) $first_passed = true;
		}
	}
}


function subdmn_exists(&$sql, $user_id, $domain_id, $sub_name) {
	global $dmn_name;

	if ($_POST['dmn_type'] == 'als') {
		$query_subdomain = "
			SELECT
				count(`subdomain_alias_id`) as cnt
			FROM
				`subdomain_alias`
			WHERE
				`alias_id` = ?
			AND
				`subdomain_alias_name` = ?
		";
	
		$query_domain = "
			SELECT
				count(`alias_id`) as cnt
			FROM
				`domain_aliasses`
			WHERE
				`alias_name` = ?
		";
	} else {
		$query_subdomain = "
			SELECT
				count(`subdomain_id`) as cnt
			FROM
				`subdomain`
			WHERE
				`domain_id` = ?
			AND
				`subdomain_name` = ?
		";
	
		$query_domain = "
			SELECT
				count(`domain_id`) as cnt
			FROM
				`domain`
			WHERE
				`domain_name` = ?
		";
	}
	$domain_name = $sub_name . "." . $dmn_name;

	$rs_subdomain = exec_query($sql, $query_subdomain, array($domain_id, $sub_name));
	$rs_domain = exec_query($sql, $query_domain, array($domain_name));

	$std_subs = array( 'www', 'mail', 'webmail', 'pop', 'pop3', 'imap', 'smtp', 'pma', 'relay', 'ftp', 'ns1', 'ns2', 'localhost' );

	if ($rs_subdomain->fields['cnt'] == 0 && $rs_domain->fields['cnt'] == 0 && !in_array($sub_name, $std_subs)) {
		return false;
	}

	return true;
}

function subdmn_mnt_pt_exists(&$sql, $user_id, $domain_id, $sub_name, $sub_mnt_pt) {

	if ($_POST['dmn_type'] == 'als') {
		$query = "
			SELECT
				count(`subdomain_alias_id`) as cnt
			FROM
				`subdomain_alias`
			WHERE
				`alias_id` = ?
			AND
				`subdomain_alias_mount` = ?
		";
		unset($query2);
		unset($rs2);
	} else {
		$query = "
			SELECT
				count(`subdomain_id`) as cnt
			FROM
				`subdomain`
			WHERE
				`domain_id` = ?
			AND
				`subdomain_mount` = ?
		";
	
		$query2 = "
			SELECT
				count(`alias_id`) as cnt
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = ?
			AND
				`alias_mount` = ?
		";
	}
	$rs = exec_query($sql, $query, array($domain_id, $sub_mnt_pt));
	if (isset($query2))
		$rs2 = exec_query($sql, $query2, array($domain_id, $sub_mnt_pt));

	if ($rs->fields['cnt'] > 0 || (isset($rs2) && $rs2->fields['cnt'] > 0))
		return true;

	return false;
}

function subdomain_schedule(&$sql, $user_id, $domain_id, $sub_name, $sub_mnt_pt) {
	$status_add = Config::get('ITEM_ADD_STATUS');

	check_for_lock_file();
	if ($_POST['dmn_type'] == 'als') {
		$query = "
			INSERT INTO
				`subdomain_alias`
					(`alias_id`,
					`subdomain_alias_name`,
					`subdomain_alias_mount`,
					`subdomain_alias_status`)
			VALUES
				(?, ?, ?, ?)
		";
	} else {
		$query = "
			INSERT INTO
				`subdomain`
					(`domain_id`,
					`subdomain_name`,
					`subdomain_mount`,
					`subdomain_status`)
			VALUES
				(?, ?, ?, ?)
		";
	}

	$rs = exec_query($sql, $query, array($domain_id, $sub_name, $sub_mnt_pt, $status_add));

	$sub_id = $sql->Insert_ID();

	// We do not need to create the default mail addresses, subdomains are
	// related to their domains.

	write_log($_SESSION['user_logged'] . ": adds new subdomain: " . $sub_name);
	send_request();
}

function check_subdomain_data(&$tpl, &$sql, $user_id, $dmn_name) {
	$dmn_id = $domain_id = get_user_domain_id($sql, $user_id);

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_subd') {
		if (empty($_POST['subdomain_name'])) {
			set_page_message(tr('Please specify subdomain name!'));
			return;
		}

		$sub_name = strtolower($_POST['subdomain_name']);
		$sub_name = encode_idna($sub_name);

		if (isset($_POST['subdomain_mnt_pt']) && $_POST['subdomain_mnt_pt'] !== '') {
			$sub_mnt_pt = strtolower($_POST['subdomain_mnt_pt']);
			$sub_mnt_pt = array_encode_idna($sub_mnt_pt, true);
		} else {
			$sub_mnt_pt = "/";
		}
		
		if ($_POST['dmn_type'] === 'als') {
			if (!isset($_POST['als_id'])) {
				set_page_message(tr('No valid alias domain selected!'));
				return;
			}
			$query_alias = "
				SELECT
					`alias_mount`
				FROM
					`domain_aliasses`
				WHERE
					`alias_id` = ?
			";
	
			$rs = exec_query($sql, $query_alias, array($_POST['als_id']));
			$als_mnt = $rs->fields['alias_mount'];
			if ($sub_mnt_pt[0] != '/')
				$sub_mnt_pt = '/'.$sub_mnt_pt;
			$sub_mnt_pt = $als_mnt.$sub_mnt_pt;
			$sub_mnt_pt = str_replace( '//', '/', $sub_mnt_pt );
			$domain_id = $_POST['als_id'];
		}

		if (subdmn_exists($sql, $user_id, $domain_id, $sub_name)) {
			set_page_message(tr('Subdomain already exists or is not allowed!'));
		} else if (!chk_subdname($sub_name . "." . $dmn_name)) {
			set_page_message(tr('Wrong subdomain syntax!'));
		} else if (mount_point_exists($dmn_id, array_decode_idna($sub_mnt_pt, true))) {
			set_page_message(tr('Mount point already in use!'));
		} else if (!chk_mountp($sub_mnt_pt)) {
			set_page_message(tr('Incorrect mount point syntax'));
		} else {
			// now lets fix the mountpoint
			$sub_mnt_pt = array_decode_idna($sub_mnt_pt, true);

			subdomain_schedule($sql, $user_id, $domain_id, $sub_name, $sub_mnt_pt);
			set_page_message(tr('Subdomain scheduled for addition!'));
			header('Location:domains_manage.php');
			exit(0);
		}
	}
}

// common page data.

// check User sql permision
if (isset($_SESSION['subdomain_support']) && $_SESSION['subdomain_support'] == "no") {
	header("Location: index.php");
}

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_ADD_SUBDOMAIN_PAGE_TITLE' => tr('ispCP - Client/Add Subdomain'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

$dmn_name = check_subdomain_permissions($sql, $_SESSION['user_id']);
gen_user_add_subdomain_data($tpl, $sql, $_SESSION['user_id']);
check_subdomain_data($tpl, $sql, $_SESSION['user_id'], $dmn_name);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_manage_domains.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_ADD_SUBDOMAIN' => tr('Add subdomain'),
		'TR_SUBDOMAIN_DATA' => tr('Subdomain data'),
		'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
		'TR_DIR_TREE_SUBDOMAIN_MOUNT_POINT' => tr('Directory tree mount point'),
		'TR_ADD' => tr('Add'),
		'TR_DMN_HELP' => tr("You do not need 'www.' ispCP will add it on its own.")
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

?>
