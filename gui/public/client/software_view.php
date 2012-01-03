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
 *  Functions
 */

/**
 * @param $tpl
 * @param $user_id
 * @return
 */
function gen_page_lists($tpl, $user_id) {
	if (!isset($_GET['id']) || $_GET['id'] === '' || !is_numeric($_GET['id'])) {
		set_page_message(tr('Software not found!'), 'error');
		redirectTo('software.php');
		exit;
	} else {
		$software_id = $_GET['id'];
	}
    list($dmn_id, $dmn_name,,,$dmn_created_id,,,,,,$dmn_sqld_limit,) = get_domain_default_props($user_id);
	get_software_props ($tpl, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit);
	return $software_id;
}

/************************************************************************************
 * Main program
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
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/software_view.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('software_message', 'page');
$tpl->define_dynamic('software_install', 'page');
$tpl->define_dynamic('installed_software_info', 'page');
$tpl->define_dynamic('software_item', 'page');
$tpl->define_dynamic('no_software', 'page');

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Software details'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

if (isset($_SESSION['software_support']) && $_SESSION['software_support'] == "no") {
	$tpl -> assign('NO_SOFTWARE', '');
}

$software_id = gen_page_lists($tpl, $_SESSION['user_id']);

generateNavigation($tpl);
get_client_software_permission ($tpl, $_SESSION['user_id']);

$tpl->assign(
	array(
		 'TR_SOFTWARE_MENU_PATH' => tr('i-MSCP - application installer'),
		 'TR_SOFTWARE_VIEW_PATH' => tr('Software details'),
		 'SOFTWARE_ID' => $software_id,
		 'TR_MANAGE_USERS' => tr('Manage users'),
		 'TR_VIEW_SOFTWARE' => tr('Software details'),
		 'TR_NAME' => tr('Software'),
		 'TR_VERSION' => tr('Version'),
		 'TR_LANGUAGE' => tr('Language'),
		 'TR_TYPE' => tr('Type'),
		 'TR_DB' => tr('Database required'),
		 'TR_LINK' => tr('Homepage'),
		 'TR_DESC' => tr('Description'),
		 'TR_BACK' => tr('Back'),
		 'TR_INSTALL' => tr('Install'),
		 'TR_SOFTWARE_MENU' => tr('Software installation')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
