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
 *  Functions
 */

/**
 * Must be documented
 */
function check_client_domainalias_counts($user_id) {

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

	$als_cnt = get_domain_running_als_cnt($dmn_id);

	if ($dmn_als_limit != 0 && $als_cnt >= $dmn_als_limit) {
		set_page_message(tr('Domain alias limit reached!'), 'error');
		redirectTo('domains_manage.php');
	}
}

/**
 * @return void
 */
function init_empty_data() {
	global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;

	$cr_user_id = $alias_name = $domain_ip = $forward = $mount_point = '';

} // End of init_empty_data()


/**
 * Show data fields
 */
function gen_al_page($tpl, $reseller_id) {

	global $alias_name, $forward, $forward_prefix, $mount_point;

	/** @var $cfg iMSCP_Config_Handler_FileCP_ */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['status']) && $_POST['status'] == 1) {

		$forward_prefix = clean_input($_POST['forward_prefix']);

		$check_en = $cfg->HTML_CHECKED;
		$check_dis = '';
		$forward = encode_idna(strtolower(clean_input($_POST['forward'])));

		$tpl->assign(
			array(
				'READONLY_FORWARD'	=> '',
				'DISABLE_FORWARD'	=> '',
				'HTTP_YES'			=> ($forward_prefix === 'http://') ? $cfg->HTML_SELECTED : '',
				'HTTPS_YES'			=> ($forward_prefix === 'https://') ? $cfg->HTML_SELECTED : '',
				'FTP_YES'			=> ($forward_prefix === 'ftp://') ? $cfg->HTML_SELECTED : ''
			)
		);
	} else {
		$check_en = '';
		$check_dis = $cfg->HTML_CHECKED;
		$forward = '';

		$tpl->assign(
			array(
				'READONLY_FORWARD'	=> $cfg->HTML_READONLY,
				'DISABLE_FORWARD'	=> $cfg->HTML_DISABLED,
				'HTTP_YES'			=> '',
				'HTTPS_YES'			=> '',
				'FTP_YES'			=> ''
				)
			);
	}

	$tpl->assign(
		array(
			'DOMAIN'	=> tohtml(decode_idna($alias_name)),
			'MP'		=> tohtml($mount_point),
			'FORWARD'	=> tohtml($forward),
			'CHECK_EN'	=> $check_en,
			'CHECK_DIS' => $check_dis,
		)
	);

} // End of gen_al_page()

/**
 * @return
 */
