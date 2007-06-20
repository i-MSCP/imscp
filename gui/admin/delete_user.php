<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

require '../include/ispcp-lib.php';

check_login(__FILE__);

/* do we have a proper delete_id ? */

if (!isset($_GET['delete_id']) or !is_numeric($_GET['delete_id'])) {
  header( "Location: manage_users.php" );
  die();
}

$delete_id = $_GET['delete_id'];

$query = <<<SQL_QUERY
    select
        admin_type
    from
        admin
    where
        admin_id=?
SQL_QUERY;
$rs = exec_query($sql, $query, array($delete_id));

$local_admin_type = $rs->fields['admin_type'];

if ($local_admin_type == 'admin') {
  $query = <<<SQL_QUERY
        select
            count(admin_id) as children
        from
            admin
        where
            created_by = ?
SQL_QUERY;

} else if ($local_admin_type == 'reseller') {
  $query = <<<SQL_QUERY
        select
            count(admin_id) as children from admin
        where
            created_by = ?
SQL_QUERY;

} else if ($local_admin_type == 'user') {
  $query = <<<SQL_QUERY
        select
            count(domain_id) as children from domain
        where
            domain_admin_id = ?
SQL_QUERY;

}
$rs = exec_query($sql, $query, array($delete_id));

if ($rs -> fields['children'] > 0 && $local_admin_type !== 'user') {
  /* this user have domain ! */
  $hdomain = 1;
  $_SESSION['hdomain'] = 1;
  header("Location: manage_users.php");
  die();
} else {
  if ($local_admin_type == 'admin') {
    $query = <<<SQL_QUERY
            delete
                from email_tpls
            where
                owner_id = ? and
                name = 'add-user-auto-msg'
SQL_QUERY;
    $rs = exec_query($sql, $query, array($delete_id));

  } else if ($local_admin_type == 'reseller') {
    $query = <<<SQL_QUERY
            delete
                from email_tpls
            where
                owner_id = ? and
                name = 'add-user-auto-msg'
SQL_QUERY;
    $rs = exec_query($sql, $query, array($delete_id));

    $query = <<<SQL_QUERY
            delete
                from reseller_props
            where
                reseller_id = ?
SQL_QUERY;
    $rs = exec_query($sql, $query, array($delete_id));

		// delete orders
	 $query = <<<SQL_QUERY
    	    delete from
        	    orders
        	where
            	user_id  = ?

SQL_QUERY;
	$rs = exec_query($sql, $query, array($delete_id));

			// delete orders settings
	 $query = <<<SQL_QUERY
    	    delete from
        	    orders_settings
        	where
            	user_id  = ?

SQL_QUERY;
	$rs = exec_query($sql, $query, array($delete_id));


    $query = <<<SQL_QUERY
            delete
                from hosting_plans
            where
                reseller_id = ?
SQL_QUERY;
    $rs = exec_query($sql, $query, array($delete_id));

		/*

		 $query = <<<SQL_QUERY

        select

            admin_id

		from
			admin

        where

            created_by = '$delete_id'

SQL_QUERY;

	$rs = execute_query($sql, $query);

			while (!$rs -> EOF) {
				$delete_user_account_id = $rs -> fields['admin_id'];
				print $delete_user_account_id."<br>";
				rm_rf_user_account ($delete_user_account_id);
			}
			die ();
			*/


  } else if ($local_admin_type == 'user') {
    rm_rf_user_account($delete_id);
    check_for_lock_file();
    send_request();
  }

  $query = <<<SQL_QUERY
        delete
            from admin
        where
            admin_id = ?
SQL_QUERY;
  $rs = exec_query($sql, $query, array($delete_id));

  $query = <<<SQL_QUERY
            delete
                from user_gui_props
            where
                user_id = ?
SQL_QUERY;
  $rs = exec_query($sql, $query, array($delete_id));
  $user_logged= $_SESSION['user_logged'];
  $local_admin_name = $_GET['delete_username'];
  write_log("$user_logged: delete user $local_admin_name, $local_admin_type, $delete_id!");
  $_SESSION['user_deleted'] = 1;
  header("Location: manage_users.php");
  die();
}

?>