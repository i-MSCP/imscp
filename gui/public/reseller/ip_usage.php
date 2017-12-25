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

use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate List of Domains assigned to IPs
 *
 * @param  TemplateEngine $tpl Template engine
 * @return void
 */
function generatePage($tpl)
{
    $stmt = exec_query('SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?', [$_SESSION['user_id']]);
    $resellerIps = explode(';', substr($stmt->fetchColumn(), 0, -1));
    $stmt = execute_query('SELECT ip_id, ip_number FROM server_ips WHERE ip_id IN (' . implode(',', $resellerIps) . ')');

    while ($ip = $stmt->fetch()) {
        $stmt2 = exec_query(
            '
                SELECT domain_name
                FROM domain
                JOIN admin ON(admin_id = domain_admin_id)
                WHERE domain_ip_id = ?
                AND created_by = ?
                UNION ALL
                SELECT alias_name AS domain_name
                FROM domain_aliasses
                JOIN domain USING(domain_id)
                JOIN admin ON(admin_id = domain_admin_id)
                WHERE alias_ip_id = ?
                AND created_by = ?
            ',
            [$ip['ip_id'], $_SESSION['user_id'], $ip['ip_id'], $_SESSION['user_id']]
        );

        $domainsCount = $stmt2->rowCount();

        $tpl->assign(
            [
                'IP'           => tohtml(($ip['ip_number'] == '0.0.0.0') ? tr('Any') : $ip['ip_number']),
                'RECORD_COUNT' => tr('Total Domains') . ': ' . ($domainsCount)
            ]
        );

        if ($domainsCount) {
            while ($data = $stmt2->fetch()) {
                $tpl->assign('DOMAIN_NAME', tohtml(idn_to_utf8($data['domain_name'])));
                $tpl->parse('DOMAIN_ROW', '.domain_row');
            }
        } else {
            $tpl->assign('DOMAIN_NAME', tr('No used yet'));
            $tpl->parse('DOMAIN_ROW', 'domain_row');
        }

        $tpl->parse('IP_ROW', '.ip_row');
        $tpl->assign('DOMAIN_ROW', '');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptStart);

if (!resellerHasCustomers()) {
    showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'reseller/ip_usage.tpl',
    'page_message' => 'layout',
    'ip_row'       => 'page',
    'domain_row'   => 'ip_row'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                   => tohtml(tr('Reseller / Statistics / IP Usage')),
    'TR_DOMAIN_STATISTICS'            => tohtml(tr('Domain statistics')),
    'TR_IP_RESELLER_USAGE_STATISTICS' => tohtml(tr('Reseller/IP usage statistics')),
    'TR_DOMAIN_NAME'                  => tohtml(tr('Domain Name'))
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
