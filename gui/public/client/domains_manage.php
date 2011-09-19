<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/domains_manage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('als_message', 'page');
$tpl->define_dynamic('als_list', 'page');
$tpl->define_dynamic('als_item', 'als_list');
$tpl->define_dynamic('als_status_reload_true','als_item');
$tpl->define_dynamic('als_status_reload_false','als_item');
$tpl->define_dynamic('alias_add', 'page');
$tpl->define_dynamic('sub_message', 'page');
$tpl->define_dynamic('sub_list', 'page');
$tpl->define_dynamic('sub_item', 'sub_list');
$tpl->define_dynamic('status_reload_true','sub_item');
$tpl->define_dynamic('status_reload_false','sub_item');
$tpl->define_dynamic('subdomain_add', 'page');
$tpl->define_dynamic('isactive_dns', 'page');
$tpl->define_dynamic('dns_message', 'page');
$tpl->define_dynamic('dns_list', 'page');
$tpl->define_dynamic('dns_item', 'dns_list');


// page functions.

function gen_user_dns_list($tpl, $user_id) {
	$domain_id = get_user_domain_id($user_id);

	$query = "
		SELECT
			`domain_dns`.`domain_dns_id`,
			`domain_dns`.`domain_id`,
			`domain_dns`.`domain_dns`,
			`domain_dns`.`domain_class`,
			`domain_dns`.`domain_type`,
			`domain_dns`.`domain_text`,
			IFNULL(`domain_aliasses`.`alias_name`, `domain`.`domain_name`) AS 'domain_name',
			IFNULL(`domain_aliasses`.`alias_status`, `domain`.`domain_status`) AS 'domain_status',
			`domain_dns`.`protected`
		FROM
			`domain_dns`
			LEFT JOIN `domain_aliasses` USING (`alias_id`, `domain_id`),
			`domain`
		WHERE
			`domain_dns`.`domain_id` = ?
		AND
			`domain`.`domain_id` = `domain_dns`.`domain_id`
		ORDER BY
			`domain_id`,
			`alias_id`,
			`domain_dns`,
			`domain_type`
	";

	$rs = exec_query($query, $domain_id);
	if ($rs->recordCount() == 0) {
		$tpl->assign(array('DNS_MSG' => tr("Manual zone's records list is empty!"), 'DNS_LIST' => ''));
		$tpl->parse('DNS_MESSAGE', 'dns_message');
	} else {
		$counter = 0;

		while (!$rs->EOF) {
			if ($counter % 2 == 0) {
				$tpl->assign('ITEM_CLASS', 'content');
			} else {
				$tpl->assign('ITEM_CLASS', 'content2');
			}

			list($dns_action_delete, $dns_action_script_delete) = gen_user_dns_action(
				'Delete', $rs->fields['domain_dns_id'],
				($rs->fields['protected'] == 'no') ? $rs->fields['domain_status'] : 'PROTECTED'
			);

			list($dns_action_edit, $dns_action_script_edit) = gen_user_dns_action(
				'Edit', $rs->fields['domain_dns_id'],
				($rs->fields['protected'] == 'no') ? $rs->fields['domain_status'] : 'PROTECTED'
			);

			$domain_name = decode_idna($rs->fields['domain_name']);
			$sbd_name = $rs->fields['domain_dns'];
			$sbd_data = $rs->fields['domain_text'];
			$tpl->assign(
				array(
					'DNS_DOMAIN'				=> tohtml($domain_name),
					'DNS_NAME'					=> tohtml($sbd_name),
					'DNS_CLASS'					=> tohtml($rs->fields['domain_class']),
					'DNS_TYPE'					=> tohtml($rs->fields['domain_type']),
					'DNS_DATA'					=> tohtml($sbd_data),
//					'DNS_ACTION_SCRIPT_EDIT'	=> $sub_action,
					'DNS_ACTION_SCRIPT_DELETE'	=> tohtml($dns_action_script_delete),
					'DNS_ACTION_DELETE'			=> tohtml($dns_action_delete),
					'DNS_ACTION_SCRIPT_EDIT'	=> tohtml($dns_action_script_edit),
					'DNS_ACTION_EDIT'			=> tohtml($dns_action_edit),
					'DNS_TYPE_RECORD'			=> tr("%s record", $rs->fields['domain_type'])
				)
			);
			$tpl->parse('DNS_ITEM', '.dns_item');
			$rs->moveNext();
			$counter++;
		}

		$tpl->parse('DNS_LIST', 'dns_list');
		$tpl->assign('DNS_MESSAGE', '');
	}
}

