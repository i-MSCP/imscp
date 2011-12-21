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
 * @return void
 */
function gen_page_lists($tpl, $user_id) {

    list($dmn_id,$dmn_name,,,$dmn_created_id,) = get_domain_default_props($user_id);
    $software_poss = gen_software_list($tpl, $dmn_id, $dmn_name, $dmn_created_id, $_SESSION['user_id']);
    $tpl->assign('TOTAL_SOFTWARE_AVAILABLE', $software_poss);
	$tpl->parse('SOFTWARE_MESSAGE', 'software_message');
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
$tpl->define_dynamic('layout', $cfg->CLIENT_TEMPLATE_PATH . '/../shared/layouts/ui.tpl');
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/software.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('software_message', 'page');
$tpl->define_dynamic('software_item', 'page');
$tpl->define_dynamic('software_action_delete', 'page');
$tpl->define_dynamic('software_action_install', 'page');
$tpl->define_dynamic('software_total', 'page');
$tpl->define_dynamic('no_software', 'page');
$tpl->define_dynamic('no_software_support', 'page');
$tpl->define_dynamic('del_software_support', 'page');
$tpl->define_dynamic('del_software_item', 'page');
$tpl->define_dynamic('t_software_support', 'page');

if(isset($_SESSION['software_support']) && $_SESSION['software_support'] == "no") {
	$tpl->assign('NO_SOFTWARE', '');
}

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client/Manage Users'),
		 'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_SOFTWARE_MENU_PATH' => tr('i-MSCP - application installer'),
		 'TR_MANAGE_USERS' => tr('Manage users'),
		 'TR_INSTALL_SOFTWARE' => tr('Install software'),
		 'TR_SOFTWARE' => tr('Software'),
		 'TR_VERSION' => tr('Version'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_TYPE' => tr('Type'),
		 'TR_NEED_DATABASE' => tr('Database'),
		 'TR_STATUS' => tr('Status'),
		 'TR_ACTION' => tr('Action'),
		 'TR_SOFTWARE_AVAILABLE' => tr('Apps available'),
		 'TR_DELETE' => tr('Delete'),
		 'TR_SOFTWARE_MENU' => tr('Software installation'),
		 'TR_CLIENT_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Application Management'),
		 'TR_SOFTWARE_ASC' => 'software.php?sortby=name&order=asc',
		 'TR_SOFTWARE_DESC' => 'software.php?sortby=name&order=desc',
		 'TR_TYPE_ASC' => 'software.php?sortby=type&order=asc',
		 'TR_TYPE_DESC' => 'software.php?sortby=type&order=desc',
		 'TR_NEED_DATABASE_ASC' => 'software.php?sortby=database&order=asc',
		 'TR_NEED_DATABASE_DESC' => 'software.php?sortby=database&order=desc',
		 'TR_STATUS_ASC' => 'software.php?sortby=status&order=asc',
		 'TR_STATUS_DESC' => 'software.php?sortby=status&order=desc',
		 'TR_LANGUAGE_ASC' => 'software.php?sortby=language&order=asc',
		 'TR_LANGUAGE_DESC' => 'software.php?sortby=language&order=desc'));

gen_page_lists($tpl, $_SESSION['user_id']);
generateNavigation($tpl);
get_client_software_permission($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
