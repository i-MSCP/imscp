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
 * Main
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/server_status.tpl',
		'page_message' => 'layout',
		'service_status' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / General / Services Status'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_SERVICE' => tr('Service Name'),
		'TR_IP' => tr('IP Address'),
		'TR_PORT' => tr('Port'),
		'TR_STATUS' => tr('Status'),
		'TR_SERVER_STATUS' => tr('Server status'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);

// Services status string
$running = tr('UP');
$down = tr('DOWN');

$services = new iMSCP_Services();

foreach ($services as $service) {
	$services->setService($services->key($services), false);

	if ($services->isVisible()) {
		$serviceState = $services->isRunning();

		$tpl->assign(
			array(
				'SERVICE' => tohtml($services->getName()),
				'IP' => tohtml($services->getIp()),
				'PORT' => tohtml($services->getPort()),
				'STATUS' => $serviceState ? "<b>$running</b>" : $down,
				'CLASS' => $serviceState ? 'up' : 'down'
			)
		);

		$tpl->parse('SERVICE_STATUS', '.service_status');
	}
}

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
