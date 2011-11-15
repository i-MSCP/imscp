<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @link		http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/*******************************************************************************
 * Script functions
 */

// TODO must be review (all is not correctly checked)

/**
 * Returns clean input data.
 *
 * Return an array of cleaned input data. For performance reasons, the cleanup
 * is performed only once.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return array cleaned data
 */
function reseller_getCleanData()
{
	static $cdata = array();

	if (empty($cdata) && !empty($_POST)) {
		$cdata = array(
			'customer_id' => clean_input($_POST['customer_id']),
			'fname' => clean_input($_POST['fname']),
			'lname' => clean_input($_POST['lname']),
			'gender' => clean_input($_POST['gender']),
			'firm' => clean_input($_POST['firm']),
			'zip' => clean_input($_POST['zip']),
			'city' => clean_input($_POST['city']),
			'state' => clean_input($_POST['state']),
			'country' => clean_input($_POST['country']),
			'email' => clean_input($_POST['email']),
			'phone' => clean_input($_POST['phone']),
			'fax' => clean_input($_POST['fax']),
			'street1' => clean_input($_POST['street1']),
			'street2' => clean_input($_POST['street2']),
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
			'edit_id' => clean_input($_POST['edit_id']),
			'software_allowed' => clean_input($_POST['domain_software_allowed']),
			'softwaredepot_allowed' => clean_input($_POST['domain_softwaredepot_allowed']),
			'websoftwaredepot_allowed' => clean_input($_POST['domain_websoftwaredepot_allowed']),
			'php_ini_system' => clean_input($_POST['phpini_system']),
			'php_ini_al_register_globals' => clean_input($_POST['phpini_al_register_globals']),
			'php_ini_al_allow_url_fopen' => clean_input($_POST['phpini_al_allow_url_fopen']),
			'php_ini_al_display_errors' => clean_input($_POST['phpini_al_display_errors']),
			'php_ini_al_disable_functions' => clean_input($_POST['phpini_al_disable_functions']),
			'php_ini_max_memory_limit' => clean_input($_POST['phpini_max_memory_limit']),
			'php_ini_max_upload_max_filesize' => clean_input($_POST['phpini_max_upload_max_filesize']),
			'php_ini_max_post_max_size' => clean_input($_POST['phpini_max_post_max_size']),
			'php_ini_max_max_execution_time' => clean_input($_POST['phpini_max_max_execution_time']),
			'php_ini_max_max_input_time' => clean_input($_POST['phpini_max_max_input_time'])
		);
	}

	return $cdata;
}

/**
 * Checks reseller data.
 *
 * @param array &$errFields rerefence to the error indicators of input fields
 * @return boolean TRUE if all data are valid, FALSE otherwise
 */
