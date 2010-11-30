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
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/software_install.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('software_item', 'page');
$tpl->define_dynamic('show_domain_list', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('software_install', 'page');
$tpl->define_dynamic('no_software', 'page');
$tpl->define_dynamic('installdb_item', 'page');
$tpl->define_dynamic('select_installdb', 'page');
$tpl->define_dynamic('require_installdb', 'page');
$tpl->define_dynamic('select_installdbuser', 'page');
$tpl->define_dynamic('installdbuser_item', 'page');
$tpl->define_dynamic('softwaredbuser_message', 'page');
$tpl->define_dynamic('create_db', 'page');
$tpl->define_dynamic('create_message_db', 'page');

//
// form data
//

if (isset($_POST['Submit2'])) {
	$id = $_GET['id'];
	$domain_path = "";
	$other_dir = clean_input($_POST['other_dir'], true);
	$query = "
		SELECT
			`software_master_id`,
			`software_db`,
			`software_name`,
			`software_version`,
			`software_language`,
			`software_depot`
		FROM
			`web_software`
		WHERE
			`software_id` = ?
	";
	$rs = exec_query($sql, $query, $_GET['id']);
	$install_username = clean_input($_POST['install_username'], true);
	$install_password = clean_input($_POST['install_password'], true);
	$install_email = clean_input($_POST['install_email'], true);
	if(isset($_POST['createdir']) && $_POST['createdir'] === '1') {
		$createdir = clean_input($_POST['createdir'], true);
		set_page_message(tr('The directory -%1$s- was created!', $other_dir));
	} else {
		$createdir = '0';
	}
	//Check dir exists
    $sql = iMSCP_Registry::get('Db');
    $domain = $_SESSION['user_logged'];
    $vfs = new iMSCP_VirtualFileSystem($domain, $sql);
    $list = $vfs->ls($other_dir);
    //Check dir exists
	
	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$dmn_last_modified,
		$dmn_mailacc_limit,
		$dmn_ftpacc_limit,
		$dmn_traff_limit,
		$dmn_sqld_limit,
		$dmn_sqlu_limit,
		$dmn_status,
		$dmn_als_limit,
		$dmn_subd_limit,
		$dmn_ip_id,
		$dmn_disk_limit,
		$dmn_disk_usage,
		$dmn_php,
		$dmn_cgi) = get_domain_default_props($sql, $_SESSION['user_id']);
	
	
	$querypath = "
		SELECT
			`software_name` as swname,
			`software_version` as swversion
		FROM
			`web_software_inst`
		WHERE
			`domain_id` = ?
		AND
			`path` = ?
	";
	$rspath = exec_query($sql, $querypath, array($dmn_id, $other_dir));
	list ($posted_domain_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $posted_mountpath) = split(';', $_POST['selected_domain']);
	if(($posted_aliasdomain_id + $posted_subdomain_id + $posted_aliassubdomain_id) > 0){
		if($posted_aliasdomain_id > 0){
			$querydomainpath = "
				SELECT
					`alias_mount` as domainpath
				FROM
					`domain_aliasses`
				WHERE
					`alias_id` = ?
			";
			$rsdomainpath = exec_query($sql, $querydomainpath, $posted_aliasdomain_id);
			$domain_path = $rsdomainpath->fields['domainpath'];
			$domain_path = str_replace("/", "\/", $domain_path);
		} elseif($posted_subdomain_id > 0){
			$querydomainpath = "
				SELECT
					`subdomain_mount` as domainpath
				FROM
					`subdomain`
				WHERE
					`subdomain_id` = ?
			";
			$rsdomainpath = exec_query($sql, $querydomainpath, $posted_subdomain_id);
			$domain_path = $rsdomainpath->fields['domainpath'];
			$domain_path = str_replace("/", "\/", $domain_path);
		} elseif($posted_aliassubdomain_id > 0){
			$querydomainpath = "
				SELECT
					`subdomain_alias_mount` as domainpath
				FROM
					`subdomain_alias`
				WHERE
					`subdomain_alias_id` = ?
			";
			$rsdomainpath = exec_query($sql, $querydomainpath, $posted_aliassubdomain_id);
			$domain_path = $rsdomainpath->fields['domainpath'];
			$domain_path = str_replace("/", "\/", $domain_path);
		} else {
			$domain_path = $posted_mountpath;
		}
	} else {
		$domain_path = $posted_mountpath;
	}
	if($rs->fields['software_db'] == "1") {
		$selected_db = clean_input($_POST['selected_db'], true);
		$sql_user = clean_input($_POST['sql_user'], true);
		$querydbuser = "
			SELECT
				`sqlu_pass`
			FROM
				`sql_user`
			WHERE
				`sqlu_name` = ?
		";
		$rsdatabase = exec_query($sql, $querydbuser, $sql_user);
		$sql_pass = decrypt_db_password($rsdatabase->fields['sqlu_pass']);
		$connect = @mysql_connect($cfg->DATABASE_HOST, $sql_user, $sql_pass);
		$db_selected = @mysql_select_db($selected_db, $connect);
		$sql_pass = $rsdatabase->fields['sqlu_pass'];
	}
	if($rs->fields['software_db'] == "1" && !$db_selected) {		
		set_page_message(tr('Please select the correct user for your database!'));
	} elseif(empty($install_username) || empty($install_password) || empty($install_email)) {
		set_page_message(tr('You have to fill out inputs!'));
	} elseif (!chk_password($install_password)){
		if ($cfg->PASSWD_STRONG) {
			set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS));
		} else {
			set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS));
		}
	} elseif(!preg_match("/htdocs/",$other_dir)){
		set_page_message(tr('You cant\'t install outside from htdocs!'));
	} elseif(($posted_aliasdomain_id + $posted_subdomain_id + $posted_aliassubdomain_id) > 0 && !preg_match("/".$domain_path."/",$other_dir)){
		set_page_message(tr('You choose a directory, which doesn\'t match with the domain directory!'));
	} elseif(!$list && $createdir === '0'){
			set_page_message(tr('The directory -%1$s- doesn\'t exist. Please create it!', $other_dir));
	} elseif ($rspath->recordCount() > 0) {
		set_page_message(tr('Please select another directory! %1$s (%2$s) is installed there!', $rspath->fields['swname'], $rspath->fields['swversion']));
	} else {
		$sw_db_required = $rs->fields['software_db'];
		$sw_software_name = $rs->fields['software_name'];
		$sw_software_version = $rs->fields['software_version'];
		$software_master_id = $rs->fields['software_master_id'];
		$software_depot = $rs->fields['software_depot'];
		$software_language = $rs->fields['software_language'];
		
		
		$query = "
			SELECT
				`software_prefix`
			FROM
				`web_software`
			WHERE
				`software_id` = ?
		";
		$rs = exec_query($sql, $query, $_GET['id']);
		
		$prefix = $rs->fields['software_prefix'];
		if($sw_db_required == "1") {
			$query="
				INSERT INTO
					`web_software_inst`
						(
							`domain_id`, `alias_id`, `subdomain_id`, `subdomain_alias_id`, `software_id`,
							`software_master_id`, `software_name`, `software_version`, `software_language`, `path`, 
							`software_prefix`, `db`, `database_user`, `database_tmp_pwd`, `install_username`,
							`install_password`, `install_email`, `software_status`, `software_depot`
						)
				VALUES
						(
							?, ?, ?, ?, ?,
							?, ?, ?, ?, ?,
							?, ?, ?, ?, ?,
							?, ?, ?, ?
						)
			";
			$rs = exec_query($sql, $query, array($dmn_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $id, $software_master_id, $sw_software_name, $sw_software_version, $software_language, $other_dir, $prefix, $selected_db, $sql_user, $sql_pass, $install_username, encrypt_db_password($install_password), $install_email, $cfg->ITEM_ADD_STATUS, $software_depot));
		} else {
			$query="
				INSERT INTO
					`web_software_inst`
						(
							`domain_id`, `alias_id`, `subdomain_id`, `subdomain_alias_id`, `software_id`,
							`software_master_id`, `software_name`, `software_version`, `software_language`, `path`, 
							`software_prefix`, `db`, `database_user`, `database_tmp_pwd`, `install_username`,
							`install_password`, `install_email`, `software_status`, `software_depot`
						)
				VALUES
						(
							?, ?, ?, ?, ?,
							?, ?, ?, ?, ?,
							?, ?, ?, ?, ?,
							?, ?, ?, ?
						)
			";
			$rs = exec_query($sql, $query, array($dmn_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $id, $software_master_id, $sw_software_name, $sw_software_version, $software_language, $other_dir, "not_required", "not_required", "not_required", "not_required", $install_username, encrypt_db_password($install_password), $install_email, $cfg->ITEM_ADD_STATUS, $software_depot));
		}
		send_request();
		header('Location: software.php');
	}
	if($rs->fields['software_db'] == "1") {
		$tpl->assign(
				array(
					'VAL_OTHER_DIR' 			=> $other_dir,
					'CHECKED_CREATEDIR' 		=>  ($createdir === '1') ? $cfg->HTML_CHECKED : '',
					'VAL_INSTALL_USERNAME' 		=> $install_username,
					'VAL_INSTALL_PASSWORD' 		=> $install_password,
					'VAL_INSTALL_EMAIL' 		=> $install_email
				)
			);
	} else {
		$tpl->assign(
				array(
					'VAL_OTHER_DIR' 			=> $other_dir,
					'CHECKED_CREATEDIR' 		=>  ($createdir === '1') ? $cfg->HTML_CHECKED : '',
					'VAL_INSTALL_USERNAME' 		=> $install_username,
					'VAL_INSTALL_PASSWORD' 		=> $install_password,
					'VAL_INSTALL_EMAIL' 		=> $install_email
				)
			);
	}
} else {
	$tpl->assign(
			array(
				'VAL_OTHER_DIR' 		=> '/htdocs',
				'CHECKED_CREATEDIR' 	=>  '',
				'VAL_INSTALL_USERNAME' 	=> '',
				'VAL_INSTALL_PASSWORD' 	=> '',
				'VAL_INSTALL_EMAIL' 	=> ''
			)
		);
}

