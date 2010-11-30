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
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/software_delete.tpl');
$tpl->define_dynamic('page_message', 'page');

function gen_page_data($tpl, $sql) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'send_delmessage') {
		$tpl->assign(
			array(
				'DELETE_MESSAGE_TEXT' => clean_input($_POST['delete_msg_text'], false)
				)
			);
	} else {
		$tpl->assign(
			array(
				'DELETE_MESSAGE_TEXT'	=> '',
				'MESSAGE' 		=> ''
				)
			);
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
	
	$query = "
		SELECT
			`software_id`,
			`software_name`,
			`software_version`,
			`software_archive`,
			`reseller_id`,
			`software_depot`
		FROM
			`web_software`
		WHERE
			`software_id` = ?
	";
	$rs = exec_query($sql, $query, $software_id);
	
	if ($rs->recordCount() != 1) {
		set_page_message(tr('Wrong software id.'));
		header('Location: software_manage.php');
	}

	$query_res = "
		SELECT
			`admin_name`,
			`email`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";
	$rs_res = exec_query($sql, $query_res, $rs->fields['reseller_id']);
	$tpl->assign(
			array(
				'DELETE_SOFTWARE_RESELLER' => tr('%1$s (%2$s)', $rs_res->fields['admin_name'], $rs_res->fields['email'])
			)
		);
	if($rs->fields['software_depot'] == "yes") {
		$del_path = $cfg->GUI_SOFTWARE_DEPOT_DIR ."/". $rs->fields['software_archive']."-".$rs->fields['software_id'].".tar.gz";
		@unlink($del_path);
		$update = "
			UPDATE 
				`web_software_inst`
			SET
				`software_res_del` = 1
			WHERE
				`software_master_id` = ?
		";
		$res = exec_query($sql, $update, $rs->fields['software_id']);
		$delete = "
			DELETE FROM
				`web_software`
			WHERE
				`software_id` = ?
		";
		$delete_master = "
			DELETE FROM
				`web_software`
			WHERE
				`software_master_id` = ?
		";
		$res = exec_query($sql, $delete, $rs->fields['software_id']);
		$res = exec_query($sql, $delete_master, $rs->fields['software_id']);
		set_page_message(tr('Software was deleted.'));
		header('Location: software_manage.php');
	}else{
		if(isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['uaction'] === 'send_delmessage') {
			if (!empty($_POST['id']) && !empty($_POST['delete_msg_text'])) {
				send_deleted_sw($rs->fields['reseller_id'], $rs->fields['software_archive'].'.tar.gz', $rs->fields['software_id'], 'Software '.$rs->fields['software_name'].' (V'.$rs->fields['software_version'].')', clean_input($_POST['delete_msg_text']));
				$del_path = $cfg->GUI_SOFTWARE_DIR."/".$rs->fields['reseller_id']."/".$rs->fields['software_archive']."-".$rs->fields['software_id'].".tar.gz";
				@unlink($del_path);
				$delete="
					DELETE FROM
						`web_software`
					WHERE
						`software_id` = ?
				";
				$res = exec_query($sql, $delete, $rs->fields['software_id']);
				set_page_message(tr('Software was deleted.'));
				header('Location: software_manage.php');
			} else {
				set_page_message(tr('Fill out a message text!'));
			}
		}
		
		$tpl->assign(
				array(
					'TR_MANAGE_SOFTWARE_PAGE_TITLE' 	=> tr('i-MSCP - Application Management'),
					'THEME_COLOR_PATH' 					=> "../themes/{$cfg->USER_INITIAL_THEME}",
					'THEME_CHARSET' 					=> tr('encoding'),
					'ISP_LOGO' 							=> get_logo($_SESSION['user_id']),
					'TR_DELETE_SEND_TO'					=> tr('Send message to'),
					'TR_DELETE_MESSAGE_TEXT'			=> tr('Message'),
					'TR_DELETE_SOFTWARE'				=> tr('Message to reseller before deleting the software'),
					'TR_DELETE_RESELLER_SOFTWARE'		=> tr('Delete reseller software'),
					'TR_DELETE_DATA'					=> tr('Reseller data'),
					'TR_SEND_MESSAGE'					=> tr('Delete software and send message'),
					'SOFTWARE_ID'						=> $software_id,
					'RESELLER_ID'						=> $rs->fields['reseller_id']
					)
			);
	}
	gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
	gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');

	gen_page_data ($tpl, $sql);
	
	gen_page_message($tpl);
	
	$tpl->parse('PAGE', 'page');

	$tpl->prnt();

	if ($cfg->DUMP_GUI_DEBUG) {
		dump_gui_debug();
	}

	unset_messages();
} else {
	set_page_message(tr('Wrong software id.'));
	header('Location: software_manage.php');
}
