<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
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
	user_goto('manage_sql.php');
}

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

check_usr_sql_perms($sql, $db_user_id);

$query = <<<SQL_QUERY
        select
            sqlu_name, sqlu_pass
        from
            sql_user
        where
            sqlu_id = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($db_user_id));

$_SESSION['PMA_single_signon_user'] = $rs->fields['sqlu_name'];
$_SESSION['PMA_single_signon_password'] = $rs->fields['sqlu_pass'];
$_SESSION['PMA_single_signon_host'] = "localhost"; // pma >= 2.11
session_write_close();

user_goto($cfg['PMA_PATH']);

if ($cfg['DUMP_GUI_DEBUG'])
	dump_gui_debug();

unset_messages();

?>