<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generates domain aliases list.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  int $resellerId Reseller unique identifier
 * @return void
 */
function reseller_generateAlsList($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$resellerProps = imscp_getResellerProperties($resellerId);

	if ($resellerProps['max_als_cnt'] != 0) {
		list(, , , , , , $customersAlsCount) = generate_reseller_user_props($resellerId);

		if(
			$customersAlsCount >= $resellerProps['max_als_cnt'] ||
			$resellerProps['current_als_cnt'] >= $resellerProps['max_als_cnt']
		) {
			$tpl->assign('ALS_ADD_BUTTON', '');
		}
	}

	$startIndex = 0;
	$rowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;
	$currentPsi = 0;
	$_SESSION['search_for'] = '';
	$searchCommon = '';
	$searchFor = '';

	if (isset($_GET['psi'])) {
		$startIndex = $_GET['psi'];
		$currentPsi = $_GET['psi'];
	}

	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {
		$_SESSION['search_for'] = trim(clean_input($_POST['search_for']));
		$_SESSION['search_common'] = $_POST['search_common'];
		$searchFor = $_SESSION['search_for'];
		$searchCommon = $_SESSION['search_common'];
	} elseif (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
		unset($_SESSION['search_for']);
		unset($_SESSION['search_common']);
	}

	$tpl->assign(
		array(
			'PSI' => $currentPsi,
			'SEARCH_FOR' => tohtml($searchFor),
			'TR_SEARCH' => tr('Search'),
			'M_ALIAS_NAME' => tr('Alias name'),
			'M_ACCOUNT_NAME' => tr('Account name')
		)
	);

	if (isset($_SESSION['search_for']) && $_SESSION['search_for'] != '') {
		if (isset($searchCommon) && $searchCommon == 'alias_name') {

			$query = "
				SELECT
					t1.*, t2.domain_id, t2.domain_name
				FROM
					domain_aliasses AS t1
				INNER JOIN
					domain AS t2 USING(domain_id)
				INNER JOIN
					admin AS t3 ON(t3.admin_id = t2.domain_admin_id)
				WHERE
					t3.created_by = ?
				ORDER BY
					t1.alias_name
				LIMIT
					$startIndex, $rowsPerPage
			";

			// count query
			$count_query = "
				SELECT
					COUNT(alias_id) AS cnt
				FROM
					domain_aliasses
				INNER JOIN
					domain USING(domain_id)
				INNER JOIN
					admin ON(admin_id = domain_admin_id)
				WHERE
					created_by = ?
				AND
					alias_name RLIKE '$searchFor'
			";
		} else {
			$query = "
				SELECT
					t1.*, t2.domain_id, t2.domain_name
				FROM
					domain_aliasses AS t1
				INNER JOIN
					domain AS t2 USING(domain_id)
				INNER JOIN
					admin AS t3 ON(t3.admin_id = t2.domain_admin_id)
				WHERE
					t3.created_by = ?
				AND
					t2.domain_name RLIKE '$searchFor'
				ORDER BY
					t1.alias_name ASC
				LIMIT
					$startIndex, $rowsPerPage
			";

			// count query
			$count_query = "
				SELECT
					COUNT(alias_id) AS cnt
				FROM
					domain_aliasses
				INNER JOIN
					domain USING(domain_id)
				INNER JOIN
					admin ON(admin_id = domain_admin_id)
				WHERE
					created_by = ?
				AND
					domain_name RLIKE '$searchFor'
			";
		}
	} else {
		$query = "
			SELECT
				t1.*, t2.domain_id, t2.domain_name
			FROM
				domain_aliasses AS t1
			INNER JOIN
				domain AS t2 USING(domain_id)
			INNER JOIN
				admin AS t3 ON(t3.admin_id = t2.domain_admin_id)
			WHERE
				t3.created_by = ?
			ORDER BY
				t1.alias_name ASC
			LIMIT
				$startIndex, $rowsPerPage
		";

		// count query
		$count_query = "
			SELECT
				COUNT(alias_id) AS cnt
			FROM
				domain_aliasses
			INNER JOIN
				domain USING(domain_id)
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			AND
				created_by = ?
		";
	}

	// let's count
	$stmt = exec_query($count_query, $resellerId);
	$recordCount = $stmt->fields['cnt'];

	// Get all alias records
	$stmt = exec_query($query, $resellerId);

	if (!$recordCount) {
		if (isset($_SESSION['search_for']) && $_SESSION['search_for'] != '') {
			$tpl->assign(
				array(
					'ALIAS_JS' => '',
					'ALIAS_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'M_DOMAIN_NAME_SELECTED' => '',
					'M_ACCOUN_NAME_SELECTED' => ''
				)
			);
		} else {
			$tpl->assign(
				array(
					'ALIAS_JS' => '',
					'SEARCH_FORM' => '',
					'ALIAS_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_PREV_GRAY' => '',
					'SCROLL_NEXT' => '',
					'SCROLL_NEXT_GRAY' => ''
				)
			);
		}

		if (isset($_SESSION['search_for'])) {
			set_page_message(tr('No records found matching the search criteria.', 'info'));
		} else {
			set_page_message(tr('You do not have any orders for domain aliases.'), 'info');
		}
		return;
	} else {
		$prevSi = $startIndex - $rowsPerPage;

		if ($startIndex == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prevSi
				)
			);
		}

		$nextSi = $startIndex + $rowsPerPage;

		if ($nextSi + 1 > $recordCount) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $nextSi
				)
			);
		}
	}

	while (!$stmt->EOF) {
		$alsId = $stmt->fields['alias_id'];
		$alsName = $stmt->fields['alias_name'];
		$alsMountPoint = ($stmt->fields['alias_mount'] != '') ? $stmt->fields['alias_mount'] : '/';
		$alsStatus = $stmt->fields['alias_status'];
		$alsForward = $stmt->fields['url_forward'];
		$showAlsForward = ($alsForward == 'no') ? '-' : $alsForward;
		$dmnName = decode_idna($stmt->fields['domain_name']);


		if ($alsStatus === 'ok') {
			$deleteLink = "alias_delete.php?id=" . $alsId;
			$editLink = "alias_edit.php?id=" . $alsId;
			$actionText = tr('Delete');
			$editText = tr('Edit');
			$statusBool = true;
		} elseif ($alsStatus == 'ordered') {
			$deleteLink = 'alias_order.php?action=delete&del_id=' . $alsId;
			$editLink = 'alias_order.php?action=activate&act_id=' . $alsId;
			$actionText = tr('Delete order');
			$editText = tr('Activate');
			$statusBool = false;
		} else {
			$deleteLink = '#';
			$editLink = '#';
			$actionText = tr('N/A');
			$editText = tr('N/A');
			$statusBool = false;
		}

		$alsStatus = translate_dmn_status($alsStatus);
		$alsName = decode_idna($alsName);
		$showAlsForward = decode_idna($showAlsForward);

		if (isset($_SESSION['search_common'])
			&& $_SESSION['search_common'] == 'account_name'
		) {
			$dmnNameSelected = '';
			$accountNameSelected = $cfg->HTML_SELECTED;
		} else {
			$dmnNameSelected = $cfg->HTML_SELECTED;
			$accountNameSelected = '';
		}

		if ($statusBool == false) {
			$tpl->assign('STATUS_RELOAD_TRUE', '');
			$tpl->assign('NAME', tohtml($alsName));
			$tpl->parse('STATUS_RELOAD_FALSE', 'status_reload_false');
		} else {
			$tpl->assign('STATUS_RELOAD_FALSE', '');
			$tpl->assign('NAME', tohtml($alsName));
			$tpl->parse('STATUS_RELOAD_TRUE', 'status_reload_true');
		}

		$tpl->assign(
			array(
				'NAME' => tohtml($alsName),
				'OWNER' => tohtml($dmnName),
				'MOUNT_POINT' => tohtml($alsMountPoint),
				'FORWARD' => tohtml($showAlsForward),
				'STATUS' => $alsStatus,
				'ID' => $alsId,
				'DELETE' => $actionText,
				'DELETE_LINK' => $deleteLink,
				'EDIT_LINK' => $editLink,
				'EDIT' => $editText,
				'M_DOMAIN_NAME_SELECTED' => $dmnNameSelected,
				'M_ACCOUN_NAME_SELECTED' => $accountNameSelected
			)
		);

		$tpl->parse('ALIAS_ITEM', '.alias_item');
		$stmt->moveNext();
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

resellerHasFeature('domain_aliases') or showBadRequestErrorPage();

/** @var $tpl iMSCP_pTemplate */
$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/alias.tpl',
		'page_message' => 'layout',
		'alias_js' => 'page',
		'alias_list' => 'page',
		'alias_item' => 'alias_list',
		'status_reload_true' => 'alias_item',
		'status_reload_false' => 'alias_item',
		'scroll_prev' => 'alias_list',
		'scroll_next_gray' => 'alias_list',
		'scroll_next' => 'alias_list',
		'als_add_button' => 'alias_list'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Customers / Domain Aliases'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_NAME' => tr('Name'),
		'TR_MOUNT_POINT' => tr('Mount point'),
		'TR_FORWARD' => tr('Forward URL'),
		'TR_STATUS' => tr('Status'),
		'TR_OWNER' => tr('Owner'),
		'TR_ACTION' => tr('Actions'),
		'TR_ADD_DOMAIN_ALIAS' => tr('Add domain alias'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')
	)
);

generateNavigation($tpl);
reseller_generateAlsList($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
