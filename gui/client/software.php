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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category i-MSCP
 * @copyright 2006-2010 by ispCP | http://isp-control.net
 * @copyright 2006-2010 by ispCP | http://i-mscp.net
 * @author ispCP Team
 * @author i-MSCP Team
 * @version SVN: $Id: Database.php 3702 2010-11-16 14:20:55Z thecry $
 * @link http://i-mscp.net i-MSCP Home Site
 * @license http://www.mozilla.org/MPL/ MPL 1.1
 */

require '../include/imscp-lib.php';

check_login(__FILE__);

$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/software.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('software_message', 'page');
$tpl -> define_dynamic('software_item', 'page');
$tpl -> define_dynamic('software_action_delete', 'page');
$tpl -> define_dynamic('software_action_install', 'page');
$tpl -> define_dynamic('software_total', 'page');
$tpl -> define_dynamic('no_software', 'page');
$tpl -> define_dynamic('no_software_support', 'page');
$tpl -> define_dynamic('del_software_support', 'page');
$tpl -> define_dynamic('del_software_item', 'page');
$tpl -> define_dynamic('t_software_support', 'page');

//
// page functions.
//


function gen_user_software_action($software_id, $dmn_id, &$sql, &$tpl) {
	$find_software = "
		SELECT
			`software_status`
		FROM
			`web_software_inst`
		WHERE
			`software_id` = ?
		AND
			`domain_id` = ?
	";
	$sw = exec_query($sql, $find_software, array($software_id, $dmn_id));
	if ($sw -> recordCount() == 0) {
		$software_status = 'not installed';
		$software_icon = 'edit';
	} else {
		if ($sw->fields['software_status'] == 'ok') {
			$software_status = 'installed';
			$software_icon = 'delete';
		} elseif($sw->fields['software_status'] == 'toadd') {
			$software_status = 'installing';
			$software_icon = 'disabled';
		} elseif($sw->fields['software_status'] == 'delete') {
			$software_status = 'deleting';
			$software_icon = 'delete';
		} else {
			$software_status = 'n/a';
			$software_icon = 'disabled';
		}
	}
	if ($software_status == 'installing') {
		$tpl->assign(
				array(
					'TR_MESSAGE_DELETE' 	=> '',
					'TR_MESSAGE_INSTALL' 	=> ''
				)
			);
		$tpl->parse('SOFTWARE_ACTION_DELETE', '');
		return array(tr(''), "", "", $software_status, $software_icon);
    } elseif ($software_status == 'deleting') {
		$tpl->assign(
				array(
					'TR_MESSAGE_DELETE' 	=> '',
					'TR_MESSAGE_INSTALL' 	=> ''
				)
			);
		$tpl->parse('SOFTWARE_ACTION_DELETE', '');
		return array(tr(''), "", "", $software_status, $software_icon);
    } elseif ($software_status == 'installed') {
		$tpl->assign(
				array(
					'TR_MESSAGE_DELETE' 		=> tr('Are you sure you want to delete this package?', true),
					'SOFTWARE_ACTION_INSTALL' 	=> ''
				)
			);
        return array(tr('Uninstall'), 'software_delete.php?id='.$software_id, 'software_view.php?id='.$software_id, $software_status, $software_icon);
    }else{
		$tpl->assign(
				array(
					'TR_MESSAGE_INSTALL' 		=> tr('Are you sure to install this package?', true),
					'SOFTWARE_ACTION_DELETE' 	=> ''
				)
			);
        return array(tr('Install'), 'software_install.php?id='.$software_id, 'software_view.php?id='.$software_id, $software_status, $software_icon);
    }
}

