<?php
/**
 *  VHCS ω (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		VHCS Team (2006)
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

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/rootkit_log.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('service_status', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_ROOTKIT_LOG_PAGE_TITLE' => tr('VHCS Admin / System Tools / Rootkit Hunter Log'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );

/* Check Log File */
$filename = "/var/log/rkhunter.log";

if (!is_readable($filename)) {

	$contents = "<b><font color='#FF0000'>The file doesn't exist or can't be opened.</font><b>" ;

}
else {

	$handle = fopen($filename, "r");

	$log = fread($handle, filesize($filename));

	$contents = nl2br($log);

	fclose($handle);
}
$tpl -> assign(
				array(
					'LOG' => $contents
				     )
			  );

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_system_tools.tpl');

$tpl -> assign(
                array(
					   'TR_ROOTKIT_LOG' => tr('Rootkit Log Checker'),
                     )
              );


gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG']))
	dump_gui_debug();

unset_messages();
?>