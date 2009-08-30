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

function count_requests(&$sql, $id_name, $table) {
	$query = "SELECT `$id_name` FROM `$table` WHERE `$id_name` NOT IN (?, ?, ?)";
	$rs = exec_query($sql, $query, array(Config::get('ITEM_OK_STATUS'), Config::get('ITEM_DISABLED_STATUS'), Config::get('ITEM_ORDERED_STATUS')));
	$count = $rs->RecordCount();
	return $count;
}

function get_error_domains(&$sql, &$tpl) {
	$ok_status = Config::get('ITEM_OK_STATUS');
	$disabled_status = Config::get('ITEM_DISABLED_STATUS');
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$add_status = Config::get('ITEM_ADD_STATUS');
	$restore_status = Config::get('ITEM_RESTORE_STATUS');
	$change_status = Config::get('ITEM_CHANGE_STATUS');
	$toenable_status = Config::get('ITEM_TOENABLE_STATUS');
	$todisable_status = Config::get('ITEM_TODISABLED_STATUS');

	$dmn_query = "SELECT `domain_name`, `domain_status`, `domain_id` FROM `domain` WHERE `domain_status` NOT IN (?, ?, ?, ?, ?, ?, ?, ?)";

	$rs = exec_query($sql, $dmn_query, array($ok_status, $disabled_status, $delete_status, $add_status,
			$restore_status, $change_status, $toenable_status, $todisable_status));

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'DOMAIN_LIST' => '',
				'TR_DOMAIN_MESSAGE' => tr('No domain system errors'),
			)
		);
		$tpl->parse('DOMAIN_MESSAGE', 'domain_message');
	} else {
		$i = 1;
		while (!$rs->EOF) {
			$tpl->assign(array('CONTENT' => ($i % 2 == 0) ? 'content2' : 'content'));

			$tpl->assign(
				array(
					'DOMAIN_MESSAGE' => '',
					'TR_DOMAIN_NAME' => $rs->fields['domain_name'],
					'TR_DOMAIN_ERROR' => $rs->fields['domain_status'],
					'CHANGE_ID' => $rs->fields['domain_id'],
					'CHANGE_TYPE' => 'domain',
				)
			);

			$tpl->parse('DOMAIN_LIST', '.domain_list');

			$i++;
			$rs->MoveNext();
		}
	}

}

function get_error_aliases(&$sql, &$tpl) {
	$ok_status = Config::get('ITEM_OK_STATUS');
	$disabled_status = Config::get('ITEM_DISABLED_STATUS');
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$add_status = Config::get('ITEM_ADD_STATUS');
	$restore_status = Config::get('ITEM_RESTORE_STATUS');
	$change_status = Config::get('ITEM_CHANGE_STATUS');
	$toenable_status = Config::get('ITEM_TOENABLE_STATUS');
	$todisable_status = Config::get('ITEM_TODISABLED_STATUS');
	$ordered_status = Config::get('ITEM_ORDERED_STATUS');

	$dmn_query = <<<SQL_QUERY
		SELECT
			`alias_name`, `alias_status`, `alias_id`
		FROM
			`domain_aliasses`
		WHERE
			`alias_status`
		NOT IN
			(?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

	$rs = exec_query($sql, $dmn_query, array(
			$ok_status,
			$disabled_status,
			$delete_status,
			$add_status,
			$restore_status,
			$change_status,
			$toenable_status,
			$todisable_status,
			$ordered_status)
	);

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'ALIAS_LIST' => '',
				'TR_ALIAS_MESSAGE' => tr('No domain alias system errors'),
			)
		);

		$tpl->parse('ALIAS_MESSAGE', 'alias_message');
	} else {
		$i = 1;
		while (!$rs->EOF) {
			$tpl->assign(
				array(
					'CONTENT' => ($i % 2 == 0) ? 'content' : 'content2',
				)
			);

			$tpl->assign(
				array(
					'ALIAS_MESSAGE' => '',
					'TR_ALIAS_NAME' => $rs->fields['alias_name'],
					'TR_ALIAS_ERROR' => $rs->fields['alias_status'],
					'CHANGE_ID' => $rs->fields['alias_id'],
					'CHANGE_TYPE' => 'alias',
				)
			);

			$tpl->parse('ALIAS_LIST', '.alias_list');

			$i++;
			$rs->MoveNext();
		}
	}
}

