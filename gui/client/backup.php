<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware							|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/backup.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('custom_buttons', 'page');

//
// page functions.
//
function send_backup_restore_request(&$sql, $user_id)
{
  if (isset($_POST['uaction']) && $_POST['uaction'] === 'bk_restore') {
    check_for_lock_file();

    $query = <<<SQL_QUERY
        UPDATE
            domain
        SET
            domain_status = 'restore'
        WHERE
            domain_admin_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id));

    send_request();
    write_log($_SESSION['user_logged'].": restore backup files.");
    set_page_message(tr('Backup archive scheduled for restoring!'));
  }
}

//
// common page data.
//
global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_CLIENT_BACKUP_PAGE_TITLE' => tr('VHCS - Client/Daily Backup'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'TID' => $_SESSION['layout_id'],
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

//
// dynamic page data.
//
send_backup_restore_request($sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl -> assign(array('TR_BACKUP' => tr('Backup'),
                     'TR_DAILY_BACKUP' => tr('Daily backup'),
                     'TR_DOWNLOAD_DIRECTION' => tr('Direction download today backup'),
                     'TR_FTP_LOG_ON' => tr('Login with your FTP account'),
                     'TR_SWITCH_TO_BACKUP' => tr('Switch to backup directory'),
                     'TR_DOWNLOAD_FILE' => tr('Download the only one file in this directory'),
                     'TR_USUALY_NAMED' => tr('(usually named backup_YYY_MM_DD.tar)'),
                     'TR_RESTORE_BACKUP' => tr('Restore backup'),
                     'TR_RESTORE_DIRECTIONS' => tr('Click Restore button and the system will<br>restore last daily backup'),
                     'TR_RESTORE' => tr('Restore')));

gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();
if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();
unset_messages();

?>
