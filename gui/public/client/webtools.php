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
 * @subpackage  Client
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
 * Hide disabled feature.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 */
function client_hideDisabledFeatures($tpl)
{
	if (!customerHasFeature('backup')) {
		$tpl->assign('BACKUP_FEATURE', '');
	}

	if (!customerHasFeature('mail')) {
		$tpl->assign('MAIL_FEATURE', '');
	}

	if (!customerHasFeature('ftp')) {
		$tpl->assign('FTP_FEATURE', '');
	}

	if (!customerHasFeature('aps')) {
		$tpl->assign('APS_FEATURE', '');
	}

	if (!customerHasFeature('webstats')) {
		$tpl->assign('WEBSTATS_FEATURE', '');
	}
}

/***********************************************************************************************************************
 * Main
 *
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/webtools.tpl',
		'page_message' => 'layout',
		'backup_feature' => 'page',
		'mail_feature' => 'page',
		'ftp_feature' => 'page',
		'aps_feature' => 'page',
		'webstats_feature' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Webtools / Overview'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_FEATURE' => tr('Feature'),
		'TR_DESCRIPTION' => tr('Description'),
		'TR_HTACCESS' => tr('Protected areas'),
		'TR_HTACCESS_TXT' => tr('Manage your protected areas, users and groups.'),
		'TR_ERROR_PAGES' => tr('Error pages'),
		'TR_ERROR_PAGES_TXT' => tr('Customize error pages for your domain.'),
		'TR_BACKUP' => tr('Backup'),
		'TR_BACKUP_TXT' => tr('Backup and restore settings.'),
		'TR_WEBMAIL' => tr('Webmail'),
		'TR_WEBMAIL_TXT' => tr('Access your mail through the web interface.'),
		'TR_FILEMANAGER' => tr('FileManager'),
		'TR_FILEMANAGER_TXT' => tr('Access your files through the web interface.'),
		'TR_WEBSTATS' => tr('Web Statistics'),
		'TR_WEBSTATS_TXT' => tr('Access your domain statistics through the Web interface.'),
		'TR_APP_INSTALLER' => tr('Application installer'),
		'TR_APP_INSTALLER_TXT' => tr('Install various Web applications with a few clicks.')
	)
);

generateNavigation($tpl);
client_hideDisabledFeatures($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
