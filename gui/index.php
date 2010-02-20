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

require 'include/ispcp-lib.php';

if (isset($_GET['logout'])) {
	unset_user_login_data();
}

do_session_timeout();

init_login();

if (isset($_POST['uname'])
	&& isset($_POST['upass'])
	&& !empty($_POST['uname'])
	&& !empty($_POST['upass'])) {

	$uname = encode_idna($_POST['uname']);

	check_input(trim($_POST['uname']));
	check_input(trim($_POST['upass']));

	if (register_user($uname, $_POST['upass'])) {
		redirect_to_level_page();
	}

	user_goto('index.php');
}

if (check_user_login() && !redirect_to_level_page()) {
	unset_user_login_data();
}

shall_user_wait();

$theme_color = isset($_SESSION['user_theme'])
	? $_SESSION['user_theme']
	: Config::get('USER_INITIAL_THEME');

$tpl = new pTemplate();

if ((Config::get('MAINTENANCEMODE')
		|| databaseUpdate::getInstance()->checkUpdateExists()
		|| criticalUpdate::getInstance()->checkUpdateExists())
	&& !isset($_GET['admin'])) {

	$tpl->define_dynamic('page', Config::get('LOGIN_TEMPLATE_PATH') . '/maintenancemode.tpl');
	$tpl->assign(
		array(
			'TR_PAGE_TITLE'		=> tr('ispCP Omega a Virtual Hosting Control System'),
			'THEME_COLOR_PATH'	=> Config::get('LOGIN_TEMPLATE_PATH'),
			'THEME_CHARSET'		=> tr('encoding'),
			'TR_MESSAGE'		=> nl2br(Config::get('MAINTENANCEMODE_MESSAGE')),
			'TR_ADMINLOGIN'		=> tr('Administrator login')
		)
	);

} else {

	$tpl->define_dynamic('page', Config::get('LOGIN_TEMPLATE_PATH') . '/index.tpl');

	$tpl->assign(
		array(
			'TR_MAIN_INDEX_PAGE_TITLE'	=> tr('ispCP Omega a Virtual Hosting Control System'),
			'THEME_COLOR_PATH'			=> Config::get('LOGIN_TEMPLATE_PATH'),
			'THEME_CHARSET'				=> tr('encoding'),
			'TR_LOGIN'					=> tr('Login'),
			'TR_USERNAME'				=> tr('Username'),
			'TR_PASSWORD'				=> tr('Password'),
			'TR_LOGIN_INFO'				=> tr('Please enter your login information'),
			// @todo: make this configurable by ispcp-lib
			'TR_SSL_LINK'               => isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] . 'http://' . htmlentities($_SERVER['HTTP_HOST']) : 'https://' . htmlentities($_SERVER['HTTP_HOST']),
			'TR_WEBMAIL_SSL_LINK'       => "webmail",
			'TR_FTP_SSL_LINK'           => "ftp",
			'TR_PMA_SSL_LINK'           => "pma",
			'TR_SSL_IMAGE'              => isset($_SERVER['HTTPS']) ? 'lock.png' : 'unlock.png',
			'TR_SSL_DESCRIPTION'		=> !isset($_SERVER['HTTPS']) ? tr('Secure Connection') : tr('Normal Connection')
		)
	);

}

if (Config::get('LOSTPASSWORD')) {
	$tpl->assign('TR_LOSTPW', tr('Lost password'));
} else {
	$tpl->assign('TR_LOSTPW', '');
}

$tpl->define_dynamic('page_message', 'page');
gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
