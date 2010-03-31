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
 * Include core libraries
 */
require '../include/ispcp-lib.php';

/*******************************************************************************
 * Functions
 */

/**
 * Get Clean Input Data
 *
 * Return an array of cleaned input data. For performance reasons, the cleanup
 * is performed only once.
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since rxxxx
 * @return array cleaned data
 */
function get_clean_input_data() {

	static $cdata = array();

	if(empty($cdata) && !empty($_POST)) {

		$cdata = array(
			'customer_id' => clean_input($_POST['customer_id'], true),
			'fname' => clean_input($_POST['fname'], true),
			'lname' => clean_input($_POST['lname'], true),
			'gender' => clean_input($_POST['gender']),
			'firm' => clean_input($_POST['firm'], true),
			'zip' => clean_input($_POST['zip'], true),
			'city' => clean_input($_POST['city'], true),
			'state' => clean_input($_POST['state'], true),
			'country' => clean_input($_POST['country'], true),
			'email' => clean_input($_POST['email'], true),
			'phone' => clean_input($_POST['phone'], true),
			'fax' => clean_input($_POST['fax'], true),
			'street1' => clean_input($_POST['street1'], true),
			'street2' => clean_input($_POST['street2'], true),
			'max_dmn_cnt' => clean_input($_POST['nreseller_max_domain_cnt']),
			'max_sub_cnt' => clean_input($_POST['nreseller_max_subdomain_cnt']),
			'max_als_cnt' => clean_input($_POST['nreseller_max_alias_cnt']),
			'max_mail_cnt' => clean_input($_POST['nreseller_max_mail_cnt']),
			'max_ftp_cnt' => clean_input($_POST['nreseller_max_ftp_cnt']),
			'max_sql_db_cnt' => clean_input($_POST['nreseller_max_sql_db_cnt']),
			'max_sql_user_cnt' => clean_input($_POST['nreseller_max_sql_user_cnt']),
			'max_traff_amnt' => clean_input($_POST['nreseller_max_traffic']),
			'max_disk_amnt' => clean_input($_POST['nreseller_max_disk']),
			'support_system' => clean_input($_POST['support_system']),
			'pass' => clean_input($_POST['pass0']),
			'pass_rep' => clean_input($_POST['pass1']),
			'edit_username' => clean_input($_POST['edit_username']),
			'edit_id' => clean_input($_POST['edit_id'])
		);
	}

	return $cdata;
}

/**
 * Check reseller data
 *
 * @param array &$errFields  rerefence to the error indicators of input fields
 * @return boolean TRUE if all data are valid, FALSE otherwise
 */
