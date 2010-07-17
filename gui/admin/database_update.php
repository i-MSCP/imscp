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

$cfg = ispCP_Registry::get('Config');
$dbUpdate = ispCP_Update_Database::getInstance();

if(isset($_POST['execute']) && $_POST['execute'] == 'update') {

	// Execute all available db updates and redirect back to database_update.php

	if(!$dbUpdate->executeUpdates()) {
		throw new ispCP_Exception($dbUpdate->getErrorMessage());
	}

	header('Location: ' . $_SERVER['PHP_SELF']);
} else {

	$tpl = new ispCP_pTemplate();
	$tpl->define_dynamic(
		'page', $cfg->ADMIN_TEMPLATE_PATH . '/database_update.tpl'
	);
	$tpl->define_dynamic('page_message', 'page');
	$tpl->define_dynamic('database_update_message', 'page');
	$tpl->define_dynamic('database_update_infos', 'page');
	$tpl->define_dynamic('table_header', 'page');

	$tpl->assign(
		array(
			'TR_ADMIN_ISPCP_UPDATES_PAGE_TITLE'	=>
				tr('ispCP - Virtual Hosting Control System'),
			'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id'])
		)
	);

	gen_admin_mainmenu(
		$tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_system_tools.tpl'
	);
	gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_system_tools.tpl');
	gen_page_message($tpl);

	$tpl->assign(
		array(
			'TR_UPDATES_TITLE' => tr('Database updates'),
			'TR_AVAILABLE_UPDATES' => tr('Available database updates'),
			'TR_UPDATE' => tr('Update'),
			'TR_INFOS' => tr('Update details')
		)
	);

	if($dbUpdate->checkUpdateExists()) {
		$tpl->assign(
			array(
				'UPDATE_MESSAGE' => '',
				'DATABASE_UPDATE_MESSAGE' => '',
				'UPDATE' => tr('New Database update is now available'),
				'INFOS' => tr('Do you want to execute the Updates now?'),
				'TR_EXECUTE_UPDATE' => tr('Execute updates')
			)
		);

		$tpl->parse('DATABASE_UPDATE_INFOS', 'database_update_infos');
	} else {
		$tpl->assign(
			array(
				'TR_UPDATE_MESSAGE' => tr('No database updates available'),
				'DATABASE_UPDATE_INFOS' => '',
				'TABLE_HEADER' => ''
			)
		);

		$tpl->parse('DATABASE_UPDATE_MESSAGE', 'database_update_message');
	}

	$tpl->parse('PAGE', 'page');
	$tpl->prnt();

	if ($cfg->DUMP_GUI_DEBUG) {
		dump_gui_debug();
	}
}
