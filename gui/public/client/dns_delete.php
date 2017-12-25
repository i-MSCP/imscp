<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by i-MSCP Team
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

use iMSCP_Registry as Registry;

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);

if (!customerHasFeature('custom_dns_records') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
}

Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onBeforeDeleteCustomDNSrecord, [
    'id' => $dnsRecordId
]);

$dnsRecordId = intval($_GET['id']);
$stmt = exec_query(
    "
      UPDATE domain_dns
      JOIN domain USING(domain_id)
      SET domain_dns_status = ?
      WHERE domain_dns_id = ?
      AND domain_admin_id = ?
      AND owned_by = 'custom_dns_feature'
      AND domain_dns_status NOT IN('toadd', 'tochange', 'todelete')
    ",
    ['todelete', $dnsRecordId, $_SESSION['user_id']]
);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAfterDeleteCustomDNSrecord, [
    'id' => $dnsRecordId
]);

send_request();
write_log(sprintf('%s scheduled deletion of a custom DNS record', $_SESSION['user_logged']), E_USER_NOTICE);
set_page_message(tr('Custom DNS record successfully scheduled for deletion.'), 'success');
redirectTo('domains_manage.php');
