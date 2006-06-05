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

global $cfg;
$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);


if (isset($_GET['gname']) && $_GET['gname'] !== '' && is_numeric($_GET['gname'])){
	$group_id = $_GET['gname'];
} else {
	header( 'Location: protected_areas.php' );
   die();
}

$query = <<<SQL_QUERY
        select
			ugroup
		from
        	htaccess_groups
        where
            id = ?
		and
			dmn_id = ?
SQL_QUERY;
$groupname = $rs->fields['ugroup'];

$query = <<<SQL_QUERY
        delete from
        	htaccess_groups
        where
            id = ?
		and
			dmn_id = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($group_id, $dmn_id));


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
		$grp_id = $rs -> fields['group_id'];

		$grp_id_splited = split(',', $grp_id);
		for ($i = 0; $i < count($grp_id_splited); $i++) {

			if ($grp_id_splited[$i] == $group_id) {
				if (count($grp_id_splited) < 2 && count($grp_id_splited) > 0){
					$status = $cfg['ITEM_DELETE_STATUS'];
				} else {
					$grp_id = preg_replace("/$group_id/", "", "$grp_id");
					$grp_id = preg_replace("/,,/", ",", "$grp_id");
					$grp_id = preg_replace("/^,/", "", "$grp_id");
					$grp_id = preg_replace("/,$/", "", "$grp_id");
					$status = $cfg['ITEM_CHANGE_STATUS'];
				}

				$update_query = <<<SQL_QUERY
				update
					htaccess
				set
					group_id = ?,
					status = ?
				where
					id = ?
SQL_QUERY;

		$rs_update = exec_query($sql, $update_query, array($grp_id, $status, $ht_id));

			}

		}

	$rs -> MoveNext();
	}


check_for_lock_file();
send_request();

write_log("$admin_login: delete group ID (protected areas): $groupname");
header( "Location: puser_manage.php" );
die();
?>
