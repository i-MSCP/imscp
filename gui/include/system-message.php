<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 *
 **/

function system_message($msg) {
	global $cfg;

	if (isset($_SESSION['user_theme'])) {

		$theme_color = $_SESSION['user_theme'];

	} else {

		$theme_color = $cfg['USER_INITIAL_THEME'];

	}

	$tpl = new pTemplate();

    // If we are on the login page, path will be like this
    $template = $cfg['LOGIN_TEMPLATE_PATH'].'/system-message.tpl';

    if (!is_file($template)) {
        // But if we're inside the panel it will be like this
        $template = '../'.$cfg['LOGIN_TEMPLATE_PATH'].'/system-message.tpl';
    }
    if (!is_file($template)) {
        // And if we don't find the template, we'll just die displaying error message
        die($msg);
    }
	$tpl->define('page', $template);
	$tpl->assign(
					array(
						'TR_SYSTEM_MESSAGE_PAGE_TITLE' => tr('ispCP Error'),
						'THEME_COLOR_PATH' => "/themes/$theme_color",
						'THEME_CHARSET' => tr('encoding'),
						'TR_BACK' => tr('Back'),
						'TR_ERROR_MESSAGE' => tr('Error Message'),
						'MESSAGE' => $msg
						)
					);

	$tpl->parse('PAGE', 'page');
	$tpl->prnt();

	exit(0);
}

?>