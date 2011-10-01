<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @version		SVN: $Id$
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/************************************************************************************
 * Script functions
 */

/**
 * Generates domain aliases list.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $userId User unique identifier
 * @return void
 */
function client_generateDomainAliasesList($tpl, $userId)
{
	$domainId = get_user_domain_id($userId);

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
	$stmt = exec_query($query, $domainId);

	if ($stmt->rowCount() == 0) {
		$tpl->assign(array(
						  'ALS_MSG' => tr('You do not have domain aliases.'),
						  'ALS_LIST' => ''));
	} else {
		while (!$stmt->EOF) {
			list(
				$action, $actionScript, $isStatusOk
			) = _client_generateDomainAliasAction($stmt->fields['alias_id'],
												  $stmt->fields['alias_status']);
			list(
				$redirectUrl, $editLink, $edit
			) = _client_generateDomainAliasRedirect($stmt->fields['alias_id'],
													$stmt->fields['alias_status'],
													$stmt->fields['url_forward']);

			$name = decode_idna($stmt->fields['alias_name']);
			$redirectUrl = decode_idna($redirectUrl);

			if($isStatusOk) {
				$tpl->assign(array(
								  'ALS_NAME' => tohtml($name),
								  'ALS_STATUS_RELOAD_FALSE' => ''));

				$tpl->parse('ALS_STATUS_RELOAD_TRUE', 'als_status_reload_true');
			} else {
				$tpl->assign(array(
								  'ALS_NAME', tohtml($name),
								  'ALS_STATUS_RELOAD_TRUE' => ''));

				$tpl->parse('ALS_STATUS_RELOAD_FALSE', 'als_status_reload_false');
			}

			$tpl->assign(array(
							  'ALS_NAME' => tohtml($name),
							  'ALS_MOUNT' => tohtml($stmt->fields['alias_mount']),
							  'ALS_STATUS' => translate_dmn_status($stmt->fields['alias_status']),
							  'ALS_REDIRECT' => tohtml($redirectUrl),
							  'ALS_EDIT_LINK' => $editLink,
							  'ALS_EDIT' => $edit,
							  'ALS_ACTION' => $action,
							  'ALS_ACTION_SCRIPT' => $actionScript));

			$tpl->parse('ALS_ITEM', '.als_item');
			$stmt->moveNext();
		}

		$tpl->assign('ALS_MESSAGE', '');
	}
}

/**
 * Generates domain alias action.
 *
 * @access private
 * @param int $id Alias unique identifier
 * @param string $status Alias status
 * @return array
 */
function _client_generateDomainAliasAction($id, $status)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(
			tr('Delete'),
			'alias_delete.php?id=' . $id,
			true
		);
	} elseif ($status == $cfg->ITEM_ORDERED_STATUS) {
		return array(
			tr('Delete order'),
			'alias_order_delete.php?del_id=' . $id,
			false
		);
	} else {
		return array(
			tr('N/A'),
			'#',
			false
		);
	}
}

/**
 * Generates domain alias redirect.
 *
 * @access private
 * @param int $id Alias unique identifier
 * @param string $status Alias status
 * @param string $redirectUrl Target URL for redirect request
 * @return array
 */
function _client_generateDomainAliasRedirect($id, $status, $redirectUrl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($redirectUrl == 'no') {
		if ($status == $cfg->ITEM_OK_STATUS) {
			return array(
				'-',
				'alias_edit.php?edit_id=' . $id,
				tr('Edit')
			);
		} else if ($status == $cfg->ITEM_ORDERED_STATUS) {
			return array(
				'-',
				'#',
				tr('N/A')
			);
		} else {
			return array(
				tr('N/A'),
				'#',
				tr('N/A')
			);
		}
	} else {
		if ($status == $cfg->ITEM_OK_STATUS) {
			return array(
				$redirectUrl,
				'alias_edit.php?edit_id=' . $id,
				tr('Edit')
			);
		} elseif ($status == $cfg->ITEM_ORDERED_STATUS) {
			return array(
				$redirectUrl,
				'#',
				tr('N/A')
			);
		} else {
			return array(
				tr('N/A'),
				'#',
				tr('N/A')
			);
		}
	}
}

/**
 * Generates subdomains list.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $userId User unique identifier
 * @return void
 */
