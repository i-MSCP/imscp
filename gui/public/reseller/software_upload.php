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
 * @subpackage  Client
 * @copyright   2010-2014 by i-MSCP team
 * @author      Sacha Bay <sascha.bay@i-mscp.net>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

resellerHasFeature('aps') or showBadRequestErrorPage();

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/software_upload.tpl',
		'page_message' => 'layout',
		'list_software' => 'page',
		'no_software_list' => 'page',
		'webdepot_list' => 'page',
		'no_webdepotsoftware_list' => 'page',
		'web_software_repository' => 'page',
		'list_webdepotsoftware' => 'web_software_repository',
		'package_install_link' => 'page',
		'package_info_link' => 'page'
	)
);

if (ask_reseller_is_allowed_web_depot($_SESSION['user_id']) == "yes") {
	list($use_webdepot, $webdepot_xml_url, $webdepot_last_update) = get_application_installer_conf();

	if ($use_webdepot) {
		$error = '';

		if (isset($_POST['uaction']) && $_POST['uaction'] == "updatewebdepot") {
			$xml_file = @file_get_contents($webdepot_xml_url);

			if (!strpos($xml_file, 'i-MSCP web software repositories list')) {
				set_page_message(tr("Unable to read xml file for web software."), 'error');
				$error = 1;
			}

			if (!$error) {
				update_webdepot_software_list($webdepot_xml_url, $webdepot_last_update);
			}
		}

		$packages_cnt = get_webdepot_software_list($tpl, $_SESSION['user_id']);

		$tpl->assign(
			array(
				'TR_WEBDEPOT' => tr('i-MSCP Software installer / Web software repository'),
				'TR_APPLY_CHANGES' => tr('Update from web depot'),
				'TR_PACKAGE_TITLE' => tr('Package title'),
				'TR_PACKAGE_INSTALL_TYPE' => tr('Package install type'),
				'TR_PACKAGE_VERSION' => tr('Package version'),
				'TR_PACKAGE_LANGUAGE' => tr('Package language'),
				'TR_PACKAGE_TYPE' => tr('Package type'),
				'TR_PACKAGE_VENDOR_HP' => tr('Package vendor HP'),
				'TR_PACKAGE_ACTION' => tr('Package actions'),
				'TR_WEBDEPOTSOFTWARE_COUNT' => tr('Web software depot packages total'),
				'TR_WEBDEPOTSOFTWARE_ACT_NUM' => $packages_cnt,
				'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
			)
		);

		$tpl->parse('WEBDEPOT_LIST', '.webdepot_list');
	} else {
		$tpl->assign('WEBDEPOT_LIST', '');
	}
} else {
	$tpl->assign('WEBDEPOT_LIST', '');
}

