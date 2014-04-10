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

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/software_delete.tpl',
		'page_message', 'page'));

/**
 * @param $tpl
 */
function gen_page_data($tpl) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'send_delmessage') {
		$tpl->assign('DELETE_MESSAGE_TEXT', clean_input($_POST['delete_msg_text'], false));
	} else {
		$tpl->assign(
			array(
				'DELETE_MESSAGE_TEXT' => '',
				'MESSAGE' => ''));
	}
}
if (isset($_GET['id']) || isset($_POST['id'])) {
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$software_id = $_GET['id'];
	} elseif (isset($_POST['id']) && is_numeric($_POST['id'])) {
		$software_id = $_POST['id'];
	} else {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software_manage.php');
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
	$rs = exec_query($query, $software_id);
	
	if ($rs->recordCount() != 1) {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software_manage.php');
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
	$rs_res = exec_query($query_res, $rs->fields['reseller_id']);
	$tpl->assign('DELETE_SOFTWARE_RESELLER', tr('%1$s (%2$s)', $rs_res->fields['admin_name'], $rs_res->fields['email']));
	if($rs->fields['software_depot'] == "yes") {
		$del_path = $cfg->GUI_APS_DEPOT_DIR ."/". $rs->fields['software_archive']."-".$rs->fields['software_id'].".tar.gz";
		@unlink($del_path);
		$update = "
			UPDATE 
				`web_software_inst`
			SET
				`software_res_del` = 1
			WHERE
				`software_master_id` = ?
		";
		$res = exec_query($update, $rs->fields['software_id']);
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
		$res = exec_query($delete, $rs->fields['software_id']);
		$res = exec_query($delete_master, $rs->fields['software_id']);
        echo "hallo";
		set_page_message(tr('Software was deleted.'), 'success');
        redirectTo('software_manage.php');
	} else {
		if(isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['uaction'] === 'send_delmessage') {
			if (!empty($_POST['id']) && !empty($_POST['delete_msg_text'])) {
				send_deleted_sw($rs->fields['reseller_id'], $rs->fields['software_archive'].'.tar.gz', $rs->fields['software_id'], 'Software '.$rs->fields['software_name'].' (V'.$rs->fields['software_version'].')', clean_input($_POST['delete_msg_text']));
				update_existing_client_installations_res_upload(
			        $rs->fields['software_id'], $rs->fields['reseller_id'], $rs->fields['software_id'], TRUE
		        );
                $del_path = $cfg->GUI_APS_DIR."/".$rs->fields['reseller_id']."/".$rs->fields['software_archive']."-".$rs->fields['software_id'].".tar.gz";
				@unlink($del_path);
				$delete="
					DELETE FROM
						`web_software`
					WHERE
						`software_id` = ?
				";
				$res = exec_query($delete, $rs->fields['software_id']);
				set_page_message(tr('Software has been deleted.'), 'success');
				redirectTo('software_manage.php');
			} else {
				set_page_message(tr('Fill out a message text.'), 'error');
			}
		}

		$tpl->assign(
			array(
				'TR_MANAGE_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Software Management'),
				'ISP_LOGO' => layout_getUserLogo(),
				'TR_DELETE_SEND_TO' => tr('Send message to'),
				'TR_DELETE_MESSAGE_TEXT' => tr('Message'),
				'TR_DELETE_SOFTWARE' => tr('Message to reseller before deleting the software'),
				'TR_DELETE_RESELLER_SOFTWARE' => tr('Delete reseller software'),
				'TR_DELETE_DATA' => tr('Reseller data'),
				'TR_DELETE' => tr('Delete'),
				'SOFTWARE_ID' => $software_id,
				'RESELLER_ID' => $rs->fields['reseller_id']));
	}

	generateNavigation($tpl);
	gen_page_data ($tpl);
	generatePageMessage($tpl);
	
	$tpl->parse('LAYOUT_CONTENT', 'page');
	$tpl->prnt();
    unsetMessages();
} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software_manage.php');
}
