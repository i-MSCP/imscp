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
 * Generate page.
 *
 * @param  iMSCP_pTemplate $tpl
 * @param int $alsId
 */
function reseller_generatePage($tpl, $alsId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$resellerId = $_SESSION['user_id'];

	$query = "
		SELECT
			t1.`domain_id`, t1.`alias_id`, t1.`alias_name`, t2.`domain_id`, t2.`domain_created_id`
		FROM
			`domain_aliasses` AS t1, `domain` AS t2
		WHERE
			t1.`alias_id` = ?
		AND
			t1.`domain_id` = t2.`domain_id`
		AND
			t2.`domain_created_id` = ?
	";
	$stmt = exec_query($query, array($alsId, $resellerId));

	if (!$stmt->rowCount()) {
		set_page_message(tr('User does not exist.'), 'error');
		redirectTo('alias.php');
	}
	// Get data from sql
	$res = exec_query("SELECT * FROM `domain_aliasses` WHERE `alias_id` = ?", $alsId);

	if (!$res->rowCount()) {
		showBadRequestErrorPage();
		exit;
	}

	$data = $res->fetchRow();

	if (isset($_POST['uaction']) && ($_POST['uaction'] == 'modify')) {
		$urlForward = strtolower(clean_input($_POST['forward']));
	} else {
		$urlForward = decode_idna(preg_replace("(ftp://|https://|http://)", "", $data['url_forward']));

		if ($data["url_forward"] == "no") {
			$checkedYes = '';
			$checkedNo = $cfg->HTML_CHECKED;
			$urlForward = '';
			$tpl->assign(
				array(
					'READONLY_FORWARD' => $cfg->HTML_READONLY,
					'DISABLE_FORWARD' => $cfg->HTML_DISABLED,
					'HTTP_YES' => '',
					'HTTPS_YES' => '',
					'FTP_YES' => ''
				)
			);
		} else {
			$checkedYes = $cfg->HTML_CHECKED;
			$checkedNo = '';
			$tpl->assign(
				array(
					'READONLY_FORWARD' => '',
					'DISABLE_FORWARD' => '',
					'HTTP_YES' => (preg_match("/http:\/\//", $data['url_forward'])) ? $cfg->HTML_SELECTED : '',
					'HTTPS_YES' => (preg_match("/https:\/\//", $data['url_forward'])) ? $cfg->HTML_SELECTED : '',
					'FTP_YES' => (preg_match("/ftp:\/\//", $data['url_forward'])) ? $cfg->HTML_SELECTED : ''
				)
			);
		}
		$tpl->assign(
			array(
				'CHECK_EN' => $checkedYes,
				'CHECK_DIS' => $checkedNo
			)
		);
	}

	// Fill in the fields
	$tpl->assign(
		array(
			'ALIAS_NAME' => tohtml(decode_idna($data['alias_name'])),
			'FORWARD' => tohtml($urlForward),
			'ID' => $alsId
		)
	);
}

/**
 * Update domain alias.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $alsId Domain alias unique identifier
 * @return bool
 */
