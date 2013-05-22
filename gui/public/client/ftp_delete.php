<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP team
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
 * @package	    iMSCP_Core
 * @subpackage  Client_Ftp
 * @copyright   2010-2013 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasFeature('ftp') && isset($_GET['id'])) {
	$ftpUserId = clean_input($_GET['id']);

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteFtp, array('ftpUserId' => $ftpUserId));

	$query = "SELECT `admin_id`, `gid` FROM `ftp_users` WHERE `userid` = ? AND `admin_id` = ?";
	$stmt = exec_query($query, array($ftpUserId, $_SESSION['user_id']));

	if (!$stmt->rowCount()) {
		showBadRequestErrorPage();
	}

	$ftpUserGid = $stmt->fields['gid'];

	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	try {
		$db->beginTransaction();

		$stmt = exec_query("SELECT `groupname`, `members` FROM `ftp_group` WHERE `gid` = ?", $ftpUserGid);

		if ($stmt->rowCount()) {
			$groupName = $stmt->fields['groupname'];
			$members = preg_split(',', $stmt->fields['members'], -1, PREG_SPLIT_NO_EMPTY);
			$member = array_search($ftpUserId, $members);

			if (false !== $member) {
				unset($members[$member]);

				if (!empty($members)) {
					exec_query(
						"UPDATE `ftp_group` SET `members` = ? WHERE `gid` = ?",
						array(implode(',', $members), $ftpUserGid)
					);
				} else {
					exec_query('DELETE FROM `ftp_group` WHERE `groupname` = ?', $groupName);
					exec_query('DELETE FROM `quotalimits` WHERE `name` = ?', $groupName);
					exec_query('DELETE FROM `quotatallies` WHERE `name` = ?', $groupName);
				}
			}
		}

		exec_query('DELETE FROM `ftp_users` WHERE `userid` = ?', $ftpUserId);

		$db->commit();

		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterDeleteFtp, array('ftpUserId' => $ftpUserId));

		write_log(sprintf("%s: deleted FTP account: %s", $_SESSION['user_logged'], $ftpUserId), E_USER_NOTICE);
		set_page_message(tr('FTP account successfully deleted.'), 'success');
	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
	}

	redirectTo('ftp_accounts.php');
}

showBadRequestErrorPage();
