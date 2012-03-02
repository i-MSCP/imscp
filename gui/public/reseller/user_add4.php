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

/************************************************************************************
 * Script functions
 */

/**
 * Generate default page.
 *
 * @return void
 */
function init_empty_data()
{
    global $cr_user_id, $alias_name, $domain_ip, $forward, $mount_point;
    $cr_user_id = $alias_name = $domain_ip = $forward = $mount_point = '';
}

/**
 * Show data fields.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function gen_al_page($tpl)
{
    global $alias_name, $forward, $forward_prefix;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $dmn_id = $_SESSION['dmn_id'];

    $query = "
		SELECT
			`alias_id`, `alias_name`, `alias_status`, `url_forward`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = ?
	";

    $stmt = exec_query($query, $dmn_id);

    if ($stmt->recordCount() === 0) {
        $tpl->assign('ALIAS_LIST', '');
    } else {
        while (!$stmt->EOF) {
            $alias_name = decode_idna($stmt->fields['alias_name']);
            $alias_status = translate_dmn_status($stmt->fields['alias_status']);
            $show_als_fwd = ($stmt->fields['url_forward'] == 'no')? '-' : $stmt->fields['url_forward'];

            $tpl->assign(
				array(
					'DOMAIN_ALIAS' => tohtml($alias_name),
					'STATUS' => $alias_status,
					'FORWARD_URL' => $show_als_fwd));

            $tpl->parse('ALIAS_ENTRY', '.alias_entry');
            $stmt->moveNext();
        }
    }

    $check_en = '';
    $check_dis = '';

    if (isset($_POST['status']) && $_POST['status'] == 1) {
        $forward_prefix = clean_input($_POST['forward_prefix']);

        if ($_POST['status'] == 1) {
            $check_en = $cfg->HTML_CHECKED;
            $forward = encode_idna(strtolower(clean_input($_POST['forward'])));
            $tpl->assign(
				array(
					'READONLY_FORWARD' => '',
					'DISABLE_FORWARD' => ''));
        } else {
            $check_dis = $cfg->HTML_CHECKED;
            $forward = '';
            $tpl->assign(
				array(
					'READONLY_FORWARD' => $cfg->HTML_READONLY,
					'DISABLE_FORWARD' => $cfg->HTML_DISABLED));
        }

		$tpl->assign(
			array(
				'HTTP_YES' => ($forward_prefix === 'http://') ? $cfg->HTML_SELECTED : '',
				'HTTPS_YES' => ($forward_prefix === 'https://') ? $cfg->HTML_SELECTED : '',
				'FTP_YES' => ($forward_prefix === 'ftp://') ? $cfg->HTML_SELECTED : ''));
    } else {
        $check_dis = $cfg->HTML_CHECKED;
        $forward = '';
        $tpl->assign(
			array(
				'READONLY_FORWARD' => $cfg->HTML_READONLY,
				'DISABLE_FORWARD' => $cfg->HTML_DISABLED,
				'HTTP_YES' => '',
				'HTTPS_YES' => '',
				'FTP_YES' => ''));
    }

	$tpl->assign(
		array(
			'DOMAIN' => !empty($_POST) ? strtolower(clean_input($_POST['ndomain_name'], true)) : '',
			'MP' => !empty($_POST) ? strtolower(clean_input($_POST['ndomain_mpoint'], true)) : '',
			'FORWARD' => tohtml(encode_idna($forward)),
			'CHECK_EN' => $check_en,
			'CHECK_DIS' => $check_dis));
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
 * Adds domain alias.
 *
 * @return void
 */
