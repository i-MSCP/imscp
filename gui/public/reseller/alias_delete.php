<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (resellerHasFeature('domain_aliases') && isset($_GET['id'])) {
	$alsId = clean_input($_GET['id']);

	$query = "
		SELECT
			domain_id, alias_id, alias_name, domain_id
		FROM
			domain_aliasses
		INNER JOIN
			domain USING (domain_id)
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			alias_id = ?
		AND
			created_by = ?
	";
	$stmt = exec_query($query, array($alsId, $_SESSION['user_id']));

	if ($stmt->rowCount()) {
		$alsName = $stmt->fields['alias_name'];

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onBeforeDeleteDomainAlias,
			array('domainAliasId' => $alsId, 'domainAliasName' => $alsName)
		);

		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		/** @var $db iMSCP_Database */
		$db = iMSCP_Database::getInstance();

		try {
			$db->beginTransaction();

			// Delete any FTP account linked to $alsId or one of its subdomains - begin

			$query = "
				SELECT
					t1.groupname, t1.gid, t1.members
				FROM
					ftp_group AS t1
				LEFT JOIN
					domain_aliasses AS t3 ON(alias_id = ?)
				LEFT JOIN
					subdomain_alias AS t4 ON(t4.alias_id = t3.alias_id)
				LEFT JOIN
					ftp_users AS t2 ON(
						userid LIKE CONCAT('%@', t4.subdomain_alias_name, '.', t3.alias_name)
						OR
						userid LIKE CONCAT('%@', t3.alias_name)
					)
				WHERE
					t1.gid = t2.gid
				LIMIT
					1
			";
			$stmt = exec_query($query, $alsId);

			if($stmt->rowCount()) {
				$ftpGroupName = $stmt->fields['groupname'];
				$ftpGroupGid = $stmt->fields['gid'];
				$ftpMembers = preg_split('/,/', $stmt->fields['members'], -1, PREG_SPLIT_NO_EMPTY);

				$newFtpMembers = array();

				foreach($ftpMembers as $ftpMember) {
					if(!preg_match("/@(?:.+?\.)*$alsName$/", $ftpMember)) {
						$newFtpMembers[] = $ftpMember;
					}
				}

				if (!empty($newFtpMembers)) {
					exec_query(
						'UPDATE ftp_group SET members = ? WHERE gid = ?',
						array(implode(',', $newFtpMembers), $ftpGroupGid)
					);
				} else {
					exec_query('DELETE FROM ftp_group WHERE groupname = ?', $ftpGroupName);
					exec_query('DELETE FROM quotalimits WHERE name = ?', $ftpGroupName);
					exec_query('DELETE FROM quotatallies WHERE name = ?', $ftpGroupName);
				}
			}

			$stmt = exec_query(
				"
					DELETE
						ftp_users
					FROM
						ftp_users
					LEFT JOIN
						domain_aliasses AS t2 ON(alias_id = ?)
					LEFT JOIN
						subdomain_alias AS t3 ON(t3.alias_id = t2.alias_id)
					WHERE
						(
							userid LIKE CONCAT('%@', t3.subdomain_alias_name, '.', t2.alias_name)
						OR
							userid LIKE CONCAT('%@', t2.alias_name)
						)
				",
				$alsId
			);
			// Delete any FTP account linked to $alsId or one of its subdomains - ending

			// Delete any custom DNS and external mail server record which have $alsId as parent
			exec_query('DELETE FROM domain_dns WHERE alias_id = ?', $alsId);

			// Schedule deletion of any mail account, which are directly or indirectly linked to $alsId
			exec_query(
				"
					UPDATE
						mail_users
					SET
						status = ?
					WHERE
						(sub_id = ? AND mail_type LIKE ?)
					OR
					(
						sub_id IN (SELECT subdomain_alias_id FROM subdomain_alias WHERE alias_id = ?)
					AND
						mail_type LIKE ?
					)
				",
				array('todelete', $alsId, '%alias_%', $alsId, '%alssub_%')
			);

			# Schedule deletion of any SSL certificat linked to subdomain, which have $alsId as parent
			exec_query(
				'
					UPDATE
						ssl_certs
					SET
						status = ?
					WHERE
						domain_id IN (SELECT subdomain_alias_id FROM subdomain_alias WHERE alias_id = ?)
					AND
						domain_type = ?
				',
				array('todelete', 'alssub', $alsId)
			);

			# Schedule deletion of any SSL certificate linked to this domain alias
			exec_query(
				'UPDATE ssl_certs SET status = ? WHERE domain_id = ? and domain_type = ?',
				array('todelete', $alsId, 'als')
			);

			# Schedule deletion of any subdomain, which have $alsId alias as parent
			exec_query(
				'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE alias_id = ?',
				array('todelete', $alsId)
			);

			# Schedule domain alias deletion
			exec_query('UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?', array('todelete', $alsId));

			$db->commit();
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
			throw $e;
		}

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onAfterDeleteDomainAlias, array('domainAliasId' => $alsId, 'domainAliasName' => $alsName)
		);

		send_request();
		write_log("{$_SESSION['user_logged']}: deleted domain alias: $alsName", E_USER_NOTICE);
		set_page_message(tr('Domain alias successfully scheduled for deletion.'), 'success');

		redirectTo('alias.php');
	}
}

showBadRequestErrorPage();
