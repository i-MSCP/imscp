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

require 'imscp-lib.php';

check_login('reseller');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onResellerScriptStart);

if (!isset($_GET['user_id'])) {
    showBadRequestErrorPage();
}

$customerId = intval($_GET['user_id']);

try {
    if (!deleteCustomer($customerId, true)) {
        showBadRequestErrorPage();
    }

    set_page_message(tr('Customer account successfully scheduled for deletion.'), 'success');
    write_log(sprintf('%s scheduled deletion of the customer account with ID %d', $_SESSION['user_logged'], $customerId), E_USER_NOTICE);
} catch (iMSCP_Exception $e) {
    set_page_message(tr('Unable to schedule deletion of the customer account. A message has been sent to the administrator.'), 'error');
    write_log(sprintf("System was unable to schedule deletion of the customer account with ID %s. Message was: %s", $customerId, $e->getMessage()), E_USER_ERROR);
}

redirectTo('users.php');
