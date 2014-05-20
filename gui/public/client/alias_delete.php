<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @package     Client_Domains_Aliases
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasFeature('domain_aliases') && isset($_GET['id'])) {
	$alsId = clean_input($_GET['id']);
	$mainDmnId = get_user_domain_id($_SESSION['user_id']);

	$query = "SELECT `alias_name` FROM `domain_aliasses` WHERE `domain_id` = ? AND `alias_id` = ?";
	$stmt = exec_query($query, array($mainDmnId, $alsId));

	if ($stmt->rowCount()) {
		$alsName = $stmt->fields['alias_name'];
		$ret = false;

		// check for subdomains
		$query = "SELECT COUNT(`subdomain_alias_id`) AS `cnt` FROM `subdomain_alias` WHERE `alias_id` = ?";
		$stmt = exec_query($query, $alsId);

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has subdomains. Remove them first.'), 'error'
			);
			$ret = true;
		}

		// Check for custom dns and external mail server records
		$query = "SELECT COUNT(`domain_dns_id`) AS `cnt` FROM `domain_dns` WHERE `alias_id` = ?";
		$stmt = exec_query($query, $alsId);

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has custom DNS records. Remove them first.'), 'error'
			);
			$ret = true;
		}

		// Check for Ftp accounts
		$query = "SELECT count(`userid`) AS `cnt` FROM `ftp_users` WHERE `userid` LIKE ?";
		$stmt = exec_query($query, "%@$alsName");

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has Ftp accounts. Remove them first.'), 'error'
			);
			$ret = true;
		}

		// Check for mail accounts
		$query = "
			SELECT
				COUNT(`mail_id`) AS `cnt`
			FROM
				`mail_users`
			WHERE
				(`sub_id` = ? AND `mail_type` LIKE ?)
			OR
				(
					`sub_id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ?)
					AND `mail_type` LIKE ?
				)
		";
		$stmt = exec_query($query, array($alsId, '%alias_%', $alsId, '%alssub_%'));

		if ($stmt->fields['cnt']) {
			set_page_message(
				tr('Domain alias you are trying to remove has email accounts. Remove them first.'), 'error'
			);
			$ret = true;
		}

		if (!$ret) {
			iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onBeforeDeleteDomainAlias,
				array('domainAliasId' => $alsId, 'domainAliasName' => $alsName)
			);

			/** @var $db iMSCP_Database */
			$db = iMSCP_Database::getInstance();

			try {
				$db->beginTransaction();

				# Schedule deletion of any SSL certificat, which have domain alias as parent
				$query = "UPDATE ssl_certs SET status = ? WHERE domain_id = ? AND domain_type = ?";
				exec_query($query, array('todelete', $alsId, 'als'));

				# Schedule deletion of domain alias
				$query = "UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?";
				exec_query($query, array('todelete', $alsId));

				$db->commit();
			} catch (iMSCP_Exception_Database $e) {
				$db->rollBack();
				throw $e;
			}

			iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onAfterDeleteDomainAlias,
				array('domainAliasId' => $alsId, 'domainAliasName' => $alsName)
			);

			send_request();

			write_log("{$_SESSION['user_logged']} scheduled deletion of domain alias $alsName", E_USER_NOTICE);
			set_page_message(tr('Domain alias successfully scheduled for deletion.'), 'success');
		}

		redirectTo('domains_manage.php');
	}
}

showBadRequestErrorPage();
