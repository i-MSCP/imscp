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
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$reseller_id = $_SESSION['user_id'];

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	$order_id = $_GET['order_id'];
} else {
	set_page_message(tr('Wrong order ID.'), 'error');
	redirectTo('orders.php');
	exit; // Useless but avoid IDE warning about possibled undefined variable
}

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL === 'admin') {
	$query = "SELECT * FROM `orders` WHERE `id` = ? AND `status` = 'update'";
	$stmt = exec_query($query, $order_id);
} else {
	$query = "SELECT * FROM `orders` WHERE `id` = ? AND `user_id` = ? AND `status` = 'update'";
	$stmt = exec_query($query, array($order_id, $reseller_id));
}

if (!$stmt->rowCount()) {
	set_page_message(tr('Permission deny.'), 'error');
	redirectTo('orders.php');
}

$hpid = $stmt->fields['plan_id'];
$customer_id = $stmt->fields['customer_id'];
$dmn_id = get_user_domain_id($customer_id);

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL === 'admin') {
	$query = "SELECT `props` FROM `hosting_plans` WHERE `id` = ?";
	$stmt = exec_query($query, $hpid);
} else {
	$query = "SELECT `props` FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";
	$stmt = exec_query($query, array($reseller_id, $hpid));
}

$data = $stmt->fetchRow();
$props = $data['props'];

$_SESSION['ch_hpprops'] = $props;

if (!reseller_limits_check($reseller_id, $hpid)) {
	set_page_message(tr('Order Canceled: resellers limit(s) exceeded.'), 'error');
	unset($_SESSION['domain_ip']);
	redirectTo('orders.php');
}

unset($_SESSION["ch_hpprops"]);

list(
	$domain_php, $domain_cgi, $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk, $backup, $domain_dns
) = explode(';', $props);

$domain_php = preg_replace('/\_/', '', $domain_php);
$domain_cgi = preg_replace('/\_/', '', $domain_cgi);
$domain_dns = preg_replace('/\_/', '', $domain_dns);

$default_mails = 0;

if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
	$query = "
		SELECT
			COUNT(`mail_id`) `cnt`
		FROM
			`mail_users`
		WHERE
			`domain_id` = ?
		AND
			(`mail_acc` = 'abuse' OR `mail_acc` = 'postmaster' OR `mail_acc` = 'webmaster')
	";
	$stmt = exec_query($query, $dmn_id);
	$default_mails = $stmt->fields['cnt'];
	$mail += $default_mails;
}

if (!imscp_limit_check($sub, -1)) {
	set_page_message(tr('Incorrect subdomains limit.'), 'error');
}

if (!imscp_limit_check($als, -1)) {
	set_page_message(tr('Incorrect aliases limit.'), 'error');
}

if (!imscp_limit_check($mail, -1)) {
	set_page_message(tr('Incorrect mail accounts limit.'), 'error');
}

if (!imscp_limit_check($ftp, -1)) {
	set_page_message(tr('Incorrect FTP accounts limit.'), 'error');
}

if (!imscp_limit_check($sql_db, -1)) {
	set_page_message(tr('Incorrect SQL users limit.'), 'error');
}

if (!imscp_limit_check($sql_user, -1)) {
	set_page_message(tr('Incorrect SQL databases limit.'), 'error');
}

if (!imscp_limit_check($traff, null)) {
	set_page_message(tr('Incorrect traffic limit.'), 'error');
}

if (!imscp_limit_check($disk, null)) {
	set_page_message(tr('Incorrect disk quota limit.'), 'error');
}

list(
	$usub_current, $usub_max, $uals_current, $uals_max, $umail_current, $umail_max, $uftp_current, $uftp_max,
	$usql_db_current, $usql_db_max, $usql_user_current, $usql_user_max, $utraff_max, $udisk_max
) = generate_user_props($dmn_id);


$resellerProperties = imscp_getResellerProperties($reseller_id);

