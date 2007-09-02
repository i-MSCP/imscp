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

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/rootkit_log.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('service_status', 'page');

$tpl -> define_dynamic('props_list', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_ROOTKIT_LOG_PAGE_TITLE' => tr('ispCP Admin / System Tools / Anti-Rootkits Tools Log Checker'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
						'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
                     )
              );

$blocksCount = 0;

/* Check Log File */

$config_entries = array('RKHUNTER_LOG', 'CHKROOTKIT_LOG', 'OTHER_ROOTKIT_LOG');

foreach ($config_entries as $config_entry) {
    if (empty($config_entry) || !isset($cfg[$config_entry])) {
        continue;
    }

    $filename = $cfg[$config_entry];
    $contents = '';

    if (!is_readable($filename)) {

        $contents = "<b><font color='#FF0000'>" . tr("%s doesn't exist or can't be read.", $filename) . "</font></b>" ;

    } else {

        $handle = fopen($filename, 'r');

        $log = fread($handle, filesize($filename));

        fclose($handle);

        $contents = nl2br(htmlentities($log));

        $contents = '<div>' . $contents . '</div>';

        $search = array();
        $replace = array();

        // rkhunter-like log colouring
        $search [] = '/[^\-]WARNING/i';
        $replace[] = '<strong><font color="orange">$0</font></strong>';
        $search [] = '/([^a-z])(OK)([^a-z])/i';
        $replace[] = '$1<font color="green">$2</font>$3';
        $search [] = '/[ \t]+clean[ \t]+/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '/Not found/i';
        $replace[] = '<font color="blue">$0</font>';
        $search [] = '/Skipped/i';
        $replace[] = '<font color="blue">$0</font>';
        $search [] = '/unknown[^)]/i';
        $replace[] = '<strong><font color="#bf55bf">$0</font></strong>';
        $search [] = '/Unsafe/i';
        $replace[] = '<strong><font color="#cfcf00">$0</font></strong>';
        $search [] = '/[1-9][0-9]*[ \t]+vulnerable/i';
        $replace[] = '<strong><font color="red">$0</font></strong>';
        $search [] = '/0[ \t]+vulnerable/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '#(\[[0-9]{2}:[[0-9]{2}:[[0-9]{2}\][ \t]+-{20,35}[ \t]+)([a-zA-Z0-9 ]+)([ \t]+-{20,35})<br />#e';
        $replace[] = '"</div><a href=\"#\" onclick=\"showHideBlocks(\'rkhuntb" . $blocksCount . "\');return false;\">$1<b>$2</b>$3</a><br /><div id=\"rkhuntb" . $blocksCount++ . "\">"';

        // chkrootkit-like log colouring
        $search [] = '/([^a-z][ \t]+)(INFECTED)/i';
        $replace[] = '$1<strong><font color="red">$2</font></strong>';
        $search [] = '/Nothing found/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '/Nothing detected/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '/Not infected/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '/no packet sniffer/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '/(: )(PACKET SNIFFER)/i';
        $replace[] = '$1<font color="orange">$2</font>';
        $search [] = '/not promisc/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '/no suspect file(s|)/i';
        $replace[] = '<font color="green">$0</font>';
        $search [] = '/([0-9]+) process(|es) hidden/i';
        $replace[] = '<font color="#cfcf00">$0</font>';

        $contents = preg_replace($search, $replace, $contents);

    }
    $tpl -> assign(
            array(
              'LOG' => $contents,
              'FILENAME' => $filename
            )
    );
    $tpl -> parse('PROPS_LIST', '.props_list');
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_system_tools.tpl');

$tpl -> assign(
                array(
					   'TR_ROOTKIT_LOG' => tr('Anti-Rootkits Tools Log Checker'),
                     )
              );


gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG'])
	dump_gui_debug();

unset_messages();
?>