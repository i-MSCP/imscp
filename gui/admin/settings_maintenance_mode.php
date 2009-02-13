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
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/settings_maintenance_mode.tpl');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
		array(
			'TR_ADMIN_MAINTENANCEMODE_PAGE_TITLE' => tr('ispCP - Admin/Maintenance mode'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id'])
			)
		);

$selected_on = '';

$selected_off = '';

if (isset($_POST['uaction']) AND $_POST['uaction'] == 'apply') {

	$maintenancemode = $_POST['maintenancemode'];
	$maintenancemode_message = clean_input($_POST['maintenancemode_message']);

	setConfig_Value('MAINTENANCEMODE', $maintenancemode);
	setConfig_Value('MAINTENANCEMODE_MESSAGE', $maintenancemode_message);

	set_page_message(tr('Settings saved !'));
}

if (Config::get('MAINTENANCEMODE')) {
	$selected_on = 'selected="selected"';
} else {
	$selected_off = 'selected="selected"';
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_system_tools.tpl');

$tpl->assign(
		array(
			'TR_MAINTENANCEMODE' => tr('Maintenance mode'),
			'TR_MESSAGE_TEMPLATE_INFO' => tr('Under this mode only administrators can login'),
			'TR_MESSAGE' => tr('Message'),
			'MESSAGE_VALUE' => Config::get('MAINTENANCEMODE_MESSAGE'),
			'SELECTED_ON' => $selected_on,
			'SELECTED_OFF' => $selected_off,
			'TR_ENABLED' => tr('Enabled'),
			'TR_DISABLED' => tr('Disabled'),
			'TR_APPLY_CHANGES' => tr('Apply changes')
			)
		);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>