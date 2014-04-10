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

if (isset($_GET['id']) || isset($_POST['id'])) {
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$software_id = $_GET['id'];
	} elseif (isset($_POST['id']) && is_numeric($_POST['id'])) {
		$software_id = $_POST['id'];
	} else {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software_manage.php');
	}
	
	if(isset($_POST['change']) && $_POST['change'] == "add"){
		$reseller_id = $_POST['selected_reseller'];
		$user_id = $_SESSION['user_id'];
		$query = "
			SELECT
				* 
			FROM
				`web_software`
			WHERE
				`software_id` = ?
		";
		$rs = exec_query($query, $software_id);
		$query = "
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
		if($reseller_id == "all"){
			$query2 = "
				SELECT 
					`reseller_id`
				FROM 
					`reseller_props`
				WHERE
					`software_allowed` = 'yes'
				AND
					`softwaredepot_allowed` = 'yes'
			";
			$rs2 = exec_query($query2, array());
			if ($rs2->recordCount() > 0){
				while(!$rs2->EOF) {
					$query3 = "
						SELECT 
							`reseller_id`
						FROM 
							`web_software`
						WHERE
							`reseller_id` = ?
						AND 
							`software_master_id` = ?
					";
					$rs3 = exec_query($query3, array($rs2->fields['reseller_id'],$software_id));
					if ($rs3->recordCount() === 0){
						exec_query(
							$query,
								array(
									$software_id, $rs2->fields['reseller_id'], $rs->fields['software_name'],
									$rs->fields['software_version'], $rs->fields['software_language'], $rs->fields['software_type'],
									$rs->fields['software_db'], $rs->fields['software_archive'], $rs->fields['software_installfile'],
									$rs->fields['software_prefix'], $rs->fields['software_link'], $rs->fields['software_desc'],
									$rs->fields['software_active'], "ok", $user_id, "yes"
								)
						);
                        /** @var $db iMSCP_Database */
                        $db = iMSCP_Registry::get('db');
						$sw_id = $db->insertId();
						update_existing_client_installations_sw_depot($sw_id, $software_id, $rs2->fields['reseller_id']);
					}
					$rs2->MoveNext();
				}
			}else{
				set_page_message(tr('No resellers found.'), 'error');
				redirectTo('software_rights.php?id='.$software_id);
			}
		}else{
			exec_query(
				$query, 
					array(
						$software_id, $reseller_id, 
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
						$rs->fields['software_active'],
						"ok", $user_id, "yes"
					)
			);
            /** @var $db iMSCP_Database */
            $db = iMSCP_Registry::get('db');
			$sw_id = $db->insertId();
			update_existing_client_installations_sw_depot($sw_id, $software_id, $reseller_id);
		}
		set_page_message(tr('Rights successfully added.'), 'success');
		redirectTo('software_rights.php?id='.$software_id);
	} else {
		$reseller_id = $_GET['reseller_id'];
		$delete = "
			DELETE FROM
				`web_software`
			WHERE
				`software_master_id` = ?
			AND
				`reseller_id` = ?
		";
		$update = "
			UPDATE
				`web_software_inst`
			SET
				`software_res_del` = 1
			WHERE
				`software_master_id` = ?
		";
		exec_query($delete, array($software_id, $reseller_id));
		exec_query($update, $software_id);
		set_page_message(tr('Rights successfully removed.'), 'success');
		redirectTo('software_rights.php?id='.$software_id);
	}
} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software_manage.php');
}