function gen_user_dns_action($action, $dns_id, $status) {

	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(tr($action), 'dns_'.strtolower($action).'.php?edit_id='.$dns_id);
	} elseif($action != 'Edit' && $status == 'PROTECTED') {
		return array(tr('N/A'), 'protected');
	}

	return array(tr('N/A'), '#');
}

function gen_user_sub_action($sub_id, $sub_status) {

	$cfg = iMSCP_Registry::get('config');

	if ($sub_status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete'), "subdomain_delete.php?id=$sub_id",true);
	} else {
		return array(tr('N/A'), '#',false);
	}
}

function gen_user_alssub_action($sub_id, $sub_status) {

	$cfg = iMSCP_Registry::get('config');

	if ($sub_status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete'), "alssub_delete.php?id=$sub_id",true);
	} else {
		return array(tr('N/A'), '#',false);
	}
}

function gen_user_sub_forward($sub_id, $sub_status, $url_forward, $dmn_type) {

	$cfg = iMSCP_Registry::get('config');

	if ($sub_status === $cfg->ITEM_OK_STATUS) {
		return array(
			$url_forward === 'no' || $url_forward === NULL
			?
				'-'
			:
				$url_forward,
			'subdomain_edit.php?id='.$sub_id.'&amp;type='.$dmn_type, tr('Edit')
		);
	} else if ($sub_status === $cfg->ITEM_ORDERED_STATUS) {
		return array(
			$url_forward === 'no' || $url_forward === NULL
			?
				'-'
			:
				$url_forward, '#', tr('N/A')
			);
	} else {
		return array(tr('N/A'), '#', tr('N/A'));
	}
}

