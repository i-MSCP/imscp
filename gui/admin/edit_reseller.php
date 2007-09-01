<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
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

if (isset($_GET['edit_id'])) {
	$edit_id = $_GET['edit_id'];
} else if (isset($_POST['edit_id'])) {
	$edit_id = $_POST['edit_id'];
} else {
	user_goto('manage_users.php');
}

$tpl = new pTemplate();
$tpl->define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'] . '/edit_reseller.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('rsl_ip_message', 'page');
$tpl->define_dynamic('rsl_ip_list', 'page');
$tpl->define_dynamic('rsl_ip_item', 'rsl_ip_list');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl->assign(
		array(
			'TR_ADMIN_EDIT_RESELLER_PAGE_TITLE' => tr('ispCP - Admin/Manage users/Edit Reseller'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id']),
			'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
			)
		);
// Get Server IPs;
function get_servers_ips(&$tpl, &$sql, $rip_lst) {
	$query = <<<SQL_QUERY
        SELECT
            ip_id, ip_number, ip_domain
        FROM
            server_ips
        ORDER BY
            ip_number

SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	$i = 0;

	$reseller_ips = '';

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array('RSL_IP_MESSAGE' => tr('Reseller IP list is empty!'),
				'RSL_IP_LIST' => ''
				)
			);

		$tpl->parse('RSL_IP_MESSAGE', 'rsl_ip_message');
	} else {
		$tpl->assign(
			array('TR_RSL_IP_NUMBER' => tr('No.'),
				'TR_RSL_IP_ASSIGN' => tr('Assign'),
				'TR_RSL_IP_LABEL' => tr('Label'),
				'TR_RSL_IP_IP' => tr('Number'),
				)
			);
		while (!$rs->EOF) {
			if ($i % 2 == 0) {
				$tpl->assign(
					array('RSL_IP_CLASS' => 'content',
						)
					);
			} else {
				$tpl->assign(
					array('RSL_IP_CLASS' => 'content2',
						)
					);
			}

			$ip_id = $rs->fields['ip_id'];

			$ip_var_name = "ip_$ip_id";
			$ip_item_assigned = '';

			if (isset($_POST['uaction']) && $_POST['uaction'] === 'update_reseller') {
				if (isset($_POST[$ip_var_name]) && $_POST[$ip_var_name] == 'asgned') {
					$ip_item_assigned = 'checked';
					$reseller_ips .= "$ip_id;";
				} else {
					$ip_item_assigned = '';
				}
			} else {
				if (preg_match("/$ip_id\;/", $rip_lst) == 1) {
					$ip_item_assigned = 'checked';
					$reseller_ips .= "$ip_id;";
				}
			}

			$tpl->assign(
				array(
					'RSL_IP_NUMBER' => $i + 1,
					'RSL_IP_LABEL' => $rs->fields['ip_domain'],
					'RSL_IP_IP' => $rs->fields['ip_number'],
					'RSL_IP_CKB_NAME' => $ip_var_name,
					'RSL_IP_CKB_VALUE' => 'asgned',
					'RSL_IP_ITEM_ASSIGNED' => $ip_item_assigned,
					)
				);

			$tpl->parse('RSL_IP_ITEM', '.rsl_ip_item');
			$rs->MoveNext();

			$i++;
		}

		$tpl->parse('RSL_IP_LIST', 'rsl_ip_list');
		$tpl->assign('RSL_IP_MESSAGE', '');
	}

	return $reseller_ips;
}