function reseller_updateDomainAlias($tpl, $alsId)
{
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['status']) && $_POST['status'] == 1) {
		$forwardProto = clean_input($_POST['forward_prefix']);
		$forward = strtolower(clean_input($_POST['forward']));
		$forwardUrl = @parse_url($forwardProto . $forward);

		if ($forwardUrl === false) {
			set_page_message(tr('Wrong address in forward URL.'), 'error');
		} else {
			$domain = $forwardUrl['host'];

			if (substr_count($domain, '.') <= 2) {
				$ret = validates_dname($domain);
			} else {
				$ret = validates_dname($domain, true);
			}

			if (!$ret) {
				set_page_message(tr('Wrong domain part in forward URL.', 'error'));
			} else {
				$domain = encode_idna($forwardUrl['host']);
				$forward = $forwardUrl['scheme'] . '://';

				if (isset($forwardUrl['user'])) {
					$forward .= $forwardUrl['user'] . (isset($forwardUrl['pass']) ? ':' . $forwardUrl['pass'] : '') . '@';
				}

				$forward .= $domain;

				if (isset($forwardUrl['port'])) {
					$forward .= ':' . $forwardUrl['port'];
				}

				if (isset($forwardUrl['path'])) {
					$forward .= $forwardUrl['path'];
				} else {
					$forward .= '/';
				}

				if (isset($forwardUrl['query'])) {
					$forward .= '?' . $forwardUrl['query'];
				}

				if (isset($forwardUrl['fragment'])) {
					$forward .= '#' . $forwardUrl['fragment'];
				}
			}
		}

		$checkedYes = $cfg->HTML_CHECKED;
		$checkedNo = '';

		$tpl->assign(
			array(
				'FORWARD' => tohtml($forward),
				'HTTP_YES' => ($forwardProto === 'http://') ? $cfg->HTML_SELECTED : '',
				'HTTPS_YES' => ($forwardProto === 'https://') ? $cfg->HTML_SELECTED : '',
				'FTP_YES' => ($forwardProto === 'ftp://') ? $cfg->HTML_SELECTED : '',
				'CHECK_EN' => $checkedYes,
				'CHECK_DIS' => $checkedNo,
				'DISABLE_FORWARD' => '',
				'READONLY_FORWARD' => ''
			)
		);
	} else {
		$checkedYes = $cfg->HTML_CHECKED;
		$checkedNo = '';
		$forward = 'no';

		$tpl->assign(
			array(
				'READONLY_FORWARD' => $cfg->HTML_READONLY,
				'DISABLE_FORWARD' => $cfg->HTML_DISABLED,
				'CHECK_EN' => $checkedYes,
				'CHECK_DIS' => $checkedNo
			)
		);
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		$query = "UPDATE `domain_aliasses` SET `url_forward` = ?, `alias_status` = ? WHERE `alias_id` = ?";
		exec_query($query, array($forward, $cfg->ITEM_TOCHANGE_STATUS, $alsId));

		$query = "UPDATE `subdomain_alias` SET `subdomain_alias_status` = ? WHERE `alias_id` = ?";
		exec_query($query, array($cfg->ITEM_TOCHANGE_STATUS, $alsId));

		send_request();
		return true;
	} else {
		return false;
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

resellerHasFeature('domain_aliases') or showBadRequestErrorPage();

$cfg = iMSCP_Registry::get('config');

if(isset($_GET['edit_id'])) {
	$alsId = clean_input($_GET['edit_id']);

	$tpl = new iMSCP_pTemplate();

	if (isset($_POST['uaction']) && reseller_updateDomainAlias($tpl, $alsId)) {
		set_page_message(tr('Domain alias successfully updated'), 'success');
		write_log("{$_SESSION['user_logged']}: edited domain alias with ID: $alsId", E_USER_NOTICE);
		redirectTo('alias.php');
	}

	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'reseller/alias_edit.tpl',
			'page_message' => 'layout'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Reseller / Customers / Domain Aliases / Edit Alias'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_ALIAS_DATA' => tr('Domain alias data'),
			'TR_ALIAS_NAME' => tr('Alias name'),
			'TR_FORWARD' => tr('Forward to URL'),
			'TR_MODIFY' => tr('Update'),
			'TR_CANCEL' => tr('Cancel'),
			'TR_ENABLE_FWD' => tr("Enable Forward"),
			'TR_ENABLE' => tr("Enable"),
			'TR_DISABLE' => tr("Disable"),
			'TR_PREFIX_HTTP' => 'http://',
			'TR_PREFIX_HTTPS' => 'https://',
			'TR_PREFIX_FTP' => 'ftp://'
		)
	);

	generateNavigation($tpl);
	reseller_generatePage($tpl, $alsId);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}




