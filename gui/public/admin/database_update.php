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
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Generate database update details.
 *
 * @param $tpl iMSCP_pTemplate
 * return void
 */
function admin_generateDatabaseUpdateDetail($tpl)
{
	$dbUpdatesDetail = iMSCP_Update_Database::getInstance()->getDatabaseUpdatesDetails();

	foreach ($dbUpdatesDetail as $revision => $detail) {
		$tpl->assign(
			array(
				'DB_UPDATE_REVISION' => (int)$revision,
				'DB_UPDATE_DETAIL' => _admin_generateIssueTrackerLink($detail)
			)
		);

		$tpl->parse('DATABASE_UPDATE', '.database_update');
	}
}

/**
 * Generate issue tracker link for tickets references in database update detail.
 *
 * @access private
 * @param string $detail database update detail
 * @return string
 */
function _admin_generateIssueTrackerLink($detail)
{
	return preg_replace(
		'/#([0-9]+)/',
		'<a href="http://trac.i-mscp.net/ticket/\1" target="_blank" title="' . tr('More Details') . '">#\1</a>',
		$detail
	);
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/** @var $dbUpdate iMSCP_Update_Database */
$dbUpdate = iMSCP_Update_Database::getInstance();

if (isset($_POST['uaction']) && $_POST['uaction'] == 'update') {
	// Execute all available db updates
	if (!$dbUpdate->applyUpdates()) {
		throw new iMSCP_Exception($dbUpdate->getError());
	}

	// Set success page message
	set_page_message('Database update successfully applied.', 'success');
	redirectTo('system_info.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/database_update.tpl',
		'page_message' => 'layout',
		'database_updates' => 'page',
		'database_update' => 'database_updates'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / System Tools / Database Update'),
		'ISP_LOGO' => layout_getUserLogo(),
	)
);

generateNavigation($tpl);

if ($dbUpdate->isAvailableUpdate()) {
	set_page_message(tr('One or more database updates are now available. See the details below.'), 'info');
	admin_generateDatabaseUpdateDetail($tpl);

	$tpl->assign(
		array(
			'TR_DATABASE_UPDATES' => tr('Database Update Revision'),
			'TR_DATABASE_UPDATE_DETAIL' => tr('Database Update details'),
			'TR_PROCESS_UPDATES' => tr('Process update')));
} else {
	$tpl->assign('DATABASE_UPDATES', '');
	set_page_message(tr('No database update available.'), 'info');
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(
	iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl)
);

$tpl->prnt();

unsetMessages();
