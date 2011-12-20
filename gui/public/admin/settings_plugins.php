<?php

/***********************************************************************************************************************
 * function script
 */

/***********************************************************************************************************************
 * Main script
 */

// Include all needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => $cfg->ADMIN_TEMPLATE_PATH . '/../shared/layouts/ui.tpl',
		'page' => $cfg->ADMIN_TEMPLATE_PATH . '/settings_plugins.tpl'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Settings / Plugins'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_SETTINGS' => tr('Settings'),
		'TR_PLUGINS_SETTINGS' => tr('PLugins settings')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
