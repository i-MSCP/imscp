<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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

$dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);


if (isset($_GET['gname']) && $_GET['gname'] !== '' && is_numeric($_GET['gname'])){
	$group_id = $_GET['gname'];
} else {
	header( 'Location: protected_areas.php' );
   die();
}

$change_status = Config::get('ITEM_DELETE_STATUS');

$query = <<<SQL_QUERY
        update
        	htaccess_groups
        set
        	status = ?
        where
            id = ?
		and
			dmn_id = ?
SQL_QUERY;

$rs = exec_query($sql, $query, array($change_status, $group_id, $dmn_id));


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
				//Does this group affect some htaccess ?
			if ($grp_id_splited[$i] == $group_id) {
				//oh -> our group was used in htaccess
				  if (count($grp_id_splited) < 2 && count($grp_id_splited) > 0){
	 	            $status = Config::get('ITEM_DELETE_STATUS');
	 	          } else {
					$grp_id = preg_replace("/$group_id/", "", "$grp_id");
					$grp_id = preg_replace("/,,/", ",", "$grp_id");
					$grp_id = preg_replace("/^,/", "", "$grp_id");
					$grp_id = preg_replace("/,$/", "", "$grp_id");
					$status = Config::get('ITEM_CHANGE_STATUS');
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

	//we like to have our changes honoured to make group-deletion even without htaccess - relation possible!
		$status = Config::get('ITEM_CHANGE_STATUS');
		$query = <<<SQL_QUERY
   				 update
  						htaccess
					set
						status = ?
					where
						dmn_id = ?
					and
						status NOT like 'delete'
SQL_QUERY;
		 $rs = exec_query($sql, $query, array($status, $dmn_id));


check_for_lock_file();
send_request();

write_log("$admin_login: deletes group ID (protected areas): $groupname");
header( "Location: puser_manage.php" );
die();

?>