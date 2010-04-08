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

/**
 * @todo use db prepared statements
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$theme_color = Config::getInstance()->get('USER_INITIAL_THEME');

if (isset($_GET['del_id']) && !empty($_GET['del_id'])) {
	$del_id = $_GET['del_id'];
} else {
	$_SESSION['orderaldel'] = '_no_';
	user_goto('domains_manage.php');
}

$query = "DELETE FROM `domain_aliasses` WHERE `alias_id` = '" . $del_id . "'";
$rs = exec_query($sql, $query);

user_goto('domains_manage.php');
