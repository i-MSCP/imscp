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

/* Do we have a proper delete_id? */
if (!isset($_GET['delete_lang'])) {
	user_goto('multilanguage.php');
}

$delete_lang = $_GET['delete_lang'];

/* ERROR - we have domains that use this IP */
if ($delete_lang == Config::get('USER_INITIAL_LANG')) {
	set_page_message('Error we can\'t delete system default language!');

	user_goto('multilanguage.php');
}

/* check if someone still uses that lang */
$query = <<<SQL_QUERY
	SELECT
		*
	FROM
		`user_gui_props`
	WHERE
		`lang` = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_lang));

/* ERROR - we have domains that use this IP */
if ($rs->RecordCount () > 0) {
	set_page_message('Error we have user that uses that language!');

	user_goto('multilanguage.php');
}

$query = "DROP TABLE $delete_lang";

$rs = exec_query($sql, $query, array());

write_log(sprintf("%s removed language: %s", $_SESSION['user_logged'], $delete_lang));

set_page_message(tr('Language was removed!'));

user_goto('multilanguage.php');
