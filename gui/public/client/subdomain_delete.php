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
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

if (!customerHasFeature('subdomains') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
}

ignore_user_abort(true);
set_time_limit(0);

$id = clean_input($_GET['id']);

$stmt = exec_query(
    "
        SELECT t1.domain_id, CONCAT(t1.subdomain_name, '.', t2.domain_name) AS subdomain_name, t1.subdomain_mount
        FROM subdomain AS t1
        JOIN domain AS t2 USING(domain_id)
        WHERE t1.subdomain_id = ?
        AND t2.domain_admin_id = ?
    ",
    [$id, $_SESSION['user_id']]
);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

$stmt = exec_query(
    'SELECT mail_id FROM mail_users WHERE (mail_type LIKE ? OR mail_type = ?) AND sub_id = ? LIMIT 1',
    [$id, MT_SUBDOM_MAIL . '%', MT_SUBDOM_FORWARD]
);

if ($stmt->rowCount()) {
    set_page_message(tr('Subdomain you are trying to remove has mail accounts. Remove them first.'), 'error');
    redirectTo('domains_manage.php');
}

$stmt = exec_query('SELECT userid FROM ftp_users WHERE userid LIKE ? LIMIT 1', "%@{$row['subdomain_name']}");
if ($stmt->rowCount()) {
    set_page_message(tr('The subdomain you are trying to remove has FTP accounts. Remove them first.'), 'error');
    redirectTo('domains_manage.php');
}

$db = iMSCP_Database::getInstance();

try {
    $db->beginTransaction();

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteSubdomain, [
        'subdomainId'   => $id,
        'subdomainName' => $row['subdomain_name'],
        'type'          => 'sub'
    ]);

    exec_query("DELETE FROM php_ini WHERE domain_id = ? AND domain_type = 'sub'", $id);
    exec_query("UPDATE ssl_certs SET status = 'todelete' WHERE domain_id = ? AND domain_type = 'sub'", $id);
    exec_query(
        "UPDATE htaccess SET status = 'todelete' WHERE dmn_id = ? AND path LIKE ?",
        [$row['domain_id'], utils_normalizePath($row['subdomain_mount']) . '%']
    );
    exec_query("UPDATE subdomain SET subdomain_status = 'todelete' WHERE subdomain_id = ?", $id);

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterDeleteSubdomain, [
        'subdomainId'   => $id,
        'subdomainName' => $row['subdomain_name'],
        'type'          => 'sub'
    ]);

    $db->commit();
    send_request();
    write_log(
        sprintf("%s scheduled deletion of the %s subdomain", decode_idna($_SESSION['user_logged']), $row['subdomain_name']),
        E_USER_NOTICE
    );
    set_page_message(tr('Subdomain scheduled for deletion.'), 'success');
} catch (iMSCP_Exception $e) {
    $db->rollBack();
    write_log(sprintf('System was unable to remove a subdomain: %s', $e->getMessage()), E_ERROR);
    set_page_message(tr("Couldn't delete subdomain. An unexpected error occurred."), 'error');
}

redirectTo('domains_manage.php');
