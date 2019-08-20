<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\TemplateEngine;

require_once 'imscp-lib.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic([
    'page'                => 'client/language.tpl',
    'page_message'        => 'layout',
    'languages_available' => 'page',
    'def_language'        => 'languages_available'
]);


if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
    list($customerCurrentLanguage) = get_user_gui_props($_SESSION['user_id']);
} else {
    $customerCurrentLanguage = $_SESSION['user_def_lang'];
}

if (!empty($_POST)) {
    $customerId = $_SESSION['user_id'];
    $customerNewLanguage = clean_input($_POST['def_language']);

    if (!in_array($customerNewLanguage, i18n_getAvailableLanguages(true), true)) {
        showBadRequestErrorPage();
    }

    if ($customerCurrentLanguage != $customerNewLanguage) {
        exec_query('UPDATE user_gui_props SET lang = ? WHERE user_id = ?', [
            $customerNewLanguage, $_SESSION['user_id']
        ]);

        if (!isset($_SESSION['logged_from_id'])) {
            unset($_SESSION['user_def_lang']);
            $_SESSION['user_def_lang'] = $customerNewLanguage;
        }

        set_page_message(tr('Language has been updated.'), 'success');
    } else {
        set_page_message(tr('Nothing has been changed.'), 'info');
    }

    redirectTo('language.php');
}

$tpl->assign([
    'TR_PAGE_TITLE'      => tr('Client / Profile / Language'),
    'TR_GENERAL_INFO'    => tr('General information'),
    'TR_LANGUAGE'        => tr('Language'),
    'TR_CHOOSE_LANGUAGE' => tr('Choose your language'),
    'TR_UPDATE'          => tr('Update')
]);

generateNavigation($tpl);
generateLanguagesList($tpl, $customerCurrentLanguage);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