function get_error_subdomains(&$sql, &$tpl) {
	$ok_status = Config::get('ITEM_OK_STATUS');
	$disabled_status = Config::get('ITEM_DISABLED_STATUS');
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$add_status = Config::get('ITEM_ADD_STATUS');
	$restore_status = Config::get('ITEM_RESTORE_STATUS');
	$change_status = Config::get('ITEM_CHANGE_STATUS');
	$toenable_status = Config::get('ITEM_TOENABLE_STATUS');
	$todisable_status = Config::get('ITEM_TODISABLED_STATUS');

	$dmn_query = <<<SQL_QUERY
		SELECT
			`subdomain_name`, `subdomain_status`, `subdomain_id`
		FROM
			`subdomain`
		WHERE
			`subdomain_status`
		NOT IN
			(?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

	$rs = exec_query($sql, $dmn_query, array(
			$ok_status,
			$disabled_status,
			$delete_status,
			$add_status,
			$restore_status,
			$change_status,
			$toenable_status,
			$todisable_status)
	);

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'SUBDOMAIN_LIST' => '',
				'TR_SUBDOMAIN_MESSAGE' => tr('No subdomain system errors'),
			)
		);

		$tpl->parse('SUBDOMAIN_MESSAGE', 'subdomain_message');
	} else {
		$i = 1;
		while (!$rs->EOF) {
			$tpl->assign(array('CONTENT' => ($i % 2 == 0) ? 'content' : 'content2'));

			$tpl->assign(
				array(
					'SUBDOMAIN_MESSAGE' => '',
					'TR_SUBDOMAIN_NAME' => $rs->fields['subdomain_name'],
					'TR_SUBDOMAIN_ERROR' => $rs->fields['subdomain_status'],
					'CHANGE_ID' => $rs->fields['subdomain_id'],
					'CHANGE_TYPE' => 'subdomain'
				)
			);

			$tpl->parse('SUBDOMAIN_LIST', '.subdomain_list');

			$i++;
			$rs->MoveNext();
		}
	}
}

function get_error_alias_subdomains(&$sql, &$tpl) {
	$ok_status = Config::get('ITEM_OK_STATUS');
	$disabled_status = Config::get('ITEM_DISABLED_STATUS');
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$add_status = Config::get('ITEM_ADD_STATUS');
	$restore_status = Config::get('ITEM_RESTORE_STATUS');
	$change_status = Config::get('ITEM_CHANGE_STATUS');
	$toenable_status = Config::get('ITEM_TOENABLE_STATUS');
	$todisable_status = Config::get('ITEM_TODISABLED_STATUS');

	$dmn_query = <<<SQL_QUERY
		SELECT
			`subdomain_alias_name`, `subdomain_alias_status`, `subdomain_alias_id`
		FROM
			`subdomain_alias`
		WHERE
			`subdomain_alias_status`
		NOT IN
			(?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

	$rs = exec_query($sql, $dmn_query, array(
			$ok_status,
			$disabled_status,
			$delete_status,
			$add_status,
			$restore_status,
			$change_status,
			$toenable_status,
			$todisable_status)
	);

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'SUBDOMAIN_ALIAS_LIST' => '',
				'TR_SUBDOMAIN_ALIAS_MESSAGE' => tr('No alias subdomain system errors'),
			)
		);

		$tpl->parse('SUBDOMAIN_ALIAS_MESSAGE', 'subdomain_alias_message');
	} else {
		$i = 1;
		while (!$rs->EOF) {
			$tpl->assign(array('CONTENT' => ($i % 2 == 0) ? 'content' : 'content2'));

			$tpl->assign(
				array(
					'SUBDOMAIN_ALIAS_MESSAGE' => '',
					'TR_SUBDOMAIN_ALIAS_NAME' => $rs->fields['subdomain_alias_name'],
					'TR_SUBDOMAIN_ALIAS_ERROR' => $rs->fields['subdomain_alias_status'],
					'CHANGE_ID' => $rs->fields['subdomain_alias_id'],
					'CHANGE_TYPE' => 'subdomain_alias'
				)
			);

			$tpl->parse('SUBDOMAIN_ALIAS_LIST', '.subdomain_alias_list');

			$i++;
			$rs->MoveNext();
		}
	}
}