function check_data(&$errFields) {

	// Get needed data
	$rdata =& get_data();

	/**
	 * Check for new password
	 */

	if (!empty($_POST['pass0']) || !empty($_POST['pass1'])) {

		if (!chk_password($_POST['pass0'])) {

			if (Config::get('PASSWD_STRONG')) {
				set_page_message(
					sprintf(
						tr('The password must be at least %s long and contain letters and numbers to be valid.'),Config::get('PASSWD_CHARS'))
				);

			} else {

				set_page_message(
					sprintf(
						tr('Password data is shorter than %s signs or includes not permitted signs!'),Config::get('PASSWD_CHARS'))
				);
			}

			$errFields[] = 'PWD_ERR';
		}

		if ($_POST['pass0'] != $_POST['pass1']) {
			set_page_message(tr('Entered passwords do not match!'));

			$errFields[] = 'PWD_ERR';
			$errFields[] = 'PWDR_ERR';
		}
	}

	/**
	 * Check for mail address
	 */

	if (!chk_email($rdata['email'])) {
		set_page_message(tr('Incorrect email syntax!'));

		$errFields[] = 'EMAIL_ERR';
	}

	list(
		$udmn_current, $udmn_max, $udmn_uf, $usub_current, $usub_max, $usub_uf,
		$uals_current, $uals_max, $uals_uf, $umail_current, $umail_max, $umail_uf,
		$uftp_current, $uftp_max, $uftp_uf, $usql_db_current, $usql_db_max,
		$usql_db_uf, $usql_user_current, $usql_user_max, $usql_user_uf,
		$utraff_current, $utraff_max, $utraff_uf, $udisk_current, $udisk_max,
		$udisk_uf
	) = generate_reseller_users_props($rdata['edit_id']);


	list(
		$rdmn_current, $rdmn_max, $rsub_current, $rsub_max, $rals_current,
		$rals_max, $rmail_current, $rmail_max, $rftp_current, $rftp_max,
		$rsql_db_current, $rsql_db_max, $rsql_user_current, $rsql_user_max,
		$rtraff_current, $rtraff_max, $rdisk_current, $rdisk_max
	) = generate_reseller_props($rdata['edit_id']);

	/**
	 * Check for new domains limit
	 */

	if (ispcp_limit_check($rdata['max_dmn_cnt'], null)) {
		$rs = _check_new_limit(
			$rdata['max_dmn_cnt'], $rdmn_current,
			$udmn_current, $udmn_uf, tr('Domains')
		);
	} else {
		set_page_message(tr('Incorrect domains limit!'));
		$rs = false;
	}

	if(!$rs)
		$errFields[] = 'DMN_ERR';

	/**
	 * Check for new subdomains limit
	 */

	if (ispcp_limit_check($rdata['max_sub_cnt'])) {
		$rs = _check_new_limit(
			$rdata['max_sub_cnt'], $rsub_current,
			$usub_current, $usub_uf, tr('Subdomains')
		);
	} else {
		set_page_message(tr('Incorrect subdomains limit!'));
		$rs = false;
	}

	if(!$rs)
		$errFields[] = 'SUB_ERR';

	/**
	 * Check for new domain alias limit
	 */

	if (ispcp_limit_check($rdata['max_als_cnt'])) {
		$rs = _check_new_limit(
			$rdata['max_als_cnt'], $rals_current,
			$uals_current, $uals_uf, tr('Aliases')
		);
	} else {
		set_page_message(tr('Incorrect aliases limit!'));
		$rs = false;
	}

	if(!$rs)
		$errFields[] = 'ALS_ERR';

	/**
	 * Check for new mail accounts limit
	 */

	if (ispcp_limit_check($rdata['max_mail_cnt'])) {
		$rs = _check_new_limit(
			$rdata['max_mail_cnt'], $rmail_current,
			$umail_current, $umail_uf, tr('Mail')
		);
	} else {
		set_page_message(tr('Incorrect mail accounts limit!'));
		$rs = false;
	}

	if(!$rs)
		$errFields[] = 'MAIL_ERR';

	/**
	 * Check for new Ftp accounts limit
	 */

	if (ispcp_limit_check($rdata['max_ftp_cnt'])) {
		$rs = _check_new_limit(
			$rdata['max_ftp_cnt'], $rftp_current,
			$uftp_current, $uftp_uf, tr('FTP')
		);
	} else {
		set_page_message(tr('Incorrect FTP accounts limit!'));
		$rs = false;
	}

	if(!$rs)
		$errFields[] = 'FTP_ERR';

	/**
	 * Check for new Sql databases limit
	 */

	if (!$rs = ispcp_limit_check($rdata['max_sql_db_cnt'])) {
		set_page_message(tr('Incorrect SQL databases limit!'));
	} else if ($rdata['max_sql_db_cnt'] == -1 && $rdata['max_sql_user_cnt'] != -1) {
		set_page_message(
			tr('SQL databases limit is <i>disabled</i> but SQL users limit not!')
		);
		$rs = false;
	} else {
		$rs = _check_new_limit(
			$rdata['max_sql_db_cnt'], $rsql_db_current,
			$usql_db_current, $usql_db_uf, tr('SQL Databases')
		);
	}

	if(!$rs)
		$errFields[] = 'SQLD_ERR';

	/**
	 * Check for new Sql users limit
	 */

	if (!$rs = ispcp_limit_check($rdata['max_sql_user_cnt'])) {
		set_page_message(tr('Incorrect SQL users limit!'));
	} else if ($rdata['max_sql_db_cnt'] != -1
		&& $rdata['max_sql_user_cnt'] == -1) {
		set_page_message(
			tr('SQL users limit is <i>disabled</i> but SQL databases limit not!')
		);
		$rs = false;
	} else {
		$rs = _check_new_limit(
			$rdata['max_sql_user_cnt'], $rsql_user_current,
			$usql_user_current, $usql_user_uf, tr('SQL Users')
		);
	}

	if(!$rs)
		$errFields[] = 'SQLU_ERR';

	/**
	 * Check for new traffic limit
	 */

	if (ispcp_limit_check($rdata['max_traff_amnt'], null)) {
		$rs = _check_new_limit(
			$rdata['max_traff_amnt'], $rtraff_current,
			$utraff_current / 1024 / 1024, $utraff_uf,
			tr('Web Traffic')
		);
	} else {
		set_page_message(tr('Incorrect traffic limit!'));
		$rs = false;
	}

	if(!$rs)
		$errFields[] = 'TRF_ERR';

	/**
	 * Check for new diskspace limit
	 */

	if (ispcp_limit_check($rdata['max_disk_amnt'], null)) {
		$rs = _check_new_limit(
			$rdata['max_disk_amnt'], $rdisk_current,
			$udisk_current / 1024 / 1024, $udisk_uf,
			tr('Disk storage')
		);
	} else {
		set_page_message(tr('Incorrect disk quota limit!'));
		$rs = false;
	}

	if(!$rs)
		$errFields[] = 'DISK_ERR';

	/**
	 * Check for IP adresses
	 */

	if ($rdata['reseller_ips'] == '') {
		set_page_message(
			tr('You must assign at least one IP number for a reseller!')
		);
	}

	check_user_ip_data($rdata['edit_id'], $rdata['rip_lst'], $rdata['reseller_ips']);

} // check_reseller_data()

