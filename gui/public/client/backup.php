<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

/**
 * Schedule backup restoration.
 *
 * @param int $userId Customer unique identifier
 * @return void
 */
function scheduleBackupRestoration($userId)
{
    exec_query("UPDATE domain SET domain_status = ? WHERE domain_admin_id = ?", ['torestore', $userId]);
    send_request();
    write_log(sprintf('A backup restore has been scheduled by %s.', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Backup has been successfully scheduled for restoration.'), 'success');
}

require_once 'imscp-lib.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);
customerHasFeature('backup') or showBadRequestErrorPage();

if (!empty($_POST)) {
    scheduleBackupRestoration($_SESSION['user_id']);
    redirectTo('backup.php');
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/backup.tpl',
    'page_message' => 'layout'
]);

$cfg = Registry::get('config');
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
EventAggregator::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