function reseller_checkData(&$errFields)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Get needed data
	$rdata =& get_data();

	// Check for password

	if (!empty($_POST['pass0']) || !empty($_POST['pass1'])) {
		if (!chk_password($_POST['pass0'])) {
			if ($cfg->PASSWD_STRONG) {
				set_page_message(tr(
									 'The password must be at least %s long and contain letters and numbers to be valid.',
									 $cfg->PASSWD_CHARS
								 ), 'error');
			} else {
				set_page_message(tr(
									 'Password data is shorter than %s signs or includes not permitted signs.',
									 $cfg->PASSWD_CHARS
								 ), 'error');
			}

			$errFields[] = 'PWD_ERR';
		}

		if ($_POST['pass0'] != $_POST['pass1']) {
			set_page_message(tr('Passwords do not match.'), 'error');

			$errFields[] = 'PWD_ERR';
			$errFields[] = 'PWDR_ERR';
		}
	}

	// Check for email syntax

	if (!chk_email($rdata['email'])) {
		set_page_message(tr('Incorrect email syntax.'), 'error');

		$errFields[] = 'EMAIL_ERR';
	}

	list(
		$udmn_current, , $udmn_uf, $usub_current, , $usub_uf, $uals_current, ,
		$uals_uf, $umail_current, , $umail_uf, $uftp_current, , $uftp_uf,
		$usql_db_current, , $usql_db_uf, $usql_user_current, , $usql_user_uf,
		$utraff_current, , $utraff_uf, $udisk_current, , $udisk_uf
		) = generate_reseller_users_props($rdata['edit_id']);


	list($rdmn_current, , $rsub_current, , $rals_current, , $rmail_current, ,
		$rftp_current, , $rsql_db_current, , $rsql_user_current, , $rtraff_current, ,
		$rdisk_current) = generate_reseller_props($rdata['edit_id']);

	// Check for domain (customer) accounts limit

	if (imscp_limit_check($rdata['max_dmn_cnt'], null)) {
		$rs = reseller_checkServiceItemsLimit($rdata['max_dmn_cnt'], $rdmn_current,
											  $udmn_current,   $udmn_uf,   tr('Domains'));
	} else {
		set_page_message(tr('Incorrect limit for %s.', tr('domain')), 'error');
		$rs = false;
	}

	if (!$rs) {
		$errFields[] = 'DMN_ERR';
	}

	// Check for domain aliases limit

	if (imscp_limit_check($rdata['max_als_cnt'])) {
		$rs = reseller_checkServiceItemsLimit($rdata['max_als_cnt'], $rals_current,
											  $uals_current, $uals_uf, tr('Aliases'));
	} else {
		set_page_message(tr('Incorrect limit for %s.', tr('domain aliases')), 'error');
		$rs = false;
	}

	if (!$rs) {
		$errFields[] = 'ALS_ERR';
	}

	// Check for subdomains limit

	if (imscp_limit_check($rdata['max_sub_cnt'])) {
		$rs = reseller_checkServiceItemsLimit($rdata['max_sub_cnt'], $rsub_current,
											  $usub_current, $usub_uf, tr('Subdomains'));
	} else {
		set_page_message(tr('Incorrect limit for %s.', tr('subdomains')), 'error');
		$rs = false;
	}

	if (!$rs) {
		$errFields[] = 'SUB_ERR';
	}

	// Check for mail accounts limit

	if (imscp_limit_check($rdata['max_mail_cnt'])) {
		$rs = reseller_checkServiceItemsLimit($rdata['max_mail_cnt'], $rmail_current,
											  $umail_current, $umail_uf, tr('Mail'));
	} else {
		set_page_message(tr('Incorrect limit for %s.', tr('mail accounts')), 'error');
		$rs = false;
	}

	if (!$rs) {
		$errFields[] = 'MAIL_ERR';
	}

	// Check for Ftp accounts limit

	if (imscp_limit_check($rdata['max_ftp_cnt'])) {
		$rs = reseller_checkServiceItemsLimit($rdata['max_ftp_cnt'], $rftp_current,
											  $uftp_current, $uftp_uf, tr('FTP'));
	} else {
		set_page_message(tr('Incorrect limit for %s.', tr('Ftp accounts')), 'error');
		$rs = false;
	}

	if (!$rs) {
		$errFields[] = 'FTP_ERR';
	}

	 // Check for databases limit

	if (!$rs = imscp_limit_check($rdata['max_sql_db_cnt'])) {
		set_page_message(tr('Incorrect limit for %s.', tr('SQL databases')), 'error');
	} elseif ($rdata['max_sql_db_cnt'] == -1 && $rdata['max_sql_user_cnt'] != -1) {
		set_page_message(tr('SQL databases limit is <i>disabled</i> but SQL users limit is not.'), 'error');
		$rs = false;
	} else {
		$rs = reseller_checkServiceItemsLimit($rdata['max_sql_db_cnt'], $rsql_db_current,
											  $usql_db_current, $usql_db_uf, tr('SQL databases'));
	}

	if (!$rs) {
		$errFields[] = 'SQLD_ERR';
	}

	// Check for SQL users limit

	if (!$rs = imscp_limit_check($rdata['max_sql_user_cnt'])) {
		set_page_message(tr('Incorrect limit for %s.', tr('SQL users')), 'error');
	} else if ($rdata['max_sql_db_cnt'] != -1 && $rdata['max_sql_user_cnt'] == -1) {
		set_page_message(tr('SQL users limit is <i>disabled</i> but SQL databases limit is not.'), 'error');
		$rs = false;
	} else {
		$rs = reseller_checkServiceItemsLimit($rdata['max_sql_user_cnt'], $rsql_user_current,
											  $usql_user_current, $usql_user_uf, tr('SQL Users'));
	}

	if (!$rs) {
		$errFields[] = 'SQLU_ERR';
	}

	// Check for traffic limit

	if (imscp_limit_check($rdata['max_traff_amnt'], null)) {
		$rs = reseller_checkServiceItemsLimit($rdata['max_traff_amnt'], $rtraff_current,
											  $utraff_current / 1024 / 1024, $utraff_uf,
											  tr('Web Traffic'));
	} else {
		set_page_message(tr('Incorrect limit for %s.', tr('traffic')), 'error');
		$rs = false;
	}

	if (!$rs) {
		$errFields[] = 'TRF_ERR';
	}

	 // Check for new disk space limit

	if (imscp_limit_check($rdata['max_disk_amnt'], null)) {
		$rs = reseller_checkServiceItemsLimit($rdata['max_disk_amnt'], $rdisk_current,
							   $udisk_current / 1024 / 1024, $udisk_uf,
							   tr('Disk storage'));
	} else {
		set_page_message(tr('Incorrect limit for %s.', tr('disk space')), 'error');
		$rs = false;
	}

	if (!$rs) {
		$errFields[] = 'DISK_ERR';
	}

	// Check for IP(s) assignment

	if ($rdata['reseller_ips'] == '') {
		set_page_message(tr('You must assign at least one IP per reseller.'), 'error');
	}

	_reseller_checkUserIpData($rdata['edit_id'], $rdata['rip_lst'], $rdata['reseller_ips']);

	// Check for PHP directives

	/** @var $phpini iMSCP_PHPini */
	$phpini = iMSCP_PHPini::getInstance();

	if (!$phpini->setRePerm('phpiniPostMaxSize', $rdata['php_ini_max_post_max_size'])) {
		set_page_message(tr("Max value for the PHP %s directive is out of range.", 'post_max_size'), 'error');
	}

	if (!$phpini->setRePerm('phpiniUploadMaxFileSize', $rdata['php_ini_max_upload_max_filesize'])) {
		set_page_message(tr("Max value for the PHP %s directive is out of range.", 'upload_max_filesize'), 'error');
	}

	if (!$phpini->setRePerm('phpiniMaxExecutionTime', $rdata['php_ini_max_max_execution_time'])) {
		set_page_message(tr("Max value for the PHP %s directive is out of range.", 'max_execution_time'), 'error');
	}

	if (!$phpini->setRePerm('phpiniMemoryLimit', $rdata['php_ini_max_memory_limit'])) {
		set_page_message(tr("Max value for the PHP %s directive is out of range.", 'memory_limit'), 'error');
	}

	if (!$phpini->setRePerm('phpiniMaxInputTime', $rdata['php_ini_max_max_input_time'])) {
		set_page_message(tr("Max value for the PHP %s directive is out of range.", 'memory_limit'), 'error');
	}

	// Any error found?
	if(Zend_Session::namespaceIsset('pageMessages')) {
		return false;
	}

	return true;
}

