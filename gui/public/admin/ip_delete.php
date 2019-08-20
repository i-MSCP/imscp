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

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

if (!isset($_GET['ip_id'])) {
    showBadRequestErrorPage();
}

$ipId = intval($_GET['ip_id']);

$stmt = exec_query('SELECT ip_number FROM server_ips WHERE ip_id = ?', $ipId);
if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetchRow();
$ipAddr = $row['ip_number'];

$stmt = execute_query('SELECT reseller_ips FROM reseller_props');
while ($row = $stmt->fetchRow()) {
    if (in_array($ipId, explode(';', $row['reseller_ips'], -1))) {
        set_page_message(tr('You cannot remove an IP that is assigned to a reseller.'), 'error');
        redirectTo('ip_manage.php');
    }
}

$stmt = execute_query('SELECT count(*) cnt FROM server_ips');
$row = $stmt->fetchRow();

if ($row ['cnt'] < 2) {
    set_page_message(tr('You cannot delete the last active IP address.'), 'error');
    redirectTo('ip_manage.php');
}

EventAggregator::getInstance()->dispatch(Events::onDeleteIpAddr);
exec_query('UPDATE server_ips SET ip_status = ? WHERE ip_id = ?', ['todelete', $ipId]);
send_request();
write_log(sprintf("An IP address (%s) has been deleted by %s", $ipAddr, $_SESSION['user_logged']), E_USER_NOTICE);
set_page_message(tr('IP address successfully scheduled for deletion.'), 'success');
redirectTo('ip_manage.php');
