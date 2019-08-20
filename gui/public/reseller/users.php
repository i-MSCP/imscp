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

require 'imscp-lib.php';

check_login('reseller');
EventAggregator::getInstance()->dispatch(Events::onResellerScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
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
EventAggregator::getInstance()->dispatch(
    Events::onResellerScriptEnd, ['templateEngine' => $tpl]
);
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
