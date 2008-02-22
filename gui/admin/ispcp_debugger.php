<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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

function get_error_domains(&$sql, &$tpl) {
	global $cfg;

	$ok_status = $cfg['ITEM_OK_STATUS'];
	$disabled_status = $cfg['ITEM_DISABLED_STATUS'];
	$delete_status = $cfg['ITEM_DELETE_STATUS'];
	$add_status = $cfg['ITEM_ADD_STATUS'];
	$restore_status = $cfg['ITEM_RESTORE_STATUS'];
	$change_status = $cfg['ITEM_CHANGE_STATUS'];
	$toenable_status = $cfg['ITEM_TOENABLE_STATUS'];
	$todisable_status = $cfg['ITEM_TODISABLED_STATUS'];

	$dmn_query = <<<SQL_QUERY
        select
            domain_name, domain_status
        from
            domain
        where
            (domain_status != ?
          and
            domain_status != ?
          and
            domain_status != ?
          and
            domain_status != ?
          and
            domain_status != ?
          and
            domain_status != ?
          and
            domain_status != ?
          and
            domain_status != ?)
SQL_QUERY;

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
			if ($i % 2 == 0) {
				$tpl->assign(
						array(
							'CONTENT' => 'content2',
							)
					);
			} else {
				$tpl->assign(
						array(
							'CONTENT' => 'content1',
							)
					);
			}

			$tpl->assign(
					array(
						'DOMAIN_MESSAGE' => '',
						'TR_DOMAIN_NAME' => $rs->fields['domain_name'],
						'TR_DOMAIN_ERROR' => $rs->fields['domain_status'],
						)
				);

			$tpl->parse('DOMAIN_LIST', '.domain_list');

			$i++;
			$rs->MoveNext();
		}
	}
}

function get_error_aliases(&$sql, &$tpl) {
	global $cfg;

	$ok_status = $cfg['ITEM_OK_STATUS'];
	$disabled_status = $cfg['ITEM_DISABLED_STATUS'];
	$delete_status = $cfg['ITEM_DELETE_STATUS'];
	$add_status = $cfg['ITEM_ADD_STATUS'];
	$restore_status = $cfg['ITEM_RESTORE_STATUS'];
	$change_status = $cfg['ITEM_CHANGE_STATUS'];
	$toenable_status = $cfg['ITEM_TOENABLE_STATUS'];
	$todisable_status = $cfg['ITEM_TODISABLED_STATUS'];
	$ordered_status = $cfg['ITEM_ORDERED_STATUS'];

	$dmn_query = <<<SQL_QUERY
        select
            alias_name, alias_status
        from
            domain_aliasses
        where
            (alias_status != ?
          and
            alias_status != ?
          and
            alias_status != ?
          and
            alias_status != ?
          and
            alias_status != ?
          and
            alias_status != ?
          and
            alias_status != ?
          and
            alias_status != ?
          and
            alias_status != ?)
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
											$ordered_status));

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
			if ($i % 2 == 0) {
				$tpl->assign(
						array(
							'CONTENT' => 'content',
							)
					);
			} else {
				$tpl->assign(
						array(
							'CONTENT' => 'content2',
							)
					);
			}

			$tpl->assign(
					array(
						'ALIAS_MESSAGE' => '',
						'TR_ALIAS_NAME' => $rs->fields['alias_name'],
						'TR_ALIAS_ERROR' => $rs->fields['alias_status'],
						)
				);

			$tpl->parse('ALIAS_LIST', '.alias_list');

			$i ++;
			$rs->MoveNext();
		}
	}
}

