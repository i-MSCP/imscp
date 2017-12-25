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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2018 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Checks for external event
 *
 * @return void
 */
function check_external_events()
{
    if (isset($_SESSION['edit'])) {
        if ('_yes_' == $_SESSION['edit']) {
            set_page_message(tr('User data were successfully updated.'), 'success');
        } else {
            set_page_message(tr('User data were not updated.'), 'error');
        }
        unset($_SESSION['edit']);
        return;
    }

    if (isset($_SESSION['user_has_domain'])) {
        if ($_SESSION['user_has_domain'] == '_yes_') {
            set_page_message(tr('This user has domain record. First remove the domain from the system.'), 'error');
        }

        unset($_SESSION['user_has_domain']);
        return;
    }

    if (isset($_SESSION['user_deleted'])) {
        if ($_SESSION['user_deleted'] == '_yes_') {
            set_page_message(tr('User scheduled for deletion.'), 'info');
        } else {
            set_page_message(tr('User has not been deleted.'), 'error');
        }

        unset($_SESSION['user_deleted']);
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                         => 'shared/layouts/ui.tpl',
    'page'                           => 'reseller/users.phtml',
    'page_message'                   => 'layout',
    'client_search_form'             => 'page',
    'client_show_domain_aliases_blk' => 'client_search_form',
    'client_domain_aliases_switch'   => 'client_search_form',
    'client_domain_aliases_show'     => 'client_domain_aliases_switch',
    'client_domain_aliases_hide'     => 'client_domain_aliases_switch',
    'client_message'                 => 'page',
    'client_list'                    => 'page',
    'client_item'                    => 'client_list',
    'client_domain_status_ok'        => 'client_item',
    'client_domain_status_not_ok'    => 'client_item',
    'client_restricted_links'        => 'client_item',
    'client_domain_alias_blk'        => 'client_item',
    'client_scroll_prev'             => 'client_list',
    'client_scroll_prev_gray'        => 'client_list',
    'client_scroll_next_gray'        => 'client_list',
    'client_scroll_next'             => 'client_list'
]);
$tpl->assign('TR_PAGE_TITLE', tr('Reseller / Customers / Overview'));

generateNavigation($tpl);
gen_user_list($tpl);
check_external_events();
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unset($_SESSION['dmn_name']);
unset($_SESSION['ch_hpprops']);
unset($_SESSION['local_data']);
unset($_SESSION['dmn_ip']);
unset($_SESSION['dmn_id']);
unset($GLOBALS['dmn_name']);
unset($GLOBALS['ch_hpprops']);
unset($GLOBALS['local_data']);
unset($GLOBALS['user_add3_added']);
unset($GLOBALS['user_add3_added']);
unset($GLOBALS['dmn_ip']);
unset($GLOBALS['dmn_id']);

unsetMessages();
