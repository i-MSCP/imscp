<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
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

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('RESELLER_TEMPLATE_PATH') . '/domain_edit.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('ip_entry', 'page');
$tpl->define_dynamic('logged_from', 'page');

$theme_color = Config::get('USER_INITIAL_THEME');

if (Config::exists('HOSTING_PLANS_LEVEL')
	&& Config::get('HOSTING_PLANS_LEVEL') === 'admin') {
	user_goto('users.php');
}

$tpl->assign(
	array(
		'TR_EDIT_DOMAIN_PAGE_TITLE'	=> tr('ispCP - Domain/Edit'),
		'THEME_COLOR_PATH'			=> "../themes/$theme_color",
		'THEME_CHARSET'				=> tr('encoding'),
		'ISP_LOGO'					=> get_logo($_SESSION['user_id'])
	)
);

/*
 *
 * static page messages.
 *
 */
$tpl->assign(
	array(
		'TR_EDIT_DOMAIN'				=> tr('Edit Domain'),
		'TR_DOMAIN_PROPERTIES'			=> tr('Domain properties'),
		'TR_DOMAIN_NAME'				=> tr('Domain name'),
		'TR_DOMAIN_EXPIRE'				=> tr('Domain expire'),
		'TR_DOMAIN_NEW_EXPIRE'			=> tr('New expire date'),
		'TR_DOMAIN_EXPIRE_UNCHANGED'	=> tr('Unchanged'),
		'TR_DOMAIN_EXPIRE_MIN_1_MONTH'	=> tr('- 1 Month'),
		'TR_DOMAIN_EXPIRE_PLUS_1_MONTH'	=> tr('+ 1 Month'),
		'TR_DOMAIN_EXPIRE_PLUS_2_MONTHS'=> tr('+ 2 Months'),
		'TR_DOMAIN_EXPIRE_PLUS_3_MONTHS'=> tr('+ 3 Months'),
		'TR_DOMAIN_EXPIRE_PLUS_6_MONTHS'=> tr('+ 6 Months'),
		'TR_DOMAIN_EXPIRE_PLUS_1_YEAR'	=> tr('+ 1 Year'),
		'TR_DOMAIN_EXPIRE_PLUS_2_YEARS'	=> tr('+ 2 Years'),
		'TR_DOMAIN_IP'					=> tr('Domain IP'),
		'TR_PHP_SUPP'					=> tr('PHP support'),
		'TR_CGI_SUPP'					=> tr('CGI support'),
		'TR_DNS_SUPP'					=> tr('Manual DNS support'),
		'TR_SUBDOMAINS'					=> tr('Max subdomains<br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_ALIAS'						=> tr('Max aliases<br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_MAIL_ACCOUNT'				=> tr('Mail accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_FTP_ACCOUNTS'				=> tr('FTP accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_SQL_DB'						=> tr('SQL databases limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_SQL_USERS'					=> tr('SQL users limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
		'TR_TRAFFIC'					=> tr('Traffic limit [MB] <br /><i>(0 unlimited)</i>'),
		'TR_DISK'						=> tr('Disk limit [MB] <br /><i>(0 unlimited)</i>'),
		'TR_USER_NAME'					=> tr('Username'),
		'TR_BACKUP'						=> tr('Backup'),
		'TR_BACKUP_DOMAIN'				=> tr('Domain'),
		'TR_BACKUP_SQL'					=> tr('SQL'),
		'TR_BACKUP_FULL'				=> tr('Full'),
		'TR_BACKUP_NO'					=> tr('No'),
		'TR_UPDATE_DATA'				=> tr('Submit changes'),
		'TR_CANCEL'						=> tr('Cancel'),
		'TR_YES'						=> tr('Yes'),
		'TR_NO'							=> tr('No'),
		'TR_DMN_EXP_HELP'				=> tr("In case 'Domain expire' is 'N/A', the expiration date will be set from today.")
	)
);

gen_reseller_mainmenu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/main_menu_users_manage.tpl');
gen_reseller_menu($tpl, Config::get('RESELLER_TEMPLATE_PATH') . '/menu_users_manage.tpl');

gen_logged_from($tpl);

gen_page_message($tpl);

if (isset($_POST['uaction']) && ('sub_data' === $_POST['uaction'])) {
	// Process data
	if (isset($_SESSION['edit_id'])) {
		$editid = $_SESSION['edit_id'];
	} else {
		unset($_SESSION['edit_id']);
		$_SESSION['edit'] = '_no_';

		user_goto('users.php');
	}

	if (check_user_data($tpl, $sql, $_SESSION['user_id'], $editid)) { // Save data to db
		$_SESSION['dedit'] = "_yes_";
		user_goto('users.php');
	}
	load_additional_data($_SESSION['user_id'], $editid);
} else {
	// Get user id that comes for edit
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	}

	load_user_data($_SESSION['user_id'], $editid);
	// $_SESSION['edit_ID'] = $editid;
	$_SESSION['edit_id'] = $editid;
	$tpl->assign('MESSAGE', "");
}

gen_editdomain_page($tpl);

// Begin function block

/**
 * Load data from sql
 */
function load_user_data($user_id, $domain_id) {
	$sql = Database::getInstance();

	global $domain_name, $domain_expires, $domain_ip, $php_sup;
	global $cgi_supp , $sub, $als;
	global $mail, $ftp, $sql_db;
	global $sql_user, $traff, $disk;
	global $username;
	global $dns_supp;

	$query = "
		SELECT
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_created_id` = ?
	";

	$rs = exec_query($sql, $query, array($domain_id, $user_id));

	if ($rs->RecordCount() == 0) {
		set_page_message(tr('User does not exist or you do not have permission to access this interface!'));

		user_goto('users.php');
	}

	list($a, $sub,
		$b, $als,
		$c, $mail,
		$d, $ftp,
		$e, $sql_db,
		$f, $sql_user,
		$traff, $disk
	) = generate_user_props($domain_id);;

	load_additional_data($user_id, $domain_id);
} // End of load_user_data()

/**
 * Load additional data
 */
function load_additional_data($user_id, $domain_id) {
	$sql = Database::getInstance();
	global $domain_name, $domain_expires, $domain_ip, $php_sup;
	global $cgi_supp, $username, $allowbackup;
	global $dns_supp;
	// Get domain data
	$query = "
		SELECT
			`domain_name`,
			`domain_expires`,
			`domain_ip_id`,
			`domain_php`,
			`domain_cgi`,
			`domain_admin_id`,
			`allowbackup`,
			`domain_dns`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$res = exec_query($sql, $query, $domain_id);
	$data = $res->FetchRow();

	$domain_name		= $data['domain_name'];

	$domain_expires = $data['domain_expires'];
	$_SESSION['domain_expires'] = $domain_expires;

	if ($domain_expires == 0) {
		$domain_expires = tr('N/A');
	} else {
		$date_formt = Config::get('DATE_FORMAT');
		$domain_expires = date($date_formt, $domain_expires);
	}

	$domain_ip_id		= $data['domain_ip_id'];
	$php_sup			= $data['domain_php'];
	$cgi_supp			= $data['domain_cgi'];
	$allowbackup			= $data['allowbackup'];
	$domain_admin_id	= $data['domain_admin_id'];
	$dns_supp			= $data['domain_dns'];
	// Get IP of domain
	$query = "
		SELECT
			`ip_number`,
			`ip_domain`
		FROM
			`server_ips`
		WHERE
			`ip_id` = ?
	";

	$res = exec_query($sql, $query, array($domain_ip_id));
	$data = $res->FetchRow();

	$domain_ip = $data['ip_number'] . '&nbsp;(' . $data['ip_domain'] . ')';
	// Get username of domain
	$query = "
		SELECT
			`admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		AND
			`admin_type` = 'user'
		AND
			`created_by` = ?
	";

	$res = exec_query($sql, $query, array($domain_admin_id, $user_id));
	$data = $res->FetchRow();

	$username = $data['admin_name'];
} // End of load_additional_data()

/**
 * Show user data
 */
function gen_editdomain_page(&$tpl) {
	global $domain_name, $domain_expires, $domain_ip, $php_sup;
	global $cgi_supp , $sub, $als;
	global $mail, $ftp, $sql_db;
	global $sql_user, $traff, $disk;
	global $username, $allowbackup;
	global $dns_supp;
	// Fill in the fields
	$domain_name = decode_idna($domain_name);

	$username = decode_idna($username);

	generate_ip_list($tpl, $_SESSION['user_id']);

	if ($allowbackup === 'dmn') {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => 'selected="selected"',
				'BACKUP_SQL' => '',
				'BACKUP_FULL' => '',
				'BACKUP_NO' => '',
			)
		);
	} else if ($allowbackup === 'sql')  {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => '',
				'BACKUP_SQL' => 'selected="selected"',
				'BACKUP_FULL' => '',
				'BACKUP_NO' => '',
			)
		);
	} else if ($allowbackup === 'full')  {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => '',
				'BACKUP_SQL' => '',
				'BACKUP_FULL' => 'selected="selected"',
				'BACKUP_NO' => '',
			)
		);
	} else if ($allowbackup === 'no')  {
		$tpl->assign(
			array(
				'BACKUP_DOMAIN' => '',
				'BACKUP_SQL' => '',
				'BACKUP_FULL' => '',
				'BACKUP_NO' => 'selected="selected"',
			)
		);
	}

	$tpl->assign(
		array(
			'PHP_YES'				=> ($php_sup == 'yes') ? 'selected="selected"' : '',
			'PHP_NO'				=> ($php_sup != 'yes') ? 'selected="selected"' : '',
			'CGI_YES'				=> ($cgi_supp == 'yes') ? 'selected="selected"' : '',
			'CGI_NO'					=> ($cgi_supp != 'yes') ? 'selected="selected"' : '',
			'DNS_YES'				=> ($dns_supp == 'yes') ? 'selected="selected"' : '',
			'DNS_NO'				=> ($dns_supp != 'yes') ? 'selected="selected"' : '',
			'VL_DOMAIN_NAME'		=> $domain_name,
			'VL_DOMAIN_EXPIRE'		=> $domain_expires,
			'VL_DOMAIN_IP'			=> $domain_ip,
			'VL_DOM_SUB'			=> $sub,
			'VL_DOM_ALIAS'			=> $als,
			'VL_DOM_MAIL_ACCOUNT'	=> $mail,
			'VL_FTP_ACCOUNTS'		=> $ftp,
			'VL_SQL_DB'				=> $sql_db,
			'VL_SQL_USERS'			=> $sql_user,
			'VL_TRAFFIC'			=> $traff,
			'VL_DOM_DISK'			=> $disk,
			'VL_USER_NAME'			=> $username
		)
	);
} // End of gen_editdomain_page()