function get_error_subdomains(&$sql, &$tpl) {
	global $cfg;

	$ok_status = $cfg['ITEM_OK_STATUS'];
	$disabled_status = $cfg['ITEM_DISABLED_STATUS'];
	$delete_status = $cfg['ITEM_DELETE_STATUS'];
	$add_status = $cfg['ITEM_ADD_STATUS'];
	$restore_status = $cfg['ITEM_RESTORE_STATUS'];
	$change_status = $cfg['ITEM_CHANGE_STATUS'];
	$toenable_status = $cfg['ITEM_TOENABLE_STATUS'];
	$todisable_status = $cfg['ITEM_TODISABLED_STATUS'];

	$dmn_query = <<<SQL_QUERY
      select
          subdomain_name, subdomain_status
      from
          subdomain
      where
          (subdomain_status != ?
        and
          subdomain_status != ?
        and
          subdomain_status != ?
        and
          subdomain_status != ?
        and
          subdomain_status != ?
        and
          subdomain_status != ?
        and
          subdomain_status != ?
        and
          subdomain_status != ?)
SQL_QUERY;

	$rs = exec_query($sql, $dmn_query, array(
											$ok_status,
											$disabled_status,
											$delete_status,
											$add_status,
											$restore_status,
											$change_status,
											$toenable_status,
											$todisable_status));

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
			if ($i % 2 == 0) {
				$tpl->assign(
						array(
							'CONTENT' => 'content',
							)
					);
			} else {
				$tpl->assign(
						array(
							'CONTENT' => 'content2',
							)
					);
			}

			$tpl->assign(
					array(
						'SUBDOMAIN_MESSAGE' => '',
						'TR_SUBDOMAIN_NAME' => $rs->fields['subdomain_name'],
						'TR_SUBDOMAIN_ERROR' => $rs->fields['subdomain_status'],
						)
				);

			$tpl->parse('SUBDOMAIN_LIST', '.subdomain_list');

			$i ++;
			$rs->MoveNext();
		}
	}
}

function get_error_mails(&$sql, &$tpl) {
	global $cfg;

	$ok_status = $cfg['ITEM_OK_STATUS'];
	$disabled_status = $cfg['ITEM_DISABLED_STATUS'];
	$delete_status = $cfg['ITEM_DELETE_STATUS'];
	$add_status = $cfg['ITEM_ADD_STATUS'];
	$restore_status = $cfg['ITEM_RESTORE_STATUS'];
	$change_status = $cfg['ITEM_CHANGE_STATUS'];
	$toenable_status = $cfg['ITEM_TOENABLE_STATUS'];
	$todisable_status = $cfg['ITEM_TODISABLED_STATUS'];
	$ordered_status = $cfg['ITEM_ORDERED_STATUS'];

	$dmn_query = <<<SQL_QUERY
        select
            mail_acc, domain_id, mail_type, status
        from
            mail_users
        where
            (status != ?
              and
            status != ?
              and
            status != ?
              and
            status != ?
              and
            status != ?
              and
            status != ?
              and
            status != ?
              and
            status != ?
              and
            status != ?)
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
											$ordered_status));

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

			if ($rs->fields['mail_type'] == 'normal_mail' || $rs->fields['mail_type'] == 'normal_forward') {
				$query = <<<SQL_QUERY
            select
                 domain_name as domain_name
            from
                  domain
            where
                  domain_id = ?
SQL_QUERY;
			} else if ($rs->fields['mail_type'] == 'subdom_mail' || $rs->fields['mail_type'] == 'subdom_forward') {
				$query = <<<SQL_QUERY
            select
                subdomain_name as domain_name
            from
                subdomain
            where
                subdomain_id = ?
SQL_QUERY;
			} else if ($rs->fields['mail_type'] == 'alias_mail' || $rs->fields['mail_type'] == 'alias_forward') {
				$query = <<<SQL_QUERY
            select
                alias_name as domain_name
            from
                domain_aliasses
            where
                alias_id  = ?
SQL_QUERY;
			} else {
				write_log(sprintf('FIXME: %s:%d' . "\n" . 'Unknown mail type %s',__FILE__, __LINE__, $rs->fields['mail_type']));
				die('FIXME: ' . __FILE__ . ':' . __LINE__);
			}

			$sr = exec_query($sql, $query, array($searched_id));
			$domain_name = $sr->fields['domain_name'];

			if ($i % 2 == 0) {
				$tpl->assign(
						array(
							'CONTENT' => 'content',
							)
					);
			} else {
				$tpl->assign(
						array(
							'CONTENT' => 'content2',
							)
					);
			}

			$tpl->assign(
					array(
						'MAIL_MESSAGE' => '',
						'TR_MAIL_NAME' => $rs->fields['mail_acc'] . "@" . $domain_name,
						'TR_MAIL_ERROR' => $rs->fields['status'],
						)
				);

			$tpl->parse('MAIL_LIST', '.mail_list');

			$i ++;
			$rs->MoveNext();
		}
	}
}