function gen_user_sub_list($tpl, $user_id) {

	$domain_id = get_user_domain_id($user_id);

	$query = "
		SELECT
			`subdomain_id`,
			`subdomain_name`,
			`subdomain_mount`,
			`subdomain_status`,
			`subdomain_url_forward`,
			`domain_name`
		FROM
			`subdomain` JOIN `domain`
		ON
			`subdomain`.`domain_id` = `domain`.`domain_id`
		WHERE
			`subdomain`.`domain_id` = ?
		ORDER BY
			`subdomain_name`
	";

	$query2 = "
		SELECT
			`subdomain_alias_id`,
			`subdomain_alias_name`,
			`subdomain_alias_mount`,
			`subdomain_alias_url_forward`,
			`subdomain_alias_status`,
			`alias_name`
		FROM
			`subdomain_alias` JOIN `domain_aliasses`
		ON
			`subdomain_alias`.`alias_id` = `domain_aliasses`.`alias_id`
		WHERE
			`domain_id` = ?
		ORDER BY
			`subdomain_alias_name`
	";

	$rs = exec_query($query, $domain_id);
	$rs2 = exec_query($query2, $domain_id);

	if (($rs->recordCount() + $rs2->recordCount()) == 0) {
		$tpl->assign(array('SUB_MSG' => tr('Subdomain list is empty!'), 'SUB_LIST' => ''));
		$tpl->parse('SUB_MESSAGE', 'sub_message');
	} else {
		$counter = 0;
		while (!$rs->EOF) {
			list($sub_action, $sub_action_script, $status_bool) = gen_user_sub_action($rs->fields['subdomain_id'], $rs->fields['subdomain_status']);
			list($sub_forward, $sub_edit_link, $sub_edit) = gen_user_sub_forward($rs->fields['subdomain_id'], $rs->fields['subdomain_status'], $rs->fields['subdomain_url_forward'], 'dmn');
			$sbd_name = decode_idna($rs->fields['subdomain_name']);
			$sub_forward = decode_idna($sub_forward);
			if($status_bool == false) { // reload
				$tpl->assign('STATUS_RELOAD_TRUE', '');
				$tpl->assign('SUB_NAME', tohtml($sbd_name));
				$tpl->assign('SUB_ALIAS_NAME', tohtml($rs->fields['domain_name']));
				$tpl->parse('STATUS_RELOAD_FALSE', 'status_reload_false');
			} else {
				$tpl->assign('STATUS_RELOAD_FALSE', '');
				$tpl->assign('SUB_NAME', tohtml($sbd_name));
				$tpl->assign('SUB_ALIAS_NAME', tohtml($rs->fields['domain_name']));
				$tpl->parse('STATUS_RELOAD_TRUE', 'status_reload_true');
			}
			$tpl->assign(
				array(
					'SUB_NAME'			=> tohtml($sbd_name),
					'SUB_MOUNT'			=> tohtml($rs->fields['subdomain_mount']),
					'SUB_FORWARD'		=> $sub_forward,
					'SUB_STATUS'		=> translate_dmn_status($rs->fields['subdomain_status']),
					'SUB_EDIT_LINK'		=> $sub_edit_link,
					'SUB_EDIT'			=> $sub_edit,
					'SUB_ACTION'		=> $sub_action,
					'SUB_ACTION_SCRIPT'	=> $sub_action_script,
					'ITEM_CLASS'		=> ($counter % 2 == 0) ? 'content' : 'content2'
				)
			);
			$tpl->parse('SUB_ITEM', '.sub_item');
			$rs->moveNext();
			$counter++;
		}
		while (!$rs2->EOF) {
			list($sub_action, $sub_action_script, $status_bool) = gen_user_alssub_action($rs2->fields['subdomain_alias_id'], $rs2->fields['subdomain_alias_status']);
			list($sub_forward, $sub_edit_link, $sub_edit) = gen_user_sub_forward($rs2->fields['subdomain_alias_id'], $rs2->fields['subdomain_alias_status'], $rs2->fields['subdomain_alias_url_forward'], 'als');
			$sbd_name = decode_idna($rs2->fields['subdomain_alias_name']);
			$sub_forward = decode_idna($sub_forward);
			if($status_bool == false) { // reload
				$tpl->assign('STATUS_RELOAD_TRUE', '');
				$tpl->assign('SUB_NAME', tohtml($sbd_name));
				$tpl->assign('SUB_ALIAS_NAME', tohtml($rs2->fields['alias_name']));
				$tpl->parse('STATUS_RELOAD_FALSE', 'status_reload_false');
			} else {
				$tpl->assign('STATUS_RELOAD_FALSE', '');
				$tpl->assign('SUB_NAME', tohtml($sbd_name));
				$tpl->assign('SUB_ALIAS_NAME', tohtml($rs2->fields['alias_name']));
				$tpl->parse('STATUS_RELOAD_TRUE', 'status_reload_true');
			}
			$tpl->assign(
				array(
					'SUB_NAME'			=> tohtml($sbd_name),
					'SUB_MOUNT'			=> tohtml($rs2->fields['subdomain_alias_mount']),
					'SUB_FORWARD'		=> $sub_forward,
					'SUB_STATUS'		=> translate_dmn_status($rs2->fields['subdomain_alias_status']),
					'SUB_EDIT_LINK'		=> $sub_edit_link,
					'SUB_EDIT'			=> $sub_edit,
					'SUB_ACTION'		=> $sub_action,
					'SUB_ACTION_SCRIPT'	=> $sub_action_script,
					'ITEM_CLASS'		=> ($counter % 2 == 0) ? 'content' : 'content2'
				)
			);
			$tpl->parse('SUB_ITEM', '.sub_item');
			$rs2->moveNext();
			$counter++;
		}

		$tpl->parse('SUB_LIST', 'sub_list');
		$tpl->assign('SUB_MESSAGE', '');
	}
}

function gen_user_als_action($als_id, $als_status) {

	$cfg = iMSCP_Registry::get('config');

	if ($als_status === $cfg->ITEM_OK_STATUS) {
		return array(tr('Delete'), 'alias_delete.php?id=' . $als_id, true);
	} else if ($als_status === $cfg->ITEM_ORDERED_STATUS) {
		return array(tr('Delete order'), 'alias_order_delete.php?del_id=' . $als_id, false);
	} else {
		return array(tr('N/A'), '#',false);
	}
}

function gen_user_als_forward($als_id, $als_status, $url_forward) {

	if ($url_forward === 'no') {
		if ($als_status === 'ok') {
			return array("-", "alias_edit.php?edit_id=" . $als_id, tr("Edit"));
		} else if ($als_status === 'ordered') {
			return array("-", "#", tr("N/A"));
		} else {
			return array(tr("N/A"), "#", tr("N/A"));
		}
	} else {
		if ($als_status === 'ok') {
			return array($url_forward, "alias_edit.php?edit_id=" . $als_id, tr("Edit"));
		} else if ($als_status === 'ordered') {
			return array($url_forward, "#", tr("N/A"));
		} else {
			return array(tr("N/A"), "#", tr("N/A"));
		}
	}
}

