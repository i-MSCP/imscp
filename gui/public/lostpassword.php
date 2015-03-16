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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

// Include core library
require_once 'imscp-lib.php';
require_once LIBRARY_PATH . '/Functions/LostPassword.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onLostPasswordScriptStart);

// Purge expired sessions
do_session_timeout();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Lost password feature is disabled ?
if (!$cfg['LOSTPASSWORD']) {
	redirectTo('/index.php');
}

// Check for gd library availability
if (!check_gd()) {
	throw new iMSCP_Exception(tr("PHP GD extension not loaded."));
}

// Remove old unique keys
removeOldKeys($cfg['LOSTPASSWORD_TIMEOUT']);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/simple.tpl',
	'page' => 'lostpassword.tpl',
	'page_message' => 'layout'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel / Lost Password'),
	'CONTEXT_CLASS' => '',
	'productLongName' => tr('internet Multi Server Control Panel'),
	'productLink' => 'http://www.i-mscp.net',
	'productCopyright' => tr('© 2010-2015 i-MSCP Team<br/>All Rights Reserved'),
	'TR_CAPCODE' => tr('Security code'),
	'GET_NEW_IMAGE' => tr('Get a new image'),
	'TR_IMGCAPCODE' => '<img id="captcha" src="imagecode.php" width="' . $cfg['LOSTPASSWORD_CAPTCHA_WIDTH'] .
		'" height="' . $cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'] . '" alt="captcha image" />',
	'TR_USERNAME' => tr('Username'),
	'TR_SEND' => tr('Send'),
	'TR_CANCEL' => tr('Cancel')
));

// A request for new password was validated ( User clicked on the link he has received by mail )
if (isset($_GET['key']) && $_GET['key'] != '') {
	// Check key
	clean_input($_GET['key']);

	// Sending new password
	if (sendPassword($_GET['key'])) {
		set_page_message(tr('Your new password has been sent. Check your email.'), 'success');
		redirectTo('index.php');
	} else {
		set_page_message(tr('New password has not been sent. Ask your administrator.'), 'error');
	}
} elseif (!empty($_POST)) { // Request for new password
	$bruteForce = new iMSCP_Plugin_Bruteforce(iMSCP_Registry::get('pluginManager'), 'captcha');

	if ($bruteForce->isWaiting() || $bruteForce->isBlocked()) {
		set_page_message($bruteForce->getLastMessage(), 'error');
		redirectTo('lostpassword.php');
	} else {
		$bruteForce->recordAttempt();
	}

	if (!empty($_POST['uname']) && isset($_SESSION['image']) && isset($_POST['capcode'])) {
		clean_input($_POST['uname']);
		clean_input($_POST['capcode']);

		if ($_SESSION['image'] != $_POST['capcode']) {
			set_page_message(tr('Wrong security code'), 'error');
		} elseif (!requestPassword($_POST['uname'])) {
			set_page_message(tr('Wrong username.'), 'error');
		} else {
			set_page_message(tr('Your request for new password has been registered. You will receive an email with instructions to complete the process.'), 'success');
		}
	} else {
		set_page_message(tr('All fields are required.'), 'error');
	}
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onLostPasswordScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