$tpl = new pTemplate();

$tpl->define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'] . '/ispcp_debugger.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('domain_message', 'page');
$tpl->define_dynamic('alias_message', 'page');
$tpl->define_dynamic('subdomain_message', 'page');
$tpl->define_dynamic('mail_message', 'page');
$tpl->define_dynamic('domain_list', 'page');
$tpl->define_dynamic('alias_list', 'page');
$tpl->define_dynamic('subdomain_list', 'page');
$tpl->define_dynamic('mail_list', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

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
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/menu_system_tools.tpl');

$tpl->assign(
		array(
			'TR_DEBUGGER_TITLE' => tr('ispCP debugger'),
			'TR_DOMAIN_ERRORS' => tr('Domain errors'),
			'TR_ALIAS_ERRORS' => tr('Domain alias errors'),
			'TR_SUBDOMAIN_ERRORS' => tr('Subdomain errors'),
			'TR_MAIL_ERRORS' => tr('Mail account errors'),
			'TR_DAEMON_TOOLS' => tr('ispCP Daemon tools'),
			'TR_EXEC_REQUESTS' => tr('Execute requests')
			)
	);

// Handy way to change domain/subdomain/alias/mail status
// TODO: write implementation in the GUI
if (isset($_GET['action'])) {
	if ($_GET['action'] == 'run_engine') {
		check_for_lock_file();
		$c = send_request();
		set_page_message(tr('Daemon returned %d as status code', $c));
	} else if ($_GET['action'] == 'change_status' && (
			isset($_GET['account_id']) && isset($_GET['new_status']) &&	isset($_GET['type']))) {

		switch ($_GET['new_status']) {
			case $cfg['ITEM_OK_STATUS']:
			case $cfg['ITEM_DISABLED_STATUS']:
			case $cfg['ITEM_ADD_STATUS']:
			case $cfg['ITEM_CHANGE_STATUS']:
			case $cfg['ITEM_TOENABLE_STATUS']:
			case $cfg['ITEM_RESTORE_STATUS']:
			case $cfg['ITEM_TODISABLED_STATUS']:
			case $cfg['ITEM_DELETE_STATUS']:
			case $cfg['ITEM_ORDERED_STATUS']:
				break;
			default:
				set_page_message(tr('Unknown domain status!'));
				user_goto('ispcp_debugger.php');
				break;
		}

		switch ($_GET['type']) {
			case 'domain':
				$query = 'UPDATE domain SET domain_status = ? WHERE domain_id = ?';
				break;
			case 'alias':
				$query = 'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?';
				break;
			case 'subdomain':
				$query = 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?';
				break;
			case 'mail':
				$query = 'UPDATE mail_users SET status = ? WHERE mail_id = ?';
				break;
			default:
				set_page_message(tr('Unknown account type!'));
				user_goto('ispcp_debugger.php');
				break;
		}

		if (!is_int($_GET['account_id'])) {
			set_page_message(tr('Invalid account id!'));
			user_goto('ispcp_debugger.php');
		}

		$rs = exec_query($sql, $query, array($_GET['new_status'], $_GET['account_id']));

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

get_error_mails($sql, $tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg['DUMP_GUI_DEBUG'])
	dump_gui_debug();

unset_messages();

?>