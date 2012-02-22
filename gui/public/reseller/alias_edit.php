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
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if(!resellerHasFeature('domain_aliases')) {
	return 'index.php';
}

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/alias_edit.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Manage Domain Alias/Edit Alias'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

$tpl->assign(
	array(
		'TR_EDIT_ALIAS' => tr('Edit domain alias'),
		'TR_ALIAS_NAME' => tr('Alias name'),
		'TR_DOMAIN_IP' => tr('Domain IP'),
		'TR_FORWARD' => tr('Forward to URL'),
		'TR_MODIFY' => tr('Modify'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_ENABLE_FWD' => tr("Enable Forward"),
		'TR_ENABLE' => tr("Enable"),
		'TR_DISABLE' => tr("Disable"),
		'TR_DISABLE' => tr("Disable"),
		'TR_PREFIX_HTTP' => 'http://',
		'TR_PREFIX_HTTPS' => 'https://',
		'TR_PREFIX_FTP' => 'ftp://'));

generateNavigation($tpl);

// "Modify" button has been pressed
if (isset($_POST['uaction']) && ('modify' === $_POST['uaction'])) {
	if (isset($_SESSION['edit_ID'])) {
		$editid = $_SESSION['edit_ID'];
	} else if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	} else {
		unset($_SESSION['edit_ID']);

		$_SESSION['aledit'] = '_no_';
		redirectTo('alias.php');
	}
	// Save data to db
	if (check_fwd_data($tpl, $editid)) {
		$_SESSION['aledit'] = "_yes_";
		redirectTo('alias.php');
	}
} else {
	// Get user id that comes for edit
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	}

	$_SESSION['edit_ID'] = $editid;
	$tpl->assign('PAGE_MESSAGE', "");
}

gen_editalias_page($tpl, $editid);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();

/**
 *
 * @param  iMSCP_pTemplate $tpl
 * @param $edit_id
 */
function gen_editalias_page($tpl, $edit_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$reseller_id = $_SESSION['user_id'];

	$query = "
		SELECT
			t1.`domain_id`,
			t1.`alias_id`,
			t1.`alias_name`,
			t2.`domain_id`,
			t2.`domain_created_id`
		FROM
			`domain_aliasses` AS t1,
			`domain` AS t2
		WHERE
			t1.`alias_id` = ?
		AND
			t1.`domain_id` = t2.`domain_id`
		AND
			t2.`domain_created_id` = ?
	";

	$rs = exec_query($query, array($edit_id, $reseller_id));

	if ($rs->recordCount() == 0) {
		set_page_message(tr('User does not exist.'), 'error');
		redirectTo('alias.php');
	}
	// Get data from sql
	$res = exec_query("SELECT * FROM `domain_aliasses` WHERE `alias_id` = ?", $edit_id);

	if ($res->recordCount() <= 0) {
		$_SESSION['aledit'] = '_no_';
		redirectTo('alias.php');
	}
	$data = $res->fetchRow();
	// Get IP data
	$ipres = exec_query("SELECT * FROM `server_ips` WHERE `ip_id` = ?", $data['alias_ip_id']);
	$ipdat = $ipres->fetchRow();
	$ip_data = $ipdat['ip_number'] . ' (' . $ipdat['ip_alias'] . ')';

	if (isset($_POST['uaction']) && ($_POST['uaction'] == 'modify')) {
		$url_forward = strtolower(clean_input($_POST['forward']));
	} else {
		$url_forward = decode_idna(preg_replace("(ftp://|https://|http://)", "", $data['url_forward']));

		if ($data["url_forward"] == "no") {
			$check_en = '';
			$check_dis = $cfg->HTML_CHECKED;
			$url_forward = '';
			$tpl->assign(
				array(
					'READONLY_FORWARD' => $cfg->HTML_READONLY,
					'DISABLE_FORWARD' => $cfg->HTML_DISABLED,
					'HTTP_YES' => '',
					'HTTPS_YES' => '',
					'FTP_YES' => ''));
		} else {
			$check_en = $cfg->HTML_CHECKED;
			$check_dis = '';
			$tpl->assign(
				array(
					'READONLY_FORWARD' => '',
					'DISABLE_FORWARD' => '',
					'HTTP_YES' => (preg_match("/http:\/\//", $data['url_forward'])) ? $cfg->HTML_SELECTED : '',
					'HTTPS_YES' => (preg_match("/https:\/\//", $data['url_forward'])) ? $cfg->HTML_SELECTED : '',
					'FTP_YES' => (preg_match("/ftp:\/\//", $data['url_forward'])) ? $cfg->HTML_SELECTED : ''));
		}
		$tpl->assign(
				array(
					'CHECK_EN' => $check_en,
					'CHECK_DIS' => $check_dis));
	}

	// Fill in the fields
	$tpl->assign(
		array(
			'ALIAS_NAME' => tohtml(decode_idna($data['alias_name'])),
			'DOMAIN_IP' => $ip_data,
			'FORWARD' => tohtml($url_forward),
			'ID' => $edit_id));
} // End of gen_editalias_page()

/**
 * Check input data
 *
 * @param iMSCP_pTemplate $tpl
 * @param $alias_id
 * @return bool
 */
function check_fwd_data($tpl, $alias_id) {

	$cfg = iMSCP_Registry::get('config');

	$forward_url = strtolower(clean_input($_POST['forward']));

	if (isset($_POST['status']) && $_POST['status'] == 1) {
		$forward_prefix = clean_input($_POST['forward_prefix']);
		if (substr_count($forward_url, '.') <= 2) {
			$ret = validates_dname($forward_url);
		} else {
			$ret = validates_dname($forward_url, true);
		}
		if (!$ret) {
			set_page_message(tr("Wrong domain part in forward URL."), 'error');
		} else {
			$forward_url = encode_idna($forward_prefix.$forward_url);
		}

		$check_en = $cfg->HTML_CHECKED;
		$check_dis = '';
		$tpl->assign(
			array(
				'FORWARD' => tohtml($forward_url),
				'HTTP_YES' => ($forward_prefix === 'http://') ? $cfg->HTML_SELECTED : '',
				'HTTPS_YES' => ($forward_prefix === 'https://') ? $cfg->HTML_SELECTED : '',
				'FTP_YES' => ($forward_prefix === 'ftp://') ? $cfg->HTML_SELECTED : '',
				'CHECK_EN' => $check_en,
				'CHECK_DIS' => $check_dis,
				'DISABLE_FORWARD' => '',
				'READONLY_FORWARD' => ''));
	} else {
		$check_en = $cfg->HTML_CHECKED;
		$check_dis = '';
		$forward_url = 'no';
		$tpl->assign(
			array(
				'READONLY_FORWARD' => $cfg->HTML_READONLY,
				'DISABLE_FORWARD' => $cfg->HTML_DISABLED,
				'CHECK_EN' => $check_en,
				'CHECK_DIS' => $check_dis,));
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		$query = "
			UPDATE
				`domain_aliasses`
			SET
				`url_forward` = ?,
				`alias_status` = ?
			WHERE
				`alias_id` = ?
		";
		exec_query($query, array($forward_url, $cfg->ITEM_CHANGE_STATUS, $alias_id));

		$query = "
			UPDATE
				`subdomain_alias`
			SET
				`subdomain_alias_status` = ?
			WHERE
				`alias_id` = ?
		";
		exec_query($query, array($cfg->ITEM_CHANGE_STATUS, $alias_id));

		send_request();

		unset($_SESSION['edit_ID']);
		return true;
	} else {
		return false;
	}
} // End of check_user_data()
