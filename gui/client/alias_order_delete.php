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

$theme_color = Config::get('USER_INITIAL_THEME');

if(isset($_GET['del_id']) && !empty($_GET['del_id']))
	$del_id = $_GET['del_id'];
else {
	$_SESSION['orderaldel'] = '_no_';
	header("Location: domains_manage.php");
	die();
}

$query = "DELETE FROM domain_aliasses WHERE alias_id='".$del_id."'";
$rs = exec_query($sql, $query);
header("Location: domains_manage.php");
die();

?>