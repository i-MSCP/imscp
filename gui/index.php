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

require 'include/imscp-lib.php';

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('Config');

if (isset($_GET['logout'])) {
	unset_user_login_data();
}

do_session_timeout();
init_login();

if (isset($_POST['uname']) && isset($_POST['upass']) && !empty($_POST['uname']) && !empty($_POST['upass'])) {
	$uname = encode_idna($_POST['uname']);

	check_input(trim($_POST['uname']));
	check_input(trim($_POST['upass']));

	register_user($uname, $_POST['upass']);
	//user_goto('index.php');
}

if (check_user_login() && !redirect_to_level_page()) {
	unset_user_login_data();
}

shall_user_wait();

$theme_color = isset($_SESSION['user_theme']) ? $_SESSION['user_theme'] : $cfg->USER_INITIAL_THEME;

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('lostpwd', 'page');

if (($cfg->MAINTENANCEMODE || iMSCP_Update_Database::getInstance()->checkUpdateExists()) && !isset($_GET['admin'])) {

	$tpl->define_dynamic('page', $cfg->LOGIN_TEMPLATE_PATH . '/maintenancemode.tpl');
	$tpl->assign(
		array(
			'TR_PAGE_TITLE'		=> tr('i-MSCP i-MSCP - Multi Server Control Panel'),
			'THEME_COLOR_PATH'	=> $cfg->LOGIN_TEMPLATE_PATH,
			'THEME_CHARSET'		=> tr('encoding'),
			'TR_MESSAGE'		=> nl2br(tohtml($cfg->MAINTENANCEMODE_MESSAGE)),
			'TR_ADMINLOGIN'		=> tr('Administrator login')
		)
	);

} else {
	$tpl->define_dynamic('page', $cfg->LOGIN_TEMPLATE_PATH . '/index.tpl');

	$tpl->assign(
		array(
			'TR_MAIN_INDEX_PAGE_TITLE'	=> tr('i-MSCP - Multi Server Control Panel'),
			'THEME_COLOR_PATH'			=> $cfg->LOGIN_TEMPLATE_PATH,
			'THEME_CHARSET'				=> tr('encoding'),
			'TR_LOGIN'					=> tr('Login'),
			'TR_USERNAME'				=> tr('Username'),
			'TR_PASSWORD'				=> tr('Password'),
			'TR_PHPMYADMIN'				=> tr('phpMyAdmin'),
			'TR_FILEMANAGER'			=> tr('FileManager'),
			'TR_WEBMAIL'				=> tr('Webmail'),
			// @todo: make this configurable by i-mscp-lib
			'TR_SSL_LINK'               => isset($_SERVER['HTTPS'])
				? 'http://' . htmlentities($_SERVER['HTTP_HOST']) : 'https://' . htmlentities($_SERVER['HTTP_HOST']),
			'TR_WEBMAIL_LINK'       	=> 'webmail',
			'TR_FTP_LINK'           	=> 'ftp',
			'TR_PMA_LINK'           	=> 'pma',
			'TR_SSL_IMAGE'              => isset($_SERVER['HTTPS']) ? 'lock.png' : 'unlock.png',
			'TR_SSL_DESCRIPTION'		=> !isset($_SERVER['HTTPS']) ? tr('Secure Connection') : tr('Normal Connection')
		)
	);
}

if ($cfg->LOSTPASSWORD) {
	$tpl->assign('TR_LOSTPW', tr('Lost password'));
} else {
	$tpl->assign('LOSTPWD', '');
}

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) dump_gui_debug();
