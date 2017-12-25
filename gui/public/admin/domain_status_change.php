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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2018 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

if (isset($_GET['domain_id'])) {
    $domainId = intval($_GET['domain_id']);
    $stmt = exec_query('SELECT domain_admin_id, domain_status FROM domain WHERE domain_id = ?', [$domainId]);

    if ($stmt->rowCount()) {
        $row = $stmt->fetch();

        if ($row['domain_status'] == 'ok') {
            change_domain_status($row['domain_admin_id'], 'deactivate');
        } elseif ($row['domain_status'] == 'disabled') {
            change_domain_status($row['domain_admin_id'], 'activate');
        } else {
            showBadRequestErrorPage();
        }

        redirectTo('users.php');
    }
}

showBadRequestErrorPage();
