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
 * @subpackage	Reseller
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/************************************************************************************
 * Script functions
 */

/**
 * Generates order page.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $user_id User unique identifier
 * @return void
 */
function generateOrderPage($tpl, $user_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$start_index = 0;

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_GET['psi'];
	}

	$rows_per_page = $cfg->DOMAIN_ROWS_PER_PAGE;
	// count query
	$count_query = "
		SELECT
			COUNT(`id`) AS `cnt`
		FROM
			`orders`
		WHERE
			`user_id` = ?
		AND
			`status` != ?
		AND
		    `status` != ?
	";
	$rs = exec_query($count_query, array($user_id, $cfg->ITEM_ORDER_UNCONFIRMED_STATUS,
		$cfg->ITEM_ORDER_TREATED_STATUS));

	$records_count = $rs->fields['cnt'];

	$query = "
		SELECT
			*
		FROM
			`orders`
		WHERE
			`user_id` = ?
		AND
			`status` != ?
		AND
		    `status` != ?
		ORDER BY
			`date` DESC
		LIMIT
			$start_index, $rows_per_page
	";
	$rs = exec_query($query, array($user_id, $cfg->ITEM_ORDER_UNCONFIRMED_STATUS,
		$cfg->ITEM_ORDER_TREATED_STATUS));

	$prev_si = $start_index - $rows_per_page;

	if ($start_index == 0) {
		$tpl->assign('SCROLL_PREV', '');
	} else {
		$tpl->assign(array(
			'SCROLL_PREV_GRAY' => '',
			'PREV_PSI' => $prev_si));
	}

	$next_si = $start_index + $rows_per_page;

	if ($next_si + 1 > $records_count) {
		$tpl->assign('SCROLL_NEXT', '');
	} else {
		$tpl->assign(array(
			'SCROLL_NEXT_GRAY' => '',
			'NEXT_PSI' => $next_si));
	}

	if ($rs->recordCount() == 0) {
		set_page_message(tr('You do not have new orders.'), 'info');
		$tpl->assign('ORDERS_TABLE', '');
		$tpl->assign('SCROLL_NEXT_GRAY', '');
		$tpl->assign('SCROLL_PREV_GRAY', '');
	} else {
		while (!$rs->EOF) {
			$plan_id = $rs->fields['plan_id'];
			$order_status = tr('New order');

			$planname_query = "SELECT `name` FROM `hosting_plans` WHERE `id` = ?";
			$rs_planname = exec_query($planname_query, $plan_id);

			$plan_name = $rs_planname->fields['name'];
			$status = $rs->fields['status'];

			if ($status === 'update') {
				$customer_id = $rs->fields['customer_id'];
				$cusrtomer_query = "SELECT * FROM `admin` WHERE `admin_id` = ?";
				$rs_customer = exec_query($cusrtomer_query, $customer_id);

				$user_details = tohtml($rs_customer->fields['fname']) . '&nbsp;'
					. tohtml($rs_customer->fields['lname'])
					. "<br /><a href=\"mailto:" . tohtml($rs_customer->fields['email'])
					. "\" class=\"link\">" . tohtml($rs_customer->fields['email'])
					. "</a><br />" . tohtml($rs_customer->fields['zip'])
					. '&nbsp;' . tohtml($rs_customer->fields['city'])
					. '&nbsp;' . tohtml($rs_customer->fields['state'])
					. '&nbsp;' . tohtml($rs_customer->fields['country']);
				$order_status = tr('Update order');
				$tpl->assign('LINK', 'orders_update.php?order_id=' . $rs->fields['id']);
			} else {
				$user_details = $rs->fields['fname'] . '&nbsp;'
					. tohtml($rs->fields['lname'])
					. "<br /><a href=\"mailto:" . tohtml($rs->fields['email'])
					. "\" class=\"link\">" . tohtml($rs->fields['email'])
					. "</a><br />" . tohtml($rs->fields['zip'])
					. '&nbsp;' . tohtml($rs->fields['city'])
					. '&nbsp;' . tohtml($rs->fields['state'])
					. '&nbsp;' . tohtml($rs->fields['country']);
				$tpl->assign('LINK', 'orders_detailst.php?order_id=' . $rs->fields['id']);
			}

			$tpl->assign(array(
				'ID' => $rs->fields['id'],
				'HP' => tohtml($plan_name),
				'DOMAIN' => tohtml($rs->fields['domain_name']),
				'USER' => $user_details,
				'STATUS' => $order_status));

			$tpl->parse('ORDER', '.order');
			$rs->moveNext();
		}
	}
}

/**
 * Remove all unconfirmed orders that are expired.
 *
 * @return void
 */
function OrdersGarbageCollector()
{
	$cfg = iMSCP_Registry::get('config');
	$expireTime = time() - intval($cfg->ORDERS_EXPIRE_TIME);

	$query = "DELETE FROM `orders` WHERE `date` <= ? AND `status` = ?";
	exec_query($query, array($expireTime, $cfg->ITEM_ORDER_UNCONFIRMED_STATUS));
}

/************************************************************************************
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
		'page' => 'reseller/orders.tpl',
		'page_message' => 'layout',
		'orders_table' => 'page',
		'order' => 'orders_table',
		'scroll_prev_gray' => 'page',
		'scroll_prev' => 'page',
		'scroll_next_gray' => 'page',
		'scroll_next' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Reseller / Order management'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MANAGE_ORDERS' => tr('Manage Orders'),
		'TR_ID' => tr('ID'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_USER' => tr('Customer data'),
		'TR_ACTION' => tr('Action'),
		'TR_STATUS' => tr('Order'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_DETAILS' => tr('Details'),
		'TR_HP' => tr('Hosting plan'),
		'TR_MESSAGE_DELETE_ACCOUNT' => tr('Are you sure you want to delete this order?'),
		'TR_ADD' => tr('Add/Details'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')));

generateOrderPage($tpl, $_SESSION['user_id']);
OrdersGarbageCollector();
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
