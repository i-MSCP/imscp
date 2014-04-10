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
		'page' => 'admin/software_options.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Software Options'),
		'ISP_LOGO' => layout_getUserLogo()));

if(isset($_POST['uaction']) && $_POST['uaction'] == 'apply') {
    $error = "";

    $webdepot_xml_url = encode_idna(strtolower(clean_input($_POST['webdepot_xml_url'])));
    (strlen($webdepot_xml_url) > 0) ? $use_webdepot = $_POST['use_webdepot'] : $use_webdepot = '0';

    if(strlen($webdepot_xml_url) > 0 && $use_webdepot === '1') {
        $xml_file = @file_get_contents($webdepot_xml_url);
        if (!strpos($xml_file, 'i-MSCP web software repositories list')) {
            set_page_message(tr("Unable to read xml file for web software."), 'error');
            $error = 1;
        }
    }
    if(!$error){
        $query = "
            UPDATE
                `web_software_options`
            SET
                `use_webdepot` = '".$use_webdepot."',
                `webdepot_xml_url` = '".$webdepot_xml_url."'
        ";
        execute_query($query);
        set_page_message(tr("Software installer options successfully updated."), 'success');
    }
}

$query = "SELECT * FROM `web_software_options`";
$rs = execute_query($query);

$tpl->assign(
	array(
		'TR_OPTIONS_SOFTWARE' => tr('Software installer options'),
		'TR_MAIN_OPTIONS' => tr('Software installer options'),
		'TR_USE_WEBDEPOT' => tr('Remote Web software repository'),
		'TR_WEBDEPOT_XML_URL' => tr('XML file URL for the Web software repository'),
		'TR_WEBDEPOT_LAST_UPDATE' => tr('Last Web software repository update'),
		'USE_WEBDEPOT_SELECTED_OFF' => (($rs->fields['use_webdepot'] == "0") ? $cfg->HTML_SELECTED : ''),
		'USE_WEBDEPOT_SELECTED_ON' => (($rs->fields['use_webdepot'] == "1") ? $cfg->HTML_SELECTED : ''),
		'WEBDEPOT_XML_URL_VALUE' => $rs->fields['webdepot_xml_url'],
		'WEBDEPOT_LAST_UPDATE_VALUE' => ($rs->fields['webdepot_last_update'] == "0000-00-00 00:00:00") ? tr('not available') : $rs->fields['webdepot_last_update'],
		'TR_APPLY_CHANGES' => tr('Apply changes'),
		'TR_ENABLED' => tr('Enabled'),
		'TR_DISABLED' => tr('Disabled')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
