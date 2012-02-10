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
 * @author		Daniel Andreca <sci2tech@gmail.com>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/****************************************************************************
 * Script functions
 */

/**
 * Get full name and owner id for the given domain entity.
 *
 * @param string $type Domain entity type to update (dmn, als,sub, alssub)
 * @param int $id Domain entity unique identifier
 * @return array
 */
function client_getFullName($type, $id) {
	switch ($type) {
		case 'dmn':
			$query = 'SELECT `domain_name` `name`, `domain_admin_id` FROM `domain` WHERE `domain_id` = ?';
			break;
		case 'als':
			$query = 'SELECT `alias_name` `name`, `domain_admin_id` FROM `domain_aliasses` LEFT JOIN `domain` USING(`domain_id`) WHERE `alias_id` = ?';
			break;
		case 'sub':
			$query = 'SELECT CONCAT(`subdomain_name`, \'.\', `domain_name`) `name`, `domain_admin_id` FROM `subdomain` LEFT JOIN `domain` USING(`domain_id`) WHERE `subdomain_id` = ?';
			break;
		default: // alssub
			$query = 'SELECT CONCAT(`subdomain_alias_name`, \'.\', `alias_name`) `name`, `domain_admin_id` FROM `subdomain_alias` LEFT JOIN `domain_aliasses` USING(`alias_id`) LEFT JOIN `domain` USING(`domain_id`) WHERE `subdomain_alias_id` = ?';
			break;
	}
	$stmt = exec_query($query, array($id));

	return array($stmt->fields['name'], $stmt->fields['domain_admin_id']);
}

/**
 * Update status for the given domain entity.
 *
 * @param string $type Domain entity type to update (dmn, als,sub, alssub)
 * @param int $id Domain entity unique identifier
 */
function client_updateEntityStatus($type, $id) {
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	switch ($type) {
		case 'dmn':
			$query = 'UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?';
			break;
		case 'als':
			$query = 'UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?';
			break;
		case 'sub':
			$query = 'UPDATE `subdomain` SET `subdomain_status` = ? WHERE `subdomain_id` = ?';
			break;
		default: // alssub
			$query = 'UPDATE `subdomain_alias` SET `subdomain_alias_status` = ? WHERE `subdomain_alias_id` = ?';
	}

	exec_query($query, array($cfg->ITEM_CHANGE_STATUS, $id));
}

/**
 * Generate page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $id Domain entity unique identifier
 * @param string $type Domain entity type
 */
