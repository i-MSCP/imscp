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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Schedule backup restoration.
 *
 * @param int $userId Customer unique identifier
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function scheduleBackupRestoration($userId)
{
    exec_query("UPDATE domain SET domain_status = ? WHERE domain_admin_id = ?", ['torestore', $userId]);
    send_request();
    write_log(sprintf('A backup restore has been scheduled by %s.', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Backup has been successfully scheduled for restoration.'), 'success');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('backup') or showBadRequestErrorPage();

if (!empty($_POST)) {
    scheduleBackupRestoration($_SESSION['user_id']);
    redirectTo('backup.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/backup.tpl',
    'page_message' => 'layout'
]);

$cfg = iMSCP_Registry::get('config');
$algo = strtolower($cfg['BACKUP_COMPRESS_ALGORITHM']);

if ($algo == 'no') {
    $name = 'web-backup-.*-%Y.%m.%d-%H-%M.tar';
} elseif ($algo == 'gzip' || $algo == 'pigz') {
    $name = 'web-backup-.*-%Y.%m.%d-%H-%M.tar.gz';
} elseif ($algo == 'bzip2' || $algo == 'pbzip2') {
    $name = 'web-backup-.*-%Y.%m.%d-%H-%M.tar.bz2';
} elseif ($algo == 'lzma') {
    $name = 'web-backup-.*-%Y.%m.%d-%H-%M.tar.lzma';
} elseif ($algo == 'xz') {
    $name = 'web-backup-.*-%Y.%m.%d-%H-%M.tar.xz';
} else {
    $name = NULL;
}

$tpl->assign([
    'TR_PAGE_TITLE'         => tr('Client / Webtools / Daily Backup'),
    'TR_BACKUP'             => tr('Backup'),
    'TR_DAILY_BACKUP'       => tr('Daily backup'),
    'TR_DOWNLOAD_DIRECTION' => tr("Instructions to download today's backup"),
    'TR_FTP_LOG_ON'         => tr('Login with your FTP account'),
    'TR_SWITCH_TO_BACKUP'   => tr('Switch to the backups directory'),
    'TR_DOWNLOAD_FILE'      => tr('Download the archives stored in this directory'),
    'TR_USUALY_NAMED'       => is_null($name) ? '' : tr('(usually named') . ' ' . tohtml($name) . ')',
    'TR_RESTORE_BACKUP'     => tr('Restore backup'),
    'TR_RESTORE_DIRECTIONS' => tr('Click the Restore button and the system will restore the last daily backup'),
    'TR_RESTORE'            => tr('Restore'),
    'TR_CONFIRM_MESSAGE'    => tr('Are you sure you want to restore the backup?')
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
