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
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

// Purge expired sessions
do_session_timeout();

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onLoginScriptStart);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'default';
$auth = iMSCP_Authentication::getInstance();
init_login($auth->getEvents());

switch ($action) {
	case 'logout':
		if ($auth->hasIdentity()) {
			$adminName = $auth->getIdentity()->admin_name;
			$auth->unsetIdentity();
			set_page_message(tr('You have been successfully logged out.'), 'success');
			write_log(sprintf("%s logged out", decode_idna($adminName)), E_USER_NOTICE);
		}
		break;
	case 'login':
		// Authentication process is triggered whatever the status of the following variables since authentication
		// is pluggable and plugins can provide their own authentication logic without using these variables.
		if (!empty($_REQUEST['uname'])) $auth->setUsername(clean_input($_REQUEST['uname']));
		if (!empty($_REQUEST['upass'])) $auth->setPassword(clean_input($_REQUEST['upass']));
		$result = $auth->authenticate();

		if ($result->isValid()) { // Authentication process succeeded
			write_log(sprintf("%s logged in", $result->getIdentity()->admin_name), E_USER_NOTICE);
		} elseif (($messages = $result->getMessages())) { // Authentication process failed
			$messages = format_message($messages);
			set_page_message($messages, 'error');
			write_log(sprintf("Authentication failed. Reason: %s", $messages), E_USER_NOTICE);
		}
}

# Redirect user to its interface level
if($action != 'logout') redirectToUiLevel();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/simple.tpl',
		'page_message' => 'layout',
		'lostpwd_button' => 'page'
	)
);

$tpl->assign(
	array(
		'productLongName' => tr('internet Multi Server Control Panel'),
		'productLink' => 'http://www.i-mscp.net',
		'productCopyright' => tr('© 2010-2014 i-MSCP Team<br/>All Rights Reserved')
	)
);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if ($cfg['MAINTENANCEMODE'] && !isset($_REQUEST['admin'])) {
	$tpl->define_dynamic('page', 'message.tpl');
	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Maintenance'),
			'CONTEXT_CLASS' => ' no_header',
			'BOX_MESSAGE_TITLE' => tr('System under maintenance'),
			'BOX_MESSAGE' => nl2br(tohtml($cfg['MAINTENANCEMODE_MESSAGE'])),
			'TR_BACK' => tr('Administrator login'),
			'BACK_BUTTON_DESTINATION' => '/index.php?admin=1'
		)
	);
} else {
	$tpl->define_dynamic(
		array(
			'page' => 'index.tpl',
			'lost_password_support' => 'page',
			'ssl_support' => 'page'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Login'),
			'CONTEXT_CLASS' => '',
			'TR_LOGIN' => tr('Login'),
			'TR_USERNAME' => tr('Username'),
			'UNAME' => isset($_REQUEST['uname']) ? stripslashes($_REQUEST['uname']) : '',
			'TR_PASSWORD' => tr('Password')
		)
	);

	if (
		$cfg->exists('PANEL_SSL_ENABLED') && $cfg['PANEL_SSL_ENABLED'] == 'yes' &&
		$cfg['BASE_SERVER_VHOST_PREFIX'] != 'https://'
	) {
		$tpl->assign(
			array(
				'SSL_LINK' => isset($_SERVER['HTTPS']) ? 'http://' . tohtml($_SERVER['HTTP_HOST']) : 'https://' . tohtml($_SERVER['HTTP_HOST']),
				'SSL_IMAGE_CLASS' => isset($_SERVER['HTTPS']) ? 'i_unlock' : 'i_lock',
				'TR_SSL' => !isset($_SERVER['HTTPS']) ? tr('Secure connection') : tr('Normal connection'),
				'TR_SSL_DESCRIPTION' => !isset($_SERVER['HTTPS']) ? tr('Use secure connection (SSL)') : tr('Use normal connection (No SSL)')
			)
		);
	} else {
		$tpl->assign('SSL_SUPPORT', '');
	}

	if ($cfg['LOSTPASSWORD']) {
		$tpl->assign('TR_LOSTPW', tr('Lost password'));
	} else {
		$tpl->assign('LOST_PASSWORD_SUPPORT', '');
	}
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onLoginScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