function client_generateSubdomainsList($tpl, $userId)
{
	$domainId = get_user_domain_id($userId);

	// Subdomains
	$query = "
		SELECT
			`subdomain_id`, `subdomain_name`, `subdomain_mount`, `subdomain_status`,
			`subdomain_url_forward`, `domain_name`
		FROM
			`subdomain`
		JOIN
			`domain` ON (`subdomain`.`domain_id` = `domain`.`domain_id`)
		WHERE
			`subdomain`.`domain_id` = ?
		ORDER BY
			`subdomain_name`
	";
	$stmt1 = exec_query($query, $domainId);

	// Domain aliases subdomains
	$query = "
		SELECT
			`subdomain_alias_id`, `subdomain_alias_name`, `subdomain_alias_mount`,
			`subdomain_alias_url_forward`, `subdomain_alias_status`, `alias_name`
		FROM
			`subdomain_alias`
		JOIN
			`domain_aliasses` ON `subdomain_alias`.`alias_id` = `domain_aliasses`.`alias_id`
		WHERE
			`domain_id` = ?
		ORDER BY
			`subdomain_alias_name`
	";
	$stmt2 = exec_query($query, $domainId);

	if (!($stmt1->rowCount() || $stmt2->rowCount())) {
		$tpl->assign(array(
						  'SUB_MSG' => tr('You do not have subdomains.'),
						  'SUB_LIST' => ''));
	} else {
		while (!$stmt1->EOF) {
			list(
				$action, $actionScript, $isStatusOk
			) = _client_generateSubdomainAction($stmt1->fields['subdomain_id'],
												$stmt1->fields['subdomain_status']);

			list(
				$redirectUrl, $editLink, $edit
			) = _client_generateSubdomainRedirect($stmt1->fields['subdomain_id'],
									  $stmt1->fields['subdomain_status'],
									  $stmt1->fields['subdomain_url_forward'], 'dmn');

			$name = decode_idna($stmt1->fields['subdomain_name']);
			$redirectUrl = decode_idna($redirectUrl);

			if ($isStatusOk) {
				$tpl->assign(array(
								  'SUB_NAME' => tohtml($name),
								  'SUB_ALIAS_NAME' => tohtml($stmt1->fields['domain_name']),
								  'SUB_STATUS_RELOAD_FALSE' => ''));

				$tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
			} else {
				$tpl->assign(array(
								  'SUB_NAME' => tohtml($name),
								  'SUB_ALIAS_NAME' => tohtml($stmt1->fields['domain_name']),
								  'SUB_STATUS_RELOAD_TRUE' => ''));

				$tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
			}

			$tpl->assign(array(
							  'SUB_NAME' => tohtml($name),
							  'SUB_MOUNT' => tohtml($stmt1->fields['subdomain_mount']),
							  'SUB_REDIRECT' => $redirectUrl,
							  'SUB_STATUS' => translate_dmn_status($stmt1->fields['subdomain_status']),
							  'SUB_EDIT_LINK' => $editLink,
							  'SUB_EDIT' => $edit,
							  'SUB_ACTION' => $action,
							  'SUB_ACTION_SCRIPT' => $actionScript));

			$tpl->parse('SUB_ITEM', '.sub_item');
			$stmt1->moveNext();
		}

		while (!$stmt2->EOF) {
			list(
				$action, $actionScript, $isStatusOk
			) = _client_generateSubdomainAliasAction($stmt2->fields['subdomain_alias_id'],
									   $stmt2->fields['subdomain_alias_status']);

			list(
				$redirectUrl, $editLink, $edit
			) = _client_generateSubdomainRedirect($stmt2->fields['subdomain_alias_id'],
									  $stmt2->fields['subdomain_alias_status'],
									  $stmt2->fields['subdomain_alias_url_forward'], 'als');

			$name = decode_idna($stmt2->fields['subdomain_alias_name']);
			$redirectUrl = decode_idna($redirectUrl);

			if ($isStatusOk) {
				$tpl->assign(array(
								  'SUB_NAME' => tohtml($name),
								  'SUB_ALIAS_NAME' => tohtml($stmt2->fields['alias_name']),
								  'SUB_STATUS_RELOAD_FALSE' => ''));

				$tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
			} else {
				$tpl->assign(array(
								  'SUB_NAME' => tohtml($name),
								  'SUB_ALIAS_NAME' => tohtml($stmt2->fields['alias_name']),
								  'SUB_STATUS_RELOAD_TRUE' => ''));

				$tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
			}

			$tpl->assign(array(
							  'SUB_NAME' => tohtml($name),
							  'SUB_MOUNT' => tohtml($stmt2->fields['subdomain_alias_mount']),
							  'SUB_REDIRECT' => $redirectUrl,
							  'SUB_STATUS' => translate_dmn_status($stmt2->fields['subdomain_alias_status']),
							  'SUB_EDIT_LINK' => $editLink,
							  'SUB_EDIT' => $edit,
							  'SUB_ACTION' => $action,
							  'SUB_ACTION_SCRIPT' => $actionScript));

			$tpl->parse('SUB_ITEM', '.sub_item');
			$stmt2->moveNext();
		}

		$tpl->assign('SUB_MESSAGE', '');
	}
}

