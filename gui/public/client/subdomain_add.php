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
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/****************************************************************************************
 * Script functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $userId User unique identifier
 * @return void
 */
function client_generatePage($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$subdomainName = $subdomainMountPoint = $forward = $forwardPrefix = '';

	$query = 'SELECT `domain_name`, `domain_id` FROM `domain` WHERE`domain_admin_id` = ?';
	$stmt = exec_query($query, $userId);

	$domainName = idn_to_utf8($stmt->fields['domain_name']);

	$tpl->assign(
		array(
			'DOMAIN_NAME' => '.' . tohtml($domainName),
			'SUB_DMN_CHECKED' => $cfg->HTML_CHECKED,
			'SUB_ALS_CHECKED' => ''
		)
	);

	_client_generateDomainAliasesList($tpl, $stmt->fields['domain_id'], 'no');

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_subd') {
		if ($_POST['status'] == 1) {
			$forwardPrefix = clean_input($_POST['forward_prefix']);
			$checkEnabled = $cfg->HTML_CHECKED;
			$checkDisabled = '';
			$forward = strtolower(clean_input($_POST['forward']));

			$tpl->assign(
				array(
					'READONLY_FORWARD' => '',
					'DISABLE_FORWARD' => ''
				)
			);
		} else {
			$checkEnabled = '';
			$checkDisabled = $cfg->HTML_CHECKED;
			$forward = '';

			$tpl->assign(
				array(
					'READONLY_FORWARD' => ' readonly',
					'DISABLE_FORWARD' => ' disabled="disabled"'
				)
			);
		}

		$tpl->assign(
			array(
				'HTTP_YES' => ($forwardPrefix == 'http://') ? $cfg->HTML_SELECTED : '',
				'HTTPS_YES' => ($forwardPrefix == 'https://') ? $cfg->HTML_SELECTED : '',
				'FTP_YES' => ($forwardPrefix == 'ftp://') ? $cfg->HTML_SELECTED : ''
			)
		);

		$subdomainName = clean_input($_POST['subdomain_name']);
		$subdomainMountPoint = array_encode_idna(clean_input($_POST['subdomain_mnt_pt']), true);
	} else {
		$checkEnabled = '';
		$checkDisabled = $cfg->HTML_CHECKED;
		$forward = '';
		$tpl->assign(
			array(
				'READONLY_FORWARD' => ' readonly',
				'DISABLE_FORWARD' => $cfg->HTML_DISABLED,
                'HTTP_YES' => '',
                'HTTPS_YES' => '',
                'FTP_YES' => ''
			)
		);
	}

	$tpl->assign(
		array(
			'SUBDOMAIN_NAME' => $subdomainName,
			'SUBDOMAIN_MOUNT_POINT' => $subdomainMountPoint,
			'FORWARD' => $forward,
			'CHECK_EN' => $checkEnabled,
			'CHECK_DIS' => $checkDisabled
		)
	);
}

/**
 * Generates domain aliases list that belong to the given domain ID.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId ID of parent domain
 * @param $postCheck
 * @return void
 */
function _client_generateDomainAliasesList($tpl, $domainId, $postCheck)
{

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$statusOk = $cfg->ITEM_OK_STATUS;

	$query = "
		SELECT
			`alias_id`, `alias_name`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = ?
		AND
			`alias_status` = ?
		ORDER BY
			`alias_name`
	";
	$stmt = exec_query($query, array($domainId, $statusOk));

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'ALS_ID' => '0',
				'ALS_SELECTED' => $cfg->HTML_SELECTED,
				'ALS_NAME' => tr('Empty list')
			)
		);

		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('TO_ALIAS_DOMAIN', '');
		$_SESSION['alias_count'] = 'no';
	} else {
		$firstPassed = false;

		while (!$stmt->EOF) {
			if ($postCheck == 'yes') {
				$aliasId = (!isset($_POST['als_id'])) ? '' : $_POST['als_id'];
				$aliasSelected = ($aliasId == $stmt->fields['alias_id']) ? $cfg->HTML_SELECTED : '';
			} else {
				$aliasSelected = (!$firstPassed) ? $cfg->HTML_SELECTED : '';
			}

			$tpl->assign(
				array(
					'ALS_ID' => $stmt->fields['alias_id'],
					'ALS_SELECTED' => $aliasSelected,
					'ALS_NAME' => tohtml(idn_to_utf8($stmt->fields['alias_name']))
				)
			);

			$tpl->parse('ALS_LIST', '.als_list');
			$stmt->moveNext();

			if (!$firstPassed) {
				$firstPassed = true;
			}
		}
	}
}

