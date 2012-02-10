<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/rootkit_log.tpl',
		'page_message' => 'layout',
		'service_status' => 'page',
		'props_list' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP Admin / System Tools / Anti-Rootkits Tools Log Checker'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

$blocksCount = 0;

// Check Log File

$config_entries = array('RKHUNTER_LOG', 'CHKROOTKIT_LOG', 'OTHER_ROOTKIT_LOG');

foreach ($config_entries as $config_entry) {
	if (empty($config_entry) || !$cfg->exists($config_entry) || !$cfg->$config_entry) {
		continue;
	}

	$filename = $cfg->$config_entry;
	$contents = '';

	if (@file_exists($filename) && is_readable($filename) && filesize($filename)>0) {
		$handle = fopen($filename, 'r');

		$log = fread($handle, filesize($filename));

		fclose($handle);

		$contents = nl2br(tohtml($log));

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
		$contents = '<strong style="color:red">' . tr("%s doesn't exist or is empty", $filename) . '</strong>';
	}

	$tpl->assign(
		array(
			'LOG' => $contents,
			'FILENAME' => tohtml($filename)));

	$tpl->parse('PROPS_LIST', '.props_list');
}

generateNavigation($tpl);

$tpl->assign('TR_ROOTKIT_LOG' , tr('Anti-Rootkits Tools Log Checker'));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
