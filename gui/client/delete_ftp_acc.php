<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2008 by ispCP | http://isp-control.net
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

if (isset($_GET['id']) && $_GET['id'] !== '') {
  global $cfg;

  $ftp_id = $_GET['id'];
  $dmn_name = $_SESSION['user_logged'];

  $query = <<<SQL_QUERY
        select
             t1.userid,
			 t1.uid,
			 t2.domain_gid
        from
            ftp_users as t1,
            domain as t2
        where
            t1.userid = ?
          and
            t1.uid = t2.domain_gid
          and
            t2.domain_name = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($ftp_id, $dmn_name));
  $ftp_name = $rs->fields['userid'];

  if ($rs -> RecordCount() == 0) {
    user_goto('ftp_accounts.php');
  }

  $query = <<<SQL_QUERY
        select
            t1.gid,
            t2.members
        from
            ftp_users as t1,
            ftp_group as t2
        where
            t1.gid = t2.gid
          and
            t1.userid = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($ftp_id));

  $ftp_gid = $rs -> fields['gid'];
  $ftp_members = $rs -> fields['members'];
  $members = preg_replace("/$ftp_id/", "", "$ftp_members");
  $members = preg_replace("/,,/", ",", "$members");
  $members = preg_replace("/^,/", "", "$members");
  $members = preg_replace("/,$/", "", "$members");

  if (strlen($members) == 0) {
    $query = <<<SQL_QUERY
      delete from
          ftp_group
      where
          gid = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($ftp_gid));

  } else {
    $query = <<<SQL_QUERY
      update
          ftp_group
      set
          members = ?
      where
          gid = ?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($members, $ftp_gid));
  }

  $query = <<<SQL_QUERY
      delete from
          ftp_users
      where
          userid = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($ftp_id));

  write_log($_SESSION['user_logged'].": delete FTP account: ".$ftp_name);
  set_page_message(tr('FTP account deleted successfully!'));
  user_goto('ftp_accounts.php');

} else {

  user_goto('ftp_accounts.php');
}

?>