/**
 * Whether or not the given subdomain exists in database.
 *
 * @param int $domainId ID of parent domain
 * @param string $subdomainName Subdomain name
 * @return bool TRUE if $subdomainName exists in database, FALSE otherwise
 */
function _client_subdomainExists($domainId, $subdomainName)
{
	global $domainName;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($_POST['dmn_type'] == 'als') {
		$query1 = '
			SELECT
				COUNT(`subdomain_alias_id`) `cnt`
			FROM
				`subdomain_alias`
			WHERE
				`alias_id` = ?
			AND
				`subdomain_alias_name` = ?
		';
		$query2 = 'SELECT COUNT(`alias_id`) `cnt` FROM `domain_aliasses` WHERE `alias_name` = ?';
	} else {
		$query1 = '
			SELECT
				COUNT(`subdomain_id`) `cnt`
			FROM
				`subdomain`
			WHERE
				`domain_id` = ?
			AND
				`subdomain_name` = ?
		';
		$query2 = 'SELECT COUNT(`domain_id`) `cnt` FROM `domain` WHERE `domain_name` = ?';
	}

	$domainName = $subdomainName . '.' . $domainName;
	$stmt1 = exec_query($query1, array($domainId, $subdomainName));
	$stmt2 = exec_query($query2, array($domainName));

	$unallowedSubdomains = array(
		'www', 'mail', 'webmail', 'pop', 'pop3', 'imap', 'smtp', 'pma', 'relay', 'ftp', 'ns1', 'ns2', 'localhost'
	);

	if ($stmt1->fields['cnt'] == 0 && $stmt2->fields['cnt'] == 0 && !in_array($subdomainName, $unallowedSubdomains)
		&& $cfg->BASE_SERVER_VHOST != $domainName
	) {
		return false;
	}

	return true;
}

/**
 * Is allowed mount point?
 *
 * @param string $mountPoint Mount point
 * @param int $domainId parent domain ID
 * @return bool TRUE if $mountPoint is allowed, FALSE otherwise
 */
function _client_isAllowedMountPoint($mountPoint, $domainId)
{
	$regRestrictedTokens = 'backups|cgi-bin|domain_disable_page|errors|logs|phptmp';

	if(preg_match("@^(.*)({$regRestrictedTokens})(?:[/]|$).*@", $mountPoint, $matches)) {
		$mountPoint = $matches[1];
		if($mountPoint == '/') {
			return false;
		} elseif(in_array($matches[2], array('cgi-bin', 'domain_disable_page', 'phptmp'))) {
			$mountPoint = rtrim($mountPoint, '/');
			$mountPoint = "^$mountPoint/?$";

			$query = "
				SELECT `subdomain_mount` `mpoint` FROM `subdomain` WHERE `subdomain_mount` REGEXP ? AND `domain_id` = ?
				UNION
				SELECT `subdomain_alias_mount` `mpoint` FROM subdomain_alias WHERE `subdomain_alias_mount` REGEXP ?
				AND alias_id IN(SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
				UNION
				SELECT alias_mount `mpoint` FROM domain_aliasses WHERE alias_mount REGEXP ? AND domain_id = ?
			";
			$stmt = exec_query($query, array($mountPoint, $domainId, $mountPoint, $domainId, $mountPoint, $domainId));

			if($stmt->rowCount()){
				return false;
			}
		}
	}

	return true;
}

/**
 * Adds a subdomain.
 *
 * @param int $userId customer unique identifier
 * @param int $domainId Domain unique identifier
 * @param string $subdomainName Subdomain name
 * @param string $subdomainMountPoint Subdomain mount point
 * @param string $forward Forward URL
 * @return void
 */
function client_addSubdomain($userId, $domainId, $subdomainName, $subdomainMountPoint, $forward)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	$statusAdd = $cfg->ITEM_ADD_STATUS;

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeAddSubdomain,
		array('subdomainName' => $subdomainName, 'domainId' => $domainId, 'customerId' => $userId)
	);

	if ($_POST['dmn_type'] == 'als') {
		$query = "
			INSERT INTO `subdomain_alias` (
			    `alias_id`, `subdomain_alias_name`, `subdomain_alias_mount`, `subdomain_alias_url_forward`,
			    `subdomain_alias_status`
			) VALUES (
			    ?, ?, ?, ?, ?
			)
		";
	} else {
		$query = "
			INSERT INTO `subdomain` (
			    `domain_id`, `subdomain_name`, `subdomain_mount`, `subdomain_url_forward`, `subdomain_status`
			) VALUES (
			    ?, ?, ?, ?, ?
			)
		";
	}

	exec_query($query, array($domainId, $subdomainName, $subdomainMountPoint, $forward, $statusAdd));
	$subdomain_id = $db->insertId();

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterAddSubdomain,
		array(
			'subdomainName' => $subdomainName, 'domainId' => $domainId, 'customerId' => $userId,
			'subdomainId' => $subdomain_id
		)
	);

	update_reseller_c_props(get_reseller_id($domainId));

	write_log($_SESSION['user_logged'] . ": added new subdomain: " . $subdomainName, E_USER_NOTICE);
	send_request();
}

