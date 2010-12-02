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
 *
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * 
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

// Include needed library
require 'include/imscp-lib.php';

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('Config');

// Lost password feature is disabled ?
if (!$cfg->LOSTPASSWORD) {
	user_goto('/index.php');
}

// Check for gd library availability
if (!check_gd()) {
	throw new iMSCP_Exception(tr("Error: php-extension 'gd' not loaded!"));
}

// Check for font files availability
if (!captcha_fontfile_exists()) {
	throw new iMSCP_Exception(tr('Error: Captcha fontfile not found!'));
}

// Remove old unique keys
removeOldKeys($cfg->LOSTPASSWORD_TIMEOUT);

// Set the theme
isset($_SESSION['user_theme']) ? $theme_color = $_SESSION['user_theme'] : $theme_color = $cfg->USER_INITIAL_THEME;

// Creating new iMSCP_pTemplate instance
$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('page', $cfg->LOGIN_TEMPLATE_PATH . '/lostpassword.tpl');

$tpl->assign(
	array(
		'TR_MAIN_INDEX_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel - Lost password'),
		'THEME_COLOR_PATH' => $cfg->LOGIN_TEMPLATE_PATH,
		'THEME_CHARSET' => tr('encoding'),
		'productLongName' => tr('internet Multi Server Control Panel'),
		'productLink' => 'http://www.i-mscp.net',
		'productCopyright' => tr('© Copyright 2010 i-MSCP Team<br/>All Rights Reserved'),
		'TR_CAPCODE' => tr('Security code'),
		'TR_IMGCAPCODE_DESCRIPTION' => tr(
			'To avoid abuse, we ask you to write the combination of letters on the picture above into the field "Security code"'
		),
		'TR_IMGCAPCODE' => '<img src="imagecode.php" width="' . $cfg->LOSTPASSWORD_CAPTCHA_WIDTH . '" height="' .
			$cfg->LOSTPASSWORD_CAPTCHA_HEIGHT . '" border="0" alt="captcha image" />',
		'TR_USERNAME' => tr('Username'),
		'TR_SEND' => tr('Get password'),
		'TR_BACK' => tr('Back')
	)
);

// A request for new password was validated (User clicked on the link he has received by mail)
if (isset($_GET['key']) && $_GET['key'] != '') {
	// Check key
	check_input($_GET['key']);

	// Sending new password
	if (sendpassword($_GET['key'])) {
		set_page_message(tr('Your new password has been sent. Check your mail.'));
	} else {
		iMSCP_Registry::set('messageCls', 'error');
		set_page_message(tr('New password has not been sent. Ask your administrator.'));
	}
} elseif (isset($_POST['uname'])) { // Request for new password

	// Check if we are not blocked (brute force feature)
	check_ipaddr(getipaddr(), 'captcha');

	if ($_POST['uname'] != '' && isset($_SESSION['image']) && isset($_POST['capcode'])) {
		check_input(trim($_POST['uname']));
		check_input($_POST['capcode']);

		if ($_SESSION['image'] == $_POST['capcode'] && requestPassword($_POST['uname'])) {
			set_page_message(
				tr('Your password request has been initiated. You will receive an email with instructions to complete the process. This reset request will expire in %s minutes.')
			);
		} else {
			iMSCP_Registry::set('messageCls', 'error');
			set_page_message(tr('User or security code are incorrect!'));
		}
	} else {
		iMSCP_Registry::set('messageCls', 'error');
		set_page_message(tr('All fields are required!'));
	}
} else { // Lost password form (Default)
	unblock($cfg->BRUTEFORCE_BLOCK_TIME, 'captcha');
	is_ipaddr_blocked(null, 'captcha', true);
}

// Generate page message
gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) dump_gui_debug();
