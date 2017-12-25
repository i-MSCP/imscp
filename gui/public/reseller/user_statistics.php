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
 * Script functions
 */

/**
 * Generates statistics for the given user
 *
 * @access private
 * @param TemplateEngine $tpl Template engine instance
 * @param int $adminId User unique identifier
 * @return void
 */
function _generateUserStatistics(TemplateEngine $tpl, $adminId)
{
    list($webTraffic, $ftpTraffic, $smtpTraffic, $pop3Traffic, $trafficUsage, $diskUsage
        ) = getClientTrafficAndDiskStats($adminId);
    list($subCount, $subLimit, $alsCount, $alsLimit, $mailCount, $mailLimit, $ftpCount, $ftpLimit, $sqlDbCount,
        $sqlDbLimit, $sqlUsersCount, $sqlUsersLlimit, $trafficLimit, $diskLimit
        ) = getClientItemCountsAndLimits($adminId);
    $trafficPercent = getPercentUsage($trafficUsage, $trafficLimit);
    $diskPercent = getPercentUsage($diskUsage, $diskLimit);
    $tpl->assign([
        'USER_ID'               => tohtml($adminId),
        'USERNAME'              => tohtml(decode_idna(get_user_name($adminId))),
        'TRAFFIC_PERCENT_WIDTH' => tohtml($trafficPercent, 'htmlAttr'),
        'TRAFFIC_PERCENT'       => tohtml($trafficPercent),
        'TRAFFIC_MSG'           => ($trafficLimit > 0)
            ? tohtml(sprintf('%s / %s', bytesHuman($trafficUsage), bytesHuman($trafficLimit)))
            : tohtml(sprintf('%s / ∞', bytesHuman($trafficUsage))),
        'DISK_PERCENT_WIDTH'    => tohtml($diskPercent, 'htmlAttr'),
        'DISK_PERCENT'          => tohtml($diskPercent),
        'DISK_MSG'              => ($diskLimit > 0)
            ? tohtml(sprintf('%s / %s', bytesHuman($diskUsage), bytesHuman($diskLimit)))
            : tohtml(sprintf('%s / ∞', bytesHuman($diskUsage))),
        'WEB'                   => tohtml(bytesHuman($webTraffic)),
        'FTP'                   => tohtml(bytesHuman($ftpTraffic)),
        'SMTP'                  => tohtml(bytesHuman($smtpTraffic)),
        'POP3'                  => tohtml(bytesHuman($pop3Traffic)),
        'SUB_MSG'               => tohtml(sprintf('%s / %s', $subCount, translate_limit_value($subLimit))),
        'ALS_MSG'               => tohtml(sprintf('%s / %s', $alsCount, translate_limit_value($alsLimit))),
        'MAIL_MSG'              => tohtml(sprintf('%s / %s', $mailCount, translate_limit_value($mailLimit))),
        'FTP_MSG'               => tohtml(sprintf('%s / %s', $ftpCount, translate_limit_value($ftpLimit))),
        'SQL_DB_MSG'            => tohtml(sprintf('%s / %s', $sqlDbCount, translate_limit_value($sqlDbLimit))),
        'SQL_USER_MSG'          => tohtml(sprintf('%s / %s', $sqlUsersCount, translate_limit_value($sqlUsersLlimit)))
    ]);
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $stmt = exec_query('SELECT admin_id FROM admin WHERE created_by = ?', [$_SESSION['user_id']]);
    while ($row = $stmt->fetch()) {
        _generateUserStatistics($tpl, $row['admin_id']);
        $tpl->parse('USER_STATISTICS_ENTRY_BLOCK', '.user_statistics_entry_block');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onResellerScriptStart);
resellerHasCustomers() or showBadRequestErrorPage();

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                        => 'shared/layouts/ui.tpl',
    'page'                          => 'reseller/user_statistics.tpl',
    'page_message'                  => 'layout',
    'user_statistics_entries_block' => 'page',
    'user_statistics_entry_block'   => 'user_statistics_entries_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE'   => tohtml(tr('Reseller / Statistics / Overview')),
    'TR_USER'         => tohtml(tr('User'), 'htmlAttr'),
    'TR_TRAFF'        => tohtml(tr('Monthly traffic usage')),
    'TR_DISK'         => tohtml(tr('Disk usage')),
    'TR_WEB'          => tohtml(tr('HTTP traffic')),
    'TR_FTP_TRAFF'    => tohtml(tr('FTP traffic')),
    'TR_SMTP'         => tohtml(tr('SMTP traffic')),
    'TR_POP3'         => tohtml(tr('POP3/IMAP')),
    'TR_SUBDOMAIN'    => tohtml(tr('Subdomains')),
    'TR_ALIAS'        => tohtml(tr('Domain aliases')),
    'TR_MAIL'         => tohtml(tr('Mail accounts')),
    'TR_FTP'          => tohtml(tr('FTP accounts')),
    'TR_SQL_DB'       => tohtml(tr('SQL databases')),
    'TR_SQL_USER'     => tohtml(tr('SQL users')),
    'TR_USER_TOOLTIP' => tohtml(tr('Show detailed statistics for this user'), 'htmlAttr')
]);

Registry::get('iMSCP_Application')->getEventsManager()->registerListener(Events::onGetJsTranslations, function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
