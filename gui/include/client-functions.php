<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version		$ID$
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

function get_domain_default_props(&$sql, $domain_admin_id, $returnWKeys = false) {
	$query = <<<SQL_QUERY
        select
            domain_id,
            domain_name,
            domain_gid,
            domain_uid,
            domain_created_id,
            domain_created,
            domain_last_modified,
            domain_mailacc_limit,
            domain_ftpacc_limit,
            domain_traffic_limit,
            domain_sqld_limit,
            domain_sqlu_limit,
            domain_status,
            domain_alias_limit,
            domain_subd_limit,
            domain_ip_id,
            domain_disk_limit,
            domain_disk_usage,
            domain_php,
            domain_cgi
        from
            domain
        where
            domain_admin_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_admin_id));

	if (!$returnWKeys) {
		return array(
			$rs->fields['domain_id'],
			$rs->fields['domain_name'],
			$rs->fields['domain_gid'],
			$rs->fields['domain_uid'],
			$rs->fields['domain_created_id'],
			$rs->fields['domain_created'],
			$rs->fields['domain_last_modified'],
			$rs->fields['domain_mailacc_limit'],
			$rs->fields['domain_ftpacc_limit'],
			$rs->fields['domain_traffic_limit'],
			$rs->fields['domain_sqld_limit'],
			$rs->fields['domain_sqlu_limit'],
			$rs->fields['domain_status'],
			$rs->fields['domain_alias_limit'],
			$rs->fields['domain_subd_limit'],
			$rs->fields['domain_ip_id'],
			$rs->fields['domain_disk_limit'],
			$rs->fields['domain_disk_usage'],
			$rs->fields['domain_php'],
			$rs->fields['domain_cgi']);
	} else {
		return $rs->fields;
	}
}

function get_domain_running_sub_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        select
            count(subdomain_id) as cnt
        from
            subdomain
        where
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$sub_count = $rs->fields['cnt'];

	return $sub_count;
}

function get_domain_running_als_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        select
            count(alias_id) as cnt
        from
            domain_aliasses
        where
            domain_id = ?

SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$als_count = $rs->fields['cnt'];

	return $als_count;
}

function get_domain_running_mail_acc_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        SELECT
            COUNT(mail_id) AS cnt
        FROM
            mail_users
        WHERE
            mail_type RLIKE 'normal_'
          AND
            mail_type NOT LIKE 'normal_catchall'
          AND
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$dmn_mail_acc = $rs->fields['cnt'];

	$query = <<<SQL_QUERY
        SELECT
            COUNT(mail_id) AS cnt
        FROM
            mail_users
        WHERE
            mail_type RLIKE 'alias_'
          AND
            mail_type NOT LIKE 'alias_catchall'
          AND
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$als_mail_acc = $rs->fields['cnt'];

	$query = <<<SQL_QUERY
        SELECT
            COUNT(mail_id) AS cnt
        FROM
            mail_users
        WHERE
            mail_type RLIKE 'subdom_'
          AND
            mail_type NOT LIKE 'subdom_catchall'
          AND
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$sub_mail_acc = $rs->fields['cnt'];

	return array($dmn_mail_acc + $als_mail_acc + $sub_mail_acc,
		$dmn_mail_acc,
		$als_mail_acc,
		$sub_mail_acc);
}

function get_domain_running_dmn_ftp_acc_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        select
            domain_name
        from
            domain
        where
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$dmn_name = $rs->fields['domain_name'];

	$query = <<<SQL_QUERY
        select
            count(userid) as cnt
        from
            ftp_users
        where
            userid rlike ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array('@' . $dmn_name));

	$dmn_ftp_acc_cnt = $rs->fields['cnt'];

	return $dmn_ftp_acc_cnt;
}

function get_domain_running_sub_ftp_acc_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        select
            subdomain_name
        from
            subdomain
        where
            domain_id = ?
        order by
            subdomain_id
