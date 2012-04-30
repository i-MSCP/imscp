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
$tpl->define_dynamic('page', 'client/mail_external.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('relay_message', 'page');
$tpl->define_dynamic('relay_item', 'page');
$tpl->define_dynamic('relay_item_new', 'page');
$tpl->define_dynamic('relay_item_edit', 'page');
$tpl->define_dynamic('relay_item_delete', 'page');

/**
 * @param $tpl
 * @param $action
 * @param $dmn_id
 * @param $dmn_name
 * @param $relay_status
 * @param $rel_type
 * @return void
 */
function gen_relay_item(&$tpl, $action, $dmn_id, $dmn_name, $relay_status, $rel_type)
{
	$show_dmn_name = decode_idna($dmn_name);

	if ($action === 'create') {
		$tpl->assign(
			array(
				'RELAY_DOMAIN' => tohtml($show_dmn_name),
                'RELAY_ACTIVE' => tr('not activated'),
				'RELAY_STATUS' => translate_dmn_status($relay_status),
				'RELAY_CREATE_ACTION' => tr('Create Relay Entry'),
				'RELAY_CREATE_ACTION_SCRIPT' => "mail_external_add.php?id=$dmn_id;$rel_type",
				'RELAY_EDIT_ACTION' => '',
				'RELAY_EDIT_ACTION_SCRIPT' => '',
				'RELAY_DELETE_ACTION' => '',
				'RELAY_DELETE_ACTION_SCRIPT' => '',
                'RELAY_ITEM_EDIT' => '',
                'RELAY_ITEM_DELETE' => ''));

		$tpl->parse('RELAY_ITEM_NEW', 'relay_item_new');
	} else {
		$tpl->assign(
			array(
				'RELAY_DOMAIN' => tohtml($show_dmn_name),
				'RELAY_ACTIVE' => tr('activated'),
				'RELAY_STATUS' => translate_dmn_status($relay_status),
				'RELAY_CREATE_ACTION' => '',
				'RELAY_CREATE_ACTION_SCRIPT' => '',
				'RELAY_EDIT_ACTION' => ($relay_status === "ok") ? tr('Edit Relay Entry') : tr('N/A'),
				'RELAY_EDIT_ACTION_SCRIPT' => ($relay_status === "ok") ? "mail_external_edit.php?id=$dmn_id;$rel_type" : '',
				'RELAY_DELETE_ACTION' => ($relay_status === "ok") ? tr('Delete Relay Entry') : tr('N/A'),
				'RELAY_DELETE_ACTION_SCRIPT' => ($relay_status === "ok") ? "mail_external_delete.php?id=$dmn_id;$rel_type" : '',
				'RELAY_ITEM_NEW' => ''));

		$tpl->parse('RELAY_ITEM_EDIT', 'relay_item_edit');
		$tpl->parse('RELAY_ITEM_DELETE', 'relay_item_delete');
	}
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $dmn_name
 */
function gen_page_relay_list($tpl, $dmn_id, $dmn_name)
{
	$tpl->assign('RELAY_MESSAGE', '');

	$query = "
		SELECT
			`external_mail_status`, `external_mail`
		FROM
			`domain`
		WHERE
			`domain_id` = '$dmn_id'
		AND
			`domain_status` = 'ok'
		";

	$rs = execute_query($query);

	if ($rs->fields['external_mail'] == "off" && $rs->fields['external_mail_status'] == "ok") {
		gen_relay_item($tpl, 'create', $dmn_id, $dmn_name, $rs->fields['external_mail_status'], 'normal');
	} else {
		gen_relay_item(
			$tpl,
			'',
			$dmn_id,
			$dmn_name,
			$rs->fields['external_mail_status'], 'normal');
	}

	$tpl->parse('RELAY_ITEM', 'relay_item');

	$query = "
		SELECT
			`alias_id`, `alias_name`
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = '$dmn_id'
		AND
			`alias_status` = 'ok'
	";

	$rs = execute_query($query);

	while (!$rs->EOF) {
		$als_id = $rs->fields['alias_id'];
		$als_name = $rs->fields['alias_name'];

		$query = "
			SELECT
				`external_mail_status`, `external_mail`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = '$dmn_id'
			AND
				`alias_id` = '$als_id'
		";

		$rs_als = execute_query($query);

		if ($rs_als->fields['external_mail'] == "off" && $rs_als->fields['external_mail_status'] == "ok") {
			gen_relay_item($tpl, 'create', $als_id, $als_name, $rs_als->fields['external_mail_status'], 'alias');
		} else {
			gen_relay_item(
				$tpl,
				'',
				$als_id,
				$als_name,
				$rs_als->fields['external_mail_status'], 'alias');
		}

		$tpl->parse('RELAY_ITEM', '.relay_item');

		$rs->moveNext();
	}
}

/**
 * @param $tpl
 * @param $user_id
 */
function gen_page_lists($tpl, $user_id)
{
	list($dmn_id,
		$dmn_name,
		$dmn_gid,
		$dmn_uid,
		$dmn_created_id,
		$dmn_created,
		$dmn_expires,
		$dmn_last_modified,
		$dmn_mailacc_limit,
		$dmn_ftpacc_limit,
		$dmn_traff_limit,
		$dmn_sqld_limit,
		$dmn_sqlu_limit,
		$dmn_status,
		$dmn_als_limit,
		$dmn_subd_limit,
		$dmn_ip_id,
		$dmn_disk_limit,
		$dmn_disk_usage,
		$dmn_php,
		$dmn_cgi,
		$allowbackup,
		$dmn_dns
	) = get_domain_default_props($user_id);

	gen_page_relay_list($tpl, $dmn_id, $dmn_name);
}

// common page data.

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client / Manage mail / External mail servers'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	$tpl->assign('NO_MAILS', '');
}

gen_page_lists($tpl, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		 'TR_STATUS' => tr('Status'),
		 'TR_ACTION' => tr('Action'),
		 'TR_TITLE_RELAY_MAIL_USERS' => tr('External mail servers'),
		 'TR_DOMAIN' => tr('Domain'),
		 'TR_RELAY' => tr('External mail servers'),
         'TR_RELAY_ACTIVE' => tr('External mail servers status'),
		 'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete the %s external mail server?', true, '%s')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
