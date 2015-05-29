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
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function admin_generateLanguagesList($tpl)
{
	$cfg = iMSCP_Registry::get('config');
	$defaultLanguage = $cfg['USER_INITIAL_LANG'];
	$availableLanguages = i18n_getAvailableLanguages();

	if (!empty($availableLanguages)) {
		foreach ($availableLanguages as $languageDefinition) {
			$tpl->assign(array(
				'LANGUAGE_NAME' => tohtml($languageDefinition['language']),
				'NUMBER_TRANSLATED_STRINGS' => tohtml(tr('%d strings translated', $languageDefinition['translatedStrings'])),
				'LANGUAGE_REVISION' => tohtml($languageDefinition['revision']),
				'LOCALE_CHECKED' => ($languageDefinition['locale'] == $defaultLanguage) ? 'checked' : '',
				'LOCALE' => tohtml($languageDefinition['locale'], 'htmlAttr')
			));

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

$eventManager = iMSCP_Events_Aggregator::getInstance();
$eventManager->dispatch(iMSCP_Events::onAdminScriptStart);

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
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'admin/multilanguage.tpl',
	'page_message' => 'layout',
	'languages_block' => 'page',
	'language_block' => 'languages_block'
));

$tpl->assign(array(
	'TR_PAGE_TITLE' => tohtml(tr('Admin / Settings / Languages')),
	'TR_MULTILANGUAGE' => tohtml(tr('Internationalization')),
	'TR_LANGUAGE_NAME' => tohtml(tr('Language')),
	'TR_NUMBER_TRANSLATED_STRINGS' => tohtml(tr('Translated strings')),
	'TR_LANGUAGE_REVISION' => tohtml(tr('Revision date')),
	'TR_DEFAULT_LANGUAGE' => tohtml(tr('Default language')),
	'TR_SAVE' => tohtml(tr('Save'), 'htmlAttr'),
	'TR_IMPORT_NEW_LANGUAGE' => tohtml(tr('Import new language file')),
	'TR_LANGUAGE_FILE' => tohtml(tr('Language file')),
	'TR_REBUILD_INDEX' => tohtml(tr('Rebuild languages index'), 'htmlAttr'),
	'TR_UPLOAD_HELP' => tohtml(tr('Only gettext Machine Object files (MO files) are accepted.'), 'htmlAttr'),
	'TR_IMPORT' => tohtml(tr('Import'), 'htmlAttr')
));

$eventManager->registerListener('onGetJsTranslations', function($e) {
	/* @var $e iMSCP_Events_Event */
	$e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations();
});

generateNavigation($tpl);
admin_generateLanguagesList($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
