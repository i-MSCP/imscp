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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2011 by i-MSCP | http://i-mscp.net
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 */

/**
 * @param $user_id
 * @return
 */
function check_subdomain_permissions($user_id) {
	$props = get_domain_default_props($user_id, true);

	$dmn_id = $props['domain_id'];
	$dmn_name = $props['domain_name'];
	$dmn_subd_limit = $props['domain_subd_limit'];

	$sub_cnt = get_domain_running_sub_cnt($dmn_id);

	if ($dmn_subd_limit != 0 && $sub_cnt >= $dmn_subd_limit) {
		set_page_message(tr('Subdomains limit reached.'), 'error');
		redirectTo('domains_manage.php');
	}

	if (@$_POST['dmn_type'] == 'als') {
		$query_alias = "
			SELECT
				`alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`alias_id` = ?
		";
		$rs = exec_query($query_alias, $_POST['als_id']);
		return $rs->fields['alias_name'];
	}
	return $dmn_name; // Will be used in subdmn_exists()
}

/**
 * @param $tpl
 * @param $user_id
 * @return void
 */
function gen_user_add_subdomain_data($tpl, $user_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$subdomain_name = $subdomain_mnt_pt = $forward = $forward_prefix = '';

	$query = '
		SELECT
			`domain_name`, `domain_id`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	';

	$rs = exec_query($query, $user_id);
	$domainname = decode_idna($rs->fields['domain_name']);
	$tpl->assign(
		array(
			'DOMAIN_NAME' => '.' . tohtml($domainname),
			'SUB_DMN_CHECKED' => $cfg->HTML_CHECKED,
			'SUB_ALS_CHECKED' => ''));
	gen_dmn_als_list($tpl, $rs->fields['domain_id'], 'no');

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_subd') {
		if($_POST['status'] == 1) {
			$forward_prefix = clean_input($_POST['forward_prefix']);
			$check_en = 'checked="checked"';
			$check_dis = '';
			$forward = strtolower(clean_input($_POST['forward']));
			$tpl->assign(
				array(
					'READONLY_FORWARD' => '',
					'DISABLE_FORWARD' => ''));
		} else {
			$check_en = '';
			$check_dis = 'checked="checked"';
			$forward = '';
			$tpl->assign(
				array(
					'READONLY_FORWARD' => ' readonly',
					'DISABLE_FORWARD' => ' disabled="disabled"'));
		}
		$tpl->assign(
			array(
				'HTTP_YES' => ($forward_prefix === 'http://') ? 'selected="selected"' : '',
				'HTTPS_YES' => ($forward_prefix === 'https://') ? 'selected="selected"' : '',
				'FTP_YES' => ($forward_prefix === 'ftp://') ? 'selected="selected"' : ''));

		$subdomain_name = clean_input($_POST['subdomain_name']);
		$subdomain_mnt_pt = array_encode_idna(clean_input($_POST['subdomain_mnt_pt']), true);
	} else {
		$check_en = '';
		$check_dis = 'checked="checked"';
		$forward = '';
		$tpl->assign(
			array(
				'READONLY_FORWARD' => ' readonly',
				'DISABLE_FORWARD' => ' disabled="disabled"'));
	}
	$tpl->assign(
		array(
			'SUBDOMAIN_NAME' => $subdomain_name,
			'SUBDOMAIN_MOUNT_POINT' => $subdomain_mnt_pt,
			'FORWARD'	=> $forward,
			'CHECK_EN'	=> $check_en,
			'CHECK_DIS' => $check_dis));
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $post_check
 * @return void
 */
function gen_dmn_als_list($tpl, $dmn_id, $post_check) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$ok_status = $cfg->ITEM_OK_STATUS;

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
	$rs = exec_query($query, array($dmn_id, $ok_status));

	if ($rs->recordCount() == 0) {
		$tpl->assign(
			array(
				'ALS_ID' => '0',
				'ALS_SELECTED' => $cfg->HTML_SELECTED,
				'ALS_NAME' => tr('Empty list')));

		$tpl->parse('ALS_LIST', 'als_list');
		$tpl->assign('TO_ALIAS_DOMAIN', '');
		$_SESSION['alias_count'] = "no";
	} else {
		$first_passed = false;
		while (!$rs->EOF) {
			if ($post_check === 'yes') {
				$als_id = (!isset($_POST['als_id'])) ? '' : $_POST['als_id'];
				$als_selected = ($als_id == $rs->fields['alias_id']) ? $cfg->HTML_SELECTED : '';
			} else {
				$als_selected = (!$first_passed) ? $cfg->HTML_SELECTED : '';
			}

			$alias_name = decode_idna($rs->fields['alias_name']);
			$tpl->assign(
				array(
					'ALS_ID' => $rs->fields['alias_id'],
					'ALS_SELECTED' => $als_selected,
					'ALS_NAME' => tohtml($alias_name)));

			$tpl->parse('ALS_LIST', '.als_list');
			$rs->moveNext();

			if (!$first_passed) {
				$first_passed = true;
			}
		}
	}
}

/**
 * @param $user_id
 * @param $domain_id
 * @param $sub_name
 * @return bool
 */
function subdmn_exists($user_id, $domain_id, $sub_name) {
	global $dmn_name;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($_POST['dmn_type'] == 'als') {
		$query_subdomain = "
			SELECT
				COUNT(`subdomain_alias_id`) AS cnt
			FROM
				`subdomain_alias`
			WHERE
				`alias_id` = ?
			AND
				`subdomain_alias_name` = ?
		";

		$query_domain = "
			SELECT
				COUNT(`alias_id`) AS cnt
			FROM
				`domain_aliasses`
			WHERE
				`alias_name` = ?
		";
	} else {
		$query_subdomain = "
			SELECT
				COUNT(`subdomain_id`) AS cnt
			FROM
				`subdomain`
			WHERE
				`domain_id` = ?
			AND
				`subdomain_name` = ?
		";

		$query_domain = "
			SELECT
				COUNT(`domain_id`) AS cnt
			FROM
				`domain`
			WHERE
				`domain_name` = ?
		";
	}
	$domain_name = $sub_name . "." . $dmn_name;

	$rs_subdomain = exec_query($query_subdomain, array($domain_id, $sub_name));
	$rs_domain = exec_query($query_domain, array($domain_name));

	$std_subs = array(
		'www', 'mail', 'webmail', 'pop', 'pop3', 'imap', 'smtp', 'pma', 'relay',
		'ftp', 'ns1', 'ns2', 'localhost');

	if ($rs_subdomain->fields['cnt'] == 0
		&& $rs_domain->fields['cnt'] == 0
		&& !in_array($sub_name, $std_subs)
		&& $cfg->BASE_SERVER_VHOST != $domain_name
	) {
		return false;
	}

	return true;
}

/**
 * @param $user_id
 * @param $domain_id
 * @param $sub_name
 * @param $sub_mnt_pt
 * @return bool
 */
function subdmn_mnt_pt_exists($user_id, $domain_id, $sub_name, $sub_mnt_pt) {

	if ($_POST['dmn_type'] == 'als') {
		$query = "
			SELECT
				COUNT(`subdomain_alias_id`) AS cnt
			FROM
				`subdomain_alias`
			WHERE
				`alias_id` = ?
			AND
				`subdomain_alias_mount` = ?
		";

		if (isset($query2))
			unset($query2);

		if (isset($rs2))
			unset($rs2);
	} else {
		$query = "
			SELECT
				COUNT(`subdomain_id`) AS cnt
			FROM
				`subdomain`
			WHERE
				`domain_id` = ?
			AND
				`subdomain_mount` = ?
		";

		$query2 = "
			SELECT
				COUNT(`alias_id`) AS cnt
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = ?
			AND
				`alias_mount` = ?
		";
	}

	$rs = exec_query($query, array($domain_id, $sub_mnt_pt));
	if (isset($query2))
		$rs2 = exec_query($query2, array($domain_id, $sub_mnt_pt));

	if ($rs->fields['cnt'] > 0 || (isset($rs2) && $rs2->fields['cnt'] > 0)) {
		return true;
	}
	return false;
}

/**
 * @param $user_id
 * @param $domain_id
 * @param $sub_name
 * @param $sub_mnt_pt
 * @param $forward
 * @return void
 */
function subdomain_schedule($user_id, $domain_id, $sub_name, $sub_mnt_pt, $forward) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$status_add = $cfg->ITEM_ADD_STATUS;

	if ($_POST['dmn_type'] == 'als') {
		$query = "
			INSERT INTO `subdomain_alias` (
			    `alias_id`, `subdomain_alias_name`, `subdomain_alias_mount`,
			    `subdomain_alias_url_forward`, `subdomain_alias_status`
			) VALUES (
			    ?, ?, ?, ?, ?
			)
		";
	} else {
		$query = "
			INSERT INTO `subdomain` (
			    `domain_id`, `subdomain_name`, `subdomain_mount`,
			    `subdomain_url_forward`, `subdomain_status`
			) VALUES (
			    ?, ?, ?, ?, ?
			)
		";
	}

	exec_query($query, array($domain_id, $sub_name, $sub_mnt_pt, $forward, $status_add));

	update_reseller_c_props(get_reseller_id($domain_id));

	write_log($_SESSION['user_logged'] . ": adds new subdomain: " . $sub_name, E_USER_NOTICE);
	send_request();
}

/***
 * @param $tpl
 * @param $user_id
 * @param $dmn_name
 * @return
 */
function check_subdomain_data($tpl, $user_id, $dmn_name) {

	global $validation_err_msg;
	$dmn_id = $domain_id = get_user_domain_id($user_id);

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_subd') {
		if (empty($_POST['subdomain_name'])) {
			 set_page_message(tr('Please specify subdomain name.'), 'error');
			return;
		}

		$sub_name = strtolower($_POST['subdomain_name']);

		if ($_POST['status'] == 1) {
			$forward = strtolower(clean_input($_POST['forward']));
			$forward_prefix = clean_input($_POST['forward_prefix']);
		} else {
			$forward = 'no';
			$forward_prefix = '';
		}

		// Should be perfomed after domain names syntax validation now
		//$sub_name = encode_idna($sub_name);

		if (isset($_POST['subdomain_mnt_pt']) && $_POST['subdomain_mnt_pt'] !== '') {
			$sub_mnt_pt = array_encode_idna(strtolower($_POST['subdomain_mnt_pt']), true);
		} else {
			$sub_mnt_pt = "/";
		}

		if ($_POST['dmn_type'] === 'als') {
			if (!isset($_POST['als_id'])) {
				set_page_message(tr('No valid alias domain selected.'), 'error');
				return;
			}

			$query_alias = "
				SELECT
					`alias_mount`
				FROM
					`domain_aliasses`
				WHERE
					`alias_id` = ?
			";
			$rs = exec_query($query_alias, $_POST['als_id']);

			$als_mnt = $rs->fields['alias_mount'];

			if ($sub_mnt_pt[0] != '/')
				$sub_mnt_pt = '/'.$sub_mnt_pt;

			$sub_mnt_pt = $als_mnt.$sub_mnt_pt;
			$sub_mnt_pt = str_replace('//', '/', $sub_mnt_pt);
			$domain_id = $_POST['als_id'];
		}

		// First check if input string is a valid domain names
		if (!validates_subdname($sub_name, decode_idna($dmn_name))) {
			set_page_message($validation_err_msg, 'error');
			return;
		}

		// Should be perfomed after domain names syntax validation now
		$sub_name = encode_idna($sub_name);

		if (subdmn_exists($user_id, $domain_id, $sub_name)) {
			set_page_message(tr('Subdomain already exists or is not allowed.'), 'error');
		} elseif (mount_point_exists($dmn_id, array_encode_idna($sub_mnt_pt, true))) {
			set_page_message(tr('Mount point already in use'), 'error');
		} elseif (!validates_mpoint($sub_mnt_pt)) {
			set_page_message(tr('Incorrect mount point syntax.'), 'error');
		} elseif ($_POST['status'] == 1) {
			$surl = @parse_url($forward_prefix.decode_idna($forward));
			if ($surl === false) {
				set_page_message(tr('Wrong domain part in forward URL.'), 'error');
			} else {
				$domain = $surl['host'];
				if (substr_count($domain, '.') <= 2) {
					$ret = validates_dname($domain);
				} else {
					$ret = validates_dname($domain, true);
				}
				$domain = encode_idna($surl['host']);
				if (!$ret) {
					set_page_message(tr('Wrong domain part in forward URL.'), 'error');
				} else {
					$domain = encode_idna($surl['host']);
					$forward = $surl['scheme'].'://';
					if (isset($surl['user'])) {
						$forward .= $surl['user'] . (isset($surl['pass']) ? ':' . $surl['pass'] : '') .'@';
					}
					$forward .= $domain;
					if (isset($surl['port'])) {
						$forward .= ':'.$surl['port'];
					}
					if (isset($surl['path'])) {
						$forward .= $surl['path'];
					} else {
						$forward .= '/';
					}
					if (isset($surl['query'])) {
						$forward .= '?'.$surl['query'];
					}
					if (isset($surl['fragment'])) {
						$forward .= '#'.$surl['fragment'];
					}
				}
			}
		} else {
			// now let's fix the mountpoint
			//$mount_point = array_encode_idna($mount_point, true);
			$sub_mnt_pt = array_encode_idna($sub_mnt_pt, true);
		}

		if(Zend_Session::namespaceIsset('pageMessages')) {
			return;
		}

		subdomain_schedule($user_id, $domain_id, $sub_name, $sub_mnt_pt, $forward);
		set_page_message(tr('Subdomain scheduled for addition.'), 'success');
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

$domainProperties =  get_domain_default_props($_SESSION['user_id'], true);

// Avoid useless work during Ajax request
if(!is_xhr()) {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic('layout', $cfg->CLIENT_TEMPLATE_PATH . '/../shared/layouts/ui.tpl');
	$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/subdomain_add.tpl');
	$tpl->define_dynamic('page_message', 'page');
	$tpl->define_dynamic('subdomain_add_js', 'page');
	$tpl->define_dynamic('subdomain_add_form', 'page');
	$tpl->define_dynamic('als_list', 'subdomain_add_form');

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Client/Add Subdomain'),
			'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo()));

	generateNavigation($tpl);

	$tpl->assign(
		array(
			 'TR_TITLE_ADD_SUBDOMAIN' => tr('Add subdomain'),
			 'TR_PAGE_TITLE' => tr('Add domain alias'),
			 'TR_SUBDOMAIN_DATA' => tr('Subdomain data'),
			 'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
			 'TR_MOUNT_POINT' => tr('Mount point'),
			 'TR_FORWARD' => tr('Redirect to URL'),
			 'TR_ADD' => tr('Add'),
			 'TR_DMN_HELP' => tr("You do not need 'www.' i-MSCP will add it automatically."),
			 'TR_ENABLE_FWD' => tr('Redirect'),
			 'TR_ENABLE' => tr('Enable'),
			 'TR_DISABLE' => tr('Disable'),
			 'TR_PREFIX_HTTP' => 'http://',
			 'TR_PREFIX_HTTPS' => 'https://',
			 'TR_PREFIX_FTP' => 'ftp://'));
}

/**
 * Dispatches the request
 */
$currentNumberSubdomains = get_domain_running_sub_cnt($domainProperties['domain_id']);
if ($currentNumberSubdomains != 0 && $currentNumberSubdomains == $domainProperties['domain_subd_limit']) {
	if(is_xhr()) {
		set_page_message(tr('Wrong request'));
		redirectTo('domains_manage.php');
	}

	set_page_message(tr('We are sorry but you reached the maximum number of subdomains allowed by your subscription. Contact your reseller for more information.'), 'warning');
	$tpl->assign(array(
					  'SUBDOMAIN_ADD_JS' => '',
					  'SUBDOMAIN_ADD_FORM' => ''));
} elseif (isset($_POST['uaction'])) {
		if ($_POST['uaction'] == 'toASCII') { // Ajax request
			header('Content-Type: text/plain; charset=utf-8');
			header('Cache-Control: no-cache, private');
			// backward compatibility for HTTP/1.0
			header('Pragma: no-cache');
			header("HTTP/1.0 200 Ok");

			// Todo check return value here before echo...
			echo "/" . encode_idna(strtolower($_POST['subdomain']));
			exit;
		} elseif ($_POST['uaction'] == 'add_subd') {
			$dmn_name = check_subdomain_permissions($_SESSION['user_id']);
			gen_user_add_subdomain_data($tpl, $_SESSION['user_id']);
			check_subdomain_data($tpl, $_SESSION['user_id'], $dmn_name);
		} else {
			throw new iMSCP_Exception(tr("Error: unknown action! {$_POST['uaction']}"));
		}
} else { // Default view
	gen_user_add_subdomain_data($tpl, $_SESSION['user_id']);
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