SQL_QUERY;

	$query2 = <<<SQL_QUERY
        select
            domain_name
        from
            domain
        where
            domain_id = ?
SQL_QUERY;

	$dmn = exec_query($sql, $query2, array($domain_id));
	$rs = exec_query($sql, $query, array($domain_id));

	$sub_ftp_acc_cnt = 0;

	while (!$rs->EOF) {
		$sub_name = $rs->fields['subdomain_name'];

		$query = <<<SQL_QUERY
            select
                count(userid) as cnt
            from
                ftp_users
            where
                userid rlike ?
SQL_QUERY;

		$rs_cnt = exec_query($sql, $query, array('@' . $sub_name . '.' . $dmn->fields['domain_name']));

		$sub_ftp_acc_cnt += $rs_cnt->fields['cnt'];

		$rs->MoveNext();
	}

	return $sub_ftp_acc_cnt;
}

function get_domain_running_als_ftp_acc_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        select
            alias_name
        from
            domain_aliasses
        where
            domain_id = ?
        order by
            alias_id
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$als_ftp_acc_cnt = 0;

	while (!$rs->EOF) {
		$als_name = $rs->fields['alias_name'];

		$query = <<<SQL_QUERY
            select
                count(userid) as cnt
            from
                ftp_users
            where
                userid rlike ?
SQL_QUERY;

		$rs_cnt = exec_query($sql, $query, array('@' . $als_name));

		$als_ftp_acc_cnt += $rs_cnt->fields['cnt'];

		$rs->MoveNext();
	}

	return $als_ftp_acc_cnt;
}

function get_domain_running_ftp_acc_cnt(&$sql, $domain_id) {

	$dmn_ftp_acc_cnt = get_domain_running_dmn_ftp_acc_cnt($sql, $domain_id);
	$sub_ftp_acc_cnt = get_domain_running_sub_ftp_acc_cnt($sql, $domain_id);
	$als_ftp_acc_cnt = get_domain_running_als_ftp_acc_cnt($sql, $domain_id);

	return array(
		$dmn_ftp_acc_cnt + $sub_ftp_acc_cnt + $als_ftp_acc_cnt,
		$dmn_ftp_acc_cnt,
		$sub_ftp_acc_cnt,
		$als_ftp_acc_cnt);
}

function get_domain_running_sqld_acc_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        select
            count(sqld_id) as cnt
        from
            sql_database
        where
            domain_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$sqld_acc_cnt = $rs->fields['cnt'];

	return $sqld_acc_cnt;
}

function get_domain_running_sqlu_acc_cnt(&$sql, $domain_id) {
	$query = <<<SQL_QUERY
        select distinct
            t1.sqlu_name
        from
            sql_user as t1, sql_database as t2
        where
            t2.domain_id = ?
          and
            t2.sqld_id = t1.sqld_id
SQL_QUERY;

	$rs = exec_query($sql, $query, array($domain_id));

	$sqlu_acc_cnt = $rs->RecordCount();

	return $sqlu_acc_cnt;
}

function get_domain_running_sql_acc_cnt(&$sql, $domain_id) {

	$sqld_acc_cnt = get_domain_running_sqld_acc_cnt($sql, $domain_id);
	$sqlu_acc_cnt = get_domain_running_sqlu_acc_cnt($sql, $domain_id);

	return array($sqld_acc_cnt, $sqlu_acc_cnt);
}

function get_domain_running_props_cnt(&$sql, $domain_id) {

	$sub_cnt = get_domain_running_sub_cnt($sql, $domain_id);
	$als_cnt = get_domain_running_als_cnt($sql, $domain_id);

	list($mail_acc_cnt, $dmn_mail_acc_cnt, $sub_mail_acc_cnt, $als_mail_acc_cnt) = get_domain_running_mail_acc_cnt($sql, $domain_id);
	list($ftp_acc_cnt, $dmn_ftp_acc_cnt, $sub_ftp_acc_cnt, $als_ftp_acc_cnt) = get_domain_running_ftp_acc_cnt($sql, $domain_id);
	list($sqld_acc_cnt, $sqlu_acc_cnt) = get_domain_running_sql_acc_cnt($sql, $domain_id);

	return array($sub_cnt, $als_cnt, $mail_acc_cnt, $ftp_acc_cnt, $sqld_acc_cnt, $sqlu_acc_cnt);
}