function check_user_data() {
	global $reseller_ips;

	if (!empty($_POST['pass']) || !empty($_POST['pass_rep'])) {
		if (!chk_password($_POST['pass'])) {
			set_page_message(tr("Incorrect password count or no number!<br />"));

			return false;
		}
		if ($_POST['pass'] != $_POST['pass_rep']) {
			set_page_message(tr("Entered passwords do not match!"));

			return false;
		}
	}

	if (!chk_email($_POST['email'])) {
		set_page_message(tr("Incorrect email count or no number!<br />"));
		return false;
	}

	if (!ispcp_limit_check($_POST['nreseller_max_domain_cnt'], null)) {
		set_page_message(tr("Incorrect max domain count or no number!<br />"));
		return false;
	}

	if (!ispcp_limit_check($_POST['nreseller_max_subdomain_cnt'], -1)) {
		set_page_message(tr("Incorrect max subdomain count or no number!<br />"));
		return false;
	}

	if (!ispcp_limit_check($_POST['nreseller_max_alias_cnt'], -1)) {
		set_page_message(tr('Incorrect max alias count or no number!<br />'));
		return false;
	}

	if (!ispcp_limit_check($_POST['nreseller_max_ftp_cnt'], -1)) {
		set_page_message(tr('Incorrect max FTP count or no number!<br />'));
		return false;
	}

	if (!ispcp_limit_check($_POST['nreseller_max_mail_cnt'], -1)) {
		set_page_message(tr('Incorrect max mail count or no number!<br />'));
		return false;
	}
	if (!ispcp_limit_check($_POST['nreseller_max_sql_db_cnt'], -1)) {
		set_page_message(tr('Incorrect max SQL databases count or no number!<br />'));

		return false;
	}
	if (!ispcp_limit_check($_POST['nreseller_max_sql_user_cnt'], -1)) {
		set_page_message(tr('Incorrect max SQL users count or no number!<br />'));
		return false;
	}
	if (!ispcp_limit_check($_POST['nreseller_max_traffic'], null)) {
		set_page_message(tr('Incorrect max traffic amount or syntax!'));
		return false;
	}
	if (!ispcp_limit_check($_POST['nreseller_max_disk'], null)) {
		set_page_message(tr('Incorrect max disk amount or syntax!'));
		return false;
	}
	if ($reseller_ips == '') {
		set_page_message(tr('You must assign at least one IP number for a reseller!'));
		return false;
	}

	global $edit_id, $rip_lst;

	return check_reseller_data($edit_id, $rip_lst, $reseller_ips);
}

