<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/manage_reseller_users.tpl',
		'page_message' => 'layout',
		'hosting_plans' => 'page',
		'reseller_list' => 'page',
		'reseller_item' => 'reseller_list',
		'src_reseller' => 'page',
		'src_reseller_option' => 'src_reseller',
		'dst_reseller' => 'page',
		'dst_reseller_option' => 'dst_reseller'));

/**
 * @param $tpl
 */
function gen_user_table($tpl) {

	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`admin_id`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_type` = 'reseller'
		ORDER BY
			`admin_name`
	";
	$rs = execute_query($query);

	if ($rs->recordCount() == 0) {
		set_page_message(tr('Reseller list is empty.'), 'error');
		redirectTo('manage_users.php');
	}

	$reseller_id = $rs->fields['admin_id'];
	$all_resellers = array();

	while (!$rs->EOF) {

		if ((isset($_POST['uaction']) && $_POST['uaction'] === 'change_src') && (isset($_POST['src_reseller']) &&
			$_POST['src_reseller'] == $rs->fields['admin_id'])
		) {
			$selected = $cfg->HTML_SELECTED;
			$reseller_id = $_POST['src_reseller'];
		} elseif ((isset($_POST['uaction']) && $_POST['uaction'] === 'move_user') && (isset($_POST['dst_reseller']) &&
			$_POST['dst_reseller'] == $rs->fields['admin_id'])
		) {
			$selected = $cfg->HTML_SELECTED;
			$reseller_id = $_POST['dst_reseller'];
		} else {
			$selected = '';
		}

		$all_resellers[] = $rs->fields['admin_id'];

		$tpl->assign(
			array(
				'SRC_RSL_OPTION' => tohtml($rs->fields['admin_name']),
				'SRC_RSL_VALUE' => $rs->fields['admin_id'],
				'SRC_RSL_SELECTED' => $selected));

		$tpl->assign(
			array(
				'DST_RSL_OPTION' => tohtml($rs->fields['admin_name']),
				'DST_RSL_VALUE' => $rs->fields['admin_id'],
				'DST_RSL_SELECTED' => ''));

		$tpl->parse('SRC_RESELLER_OPTION', '.src_reseller_option');
		$tpl->parse('DST_RESELLER_OPTION', '.dst_reseller_option');
		$rs->moveNext();
	}

	if (isset($_POST['src_reseller']) && $_POST['src_reseller'] == 0) {
		$selected = $cfg->HTML_SELECTED;
		$reseller_id = 0;
	} else {
		$selected = '';
	}

	$tpl->assign(
		array(
			'SRC_RSL_OPTION' => tr("N/A"),
			'SRC_RSL_VALUE' => 0,
			'SRC_RSL_SELECTED' => $selected));

	$tpl->parse('SRC_RESELLER_OPTION', '.src_reseller_option');

	if ($reseller_id === 0) {
		$query = "
			SELECT
				`admin_id`, `admin_name`
			FROM
				`admin`
			WHERE
				`admin_type` = 'user'
			AND
				`created_by` NOT IN (?)
			ORDER BY
				`admin_name`
		";
		$not_in = implode(',', $all_resellers);
		$rs = exec_query($query, $not_in);
	} else {
		$query = "
			SELECT
				`admin_id`, `admin_name`
			FROM
				`admin`
			WHERE
				`admin_type` = 'user'
			AND
				`created_by` = ?
			ORDER BY
				`admin_name`
		";
		$rs = exec_query($query, $reseller_id);
	}


	if ($rs->recordCount() == 0) {
		set_page_message(tr('User list is empty.'), 'error');
		redirectTo('manage_users.php');
	} else {
		$i = 0;
		while (!$rs->EOF) {
			$admin_id = $rs->fields['admin_id'];
			$admin_id_var_name = 'admin_id_' . $admin_id;
			$show_admin_name = decode_idna($rs->fields['admin_name']);

			$tpl->assign(
				array(
					'NUMBER' => $i + 1,
					'USER_NAME' => tohtml($show_admin_name),
					'CKB_NAME' => $admin_id_var_name));

			$tpl->parse('RESELLER_ITEM', '.reseller_item');
			$rs->moveNext();

			$i++;
		}

		$tpl->parse('RESELLER_LIST', 'reseller_list');
	}
}

/**
 *
 */
function update_reseller_user() {

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'move_user' && check_user_data()) {
		set_page_message(tr('User was moved'), 'success');
	}
}

/**
 * @return bool
 */
function check_user_data() {

	$query = "
		SELECT
			`admin_id`
		FROM
			`admin`
		WHERE
			`admin_type` = 'user'
		ORDER BY
			`admin_name`
	";
	$rs = execute_query($query);

	$selected_users = '';

	while (!$rs->EOF) {
		$admin_id = $rs->fields['admin_id'];

		$admin_id_var_name = 'admin_id_' . $admin_id;

		if (isset($_POST[$admin_id_var_name])
			&& $_POST[$admin_id_var_name] === 'on') {
			$selected_users .= $rs->fields['admin_id'] . ';';
		}

		$rs->Movenext();
	}

	if ($selected_users == '') {
		set_page_message(tr('Please select some user(s)!'), 'error');

		return false;
	} else if ($_POST['src_reseller'] == $_POST['dst_reseller']) {
		set_page_message(tr('Source and destination reseller are the same!'), 'error');

		return false;
	}

	$dst_reseller = $_POST['dst_reseller'];

	$query = "
		SELECT
			`reseller_ips`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";
	$rs = exec_query($query, $dst_reseller);

	$mru_error = '_off_';

	$dest_reseller_ips = $rs->fields['reseller_ips'];

	check_ip_sets($dest_reseller_ips, $selected_users, $mru_error);

	if ($mru_error == '_off_') {
		manage_reseller_limits($_POST['dst_reseller'], $_POST['src_reseller'], $selected_users, $mru_error);
	}

	if ($mru_error != '_off_') {
		set_page_message($mru_error, 'error');

		return false;
	}

	return true;
}

/**
 * @param $dest_reseller
 * @param $src_reseller
 * @param $users
 * @param $err
 * @return bool
 */
function manage_reseller_limits($dest_reseller, $src_reseller, $users, &$err) {

	list($dest_dmn_current, $dest_dmn_max,
		$dest_sub_current, $dest_sub_max,
		$dest_als_current, $dest_als_max,
		$dest_mail_current, $dest_mail_max,
		$dest_ftp_current, $dest_ftp_max,
		$dest_sql_db_current, $dest_sql_db_max,
		$dest_sql_user_current, $dest_sql_user_max,
		$dest_traff_current, $dest_traff_max,
		$dest_disk_current, $dest_disk_max
	) = generate_reseller_props($dest_reseller);

	list($src_dmn_current, $src_dmn_max,
		$src_sub_current, $src_sub_max,
		$src_als_current, $src_als_max,
		$src_mail_current, $src_mail_max,
		$src_ftp_current, $src_ftp_max,
		$src_sql_db_current, $src_sql_db_max,
		$src_sql_user_current, $src_sql_user_max,
		$src_traff_current, $src_traff_max,
		$src_disk_current, $src_disk_max
	) = generate_reseller_props($src_reseller);

	$users_array = explode(";", $users);

	for ($i = 0, $cnt_users_array = count($users_array) - 1; $i < $cnt_users_array; $i++) {
		$query = "
			SELECT
				`domain_id`, `domain_name`
			FROM
				`domain`
			WHERE
				`domain_admin_id` = ?
		";

		$rs = exec_query($query, $users_array[$i]);

		$domain_name = $rs->fields['domain_name'];

		$domain_id = $rs->fields['domain_id'];

		list($sub_current, $sub_max,
			$als_current, $als_max,
			$mail_current, $mail_max,
			$ftp_current, $ftp_max,
			$sql_db_current, $sql_db_max,
			$sql_user_current, $sql_user_max,
			$traff_max, $disk_max
		) = generate_user_props($domain_id);

		calculate_reseller_dvals($dest_dmn_current, $dest_dmn_max, $src_dmn_current, $src_dmn_max, 1, $err, 'Domain', $domain_name);

		if ($err == '_off_') {
			calculate_reseller_dvals($dest_sub_current, $dest_sub_max, $src_sub_current, $src_sub_max, $sub_max, $err, 'Subdomain', $domain_name);
			calculate_reseller_dvals($dest_als_current, $dest_als_max, $src_als_current, $src_als_max, $als_max, $err, 'Alias', $domain_name);
			calculate_reseller_dvals($dest_mail_current, $dest_mail_max, $src_mail_current, $src_mail_max, $mail_max, $err, 'Mail', $domain_name);
			calculate_reseller_dvals($dest_ftp_current, $dest_ftp_max, $src_ftp_current, $src_ftp_max, $ftp_max, $err, 'FTP', $domain_name);
			calculate_reseller_dvals($dest_sql_db_current, $dest_sql_db_max, $src_sql_db_current, $src_sql_db_max, $sql_db_max, $err, 'SQL Database', $domain_name);
			calculate_reseller_dvals($dest_sql_user_current, $dest_sql_user_max, $src_sql_user_current, $src_sql_user_max, $sql_user_max, $err, 'SQL User', $domain_name);
			calculate_reseller_dvals($dest_traff_current, $dest_traff_max, $src_traff_current, $src_traff_max, $traff_max, $err, 'Traffic', $domain_name);
			calculate_reseller_dvals($dest_disk_current, $dest_disk_max, $src_disk_current, $src_disk_max, $disk_max, $err, 'Disk', $domain_name);
		}

		if ($err != '_off_') {
			return false;
		}
	}

	// Let's Make Necessary Updates;

	$src_reseller_props = "$src_dmn_current;$src_dmn_max;";
	$src_reseller_props .= "$src_sub_current;$src_sub_max;";
	$src_reseller_props .= "$src_als_current;$src_als_max;";
	$src_reseller_props .= "$src_mail_current;$src_mail_max;";
	$src_reseller_props .= "$src_ftp_current;$src_ftp_max;";
	$src_reseller_props .= "$src_sql_db_current;$src_sql_db_max;";
	$src_reseller_props .= "$src_sql_user_current;$src_sql_user_max;";
	$src_reseller_props .= "$src_traff_current;$src_traff_max;";
	$src_reseller_props .= "$src_disk_current;$src_disk_max;";

	update_reseller_props($src_reseller, $src_reseller_props);

	$dest_reseller_props = "$dest_dmn_current;$dest_dmn_max;";
	$dest_reseller_props .= "$dest_sub_current;$dest_sub_max;";
	$dest_reseller_props .= "$dest_als_current;$dest_als_max;";
	$dest_reseller_props .= "$dest_mail_current;$dest_mail_max;";
	$dest_reseller_props .= "$dest_ftp_current;$dest_ftp_max;";
	$dest_reseller_props .= "$dest_sql_db_current;$dest_sql_db_max;";
	$dest_reseller_props .= "$dest_sql_user_current;$dest_sql_user_max;";
	$dest_reseller_props .= "$dest_traff_current;$dest_traff_max;";
	$dest_reseller_props .= "$dest_disk_current;$dest_disk_max;";

	update_reseller_props($dest_reseller, $dest_reseller_props);

	for ($i = 0, $cnt_users_array = count($users_array) - 1; $i < $cnt_users_array; $i++) {
		$query = "UPDATE `admin` SET `created_by` = ? WHERE `admin_id` = ?";
		exec_query($query, array($dest_reseller, $users_array[$i]));

		$query = "UPDATE `domain` SET `domain_created_id` = ? WHERE `domain_admin_id` = ?";
		exec_query($query, array($dest_reseller, $users_array[$i]));
	}

	return true;
}

/**
 * @param $dest
 * @param $dest_max
 * @param $src
 * @param $src_max
 * @param $umax
 * @param $err
 * @param $obj
 * @param $uname
 * @return mixed
 */
function calculate_reseller_dvals(&$dest, $dest_max, &$src, $src_max, $umax, &$err, $obj, $uname) {
	if ($dest_max == 0 && $src_max == 0 && $umax == -1) {
		return;
	} else if ($dest_max == 0 && $src_max == 0 && $umax == 0) {
		return;
	} else if ($dest_max == 0 && $src_max == 0 && $umax > 0) {
		$src -= $umax;

		$dest += $umax;

		return;
	} else if ($dest_max == 0 && $src_max > 0 && $umax == -1) {
		return;
	} else if ($dest_max == 0 && $src_max > 0 && $umax == 0) {
		// Impossible condition;
		return;
	} else if ($dest_max == 0 && $src_max > 0 && $umax > 0) {
		$src -= $umax;

		$dest += $umax;

		return;
	} else if ($dest_max > 0 && $src_max == 0 && $umax == -1) {
		return;
	} else if ($dest_max > 0 && $src_max == 0 && $umax == 0) {
		if ($err == '_off_') {
			$err = '';
		}
		$err .= tr('<b>%1$s</b> has unlimited rights for a <b>%2$s</b> Service !<br>', $uname, $obj);

		$err .= tr('You cannot move <b>%1$s</b> in a destination reseller,<br>which has limits for the <b>%2$s</b> service!', $uname, $obj);

		return;
	} else if ($dest_max > 0 && $src_max == 0 && $umax > 0) {
		if ($dest + $umax > $dest_max) {
			if ($err == '_off_') {
				$err = '';
			}
			$err .= tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br>service in destination reseller!<br>', $uname, $obj);

			$err .= tr('Moving aborted!');
		} else {
			$src -= $umax;

			$dest += $umax;
		}

		return;
	} else if ($dest_max > 0 && $src_max > 0 && $umax == -1) {
		return;
	} else if ($dest_max > 0 && $src_max > 0 && $umax == 0) {
		// Impossible condition;
		return;
	} else if ($dest_max > 0 && $src_max > 0 && $umax > 0) {
		if ($dest + $umax > $dest_max) {
			if ($err == '_off_') {
				$err = '';
			}
			$err .= tr('<b>%1$s</b> is exceeding limits for a <b>%2$s</b><br>service in destination reseller!<br>', $uname, $obj);

			$err .= tr('Moving aborted!');
		} else {
			$src -= $umax;

			$dest += $umax;
		}

		return;
	}
}

/**
 * @param $dest
 * @param $users
 * @param $err
 * @return bool
 */
function check_ip_sets($dest, $users, &$err) {

	$users_array = explode(";", $users);

	for ($i = 0, $cnt_users_array = count($users_array); $i < $cnt_users_array; $i++) {
		$query = "
			SELECT
				`domain_name`, `domain_ip_id`
			FROM
				`domain`
			WHERE
				`domain_admin_id` = ?
		";

		$rs = exec_query($query, $users_array[$i]);

		$domain_ip_id = $rs->fields['domain_ip_id'];

		$domain_name = $rs->fields['domain_name'];

		if (!preg_match("/$domain_ip_id;/", $dest)) {
			if ($err == '_off_') {
				$err = '';
			}
			$err .= tr('<b>%s</b> has IP address that cannot be managed from the destination reseller !<br>This user cannot be moved!', $domain_name);

			return false;
		}
	}

	return true;
}


$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin/Manage users/User assignment'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);
update_reseller_user();
gen_user_table($tpl);

$tpl->assign(
	array(
		'TR_USER_ASSIGNMENT' => tr('User assignment'),
		'TR_RESELLER_USERS' => tr('Users'),
		'TR_NUMBER' => tr('No.'),
		'TR_MARK' => tr('Mark'),
		'TR_USER_NAME' => tr('User name'),
		'TR_FROM_RESELLER' => tr('From reseller'),
		'TR_TO_RESELLER' => tr('To reseller'),
		'TR_MOVE' => tr('Move'),));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
