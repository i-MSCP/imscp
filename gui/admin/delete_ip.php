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

/* do we have a proper delete_id ? */

if (!isset($_GET['delete_id'])) {

    header( "Location: ip_manage.php" );

    die();
}

if (!is_numeric($_GET['delete_id'])) {

    header( "Location: ip_manage.php" );

    die();

}

$delete_id = $_GET['delete_id'];

/* check for domain that user this ip */

$query = <<<SQL_QUERY
    select
        count(domain_id) as dcnt
    from
        domain
    where
        domain_ip_id=?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_id));

if ($rs -> fields['dcnt'] > 0) {
    /* ERR - we have domain that use this ip */

    set_page_message(tr('Error we have domain that use this IP!'));

    header( "Location: ip_manage.php" );
    die();
}


// check if the IP is assignet to reseller
    $query = <<<SQL_QUERY
        select
            reseller_ips
        from
            reseller_props
SQL_QUERY;

    $res = exec_query($sql, $query, array());
    $data = $res -> FetchRow();
    $reseller_ips = $data['reseller_ips'];

    while ($data = $res -> FetchRow()) {
      if (preg_match("/$delete_id;/", $reseller_ips) == 1) {
        set_page_message(tr('Error we have reseller that use this IP!'));
        header( "Location: ip_manage.php" );
        die();
      }
    }


$query = <<<SQL_QUERY
    select
        *
    from
        server_ips
    where
        ip_id=?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_id));

$user_logged= $_SESSION['user_logged'];

$ip_number = $rs->fields['ip_number'];

write_log("$user_logged: delete IP4 addres $ip_number");

/* delete it ! */
$query = <<<SQL_QUERY
    delete from
        server_ips
    where
        ip_id=?
SQL_QUERY;

$rs = exec_query($sql, $query, array($delete_id));

set_page_message(tr('IP was deleted!'));

header( "Location: ip_manage.php" );
die();

?>
