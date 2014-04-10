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

/** Include core library */
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/software_manage.tpl',
		'page_message' => 'layout',
		'list_software' => 'page',
		'no_software_list' => 'page',
		'list_softwaredepot' => 'page',
		'no_softwaredepot_list' => 'page',
		'no_reseller_list' => 'page',
		'list_reseller' => 'page',
		'webdepot_list' => 'page',
		'no_webdepotsoftware_list' => 'page',
		'web_software_repository' => 'page',
		'list_webdepotsoftware' => 'web_software_repository',
		'package_install_link' => 'page',
		'package_info_link' => 'page'
	)
);

list($use_webdepot, $webdepot_xml_url, $webdepot_last_update) = get_application_installer_conf();

if ($use_webdepot) {
	$error = '';

	if (isset($_POST['uaction']) && $_POST['uaction'] == "updatewebdepot") {
		//$xml_file =  @file_get_contents(encode_idna(strtolower(clean_input($_POST['webdepot_xml_url']))));
		$xml_file = @file_get_contents($webdepot_xml_url);

		if ($xml_file === false) {
			set_page_message(tr("Unable to read Web software repository index file."), 'error');
			$error = 1;
		}
		if (!$error) {
			update_webdepot_software_list($webdepot_xml_url, $webdepot_last_update);
		}
	}

	$packages_cnt = get_webdepot_software_list($tpl, $_SESSION['user_id']);

	$tpl->assign(
		array(
			'TR_WEBDEPOT' => tr('i-MSCP Web software repository'),
			'TR_APPLY_CHANGES' => tr('Update from Web software repository'),
			'TR_PACKAGE_TITLE' => tr('Package name'),
			'TR_PACKAGE_INSTALL_TYPE' => tr('Package install type'),
			'TR_PACKAGE_VERSION' => tr('Package version'),
			'TR_PACKAGE_LANGUAGE' => tr('Package language'),
			'TR_PACKAGE_TYPE' => tr('Package type'),
			'TR_PACKAGE_VENDOR_HP' => tr('Package vendor HP'),
			'TR_PACKAGE_ACTION' => tr('Package actions'),
			'TR_WEBDEPOTSOFTWARE_COUNT' => tr('Total packages in Web software repository'),
			'TR_WEBDEPOTSOFTWARE_ACT_NUM' => $packages_cnt,
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
		)
	);

	$tpl->parse('WEBDEPOT_LIST', '.webdepot_list');
} else {
	$tpl->assign('WEBDEPOT_LIST', '');
}


if (isset($_POST['upload']) && $_SESSION['software_upload_token'] == $_POST['send_software_upload_token']) {
	$success = 1;

	unset($_SESSION['software_upload_token']);

	if ($_FILES['sw_file']['name'] != '' AND !empty($_POST['sw_wget'])) {
		set_page_message(tr('You have to choose between file-upload and wget-function.'), 'error');
		$success = 0;
	} elseif ($_FILES['sw_file']['name'] == '' AND empty($_POST['sw_wget'])) {
		set_page_message(tr('You must select a file to upload/download.'), 'error');
		$success = 0;
	} else {
		if ($_FILES['sw_file']['name'] && $_FILES['sw_file']['name'] != "none") {
			if (substr($_FILES['sw_file']['name'], -7) != '.tar.gz') {
				set_page_message(tr("File needs to be a 'tar.gz' archive."), 'error');
				$success = 0;
			}
			$file = 0;
		} else {
			if (substr($_POST['sw_wget'], -7) != '.tar.gz') {
				set_page_message(tr("File needs to be a 'tar.gz' archive."), 'error');
				$success = 0;
			}
			$file = 1;
		}
	}

	if ($success == 1) {
		$user_id = $_SESSION['user_id'];
		$upload = 1;

		$fname = '';

		if ($file == 0) {
			$fname = $_FILES['sw_file']['name'];
		} elseif ($file == 1) {
			$fname = substr($_POST['sw_wget'], (strrpos($_POST['sw_wget'], '/') + 1));
		}

		$filename = substr($fname, 0, -7);
		$extension = substr($fname, -7);

		$query = "
			INSERT INTO
				`web_software`
					(
						`reseller_id`, `software_name`, `software_version`, `software_language`, `software_type`,
						`software_db`, `software_archive`, `software_installfile`, `software_prefix`, `software_link`,
						`software_desc`, `software_active`, `software_status`, `software_depot`
					) VALUES (
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
					)
				;
			";

		$rs = exec_query(
			$query,
			array(
				$user_id, 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 0,
				$filename, 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 1,
				'toadd', 'yes'
			)
		);

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');
		$sw_id = $db->insertId();

		if ($file == 0) {
			$dest_dir = $cfg->GUI_APS_DEPOT_DIR . '/' . $filename . '-' . $sw_id . $extension;

			if (!is_dir($cfg->GUI_APS_DEPOT_DIR)) {
				@mkdir($cfg->GUI_APS_DEPOT_DIR, 0755, true);
			}

			if (!move_uploaded_file($_FILES['sw_file']['tmp_name'], $dest_dir)) {
				// Delete software entry
				$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
				exec_query($query, $sw_id);

				$sw_wget = '';

				set_page_message(
					tr(
						'Unable to upload file. Max. upload filesize (%1$d MB) reached?',
						ini_get('upload_max_filesize')
					),
					'error'
				);

				$upload = 0;
			}
		}

		if ($file == 1) {
			$sw_wget = $_POST['sw_wget'];
			$dest_dir = $cfg->GUI_APS_DEPOT_DIR . '/' . $filename . '-' . $sw_id . $extension;

			// Reading filesize
			$parts = parse_url($sw_wget);
			$connection = @fsockopen($parts['host'], 80, $errno, $errstr, 30);

			if ($connection) {
				$appdata = get_headers($sw_wget, true);
				$length = (isset($appdata['Content-Length'])) ? (int) $appdata['Content-Length'] : null;

				($length) ? $remote_file_size = $length : $remote_file_size = 0;
				$show_remote_file_size = bytesHuman($remote_file_size);

				if ($remote_file_size < 1) {
					// Delete software entry
					$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
					exec_query($query, $sw_id);
					$show_max_remote_filesize = bytesHuman($cfg->APS_MAX_REMOTE_FILESIZE);
					set_page_message(
						tr(
							'The remote filesize (%s) is lower than 1 Byte. Please check the URL.',
							$show_remote_file_size
						),
						'error'
					);

					$upload = 0;
				} elseif ($remote_file_size > $cfg->APS_MAX_REMOTE_FILESIZE) {
					// Delete software entry
					$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
					exec_query($query, $sw_id);
					$show_max_remote_filesize = bytesHuman($cfg->APS_MAX_REMOTE_FILESIZE);
					set_page_message(
						tr('Max. remote filesize (%s) is reached. Your remote file is %s',
							$show_max_remote_filesize, $show_remote_file_size), 'error');

					$upload = 0;
				} else {
					$remote_file = @file_get_contents($sw_wget);
					if ($remote_file) {
						$output_file = @fopen($dest_dir, 'w+');
						fwrite($output_file, $remote_file);
						fclose($output_file);
					} else {
						// Delete software entry
						$query = "DELETE FROM `web_software` WHERE`software_id` = ?";
						exec_query($query, $sw_id);
						set_page_message(tr('Error: Remote File not found!'), 'error');
						$upload = 0;
					}
				}
			} else {
				// Delete software entry
				$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
				exec_query($query, $sw_id);
				set_page_message(tr('Could not upload file. File not found.'), 'error');
				$upload = 0;
			}
		}

		if ($upload == 1) {
			$tpl->assign(array('VAL_WGET' => ''));
			send_request();
			set_page_message(tr('File has been successfully uploaded.'), 'success');
		} else {
			$tpl->assign(array('VAL_WGET' => $sw_wget));
		}
	} else {
		$tpl->assign(array('VAL_WGET' => $_POST['sw_wget']));
	}
} else {
	unset($_SESSION['software_upload_token']);
	$tpl->assign(array('VAL_WGET' => ''));
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Software Management'),
		'ISP_LOGO' => layout_getUserLogo()));

