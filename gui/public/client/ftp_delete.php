<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
EventsManager::getInstance()->dispatch(Events::onClientScriptStart);

if (!customerHasFeature('ftp')
    || !isset($_GET['id'])
) {
    showBadRequestErrorPage();
}

$userid = clean_input($_GET['id']);

$stmt = exec_query(
    'SELECT admin_name as groupname FROM ftp_users JOIN admin USING(admin_id) WHERE userid = ? AND admin_id = ?',
    [$userid, $_SESSION['user_id']]
);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetchRow();
$groupname = $row['groupname'];

$db = iMSCP_Database::getInstance();

try {
    $db->beginTransaction();

    EventsManager::getInstance()->dispatch(Events::onBeforeDeleteFtp, ['ftpUserId' => $userid]);

    $stmt = exec_query('SELECT members FROM ftp_group WHERE groupname = ?', $groupname);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow();
        $members = preg_split('/,/', $row['members'], -1, PREG_SPLIT_NO_EMPTY);
        $member = array_search($userid, $members);

        if (false !== $member) {
            unset($members[$member]);

            if (empty($members)) {
                exec_query('DELETE FROM ftp_group WHERE groupname = ?', $groupname);
                exec_query('DELETE FROM quotalimits WHERE name = ?', $groupname);
                exec_query('DELETE FROM quotatallies WHERE name = ?', $groupname);
            } else {
                exec_query('UPDATE ftp_group SET members = ? WHERE groupname = ?', [
                    implode(',', $members), $groupname]
                );
            }
        }
    }

    exec_query("UPDATE ftp_users SET status = 'todelete' WHERE userid = ?", $userid);

    $cfg = Registry::get('config');

    if (isset($cfg['FILEMANAGER_PACKAGE']) && $cfg['FILEMANAGER_PACKAGE'] == 'Pydio') {
        $userPrefDir = $cfg['GUI_PUBLIC_DIR'] . '/tools/ftp/data/plugins/auth.serial/' . $userid;
        if (is_dir($userPrefDir)) {
            utils_removeDir($userPrefDir);
        }
    }

    EventsManager::getInstance()->dispatch(Events::onAfterDeleteFtp, ['ftpUserId' => $userid]);

    $db->commit();
    send_request();
    write_log(sprintf('An FTP account has been deleted by %s', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('FTP account successfully deleted.'), 'success');
} catch (iMSCPException $e) {
    $db->rollBack();
    throw $e;
}

redirectTo('ftp_accounts.php');
