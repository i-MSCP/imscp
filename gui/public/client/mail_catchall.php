<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/mail_catchall.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('catchall_message', 'page');
$tpl->define_dynamic('catchall_item', 'page');

/**
 * @param $mail_id
 * @param $mail_status
 * @return array
 */
function gen_user_mail_action($mail_id, $mail_status) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($mail_status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete'), "mail_delete.php?id=$mail_id", "mail_edit.php?id=$mail_id");
	} else {
		return array(tr('N/A'), '#', '#');
	}
}

/**
 * @param $mail_id
 * @param $mail_status
 * @return array|null
 */
function gen_user_catchall_action($mail_id, $mail_status) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($mail_status === $cfg->ITEM_ADD_STATUS) {
		return array(tr('N/A'), '#'); // Addition in progress
	} else if ($mail_status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete CatchAll'), "mail_catchall_delete.php?id=$mail_id");
	} else if ($mail_status === $cfg->ITEM_CHANGE_STATUS) {
		return array(tr('N/A'), '#');
	} else if ($mail_status === $cfg->ITEM_DELETE_STATUS) {
		return array(tr('N/A'), '#');
	} else {
		return null;
	}
}

/**
 * @param $tpl
 * @param $action
 * @param $dmn_id
 * @param $dmn_name
 * @param $mail_id
 * @param $mail_acc
 * @param $mail_status
 * @param $ca_type
 * @return void
 */
