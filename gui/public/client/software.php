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
 * @copyright   2010-2012 by i-MSCP team
 * @author      Sacha Bay <sascha.bay@i-mscp.net>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/************************************************************************************
 *  Script functions
 */

/**
 * client_generatePageLists.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $customerId Customer unique identifier
 * @return void
 */
function client_generatePageLists($tpl, $customerId)
{
    $domainProperties = get_domain_default_props($customerId, true);
    $software_poss = gen_software_list($tpl, $domainProperties['domain_id'], $domainProperties['domain_created_id']);
    $tpl->assign('TOTAL_SOFTWARE_AVAILABLE', $software_poss);
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('aps')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/software.tpl',
		'page_message' => 'layout',
		'software_message' => 'page',
		'software_item' => 'page',
		'software_action_delete' => 'page',
		'software_action_install' => 'page',
		'software_total' => 'page',
		'no_software' => 'page',
		'no_software_support' => 'page',
		'del_software_support' => 'page',
		'del_software_item' => 'page',
		't_software_support' => 'page'));


$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client / Webtools / Softwares'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_SOFTWARE' => tr('Software'),
		 'TR_VERSION' => tr('Version'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_TYPE' => tr('Type'),
		 'TR_NEED_DATABASE' => tr('Database'),
		 'TR_STATUS' => tr('Status'),
		 'TR_ACTION' => tr('Action'),
		 'TR_SOFTWARE_AVAILABLE' => tr('Available softwares'),
		 'TR_SOFTWARE_ASC' => 'software.php?sortby=name&order=asc',
		 'TR_SOFTWARE_DESC' => 'software.php?sortby=name&order=desc',
		 'TR_TYPE_ASC' => 'software.php?sortby=type&order=asc',
		 'TR_TYPE_DESC' => 'software.php?sortby=type&order=desc',
		 'TR_NEED_DATABASE_ASC' => 'software.php?sortby=database&order=asc',
		 'TR_NEED_DATABASE_DESC' => 'software.php?sortby=database&order=desc',
		 'TR_STATUS_ASC' => 'software.php?sortby=status&order=asc',
		 'TR_STATUS_DESC' => 'software.php?sortby=status&order=desc',
		 'TR_LANGUAGE_ASC' => 'software.php?sortby=language&order=asc',
		 'TR_LANGUAGE_DESC' => 'software.php?sortby=language&order=desc'));

generateNavigation($tpl);
client_generatePageLists($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