/**
 * Check input data
 */
function check_user_data(&$tpl, &$sql, $reseller_id, $user_id) {
	global $sub, $als, $mail, $ftp;
	global $sql_db, $sql_user, $traff;
	global $disk, $sql, $domain_ip, $domain_php;
	global $domain_cgi, $allowbackup;
	global $domain_dns;
	global $domain_expires, $domain_new_expire;

	$domain_new_expire = clean_input($_POST['dmn_expire']);
	$sub = clean_input($_POST['dom_sub']);
	$als = clean_input($_POST['dom_alias']);
	$mail = clean_input($_POST['dom_mail_acCount']);
	$ftp = clean_input($_POST['dom_ftp_acCounts']);
	$sql_db = clean_input($_POST['dom_sqldb']);
	$sql_user = clean_input($_POST['dom_sql_users']);
	$traff = clean_input($_POST['dom_traffic']);
	$disk = clean_input($_POST['dom_disk']);
	// $domain_ip = $_POST['domain_ip'];
	$domain_php		= preg_replace("/\_/", "", $_POST['domain_php']);
	$domain_cgi		= preg_replace("/\_/", "", $_POST['domain_cgi']);
	$domain_dns		= preg_replace("/\_/", "", $_POST['domain_dns']);
	$allowbackup		= preg_replace("/\_/", "", $_POST['backup']);

	$ed_error = '';

	if (!ispcp_limit_check($sub, -1)) {
		$ed_error .= tr('Incorrect subdomains limit!');
	}
	if (!ispcp_limit_check($als, -1)) {
		$ed_error .= tr('Incorrect aliases limit!');
	}
	if (!ispcp_limit_check($mail, -1)) {
		$ed_error .= tr('Incorrect mail accounts limit!');
	}
	if (!ispcp_limit_check($ftp, -1)) {
		$ed_error .= tr('Incorrect FTP accounts limit!');
	}
	if (!ispcp_limit_check($sql_db, -1)) {
		$ed_error .= tr('Incorrect SQL users limit!');
	}
	else if ($sql_db == -1 && $sql_user != -1) {
		$ed_error .= tr('SQL databases limit is <i>disabled</i>!');
	}
	if (!ispcp_limit_check($sql_user, -1)) {
		$ed_error .= tr('Incorrect SQL databases limit!');
	}
	else if ($sql_user == -1 && $sql_db != -1) {
		$ed_error .= tr('SQL users limit is <i>disabled</i>!');
	}
	if (!ispcp_limit_check($traff, null)) {
		$ed_error .= tr('Incorrect traffic limit!');
	}
	if (!ispcp_limit_check($disk, null)) {
		$ed_error .= tr('Incorrect disk quota limit!');
	}

	// $user_props = generate_user_props($user_id);
	// $reseller_props = generate_reseller_props($reseller_id);
	list($usub_current, $usub_max,
		$uals_current, $uals_max,
		$umail_current, $umail_max,
		$uftp_current, $uftp_max,
		$usql_db_current, $usql_db_max,
		$usql_user_current, $usql_user_max,
		$utraff_max, $udisk_max
	) = generate_user_props($user_id);

	$previous_utraff_max = $utraff_max;

	list($rdmn_current, $rdmn_max,
		$rsub_current, $rsub_max,
		$rals_current, $rals_max,
		$rmail_current, $rmail_max,
		$rftp_current, $rftp_max,
		$rsql_db_current, $rsql_db_max,
		$rsql_user_current, $rsql_user_max,
		$rtraff_current, $rtraff_max,
		$rdisk_current, $rdisk_max
	) = get_reseller_default_props($sql, $reseller_id); //generate_reseller_props($reseller_id);
	list($a, $b, $c, $d, $e, $f, $utraff_current, $udisk_current, $i, $h) = generate_user_traffic($user_id);

	if (empty($ed_error)) {
		calculate_user_dvals($sub, $usub_current, $usub_max, $rsub_current, $rsub_max, $ed_error, tr('Subdomain'));
		calculate_user_dvals($als, $uals_current, $uals_max, $rals_current, $rals_max, $ed_error, tr('Alias'));
		calculate_user_dvals($mail, $umail_current, $umail_max, $rmail_current, $rmail_max, $ed_error, tr('Mail'));
		calculate_user_dvals($ftp, $uftp_current, $uftp_max, $rftp_current, $rftp_max, $ed_error, tr('FTP'));
		calculate_user_dvals($sql_db, $usql_db_current, $usql_db_max, $rsql_db_current, $rsql_db_max, $ed_error, tr('SQL Database'));
	}

	if (empty($ed_error)) {
		$query = "
			SELECT
				COUNT(su.`sqlu_id`) AS cnt
			FROM
				`sql_user` AS su,
				`sql_database` AS sd
			WHERE
				su.`sqld_id` = sd.`sqld_id`
			AND
				sd.`domain_id` = ?
";

		$rs = exec_query($sql, $query, array($_SESSION['edit_id']));
		calculate_user_dvals($sql_user, $rs->fields['cnt'], $usql_user_max, $rsql_user_current, $rsql_user_max, $ed_error, tr('SQL User'));
	}

	if (empty($ed_error)) {
		calculate_user_dvals($traff, $utraff_current / 1024 / 1024 , $utraff_max, $rtraff_current, $rtraff_max, $ed_error, tr('Traffic'));
		calculate_user_dvals($disk, $udisk_current / 1024 / 1024, $udisk_max, $rdisk_current, $rdisk_max, $ed_error, tr('Disk'));
	}

	if (empty($ed_error)) {
		// Set domains status to 'change' to update mod_cband's limit
		if ($previous_utraff_max != $utraff_max) {
			$query = "UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_id` = ?";
			exec_query($sql, $query, array($user_id));
			$query = "UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `domain_id` = ?";
			exec_query($sql, $query, array($user_id));
			send_request();
		}

		$user_props = "$usub_current;$usub_max;";
		$user_props .= "$uals_current;$uals_max;";
		$user_props .= "$umail_current;$umail_max;";
		$user_props .= "$uftp_current;$uftp_max;";
		$user_props .= "$usql_db_current;$usql_db_max;";
		$user_props .= "$usql_user_current;$usql_user_max;";
		$user_props .= "$utraff_max;";
		$user_props .= "$udisk_max;";
		// $user_props .= "$domain_ip;";
		$user_props .= "$domain_php;";
		$user_props .= "$domain_cgi;";
		$user_props .= "$domain_dns";
		update_user_props($user_id, $user_props);

		$domain_expires = $_SESSION['domain_expires'];

		if ($domain_expires != 0 && $domain_new_expire != 0) {
			$domain_new_expire = $domain_expires + ($domain_new_expire * 2635200);
			update_expire_date($user_id, $domain_new_expire);
		} elseif ($domain_expires == 0 && $domain_new_expire != 0) {
			$domain_new_expire = time() + ($domain_new_expire * 2635200);
			update_expire_date($user_id, $domain_new_expire);
		}

		$reseller_props = "$rdmn_current;$rdmn_max;";
		$reseller_props .= "$rsub_current;$rsub_max;";
		$reseller_props .= "$rals_current;$rals_max;";
		$reseller_props .= "$rmail_current;$rmail_max;";
		$reseller_props .= "$rftp_current;$rftp_max;";
		$reseller_props .= "$rsql_db_current;$rsql_db_max;";
		$reseller_props .= "$rsql_user_current;$rsql_user_max;";
		$reseller_props .= "$rtraff_current;$rtraff_max;";
		$reseller_props .= "$rdisk_current;$rdisk_max";

		if (!update_reseller_props($reseller_id, $reseller_props)) {
			set_page_message(tr('Domain properties could not be updated!'));

			return false;
		}

		// Backup Settings
		$query = "UPDATE `domain` SET `allowbackup` = ? WHERE `domain_id` = ?";
		$rs = exec_query($sql, $query, array($allowbackup, $user_id));

		// update the sql quotas, too
		$query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?";
		$rs = exec_query($sql, $query, array($user_id));
		$temp_dmn_name = $rs->fields['domain_name'];

		$query = "SELECT COUNT(`name`) AS cnt FROM `quotalimits` WHERE `name` = ?";
		$rs = exec_query($sql, $query, array($temp_dmn_name));
		if ($rs->fields['cnt'] > 0) {
			// we need to update it
			if ($disk == 0) {
				$dlim = 0;
			} else {
				$dlim = $disk * 1024 * 1024;
			}

			$query = "UPDATE `quotalimits` SET `bytes_in_avail` = ? WHERE `name` = ?";
			$rs = exec_query($sql, $query, array($dlim, $temp_dmn_name));
		}

		set_page_message(tr('Domain properties updated successfully!'));

		return true;
	} else {
		$tpl->assign('MESSAGE', $ed_error);
		$tpl->parse('PAGE_MESSAGE', 'page_message');

		return false;
	}
} // End of check_user_data()