function client_generatePage($tpl, $id, $type) {
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	list($name, $owner) = client_getFullName($type, $id);

	if ($owner !== $_SESSION['user_id']) {
		set_page_message(tr('Wrong request.'), 'error');
		redirectTo('domains_manage.php');
	}

	if (isset($_POST['send']) && $cfg->ENABLE_SSL) {
		if ($_POST['pass'] != $_POST['pass_rep']) {
			set_page_message(tr('Passwords doesn\'t not matches.'), 'error');
		}

		if (!is_resource(@openssl_x509_read($_POST['cert_cert']))) {
			set_page_message(tr('Invalid certificate.'), 'error');
		}

		if (($k = @openssl_pkey_get_private(array($_POST['key_cert'], $_POST['pass']))) === false) {
			set_page_message(tr('Invalid key or password.'), 'error');
		}

		if ($k && @openssl_x509_check_private_key($_POST['cert_cert'], $k) !== true) {
			set_page_message(tr('Certificate doesn\'t match key.'), 'error');
		}

		if (!empty($_POST['ca_cert']) && !is_resource(@openssl_x509_read($_POST['ca_cert']))) {
			set_page_message(tr('Invalid intermediate certificate.'), 'error');
		}

		if (!Zend_Session::namespaceIsset('pageMessages')) {
			$query = 'DELETE FROM `ssl_certs` WHERE `type` = ? AND `id` = ?';
			exec_query($query, array($type, $id));

			$query = '
				INSERT INTO `ssl_certs` (
					`id`, `type`, `password`, `key`, `cert`, `ca_cert`, `status`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?
				)
			';
			exec_query($query, array($id, $type, $_POST['pass'], $_POST['key_cert'], $_POST['cert_cert'], $_POST['ca_cert'], $cfg->ITEM_ADD_STATUS));
			client_updateEntityStatus($type, $id);
			set_page_message(tr('Certificate successfully scheduled for addition or modification.'), 'success');
			write_log($_SESSION['user_logged'] . ': added new certificate for: ' . $name, E_USER_NOTICE);
			send_request();
		}
	} elseif (isset($_POST['delete'])) {
		$query = 'UPDATE `ssl_certs` SET `status` = ? WHERE `type` = ? AND `id` = ? ';
		exec_query($query, array($cfg->ITEM_DELETE_STATUS, $type, $id));
		client_updateEntityStatus($type, $id);
		set_page_message(tr('Certificate sucessfully scheduled for deletion.'), 'success');
		write_log($_SESSION['user_logged'] . ': deleted certificate for: ' . $name, E_USER_NOTICE);
		send_request();
	}

	$query = 'SELECT * FROM `ssl_certs` WHERE `type` = ? AND `id` = ?';
	$stmt = exec_query($query, array($type, $id));

	if (!$stmt->rowCount()) {
		$tpl->assign('TR_DYNAMIC_TITLE', tr('Add SSL certificate'));

		if ($cfg->ENABLE_SSL) {
			$status = tr('No certificate found.');
		} else {
			set_page_message(tr('SSL feature is disabled. You cannot add / change certificate'), 'warning');
		}
	} else {
		if ($cfg->ENABLE_SSL) {
			$tpl->assign('TR_DYNAMIC_TITLE', tr('Edit SSL certificate'));
		} else {
			$tpl->assign('TR_DYNAMIC_TITLE', tr('View SSL certificate'));
			$tpl->assign('CERT_ENABLE', '');
		}

		if (in_array($stmt->fields['status'], array($cfg->ITEM_OK_STATUS, $cfg->ITEM_DELETE_STATUS, $cfg->ITEM_ADD_STATUS, $cfg->ITEM_CHANGE_STATUS))) {
			$status = translate_dmn_status($stmt->fields['status']);
		} else {
			$status = tr('Error') . ': ' . $stmt->fields['status'];
		}
	}

	if (isset($status)) {
		$tpl->assign(
			array(
				'DOMAIN_NAME'	=>	$name,
				'KEY_CERT'		=>	isset($_POST['send']) && isset($_POST['key_cert'])
										? $_POST['key_cert']
										: ($stmt->fields['key'] ? $stmt->fields['key'] : ''),
				'CERT'			=>	isset($_POST['send']) && isset($_POST['cert_cert'])
										? $_POST['cert_cert']
										: ($stmt->fields['cert'] ? $stmt->fields['cert'] : ''),
				'CA_CERT'		=>	isset($_POST['send']) && isset($_POST['ca_cert'])
										? $_POST['ca_cert']
										: ($stmt->fields['ca_cert'] ? $stmt->fields['ca_cert'] : ''),
				'STATUS'		=>	$status
			)
		);
	} else {
		redirectTo('domains_manage.php');
	}
}

/****************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/cert_view.tpl',
		'page_message' => 'layout',
		'cert_enable' => 'page'
	)
);

if (!isset($_GET['id']) || !isset($_GET['type']) || !in_array($_GET['type'], array('dmn', 'als', 'sub', 'alssub'))) {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('domains_manage.php');
} else {
	$id = intval($_GET['id']);
	$type = $_GET['type'];
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Client/Manage certificates'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_CERTIFICATE_DATA' => tr('Certificate data'),
		'TR_CERT_FOR' => tr('Certificate for'),
		'TR_STATUS' => tr('Status'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat  password'),
		'TR_CERTIFICATE_KEY' => tr('Certificate key'),
		'TR_CERTIFICATE' => tr('Certificate'),
		'TR_INTERM_CERTIFICATE' => tr('Intermediar certificate'),
		'TR_DELETE' => tr('Delete'),
		'TR_SAVE' => tr('Save'),
		'TR_CANCEL' => tr('Cancel'),
		'ID' => $id,
		'TYPE' => $type
	)
);

generateNavigation($tpl);
client_generatePage($tpl, $id, $type);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