function add_domain_alias() {

	global $cr_user_id, $alias_name, $domain_ip, $forward, $forward_prefix,
		$mount_point, $validation_err_msg;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$cr_user_id = $domain_id = get_user_domain_id($_SESSION['user_id']);
	$alias_name	= strtolower($_POST['ndomain_name']);
	$mount_point = array_encode_idna(strtolower($_POST['ndomain_mpoint']), true);

	if ($_POST['status'] == 1) {
		$forward = encode_idna(strtolower(clean_input($_POST['forward'])));
		$forward_prefix = clean_input($_POST['forward_prefix']);
	} else {
		$forward = 'no';
		$forward_prefix = '';
	}

	$query = "
		SELECT
			`domain_ip_id`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$rs = exec_query($query, $cr_user_id);
	$domain_ip = $rs->fields['domain_ip_id'];

	// First check if input string is a valid domain names
	if (!validates_dname($alias_name)) {
		set_page_message($validation_err_msg, 'error');
		return;
	}

	// Should be perfomed after domain names syntax validation now
	$alias_name = encode_idna($alias_name);

	if (imscp_domain_exists($alias_name, 0)) {
	 set_page_message(tr('Domain with that name already exists on the system.'), 'error');
	} else if (!validates_mpoint($mount_point) && $mount_point != '/') {
		set_page_message(tr('Incorrect mount point syntax.'), 'error');
	} else if ($alias_name == $cfg->BASE_SERVER_VHOST) {
		set_page_message(tr('Master domain cannot be used.'), 'error');
	} else if ($_POST['status'] == 1) {
		$aurl = @parse_url($forward_prefix.decode_idna($forward));
		if ($aurl === false) {
			set_page_message(tr('Wrong address in forward URL.'), 'error');
		} else {
			$domain = $aurl['host'];
			if (substr_count($domain, '.') <= 2) {
				$ret = validates_dname($domain);
			} else {
				$ret = validates_dname($domain, true);
			}
			$domain = encode_idna($aurl['host']);
			if (!$ret) {
				set_page_message(tr('Wrong domain part in forward URL.'), 'error');
			} else {
				$domain = encode_idna($aurl['host']);
				$forward = $aurl['scheme'].'://';
				if (isset($aurl['user'])) {
					$forward .= $aurl['user'] . (isset($aurl['pass']) ? ':' . $aurl['pass'] : '') .'@';
				}
				$forward .= $domain;
				if (isset($aurl['port'])) {
					$forward .= ':'.$aurl['port'];
				}
				if (isset($aurl['path'])) {
					$forward .= $aurl['path'];
				} else {
					$forward .= '/';
				}
				if (isset($aurl['query'])) {
					$forward .= '?'.$aurl['query'];
				}
				if (isset($aurl['fragment'])) {
					$forward .= '#'.$aurl['fragment'];
				}
			}
		}
	} else {
		$query = "
			SELECT 
				`domain_id`
			FROM
				`domain_aliasses`
			WHERE
				`alias_name` = ?
		";
		$res = exec_query($query, $alias_name);
		$query = "
			SELECT
				`domain_id`
			FROM
				`domain`
			WHERE
				`domain_name` = ?
		";
		$res2 = exec_query($query, $alias_name);
		if ($res->rowCount() > 0 || $res2->rowCount() > 0) {
			// we already have domain with this name
			set_page_message(tr('Domain with this name already exist.'), 'error');
		}

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
		$subdomres = exec_query($query, array($cr_user_id, $mount_point));
		$subdomdata = $subdomres->fetchRow();
		$query = "
			SELECT
				COUNT(`subdomain_alias_id`) AS alscnt
			FROM
				`subdomain_alias`
			WHERE
				`alias_id`
			IN
				(SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
			AND
				`subdomain_alias_mount` = ?
		";
		$alssubdomres = exec_query($query, array($cr_user_id, $mount_point));
		$alssubdomdata = $alssubdomres->fetchRow();
		if ($subdomdata['cnt'] > 0 || $alssubdomdata['alscnt'] > 0) {
			set_page_message(tr('There is a subdomain with the same mount point.'), 'error');
		}
	}

	if (Zend_Session::namespaceIsset('pageMessages')) {
		return;
	}

	// Begin add new alias domain

	$status = $cfg->ITEM_ORDERED_STATUS;

	$query = "
		INSERT INTO
			`domain_aliasses`
				(`domain_id`, `alias_name`, `alias_mount`, `alias_status`, `alias_ip_id`, `url_forward`)
		VALUES 
				(?, ?, ?, ?, ?, ?)
	";
	exec_query($query, array($cr_user_id, $alias_name, $mount_point, $status, $domain_ip, $forward));

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');
	$als_id = $db->insertId();

	update_reseller_c_props(get_reseller_id($cr_user_id));

	$admin_login = $_SESSION['user_logged'];

	if ($status == $cfg->ITEM_ORDERED_STATUS) {
		// notify the reseller:
		send_alias_order_email($alias_name);

		write_log("$admin_login: add domain alias for activation: $alias_name.", E_USER_NOTICE);
		set_page_message(tr('Alias awaiting for activation.'), 'success');
	} else {
		send_request();
		write_log("$admin_login: domain alias scheduled for addition: $alias_name.", E_USER_NOTICE);
		set_page_message(tr('Alias scheduled for addition.'), 'success');
	}

	redirectTo('domains_manage.php');
} // End of add_domain_alias();


/**
 * Main program
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('domain_aliases')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Avoid useless work during Ajax request
if(!is_xhr()) {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic('page', $cfg->CLIENT_TEMPLATE_PATH . '/alias_add.tpl');
	$tpl->define_dynamic('page_message', 'page');
	$tpl->define_dynamic('domain_alias_add_js', 'page');
	$tpl->define_dynamic('domain_alias_add_form', 'page');
	$tpl->define_dynamic('user_entry', 'domain_alias_add_form');
	$tpl->define_dynamic('ip_entry', 'page');
	
	$tpl->assign(
		array(
			'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo(),
		)
	);

	gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_manage_domains.tpl');
	gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_manage_domains.tpl');

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP Client : Add Alias'),
			'TR_TITLE_ADD_DOMAIN_ALIAS' => tr('Add domain alias'),
			'TR_DOMAIN_ALIAS_DATA' => tr('Domain alias data'),
			'TR_DOMAIN_ALIAS_NAME' => tr('Domain alias name'),
			'TR_DOMAIN_ACCOUNT' => tr('User account'),
			'TR_MOUNT_POINT' => tr('Mount point'),
			'TR_FORWARD' => tr('Redirect to URL'),
			'TR_ADD' => tr('Add'),
			'TR_DMN_HELP' => tr("You do not need 'www.' i-MSCP will add it automatically."),
			'TR_ENABLE_FWD' => tr("Redirect"),
			'TR_ENABLE' => tr("Enable"),
			'TR_DISABLE' => tr("Disable"),
			'TR_PREFIX_HTTP' => 'http://',
			'TR_PREFIX_HTTPS' => 'https://',
			'TR_PREFIX_FTP' => 'ftp://'
		)
	);
}

$domainProperties = get_domain_default_props($_SESSION['user_id'], true);
$currentNumberDomainAliases = get_domain_running_als_cnt($domainProperties['domain_id']);

/**
 * Dispatches the request
 */
if ($currentNumberDomainAliases != 0
	&& $currentNumberDomainAliases == $domainProperties['domain_alias_limit']
) {
	if(is_xhr()) {
		set_page_message(tr('Wrong request.'));
		redirectTo('domains_manage.php');
	}

	set_page_message(tr('We are sorry but you reached the maximum number of domain aliases allowed by your subscription. Contact your reseller for more information.'), 'warning');
	$tpl->assign(array(
					  'DOMAIN_ALIAS_ADD_JS' => '',
					  'DOMAIN_ALIAS_ADD_FORM' => ''));
}elseif(isset($_POST['uaction'])) {
	if($_POST['uaction'] == 'toASCII') { // Ajax request
		header('Content-Type: text/plain; charset=utf-8');
		header('Cache-Control: no-cache, private');
		// backward compatibility for HTTP/1.0
		header('Pragma: no-cache');
		header("HTTP/1.0 200 Ok");
		
		// Todo check return value here before echo...
		echo "/".encode_idna(strtolower($_POST['domain']));
		exit;
	} elseif($_POST['uaction'] == 'add_alias') {
		add_domain_alias();
	} else {
		set_page_message(tr('Wrong request.'), 'error');
		redirectTo('domains_manage.php');
	}
} else { // Default view
	init_empty_data();
}

gen_al_page($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
