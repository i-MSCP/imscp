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
 * @package        iMSCP_Core
 * @subpackage    Client
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
 * Generate edit page
 *
 * @param iMSCP_pTemplate $tpl
 * @param $errorPageId
 * @return void
 */
function generateErrorPageData($tpl, $errorPageId)
{
	$domain = $_SESSION['user_logged'];

	// Check if we already have an error page
	$vfs = new iMSCP_VirtualFileSystem($domain);
	$errorPageContent = $vfs->get('/errors/' . $errorPageId . '.html');

	if (false !== $errorPageContent) {
		// We already have an error page, return it
		$tpl->assign('ERROR', tohtml($errorPageContent));
		return;
	}
	// No error page
	$tpl->assign('ERROR', '');
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('custom_error_pages') or showBadRequestErrorPage();

if (!isset($_GET['eid'])) {
	showBadRequestErrorPage();
	exit;
} else {
	$errorPageId = intval($_GET['eid']);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/error_edit.tpl',
		'page_message' => 'layout'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr(' Client / Webtools / Custom Error Pages / Edit Custom Error Page'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ERROR_EDIT_PAGE' => tr('Edit error page'),
		'TR_SAVE' => tr('Save'),
		'TR_CANCEL' => tr('Cancel'),
		'EID' => $errorPageId
	)
);

if (in_array($errorPageId, array('401', '403', '404', '500', '503'))) {
	generateErrorPageData($tpl, $errorPageId);
} else {
	showBadRequestErrorPage();
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
