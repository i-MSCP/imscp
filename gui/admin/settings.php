<?php
/**
 *  VHCS ω (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		VHCS Team, Benedikt Heintel (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 *
 **/

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/settings.tpl');

$tpl -> define_dynamic('def_language', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_SETTINGS_PAGE_TITLE' => tr('VHCS - Admin/Settings'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );


if (isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {

	$lostpassword 				= clean_input($_POST['lostpassword']);
	$lostpassword_timeout 		= clean_input($_POST['lostpassword_timeout']);
	$bruteforce 				= clean_input($_POST['bruteforce']);
	$bruteforce_between 		= clean_input($_POST['bruteforce_between']);
	$bruteforce_max_login 		= clean_input($_POST['bruteforce_max_login']);
	$bruteforce_block_time 		= clean_input($_POST['bruteforce_block_time']);
	$bruteforce_between_time 	= clean_input($_POST['bruteforce_between_time']);
  	$user_initial_lang 			= clean_input($_POST['def_language']);
	$support_system 			= clean_input($_POST['support_system']);
	$domain_rows_per_page 		= clean_input($_POST['domain_rows_per_page']);

	if ( (!is_number($lostpassword_timeout)) OR (!is_number($bruteforce_max_login))
		OR (!is_number($bruteforce_block_time))	OR (!is_number($bruteforce_between_time))
		OR (!is_number($domain_rows_per_page)) ) {

			set_page_message(tr('ERROR: Only positive numbers are allowed !'));

	} else if ($domain_rows_per_page < 1) {
		$domain_rows_per_page = 1;
	} else {
		setConfig_Value('LOSTPASSWORD', $lostpassword);
		setConfig_Value('LOSTPASSWORD_TIMEOUT', $lostpassword_timeout);
		setConfig_Value('BRUTEFORCE', $bruteforce);
		setConfig_Value('BRUTEFORCE_BETWEEN', $bruteforce_between);
		setConfig_Value('BRUTEFORCE_MAX_LOGIN', $bruteforce_max_login);
		setConfig_Value('BRUTEFORCE_BLOCK_TIME', $bruteforce_block_time);
		setConfig_Value('BRUTEFORCE_BETWEEN_TIME', $bruteforce_between_time);
		setConfig_Value('USER_INITIAL_LANG', $user_initial_lang);
		setConfig_Value('VHCS_SUPPORT_SYSTEM', $support_system);
		setConfig_Value('DOMAIN_ROWS_PER_PAGE', $domain_rows_per_page);
		set_page_message(tr('Settings saved !'));
	}
}

$tpl -> assign(
                array(
					'LOSTPASSWORD_TIMEOUT_VALUE' => $cfg['LOSTPASSWORD_TIMEOUT'],
					'BRUTEFORCE_MAX_LOGIN_VALUE' => $cfg['BRUTEFORCE_MAX_LOGIN'],
					'BRUTEFORCE_BLOCK_TIME_VALUE' => $cfg['BRUTEFORCE_BLOCK_TIME'],
					'BRUTEFORCE_BETWEEN_TIME_VALUE' => $cfg['BRUTEFORCE_BETWEEN_TIME'],
					'DOMAIN_ROWS_PER_PAGE' => $cfg['DOMAIN_ROWS_PER_PAGE']
					)
			);

gen_def_language($tpl, $sql, $cfg['USER_INITIAL_LANG']);

if ($cfg['LOSTPASSWORD'] == 1) {
	$tpl -> assign('LOSTPASSWORD_SELECTED_ON', 'selected');
	$tpl -> assign('LOSTPASSWORD_SELECTED_OFF', '');
} else {
	$tpl -> assign('LOSTPASSWORD_SELECTED_ON', '');
	$tpl -> assign('LOSTPASSWORD_SELECTED_OFF', 'selected');
}

if ($cfg['BRUTEFORCE'] == 1) {
	$tpl -> assign('BRUTEFORCE_SELECTED_ON', 'selected');
	$tpl -> assign('BRUTEFORCE_SELECTED_OFF', '');
} else {
	$tpl -> assign('BRUTEFORCE_SELECTED_ON', '');
	$tpl -> assign('BRUTEFORCE_SELECTED_OFF', 'selected');
}

if ($cfg['BRUTEFORCE_BETWEEN'] == 1) {
	$tpl -> assign('BRUTEFORCE_BETWEEN_SELECTED_ON', 'selected');
	$tpl -> assign('BRUTEFORCE_BETWEEN_SELECTED_OFF', '');
} else {
	$tpl -> assign('BRUTEFORCE_BETWEEN_SELECTED_ON', '');
	$tpl -> assign('BRUTEFORCE_BETWEEN_SELECTED_OFF', 'selected');
}

if ($cfg['VHCS_SUPPORT_SYSTEM'] == 1) {
	$tpl -> assign('SUPPORT_SYSTEM_SELECTED_ON', 'selected');
	$tpl -> assign('SUPPORT_SYSTEM_SELECTED_OFF', '');
} else {
	$tpl -> assign('SUPPORT_SYSTEM_SELECTED_ON', '');
	$tpl -> assign('SUPPORT_SYSTEM_SELECTED_OFF', 'selected');
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_settings.tpl');

$tpl -> assign(
                array(
                       'TR_GENERAL_SETTINGS' => tr('General settings'),
                       'TR_SETTINGS' => tr('Settings'),
              		   'TR_MESSAGE' => tr('Message'),
                       'TR_LOSTPASSWORD' => tr('Lostpassword'),
                       'TR_LOSTPASSWORD_TIMEOUT' => tr('Activationlink valid (minutes)'),
                       'TR_BRUTEFORCE' => tr('Bruteforce detection'),
                       'TR_BRUTEFORCE_BETWEEN' => tr('Time between logins'),
                       'TR_BRUTEFORCE_MAX_LOGIN' => tr('Number of login attempts'),
                       'TR_BRUTEFORCE_BLOCK_TIME' => tr('Blocktime (minutes)'),
                       'TR_BRUTEFORCE_BETWEEN_TIME' => tr('Time between logins (seconds)'),
                       'TR_OTHER_SETTINGS' => tr('Other settings'),
                       'TR_USER_INITIAL_LANG' => tr('Default language'),
                       'TR_SUPPORT_SYSTEM' => tr('Support system'),
                       'TR_ENABLED' => tr('Enabled'),
                       'TR_DISABLED' => tr('Disabled'),
                       'TR_APPLY_CHANGES' => tr('Apply changes'),
                       'TR_SERVERPORTS' => tr('Serverports'),
                       'TR_DOMAIN_ROWS_PER_PAGE' => tr('Domains per page')
                     )
              );


gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
