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
 **/


require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/change_password.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_CHANGE_PASSWORD_PAGE_TITLE' => tr('ispCP - Client/Change Password'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'), 
                     'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_pass') {

    if (empty($_POST['pass']) || empty($_POST['pass_rep']) || empty($_POST['curr_pass'])) {

        set_page_message(tr('Please fill up all data fields!'));

    } else if ($_POST['pass'] !== $_POST['pass_rep']) {

        set_page_message(tr('Passwords do not match!'));

    } else if (!chk_password($_POST['pass'])) {

        set_page_message(tr('Incorrect password length or syntax!'));

    } else if (!check_udata($_SESSION['user_id'], $_POST['curr_pass'])) {

        set_page_message(tr('The current password is wrong!'));

    } else {

    $upass = crypt_user_pass($_POST['pass']);

		$_SESSION['user_pass'] = $upass;

    $user_id = $_SESSION['user_id'];

    $query = <<<SQL_QUERY
            update
              admin
            set
              admin_pass = ?
            where
              admin_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($upass, $user_id));
    write_log($_SESSION['user_logged'].": update password!");
    set_page_message(tr('User password updated successfully!'));
  }
}

function check_udata($id, $pass) {
	global $sql;

	$query = <<<SQL_QUERY
        select
          	 admin_id, admin_pass
        from
            admin
        where
            admin_id = ?
        and
        	admin_pass = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($id,md5($pass)));

  if (($rs -> RecordCount()) != 1)
  	return false; else return true;
}

/*
 *
 * static page messages.
 *
 */
gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_general_information.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_general_information.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_CHANGE_PASSWORD' => tr('Change password'),
                     'TR_PASSWORD_DATA' => tr('Password data'),
                     'TR_PASSWORD' => tr('Password'),
                     'TR_PASSWORD_REPEAT' => tr('Repeat password'),
                     'TR_UPDATE_PASSWORD' => tr('Update password'),
		                 'TR_CURR_PASSWORD' => tr('Current password')
                     ));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

?>