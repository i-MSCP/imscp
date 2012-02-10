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

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('custom_error_pages')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/error_pages.tpl');
$tpl->define_dynamic('page_message', 'layout');

// page functions.

/**
 * @param $user_id
 * @param $eid
 * @return bool
 */
function write_error_page($user_id, $eid) {

	$error = $_POST['error'];
	$file = '/errors/' . $eid . '.html';
	$vfs = new iMSCP_VirtualFileSystem($_SESSION['user_logged']);

	return $vfs->put($file, $error);
}

/**
 * @param $user_id
 * @return void
 */
function update_error_page($user_id) {

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'updt_error') {
		$eid = intval($_POST['eid']);

		if (in_array($eid, array(401, 402, 403, 404, 500, 503))
			&& write_error_page($_SESSION['user_id'], $eid)) {
			set_page_message(tr('Custom error page updated.'), 'success');
		} else {
			set_page_message(tr('System error - custom error page was not updated.'), 'error');
		}
	}
}

$domain = $_SESSION['user_logged'];
$domain = "http://www." . $domain;

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Client/Manage Error Custom Pages'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DOMAIN' => $domain));

update_error_page($_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_ERROR_401' => tr('Error 401 (unauthorised)'),
		'TR_ERROR_403' => tr('Error 403 (forbidden)'),
		'TR_ERROR_404' => tr('Error 404 (not found)'),
		'TR_ERROR_500' => tr('Error 500 (internal server error)'),
		'TR_ERROR_503' => tr('Error 503 (service unavailable)'),
		'TR_ERROR_PAGES' => tr('Custom error pages'),
		'TR_EDIT' => tr('Edit'),
		'TR_VIEW' => tr('View')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
