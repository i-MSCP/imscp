<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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

// Deactivate HTMLPurifyer HTML Tag stripping
if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_error'){
	define('OVERRIDE_PURIFIER', 1);
}

require '../include/class.vfs.php';
require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/error_pages.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');

// page functions.

function write_error_page(&$sql, $user_id, $eid) {

	$error = stripslashes($_POST['error']);
	$file = '/errors/' . $eid . '.html';
	$vfs = &new vfs($_SESSION['user_logged'], $sql);

	return $vfs->put($file, $error);
}

function update_error_page(&$sql, $user_id) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_error') {
		$eid = intval($_POST['eid']);
		if (in_array($eid, array(401, 402, 403, 404, 500, 503)) && write_error_page($sql, $_SESSION['user_id'], $eid)) {
			set_page_message(tr('Custom error page was updated!'));
		} else {
			set_page_message(tr('System error - custom error page was NOT updated!'));
		}
	}
}

$theme_color = Config::get('USER_INITIAL_THEME');

// common page data.

$domain = $_SESSION['user_logged'];
$domain = "http://www." . $domain;

$tpl->assign(
	array(
		'TR_CLIENT_ERROR_PAGE_TITLE' => tr('ispCP - Client/Manage Error Custom Pages'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
		'DOMAIN' => $domain
		));

// dynamic page data.

update_error_page($sql, $_SESSION['user_id']);

// static page messages.

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_webtools.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
		array(
			'TR_ERROR_401' => tr('Error 401 (unauthorised)'),
			'TR_ERROR_403' => tr('Error 403 (forbidden)'),
			'TR_ERROR_404' => tr('Error 404 (not found)'),
			'TR_ERROR_500' => tr('Error 500 (internal server error)'),
			'TR_ERROR_503' => tr('Error 503 (service unavailable)'),
			'TR_ERROR_PAGES' => tr('Error pages'),
			'TR_EDIT' => tr('Edit'),
			'TR_VIEW' => tr('View')
		)
	);

gen_page_message($tpl);
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>