function gen_client_mainmenu(&$tpl, $menu_file) {
	global $sql, $cfg;

	$tpl->define_dynamic('menu', $menu_file);
	$tpl->define_dynamic('isactive_awstats', 'menu');
	$tpl->define_dynamic('isactive_domain', 'menu');
    $tpl->define_dynamic('isactive_email', 'menu');
    $tpl->define_dynamic('isactive_ftp', 'menu');
    $tpl->define_dynamic('isactive_sql', 'menu');
    $tpl->define_dynamic('isactive_support', 'menu');
	$tpl->define_dynamic('custom_buttons', 'menu');

	$tpl->assign(
			array(
				'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
				'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
				'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
				'TR_MENU_MANAGE_DOMAINS' => tr('Manage domains'),
				'TR_MENU_ADD_SUBDOMAIN' => tr('Add subdomain'),
				'TR_MENU_MANAGE_USERS' => tr('Email and FTP accounts'),
				'TR_MENU_ADD_MAIL_USER' => tr('Add mail user'),
				'TR_MENU_ADD_FTP_USER' => tr('Add FTP user'),
				'TR_MENU_MANAGE_SQL' => tr('Manage SQL'),
				'TR_MENU_ERROR_PAGES' => tr('Error pages'),
				'TR_MENU_ADD_SQL_DATABASE' => tr('Add SQL database'),
				'TR_MENU_DOMAIN_STATISTICS' => tr('Domain statistics'),
				'TR_MENU_DAILY_BACKUP' => tr('Daily backup'),
				'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
				'TR_MENU_NEW_TICKET' => tr('New ticket'),
				'TR_MENU_LOGOUT' => tr('Logout'),
				'PHP_MY_ADMIN' => tr('PhpMyAdmin'),
				'TR_WEBMAIL' => tr('Webmail'),
				'TR_FILEMANAGER' => tr('Filemanager'),
				'TR_MENU_WEBTOOLS' => tr('Webtools'),
				'TR_HTACCESS' => tr('Protected areas'),
				'TR_AWSTATS' => tr('Web statistics'),
				'TR_HTACCESS_USER' => tr('Group/User management'),
				'TR_MENU_OVERVIEW' => tr('Overview'),
				'TR_MENU_EMAIL_ACCOUNTS' => tr('Email Accounts'),
				'TR_MENU_FTP_ACCOUNTS' => tr('FTP Accounts'),
				'TR_MENU_LANGUAGE' => tr('Language'),
				'TR_MENU_CATCH_ALL_MAIL' => tr('Catch all'),
				'TR_MENU_ADD_ALIAS' => tr('Add alias'),
				'TR_MENU_UPDATE_HP' => tr('Update Hosting Package'),
				'SUPPORT_SYSTEM_PATH' => $cfg['ISPCP_SUPPORT_SYSTEM_PATH'],
				'SUPPORT_SYSTEM_TARGET' => $cfg['ISPCP_SUPPORT_SYSTEM_TARGET'],
				'WEBMAIL_PATH' => $cfg['WEBMAIL_PATH'],
				'WEBMAIL_TARGET' => $cfg['WEBMAIL_TARGET'],
				'PMA_PATH' => $cfg['PMA_PATH'],
				'PMA_TARGET' => $cfg['PMA_TARGET'],
				'FILEMANAGER_PATH' => $cfg['FILEMANAGER_PATH'],
				'FILEMANAGER_TARGET' => $cfg['FILEMANAGER_TARGET'],
			)
		);

	$query = <<<SQL_QUERY
        select
            *
        from
            custom_menus
        where
            menu_level = 'user'
          or
            menu_level = 'all'
SQL_QUERY;

	$rs = exec_query($sql, $query, array());
	if ($rs->RecordCount() == 0) {
		$tpl->assign('CUSTOM_BUTTONS', '');
	} else {
		global $i;
		$i = 100;

		while (!$rs->EOF) {
			$menu_name = $rs->fields['menu_name'];
			$menu_link = get_menu_vars($rs->fields['menu_link']);
			$menu_target = $rs->fields['menu_target'];
			$menu_link = str_replace('{ispcp_uname}', $_SESSION['user_logged'], $menu_link);

			if ($menu_target === '') {
				$menu_target = "";
			} else {
				$menu_target = "target=\"" . $menu_target . "\"";
			}

			$tpl->assign(
					array(
						'BUTTON_LINK' => $menu_link,
						'BUTTON_NAME' => $menu_name,
						'BUTTON_TARGET' => $menu_target,
						'BUTTON_ID' => $i,
						)
				);

			$tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
			$rs->MoveNext();
			$i++;
		} // end while
	} // end else

	list($dmn_id, $dmn_name, $dmn_gid, $dmn_uid, $dmn_created_id, $dmn_created, $dmn_last_modified,
         $dmn_mailacc_limit, $dmn_ftpacc_limit, $dmn_traff_limit, $dmn_sqld_limit, $dmn_sqlu_limit,
         $dmn_status, $dmn_als_limit, $dmn_subd_limit, $dmn_ip_id, $dmn_disk_limit, $dmn_disk_usage,
         $dmn_php, $dmn_cgi) = get_domain_default_props($sql, $_SESSION['user_id']);

    if ($dmn_mailacc_limit == -1) $tpl->assign('ISACTIVE_EMAIL', '');
	if (($dmn_als_limit == -1) && ($dmn_subd_limit == -1)) $tpl->assign('ISACTIVE_DOMAIN', '');
    if ($dmn_ftpacc_limit == -1) $tpl->assign('ISACTIVE_FTP', '');
    if ($dmn_sqld_limit == -1) $tpl->assign('ISACTIVE_SQL', '');

	if (!$cfg['ISPCP_SUPPORT_SYSTEM']) {
		$tpl->assign('ISACTIVE_SUPPORT', '');
	}

	if ($cfg['AWSTATS_ACTIVE'] == "no") {
		$tpl->assign('ISACTIVE_AWSTATS', '');
	} else {
		$tpl->assign('AWSTSTS', 'http://' . $_SERVER['HTTP_HOST'] . '/stats');
	}

	$tpl->parse('MAIN_MENU', 'menu');
}

