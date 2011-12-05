<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/settings_server_traffic.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Admin/Server Traffic Settings'),
		 'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

/**
 * @todo What's about the outcommented code?
 */
function update_server_settings()
{

	if (!isset($_POST['uaction']) && !isset($_POST['uaction'])) {
		return;
	}

	$max_traffic = clean_input($_POST['max_traffic']);
	$traffic_warning = $_POST['traffic_warning'];

	if (!is_numeric($max_traffic) || !is_numeric($traffic_warning)) {
		set_page_message(tr('Wrong data input!'), 'error');
	}

	if ($traffic_warning > $max_traffic) {
		set_page_message(tr('Warning traffic is bigger than max traffic!'), 'warning');

		return;
	}

	if ($max_traffic < 0) {
		$max_traffic = 0;
	}

	if ($traffic_warning < 0) {
		$traffic_warning = 0;
	}

	$query = "UPDATE `straff_settings` SET `straff_max` = ?, `straff_warn` = ?";
	exec_query($query, array($max_traffic, $traffic_warning));

	set_page_message(tr('Server traffic settings updated successfully!'), 'success');
}

/**
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function generate_server_data($tpl)
{

	$query = "SELECT `straff_max`, `straff_warn` FROM`straff_settings`";
	$stmt = exec_query($query);

	$tpl->assign(
		array(
			 'MAX_TRAFFIC' => $stmt->fields['straff_max'],
			 'TRAFFIC_WARNING' => $stmt->fields['straff_warn'],));
}

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

$tpl->assign(
	array(
		 'TR_MODIFY' => tr('Modify'),
		 'TR_SERVER_TRAFFIC_SETTINGS' => tr('Server traffic settings'),
		 'TR_SET_SERVER_TRAFFIC_SETTINGS' => tr('Server traffic settings'),
		 'TR_MAX_TRAFFIC' => tr('Max traffic'),
		 'TR_WARNING' => tr('Warning traffic'),
		'TR_MIB' => tr('MiB')));

update_server_settings();
generate_server_data($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
