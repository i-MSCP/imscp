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

/************************************************************************************
 * Script functions
 */

/**
 * Generates domains list.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId Customer unique identifier
 * @return void
 */
function client_generateDomainsList($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`domain_id`, `domain_name`, `domain_created`, `domain_expires`, `domain_status`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
		ORDER BY
			`domain_name`
	";
	$stmt = exec_query($query, (int)$userId);

	while (!$stmt->EOF) {
		$domainName = decode_idna($stmt->fields['domain_name']);

		if ($stmt->fields['domain_status'] == $cfg->ITEM_OK_STATUS) {
			$tpl->assign(
				array(
					 'DOMAIN_NAME' => tohtml($domainName),
					 'DOMAIN_STATUS_RELOAD_FALSE' => ''));

			$tpl->parse('DOMAIN_STATUS_RELOAD_TRUE', 'domain_status_reload_true');
		} else {
			$tpl->assign(
				array(
					 'DOMAIN_NAME' => tohtml($domainName),
					 'DOMAIN_STATUS_RELOAD_TRUE' => ''));

			$tpl->parse('DOMAIN_STATUS_RELOAD_FALSE', 'domain_status_reload_false');
		}

		$tpl->assign(
			array(
				'DOMAIN_NAME' => tohtml($domainName),
				'DOMAIN_CREATE_DATE' => tohtml(date($cfg->DATE_FORMAT, $stmt->fields['domain_created'])),
				'DOMAIN_EXPIRE_DATE' => ($stmt->fields['domain_expires'] != 0)
					? tohtml(date($cfg->DATE_FORMAT, $stmt->fields['domain_expires']))
					: tr('No set'),
				'DOMAIN_STATUS' => translate_dmn_status($stmt->fields['domain_status']),
				'CERT_SCRIPT' => 'cert_view.php?id=' . $stmt->fields['domain_id'] . '&type=dmn',
				'VIEW_CERT' => tr('View certificates')
			)
		);

		$tpl->parse('DOMAIN_ITEM', '.domain_item');
		$stmt->moveNext();
	}
}

/**
 * Generates domain aliases list.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId User unique identifier
 * @return void
 */
function client_generateDomainAliasesList($tpl, $userId)
{
	if (customerHasFeature('domain_aliases')) {
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
			$tpl->assign(
				array(
					 'ALS_MSG' => tr('You do not have domain aliases.'),
					 'ALS_LIST' => ''));
		} else {
			while (!$stmt->EOF) {
				list(
					$action, $actionScript, $isStatusOk, $certText, $certScript
					) = _client_generateDomainAliasAction($stmt->fields['alias_id'],
														  $stmt->fields['alias_status']);

				list(
					$redirectUrl, $editLink, $edit
					) = _client_generateDomainAliasRedirect($stmt->fields['alias_id'],
															$stmt->fields['alias_status'],
															$stmt->fields['url_forward']);

				$name = decode_idna($stmt->fields['alias_name']);
				$redirectUrl = decode_idna($redirectUrl);

				if ($isStatusOk) {
					$tpl->assign(
						array(
							 'ALS_NAME' => tohtml($name),
							 'ALS_STATUS_RELOAD_FALSE' => ''));

					$tpl->parse('ALS_STATUS_RELOAD_TRUE', 'als_status_reload_true');
				} else {
					$tpl->assign(
						array(
							 'ALS_NAME' => tohtml($name),
							 'ALS_STATUS_RELOAD_TRUE' => ''));

					$tpl->parse('ALS_STATUS_RELOAD_FALSE', 'als_status_reload_false');
				}

				$tpl->assign(
					array(
						'ALS_NAME' => tohtml($name),
						'ALS_MOUNT' => tohtml($stmt->fields['alias_mount']),
						'ALS_STATUS' => translate_dmn_status($stmt->fields['alias_status']),
						'ALS_REDIRECT' => tohtml($redirectUrl),
						'ALS_EDIT_LINK' => $editLink,
						'ALS_EDIT' => $edit,
						'ALS_ACTION' => $action,
						'CERT_SCRIPT' => $certScript,
						'VIEW_CERT' => $certText,
						'ALS_ACTION_SCRIPT' => $actionScript));

				$tpl->parse('ALS_ITEM', '.als_item');
				$stmt->moveNext();
			}

			$tpl->assign('ALS_MESSAGE', '');
		}
	} else {
		$tpl->assign('DOMAIN_ALIASES_BLOCK', '');
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
			true,
			tr('View certificates'),
			'cert_view.php?id=' . $id.'&type=als',
		);
	} elseif ($status == $cfg->ITEM_ORDERED_STATUS) {
		return array(
			tr('Delete order'),
			'alias_order_delete.php?del_id=' . $id,
			false,
			'-',
			'#'
		);
	} else {
		return array(
			tr('N/A'),
			'#',
			false,
			tr('N/A'),
			'#'
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
			return array('-', 'alias_edit.php?edit_id=' . $id, tr('Edit'));
		} elseif ($status == $cfg->ITEM_ORDERED_STATUS) {
			return array('-', '#', tr('N/A'));
		} else {
			return array(tr('N/A'), '#', tr('N/A'));
		}
	} else {
		if ($status == $cfg->ITEM_OK_STATUS) {
			return array($redirectUrl, 'alias_edit.php?edit_id=' . $id, tr('Edit'));
		} elseif ($status == $cfg->ITEM_ORDERED_STATUS) {
			return array($redirectUrl, '#',tr('N/A'));
		} else {
			return array(tr('N/A'), '#', tr('N/A'));
		}
	}
}