if (isset($_POST['upload']) && $_SESSION['software_upload_token'] == $_POST['send_software_upload_token']) {
	$success = 1;
	unset($_SESSION['software_upload_token']);

	if ($_FILES['sw_file']['name'] != '' AND !empty($_POST['sw_wget'])) {
		set_page_message(tr('You must choose between local and remote upload.'), 'error');
		$success = 0;
	} elseif ($_FILES['sw_file']['name'] == '' AND empty($_POST['sw_wget'])) {
		set_page_message(tr('You must select a file to upload.'), 'error');
		$success = 0;
	} else {
		if ($_FILES['sw_file']['name'] && $_FILES['sw_file']['name'] != 'none') {
			if (substr($_FILES['sw_file']['name'], -7) != '.tar.gz') {
				set_page_message(tr("Only 'tar.gz' archives are accepted."), 'error');
				$success = 0;
			}

			$file = 0;
		} else {
			if (substr($_POST['sw_wget'], -7) != '.tar.gz') {
				set_page_message(tr("Only 'tar.gz' archives are accepted."), 'error');
				$success = 0;
			}

			$file = 1;
		}
	}

	if ($success == 1) {
		$user_id = $_SESSION['user_id'];
		$upload = 1;

		if ($file == 0) {
			$fname = $_FILES['sw_file']['name'];
		} else {
			$fname = substr($_POST['sw_wget'], (strrpos($_POST['sw_wget'], '/') + 1));
		}

		$filename = substr($fname, 0, -7);
		$extension = substr($fname, -7);

		$query = "
			INSERT INTO `web_software` (
				`reseller_id`, `software_name`, `software_version`, `software_language`, `software_type`, `software_db`,
				`software_archive`, `software_installfile`, `software_prefix`, `software_link`,
				`software_desc`, `software_status`
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
			)
		";
		$rs = exec_query(
			$query,
			array(
				$user_id, 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', '0',
				$filename, 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 'toadd'
			)
		);
		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');
		$sw_id = $db->insertId();

		if ($file == 0) {
			$dest_dir = $cfg->GUI_APS_DIR . '/' . $user_id . '/' . $filename . '-' . $sw_id . $extension;

			if (!is_dir($cfg->GUI_APS_DIR . '/' . $user_id)) {
				@mkdir($cfg->GUI_APS_DIR . '/' . $user_id, 0755, true);
			}

			if (!move_uploaded_file($_FILES['sw_file']['tmp_name'], $dest_dir)) {
				// Delete software entry
				$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
				exec_query($query, $sw_id);
				$sw_wget = "";
				set_page_message(
					tr('Could not upload the file. Max. upload filesize (%1$d MB) has been reached.',
						ini_get('upload_max_filesize')
					),
					'error'
				);
				$upload = 0;
			}
		}
		if ($file == 1) {
			$sw_wget = $_POST['sw_wget'];
			$dest_dir = $cfg->GUI_APS_DIR . '/' . $user_id . '/' . $filename . '-' . $sw_id . $extension;

			// Reading Filesize
			$parts = parse_url($sw_wget);
			$connection = fsockopen($parts['host'], 80, $errno, $errstr, 30);

			if ($connection) {
				$appdata = get_headers($sw_wget, true);
				$length = (isset($appdata['Content-Length'])) ? (int)$appdata['Content-Length'] : null;

				($length) ? $remote_file_size = $length : $remote_file_size = 0;
				$show_remote_file_size = bytesHuman($remote_file_size);

				if ($remote_file_size < 1) {
					// Delete software entry
					$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
					exec_query($query, $sw_id);
					$show_max_remote_filesize = bytesHuman($cfg->APS_MAX_REMOTE_FILESIZE);
					set_page_message(
						tr(
							'Your remote filesize (%s) is lower than 1 Byte. Please check your URL.',
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
						tr(
							'Max. remote filesize (%s) has been reached. Your remote file is %s',
							$show_max_remote_filesize, $show_remote_file_size
						),
						'error'
					);
					$upload = 0;
				} else {
					$remote_file = @file_get_contents($sw_wget);

					if ($remote_file) {
						$output_file = fopen($dest_dir, 'w+');
						fwrite($output_file, $remote_file);
						fclose($output_file);
					} else {
						// Delete software entry
						$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
						exec_query($query, $sw_id);
						set_page_message(tr('Remote file not found.'), 'error');
						$upload = 0;
					}
				}
			} else {
				// Delete software entry
				$query = "DELETE FROM `web_software` WHERE `software_id` = ?";
				exec_query($query, $sw_id);
				set_page_message(tr('Could not upload file.'), 'error');
				$upload = 0;
			}
		}
		if ($upload == 1) {
			$tpl->assign(
				array(
					'VAL_WGET' => '',
					'SW_INSTALLED' => ''
				)
			);
			send_request();
			set_page_message(tr('File successfully uploaded.'), 'success');
		} else {
			$tpl->assign('VAL_WGET', $sw_wget);
		}
	} else {
		$tpl->assign('VAL_WGET', $_POST['sw_wget']);

	}
} else {
	unset($_SESSION['software_upload_token']);
	$tpl->assign('VAL_WGET', '');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / General / Software Upload'),
		'ISP_LOGO' => layout_getUserLogo()
	)
);

$sw_cnt = get_avail_software_reseller($tpl, $_SESSION['user_id']);

$tpl->assign(
	array(
		'TR_SOFTWARE_UPLOAD' => tr('Software upload'),
		'GENERAL_INFO' => tr('General information'),
		'TR_UPLOADED_SOFTWARE' => tr('Software available'),
		'TR_SOFTWARE_NAME' => tr('Software-Synonym'),
		'TR_SOFTWARE_VERSION' => tr('Software-Version'),
		'TR_SOFTWARE_LANGUAGE' => tr('Language'),
		'TR_SOFTWARE_STATUS' => tr('Software status'),
		'TR_SOFTWARE_TYPE' => tr('Type'),
		'TR_SOFTWARE_DELETE' => tr('Action'),
		'TR_SOFTWARE_COUNT' => tr('Software total'),
		'TR_SOFTWARE_NUM' => $sw_cnt,
		'TR_UPLOAD_SOFTWARE' => tr('Software upload'),
		'TR_SOFTWARE_DB' => tr('Requires Database?'),
		'TR_SOFTWARE_DB_PREFIX' => tr('Database prefix'),
		'TR_SOFTWARE_HOME' => tr('Link to authors homepage'),
		'TR_SOFTWARE_DESC' => tr('Description'),
		'SOFTWARE_UPLOAD_TOKEN' => generate_software_upload_token(),
		'TR_SOFTWARE_FILE' => tr('Choose file (Max: %1$d MiB)', ini_get('upload_max_filesize')),
		'TR_SOFTWARE_URL' => tr('or remote file (Max: %s)', bytesHuman($cfg->APS_MAX_REMOTE_FILESIZE)),
		'TR_UPLOAD_SOFTWARE_BUTTON' => tr('Upload now'),
		'TR_UPLOAD_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Sofware Management'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?', true),
		'TR_MESSAGE_INSTALL' => tr('Are you sure to install this package from the webdepot?', true)
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
