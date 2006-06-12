<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware							|
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

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);
global $cfg;

if (isset($_GET['uname']) && $_GET['uname'] !== '' && is_numeric($_GET['uname'])){
	$uuser_id = $_GET['uname'];
} else {
	header( 'Location: protected_areas.php' );
    die();
}

$query = <<<SQL_QUERY
        select
            uname
        from
            htaccess_users
        where
            dmn_id = ?
		and
			id = ?

SQL_QUERY;
$rs = exec_query($sql, $query, array($dmn_id, $uuser_id));
$uname = $rs->fields['uname'];

global $cfg;
$change_status=$cfg['ITEM_DELETE_STATUS'];
// lets delete the user from the SQL
$query = <<<SQL_QUERY
        update
        	htaccess_users
        set 
        	status = ?    
        where 
        	id = ?
		and
			dmn_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($change_status, $uuser_id, $dmn_id));

// lets delete this user if assignet to a group
$query = <<<SQL_QUERY
        select
			id,
			members
		from
        	htaccess_groups
        where
			dmn_id = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($dmn_id));

	 if ($rs -> RecordCount() !== 0) {

		 while (!$rs -> EOF) {
			$members = $rs -> fields['members'];
			$group_id = $rs -> fields['id'];

			$members = preg_replace("/$uuser_id/", "", "$members");

			$members = preg_replace("/,,/", ",", "$members");
			$members = preg_replace("/^,/", "", "$members");
			$members = preg_replace("/,$/", "", "$members");

			$update_query = <<<SQL_QUERY
				update
					htaccess_groups
				set
					members = ?
				where
					id = ?
SQL_QUERY;

				$rs_update = exec_query($sql, $update_query, array($members, $group_id));

			// lets update htacces files for this group
			$status = $cfg['ITEM_CHANGE_STATUS'];
			$update_query = <<<SQL_QUERY
				update
					htaccess
				set
					status = ?
				where
					group_id like ?
SQL_QUERY;

		$rs_update = exec_query($sql, $update_query, array($status, $group_id));

			$rs -> MoveNext();
		 }
	 }


// lets delete or update htaccess files if this user is assigned
$query = <<<SQL_QUERY
        select
            *
        from
            htaccess
        where
			dmn_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($dmn_id));

	while (!$rs -> EOF) {

		$ht_id = $rs -> fields['id'];
		$usr_id = $rs -> fields['user_id'];

		$usr_id_splited = split(',', $usr_id);
		for ($i = 0; $i < count($usr_id_splited); $i++) {

			if ($usr_id_splited[$i] == $uuser_id) {
				if (count($usr_id_splited) < 2 && count($usr_id_splited) > 0){
					$status = $cfg['ITEM_DELETE_STATUS'];
				} else {
					$usr_id = preg_replace("/$uuser_id/", "", "$usr_id");
					$usr_id = preg_replace("/,,/", ",", "$usr_id");
					$usr_id = preg_replace("/^,/", "", "$usr_id");
					$usr_id = preg_replace("/,$/", "", "$usr_id");
					$status = $cfg['ITEM_CHANGE_STATUS'];
				}

				$update_query = <<<SQL_QUERY
				update
					htaccess
				set
					user_id = ?,
					status = ?
				where
					id = ?
SQL_QUERY;

			$rs_update = exec_query($sql, $update_query, array($usr_id, $status, $ht_id));

			}

		}

	$rs -> MoveNext();
	}


check_for_lock_file();
send_request();


write_log("$admin_login: delete user ID (protected areas): $uname");
header( "Location: puser_manage.php" );
die();
?>
