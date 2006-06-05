<?php 
// -----------------------------------------------------------------------------
// |             VHCS(tm) - Virtual Hosting Control System                      |
// |              Copyright (c) 2001-2006 by moleSoftware		            	|
// |			http://vhcs.net | http://www.molesoftware.com		           	|
// |                                                                            |
// | This program is free software; you can redistribute it and/or              |
// | modify it under the terms of the MPL General Public License                |
// | as published by the Free Software Foundation; either version 1.1           |
// | of the License, or (at your option) any later version.                     |
// |                                                                            |
// | You should have received a copy of the MPL Mozilla Public License          |
// | along with this program; if not, write to the Open Source Initiative (OSI) |
// | http://opensource.org | osi@opensource.org								    |
// |                                                                         	|
// -----------------------------------------------------------------------------

// Reseller current counter fix script
// save this script into dir where you can include
// /var/www/vhcs2/gui/include/vhcs-lib.php


include '../include/vhcs-lib.php';

 $query = <<<SQL_QUERY
        select
            admin_id,
            admin_name
        from
            admin
        where
            admin_type = 'reseller'
SQL_QUERY;

	$rs = exec_query($sql, $query, array());
	if ($rs -> RecordCount() == 0) {

		print "Ther is no reseller in you system";

	} else {

		while (!$rs -> EOF) {
			$reseller_id =  $rs -> fields['admin_id'];
			$admin_name =  $rs -> fields['admin_name'];



		$count_query = <<<SQL_QUERY
        select
            count(domain_id) as crn_domains,
            IFNULL(sum(domain_mailacc_limit), 0) as crn_mail,
            IFNULL(sum(domain_ftpacc_limit), 0) as crn_ftp,
            IFNULL(sum(domain_traffic_limit), 0) as crn_traffic,
            IFNULL(sum(domain_sqld_limit), 0) as crn_sql,
            IFNULL(sum(domain_sqlu_limit), 0) as crn_sql_users,
            IFNULL(sum(domain_subd_limit), 0) as crn_subdomain,
            IFNULL(sum(domain_disk_limit), 0) as crn_hdd
        from
            domain
        where
            domain_created_id = ?
SQL_QUERY;

			$rs_count = exec_query($sql, $count_query, array($reseller_id));

			$crn_domains = $rs_count -> fields['crn_domains'];
			$crn_mail =  $rs_count -> fields['crn_mail'];
			$crn_ftp =  $rs_count -> fields['crn_ftp'];
			$crn_traffic =  $rs_count -> fields['crn_traffic'];
			$crn_sql =  $rs_count -> fields['crn_sql'];
			$crn_sql_users =  $rs_count -> fields['crn_sql_users'];
			$crn_subdomain =  $rs_count -> fields['crn_subdomain'];
			$crn_hdd =  $rs_count -> fields['crn_hdd'];

			print "<b>Reseller Name:<font color=red> ".$admin_name."</font> has the follow current values: </b><br>";


			print "Current domain count: ".$crn_domains."<br>";
			print "Current email count: ".$crn_mail."<br>";
			print "Current ftp count: ".$crn_ftp."<br>";
			print "Current traffic count: ".$crn_traffic."<br>";
			print "Current SQL count: ".$crn_sql."<br>";
			print "Current SQL user count: ".$crn_sql_users."<br>";
			print "Current subdomain count: ".$crn_subdomain."<br>";
			print "Current disk usage count: ".$crn_hdd."<br><br>";

			print "Updateing reseller current valueas ...... </b><br><br>";


	$update_query = <<<SQL_QUERY
        update
            reseller_props
        set
            current_dmn_cnt = ?,
            current_sub_cnt = ?,
            current_mail_cnt = ?,
            current_ftp_cnt  = ?,
            current_sql_db_cnt = ?,
            current_sql_user_cnt = ?,
            current_disk_amnt = ?,
            current_traff_amnt = ?
        where
            reseller_id  = ?

SQL_QUERY;

		$rs_update = exec_query($sql, $update_query, array($crn_domains,
                                                       $crn_subdomain,
                                                       $crn_mail,
                                                       $crn_ftp,
                                                       $crn_sql,
                                                       $crn_sql_users,
                                                       $crn_hdd,
                                                       $crn_traffic,
                                                       $reseller_id));

		print "<b><font color=blue>Update was successful...... </font><b><br><br>";

		$rs -> MoveNext();
		}

	}
?>