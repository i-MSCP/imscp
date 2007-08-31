<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
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

require 'include/ispcp-lib.php';

if (isset($_GET['logout'])) {
    unset_user_login_data();
}

do_session_timeout();

init_login();

if (isset($_POST['uname']) && isset($_POST['upass']) && !empty($_POST['uname']) && !empty($_POST['upass'])) {

	$uname = encode_idna($_POST['uname']);

	check_input($_POST['uname']);

	check_input($_POST['upass']);

	if (register_user($uname, $_POST['upass'])) {
	    redirect_to_level_page();
	}

	header('Location: index.php');
	exit;
}

if (check_user_login()) {
    if (!redirect_to_level_page()) {
        unset_user_login_data();
    }
}

shall_user_wait();

if (isset($_SESSION['user_theme'])) {
	$theme_color = $_SESSION['user_theme'];
} else {
	$theme_color = $cfg['USER_INITIAL_THEME'];
}

$tpl = new pTemplate();

if ($cfg['MAINTENANCEMODE'] && !isset($_GET['admin'])) {

	$tpl -> define('page', $cfg['LOGIN_TEMPLATE_PATH'].'/maintenancemode.tpl');

	$tpl -> assign(array(
						'TR_PAGE_TITLE' => tr('ispCP Omega a Virtual Hosting Control System'),
						'THEME_COLOR_PATH' => $cfg['LOGIN_TEMPLATE_PATH'],
						'THEME_CHARSET' => tr('encoding'),
						'TR_MESSAGE' => nl2br($cfg['MAINTENANCEMODE_MESSAGE']),
						'TR_ADMINLOGIN' => tr('Administrator login')
						)
					);

} else {

	$tpl -> define('page', $cfg['LOGIN_TEMPLATE_PATH'].'/index.tpl');

	$tpl -> assign(array(
						'TR_MAIN_INDEX_PAGE_TITLE' => tr('ispCP Omega a Virtual Hosting Control System'),
						'THEME_COLOR_PATH' => $cfg['LOGIN_TEMPLATE_PATH'],
						'THEME_CHARSET' => tr('encoding'),
						'TR_LOGIN' => tr('Login'),
						'TR_USERNAME' => tr('Username'),
						'TR_PASSWORD' => tr('Password'),
						'TR_LOGIN_INFO' => tr('Please enter your login information'),
						// Please make this configurable by ispcp-lib
						'TR_SSL_LINK' => isset($_SERVER['HTTPS']) ? 'http://'.$_SERVER['HTTP_HOST'] : 'https://'.$_SERVER['HTTP_HOST'],
						'TR_SSL_IMAGE' => isset($_SERVER['HTTPS']) ? 'lock.png' : 'unlock.png',
						'TR_SSL_DESCRIPTION' => !isset($_SERVER['HTTPS']) ? tr('Secure Connection') : tr('Normal Connection')
						)
					);

}

if ($cfg['LOSTPASSWORD'])
	$tpl->assign('TR_LOSTPW', tr('Lost password'));
else
	$tpl->assign('TR_LOSTPW', '');

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

?>