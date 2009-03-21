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
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/mail_catchall.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('catchall_message', 'page');
$tpl->define_dynamic('catchall_item', 'page');


// page functions.

function gen_user_mail_action($mail_id, $mail_status) {
	if ($mail_status === Config::get('ITEM_OK_STATUS')) {
		return array(tr('Delete'), "mail_delete.php?id=$mail_id", "mail_edit.php?id=$mail_id");
	} else {
		return array(tr('N/A'), '#', '#');
	}
}

function gen_user_catchall_action($mail_id, $mail_status) {
	if ($mail_status === Config::get('ITEM_ADD_STATUS')) {
		return array(tr('N/A'), '#');//Addition in progress
	} else if ($mail_status === Config::get('ITEM_OK_STATUS')) {
		return array(tr('Delete CatchAll'), "mail_catchall_delete.php?id=$mail_id");
	} else if ($mail_status === Config::get('ITEM_CHANGE_STATUS')) {
		return array(tr('N/A'), '#');
	} else if ($mail_status === Config::get('ITEM_DELETE_STATUS')) {
		return array(tr('N/A'), '#');
	} else {
		return null;
	}
}

function gen_catchall_item(&$tpl, $action, $dmn_id, $dmn_name, $mail_id, $mail_acc, $mail_status, $ca_type) {
	$show_dmn_name = decode_idna($dmn_name);

	if ($action === 'create') {
		$tpl->assign(
			array(
				'CATCHALL_DOMAIN'			=> $show_dmn_name,
				'CATCHALL_ACC'				=> tr('None'),
				'CATCHALL_STATUS'			=> tr('N/A'),
				'CATCHALL_ACTION'			=> tr('Create catch all'),
				'CATCHALL_ACTION_SCRIPT'	=> "mail_catchall_add.php?id=$dmn_id;$ca_type"
			)
		);
	} else {
		list($catchall_action, $catchall_action_script) = gen_user_catchall_action($mail_id, $mail_status);

		$show_dmn_name = decode_idna($dmn_name);
		$show_mail_acc = decode_idna($mail_acc);

		$tpl->assign(
			array(
				'CATCHALL_DOMAIN' => $show_dmn_name,
				'CATCHALL_ACC' => $show_mail_acc,
				'CATCHALL_STATUS' => translate_dmn_status($mail_status),
				'CATCHALL_ACTION' => $catchall_action,
				'CATCHALL_ACTION_SCRIPT' => $catchall_action_script
			)
		);
	}
}

/**
 * @todo use db prepared statements
 */