function get_error_mails(&$sql, &$tpl) {
	$ok_status = Config::get('ITEM_OK_STATUS');
	$disabled_status = Config::get('ITEM_DISABLED_STATUS');
	$delete_status = Config::get('ITEM_DELETE_STATUS');
	$add_status = Config::get('ITEM_ADD_STATUS');
	$restore_status = Config::get('ITEM_RESTORE_STATUS');
	$change_status = Config::get('ITEM_CHANGE_STATUS');
	$toenable_status = Config::get('ITEM_TOENABLE_STATUS');
	$todisable_status = Config::get('ITEM_TODISABLED_STATUS');
	$ordered_status = Config::get('ITEM_ORDERED_STATUS');

	$dmn_query = <<<SQL_QUERY
		SELECT
			`mail_acc`, `domain_id`, `mail_type`, `status`, `mail_id`
		FROM
			`mail_users`
		WHERE
			`status`
		NOT IN
			(?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL_QUERY;

	$rs = exec_query($sql, $dmn_query, array(
			$ok_status,
			$disabled_status,
			$delete_status,
			$add_status,
			$restore_status,
			$change_status,
			$toenable_status,
			$todisable_status,
			$ordered_status)
	);

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'MAIL_LIST' => '',
				'TR_MAIL_MESSAGE' => tr('No email account system errors'),
			)
		);

		$tpl->parse('MAIL_MESSAGE', 'mail_message');
	} else {
		$i = 1;
		while (!$rs->EOF) {
			$searched_id = $rs->fields['domain_id'];
			$query = '';

			if ($rs->fields['mail_type'] == 'normal_mail'
				|| $rs->fields['mail_type'] == 'normal_forward') {
				$query = <<<SQL_QUERY
					SELECT
						`domain_name`
					FROM
						`domain`
					WHERE
						`domain_id` = ?
SQL_QUERY;
			} else if ($rs->fields['mail_type'] == 'subdom_mail'
				|| $rs->fields['mail_type'] == 'subdom_forward') {
				$query = <<<SQL_QUERY
					SELECT
						`subdomain_name` AS domain_name
					FROM
						`subdomain`
					WHERE
						`subdomain_id` = ?
SQL_QUERY;
			} else if ($rs->fields['mail_type'] == 'alias_mail'
				|| $rs->fields['mail_type'] == 'alias_forward') {
				$query = <<<SQL_QUERY
					SELECT
						`alias_name` AS domain_name
					FROM
						`domain_aliasses`
					WHERE
						`alias_id` = ?
SQL_QUERY;
			} else {
				write_log(sprintf('FIXME: %s:%d' . "\n" . 'Unknown mail type %s',__FILE__, __LINE__, $rs->fields['mail_type']));
				die('FIXME: ' . __FILE__ . ':' . __LINE__);
			}

			$sr = exec_query($sql, $query, array($searched_id));
			$domain_name = $sr->fields['domain_name'];

			$tpl->assign(
				array(
					'CONTENT' => ($i % 2 == 0) ? 'content' : 'content2',
					)
			);

			$tpl->assign(
				array(
					'MAIL_MESSAGE' => '',
					'TR_MAIL_NAME' => $rs->fields['mail_acc'] . "@" . $domain_name,
					'TR_MAIL_ERROR' => $rs->fields['status'],
					'CHANGE_ID' => $rs->fields['mail_id'],
					'CHANGE_TYPE' => 'mail',
				)
			);

			$tpl->parse('MAIL_LIST', '.mail_list');

			$i++;
			$rs->MoveNext();
		}
	}
}

