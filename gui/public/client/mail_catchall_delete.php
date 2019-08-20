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

use iMSCP\Registry;
use iMSCP\Event\EventAggregator;
use iMSCP\Config\DbConfig;
use iMSCP\Config\FileConfig;
use iMSCP\Config\ArrayConfig;
use iMSCP\Services;
use iMSCP\PhpEditor;
use iMSCP\Database\DatabaseMySQL;
use iMSCP\Exception\Exception;
use iMSCP\Database\DatabaseException;
use Zend_Form;
use iMSCP\TemplateEngine;
use iMSCP\Event\Events;
use iMSCP\Event\EventDescription;
use iMSCP\Uri\UriRedirect;
use iMSCP\Uri\UriException;
use iMSCP\Authentication\AuthService;
use iMSCP\VirtualFileSystem;
use Net_DNS2_Resolver;
use Net_DNS2_Exception;
use iMSCP\Crypt;

require_once 'imscp-lib.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);
customerHasFeature('mail') or showBadRequestErrorPage();

if (!isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$catchallId = intval($_GET['id']);
$stmt = exec_query(
    '
        SELECT COUNT(mail_id) FROM mail_users
        JOIN domain USING(domain_id)
        WHERE mail_id = ?
        AND domain_admin_id = ?
    ',
    [$catchallId, $_SESSION['user_id']]
);

if ($stmt->fetchRow(PDO::FETCH_COLUMN) == 0) {
    showBadRequestErrorPage();
}

EventAggregator::getInstance()->dispatch(Events::onBeforeDeleteMailCatchall, [
    'mailCatchallId' => $catchallId
]);
exec_query("UPDATE mail_users SET status = 'todelete' WHERE mail_id = ?", $catchallId);
EventAggregator::getInstance()->dispatch(Events::onAfterDeleteMailCatchall, [
    'mailCatchallId' => $catchallId
]);
send_request();
write_log(sprintf('A catch-all account has been deleted by %s', $_SESSION['user_logged']), E_USER_NOTICE);
set_page_message(tr('Catch-all account successfully scheduled for deletion.'), 'success');
redirectTo('mail_catchall.php');