function gen_page_catchall_list(&$tpl, &$sql, $dmn_id, $dmn_name) {
	global $counter;

	$tpl->assign('CATCHALL_MESSAGE', '');

		$query = "
			SELECT
				mail_id, mail_acc, status
			FROM
				mail_users
			WHERE
				domain_id = '$dmn_id'
			AND
				sub_id = 0
			AND
				mail_type = 'normal_catchall'
		";

		$rs = execute_query($sql, $query);

		if ($rs->RecordCount() == 0) {
			gen_catchall_item($tpl, 'create', $dmn_id, $dmn_name, '', '', '', 'normal');
		} else {
			gen_catchall_item($tpl,
				'delete',
				$dmn_id,
				$dmn_name,
				$rs->fields['mail_id'],
				$rs->fields['mail_acc'],
				$rs->fields['status'], 'normal');
		}
		$tpl->assign(
			array(
				'ITEM_CLASS' => 'content',
			)
		);

		$tpl->parse('CATCHALL_ITEM', 'catchall_item');

		$query = "
			SELECT
				alias_id, alias_name
			FROM
				domain_aliasses
			WHERE
				domain_id = '$dmn_id'
			AND
				alias_status = 'ok'
		";

		$rs = execute_query($sql, $query);

		while (!$rs->EOF) {
			$tpl->assign('ITEM_CLASS', ($counter % 2 == 0) ? 'content2' : 'content');

			$als_id = $rs->fields['alias_id'];

			$als_name = $rs->fields['alias_name'];

			$query = "
				SELECT
					mail_id, mail_acc, status
				FROM
					mail_users
				WHERE
					domain_id = '$dmn_id'
				AND
					sub_id = '$als_id'
				AND
					mail_type = 'alias_catchall'
			";

			$rs_als = execute_query($sql, $query);

			if ($rs_als->RecordCount() == 0) {
				gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'alias');
			} else {
				gen_catchall_item(
					$tpl,
					'delete',
					$als_id,
					$als_name,
					$rs_als->fields['mail_id'],
					$rs_als->fields['mail_acc'],
					$rs_als->fields['status'], 'alias'
				);
			}

			$tpl->parse('CATCHALL_ITEM', '.catchall_item');

			$rs->MoveNext();
			$counter++;
		}

		$query = "
			SELECT
				a.subdomain_alias_id, CONCAT(a.subdomain_alias_name,'.',b.alias_name) as subdomain_name
			FROM
				subdomain_alias as a, domain_aliasses as b
			WHERE
				b.alias_id IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id`='$dmn_id')
			AND
				a.alias_id = b.alias_id
			AND
				a.subdomain_alias_status = 'ok'
		";

		$rs = execute_query($sql, $query);

		while (!$rs->EOF) {
			$tpl->assign('ITEM_CLASS', ($counter % 2 == 0) ? 'content2' : 'content');

			$als_id = $rs->fields['subdomain_alias_id'];

			$als_name = $rs->fields['subdomain_name'];

			$query = "
				SELECT
					mail_id, mail_acc, status
				FROM
					mail_users
				WHERE
					domain_id = '$dmn_id'
				AND
					sub_id = '$als_id'
				AND
					mail_type = 'alssub_catchall'
			";

			$rs_als = execute_query($sql, $query);

			if ($rs_als->RecordCount() == 0) {
				gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'alssub');
			} else {
				gen_catchall_item(
					$tpl,
					'delete',
					$als_id,
					$als_name,
					$rs_als->fields['mail_id'],
					$rs_als->fields['mail_acc'],
					$rs_als->fields['status'], 'alssub'
				);
			}

			$tpl->parse('CATCHALL_ITEM', '.catchall_item');

			$rs->MoveNext();
			$counter++;
		}

		$query = "
			SELECT
				a.subdomain_id, CONCAT(a.subdomain_name,'.',b.domain_name) as subdomain_name
			FROM
				subdomain as a, domain as b
			WHERE
				a.domain_id = '$dmn_id'
			AND
				a.domain_id = b.domain_id
			AND
				a.subdomain_status = 'ok'
		";

		$rs = execute_query($sql, $query);

		while (!$rs->EOF) {
			$tpl->assign('ITEM_CLASS', ($counter % 2 == 0) ? 'content2' : 'content');

			$als_id = $rs->fields['subdomain_id'];

			$als_name = $rs->fields['subdomain_name'];

			$query = "
				SELECT
					mail_id, mail_acc, status
				FROM
					mail_users
				WHERE
					domain_id = '$dmn_id'
				AND
					sub_id = '$als_id'
				AND
					mail_type = 'subdom_catchall'
			";

			$rs_als = execute_query($sql, $query);

			if ($rs_als->RecordCount() == 0) {
				gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'subdom');
			} else {
				gen_catchall_item($tpl,
					'delete',
					$als_id,
					$als_name,
					$rs_als->fields['mail_id'],
					$rs_als->fields['mail_acc'],
					$rs_als->fields['status'], 'subdom');
			}

			$tpl->parse('CATCHALL_ITEM', '.catchall_item');

			$rs->MoveNext();
			$counter++;
		}
}

function gen_page_lists(&$tpl, &$sql, $user_id)
{
	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$dmn_last_modified,
		$dmn_mailacc_limit,
		$dmn_ftpacc_limit,
		$dmn_traff_limit,
		$dmn_sqld_limit,
		$dmn_sqlu_limit,
		$dmn_status,
		$dmn_als_limit,
		$dmn_subd_limit,
		$dmn_ip_id,
		$dmn_disk_limit,
		$dmn_disk_usage,
		$dmn_php,
		$dmn_cgi) = get_domain_default_props($sql, $user_id);

	gen_page_catchall_list($tpl, $sql, $dmn_id, $dmn_name);
	// gen_page_ftp_list($tpl, $sql, $dmn_id, $dmn_name);
}

// common page data.

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_MANAGE_USERS_PAGE_TITLE'	=> tr('ispCP - Client/Manage Users'),
		'THEME_COLOR_PATH'					=> "../themes/$theme_color",
		'THEME_CHARSET'						=> tr('encoding'),
		'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

// dynamic page data.

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	$tpl->assign('NO_MAILS', '');
}

gen_page_lists($tpl, $sql, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_email_accounts.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_email_accounts.tpl');

gen_logged_from($tpl);
check_permissions($tpl);

$tpl->assign(
	array(
		'TR_STATUS'					=> tr('Status'),
		'TR_ACTION'					=> tr('Action'),
		'TR_CATCHALL_MAIL_USERS'	=> tr('Catch all account'),
		'TR_DOMAIN'					=> tr('Domain'),
		'TR_CATCHALL'				=> tr('Catch all'),
		'TR_MESSAGE_DELETE'			=> tr('Are you sure you want to delete %s?', true, '%s')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) dump_gui_debug();

unset_messages();

?>
