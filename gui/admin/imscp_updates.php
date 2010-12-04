<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require '../include/imscp-lib.php';

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/imscp_updates.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('update_message', 'page');
$tpl->define_dynamic('update_infos', 'page');
$tpl->define_dynamic('table_header', 'page');

$tpl->assign(
	array(
		'TR_ADMIN_IMSCP_UPDATES_PAGE_TITLE' => tr('i-MSCP - Multi Server Control Panel'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => get_logo($_SESSION['user_id'])
	)
);

/* BEGIN common functions */
function get_update_infos(&$tpl) {

	$cfg = iMSCP_Registry::get('config');

	if (!$cfg->CHECK_FOR_UPDATES) {
		$tpl->assign(
			array(
				'UPDATE_MESSAGE'	=> '',
				'UPDATE'			=> tr('Update checking is disabled!'),
				'INFOS'				=> tr('Enable update at') . " <a href=\"settings.php\">" . tr('Settings') . "</a>"
			)
		);
		$tpl->parse('UPDATE_INFOS', 'update_infos');
		return false;
	}

	if (iMSCP_Update_Version::getInstance()->checkUpdateExists()) {
		$tpl->assign(
			array(
				'UPDATE_MESSAGE' => '',
				'UPDATE' => tr('New i-MSCP update is now available'),
				'INFOS' => tr('Get it at') . " <a href=\"http://www.i-mscp.net/download.html\" class=\"link\" target=\"i-mscp\">http://www.i-mscp.net/download.html</a>"
			)
		);

		$tpl->parse('UPDATE_INFOS', 'update_infos');
	} else {
		if (iMSCP_Update_Version::getInstance()->getErrorMessage() != "") {
			$tpl->assign(array('TR_MESSAGE' => iMSCP_Update_Version::getInstance()->getErrorMessage()));
		} else {
			$tpl->assign('TABLE_HEADER', '');
		}
		$tpl->assign('UPDATE_INFOS', '');
	}
}
/* END system functions */

/*
 *
 * static page messages.
 *
 */

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_system_tools.tpl');

$tpl->assign(
	array(
		'TR_UPDATES_TITLE' => tr('i-MSCP updates'),
		'TR_AVAILABLE_UPDATES' => tr('Available i-MSCP updates'),
		'TR_MESSAGE' => tr('No new i-MSCP updates available'),
		'TR_UPDATE' => tr('Update'),
		'TR_INFOS' => tr('Update details')
	)
);

gen_page_message($tpl);

get_update_infos($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