function gen_client_menu(&$tpl, $menu_file) {
	global $sql, $cfg;

	$tpl->define_dynamic('menu', $menu_file);
	$tpl->define_dynamic('custom_buttons', 'menu');

	$tpl->assign(
			array(
				'TR_MENU_GENERAL_INFORMATION' => tr('General information'),
				'TR_MENU_CHANGE_PASSWORD' => tr('Change password'),
				'TR_MENU_CHANGE_PERSONAL_DATA' => tr('Change personal data'),
				'TR_MENU_MANAGE_DOMAINS' => tr('Manage domains'),
				'TR_MENU_ADD_SUBDOMAIN' => tr('Add subdomain'),
				'TR_MENU_MANAGE_USERS' => tr('Email and FTP accounts'),
				'TR_MENU_ADD_MAIL_USER' => tr('Add mail user'),
				'TR_MENU_ADD_FTP_USER' => tr('Add FTP user'),
				'TR_MENU_MANAGE_SQL' => tr('Manage SQL'),
				'TR_MENU_ERROR_PAGES' => tr('Error pages'),
				'TR_MENU_ADD_SQL_DATABASE' => tr('Add SQL database'),
				'TR_MENU_DOMAIN_STATISTICS' => tr('Domain statistics'),
				'TR_MENU_DAILY_BACKUP' => tr('Daily backup'),
				'TR_MENU_QUESTIONS_AND_COMMENTS' => tr('Support system'),
				'TR_MENU_NEW_TICKET' => tr('New ticket'),
				'TR_MENU_LOGOUT' => tr('Logout'),
				'PHP_MY_ADMIN' => tr('PhpMyAdmin'),
				'TR_WEBMAIL' => tr('Webmail'),
				'TR_FILEMANAGER' => tr('Filemanager'),
				'TR_MENU_WEBTOOLS' => tr('Webtools'),
				'TR_HTACCESS' => tr('Protected areas'),
				'TR_AWSTATS' => tr('Web statistics'),
				'TR_HTACCESS_USER' => tr('Group/User management'),
				'TR_MENU_OVERVIEW' => tr('Overview'),
				'TR_MENU_EMAIL_ACCOUNTS' => tr('Email Accounts'),
				'TR_MENU_FTP_ACCOUNTS' => tr('FTP Accounts'),
				'TR_MENU_LANGUAGE' => tr('Language'),
				'TR_MENU_CATCH_ALL_MAIL' => tr('Catch all'),
				'TR_MENU_ADD_ALIAS' => tr('Add alias'),
				'TR_MENU_UPDATE_HP' => tr('Update Hosting Package'),
				'SUPPORT_SYSTEM_PATH' => $cfg['ISPCP_SUPPORT_SYSTEM_PATH'],
				'SUPPORT_SYSTEM_TARGET' => $cfg['ISPCP_SUPPORT_SYSTEM_TARGET'],
				'WEBMAIL_PATH' => $cfg['WEBMAIL_PATH'],
				'WEBMAIL_TARGET' => $cfg['WEBMAIL_TARGET'],
				'PMA_PATH' => $cfg['PMA_PATH'],
				'PMA_TARGET' => $cfg['PMA_TARGET'],
				'FILEMANAGER_PATH' => $cfg['FILEMANAGER_PATH'],
				'FILEMANAGER_TARGET' => $cfg['FILEMANAGER_TARGET'],
				'VERSION' => $cfg['Version'],
				'BUILDDATE' => $cfg['BuildDate'],
				'CODENAME' => $cfg['CodeName']
				)
		);

	$query = <<<SQL_QUERY
        select
            *
        from
            custom_menus
        where
            menu_level = 'user'
          or
            menu_level = 'all'
SQL_QUERY;

	$rs = exec_query($sql, $query, array());
	if ($rs->RecordCount() == 0) {
		$tpl->assign('CUSTOM_BUTTONS', '');
	} else {
		global $i;
		$i = 100;

		while (!$rs->EOF) {
			$menu_name = $rs->fields['menu_name'];
			$menu_link = get_menu_vars($rs->fields['menu_link']);
			$menu_target = $rs->fields['menu_target'];

			if ($menu_target === '') {
				$menu_target = "";
			} else {
				$menu_target = "target=\"" . $menu_target . "\"";
			}

			$tpl->assign(
				array('BUTTON_LINK' => $menu_link,
					'BUTTON_NAME' => $menu_name,
					'BUTTON_TARGET' => $menu_target,
					'BUTTON_ID' => $i,
					)
				);

			$tpl->parse('CUSTOM_BUTTONS', '.custom_buttons');
			$rs->MoveNext();
			$i++;
		} // end while
	} // end else
	if (!$cfg['ISPCP_SUPPORT_SYSTEM']) {
		$tpl->assign('SUPPORT_SYSTEM', '');
	}

	if ($cfg['AWSTATS_ACTIVE'] == "no") {
		$tpl->assign('AWSTSTS', '');
	} else {
		$tpl->assign('AWSTSTS', 'http://' . $_SERVER['HTTP_HOST'] . '/stats');
	}

	$tpl->parse('MENU', 'menu');
}