$sw_cnt = get_avail_software($tpl);
$swdepot_cnt = get_avail_softwaredepot($tpl);
$res_cnt = get_reseller_software($tpl);

$tpl->assign(
	array(
		'TR_SOFTWARE_DEPOT' => tr('Software in repository'),
		'SOFTWARE_UPLOAD_TOKEN' => generate_software_upload_token(),
		'TR_SOFTWARE_ADMIN' => tr('Admin'),
		'TR_SOFTWARE_RIGHTS' => tr('Permissions'),
		'TR_SOFTWAREDEPOT_COUNT' => tr('Total Web software repositories'),
		'TR_SOFTWAREDEPOT_NUM' => $swdepot_cnt,
		'TR_UPLOAD_SOFTWARE' => tr('Software depot upload'),
		'TR_SOFTWARE_FILE' => tr('Choose file (Max: %1$d MiB)', ini_get('upload_max_filesize')),
		'TR_SOFTWARE_URL' => tr('or remote file (Max: %s)', bytesHuman($cfg->APS_MAX_REMOTE_FILESIZE)),
		'TR_UPLOAD_SOFTWARE_BUTTON' => tr('Upload now'),
		'TR_AWAITING_ACTIVATION' => tr('Awaiting activation'),
		'TR_RESELLER_SOFTWARES_LIST' => tr('Reseller software list'),
		'TR_SOFTWARE_NAME' => tr('Software name'),
		'TR_SOFTWARE_VERSION' => tr('Version'),
		'TR_SOFTWARE_LANGUAGE' => tr('Language'),
		'TR_SOFTWARE_TYPE' => tr('Type'),
		'TR_SOFTWARE_RESELLER' => tr('Reseller'),
		'TR_SOFTWARE_IMPORT' => tr('Import in local repository'),
		'TR_SOFTWARE_DOWNLOAD' => tr('Download'),
		'TR_SOFTWARE_ACTIVATION' => tr('Activate'),
		'TR_SOFTWARE_DELETE' => tr('Delete'),
		'TR_SOFTWARE_ACT_COUNT' => tr('Total software'),
		'TR_SOFTWARE_ACT_NUM' => $sw_cnt,
		'TR_RESELLER_NAME' => tr('Reseller'),
		'TR_RESELLER_ACT_COUNT' => tr('Total reseller'),
		'TR_RESELLER_ACT_NUM' => $res_cnt,
		'TR_RESELLER_COUNT_SWDEPOT' => tr('Software in repository'),
		'TR_RESELLER_COUNT_WAITING' => tr('Awaiting activation'),
		'TR_RESELLER_COUNT_ACTIVATED' => tr('Activated software'),
		'TR_RESELLER_SOFTWARE_IN_USE' => tr('Total installations'),
		'TR_MESSAGE_ACTIVATE' => tr('Are you sure you want to activate this package?', true),
		'TR_MESSAGE_IMPORT' => tr('Are you sure you want to import this package into the local software repository?', true),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?', true),
		'TR_MESSAGE_INSTALL' => tr('Are you sure you want to install this package from the Web software repository?', true),
		'TR_ADMIN_SOFTWARE_PAGE_TITLE' => tr('i-MSCP / Software Installer / Management'),
		'TR_SOFTWARE_UPDLOAD' => tr('Software upload')
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
