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
 * @subpackage  Admin
 * @copyright   2010-2014 by i-MSCP team
 * @author      Sacha Bay <sascha.bay@i-mscp.net>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/software_reseller.tpl',
		'page_message' => 'layout',
		'list_software' => 'page',
		'no_software_list' => 'page',
		'list_softwaredepot' => 'page',
		'no_softwaredepot_list' => 'page',
		'no_reseller_list' => 'page',
		'list_reseller' => 'page',
		'software_is_in_softwaredepot' => 'page',
		'software_is_not_in_softwaredepot' => 'page'));

if (isset($_GET['id'])){
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$reseller_id = $_GET['id'];
	} else {
		set_page_message(tr('Wrong reseller id.'), 'error');
		redirectTo('software_manage.php');
	}

} else {
	set_page_message(tr('Wrong reseller id.'), 'error');
	redirectTo('software_manage.php');
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Software Management / Reseller Software'),
		'ISP_LOGO' => layout_getUserLogo()));

$software_cnt = get_installed_res_software($tpl, $_GET['id']);
$res_cnt = get_reseller_software($tpl, $_GET['id']);

$tpl->assign(
	array(
		'RESELLER_ID' => $reseller_id,
		'TR_SOFTWARE_INSTALLED' => tr('Installed on'),
		'TR_SOFTWARE_RIGHTS' => tr('Permissions'),
		'TR_SOFTWAREDEPOT_COUNT' => tr('Total Software'),
		'TR_SOFTWAREDEPOT_NUM' => $software_cnt,
		'TR_AWAITING_ACTIVATION' => tr('Awaiting activation'),
		'TR_ACTIVATED_SOFTWARE' => tr('Reseller list'),
		'TR_SOFTWARE_NAME' => tr('Software name'),
		'TR_SOFTWARE_VERSION' => tr('Version'),
		'TR_SOFTWARE_LANGUAGE' => tr('Language'),
		'TR_SOFTWARE_TYPE' => tr('Type'),
		'TR_RESELLER_NAME' => tr('Reseller'),
		'TR_RESELLER_ACT_COUNT' => tr('Reseller total'),
		'TR_RESELLER_ACT_NUM' => $res_cnt,
		'TR_RESELLER_COUNT_SWDEPOT' => tr('Software repository'),
		'TR_RESELLER_COUNT_WAITING' => tr('Awaiting activation'),
		'TR_RESELLER_COUNT_ACTIVATED' => tr('Activated software'),
		'TR_RESELLER_SOFTWARE_IN_USE' => tr('Total installations'),
		'TR_ADMIN_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Software Installer / Management')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
