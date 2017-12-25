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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generates statistics for the given reseller
 *
 * @param TemplateEngine $tpl Template engine instance
 * @param int $resellerId Reseller unique identifier
 * @param string $resellerName Reseller name
 * @return void
 */
function _generateResellerStatistics(TemplateEngine $tpl, $resellerId, $resellerName)
{
    $resellerProps = imscp_getResellerProperties($resellerId, true);
    $rtraffLimit = $resellerProps['max_traff_amnt'] * 1048576;
    $rdiskLimit = $resellerProps['max_disk_amnt'] * 1048576;
    list($rdmnConsumed, $rsubConsumed, $ralsConsumed, $rmailConsumed, $rftpConsumed, $rsqlDbConsumed,
        $rsqlUserConsumed, $rtraffConsumed, $rdiskConsumed) = getResellerStats($resellerId);

    $diskUsagePercent = getPercentUsage($rdiskConsumed, $rdiskLimit);
    $trafficPercent = getPercentUsage($rtraffConsumed, $rtraffLimit);

    $tpl->assign([
        'RESELLER_NAME'         => tohtml($resellerName),
        'RESELLER_ID'           => tohtml($resellerId),
        'DISK_PERCENT_WIDTH'    => tohtml($diskUsagePercent, 'htmlAttr'),
        'DISK_PERCENT'          => tohtml($diskUsagePercent),
        'DISK_MSG'              => ($rdiskLimit == 0)
            ? tohtml(sprintf('%s / ∞', bytesHuman($rdiskConsumed)))
            : tohtml(sprintf('%s / %s', bytesHuman($rdiskConsumed), bytesHuman($rdiskLimit))),
        'TRAFFIC_PERCENT_WIDTH' => tohtml($trafficPercent, 'htmlAttr'),
        'TRAFFIC_PERCENT'       => tohtml($trafficPercent),
        'TRAFFIC_MSG'           => ($rtraffLimit == 0)
            ? tohtml(sprintf('%s / ∞', bytesHuman($rtraffConsumed)))
            : tohtml(sprintf('%s / %s', bytesHuman($rtraffConsumed), bytesHuman($rtraffLimit))),
        'DMN_MSG'               => ($resellerProps['max_dmn_cnt'] == 0)
            ? tohtml(sprintf('%s / ∞', $rdmnConsumed))
            : ($resellerProps['max_dmn_cnt'] == -1
                ? '-' : tohtml(sprintf('%s / %s', $rdmnConsumed, $resellerProps['max_dmn_cnt']))),
        'SUB_MSG'               => ($resellerProps['max_sub_cnt'] == 0)
            ? tohtml(sprintf('%s / ∞', $rsubConsumed))
            : ($resellerProps['max_sub_cnt'] == -1
                ? '-' : tohtml(sprintf('%s / %s', $rsubConsumed, $resellerProps['max_sub_cnt']))),
        'ALS_MSG'               => ($resellerProps['max_als_cnt'] == 0)
            ? tohtml(sprintf('%s / ∞', $ralsConsumed))
            : ($resellerProps['max_als_cnt'] == -1
                ? '-' : tohtml(sprintf('%s / %s', $ralsConsumed, $resellerProps['max_als_cnt']))),
        'MAIL_MSG'              => ($resellerProps['max_mail_cnt'] == 0)
            ? tohtml(sprintf('%s / ∞', $rmailConsumed))
            : ($resellerProps['max_mail_cnt'] == -1
                ? '-' : tohtml(sprintf('%s / %s', $rmailConsumed, $resellerProps['max_mail_cnt']))),
        'FTP_MSG'               => ($resellerProps['max_ftp_cnt'] == 0)
            ? tohtml(sprintf('%s / ∞', $rftpConsumed))
            : ($resellerProps['max_ftp_cnt'] == -1
                ? '-' : tohtml(sprintf('%s / %s', $rftpConsumed, $resellerProps['max_ftp_cnt']))),
        'SQL_DB_MSG'            => ($resellerProps['max_sql_db_cnt'] == 0)
            ? tohtml(sprintf('%s / ∞', $rsqlDbConsumed))
            : ($resellerProps['max_sql_db_cnt'] == -1
                ? '-' : tohtml(sprintf('%s / %s', $rsqlDbConsumed, $resellerProps['max_sql_db_cnt']))),
        'SQL_USER_MSG'          => ($resellerProps['max_sql_user_cnt'] == 0)
            ? tohtml(sprintf('%s / ∞', $rsqlUserConsumed))
            : ($resellerProps['max_sql_user_cnt'] == -1
                ? '-' : tohtml(sprintf('%s / %s', $rsqlUserConsumed, $resellerProps['max_sql_user_cnt'])))
    ]);
}

/**
 * Generates page
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $stmt = execute_query("SELECT admin_id, admin_name FROM admin WHERE admin_type = 'reseller'");
    while ($row = $stmt->fetch()) {
        _generateResellerStatistics($tpl, $row['admin_id'], $row['admin_name']);
        $tpl->parse('RESELLER_STATISTICS_BLOCK', '.reseller_statistics_block');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptStart);
systemHasResellers() or showBadRequestErrorPage();

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                    => 'shared/layouts/ui.tpl',
    'page'                      => 'admin/reseller_statistics.tpl',
    'page_message'              => 'layout',
    'reseller_statistics_block' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'              => tohtml(tr('Admin / Statistics / Reseller Statistics')),
    'TR_RESELLER_NAME'           => tohtml(tr('Reseller')),
    'TR_TRAFFIC_USAGE'           => tohtml(tr('Monthly traffic usage')),
    'TR_DISK_USAGE'              => tohtml(tr('Disk usage')),
    'TR_DOMAINS'                 => tohtml(tr('Domains')),
    'TR_SUBDOMAINS'              => tohtml(tr('Subdomains')),
    'TR_DOMAIN_ALIASES'          => tohtml(tr('Domain aliases')),
    'TR_MAIL_ACCOUNTS'           => tohtml(tr('Mail accounts')),
    'TR_FTP_ACCOUNTS'            => tohtml(tr('FTP accounts')),
    'TR_SQL_DATABASES'           => tohtml(tr('SQL databases')),
    'TR_SQL_USERS'               => tohtml(tr('SQL users')),
    'TR_DETAILED_STATS_TOOLTIPS' => tohtml(tr('Show detailed statistics for this reseller'), 'htmlAttr')
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