function get_user_domain_id(&$sql, $user_id) {
	$query = <<<SQL_QUERY
        select
            domain_id
        from
            domain
        where
            domain_admin_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($user_id));

	return $rs->fields['domain_id'];
}

function user_trans_mail_type($mail_type) {
	if ($mail_type === 'normal_mail') {
		return tr('Domain mail');
	} else if ($mail_type === 'normal_forward') {
		return tr('Email forward');
	} else if ($mail_type === 'alias_mail') {
		return tr('Alias mail');
	} else if ($mail_type === 'alias_forward') {
		return tr('Alias forward');
	} else if ($mail_type === 'subdom_mail') {
		return tr('Subdomain mail');
	} else if ($mail_type === 'subdom_forward') {
		return tr('Subdomain forward');
	} else if ($mail_type === 'normal_catchall') {
		return tr('Domain mail');
	} else if ($mail_type === 'alias_catchall') {
		return tr('Domain mail');
	} else {
		return tr('Unknown type');
	}
}

function user_goto($dest) {
	header("Location: $dest");
	exit(0);
}

function count_sql_user_by_name(&$sql, $sqlu_name) {
	$query = <<<SQL_QUERY
		SELECT
			COUNT(sqlu_id) as cnt
		FROM
			sql_user
		WHERE
			sqlu_name = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($sqlu_name));

	return $rs->fields['cnt'];
}

