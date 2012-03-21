<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onLoginScriptStart);

// Purge expired sessions
do_session_timeout();

$auth = iMSCP_Authentication::getInstance();

// Init login process
init_login($auth->events());

if (isset($_GET['logout']) && $auth->hasIdentity()) {
	$adminName = $auth->getIdentity()->admin_name;
	$auth->unsetIdentity();
	set_page_message(tr('You have been successfully logged out.'), 'success');
	write_log(sprintf("%s logged out", $adminName), E_USER_NOTICE);
	redirectTo('index.php');
} elseif (!empty($_POST)) {
	if (isset($_POST['uname']) && isset($_POST['upass'])) {
		if (!empty($_POST['uname']) && !empty($_POST['upass'])) {
			$result = $auth
				->setUsername(idn_to_ascii(clean_input($_POST['uname'])))
				->setPassword(clean_input($_POST['upass']))
				->authenticate();

			if (!$result->isValid()) {
				if(($messages = $result->getMessages())) {
					$messages = format_message($messages);
					set_page_message($messages, 'error');
					write_log(sprintf("Authentication failed. Reason: %s", $messages), E_USER_NOTICE);
				} else {
					write_log('Authentication failed for unknown reason', E_USER_NOTICE);
				}
			} else {
				write_log(sprintf("%s logged in", $result->getIdentity()->admin_name), E_USER_NOTICE);
			}
		} else {
			set_page_message(tr('All fields are required.'), 'error');
		}
	}
}

redirectToUiLevel();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/simple.tpl',
		'page_message' => 'layout',
		'lostpwd_button' => 'page',));

$tpl->assign(
	array(
		'productLongName' => tr('internet Multi Server Control Panel'),
		'productLink' => 'http://www.i-mscp.net',
		'productCopyright' => tr('© 2010-2012 i-MSCP Team<br/>All Rights Reserved'),
		'THEME_CHARSET' => tr('encoding')));

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if ($cfg->MAINTENANCEMODE && !isset($_GET['admin'])) {
	$tpl->define_dynamic('page', 'box.tpl');
	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Maintenance'),
			'CONTEXT_CLASS' => 'box_message',
			'BOX_MESSAGE_TITLE' => tr('System under maintenance'),
			'BOX_MESSAGE' => nl2br(tohtml($cfg->MAINTENANCEMODE_MESSAGE)),
			'TR_BACK' => tr('Administrator login'),
			'BACK_BUTTON_DESTINATION' => '/index.php?admin=1'));
} else {
	$tpl->define_dynamic(
		array(
			'page' => 'index.tpl',
			'ssl_support' => 'page'));

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Login'),
			'CONTEXT_CLASS' => 'login',
			'TR_LOGIN' => tr('Login'),
			'TR_USERNAME' => tr('Username'),
			'TR_PASSWORD' => tr('Password'),
			'TR_PHPMYADMIN' => tr('phpMyAdmin'),
			'TR_LOGIN_INTO_PMA' => tr('Login into PḧpMyAdmin'),
			'TR_FILEMANAGER' => tr('FileManager'),
			'TR_LOGIN_INTO_WEBMAIL' => tr('Login into the webmail'),
			'TR_WEBMAIL' => tr('Webmail'),
			'TR_WEBMAIL_LINK' => '/webmail',
			'TR_LOGIN_INTO_FMANAGER' => tr('Login into the filemanager'),
			'TR_FTP_LINK' => '/ftp',
			'TR_PMA_LINK' => '/pma'));

	if ($cfg->exists('SSL_ENABLED') && $cfg->SSL_ENABLED == 'yes') {
		$tpl->assign(array(
			'SSL_LINK' => isset($_SERVER['HTTPS']) ? 'http://' . htmlentities($_SERVER['HTTP_HOST']) : 'https://' . htmlentities($_SERVER['HTTP_HOST']),
			'SSL_IMAGE_CLASS' => isset($_SERVER['HTTPS']) ? 'i_unlock' : 'i_lock',
			'TR_SSL' => !isset($_SERVER['HTTPS']) ? tr('Secure connection') : tr('Normal connection'),
			'TR_SSL_DESCRIPTION' => !isset($_SERVER['HTTPS']) ? tr('Use secure connection (SSL)') : tr('Use normal connection (No SSL)')));
	} else {
		$tpl->assign('SSL_SUPPORT', '');
	}


	if ($cfg->LOSTPASSWORD) {
		$tpl->assign('TR_LOSTPW', tr('Lost password'));
	} else {
		$tpl->assign('LOSTPWD_BUTTON', '');
	}
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onLoginScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
