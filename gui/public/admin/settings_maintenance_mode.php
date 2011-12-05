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

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/settings_maintenance_mode.tpl');

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin/Maintenance mode'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

$selected_on = '';
$selected_off = '';

if (isset($_POST['uaction']) AND $_POST['uaction'] == 'apply') {

	$maintenancemode = $_POST['maintenancemode'];
	$maintenancemode_message = clean_input($_POST['maintenancemode_message']);

	$db_cfg = iMSCP_Registry::get('dbConfig');

	$db_cfg->MAINTENANCEMODE = $maintenancemode;
	$db_cfg->MAINTENANCEMODE_MESSAGE = $maintenancemode_message;

	$cfg->replaceWith($db_cfg);

	set_page_message(tr('Settings saved !'), 'success');
}

if ($cfg->MAINTENANCEMODE) {
	$selected_on = $cfg->HTML_SELECTED;
} else {
	$selected_off = $cfg->HTML_SELECTED;
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_system_tools.tpl');

$tpl->assign(
	array(
		'TR_MAINTENANCEMODE' => tr('Maintenance mode'),
		'TR_MESSAGE_TEMPLATE_INFO' => tr('Under this mode only administrators can login'),
		'TR_MESSAGE' => tr('Message'),
		'MESSAGE_VALUE' => $cfg->MAINTENANCEMODE_MESSAGE,
		'SELECTED_ON' => $selected_on,
		'SELECTED_OFF' => $selected_off,
		'TR_ENABLED' => tr('Enabled'),
		'TR_DISABLED' => tr('Disabled'),
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_MAINTENANCE_MESSAGE' => tr('Maintenance message')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
