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
 * @copyright   2010-2014 by i-MSCP team
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
 * @return void
 */
function client_generatePageLists($tpl)
{
	$domainProperties = get_domain_default_props($_SESSION['user_id']);

	$stmt = exec_query('SELECT created_by FROM admin WHERE admin_id = ?', $_SESSION['user_id']);

	$software_poss = gen_software_list($tpl, $domainProperties['domain_id'], $stmt->fields['created_by']);
	$tpl->assign('TOTAL_SOFTWARE_AVAILABLE', $software_poss);
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('aps') or showBadRequestErrorPage();

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
		'software_list' => 'page',
		'del_software_support' => 'software_list',
		'del_software_item' => 'software_list',
		't_software_support' => 'software_list'
	)
);


$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Client / Webtools / Software'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_SOFTWARE' => tr('Software'),
		 'TR_VERSION' => tr('Version'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_TYPE' => tr('Type'),
		 'TR_NEED_DATABASE' => tr('Database'),
		 'TR_STATUS' => tr('Status'),
		 'TR_ACTION' => tr('Action'),
		 'TR_SOFTWARE_AVAILABLE' => tr('Available software'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
	)
);

generateNavigation($tpl);
client_generatePageLists($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
