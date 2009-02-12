<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
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

$theme_color = Config::get('USER_INITIAL_THEME');

if(isset($_GET['action']) && $_GET['action'] === "delete") {

	if(isset($_GET['del_id']) && !empty($_GET['del_id']))
		$del_id = $_GET['del_id'];
	else{
		$_SESSION['orderaldel'] = '_no_';
		header("Location: alias.php");
		die();
	}

	$query = "DELETE FROM domain_aliasses WHERE alias_id=?";
	$rs = exec_query($sql, $query, $del_id);

	// delete "ordered"/pending email accounts
	$domain_id = who_owns_this($del_id, 'als_id', true);
	$query = "DELETE FROM mail_users WHERE sub_id=? AND domain_id = ? AND status=? AND mail_type LIKE 'alias%'";
	$rs = exec_query($sql, $query, array($del_id, $domain_id, Config::get('ITEM_ORDERED_STATUS')));

	header("Location: alias.php");
	die();

} else if (isset($_GET['action']) && $_GET['action'] === "activate") {

	if(isset($_GET['act_id']) && !empty($_GET['act_id']))
		$act_id = $_GET['act_id'];
	else{
		$_SESSION['orderalact'] = '_no_';
		header("Location: alias.php");
		die();
	}
	$query = "SELECT alias_name FROM domain_aliasses WHERE alias_id=?";
	$rs = exec_query($sql, $query, $act_id);
	if ($rs -> RecordCount() == 0) {
		header('Location: alias.php');
		die();
	}
	$alias_name = $rs -> fields['alias_name'];

	$query = "UPDATE domain_aliasses SET alias_status='toadd' WHERE alias_id=?";
	$rs = exec_query($sql, $query, $act_id);

	$domain_id = who_owns_this($act_id, 'als_id', true);
	$query = 'SELECT `email` FROM `admin`, `domain` WHERE `admin`.`admin_id` = `domain`.`domain_admin_id` AND `domain`.`domain_id`= ?';
	$rs = exec_query($sql, $query, $domain_id);
	if ($rs -> RecordCount() == 0) {
		header('Location: alias.php');
		die();
	}
	$user_email = $rs -> fields['email'];
	// Create the 3 default addresses if wanted
	if (Config::get('CREATE_DEFAULT_EMAIL_ADDRESSES')) client_mail_add_default_accounts($domain_id, $user_email, $alias_name, 'alias', $act_id);

	// enable "ordered"/pending email accounts
// ??? are there pending mail_addresses ???, joximu
	$query = "UPDATE mail_users SET status=? WHERE sub_id=? AND domain_id = ? AND status=? AND mail_type LIKE 'alias%'";
	$rs = exec_query($sql, $query, array(Config::get('ITEM_ADD_STATUS'), $act_id, $domain_id, Config::get('ITEM_ORDERED_STATUS')));

	send_request();

	$admin_login = $_SESSION['user_logged'];

	write_log("$admin_login: domain alias activated: $alias_name.");

	set_page_message(tr('Alias scheduled for activation!'));

	$_SESSION['orderalact'] = '_yes_';
	header("Location: alias.php");
	die();

} else {
	header("Location: alias.php");
	die();
}

?>