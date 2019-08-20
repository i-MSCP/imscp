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

use iMSCP\Config\DbConfig;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

/**
 * Update server traffic settings.
 *
 * @param int $trafficLimit Monthly traffic limit
 * @param int $trafficWarning Traffic warning
 * @return bool TRUE on success FALSE otherwise
 */
function admin_updateServerTrafficSettings($trafficLimit, $trafficWarning)
{
    $retVal = true;

    if (!is_numeric($trafficLimit)) {
        set_page_message(tr('Monthly traffic limit must be a number.'), 'error');
        $retVal = false;
    }

    if (!is_numeric($trafficWarning)) {
        set_page_message(tr('Monthly traffic warning must be a number.'), 'error');
        $retVal = false;
    }

    if ($retVal && $trafficWarning > $trafficLimit) {
        set_page_message(tr('Monthly traffic warning cannot be bigger than monthly traffic limit.'), 'error');
        $retVal = false;
    }

    if ($retVal) {
        /** @var $dbConfig Dbconfig */
        $dbConfig = Registry::get('dbConfig');

        $dbConfig['SERVER_TRAFFIC_LIMIT'] = $trafficLimit;
        $dbConfig['SERVER_TRAFFIC_WARN'] = $trafficWarning;

        // gets the number of queries that were been executed
        $updtCount = $dbConfig->countQueries('update');
        $newCount = $dbConfig->countQueries('insert');

        // An Update was been made in the database ?
        if ($updtCount || $newCount) {
            set_page_message(tr('Monthly server traffic settings successfully updated.', $updtCount), 'success');
            write_log(sprintf('Server monthly traffic settings were updated by %s', $_SESSION['user_logged']), E_USER_NOTICE);
        } else {
            set_page_message(tr('Nothing has been changed.'), 'info');
        }
    }

    return $retVal;
}

/**
 * Generates page.
 *
 * @param TemplateEngine $tpl
 * @param int $trafficLimit Monthly traffic limit
 * @param int $trafficWarning Traffic warning
 * @return void
 */
function admin_generatePage(TemplateEngine $tpl, $trafficLimit, $trafficWarning)
{
    $cfg = Registry::get('config');

    if (empty($_POST)) {
        $trafficLimit = $cfg['SERVER_TRAFFIC_LIMIT'];
        $trafficWarning = $cfg['SERVER_TRAFFIC_WARN'];
    }

    $tpl->assign([
        'MAX_TRAFFIC'     => tohtml($trafficLimit),
        'TRAFFIC_WARNING' => tohtml($trafficWarning)
    ]);
}

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

$trafficLimit = $trafficWarning = 0;

if (!empty($_POST)) {
    $trafficLimit = !isset($_POST['max_traffic']) ?: clean_input($_POST['max_traffic']);
    $trafficWarning = !isset($_POST['traffic_warning']) ?: clean_input($_POST['traffic_warning']);
    admin_updateServerTrafficSettings($trafficLimit, $trafficWarning);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'admin/settings_server_traffic.tpl',
    'page_message'  => 'layout',
    'hosting_plans' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                  => tr('Admin / Settings / Monthly Server Traffic'),
    'TR_SET_SERVER_TRAFFIC_SETTINGS' => tr('Monthly server traffic settings'),
    'TR_MAX_TRAFFIC'                 => tr('Max traffic'),
    'TR_WARNING'                     => tr('Warning traffic'),
    'TR_MIB'                         => tr('MiB'),
    'TR_UPDATE'                      => tr('Update'),
]);

generateNavigation($tpl);
admin_generatePage($tpl, $trafficLimit, $trafficWarning);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
