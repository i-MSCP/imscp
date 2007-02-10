<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2004 be moleSoftware		            		|
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
define('IN_PHPSYSINFO', true);
require_once('../include/phpsysinfo/class.error.inc.php');
require_once('../include/phpsysinfo/common_functions.php');
require_once('../include/phpsysinfo/class.' . PHP_OS . '.inc.php' );
$sysinfo = new sysinfo;
$error = new error;

function compat_array_keys ($arr)
{
        $result = array();

        while (list($key, $val) = each($arr)) {
            $result[] = $key;
        }
        return $result;
}

function compat_in_array ($value, $arr)
{
    while (list($key,$val) = each($arr)) {
        if ($value == $val) {
            return true;
        }
    }
    return false;
}


include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/sysinfo.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('disk_list', 'page');

$tpl -> define_dynamic('disk_list_item', 'disk_list');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_SYSTEM_INFO_PAGE_TITLE' => tr('VHCS - Virtual Hosting Control System'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );

              
function gen_mount_point(&$tpl)
{
    global $sysinfo;
    $mount_points = $sysinfo->filesystems();
    
    while (list($number, $row) = each($mount_points)) {
        
     if (($number+1) % 2 == 0) {
        $tpl -> assign(
                array(
                    'ITEM_CLASS' => 'content', 
                    )
                );
    }
    else{
        $tpl -> assign(
                array(
                    'ITEM_CLASS' => 'content2', 
                    )
                );      
    }
       $tpl -> assign(
                array(
                        'MOUNT' => $row['mount'],
                        'TYPE' => $row['fstype'],
                        'PARTITION' => $row['disk'],
                        'PERCENT' => $row['percent'],
                        'FREE' => make_hr($row['free']*1014),
                        'USED' => make_hr($row['used']*1024),
                        'SIZE' => make_hr($row['size']*1024),
                     )
              );
              
          $tpl -> parse('DISK_LIST_ITEM', '.disk_list_item');
     }

    $tpl -> parse('DISK_LIST', 'disk_list');
}

/*
 *
 * static page messages.
 *
 */
gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_system_tools.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_system_tools.tpl');

gen_mount_point($tpl);

$kernel = $sysinfo->kernel();

//maybe add translation here
$text['days'] = tr('days');
$text['hours'] = tr('hours');
$text['minutes'] = tr('minutes');
$text['gb'] = "GB";
$text['mb'] = "MB";
$text['kb'] = "KB";

$uptime = uptime($sysinfo->uptime());
$load = $sysinfo->loadavg();
$cpu = $sysinfo->cpu_info();
$mem = $sysinfo->memory();

if ( !isset($cpu['cpuspeed']) ) {
	$cpu['cpuspeed'] = "n.a.";
}
if ( !isset($cpu['bogomips']) ) {
	$cpu['bogomips'] = "n.a.";
}
if ( !isset($cpu['cache']) ) {
	$cpu['cache'] = "n.a.";
}

$tpl -> assign(
        array(
                'TR_SYSTEM_INFO_TITLE' => tr('System info'),
                'TR_SYSTEM_INFO' => tr('Vital system info'),
                'TR_CPU_SYSTEM_INFO' => tr('CPU system Info'),
                'TR_CPU_MODEL' => tr('CPU model'),
                'TR_CPU_MHZ' => tr('CPU MHz'),
                'TR_CPU_CACHE' => tr('CPU cache'),
                'TR_CPU_BOGOMIPS' => tr('CPU bogomips'),
                'CPU_MODEL' => $cpu['model'],
                'CPU_MHZ' => $cpu['cpuspeed'],
                'CPU_CACHE' => $cpu['cache'],
                'CPU_BOGOMIPS' => $cpu['bogomips'],
                'TR_MEMRY_SYSTEM_INFO' => tr('Memory system info'),
                'TR_RAM' => tr('RAM'),
                'TR_TOTAL' => tr('Total'),
                'TR_USED' => tr('Used'),
                'TR_FREE' => tr('Free'),
                'TR_SWAP' => tr('Swap'),
                'TR_UPTIME' => tr('Up time'),
                'UPTIME' => $uptime,
                'TR_KERNEL' => tr('Kernel'),
                'KERNEL' => $kernel,
                'TR_LOAD' => tr('Load'),
                'LOAD' => $load['avg'][0] . ' ' . $load['avg'][1] . '  '. $load['avg'][2] ,
                'RAM' => tr('RAM'),
                'RAM_TOTAL' => format_bytesize($mem['ram']['total']),
                'RAM_USED' => format_bytesize($mem['ram']['t_used']),
                'RAM_FREE' => format_bytesize($mem['ram']['t_free']),
                'SWAP_TOTAL' => format_bytesize($mem['swap']['total']),
                'SWAP_USED' => format_bytesize($mem['swap']['used']),
                'SWAP_FREE' => format_bytesize($mem['swap']['free']),
                'TR_FILE_SYSTEM_INFO' => tr('Filesystem system Info'),
                'TR_MOUNT' => tr('Mount'),
                'TR_TYPE' => tr('Type'),
                'TR_PARTITION' => tr('Partition'),
                'TR_PERCENT' => tr('Percent'),
                'TR_SIZE' => tr('Size')
                )
        );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();

?>
