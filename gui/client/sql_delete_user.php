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

if (isset($_GET['id'])) {
    $db_user_id = $_GET['id'];
} else {
    user_goto('sql_manage.php');
}

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

check_usr_sql_perms($sql, $db_user_id);

sql_delete_user($sql, $dmn_id, $db_user_id);

write_log($_SESSION['user_logged'].": deletes SQL user ".$db_user_id."!");
set_page_message(tr('SQL user was removed successfully!'));

user_goto('sql_manage.php');

?>