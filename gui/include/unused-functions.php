<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 *  This file contains all the functions that are currently not used
 */

/* Unused functions moved from include/admin-functions.php */

function substract_from_reseller_props($reseller_id, $domain_id) {

	// function update reseller props before deleting account
	list($rdmn_current, $rdmn_max,
			$rsub_current, $rsub_max,
			$rals_current, $rals_max,
			$rmail_current, $rmail_max,
			$rftp_current, $rftp_max,
			$rsql_db_current, $rsql_db_max,
			$rsql_user_current, $rsql_user_max,
			$rtraff_current, $rtraff_max,
			$rdisk_current, $rdisk_max
	) = generate_reseller_props($reseller_id);

	// NXW: Unused variable so ..
	/*
	list($sub_current, $sub_max,
		$als_current, $als_max,
		$mail_current, $mail_max,
		$ftp_current, $ftp_max,
		$sql_db_current, $sql_db_max,
		$sql_user_current, $sql_user_max,
		$traff_max, $disk_max
	) = generate_user_props($domain_id);
	*/
	list(
		$sub_current,,$als_current,,$mail_current,,$ftp_current,,
		$sql_db_current,,$sql_user_current
	) = generate_user_props($domain_id);

	// NXW: Unused variable so ..
	/*
	list($tmpval1,
		$tmpval2,
		$tmpval3,
		$tmpval4,
		$tmpval5,
		$tmpval16,
		$traff_current,
		$disk_current,
		$tmpval7,
		$tmpval8
	) = generate_user_traffic($domain_id);
	*/
	list(,,,,,,$traff_current,$disk_current) = generate_user_traffic($domain_id);

	$rdmn_current -= 1;
	$rsub_current -= $sub_current;
	$rals_current -= $als_current;
	$rmail_current -= $mail_current;
	$rftp_current -= $ftp_current;
	$rsql_db_current -= $sql_db_current;
	$rsql_user_current -= $sql_user_current;
	$rtraff_current -= $traff_current;
	$rdisk_current -= $disk_current;
	$rprops = "$rdmn_current;$rdmn_max;";
	$rprops .= "$rsub_current;$rsub_max;";
	$rprops .= "$rals_current;$rals_max;";
	$rprops .= "$rmail_current;$rmail_max;";
	$rprops .= "$rftp_current;$rftp_max;";
	$rprops .= "$rsql_db_current;$rsql_db_max;";
	$rprops .= "$rsql_user_current;$rsql_user_max;";
	$rprops .= "$rtraff_current;$rtraff_max;";
	$rprops .= "$rdisk_current;$rdisk_max;";

	update_reseller_props($reseller_id, $rprops);
}

function get_cnt_of_user(&$sql, $user_type) {

	$query = "
		SELECT COUNT(*) AS
			`cnt`
		FROM
			`admin`
		WHERE
			`admin_type` = ?
		;
	";

	$rs = exec_query($sql, $query, $user_type);
	$admin_cnt = $rs->fields['cnt'];

	return $admin_cnt;
}

/**
 * @todo implement check for dynamic table/row in SQL query
 */
function records_rlike_count($field, $table, $where, $value, $a, $b) {

	$sql = ispCP_Registry::get('Db');

	if($where != '') {
		$query = "
			SELECT COUNT(*) AS
				`cnt`
			FROM
				$table
			WHERE
				$where
			RLIKE
				?
			;
		";

		$rs = exec_query($sql, $query, $a . $value . $b);
	} else {
		$query = "
			SELECT COUNT(*) AS
				`cnt`
			FROM
				$table
			;
		";

		$rs = exec_query($sql, $query);
	}

	return $rs->fields['cnt'];
}

function remove_users_common_properties($id_user) {

	$sql = ispCP_Registry::get('Db');

	$query = "
		DELETE FROM
			`admin`
		WHERE
			`admin_id` = ?
		;
	";

	// $rs = exec_query($sql, $query, $id_user);
	exec_query($sql, $query, $id_user);

	$query = "
		DELETE FROM
			`user_gui_props`
		WHERE
			`user_id` = ?
		;
	";

	// Unused result so ...
	// $rs = exec_query($sql, $query, $id_user);
	exec_query($sql, $query, $id_user);
}

/* Unused functions moved from include/cal-functions.php */

function check_user_pass($crdata, $data) {
	$salt = get_salt_from($crdata);
	$udata = crypt($data, $salt);
	return ($udata == $crdata);
}

/* Unused functions moved from include/data-function.php */

function split_date($date) {

	if (check_date($date)) {
		$res = preg_match_all("/^([^\.]+)\.([^\.]+)\.([^\n]+)\n/D", "$date\n", $parts, PREG_PATTERN_ORDER);
		return array($parts[1][0], $parts[2][0], $parts[3][0]);
	}

	return '';
}

/* Unused functions moved from include/emailtpl-functions.php */

