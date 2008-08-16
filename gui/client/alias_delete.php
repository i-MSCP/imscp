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

if (isset($_GET['id']) && $_GET['id'] !== '') {
  $als_id = $_GET['id'];
  $dmn_id = get_user_domain_id($sql, $_SESSION['user_id']);

  $query = <<<SQL_QUERY
        select
             alias_id
             alias_name
        from
            domain_aliasses
        where
            domain_id = ?
          and
            alias_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($dmn_id, $als_id));
  $alias_name = $rs->fields['alias_name'];

  if ($rs -> RecordCount() == 0) {
    user_goto('domains_manage.php');
  }

  // check for mail accounts
  $query = <<<SQL_QUERY
		select
			count(mail_id) as cnt
		from
			mail_users
		WHERE
			sub_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($als_id));

  if ($rs -> fields['cnt'] > 0 ) {
    set_page_message(tr('Domain alias you are trying to remove has email accounts !<br>First remove them!'));
    header('Location: domains_manage.php');
    exit(0);
  }

  // check for ftp accounts
  $query = <<<SQL_QUERY
  		select
		  	count(fg.gid) as ftpnum
		from
			ftp_group fg,
            domain dmn,
			domain_aliasses d
		where
			d.alias_id = ?
		and
			fg.groupname = dmn.domain_name
		and
			fg.members RLIKE d.alias_name
        and
            d.domain_id = dmn.domain_id
SQL_QUERY;

  $rs = exec_query($sql, $query, array($als_id));
  if ($rs -> fields['ftpnum'] > 0 ) {
    set_page_message(tr('Domain alias you are trying to remove has FTP accounts!<br>First remove them!'));
    header('Location: domains_manage.php');
    exit(0);
  }

  check_for_lock_file();

  $query = <<<SQL_QUERY
        update
            domain_aliasses
        set
            alias_status = 'delete'
        where
            alias_id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($als_id));

  send_request();
  write_log($_SESSION['user_logged'].": delete alias ".$alias_name."!");
  set_page_message(tr('Alias scheduled for deletion!'));
  header('Location: domains_manage.php');
  exit(0);
} else {
  header('Location: domains_manage.php');
  exit(0);
}

?>