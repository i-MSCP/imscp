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
 * Get traffic for the given domain and the given period
 *
 * @param int $domainId User main domain unique identifier
 * @param int $startDate An UNIX timestamp representing a start date
 * @param int $endDate An UNIX timestamp representing an end date
 * @return array
 */
function getUserTraffic($domainId, $startDate, $endDate)
{
    static $stmt;

    if (NULL === $stmt) {
        /** @var iMSCP_Database $db */
        $db = Registry::get('iMSCP_Application')->getDatabase();
        $stmt = $db->prepare(
            '
                SELECT IFNULL(SUM(dtraff_web), 0) AS web_traffic,
                    IFNULL(SUM(dtraff_ftp), 0) AS ftp_traffic,
                    IFNULL(SUM(dtraff_mail), 0) AS smtp_traffic,
                    IFNULL(SUM(dtraff_pop),0) AS pop_traffic
                FROM domain_traffic
                WHERE domain_id = ?
                AND dtraff_time BETWEEN ? AND ?
            '
        );
    }

    $stmt->execute([$domainId, $startDate, $endDate]);

    if (!$stmt->rowCount()) {
        return array_fill(0, 4, 0);
    }

    $row = $stmt->fetch();

    return [$row['web_traffic'], $row['ftp_traffic'], $row['smtp_traffic'], $row['pop_traffic']];
}

/**
 * Generate statistics for the given period
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $domainId = get_user_domain_id($_SESSION['user_id']);
    $month = isset($_GET['month']) ? filter_digits($_GET['month']) : date('n');
    $year = isset($_GET['year']) ? filter_digits($_GET['year']) : date('Y');
    $stmt = exec_query(
        'SELECT dtraff_time FROM domain_traffic WHERE domain_id = ? ORDER BY dtraff_time ASC LIMIT 1', [$domainId]
    );
    $nPastYears = $stmt->rowCount() ? date('Y') - date('Y', $stmt->fetchColumn()) : 0;

    generateDMYlists($tpl, 0, $month, $year, $nPastYears);

    $stmt = exec_query(
        'SELECT domain_id FROM domain_traffic WHERE domain_id = ? AND dtraff_time BETWEEN ? AND ? LIMIT 1',
        [$domainId, getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year)]
    );

    if (!$stmt->rowCount()) {
        set_page_message(tr('No statistics found for the given period. Try another period.'), 'static_info');
        $tpl->assign('STATISTICS_BLOCK', '');
        return;
    }

    $requestedPeriod = getLastDayOfMonth($month, $year);
    $toDay = ($requestedPeriod < time()) ? date('j', $requestedPeriod) : date('j');
    $all = array_fill(0, 8, 0);
    $dateFormat = Registry::get('config')['DATE_FORMAT'];

    for ($fromDay = 1; $fromDay <= $toDay; $fromDay++) {
        $startDate = mktime(0, 0, 0, $month, $fromDay, $year);
        $endDate = mktime(23, 59, 59, $month, $fromDay, $year);
        list($webTraffic, $ftpTraffic, $smtpTraffic, $popTraffic) = getUserTraffic($domainId, $startDate, $endDate);
        $tpl->assign([
            'DATE'       => tohtml(date($dateFormat, strtotime($year . '-' . $month . '-' . $fromDay))),
            'WEB_TRAFF'  => tohtml(bytesHuman($webTraffic)),
            'FTP_TRAFF'  => tohtml(bytesHuman($ftpTraffic)),
            'SMTP_TRAFF' => tohtml(bytesHuman($smtpTraffic)),
            'POP_TRAFF'  => tohtml(bytesHuman($popTraffic)),
            'SUM_TRAFF'  => tohtml(bytesHuman($webTraffic + $ftpTraffic + $smtpTraffic + $popTraffic))
        ]);
        $all[0] += $webTraffic;
        $all[1] += $ftpTraffic;
        $all[2] += $smtpTraffic;
        $all[3] += $popTraffic;
        $tpl->parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');
    }

    $tpl->assign([
        'WEB_ALL'  => tohtml(bytesHuman($all[0])),
        'FTP_ALL'  => tohtml(bytesHuman($all[1])),
        'SMTP_ALL' => tohtml(bytesHuman($all[2])),
        'POP_ALL'  => tohtml(bytesHuman($all[3])),
        'SUM_ALL'  => tohtml(bytesHuman(array_sum($all)))
    ]);

}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'             => 'shared/layouts/ui.tpl',
    'page'               => 'client/traffic_statistics.tpl',
    'page_message'       => 'layout',
    'month_list'         => 'page',
    'year_list'          => 'page',
    'statistics_block'   => 'page',
    'traffic_table_item' => 'statistics_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tohtml(tr('Client / Statistics')),
    'TR_STATISTICS' => tohtml(tr('Statistics')),
    'TR_MONTH'      => tohtml(tr('Month')),
    'TR_YEAR'       => tohtml(tr('Year')),
    'TR_SHOW'       => tohtml(tr('Show')),
    'TR_WEB_TRAFF'  => tohtml(tr('Web traffic')),
    'TR_FTP_TRAFF'  => tohtml(tr('FTP traffic')),
    'TR_SMTP_TRAFF' => tohtml(tr('SMTP traffic')),
    'TR_POP_TRAFF'  => tohtml(tr('POP3/IMAP traffic')),
    'TR_SUM'        => tohtml(tr('All traffic')),
    'TR_ALL'        => tohtml(tr('All')),
    'TR_DATE'       => tohtml(tr('Date'))
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