function check_reseller_data($reseller_id, $rip_lst, $reseller_ips) {
	$reseller_max_domain_cnt = clean_input($_POST['nreseller_max_domain_cnt']);
	$reseller_max_subdomain_cnt = clean_input($_POST['nreseller_max_subdomain_cnt']);
	$reseller_max_alias_cnt = clean_input($_POST['nreseller_max_alias_cnt']);
	$reseller_max_mail_cnt = clean_input($_POST['nreseller_max_mail_cnt']);
	$reseller_max_ftp_cnt = clean_input($_POST['nreseller_max_ftp_cnt']);
	$reseller_max_sql_db_cnt = clean_input($_POST['nreseller_max_sql_db_cnt']);
	$reseller_max_sql_user_cnt = clean_input($_POST['nreseller_max_sql_user_cnt']);
	$reseller_max_traffic = clean_input($_POST['nreseller_max_traffic']);
	$reseller_max_disk = clean_input($_POST['nreseller_max_disk']);

	list ($udmn_current, $udmn_max, $udmn_uf,
		$usub_current, $usub_max, $usub_uf,
		$uals_current, $uals_max, $uals_uf,
		$umail_current, $umail_max, $umail_uf,
		$uftp_current, $uftp_max, $uftp_uf,
		$usql_db_current, $usql_db_max, $usql_db_uf,
		$usql_user_current, $usql_user_max, $usql_user_uf,
		$utraff_current, $utraff_max, $utraff_uf,
		$udisk_current, $udisk_max, $udisk_uf
		) = generate_reseller_users_props($reseller_id);

	list ($rdmn_current, $rdmn_max,
		$rsub_current, $rsub_max,
		$rals_current, $rals_max,
		$rmail_current, $rmail_max,
		$rftp_current, $rftp_max,
		$rsql_db_current, $rsql_db_max,
		$rsql_user_current, $rsql_user_max,
		$rtraff_current, $rtraff_max,
		$rdisk_current, $rdisk_max
		) = generate_reseller_props($reseller_id);

	$err = '_off_';

	calculate_new_reseller_vals($reseller_max_domain_cnt, $rdmn_current, $rdmn_max, $udmn_current, $rdmn_current, $udmn_uf, $err, tr('Domains'));
	if ($err == '_off_') {
		calculate_new_reseller_vals($reseller_max_subdomain_cnt, $rsub_current, $rsub_max, $usub_current, $rsub_current, $usub_uf, $err, tr('Subdomains'));
	}

	if ($err == '_off_') {
		if ($uals_max != $rals_current && $uals_current > 0)
			$err = tr('Inconsistency between current_als_cnt and actual alias count: %1$d != %2$d', $uals_max, $rals_current);
		else
			calculate_new_reseller_vals($reseller_max_alias_cnt, $rals_current, $rals_max, $uals_current, $uals_max, $uals_uf, $err, tr('Aliases'));
	}

	if ($err == '_off_') {
		if ($umail_max != $rmail_current && $umail_current > 0)
			$err = tr('Inconsistency between current_mail_cnt and actual mail count: %1$d != %2$d', $umail_max, $rmail_current);
		else
			calculate_new_reseller_vals($reseller_max_mail_cnt, $rmail_current, $rmail_max, $umail_current, $umail_max, $umail_uf, $err, tr('Mail'));
	}

	if ($err == '_off_') {
		if ($uftp_max != $rftp_current && $uftp_current > 0)
			$err = tr('Inconsistency between current_ftp_cnt and actual ftp count: %1$d != %2$d', $uftp_max, $rftp_current);
		else
			calculate_new_reseller_vals($reseller_max_ftp_cnt, $rftp_current, $rftp_max, $uftp_current, $uftp_max, $uftp_uf, $err, tr('FTP'));
	}

	if ($err == '_off_') {
		calculate_new_reseller_vals($reseller_max_sql_db_cnt, $rsql_db_current, $rsql_db_max, $usql_db_current, $usql_db_max, $usql_db_uf, $err, tr('SQL Databases'));
	}

	if ($err == '_off_') {
		calculate_new_reseller_vals($reseller_max_sql_user_cnt, $rsql_user_current, $rsql_user_max, $usql_user_current, $usql_user_max, $usql_user_uf, $err, tr('SQL Users'));
	}

	if ($err == '_off_') {
		calculate_new_reseller_vals($reseller_max_traffic, $rtraff_current, $rtraff_max, $utraff_current / 1024 / 1024, $utraff_max, $utraff_uf, $err, tr('Web Traffic'));
	}

	if ($err == '_off_') {
		calculate_new_reseller_vals($reseller_max_disk, $rdisk_current, $rdisk_max, $udisk_current / 1024 / 1024, $udisk_max, $udisk_uf, $err, tr('Disk storage'));
		// ($data,               $r,           &$rmax,         $u,                         $umax,       $uf,    &$err, $obj)
	}

	if ($err == '_off_') {
		check_user_ip_data($reseller_id, $rip_lst, $reseller_ips, $err);
	}

	if ($err != '_off_') {
		set_page_message($err);
		return false;
	}

	return true;
}

