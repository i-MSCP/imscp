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

/**
 * @var $sql iMSCP_Database
 */
$sql = iMSCP_Registry::get('Db');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/software_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('list_software', 'page');
$tpl->define_dynamic('no_software_list', 'page');
$tpl->define_dynamic('list_softwaredepot', 'page');
$tpl->define_dynamic('no_softwaredepot_list', 'page');
$tpl->define_dynamic('no_reseller_list', 'page');
$tpl->define_dynamic('list_reseller', 'page');

// form data

function formatFilesize($byte) {
	$string = 'Byte';

	if($byte > 1024) {
		$byte /= 1024;
		$string = 'KB';
	}

	if($byte > 1024) {
		$byte /= 1024;
		$string = 'MB';
	}

	if($byte > 1024) {
		$byte /= 1024;
		$string = 'GB';
	}

	if(number_format($byte,0) != $byte) $byte = number_format($byte, 2);
	return $byte . ' ' . $string;
}

if (isset($_POST['upload']) && $_SESSION['software_upload_token'] == $_POST['send_software_upload_token']) {
	$success = 1;

	unset($_SESSION['software_upload_token']);

	if ($_FILES['sw_file']['name'] != '' AND !empty($_POST['sw_wget'])) {
		set_page_message(tr('You have to choose between file-upload and wget-function.'));
		$success = 0;
	} elseif ($_FILES['sw_file']['name'] == '' AND empty($_POST['sw_wget'])) {
		set_page_message(tr('You must select a file to upload/download.'));
		$success = 0;
	} else {
		if ($_FILES['sw_file']['name'] && $_FILES['sw_file']['name'] != "none") {
			if (substr($_FILES['sw_file']['name'], -7) != '.tar.gz') {
				set_page_message(tr('File needs to be a .tar.gz-archive'));
				$success = 0;
			}
			$file = 0;
		} else {
			if (substr($_POST['sw_wget'], -7) != '.tar.gz') {
				set_page_message(tr('File needs to be a .tar.gz-archive'));
				$success = 0;
			}
			$file = 1;
		}
	}

	if ($success == 1) {
		$user_id = $_SESSION['user_id'];
		$upload = 1;

		if($file == 0) {
			$fname = $_FILES['sw_file']['name'];
		} elseif($file == 1) {
			$fname = substr($_POST['sw_wget'], (strrpos($_POST['sw_wget'], '/') +1));
		}

		$filename = substr($fname, 0, -7);
		$extension = substr($fname, -7);

		$query="
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
			$sql, $query,
				array(
					$user_id, 'waiting_for_input', 'waiting_for_input', 'waiting_for_input',  'waiting_for_input', 0,
					$filename, 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 'waiting_for_input', 1,
					'toadd', 'yes'
				)
		);

		$sw_id = $sql->insertId();
		if ($file == 0) {
			$dest_dir = $cfg->GUI_SOFTWARE_DEPOT_DIR . '/' . $filename . '-' . $sw_id.$extension;

			if (!is_dir($cfg->GUI_SOFTWARE_DEPOT_DIR)) {
				@mkdir($cfg->GUI_SOFTWARE_DEPOT_DIR,0755,true);
			}

			if (!move_uploaded_file($_FILES['sw_file']['tmp_name'], $dest_dir)) {
				// Delete software entry
				$query = "
					DELETE FROM `web_software` WHERE `software_id` = ?;";
				exec_query($sql, $query, array($sw_id));

				$sw_wget = '';

				set_page_message(tr(
					'ERROR: Could not upload file. Max. upload filesize (%1$d MB) reached?',
					ini_get('upload_max_filesize'))
				);

				$upload = 0;
			}
		}
		if ($file == 1) {
			$sw_wget = $_POST['sw_wget'];
			$dest_dir = $cfg->GUI_SOFTWARE_DEPOT_DIR . '/' . $filename . '-' . $sw_id.$extension;

			// Reading filesize
   			$parts = parse_url($sw_wget);
   			$connection = fsockopen($parts['host'], 80, $errno, $errstr, 30);

   			if($connection) {
   				fputs($connection, 'GET ' . $sw_wget . " HTTP/1.1\r\nHost: " . $parts['host'] . "\r\n\r\n");
   				$size = 0;

				$length = null;
   				//while(!isset($length) || ($size <= 500 && !feof($connection))) {
				while(is_null($length) || ($size <= 500 && !feof($connection))) {
   					$tstr = fgets($connection, 128);
   					$size += strlen($tstr);

   					if(substr($tstr, 0, 14) == 'Content-Length') {
   						$length = substr($tstr, 15);
   					}
   				}

   				if($length) {
					$remote_file_size = $length;
				} else {
					$remote_file_size = 0;
				}

				$show_remote_file_size = formatFilesize($remote_file_size);

				if($remote_file_size < 1){
					// Delete software entry
					$query = "DELETE FROM `web_software` WHERE `software_id` = ?;";
					exec_query($sql, $query, $sw_id);

					$show_max_remote_filesize = formatFilesize($cfg->MAX_REMOTE_FILESIZE);
					set_page_message(tr(
						'ERROR: Your remote filesize (%1$d B) is lower than 1 Byte. Please check your URL!',
						$show_remote_file_size)
					);

					$upload = 0;
				} elseif($remote_file_size > $cfg->MAX_REMOTE_FILESIZE) {
					// Delete software entry
					$query = "DELETE FROM `web_software` WHERE `software_id` = ?;";
					exec_query($sql, $query, $sw_id);
					$show_max_remote_filesize = formatFilesize($cfg->MAX_REMOTE_FILESIZE);
					set_page_message(tr(
						'ERROR: Max. remote filesize (%1$d MB) is reached. Your remote file is %2$d MB',
						$show_max_remote_filesize, $show_remote_file_size)
					);

					$upload = 0;
				} else {
					$remote_file = @file_get_contents($sw_wget);
					if($remote_file) {
						$output_file = fopen($dest_dir, 'w+');
						fwrite($output_file,$remote_file);
						fclose($output_file);
					} else {
						// Delete software entry
						$query = "DELETE FROM `web_software` WHERE`software_id` = ?;";
						exec_query($sql, $query, $sw_id);
						set_page_message(tr('ERROR: Remote File not found!'));
						$upload = 0;
					}
				}
   			} else {
				// Delete software entry
				$query = "DELETE FROM `web_software` WHERE `software_id` = ?;";
				exec_query($sql, $query, $sw_id);
				set_page_message(tr('ERROR: Could not upload the file. File not found!'));
				$upload = 0;
			}
		}

		if ($upload == 1) {
			$tpl->assign(array('VAL_WGET' => ''));
			send_request();
			set_page_message(tr('File was successfully uploaded.'));	
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

// Begin function block
function get_avail_software (&$tpl, &$sql) {
	$query="
		SELECT
			`a`.`software_id` as `id`, `a`.`software_name` as `name`, `a`.`software_version` as `version`,
			`a`.`software_language` as `language`, `a`.`software_type` as `type`, `a`.`software_desc` as `description`,
			`a`.`reseller_id`, `b`.`admin_id`, `b`.`admin_name` as `reseller`
		FROM
			`web_software` a, `admin` b
		WHERE
			`a`.`software_active` = 0
		AND
			`a`.`reseller_id` = `b`.`admin_id`
		AND
			`a`.software_depot = 'no'
		ORDER BY
			`a`.`reseller_id` ASC,
			`a`.`software_type` ASC,
			`a`.`software_name` ASC
		;
	";
	$rs = exec_query($sql, $query, array());
	if ($rs->recordCount() > 0) {
		while(!$rs->EOF) {
			$import_url = "software_import.php?id=".$rs->fields['id'];
			$act_url 	= "software_activate.php?id=".$rs->fields['id'];
			$del_url 	= "software_delete.php?id=".$rs->fields['id'];
			$dow_url 	= "software_download.php?id=".$rs->fields['id'];

			$tpl->assign(
				array(
					'TR_NAME'		=> $rs->fields['name'],
					'TR_TOOLTIP' 	=> $rs->fields['description'],
					'TR_VERSION' 	=> $rs->fields['version'],
					'TR_LANGUAGE'	=> $rs->fields['language'],
					'TR_TYPE'		=> $rs->fields['type'],
					'TR_RESELLER' 	=> $rs->fields['reseller'],
					'DOWNLOAD_LINK' => $dow_url,
					'TR_DOWNLOAD'	=> tr('Download'),
					'ACTIVATE_LINK' => $act_url,
					'IMPORT_LINK' 	=> $import_url,
					'TR_ACTIVATION' => tr('Activate'),
					'TR_IMPORT' 	=> tr('Import'),
					'DELETE_LINK' 	=> $del_url,
					'TR_DELETE' 	=> tr('Delete')
				)
			);

			$tpl->parse('LIST_SOFTWARE', '.list_software');
			$rs->moveNext();
		}
		$tpl->assign('NO_SOFTWARE_LIST', '');
	} else {
		$tpl->assign(array('NO_SOFTWARE' => tr('No software is waiting for activation')));
		$tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
		$tpl->assign('LIST_SOFTWARE', '');
	}
	return $rs->recordCount();
}

function get_avail_softwaredepot ($tpl, $sql) {

	/**
 	 * @var $cfg iMSCP_Config_Handler_File
 	 */
	$cfg = iMSCP_Registry::get('Config');

	$query="
		SELECT
			`a`.`software_id` as `id`, `a`.`software_name` as `name`, `a`.`software_version` as `version`,
			`a`.`software_language` as `language`, `a`.`software_type` as `type`, `a`.`software_desc` as `description`,
			`a`.`reseller_id`, `a`.`software_archive` as `filename`, `a`.`software_status` as `swstatus`, `b`.`admin_id`,
			`b`.`admin_name` as `admin`
		FROM
			`web_software` a, `admin` b
		WHERE
			`a`.`software_depot` = 'yes'
		AND
			`a`.`software_master_id` = 0
		AND
			`a`.`reseller_id` = `b`.`admin_id`
		ORDER BY
			`a`.`reseller_id` ASC, `a`.`software_type` ASC, `a`.`software_name` ASC
		;
	";

	$rs = exec_query($sql, $query, array());

	if ($rs->recordCount() > 0) {
		while(!$rs->EOF) {
			if($rs->fields['swstatus'] == 'ok' || $rs->fields['swstatus'] == 'ready') {
				if($rs->fields['swstatus'] == 'ready') {
					$updatequery = "UPDATE `web_software` SET `software_status` = 'ok' WHERE `software_id` = ?;";
					exec_query($sql, $updatequery, $rs->fields['id']);
					set_page_message(tr('Package installed successfully!'));
				}

				$del_url = 'software_delete.php?id=' . $rs->fields['id'];
				$dow_url = 'software_download.php?id=' . $rs->fields['id'];
				$rights_url = 'software_rights.php?id=' . $rs->fields['id'];

				$tpl->assign(
					array(
						'TR_NAME'				=> $rs->fields['name'],
						'LINK_COLOR'			=> '#000000',
						'TR_TOOLTIP'			=> $rs->fields['description'],
						'TR_VERSION'			=> $rs->fields['version'],
						'TR_LANGUAGE'			=> $rs->fields['language'],
						'TR_TYPE'				=> $rs->fields['type'],
						'TR_ADMIN'				=> $rs->fields['admin'],
						'DOWNLOAD_LINK'			=> $dow_url,
						'TR_DOWNLOAD'			=> tr('Download'),
						'DELETE_LINK'			=> $del_url,
						'TR_DELETE'				=> tr('Delete'),
						'SOFTWARE_ICON'			=> 'edit',
						'SOFTWARE_RIGHTS_LINK'	=> $rights_url,
						'RIGHTS_LINK'			=> tr('Rights'),
						'TR_SOFTWARE_RIGHTS' 	=> tr('Softwarerights')
						)
					);
			} else {
				if($rs->fields['swstatus'] == 'toadd') {
					$del_url = 'software_delete.php?id=' . $rs->fields['id'];
					$tpl->assign(
						array(
							'TR_NAME'				=> tr('Installing your uploaded package. Please refresh this site.'),
							'LINK_COLOR'			=> '#FF0000',
							'TR_VERSION'			=> '',
							'TR_LANGUAGE'			=> '',
							'TR_TOOLTIP'			=> tr(
								'The package will be installed automaticly to your system after upload.<br />Refresh your site to see the new status!'
							),
							'TR_DOWNLOAD'			=> '',
							'DOWNLOAD_LINK'			=> '',
							'DELETE_LINK'			=> $del_url,
							'TR_DELETE'				=> tr('Delete'),
							'TR_TYPE'				=> '<font color="#FF0000">' . tr('installing') . '</font>',
							'TR_ADMIN'				=> $rs->fields['admin'],
							'SOFTWARE_ICON'			=> 'disabled',
							'RIGHTS_LINK'			=> '',
							'TR_SOFTWARE_RIGHTS' 	=> '',
							'SOFTWARE_RIGHTS_LINK' 	=> ''
						)
					);
					} else {
						if($rs->fields['swstatus'] == "delete") {
							$tpl->assign(
								array(
									'TR_NAME'				=> tr('Failure in the package. Deleting!'),
									'LINK_COLOR' 			=> '#FF0000',
									'TR_VERSION' 			=> '',
									'TR_LANGUAGE' 			=> '',
									'TR_TOOLTIP' 			=> tr(
										'There is an Error inside your package. Please check it!<br />Refresh your site to see the new status!'
									),
									'TR_DOWNLOAD' 			=> '',
									'DOWNLOAD_LINK' 		=> '',
									'DELETE_LINK' 			=> '',
									'TR_DELETE' 			=> '',
									'TR_TYPE'				=> '<font color="#FF0000">' . tr('deleting') . '</font>',
									'TR_ADMIN'				=> $rs->fields['admin'],
									'SOFTWARE_ICON'			=> 'delete',
									'RIGHTS_LINK'			=> '',
									'TR_SOFTWARE_RIGHTS' 	=> '',
									'SOFTWARE_RIGHTS_LINK'	=> ''
								)
							);

							set_page_message(tr('Your package is corrupt. Please correct it!'));
						} elseif (preg_match('/double_depot_/i', $rs->fields['swstatus'])) {
							$tpl->assign(
								array(
									'TR_NAME'				=> tr('Package already exist in the software depot!'),
									'LINK_COLOR'			=> '#FF0000',
									'TR_VERSION'			=> '',
									'TR_LANGUAGE'			=> '',
									'TR_TOOLTIP'			=> tr(
										'Check your software depot uploads!<br />It is not allowed to upload this package two times.<br />Refresh your site to see the new status!'
									),
									'TR_DOWNLOAD'			=> '',
									'DOWNLOAD_LINK'			=> '',
									'DELETE_LINK'			=> '',
									'TR_DELETE'				=> '',
									'TR_TYPE'				=> '<font color="#FF0000">' . tr('deleting') . '</font>',
									'TR_ADMIN'				=> $rs->fields['admin'],
									'SOFTWARE_ICON'			=> 'delete',
									'RIGHTS_LINK'			=> '',
									'TR_SOFTWARE_RIGHTS' 	=> '',
									'SOFTWARE_RIGHTS_LINK' 	=> ''
								)
							);

							set_page_message(tr('This package already exist in the software depot!'));
						} elseif (preg_match('/double_res_/i', $rs->fields['swstatus'])) {
							$tpl->assign(
								array(
									'TR_NAME'				=> tr('Package already exist in the reseller depot!'),
									'LINK_COLOR' 			=> '#FF0000',
									'TR_VERSION' 			=> '',
									'TR_LANGUAGE' 			=> '',
									'TR_TOOLTIP' 			=> tr(
										'Check the reseller uploads!<br />It is not allowed to upload this package two times.<br />Refresh your site to see the new status!'
									),
									'TR_DOWNLOAD' 			=> '',
									'DOWNLOAD_LINK' 		=> '',
									'DELETE_LINK' 			=> '',
									'TR_DELETE' 			=> '',
									'TR_TYPE'				=> '<font color="#FF0000">' . tr('deleting') . '</font>',
									'TR_ADMIN'				=> $rs->fields['admin'],
									'SOFTWARE_ICON'			=> 'delete',
									'RIGHTS_LINK'			=> '',
									'TR_SOFTWARE_RIGHTS' 	=> '',
									'SOFTWARE_RIGHTS_LINK' 	=> ''
								)
							);

							$exist_software_id = substr (strrchr ($rs->fields['swstatus'], '_'), 1);
							$query="
								SELECT
									`a`.`reseller_id`,  `b`.`admin_id`,  `b`.`admin_name` as `resellername`
								FROM
									`web_software` a, `admin` b
								WHERE
									a.`software_id` = ?
								AND
									a.`reseller_id` = b.`admin_id`
								;	
							";

							$rs_res = exec_query($sql, $query, $exist_software_id);
							set_page_message(tr(
								'This package already exist in the depot of the reseller "%1$s"!',
								$rs_res->fields['resellername'])
							);
						}

						$del_path = $cfg->GUI_SOFTWARE_DEPOT_DIR . '/' . $rs->fields['filename'] . '-'.
							$rs->fields['id'] . '.tar.gz';

						@unlink($del_path);
						$delete="DELETE FROM `web_software` WHERE `software_id` = ?;";
						exec_query($sql, $delete, $rs->fields['id']);
					}
			}

			$tpl->parse('LIST_SOFTWAREDEPOT', '.list_softwaredepot');
			$rs->moveNext();
		}
		$tpl->assign('NO_SOFTWAREDEPOT_LIST', '');
	} else {
		$tpl->assign('NO_SOFTWAREDEPOT', tr('No software in software depot available!'));
		$tpl->parse('NO_SOFTWAREDEPOT_LIST', '.no_softwaredepot_list');
		$tpl->assign('LIST_SOFTWAREDEPOT', '');
	}
	return $rs->recordCount();
}

function get_reseller_software ($tpl, $sql) {

	$query="
		SELECT
			`t1`.`admin_id` as reseller_id, `t1`.`admin_name` as reseller
		FROM
			`admin` t1
		LEFT JOIN
			`reseller_props` AS `t2` ON `t2`.reseller_id = `t1`.`admin_id`
		WHERE
			`t1`.`admin_type` = 'reseller'
		AND
			`t2`.`software_allowed` = 'yes'
		ORDER BY
			`t1`.`admin_id` ASC
		;
	";

	$rs = exec_query($sql, $query, array());

	if ($rs->recordCount() > 0) {
		while(!$rs->EOF) {
			$query="SELECT `software_id` FROM `web_software` WHERE `reseller_id` = ?;";
			$rssoftware = exec_query($sql, $query, $rs->fields['reseller_id']);
			$software_ids = array();

			while ($data = $rssoftware->fetchRow()) {
				$software_ids[] = $data['software_id'];
			}

			$query="
				SELECT
					count(`software_id`) as swdepot
				FROM 
					`web_software`
				WHERE
					`software_active` = 1
				AND
					`software_depot` = 'yes'
				AND 
					`reseller_id` = ?
				;
			";

			$rscountswdepot = exec_query($sql, $query, $rs->fields['reseller_id']);

			$query="
				SELECT
					count(`software_id`) as waiting
				FROM
					`web_software`
				WHERE
					`software_active` = 0
				AND
					`reseller_id` = ?
				;
			";

			$rscountwaiting = exec_query($sql, $query, $rs->fields['reseller_id']);

			$query="
				SELECT
					count(`software_id`) as `activated`
				FROM
					`web_software`
				WHERE
					`software_active` = 1
				AND
					`reseller_id` = ?
				;
			";

			$rscountactivated = exec_query($sql, $query, $rs->fields['reseller_id']);

			if(count($software_ids) > 0){
				$query="
					SELECT
						count(`domain_id`) as `in_use`
					FROM
						`web_software_inst`
					WHERE
						`software_id`
					IN
						(".implode(',', $software_ids ).")
					AND
						`software_status` = 'ok'
					;
				";
				$rscountin_use = exec_query($sql, $query, array());
				$sw_in_use = $rscountin_use->fields['in_use'];
			}else{
				$sw_in_use = 0;
			}
			$tpl->assign(
				array(
					'RESELLER_NAME' 			=> $rs->fields['reseller'],
					'RESELLER_ID' 				=> $rs->fields['reseller_id'],
					'RESELLER_COUNT_SWDEPOT' 	=> $rscountswdepot->fields['swdepot'],
					'RESELLER_COUNT_WAITING' 	=> $rscountwaiting->fields['waiting'],
					'RESELLER_COUNT_ACTIVATED' 	=> $rscountactivated->fields['activated'],
					'RESELLER_SOFTWARE_IN_USE' 	=> $sw_in_use
					)
				);

			$tpl->parse('LIST_RESELLER', '.list_reseller');
			$rs->moveNext();
		}
		$tpl->assign('NO_RESELLER_LIST', '');
	} else {
		$tpl->assign('NO_RESELLER', tr('No reseller with activated software installer found!'));
		$tpl->parse('NO_RESELLER_LIST', '.no_reseller_list');
		$tpl->assign('LIST_RESELLER', '');
	}
	return $rs->recordCount();
}

$tpl->assign(
	array(
		'TR_MANAGE_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Application Management'),
		'THEME_COLOR_PATH'				=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'					=> tr('encoding'),
		'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
		)
);

$sw_cnt = get_avail_software($tpl, $sql);
$swdepot_cnt = get_avail_softwaredepot($tpl, $sql);
$res_cnt = get_reseller_software($tpl, $sql);

$tpl->assign(
	array(
		'TR_SOFTWARE_DEPOT' 			=> tr('Software depot'),
		'SOFTWARE_UPLOAD_TOKEN' 		=> generate_software_upload_token(),
		'TR_SOFTWARE_ADMIN' 			=> tr('Admin'),
		'TR_SOFTWARE_RIGHTS' 			=> tr('Softwarerights'),
		'TR_SOFTWAREDEPOT_COUNT' 		=> tr('Software depot total'),
		'TR_SOFTWAREDEPOT_NUM' 			=> $swdepot_cnt,
		'TR_UPLOAD_SOFTWARE' 			=> tr('Software depot upload'),
		'TR_SOFTWARE_FILE' 				=> tr('Choose file (Max: %1$d MB)', ini_get('upload_max_filesize')),
		'TR_SOFTWARE_URL' 				=> tr('or remote file (Max: %1$d MB)', formatFilesize($cfg->MAX_REMOTE_FILESIZE)),
		'TR_UPLOAD_SOFTWARE_BUTTON' 	=> tr('Upload now'),
		'TR_AWAITING_ACTIVATION' 		=> tr('Awaiting Activation'),
		'TR_ACTIVATED_SOFTWARE' 		=> tr('Reseller software list'),
		'TR_SOFTWARE_NAME' 				=> tr('Application'),
		'TR_SOFTWARE_VERSION' 			=> tr('App-Version'),
		'TR_SOFTWARE_LANGUAGE' 			=> tr('Language'),
		'TR_SOFTWARE_TYPE' 				=> tr('Type'),
		'TR_SOFTWARE_RESELLER' 			=> tr('Reseller'),
		'TR_SOFTWARE_IMPORT' 			=> tr('Depot import'),
		'TR_SOFTWARE_DOWNLOAD' 			=> tr('Download'),
		'TR_SOFTWARE_ACTIVATION' 		=> tr('Activate'),
		'TR_SOFTWARE_DELETE' 			=> tr('Delete'),
		'TR_SOFTWARE_ACT_COUNT'			=> tr('Software total'),
		'TR_SOFTWARE_ACT_NUM' 			=> $sw_cnt,
		'TR_RESELLER_NAME' 				=> tr('Reseller'),
		'TR_RESELLER_ACT_COUNT' 		=> tr('Reseller total'),
		'TR_RESELLER_ACT_NUM' 			=> $res_cnt,
		'TR_RESELLER_COUNT_SWDEPOT' 	=> tr('Software depot'),
		'TR_RESELLER_COUNT_WAITING' 	=> tr('Waiting for activation'),
		'TR_RESELLER_COUNT_ACTIVATED'	=> tr('Activated software'),
		'TR_RESELLER_SOFTWARE_IN_USE'	=> tr('Total installations'),
		'TR_MESSAGE_ACTIVATE' 			=> tr('Are you sure you want to activate this package?', true),
		'TR_MESSAGE_IMPORT' 			=> tr('Are you sure you want to import this package into the software depot?', true),
		'TR_MESSAGE_DELETE' 			=> tr('Are you sure you want to delete this package?', true),
		'TR_ADMIN_SOFTWARE_PAGE_TITLE'	=> tr('i-MSCP - Application Management')
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
