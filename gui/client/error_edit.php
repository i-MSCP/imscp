<?php
/**
 *  ispCP ω OMEGA a Virtual Hosting Control System
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
 **/

require '../include/vfs.php';
require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/error_edit.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');

function gen_error_page_data(&$tpl, &$sql, $user_id, $eid) {
	$domain = $_SESSION['user_logged'];

	// Check if we already have an error page
	$vfs   =& new vfs($domain, $sql);
	$error =  $vfs->get('/errors/' . $eid . '.html');
	if (false !== $error) {
		// We already have an error page, return it
		$tpl->assign(array('ERROR' => $error));
		return;
	}

	// No error info :'(
	$tpl->assign( array('ERROR' => '') );
}

//
// common page data.
//

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_CLIENT_ERROR_PAGE_TITLE' => tr('ISPCP - Client/Manage Error Custom Pages'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'TID' => $_SESSION['layout_id'],
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id'])
                     )
              );

//
// dynamic page data.
//
if (!isset($_GET['eid'])) {
	set_page_message(tr('Server error - please choose error page'));
	header("Location: error_pages.php");
	die();
} else {
	$eid = intval($_GET['eid']);
}

if ($eid == 401 || $eid == 403 || $eid == 404 | $eid == 500) {
	gen_error_page_data($tpl, $sql, $_SESSION['user_id'], $_GET['eid']);
} else {
	$tpl -> assign(
                array(
                        'ERROR' => tr('Server error - please choose error page'),
                        'EID' => '0'
                     )
              );
}

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);


$tpl -> assign(
                array(
                        'TR_ERROR_EDIT_PAGE' => tr('Edit error page'),
                        'TR_SAVE' => tr('Save'),
                        'TR_CANCEL' => tr('Cancel'),
                        'EID' => $eid
                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>