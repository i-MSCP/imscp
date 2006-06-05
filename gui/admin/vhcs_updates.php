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



/* BEGIN common functions */
function get_update_infos(&$tpl)
{
 
	$info_url = "http://updates.vhcs.net/update.php";
	$last_update = "http://updates.vhcs.net/last_update.php";
	
       // Fake the browser type 
       ini_set('user_agent','MSIE 4\.0b2;'); 

       $dh = @fopen("$info_url",'r'); 
       $info_result = @fread($dh,8192);       
	   
	   $dh2 = @fopen("$last_update",'r'); 
       $last_update_result = @fread($dh2,8192);                                                                                                                      

	   $current_version = 20050818;
	   if ($current_version <  $last_update_result)
	   {	
	    
		   $tpl -> assign(
					array(
							'UPDATE_MESSAGE' =>  '',
							'UPDATE' =>  tr('New VHCS update is now available'),
							'INFOS' => $info_result,
						 )
				  );
		
			$tpl -> parse('UPDATE_INFOS', 'update_infos');
		} else {
			$tpl -> assign('UPDATE_INFOS', '');
		
		}
}

/* END system functions */

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/vhcs_updates.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('update_message', 'page');

$tpl -> define_dynamic('update_infos', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_VHCS_UPDATES_PAGE_TITLE' => tr('VHCS - Virtual Hosting Control System'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );

              


/*
 *
 * static page messages.
 *
 */

gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_system_tools.tpl');

$tpl -> assign(
        array(
                'TR_UPDATES_TITLE' => tr('VHCS updates'),
				'TR_AVAILABLE_UPDATES' => tr('Available VHCS updates'),
				'TR_MESSAGE' => tr('No new VHCS updates available'),
				'TR_UPDATE' => tr('Update'),
				'TR_INFOS' => tr('Update details'),

                )
        );

gen_page_message($tpl);

get_update_infos($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
