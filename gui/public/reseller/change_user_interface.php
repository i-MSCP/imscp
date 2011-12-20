<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-msCP | http://i-mscp.net
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

// Check for login
check_login(__FILE__);

// logged from admin to reseller - switch back to admin
if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id']) && isset($_GET['action']) &&
	$_GET['action'] == 'go_back') {

	change_user_interface($_SESSION['user_id'], $_SESSION['logged_from_id']);

// Switch to customer
} elseif (isset($_SESSION['user_id']) && isset($_GET['to_id'])) {

	$to_id = $_GET['to_id'];

	// admin logged as reseller:
	if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
		$from_id = $_SESSION['logged_from_id'];
	} else { // reseller:
		$from_id = $_SESSION['user_id'];

		if (who_owns_this($to_id, 'client') != $from_id) {
			set_page_message(tr('User does not exist or you do not have permission to access this interface.'), 'error');
			redirectTo('users.php?psi=last');
		}
	}

    // Remember some data
    if (isset($_SESSION['search_for'])) {
        $_SESSION['uistack'] = array('search_for' => $_SESSION['search_for']);

        if (isset($_SESSION['search_status'])) {
            $_SESSION['uistack']['search_status'] = $_SESSION['search_status'];
        }

        if (isset($_SESSION['search_common'])) {
            $_SESSION['uistack']['search_common'] = $_SESSION['search_common'];
        }

        if (isset($_SESSION['search_page'])) {
            $_SESSION['uistack']['search_page'] = $_SESSION['search_page'];
        }
    }

	change_user_interface($from_id, $to_id);

} else {
	redirectTo('index.php');
}
