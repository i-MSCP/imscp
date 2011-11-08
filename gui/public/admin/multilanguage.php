<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 *
 * @license
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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */


/*******************************************************************************
 * Script functions
 */

/**
 * Generate page
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generatePage($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$default_language = $cfg->USER_INITIAL_LANG;
	$availableLanguages = i18n_getAvailableLanguages();
	$ItemPerPage = 10;

	if (!empty($_GET['psi'])) {
		$page = intval($_GET['psi']);
	} else {
		$page = 1;
	}

	$pages = ceil(count($availableLanguages) / $ItemPerPage);

	if ($page > $pages) {
		$page = $pages;
	}

	$start = ceil(($page - 1) * $ItemPerPage);
	$availableLanguages = array_slice($availableLanguages, $start, $ItemPerPage);

	if ($page < $pages) {
		$tpl->assign('NEXT_PSI', $page + 1);
		$tpl->assign('SCROLL_NEXT_GRAY', '');
	} else {
		$tpl->assign('SCROLL_NEXT', '');
	}

	if ($page != 1) {
		$tpl->assign('PREV_PSI', $page - 1);
		$tpl->assign('SCROLL_PREV_GRAY', '');
	} else {
		$tpl->assign('SCROLL_PREV', '');
	}

	foreach ($availableLanguages as $language) {
			$tpl->assign(array(
							  'LANGUAGE' => tohtml($language['language']),
							  'MESSAGES' => tr('%d strings translated', $language['translatedStrings']),
							  'LANGUAGE_REVISION' => $language['revision'],
							  'LAST_TRANSLATOR' => preg_replace('/\s<.*>/', '', $language['lastTranslator']),
							  'LANG_VALUE_CHECKED' => $default_language == $language['locale']
								  ? $cfg->HTML_CHECKED : '',
							  'LANG_VALUE' => $language['locale']));

		$tpl->parse('LANG_ROW', '.lang_row');
	}

	if($page != 1) {
		$tpl->assign('PSI', '?psi=' . $page);
	} else {
		$tpl->assign('PSI', '');
	}
}

/*******************************************************************************
 * Main script
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Dispatches the request
if (isset($_POST['uaction'])) {
    if($_POST['uaction'] == 'uploadLanguage') {
		if(i18n_importMachineObjectFile()) {
			set_page_message(tr('Language file successfully installed/updated.'), 'success');
		}
    } elseif($_POST['uaction'] == 'changeLanguage') {
        i18n_changeDefaultLanguage();
		set_page_message(tr('Default language successfully updated.'), 'success');

        // Fix to see change on next load
        redirectTo('multilanguage.php');
    } elseif($_POST['uaction'] == 'rebuildIndex') {
		i18n_buildLanguageIndex();
		set_page_message(tr('Languages index was successfully re-built.'), 'success');
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
						  'page' => $cfg->ADMIN_TEMPLATE_PATH . '/multilanguage.tpl',
						  'page_message' => 'page',
						  'lang_row' => 'page',
						  'lang_show' => 'lang_row',
						  'lang_delete_link' => 'lang_row',
						  'lang_def' => 'lang_row',
						  'scroll_prev_gray' => 'page',
						  'scroll_prev' => 'page',
						  'scroll_next_gray' => 'page',
						  'scroll_next' => 'page'));

$tpl->assign(array(
                  'TR_PAGE_TITLE' => tr('i-MSCP - Admin/Internationalisation'),
                  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
                  'THEME_CHARSET' => tr('encoding'),
                  'ISP_LOGO' => layout_getUserLogo(),
                  'TR_MULTILANGUAGE' => tr('Internationalization'),
                  'TR_INSTALLED_LANGUAGES' => tr('Available languages'),
                  'TR_LANGUAGE' => tr('Language'),
                  'TR_MESSAGES' => tr('Translated strings'),
                  'TR_LANG_REV' => tr('Revision Date'),
				  'TR_LAST_TRANSLATOR' => tr('Last translator'),
                  'TR_DEFAULT' => tr('Default language'),
                  'TR_ACTION' => tr('Action'),
                  'TR_SAVE' => tr('Save'),
                  'TR_INSTALL_NEW_LANGUAGE' => tr('Install / Update language'),
                  'TR_LANGUAGE_FILE' => tr('Language file'),
                  'ISP_LOGO' => layout_getUserLogo(),
                  'TR_INSTALL' => tr('Install / Update'),
                  'TR_EXPORT' => tr('Export'),
				  'TR_REBUILD_INDEX' => tr('Rebuild languages index')
           ));

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
