<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package	    iMSCP_Core
 * @subpackage  Client_Ftp
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Generates page data
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function ftp_generatePageData($tpl)
{
	$query = "SELECT `userid` FROM `ftp_users` WHERE `admin_id` = ? ORDER BY LENGTH(`userid`) DESC";
	$stmt = exec_query($query, $_SESSION['user_id']);

	if (!$stmt->rowCount()) {
		set_page_message(tr('You do not have FTP accounts.'), 'info');
		$tpl->assign('FTP_ACCOUNTS', '');
	} else {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		if(!(isset($cfg->FILEMANAGER_ADDON) && $cfg->FILEMANAGER_ADDON == 'AjaXplorer')) {
			$tpl->assign('FTP_EASY_LOGIN', '');
		}

		$nbFtpAccounts = 0;

		while(!$stmt->EOF) {
			$userid = $stmt->fields['userid'];

			$tpl->assign(
				array(
					'FTP_ACCOUNT' => tohtml($userid),
					'UID' => urlencode($userid)
				)
			);

			$tpl->parse('FTP_ITEM', '.ftp_item');
			$stmt->moveNext();
			$nbFtpAccounts++;
		}

		$tpl->assign('TOTAL_FTP_ACCOUNTS', $nbFtpAccounts);
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('ftp') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/ftp_accounts.tpl',
		'page_message' => 'layout',
		'ftp_message' => 'page',
		'ftp_accounts' => 'page',
		'ftp_item' => 'ftp_accounts',
		'ftp_easy_login' => 'ftp_item'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / FTP / Overview'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_TOTAL_FTP_ACCOUNTS' => tr('FTPs total'),
		'TR_FTP_USERS' => tr('FTP Users'),
		'TR_FTP_ACCOUNT' => tr('FTP account'),
		'TR_FTP_ACTION' => tr('Actions'),
		'TR_LOGINAS' => tr('Login As'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s FTP user?', true, '%s'),
		'FILEMANAGER_TARGET' => $cfg->FILEMANAGER_TARGET
	)
);

generateNavigation($tpl);
ftp_generatePageData($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
