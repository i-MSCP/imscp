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
 * @subpackage  Admin
 * @copyright   2010-2014 by i-MSCP team
 * @author      iMSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Generate page
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function admin_generateLanguagesList($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$htmlChecked = $cfg->HTML_CHECKED;

	$defaultLanguage = $cfg->USER_INITIAL_LANG;
	$availableLanguages = i18n_getAvailableLanguages();

	if (!empty($availableLanguages)) {
		foreach ($availableLanguages as $languageDefinition) {
			$tpl->assign(
				array(
					'LANGUAGE_NAME' => tohtml($languageDefinition['language']),
					'NUMBER_TRANSLATED_STRINGS' => tr('%d strings translated', $languageDefinition['translatedStrings']),
					'LANGUAGE_REVISION' => $languageDefinition['revision'],
					'LAST_TRANSLATOR' => preg_replace('/\s<.*>/', '', $languageDefinition['lastTranslator']),
					'LOCALE_CHECKED' => ($languageDefinition['locale'] == $defaultLanguage) ? $htmlChecked : '',
					'LOCALE' => $languageDefinition['locale']));

			$tpl->parse('LANGUAGE_BLOCK', '.language_block');
		}
	} else {
		$tpl->assign('LANGUAGES_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Dispatches the request
if (isset($_POST['uaction'])) {
	if ($_POST['uaction'] == 'uploadLanguage') {
		if (i18n_importMachineObjectFile()) {
			set_page_message(tr('Language file successfully installed.'), 'success');
		}
	} elseif ($_POST['uaction'] == 'changeLanguage') {
		if (i18n_changeDefaultLanguage()) {
			set_page_message(tr('Default language successfully updated.'), 'success');
			// Force change on next load
			redirectTo('multilanguage.php');
		} else {
			set_page_message(tr('Unknown language name.'), 'error');
		}
	} elseif ($_POST['uaction'] == 'rebuildIndex') {
		i18n_buildLanguageIndex();
		set_page_message(tr('Languages index was successfully re-built.'), 'success');
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/multilanguage.tpl',
		'page_message' => 'layout',
		'languages_block' => 'page',
		'language_block' => 'languages_block')
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Languages'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MULTILANGUAGE' => tr('Internationalization'),
		'TR_LANGUAGE_NAME' => tr('Language'),
		'TR_NUMBER_TRANSLATED_STRINGS' => tr('Translated strings'),
		'TR_LANGUAGE_REVISION' => tr('Revision date'),
		'TR_LAST_TRANSLATOR' => tr('Last translator'),
		'TR_DEFAULT_LANGUAGE' => tr('Default language'),
		'TR_SAVE' => tr('Save'),
		'TR_INSTALL_NEW_LANGUAGE' => tr('Install'),
		'TR_LANGUAGE_FILE' => tr('Language file'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_REBUILD_INDEX' => tr('Rebuild languages index'),
		'TR_UPLOAD_HELP' => tr('Only gettext Machine Object files (MO files) are accepted.'),
		'TR_INSTALL' => tr('Install'),
		'TR_CANCEL' => tr('Cancel')
	)
);

generateNavigation($tpl);
admin_generateLanguagesList($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
