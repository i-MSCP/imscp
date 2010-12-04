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

// Include needed libraries
require '../include/imscp-lib.php';

// Check for login
check_login(__FILE__);

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('Config');

/**
 * @var $dbUpdate iMSCP_Update_Database
 */
$dbUpdate = iMSCP_Update_Database::getInstance();

if(isset($_POST['uaction']) && $_POST['uaction'] == 'update') {

	// Execute all available db updates
	if(!$dbUpdate->executeUpdates()) {
		throw new iMSCP_Exception($dbUpdate->getErrorMessage());
	}

	// Set success page message
	set_page_message('All database update were successfully applied', 'success');

	// Redirect back to database_update.php
	user_goto($_SERVER['PHP_SELF']);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/database_update.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('database_update', 'page');

$tpl->assign(
	array(
		'TR_PAGE_TITLE'	=> tr('i-MSCP - Admin / System tools / Database Update'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
		'TR_SECTION_TITLE' => tr('Database updates')
	)
);

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_system_tools.tpl');

if($dbUpdate->checkUpdateExists()) {
	$tpl->assign(
		array(
			'TR_AVAILABLE_UPDATES' => tr('Available database updates'),
			'TR_UPDATE' => tr('Update'),
			'TR_INFOS' => tr('Update details'),
			'UPDATE' => tr('New Database update is now available'),
			'INFOS' => tr("No provided"), // Todo add system to be able to rpovide some info
			'TR_EXECUTE_UPDATE' => tr('Execute updates')
		)
	);
} else {
	$tpl->assign('DATABASE_UPDATE', '');
	set_page_message('No database updates available');
}

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