/**
 * Check new limit per service
 *
 * Here, the following is considered as unique service:
 * domains, subdomains, domain alias, mail, ftp, sql user,
 * sql database, traffic, diskspace
 *
 * @access private
 * @param int $new_limit new limit
 * @param int $assigned_by_reseller
 * @param int $used_by_customers
 * @param string unlimited: set to '_on_' if unlimited, '_off_' otherwise
 * @param string service name, like domains subdomains...
 * @return boolean TRUE if no error was occured, FALSE otherwise
 */
function _check_new_limit($new_limit, $assigned_by_reseller, $used_by_customers, $unlimited, $service_name) {

	// Small Workaround to get the error state
	$err_state = isset($_SESSION['user_page_message']) ?
		strlen($_SESSION['user_page_message']) : 0;

	if($new_limit != 0) {

		// The service is limited for all customers
		if($unlimited == '_off_') {

			// If the new limit is < to the already used accounts/limits by users
			if($new_limit < $used_by_customers && $new_limit != -1) {
				set_page_message(
					tr("This reseller's customers are using/have more/higher <b>%s</b> accounts/limits than the new limit you entered.", $service_name)
				);

			// If the new limit is < to the already assigned accounts/limits by reseller
			} elseif($new_limit < $assigned_by_reseller && $new_limit != -1) {
				set_page_message(
					tr('This reseller has already assigned more/higher <b>%s</b> accounts/limits than the new limit you entered.', $service_name)
				); 
			
			// If the new limit is -1 (disabled) and the already used accounts/limits by users is greater 0
			} elseif($new_limit == -1 && $used_by_customers > 0) {
				set_page_message(
					tr("This reseller's customers are using/have more/higher <b>%s</b> accounts/limits than the new limit you entered.", $service_name)
				);
			
			// If the new limit is -1 (disabled) and the already assigned accounts/limits by reseller is greater 0
			} elseif($new_limit == -1 && $assigned_by_reseller > 0) {
				set_page_message(
					tr('This reseller has already assigned more/higher <b>%s</b> accounts/limits than the new limit you entered.', $service_name)
				);
			} 
			
		// One or more reseller's customers have unlimited rights
		} elseif($new_limit != 0) {
			set_page_message(
				tr('This reseller has customer(s) with unlimited rights for the <b>%s</b> service!', $service_name)
			);

			set_page_message(
				tr('If you want to limit the reseller, you must first limit its customers!')
			);
		}
	}

	if(isset($_SESSION['user_page_message']) &&
		$err_state < strlen($_SESSION['user_page_message']))
		return false;

	return true;
}

