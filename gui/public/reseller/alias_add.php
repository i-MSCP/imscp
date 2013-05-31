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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Initialize variables.
 *
 * @return void
 */
function reseller_initVariables()
{
	global $customerDmnId, $alsName, $forward, $mountPoint;

	$customerDmnId = $alsName = $forward = $mountPoint = '';
}

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function reseller_generatePage($tpl, $resellerId)
{
	global $alsName, $forward, $forwardProto, $mountPoint;

	$cfg = iMSCP_Registry::get('config');

	$resellerProps = imscp_getResellerProperties($resellerId);

	if ($resellerProps['max_als_cnt'] != 0) {
		list(, , , , , , $customerAlsCount) = generate_reseller_user_props($resellerId);

		if ($customerAlsCount >= $resellerProps['max_als_cnt']) {
			set_page_message(tr('Domain alias limit reached.'), 'error');
			redirectTo('alias.php');
		}
	}

	if (isset($_POST['status']) && $_POST['status'] == 1) {
		$forwardProto = clean_input($_POST['forward_prefix']);

		if ($_POST['status'] == 1) {
			$checkedYes = $cfg->HTML_CHECKED;
			$checkedNo = '';
			$forward = encode_idna(strtolower(clean_input($_POST['forward'])));

			$tpl->assign(
				array(
					'READONLY_FORWARD' => '',
					'DISABLE_FORWARD' => ''
				)
			);
		} else {
			$checkedYes = '';
			$checkedNo = $cfg->HTML_CHECKED;
			$forward = '';

			$tpl->assign(
				array(
					'READONLY_FORWARD' => $cfg->HTML_READONLY,
					'DISABLE_FORWARD' => $cfg->HTML_DISABLED
				)
			);
		}

		$tpl->assign(
			array(
				'HTTP_YES' => ($forwardProto == 'http://') ? $cfg->HTML_SELECTED : '',
				'HTTPS_YES' => ($forwardProto == 'https://') ? $cfg->HTML_SELECTED : '',
				'FTP_YES' => ($forwardProto == 'ftp://') ? $cfg->HTML_SELECTED : ''
			)
		);
	} else {
		$checkedYes = '';
		$checkedNo = $cfg->HTML_CHECKED;
		$forward = '';

		$tpl->assign(
			array(
				'READONLY_FORWARD' => $cfg->HTML_READONLY,
				'DISABLE_FORWARD' => $cfg->HTML_DISABLED,
				'HTTP_YES' => '',
				'HTTPS_YES' => '',
				'FTP_YES' => ''
			)
		);
	}

	$tpl->assign(
		array(
			'DOMAIN' => tohtml($alsName),
			'MP' => tohtml($mountPoint),
			'FORWARD' => tohtml(encode_idna($forward)),
			'CHECK_EN' => $checkedYes,
			'CHECK_DIS' => $checkedNo
		)
	);

	reseller_generateUserList($tpl, $resellerId);
}

/**
 * Is allowed mount point?
 *
 * @param string $mountPoint Mount point
 * @param int $domainId parent domain ID
 * @return bool TRUE if $mountPoint is allowed, FALSE otherwise
 */