function add_domain_alias()
{
    global $cr_user_id, $alias_name, $domain_ip, $forward, $forward_prefix, $mount_point, $validation_err_msg;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $cr_user_id = (int) $_SESSION['dmn_id'];

    $alias_name = strtolower(clean_input($_POST['ndomain_name']));
    $domain_ip = $_SESSION['dmn_ip'];
    $mount_point = array_encode_idna(strtolower($_POST['ndomain_mpoint']), true);

    if ($_POST['status'] == 1) {
        $forward = encode_idna(strtolower(clean_input($_POST['forward'])));
        $forward_prefix = clean_input($_POST['forward_prefix']);
    } else {
        $forward = 'no';
        $forward_prefix = '';
    }

    if (!validates_dname($alias_name)) {
        set_page_message($validation_err_msg, 'error');
        return;
    }

    // Should be done after domain names syntax validation
    $alias_name = encode_idna($alias_name);

    if (imscp_domain_exists($alias_name, $_SESSION['user_id'])) {
        set_page_message(tr('Domain already registered on the system.'), 'error');
    } elseif (!validates_mpoint($mount_point)) {
        set_page_message(tr('Incorrect mount point syntax'), 'error');
	} elseif(!_reseller_isAllowedMountPoint($mount_point, $cr_user_id)) {
		set_page_message(tr('This mount point is not allowed.'), 'error');
    } elseif ($_POST['status'] == 1) {
        if(($urlElements = @parse_url($forward_prefix . decode_idna($forward))) === false) {
            set_page_message(tr('Wrong syntax in forward URL.'), 'error');
        } else {
            $domain = $urlElements['host'];

            if (substr_count($domain, '.') <= 2) {
                $retval = validates_dname($domain);
            } else {
                $retval = validates_dname($domain, true);
            }

            if (!$retval) {
                 set_page_message(tr('Wrong hostname in forward URL.'), 'error');
            } else {
                $domain = encode_idna($urlElements['host']);
                $forward = $urlElements['scheme'] . '://';
                if (isset($aurl['user'])) {
                    $forward .= $urlElements['user'] . (isset($urlElements['pass'])
                        ? ':' . $urlElements['pass'] : '') . '@';
                }

                $forward .= $domain;

                if (isset($aurl['port'])) {
                    $forward .= ':' . $urlElements['port'];
                }

                if (isset($aurl['path'])) {
                    $forward .= $urlElements['path'];
                } else {
                    $forward .= '/';
                }

                if (isset($aurl['query'])) {
                    $forward .= '?' . $urlElements['query'];
                }

                if (isset($aurl['fragment'])) {
                    $forward .= '#' . $urlElements['fragment'];
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
        $stmt1 = exec_query($query, $alias_name);

        $query = "
			SELECT
				`domain_id`
			FROM
				`domain`
			WHERE
				`domain_name` = ?
		";
        $stmt2 = exec_query($query, $alias_name);

        if ($stmt1->rowCount() > 0 || $stmt2->rowCount() > 0) {
             set_page_message(tr('Domain already registered on the system.'), 'error');
        }

        if (mount_point_exists($cr_user_id, $mount_point)) {
             set_page_message(tr('Mount point already in use.'), 'error');
        }
    }

    if(Zend_Session::namespaceIsset('pageMessages')) {
        return;
    }

    $query = "
		INSERT INTO
			`domain_aliasses` (
			    `domain_id`, `alias_name`, `alias_mount`, `alias_status`,
			    `alias_ip_id`, `url_forward`
            ) VALUES (
                ?, ?, ?, ?, ?, ?
            )
	";
    exec_query($query, array($cr_user_id, $alias_name, $mount_point, $cfg->ITEM_ADD_STATUS, $domain_ip, $forward));

    update_reseller_c_props(get_reseller_id($cr_user_id));
    send_request();

    $admin_login = $_SESSION['user_logged'];
    write_log("$admin_login: add domain alias: $alias_name", E_USER_NOTICE);

    $_SESSION['alias_scheduled_for_creation'] = 1;

    redirectTo('user_add4.php');
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

if (!is_xhr()) {
    $tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'reseller/user_add4.tpl',
			'page_message' => 'layout',
			'alias_list' => 'page',
			'alias_entry' => 'alias_list'));

    $tpl->assign(
		array(
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_PAGE_TITLE' => tr('i-MSCP - User/Add domain alias'),
			'TR_MANAGE_DOMAIN_ALIAS' => tr('Manage domain alias'),
			'TR_ADD_ALIAS' => tr('Add domain alias'),
			'TR_DOMAIN_NAME' => tr('Domain name'),
			'TR_DOMAIN_ACCOUNT' => tr('User account'),
			'TR_MOUNT_POINT' => tr('Directory mount point'),
			'TR_DMN_HELP' => tr("You do not need 'www.' i-MSCP will add it on its own."),
			'TR_DOMAIN_IP' => tr('Domain IP'),
			'TR_FORWARD' => tr('Forward to URL'),
			'TR_ADD' => tr('Add alias'),
			'TR_DOMAIN_ALIAS' => tr('Domain alias'),
			'TR_STATUS' => tr('Status'),
			'TR_ADD_USER' => tr('Add user'),
			'TR_GO_USERS' => tr('Done'),
			'TR_ENABLE_FWD' => tr("Enable Forward"),
			'TR_ENABLE' => tr("Enable"),
			'TR_DISABLE' => tr("Disable"),
			'TR_PREFIX_HTTP' => 'http://',
			'TR_PREFIX_HTTPS' => 'https://',
			'TR_PREFIX_FTP' => 'ftp://'));

    generateNavigation($tpl);

    if (isset($_SESSION['dmn_id']) && $_SESSION['dmn_id'] !== '') {
        $domain_id = $_SESSION['dmn_id'];
        $reseller_id = $_SESSION['user_id'];

        $query = "
			SELECT
				`domain_id`, `domain_status`
			FROM
				`domain`
			WHERE
				`domain_id` = ?
			AND
				`domain_created_id` = ?
		";

        $stmt = exec_query($query, array($domain_id, $reseller_id));

        if ($stmt->recordCount() == 0) {
            set_page_message(tr('User does not exist or you do not have permission to access this interface!'), 'error');
            redirectTo('users.php?psi=last');
        } else {
            $row = $stmt->fetchRow();
            $dmn_status = $row['domain_status'];

            if ($dmn_status != $cfg->ITEM_OK_STATUS &&
                $dmn_status != $cfg->ITEM_ADD_STATUS
            ) {
                set_page_message(tr('System error with Domain Id: %d', $domain_id));
                redirectTo('users.php?psi=last');
            }
        }
    } else {
        set_page_message(tr('User does not exist or you do not have permission to access this interface!'), 'error');
        redirectTo('users.php?psi=last');
    }
}

/**
 * Dispatches the request
 */
if (isset($_POST['uaction'])) {
    if ($_POST['uaction'] == 'toASCII') { // Ajax request
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-cache, private');
        // backward compatibility for HTTP/1.0
        header('Pragma: no-cache');
        header("HTTP/1.0 200 Ok");

        // Todo check return value here before echo...
        echo '/' . encode_idna(strtolower($_POST['domain']));
        exit;
    } elseif ($_POST['uaction'] == 'add_alias') {
        add_domain_alias();
    } else {
        throw new iMSCP_Exception(tr("Unknown action: {$_POST['uaction']}"));
    }
} else { // Default page
    init_empty_data();
    if (isset($_SESSION['alias_scheduled_for_creation'])) {
        set_page_message(tr('Domain alias scheduled for creation.'), 'success');
        unset($_SESSION['alias_scheduled_for_creation']);
    }
}

gen_al_page($tpl);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
