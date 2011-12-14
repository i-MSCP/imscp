<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 *
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Login
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['logout'])) {
	unset_user_login_data();
}

do_session_timeout();
init_login();

if(!empty($_POST)) {
	if (isset($_POST['uname']) && isset($_POST['upass'])) {
		if (!empty($_POST['uname']) && !empty($_POST['upass'])) {
			$uname = encode_idna($_POST['uname']);
			check_input(trim($_POST['uname']));
			check_input(trim($_POST['upass']));
			register_user($uname, $_POST['upass']);
		} else {
			set_page_message(tr('All fields are required.'), 'error');
		}
	}
}

if (check_user_login() && !redirect_to_level_page()) {
	unset_user_login_data();
}

shall_user_wait();

$theme_color = isset($_SESSION['user_theme']) ? $_SESSION['user_theme'] : $cfg->USER_INITIAL_THEME;

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('lostpwd_button', 'page');
$tpl->define_dynamic('ssl_support', 'page');
$tpl->assign(
	array(
		'productLongName' => tr('internet Multi Server Control Panel'),
		'productLink' => 'http://www.i-mscp.net',
		'productCopyright' => tr('© 2010-2011 i-MSCP Team<br/>All Rights Reserved'),
		'THEME_CHARSET' => tr('encoding'),
		'THEME_COLOR_PATH' => $cfg->LOGIN_TEMPLATE_PATH));

if (($cfg->MAINTENANCEMODE || iMSCP_Update_Database::getInstance()->isAvailableUpdate()) && !isset($_GET['admin'])) {
	$tpl->define_dynamic('page', $cfg->LOGIN_TEMPLATE_PATH . '/maintenancemode.tpl');
	$tpl->assign(
		array(
			'TR_MESSAGE' => nl2br(tohtml($cfg->MAINTENANCEMODE_MESSAGE)),
			'TR_ADMINLOGIN' => tr('Administrator login')));
} else {
	$tpl->define_dynamic('page', $cfg->LOGIN_TEMPLATE_PATH . '/index.tpl');
	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Login'),
			'TR_LOGIN' => tr('Login'),
			'TR_USERNAME' => tr('Username'),
			'TR_PASSWORD' => tr('Password'),
			'TR_PHPMYADMIN' => tr('phpMyAdmin'),
			'TR_FILEMANAGER' => tr('FileManager'),
			'TR_WEBMAIL' => tr('Webmail'),
			'TR_WEBMAIL_LINK' => '/webmail',
			'TR_FTP_LINK' => '/ftp',
			'TR_PMA_LINK' => '/pma'));
}

if ($cfg->exists('SSL_ENABLED') && $cfg->SSL_ENABLED == 'yes') {
	$tpl->assign(array(
		'SSL_LINK' => isset($_SERVER['HTTPS']) ? 'http://' . htmlentities($_SERVER['HTTP_HOST']) : 'https://' . htmlentities($_SERVER['HTTP_HOST']),
		'SSL_IMAGE_CLASS' => isset($_SERVER['HTTPS']) ? 'i_unlock' : 'i_lock',
		'TR_SSL' => !isset($_SERVER['HTTPS']) ? tr('Secure connection') : tr('Normal connection'),
		'TR_SSL_DESCRIPTION' => !isset($_SERVER['HTTPS']) ? tr('Use secure connection (SSL)') : tr('Use normal connection (No SSL)')
	));
} else {
	$tpl->assign('SSL_SUPPORT', '');
}


if ($cfg->LOSTPASSWORD) {
	$tpl->assign('TR_LOSTPW', tr('Lost password'));
} else {
	$tpl->assign('LOSTPWD_BUTTON', '');
}

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onLoginScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
