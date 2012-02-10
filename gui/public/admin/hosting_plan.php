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

/************************************************************************************
 * Script functions
 */

/**
 * Generates hosting plans list.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 */
function admin_generateHostingPlansList($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`t1`.`id`, `t1`.`reseller_id`, `t1`.`name`, `t1`.`props`, `t1`.`status`,
			`t2`.`admin_id`, `t2`.`admin_type`
		FROM
			`hosting_plans` `t1`,
			`admin` `t2`
		WHERE
			`t2`.`admin_type` = ?
		AND
			`t1`.`reseller_id` = t2.`admin_id`
		ORDER BY
			`t1`.`name`
	";
	$stmt = exec_query($query, 'admin');

	if (!$stmt->rowCount()) {
		set_page_message(tr('Hosting plans not found.'), 'info');
		$tpl->assign('HP_TABLE', '');
	} else {
		$editTranslation = tr('Edit');
		$deleteTranslation = tr('Delete');
		$showHostingPlanTranslation = tr('Show hosting plan');

		$tpl->assign(
			array(
				 'TR_HOSTING_PLANS' => tr('Hosting plans'),
				 'TR_NUMBER' => tr('No.'),
				 'TR_EDIT' => $editTranslation,
				 'TR_PLAN_NAME' => tr('Name'),
				 'TR_ACTIONS' => tr('Actions')));

		$coid = $cfg->exists('CUSTOM_ORDERPANEL_ID') ? $cfg->CUSTOM_ORDERPANEL_ID : '';
		$i = 1;

		while (!$stmt->EOF) {
			$tpl->assign(
				array(
					 'PLAN_NUMBER' => $i++,
					 'PLAN_NAME' => tohtml($stmt->fields['name']),
					 'PLAN_NAME2' => addslashes(clean_html($stmt->fields['name'], true)),
					 'PLAN_ACTION' => $deleteTranslation,
					 'PLAN_SHOW' => $showHostingPlanTranslation,
					 'PURCHASING' => ($stmt->fields['status']) ? tr('Enabled') : tr('Disabled'),
					 'CUSTOM_ORDERPANEL_ID' => $coid,
					 'HP_ID' => $stmt->fields['id'],
					 'ADMIN_ID' => $_SESSION['user_id']));

			$tpl->parse('HP_ENTRY', '.hp_entry');
			$stmt->moveNext();
		}

		$tpl->parse('HP_TABLE', 'hp_table');
	}
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if ($cfg->HOSTING_PLANS_LEVEL != 'admin') {
	redirectTo('index.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		 'layout' => 'shared/layouts/ui.tpl',
		 'page' => 'admin/hosting_plan.tpl',
		 'page_message' => 'layout',
		 'hosting_plans' => 'page',
		 'hp_table' => 'page',
		 'hp_entry' => 'hp_table',
		 'hp_delete' => 'page',
		 'hp_menu_add' => 'page'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Administrator / Hosting Plans Management'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_HOSTING_PLANS' => tr('Hosting plans'),
		 'TR_PURCHASING' => tr('Purchasing'),
		 'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s hosting plan?', true, '%s')));

generateNavigation($tpl);
admin_generateHostingPlansList($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