/**
* Function that seems to check if it is safe to set the new reseller limits (per 'service', e.g. mail, domains, etc)
*
* @param int $new_limit New limit
* @param int $r Service usage information of reseller (assigned, possibly being used or not)
* @param int $rmax Current reseller's limit
* @param int $u Service usage information of reseller's users (assigned, possibly being used or not)
* @param int $umax Current reseller users' limit
* @param int $unlimited Unlimited: _on_, limited: _off_
* @param string $ &$err Error message returned in case something is not good
* @param string $service The 'service' name, like domains, subdomains, mail accounts, sql users, etc
*/
function calculate_new_reseller_vals ($new_limit, $r, &$rmax, $u, $umax, $unlimited, &$err, $service) {
	if ($unlimited == '_off_') {
		// We have something like that: $u <= ($umax = $r) <= $rmax
		if ($umax != $r && $u > 0) { // ... && $u != unlimited
			$err = tr('Reseller data inconsistency!'); //really?

			return;
		}

		$both = $umax = $r;

		if ($rmax > 0) { // not unlimited
			if ($new_limit == 0 || $new_limit >= $rmax) { // if we are increasing it's ok
				$rmax = $new_limit;
			} else if ($both <= $new_limit && $new_limit < $rmax) { // if reducing but the reseller isn't using more than the new limit it's ok
				$rmax = $new_limit;
			} else if ($u < $new_limit && $r > $new_limit) { // reseller has assigned more than the new limit
				$err = tr('This reseller has already assigned more/higher <b>%s</b> accounts/limits than the new limit you entered.', $service);

				$err .= tr('Edit reseller aborted!');
			} else if ($new_limit <= $u) { // users are using more than new limit
				$err = tr("This reseller's customers are using/have more/higher <b>%s</b> accounts/limits than the new limit you entered.", $service);

				$err .= tr('Edit reseller aborted!');
			}
		} else { // if reseller is not limited
			if ($new_limit == 0) { // == unlimited
				$rmax = $new_limit;
			} else if ($r <= $new_limit) {
				$rmax = $new_limit;
			} else if ($u < $new_limit && $r > $new_limit) {
				$err = tr('This reseller has already assigned more/higher <b>%s</b> accounts/limits than the new limit you entered.', $service);

				$err .= tr('Edit reseller aborted!');
			} else if ($new_limit <= $u) {
				$err = tr("This reseller's customers are using/have more/higher <b>%s</b> accounts/limits than the new limit you entered.", $service);

				$err .= tr('Edit reseller aborted!');
			}
		}
	} else if ($unlimited == '_on_') {
		if ($new_limit > 0) {
			$err = tr('This reseller has customer(s) with unlimited rights for the <b>%s</b> service!<br>', $service);

			$err .= tr('If you want to limit the reseller, you must first limit its customers!<br>');

			$err .= tr('Edit reseller aborted!');
		}
	}
}

function check_user_ip_data($reseller_id, $r_ips, $u_ips, &$err)
{
	if ($r_ips == $u_ips) {
		return;
	} else {
		$rip_array = explode(";", $r_ips);

		for ($i = 0; $i < count($rip_array) - 1; $i++) {
			$ip = $rip_array[$i];

			if (!preg_match("/$ip;/", $u_ips)) {
				$ip_num = '';
				$ip_name = '';

				if (have_reseller_ip_users($reseller_id, $ip, $ip_num, $ip_name)) {
					$ip_msg = "$ip_num ($ip_name)";

					$err = tr('This reseller has domains assigned to the <b>%s</b> address!<br>', $ip_msg);
					$err .= tr('Edit reseller aborted!');

					return;
				}
			}
		}
	}
}

function have_reseller_ip_users($reseller_id, $ip, &$ip_num, &$ip_name) {
	global $sql;

	$query = <<<SQL_QUERY
        select
            admin_id
        from
            admin
        where
            created_by = ?

SQL_QUERY;

	$res = exec_query($sql, $query, array($reseller_id));

	if ($res->RowCount() == 0) {
		return false;
	} while (!$res->EOF) {
		$admin_id = $res->fields['admin_id'];

		$query = <<<SQL_QUERY
            select
                domain.domain_id,
                server_ips.ip_number,
                server_ips.ip_domain
            from
                domain,
				server_ips
            where
                domain.domain_created_id = ?
              and
                server_ips.ip_id = domain.domain_ip_id
              and
              	server_ips.ip_id = ?
SQL_QUERY;

		$dres = exec_query($sql, $query, array($reseller_id, $ip));

		if ($dres->RowCount() != 0) {
			$ip_num = $dres->fields['ip_number'];
			$ip_name = $dres->fields['ip_domain'];
			return true;
		}

		$res->MoveNext();
	}

	return false;
}