/**
 * Generates subdomains list.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId User unique identifier
 * @return void
 */
function client_generateSubdomainsList($tpl, $userId)
{
	if (customerHasFeature('subdomains')) {
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
					$action, $actionScript, $isStatusOk, $certText, $certScript
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
					$tpl->assign(
						array(
							 'SUB_NAME' => tohtml($name),
							 'SUB_ALIAS_NAME' => tohtml($stmt1->fields['domain_name']),
							 'SUB_STATUS_RELOAD_FALSE' => ''));

					$tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
				} else {
					$tpl->assign(
						array(
							 'SUB_NAME' => tohtml($name),
							 'SUB_ALIAS_NAME' => tohtml($stmt1->fields['domain_name']),
							 'SUB_STATUS_RELOAD_TRUE' => ''));

					$tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
				}

				$tpl->assign(
					array(
						'SUB_NAME' => tohtml($name),
						'SUB_MOUNT' => tohtml($stmt1->fields['subdomain_mount']),
						'SUB_REDIRECT' => $redirectUrl,
						'SUB_STATUS' => translate_dmn_status($stmt1->fields['subdomain_status']),
						'SUB_EDIT_LINK' => $editLink,
						'SUB_EDIT' => $edit,
						'CERT_SCRIPT' => $certScript,
						'VIEW_CERT' => $certText,
						'SUB_ACTION' => $action,
						'SUB_ACTION_SCRIPT' => $actionScript));

				$tpl->parse('SUB_ITEM', '.sub_item');
				$stmt1->moveNext();
			}

			while (!$stmt2->EOF) {
				list(
					$action, $actionScript, $isStatusOk, $certText, $certScript
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
					$tpl->assign(
						array(
							 'SUB_NAME' => tohtml($name),
							 'SUB_ALIAS_NAME' => tohtml($stmt2->fields['alias_name']),
							 'SUB_STATUS_RELOAD_FALSE' => ''));

					$tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
				} else {
					$tpl->assign(
						array(
							 'SUB_NAME' => tohtml($name),
							 'SUB_ALIAS_NAME' => tohtml($stmt2->fields['alias_name']),
							 'SUB_STATUS_RELOAD_TRUE' => ''));

					$tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
				}

				$tpl->assign(
					array(
						'SUB_NAME' => tohtml($name),
						'SUB_MOUNT' => tohtml($stmt2->fields['subdomain_alias_mount']),
						'SUB_REDIRECT' => $redirectUrl,
						'SUB_STATUS' => translate_dmn_status($stmt2->fields['subdomain_alias_status']),
						'SUB_EDIT_LINK' => $editLink,
						'SUB_EDIT' => $edit,
						'CERT_SCRIPT' => $certScript,
						'VIEW_CERT' => $certText,
						'SUB_ACTION' => $action,
						'SUB_ACTION_SCRIPT' => $actionScript));

				$tpl->parse('SUB_ITEM', '.sub_item');
				$stmt2->moveNext();
			}

			$tpl->assign('SUB_MESSAGE', '');
		}
	} else {
		$tpl->assign('SUBDOMAINS_BLOCK', '');
	}
}

/**
 * Generates subdomain redirect.
 *
 * @access private
 * @param int $id Subdomain unique identifier
 * @param string $status Subdomain status
 * @param string $redirectUrl Target URL for redirect request
 * @param string $entityType Subdomain type (dmn|als)
 * @return array
 */
function _client_generateSubdomainRedirect($id, $status, $redirectUrl, $entityType)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($status == $cfg->ITEM_OK_STATUS) {
		return array(($redirectUrl == 'no') ? '-' : $redirectUrl, 'subdomain_edit.php?id=' . $id . '&amp;type=' . $entityType, tr('Edit'));
	} elseif ($status == $cfg->ITEM_ORDERED_STATUS) {
		return array(($redirectUrl == 'no') ? '-' : $redirectUrl, '#', tr('N/A'));
	} else {
		return array(tr('N/A'), '#', tr('N/A'));
	}
}

