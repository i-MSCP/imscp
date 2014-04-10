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
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('custom_error_pages') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/error_pages.tpl',
		'page_message' => 'layout'
	)
);

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
		'TR_PAGE_TITLE' => tr('Client / Webtools / Custom Error Pages'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DOMAIN' => $domain));

update_error_page($_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_ERROR_401' => tr('Unauthorized'),
		'TR_ERROR_403' => tr('Forbidden'),
		'TR_ERROR_404' => tr('Not Found'),
		'TR_ERROR_500' => tr('Internal Server Error'),
		'TR_ERROR_503' => tr('Service Unavailable'),
		'TR_ERROR_PAGES' => tr('Custom error pages'),
		'TR_EDIT' => tr('Edit'),
		'TR_VIEW' => tr('View')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
