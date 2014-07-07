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
 * @subpackage  Client
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
			domain_id, domain_name, domain_created, domain_expires, domain_status
		FROM
			domain
		WHERE
			domain_admin_id = ?
		ORDER BY
			domain_name
	";
	$stmt = exec_query($query, (int)$userId);

	while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		$domainName = decode_idna($row['domain_name']);

		if ($row['domain_status'] == 'ok') {
			$tpl->assign(
				array(
					'DOMAIN_NAME' => tohtml($domainName),
					'DOMAIN_STATUS_RELOAD_FALSE' => ''
				)
			);

			$tpl->parse('DOMAIN_STATUS_RELOAD_TRUE', 'domain_status_reload_true');
		} else {
			$tpl->assign(
				array(
					'DOMAIN_NAME' => tohtml($domainName),
					'DOMAIN_STATUS_RELOAD_TRUE' => ''
				)
			);

			$tpl->parse('DOMAIN_STATUS_RELOAD_FALSE', 'domain_status_reload_false');
		}

		$tpl->assign(
			array(
				'DOMAIN_NAME' => tohtml($domainName),
				'DOMAIN_CREATE_DATE' => tohtml(date($cfg['DATE_FORMAT'], $row['domain_created'])),
				'DOMAIN_EXPIRE_DATE' => ($row['domain_expires'] != 0)
					? tohtml(date($cfg['DATE_FORMAT'], $row['domain_expires'])) : tr('Never'),
				'DOMAIN_STATUS' => translate_dmn_status($row['domain_status']),
				'CERT_SCRIPT' => tohtml('cert_view.php?domain_id=' . $row['domain_id'] . '&domain_type=dmn'),
				'VIEW_CERT' => tr('Add / Edit SSL certificate')
			)
		);


		$tpl->parse('DOMAIN_ITEM', '.domain_item');
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

		$stmt = exec_query(
			'
				SELECT
					alias_id, alias_name, alias_status, alias_mount, alias_ip_id, url_forward
				FROM
					domain_aliasses
				WHERE
					domain_id = ?
				ORDER BY
					alias_mount, alias_name
			',
			$domainId
		);

		if (!$stmt->rowCount()) {
			$tpl->assign(
				array(
					'ALS_MSG' => tr('You do not have domain aliases.'),
					'ALS_LIST' => ''
				)
			);
		} else {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$alsId = $row['alias_id'];
				$alsName = $row['alias_name'];
				$alsStatus = $row['alias_status'];
				$alsForwardUrl = $row['url_forward'];
				$alsMountPoint = $row['alias_mount'];

				list(
					$action, $actionScript, $isStatusOk, $certText, $certScript
				) = _client_generateDomainAliasAction(
					$alsId, $alsStatus
				);

				list(
					$redirectUrl, $editLink, $edit
				) = _client_generateDomainAliasRedirect(
					$alsId, $alsStatus, $alsForwardUrl
				);

				$alsName = decode_idna($alsName);
				$redirectUrl = decode_idna($redirectUrl);

				if ($isStatusOk) {
					$tpl->assign(
						array(
							'ALS_NAME' => tohtml($alsName),
							'ALS_STATUS_RELOAD_FALSE' => ''
						)
					);

					$tpl->parse('ALS_STATUS_RELOAD_TRUE', 'als_status_reload_true');
				} else {
					$tpl->assign(
						array(
							'ALS_NAME' => tohtml($alsName),
							'ALS_STATUS_RELOAD_TRUE' => ''
						)
					);

					$tpl->parse('ALS_STATUS_RELOAD_FALSE', 'als_status_reload_false');
				}

				$tpl->assign(
					array(
						'ALS_NAME' => tohtml($alsName),
						'ALS_MOUNT' => tohtml($alsMountPoint),
						'ALS_STATUS' => translate_dmn_status($alsStatus),
						'ALS_REDIRECT' => tohtml($redirectUrl),
						'ALS_EDIT_LINK' => $editLink,
						'ALS_EDIT' => $edit,
						'ALS_ACTION' => $action,
						'CERT_SCRIPT' => $certScript,
						'VIEW_CERT' => $certText,
						'ALS_ACTION_SCRIPT' => $actionScript
					)
				);

				$tpl->parse('ALS_ITEM', '.als_item');
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
	if ($status == 'ok') {
		return array(
			tr('Delete'),
			tohtml("alias_delete.php?id=$id"),
			true,
			tr('Add / Edit SSL certificate'),
			tohtml("cert_view.php?domain_id=$id&domain_type=als")
		);
	} elseif ($status == 'ordered') {
		return array(tr('Delete order'), tohtml("alias_order_delete.php?del_id=$id"), false, '-', '#');
	} else {
		return array(tr('N/A'), '#', false, tr('N/A'), '#');
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
	if ($redirectUrl == 'no') {
		if ($status == 'ok') {
			return array('-', tohtml("alias_edit.php?id=$id"), tr('Edit'));
		} elseif ($status == 'ordered') {
			return array('-', '#', tr('N/A'));
		} else {
			return array(tr('N/A'), '#', tr('N/A'));
		}
	} else {
		if ($status == 'ok') {
			return array($redirectUrl, tohtml("alias_edit.php?id=$id"), tr('Edit'));
		} elseif ($status == 'ordered') {
			return array($redirectUrl, '#', tr('N/A'));
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
		$stmt1 = exec_query(
			'
				SELECT
					subdomain_id, subdomain_name, subdomain_mount, subdomain_status, subdomain_url_forward, domain_name
				FROM
					subdomain
				JOIN
					domain ON (subdomain.domain_id = domain.domain_id)
				WHERE
					subdomain.domain_id = ?
				ORDER BY
					subdomain_name
			',
			$domainId
		);

		// Domain aliases subdomains
		$stmt2 = exec_query(
			'
				SELECT
					subdomain_alias_id, subdomain_alias_name, subdomain_alias_mount, subdomain_alias_url_forward,
					subdomain_alias_status, alias_name
				FROM
					subdomain_alias
				JOIN
					domain_aliasses ON subdomain_alias.alias_id = domain_aliasses.alias_id
				WHERE
					domain_id = ?
				ORDER BY
					subdomain_alias_name
			',
			$domainId
		);

		if (!($stmt1->rowCount() || $stmt2->rowCount())) {
			$tpl->assign(
				array(
					'SUB_MSG' => tr('You do not have subdomains.'),
					'SUB_LIST' => ''
				)
			);
		} else {
			while ($row = $stmt1->fetchRow(PDO::FETCH_ASSOC)) {
				$domainName = $row['domain_name'];
				$subId = $row['subdomain_id'];
				$subName = $row['subdomain_name'];
				$subStatus = $row['subdomain_status'];
				$subUrlForward = $row['subdomain_url_forward'];
				$subMountPoint = $row['subdomain_mount'];

				list(
					$action, $actionScript, $isStatusOk, $certText, $certScript
				) = _client_generateSubdomainAction(
					$subId, $subStatus
				);

				list(
					$redirectUrl, $editLink, $edit
				) = _client_generateSubdomainRedirect(
					$subId, $subStatus, $subUrlForward, 'dmn'
				);

				$domainName = decode_idna($domainName);
				$subName = decode_idna($subName);
				$redirectUrl = decode_idna($redirectUrl);

				if ($isStatusOk) {
					$tpl->assign(
						array(
							'SUB_NAME' => tohtml($subName),
							'SUB_ALIAS_NAME' => tohtml($domainName),
							'SUB_STATUS_RELOAD_FALSE' => ''
						)
					);

					$tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
				} else {
					$tpl->assign(
						array(
							'SUB_NAME' => tohtml($subName),
							'SUB_ALIAS_NAME' => tohtml($domainName),
							'SUB_STATUS_RELOAD_TRUE' => ''
						)
					);

					$tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
				}

				$tpl->assign(
					array(
						'SUB_MOUNT' => tohtml($subMountPoint),
						'SUB_REDIRECT' => $redirectUrl,
						'SUB_STATUS' => translate_dmn_status($subStatus),
						'SUB_EDIT_LINK' => $editLink,
						'SUB_EDIT' => $edit,
						'CERT_SCRIPT' => $certScript,
						'VIEW_CERT' => $certText,
						'SUB_ACTION' => $action,
						'SUB_ACTION_SCRIPT' => $actionScript
					)
				);

				$tpl->parse('SUB_ITEM', '.sub_item');
			}

			while ($row = $stmt2->fetchRow(PDO::FETCH_ASSOC)) {
				$alsName = $row['alias_name'];
				$alssubId = $row['subdomain_alias_id'];
				$alssubName = $row['subdomain_alias_name'];
				$alssubStatus = $row['subdomain_alias_status'];
				$alssubMountPoint = $row['subdomain_alias_mount'];
				$alssubUrlForward = $row['subdomain_alias_url_forward'];

				list(
					$action, $actionScript, $isStatusOk, $certText, $certScript
				) = _client_generateSubdomainAliasAction(
					$alssubId, $alssubStatus
				);

				list(
					$redirectUrl, $editLink, $edit
				) = _client_generateSubdomainRedirect(
					$alssubId, $alssubStatus, $alssubUrlForward, 'als'
				);

				$alsName = decode_idna($alsName);
				$name = decode_idna($alssubName);
				$redirectUrl = decode_idna($redirectUrl);

				if ($isStatusOk) {
					$tpl->assign(
						array(
							'SUB_NAME' => tohtml($name),
							'SUB_ALIAS_NAME' => tohtml($alsName),
							'SUB_STATUS_RELOAD_FALSE' => ''
						)
					);

					$tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
				} else {
					$tpl->assign(
						array(
							'SUB_NAME' => tohtml($name),
							'SUB_ALIAS_NAME' => tohtml($alsName),
							'SUB_STATUS_RELOAD_TRUE' => ''
						)
					);

					$tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
				}

				$tpl->assign(
					array(
						'SUB_NAME' => tohtml($name),
						'SUB_MOUNT' => tohtml($alssubMountPoint),
						'SUB_REDIRECT' => $redirectUrl,
						'SUB_STATUS' => translate_dmn_status($alssubStatus),
						'SUB_EDIT_LINK' => $editLink,
						'SUB_EDIT' => $edit,
						'CERT_SCRIPT' => $certScript,
						'VIEW_CERT' => $certText,
						'SUB_ACTION' => $action,
						'SUB_ACTION_SCRIPT' => $actionScript)
				);

				$tpl->parse('SUB_ITEM', '.sub_item');
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
	if ($status == 'ok') {
		return array(
			($redirectUrl == 'no') ? '-' : $redirectUrl,
			tohtml("subdomain_edit.php?id=$id&type=$entityType"),
			tr('Edit')
		);
	} elseif ($status == 'ordered') {
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
	if ($status == 'ok') {
		return array(
			tr('Delete'), tohtml("subdomain_delete.php?id=$id"),
			true,
			tr('Add / Edit SSL certificate'),
			tohtml("cert_view.php?domain_id=$id&domain_type=sub"),
		);
	} else {
		return array(tr('N/A'), '#', false, tr('N/A'), '#');
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
	if ($status == 'ok') {
		return array(
			tr('Delete'),
			tohtml("alssub_delete.php?id=$id"),
			true,
			tr('Add / Edit SSL certificate'),
			tohtml("cert_view.php?domain_id=$id&domain_type=alssub"),
		);
	} else {
		return array(tr('N/A'), '#', false, tr('N/A'), '#');
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
	$filterCond = '';

	if (!customerHasFeature('custom_dns_records')) {
		$filterCond = "AND owned_by <> 'custom_dns_feature'";
	}

	$stmt = exec_query(
		"
			SELECT
				t1.*,
				IFNULL(t3.alias_name, t2.domain_name) domain_name,
				IFNULL(t3.alias_status, t2.domain_status) domain_status
			FROM
				domain_dns AS t1
			LEFT JOIN
				domain AS t2 USING (domain_id)
			LEFT JOIN
				domain_aliasses AS t3 USING (alias_id)
			WHERE
				t1.domain_id = ?
			$filterCond
			ORDER BY
				t1.domain_id, t1.alias_id, t1.domain_dns, t1.domain_type
		",
		get_user_domain_id($userId)
	);

	if ($stmt->rowCount()) {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			list(
				$actionEdit,
				$actionScriptEdit
				) = _client_generateCustomDnsRecordAction(
				'edit',
				($row['owned_by'] === 'custom_dns_feature')
					? $row['domain_dns_id']
					: (
				($row['owned_by'] === 'ext_mail_feature')
					? $row['domain_id'] . ';' . ($row['alias_id'] ? 'alias' : 'normal')
					: null // FIXME Allow any component to provide it id for edit link
				),
				$row['domain_status'],
				$row['owned_by']
			);

			if ($row['owned_by'] !== 'custom_dns_feature') {
				$tpl->assign('DNS_DELETE_LINK', '');
			} else {
				list(
					$actionDelete,
					$actionScriptDelete
					) = _client_generateCustomDnsRecordAction(
					'Delete', $row['domain_dns_id'], $row['domain_status']
				);

				$tpl->assign(
					array(
						'DNS_ACTION_SCRIPT_DELETE' => tohtml($actionScriptDelete),
						'DNS_ACTION_DELETE' => $actionDelete,
						'DNS_TYPE_RECORD' => tr("%s record", $row['domain_type'])
					)
				);

				$tpl->parse('DNS_DELETE_LINK', '.dns_delete_link');
			}

			// Remove TTL part if any
			# FIXME TTL must be in dedicated column
			if(strpos($row['domain_dns'], ' ') !== false) {
				$dnsName = explode(' ', $row['domain_dns']);
				$dnsName = $dnsName[0];
			} else {
				$dnsName = $row['domain_dns'];
			}

			$tpl->assign(
				array(
					'DNS_DOMAIN' => tohtml(decode_idna($row['domain_name'])),
					'DNS_NAME' => tohtml(decode_idna($dnsName)),
					'DNS_CLASS' => tohtml($row['domain_class']),
					'DNS_TYPE' => tohtml($row['domain_type']),
					'LONG_DNS_DATA' => tohtml(wordwrap(decode_idna($row['domain_text']), 80, "\n", true)),
					'SHORT_DNS_DATA' => decode_idna((strlen($row['domain_text']) > 20) ?
						substr($row['domain_text'], 0, 17) . '...' : $row['domain_text']),
					'DNS_ACTION_SCRIPT_EDIT' => tohtml($actionScriptEdit),
					'DNS_ACTION_EDIT' => $actionEdit
				)
			);

			$tpl->parse('DNS_ITEM', '.dns_item');
			$tpl->assign('DNS_DELETE_LINK', '');
		}

		$tpl->parse('DNS_LIST', 'dns_list');
		$tpl->assign('DNS_MESSAGE', '');
	} else {
		if (customerHasFeature('custom_dns_records')) {
			$tpl->assign(
				array(
					'DNS_MSG' => tr('You do not have DNS resource records.'),
					'DNS_LIST' => ''
				)
			);
		} else {
			$tpl->assign('CUSTOM_DNS_RECORDS_BLOCK', '');
		}

	}
}

/**
 * Generates custom DNS record action.
 *
 * @access private
 * @param string $action Action
 * @param string|null $id Custom DNS record unique identifier
 * @param string $status Custom DNS record status
 * @param string $ownedBy Owner of the DNS record
 * @return array
 */
function _client_generateCustomDnsRecordAction($action, $id, $status, $ownedBy = 'custom_dns_feature')
{
	if($status == 'ok') {
		if($action == 'edit') {
			if($ownedBy === 'custom_dns_feature') {
				return array(tr('Edit'), tohtml("dns_edit.php?id=$id"));
			} elseif($ownedBy === 'ext_mail_feature') {
				return array(tr('Edit'), tohtml("mail_external_edit.php?item=" . urlencode($id)));
			}
		} elseif($ownedBy === 'custom_dns_feature') {
			return array(tr('Delete'), tohtml("dns_delete.php?id=$id"));
		}
	}

	return array(tr('N/A') ,  '#');
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

// If the feature is disabled, redirects in silent way
customerHasFeature('domain') or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
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
		'dns_item' => 'dns_list',
		'dns_edit_link' => 'dns_item',
		'dns_delete_link' => 'dns_item'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Domains'),
		'ISP_LOGO' => layout_getUserLogo(),

		'TR_DOMAINS' => tr('Domains'),
		'TR_CREATE_DATE' => tr('Creation date'),
		'TR_EXPIRE_DATE' => tr('Expire date'),

		'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
		'TR_SUBDOMAINS' => tr('Subdomains'),

		'TR_NAME' => tr('Name'),
		'TR_MOUNT' => tr('Mount point'),
		'TR_REDIRECT' => tr('Redirect'),
		'TR_STATUS' => tr('Status'),
		'TR_CERT' => tr('SSL certificate'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),

		'TR_DNS' => tr('DNS resource records'),
		'TR_DNS_NAME' => tr('Name'),
		'TR_DNS_CLASS' => tr('Class'),
		'TR_DNS_TYPE' => tr('Type'),
		'TR_DNS_ACTION' => tr('Actions'),
		'TR_DNS_DATA' => tr('Record data'),
		'TR_DOMAIN_NAME' => tr('Domain')
	)
);

generateNavigation($tpl);

client_generateDomainsList($tpl, $_SESSION['user_id']);
client_generateSubdomainsList($tpl, $_SESSION['user_id']);
client_generateDomainAliasesList($tpl, $_SESSION['user_id']);
client_generateCustomDnsRecordsList($tpl, $_SESSION['user_id']);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
