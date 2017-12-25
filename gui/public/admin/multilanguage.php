<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by i-MSCP Team
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

use iMSCP_Events as Events;
use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function admin_generateLanguagesList($tpl)
{
    $defaultLanguage = Registry::get('config')['USER_INITIAL_LANG'];

    foreach (i18n_getAvailableLanguages() as $language) {
        $tpl->assign([
            'LANGUAGE_NAME'             => tohtml($language['language']),
            'NUMBER_TRANSLATED_STRINGS' => ($language['locale'] == Zend_Locale::BROWSER)
                ? $language['translatedStrings'] : tohtml(tr('%d strings translated', $language['translatedStrings'])),
            'LANGUAGE_CREATION_DATE'    => tohtml($language['creation']),
            'LAST_TRANSLATOR'           => tohtml($language['lastTranslator']),
            'LOCALE_CHECKED'            => ($language['locale'] == $defaultLanguage) ? ' checked' : '',
            'LOCALE'                    => tohtml($language['locale'], 'htmlAttr')
        ]);
        $tpl->parse('LANGUAGE_BLOCK', '.language_block');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptStart);

if (isset($_POST['uaction'])) {
    if ($_POST['uaction'] == 'uploadLanguage') {
        if (i18n_importMachineObjectFile()) {
            set_page_message(tr('Language file successfully installed.'), 'success');
        }
    } elseif ($_POST['uaction'] == 'changeLanguage') {
        if (i18n_changeDefaultLanguage()) {
            set_page_message(tr('Default language successfully updated.'), 'success');
        } else {
            set_page_message(tr('Unknown language name.'), 'error');
        }
    } elseif ($_POST['uaction'] == 'rebuildIndex') {
        i18n_buildLanguageIndex();
        set_page_message(tr('Languages index was successfully re-built.'), 'success');
    }

    redirectTo('multilanguage.php');
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'            => 'shared/layouts/ui.tpl',
    'page'              => 'admin/multilanguage.phtml',
    'page_message'      => 'layout',
    'languages_block'   => 'page',
    'language_block_js' => 'page',
    'language_block'    => 'languages_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                => tohtml(tr('Admin / Settings / Languages')),
    'TR_MULTILANGUAGE'             => tohtml(tr('Internationalization')),
    'TR_LANGUAGE_NAME'             => tohtml(tr('Language')),
    'TR_NUMBER_TRANSLATED_STRINGS' => tohtml(tr('Translated strings')),
    'TR_LANGUAGE_CREATION_DATE'    => tohtml(tr('Creation date')),
    'TR_LAST_TRANSLATOR'           => tohtml(tr('Last translator')),
    'TR_DEFAULT_LANGUAGE'          => tohtml(tr('Default language')),
    'TR_DEFAULT'                   => tohtml(tr('Default')),
    'TR_SAVE'                      => tohtml(tr('Save'), 'htmlAttr'),
    'TR_IMPORT_NEW_LANGUAGE'       => tohtml(tr('Import new language file')),
    'TR_LANGUAGE_FILE'             => tohtml(tr('Language file')),
    'TR_REBUILD_INDEX'             => tohtml(tr('Rebuild languages index'), 'htmlAttr'),
    'TR_UPLOAD_HELP'               => tohtml(tr('Only gettext Machine Object files (MO files) are accepted.'), 'htmlAttr'),
    'TR_IMPORT'                    => tohtml(tr('Import'), 'htmlAttr')
]);

generateNavigation($tpl);
admin_generateLanguagesList($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
