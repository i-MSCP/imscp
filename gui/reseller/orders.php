<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

// Begin page line
require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/orders.tpl');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('page_message', 'page');

// Table with orders
$tpl -> define_dynamic('orders_table', 'page');
$tpl -> define_dynamic('order', 'orders_table');

// scrolling
$tpl -> define_dynamic('scroll_prev_gray', 'page');
$tpl -> define_dynamic('scroll_prev', 'page');
$tpl -> define_dynamic('scroll_next_gray', 'page');
$tpl -> define_dynamic('scroll_next', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_RESELLER_MAIN_INDEX_PAGE_TITLE' => tr('ISPCP - Reseller/Order management'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

// Functions
//*
//*
function gen_order_page (&$tpl, &$sql, $user_id)
{

	$start_index = 0;
	$current_psi = 0;

	if (isset($_GET['psi']) && is_numeric($_GET['psi'])) {
		$start_index = $_GET['psi'];
		$current_psi = $_GET['psi'];
	}

	global $cfg;
	$rows_per_page = $cfg['DOMAIN_ROWS_PER_PAGE'];

// count query
		$count_query = <<<SQL_QUERY
                select
                    count(id) as cnt
                from
                    orders
                where
                    user_id = ?
				and
					status != ?
SQL_QUERY;

	// lets count
	$rs = exec_query($sql, $count_query, array($user_id,'added'));
	$records_count = $rs -> fields['cnt'];



	$query = <<<SQL_QUERY
        SELECT
            *
        FROM
            orders
        WHERE
            user_id = ?
		  AND
			status != ?
        ORDER BY
            date DESC
		LIMIT
			$start_index, $rows_per_page
SQL_QUERY;
 	$rs = exec_query($sql, $query, array($user_id,'added'));


	$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {

				$tpl -> assign('SCROLL_PREV', '');

		} else {

				$tpl -> assign(
								array(
										'SCROLL_PREV_GRAY' => '',
										'PREV_PSI' => $prev_si
									 )
							  );

		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {

				$tpl -> assign('SCROLL_NEXT', '');

		} else {

				$tpl -> assign(
								array(
										'SCROLL_NEXT_GRAY' => '',
										'NEXT_PSI' => $next_si
									 )
							  );

		}


	if ($rs -> RecordCount() == 0) {
		set_page_message(tr('You do not have new orders!'));
		$tpl -> assign('ORDERS_TABLE', '');
		$tpl -> assign('SCROLL_NEXT_GRAY', '');
		$tpl -> assign('SCROLL_PREV_GRAY', '');
	} else {
		$counter = 0;
		while (!$rs -> EOF) {

			$plan_id = $rs -> fields['plan_id'];
			$order_status = tr('New order');
			// lets get hosting plan name
	$planname_query = <<<SQL_QUERY
        select
            name
		from
	        hosting_plans
        where
            id = ?
SQL_QUERY;
 	$rs_planname = exec_query($sql, $planname_query, array($plan_id));
	$plan_name = $rs_planname -> fields['name'];


			if ($counter % 2 == 0) {

               	$tpl -> assign('ITEM_CLASS', 'content');

          	} else {

		        $tpl -> assign('ITEM_CLASS', 'content2');
           	}
			$status = $rs -> fields['status'];
			if ($status === 'update') {
				$customer_id = $rs -> fields['customer_id'];
				$cusrtomer_query = <<<SQL_QUERY
			        select
            			*
					from
	        			admin
			        where
            			admin_id = ?
SQL_QUERY;
			 	$rs_customer = exec_query($sql, $cusrtomer_query, array($customer_id));

				$user_details = $rs_customer -> fields['fname']."&nbsp;".$rs_customer -> fields['lname']."<br><a href=\"mailto:".$rs_customer -> fields['email']."\" class=\"link\">".$rs_customer -> fields['email']."</a><br>".$rs_customer -> fields['zip']."&nbsp;".$rs_customer -> fields['city']."&nbsp;".$rs_customer -> fields['country'];
				$order_status = tr('Update order');
				$tpl -> assign('LINK', 'orders_update.php?order_id='.$rs -> fields['id']);
			} else {
				$user_details = $rs -> fields['fname']."&nbsp;".$rs -> fields['lname']."<br><a href=\"mailto:".$rs -> fields['email']."\" class=\"link\">".$rs -> fields['email']."</a><br>".$rs -> fields['zip']."&nbsp;".$rs -> fields['city']."&nbsp;".$rs -> fields['country'];
				$tpl -> assign('LINK', 'orders_detailst.php?order_id='.$rs -> fields['id']);
			}
			$tpl -> assign(
                            array(
                                    'ID' => $rs -> fields['id'],
									'HP' => $plan_name,
                                    'DOMAIN' => $rs -> fields['domain_name'],
                                    'USER' => $user_details,
									'STATUS' => $order_status,
                                 )
                          );

			$tpl -> parse('ORDER', '.order');

    	    $rs -> MoveNext(); $counter ++;

        }

	}

}


//
// end of functions
//


/*
 *
 * static page messages.
 *
 */

gen_order_page($tpl, $sql, $_SESSION['user_id']);

gen_reseller_mainmenu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/main_menu_orders.tpl');
gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_orders.tpl');

gen_logged_from($tpl);


$tpl -> assign(array('TR_MANAGE_ORDERS' => tr('Manage Orders'),
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
					 'TR_ADD' => tr('Add/Details')));
gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

?>
