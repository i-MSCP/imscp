<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2010-2014 by i-MSCP team
 * @author      Sacha Bay <sascha.bay@i-mscp.net>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

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
		$source_file = $cfg->GUI_APS_DIR.'/'.$rs->fields['reseller_id'].'/'.$rs->fields['software_archive'].'-'.$rs->fields['software_id'].'.tar.gz';
		$dest_file = $cfg->GUI_APS_DEPOT_DIR.'/'.$rs->fields['software_archive'].'-'.$rs->fields['software_id'].'.tar.gz';
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
		
		set_page_message(tr('Software has been successfully imported.'), 'success');
		redirectTo('software_manage.php');
	}
} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software_manage.php');
}
