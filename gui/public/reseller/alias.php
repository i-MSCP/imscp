<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP Team
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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get table data
 *
 * @return array
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function reseller_getDatatable()
{
    $columns = ['alias_name', 'alias_mount', 'url_forward', 'admin_name', 'alias_status'];
    $columnAliases = ['t1.alias_name', 't1.alias_mount', 't1.url_forward', 't3.admin_name', 't1.alias_status'];
    $nbColumns = count($columns);

    /* Paging */
    $limit = '';

    if (isset($_GET['iDisplayStart'])
        && isset($_GET['iDisplayLength'])
        && $_GET['iDisplayLength'] != '-1'
    ) {
        $limit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
    }

    /* Ordering */
    $order = '';

    if (isset($_GET['iSortCol_0'])) {
        $order = 'ORDER BY ';

        if (isset($_GET['iSortingCols'])) {
            $iSortingCols = intval($_GET['iSortingCols']);

            for ($i = 0; $i < $iSortingCols; $i++) {
                if (isset($_GET['iSortCol_' . $i])
                    && isset($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])])
                    && $_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == 'true'
                    && isset($_GET['sSortDir_' . $i])
                    && in_array($_GET['sSortDir_' . $i], ['asc', 'desc'], true)
                ) {
                    $order .= $columnAliases[intval($_GET['iSortCol_' . $i])] . ' ' . $_GET['sSortDir_' . $i] . ', ';
                }
            }
        }

        $order = substr_replace($order, '', -2);
        if ($order == 'ORDER BY') {
            $order = '';
        }
    }

    /* Filtering */
    $where = 'WHERE created_by = ' . quoteValue($_SESSION['user_id'], PDO::PARAM_INT);

    if (isset($_GET['sSearch'])
        && $_GET['sSearch'] != ''
    ) {
        $where .= ' AND (';
        for ($i = 0; $i < $nbColumns; $i++) {
            $where .= "{$columnAliases[$i]} LIKE " . quoteValue("%{$_GET['sSearch']}%") . ' OR ';
        }

        $where = substr_replace($where, '', -3);
        $where .= ')';
    }

    /* Individual column filtering */
    for ($i = 0; $i < $nbColumns; $i++) {
        if (isset($_GET["bSearchable_$i"])
            && $_GET["bSearchable_$i"] == 'true'
            && isset($_GET["sSearch_$i"])
            && $_GET["sSearch_$i"] != ''
        ) {
            $where .= "AND {$columnAliases[$i]} LIKE " . quoteValue("%{$_GET["sSearch_$i"]}%");
        }
    }

    /* Get data to display */
    $rResult = execute_query(
        "
            SELECT SQL_CALC_FOUND_ROWS t1.alias_id, " . implode(', ', $columnAliases) . "
            FROM domain_aliasses AS t1
            JOIN domain AS t2 USING(domain_id)
            JOIN admin AS t3 ON(t3.admin_id = t2.domain_admin_id)
            $where $order $limit
        "
    );

    /* Total records after filtering (without limit) */
    $stmt = execute_query('SELECT FOUND_ROWS()');
    $iTotalDisplayRecords = $stmt->fetchRow(PDO::FETCH_NUM);
    $iTotalDisplayRecords = $iTotalDisplayRecords[0];

    /* Total record before any filtering */
    $stmt = exec_query(
        "
            SELECT COUNT(t1.alias_id) FROM domain_aliasses AS t1
            JOIN domain AS t2 USING(domain_id)
            JOIN admin AS t3 ON(t3.admin_id = t2.domain_admin_id)
            WHERE t3.created_by = ?
        ",
        $_SESSION['user_id']
    );
    $iTotalRecords = $stmt->fetchRow(PDO::FETCH_NUM);
    $iTotalRecords = $iTotalRecords[0];

    /* Output */
    $output = [
        'sEcho'                => intval($_GET['sEcho']),
        'iTotalDisplayRecords' => $iTotalDisplayRecords,
        'iTotalRecords'        => $iTotalRecords,
        'aaData'               => []
    ];

    $trDelete = tr('Delete');
    $trEdit = tr('Edit');
    $trActivate = tr('Activate');

    while ($data = $rResult->fetchRow()) {
        $row = [];
        $aliasName = decode_idna($data['alias_name']);

        for ($i = 0; $i < $nbColumns; $i++) {
            if ($columns[$i] == 'alias_name') {
                if ($data['alias_status'] == 'ok') {
                    $row[$columns[$i]] = "<a href=\"http://www.$aliasName\" target=\"_blank\" class=\"icon i_domain_icon\">" .
                        $aliasName . '</a>';
                } else {
                    $row[$columns[$i]] = '<span class="icon i_domain_icon">' . decode_idna($data[$columns[$i]]) .
                        '</span>';
                }
            } elseif ($columns[$i] == 't3.admin_name') {
                $row[$columns[$i]] = tohtml(decode_idna($data[$columns[$i]]));
            } elseif ($columns[$i] == 't1.alias_status') {
                $row[$columns[$i]] = translate_dmn_status($data[$columns[$i]]);
            } else {
                $row[$columns[$i]] = tohtml($data[$columns[$i]]);
            }
        }

        $aliasId = $data['alias_id'];

        switch ($data['alias_status']) {
            case 'ok':
                $actions = "<a href=\"alias_edit.php?id=$aliasId\" class=\"icon i_edit\" " .
                    "title=\"$trEdit\">$trEdit</a>";

                $actions .= "\n<a href=\"alias_delete.php?id=$aliasId\" onclick=\"return delete_alias(this, '" . tojs($aliasName) . "')\" " .
                    "class=\"icon i_close\" title=\"$trDelete\">$trDelete</a>";
                break;
            case 'ordered':
                $actions = "<a href=\"alias_order.php?action=activate&act_id=$aliasId\" class=\"icon i_open\" " .
                    "title=\"$trActivate\">$trActivate</a>";

                $actions .= "\n<a href=\"alias_order.php?action=delete&del_id=$aliasId\" " .
                    "onclick=\"return delete_alias_order(this, '" . tojs($aliasName) . "')\" class=\"icon i_close\" " .
                    "title=\"$trDelete\">$trDelete</a>";
                break;
            default;
                $actions = tr('n\a');
        }

        $row['actions'] = $actions;
        $output['aaData'][] = $row;
    }

    return $output;
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('reseller');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
resellerHasFeature('domain_aliases') && resellerHasCustomers() or showBadRequestErrorPage();

