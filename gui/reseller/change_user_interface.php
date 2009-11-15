<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

// let's back to admin interface - am I admin or what ? :-)

if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])
	&& isset($_GET['action']) && $_GET['action'] == "go_back") {
	change_user_interface($_SESSION['user_id'], $_SESSION['logged_from_id']);
} else if (isset($_SESSION['user_id']) && isset($_GET['to_id'])) {

	$to_id = $_GET['to_id'];

	// admin logged as reseller:
	if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
		$from_id = $_SESSION['logged_from_id'];
	} else { // reseller:

		$from_id = $_SESSION['user_id'];

		if (who_owns_this($to_id, 'client') != $from_id) {

			set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

			user_goto('users.php');
		}

	}

	change_user_interface($from_id, $to_id);

} else {
	user_goto('index.php');
}
