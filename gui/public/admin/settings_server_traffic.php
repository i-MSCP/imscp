<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Update server traffic settings.
 *
 * @param int $trafficLimit Monthly traffic limit
 * @param int $trafficWarning Traffic warning
 * @return bool TRUE on success FALSE otherwise
 */
function admin_updateServerTrafficSettings($trafficLimit, $trafficWarning)
{
	$retVal = true;

	if (!is_numeric($trafficLimit)) {
		set_page_message(tr('Monthly traffic limit must be a number.'), 'error');
		$retVal = false;
	}

	if (!is_numeric($trafficWarning)) {
		set_page_message(tr('Monthly traffic warning must be a number.'), 'error');
		$retVal = false;
	}

	if ($retVal && $trafficWarning > $trafficLimit) {
		set_page_message(tr('Monthly traffic warning cannot be bigger than monthly traffic limit.'), 'error');
		$retVal = false;
	}

	if ($retVal) {
		/** @var $db_cfg iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		$dbConfig->SERVER_TRAFFIC_LIMIT = $trafficLimit;
		$dbConfig->SERVER_TRAFFIC_WARN = $trafficWarning;

		// gets the number of queries that were been executed
		$updtCount = $dbConfig->countQueries('update');
		$newCount = $dbConfig->countQueries('insert');

		// An Update was been made in the database ?
		if ($updtCount || $newCount) {
			set_page_message(tr('Server traffic settings successfully updated.', $updtCount), 'success');
			write_log("{$_SESSION['user_logged']} updated server  traffic settings.", E_USER_NOTICE);
		} else {
			set_page_message(tr("Nothing has been changed."), 'info');
		}

	}

	return $retVal;
}

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $trafficLimit Monthly traffic limit
 * @param int $trafficWarning Traffic warning
 * @return void
 */
function admin_generatePage($tpl, $trafficLimit, $trafficWarning)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (empty($_POST)) {
		$trafficLimit = $cfg->SERVER_TRAFFIC_LIMIT;
		$trafficWarning = $cfg->SERVER_TRAFFIC_WARN;
	}

	$tpl->assign(
		array(
			'MAX_TRAFFIC' => tohtml($trafficLimit),
			'TRAFFIC_WARNING' => tohtml($trafficWarning)
		)
	);
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

$trafficLimit = $trafficWarning = 0;

// Dispatches the request
if (!empty($_POST)) {
	$trafficLimit = !isset($_POST['max_traffic']) ? : clean_input($_POST['max_traffic']);
	$trafficWarning = !isset($_POST['traffic_warning']) ? : clean_input($_POST['traffic_warning']);

	admin_updateServerTrafficSettings($trafficLimit, $trafficWarning);
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
		'TR_PAGE_TITLE' => tr('Admin / Settings / Server Traffic'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_SET_SERVER_TRAFFIC_SETTINGS' => tr('Server traffic settings'),
		'TR_MAX_TRAFFIC' => tr('Max traffic'),
		'TR_WARNING' => tr('Warning traffic'),
		'TR_MIB' => tr('MiB'),
		'TR_UPDATE' => tr('Update'),
	)
);

generateNavigation($tpl);
admin_generatePage($tpl, $trafficLimit, $trafficWarning);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
