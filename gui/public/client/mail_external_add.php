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
 * @author		Sascha Bay <worst.case@gmx.de>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('external_mail') || !customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/mail_external_add.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('relay_message', 'page');

if (isset($_GET['id']) && isset($_GET['id'])) {
	$item_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$item_id = $_POST['id'];
} else {
	redirectTo('mail_external.php');
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $dmn_type
 * @param $post_check
 * @return void
 */
function gen_page_form_data($tpl, $dmn_id, $dmn_type, $post_check) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($post_check === 'no') {

		$tpl->assign(
			array(
				'CNAME_DOMAIN_DNS' => '',
				'MX_PRIORITY' => '',
                'DOMAIN_TEXT' => ''));

	} else {

		$tpl->assign(
			array(
				'CNAME_DOMAIN_DNS' => '',
                'MX_PRIORITY' => '',
                'DOMAIN_TEXT' => ''));
	}
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $item_type
 * @return void
 */
function get_dmn_data($tpl, $dmn_id, $item_type) {
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

	if ($item_type === 'normal') {
        $query = "
              SELECT
                `domain_name`
              FROM
                `domain`
              WHERE
                `domain_id` = '$dmn_id'
            ";
        $rs = execute_query($query);
        $dmn_name = $rs->fields['domain_name'];
	} else {
        $query = "
              SELECT
                `alias_name`
              FROM
                `domain_aliasses`
              WHERE
                `alias_id` = '$dmn_id'
            ";
        $rs = execute_query($query);
        $dmn_name = $rs->fields['alias_name'];
	}
    $dmn_name_decoded = decode_idna($dmn_name);

    $tpl->assign(
        array(
            'DOMAIN_NAME' => tohtml($dmn_name),
            'ID' => $dmn_id.";".$item_type,
            'MX_ENTRY' => (isset($_POST['domain_type']) && $_POST['domain_type'] === 'cname') ? '' : $cfg->HTML_CHECKED,
            'CNAME_ENTRY' => (isset($_POST['domain_type']) && $_POST['domain_type'] === 'cname') ? $cfg->HTML_CHECKED : '',
            'DEFAULT' => (isset($_POST['domain_type']) && $_POST['domain_type'] === 'cname') ? 'cname' : 'mx'));
}

/**
 * @param $tpl
 * @param $item_id
 * @param $user_id
 * @return void
 */
function gen_page_external_mail_props($tpl, $item_id, $user_id) {
    $match = array();
    if (preg_match("/(\d+);(normal|alias)/", $item_id, $match) == 1) {
        $item_id = $match[1];
        $item_type = $match[2];
        if ($item_type === 'normal' || $item_type === 'alias') {
            get_dmn_data($tpl, $item_id, $item_type);
        } else {
            redirectTo('mail_external.php');
        }
        $post_check = isset($_POST['uaction']) ? 'yes' : 'no';
        gen_page_form_data($tpl, $item_id, $item_type, $post_check);
        if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_external_mail') {

        }
    } else {
        redirectTo('mail_external.php');
    }
}

// common page data.

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client / Manage mail / Create External mail server entry'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	$tpl->assign('NO_MAILS', '');
}

gen_page_external_mail_props($tpl, $item_id, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
         'TR_ACTION' => tr('Action'),
         'TR_RELAY_TYPE' => tr('Entry type'),
         'TR_MX' => tr('MX'),
         'TR_CNAME' => tr('CNAME'),
         'TR_RELAY_DNS' => tr('Alias / Canonical name'),
		 'TR_CREATE_RELAY' => tr('Activate Entries'),
         'CNAME_PRIORITY' => tr('Not needed in CNAME'),
         'TR_MX_PRIORITY' => tr('Priority'),
		 'TR_MX_ENTRY' => tr('MX entry IN'),
		 'TR_TITLE_RELAY_MAIL_USERS' => tr('Create External mail server entry'),
		 'TR_DOMAIN' => tr('Domain'),
		 'TR_RELAY' => tr('External mail servers'),
         'TR_CNAME_ENTRY' => tr('CNAME'),
         'TR_RELAY_SERVER' => tr('IP or FQDN-Servername'),
         'TR_ADD_NEW' => tr('Add new line'),
         'TR_REMOVE_LAST' => tr('Remove last'),
         'TR_RESET' => tr('Reset')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