$exec_count = count_requests($sql, 'domain_status', 'domain');
$exec_count = $exec_count + count_requests($sql, 'alias_status', 'domain_aliasses');
$exec_count = $exec_count + count_requests($sql, 'subdomain_status', 'subdomain');
$exec_count = $exec_count + count_requests($sql, 'subdomain_alias_status', 'subdomain_alias');
$exec_count = $exec_count + count_requests($sql, 'status', 'mail_users');
$exec_count = $exec_count + count_requests($sql, 'status', 'htaccess');
$exec_count = $exec_count + count_requests($sql, 'status', 'htaccess_groups');
$exec_count = $exec_count + count_requests($sql, 'status', 'htaccess_users');

$tpl = new pTemplate();

$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/ispcp_debugger.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('domain_message', 'page');
$tpl->define_dynamic('alias_message', 'page');
$tpl->define_dynamic('subdomain_message', 'page');
$tpl->define_dynamic('subdomain_alias_message', 'page');
$tpl->define_dynamic('mail_message', 'page');
$tpl->define_dynamic('domain_list', 'page');
$tpl->define_dynamic('alias_list', 'page');
$tpl->define_dynamic('subdomain_list', 'page');
$tpl->define_dynamic('subdomain_alias_list', 'page');
$tpl->define_dynamic('mail_list', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_ISPCP_DEBUGGER_PAGE_TITLE' => tr('ispCP - Virtual Hosting Control System'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_system_tools.tpl');

$tpl->assign(
	array(
		'TR_DEBUGGER_TITLE' => tr('ispCP debugger'),
		'TR_DOMAIN_ERRORS' => tr('Domain errors'),
		'TR_ALIAS_ERRORS' => tr('Domain alias errors'),
		'TR_SUBDOMAIN_ERRORS' => tr('Subdomain errors'),
		'TR_SUBDOMAIN_ALIAS_ERRORS' => tr('Alias subdomain errors'),
		'TR_MAIL_ERRORS' => tr('Mail account errors'),
		'TR_DAEMON_TOOLS' => tr('ispCP Daemon tools'),
		'TR_EXEC_REQUESTS' => tr('Execute requests'),
		'TR_CHANGE_STATUS' => tr("Set status to 'change'"),
		'EXEC_COUNT' => $exec_count,
	)
);

if (isset($_GET['action']) && $exec_count > 0) {
	if ($_GET['action'] == 'run_engine') {
		$c = send_request();
		set_page_message(tr('Daemon returned %d as status code', $c));
	} else if ($_GET['action'] == 'change_status' && (
			isset($_GET['id']) && isset($_GET['type']))) {
		switch ($_GET['type']) {
			case 'domain':
				$query = 'UPDATE `domain` SET `domain_status` = "change" WHERE `domain_id` = ?';
				break;
			case 'alias':
				$query = 'UPDATE `domain_aliasses` SET `alias_status` = "change" WHERE `alias_id` = ?';
				break;
			case 'subdomain':
				$query = 'UPDATE `subdomain` SET `subdomain_status` = "change" WHERE `subdomain_id` = ?';
				break;
			case 'subdomain_alias':
				$query = 'UPDATE `subdomain_alias` SET `subdomain_alias_status` = "change" WHERE `subdomain_alias_id` = ?';
				break;
			case 'mail':
				$query = 'UPDATE `mail_users` SET `status` = "change" WHERE `mail_id` = ?';
				break;
			default:
				set_page_message(tr('Unknown type!'));
				user_goto('ispcp_debugger.php');
				break;
		}

		$rs = exec_query($sql, $query, $_GET['id']);

		if ($rs !== false) {
			set_page_message(tr('Done'));
			user_goto('ispcp_debugger.php');
		} else {
			$msg = tr('Unknown Error') . '<br/>' . $sql->ErrorMsg();
			set_page_message($msg);
			user_goto('ispcp_debugger.php');
		}
	}
}

gen_page_message($tpl);

get_error_domains($sql, $tpl);

get_error_aliases($sql, $tpl);

get_error_subdomains($sql, $tpl);

get_error_alias_subdomains($sql, $tpl);

get_error_mails($sql, $tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