if (is_xhr()) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');
    header('Status: 200 OK');
    echo json_encode(reseller_getDatatable());
    exit;
}

/** @var $tpl iMSCP_pTemplate */
$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'         => 'shared/layouts/ui.tpl',
    'page'           => 'reseller/alias.tpl',
    'page_message'   => 'layout',
    'als_add_button' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                 => tr('Reseller / Customers / Domain Aliases'),
    'TR_ALIAS_NAME'                 => tr('Domain alias name'),
    'TR_MOUNT_POINT'                => tr('Mount point'),
    'TR_FORWARD_URL'                => tr('Forward URL'),
    'TR_STATUS'                     => tr('Status'),
    'TR_CUSTOMER'                   => tr('Customer'),
    'TR_ACTIONS'                    => tr('Actions'),
    'TR_ADD_DOMAIN_ALIAS'           => tr('Add domain alias'),
    'TR_MESSAGE_DELETE_ALIAS'       => tojs(tr('Are you sure you want to delete the %s domain alias?', '%s')),
    'TR_MESSAGE_DELETE_ALIAS_ORDER' => tojs(tr('Are you sure you want to delete the %s domain alias order?', '%s')),
    'TR_PROCESSING_DATA'            => tr('Processing...')
]);

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

$resellerProps = imscp_getResellerProperties($_SESSION['user_id']);

if ($resellerProps['max_als_cnt'] != 0
    && $resellerProps['current_als_cnt'] >= $resellerProps['max_als_cnt']
) {
    $tpl->assign('ALS_ADD_BUTTON', '');
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
