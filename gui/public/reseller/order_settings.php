<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
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

/**********************************************************************************
 * Script functions
 */

/**
 *
 */
function save_haf()
{
	$user_id = $_SESSION['user_id'];
	$header = $_POST['header'];
	$footer = $_POST['footer'];

	$query = "SELECT `id` FROM `orders_settings` WHERE `user_id` = ?";
	$stmt = exec_query($query, $user_id);

	if ($stmt->rowCount()) {
		// update query
		$query = "UPDATE `orders_settings` SET `header` = ?, `footer` = ? WHERE `user_id` = ?";
		exec_query($query, array($header, $footer, $user_id));
	} else {
		// create query
		$query = "
			INSERT INTO
				`orders_settings`(`user_id`, `header`, `footer`)
			VALUES
				(?, ?, ?)
		";

		exec_query($query, array($user_id, $header, $footer));
	}
}

/**********************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/order_settings.tpl',
		'page_message' => 'page',
		'purchase_header' => 'page',
		'purchase_footer' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Reseller/Order settings'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

if (isset($_POST['header']) && $_POST['header'] !== '' && isset ($_POST['footer']) && $_POST['footer'] !== '') {
	save_haf();
}

$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';

$url = $cfg->BASE_SERVER_VHOST_PREFIX . $cfg->BASE_SERVER_VHOST . '/orderpanel/index.php?';
$url .= "coid=$coid&amp;user_id={$_SESSION['user_id']}";

$tpl->assign(
	array(
		'TR_ORDER_TEMPLATE' => tr('Order template'),
		'TR_IMPLEMENT_INFO' => tr('Implementation URL'),
		'TR_HEADER' => tr('Header'),
		'TR_IMPLEMENT_URL' => $url,
		'TR_FOOTER' => tr('Footer'),
		'TR_PREVIEW' => tr('Preview'),
		'TR_UPDATE' => tr('Update')));

gen_purchase_haf($tpl, $_SESSION['user_id'], true);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