/**
 * Check reseller limit for a specific service.
 *
 * @param int $newLimit 			New limit (-1 for deactivation, 0 for unlimited,
 * 									$newLimit > 0 to limit items quantity)
 * @param int $assignedByReseller 	How many items are already assigned by reseller
 * @param int $usedByResellerClient How many items are already in use by reseller's
 * 									customers.
 * @param string $unlimitedService 	Tells whether or not  the service is set as
 * 									unlimited for a reseller's customer (_on_|_off_)
 * @param String $serviceName 		Service name for which new limit is verified
 * @return bool 					TRUE if new limit is valid, FALSE otherwise
 */
function reseller_checkServiceItemsLimit($newLimit, $assignedByReseller,
	$usedByResellerClient, $unlimitedService, $serviceName)
{
	$retVal = true;

	// We process only if the new limit value is not equal to 0 (unlimited)
	if ($newLimit != 0) {
		// The service is limited for all customers
		if ($unlimitedService == '_off_') {
			// If the new limit is < to the already used accounts/limits by users
			if ($newLimit < $usedByResellerClient && $newLimit != -1) {
				set_page_message(tr("This reseller's customers are using/have more/higher <b>%s</b> accounts/limits than the new limit you entered.", $serviceName),
								 'error');
				$retVal = false;

			// If the new limit is < to the already assigned accounts/limits by reseller
			} elseif ($newLimit < $assignedByReseller && $newLimit != -1) {
				set_page_message(tr('This reseller has already assigned more/higher <b>%s</b> accounts/limits than the new limit you entered.', $serviceName),
								 'error');
				$retVal = false;

			// If the new limit is -1 (disabled) and the already used accounts/limits by users is greater 0
			} elseif ($newLimit == -1 && $usedByResellerClient > 0) {
				set_page_message(tr("This reseller's customers are using/have more/higher <b>%s</b> accounts/limits than the new limit you entered.", $serviceName),
								 'error');
				$retVal = false;

			// If the new limit is -1 (disabled) and the already assigned accounts/limits by reseller is greater 0
			} elseif ($newLimit == -1 && $assignedByReseller > 0) {
				set_page_message(tr('This reseller has already assigned more/higher <b>%s</b> accounts/limits than the new limit you entered.', $serviceName),
								 'error');
				$retVal = false;
			}

			// One or more reseller's customers have unlimited rights
		} elseif ($newLimit != 0) {
			set_page_message(
				tr(
					'This reseller has customer(s) with unlimited rights for the <b>%s</b> service.',
					$serviceName
				),
				'error'
			);
			set_page_message(tr('If you want to limit the reseller, you must first limit its customers.'), 'error');
			$retVal = false;
		}
	}

	return $retVal;
}

/**
 * Checks user Ip data.
 *
 * @access private
 * @param int $reseller_id reselller unique identifier
 * @param string $r_ips reseller Ips
 * @param string $u_ips users Ips
 * @return void
 */
