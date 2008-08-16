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
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/manage_reseller_owners.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('reseller_list', 'page');
$tpl->define_dynamic('reseller_item', 'reseller_list');
$tpl->define_dynamic('select_admin', 'page');
$tpl->define_dynamic('select_admin_option', 'select_admin');

$theme_color = Config::get('USER_INITIAL_THEME');

function gen_reseller_table(&$tpl, &$sql) {
	$query = <<<SQL_QUERY
        SELECT
            t1.admin_id, t1.admin_name, t2.admin_name AS created_by
        FROM
            admin AS t1,
			admin AS t2
        WHERE
            t1.admin_type = 'reseller'
		  AND
            t1.created_by = t2.admin_id
        ORDER BY
            created_by,
			admin_id
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$i = 0;

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array('MESSAGE' => tr('Reseller list is empty!'),
				'RESELLER_LIST' => '',
				)
			);

		$tpl->parse('PAGE_MESSAGE', 'page_message');
	} else {
		while (!$rs->EOF) {
			if ($i % 2 == 0) {
				$tpl->assign(
					array('RSL_CLASS' => 'content',
						)
					);
			} else {
				$tpl->assign(
					array('RSL_CLASS' => 'content2',
						)
					);
			}

			$admin_id = $rs->fields['admin_id'];

			$admin_id_var_name = "admin_id_$admin_id";

			$tpl->assign(
				array('NUMBER' => $i + 1,
					'RESELLER_NAME' => $rs->fields['admin_name'],
					'OWNER' => $rs->fields['created_by'],
					'CKB_NAME' => $admin_id_var_name,
					)
				);

			$tpl->parse('RESELLER_ITEM', '.reseller_item');

			$rs->MoveNext();

			$i++;
		}

		$tpl->parse('RESELLER_LIST', 'reseller_list');

		$tpl->assign('PAGE_MESSAGE', '');
	}

	$query = <<<SQL_QUERY
        SELECT
            admin_id, admin_name
        FROM
            admin
        WHERE
            admin_type = 'admin'
        ORDER BY
            admin_name
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	while (!$rs->EOF) {
		$selected = '';

		if (isset($_POST['uaction']) && $_POST['uaction'] === 'reseller_owner') {
			if (isset($_POST['dest_admin']) && $_POST['dest_admin'] == $rs->fields['admin_id']) {
				$selected = 'selected';
			}
		}

		$tpl->assign(
			array('OPTION' => $rs->fields['admin_name'],
				'VALUE' => $rs->fields['admin_id'],
				'SELECTED' => $selected,
				)
			);

		$tpl->parse('SELECT_ADMIN_OPTION', '.select_admin_option');

		$rs->MoveNext();

		$i++;
	}

	$tpl->parse('SELECT_ADMIN', 'select_admin');

	$tpl->assign('PAGE_MESSAGE', '');
}

function update_reseller_owner($sql) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'reseller_owner') {
		$query = <<<SQL_QUERY
            SELECT
                admin_id
            FROM
                admin
            WHERE
                admin_type = 'reseller'
            ORDER BY
                admin_name
SQL_QUERY;

		$rs = execute_query($sql, $query);

		while (!$rs->EOF) {
			$admin_id = $rs->fields['admin_id'];

			$admin_id_var_name = "admin_id_$admin_id";

			if (isset($_POST[$admin_id_var_name]) && $_POST[$admin_id_var_name] === 'on') {
				$dest_admin = $_POST['dest_admin'];

				$query = <<<SQL_QUERY
                    UPDATE
                        admin
                    SET
                        created_by = ?
                    WHERE
                        admin_id  = ?
SQL_QUERY;

				$up = exec_query($sql, $query, array($dest_admin, $admin_id));
			}

			$rs->MoveNext();
		}
	}
}

/*
 *
 * static page messages.
 *
 */

$tpl->assign(
	array('TR_ADMIN_MANAGE_RESELLER_OWNERS_PAGE_TITLE' => tr('ispCP - Admin/Manage users/Reseller assignment'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
		)
	);

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_users_manage.tpl');

update_reseller_owner($sql);

gen_reseller_table($tpl, $sql);

$tpl->assign(
	array('TR_RESELLER_ASSIGNMENT' => tr('Reseller assignment'),
		'TR_RESELLER_USERS' => tr('Reseller users'),
		'TR_NUMBER' => tr('No.'),
		'TR_MARK' => tr('Mark'),
		'TR_RESELLER_NAME' => tr('Reseller name'),
		'TR_OWNER' => tr('Owner'),
		'TR_TO_ADMIN' => tr('To Admin'),
		'TR_MOVE' => tr('Move'),
		)
	);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>