function set_alias_order_email($admin_id, $data) {
	set_email_tpl_data($admin_id, 'alias-order-msg', $data);
}

/* Unused functions moved from include/input-checks.php */

/**
 * @todo document this function
 */
function get_post($value) {
	if (array_key_exists($value, $_POST)) {
		return $_POST[$value];
	} else {
		return null;
	}
}

/**
 * @todo document this function
 */
function is_subdir_of($base_domain, $subdomain, $realPath = true) {
	if ($realPath) {
		$base_domain = realpath($base_domain);
		$subdomain = realpath($subdomain);
	}

	$t = explode($base_domain, $subdomain);

	return (count($t) > 1 && $t[0] === '');
}

/* Unused functions moved from include/ispcp-function.php */

function escape_user_data($data) {

	$res_one = preg_replace("/\\\\/", "", $data);
	$res = preg_replace("/'/", "\\\'", $res_one);
	return $res;

}

function strip_html($input) {
	$output = htmlspecialchars($input, ENT_QUOTES, "UTF-8");
	return $output;
}

/* Unused functions moved from include/layout-functions.php */

function check_language_exist($lang_table) {
	$sql = ispCP_Registry::get('Db');

	return (in_array($lang_table, $sql->MetaTables()) == true) ? true : false;
}

function add_domain_extras(&$dmn_id, &$admin_id, &$sql) {
	$query = "
		INSERT INTO `domain_extras`
			(`dmn_id`,
			`admin_id`,
			`frontpage`,
			`htaccess`,
			`supportsystem`,
			`backup`,
			`errorpages`,
			`webmail`,
			`filemanager`,
			`installer`)
		VALUES
			(?,
			?,
			'0',
			'1',
			'1',
			'1',
			'1',
			'1',
			'1',
			'0')
	";

	$rs = exec_query($sql, $query, array($dmn_id, $admin_id));
}

/**
 * Get count from table by given domain_id's
 *
 * @param $tablename string database table name
 * @param $ua array domain_ids
 * @return int count
 */
function get_reseller_detail_count($tablename, $ua) {
	global $sql;

	$delstatus = Config::getInstance()->get('ITEM_DELETE_STATUS');

	$query = "SELECT COUNT(*) AS cnt FROM `".$tablename;
	if ($tablename == 'ftp_users') {
		$fieldname = 'uid';
	} else {
		$fieldname = 'domain_id';
	}
	$query .= "` WHERE `".$fieldname."` IN (".implode(',', $ua).")";
	if ($tablename == 'mail_users') {
		$query .= " AND `mail_acc` != 'abuse'
			AND `mail_acc` != 'postmaster'
			AND `mail_acc` != 'webmaster'
			AND `mail_type` NOT RLIKE '_catchall'";
		$query .= " AND status != '".$delstatus."'";
	} else if ($tablename == 'subdomain') {
		$query .= " AND subdomain_status != '".$delstatus."'";
	} else if ($tablename == 'domain_aliasses') {
		$query .= " AND alias_status != '".$delstatus."'";
	}
	$res = exec_query($sql, $query);

	return $res->fields['cnt'];
}

/* Unused functions moved from include/sql.php */

/**
 * @todo remove check for PHP <= 4.2.2, this produces unmantainable code
 */
function check_query($exclude = array()) {
	$matches = null;

	if (phpversion() <= '4.2.2') {
		$message = "Your PHP version is older than 4.2.2!";
		write_log($message);

		throw new ispCP_Exception("Error:  $message");
	}

	if (!is_array($exclude)) {
		$exclude = array($exclude);
	}

	foreach ($_REQUEST as $key => $value) {
		if (in_array($key, $exclude)) {
			continue;
		}

		if (!is_array($value)) {
			if (match_sqlinjection($value, $matches)) {
				$message = "Possible SQL injection detected: $key=>$value. <b>${matches[0]}</b>. Script terminated.";
				write_log($message);

				throw new ispCP_Exception("<b>WARNING</b>: $message");
			}
		} else {
			foreach ($value as $skey => $svalue) {
				if (!is_array($svalue)) {
					if (match_sqlinjection($svalue, $matches)) {
						$message = "Possible SQL injection detected: $skey=>$svalue <b>${matches[0]}</b>. Script terminated.";
						write_log($message);

						throw new ispCP_Exception("<b>WARNING</b>: $message");
					}
				}
			}
		}
	}
}

/**
 * Function match_sqlinjection
 * @todo document this function
 */
function match_sqlinjection($value, &$matches) {
	$matches = array();
	return (preg_match("/((DELETE)|(INSERT)|(UPDATE)|(ALTER)|(CREATE)|( TABLE)|(DROP))\s[A-Za-z0-9 ]{0,200}(\s(FROM)|(INTO)|(TABLE)\s)/i", $value, $matches) > 0);
}