/**
 * Check subdomain data.
 *
 * @param int $userId User unique identifier
 * @param string $domainName ID of parent domain
 * @return
 */
function client_checkSubdomain($userId, $domainName)
{
	global $validation_err_msg;
	$domainId = get_user_domain_id($userId);

	if (isset($_POST['uaction']) && $_POST['uaction'] == 'add_subd') {
		if (empty($_POST['subdomain_name'])) {
			set_page_message(tr('Please specify subdomain name.'), 'error');
			return;
		}

		$subdomainName = strtolower($_POST['subdomain_name']);

		if ($_POST['status'] == 1) {
			$forward = strtolower(clean_input($_POST['forward']));
			$forwardPrefix = clean_input($_POST['forward_prefix']);
		} else {
			$forward = 'no';
			$forwardPrefix = '';
		}

		if (isset($_POST['subdomain_mnt_pt']) && $_POST['subdomain_mnt_pt'] !== '') {
			$subdomainMountPoint = array_encode_idna(strtolower($_POST['subdomain_mnt_pt']), true);
		} else {
			$subdomainMountPoint = '/';
		}

		if ($_POST['dmn_type'] == 'als') {
			if (!isset($_POST['als_id'])) {
				set_page_message(tr('No valid alias domain selected.'), 'error');
				return;
			}

			$query = "SELECT `alias_mount` FROM `domain_aliasses` WHERE `alias_id` = ?";
			$stmt = exec_query($query, $_POST['als_id']);

			$aliasMountPoint = $stmt->fields['alias_mount'];

			if ($subdomainMountPoint[0] != '/') {
				$subdomainMountPoint = '/' . $subdomainMountPoint;
			}

			$subdomainMountPoint = $aliasMountPoint . $subdomainMountPoint;
			$subdomainMountPoint = str_replace('//', '/', $subdomainMountPoint);
			$domainId = $_POST['als_id'];
		}

		// First check if input string is a valid domain names
		if (!validates_subdname($subdomainName, decode_idna($domainName))) {
			set_page_message($validation_err_msg, 'error');
			return;
		}

		// Should be perfomed after domain names syntax validation now
		$subdomainName = encode_idna($subdomainName);

		if (_client_subdomainExists($domainId, $subdomainName)) {
			set_page_message(tr('Subdomain already exists or is not allowed.'), 'error');
		} elseif(!_client_isAllowedMountPoint($subdomainMountPoint, $domainId)) {
			set_page_message(tr('This mount point is not allowed.'), 'error');
		} elseif (!validates_mpoint($subdomainMountPoint)) {
			set_page_message(tr('Incorrect or is not allowed.'), 'error');
		} elseif ($_POST['status'] == 1) {
			$surl = @parse_url($forwardPrefix . decode_idna($forward));

			if ($surl === false) {
				set_page_message(tr('Wrong domain part in forward URL.'), 'error');
			} else {
				$domain = $surl['host'];

				if (substr_count($domain, '.') <= 2) {
					$retVal = validates_dname($domain);
				} else {
					$retVal = validates_dname($domain, true);
				}

				if (!$retVal) {
					set_page_message(tr('Wrong domain part in forward URL.'), 'error');
				} else {
					$domain = encode_idna($surl['host']);
					$forward = $surl['scheme'] . '://';

					if (isset($surl['user'])) {
						$forward .= $surl['user'] . (isset($surl['pass']) ? ':' . $surl['pass'] : '') . '@';
					}

					$forward .= $domain;

					if (isset($surl['port'])) {
						$forward .= ':' . $surl['port'];
					}

					if (isset($surl['path'])) {
						$forward .= $surl['path'];
					} else {
						$forward .= '/';
					}

					if (isset($surl['query'])) {
						$forward .= '?' . $surl['query'];
					}

					if (isset($surl['fragment'])) {
						$forward .= '#' . $surl['fragment'];
					}
				}
			}
		} else {
			$subdomainMountPoint = array_encode_idna($subdomainMountPoint, true);
		}

		if (Zend_Session::namespaceIsset('pageMessages')) {
			return;
		}

		client_addSubdomain($userId, $domainId, $subdomainName, $subdomainMountPoint, $forward);
		set_page_message(tr('Subdomain successfully scheduled for addition.'), 'success');
		redirectTo('domains_manage.php');
	}
}