function gen_software_list(&$tpl, &$sql, $dmn_id, $dmn_name, $reseller_id, $admin_id) {
	global $counter, $delcounter;
	$query = "
		SELECT
			`domain_software_allowed`,
			`domain_ftpacc_limit`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";
	$rs = exec_query($sql, $query, $admin_id);
	if ($rs->fields('domain_software_allowed') == 'yes' && $rs->fields('domain_ftpacc_limit') != "-1") {
		$find_deleted_software = "
			SELECT
				`software_id`,
				`software_status`,
				`software_res_del`,
				`software_name`,
				`software_version`
			FROM
				`web_software_inst`
			WHERE
				`domain_id` = ?
			AND
				software_res_del = '1'
		";
		$deleted_sw = exec_query($sql, $find_deleted_software, $dmn_id);
		if ($deleted_sw->recordCount() == 0) {
			$tpl->assign('SOFTWARE_DEL_ITEM', '');
			$tpl->assign('DEL_SOFTWARE_SUPPORT', '');
		}else{
			while (!$deleted_sw ->EOF) {
				if ($delcounter % 2 == 0) {
					$tpl -> assign('DEL_ITEM_CLASS', 'content');
          		} else {
		            $tpl -> assign('DEL_ITEM_CLASS', 'content2');
           		}
				if($deleted_sw->fields['software_status'] == 'ok') {
					$delsoftware_status = 'installed';
					$del_software_action_script="software_delete.php?id=".$deleted_sw->fields['software_id'];
					$tpl->assign(
								array(
									'DEL_SOFTWARE_ACTION' 	=> tr('Uninstall'),
									'TR_RES_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?', true)
								)
							);
				} elseif($deleted_sw->fields['software_status'] == 'toadd') {
					$delsoftware_status = 'installing';
					$del_software_action_script="software_delete.php?id=".$deleted_sw->fields['software_id'];
					$tpl->assign('DEL_SOFTWARE_ACTION', tr('Uninstall'));
					$tpl->assign('TR_RES_MESSAGE_DELETE',tr('Are you sure you want to delete this package?', true));
				} elseif($deleted_sw->fields['software_status'] == 'delete') {
					$delsoftware_status = 'deleting';
					$del_software_action_script="";
					$tpl->assign(
							array(
								'DEL_SOFTWARE_ACTION' 	=> '',
								'TR_RES_MESSAGE_DELETE' => ''
							)
						);
				} else {
					$delsoftware_status = 'n/a';
				}
				$software_name = $deleted_sw->fields['software_name'];
				$software_version = $deleted_sw->fields['software_version'];
				$tpl -> assign(
                            array(
								'SOFTWARE_DEL_RES_MESSAGE' 		=> tr('This Package (%s, V%s) was deleted by your reseller. You can only uninstall this package!<br />Please delete the files and database for this package manually!', $software_name, $software_version),
								'DEL_SOFTWARE_STATUS' 			=> $delsoftware_status,
								'DEL_SOFTWARE_ACTION_SCRIPT' 	=> $del_software_action_script
							)
						);
				$tpl -> parse('DEL_SOFTWARE_ITEM', '.del_software_item');
				$deleted_sw->moveNext();
				$delcounter ++;
			}
			$tpl->assign(
					array(
						'TR_DEL_SOFTWARE' 	=> tr('Installed Package which was deleted by your reseller!'),
						'TR_DEL_STATUS' 	=> tr('Status'),
						'TR_DEL_ACTION' 	=> tr('Action')
					)
				);
			$tpl -> parse('DEL_SOFTWARE_SUPPORT', '.del_software_support');
		}
		
		if (isset($_GET['sortby']) && isset($_GET['order'])) {
			if ($_GET['order'] === "asc" || $_GET['order'] === "desc") {
				if ($_GET['sortby'] === "name") {
					$ordertype = "`software_name` ".$_GET['order'];
				} elseif ($_GET['sortby'] === "database") {
					$ordertype = "`software_db` ".$_GET['order'];
				} elseif ($_GET['sortby'] === "type") {
					$ordertype = "`software_type` ".$_GET['order'];
				} elseif ($_GET['sortby'] === "language") {
					$ordertype = "`software_language` ".$_GET['order'];
				} else {
					$ordertype = "`software_active` ASC, `software_type` ASC";
				}
			} else {
				$ordertype = "`software_name` ASC, `software_type` ASC";
			}
		} else {
			$ordertype = "`software_name` ASC, `software_type` ASC";
		}
		
		$list_query = "
			SELECT
				`software_id`,
				`software_name`,
				`software_version`,
				`software_language`,
				`software_type`,
				`software_db`,
				`software_desc`
			FROM
				`web_software`
			WHERE
				`reseller_id` = ?
			AND
				`software_active` = '1'
			ORDER BY
				$ordertype
		";
				
		$rs = exec_query($sql, $list_query, $reseller_id);
		if ($rs -> recordCount() == 0) {
			$tpl->assign('SOFTWARE_ITEM', '');
			$tpl->assign(
					array(
						'NO_SOFTWARE_AVAIL' 	=> tr('No software available'),
						'ASC_DESC_BUTTON' 		=> ''
					)
				);
			$tpl->parse('NO_SOFTWARE_SUPPORT', '.no_software_support');
			return 0;
		} else {
			$tpl->assign('NO_SOFTWARE_SUPPORT', '');
			while(!$rs -> EOF) {
				if($counter % 2 == 0) {
					$tpl -> assign('ITEM_CLASS', 'content');
				} else {
					$tpl -> assign('ITEM_CLASS', 'content2');
				}
				list(
					$software_action, $software_action_script, $view_software_script,
					$software_status, $software_icon) = gen_user_software_action($rs -> fields['software_id'], $dmn_id, $sql,
					$tpl);
				$tpl -> assign(
							array(
								'SOFTWARE_NAME' 			=> $rs -> fields['software_name'],
								'SOFTWARE_DESCRIPTION'		=> $rs -> fields['software_desc'],
								'SOFTWARE_VERSION' 			=> $rs -> fields['software_version'],
								'SOFTWARE_LANGUAGE' 		=> $rs -> fields['software_language'],
								'SOFTWARE_TYPE' 			=> $rs -> fields['software_type'],
								'SOFTWARE_STATUS' 			=> $software_status,
								'SOFTWARE_ACTION' 			=> $software_action,
								'SOFTWARE_ACTION_SCRIPT' 	=> $software_action_script,
								'VIEW_SOFTWARE_SCRIPT' 		=> $view_software_script,
								'SOFTWARE_ICON' 			=> $software_icon
							)
						);
				if($rs -> fields['software_db'] == '1'){
					$tpl -> assign('SOFTWARE_NEED_DATABASE', tr('required'));
				} else {
					$tpl -> assign('SOFTWARE_NEED_DATABASE', tr('not required'));
				}
				if($software_status == "installed") {
					$tpl->parse('SOFTWARE_ACTION_DELETE', 'software_action_delete');
				} elseif($software_status == "not installed") {
					$tpl->parse('SOFTWARE_ACTION_INSTALL', 'software_action_install');
				}
				$tpl -> parse('SOFTWARE_ITEM', '.software_item');
				$rs->moveNext();
				$counter ++;
			}
			return $rs -> recordCount();		
		}
	} else {
		$tpl->assign(
				array(
					'NO_SOFTWARE_AVAIL' 	=> tr('You do not have permissions to install software'),
					'DEL_SOFTWARE_SUPPORT' 	=> ''
				)
			);
		return 0;
	}
}