list($a, $b, $c, $d, $e, $f, $utraff_current, $udisk_current, $i, $h) = generate_user_traffic($dmn_id);

if (empty($ed_error)) {
	calculate_user_dvals($sub, $usub_current, $usub_max, $rsub_current, $resellerProperties['max_sub_cnt'], tr('Subdomain'));
	calculate_user_dvals($als, $uals_current, $uals_max, $rals_current, $resellerProperties['max_als_cnt'], tr('Alias'));
	calculate_user_dvals($mail, $umail_current, $umail_max, $rmail_current, $resellerProperties['max_mail_cnt'], tr('Mail'));
	calculate_user_dvals($ftp, $uftp_current, $uftp_max, $rftp_current, $resellerProperties['max_ftp_cnt'], tr('FTP'));
	calculate_user_dvals($sql_db, $usql_db_current, $usql_db_max, $rsql_db_current, $resellerProperties['max_sql_db_cnt'], tr('SQL Database'));
	calculate_user_dvals($sql_user, $usql_user_current, $usql_user_max, $rsql_user_current, $resellerProperties['max_sql_user_cnt'], tr('SQL User'));
	calculate_user_dvals($traff, $utraff_current / 1024 / 1024 , $utraff_max, $rtraff_current, $resellerProperties['max_traff_amnt'], tr('Traffic'));
	calculate_user_dvals($disk, $udisk_current / 1024 / 1024, $udisk_max, $rdisk_current, $resellerProperties['max_disk_amnt'], tr('Disk'));
}

if (!Zend_Session::namespaceIsset('pageMessages')) {
	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
		$umail_max -= $default_mails;
	}

	$user_props = "$usub_current;$usub_max;";
	$user_props .= "$uals_current;$uals_max;";
	$user_props .= "$umail_current;$umail_max;";
	$user_props .= "$uftp_current;$uftp_max;";
	$user_props .= "$usql_db_current;$usql_db_max;";
	$user_props .= "$usql_user_current;$usql_user_max;";
	$user_props .= "$utraff_max;";
	$user_props .= "$udisk_max;";
	$user_props .= "$domain_php;";
	$user_props .= "$domain_cgi;";
	$user_props .= "$backup;";
	$user_props .= "$domain_dns";

	update_user_props($dmn_id, $user_props);

	$reseller_props = "{$resellerProperties['current_dmn_cnt']};{$resellerProperties['max_dmn_cnt']};";
	$reseller_props .= "{$resellerProperties['current_sub_cnt']};{$resellerProperties['max_sub_cnt']};";
	$reseller_props .= "{$resellerProperties['current_als_cnt']};{$resellerProperties['max_als_cnt']};";
	$reseller_props .= "{$resellerProperties['current_mail_cnt']};{$resellerProperties['max_mail_cnt']};";
	$reseller_props .= "{$resellerProperties['current_ftp_cnt']};{$resellerProperties['max_ftp_cnt']};";
	$reseller_props .= "{$resellerProperties['current_sql_db_cnt']};{$resellerProperties['max_sql_db_cnt']};";
	$reseller_props .= "{$resellerProperties['current_sql_user_cnt']};{$resellerProperties['max_sql_user_cnt']};";
	$reseller_props .= "{$resellerProperties['current_traff_amnt']};{$resellerProperties['max_traff_amnt']};";
	$reseller_props .= "{$resellerProperties['current_disk_amnt']};{$resellerProperties['max_disk_amnt']}";

	update_reseller_props($reseller_id, $reseller_props);

	// update the sql quotas, too
	$query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?";
	$stmt = exec_query($query, $dmn_id);
	$temp_dmn_name = $stmt->fields['domain_name'];

	$query = "SELECT COUNT(`name`) AS cnt FROM `quotalimits` WHERE `name` = ?";
	$stmt = exec_query($query, $temp_dmn_name);

	if ($stmt->fields['cnt'] > 0) {
		// we need to update it
		if ($disk == 0) {
			$dlim = 0;
		} else {
			$dlim = $disk * 1024 * 1024;
		}

		$query = "UPDATE `quotalimits` SET `bytes_in_avail` = ? WHERE `name` = ?";
		$stmt = exec_query($query, array($dlim, $temp_dmn_name));
	}

	$query = "UPDATE `orders` SET `status` = ? WHERE `id` = ?";
	exec_query($query, array('added', $order_id));

	set_page_message(tr('Domain properties successfully updated.'), 'success');
	redirectTo('users.php?psi=last');
} else {
	redirectTo('orders.php');
}

