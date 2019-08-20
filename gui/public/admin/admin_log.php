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

use iMSCP\Database\DatabaseException;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\EventDescription;
use iMSCP\Event\Events;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

/**
 * Send JSON response
 *
 * @param int $statusCode
 * @param array $data
 */
function admin_sendJsonResponse($statusCode = 200, array $data = [])
{
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');

    switch ($statusCode) {
        case 202:
            header('Status: 202 Accepted');
            break;
        case 400:
            header('Status: 400 Bad Request');
            break;
        case 404:
            header('Status: 404 Not Found');
            break;
        case 500:
            header('Status: 500 Internal Server Error');
            break;
        case 501:
            header('Status: 501 Not Implemented');
            break;
        default:
            header('Status: 200 OK');
    }

    exit(json_encode($data));
}

/**
 * Clear logs
 *
 * @return void
 */
function admin_clearLogs()
{
    switch ($_POST['uaction_clear']) {
        case 0:
            $query = 'DELETE FROM log';
            $msg = sprintf('%s deleted the full admin log.', $_SESSION['user_logged']);
            break;
        case 2:
            $query = 'DELETE FROM log WHERE DATE_SUB(CURDATE(), INTERVAL 14 DAY) >= log_time';
            $msg = sprintf('%s deleted the admin log older than two weeks!', $_SESSION['user_logged']);
            break;
        case 4:
            $query = 'DELETE FROM log WHERE DATE_SUB(CURDATE(), INTERVAL 1 MONTH) >= log_time';
            $msg = sprintf('%s deleted the admin log older than one month.', $_SESSION['user_logged']);
            break;
        case 12:
            $query = 'DELETE FROM log WHERE DATE_SUB(CURDATE(), INTERVAL 3 MONTH) >= log_time';
            $msg = sprintf('%s deleted the admin log older than three months.', $_SESSION['user_logged']);
            break;

        case 26:
            $query = 'DELETE FROM log WHERE DATE_SUB(CURDATE(), INTERVAL 6 MONTH) >= log_time';
            $msg = sprintf('%s deleted the admin log older than six months.', $_SESSION['user_logged']);
            break;
        case 52;
            $query = 'DELETE FROM log WHERE DATE_SUB(CURDATE(), INTERVAL 1 YEAR) >= log_time';
            $msg = sprintf('%s deleted the admin log older than one year.', $_SESSION['user_logged']);
            break;
        default:
            admin_sendJsonResponse(400, ['message' => tr('Bad request.')]);
            exit;
    }

    try {
        $stmt = execute_query($query);

        if ($stmt->rowCount()) {
            write_log($msg, E_USER_NOTICE);
            admin_sendJsonResponse(200, ['message' => tr('Log entries successfully deleted.')]);
        } else {
            admin_sendJsonResponse(202, ['message' => tr('Nothing has been deleted.')]);
        }
    } catch (DatabaseException $e) {
        admin_sendJsonResponse(500, ['message' => tr('An unexpected error occurred: %s', $e->getMessage())]);
    }
}

/**
 * Get logs
 */