function update_reseller(&$sql) {
	global $edit_id, $reseller_ips;

	if (isset($_POST['Submit']) && isset($_POST['uaction']) && $_POST['uaction'] === 'update_reseller') {
		$user_id = $_SESSION['user_id'];

		if (check_user_data()) {
			$fname = clean_input($_POST['fname']);
			$lname = clean_input($_POST['lname']);
			$gender = $_POST['gender'];
			$firm = clean_input($_POST['firm']);
			$zip = clean_input($_POST['zip']);
			$city = clean_input($_POST['city']);
			$country = clean_input($_POST['country']);
			$email = clean_input($_POST['email']);
			$phone = clean_input($_POST['phone']);
			$fax = clean_input($_POST['fax']);
			$street1 = clean_input($_POST['street1']);
			$street2 = clean_input($_POST['street2']);

			if (get_gender_by_code($gender, true) === null) {
				$gender = '';
			}

			if (empty($_POST['pass'])) {
				$query = <<<SQL_QUERY
                    update
                        admin
                    set
                        fname = ?,
                        lname = ?,
                        firm = ?,
                        zip = ?,
                        city = ?,
                        country = ?,
                        email = ?,
                        phone = ?,
                        fax = ?,
                        street1 = ?,
                        street2 = ?,
                        gender = ?
                    where
                        admin_id = ?
SQL_QUERY;
				$rs = exec_query($sql, $query, array($fname,
						$lname,
						$firm,
						$zip,
						$city,
						$country,
						$email,
						$phone,
						$fax,
						$street1,
						$street2,
						$gender,
						$edit_id));
			} else {
				$upass = crypt_user_pass($_POST['pass']);
				$query = <<<SQL_QUERY
                    update
                        admin
                    set
                        admin_pass = ?,
                        fname = ?,
                        lname = ?,
                        firm = ?,
                        zip = ?,
                        city = ?,
                        country = ?,
                        email = ?,
                        phone = ?,
                        fax = ?,
                        street1 = ?,
                        street2 = ?,
                        gender = ?
                    where
                        admin_id = ?
SQL_QUERY;
				$rs = exec_query($sql, $query, array($upass,
						$fname,
						$lname,
						$firm,
						$zip,
						$city,
						$country,
						$email,
						$phone,
						$fax,
						$street1,
						$street2,
						$gender,
						$edit_id));
			}

			$nreseller_max_domain_cnt = clean_input($_POST['nreseller_max_domain_cnt']);
			$nreseller_max_subdomain_cnt = clean_input($_POST['nreseller_max_subdomain_cnt']);
			$nreseller_max_alias_cnt = clean_input($_POST['nreseller_max_alias_cnt']);
			$nreseller_max_mail_cnt = clean_input($_POST['nreseller_max_mail_cnt']);
			$nreseller_max_ftp_cnt = clean_input($_POST['nreseller_max_ftp_cnt']);
			$nreseller_max_sql_db_cnt = clean_input($_POST['nreseller_max_sql_db_cnt']);
			$nreseller_max_sql_user_cnt = clean_input($_POST['nreseller_max_sql_user_cnt']);
			$nreseller_max_traffic = clean_input($_POST['nreseller_max_traffic']);
			$nreseller_max_disk = clean_input($_POST['nreseller_max_disk']);
			$customer_id = clean_input($_POST['customer_id']);

			$query = <<<SQL_QUERY
                update reseller_props
                set
                    reseller_ips = ?,
                    max_dmn_cnt = ?,
                    max_sub_cnt = ?,
                    max_als_cnt = ?,
                    max_mail_cnt = ?,
                    max_ftp_cnt = ?,
                    max_sql_db_cnt = ?,
                    max_sql_user_cnt = ?,
                    max_traff_amnt = ?,
                    max_disk_amnt = ?,
                    customer_id = ?

                where

                    reseller_id = ?

SQL_QUERY;

			$rs = exec_query($sql, $query, array($reseller_ips,
					$nreseller_max_domain_cnt,
					$nreseller_max_subdomain_cnt,
					$nreseller_max_alias_cnt,
					$nreseller_max_mail_cnt,
					$nreseller_max_ftp_cnt,
					$nreseller_max_sql_db_cnt,
					$nreseller_max_sql_user_cnt,
					$nreseller_max_traffic,
					$nreseller_max_disk,
					$customer_id,
					$edit_id));

			$edit_username = clean_input($_POST['edit_username']);

			$user_logged = $_SESSION['user_logged'];

			write_log("$user_logged: change data/password for reseller: $edit_username!");

			if (isset($_POST['send_data']) && !empty($_POST['pass'])) {
				send_add_user_auto_msg ($user_id,
					$edit_username,
					clean_input($_POST['pass']),
					clean_input($_POST['email']),
					clean_input($_POST['fname']),
					clean_input($_POST['lname']),
					tr('Reseller'),
					$gender);
			}

			$_SESSION['user_updated'] = 1;
			$_SESSION['reseller_ips'] = $reseller_ips;

			header("Location: manage_users.php");
			die();
		} else {
		}
	}
}