function gen_catchall_item(&$tpl, $action, $dmn_id, $dmn_name, $mail_id, $mail_acc,
	$mail_status, $ca_type)
{
	$show_dmn_name = decode_idna($dmn_name);

	if ($action === 'create') {
		$tpl->assign(
			array(
				'CATCHALL_DOMAIN' => tohtml($show_dmn_name),
				'CATCHALL_ACC' => tr('None'),
				'CATCHALL_STATUS' => tr('N/A'),
				'CATCHALL_ACTION' => tr('Create catch all'),
				'CATCHALL_ACTION_SCRIPT' => "mail_catchall_add.php?id=$dmn_id;$ca_type"));
	} else {
		list($catchall_action, $catchall_action_script) = gen_user_catchall_action($mail_id, $mail_status);

		$show_dmn_name = decode_idna($dmn_name);
		$show_mail_acc = decode_idna($mail_acc);

		$tpl->assign(
			array(
				'CATCHALL_DOMAIN' => tohtml($show_dmn_name),
				'CATCHALL_ACC' => tohtml($show_mail_acc),
				'CATCHALL_STATUS' => translate_dmn_status($mail_status),
				'CATCHALL_ACTION' => $catchall_action,
				'CATCHALL_ACTION_SCRIPT' => $catchall_action_script));
	}
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $dmn_name
 */
function gen_page_catchall_list($tpl, $dmn_id, $dmn_name) {

	$tpl->assign('CATCHALL_MESSAGE', '');

		$query = "
			SELECT
				`mail_id`, `mail_acc`, `status`
			FROM
				`mail_users`
			WHERE
				`domain_id` = '$dmn_id'
			AND
				`sub_id` = 0
			AND
				`mail_type` = 'normal_catchall'
		";

		$rs = execute_query($query);

		if ($rs->recordCount() == 0) {
			gen_catchall_item($tpl, 'create', $dmn_id, $dmn_name, '', '', '', 'normal');
		} else {
			gen_catchall_item(
				$tpl,
				'delete',
				$dmn_id,
				$dmn_name,
				$rs->fields['mail_id'],
				$rs->fields['mail_acc'],
				$rs->fields['status'], 'normal');
		}

		$tpl->parse('CATCHALL_ITEM', 'catchall_item');

		$query = "
			SELECT
				`alias_id`, `alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = '$dmn_id'
			AND
				`alias_status` = 'ok'
		";

		$rs = execute_query($query);

		while (!$rs->EOF) {
			$als_id = $rs->fields['alias_id'];
			$als_name = $rs->fields['alias_name'];

			$query = "
				SELECT
					`mail_id`, `mail_acc`, `status`
				FROM
					`mail_users`
				WHERE
					`domain_id` = '$dmn_id'
				AND
					`sub_id` = '$als_id'
				AND
					`mail_type` = 'alias_catchall'
			";

			$rs_als = execute_query($query);

			if ($rs_als->recordCount() == 0) {
				gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'alias');
			} else {
				gen_catchall_item(
					$tpl,
					'delete',
					$als_id,
					$als_name,
					$rs_als->fields['mail_id'],
					$rs_als->fields['mail_acc'],
					$rs_als->fields['status'], 'alias'
				);
			}

			$tpl->parse('CATCHALL_ITEM', '.catchall_item');

			$rs->moveNext();
		}

		$query = "
			SELECT
				a.`subdomain_alias_id`, CONCAT(a.`subdomain_alias_name`,'.',b.`alias_name`) AS `subdomain_name`
			FROM
				`subdomain_alias` AS a, `domain_aliasses` AS b
			WHERE
				b.`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = '$dmn_id')
			AND
				a.`alias_id` = b.`alias_id`
			AND
				a.`subdomain_alias_status` = 'ok'
		";

		$rs = execute_query($query);

		while (!$rs->EOF) {
			$als_id = $rs->fields['subdomain_alias_id'];
			$als_name = $rs->fields['subdomain_name'];

			$query = "
				SELECT
					`mail_id`, `mail_acc`, `status`
				FROM
					`mail_users`
				WHERE
					`domain_id` = '$dmn_id'
				AND
					`sub_id` = '$als_id'
				AND
					`mail_type` = 'alssub_catchall'
			";

			$rs_als = execute_query($query);

			if ($rs_als->recordCount() == 0) {
				gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'alssub');
			} else {
				gen_catchall_item(
					$tpl,
					'delete',
					$als_id,
					$als_name,
					$rs_als->fields['mail_id'],
					$rs_als->fields['mail_acc'],
					$rs_als->fields['status'], 'alssub'
				);
			}

			$tpl->parse('CATCHALL_ITEM', '.catchall_item');

			$rs->moveNext();
		}

		$query = "
			SELECT
				a.`subdomain_id`, CONCAT(a.`subdomain_name`,'.',b.`domain_name`) AS `subdomain_name`
			FROM
				`subdomain` AS a, `domain` AS b
			WHERE
				a.`domain_id` = '$dmn_id'
			AND
				a.`domain_id` = b.`domain_id`
			AND
				a.`subdomain_status` = 'ok'
		";
		$rs = execute_query($query);

		while (!$rs->EOF) {
			$als_id = $rs->fields['subdomain_id'];
			$als_name = $rs->fields['subdomain_name'];

			$query = "
				SELECT
					`mail_id`, `mail_acc`, `status`
				FROM
					`mail_users`
				WHERE
					`domain_id` = '$dmn_id'
				AND
					`sub_id` = '$als_id'
				AND
					`mail_type` = 'subdom_catchall'
			";

			$rs_als = execute_query($query);

			if ($rs_als->recordCount() == 0) {
				gen_catchall_item($tpl, 'create', $als_id, $als_name, '', '', '', 'subdom');
			} else {
				gen_catchall_item($tpl,
					'delete',
					$als_id,
					$als_name,
					$rs_als->fields['mail_id'],
					$rs_als->fields['mail_acc'],
					$rs_als->fields['status'], 'subdom');
			}

			$tpl->parse('CATCHALL_ITEM', '.catchall_item');

			$rs->moveNext();
		}
}

/**
 * @param $tpl
 * @param $user_id
 */
function gen_page_lists($tpl, $user_id)
{
	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$dmn_expires,
		$dmn_last_modified,
		$dmn_mailacc_limit,
		$dmn_ftpacc_limit,
		$dmn_traff_limit,
		$dmn_sqld_limit,
		$dmn_sqlu_limit,
		$dmn_status,
		$dmn_als_limit,
		$dmn_subd_limit,
		$dmn_ip_id,
		$dmn_disk_limit,
		$dmn_disk_usage,
		$dmn_php,
		$dmn_cgi,
		$allowbackup,
		$dmn_dns
	) = get_domain_default_props($user_id);

	gen_page_catchall_list($tpl, $dmn_id, $dmn_name);
}

// common page data.

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client / Manage mail / Catchall'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	$tpl->assign('NO_MAILS', '');
}

gen_page_lists($tpl, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_STATUS' => tr('Status'),
		 'TR_ACTION' => tr('Action'),
		 'TR_TITLE_CATCHALL_MAIL_USERS' => tr('Catch all'),
		 'TR_DOMAIN' => tr('Domain'),
		 'TR_CATCHALL' => tr('Catch all'),
		 'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s catch all?', true, '%s')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
