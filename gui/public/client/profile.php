<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2010-2015 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/*******************************************************************************
 * Script functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 */
function client_generatePage($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT domain_created from admin where admin_id = ?";
	$stmt = exec_query($query, (int)$_SESSION['user_id']);

	switch($_SESSION['user_type']) {
		case "user":
			$trUserType = tr('User');
			break;
		case "reseller":
			$trUserType = tr('Reseller');
			break;
		case "admin":
			$trUserType = tr('Admin');
			break;
		default:
			$trUserType = tr('Unknown user type');
	}

	$tpl->assign(
		array(
			'TR_ACCOUNT_SUMMARY' => tr('Account summary'),
			'TR_USERNAME' => tr('Username'),
			'USERNAME' => tohtml($_SESSION['user_logged']),
			'TR_ACCOUNT_TYPE' => tr('Account type'),
			'ACCOUNT_TYPE' => $trUserType,
			'TR_REGISTRATION_DATE' => tr('Registration date'),
			'REGISTRATION_DATE' => ($stmt->fields['domain_created'] != 0) ? date($cfg->DATE_FORMAT, $stmt->fields['domain_created']) : tr('Unknown')));
}

/*******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('user');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/profile.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Client / Profile / Account Summary'),
		 'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
