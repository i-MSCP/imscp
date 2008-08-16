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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/domains_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('als_message', 'page');
$tpl->define_dynamic('als_list', 'page');
$tpl->define_dynamic('als_item', 'als_list');
$tpl->define_dynamic('sub_message', 'page');
$tpl->define_dynamic('sub_list', 'page');
$tpl->define_dynamic('sub_item', 'sub_list');

// page functions.

function gen_user_sub_action($sub_id, $sub_status) {
	if ($sub_status === Config::get('ITEM_OK_STATUS')) {
		return array(tr('Delete'), "subdomain_delete.php?id=$sub_id");
	} else {
		return array(tr('N/A'), '#');
	}
}

function gen_user_sub_list(&$tpl, &$sql, $user_id) {
	$domain_id = get_user_domain_id($sql, $user_id);

	$query = <<<SQL_QUERY
        SELECT
            subdomain_id, subdomain_name, subdomain_mount, subdomain_status
        FROM
            subdomain
        WHERE
            domain_id = ?
        ORDER BY
            subdomain_name
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(array('SUB_MSG' => tr('Subdomain list is empty!'), 'SUB_LIST' => ''));
		$tpl->parse('SUB_MESSAGE', 'sub_message');
	} else {
		$counter = 0;
		while (!$rs->EOF) {
			if ($counter % 2 == 0) {
				$tpl->assign('ITEM_CLASS', 'content');
			} else {
				$tpl->assign('ITEM_CLASS', 'content2');
			}

			list($sub_action, $sub_action_script) = gen_user_sub_action($rs->fields['subdomain_id'], $rs->fields['subdomain_status']);
			$sbd_name = decode_idna($rs->fields['subdomain_name']);
			$tpl->assign(
						array(
							'SUB_NAME' => $sbd_name,
							'SUB_MOUNT' => $rs->fields['subdomain_mount'],
							'SUB_STATUS' => translate_dmn_status($rs->fields['subdomain_status']),
							'SUB_ACTION' => $sub_action,
							'SUB_ACTION_SCRIPT' => $sub_action_script
						)
					);
			$tpl->parse('SUB_ITEM', '.sub_item');
			$rs->MoveNext();
			$counter++;
		}

		$tpl->parse('SUB_LIST', 'sub_list');
		$tpl->assign('SUB_MESSAGE', '');
	}
}

function gen_user_als_action($als_id, $als_status) {
	if ($als_status === Config::get('ITEM_OK_STATUS')) {
		return array(tr('Delete'), "alias_delete.php?id=$als_id");
	} else if ($als_status === Config::get('ITEM_ORDERED_STATUS')) {
		return array(tr('Delete order'), "alias_order_order.php?del_id=$als_id");
	} else {
		return array(tr('N/A'), '#');
	}
}

function gen_user_als_forward($als_id, $als_status, $url_forward) {
	if ($url_forward === 'no') {
		if ($als_status === 'ok') {
			return array("-", "alias_edit.php?edit_id=" . $als_id, tr("Edit"));
		} else if ($als_status === 'ordered') {
			return array("-", "#", tr("N/A"));
		} else {
			return array(tr("N/A"), "#", tr("N/A"));
		}
	} else {
		if ($als_status === 'ok') {
			return array($url_forward, "alias_edit.php?edit_id=" . $als_id, tr("Edit"));
		} else if ($als_status === 'ordered') {
			return array($url_forward, "#", tr("N/A"));
		} else {
			return array(tr("N/A"), "#", tr("N/A"));
		}
	}
}

function gen_user_als_list(&$tpl, &$sql, $user_id) {
	$domain_id = get_user_domain_id($sql, $user_id);

	$query = <<<SQL_QUERY
        SELECT
            alias_id, alias_name, alias_status, alias_mount, alias_ip_id, url_forward
        FROM
            domain_aliasses
        WHERE
            domain_id = ?
        ORDER BY
            alias_mount,
            alias_name
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(array('ALS_MSG' => tr('Alias list is empty!'), 'ALS_LIST' => ''));
		$tpl->parse('ALS_MESSAGE', 'als_message');
	} else {
		$counter = 0;
		while (!$rs->EOF) {
			if ($counter % 2 == 0) {
				$tpl->assign('ITEM_CLASS', 'content');
			} else {
				$tpl->assign('ITEM_CLASS', 'content2');
			}
			list($als_action, $als_action_script) = gen_user_als_action($rs->fields['alias_id'], $rs->fields['alias_status']);
			list($als_forward, $alias_edit_link, $als_edit) = gen_user_als_forward($rs->fields['alias_id'], $rs->fields['alias_status'], $rs->fields['url_forward']);

			$alias_name = decode_idna($rs->fields['alias_name']);
			$als_forward = decode_idna($als_forward);
			$tpl->assign(
						array(
							'ALS_NAME' => $alias_name,
							'ALS_MOUNT' => $rs->fields['alias_mount'],
							'ALS_STATUS' => translate_dmn_status($rs->fields['alias_status']),
							'ALS_FORWARD' => $als_forward,
							'ALS_EDIT_LINK' => $alias_edit_link,
							'ALS_EDIT' => $als_edit,
							'ALS_ACTION' => $als_action,
							'ALS_ACTION_SCRIPT' => $als_action_script
						)
					);
			$tpl->parse('ALS_ITEM', '.als_item');
			$rs->MoveNext();
			$counter ++;
		}

		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('ALS_MESSAGE', '');
	}
}

// common page data.

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(array('TR_CLIENT_MANAGE_DOMAINS_PAGE_TITLE' => tr('ispCP - Client/Manage Domains'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])));

// dynamic page data.

gen_user_sub_list($tpl, $sql, $_SESSION['user_id']);
gen_user_als_list($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_manage_domains.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
		array(
			'TR_MANAGE_DOMAINS' => tr('Manage domains'),
			'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
			'TR_ALS_NAME' => tr('Name'),
			'TR_ALS_MOUNT' => tr('Mount point'),
			'TR_ALS_FORWARD' => tr('Forward'),
			'TR_ALS_STATUS' => tr('Status'),
			'TR_ALS_ACTION' => tr('Action'),
			'TR_SUBDOMAINS' => tr('Subdomains'),
			'TR_SUB_NAME' => tr('Name'),
			'TR_SUB_MOUNT' => tr('Mount point'),
			'TR_SUB_STATUS' => tr('Status'),
			'TR_SUB_ACTION' => tr('Actions'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', '%s', true)
		)
	);

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>