/************************************************************************************
 * Main program
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('subdomains')) {
	redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Avoid useless work during Ajax request
if (!is_xhr()) {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/subdomain_add.tpl',
			'page_message' => 'layout',
			'subdomain_add_js' => 'page',
			'subdomain_add_form' => 'page',
			'als_list' => 'subdomain_add_form'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Client / Manage domains - Add subdomain'),
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_SUBDOMAIN_DATA' => tr('Subdomain data'),
			'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
			'TR_MOUNT_POINT' => tr('Mount point'),
			'TR_FORWARD' => tr('Redirect to URL'),
			'TR_ADD' => tr('Add'),
			'TR_ENABLE_FWD' => tr('Redirect'),
			'TR_ENABLE' => tr('Enable'),
			'TR_DISABLE' => tr('Disable'),
			'TR_PREFIX_HTTP' => 'http://',
			'TR_PREFIX_HTTPS' => 'https://',
			'TR_PREFIX_FTP' => 'ftp://')
	);

	generateNavigation($tpl);
}

$domainProperties = get_domain_default_props($_SESSION['user_id'], true);
$subdomainsUsage = get_domain_running_sub_cnt($domainProperties['domain_id']);

/**
 * Dispatches the request
 */

if ($domainProperties['domain_subd_limit'] != 0 && $subdomainsUsage >= $domainProperties['domain_subd_limit']) {

	set_page_message(tr('We are sorry but You reached the maximum number of subdomains allowed by your subscription.'), 'warning');

	if (is_xhr()) {
		header('Status: 403 Operation not allowed');
	}

	redirectTo('domains_manage.php'); // Location header is retrieved via the jqXHR object in case of xhr request
} elseif (isset($_POST['uaction'])) {
	if ($_POST['uaction'] == 'toASCII') { // Ajax request
		header('Content-Type: text/plain; charset=utf-8');
		header('Cache-Control: no-cache, private');
		header('Pragma: no-cache');
		header("HTTP/1.0 200 Ok");

		// Todo check return value here before echo...
		echo '/' . idn_to_ascii(strtolower($_POST['subdomain']));
		exit;
	} elseif ($_POST['uaction'] == 'add_subd') {
		client_generatePage($tpl, $_SESSION['user_id']);
		client_checkSubdomain($_SESSION['user_id'], $domainProperties['domain_name']);
	} else {
		set_page_message(tr('Wrong request'), 'error');
		redirectTo('domains_manage.php');
	}
} else {
	client_generatePage($tpl, $_SESSION['user_id']);
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
