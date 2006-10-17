<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



// Begin page line
include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['RESELLER_TEMPLATE_PATH'].'/order_settings.tpl');
$tpl -> define_dynamic('logged_from', 'page');

// Table with orders
$tpl -> define_dynamic('purchase_header', 'page');

$tpl -> define_dynamic('purchase_footer', 'page');
$tpl -> define_dynamic('page_message', 'page');

$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(array('TR_RESELLER_MAIN_INDEX_PAGE_TITLE' => tr('VHCS - Reseller/Order settings'),
                     'THEME_COLOR_PATH' => "../themes/$theme_color",
                     'THEME_CHARSET' => tr('encoding'),
                     'VHCS_LICENSE' => $cfg['VHCS_LICENSE'],
                     'ISP_LOGO' => get_logo($_SESSION['user_id'])));

// Functions
//*
//*
function save_haf(&$tpl, &$sql)
{

	global $cfg;

	$user_id = $_SESSION['user_id'];
	$header = $_POST['header'];
	$footer = $_POST['footer'];
	
	$query = <<<SQL_QUERY
        select
            id
        from
            orders_settings
        where
            user_id = ?
SQL_QUERY;
 	$rs = exec_query($sql, $query, array($user_id));
	
	if ($rs -> RecordCount() !== 0) {
		 
		 //update query
		 
		   $query = <<<SQL_QUERY
        update
            orders_settings
        set
            header = ?, 
			footer = ?
        where
            user_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($header, $footer, $user_id));
	
	} else {
	//create query
	    $query = <<<SQL_QUERY
              insert into 
			  		orders_settings(user_id, header, footer)
              values
                 (?, ?, ?)
SQL_QUERY;

    $rs = exec_query($sql, $query, array($user_id, $header, $footer));
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
if (isset($_POST['header']) && $_POST['header'] !== '' && isset ($_POST['footer']) && $_POST['footer'] !== '')
	save_haf($tpl, $sql);
	
gen_purchase_haf($tpl, $sql, $_SESSION['user_id']);

gen_reseller_menu($tpl, $cfg['RESELLER_TEMPLATE_PATH'].'/menu_orders.tpl');

gen_logged_from($tpl);


$tpl -> assign(array('TR_MANAGE_ORDERS' => tr('Manage Orders'),
                     'TR_APPLY_CHANGES' => tr('Apply changes'),
					 'TR_HEADER' => tr('Header'),
					 'TR_PREVIEW' => tr('Preview'),
					 'TR_IMPLEMENT_INFO' => tr('Implementation URL'),
					 'TR_IMPLEMENT_URL' => 'http://www.Your-VHCS-Domain/vhcs2/orderpanel/index.php?user_id='.$_SESSION['user_id'],
                     'TR_FOOTER' => tr('Footer')));
gen_page_message($tpl);
$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