//
// page functions.
//

function gen_user_domain_list($tpl, $sql, $user_id) {
	global $selecteddomain;
	$domain_id = get_user_domain_id($sql, $user_id);
	
	//Get Domain Data
	$querydomain = "
		SELECT
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_status` = 'ok'
		AND
			`domain_id` = ?
	";
	$rsdomain = exec_query($sql, $querydomain, $domain_id);
	
	//Get Aliase
	$queryaliase = "
		SELECT
			`alias_id`,
			`alias_name`,
			`alias_mount`
		FROM
			`domain_aliasses`
		WHERE
			`alias_status` = 'ok'
		AND
			`url_forward` = 'no'
		AND
			`domain_id` = ?
	";
	$rsaliase = exec_query($sql, $queryaliase, $domain_id);
	
	//Get Subdomains
	$querysubdomain = "
		SELECT
			`subdomain_id`,
			`subdomain_name`,
			`subdomain_mount`,
			`domain`.`domain_name`
		FROM
			`subdomain` JOIN `domain`
		ON
			`subdomain`.`domain_id` = `domain`.`domain_id`
		WHERE
			`subdomain`.`subdomain_status` = 'ok'
		AND
			`subdomain`.`domain_id` = ?
	";
	$rssubdomain = exec_query($sql, $querysubdomain, $domain_id);
	
	//Get Subaliase
	$querysubaliase = "
		SELECT
			`subdomain_alias_id`,
			`subdomain_alias_name`,
			`subdomain_alias_mount`,
			`domain_aliasses`.`alias_name`
		FROM
			`subdomain_alias` JOIN `domain_aliasses`
		ON
			`subdomain_alias`.`alias_id` = `domain_aliasses`.`alias_id`
		WHERE
			`subdomain_alias`.`subdomain_alias_status` = 'ok'
		AND
			`domain_id` = ?
	";
	$rssubaliase = exec_query($sql, $querysubaliase, $domain_id);
	
	if (isset($_POST['selected_domain'])){
		list ($posted_domain_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $posted_mountpath) = explode(";", $_POST['selected_domain']);
	} else {
		$selecteddomain = '';
	}
	
	if (($rsaliase->recordCount() + $rssubdomain->recordCount() + $rssubaliase->recordCount()) > 0) {
		while (!$rsaliase->EOF) {
			if (isset($_POST['selected_domain']) && $posted_aliasdomain_id != 0){
				if($posted_aliasdomain_id == $rsaliase->fields['alias_id']) {
					$selecteddomain = $cfg->HTML_SELECTED;
				} else {
					$selecteddomain = '';
				}
			} else {
				$selecteddomain = '';
			}
			$tpl->assign(
				array(
					'SELECTED_DOMAIN' 		=> $selecteddomain,
					'DOMAIN_NAME_VALUES'	=> $domain_id.';'.$rsaliase->fields['alias_id'].';0;0;'.$rsaliase->fields['alias_mount'].'/htdocs',
					'DOMAIN_NAME'			=> decode_idna($rsaliase->fields['alias_name']),
				)
			);
			$tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
			$rsaliase->moveNext();
		}
		while (!$rssubdomain->EOF) {
			if (isset($_POST['selected_domain']) && $posted_subdomain_id != 0){
				if($posted_subdomain_id == $rssubdomain->fields['subdomain_id']) {
					$selecteddomain = $cfg->HTML_SELECTED;
				} else {
					$selecteddomain = '';
				}
			} else {
				$selecteddomain = '';
			}
			$subdomainname = $rssubdomain->fields['subdomain_name'].".".$rssubdomain->fields['domain_name'];
			$tpl->assign(
				array(
					'SELECTED_DOMAIN' 		=> $selecteddomain,
					'DOMAIN_NAME_VALUES' 	=> $domain_id.';0;'.$rssubdomain->fields['subdomain_id'].';0;'.$rssubdomain->fields['subdomain_mount'].'/htdocs',
					'DOMAIN_NAME'			=> decode_idna($subdomainname),
				)
			);
			$tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
			$rssubdomain->moveNext();
		}
		while (!$rssubaliase->EOF) {
			if (isset($_POST['selected_domain']) && $posted_aliassubdomain_id != 0){
				if($posted_aliassubdomain_id == $rssubaliase->fields['subdomain_alias_id']) {
					$selecteddomain = $cfg->HTML_SELECTED;
				} else {
					$selecteddomain = '';
				}
			} else {
				$selecteddomain = '';
			}
			$aliassubdomainname = $rssubaliase->fields['subdomain_alias_name'].".".$rssubaliase->fields['alias_name'];
			$tpl->assign(
				array(
					'SELECTED_DOMAIN' 		=> $selecteddomain,
					'DOMAIN_NAME_VALUES' 	=> $domain_id.';0;0;'.$rssubaliase->fields['subdomain_alias_id'].';'.$rssubaliase->fields['subdomain_alias_mount'].'/htdocs',
					'DOMAIN_NAME' 			=> decode_idna($aliassubdomainname),
				)
			);
			$tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
			$rssubaliase->moveNext();
		}
		$tpl->assign(
				array(
					'DOMAINSTANDARD_NAME_VALUES' 	=> $domain_id.';0;0;0;/htdocs',
					'DOMAINSTANDARD_NAME' 			=> decode_idna($rsdomain->fields['domain_name']),
				)
			);
	} else {
		$tpl->assign(
				array(
					'SELECTED_DOMAIN' 				=> $selecteddomain,
					'DOMAIN_NAME_VALUES' 			=> '',
					'DOMAIN_NAME' 					=> '',
					'DOMAINSTANDARD_NAME_VALUES' 	=> $domain_id.';0;0;0;/htdocs',
					'DOMAINSTANDARD_NAME' 			=> decode_idna($rsdomain->fields['domain_name']),
					'SHOW_DOMAIN_LIST' 				=> ''
				)
			);
	}
}
function check_db_user_list($tpl, $sql, $db_id) {
	global $count;
	$query = "
		SELECT
			`sqlu_id`, `sqlu_name`
		FROM
			`sql_user`
		WHERE
			`sqld_id` = ?
		ORDER BY
			`sqlu_name`
	";
	$rs = exec_query($sql, $query, $db_id);
	if ($rs->recordCount() == 0) {
		$tpl->assign(
				array(
					'STATUS_COLOR' 				=> 'red',
					'SQLUSER_STATUS_MESSAGE'	=> tr('Database user list is empty!'),
					'INSTALLDBUSER_ITEM'		=> '',
					'SELECT_INSTALLDBUSER'		=> ''
				)
			);
		$tpl->parse('SOFTWAREDBUSER_MESSAGE', 'softwaredbuser_message');
	} else {
		$tpl->assign(
				array(
					'SELECT_INSTALLDBUSER' 		=> '',
					'SOFTWAREDBUSER_MESSAGE'	=> ''
				)
			);

		while (!$rs->EOF) {
			if (isset($_POST['sql_user']) && $_POST['sql_user'] == $rs->fields['sqlu_name']){
				$selecteddbuser = $cfg->HTML_SELECTED;
				}else{
				$selecteddbuser = '';
			}
			$count++;
			$user_id = $rs->fields['sqlu_id'];
			$user_mysql = $rs->fields['sqlu_name'];
			$tpl -> assign(
					array(
						'SQLUSER_NAME' 		=> $user_mysql,
						'SELECTED_DBUSER'	=> $selecteddbuser
					)
				);
			$tpl -> parse('INSTALLDBUSER_ITEM', '.installdbuser_item');
			$rs->moveNext();
		}
		$tpl->parse('SELECT_INSTALLDBUSER', 'select_installdbuser');
	}
	return $count;
}

function check_db_avail(&$tpl, &$sql, $dmn_id, $dmn_sqld_limit) {
  $existdbuser = 0;
  $check_db = "
		SELECT
			`sqld_id`,
			`sqld_name`
		FROM
			`sql_database`
		WHERE
			`domain_id` = ?
		ORDER BY
			`sqld_name` ASC
	";
  $rs = exec_query($sql, $check_db, $dmn_id);
  if ($rs->recordCount() > 0) {
	while (!$rs->EOF) {
				if (isset($_POST['selected_db']) && $_POST['selected_db'] == $rs->fields['sqld_name']){
					$selecteddb = $cfg->HTML_SELECTED;
					}else{
					$selecteddb = '';
				}
				$tpl -> assign(
						array(
							'DB_NAME' 		=> $rs->fields['sqld_name'],
							'SELECTED_DB' 	=> $selecteddb
							)
						);
				$tpl->parse('INSTALLDB_ITEM', '.installdb_item');
				$existdbuser = check_db_user_list($tpl, $sql, $rs->fields['sqld_id']);
				$existdbuser = +$existdbuser;
				$rs->moveNext();
		}
		if($existdbuser == 0) {
			$tpl->assign('SOFTWARE_INSTALL', '');
		}
		$tpl -> assign(
					array(
						'ADD_DATABASE_MESSAGE' 	=> '',
						'CREATE_MESSAGE_DB' 	=> ''
					)
				);
		$tpl->parse('SELECT_INSTALLDB', 'select_installdb');
	} else {
		$tpl -> assign(
					array(
						'SELECT_INSTALLDBUSER' 		=> '',
						'SOFTWAREDBUSER_MESSAGE' 	=> '',
						'SELECT_INSTALLDB'			=> '',
						'ADD_DATABASE_MESSAGE' 		=> tr('At first you must create a database!'),
						'SOFTWARE_INSTALL'			=> ''
					)
				);
		$tpl -> parse('CREATE_MESSAGE_DB', '.create_message_db');
	}
  if($rs -> recordCount() < $dmn_sqld_limit OR $dmn_sqld_limit == 0) {
	$tpl -> assign(
				array(
					'ADD_DB_LINK' 	=> 'sql_database_add.php',
					'BUTTON_ADD_DB' => tr('Add new database')
				)
			);
	$tpl -> parse('CREATE_DB', '.create_db');
  } else {
	$tpl -> assign(
				array(
					'CREATE_MESSAGE_DB' => '',
					'ADD_DB_LINK' 		=> '',
					'BUTTON_ADD_DB' 	=> '',
					'CREATE_DB' 		=> ''
				)
			);
  }
}
	
function check_software_avail($sql, $software_id, $dmn_created_id) {
  $check_avail = "
		SELECT
			`reseller_id` AS reseller
		FROM
			`web_software`
		WHERE
			`software_id` = ?
		AND
			`reseller_id` = ?
	";
  $sa = exec_query($sql, $check_avail, array($software_id, $dmn_created_id));
  if ($sa -> recordCount() == 0) {
	return FALSE;
  } else {
	return TRUE;
  }
}

function check_is_installed(&$tpl, &$sql, $dmn_id, $software_id) {
  $is_installed = "
		SELECT
			`software_id`
		FROM
			`web_software_inst`
		WHERE
			`domain_id` = ?
		AND
			`software_id` = ?
	";
  $is_inst = exec_query($sql, $is_installed, array($dmn_id, $software_id));
  if ($is_inst -> recordCount() == 0) {
	$tpl -> assign ('SOFTWARE_INSTALL_BUTTON', 'software_install.php?id='.$software_id);
	$tpl -> parse('SOFTWARE_INSTALL', '.software_install');
  } else {
	$tpl -> assign ('SOFTWARE_INSTALL', '');
  }
}

function get_software_props ($tpl, $sql, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit) {
  if (!check_software_avail($sql, $software_id, $dmn_created_id)) {
	set_page_message(tr('Software not found!'));
	header('Location: software.php');
	exit;
  } else {
	gen_user_domain_list($tpl, $sql, $_SESSION['user_id']);
	$software_props = "
		SELECT
			`software_name`,
			`software_type`,
			`software_db`
		FROM
			`web_software`
		WHERE
			`software_id` = ?
		AND
			`reseller_id` = ?
	";
	$rs = exec_query($sql, $software_props, array($software_id, $dmn_created_id));
	check_is_installed($tpl, $sql, $dmn_id, $software_id);
	if ($rs -> fields['software_db'] == 1) {
		$tpl -> assign ('SOFTWARE_DB', tr('yes'));
		if ($dmn_sqld_limit == '-1') { 
			$tpl -> parse('REQUIRE_INSTALLDB', '.require_installdb');
		}
		check_db_avail($tpl, $sql, $dmn_id, $dmn_sqld_limit);
 	} else {
		$tpl -> assign (
					array(
						'SOFTWARE_DB' 			=> tr('no'),
						'REQUIRE_INSTALLDB' 	=> ''
					)
				);
	}
	$tpl -> assign (
				array(
					'TR_SOFTWARE_NAME' 	=> $rs -> fields['software_name'],
					'SOFTWARE_TYPE' 	=> $rs -> fields['software_type']
				)
			);
	$tpl -> parse('SOFTWARE_ITEM', '.software_item');
  }
}

function gen_page_lists($tpl, $sql, $user_id) {
	if (!isset($_GET['id']) || $_GET['id'] === '' || !is_numeric($_GET['id'])) {
		set_page_message(tr('Software not found!'));
		header('Location: software.php');
		exit;
	} else {
		$software_id = $_GET['id'];
	}
    list($dmn_id,$dmn_name,,,$dmn_created_id,,,,,,$dmn_sqld_limit) = get_domain_default_props($sql, $user_id);
	get_software_props ($tpl, $sql, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit);
	return $software_id;
}

$tpl -> assign(
			array(
				'TR_CLIENT_INSTALL_SOFTWARE_PAGE_TITLE' => tr('ispCP - Install Software'),
				'THEME_COLOR_PATH' 						=> "../themes/{$cfg->USER_INITIAL_THEME}",
				'THEME_CHARSET' 						=> tr('encoding'),
				'ISP_LOGO'								=> get_logo($_SESSION['user_id'])
			)
		);

//
// dynamic page data.
//

$software_id = gen_page_lists($tpl, $sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_webtools.tpl');

gen_logged_from($tpl);

get_client_software_permission ($tpl, $sql,$_SESSION['user_id']);

check_permissions($tpl);


$tpl -> assign(
	array(
		'TR_SOFTWARE_MENU_PATH'			=> tr('i-MSCP - application installer'),
		'TR_INSTALL_SOFTWARE'			=> tr('Install Software'),
		'SOFTWARE_ID'					=> $software_id,
		'TR_NAME'						=> tr('Software'),
		'TR_TYPE'						=> tr('Type'),
		'TR_DB'							=> tr('Database required'),
		'TR_SELECT_DOMAIN'				=> tr('Select Domain'),
		'TR_BACK'						=> tr('back'),
		'TR_INSTALL'					=> tr('install'),
		'TR_PATH'						=> tr('Install path'),
		'CHOOSE_DIR'					=> tr('Choose dir'),
		'CREATEDIR_MESSAGE'				=> tr('Create directory, if not exist!'),
		'TR_SELECT_DB'					=> tr('Select database'),
		'TR_SQL_USER'					=> tr('SQL-User'),
		'TR_SQL_PWD'					=> tr('Password'),
		'TR_SOFTWARE_MENU'				=> tr('Software installation'),
		'TR_CLIENT_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Application Management')
	)
);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