function admin_getLogs()
{
    try {
        // Filterable / orderable columns
        $columns = ['log_time', 'log_message'];

        $nbColumns = count($columns);

        $indexColumn = 'log_id';

        /* DB table to use */
        $table = 'log';

        /* Paging */
        $limit = '';

        if (isset($_GET['iDisplayStart']) && isset($_GET['iDisplayLength']) && $_GET['iDisplayLength'] !== '-1') {
            $limit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
        }

        /* Ordering */
        $order = '';

        if (isset($_GET['iSortCol_0']) && isset($_GET['iSortingCols'])) {
            $order = 'ORDER BY ';

            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] === 'true') {
                    $sortDir = (
                        isset($_GET['sSortDir_' . $i]) && in_array($_GET['sSortDir_' . $i], ['asc', 'desc'])
                    ) ? $_GET['sSortDir_' . $i] : 'asc';

                    $order .= $columns[intval($_GET['iSortCol_' . $i])] . ' ' . $sortDir . ', ';
                }
            }

            $order = substr_replace($order, '', -2);

            if ($order == 'ORDER BY ') {
                $order = '';
            }
        }

        /* Filtering */
        $where = '';

        if (isset($_GET['sSearch']) && $_GET['sSearch'] != '') {
            $where .= 'WHERE (';

            for ($i = 0; $i < $nbColumns; $i++) {
                $where .= $columns[$i] . ' LIKE ' . quoteValue('%' . $_GET['sSearch'] . '%') . ' OR ';
            }

            $where = substr_replace($where, '', -3);
            $where .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < $nbColumns; $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] === 'true' && $_GET['sSearch_' . $i] !== '') {
                $where .= "AND {$columns[$i]} LIKE " . quoteValue('%' . $_GET['sSearch_' . $i] . '%');
            }
        }

        /* Get data to display */
        $rResult = execute_query(
            '
                SELECT SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $columns)) . "
                FROM $table
                $where
                $order
                $limit
            "
        );

        /* Data set length after filtering */
        $resultFilterTotal = execute_query('SELECT FOUND_ROWS()');
        $resultFilterTotal = $resultFilterTotal->fetchRow(PDO::FETCH_NUM);
        $filteredTotal = $resultFilterTotal[0];

        /* Total data set length */
        $resultTotal = exec_query("SELECT COUNT($indexColumn) FROM $table");
        $resultTotal = $resultTotal->fetchRow(PDO::FETCH_NUM);
        $total = $resultTotal[0];

        /* Output */
        $output = [
            'sEcho'                => intval($_GET['sEcho']),
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filteredTotal,
            'aaData'               => []
        ];

        $dateFormat = Registry::get('config')['DATE_FORMAT'] . ' H:i:s';

        while ($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
            $row = [];

            for ($i = 0; $i < $nbColumns; $i++) {
                if ($columns[$i] == 'log_time') {
                    $row[$columns[$i]] = date($dateFormat, strtotime($data[$columns[$i]]));
                } else {
                    $replaces = [
                        '/\b(deactivated|delete[sd]?|deletion|deactivation|failed)\b/i' => '<strong style="color:#FF0000">\\1</strong>',
                        '/\b(remove[sd]?)\b/i'                                          => '<strong style="color:#FF0000">\\1</strong>',
                        '/\b(unable)\b/i'                                               => ' <strong style="color:#FF0000">\\1</strong>',
                        '/\b(activated|activation|addition|add(s|ed)?|switched)\b/i'    => '<strong style="color:#33CC66">\\1</strong>',
                        '/\b(created|ordered)\b/i'                                      => '<strong style="color:#3300FF">\\1</strong>',
                        '/\b(update[sd]?)\b/i'                                          => '<strong style="color:#3300FF">\\1</strong>',
                        '/\b(edit(s|ed)?)\b/i'                                          => '<strong style="color:#33CC66">\\1</strong>',
                        '/\b(unknown)\b/i'                                              => '<strong style="color:#CC00FF">\\1</strong>',
                        '/\b(logged)\b/i'                                               => '<strong style="color:#336600">\\1</strong>',
                        '/\b(Warning[\!]?)\b/i'                                         => '<strong style="color:#FF0000">\\1</strong>',
                    ];

                    foreach ($replaces as $pattern => $replacement) {
                        $data[$columns[$i]] = preg_replace($pattern, $replacement, $data[$columns[$i]]);
                    }

                    $row[$columns[$i]] = $data[$columns[$i]];
                }
            }

            $output['aaData'][] = $row;
        }

        admin_sendJsonResponse(200, $output);
    } catch (DatabaseException $e) {
        write_log(sprintf('Unable to get logs: %s', $e->getMessage()), E_USER_ERROR);

        admin_sendJsonResponse(
            500, ['message' => tr('An unexpected error occurred: %s', $e->getMessage())]
        );
    }

    admin_sendJsonResponse(400, ['message' => tr('Bad request.')]);
}

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

if (isset($_REQUEST['action'])) {
    if (is_xhr()) {
        switch (clean_input($_REQUEST['action'])) {
            case 'get_logs':
                admin_getLogs();
                break;
            case 'clear_logs':
                admin_clearLogs();
                break;
            default:
                admin_sendJsonResponse(400, ['message' => tr('Bad request.')]);
        }
    }

    showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/admin_log.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'           => tr('Admin / General / Admin Log'),
    'TR_CLEAR_LOG'            => tr('Clear log'),
    'ROWS_PER_PAGE'           => json_encode(Registry::get('config')['DOMAIN_ROWS_PER_PAGE']),
    'TR_DATE'                 => tr('Date'),
    'TR_MESSAGE'              => tr('Message'),
    'TR_CLEAR_LOG_MESSAGE'    => tr('Delete from log:'),
    'TR_CLEAR_LOG_EVERYTHING' => tr('everything'),
    'TR_CLEAR_LOG_LAST2'      => tr('older than 2 weeks'),
    'TR_CLEAR_LOG_LAST4'      => tr('older than 1 month'),
    'TR_CLEAR_LOG_LAST12'     => tr('older than 3 months'),
    'TR_CLEAR_LOG_LAST26'     => tr('older than 6 months'),
    'TR_CLEAR_LOG_LAST52'     => tr('older than 12 months'),
    'TR_LOADING_DATA'         => tr('Loading data...'),
    'TR_TIMEOUT_ERROR'        => json_encode(tr('Request Timeout: The server took too long to send the data.')),
    'TR_UNEXPECTED_ERROR'     => json_encode(tr('An unexpected error occurred.'))
]);

EventAggregator::getInstance()->registerListener('onGetJsTranslations', function (EventDescription $e) {
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