function sql_delete_user(&$sql, $dmn_id, $db_user_id) {
	// let's get sql user common data;
	$query = <<<SQL_QUERY
         select
            t1.sqld_id, t1.sqlu_name, t2.sqld_name, t1.sqlu_name
         from
            sql_user as t1,
            sql_database as t2
         where
            t1.sqld_id = t2.sqld_id
           and
            t2.domain_id = ?
           and
            t1.sqlu_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $db_user_id));

	if ($rs->RecordCount() == 0) {
		user_goto('manage_sql.php');
	}
	// remove from ispcp sql_user table.
	$query = 'delete from sql_user where sqlu_id = ?';

	exec_query($sql, $query, array($db_user_id));
	$db_name = quoteIdentifier($rs->fields['sqld_name']);
	$db_user_name = $rs->fields['sqlu_name'];

	if (count_sql_user_by_name($sql, $rs->fields['sqlu_name']) == 0) {
		$db_id = $rs->fields['sqld_id'];
		// revoke grants on global level, if any;
		$query = <<<SQL_QUERY
        	revoke all on *.* from ?@'%'
SQL_QUERY;
		$rs = exec_query($sql, $query, array($db_user_name));
		$query = <<<SQL_QUERY
		revoke all on *.* from ?@localhost
SQL_QUERY;
		$rs = exec_query($sql, $query, array($db_user_name));
		// delete user record from mysql.user table;
		$query = <<<SQL_QUERY
       	 delete from
        	    mysql.user
       	 where
         	   Host = '%'
         and
          	  User = ?
SQL_QUERY;
		$rs = exec_query($sql, $query, array($db_user_name));

		$query = <<<SQL_QUERY
	delete from
		mysql.user
	where
		Host = 'localhost'
	and
		User = ?
SQL_QUERY;
		$rs = exec_query($sql, $query, array($db_user_name));
		// flush privileges.
		$query = <<<SQL_QUERY
        	flush privileges
SQL_QUERY;
		$rs = exec_query($sql, $query, array());
	} else {
		$new_db_name = str_replace("_", "\\_", $db_name);
		$query = <<<SQL_QUERY
       	 	revoke all on $new_db_name.* from ?@'%'
SQL_QUERY;
		$rs = exec_query($sql, $query, array($db_user_name));
		$query = <<<SQL_QUERY
		revoke all on $new_db_name.* from ?@localhost
SQL_QUERY;
		$rs = exec_query($sql, $query, array($db_user_name));
	}
}

