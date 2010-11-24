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
$tpl->define_dynamic('page', $cfg->RESELLER_TEMPLATE_PATH . '/software_upload.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('list_software', 'page');
$tpl->define_dynamic('no_software_list', 'page');
$tpl->define_dynamic('t_software_support', 'page');

// form data

function formatFilesize($byte) {
	$string = "Byte";
	if($byte>1024) {
		$byte/=1024;
		$string="KB";
	}
	if($byte>1024) {
		$byte/=1024;
		$string="MB";
	}
	if($byte>1024) {
		$byte/=1024;
		$string="GB";
	}
	if(number_format($byte,0)!=$byte) $byte=number_format($byte,2);
	return $byte." ".$string;
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
			$fname = substr($_POST['sw_wget'], (strrpos($_POST['sw_wget'], '/')+1));
		}
		$filename = substr($fname, 0, -7);
		$extension = substr($fname, -7);
			$query="
				INSERT INTO
					`web_software`
						(
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
							`software_status`
						)
				VALUES
						(
							?, ?, ?, ?, ?, ?,
							?, ?, ?, ?, ?, ?
						)
			";
		$rs = exec_query($sql, $query, array(
							$user_id, 
							"waiting_for_input", 
							"waiting_for_input", 
							"waiting_for_input", 
							"waiting_for_input", 
							"0", 
							$filename, 
							"waiting_for_input", 
							"waiting_for_input", 
							"waiting_for_input", 
							"waiting_for_input", 
							"toadd"
							)
						);
		$sw_id = $sql->insertId();
		if ($file == 0) {
			$dest_dir = $cfg->GUI_SOFTWARE_DIR.'/'.$user_id.'/'.$filename.'-'.$sw_id.$extension;
			if (!is_dir($cfg->GUI_SOFTWARE_DIR.'/'.$user_id)) {
				@mkdir($cfg->GUI_SOFTWARE_DIR.'/'.$user_id,0755,true);
			}
			if (!move_uploaded_file($_FILES['sw_file']['tmp_name'], $dest_dir)) {
				// Delete software entry
				$query = "
					DELETE FROM
						`web_software`
					WHERE
						`software_id` = ?
				";
				exec_query($sql, $query, array($sw_id));
				$sw_wget = "";
				set_page_message(tr('ERROR: Could not upload the file. Max. upload filesize (%1$d MB) reached?', ini_get('upload_max_filesize')));
				$upload = 0;
			}
		}
		if ($file == 1) {
			$sw_wget = $_POST['sw_wget'];
			$dest_dir = $cfg->GUI_SOFTWARE_DIR.'/'.$user_id.'/'.$filename.'-'.$sw_id.$extension;
			// Reading Filesize
   			$parts = parse_url($sw_wget);
   			$connection = fsockopen($parts['host'],80,$errno,$errstr,30);
   			if($connection) {
   				fputs($connection,"GET ".$sw_wget." HTTP/1.1\r\nHost: ".$parts['host']."\r\n\r\n");
   				$size = 0;
   				while(!isset($length) || ($size <= 500 && !feof($connection))) {
   					$tstr = fgets($connection,128);
   					$size += strlen($tstr);
   					if(substr($tstr,0,14) == 'Content-Length') {
   						$length = substr($tstr,15);
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
					$query = "
						DELETE FROM
							`web_software`
						WHERE
							`software_id` = ?
					";
					exec_query($sql, $query, $sw_id);
					$show_max_remote_filesize = formatFilesize($cfg->MAX_REMOTE_FILESIZE);
					set_page_message(tr('ERROR: Your remote filesize (%1$d B) is lower than 1 Byte. Please check your URL!', $show_remote_file_size));
					$upload = 0;
				} elseif($remote_file_size > $cfg->MAX_REMOTE_FILESIZE) {
					// Delete software entry
					$query = "
						DELETE FROM
							`web_software`
						WHERE
							`software_id` = ?
					";
					exec_query($sql, $query, $sw_id);
					$show_max_remote_filesize = formatFilesize($cfg->MAX_REMOTE_FILESIZE);
					set_page_message(tr('ERROR: Max. remote filesize (%1$d MB) is reached. Your remote file ist %2$d MB', $show_max_remote_filesize, $show_remote_file_size));
					$upload = 0;
				} else {
					$remote_file = @file_get_contents($sw_wget);
					if($remote_file) {
						$output_file = fopen($dest_dir,'w+');
						fwrite($output_file,$remote_file);
						fclose($output_file);
					} else {
						// Delete software entry
						$query = "
							DELETE FROM
								`web_software`
							WHERE
								`software_id` = ?
						";
						exec_query($sql, $query, $sw_id);
						set_page_message(tr('ERROR: Remote File not found!'));
						$upload = 0;
					}
				}
   			}else{
				// Delete software entry
				$query = "
					DELETE FROM
						`web_software`
					WHERE
						`software_id` = ?
				";
				exec_query($sql, $query, $sw_id);
				set_page_message(tr('ERROR: Could not upload the file. File not found!'));
				$upload = 0;
			}
		}
		if ($upload == 1) {
			$tpl->assign(
						array(
							'VAL_WGET'	=> '',
							'SW_INSTALLED'	=> ''
						)
					);
			send_request();
			set_page_message(tr('File was successfully uploaded.'));
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

// Begin function block
function get_avail_software (&$tpl, &$sql, $user_id) {
	global $cfg;
	$query = "
		SELECT
			`software_allowed`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";

    $rs = exec_query($sql, $query, $user_id);
    $software_allowed = $rs->fields('software_allowed');
	
	if ($software_allowed == 'yes') {
		if (isset($_GET['sortby']) && isset($_GET['order'])) {
			if ($_GET['order'] === "asc" || $_GET['order'] === "desc") {
				if ($_GET['sortby'] === "name") {
					$ordertype = "`software_name` ".$_GET['order'];
				} elseif ($_GET['sortby'] === "status") {
					$ordertype = "`software_active` ".$_GET['order'];
				} elseif ($_GET['sortby'] === "language") {
					$ordertype = "`software_language` ".$_GET['order'];
				} elseif ($_GET['sortby'] === "type") {
					$ordertype = "`software_type` ".$_GET['order'];
				} else {
					$ordertype = "`software_active` ASC, `software_type` ASC";
				}
			} else {
				$ordertype = "`software_active` ASC, `software_type` ASC";
			}
		} else {
			$ordertype = "`software_active` ASC, `software_type` ASC";
		}
		$query="
			SELECT
				`software_id` as id,
				`reseller_id` as resellerid,
				`software_name` as name,
				`software_version` as version,
				`software_language` as language,
				`software_desc` as description,
				`software_type` as type,
				`software_active` as swactive,
				`software_archive` as filename,
				`software_status` as swstatus,
				`software_depot` as softwaredepot
			FROM
				`web_software`
			WHERE
				`reseller_id` = ?
			ORDER BY
				$ordertype
		";
				
		$rs = exec_query($sql, $query, $user_id);
		if ($rs->recordCount() > 0) {
			while(!$rs->EOF) {
				if($rs->fields['swstatus'] == "ok" || $rs->fields['swstatus'] == "ready") {
					if($rs->fields['swstatus'] == "ready") {
						$updatequery = "
							UPDATE
								`web_software`
							SET	`software_status` = 'ok'
							WHERE
								`software_id` = ?
						";
						exec_query($sql, $updatequery, $rs->fields['id']);
						send_new_sw_upload ($user_id,$rs->fields['filename'].".tar.gz",$rs->fields['id']);
						set_page_message(tr('Package installed successfully... Awaiting release from Admin!'));
					}
					$url = "software_delete.php?id=".$rs->fields['id'];
					
					$query2="
						SELECT
							`domain`.`domain_id` as did,
							`domain`.`domain_name` as domain,
							`web_software_inst`.`domain_id` as wdid,
							`web_software_inst`.`software_id` as sid,
							`web_software`.`software_id` as wsid
						FROM
							`domain`,
							`web_software`,
							`web_software_inst`
						WHERE
							`web_software_inst`.`software_id` = ?
						AND
							`web_software`.`software_id` = `web_software_inst`.`software_id`
						AND
							`domain`.`domain_id` = `web_software_inst`.`domain_id`
					";
					$rs2 = exec_query($sql, $query2, $rs->fields['id']);
					if ($rs2->recordCount() > 0) {
						$swinstalled_domain = tr('This software is installed on following domain(s):');
						$swinstalled_domain .= "<ul>";
						while(!$rs2->EOF) {
							$swinstalled_domain .= "<li>".$rs2->fields['domain']."</li>";
							$rs2->moveNext();
						}
						$swinstalled_domain .= "</ul>";
						$tpl->assign('SW_INSTALLED', $swinstalled_domain);
					} else {
						$tpl->assign('SW_INSTALLED', tr('This package is not installed yet'));
					}
					
					$tpl->assign(
							array(
								'SW_NAME' 				=> $rs->fields['name'],
								'LINK_COLOR' 			=> '#000000',
								'SW_VERSION' 			=> $rs->fields['version'],
								'SW_LANGUAGE' 			=> $rs->fields['language'],
								'SW_DESCRIPTION' 		=> $rs->fields['description'],
								'SW_TYPE' 				=> $rs->fields['type'],
								'DELETE' 				=> $url,
								'TR_DELETE' 			=> tr('Delete'),
								'WAITING_SOFTWARE_LIST' => '',
								'SOFTWARE_ICON' 		=> 'delete'
							)
						);
					if ($rs->fields['swactive'] == "0"){
						$tpl->assign('SW_STATUS', tr('waiting for activation'));
					} 
					elseif ($rs->fields['swactive'] == "1" && $rs->fields['softwaredepot'] == "yes"){
						$tpl->assign('SW_STATUS', tr('activated (Softwaredepot)'));
					}
					else {
						$tpl->assign('SW_STATUS', tr('activated'));
					}
				} else {
					if($rs->fields['swstatus'] == "toadd") {
						$url = "software_delete.php?id=".$rs->fields['id'];
						$tpl->assign(
								array(
									'SW_NAME'			=> tr('Installing your uploaded package. Please refresh the site.'),
									'LINK_COLOR' 		=> '#FF0000',
									'SW_VERSION' 		=> '',
									'SW_LANGUAGE'		=> '',
									'SW_DESCRIPTION'	=> tr('After your upload the package it will be installed on your systems.<br />Refresh your site to see the new status!'),
									'SW_TYPE' 			=> '',
									'DELETE' 			=> $url,
									'TR_DELETE' 		=> tr('Delete'),
									'SW_STATUS' 		=> tr('installing'),
									'SOFTWARE_ICON' 	=> 'disabled'
								)
							);
					} else {
						if($rs->fields['swstatus'] == "delete") {
							$tpl->assign(
									array(
										'SW_NAME' 			=> tr('Failure in package. Deleting!'),
										'LINK_COLOR' 		=> '#FF0000',
										'SW_VERSION' 		=> '',
										'SW_LANGUAGE' 		=> '',
										'SW_DESCRIPTION'	=> tr('Check your package. There is an error inside!<br />Refresh your site to see the new status!'),
										'SW_TYPE' 			=> '',
										'DELETE' 			=> '',
										'TR_DELETE' 		=> '',
										'SW_STATUS' 		=> tr('deleting'),
										'SOFTWARE_ICON' 	=> 'disabled'
									)
								);
							set_page_message(tr('The package is corrupt. Please correct it!'));
						} elseif (preg_match("/double_depot_/i", $rs->fields['swstatus'])) {
							$tpl->assign(
									array(
										'SW_NAME' 			=> tr('Package already exist in the software depot!'),
										'LINK_COLOR' 		=> '#FF0000',
										'SW_VERSION' 		=> '',
										'SW_LANGUAGE'	 	=> '',
										'SW_DESCRIPTION' 	=> tr('Please contact the administrator!<br />Ask him for the permissions to use this package.<br />It is not allowed to upload this packet two times.<br />Refresh your site to see the new status!'),
										'SW_TYPE' 			=> '',
										'DELETE' 			=> '',
										'TR_DELETE' 		=> '',
										'SW_STATUS' 		=> tr('deleting'),
										'SOFTWARE_ICON' 	=> 'disabled'
									)
								);
							set_page_message(tr('This package already exist in the administrator software depot!'));
						} elseif (preg_match("/double_res_/i", $rs->fields['swstatus'])) {
							$tpl->assign(
									array(
										'SW_NAME' 			=> tr('Package already exist in your software depot!'),
										'LINK_COLOR' 		=> '#FF0000',
										'SW_VERSION' 		=> '',
										'SW_LANGUAGE' 		=> '',
										'SW_DESCRIPTION' 	=> tr('Check your own uploads!<br />Ask the administrator if you don\'t find the package.<br />It is not allowed to upload this packages two times.<br />Refresh your site to see the new status!'),
										'SW_TYPE' 			=> '',
										'DELETE' 			=> '',
										'TR_DELETE' 		=> '',
										'SW_STATUS' 		=> tr('deleting'),
										'SOFTWARE_ICON' 	=> 'disabled'
									)
								);
							set_page_message(tr('This package already exist in your software depot!'));
						}
						$del_path = $cfg->GUI_SOFTWARE_DIR."/".$rs->fields['resellerid']."/".$rs->fields['filename']."-".$rs->fields['id'].".tar.gz";
						@unlink($del_path);
						$delete = "
							DELETE FROM
								`web_software`
							WHERE
								`software_id` = ?
						";
						$res = exec_query($sql, $delete, $rs->fields['id']);
					}
				}
				$tpl->parse('LIST_SOFTWARE', '.list_software');
				$rs->moveNext();
			}
			$tpl->assign('NO_SOFTWARE_LIST', '');
		} else {
			$tpl->assign(
					array(
						'NO_SOFTWARE' 	=> tr('You do not have any software uploaded yet'),
						'LIST_SOFTWARE'	=> ''
					)
				);
			$tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
		}
		return $rs->recordCount();
	} else {
		$tpl->assign(
				array(
					'NO_SOFTWARE' 	=> tr('You do not have permissions to upload software'),
					'LIST_SOFTWARE' => ''
				)
			);
		$tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
		return 0;
	}
}

$tpl->assign(
		array(
			'TR_MANAGE_SOFTWARE_PAGE_TITLE'	=> tr('i-MSCP - Application Management'),
			'THEME_COLOR_PATH'				=> "../themes/{$cfg->USER_INITIAL_THEME}",
			'THEME_CHARSET'					=> tr('encoding'),
			'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
		)
	);

$sw_cnt = get_avail_software (&$tpl, &$sql, $_SESSION['user_id']);

$tpl->assign(
		array(
			"GENERAL_INFO"					=> tr("General information"),
			'TR_UPLOADED_SOFTWARE' 			=> tr('Software available'),
			'TR_SOFTWARE_NAME'				=> tr('Software-Synonym'),
			'TR_SOFTWARE_VERSION' 			=> tr('Software-Version'),
			'TR_SOFTWARE_LANGUAGE' 			=> tr('Language'),
			'TR_SOFTWARE_STATUS' 			=> tr('Software status'),
			'TR_SOFTWARE_TYPE' 				=> tr('Type'),
			'TR_SOFTWARE_DELETE' 			=> tr('Action'),
			'TR_SOFTWARE_COUNT' 			=> tr('Software total'),
			'TR_SOFTWARE_NUM' 				=> $sw_cnt,
			'TR_UPLOAD_SOFTWARE' 			=> tr('Software upload'),
			'TR_SOFTWARE_DB' 				=> tr('Requires Database?'),
			'TR_SOFTWARE_DB_PREFIX' 		=> tr('Database prefix'),
			'TR_SOFTWARE_HOME' 				=> tr('Link to authors homepage'),
			'TR_SOFTWARE_DESC' 				=> tr('Description'),
			'SOFTWARE_UPLOAD_TOKEN' 		=> generate_software_upload_token(),
			'TR_SOFTWARE_FILE' 				=> tr('Choose file (Max: %1$d MB)', ini_get('upload_max_filesize')),
			'TR_SOFTWARE_URL' 				=> tr('or remote file (Max: %1$d MB)', formatFilesize($cfg->MAX_REMOTE_FILESIZE)),
			'TR_UPLOAD_SOFTWARE_BUTTON' 	=> tr('Upload now'),
			'TR_UPLOAD_SOFTWARE_PAGE_TITLE'	=> tr('i-MSCP - Application Management'),
			'TR_MESSAGE_DELETE' 			=> tr('Are you sure you want to delete this package?', true),
			'TR_SOFTWARE_NAME_ASC' 			=> 'software_upload.php?sortby=name&order=asc',
			'TR_SOFTWARE_NAME_DESC' 		=> 'software_upload.php?sortby=name&order=desc',
			'TR_SOFTWARE_TYPE_ASC' 			=> 'software_upload.php?sortby=type&order=asc',
			'TR_SOFTWARE_TYPE_DESC' 		=> 'software_upload.php?sortby=type&order=desc',
			'TR_SOFTWARE_STATUS_ASC' 		=> 'software_upload.php?sortby=status&order=asc',
			'TR_SOFTWARE_STATUS_DESC' 		=> 'software_upload.php?sortby=status&order=desc',
			'TR_LANGUAGE_ASC' 				=> 'software_upload.php?sortby=language&order=asc',
			'TR_LANGUAGE_DESC' 				=> 'software_upload.php?sortby=language&order=desc'
		)
	);

gen_reseller_mainmenu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/main_menu_general_information.tpl');
gen_reseller_menu($tpl, $cfg->RESELLER_TEMPLATE_PATH . '/menu_general_information.tpl');

gen_logged_from($tpl);

get_reseller_software_permission (&$tpl,&$sql,$_SESSION['user_id']);

gen_page_message($tpl);

$tpl->assign('LAYOUT', '');
$tpl->parse('PAGE', 'page');
$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
?>
