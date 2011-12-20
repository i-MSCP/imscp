<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * The Original Code is i-MSCP - Multi Server Control Panel.
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by Initial Developer are Copyright (C) 2010-2011
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category i-MSCP
 * @copyright 2010-2011 by i-MSCP | http://i-mscp.net
 * @author Sacha Bay <sascha.bay@i-mscp.net>
 * @link http://i-mscp.net i-MSCP Home Site
 * @license http://www.mozilla.org/MPL/ MPL 1.1
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['id']) AND is_numeric($_GET['id'])) {
	$query="
		SELECT
			*
		FROM
			`web_software`
		WHERE
			`software_id` = ?
	";
	$rs = exec_query($query, $_GET['id']);

	if ($rs->recordCount() != 1) {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software_manage.php');
	} else {
		$source_file = $cfg->GUI_SOFTWARE_DIR.'/'.$rs->fields['reseller_id'].'/'.$rs->fields['software_archive'].'-'.$rs->fields['software_id'].'.tar.gz';
		$dest_file = $cfg->GUI_SOFTWARE_DEPOT_DIR.'/'.$rs->fields['software_archive'].'-'.$rs->fields['software_id'].'.tar.gz';
		$user_id = $_SESSION['user_id'];
		$update="
			UPDATE
				`web_software`
			SET
				`reseller_id` = '".$user_id."',
				`software_active` = 1,
				`software_depot` = 'yes'
			WHERE
				`software_id` = ?
		";
		
		@copy($source_file, $dest_file);
		@unlink($source_file);
		
		$res = exec_query($update, $_GET['id']);
		$query="
			INSERT INTO
				`web_software`
					(
						`software_master_id`, 
						`reseller_id`, 
						`software_name`,
						`software_version`, 
						`software_language`, 
						`software_type`,
						`software_db`, 
						`software_archive`, 
						`software_installfile`,
						`software_prefix`, 
						`software_link`, 
						`software_desc`,
						`software_active`, 
						`software_status`, 
						`rights_add_by`,
						`software_depot`
					)
			VALUES
					(
						?, ?, ?,
						?, ?, ?,
						?, ?, ?,
						?, ?, ?,
						?, ?, ?,
						?
					)
		";
		exec_query(
			$query,
			array(
				$rs->fields['software_id'], 
				$rs->fields['reseller_id'], 
				$rs->fields['software_name'],
				$rs->fields['software_version'], 
				$rs->fields['software_language'], 
				$rs->fields['software_type'],
				$rs->fields['software_db'], 
				$rs->fields['software_archive'], 
				$rs->fields['software_installfile'],
				$rs->fields['software_prefix'], 
				$rs->fields['software_link'], 
				$rs->fields['software_desc'],
				"1", "ok", $user_id, "yes"
			)
		);
        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');
		$sw_id = $db->insertId();
		update_existing_client_installations_res_upload(
			$sw_id, $rs->fields['reseller_id'], $rs->fields['software_id']
		);
		
		set_page_message(tr('Software was imported successfully.'), 'success');
		redirectTo('software_manage.php');
	}
} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software_manage.php');
}
