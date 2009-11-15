<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/rootkit_log.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('service_status', 'page');
$tpl->define_dynamic('props_list', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_ROOTKIT_LOG_PAGE_TITLE' => tr('ispCP Admin / System Tools / Anti-Rootkits Tools Log Checker'),
		'THEME_COLOR_PATH' => "../themes/$theme_color",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

$blocksCount = 0;

/* Check Log File */

$config_entries = array('RKHUNTER_LOG', 'CHKROOTKIT_LOG', 'OTHER_ROOTKIT_LOG');

foreach ($config_entries as $config_entry) {
	if (empty($config_entry) || !Config::exists($config_entry) || !Config::get($config_entry)) {
		continue;
	}

	$filename = Config::get($config_entry);
	$contents = '';

	if (@file_exists($filename) && is_readable($filename) && filesize($filename)>0) {
		$handle = fopen($filename, 'r');

		$log = fread($handle, filesize($filename));

		fclose($handle);

		$contents = nl2br(htmlentities($log));

		$contents = '<div>' . $contents . '</div>';

		$search = array();
		$replace = array();
		// rkhunter-like log colouring
		$search [] = '/[^\-]WARNING/i';
		$replace[] = '<strong style="color:orange">$0</strong>';
		$search [] = '/([^a-z])(OK)([^a-z])/i';
		$replace[] = '$1<span style="color:green">$2</span>$3';
		$search [] = '/[ \t]+clean[ \t]+/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '/Not found/i';
		$replace[] = '<span style="color:blue">$0</span>';
		$search [] = '/Skipped/i';
		$replace[] = '<span style="color:blue">$0</span>';
		$search [] = '/unknown[^)]/i';
		$replace[] = '<strong style="color:#bf55bf">$0</strong>';
		$search [] = '/Unsafe/i';
		$replace[] = '<strong style="color:#cfcf00">$0</strong>';
		$search [] = '/[1-9][0-9]*[ \t]+vulnerable/i';
		$replace[] = '<strong style="color:red">$0</strong>';
		$search [] = '/0[ \t]+vulnerable/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '#(\[[0-9]{2}:[[0-9]{2}:[[0-9]{2}\][ \t]+-{20,35}[ \t]+)([a-zA-Z0-9 ]+)([ \t]+-{20,35})<br />#e';
		$replace[] = '"</div><a href=\"#\" onclick=\"showHideBlocks(\'rkhuntb" . $blocksCount . "\');return false;\">$1<b>$2</b>$3</a><br /><div id=\"rkhuntb" . $blocksCount++ . "\">"';
		// chkrootkit-like log colouring
		$search [] = '/([^a-z][ \t]+)(INFECTED)/i';
		$replace[] = '$1<strong style="color:red">$2</strong>';
		$search [] = '/Nothing found/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '/Nothing detected/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '/Not infected/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '/no packet sniffer/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '/(: )(PACKET SNIFFER)/i';
		$replace[] = '$1<span style="color:orange">$2</span>';
		$search [] = '/not promisc/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '/no suspect file(s|)/i';
		$replace[] = '<span style="color:green">$0</span>';
		$search [] = '/([0-9]+) process(|es) hidden/i';
		$replace[] = '<span style="color:#cfcf00">$0</span>';

		$contents = preg_replace($search, $replace, $contents);
	} else {
		$contents = '<strong style="color:#FF0000">' . tr("%s doesn't exist or is empty", $filename) . '</strong>';
	}

	$tpl->assign(
		array(
			'LOG' => $contents,
			'FILENAME' => $filename
		)
	);
	$tpl->parse('PROPS_LIST', '.props_list');
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_system_tools.tpl');

$tpl->assign(
	array(
		'TR_ROOTKIT_LOG' => tr('Anti-Rootkits Tools Log Checker'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();