function calculate_user_dvals($data, $u, &$umax, &$r, $rmax, &$err, $obj) {
	if ($rmax == 0 && $umax == -1) {
		if ($data == -1) {
			return;
		} else if ($data == 0) {
			$umax = $data;
			return;
		} else if ($data > 0) {
			$umax = $data;
			$r += $umax;
			return;
		}
	} else if ($rmax == 0 && $umax == 0) {
		if ($data == -1) {
			if ($u > 0) {
				$err .= tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on system!', $obj);
			} else {
				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj);
			} else {
				$umax = $data;
				$r += $umax;
			}
			return;
		}
	} else if ($rmax == 0 && $umax > 0) {
		if ($data == -1) {
			if ($u > 0) {
				$err .= tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on the system!', $obj);
			} else {
				$r -= $umax;
				$umax = $data;
			}
			return;
		} else if ($data == 0) {
			$r -= $umax;
			$umax = $data;
			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj);
			} else {
				if ($umax > $data) {
					$data_dec = $umax - $data;
					$r -= $data_dec;
				} else {
					$data_inc = $data - $umax;
					$r += $data_inc;
				}
				$umax = $data;
			}
			return;
		}
	} else if ($rmax > 0 && $umax == -1) {
		if ($data == -1) {
			return;
		} else if ($data == 0) {
			$err .= tr('The <em>%s</em> service cannot be unlimited!', $obj) . tr('There are reseller limits for the <em>%s</em> service!', $obj);
			return;
		} else if ($data > 0) {
			if ($r + $data > $rmax) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service!', $obj);
			} else {
				$r += $data;

				$umax = $data;
			}

			return;
		}
	} else if ($rmax > 0 && $umax == 0) {
		// We Can't Get Here! This clone is present only for sample purposes;
		if ($data == -1) {
			die("FIXME: ". __FILE__ .":". __LINE__);
		} else if ($data == 0) {
			die("FIXME: ". __FILE__ .":". __LINE__);
		} else if ($data > 0) {
			die("FIXME: ". __FILE__ .":". __LINE__);
		}
	} else if ($rmax > 0 && $umax > 0) {
		if ($data == -1) {
			if ($u > 0) {
				$err .= tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on the system!', $obj);
			} else {
				$r -= $umax;
				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			$err .= tr('The <em>%s</em> service cannot be unlimited!', $obj) . tr('There are reseller limits for the <em>%s</em> service!', $obj);

			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj);
			} else {
				if ($umax > $data) {
					$data_dec = $umax - $data;
					$r -= $data_dec;
				} else {
					$data_inc = $data - $umax;

					if ($r + $data_inc > $rmax) {
						$err .= tr('The <em>%s</em> service cannot be limited!', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service!', $obj);
						return;
					}

					$r += $data_inc;
				}

				$umax = $data;
			}

			return;
		}
	}
} // End of calculate_user_dvals()

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
unset_messages();