/**
 * @param $data
 * @param $u
 * @param $umax
 * @param $r
 * @param $rmax
 * @param $obj
 * @return mixed
 */
function calculate_user_dvals($data, $u, &$umax, &$r, $rmax, $obj) {
	if ($rmax == -1 && $umax >= 0) {
		if ($u > 0) {
			set_page_message(tr('The <em>%s</em> service cannot be disabled.', $obj) . tr('There are <em>%s</em> records on system.', $obj), 'error');
			return;
		} else if ($data != -1){
			set_page_message(tr('The <em>%s</em> have to be disabled.', $obj) . tr('The admin has <em>%s</em> disabled on this system.', $obj), 'error');
			return;
		} else {
			$umax = $data;
		}
		return;
	} else if ($rmax == 0 && $umax == -1) {
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
				set_page_message(tr('The <em>%s</em> service cannot be disabled.', $obj) . tr('There are <em>%s</em> records on the system.', $obj), 'error');
			} else {
				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			return;
		} else if ($data > 0) {
			if ($u > $data) {
				set_page_message(tr('The <em>%s</em> service cannot be limited.', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system.', $obj), 'error');
			} else {
				$umax = $data;

				$r += $umax;
			}

			return;
		}
	} else if ($rmax == 0 && $umax > 0) {
		if ($data == -1) {
			if ($u > 0) {
				set_page_message(tr('The <em>%s</em> service cannot be disabled.', $obj) . tr('There are <em>%s</em> records on the system.', $obj), 'error');
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
				set_page_message(tr('The <em>%s</em> service cannot be limited.', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system.', $obj), 'error');
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
			set_page_message(tr('The <em>%s</em> service cannot be unlimited.', $obj) . tr('There are reseller limits for the <em>%s</em> service.', $obj), 'error');

			return;
		} else if ($data > 0) {
			if ($r + $data > $rmax) {
				set_page_message(tr('The <em>%s</em> service cannot be limited.', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service.', $obj), 'error');
			} else {
				$r += $data;

				$umax = $data;
			}

			return;
		}
	} else if ($rmax > 0 && $umax == 0) {
		// We Can't Get Here! This clone is present only for
		// sample purposes;
		if ($data == -1) {
		} else if ($data == 0) {
		} else if ($data > 0) {
		}
	} else if ($rmax > 0 && $umax > 0) {
		if ($data == -1) {
			if ($u > 0) {
				set_page_message(tr('The <em>%s</em> service cannot be disabled.', $obj) . tr('There are <em>%s</em> records on the system.', $obj), 'error');
			} else {
				$r -= $umax;

				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			set_page_message(tr('The <em>%s</em> service cannot be unlimited.', $obj) . tr('There are reseller limits for the <em>%s</em> service.', $obj), 'error');

			return;
		} else if ($data > 0) {
			if ($u > $data) {
				set_page_message(tr('The <em>%s</em> service cannot be limited.', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system.', $obj), 'error');
			} else {
				if ($umax > $data) {
					$data_dec = $umax - $data;

					$r -= $data_dec;
				} else {
					$data_inc = $data - $umax;

					if ($r + $data_inc > $rmax) {
						set_page_message(tr('The <em>%s</em> service cannot be limited.', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service.', $obj), 'error');

						return;
					}

					$r += $data_inc;
				}

				$umax = $data;
			}

			return;
		}
	}
} // end of calculate_user_dvals()
