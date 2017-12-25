<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;
use iMSCP_Services as Services;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $services = new Services();

    foreach ($services as $service) {
        $isRunning = $services->isRunning(isset($_GET['refresh']));

        if ($isRunning && $service[0] == 23) {
            set_page_message(
                tr('The Telnet-Server is currently running on your server. This legacy service is not secure.'),
                'static_warning'
            );
        }

        if (!$service[3]) {
            continue;
        }

        $tpl->assign([
            'SERVICE'        => tohtml($service[2]),
            'IP'             => ($service[4] === '0.0.0.0') ? tr('Any') : tohtml($service[4]),
            'PORT'           => tohtml($service[0]),
            'STATUS'         => $isRunning ? tr('UP') : tr('DOWN'),
            'CLASS'          => $isRunning ? 'up' : 'down',
            'STATUS_TOOLTIP' => tohtml($isRunning ? tr('Service is running') : tr('Service is not running'), 'htmlAttr')
        ]);
        $tpl->parse('SERVICE_STATUS', '.service_status');
    }

    if (isset($_GET['refresh'])) {
        set_page_message('Service statuses were refreshed.', 'success');
        redirectTo('service_statuses.php');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'         => 'shared/layouts/ui.tpl',
    'page'           => 'admin/service_statuses.tpl',
    'page_message'   => 'layout',
    'service_status' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'    => tohtml(tr('Admin / General / Services Status')),
    'TR_SERVICE'       => tohtml(tr('Service name')),
    'TR_IP'            => tohtml(tr('IP address')),
    'TR_PORT'          => tohtml(tr('Port')),
    'TR_STATUS'        => tohtml(tr('Status')),
    'TR_SERVER_STATUS' => tohtml(tr('Server status')),
    'TR_FORCE_REFRESH' => tohtml(tr('Force refresh', 'htmlAttr'))
]);

Registry::get('iMSCP_Application')->getEventsManager()->registerListener(Events::onGetJsTranslations, function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
