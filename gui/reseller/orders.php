<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
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

// Begin page line
require '../include/imscp-lib.php';

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/orders.tpl');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('page_message', 'page');
// Table with orders
$tpl->define_dynamic('orders_table', 'page');
$tpl->define_dynamic('order', 'orders_table');
// scrolling
$tpl->define_dynamic('scroll_prev_gray', 'page');
$tpl->define_dynamic('scroll_prev', 'page');
$tpl->define_dynamic('scroll_next_gray', 'page');
$tpl->define_dynamic('scroll_next', 'page');

$tpl->assign(
	array(
		'TR_RESELLER_MAIN_INDEX_PAGE_TITLE'	=> tr('i-MSCP - Reseller/Order management'),
		'THEME_COLOR_PATH'					=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'						=> tr('encoding'),
		'ISP_LOGO'							=> get_logo($_SESSION['user_id'])
	)
);

/*
 * Functions
 */

function gen_order_page($tpl, $user_id) {
	$cfg = iMSCP_Registry::get('config');

	$start_index = 0;

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_GET['psi'];
	}

	$rows_per_page = $cfg->DOMAIN_ROWS_PER_PAGE;
	// count query
	$count_query = "
		SELECT
			COUNT(`id`) AS cnt
		FROM
			`orders`
		WHERE
			`user_id` = ?
		AND
			`status` != ?
	";
	// let's count
	$rs = exec_query($count_query, array($user_id, 'added'));
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
		ORDER BY
			`date` DESC
		LIMIT
			$start_index, $rows_per_page
	";
	$rs = exec_query($query, array($user_id, 'added'));

	$prev_si = $start_index - $rows_per_page;

	if ($start_index == 0) {
		$tpl->assign('SCROLL_PREV', '');
	} else {
		$tpl->assign(
			array(
				'SCROLL_PREV_GRAY' => '',
				'PREV_PSI' => $prev_si
			)
		);
	}

	$next_si = $start_index + $rows_per_page;

	if ($next_si + 1 > $records_count) {
		$tpl->assign('SCROLL_NEXT', '');
	} else {
		$tpl->assign(
			array(
				'SCROLL_NEXT_GRAY' => '',
				'NEXT_PSI' => $next_si
			)
		);
	}

	if ($rs->recordCount() == 0) {
		set_page_message(tr('You do not have new orders!'));
		$tpl->assign('ORDERS_TABLE', '');
		$tpl->assign('SCROLL_NEXT_GRAY', '');
		$tpl->assign('SCROLL_PREV_GRAY', '');
	} else {
		$counter = 0;
		while (!$rs->EOF) {
			$plan_id = $rs->fields['plan_id'];
			$order_status = tr('New order');
			// let's get hosting plan name
			$planname_query = "
				SELECT
					`name`
				FROM
					`hosting_plans`
				WHERE
					`id` = ?
			";
			$rs_planname = exec_query($planname_query, $plan_id);
			$plan_name = $rs_planname->fields['name'];

			$tpl->assign('ITEM_CLASS', ($counter % 2 == 0) ? 'content' : 'content2');

			$status = $rs->fields['status'];
			if ($status === 'update') {
				$customer_id = $rs->fields['customer_id'];
				$cusrtomer_query = "
					SELECT
						*
					FROM
						`admin`
					WHERE
						`admin_id` = ?
				";
				$rs_customer = exec_query($cusrtomer_query, $customer_id);
				$user_details = tohtml($rs_customer->fields['fname']) . "&nbsp;"
					. tohtml($rs_customer->fields['lname'])
					. "<br /><a href=\"mailto:" . tohtml($rs_customer->fields['email'])
					. "\" class=\"link\">" . tohtml($rs_customer->fields['email'])
					. "</a><br />" . tohtml($rs_customer->fields['zip'])
					. "&nbsp;" . tohtml($rs_customer->fields['city'])
					. "&nbsp;" . tohtml($rs_customer->fields['state'])
					. "&nbsp;" . tohtml($rs_customer->fields['country']);
				$order_status = tr('Update order');
				$tpl->assign('LINK', 'orders_update.php?order_id=' . $rs->fields['id']);
			} else {
				$user_details = $rs->fields['fname'] . "&nbsp;"
					. tohtml($rs->fields['lname'])
					. "<br /><a href=\"mailto:" . tohtml($rs->fields['email'])
					. "\" class=\"link\">" . tohtml($rs->fields['email'])
					. "</a><br />" . tohtml($rs->fields['zip'])
					. "&nbsp;" . tohtml($rs->fields['city'])
					. "&nbsp;" . tohtml($rs->fields['state'])
					. "&nbsp;" . tohtml($rs->fields['country']);
				$tpl->assign('LINK', 'orders_detailst.php?order_id=' . $rs->fields['id']);
			}
			$tpl->assign(
				array(
					'ID'		=> $rs->fields['id'],
					'HP'		=> tohtml($plan_name),
					'DOMAIN'	=> tohtml($rs->fields['domain_name']),
					'USER'		=> $user_details,
					'STATUS'	=> $order_status,
				)
			);

			$tpl->parse('ORDER', '.order');
			$rs->moveNext();
			$counter++;
		}
	}
}

// end of functions

/*
 *
 * static page messages.
 *
 */

gen_order_page($tpl, $_SESSION['user_id']);

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_orders.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_orders.tpl');

gen_logged_from($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_ORDERS'			=> tr('Manage Orders'),
		'TR_ID'						=> tr('ID'),
		'TR_DOMAIN'					=> tr('Domain'),
		'TR_USER'					=> tr('Customer data'),
		'TR_ACTION'					=> tr('Action'),
		'TR_STATUS'					=> tr('Order'),
		'TR_EDIT'					=> tr('Edit'),
		'TR_DELETE'					=> tr('Delete'),
		'TR_DETAILS'				=> tr('Details'),
		'TR_HP'						=> tr('Hosting plan'),
		'TR_MESSAGE_DELETE_ACCOUNT'	=> tr('Are you sure you want to delete this order?', true),
		'TR_ADD'					=> tr('Add/Details')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

unsetMessages();