function get_reseller_prop(&$sql) {
	global $edit_id;

	$query = <<<SQL_QUERY
        select
            admin_name, fname,
            lname, firm,
            zip, city,
            country, email,
            phone, fax,
            street1, street2,

            max_dmn_cnt, current_dmn_cnt,
            max_sub_cnt, current_sub_cnt,
            max_als_cnt, current_als_cnt,
            max_mail_cnt, current_mail_cnt,
            max_ftp_cnt, current_ftp_cnt,
            max_sql_db_cnt, current_sql_db_cnt,
            max_sql_user_cnt, current_sql_user_cnt,
            max_traff_amnt, current_traff_amnt,
            max_disk_amnt, current_disk_amnt,
            r.customer_id as customer_id, reseller_ips, gender
        from
            admin as a,
            reseller_props as r
        where
            a.admin_id = ? and
            r.reseller_id = a.admin_id

SQL_QUERY;

	$rs = exec_query($sql, $query, array($edit_id));

	if ($rs->RecordCount() <= 0) {
		header('Location: manage_users.php');
		die();
	}

	return array($rs->fields['admin_name'],
		$rs->fields['fname'],
		$rs->fields['lname'],
		$rs->fields['firm'],
		$rs->fields['zip'],
		$rs->fields['city'],
		$rs->fields['country'],
		$rs->fields['email'],
		$rs->fields['phone'],
		$rs->fields['fax'],
		$rs->fields['street1'],
		$rs->fields['street2'],
		$rs->fields['gender'],

		$rs->fields['max_dmn_cnt'],
		$rs->fields['current_dmn_cnt'],
		$rs->fields['max_sub_cnt'],
		$rs->fields['current_sub_cnt'],
		$rs->fields['max_als_cnt'],
		$rs->fields['current_als_cnt'],
		$rs->fields['max_mail_cnt'],
		$rs->fields['current_mail_cnt'],
		$rs->fields['max_ftp_cnt'],
		$rs->fields['current_ftp_cnt'],
		$rs->fields['max_sql_db_cnt'],
		$rs->fields['current_sql_db_cnt'],
		$rs->fields['max_sql_user_cnt'],
		$rs->fields['current_sql_user_cnt'],
		$rs->fields['max_traff_amnt'],
		$rs->fields['current_traff_amnt'],
		$rs->fields['max_disk_amnt'],
		$rs->fields['current_disk_amnt'],
		$rs->fields['customer_id'],
		$rs->fields['reseller_ips']);
}

/*
 *
 * static page messages.
 *
 */

list($admin_name, $fname,
	$lname, $firm,
	$zip, $city,
	$country, $email,
	$phone, $fax,
	$street1, $street2, $gender,

	$max_dmn_cnt, $current_dmn_cnt,
	$max_sub_cnt, $current_sub_cnt,
	$max_als_cnt, $current_als_cnt,
	$max_mail_cnt, $current_mail_cnt,
	$max_ftp_cnt, $current_ftp_cnt,
	$max_sql_db_cnt, $current_sql_db_cnt,
	$max_sql_user_cnt, $current_sql_user_cnt,
	$max_traff_amnt, $current_traff_amnt,
	$max_disk_amnt, $current_disk_amnt,
	$customer_id, $rip_lst
	) = get_reseller_prop(&$sql);

$reseller_ips = get_servers_IPs($tpl, $sql, $rip_lst);

update_reseller($sql);

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/main_menu_manage_users.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'] . '/menu_manage_users.tpl');

