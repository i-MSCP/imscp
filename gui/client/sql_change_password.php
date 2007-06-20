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
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/sql_change_password.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');

if (isset($_GET['id'])) {
  $db_user_id = $_GET['id'];
} else if (isset($_POST['id'])) {
  $db_user_id = $_POST['id'];
} else {
  user_goto('manage_sql.php');
}

//
// page functions.
//
function change_sql_user_pass(&$sql, $db_user_id, $db_user_name)
{
  global $cfg;

  if (!isset($_POST['uaction'])) return;

  if ($_POST['pass'] === '' && $_POST['pass_rep'] === '') {
    set_page_message(tr('Please type user password!'));
    return;
  }

  if ($_POST['pass'] !== $_POST['pass_rep']) {
    set_page_message(tr('Entered passwords does not match!'));
    return;
  }

  if (strlen($_POST['pass']) > $cfg['MAX_SQL_PASS_LENGTH']) {
    set_page_message(tr('Too long user password!'));
    return;
  }

	if (chk_password($_POST['pass'])) {
  	set_page_message( tr("Incorrect password range or syntax!"));
    return;
  }

  $user_pass = $_POST['pass'];

  //
  // update user pass in the ispcp sql_user table;
  //
  $query = <<<SQL_QUERY
        update
            sql_user
        set
            sqlu_pass = ?
        where
            sqlu_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($user_pass, $db_user_id));

  //
  // update user pass in the mysql system tables;
  //
  $query = <<<SQL_QUERY

        SET PASSWORD FOR '$db_user_name'@'%' = PASSWORD('$user_pass')

SQL_QUERY;

    $rs = execute_query($sql, $query);

  $query = <<<SQL_QUERY

	SET PASSWORD FOR '$db_user_name'@localhost = PASSWORD('$user_pass')

SQL_QUERY;
    $rs = execute_query($sql, $query);

    write_log($_SESSION['user_logged'].": update SQL user password: ".$db_user_name);
    set_page_message(tr('SQL user password was successfully changed!'));
    user_goto('manage_sql.php');
}

function gen_page_data(&$tpl, &$sql, $db_user_id)
{
  $query = <<<SQL_QUERY
        select sqlu_name from sql_user where sqlu_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($db_user_id));
  $tpl -> assign(array('USER_NAME' => $rs -> fields['sqlu_name'],
                       'ID' => $db_user_id));
  return $rs -> fields['sqlu_name'];
}

//
// common page data.
//
if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no")
{
  header("Location: index.php");
}

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_SQL_CHANGE_PASSWORD_PAGE_TITLE' => tr('ISPCP - Client/Change SQL User Password'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
$db_user_name = gen_page_data($tpl, $sql, $db_user_id);
check_usr_sql_perms($sql, $db_user_id);
change_sql_user_pass($sql, $db_user_id, $db_user_name);

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_manage_sql.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_manage_sql.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_CHANGE_SQL_USER_PASSWORD' => tr('Change SQL user password'),
                     'TR_USER_NAME' => tr('User name'),
                     'TR_PASS' => tr('Password'),
                     'TR_PASS_REP' => tr('Password (repeat)'),
                     'TR_CHANGE' => tr('Change')));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

?>