/**
 * Must be documented
 *
 * @param int $reseller_id reselller unique identifier
 * @param string $r_ips reseller Ips
 * @param string $u_ips users Ips
 * @return void
 */
function check_user_ip_data($reseller_id, $r_ips, $u_ips) {

	if($r_ips != $u_ips) {
		$rip_array = explode(';', $r_ips);

		for ($i = 0, $cnt_rip_array = count($rip_array) - 1; $i < $cnt_rip_array; $i++) {
			$ip = $rip_array[$i];

			if (!preg_match("/$ip;/", $u_ips)) {
				$ip_num = '';
				$ip_name = '';

				if (have_reseller_ip_users($reseller_id, $ip, $ip_num, $ip_name)) {
					$ip_msg = "$ip_num ($ip_name)";

					set_page_message(
						tr('This reseller has domains assigned to the <b>%s</b> address!', $ip_msg)
					);

					break;
				}
			}
		}
	}
}

/**
 * Get reseller properties and additional data
 *
 * @param int $reseller_id reselller unique identifier
 * @return array of properties and personal data belong to the reseller
 */
function get_reseller_prop($reseller_id) {

	$sql = Database::getInstance();

	$query = "
		SELECT
			`admin_name`, `fname`, `lname`, `firm`, `zip`, `city`, `state`,
			`country`, `email`, `phone`, `fax`, `street1`, `street2`, `max_dmn_cnt`,
			`max_sub_cnt`, `max_als_cnt`, `max_mail_cnt`, `max_ftp_cnt`,
			`max_sql_db_cnt`, `max_sql_user_cnt`, `max_traff_amnt`, `max_disk_amnt`,
			r.`support_system` as support_system, r.`customer_id` AS customer_id,
			`reseller_ips` AS rip_lst, `gender`
		FROM
			`admin` AS a, `reseller_props` AS r
		WHERE
			a.`admin_id` = ?
		AND
			r.`reseller_id` = a.`admin_id`
	";

	$rs = exec_query($sql, $query, array($reseller_id));

	if ($rs->RecordCount() <= 0) {
			set_page_message(
				tr('ERROR: The reseller account you trying to edit does not exist!')
			);

			user_goto('manage_users.php');
	}

	foreach($rs->fields as $fname => $value){
		if(!is_int($fname))
			$rdata[$fname] = $value;
	}

	return $rdata;
}

/**
 * Get Server IPs
 *
 * @param object &$tpl reference to the temmplate instance
 * @param string reseller IP addresses list
 * @return string reseller list of assigned Ips
 */
