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
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright	2010-2011 by i-MSCP team
 * @author		iMSCP Team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
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
	/*
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
	*/

	foreach ($availableLanguages as $language) {
			$tpl->assign(array(
							  'LANGUAGE' => tohtml($language['language']),
							  'MESSAGES' => tr('%d strings translated', $language['translatedStrings']),
							  'LANGUAGE_REVISION' => $language['revision'],
							  'LAST_TRANSLATOR' => preg_replace('/\s<.*>/', '', $language['lastTranslator']),
							  'LANG_VALUE_CHECKED' => $default_language == $language['locale'] ? $cfg->HTML_CHECKED : '',
							  'LANG_VALUE' => $language['locale']));

		$tpl->parse('LANG_ROW', '.lang_row');
	}

	/*
	if($page != 1) {
		$tpl->assign('PSI', '?psi=' . $page);
	} else {
		$tpl->assign('PSI', '');
	}
	*/
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
			set_page_message(tr('Language file successfully installed.'), 'success');
		}
    } elseif($_POST['uaction'] == 'changeLanguage') {
        if(i18n_changeDefaultLanguage()) {
			set_page_message(tr('Default language successfully updated.'), 'success');
			// Force change on next load
        	redirectTo('multilanguage.php');
		} else {
			set_page_message(tr('Unknown language name.'), 'error');
		}
    } elseif($_POST['uaction'] == 'rebuildIndex') {
		i18n_buildLanguageIndex();
		set_page_message(tr('Languages index was successfully re-built.'), 'success');
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		 'page' => $cfg->ADMIN_TEMPLATE_PATH . '/multilanguage.tpl',
		 'page_message' => 'page',
		 'lang_row' => 'page',
		 'lang_show' => 'lang_row',
		 'lang_delete_link' => 'lang_row',
		 'lang_def' => 'lang_row',
		/*
		 'scroll_prev_gray' => 'page',
		 'scroll_prev' => 'page',
		 'scroll_next_gray' => 'page',
		 'scroll_next' => 'page'
		*/
	));

$tpl->assign(
	array(
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
		 'TR_SAVE' => tr('Save'),
		 'TR_INSTALL_NEW_LANGUAGE' => tr('Install'),
		 'TR_LANGUAGE_FILE' => tr('Language file'),
		 'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		 'TR_REBUILD_INDEX' => tr('Rebuild languages index'),
		 'TR_UPLOAD_HELP' => tr('Seul les fichier gettext (*.mo) sont acceptés.'),
		 'TR_HELP' => tr('Help'),
		 'TR_INSTALL' => tr('Install'),
		 'TR_CANCEL' => tr('Cancel')));

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
