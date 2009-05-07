<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
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