/**
 * Generates user subdomains redirection.
 *
 * @param int $id Subdomain unique identifier
 * @param string $status Subdomain status
 * @param string $redirectUrl Subdomain redirect URL
 * @param string $entityType Subdomain type (dmn|als)
 * @return array
 */
function _client_generateSubdomainRedirect($id, $status, $redirectUrl, $entityType)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(
			($redirectUrl == 'no') ? '-' : $redirectUrl,
			'subdomain_edit.php?id=' . $id . '&amp;type=' . $entityType,
			tr('Edit')
		);
	} elseif ($status == $cfg->ITEM_ORDERED_STATUS) {
		return array(
			($redirectUrl == 'no') ? '-' : $redirectUrl,
			'#',
			tr('N/A')
		);
	} else {
		return array(
			tr('N/A'),
			'#',
			tr('N/A')
		);
	}
}

/**
 * Generates user subdomain action.
 *
 * @access private
 * @param int $id Subdomain unique identifier
 * @param string $status Subdomain status
 * @return array
 */
function _client_generateSubdomainAction($id, $status)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(
			tr('Delete'),
			'subdomain_delete.php?id=' . $id,
			true
		);
	} else {
		return array(
			tr('N/A'),
			'#',
			false
		);
	}
}

/**
 * Generates subdomain aliases action.
 *
 * @param int $id Subdomain Alias unique identifier
 * @param string $status Subdomain alias Status
 * @return array
 */
function _client_generateSubdomainAliasAction($id, $status)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(
			tr('Delete'),
			'alssub_delete.php?id=' . $id,
			true
		);
	} else {
		return array(
			tr('N/A'),
			'#',
			false
		);
	}
}

/**
 * Generates custom DNS records list.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId User unique identifier
 * @return void
 */
function client_generateCustomDnsRecordsList($tpl, $userId)
{
	$domainId = get_user_domain_id($userId);

	$query = "
		SELECT
			`domain_dns`.`domain_dns_id`, `domain_dns`.`domain_id`,
			`domain_dns`.`domain_dns`, `domain_dns`.`domain_class`,
			`domain_dns`.`domain_type`, `domain_dns`.`domain_text`,
			IFNULL(`domain_aliasses`.`alias_name`, `domain`.`domain_name`) AS 'domain_name',
			IFNULL(`domain_aliasses`.`alias_status`, `domain`.`domain_status`) AS 'domain_status',
			`domain_dns`.`protected`
		FROM
			`domain_dns`
		LEFT JOIN
			`domain_aliasses` USING (`alias_id`, `domain_id`), `domain`
		WHERE
			`domain_dns`.`domain_id` = ?
		AND
			`domain`.`domain_id` = `domain_dns`.`domain_id`
		ORDER BY
			`domain_id`, `alias_id`, `domain_dns`, `domain_type`
	";
	$stmt = exec_query($query, $domainId);

	if ($stmt->rowCount() == 0) {
		$tpl->assign(array(
						  'DNS_MSG' => tr('You do not have custom DNS records.'),
						  'DNS_LIST' => ''));
	} else {
		while (!$stmt->EOF) {
			list(
				$actionDelete, $actionScriptDelete
			) = _client_generateCustomDnsRecordAction('Delete', $stmt->fields['domain_dns_id'],
									($stmt->fields['protected'] == 'no')
										? $stmt->fields['domain_status'] : 'PROTECTED'
			);

			list(
				$actionEdit, $actionScriptEdit
			) = _client_generateCustomDnsRecordAction('Edit', $stmt->fields['domain_dns_id'],
									($stmt->fields['protected'] == 'no')
										? $stmt->fields['domain_status'] : 'PROTECTED'
			);

			$domainName = decode_idna($stmt->fields['domain_name']);
			$sbd_name = $stmt->fields['domain_dns'];
			$sbd_data = $stmt->fields['domain_text'];

			$tpl->assign(array(
							  'DNS_DOMAIN' => tohtml($domainName),
							  'DNS_NAME' => tohtml($sbd_name),
							  'DNS_CLASS' => tohtml($stmt->fields['domain_class']),
							  'DNS_TYPE' => tohtml($stmt->fields['domain_type']),
							  'DNS_DATA' => tohtml($sbd_data),
							  'DNS_ACTION_SCRIPT_DELETE' => tohtml($actionScriptDelete),
							  'DNS_ACTION_DELETE' => tohtml($actionDelete),
							  'DNS_ACTION_SCRIPT_EDIT' => tohtml($actionScriptEdit),
							  'DNS_ACTION_EDIT' => tohtml($actionEdit),
							  'DNS_TYPE_RECORD' => tr("%s record", $stmt->fields['domain_type'])));

			$tpl->parse('DNS_ITEM', '.dns_item');
			$stmt->moveNext();
		}

		$tpl->parse('DNS_LIST', 'dns_list');
		$tpl->assign('DNS_MESSAGE', '');
	}
}