function gen_user_als_list($tpl, $user_id) {

	$domain_id = get_user_domain_id($user_id);

	$query = "
		SELECT
			`alias_id`, `alias_name`, `alias_status`, `alias_mount`, `alias_ip_id`,
			`url_forward`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = ?
		ORDER BY
			`alias_mount`, `alias_name`
	";

	$rs = exec_query($query, $domain_id);

	if ($rs->recordCount() == 0) {
		$tpl->assign(array('ALS_MSG' => tr('Alias list is empty!'), 'ALS_LIST' => ''));
		$tpl->parse('ALS_MESSAGE', 'als_message');
	} else {
		$counter = 0;
		while (!$rs->EOF) {
			$tpl->assign('ITEM_CLASS', ($counter % 2 == 0) ? 'content' : 'content2');

			list($als_action, $als_action_script, $status_bool) = gen_user_als_action($rs->fields['alias_id'], $rs->fields['alias_status']);
			list($als_forward, $alias_edit_link, $als_edit) = gen_user_als_forward($rs->fields['alias_id'], $rs->fields['alias_status'], $rs->fields['url_forward']);

			$alias_name = decode_idna($rs->fields['alias_name']);
			$als_forward = decode_idna($als_forward);

			if($status_bool == false) { // reload
				$tpl->assign('ALS_STATUS_RELOAD_TRUE', '');
				$tpl->assign('ALS_NAME', tohtml($alias_name));
				$tpl->parse('ALS_STATUS_RELOAD_FALSE', 'als_status_reload_false');
			} else {
				$tpl->assign('ALS_STATUS_RELOAD_FALSE', '');
				$tpl->assign('ALS_NAME', tohtml($alias_name));
				$tpl->parse('ALS_STATUS_RELOAD_TRUE', 'als_status_reload_true');
			}

			$tpl->assign(
				array(
					'ALS_NAME'			=> tohtml($alias_name),
					'ALS_MOUNT'			=> tohtml($rs->fields['alias_mount']),
					'ALS_STATUS'		=> translate_dmn_status($rs->fields['alias_status']),
					'ALS_FORWARD'		=> tohtml($als_forward),
					'ALS_EDIT_LINK'		=> $alias_edit_link,
					'ALS_EDIT'			=> $als_edit,
					'ALS_ACTION'		=> $als_action,
					'ALS_ACTION_SCRIPT'	=> $als_action_script
				)
			);
			$tpl->parse('ALS_ITEM', '.als_item');
			$rs->moveNext();
			$counter++;
		}

		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('ALS_MESSAGE', '');
	}
}

// common page data.

$tpl->assign(
	array(
		'TR_CLIENT_MANAGE_DOMAINS_PAGE_TITLE'	=> tr('i-MSCP - Client/Manage Domains'),
		'THEME_COLOR_PATH'						=> "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'							=> tr('encoding'),
		'ISP_LOGO'								=> layout_getUserLogo()
	)
);

// dynamic page data.

gen_user_sub_list($tpl, $_SESSION['user_id']);
gen_user_als_list($tpl, $_SESSION['user_id']);
gen_user_dns_list($tpl, $_SESSION['user_id']);
gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_manage_domains.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_DOMAINS'	=> tr('Manage domains'),
		'TR_DOMAIN_ALIASES'	=> tr('Domain aliases'),
		'TR_ALS_NAME'		=> tr('Name'),
		'TR_ALS_MOUNT'		=> tr('Mount point'),
		'TR_ALS_FORWARD'	=> tr('Forward'),
		'TR_ALS_STATUS'		=> tr('Status'),
		'TR_ALS_ACTION'		=> tr('Action'),
		'TR_SUBDOMAINS'		=> tr('Subdomains'),
		'TR_SUB_NAME'		=> tr('Name'),
		'TR_SUB_MOUNT'		=> tr('Mount point'),
		'TR_SUB_FORWARD'	=> tr('Forward'),
		'TR_SUB_STATUS'		=> tr('Status'),
		'TR_SUB_ACTION'		=> tr('Actions'),
		'TR_MESSAGE_DELETE'	=> tr('Are you sure you want to delete %s?', true, '%s'),
		'TR_DNS'			=> tr("DNS zone's records (EXPERIMENTAL)"),
		'TR_DNS_NAME'		=> tr('Name'),
		'TR_DNS_CLASS'		=> tr('Class'),
		'TR_DNS_TYPE'		=> tr('Type'),
		'TR_DNS_ACTION'		=> tr('Actions'),
		'TR_DNS_DATA'		=> tr('Record data'),
		'TR_DOMAIN_NAME'	=> tr('Domain'),
		'TR_MENUPHPINI' 	=> tr('php.ini'),
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
	iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
