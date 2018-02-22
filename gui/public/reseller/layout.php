<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate layout color form
 *
 * @since iMSCP 1.0.1.6
 * @param $tpl iMSCP_pTemplate Template engine instance
 * @return void
 */
function reseller_generateLayoutColorForm($tpl)
{
    $colors = layout_getAvailableColorSet();

    if (!empty($POST) && isset($_POST['layoutColor']) && in_array($_POST['layoutColor'], $colors)) {
        $selectedColor = $_POST['layoutColor'];
    } else {
        $selectedColor = layout_getUserLayoutColor($_SESSION['user_id']);
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

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'              => 'shared/layouts/ui.tpl',
    'page'                => 'reseller/layout.tpl',
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
        layout_updateUserLogo() ?:
            set_page_message(tr('Logo successfully updated.'), 'success');
    } elseif ($_POST['uaction'] == 'deleteIspLogo') {
        layout_deleteUserLogo() ?: set_page_message(tr('Logo successfully removed.'), 'success');
    } elseif ($_POST['uaction'] == 'changeLayoutColor' && isset($_POST['layoutColor'])) {
        if (layout_setUserLayoutColor($_SESSION['user_id'], $_POST['layoutColor'])) {
            if (!isset($_SESSION['logged_from_id'])) {
                $_SESSION['user_theme_color'] = $_POST['layoutColor'];
                set_page_message(tr('Layout color successfully updated.'), 'success');
            } else {
                set_page_message(tr("Reseller's layout color successfully updated."), 'success');
            }
        } else {
            set_page_message(tr('Unknown layout color.'), 'error');
        }
    } elseif ($_POST['uaction'] == 'changeShowLabels') {
        layout_setMainMenuLabelsVisibility($_SESSION['user_id'], intval($_POST['mainMenuShowLabels']));
        set_page_message(tr('Main menu labels visibility successfully updated.'), 'success');
    } else {
        set_page_message(tr('Unknown action: %s', tohtml($_POST['uaction'])), 'error');
    }
}

if (layout_isMainMenuLabelsVisible($_SESSION['user_id'])) {
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


$ispLogo = layout_getUserLogo(false);

if (layout_isUserLogo($ispLogo)) {
    $tpl->parse('LOGO_REMOVE_BUTTON', '.logo_remove_button');
} else {
    $tpl->assign('LOGO_REMOVE_BUTTON', '');
}

$tpl->assign([
    'TR_PAGE_TITLE'            => tr('Reseller / Profile / Layout'),
    'OWN_LOGO'                 => $ispLogo,
    'TR_LAYOUT_SETTINGS'       => tr('Layout'),
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
reseller_generateLayoutColorForm($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
