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
 * Functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function admin_generatePage($tpl)
{
	$query = "
			SELECT
				`t1`.`id`, `t1`.`name`, `t1`.`props`, `t1`.`status`
			FROM
				`hosting_plans` AS `t1`
			LEFT JOIN
				`admin` AS `t2` ON(`t2`.`admin_id` = `t1`.`reseller_id`)
			WHERE
				`t2`.`admin_type` = ?
			ORDER BY
				`t1`.`id`
		";
	$stmt = exec_query($query, 'admin');

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'HOSTING_PLANS_JS',
				'HOSTING_PLANS' => ''
			)
		);

		set_page_message(tr("No hosting plan available."), 'info');
	} else {
		$tpl->assign(
			array(
				'TR_NUMBER' => tr('No.'),
				'TR_NAME' => tr('Name'),
				'TR_STATUS' => tr('Status'),
				'TR_ACTIONS' => tr('Actions'),
				'TR_EDIT' => tr('Edit'),
				'TR_DELETE' => tr('Delete'),
				'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s hosting plan?', true, '%s')
			)
		);

		$i = 1;

		while ($data = $stmt->fetchRow()) {
			$tpl->assign(
				array(
					'NUMBER' => $i++,
					'NAME' => tohtml($data['name']),
					'STATUS' => ($data['status']) ? tr('Available') : tr('Unavailable'),
					'ID' => $data['id'],
				)
			);

			$tpl->parse('HOSTING_PLAN', '.hosting_plan');
		}
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if ($cfg->HOSTING_PLANS_LEVEL == 'admin') {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'admin/hosting_plan.tpl',
			'page_message' => 'layout',
			'hosting_plans' => 'page',
			'hosting_plan' => 'hosting_plans'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Admin / Hosting Plans / Overview'),
			'ISP_LOGO' => layout_getUserLogo()
		)
	);

	generateNavigation($tpl);
	admin_generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