$tpl->assign(
	array('TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field!'),
		'TR_PASSWORD_NOT_MATCH' => tr("Passwords don't match!"),
		'TR_EDIT_RESELLER' => tr('Edit reseller'),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		'TR_EMAIL' => tr('E-mail'),
		'TR_UNLIMITED' => tr('unlimited'),
		'TR_MAX_DOMAIN_COUNT' => tr('Max domain count'),
		'TR_MAX_SUBDOMAIN_COUNT' => tr('Max subdomain count'),
		'TR_MAX_ALIASES_COUNT' => tr('Max aliases count'),
		'TR_MAX_MAIL_USERS_COUNT' => tr('Max mail users count'),
		'TR_MAX_FTP_USERS_COUNT' => tr('Max FTP users count'),
		'TR_MAX_SQLDB_COUNT' => tr('Max SQL databases count'),
		'TR_MAX_SQL_USERS_COUNT' => tr('Max SQL users count'),
		'TR_MAX_TRAFFIC_AMOUNT' => tr('Max traffic amount [MB]'),
		'TR_MAX_DISK_AMOUNT' => tr('Max disk amount [MB]'),
		'TR_PHP' => tr('PHP'),
		'TR_PERL_CGI' => tr('CGI / Perl'),
		'TR_JSP' => tr('JSP'),
		'TR_SSI' => tr('SSI'),
		'TR_FRONTPAGE_EXT' => tr('Frontpage extensions'),
		'TR_BACKUP_RESTORE' => tr('Backup and restore'),
		'TR_CUSTOM_ERROR_PAGES' => tr('Custom error pages'),
		'TR_PROTECTED_AREAS' => tr('Protected areas'),
		'TR_WEBMAIL' => tr('Webmail'),
		'TR_DIR_LIST' => tr('Directory listing'),
		'TR_APACHE_LOGFILES' => tr('Apache logfiles'),
		'TR_AWSTATS' => tr('AwStats'),
		'TR_LOGO_UPLOAD' => tr('Logo upload'),
		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),

		'TR_RESELLER_IPS' => tr('Reseller IPs'),

		'TR_ADDITIONAL_DATA' => tr('Additional data'),
		'TR_CUSTOMER_ID' => tr('Customer ID'),
		'TR_FIRST_NAME' => tr('First name'),
		'TR_LAST_NAME' => tr('Last name'),
		'TR_GENDER' => tr('Gender'),
		'TR_MALE' => tr('Male'),
		'TR_FEMALE' => tr('Female'),
		'TR_COMPANY' => tr('Company'),
		'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
		'TR_CITY' => tr('City'),
		'TR_COUNTRY' => tr('Country'),
		'TR_STREET_1' => tr('Street 1'),
		'TR_STREET_2' => tr('Street 2'),
		'TR_PHONE' => tr('Phone'),
		'TR_FAX' => tr('Fax'),
		'TR_PHONE' => tr('Phone'),
		'TR_UPDATE' => tr('Update'),
		'TR_SEND_DATA' => tr('Send new login data'),
		'TR_PASSWORD_GENERATE' => tr('Generate password'),

		'USERNAME' => $admin_name,
		'EMAIL' => $email,

		'MAX_DOMAIN_COUNT' => $max_dmn_cnt,
		'MAX_SUBDOMAIN_COUNT' => $max_sub_cnt,
		'MAX_ALIASES_COUNT' => $max_als_cnt,
		'MAX_MAIL_USERS_COUNT' => $max_mail_cnt,
		'MAX_FTP_USERS_COUNT' => $max_ftp_cnt,
		'MAX_SQLDB_COUNT' => $max_sql_db_cnt,
		'MAX_SQL_USERS_COUNT' => $max_sql_user_cnt,
		'MAX_TRAFFIC_AMOUNT' => $max_traff_amnt,
		'MAX_DISK_AMOUNT' => $max_disk_amnt,

		'CUSTOMER_ID' => $customer_id,
		'FIRST_NAME' => $fname,
		'LAST_NAME' => $lname,
		'VL_MALE' => (isset($gender) && $gender == 'M') ? 'checked' : '',
		'VL_FEMALE' => (isset($gender) && $gender == 'F')?  'checked' : '',

		'FIRM' => $firm,
		'ZIP' => $zip,
		'CITY' => $city,
		'COUNTRY' => $country,
		'STREET_1' => $street1,
		'STREET_2' => $street2,
		'PHONE' => $phone,
		'FAX' => $fax,

		'EDIT_ID' => $edit_id,
		'TR_UPDATE' => tr('Update'),
		)
	);

if (isset($_POST['genpass'])) {
	$tpl->assign('VAL_PASSWORD', passgen());
} else {
	$tpl->assign('VAL_PASSWORD', '');
}

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();

?>