function _reseller_checkUserIpData($reseller_id, $r_ips, $u_ips)
{
	if ($r_ips != $u_ips) {
		$rip_array = explode(';', $r_ips);

		for ($i = 0, $cnt_rip_array = count($rip_array) - 1; $i < $cnt_rip_array; $i++) {
			$ip = $rip_array[$i];

			if (!preg_match("/$ip;/", $u_ips)) {
				$ip_num = '';
				$ip_name = '';

				if (have_reseller_ip_users($reseller_id, $ip, $ip_num, $ip_name)) {
					$ip_msg = $ip_name ? "$ip_num ($ip_name)" : $ip_num;

					set_page_message(
						tr('This reseller has domains assigned to the <b>%s</b> address.', $ip_msg), 'error'
					);
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
function get_reseller_prop($reseller_id)
{
	$query = "
		SELECT
			`admin_name`, `fname`, `lname`, `firm`, `zip`, `city`, `state`,
			`country`, `email`, `phone`, `fax`, `street1`, `street2`, `max_dmn_cnt`,
			`max_sub_cnt`, `max_als_cnt`, `max_mail_cnt`, `max_ftp_cnt`,
			`max_sql_db_cnt`, `max_sql_user_cnt`, `max_traff_amnt`, `max_disk_amnt`,
			`software_allowed`, `softwaredepot_allowed`, `websoftwaredepot_allowed`,
			r.`support_system` AS support_system, r.`customer_id` AS customer_id,
			`reseller_ips` AS rip_lst, `gender`, `php_ini_system`,
			`php_ini_al_disable_functions`, `php_ini_al_allow_url_fopen`,
			`php_ini_al_register_globals`, `php_ini_al_display_errors`, `php_ini_max_post_max_size`,
			`php_ini_max_upload_max_filesize`, `php_ini_max_max_execution_time`,
			`php_ini_max_max_input_time`, `php_ini_max_memory_limit`
		FROM
			`admin` AS a, `reseller_props` AS r
		WHERE
			a.`admin_id` = ?
		AND
			r.`reseller_id` = a.`admin_id`
	";
	$stmt = exec_query($query, $reseller_id);

	if ($stmt->rowCount() == 0) {
		set_page_message(tr('The reseller account you trying to edit does not exist.'), 'error');
		redirectTo('manage_users.php');
	}

	$rdata = array();

	foreach ($stmt->fields as $fname => $value) {
		if (!is_int($fname)) {
			$rdata[$fname] = $value;
		}
	}

	return $rdata;
}

/**
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $resellerIpList Reseller IP list
 * @return string
 */
function get_servers_ips($tpl, $resellerIpList)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`ip_id`, `ip_number`, `ip_domain`
		FROM
			`server_ips`
		ORDER BY
			`ip_number`
	";
	$stmt = exec_query($query);

	$i = 0;
	$reseller_ips = '';

	if ($stmt->recordCount() == 0) {
		$tpl->assign(
			array(
				 'RSL_IP_MESSAGE' => tr('Reseller IP list is empty.'),
				 'RSL_IP_LIST' => ''
			)
		);

		$tpl->parse('RSL_IP_MESSAGE', 'rsl_ip_message');
	} else {
		$tpl->assign(array(
						  'TR_RSL_IP_NUMBER' => tr('No.'),
						  'TR_RSL_IP_ASSIGN' => tr('Assign'),
						  'TR_RSL_IP_LABEL' => tr('Label'),
						  'TR_RSL_IP_IP' => tr('Number')
					 ));

		while (!$stmt->EOF) {
			$ip_id = $stmt->fields['ip_id'];

			$ip_var_name = "ip_$ip_id";
			$ip_item_assigned = '';

			if (isset($_POST['uaction']) &&
				$_POST['uaction'] == 'update_reseller'
			) {
				if (isset($_POST[$ip_var_name]) &&
					$_POST[$ip_var_name] == 'asgned'
				) {
					$ip_item_assigned = $cfg->HTML_CHECKED;
					$reseller_ips .= "$ip_id;";
				} else {
					$ip_item_assigned = '';
				}
			} else {
				if (preg_match("/$ip_id\;/", $resellerIpList) == 1) {
					$ip_item_assigned = $cfg->HTML_CHECKED;
					$reseller_ips .= "$ip_id;";
				}
			}

			$tpl->assign(array(
							  'RSL_IP_NUMBER' => $i + 1,
							  'RSL_IP_LABEL' => $stmt->fields['ip_domain']
								  ? $stmt->fields['ip_domain'] : '',
							  'RSL_IP_IP' => $stmt->fields['ip_number'],
							  'RSL_IP_CKB_NAME' => $ip_var_name,
							  'RSL_IP_CKB_VALUE' => 'asgned',
							  'RSL_IP_ITEM_ASSIGNED' => $ip_item_assigned
						 ));

			$tpl->parse('RSL_IP_ITEM', '.rsl_ip_item');
			$stmt->moveNext();

			$i++;
		}

		$tpl->parse('RSL_IP_LIST', 'rsl_ip_list');
		$tpl->assign('RSL_IP_MESSAGE', '');
	}

	return $reseller_ips;
}

/**
 *
 * @param  int $reseller_id Reseller unique identifier
 * @param  $ip
 * @param  $ip_num
 * @param  string $ip_name
 * @return bool
 */
function have_reseller_ip_users($reseller_id, $ip, &$ip_num, &$ip_name)
{
	$query = "SELECT `admin_id` FROM `admin` WHERE `created_by` = ?";
	$stmt1 = exec_query($query, $reseller_id);

	if ($stmt1->rowCount() == 0) {
		return false;
	}

	while (!$stmt1->EOF) {
		$query = "
			SELECT
				`domain`.`domain_id`, `server_ips`.`ip_number`, `server_ips`.`ip_domain`
			FROM
				`domain`, `server_ips`
			WHERE
				`domain`.`domain_created_id` = ?
			AND
				`server_ips`.`ip_id` = `domain`.`domain_ip_id`
			AND
				`server_ips`.`ip_id` = ?
		";
		$stmt2 = exec_query($query, array($reseller_id, $ip));

		if ($stmt2->rowCount() != 0) {
			$ip_num = $stmt2->fields['ip_number'];
			$ip_name = $stmt2->fields['ip_domain'];

			return true;
		}

		$stmt1->moveNext();
	}

	return false;
}

/**
 * Update the reseller additional data and properties
 *
 * @return void
 */
function update_reseller()
{
	// Get needed data
	$rdata =& get_data();

	// Updating personal data

	$query = "
		UPDATE
			`admin`
		SET
			`fname` = ?, `lname` = ?, `firm` = ?, `zip` = ?, `city` = ?, `state` = ?,
			`country` = ?, `email` = ?, `phone` = ?, `fax` = ?, `street1` = ?,
			`street2` = ?, `gender` = ?
		WHERE
			`admin_id` = ?
	";

	$qparams = array($rdata['fname'], $rdata['lname'], $rdata['firm'],
					 $rdata['zip'], $rdata['city'], $rdata['state'],
					 $rdata['country'], $rdata['email'], $rdata['phone'],
					 $rdata['fax'], $rdata['street1'], $rdata['street2'],
					 $rdata['gender'], $rdata['edit_id']);

	if (!empty($_POST['pass0'])) {
		$query = str_replace('`fname`', '`admin_pass` = ?, `fname`', $query);
		array_unshift($qparams, crypt_user_pass($_POST['pass0']));
	}

	exec_query($query, $qparams);

	// Updating software installer proeperties

	if ($rdata['software_allowed'] == 'no') {
		$query_user = "
 			UPDATE
 				`domain`
 			SET
 				`domain_software_allowed` = ?
 			WHERE
 				`domain_created_id` = ?
 		";
		exec_query($query_user, array(
									 $rdata['softwaredepot_allowed'],
									 $rdata['edit_id']));
	}

	if ($rdata['websoftwaredepot_allowed'] == 'no') {
		$query = "
 			SELECT
 				`software_id`
			FROM
				`web_software`
			WHERE
				`software_depot` = 'yes'
			AND
				`reseller_id` = ?
 		";
		$stmt = exec_query($query, array($rdata['edit_id']));

		if ($stmt->rowCount() > 0) {
			while (!$stmt->EOF) {
				$update = "
					UPDATE
						`web_software_inst`
					SET
						`software_res_del` = '1'
					WHERE
						`software_id` = ?
				";
				exec_query($update, array($stmt->fields['software_id']));

				$stmt->MoveNext();
			}

			$delete_rights = "
				DELETE FROM
					`web_software`
				WHERE
					`software_depot` = 'yes'
				AND
					`reseller_id` = ?
			";
			exec_query($delete_rights, array($rdata['edit_id']));
		}
	}

	/** Updating reseller's properties */

	$query = "
		UPDATE
			`reseller_props`
		SET
			`reseller_ips` = ?, `max_dmn_cnt` = ?, `max_sub_cnt` = ?, `max_als_cnt` = ?,
			`max_mail_cnt` = ?, `max_ftp_cnt` = ?, `max_sql_db_cnt` = ?, `max_sql_user_cnt` = ?,
			`max_traff_amnt` = ?, `max_disk_amnt` = ?, `support_system` = ?, `customer_id` = ?,
			`software_allowed` = ?, `softwaredepot_allowed` = ?, `websoftwaredepot_allowed` = ?,
			`php_ini_system` = ?, `php_ini_al_disable_functions` = ?,
			`php_ini_al_allow_url_fopen` = ?, `php_ini_al_register_globals` = ?,
			`php_ini_al_display_errors` = ?, `php_ini_max_post_max_size` = ?,
			`php_ini_max_upload_max_filesize` = ?, `php_ini_max_max_execution_time` = ?,
			`php_ini_max_max_input_time` = ?, `php_ini_max_memory_limit` = ?
		WHERE
			`reseller_id` = ?
	";

	exec_query($query, array(
							$rdata['reseller_ips'], $rdata['max_dmn_cnt'],
							$rdata['max_sub_cnt'], $rdata['max_als_cnt'],
							$rdata['max_mail_cnt'], $rdata['max_ftp_cnt'],
							$rdata['max_sql_db_cnt'], $rdata['max_sql_user_cnt'],
							$rdata['max_traff_amnt'], $rdata['max_disk_amnt'],
							$rdata['support_system'], $rdata['customer_id'],
							$rdata['software_allowed'],
							$rdata['softwaredepot_allowed'],
							$rdata['websoftwaredepot_allowed'],
							$rdata['php_ini_system'],
							$rdata['php_ini_al_disable_functions'],
							$rdata['php_ini_al_allow_url_fopen'],
							$rdata['php_ini_al_register_globals'],
							$rdata['php_ini_al_display_errors'],
							$rdata['php_ini_max_post_max_size'],
							$rdata['php_ini_max_upload_max_filesize'],
							$rdata['php_ini_max_max_execution_time'],
							$rdata['php_ini_max_max_input_time'],
							$rdata['php_ini_max_memory_limit'],
							$rdata['edit_id']));

}

/**
 * Get reseller data
 *
 * For performance reason, the data are cached.
 *
 * Note: The template instance must always be passed as
 * parameter of this function during the first call.
 *
 * @author Laurent Declercq (Nuxwin) <l.declercq@nuxwin.com>
 * @param iMSCP_pTemplate|bool $tpl OPTIONAL template instance
 * @return array reseller properties and additional data
 */
function &get_data($tpl = false)
{
	static $rdata = array();

	if (empty($rdata) && $tpl !== false) {
		// Update action
		if (isset($_POST['uaction']) && $_POST['uaction'] == 'update_reseller') {
			// Get clean input data
			$rdata = reseller_getCleanData();

			$query = "
				SELECT
					`admin_name`, `reseller_ips`
				FROM
					`admin`, `reseller_props`
				WHERE
					`admin_id` = ?
			";
			$stmt = exec_query($query, $rdata['edit_id']);

			if ($stmt->rowCount() <= 0) {
				redirectTo('manage_users.php');
			}

			$rdata['admin_name'] = $stmt->fields['admin_name'];
			$rdata['rip_lst'] = $stmt->fields['reseller_ips'];

		} else { // Default action

			// get clean reseller unique identifier
			$edit_id = clean_input($_GET['edit_id'], true);

			// Get reseller properties
			$rdata = get_reseller_prop($edit_id);
			$rdata['edit_id'] = $edit_id;
		}

		// Both cases
		$rdata['reseller_ips'] = get_servers_ips($tpl, $rdata['rip_lst']);
	}

	return $rdata;
}

/**
 * Input fields errors highlighting.
 *
 * Highlighting erroneous input fields with a appropriate color
 *
 * @author Laurent Declercq (Nuxwin) <l.declercq@nuxwin.com>
 * @Since r2587
 * @param iMSCP_pTemplate $tpl reference to the template instance
 * @param array &$errFields reference to the array of error fields indicators
 * @return void
 */
function reseller_generateInputFieldErrorsHighLighting($tpl, &$errFields)
{
	$fields = array(
		'PWD_ERR', 'PWDR_ERR', 'EMAIL_ERR', 'DMN_ERR', 'SUB_ERR', 'ALS_ERR',
		'MAIL_ERR', 'FTP_ERR', 'SQLD_ERR', 'SQLU_ERR', 'TRF_ERR', 'DISK_ERR');

	$l1 = 'border:1px rgb(233,0,0) solid;';

	foreach ($fields as $field) {
		$tpl->assign($field, (in_array($field, $errFields)) ? $l1 : '');
	}
}

/*******************************************************************************
 * Main script
 */

// include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login(__FILE__);

// Error fields
$errFields = array();

/** Dispatches the request */
if (isset($_REQUEST['edit_id']) && !isset($_POST['Cancel'])) {
	// Ajax request
	// TODO: move Header handler in future responses class
	if (is_xhr() && isset($_POST['uaction']) && $_POST['uaction'] == 'genpass') {

		// Overwrite the default header for Ajax request
		header('Content-Type: text/plain; charset=utf-8');
		// HTTP/1.1
		header('Cache-Control: no-cache, private');
		// backward compatibility for HTTP/1.0
		header('Pragma: no-cache');
		header("HTTP/1.0 200 Ok");
		echo passgen();
		exit;
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/reseller_edit.tpl');
	$tpl->define_dynamic('page_message', 'page');
	$tpl->define_dynamic('hosting_plans', 'page');
	$tpl->define_dynamic('rsl_ip_message', 'page');
	$tpl->define_dynamic('rsl_ip_list', 'page');
	$tpl->define_dynamic('rsl_ip_item', 'rsl_ip_list');

	$tpl->assign(array(
					  'TR_PAGE_TITLE' => tr('i-MSCP - Admin/Manage users/Edit Reseller'),
					  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
					  'THEME_CHARSET' => tr('encoding'),
					  'ISP_LOGO' => layout_getUserLogo()));

	gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
	gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');

	// First, we get needed data
	$rdata =& get_data($tpl);

	// Update action
	if (isset($_POST['uaction']) && $_POST['uaction'] == 'update_reseller') {

		// Checking for data received

		// If no error was occured during data checking, we can continue
		if (reseller_checkData($errFields)) {

			// Update reseller properties and additional data
			update_reseller();

			// Adds admin log entry
			write_log("{$_SESSION['user_logged']}: changes data/password for reseller: " .
					  "{$rdata['edit_username']}!", E_USER_NOTICE);

			// Send new authentication data to reseller if needed
			if (isset($_POST['send_data']) && !empty($_POST['pass0'])) {
				send_add_user_auto_msg(
					$_SESSION['user_id'], $rdata['edit_username'], $rdata['pass'],
					$rdata['email'], $rdata['fname'], $rdata['lname'],
					tr('Reseller'), $rdata['gender']
				);
			}

			// Status indicator for the front page message after update request
			// Todo remove the statement in manage_users.php
			//$_SESSION['user_updated'] = 1;

			// FIXME: Legacy from old code - Check if realy needed
			// $_SESSION['reseller_ips'] = $rdata['reseller_ips'];

			set_page_message(tr('Reseller account successfully updated.'), 'success');
			redirectTo('manage_users.php');

		} else { // An error was occured during data checking
			set_page_message(
				'<br />' .
				tr('One or more errors were found. Please, correct them and try again.'), 'error'
			);
		}
	} else { // Default action

		// Pre-check - possible inconsistency data
		reseller_checkData($errFields);

		if (isset($_SESSION['user_page_message'])) {
			set_page_message(
				'<br />' .
				tr('Reseller data inconsistency.') . ' ' .
				tr('Please, read the message(s) above and trying to correct.'), 'error'
			);
		}
	}
} else { // Not reseller id provided or cancel action
	// Prevent the 'update' message on the parent page after cancel action
	if (isset($_POST['Cancel']) && isset($_SESSION['user_updated'])) {
		unset($_SESSION['user_updated']);
	}

	redirectTo('manage_users.php');
}

// Input Fields Errors Highlighting
reseller_generateInputFieldErrorsHighLighting($tpl, $errFields);

if ($rdata['support_system'] == 'yes') {
	$support_yes = $cfg->HTML_CHECKED;
	$support_no = '';
} else {
	$support_no = $cfg->HTML_CHECKED;
	$support_yes = '';
}

/* @var $phpini iMSCP_PHPini */
$phpini = iMSCP_PHPini::getInstance();

// Reseller has php.ini feature enabled ?
if ($rdata['php_ini_system'] == 'yes') {
	// We build form with it own values
	$tpl->assign(array(
					  'PHPINI_MAX_MEMORY_LIMIT_VAL' => $rdata['php_ini_max_memory_limit'],
					  'PHPINI_MAX_UPLOAD_MAX_FILESIZE_VAL' => $rdata['php_ini_max_upload_max_filesize'],
					  'PHPINI_MAX_POST_MAX_SIZE_VAL' => $rdata['php_ini_max_post_max_size'],
					  'PHPINI_MAX_MAX_EXECUTION_TIME_VAL' => $rdata['php_ini_max_max_execution_time'],
					  'PHPINI_MAX_MAX_INPUT_TIME_VAL' => $rdata['php_ini_max_max_input_time']));
} else {
	$tpl->assign(array(
					  'PHPINI_MAX_MEMORY_LIMIT_VAL' => $phpini->getDataDefaultVal('phpiniMemoryLimit'),
					  'PHPINI_MAX_UPLOAD_MAX_FILESIZE_VAL' => $phpini->getDataDefaultVal('phpiniUploadMaxFileSize'),
					  'PHPINI_MAX_POST_MAX_SIZE_VAL' => $phpini->getDataDefaultVal('phpiniPostMaxSize'),
					  'PHPINI_MAX_MAX_EXECUTION_TIME_VAL' => $phpini->getDataDefaultVal('phpiniMaxExecutionTime'),
					  'PHPINI_MAX_MAX_INPUT_TIME_VAL' => $phpini->getDataDefaultVal('phpiniMaxInputTime')));
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
		 'TR_SOFTWARE_SUPP' => tr('i-MSCP application installer'),
		 'TR_SOFTWAREDEPOT_SUPP' => tr('Can use software depot'),
		 'TR_WEBSOFTWAREDEPOT_SUPP' => tr('Can use websoftware depot'),
		 'SOFTWARE_YES' => ($rdata['software_allowed'] == 'yes') ? $cfg->HTML_CHECKED
			 : '',
		 'SOFTWARE_NO' => ($rdata['software_allowed'] != 'yes') ? $cfg->HTML_CHECKED
			 : '',
		 'SOFTWAREDEPOT_YES' => ($rdata['softwaredepot_allowed'] == 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'SOFTWAREDEPOT_NO' => ($rdata['softwaredepot_allowed'] != 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'WEBSOFTWAREDEPOT_YES' => ($rdata['websoftwaredepot_allowed'] == 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'WEBSOFTWAREDEPOT_NO' => ($rdata['websoftwaredepot_allowed'] != 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_SYSTEM_YES' => ($rdata['php_ini_system'] == 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_SYSTEM_NO' => ($rdata['php_ini_system'] != 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_REGISTER_GLOBALS_YES' => ($rdata['php_ini_al_register_globals'] == 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_REGISTER_GLOBALS_NO' => ($rdata['php_ini_al_register_globals'] != 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_ALLOW_URL_FOPEN_YES' => ($rdata['php_ini_al_allow_url_fopen'] == 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_ALLOW_URL_FOPEN_NO' => ($rdata['php_ini_al_allow_url_fopen'] != 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_DISPLAY_ERRORS_YES' => ($rdata['php_ini_al_display_errors'] == 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_DISPLAY_ERRORS_NO' => ($rdata['php_ini_al_display_errors'] != 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_DISABLE_FUNCTIONS_YES' => ($rdata['php_ini_al_disable_functions'] == 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'PHPINI_AL_DISABLE_FUNCTIONS_NO' => ($rdata['php_ini_al_disable_functions'] != 'yes')
			 ? $cfg->HTML_CHECKED : '',
		 'TR_PHPINI_SYSTEM' => tr('PHP Editor'),
		 'TR_PHPINI_PERMISSION_HELP' => tr('If yes, means that the reseller can allow its customers to edit this directive.'),
		 'TR_PHPINI_AL_REGISTER_GLOBALS' => tr('Can edit the PHP %s directive', 'register_globals'),
		 'TR_PHPINI_AL_ALLOW_URL_FOPEN' => tr('Can edit the PHP %s directive',  'allow_url_fopen'),
		 'TR_PHPINI_AL_DISPLAY_ERRORS' => tr('Can edit the PHP %s directive', 'display_errors'),
		 'TR_PHPINI_AL_DISABLE_FUNCTIONS' => tr('Can edit the PHP %s directive', 'disable_functions'),
		 'TR_PHPINI_MAX_MEMORY_LIMIT' => tr('Max value for the %s PHP directive [MiB]', 'memory_limit'),
		 'TR_PHPINI_MAX_UPLOAD_MAX_FILESIZE' => tr('Max value for the %s PHP directive [MiB]', 'upload_max_filesize'),
		 'TR_PHPINI_MAX_POST_MAX_SIZE' => tr('Max value for the %s PHP directive [MiB]', 'post_max_size'),
		 'TR_PHPINI_MAX_MAX_EXECUTION_TIME' => tr('Max value for the %s PHP directive [Sec.]', 'max_execution_time'),
		 'TR_PHPINI_MAX_MAX_INPUT_TIME' => tr('Max value for the %s PHP directive [Sec.]', 'max_input_time'),
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
		 'TR_EVENT_NOTICE' => html_entity_decode(htmlspecialchars_decode(tr('i-MSCP NOTICE:\n\nThe `Enter` key is disabled for performance reasons!\nInstead, use the %s button to update the data.', '`' . tr('Update') . '`')), ENT_QUOTES, 'UTF-8'),

		 'USERNAME' => tohtml($rdata['admin_name']),
		 'EMAIL' => tohtml($rdata['email']),

		 'MAX_DOMAIN_COUNT' => intval($rdata['max_dmn_cnt']),
		 'MAX_SUBDOMAIN_COUNT' => intval($rdata['max_sub_cnt']),
		 'MAX_ALIASES_COUNT' => intval($rdata['max_als_cnt']),
		 'MAX_MAIL_USERS_COUNT' => intval($rdata['max_mail_cnt']),
		 'MAX_FTP_USERS_COUNT' => intval($rdata['max_ftp_cnt']),
		 'MAX_SQLDB_COUNT' => intval($rdata['max_sql_db_cnt']),
		 'MAX_SQL_USERS_COUNT' => intval($rdata['max_sql_user_cnt']),
		 'MAX_TRAFFIC_AMOUNT' => intval($rdata['max_traff_amnt']),
		 'MAX_DISK_AMOUNT' => intval($rdata['max_disk_amnt']),

		 'SUPPORT_YES' => $support_yes,
		 'SUPPORT_NO' => $support_no,

		 'CUSTOMER_ID' => tohtml($rdata['customer_id']),
		 'FIRST_NAME' => tohtml($rdata['fname']),
		 'LAST_NAME' => tohtml($rdata['lname']),
		 'VL_MALE' => (($rdata['gender'] == 'M') ? $cfg->HTML_SELECTED : ''),
		 'VL_FEMALE' => (($rdata['gender'] == 'F') ? $cfg->HTML_SELECTED : ''),
		 'VL_UNKNOWN' =>
		 (($rdata['gender'] == 'U') || (empty($rdata['gender']))
			 ? $cfg->HTML_SELECTED
			 : ''),
		 'FIRM' => tohtml($rdata['firm']),
		 'ZIP' => tohtml($rdata['zip']),
		 'CITY' => tohtml($rdata['city']),
		 'STATE' => $rdata['state'] === NULL ? '' : tohtml($rdata['state']),
		 'COUNTRY' => tohtml($rdata['country']),
		 'STREET_1' => tohtml($rdata['street1']),
		 'STREET_2' => tohtml($rdata['street2']),
		 'PHONE' => tohtml($rdata['phone']),
		 'FAX' => tohtml($rdata['fax']),

		 'EDIT_ID' => tohtml($rdata['edit_id']),

		 // The entries below are for Demo versions only
		 'PASSWORD_DISABLED' => tr('Password change is deactivated!'),
		 'DEMO_VERSION' => tr('Demo Version!')
	)
);

if (isset($_POST['genpass'])) {
	$tpl->assign('VAL_PASSWORD', passgen());
} else {
	$tpl->assign('VAL_PASSWORD', '');
}

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd,
											  new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
