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
 * @author      Hannes Koschier <hannes@cheat.at>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('php_editor') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

// Getting customer's domain id
$domainId = $phpini->getDomId($_SESSION['user_id']);

// load custom php.ini
$phpini->loadCustomPHPini($domainId);

// load phpini client permissions
$phpini->loadClPerm($domainId);

if (!empty($_POST)) { // Post request
	if ($phpini->getDomStatus($domainId)) {
		$oldData = $phpini->getData();
		$phpini->setData('phpiniSystem', 'yes');

		if (isset($_POST['allow_url_fopen']) && $phpini->getClPermVal('phpiniAllowUrlFopen') == 'yes') {
			$phpini->setData('phpiniAllowUrlFopen', clean_input($_POST['allow_url_fopen']));
		}

		if (isset($_POST['display_errors']) && $phpini->getClPermVal('phpiniDisplayErrors') == 'yes') {
			$phpini->setData('phpiniDisplayErrors', clean_input($_POST['display_errors']));
		}

		if (isset($_POST['error_reporting']) && $phpini->getClPermVal('phpiniDisplayErrors') == 'yes') {
			$phpini->setData('phpiniErrorReporting', clean_input($_POST['error_reporting']));
		}

		if ($cfg['HTTPD_SERVER'] != 'apache_itk') {
			// Customer can disable/enable all functions
			if ($phpini->getClPermVal('phpiniDisableFunctions') == 'yes') {
				$disabledFunctions = array();

				foreach (
					array(
						'show_source', 'system', 'shell_exec', 'shell_exec', 'passthru', 'exec', 'phpinfo', 'shell',
						'symlink', 'proc_open', 'popen'
					) as $function
				) {
					if (isset($_POST[$function])) { // we are safe here
						array_push($disabledFunctions, $function);
					}
				}

				// Builds the PHP disable_function directive with a pre-check on functions that can be disabled
				$phpini->setData('phpiniDisableFunctions', $phpini->assembleDisableFunctions($disabledFunctions));
			} elseif ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') {
				$disabledFunctions = explode(',', $phpini->getDataDefaultVal('phpiniDisableFunctions'));

				if (isset($_POST['exec']) && $_POST['exec'] == 'allows') { // exec function is explicitely allowed by customer
					$disabledFunctions = array_diff($disabledFunctions, array('exec'));
				} else { // exec function is explicitely diallowed by customer (we are safe here)
					$disabledFunctions = in_array('exec', $disabledFunctions)
						? $disabledFunctions : $disabledFunctions + array('exec');
				}

				$phpini->setData('phpiniDisableFunctions', $phpini->assembleDisableFunctions($disabledFunctions));
			}
		}

		if($phpini->getData() == $oldData) {
			set_page_message(tr("Nothing has been changed."), 'info');
			redirectTo('domains_manage.php');
		}

		$phpini->saveCustomPHPiniIntoDb($domainId);
		$phpini->sendToEngine($domainId);

		set_page_message(tr('PHP configuration scheduled for update.'), 'success');

		$userLogged = isset($_SESSION['logged_from']) ? $_SESSION['logged_from'] : $_SESSION['user_logged'];
		write_log("PHP settings for domain ID <strong>$domainId</strong> were updated by {$_SESSION['user_logged']}", E_USER_NOTICE);
	} else {
		set_page_message(tr('Domain status is not ok.'), 'error');
	}

	redirectTo('domains_manage.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		 'layout' => 'shared/layouts/ui.tpl',
		 'page' => 'client/phpini.tpl',
		 'page_message' => 'layout',
		 'php_editor_first_block' =>  'page',
		 'allow_url_fopen_block' => 'php_editor_first_block',
		 'display_errors_block' => 'php_editor_first_block',
		 'error_reporting_block' => 'php_editor_first_block',
		 'disable_functions_block' => 'php_editor_first_block',
		 'php_editor_second_block' => 'page'
	)
);

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Client / Domains / PHP Settings'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_MENU_PHPINI' => tr('PHP Editor'),
		 'TR_PAGE_TEXT' => tr("In this page, you can configure some of the aspects of PHP's behavior. You must note that for now, the directives defined here apply to your entire domain account (including subdomains and domain aliases). Of course some values can be modified through the PHP ini_set() function."),
		 'TR_UPDATE_DATA' => tr('Update'),
		 'TR_CANCEL' => tr('Cancel')
	)
);

generateNavigation($tpl);

$htmlSelected = $cfg->HTML_SELECTED;
$htmlChecked = $cfg->HTML_CHECKED;

$firstBlock = false;
$tplVars = array();