function gen_page_lists(&$tpl, &$sql, $user_id) {
    list($dmn_id,
         $dmn_name,
         $dmn_gid,
         $dmn_uid,
         $dmn_created_id,
         $dmn_created,
         $dmn_last_modified,
         $dmn_mailacc_limit,
         $dmn_ftpacc_limit,
         $dmn_traff_limit,
         $dmn_sqld_limit,
         $dmn_sqlu_limit,
         $dmn_status,
         $dmn_als_limit,
         $dmn_subd_limit,
         $dmn_ip_id,
         $dmn_disk_limit,
         $dmn_disk_usage,
         $dmn_php,
         $dmn_cgi) = get_domain_default_props($sql, $user_id);
    $software_poss = gen_software_list($tpl, $sql, $dmn_id, $dmn_name, $dmn_created_id, $_SESSION['user_id']);
    $tpl -> assign('TOTAL_SOFTWARE_AVAILABLE', $software_poss);

	$tpl->parse('SOFTWARE_MESSAGE', 'software_message');
}

//
// common page data.
//

$tpl -> assign(
			array(
				'TR_CLIENT_MANAGE_USERS_PAGE_TITLE' 	=> tr('ispCP - Client/Manage Users'),
				'THEME_COLOR_PATH' 						=> "../themes/{$cfg->USER_INITIAL_THEME}",
				'THEME_CHARSET' 						=> tr('encoding'),
				'ISP_LOGO' 								=> get_logo($_SESSION['user_id'])
			)
		);

//
// dynamic page data.
//

if(isset($_SESSION['software_support']) && $_SESSION['software_support'] == "no") {
	$tpl -> assign('NO_SOFTWARE', '');
}

gen_page_lists($tpl, $sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_webtools.tpl');

gen_logged_from($tpl);

get_client_software_permission (&$tpl,&$sql,$_SESSION['user_id']);

check_permissions($tpl);

$tpl -> assign(
			array(
				'TR_SOFTWARE_MENU_PATH'			=> tr('i-MSCP - application installer'),
				'TR_MANAGE_USERS' 				=> tr('Manage users'),
				'TR_INSTALL_SOFTWARE' 			=> tr('Install software'),
				'TR_SOFTWARE' 					=> tr('Software'),
				'TR_VERSION' 					=> tr('Version'),
				'TR_LANGUAGE' 					=> tr('Language'),
				'TR_TYPE' 						=> tr('Type'),
				'TR_NEED_DATABASE' 				=> tr('Database'),
				'TR_STATUS' 					=> tr('Status'),
				'TR_ACTION' 					=> tr('Action'),
				'TR_SOFTWARE_AVAILABLE' 		=> tr('Apps available'),
				'TR_DELETE' 					=> tr('Delete'),
				'TR_SOFTWARE_MENU' 				=> tr('Software installation'),
				'TR_CLIENT_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Application Management'),
				'TR_SOFTWARE_ASC'				=> 'software.php?sortby=name&order=asc',
				'TR_SOFTWARE_DESC' 				=> 'software.php?sortby=name&order=desc',
				'TR_TYPE_ASC'					=> 'software.php?sortby=type&order=asc',
				'TR_TYPE_DESC' 					=> 'software.php?sortby=type&order=desc',
				'TR_NEED_DATABASE_ASC' 			=> 'software.php?sortby=database&order=asc',
				'TR_NEED_DATABASE_DESC'			=> 'software.php?sortby=database&order=desc',
				'TR_STATUS_ASC' 				=> 'software.php?sortby=status&order=asc',
				'TR_STATUS_DESC'				=> 'software.php?sortby=status&order=desc',
				'TR_LANGUAGE_ASC' 				=> 'software.php?sortby=language&order=asc',
				'TR_LANGUAGE_DESC' 				=> 'software.php?sortby=language&order=desc'
			)
		);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}
unset_messages();
?>