function get_servers_ips(&$tpl, $rip_lst) {

	$sql = Database::getInstance();

	$query = "
		SELECT
			`ip_id`, `ip_number`, `ip_domain`
		FROM
			`server_ips`
		ORDER BY
			`ip_number`
	";

	$rs = exec_query($sql, $query, array());

	$i = 0;
	$reseller_ips = '';

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'RSL_IP_MESSAGE' => tr('Reseller IP list is empty!'),
				'RSL_IP_LIST' => ''
			)
		);

		$tpl->parse('RSL_IP_MESSAGE', 'rsl_ip_message');
	} else {
		$tpl->assign(
				array(
					'TR_RSL_IP_NUMBER' => tr('No.'),
					'TR_RSL_IP_ASSIGN' => tr('Assign'),
					'TR_RSL_IP_LABEL' => tr('Label'),
					'TR_RSL_IP_IP' => tr('Number')
				)
			);

		while (!$rs->EOF) {
			$tpl->assign(
				array(
					'RSL_IP_CLASS' => ($i % 2 == 0) ? 'content2' : 'content4',
				)
			);

			$ip_id = $rs->fields['ip_id'];

			$ip_var_name = "ip_$ip_id";
			$ip_item_assigned = '';

			if (isset($_POST['uaction']) &&
				$_POST['uaction'] == 'update_reseller') {
				if (isset($_POST[$ip_var_name]) &&
					$_POST[$ip_var_name] == 'asgned') {
					$ip_item_assigned = 'checked="checked"';
					$reseller_ips .= "$ip_id;";
				} else {
					$ip_item_assigned = '';
				}
			} else {
				if (preg_match("/$ip_id\;/", $rip_lst) == 1) {
					$ip_item_assigned = 'checked="checked"';
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
					'RSL_IP_ITEM_ASSIGNED' => $ip_item_assigned
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

} // End get_servers_ips()

/**
 * Must be documented
 */
function have_reseller_ip_users($reseller_id, $ip, &$ip_num, &$ip_name) {

	$sql = Database::getInstance();

	$query = "
		SELECT
			`admin_id`
		FROM
			`admin`
		WHERE
			`created_by` = ?
	";

	$res = exec_query($sql, $query, array($reseller_id));

	if ($res->RowCount() == 0) {
		return false;
	}

	while (!$res->EOF) {
		$admin_id = $res->fields['admin_id'];

		$query = "
			SELECT
				`domain`.`domain_id`,
				`server_ips`.`ip_number`,
				`server_ips`.`ip_domain`
			FROM
				`domain`, `server_ips`
			WHERE
				`domain`.`domain_created_id` = ?
			AND
				`server_ips`.`ip_id` = `domain`.`domain_ip_id`
			AND
				`server_ips`.`ip_id` = ?
		";

		$dres = exec_query($sql, $query, array($reseller_id, $ip));

		if ($dres->RowCount() != 0) {
			$ip_num = $dres->fields['ip_number'];
			$ip_name = $dres->fields['ip_domain'];
			return true;
		}

		$res->MoveNext();
	}

	return false;
} // end have_reseller_ip_users()

/**
 * Update the reseller additional data and properties
 *
 * @return void
 */
function update_reseller() {

	// Get needed data
	$rdata =& get_data();

	// Get database instance
	$sql = Database::getInstance();

	/**
	 * Update reseller additional data
	 */

	$query = "
		UPDATE
			`admin`
		SET
			`fname` = ?, `lname` = ?, `firm` = ?, `zip` = ?,
			`city` = ?, `state` = ?, `country` = ?, `email` = ?,
			`phone` = ?, `fax` = ?, `street1` = ?, `street2` = ?,
			`gender` = ?
		WHERE
			`admin_id` = ?
		";

		$qparams = array(
			$rdata['fname'], $rdata['lname'], $rdata['firm'],
			$rdata['zip'], $rdata['city'], $rdata['state'],
			$rdata['country'], $rdata['email'], $rdata['phone'],
			$rdata['fax'], $rdata['street1'], $rdata['street2'],
			$rdata['gender'], $rdata['edit_id']
		);

	if(!empty($_POST['pass0'])) {
		$query = str_replace( '`fname`', '`admin_pass` = ?, `fname`', $query);
		array_unshift($qparams, crypt_user_pass($_POST['pass0']));
	}

	exec_query($sql, $query, $qparams );

	/**
	 * Update reseller properties
	 */

	$query = "
		UPDATE
			`reseller_props`
		SET
			`reseller_ips` = ?, `max_dmn_cnt` = ?, `max_sub_cnt` = ?,
			`max_als_cnt` = ?, `max_mail_cnt` = ?, `max_ftp_cnt` = ?,
			`max_sql_db_cnt` = ?, `max_sql_user_cnt` = ?, `max_traff_amnt` = ?,
			`max_disk_amnt` = ?, `support_system` = ?, `customer_id` = ?
		WHERE
			`reseller_id` = ?
	";

	exec_query(
		$sql,
		$query,
		array(
			$rdata['reseller_ips'], $rdata['max_dmn_cnt'],
			$rdata['max_sub_cnt'], $rdata['max_als_cnt'],
			$rdata['max_mail_cnt'], $rdata['max_ftp_cnt'],
			$rdata['max_sql_db_cnt'], $rdata['max_sql_user_cnt'],
			$rdata['max_traff_amnt'], $rdata['max_disk_amnt'],
			$rdata['support_system'], $rdata['customer_id'], $rdata['edit_id']
		)
	);

} // end update_reseller()

/**
 * Get reseller data
 *
 * For performance reason, the data are cached.
 *
 * Note: The template instance must always be passed as
 * parameter of this function during the first call.
 *
 * @author Laurent Declercq (Nuxwin) <laurent.declercq@ispcp.net>
 * @since r2561
 * [@param object &$tpl reference to the template instance]
 * @return array reseller properties and additional data
 */
function &get_data(&$tpl = false) {

	static $rdata = array();

	if(empty($rdata) && $tpl !== false) {

		$sql = Database::getInstance();

		// Update action
		if(isset($_POST['uaction']) && $_POST['uaction'] == 'update_reseller') {

			# Get clean input data
			$rdata = get_clean_input_data();

			$query = "
				SELECT
					`admin_name`, `reseller_ips`
				FROM
					`admin`, `reseller_props`
				WHERE
					`admin_id` = ?
			";

			$rs = exec_query($sql, $query, array($rdata['edit_id']));

			if ($rs->RecordCount() <= 0)
				user_goto('manage_users.php');

			$rdata['admin_name'] = $rs->fields['admin_name'];
			$rdata['rip_lst'] = $rs->fields['reseller_ips'];

		// Default action
		} else {

			// get clean reseller unique identifier
			$edit_id = clean_input($_GET['edit_id'], true);

			// Get reseller properties
			$rdata = get_reseller_prop($edit_id);

			$rdata['edit_id'] = $edit_id;
		}

		// Both cases
		$rdata['reseller_ips'] = get_servers_IPs($tpl, $rdata['rip_lst']);
	}

	return $rdata;
} // end get_data()

/**
 * Input Fields Errors Highlighting
 *
 * Highlighting erroneous input fields with a appropriate color
 *
 * @author Laurent Declercq (Nuxwin) <laurent.declercq@ispcp.net>
 * @Since r2587
 * @param object &$tpl reference to the template instance
 * @param array &$errFields reference to the array of error fields indicators
 * @return void
 */
function fields_highlighting(&$tpl, &$errFields) {

	$fields = array(
		'PWD_ERR', 'PWDR_ERR', 'EMAIL_ERR', 'DMN_ERR', 'SUB_ERR', 'ALS_ERR',
		'MAIL_ERR', 'FTP_ERR', 'SQLD_ERR', 'SQLU_ERR', 'TRF_ERR', 'DISK_ERR'
	);

	$l1 = 'border:1px rgb(233,0,0) solid;';

	foreach($fields as $field) {
			$tpl->assign($field, (in_array($field, $errFields)) ? $l1 : '' );
	}
}

/*******************************************************************************
 * Main
 */

check_login(__FILE__);

// Error fields indicators
$errFields = array();

/**
 * Script dispatcher - begin
 *
 * Dispatch the request according the state of $_GET || $_POST
 */

if(isset($_REQUEST['edit_id']) && !isset($_POST['Cancel'])) {

	// Ajax request
	// Todo: move Header handler in future responses class
	if (is_xhr() && isset($_POST['uaction']) && $_POST['uaction'] == 'genpass')  {
			// Disable Gzip output information
			$GLOBALS['class']['output']->showSize = false;

			// Overwrite the default header for ajax request
			header('Content-Type: text/plain; charset=utf-8');
			// HTTP/1.1
			header('Cache-Control: no-cache, private');
			// backward compatibility for HTTP/1.0
			header('Pragma: no-cache');
			header("HTTP/1.0 200 Ok");
			echo passgen();
			exit;
	}

	$tpl = new pTemplate();
	$tpl->define_dynamic('page',Config::get('ADMIN_TEMPLATE_PATH') .'/reseller_edit.tpl');
	$tpl->define_dynamic('page_message', 'page');
	$tpl->define_dynamic('hosting_plans', 'page');
	$tpl->define_dynamic('rsl_ip_message', 'page');
	$tpl->define_dynamic('rsl_ip_list', 'page');
	$tpl->define_dynamic('rsl_ip_item', 'rsl_ip_list');

	$theme_color = Config::get('USER_INITIAL_THEME');

	$tpl->assign(
		array(
			'TR_ADMIN_EDIT_RESELLER_PAGE_TITLE' =>
				tr('ispCP - Admin/Manage users/Edit Reseller'),
			'THEME_COLOR_PATH' => "../themes/$theme_color",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id'])
		)
	);

	gen_admin_mainmenu($tpl,Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
	gen_admin_menu($tpl,Config::get('ADMIN_TEMPLATE_PATH') . '/menu_users_manage.tpl');

	// First, we get needed data
	$rdata =& get_data($tpl);

	# Update action
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'update_reseller') {

		// Checking for the submitted data
		check_data($errFields);

		// If no error was occured during data checking, we can continue
		if (!isset($_SESSION['user_page_message'])) {

			// Update reseller properties and additional data
			update_reseller();

			// Adds admin log entry
			write_log(
				"{$_SESSION['user_logged']}: changes data/password for reseller: " .
				"{$rdata['edit_username']}!"
			);

			// Send new authentication data to reseller if needed
			if (isset($_POST['send_data']) && !empty($_POST['pass0'])) {
				send_add_user_auto_msg (
					$_SESSION['user_id'], $rdata['edit_username'], $rdata['pass'],
					$rdata['email'], $rdata['fname'], $rdata['lname'],
					tr('Reseller'), $rdata['gender']
				);
			}

			// Status indicator for the front page message after update request
			$_SESSION['user_updated'] = 1;

			// FIXME: Legacy from old code - Check if realy needed
			$_SESSION['reseller_ips'] = $rdata['reseller_ips'];

			// Back to the parent page after a successfull updates
			user_goto('manage_users.php');

		// An error was occured during data checking
		} else {
			set_page_message(
				'<br />' .
				tr('ERROR: One or more errors was found! Please, correct them and try again!')
			);
		}

	// Default action
	} else {

		// Pre-check - possible inconsistency data
		check_data($errFields);

		if(isset($_SESSION['user_page_message'])) {
			set_page_message(
				'<br />' .
				tr('Reseller data inconsistency!') . ' ' .
				tr('Please, read the message(s) above and trying to correct!')
			);
		}
	}

// Not reseller id provided or cancel action
} else {
	// Prevent the 'update' message on the parent page after cancel action
	if(isset($_POST['Cancel']) && isset($_SESSION['user_updated']))
		unset($_SESSION['user_updated']);

	user_goto('manage_users.php');
}

/**
 * Script dispatcher - end
 */

/**
 * Template preparation
 */

// Input Fields Errors Highlighting
fields_highlighting($tpl, $errFields);

if( $rdata['support_system'] == 'yes') {
	$support_yes = 'checked="checked"';
	$support_no = '';
} else {
	$support_no = 'checked="checked"';
	$support_yes = '';
}

$tpl->assign(
	array(
		'TR_EDIT_RESELLER' => tr('Edit reseller'),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_USERNAME' => tr('Username'),
		'TR_PASSWORD' => tr('Password'),
		'TR_PASSWORD_REPEAT' => tr('Repeat password'),
		'TR_EMAIL' => tr('E-mail'),
		'TR_UNLIMITED' => tr('unlimited'),
		'TR_MAX_DOMAIN_COUNT' =>
			tr('Domains limit<br><i>(0 unlimited)</i>'),
		'TR_MAX_SUBDOMAIN_COUNT' =>
			tr('Subdomains limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_ALIASES_COUNT' =>
			tr('Aliases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_MAIL_USERS_COUNT' =>
			tr('Mail accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_FTP_USERS_COUNT' =>
			tr('FTP accounts limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_SQLDB_COUNT' =>
			tr('SQL databases limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_SQL_USERS_COUNT' =>
			tr('SQL users limit<br><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAX_TRAFFIC_AMOUNT' =>
			tr('Traffic limit [MB]<br><i>(0 unlimited)</i>'),
		'TR_MAX_DISK_AMOUNT' =>
			tr('Disk limit [MB]<br><i>(0 unlimited)</i>'),
		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),
		'TR_SUPPORT_SYSTEM' => tr('Support system'),
		'TR_RESELLER_IPS' => tr('Reseller IPs'),
		'TR_ADDITIONAL_DATA' => tr('Additional data'),
		'TR_CUSTOMER_ID' => tr('Customer ID'),
		'TR_FIRST_NAME' => tr('First name'),
		'TR_LAST_NAME' => tr('Last name'),
		'TR_GENDER' => tr('Gender'),
		'TR_MALE' => tr('Male'),
		'TR_FEMALE' => tr('Female'),
		'TR_UNKNOWN' => tr('Unknown'),
		'TR_COMPANY' => tr('Company'),
		'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
		'TR_CITY' => tr('City'),
		'TR_STATE' => tr('State/Province'),
		'TR_COUNTRY' => tr('Country'),
		'TR_STREET_1' => tr('Street 1'),
		'TR_STREET_2' => tr('Street 2'),
		'TR_PHONE' => tr('Phone'),
		'TR_FAX' => tr('Fax'),
		'TR_PHONE' => tr('Phone'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_SEND_DATA' => tr('Send new login data'),
		'TR_PASSWORD_GENERATE' => tr('Generate password'),
		'TR_RESET' => tr('Reset'),
		'TR_GENERATED_PWD' => tr('Generated password:'),
		'TR_CTRL+C' => tr('Type `CTRL+C` to copy the generated password in the clipboard.'),
		'TR_EVENT_NOTICE' => html_entity_decode(htmlspecialchars_decode(tr('ispCP NOTICE:\n\nThe `Enter` key is disabled for performance reasons!\nInstead, use the %s button to update the data.', '`'.tr('Update').'`')), ENT_QUOTES, 'UTF-8'),

		'USERNAME' => $rdata['admin_name'],
		'EMAIL' => $rdata['email'],

		'MAX_DOMAIN_COUNT' => $rdata['max_dmn_cnt'],
		'MAX_SUBDOMAIN_COUNT' => $rdata['max_sub_cnt'],
		'MAX_ALIASES_COUNT' => $rdata['max_als_cnt'],
		'MAX_MAIL_USERS_COUNT' => $rdata['max_mail_cnt'],
		'MAX_FTP_USERS_COUNT' => $rdata['max_ftp_cnt'],
		'MAX_SQLDB_COUNT' => $rdata['max_sql_db_cnt'],
		'MAX_SQL_USERS_COUNT' => $rdata['max_sql_user_cnt'],
		'MAX_TRAFFIC_AMOUNT' => $rdata['max_traff_amnt'],
		'MAX_DISK_AMOUNT' => $rdata['max_disk_amnt'],

		'SUPPORT_YES' => $support_yes,
		'SUPPORT_NO' => $support_no,

		'CUSTOMER_ID' => $rdata['customer_id'],
		'FIRST_NAME' => $rdata['fname'],
		'LAST_NAME' => $rdata['lname'],
		'VL_MALE' => (($rdata['gender'] == 'M') ? 'selected="selected"' : ''),
		'VL_FEMALE' => (($rdata['gender'] == 'F') ? 'selected="selected"' : ''),
		'VL_UNKNOWN' =>
			(($rdata['gender'] == 'U') || (empty($rdata['gender'])) ?
				'selected="selected"' : ''),
		'FIRM' => $rdata['firm'],
		'ZIP' => $rdata['zip'],
		'CITY' => $rdata['city'],
		'STATE' => ($rdata['state'] === NULL ? '' : $rdata['state']),
		'COUNTRY' => $rdata['country'],
		'STREET_1' => $rdata['street1'],
		'STREET_2' => $rdata['street2'],
		'PHONE' => $rdata['phone'],
		'FAX' => $rdata['fax'],

		'EDIT_ID' => $rdata['edit_id'],

		// The entries below are for Demo versions only
		'PASSWORD_DISABLED'	=> tr('Password change is deactivated!'),
		'DEMO_VERSION'		=> tr('Demo Version!')
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

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}

unset_messages();
