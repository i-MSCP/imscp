<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



include '../include/vhcs-lib.php';

check_login();

$reseller = $_SESSION['user_id'];

$theme_color = $cfg['USER_INITIAL_THEME'];

if(isset($_GET['domain_id']))
	$del_id = $_GET['domain_id'];
else{
	set_page_message(tr('Wrong domain ID!'));
	Header("Location: users.php");
	die();
}

/* check for domain ouwns */
$query = "select domain_id from domain where domain_id=? and domain_created_id=?";
$res = exec_query($sql, $query, array($del_id, $reseller));
$data = $res->FetchRow();
if ($data['domain_id'] == 0 ) {
	set_page_message(tr('Wrong domain ID!'));
    header( "Location: users.php" );
    die();

}

/* check for mail acc in MAIN domain */
$query = "select count(mail_id) as mailnum from mail_users where domain_id=?";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['mailnum'] > 0 ) {
    /* ERR - we have mail acc in this domain */
    set_page_message(tr('Domain you are trying to remove has email accounts !<br> first remove them !'));
    header( "Location: users.php" );
    die();
}

/* check for ftp acc in MAIN domain */
$query = "select count(fg.gid) as ftpnum from ftp_group fg,domain d where d.domain_id=? and fg.groupname=d.domain_name";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['ftpnum'] > 0) {
    /* ERR -  we have ftp acc in this domain */
    set_page_message(tr('Domain you are trying to remove has FTP accounts !<br> first remove them !'));

    header( "Location: users.php");
    die();
}


/* check for alias domains */
$query = "select count(alias_id) as aliasnum from domain_aliasses where domain_id=?";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['aliasnum'] > 0 ) {
    /* ERR -  we have domain aliases for this domain */
    set_page_message(tr('Domain you are trying to remove has domain alias!<br> first remove them !'));
    header( "Location: users.php" );
    die();
}

/* check for subdomains */
$query = "select count(subdomain_id) as subnum from subdomain where domain_id=?";
$res = exec_query($sql, $query, array($del_id));
$data = $res->FetchRow();
if ($data['subnum'] > 0 ) {
    /* ERR - we have subdomain for this domain */
   set_page_message(tr('Domain you are trying to remove has subdomains accounts !<br> first remove them !'));
    header( "Location: users.php" );
    die();
}


substract_from_reseller_props($_SESSION['user_id'], $del_id);

$query = "update domain set domain_status='delete' where domain_id=?";
$res = exec_query($sql, $query, array($del_id));
send_request();

/* delete admin of this domain */
$query = "select domain_admin_id,domain_name from domain where domain_id=?";
$res = exec_query($sql, $query, array($del_id));
$dat = $res->FetchRow();

$query = "delete from admin where admin_id=?";
$res = exec_query($sql, $query, array($dat[domain_admin_id]));

/* delete the quota section */
$query = "delete from quotalimits where name=?";
$res = exec_query($sql, $query, array($dat[domain_admin_id]));

$admin_login = $_SESSION['user_logged'];
write_log("$admin_login: delete domain ".$dat['domain_name']);

$_SESSION['ddel'] = '_yes_';
header( "Location: users.php" );
die();

//
// Function declaration
//

function substract_from_reseller_props($reseller_id, $domain_id) {

    list (
           $rdmn_current, $rdmn_max,
           $rsub_current, $rsub_max,
           $rals_current, $rals_max,
           $rmail_current, $rmail_max,
           $rftp_current, $rftp_max,
           $rsql_db_current, $rsql_db_max,
           $rsql_user_current, $rsql_user_max,
           $rtraff_current, $rtraff_max,
           $rdisk_current, $rdisk_max
         ) =  generate_reseller_props($reseller_id);

    list (
           $sub_current, $sub_max,
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

    $rprops  = "$rdmn_current;$rdmn_max;";
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

?>
