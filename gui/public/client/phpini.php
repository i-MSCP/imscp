<?php
/*
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
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
 * @copyright	2010-2011 by i-MSCP team
 * @author		Hannes Koschier <hannes@cheat.at>
 * @Version		SVN: $Id$
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
include 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects the client in silent way
$domainProperties = get_domain_default_props($_SESSION['user_id'], true);
if ($domainProperties['phpini_perm_system'] == 'no') {
	redirectTo('domains_manage.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

$domainId = $phpini->getDomId($_SESSION['user_id']);

// load phpini client permissions
$phpini->loadClPerm($domainId);

// Dispatches the request

if ($phpini->getDomStatus($domainId) & $phpini->getClPermVal('phpiniSystem') == 'yes') {

	// save data to database
	if (isset($_POST['uaction']) && ($_POST['uaction'] == 'update')) {
		if ($phpini->getClPermVal('phpiniSystem') == 'yes') {
			$phpini->setData('phpiniSystem', 'yes');

			if (isset($_POST['phpini_register_globals']) && $phpini->getClPermVal('phpiniRegisterGlobals') == 'yes') {
				$phpini->setData('phpiniRegisterGlobals', clean_input($_POST['phpini_register_globals']));
			}

			if (isset($_POST['phpini_allow_url_fopen']) && $phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') {
				$phpini->setData('phpiniAllowUrlFopen', clean_input($_POST['phpini_allow_url_fopen']));
			}

			if (isset($_POST['phpini_display_errors']) && $phpini->getClPermVal('phpiniDisplayErrors') == 'yes') {
				$phpini->setData('phpiniDisplayErrors', clean_input($_POST['phpini_display_errors']));
			}

			if (isset($_POST['phpini_error_reporting']) && $phpini->getClPermVal('phpiniDisplayErrors') == 'yes') {
				$phpini->setData('phpiniErrorReporting', clean_input($_POST['phpini_error_reporting']));
			}

			if ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') {
				// Collect all parts for the PHP disable_functions directivesfrom $_POST
				$mytmp = array();

				foreach ($_POST as $key => $value) {
					if (substr($key, 0, 10) == 'phpini_df_') {
						array_push($mytmp, clean_input($value));
					}
				}

				$phpini->setData('phpiniDisableFunctions', $phpini->assembleDisableFunctions($mytmp));
			}

			if ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') {
				if ($_POST['phpini_disable_functions_exec'] == 'off') {
					$phpini->setData('phpiniDisableFunctions', $phpini->getDataDefaultVal('phpiniDisableFunctions'));
				} else {
					// Remove exec from default disabled function in the disable_functions PHP directive
					$tmp_arr = array_diff(explode(',', $phpini->getDataDefaultVal('phpiniDisableFunctions')), array('exec'));
					$phpini->setData('phpiniDisableFunctions', implode(',', $tmp_arr));
				}

			}

		}

		// Save client custom PHP directives values and send a request to the daemon
		// for backend process
		$phpini->saveCustomPHPiniIntoDb($domainId);
		$phpini->sendToEngine($domainId);

		set_page_message(tr('PHP directives scheduled for update.'), 'success');
		redirectTo('domains_manage.php');
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(array('page' => $cfg->CLIENT_TEMPLATE_PATH . '/phpini.tpl',
							  'page_message' => 'page',
							  'logged_from' => 'page',
							  'js_for_exec_help' => 'page',
							  't_phpini_register_globals' => 'page',
							  't_phpini_allow_url_fopen' => 'page',
							  't_phpini_display_errors' => 'page',
							  't_phpini_disable_functions' => 'page',
							  't_phpini_disable_functions_exec' => 'page',
							  't_update_ok' => 'page'));

	$tpl->assign(array(
					  'TR_PAGE_TITLE' => tr('i-MSCP / Client / Manage domains / PHP.ini editor'),
					  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
					  'THEME_CHARSET' => tr('encoding'),
					  'ISP_LOGO' => layout_getUserLogo(),
					  'TR_TITLE' => tr('PHP directives'),
					  'TR_MENU_PHPINI' => tr('PHP directives'),
					  'TR_PAGE_TEXT' => tr("In this page, you can configure some of the aspects of PHP's behavior. You must note that for now, the directives defined here apply to your entire domain account (including subdomains and domain aliases). Of course some values can be modified through the PHP ini_set() function."),

					  'TR_PHPINI_ALLOW_URL_FOPEN' => 'allow_url_fopen',
					  'TR_PHPINI_REGISTER_GLOBALS' => 'register_globals',
					  'TR_PHPINI_DISPLAY_ERRORS' => 'display_errors',
					  'TR_PHPINI_ERROR_REPORTING' => 'error_reporting',

					  'TR_PHPINI_ERROR_REPORTING_DEFAULT' => tr('Show all errors, except for notices and coding standards warnings (Default)'),
					  'TR_PHPINI_ERROR_REPORTING_DEVELOPEMENT' => tr('Show all errors, warnings and notices including coding standards (Development)'),
					  'TR_PHPINI_ERROR_REPORTING_PRODUCTION' => tr(' Show all errors, except for warnings about deprecated code (Production)'),
					  'TR_PHPINI_ERROR_REPORTING_NONE' => tr('Do not show any error'),

					  'TR_PHPINI_DISABLE_FUNCTIONS' => tr('disable_functions'),
					  'TR_PHPINI_DISABLE_FUNCTIONS_EXEC' => tr('Allows the PHP exec() function'),

					  'TR_VALUE_ON' => 'On',
					  'TR_VALUE_OFF' => 'Off',
					  'TR_ALLOWS' => tr('Allows'),
					  'TR_DISALLOWS' => tr('Disallows'),

					  'TR_UPDATE_DATA' => tr('Update'),
					  'TR_CANCEL' => tr('Cancel'),
					  'TR_PHP_INI_EXEC_HELP' => tr("When allowed, scripts can use the PHP exec() function. This function is needed by many applications but can cause some security issues")));

	gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_manage_domains.tpl');
	gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_manage_domains.tpl');
	gen_logged_from($tpl);

	// load custom php.ini
	$phpini->loadCustomPHPini($domainId);

	if ($phpini->getClPermVal('phpiniRegisterGlobals') == 'no') {
		$tpl->assign('T_PHPINI_REGISTER_GLOBALS', '');
	}

	if ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'no') {
		$tpl->assign('T_PHPINI_ALLOW_URL_FOPEN', '');
	}

	if ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') {
		$tpl->assign('T_PHPINI_DISPLAY_ERRORS', '');
	}

	if ($phpini->getClPermVal('phpiniDisableFunctions') == 'no') {

		$tpl->assign(array(
						  'JS_FOR_EXEC_HELP' => '',
						  'T_PHPINI_DISABLE_FUNCTIONS' => '',

						  'T_PHPINI_DISABLE_FUNCTIONS_EXEC' => ''));
	} elseif ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') {
			$tpl->assign('T_PHPINI_DISABLE_FUNCTIONS', '');
	} else {
		$tpl->assign(array(
						  'JS_FOR_EXEC_HELP' => '',
						  'T_PHPINI_DISABLE_FUNCTIONS_EXEC' => ''));
	}

	$htmlSelected = $cfg->HTML_SELECTED;

	$tpl->assign(array(
					  'PHPINI_ALLOW_URL_FOPEN_ON' => ($phpini->getDataVal('phpiniAllowUrlFopen') == 'On') ? $htmlSelected : '',
					  'PHPINI_ALLOW_URL_FOPEN_OFF' => ($phpini->getDataVal('phpiniAllowUrlFopen') == 'Off') ? $htmlSelected : '',

					  'PHPINI_REGISTER_GLOBALS_ON' => ($phpini->getDataVal('phpiniRegisterGlobals') == 'On') ? $htmlSelected : '',
					  'PHPINI_REGISTER_GLOBALS_OFF' => ($phpini->getDataVal('phpiniRegisterGlobals') == 'Off') ? $htmlSelected : '',

					  'PHPINI_DISPLAY_ERRORS_ON' => ($phpini->getDataVal('phpiniDisplayErrors') == 'On') ? $htmlSelected : '',
					  'PHPINI_DISPLAY_ERRORS_OFF' => ($phpini->getDataVal('phpiniDisplayErrors') == 'Off') ? $htmlSelected : '',

					  'PHPINI_ERROR_REPORTING_0' => ($phpini->getDataVal('phpiniErrorReporting') == 'E_ALL & ~E_NOTICE') ? $htmlSelected : '',
					  'PHPINI_ERROR_REPORTING_1' => ($phpini->getDataVal('phpiniErrorReporting') == 'E_ALL | E_STRICT') ? $htmlSelected : '',
					  'PHPINI_ERROR_REPORTING_2' => ($phpini->getDataVal('phpiniErrorReporting') == 'E_ALL & ~E_DEPRECATED') ? $htmlSelected : '',
					  'PHPINI_ERROR_REPORTING_3' => ($phpini->getDataVal('phpiniErrorReporting') == '0') ? $htmlSelected : ''));


	// deAssemble the disable_functions
	$phpiniDf = explode(',', $phpini->getDataVal('phpiniDisableFunctions'));
	$phpiniDfAll = array(
		'PHPINI_DF_SHOW_SOURCE_CHK', 'PHPINI_DF_SYSTEM_CHK',
		'PHPINI_DF_SHELL_EXEC_CHK', 'PHPINI_DF_PASSTHRU_CHK',
		'PHPINI_DF_EXEC_CHK', 'PHPINI_DF_PHPINFO_CHK',
		'PHPINI_DF_SHELL_CHK', 'PHPINI_DF_SYMLINK_CHK'
	);

	foreach ($phpiniDfAll as $phpiniDfVar) {
		$phpiniDfShortVar = substr($phpiniDfVar, 10);
		$phpiniDfShortVar = strtolower(substr($phpiniDfShortVar, 0, -4));

		if (in_array($phpiniDfShortVar, $phpiniDf)) {
			$tpl->assign($phpiniDfVar, $cfg->HTML_CHECKED);
		} else {
			$tpl->assign($phpiniDfVar, '');
		}
	}

	if (in_array('exec', $phpiniDf)) {
		$tpl->assign(array(
						  'PHPINI_DISABLE_FUNCTIONS_EXEC_ON' => '',
						  'PHPINI_DISABLE_FUNCTIONS_EXEC_OFF' => $htmlSelected));
	} else {
		$tpl->assign(array(
						  'PHPINI_DISABLE_FUNCTIONS_EXEC_ON' => $htmlSelected,
						  'PHPINI_DISABLE_FUNCTIONS_EXEC_OFF' => ''));
	}

	check_permissions($tpl);
	generatePageMessage($tpl);

	$tpl->parse('PAGE', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd,
												  new iMSCP_Events_Response($tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	// No permissions to use the feature or domain status not ok
	redirectTo('domains_manage.php');
}
