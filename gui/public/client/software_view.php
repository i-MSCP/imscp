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
    list($dmn_id, $dmn_name,,,$dmn_created_id,,,,,,$dmn_sqld_limit,) = get_domain_default_props($user_id);
	get_software_props ($tpl, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit);
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
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/software_view.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('software_message', 'page');
$tpl->define_dynamic('software_install', 'page');
$tpl->define_dynamic('installed_software_info', 'page');
$tpl->define_dynamic('software_item', 'page');
$tpl->define_dynamic('no_software', 'page');


$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Software details'),
		 'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()
	)
);

if (isset($_SESSION['software_support']) && $_SESSION['software_support'] == "no") {
	$tpl -> assign('NO_SOFTWARE', '');
}

$software_id = gen_page_lists($tpl, $_SESSION['user_id']);

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_webtools.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_webtools.tpl');
get_client_software_permission ($tpl, $_SESSION['user_id']);

$tpl->assign(
	array(
		 'TR_SOFTWARE_MENU_PATH' => tr('i-MSCP - application installer'),
		 'TR_SOFTWARE_VIEW_PATH' => tr('Software details'),
		 'SOFTWARE_ID' => $software_id,
		 'TR_MANAGE_USERS' => tr('Manage users'),
		 'TR_VIEW_SOFTWARE' => tr('Software details'),
		 'TR_NAME' => tr('Software'),
		 'TR_VERSION' => tr('Version'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_TYPE' => tr('Type'),
		 'TR_DB' => tr('Database required'),
		 'TR_LINK' => tr('Homepage'),
		 'TR_DESC' => tr('Description'),
		 'TR_BACK' => tr('Back'),
		 'TR_INSTALL' => tr('Install'),
		 'TR_SOFTWARE_MENU' => tr('Software installation')
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