function _reseller_isAllowedMountPoint($mountPoint, $domainId)
{
	$regRestrictedTokens = 'backups|cgi-bin|domain_disable_page|errors|logs|phptmp';

	if (preg_match("@^(.*)({$regRestrictedTokens})(?:[/]|$).*@", $mountPoint, $matches)) {
		$mountPoint = $matches[1];

		if ($mountPoint == '/') {
			return false;
		} elseif (in_array($matches[2], array('cgi-bin', 'domain_disable_page', 'phptmp'))) {
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

			if ($stmt->rowCount()) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Adds domain alias.
 *
 * @throws iMSCP_Exception_Database
 * @return void
 */
function reseller_addDomainAlias()
{
	global $customerDmnId, $alsName, $forward, $forwardProto, $mountPoint, $validation_err_msg;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$customerDmnId = clean_input($_POST['user_domain_account']);

	$alsName = strtolower($_POST['ndomain_name']);
	$mountPoint = array_encode_idna(strtolower($_POST['ndomain_mpoint']), true);

	if ($_POST['status'] == 1) {
		$forward = encode_idna(strtolower(clean_input($_POST['forward'])));
		$forwardProto = clean_input($_POST['forward_prefix']);
	} else {
		$forward = 'no';
		$forwardProto = '';
	}

	if (!validates_dname($alsName)) {
		set_page_message($validation_err_msg, 'error');
		return;
	}

	// Should be perfomed after domain names syntax validation now
	$asciiAlsName = encode_idna($alsName);

	if (imscp_domain_exists($asciiAlsName, $_SESSION['user_id'])) {
		set_page_message(tr('Domain with same name already exists.'), 'error');
	} elseif (!validates_mpoint($mountPoint)) {
		set_page_message(tr('Incorrect mount point syntax.'), 'error');
	} elseif (!_reseller_isAllowedMountPoint($mountPoint, $customerDmnId)) {
		set_page_message(tr('This mount point is not allowed.'), 'error');
	} elseif ($asciiAlsName == $cfg->BASE_SERVER_VHOST) {
		set_page_message(tr('This domain is not allowed.'), 'error');
	} elseif ($_POST['status'] == 1) {
		$aurl = @parse_url($forwardProto . decode_idna($forward));

		if ($aurl === false) {
			set_page_message(tr('Wrong address in forward URL.'), 'error');
		} else {
			$domain = $aurl['host'];

			if (substr_count($domain, '.') <= 2) {
				$ret = validates_dname($domain);
			} else {
				$ret = validates_dname($domain, true);
			}

			if (!$ret) {
				set_page_message(tr('Wrong domain part in forward URL.', 'error'));
			} else {
				$domain = encode_idna($aurl['host']);
				$forward = $aurl['scheme'] . '://';

				if (isset($aurl['user'])) {
					$forward .= $aurl['user'] . (isset($aurl['pass']) ? ':' . $aurl['pass'] : '') . '@';
				}

				$forward .= $domain;

				if (isset($aurl['port'])) {
					$forward .= ':' . $aurl['port'];
				}

				if (isset($aurl['path'])) {
					$forward .= $aurl['path'];
				} else {
					$forward .= '/';
				}

				if (isset($aurl['query'])) {
					$forward .= '?' . $aurl['query'];
				}

				if (isset($aurl['fragment'])) {
					$forward .= '#' . $aurl['fragment'];
				}
			}
		}
	} else {
		$query = "SELECT `domain_id` FROM `domain_aliasses` WHERE `alias_name` = ? LIMIT 1";
		$stmt1 = exec_query($query, $asciiAlsName);

		$query = "SELECT `domain_id` FROM `domain` WHERE `domain_name` = ? LIMIT 1";
		$stmt2 = exec_query($query, $asciiAlsName);

		if ($stmt1->rowCount() || $stmt2->rowCount()) {
			set_page_message(tr('Domain already registered on the system.'), 'error');
		}
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		$query = "SELECT `domain_ip_id` FROM `domain` WHERE `domain_id` = ?";
		$stmt = exec_query($query, $customerDmnId);

		$dmnIp = $stmt->fields['domain_ip_id'];

		/** @var iMSCP_Database $db */
		$db = iMSCP_Registry::get('db');

		try {
			$db->beginTransaction();

			$customerId = who_owns_this($customerDmnId, 'dmn_id');

			$query = "
				INSERT INTO `domain_aliasses` (
					`domain_id`, `alias_name`, `alias_mount`, `alias_status`, `alias_ip_id`, `url_forward`
				) VALUES (
					?, ?, ?, ?, ?, ?
				)
			";
			exec_query(
				$query, array($customerDmnId, $asciiAlsName, $mountPoint, $cfg->ITEM_ADD_STATUS, $dmnIp, $forward)
			);

			$alsId = $db->insertId();

			// Since the reseller is allowed to add an alias for customer accounts, whatever the value of
			// their domain aliases limit, we update the related fields to avoid any consistency problems.

			$customerProps = get_domain_default_props($customerId);
			$newCustomerAlsLimit = 0;

			if ($customerProps['domain_alias_limit'] > 0) { // Customer has als limit

				$query = "SELECT COUNT(`alias_id`) AS `cnt` FROM `domain_aliasses` WHERE `domain_id` = ?";
				$stmt = exec_query($query, $customerDmnId);
				$customerAlsCount = $stmt->fields['cnt'];

				// If the customer als limit is reached, we extend it
				if ($customerAlsCount >= $customerProps['domain_alias_limit']) {
					$newCustomerAlsLimit += $customerAlsCount;
				}
			} elseif ($customerProps['domain_alias_limit'] != 0) { // Als feature is disabled for the customer.

				// We simply enable als feature by setting the limit to 1
				$newCustomerAlsLimit = 1;

				// We also update reseller current als count (number of assigned als) by incrementing the current value.
				$query = '
					UPDATE
						`reseller_props`
					SET
						`current_als_cnt` = (`current_als_cnt` + 1)
					WHERE
						`reseller_id` = ?
				';
				exec_query($query, $_SESSION['user_id']);
			}

			// We update the customer als limit according if needed
			if ($newCustomerAlsLimit) {
				exec_query(
					'UPDATE `domain` SET `domain_alias_limit` = ? WHERE `domain_admin_id` = ?',
					array($newCustomerAlsLimit, $customerId)
				);
			}

			$query = "SELECT `email` FROM `admin` WHERE `admin_id` = ? LIMIT 1";
			$stmt = exec_query($query, $customerId);
			$customerEmail = $stmt->fields['email'];

			// Create default email accounts if needed
			if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
				client_mail_add_default_accounts($customerDmnId, $customerEmail, $asciiAlsName, 'alias', $alsId);
			}

			$db->commit();
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
			throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
		}

		send_request();
		write_log("{$_SESSION['user_logged']} added domain alias: $alsName", E_USER_NOTICE);
		set_page_message(tr('Domain alias successfully scheduled for addition'), 'success');
		redirectTo('alias.php');
	}
}

/**
 * Generate users list.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $reselleId Reseller unique identifier
 * @return bool
 */
function reseller_generateUserList($tpl, $reselleId)
{
	global $customerDmnId;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT `admin_id` FROM `admin` WHERE `admin_type` = 'user' AND `created_by` = ? ORDER BY `admin_name`";
	$stmt1 = exec_query($query, $reselleId);

	$i = 1;

	while ($adminData = $stmt1->fetchRow()) {
		$adminId = $adminData['admin_id'];

		$selected = '';

		// Get domain data
		$query = "
			SELECT `domain_id`, IFNULL(`domain_name`, '') `domain_name` FROM `domain` WHERE `domain_admin_id` = ?
		";
		$stmt2 = exec_query($query, $adminId);
		$dmnData = $stmt2->fetchRow();

		$dmnId = $dmnData['domain_id'];
		$dmnName = $dmnData['domain_name'];

		if (($customerDmnId == '' && $i == 1) || ($customerDmnId == $dmnId)) {
			$selected = $cfg->HTML_SELECTED;
		}

		$dmnName = decode_idna($dmnName);

		$tpl->assign(
			array(
				'USER' => $dmnId,
				'USER_DOMAIN_ACCOUNT' => tohtml($dmnName),
				'SELECTED' => $selected
			)
		);

		$i++;
		$tpl->parse('USER_ENTRY', '.user_entry');
	}

	return true;
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (resellerHasFeature('domain_aliases') && resellerHasCustomers()) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!is_xhr()) {
		$resellerProps = imscp_getResellerProperties($_SESSION['user_id']);

		if ($resellerProps['max_als_cnt'] != 0) {
			if ($resellerProps['current_als_cnt'] >= $resellerProps['max_als_cnt']) {
				showBadRequestErrorPage();
			}
		}

		$tpl = new iMSCP_pTemplate();
		$tpl->define_dynamic(
			array(
				'layout' => 'shared/layouts/ui.tpl',
				'page' => 'reseller/alias_add.tpl',
				'page_message' => 'layout',
				'user_entry' => 'page'
			)
		);

		$tpl->assign(
			array(
				'THEME_CHARSET' => tr('encoding'),
				'ISP_LOGO' => layout_getUserLogo(),
				'TR_PAGE_TITLE' => tr('Reseller / Customers / Domain Aliases / Add Alias'),
				'TR_ALIAS_DATA' => tr('Domain alias data'),
				'TR_DOMAIN_NAME' => tr('Domain name'),
				'TR_DOMAIN_ACCOUNT' => tr('User account'),
				'TR_MOUNT_POINT' => tr('Directory mount point'),
				'TR_FORWARD' => tr('Forward to URL'),
				'TR_ADD' => tr('Add alias'),
				'TR_DMN_HELP' => tr("You do not need 'www.' i-MSCP will add it automatically."),
				'TR_JS_EMPTYDATA' => tr("Empty data or wrong field."),
				'TR_JS_WDNAME' => tr("Wrong domain name!"),
				'TR_JS_MPOINTERROR' => tr("Please write mount point!"),
				'TR_ENABLE_FWD' => tr("Enable Forward"),
				'TR_ENABLE' => tr("Enable"),
				'TR_DISABLE' => tr("Disable"),
				'TR_PREFIX_HTTP' => 'http://',
				'TR_PREFIX_HTTPS' => 'https://',
				'TR_PREFIX_FTP' => 'ftp://'
			)
		);

		generateNavigation($tpl);
	}

	if (isset($_POST['uaction'])) {
		if ($_POST['uaction'] == 'toASCII') { // Ajax request
			header('Content-Type: text/plain; charset=utf-8');
			header('Cache-Control: no-cache, private');
			header('Pragma: no-cache');
			header("HTTP/1.0 200 Ok");

			$asciiString = encode_idna(strtolower($_POST['domain']));
			echo !empty($asciiString) ? '/' . $asciiString : '';
			exit;
		} elseif ($_POST['uaction'] == 'add_alias') {
			reseller_addDomainAlias();
		} else {
			showBadRequestErrorPage();
		}
	} else {
		reseller_initVariables();
	}

	reseller_generatePage($tpl, $_SESSION['user_id']);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
