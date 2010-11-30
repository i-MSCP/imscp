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
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/software_view.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('logged_from', 'page');
$tpl -> define_dynamic('software_message', 'page');
$tpl -> define_dynamic('software_install', 'page');
$tpl -> define_dynamic('installed_software_info', 'page');
$tpl -> define_dynamic('software_item', 'page');
$tpl -> define_dynamic('no_software', 'page');

//
// page functions.
//

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

function check_is_installed($tpl, $sql, $dmn_id, $software_id) {
	$is_installed = "
		SELECT
			`software_id`,
			`software_prefix`,
			`db`,
			`path`
		FROM
			`web_software_inst`
		WHERE
			`domain_id` = ?
		AND
			`software_id` = ?
	";
	$is_inst = exec_query($sql, $is_installed, array($dmn_id, $software_id));
	if ($is_inst -> recordCount() == 0) {
		$tpl -> assign(
					array(
						'INSTALLED_SOFTWARE_INFO' => '',
						'SOFTWARE_INSTALL_BUTTON' => 'software_install.php?id='.$software_id
					)
				);
		$tpl -> parse('SOFTWARE_INSTALL', '.software_install');
	} else {
		$tpl -> assign(
					array(
						'SOFTWARE_INSTALL_BUTTON'		=> '',
						'SOFTWARE_STATUS'				=> tr('installed'),
						'SOFTWARE_INSTALL_PATH'			=> $is_inst->fields['path'],
						'SOFTWARE_INSTALL_DATABASE'		=> $is_inst->fields['db'],
						'TR_SOFTWARE_INFO'				=> tr('Installation infos'),
						'TR_SOFTWARE_STATUS'			=> tr('Software status:'),
						'TR_SOFTWARE_INSTALL_PATH'		=> tr('Installation path:'),
						'TR_SOFTWARE_INSTALL_DATABASE' 	=> tr('Used database:'),
						'SOFTWARE_INSTALL'				=> ''
					)
				);
		$tpl -> parse('INSTALLED_SOFTWARE_INFO', '.installed_software_info');
	}
}

function get_software_props ($tpl, $sql, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit) {

	if (!check_software_avail($sql, $software_id, $dmn_created_id)) {
		set_page_message(tr('Software not found!'));
		header('Location: software.php');
		exit;
	} else {
		$software_props = "
			SELECT
				`software_name`,
				`software_version`,
				`software_language`,
				`software_type`,
				`software_db`,
				`software_link`,
				`software_desc`
			FROM
				`web_software`
			WHERE
				`software_id` = ?
			AND
				`reseller_id` = ?
		";
		$rs = exec_query($sql, $software_props, array($software_id, $dmn_created_id));
		if ($rs -> fields['software_db'] == 1) {
			$tpl -> assign('SOFTWARE_DB', tr('yes'));
			if ($dmn_sqld_limit == '-1') { 
				$tpl -> assign(
							array(
								'STATUS_COLOR' 		=> 'red',
								'STATUS_MESSAGE'	=> tr('You need a Database for this software')
							)
						);
				$tpl -> parse('SOFTWARE_MESSAGE', '.software_message');
			} else {
				$tpl -> assign(
							array(
								'STATUS_COLOR' 		=> 'green',
								'STATUS_MESSAGE' 	=> '',
								'SOFTWARE_MESSAGE' 	=> ''
							)
						);
			}
		} else {
			$tpl -> assign(
						array(
							'SOFTWARE_DB' 		=> tr('no'),
							'SOFTWARE_MESSAGE' 	=> '',
							'STATUS_MESSAGE' 	=> ''
						)
					);
		}
		$sw_link = $rs -> fields['software_link'];
		if(!preg_match("/http:/",$sw_link) && !preg_match("/https:/",$sw_link)) {
			$sw_link = "http://".$sw_link;
		}
		$tpl -> assign (
					array(
						'SOFTWARE_NAME' 		=> $rs -> fields['software_name'],
						'SOFTWARE_VERSION' 		=> $rs -> fields['software_version'],
						'SOFTWARE_LANGUAGE' 	=> $rs -> fields['software_language'],
						'SOFTWARE_TYPE' 		=> $rs -> fields['software_type'],
						'SOFTWARE_LINK' 		=> $sw_link,
						'SOFTWARE_DESC' 		=> nl2br(wordwrap($rs -> fields['software_desc'],200, "\n", true))
					)
				);
		check_is_installed($tpl, $sql, $dmn_id, $software_id);
		$tpl -> parse('SOFTWARE_ITEM', 'software_item');
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
    list($dmn_id, $dmn_name,,,$dmn_created_id,,,,,,$dmn_sqld_limit,) = get_domain_default_props($sql, $user_id);
	get_software_props ($tpl, $sql, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit);
	return $software_id;
}

//
// common page data.
//

$tpl -> assign(
	array(
		'TR_CLIENT_VIEW_SOFTWARE_PAGE_TITLE'	=> tr('i-MSCP - Software details'),
		'THEME_COLOR_PATH'						=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'							=> tr('encoding'),
		'ISP_LOGO'								=> get_logo($_SESSION['user_id'])
	)
);

//
// dynamic page data.
//

if (isset($_SESSION['software_support']) && $_SESSION['software_support'] == "no") {
	$tpl -> assign('NO_SOFTWARE', '');
}

$software_id = gen_page_lists($tpl, $sql, $_SESSION['user_id']);

//
// static page messages.
//

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_webtools.tpl');

gen_logged_from($tpl);

get_client_software_permission (&$tpl,&$sql,$_SESSION['user_id']);

check_permissions($tpl);


$tpl -> assign(
	array(
		'TR_SOFTWARE_MENU_PATH'	=> tr('i-MSCP - application installer'),
		'TR_SOFTWARE_VIEW_PATH'	=> tr('Software details'),
		'SOFTWARE_ID'			=> $software_id,
		'TR_MANAGE_USERS' 		=> tr('Manage users'),
		'TR_VIEW_SOFTWARE' 		=> tr('Software details'),
		'TR_NAME' 				=> tr('Software'),
		'TR_VERSION'			=> tr('Version'),
		'TR_LANGUAGE' 			=> tr('Language'),
		'TR_TYPE'				=> tr('Type'),
		'TR_DB' 				=> tr('Database required'),
		'TR_LINK' 				=> tr('Homepage'),
		'TR_DESC' 				=> tr('Description'),
		'TR_BACK' 				=> tr('Back'),
		'TR_INSTALL' 			=> tr('Install'),
		'TR_SOFTWARE_MENU' 		=> tr('Software installation')
	)
);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');
$tpl -> prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
