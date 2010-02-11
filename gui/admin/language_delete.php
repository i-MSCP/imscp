<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
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
