<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
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