/**
 * Generates custom DNS record action.
 *
 * @access private
 * @param string $action Action
 * @param int $id Custom DNS record unique identifier
 * @param string $status Custom DNS record status
 * @return array
 */
function _client_generateCustomDnsRecordAction($action, $id, $status)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(
			tr($action),
			'dns_' . strtolower($action) . '.php?edit_id=' . $id
		);
	} elseif ($action != 'Edit' && $status == 'PROTECTED') {
		return array(
			tr('N/A'),
			'protected'
		);
	}

	return array(
		tr('N/A'),
		'#'
	);
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
						  'page' => $cfg->CLIENT_TEMPLATE_PATH . '/domains_manage.tpl',
						  'page_message' => 'page',
						  'logged_from' => 'page',

						  'als_message' => 'page',
						  'als_list' => 'page',
						  'als_item' => 'als_list',
						  'als_status_reload_true' => 'als_item',
						  'als_status_reload_false' => 'als_item',

						  'sub_message' => 'page',
						  'sub_list' => 'page',
						  'sub_item' => 'sub_list',
						  'sub_status_reload_true' => 'sub_item',
						  'sub_status_reload_false' => 'sub_item',

						  'isactive_dns' => 'page',
						  'dns_message' => 'page',
						  'dns_list' => 'page',
						  'dns_item' => 'dns_list'));

$tpl->assign(array(
				  'TR_PAGE_TITLE' => tr('i-MSCP - Client/Manage Domains'),
				  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
				  'THEME_CHARSET' => tr('encoding'),
				  'ISP_LOGO' => layout_getUserLogo(),
				  'TR_MANAGE_DOMAINS' => tr('Manage domains'),
				  'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
				  'TR_SUBDOMAINS' => tr('Subdomains'),

				  'TR_NAME' => tr('Name'),
				  'TR_MOUNT' => tr('Mount point'),
				  'TR_REDIRECT' => tr('Redirect'),
				  'TR_STATUS' => tr('Status'),
				  'TR_ACTIONS' => tr('Actions'),
				  'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),

				  'TR_DNS' => tr('Custom DNS records'),
				  'TR_DNS_NAME' => tr('Name'),
				  'TR_DNS_CLASS' => tr('Class'),
				  'TR_DNS_TYPE' => tr('Type'),
				  'TR_DNS_ACTION' => tr('Actions'),
				  'TR_DNS_DATA' => tr('Record data'),
				  'TR_DOMAIN_NAME' => tr('Domain')));


gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_manage_domains.tpl');
gen_logged_from($tpl);

client_generateSubdomainsList($tpl, $_SESSION['user_id']);
client_generateDomainAliasesList($tpl, $_SESSION['user_id']);
client_generateCustomDnsRecordsList($tpl, $_SESSION['user_id']);

check_permissions($tpl);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd,
											  new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
