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

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/software_rights.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('list_reseller', 'page');
$tpl->define_dynamic('no_reseller_list', 'page');
$tpl->define_dynamic('no_select_reseller', 'page');
$tpl->define_dynamic('select_reseller', 'page');
$tpl->define_dynamic('reseller_item', 'page');

function get_reseller_rights ($tpl, $sql, $software_id) {
	$query = "
		SELECT 
			a.`software_id`,
			a.`software_master_id`,
			a.`reseller_id`,
			a.`rights_add_by`,
			b.`admin_name` as reseller
		FROM 
			`web_software` a,
			`admin` b
		WHERE
			a.`reseller_id` = b.`admin_id`
		AND 
			a.`software_depot` = 'yes'
		AND
			a.`software_master_id` = ?
	";
	$rs = exec_query($sql, $query, $software_id);
	if ($rs->recordCount() > 0){
		while(!$rs->EOF) {
			$adminquery = "
				SELECT
					`admin_name` as administrator 
				FROM
					`admin`
				WHERE
					`admin_id` = ?
			";
			$rs_admin = exec_query($sql, $adminquery, $rs->fields['rights_add_by']);
			if ($rs_admin->fields['administrator'] == ""){
				$added_by = tr('Admin not available');
			}else{
				$added_by = $rs_admin->fields['administrator'];
			}
			$remove_rights_url = "software_change_rights.php?id=".$rs->fields['software_master_id']."&reseller_id=".$rs->fields['reseller_id'];
			$tpl->assign(
					array(
						'RESELLER'			=> $rs->fields['reseller'],
						'ADMINISTRATOR' 	=> $added_by,
						'TR_REMOVE_RIGHT' 	=> tr('Remove'),
						'TR_MESSAGE_REMOVE'	=> tr('Are you sure to remove the permissions ?', true),
						'REMOVE_RIGHT_LINK'	=> $remove_rights_url
						)
					);
			$tpl->parse('LIST_RESELLER', '.list_reseller');
			$rs->moveNext();
		}
		$tpl->assign('NO_RESELLER_LIST', '');
	} else {
		$tpl->assign(
				array(
					'NO_RESELLER' => tr('No Reseller with permissions for this software found'),
					'LIST_RESELLER' => ''
				)
			);
		$tpl->parse('NO_RESELLER_LIST', '.no_reseller_list');
	}
	
	return $rs->recordCount();
}	

function get_reseller_list ($tpl, $sql, $software_id) {
	$query = "
		SELECT 
			a.`reseller_id`,
			b.`admin_name` as reseller
		FROM 
			`reseller_props` a,
			`admin` b
		WHERE
			a.`reseller_id` = b.`admin_id`
		AND 
			a.`software_allowed` = 'yes'
		AND
			a.`softwaredepot_allowed` = 'yes'
	";
	$rs = exec_query($sql, $query, array());
	if ($rs->recordCount() > 0){
		$reseller_count = 0;
		while(!$rs->EOF) {
			$query2 = "
				SELECT 
					`reseller_id`
				FROM 
					`web_software`
				WHERE
					`reseller_id` = ?
				AND 
					`software_master_id` = ?
			";
			$rs2 = exec_query($sql, $query2, array($rs->fields['reseller_id'],$software_id));
			if ($rs2->recordCount() === 0){
				$tpl->assign(
						array(
							'ALL_RESELLER_NAME'	=> tr('All reseller'),
							'RESELLER_ID' 		=> $rs->fields['reseller_id'],
							'RESELLER_NAME' 	=> $rs->fields['reseller'],
							'SOFTWARE_ID_VALUE'	=> $software_id
						)
					);
				$tpl->parse('RESELLER_ITEM', '.reseller_item');
				$reseller_count++;
			}
		$rs->moveNext();
		}
		if ($reseller_count > 0){
			$tpl->parse('SELECT_RESELLER', '.select_reseller');
			$tpl->assign('NO_SELECT_RESELLER', '');
		}else{
			$tpl->assign(
					array(
						'NO_RESELLER_AVAILABLE' => tr('No Reseller available to add the permissions'),
						'SELECT_RESELLER'		=> '',
						'RESELLER_ITEM'			=> ''
					)
				);
			$tpl->parse('NO_SELECT_RESELLER', '.no_select_reseller');
		}
	}else{
		$tpl->assign(
				array(
					'NO_RESELLER_AVAILABLE' => tr('No Reseller available to add the permissions'),
					'SELECT_RESELLER'		=> '',
					'RESELLER_ITEM'			=> ''
				)
			);
		$tpl->parse('NO_SELECT_RESELLER', '.no_select_reseller');
	}
}

if (isset($_GET['id']) || isset($_POST['id'])) {
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$software_id = $_GET['id'];
	} elseif (isset($_POST['id']) && is_numeric($_POST['id'])) {
		$software_id = $_POST['id'];
	} else {
		set_page_message(tr('Wrong software id.'));
		header('Location: software_manage.php');
	}

} else {
	set_page_message(tr('Wrong software id.'));
	header('Location: software_manage.php');
}

$tpl->assign(
		array(
			'TR_MANAGE_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Application Management (Permissions)'),
			'THEME_COLOR_PATH'				=> "../themes/{$cfg->USER_INITIAL_THEME}",
			'THEME_CHARSET'					=> tr('encoding'),
			'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
			)
	);

$res_cnt = get_reseller_rights ($tpl, $sql, $software_id);
$res_list = get_reseller_list ($tpl, $sql, $software_id);

$query = "
	SELECT
		`software_name`,
		`software_version`,
		`software_language`
	FROM
		`web_software`
	WHERE
		`software_id` = ?
";
$rs = exec_query($sql, $query, $software_id);
$tpl->assign(
		array(
			'SOFTWARE_RIGHTS_ID'			=> $software_id,
			'TR_SOFTWARE_DEPOT'				=> tr('Softwaredepot'),
			'TR_SOFTWARE_NAME'				=> tr('%1$s - (Version: %2$s, Language: %3$s)', $rs->fields['software_name'], $rs->fields['software_version'], $rs->fields['software_language']),
			'TR_ADD_RIGHTS' 				=> tr('Add permissions for reseller to software:'),
			'TR_RESELLER' 					=> tr('Reseller'),
			'TR_REMOVE_RIGHTS' 				=> tr('Remove permissions'),
			'TR_RESELLER_COUNT' 			=> tr('Reseller with permissions total'),
			'TR_RESELLER_NUM' 				=> $res_cnt,
			'TR_ADDED_BY' 					=> tr('Added by'),
			'TR_ADD_RIGHTS_BUTTON' 			=> tr('Add permissions'),
			'TR_SOFTWARE_RIGHTS' 			=> tr('Software permissions'),
			'TR_ADMIN_SOFTWARE_PAGE_TITLE' 	=> tr('i-MSCP - Application Management (Permissions)')
			)
	);

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');

gen_logged_from($tpl);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
