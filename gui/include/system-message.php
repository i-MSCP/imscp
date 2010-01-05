<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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

/**
 * @todo possible session injection, check $_SESSION['user_theme'] for valid value
 */
function system_message($msg, $backButtonDestination = "") {
	$theme_color = (isset($_SESSION['user_theme']))
		? $_SESSION['user_theme']
		: Config::get('USER_INITIAL_THEME');

	if (empty($backButtonDestination)) {
		$backButtonDestination = "javascript:history.go(-1)";
	}

	$tpl = new pTemplate();

	// If we are on the login page, path will be like this
	$template = Config::get('LOGIN_TEMPLATE_PATH') . '/system-message.tpl';

	if (!is_file($template)) {
		// But if we're inside the panel it will be like this
		$template = '../' . Config::get('LOGIN_TEMPLATE_PATH') . '/system-message.tpl';
	}

	if (!is_file($template)) {
		// And if we don't find the template, we'll just die displaying error message
		die($msg);
	}

	$tpl->define('page', $template);
	$tpl->assign(
		array(
			'TR_SYSTEM_MESSAGE_PAGE_TITLE'	=> tr('ispCP Error'),
			'THEME_COLOR_PATH'				=> '/themes/' . $theme_color,
			'THEME_CHARSET'					=> tr('encoding'),
			'TR_BACK'						=> tr('Back'),
			'TR_ERROR_MESSAGE'				=> tr('Error Message'),
			'MESSAGE'						=> $msg,
			'BACKBUTTONDESTINATION'			=> $backButtonDestination
		)
	);

	$tpl->parse('PAGE', 'page');
	$tpl->prnt();

	die();
}
