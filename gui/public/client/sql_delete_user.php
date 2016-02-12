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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2016 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (!customerHasFeature('sql') || !isset($_GET['id'])) {
    showBadRequestErrorPage();
}

$userId = intval($_GET['id']);

if (!sql_delete_user(get_user_domain_id($_SESSION['user_id']), $userId)) {
    write_log(sprintf('Could not delete SQL user with ID %d. An unexpected error occurred.', $userId), E_USER_ERROR);
    set_page_message(tr('Could not delete SQL user. An unexpected error occurred.'), 'error');
    redirectTo('sql_manage.php');
}

set_page_message(tr('SQL user successfully deleted.'), 'success');
write_log(sprintf('%s deleted SQL user with ID %d', decode_idna($_SESSION['user_logged']), $userId), E_USER_NOTICE);
redirectTo('sql_manage.php');
