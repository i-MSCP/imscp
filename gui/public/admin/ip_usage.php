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
 * Generate List of Domains assigned to IPs
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function listIPDomains(TemplateEngine $tpl)
{
    $stmt = execute_query('SELECT ip_id, ip_number FROM server_ips');

    while ($ip = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $stmt2 = exec_query(
            '
                SELECT t1.domain_name, t3.admin_name
                FROM domain AS t1
                JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
                JOIN admin as t3 ON(t3.admin_id = t2.created_by)
                WHERE t1.domain_ip_id = :ip_id
                UNION ALL
                SELECT t1.alias_name AS domain_name, t4.admin_name
                FROM domain_aliasses AS t1
                JOIN domain AS t2 USING(domain_id)
                JOIN admin AS t3 ON(admin_id = domain_admin_id)
                JOIN admin AS t4 ON(t4.admin_id = t3.created_by)
                WHERE alias_ip_id = :ip_id
            ',
            ['ip_id' => $ip['ip_id']]
        );

        $domainsCount = $stmt2->rowCount();

        $tpl->assign([
            'IP'           => tohtml($ip['ip_number'] == '0.0.0.0'
                ? tr('Any') : $ip['ip_number']
            ),
            'RECORD_COUNT' => tr('Total Domains') . ': ' . ($domainsCount)
        ]);

        if ($domainsCount) {
            while ($data = $stmt2->fetchRow(PDO::FETCH_ASSOC)) {
                $tpl->assign([
                    'DOMAIN_NAME'   => tohtml(
                        decode_idna($data['domain_name'])
                    ),
                    'RESELLER_NAME' => tohtml($data['admin_name'])
                ]);
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


require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(
    Events::onAdminScriptStart
);

if (!systemHasCustomers()) {
    showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/ip_usage.tpl',
    'page_message' => 'layout',
    'ip_row'       => 'page',
    'domain_row'   => 'ip_row'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                => tohtml(
        tr('Admin / Statistics / IP Usage')
    ),
    'TR_SERVER_STATISTICS'         => tohtml(tr('Server statistics')),
    'TR_IP_ADMIN_USAGE_STATISTICS' => tohtml(tr('Admin/IP usage statistics')),
    'TR_DOMAIN_NAME'               => tohtml(tr('Domain Name')),
    'TR_RESELLER_NAME'             => tohtml(tr('Reseller Name'))
]);

generateNavigation($tpl);
listIPDomains($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(
    Events::onAdminScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();

unsetMessages();
