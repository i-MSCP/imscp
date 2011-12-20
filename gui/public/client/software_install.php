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
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @author		iMSCP Team
 * @author		Sacha Bay <sascha.bay@i-mscp.net>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/************************************************************************************
 *  Functions
 */

/**
 * @param $tpl
 * @param $user_id
 * @return
 */
function gen_page_lists($tpl, $user_id) {
	if (!isset($_GET['id']) || $_GET['id'] === '' || !is_numeric($_GET['id'])) {
		set_page_message(tr('Software not found!'), 'error');
		redirectTo('software.php');
		exit;
	} else {
		$software_id = $_GET['id'];
	}
    list($dmn_id,$dmn_name,,,$dmn_created_id,,,,,,$dmn_sqld_limit) = get_domain_default_props($user_id);
	get_software_props_install ($tpl, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit);
	return $software_id;
}

/************************************************************************************
 * Main program
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('aps')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/software_install.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('software_item', 'page');
$tpl->define_dynamic('show_domain_list', 'page');
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

if (isset($_POST['Submit2'])) {
	$id = $_GET['id'];
	$domain_path = "";
	$other_dir = clean_input($_POST['other_dir'], true);

	$query = "
		SELECT
			`software_master_id`, `software_db`, `software_name`, `software_version`,
			`software_language`, `software_depot`
		FROM
			`web_software`
		WHERE
			`software_id` = ?
	";
	$rs = exec_query($query, $_GET['id']);

	$install_username = clean_input($_POST['install_username'], true);
	$install_password = clean_input($_POST['install_password'], true);
	$install_email = clean_input($_POST['install_email'], true);
	if(isset($_POST['createdir']) && $_POST['createdir'] === '1') {
		$createdir = clean_input($_POST['createdir'], true);
		set_page_message(tr('The directory -%1$s- was created.', $other_dir), 'success');
	} else {
		$createdir = '0';
	}
	//Check dir exists
    $domain = $_SESSION['user_logged'];
    $vfs = new iMSCP_VirtualFileSystem($domain);
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
		$dmn_cgi) = get_domain_default_props($_SESSION['user_id']);


	$querypath = "
		SELECT
			`software_name` as swname, `software_version` as swversion
		FROM
			`web_software_inst`
		WHERE
			`domain_id` = ?
		AND
			`path` = ?
	";
	$rspath = exec_query($querypath, array($dmn_id, $other_dir));

	list ($posted_domain_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $posted_mountpath) = explode(';', $_POST['selected_domain']);
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
			$rsdomainpath = exec_query($querydomainpath, $posted_aliasdomain_id);
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
			$rsdomainpath = exec_query($querydomainpath, $posted_subdomain_id);
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
			$rsdomainpath = exec_query($querydomainpath, $posted_aliassubdomain_id);
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
		$querydbuser = "SELECT `sqlu_pass` FROM `sql_user` WHERE `sqlu_name` = ?";
		$rsdatabase = exec_query($querydbuser, $sql_user);

		$db_connection_ok = check_db_connection($selected_db, $sql_user, $rsdatabase->fields['sqlu_pass']);
		$sql_pass = $rsdatabase->fields['sqlu_pass'];
	}

	if($rs->fields['software_db'] == "1" && !$db_connection_ok) {
		set_page_message(tr('Please select the correct user for your database!'), 'error');
	} elseif(empty($install_username) || empty($install_password) || empty($install_email)) {
		set_page_message(tr('You have to fill out inputs!'), 'error');
	} elseif (!chk_password($install_password)){
		if ($cfg->PASSWD_STRONG) {
			set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'), $cfg->PASSWD_CHARS), 'error');
		} else {
			set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs!'), $cfg->PASSWD_CHARS), 'error');
		}
	} elseif(!preg_match("/htdocs/",$other_dir)){
		set_page_message(tr('You cant\'t install outside from htdocs!'), 'error');
	} elseif(($posted_aliasdomain_id + $posted_subdomain_id + $posted_aliassubdomain_id) > 0 && !preg_match("/".$domain_path."/",$other_dir)){
		set_page_message(tr('You choose a directory, which doesn\'t match with the domain directory!'), 'error');
	} elseif(!$list && $createdir === '0'){
			set_page_message(tr('The directory -%1$s- doesn\'t exist. Please create it!', $other_dir), 'error');
	} elseif ($rspath->recordCount() > 0) {
		set_page_message(tr('Please select another directory! %1$s (%2$s) is installed there!', $rspath->fields['swname'], $rspath->fields['swversion']), 'error');
	} else {
		$sw_db_required = $rs->fields['software_db'];
		$sw_software_name = $rs->fields['software_name'];
		$sw_software_version = $rs->fields['software_version'];
		$software_master_id = $rs->fields['software_master_id'];
		$software_depot = $rs->fields['software_depot'];
		$software_language = $rs->fields['software_language'];


		$query = "SELECT `software_prefix` FROM `web_software` WHERE `software_id` = ?";
		$rs = exec_query($query, $_GET['id']);

		$prefix = $rs->fields['software_prefix'];
		if($sw_db_required == "1") {
			$query="
				INSERT INTO `web_software_inst` (
                    `domain_id`, `alias_id`, `subdomain_id`, `subdomain_alias_id`,
                    `software_id`, `software_master_id`, `software_name`,
                    `software_version`, `software_language`, `path`, `software_prefix`,
                    `db`, `database_user`, `database_tmp_pwd`, `install_username`,
                    `install_password`, `install_email`, `software_status`, `software_depot`
				) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
			";
			$rs = exec_query($query, array($dmn_id, $posted_aliasdomain_id,
                                          $posted_subdomain_id,
                                          $posted_aliassubdomain_id, $id,
                                          $software_master_id, $sw_software_name,
                                          $sw_software_version, $software_language,
                                          $other_dir, $prefix, $selected_db,
                                          $sql_user, $sql_pass, $install_username,
                                          $install_password,
                                          $install_email, $cfg->ITEM_ADD_STATUS,
                                          $software_depot));
		} else {
			$query="
				INSERT INTO `web_software_inst` (
                    `domain_id`, `alias_id`, `subdomain_id`, `subdomain_alias_id`,
                    `software_id`, `software_master_id`, `software_name`,
                    `software_version`, `software_language`, `path`, `software_prefix`,
                    `db`, `database_user`, `database_tmp_pwd`, `install_username`,
                    `install_password`, `install_email`, `software_status`, `software_depot`
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
			";
			$rs = exec_query($query, array($dmn_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $id, $software_master_id, $sw_software_name, $sw_software_version, $software_language, $other_dir, "not_required", "not_required", "not_required", "not_required", $install_username, $install_password, $install_email, $cfg->ITEM_ADD_STATUS, $software_depot));
		}
		send_request();
		redirectTo('software.php');
	}

	if($rs->fields['software_db'] == "1") {
		$tpl->assign(
			array(
				 'VAL_OTHER_DIR' => $other_dir,
				 'CHECKED_CREATEDIR' => ($createdir === '1') ? $cfg->HTML_CHECKED
					 : '',
				 'VAL_INSTALL_USERNAME' => $install_username,
				 'VAL_INSTALL_PASSWORD' => $install_password,
				 'VAL_INSTALL_EMAIL' => $install_email
			)
		);
	} else {
		$tpl->assign(
			array(
				 'VAL_OTHER_DIR' => $other_dir,
				 'CHECKED_CREATEDIR' => ($createdir === '1') ? $cfg->HTML_CHECKED
					 : '',
				 'VAL_INSTALL_USERNAME' => $install_username,
				 'VAL_INSTALL_PASSWORD' => $install_password,
				 'VAL_INSTALL_EMAIL' => $install_email
			)
		);
	}
} else {
	$tpl->assign(
		array(
			 'VAL_OTHER_DIR' => '/htdocs',
			 'CHECKED_CREATEDIR' => '',
			 'VAL_INSTALL_USERNAME' => '',
			 'VAL_INSTALL_PASSWORD' => '',
			 'VAL_INSTALL_EMAIL' => ''
		)
	);
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Install Software'),
		 'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()
	)
);


$software_id = gen_page_lists($tpl, $_SESSION['user_id']);

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_webtools.tpl');
get_client_software_permission ($tpl, $_SESSION['user_id']);

$tpl -> assign(
	array(
		 'TR_SOFTWARE_MENU_PATH' => tr('i-MSCP - application installer'),
		 'TR_INSTALL_SOFTWARE' => tr('Install Software'),
		 'SOFTWARE_ID' => $software_id,
		 'TR_NAME' => tr('Software'),
		 'TR_TYPE' => tr('Type'),
		 'TR_DB' => tr('Database required'),
		 'TR_SELECT_DOMAIN' => tr('Select Domain'),
		 'TR_BACK' => tr('back'),
		 'TR_INSTALL' => tr('install'),
		 'TR_PATH' => tr('Install path'),
		 'CHOOSE_DIR' => tr('Choose dir'),
		 'CREATEDIR_MESSAGE' => tr('Create directory, if not exist!'),
		 'TR_SELECT_DB' => tr('Select database'),
		 'TR_SQL_USER' => tr('SQL-User'),
		 'TR_SQL_PWD' => tr('Password'),
		 'TR_SOFTWARE_MENU' => tr('Software installation'),
		 'TR_CLIENT_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Application Management')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
