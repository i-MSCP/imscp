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

$reseller = $_SESSION['user_id'];

$theme_color = Config::get('USER_INITIAL_THEME');

if (isset($_GET['domain_id']))
	$del_id = $_GET['domain_id'];
else {
	set_page_message(tr('Wrong domain ID!'));
	header("Location: users.php");
	die();
}

/* check for domain owns */
$query = "SELECT `domain_id` FROM `domain` WHERE `domain_id` = ? AND `domain_created_id` = ?";
$res = exec_query($sql, $query, array($del_id, $reseller));
$data = $res->FetchRow();
if ($data['domain_id'] == 0) {
	set_page_message(tr('Wrong domain ID!'));
	header("Location: users.php");
	die();
}

/* check for mail acc in MAIN domain */
$query = "SELECT COUNT(`mail_id`) AS mailnum FROM `mail_users` WHERE `domain_id` = ?";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['mailnum'] > 0) {
	/* ERROR - we have mail acc in this domain */
	set_page_message(tr('Domain you are trying to remove has email accounts !<br> first remove them !'));
	header("Location: users.php");
	die();
}

/* check for ftp acc in MAIN domain */
$query = "SELECT COUNT(fg.`gid`) AS ftpnum FROM `ftp_group` fg, `domain` d WHERE d.`domain_id` = ? AND fg.`groupname` = d.`domain_name`";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['ftpnum'] > 0) {
	/* ERROR - we have ftp acc in this domain */
	set_page_message(tr('Domain you are trying to remove has FTP accounts !<br> first remove them !'));
	header("Location: users.php");
	die();
}

/* check for alias domains */
$query = "SELECT COUNT(`alias_id`) AS aliasnum FROM `domain_aliasses` WHERE `domain_id` = ?";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['aliasnum'] > 0) {
	/* ERROR - we have domain aliases for this domain */
	set_page_message(tr('Domain you are trying to remove has domain alias!<br> first remove them !'));
	header("Location: users.php");
	die();
}

/* check for subdomains */
$query = "SELECT COUNT(`subdomain_id`) AS subnum FROM `subdomain` WHERE `domain_id` = ?";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['subnum'] > 0) {
	/* ERROR - we have subdomain for this domain */
	set_page_message(tr('Domain you are trying to remove has subdomains accounts !<br> first remove them !'));
	header("Location: users.php");
	die();
}

substract_from_reseller_props($_SESSION['user_id'], $del_id);

$query = "UPDATE `domain` SET `domain_status` = 'delete' WHERE `domain_id` = ?";
$res = exec_query($sql, $query, array($del_id));
send_request();

/* delete admin of this domain */
$query = "SELECT `domain_admin_id`, `domain_name` FROM `domain` WHERE `domain_id` = ?";
$res = exec_query($sql, $query, array($del_id));
$dat = $res->FetchRow();

$query = "DELETE FROM `admin` WHERE `admin_id` = ?";
$res = exec_query($sql, $query, array($dat['domain_admin_id']));

/* delete the quota section */
$query = "DELETE FROM `quotalimits` WHERE `name` = ?";
$res = exec_query($sql, $query, array($dat['domain_admin_id']));


write_log($_SESSION['user_logged'] .": deletes domain " . $dat['domain_name']);

$_SESSION['ddel'] = '_yes_';
header("Location: users.php");
die();

// Function declaration

function substract_from_reseller_props($reseller_id, $domain_id) {
	list($rdmn_current, $rdmn_max,
		$rsub_current, $rsub_max,
		$rals_current, $rals_max,
		$rmail_current, $rmail_max,
		$rftp_current, $rftp_max,
		$rsql_db_current, $rsql_db_max,
		$rsql_user_current, $rsql_user_max,
		$rtraff_current, $rtraff_max,
		$rdisk_current, $rdisk_max
	) = generate_reseller_props($reseller_id);

	list($sub_current, $sub_max,
		$als_current, $als_max,
		$mail_current, $mail_max,
		$ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max,
		$sql_user_current, $sql_user_max,
		$traff_max, $disk_max
	) = generate_user_props($domain_id);

	$rdmn_current -= 1;

	if ($sub_max != -1) {
		$rsub_current -= $sub_max;
	}

	if ($als_max != -1) {
		$rals_current -= $als_max;
	}

	$rmail_current -= $mail_max;

	$rftp_current -= $ftp_max;

	if ($sql_db_max != -1) {
		$rsql_db_current -= $sql_db_max;
	}

	if ($sql_user_max != -1) {
		$rsql_user_current -= $sql_user_max;
	}

	$rtraff_current -= $traff_max;

	$rdisk_current -= $disk_max;

	$rprops = "$rdmn_current;$rdmn_max;";
	$rprops .= "$rsub_current;$rsub_max;";
	$rprops .= "$rals_current;$rals_max;";
	$rprops .= "$rmail_current;$rmail_max;";
	$rprops .= "$rftp_current;$rftp_max;";
	$rprops .= "$rsql_db_current;$rsql_db_max;";
	$rprops .= "$rsql_user_current;$rsql_user_max;";
	$rprops .= "$rtraff_current;$rtraff_max;";
	$rprops .= "$rdisk_current;$rdisk_max;";

	update_reseller_props($reseller_id, $rprops);
}
