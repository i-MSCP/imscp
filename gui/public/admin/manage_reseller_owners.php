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
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/manage_reseller_owners.tpl',
		'page_message' => 'layout',
		'hosting_plans' => 'page',
		'reseller_list' => 'page',
		'reseller_item' => 'reseller_list',
		'select_admin' => 'page',
		'select_admin_option' => 'select_admin'));

/**
 * @todo check if it's useful to have the table admin two times in the same query
 */
function gen_reseller_table($tpl) {

	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			t1.`admin_id`, t1.`admin_name`, t2.`admin_name` AS created_by
		FROM
			`admin` AS t1, `admin` AS t2
		WHERE
			t1.`admin_type` = 'reseller'
		AND
			t1.`created_by` = t2.`admin_id`
		ORDER BY
			`created_by`, `admin_id`
	";

	$rs = execute_query($query);

	$i = 0;

	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'MESSAGE' => tr('Reseller list is empty.'),
				'RESELLER_LIST' => '',
			)
		);

		$tpl->parse('PAGE_MESSAGE', 'page_message');
	} else {
		while (!$rs->EOF) {

			$admin_id = $rs->fields['admin_id'];

			$admin_id_var_name = "admin_id_".$admin_id;

			$tpl->assign(
				array(
					'NUMBER' => $i + 1,
					'RESELLER_NAME' => tohtml($rs->fields['admin_name']),
					'OWNER' => tohtml($rs->fields['created_by']),
					'CKB_NAME' => $admin_id_var_name,
				)
			);

			$tpl->parse('RESELLER_ITEM', '.reseller_item');

			$rs->moveNext();

			$i++;
		}

		$tpl->parse('RESELLER_LIST', 'reseller_list');

		$tpl->assign('PAGE_MESSAGE', '');
	}

	$query = "
		SELECT
			`admin_id`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_type` = 'admin'
		ORDER BY
			`admin_name`
	";

	$rs = execute_query($query);

	while (!$rs->EOF) {

		if ((isset($_POST['uaction']) && $_POST['uaction'] === 'reseller_owner') && (isset($_POST['dest_admin']) &&
			$_POST['dest_admin'] == $rs->fields['admin_id'])
		) {
			$selected = $cfg->HTML_SELECTED;
		} else {
			$selected = '';
		}

		$tpl->assign(
			array(
				'OPTION' => tohtml($rs->fields['admin_name']),
				'VALUE' => $rs->fields['admin_id'],
				'SELECTED' => $selected));

		$tpl->parse('SELECT_ADMIN_OPTION', '.select_admin_option');

		$rs->moveNext();

		$i++;
	}

	$tpl->parse('SELECT_ADMIN', 'select_admin');

	$tpl->assign('PAGE_MESSAGE', '');
}

/**
 *
 */
function update_reseller_owner() {

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'reseller_owner') {
		$query = "
			SELECT
				`admin_id`
			FROM
				`admin`
			WHERE
				`admin_type` = 'reseller'
			ORDER BY
				`admin_name`
		";
		$rs = execute_query($query);

		while (!$rs->EOF) {
			$admin_id = $rs->fields['admin_id'];

			$admin_id_var_name = "admin_id_$admin_id";

			if (isset($_POST[$admin_id_var_name]) && $_POST[$admin_id_var_name] === 'on') {
				$dest_admin = $_POST['dest_admin'];

				$query = "
					UPDATE
						`admin`
					SET
						`created_by` = ?
					WHERE
						`admin_id` = ?
				";

				exec_query($query, array($dest_admin, $admin_id));
			}

			$rs->moveNext();
		}
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Users / Resellers Assignment'),
		'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);
update_reseller_owner();
gen_reseller_table($tpl);

$tpl->assign(
	array(
		'TR_RESELLER_ASSIGNMENT' => tr('Reseller assignment'),
		'TR_RESELLER_USERS' => tr('Reseller users'),
		'TR_NUMBER' => tr('No.'),
		'TR_MARK' => tr('Mark'),
		'TR_RESELLER_NAME' => tr('Reseller name'),
		'TR_OWNER' => tr('Owner'),
		'TR_TO_ADMIN' => tr('To Admin'),
		'TR_MOVE' => tr('Move')));

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
