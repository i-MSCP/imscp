<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
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

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/protected_areas.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('dir_item', 'page');
$tpl->define_dynamic('action_link', 'page');
$tpl->define_dynamic('protected_areas', 'page');

$theme_color = Config::getInstance()->get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_CLIENT_WEBTOOLS_PAGE_TITLE' => tr('ispCP - Client/Webtools'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

function gen_htaccess_entries(&$tpl, &$sql, &$dmn_id) {
	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`htaccess`
		WHERE
			`dmn_id` = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id));

	if ($rs->RecordCount() == 0) {
		$tpl->assign('PROTECTED_AREAS', '');
		set_page_message(tr('You do not have protected areas'));
	} else {
		$counter = 0;
		while (!$rs->EOF) {
			$tpl->assign('CLASS', ($counter % 2 == 0) ? 'content' : 'content2');

			$id = $rs->fields['id'];
			$user_id = $rs->fields['user_id'];
			$group_id = $rs->fields['group_id'];
			$status = $rs->fields['status'];
			$path = $rs->fields['path'];
			$auth_name = $rs->fields['auth_name'];

			$tpl->assign(
				array(
					'AREA_NAME' => $auth_name,
					'JS_AREA_NAME' => addslashes(strip_tags($auth_name)),
					'AREA_PATH' => $path,
					'PID' => $id,
					'STATUS' => translate_dmn_status($status)
				)
			);
			$tpl->parse('DIR_ITEM', '.dir_item');
			$rs->MoveNext();
			$counter++;
		}
	}
}

/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/main_menu_webtools.tpl');
gen_client_menu($tpl, Config::getInstance()->get('CLIENT_TEMPLATE_PATH') . '/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

gen_htaccess_entries($tpl, $sql, $dmn_id);

$tpl->assign(
	array(
		'TR_HTACCESS' => tr('Protected areas'),
		'TR_DIRECTORY_TREE' => tr('Directory tree'),
		'TR_DIRS' => tr('Name'),
		'TR__ACTION' => tr('Action'),
		'TR_MANAGE_USRES' => tr('Manage users and groups'),
		'TR_USERS' => tr('User'),
		'TR_USERNAME' => tr('Username'),
		'TR_ADD_USER' => tr('Add user'),
		'TR_GROUPNAME' => tr('Group name'),
		'TR_GROUP_MEMBERS' => tr('Group members'),
		'TR_ADD_GROUP' => tr('Add group'),
		'TR_EDIT' => tr('Edit'),
		'TR_GROUP' => tr('Group'),
		'TR_DELETE' => tr('Delete'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
		'TR_STATUS' => tr('Status'),
		'TR_ADD_AREA' => tr('Add new protected area')
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
