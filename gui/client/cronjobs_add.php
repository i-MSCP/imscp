<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware	|
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

$tpl -> define_dynamic('page', $cfg['CLIENT_TEMPLATE_PATH'].'/cronjobs_add.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('logged_from', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_CLIENT_CRONJOBS_TITLE' => tr('VHCS - Client/Cronjob Manager'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'TID' => $_SESSION['layout_id'],
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
						'ISP_LOGO' => get_logo($_SESSION['user_id'])
                     )
              );

/*
Functions start
*/
function add_cron_job(&$tpl, &$sql, $user_id)
{

} // End of add_cron_job();

/*
Functions end
*/


/*
 *
 * static page messages.
 *
 */

gen_client_mainmenu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg['CLIENT_TEMPLATE_PATH'].'/menu_webtools.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

add_cron_job($tpl, $sql, $_SESSION['user_id']);

$tpl -> assign(
                array(
                       'TR_CRON_MANAGER' => tr('Cronjob Manager'),
					   'TR_ADD_CRONJOB' => tr('Add Cronjob'),
					   'TR_NAME' => tr('Name'),
					   'TR_DESCRIPTION' => tr('Description'),
					   'TR_ACTIVE' => tr('Active'),
					   'YES' => tr('Yes'),
					   'NO' => tr('No'),
					   'TR_CRONJOB' => tr('Cronjob'),
					   'TR_COMMAND' => tr('Command to run:'),
					   'TR_MIN' => tr('Minute(s):'),
					   'TR_HOUR' => tr('Hour(s):'),
					   'TR_DAY' => tr('Day(s):'),
					   'TR_MONTHS' => tr('Months(s):'),
					   'TR_WEEKDAYS' => tr('Weekday(s):'),
					   'TR_ADD' => tr('Add'),
					   'TR_RESET' => tr('Reset'),
					   'TR_CANCEL' => tr('Cancel'),
                     )
              );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