/**
 * Generates subdomain action.
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
			true,
			tr('View certificates'),
			'cert_view.php?id=' . $id.'&type=sub',
			);
	} else {
		return array(
			tr('N/A'),
			'#',
			false,
			tr('N/A'),
			'#'
		);
	}
}

/**
 * Generates subdomain aliases action.
 *
 * @access private
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
			true,
			tr('View certificates'),
			'cert_view.php?id=' . $id.'&type=alssub',
		);
	} else {
		return array(
			tr('N/A'),
			'#',
			false,
			tr('N/A'),
			'#'
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
	if (customerHasFeature('custom_dns_records')) {
		$domainId = get_user_domain_id($userId);

		$query = "
			SELECT
				`domain_dns`.`domain_dns_id`, `domain_dns`.`domain_id`,
				`domain_dns`.`domain_dns`, `domain_dns`.`domain_class`,
				`domain_dns`.`domain_type`, `domain_dns`.`domain_text`,
				IFNULL(`domain_aliasses`.`alias_name`, `domain`.`domain_name`) `domain_name`,
				IFNULL(`domain_aliasses`.`alias_status`, `domain`.`domain_status`) `domain_status`,
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
			$tpl->assign(
				array(
					 'DNS_MSG' => tr('You do not have custom DNS records.'),
					 'DNS_LIST' => ''));
		} else {
			while (!$stmt->EOF) {
				list(
					$actionDelete, $actionScriptDelete
				) = _client_generateCustomDnsRecordAction(
					'Delete',
					$stmt->fields['domain_dns_id'],
					($stmt->fields['protected'] == 'no')? $stmt->fields['domain_status'] : 'PROTECTED');

				list(
					$actionEdit, $actionScriptEdit
					) = _client_generateCustomDnsRecordAction(
					'Edit',
					$stmt->fields['domain_dns_id'],
					($stmt->fields['protected'] == 'no') ? $stmt->fields['domain_status'] : 'PROTECTED');

				$domainName = decode_idna($stmt->fields['domain_name']);
				$sbd_name = $stmt->fields['domain_dns'];
				$sbd_data = $stmt->fields['domain_text'];

				$tpl->assign(
					array(
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
	} else {
		$tpl->assign('CUSTOM_DNS_RECORDS_BLOCK', '');
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
		return array(($action == 'Edit') ? tr('Edit') : tr('Delete'),'dns_' . strtolower($action) . '.php?edit_id=' . $id);
	} elseif ($action != 'Edit' && $status == 'PROTECTED') {
		return array(tr('N/A'), 'protected');
	}

	return array(tr('N/A') ,  '#');
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('domain')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic(
	array(
		 'page' => 'client/domains_manage.tpl',
		 'page_message' => 'layout',
		 'domain_list' => 'page',
		 'domain_item' => 'domain_list',
		 'domain_status_reload_true' => 'domain_item',
		 'domain_status_reload_false' => 'domain_item',
		 'domain_aliases_block' => 'page',
		 'als_message' => 'domain_aliases_block',
		 'als_list' => 'domain_aliases_block',
		 'als_item' => 'als_list',
		 'als_status_reload_true' => 'als_item',
		 'als_status_reload_false' => 'als_item',

		 'subdomains_block' => 'page',
		 'sub_message' => 'subdomains_block',
		 'sub_list' => 'subdomains_block',
		 'sub_item' => 'sub_list',
		 'sub_status_reload_true' => 'sub_item',
		 'sub_status_reload_false' => 'sub_item',

		 'custom_dns_records_block' => 'page',
		 'dns_message' => 'custom_dns_records_block',
		 'dns_list' => 'custom_dns_records_block',
		 'dns_item' => 'dns_list'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client/Manage Domains'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),

		 'TR_MANAGE_DOMAINS' => tr('Manage domains'),

		 'TR_DOMAINS' => 'Domains',
		 'TR_CREATE_DATE' => 'Creation date',
		 'TR_EXPIRE_DATE' => 'Expire date',

		 'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
		 'TR_SUBDOMAINS' => tr('Subdomains'),

		 'TR_NAME' => tr('Name'),
		 'TR_MOUNT' => tr('Mount point'),
		 'TR_REDIRECT' => tr('Redirect'),
		 'TR_STATUS' => tr('Status'),
		 'TR_CERT' => tr('SSL Certificates'),
		 'TR_ACTIONS' => tr('Actions'),
		 'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),

		 'TR_DNS' => tr('Custom DNS records'),
		 'TR_DNS_NAME' => tr('Name'),
		 'TR_DNS_CLASS' => tr('Class'),
		 'TR_DNS_TYPE' => tr('Type'),
		 'TR_DNS_ACTION' => tr('Actions'),
		 'TR_DNS_DATA' => tr('Record data'),
		 'TR_DOMAIN_NAME' => tr('Domain')));

generateNavigation($tpl);

client_generateDomainsList($tpl, $_SESSION['user_id']);
client_generateSubdomainsList($tpl, $_SESSION['user_id']);
client_generateDomainAliasesList($tpl, $_SESSION['user_id']);
client_generateCustomDnsRecordsList($tpl, $_SESSION['user_id']);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
