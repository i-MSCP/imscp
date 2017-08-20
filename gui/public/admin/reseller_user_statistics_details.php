<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_pTemplate as TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get traffic data for the given user and period
 *
 * @param int $domainId User main domain unique identifier
 * @param int $startDate An UNIX timestamp representing a start date
 * @param int $endDate An UNIX timestamp representing an end date
 * @return array
 */
function getUserTraffic($domainId, $startDate, $endDate)
{
    $stmt = exec_query(
        '
            SELECT IFNULL(SUM(dtraff_web), 0) AS web_traffic,
                IFNULL(SUM(dtraff_ftp), 0) AS ftp_traffic,
                IFNULL(SUM(dtraff_mail), 0) AS smtp_traffic,
                IFNULL(SUM(dtraff_pop),0) AS pop_traffic
            FROM domain_traffic
            WHERE domain_id = ?
            AND dtraff_time BETWEEN ? AND ?
        ',
        [$domainId, $startDate, $endDate]
    );

    if (!$stmt->rowCount()) {
        return [0, 0, 0, 0];
    }

    $row = $stmt->fetchRow();

    return [$row['web_traffic'], $row['ftp_traffic'], $row['smtp_traffic'], $row['pop_traffic']];
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $userId = intval($_GET['user_id']);

    $stmt = exec_query(
        'SELECT admin_name, domain_id FROM admin JOIN domain ON(domain_admin_id = admin_id) WHERE admin_id = ?', $userId
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow();
    $domainId = $row['domain_id'];
    $adminName = decode_idna($row['admin_name']);

    if (isset($_GET['month']) && isset($_GET['year'])) {
        $year = filter_digits($_GET['year']);
        $month = filter_digits($_GET['month']);
    } else {
        $month = date('m');
        $year = date('y');
    }

    $stmt = exec_query(
        'SELECT dtraff_time FROM domain_traffic WHERE domain_id = ? ORDER BY dtraff_time ASC LIMIT 1', $domainId
    );

    if ($stmt->rowCount()) {
        $numberYears = date('y') - date('y', $stmt->fetchRow(PDO::FETCH_COLUMN));
        $numberYears = $numberYears ? $numberYears + 1 : 1;
    } else {
        $numberYears = 1;
    }

    generateDMYlists($tpl, NULL, $month, $year, $numberYears);

    $stmt = exec_query(
        'SELECT COUNT(dtraff_id) FROM domain_traffic WHERE domain_id = ? AND dtraff_time BETWEEN ? AND ?',
        [$domainId, getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year)]
    );

    if ($stmt->fetchRow(PDO::FETCH_COLUMN) < 1) {
        set_page_message(tr('No statistics found for the given period. Try another period.'), 'static_info');
        $tpl->assign([
            'USERNAME'                              => tohtml($adminName),
            'USER_ID'                               => tohtml($userId),
            'RESELLER_USER_STATISTICS_DETAIL_BLOCK' => ''
        ]);
        return;
    }
    $requestedPeriod = getLastDayOfMonth($month, $year);
    $toDay = ($requestedPeriod < time()) ? date('j', $requestedPeriod) : date('j');
    $dateFormat = iMSCP_Registry::get('config')['DATE_FORMAT'];
    $all = array_fill(0, 8, 0);

    for ($fromDay = 1; $fromDay <= $toDay; $fromDay++) {
        $startDate = mktime(0, 0, 0, $month, $fromDay, $year);
        $endDate = mktime(23, 59, 59, $month, $fromDay, $year);

        list($webTraffic, $ftpTraffic, $smtpTraffic, $popTraffic) = getUserTraffic($domainId, $startDate, $endDate);

        $tpl->assign([
            'DATE'         => date($dateFormat, strtotime($year . '-' . $month . '-' . $fromDay)),
            'WEB_TRAFFIC'  => tohtml(bytesHuman($webTraffic)),
            'FTP_TRAFFIC'  => tohtml(bytesHuman($ftpTraffic)),
            'SMTP_TRAFFIC' => tohtml(bytesHuman($smtpTraffic)),
            'POP3_TRAFFIC' => tohtml(bytesHuman($popTraffic)),
            'ALL_TRAFFIC'  => tohtml(bytesHuman($webTraffic + $ftpTraffic + $smtpTraffic + $popTraffic))
        ]);

        $all[0] += $webTraffic;
        $all[1] += $ftpTraffic;
        $all[2] += $smtpTraffic;
        $all[3] += $popTraffic;

        $tpl->parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');
    }

    $tpl->assign([
        'USERNAME'         => tohtml($adminName),
        'USER_ID'          => tohtml($userId, 'htmlAttr'),
        'ALL_WEB_TRAFFIC'  => tohtml(bytesHuman($all[0])),
        'ALL_FTP_TRAFFIC'  => tohtml(bytesHuman($all[1])),
        'ALL_SMTP_TRAFFIC' => tohtml(bytesHuman($all[2])),
        'ALL_POP3_TRAFFIC' => tohtml(bytesHuman($all[3])),
        'ALL_ALL_TRAFFIC'  => tohtml(bytesHuman(array_sum($all)))
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

EventsManager::getInstance()->dispatch(Events::onAdminScriptStart);
check_login('admin');

if (!systemHasCustomers()
    || !isset($_GET['user_id'])
) {
    showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                                => 'shared/layouts/ui.tpl',
    'page'                                  => 'admin/reseller_user_statistics_details.tpl',
    'page_message'                          => 'layout',
    'month_list'                            => 'page',
    'year_list'                             => 'page',
    'reseller_user_statistics_detail_block' => 'page',
    'traffic_table_item'                    => 'reseller_user_statistics_detail_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE'   => tohtml(tr("Admin / Statistics / Reseller Statistics / User Statistics / {USERNAME} user statistics")),
    'TR_MONTH'        => tohtml(tr('Month')),
    'TR_YEAR'         => tohtml(tr('Year')),
    'TR_WEB_TRAFFIC'  => tohtml(tr('Web traffic')),
    'TR_FTP_TRAFFIC'  => tohtml(tr('FTP traffic')),
    'TR_SMTP_TRAFFIC' => tohtml(tr('SMTP traffic')),
    'TR_POP3_TRAFFIC' => tohtml(tr('POP3/IMAP traffic')),
    'TR_ALL_TRAFFIC'  => tohtml(tr('All traffic')),
    'TR_ALL'          => tohtml(tr('All')),
    'TR_DAY'          => tohtml(tr('Day'))
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
