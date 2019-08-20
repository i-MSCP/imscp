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
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 * PhpIncludeInspection
 */

use iMSCP\Database\DatabaseMySQL;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Exception\Exception;
use iMSCP\Registry;

require 'imscp-lib.php';

check_login('reseller');
EventAggregator::getInstance()->dispatch(Events::onResellerScriptStart);
resellerHasFeature('domain_aliases') or showBadRequestErrorPage();

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['del_id'])) {
    $id = intval($_GET['del_id']);
    $stmt = exec_query(
        '
            SELECT alias_id
            FROM domain_aliasses
            JOIN domain USING(domain_id)
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE alias_id = ?
            AND created_by = ?
        ',
        [$id, $_SESSION['user_id']]
    );
    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $db = DatabaseMySQL::getInstance();

    try {
        $db->beginTransaction();
        exec_query('DELETE FROM php_ini WHERE domain_id = ? AND domain_type = ?', [$id, 'als']);
        exec_query('DELETE FROM domain_aliasses WHERE alias_id = ? AND alias_status = ?', [$id, 'ordered']);
        $db->commit();
        write_log(sprintf('An alias order has been deleted by %s.', $_SESSION['user_logged']), E_USER_NOTICE);
        set_page_message('Alias order successfully deleted.', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        write_log(sprintf('System was unable to remove alias order: %s', $e->getMessage()), E_USER_ERROR);
        set_page_message('Could not remove alias order. An unexpected error occurred.');
    }

    redirectTo('alias.php');
}

if (!isset($_GET['action']) || $_GET['action'] !== 'activate' || !isset($_GET['act_id'])) {
    showBadRequestErrorPage();
}

$id = intval($_GET['act_id']);
$stmt = exec_query(
    '
        SELECT alias_name, domain_id, email
        FROM domain_aliasses
        JOIN domain USING(domain_id)
        JOIN admin ON(admin_id = domain_admin_id)
        WHERE alias_id = ?
        AND alias_status = ?
        AND created_by = ?
    ',
    [$id, 'ordered', $_SESSION['user_id']]
);
if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetchRow();
$db = DatabaseMySQL::getInstance();

try {
    $db->beginTransaction();

    EventAggregator::getInstance()->dispatch(Events::onBeforeAddDomainAlias, [
        'domainId'        => $row['domain_id'],
        'domainAliasName' => $row['alias_name']
    ]);

    exec_query('UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?', ['toadd', $id]);

    $cfg = Registry::get('config');

    if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
        createDefaultMailAccounts($row['domain_id'], $row['email'], $row['alias_name'], MT_ALIAS_FORWARD, $id);
    }

    EventAggregator::getInstance()->dispatch(Events::onAfterAddDomainAlias, [
        'domainId'        => $row['domain_id'],
        'domainAliasName' => $row['alias_name'],
        'domainAliasId'   => $id
    ]);

    $db->commit();
    send_request();
    write_log(sprintf('An alias order has been processed by %s.', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Order successfully processed.'), 'success');
} catch (Exception $e) {
    $db->rollBack();
    write_log(sprintf('System was unable to process alias order: %s', $e->getMessage()), E_USER_ERROR);
    set_page_message('Could not process alias order. An unexpected error occurred.', 'error');
}

redirectTo('alias.php');
