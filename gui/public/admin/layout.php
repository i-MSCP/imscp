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

/**
 * Generate layout color form.
 *
 * @param $tpl TemplateEngine Template engine instance
 * @return void
 */
function admin_generateLayoutColorForm(TemplateEngine $tpl)
{
    $colors = layout_getAvailableColorSet();

    if (!empty($POST) && isset($_POST['layoutColor']) && in_array($_POST['layoutColor'], $colors)) {
        $selectedColor = $_POST['layoutColor'];
    } else {
        $selectedColor = $_SESSION['user_theme_color'];
    }

    if (!empty($colors)) {
        foreach ($colors as $color) {
            $tpl->assign([
                'COLOR'          => $color,
                'SELECTED_COLOR' => ($color == $selectedColor) ? ' selected' : ''
            ]);
            $tpl->parse('LAYOUT_COLOR_BLOCK', '.layout_color_block');
        }
    } else {
        $tpl->assign('LAYOUT_COLORS_BLOCK', '');
    }
}

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'              => 'shared/layouts/ui.tpl',
    'page'                => 'admin/layout.tpl',
    'page_message'        => 'layout',
    'logo_remove_button'  => 'page',
    'layout_colors_block' => 'page',
    'layout_color_block'  => 'layout_colors_block'
]);

/**
 * Dispatches request
 */
if (isset($_POST['uaction'])) {
    if ($_POST['uaction'] == 'updateIspLogo') {
        if (layout_updateUserLogo()) {
            set_page_message(tr('Logo successfully updated.'), 'success');
        }
    } elseif ($_POST['uaction'] == 'deleteIspLogo') {
        if (layout_deleteUserLogo()) {
            set_page_message(tr('Logo successfully removed.'), 'success');
        }
    } elseif ($_POST['uaction'] == 'changeShowLabels') {
        layout_setMainMenuLabelsVisibility($_SESSION['user_id'], intval($_POST['mainMenuShowLabels']));
        set_page_message(tr('Main menu labels visibility successfully updated.'), 'success');

    } elseif ($_POST['uaction'] == 'changeLayoutColor' && isset($_POST['layoutColor'])) {
        $userId = isset($_SESSION['logged_from_id']) ? $_SESSION['logged_from_id'] : $_SESSION['user_id'];

        if (layout_setUserLayoutColor($userId, $_POST['layoutColor'])) {
            $_SESSION['user_theme_color'] = $_POST['layoutColor'];
            set_page_message(tr('Layout color successfully updated.'), 'success');
        } else {
            set_page_message(tr('Unknown layout color.'), 'error');
        }
    } else {
        set_page_message(tr('Unknown action: %s', tohtml($_POST['uaction'])), 'error');
    }
}

if ($_SESSION['show_main_menu_labels']) {
    $tpl->assign([
        'MAIN_MENU_SHOW_LABELS_ON'  => ' selected',
        'MAIN_MENU_SHOW_LABELS_OFF' => ''
    ]);
} else {
    $tpl->assign([
        'MAIN_MENU_SHOW_LABELS_ON'  => '',
        'MAIN_MENU_SHOW_LABELS_OFF' => ' selected'
    ]);
}

$ispLogo = layout_getUserLogo();

if (layout_isUserLogo($ispLogo)) {
    $tpl->parse('LOGO_REMOVE_BUTTON', '.logo_remove_button');
} else {
    $tpl->assign('LOGO_REMOVE_BUTTON', '');
}

$tpl->assign([
    'TR_PAGE_TITLE'            => tr('Admin / Profile / Layout'),
    'ISP_LOGO'                 => $ispLogo,
    'OWN_LOGO'                 => $ispLogo,
    'TR_UPLOAD_LOGO'           => tr('Upload logo'),
    'TR_LOGO_FILE'             => tr('Logo file'),
    'TR_ENABLED'               => tr('Enabled'),
    'TR_DISABLED'              => tr('Disabled'),
    'TR_UPLOAD'                => tr('Upload'),
    'TR_REMOVE'                => tr('Remove'),
    'TR_LAYOUT_COLOR'          => tr('Layout color'),
    'TR_CHOOSE_LAYOUT_COLOR'   => tr('Choose layout color'),
    'TR_CHANGE'                => tr('Change'),
    'TR_OTHER_SETTINGS'        => tr('Other settings'),
    'TR_MAIN_MENU_SHOW_LABELS' => tr('Show labels for main menu links')
]);

generateNavigation($tpl);
admin_generateLayoutColorForm($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
