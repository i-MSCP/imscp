<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

include '../include/ispcp-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/servicemode.tpl');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_SERVICEMODE_PAGE_TITLE' => tr('ISPCP - Admin/Servicemode'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
                     )
              );

$selected_on = '';

$selected_off = '';

if (isset($_POST['uaction']) AND $_POST['uaction'] == 'apply') {

	$servicemode = $_POST['servicemode'];

	$servicemode_message = clean_input($_POST['servicemode_message']);

	setConfig_Value('SERVICEMODE', $servicemode);

	setConfig_Value('SERVICEMODE_MESSAGE', $servicemode_message);

	set_page_message(tr('Settings saved !'));

}

if ($cfg['SERVICEMODE']) {
	$selected_on = 'selected';
} else {
	$selected_off = 'selected';
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_system_tools.tpl');

$tpl -> assign(
                array(
                       'TR_SERVICEMODE' => tr('Servicemode'),
                       'TR_MESSAGE_TEMPLATE_INFO' => tr('In this mode only administrators can login'),
              		   'TR_MESSAGE' => tr('Message'),
					   'MESSAGE_VALUE' => $cfg['SERVICEMODE_MESSAGE'],
					   'SELECTED_ON' => $selected_on,
					   'SELECTED_OFF' => $selected_off,
    	               'TR_ENABLED' => tr('Enabled'),
                       'TR_DISABLED' => tr('Disabled'),
                       'TR_APPLY_CHANGES' => tr('Apply changes')
                    )
              );


gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>