function check_permissions(&$tpl) {
	if (isset($_SESSION['sql_support']) && $_SESSION['sql_support'] == "no") {
		$tpl->assign('SQL_SUPPORT', '');
	}

	if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
		$tpl->assign('ADD_EMAIL', '');
	}

	if (isset($_SESSION['subdomain_support']) && $_SESSION['subdomain_support'] == "no") {
		$tpl->assign('SUBDOMAIN_SUPPORT', '');
	}
	if (isset($_SESSION['alias_support']) && $_SESSION['alias_support'] == "no") {
		$tpl->assign('DOMAINALIAS_SUPPORT', '');
	}

	if (isset($_SESSION['subdomain_support']) && $_SESSION['subdomain_support'] == "no") {
		$tpl->assign('SUBDOMAIN_SUPPORT_CONTENT', '');
	}
	if (isset($_SESSION['alias_support']) && $_SESSION['alias_support'] == "no") {
		$tpl->assign('DOMAINALIAS_SUPPORT_CONTENT', '');
	}

	if (isset($_SESSION['alias_support']) && $_SESSION['alias_support'] == "no" && isset($_SESSION['subdomain_support']) && $_SESSION['subdomain_support'] == "no") {
		$tpl->assign('DMN_MNGMNT', '');
	}
}

function check_usr_sql_perms(&$sql, $db_user_id) {
	if (who_owns_this($db_user_id, 'sqlu_id') != $_SESSION['user_id']) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

		header('Location: manage_sql.php');
		die();
	}
}

function check_db_sql_perms(&$sql, $db_id) {
	if (who_owns_this($db_id, 'sqld_id') != $_SESSION['user_id']) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

		header('Location: manage_sql.php');
		die();
	}
}

function check_ftp_perms($sql, $ftp_acc) {
	if (who_owns_this($ftp_acc, 'ftp_user') != $_SESSION['user_id']) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

		header('Location: ftp_accounts.php');
		die();
	}
}

function delete_sql_database(&$sql, $dmn_id, $db_id) {
	$query = <<<SQL_QUERY
        select
            sqld_name as db_name
        from
            sql_database
        where
            domain_id = ?
          and
            sqld_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $db_id));

	if ($rs->RecordCount() == 0) {
		user_goto('manage_sql.php');
	}

	$db_name = quoteIdentifier($rs->fields['db_name']);
	// have we any users assigned to this database;
	$query = <<<SQL_QUERY
        select
            t2.sqlu_id as db_user_id,
            t2.sqlu_name as db_user_name
        from
            sql_database as t1,
            sql_user as t2
        where
            t1.sqld_id = t2.sqld_id
          and
            t1.domain_id = ?
          and
            t1.sqld_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $db_id));

	if ($rs->RecordCount() != 0) {
		while (!$rs->EOF) {
			$db_user_id = $rs->fields['db_user_id'];

			$db_user_name = $rs->fields['db_user_name'];

			sql_delete_user($sql, $dmn_id, $db_user_id);

			$rs->MoveNext();
		}
	}
	// drop desired database;
	$query = <<<SQL_QUERY
        drop database
SQL_QUERY;

	$query .= $db_name;

	$rs = exec_query($sql, $query, array());

	write_log($_SESSION['user_logged'] . ": delete SQL database: " . $db_name);
	// delete desired database from the ispcp sql_database table;
	$query = <<<SQL_QUERY
        delete from
            sql_database
        where
            domain_id = ?
          and
            sqld_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($dmn_id, $db_id));
}

function get_gender_by_code($code, $nullOnBad = false) {
	switch (strtolower($code)) {
		case 'm':
			return tr('Male');
			break;
		case 'f':
			return tr('Female');
			break;
		default:
			if (!$nullOnBad) {
				return tr('Unknown');
			} else {
				return null;
			}
			break;
	}
}

?>