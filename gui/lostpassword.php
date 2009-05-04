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

require 'include/ispcp-lib.php';

if (!Config::get('LOSTPASSWORD')) {
	system_message(tr('Retrieving lost passwords is currently not possible'));
	die();
}

// check for gd >= 2.x
if (!check_gd()) {
	system_message("ERROR: php-extension 'gd' not loaded!");
}

if (!captcha_fontfile_exists()) {
	system_message("ERROR: captcha fontfile not found!");
}

// remove old uniqkeys
removeOldKeys(Config::get('LOSTPASSWORD_TIMEOUT'));

if (isset($_SESSION['user_theme'])) {
	$theme_color = $_SESSION['user_theme'];
} else {
	$theme_color = Config::get('USER_INITIAL_THEME');
}

if (isset($_GET['key'])) {
	if ($_GET['key'] != "") {
		check_input($_GET['key']);

		$tpl = new pTemplate();
		$tpl->define('page', Config::get('LOGIN_TEMPLATE_PATH') . '/lostpassword_message.tpl');
		$tpl->assign(
			array(
				'TR_MAIN_INDEX_PAGE_TITLE' => tr('ispCP - Virtual Hosting Control System'),
				'THEME_COLOR_PATH' => "themes/$theme_color",
				'THEME_CHARSET' => tr('encoding')
			)
		);

		if (sendpassword($_GET['key'])) {
			$tpl->assign(
				array(
					'TR_MESSAGE' => tr('Password sent'),
					'TR_LINK' => "<a class=\"link\" href=\"index.php\">" . tr('Login') . "</a>"
				)
			);
		} else {
			$tpl->assign(
				array(
					'TR_MESSAGE' => tr('ERROR: Password was not sent'),
					'TR_LINK' => "<a class=\"link\" href=\"index.php\">" . tr('Login') . "</a>"
				)
			);
		}

		$tpl->parse('PAGE', 'page');
		$tpl->prnt();

		if (Config::get('DUMP_GUI_DEBUG'))
			dump_gui_debug();
		exit(0);
	}
}

if (isset($_POST['uname'])) {
	check_ipaddr(getipaddr(), 'captcha');

	if (($_POST['uname'] != "") && isset($_SESSION['image']) && isset($_POST['capcode'])) {
		check_input(trim($_POST['uname']));
		check_input($_POST['capcode']);

		$tpl = new pTemplate();
		$tpl->define('page', Config::get('LOGIN_TEMPLATE_PATH') . '/lostpassword_message.tpl');
		$tpl->assign(
			array(
				'TR_MAIN_INDEX_PAGE_TITLE' => tr('ispCP - Virtual Hosting Control System'),
				'THEME_COLOR_PATH' => "themes/$theme_color",
				'THEME_CHARSET' => tr('encoding')
			)
		);

		if ($_SESSION['image'] == $_POST['capcode']) {
			if (requestpassword($_POST['uname'])) {
				$tpl->assign(
					array(
						'TR_MESSAGE' => tr('The password was requested'),
						'TR_LINK' => "<a class=\"link\" href=\"index.php\">" . tr('Back') . "</a>"
					)
				);
			} else {
				$tpl->assign(
					array(
						'TR_MESSAGE' => tr('ERROR: Unknown user'),
						'TR_LINK' => "<a class=\"link\" href=\"lostpassword.php\">" . tr('Retry') . "</a>"
					)
				);
			}
		} else {
			$tpl->assign(
				array(
					'TR_MESSAGE' => tr('ERROR: Security code was not correct!') . ' ' . $_SESSION['image'],
					'TR_LINK' => "<a class=\"link\" href=\"lostpassword.php\">" . tr('Retry') . "</a>"
				)
			);
		}

		$tpl->parse('PAGE', 'page');
		$tpl->prnt();

		if (Config::get('DUMP_GUI_DEBUG'))
			dump_gui_debug();
		exit(0);
	}
}

unblock(Config::get('BRUTEFORCE_BLOCK_TIME'), 'captcha');
is_ipaddr_blocked(null, 'captcha', true);

$tpl = new pTemplate();
$tpl->define('page', Config::get('LOGIN_TEMPLATE_PATH') . '/lostpassword.tpl');
$tpl->assign(
	array(
		'TR_MAIN_INDEX_PAGE_TITLE' => tr('ispCP - Virtual Hosting Control System'),
		'THEME_COLOR_PATH' => Config::get('LOGIN_TEMPLATE_PATH'),
		'THEME_CHARSET' => tr('encoding'),
		'TR_CAPCODE' => tr('Security code'),
		'TR_IMGCAPCODE_DESCRIPTION' => tr('(To avoid abuse, we ask you to write the combination of letters on the above picture into the field "Security code")'),
		'TR_IMGCAPCODE' => "<img src=\"imagecode.php\" width=\"" . Config::get('LOSTPASSWORD_CAPTCHA_WIDTH') . "\" height=\"" . Config::get('LOSTPASSWORD_CAPTCHA_HEIGHT') . "\" border=\"0\" alt=\"captcha image\">",
		'TR_USERNAME' => tr('Username'),
		'TR_SEND' => tr('Request password'),
		'TR_BACK' => tr('Back')
	)
);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

?>