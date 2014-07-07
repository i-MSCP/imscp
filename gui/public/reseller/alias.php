<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get table data
 *
 * @return array
 */
function reseller_getDatatable()
{
	$columns = array('alias_name', 'alias_mount', 'url_forward', 'admin_name', 'alias_status');
	$nbColumns = count($columns);

	/* Paging */
	$limit = '';

	if (isset($_GET['iDisplayStart']) && isset($_GET['iDisplayLength']) && $_GET['iDisplayLength'] != '-1') {
		$limit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
	}

	/* Ordering */
	$order = '';

	if (isset($_GET['iSortCol_0'])) {
		$order = 'ORDER BY ';

		if (isset($_GET['iSortingCols'])) {
			$iSortingCols = intval($_GET['iSortingCols']);

			for ($i = 0; $i < $iSortingCols; $i++) {
				if (
					isset($_GET['iSortCol_' . $i]) &&
					isset($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])]) &&
					$_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == 'true' &&
					isset($_GET['sSortDir_' . $i])
				) {
					$order .= $columns[intval($_GET['iSortCol_' . $i])] . ' ' . $_GET['sSortDir_' . $i] . ', ';
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

	if (isset($_GET['sSearch']) && $_GET['sSearch'] != '') {
		$where .= ' AND (';

		for ($i = 0; $i < $nbColumns; $i++) {
			$where .= "{$columns[$i]} LIKE " . quoteValue("%{$_GET['sSearch']}%") . ' OR ';
		}

		$where = substr_replace($where, '', -3);
		$where .= ')';
	}

	/* Individual column filtering */
	for ($i = 0; $i < $nbColumns; $i++) {
		if (
			isset($_GET["bSearchable_$i"]) && $_GET["bSearchable_$i"] == 'true' && isset($_GET["sSearch_$i"]) &&
			$_GET["sSearch_$i"] != ''
		) {
			$where .= "AND {$columns[$i]} LIKE " . quoteValue("%{$_GET["sSearch_$i"]}%");
		}
	}

	/* Get data to display */
	$rResult = execute_query(
		"
			SELECT
				SQL_CALC_FOUND_ROWS alias_id, " . implode(', ', $columns) . "
			FROM
				domain_aliasses
			INNER JOIN
				domain USING(domain_id)
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			$where
			$order
			$limit
		"
	);

	/* Total records after filtering (without limit) */
	$stmt = execute_query('SELECT FOUND_ROWS()');
	$iTotalDisplayRecords = $stmt->fetchRow(PDO::FETCH_NUM);
	$iTotalDisplayRecords = $iTotalDisplayRecords[0];

	/* Total record before any filtering */
	$stmt = exec_query(
		"
			SELECT
				COUNT(alias_id)
			FROM
				domain_aliasses
			INNER JOIN
				domain USING(domain_id)
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			WHERE
				created_by = ?
		",
		$_SESSION['user_id']
	);
	$iTotalRecords = $stmt->fetchRow(PDO::FETCH_NUM);
	$iTotalRecords = $iTotalRecords[0];

	/* Output */
	$output = array(
		'sEcho' => intval($_GET['sEcho']),
		'iTotalDisplayRecords' => $iTotalDisplayRecords,
		'iTotalRecords' => $iTotalRecords,
		'aaData' => array()
	);

	$trDelete = tr('Delete');
	$trEdit = tr('Edit');
	$trActivate = tr('Activate');

	while ($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
		$row = array();

		for ($i = 0; $i < $nbColumns; $i++) {
			if ($columns[$i] == 'alias_name') {
				if ($data['alias_status'] == 'ok') {
					$row[$columns[$i]] = '<a href="http://www.{NAME}/" target="_blank" class="icon i_domain_icon">' .
						decode_idna($data[$columns[$i]]) . '</a>';
				} else {
					$row[$columns[$i]] = '<span class="icon i_domain_icon">' . decode_idna($data[$columns[$i]]) .
						'</span>';
				}
			} elseif ($columns[$i] == 'admin_name') {
				$row[$columns[$i]] = tohtml(decode_idna($data[$columns[$i]]));
			} elseif ($columns[$i] == 'alias_status') {
				$row[$columns[$i]] = translate_dmn_status($data[$columns[$i]]);
			} else {
				$row[$columns[$i]] = tohtml($data[$columns[$i]]);
			}
		}

		$aliasId = $data['alias_id'];
		$aliasName = $data['alias_name'];

		switch ($data['alias_status']) {
			case 'ok':
				$actions = "<a href=\"alias_edit.php?id=$aliasId\" class=\"icon i_edit\" " .
					"title=\"$trEdit\">$trEdit</a>";

				$actions .= "\n<a href=\"alias_delete.php?id=$aliasId\" onclick=\"return delete_alias('$aliasName')\" " .
					"class=\"icon i_close\" title=\"$trDelete\">$trDelete</a>";
				break;
			case 'ordered':
				$actions = "<a href=\"alias_order.php?action=activate&act_id=$aliasId\" class=\"icon i_open\" " .
					"title=\"$trActivate\">$trActivate</a>";

				$actions .= "\n<a href=\"alias_order.php?action=delete&del_id=$aliasId\" " .
					"onclick=\"return delete_alias_order('$aliasName')\" class=\"icon i_close\" " .
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

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

resellerHasFeature('domain_aliases') or showBadRequestErrorPage();

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

$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/alias.tpl',
		'page_message' => 'layout',
		'als_add_button' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Customers / Domain Aliases'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ALIAS_NAME' => tr('Domain alias name'),
		'TR_MOUNT_POINT' => tr('Mount point'),
		'TR_FORWARD_URL' => tr('Forward URL'),
		'TR_STATUS' => tr('Status'),
		'TR_CUSTOMER' => tr('Customer'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_ADD_DOMAIN_ALIAS' => tr('Add domain alias'),
		'TR_MESSAGE_DELETE_ALIAS' => tr('Are you sure you want to delete the %s domain alias?', true, '%s'),
		'TR_MESSAGE_DELETE_ALIAS_ORDER' => tr('Are you sure you want to delete the %s domain alias order?', true, '%s'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_PROCESSING_DATA' => tr('Processing...')
	)
);

$resellerProps = imscp_getResellerProperties($resellerId);

if ($resellerProps['max_als_cnt'] != 0) {
	list(, , , , , , $customersAlsCount) = generate_reseller_user_props($resellerId);

	if (
		$customersAlsCount >= $resellerProps['max_als_cnt'] ||
		$resellerProps['current_als_cnt'] >= $resellerProps['max_als_cnt']
	) {
		$tpl->assign('ALS_ADD_BUTTON', '');
	}
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