// allows_url_fopen directive
if ($phpini->getClPermVal('phpiniAllowUrlFopen') == 'no') {
	$tplVars['ALLOW_URL_FOPEN_BLOCK'] = '';
} else {
	$tplVars['TR_ALLOW_URL_FOPEN'] = tr('Allow URL fopen');
	$tplVars['ALLOW_URL_FOPEN_ON'] = ($phpini->getDataVal('phpiniAllowUrlFopen') == 'on') ? $htmlChecked : '';
	$tplVars['ALLOW_URL_FOPEN_OFF'] = ($phpini->getDataVal('phpiniAllowUrlFopen') == 'off') ? $htmlChecked : '';
	$firstBlock = true;
}

// display_errors directive
if ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') {
	$tplVars['DISPLAY_ERRORS_BLOCK'] = '';
} else {
	$tplVars['TR_DISPLAY_ERRORS'] = tr('Display errors');
	$tplVars['DISPLAY_ERRORS_ON'] = ($phpini->getDataVal('phpiniDisplayErrors') == 'on') ? $htmlChecked : '';
	$tplVars['DISPLAY_ERRORS_OFF'] = ($phpini->getDataVal('phpiniDisplayErrors') == 'off') ? $htmlChecked : '';
	$firstBlock = true;
}

// error_reporting directive
if ($phpini->getClPermVal('phpiniDisplayErrors') == 'no') {
	$tplVars['ERROR_REPORTING_BLOCK'] = '';
} else {
	$errorReportingValue = $phpini->errorReportingToLitteral($phpini->getDataVal('phpiniErrorReporting'));

	$tplVars['TR_ERROR_REPORTING'] = tr('Error reporting');
	$tplVars['TR_ERROR_REPORTING_DEFAULT'] = tr('Show all errors, except for notices and coding standards warnings (Default)');
	$tplVars['TR_ERROR_REPORTING_DEVELOPEMENT'] = tr('Show all errors, warnings and notices including coding standards (Development)');
	$tplVars['TR_ERROR_REPORTING_PRODUCTION'] = tr(' Show all errors, except for warnings about deprecated code (Production)');
	$tplVars['TR_ERROR_REPORTING_NONE'] = tr('Do not show any error');
	$tplVars['ERROR_REPORTING_0'] = ($errorReportingValue == 'E_ALL & ~E_NOTICE') ? $htmlSelected : '';
	$tplVars['ERROR_REPORTING_1'] = ($errorReportingValue == 'E_ALL | E_STRICT') ? $htmlSelected : '';
	$tplVars['ERROR_REPORTING_2'] = ($errorReportingValue == 'E_ALL & ~E_DEPRECATED') ? $htmlSelected : '';
	$tplVars['ERROR_REPORTING_3'] = ($errorReportingValue == '0') ? $htmlSelected : '';
	$firstBlock = true;
}

// disable_functions directive
if ($cfg['HTTPD_SERVER'] == 'apache_itk' || $phpini->getClPermVal('phpiniDisableFunctions') == 'no') {
	$tplVars['DISABLE_FUNCTIONS_BLOCK'] = '';
	$tplVars['PHP_EDITOR_SECOND_BLOCK'] = '';
} elseif ($phpini->getClPermVal('phpiniDisableFunctions') == 'exec') {
	$disableFunctions = explode(',', $phpini->getDataVal('phpiniDisableFunctions'));
	$allowed = in_array('exec', $disableFunctions) ? false : true;

	$tplVars['TR_DISABLE_FUNCTIONS_EXEC'] = tr('PHP exec() function');
	$tplVars['TR_EXEC_HELP'] = tr("When allowed, scripts can call the PHP exec() function. This function is needed by many applications but can cause some security issues");
	$tplVars['EXEC_ALLOWED'] = ($allowed) ? $htmlChecked : '';
	$tplVars['EXEC_DISALLOWED'] = ($allowed) ? '' : $htmlChecked;
	$tplVars['DISABLE_FUNCTIONS_BLOCK'] = '';
} else {
	$disableFunctions = explode(',', $phpini->getDataVal('phpiniDisableFunctions'));
	$disableFunctionsAll = array(
		'SHOW_SOURCE', 'SYSTEM', 'SHELL_EXEC', 'PASSTHRU', 'EXEC', 'PHPINFO', 'SHELL', 'SYMLINK', 'PROC_OPEN', 'POPEN'
	);

	foreach ($disableFunctionsAll as $function) {
		$tplVars[$function] = in_array(strtolower($function), $disableFunctions) ? $htmlChecked : '';
	}

	$tplVars['TR_DISABLE_FUNCTIONS'] = tr('Disabled functions');
	$tplVars['PHP_EDITOR_SECOND_BLOCK'] = '';
	$firstBlock = true;
}

if (!$firstBlock) {
	$tplVars['PHP_EDITOR_FIRST_BLOCK'] = '';
} else {
	$tplVars['TR_PHP_SETTINGS'] = tr('PHP Settings');
	$tplVars['TR_YES'] = tr('Yes');
	$tplVars['TR_NO'] = tr('No');
}

$tpl->assign($tplVars);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
