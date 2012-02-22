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
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/******************************************************************************
 * Script functions
 */

/**
 * Update server traffic settings.
 *
 * @param int $trafficLimit Traffic limit
 * @param int $trafficWarning Traffic warning
 * @return bool TRUE on success FALSE otherwise
 */
function admin_updateServerTrafficSettings($trafficLimit, $trafficWarning)
{
	$retVal = true;

	if (!is_numeric($trafficLimit)) {
		set_page_message(tr('Traffic limit must be a digit.'), 'error');
		$retVal = false;
	}

	if (!is_numeric($trafficWarning)) {
		set_page_message(tr('Traffic warning must be a digit.'), 'error');
		$retVal = false;
	}

	if ($retVal && $trafficWarning > $trafficLimit) {
		set_page_message(tr('Traffic warning cannot be bigger than traffic limit.'), 'error');
		$retVal = false;
	}

	if($retVal) {
		$query = "UPDATE `straff_settings` SET `straff_max` = ?, `straff_warn` = ?";
		exec_query($query, array($trafficLimit, $trafficWarning));
	}

	return $retVal;
}

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $trafficLimit Traffic limit
 * @param int $trafficWarning Traffic warning
 * @return void
 */
function admin_generatePage($tpl, $trafficLimit, $trafficWarning)
{
	if(empty($_POST)) {
		$query = "SELECT `straff_max`, `straff_warn` FROM`straff_settings`";
		$stmt = exec_query($query);

		if($stmt->rowCount()) {
			$trafficLimit = $stmt->fields['straff_max'];
			$trafficWarning = $stmt->fields['straff_warn'];
		} else {
			$query = "INSERT INTO `straff_settings` SET `straff_max` = ?, `straff_warn` = ?";
			exec_query($query, array($trafficLimit, $trafficWarning));
		}
	}

	$tpl->assign(
		array(
			'MAX_TRAFFIC' => tohtml($trafficLimit),
			'TRAFFIC_WARNING' => tohtml($trafficWarning)
		)
	);
}

/******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

$trafficLimit = $trafficWarning = 0;

// Dispatches the request
if (!empty($_POST)) {
	$trafficLimit = !isset($_POST['max_traffic']) ?: clean_input($_POST['max_traffic']);
	$trafficWarning = !isset($_POST['traffic_warning']) ?: clean_input($_POST['traffic_warning']);

	if (admin_updateServerTrafficSettings($trafficLimit, $trafficWarning)) {
		set_page_message(tr('Server traffic settings successfully updated.'), 'success');
	}
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/settings_server_traffic.tpl',
		'page_message' => 'layout',
		'hosting_plans' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Settings / Server traffic settings'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_SET_SERVER_TRAFFIC_SETTINGS' => tr('Server traffic settings'),
		'TR_MAX_TRAFFIC' => tr('Max traffic'),
		'TR_WARNING' => tr('Warning traffic'),
		'TR_MIB' => tr('MiB'),
		'TR_MODIFY' => tr('Modify'),
	)
);

generateNavigation($tpl);
admin_generatePage($tpl, $trafficLimit, $trafficWarning);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
