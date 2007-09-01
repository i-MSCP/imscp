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

$reseller_id = $_SESSION['user_id'];

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	$order_id = $_GET['order_id'];
} else {
	set_page_message(tr('Wrong order ID!'));
	header("Location: orders.php");
	die();
}

if (isset($cfg['HOSTING_PLANS_LEVEL']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin') {
	$query = <<<SQL_QUERY
	select
		*
	from
		orders
	where
			id = ?
		and
			status = 'update'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($order_id));
} else {
	$query = <<<SQL_QUERY
	select
		*
	from
		orders
	where
			id = ?
		and
			user_id = ?
		and
			status = 'update'
SQL_QUERY;

	$rs = exec_query($sql, $query, array($order_id, $reseller_id));
}

if ($rs->RecordCount() == 0) {
	set_page_message(tr('Permission deny!'));
	header('Location: orders.php');
	die();
}

$hpid = $rs->fields['plan_id'];
$customer_id = $rs->fields['customer_id'];
$dmn_id = get_user_domain_id($sql, $customer_id);
// lets check the reseller limits
$err_msg = '';

if (isset($cfg['HOSTING_PLANS_LEVEL']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin') {
	$query = "select props from hosting_plans where id = ?";
	$res = exec_query($sql, $query, array($hpid));
} else {
	$query = "select props from hosting_plans where reseller_id = ? and id = ?";
	$res = exec_query($sql, $query, array($reseller_id, $hpid));
}
$data = $res->FetchRow();
$props = $data['props'];

$_SESSION["ch_hpprops"] = $props;

reseller_limits_check($sql, $err_msg, $reseller_id, $hpid);
if (!empty($err_msg)) {
	set_page_message($err_msg);
	unset($_SESSION['domain_ip']);
	header('Location: orders.php');
	die();
}
unset($_SESSION["ch_hpprops"]);

list($domain_php, $domain_cgi, $sub,
	$als, $mail, $ftp,
	$sql_db, $sql_user,
	$traff, $disk) = explode(";", $props);

$domain_php = preg_replace("/\_/", "", $domain_php);
$domain_cgi = preg_replace("/\_/", "", $domain_cgi);

$ed_error = '';

if (!ispcp_limit_check($sub, -1)) {
	$ed_error = tr('Incorrect subdomain count or no number!<br />');
}
if (!ispcp_limit_check($als, -1)) {
	$ed_error .= tr('Incorrect alias count or no number!<br />');
}
if (!ispcp_limit_check($mail, -1)) {
	$ed_error .= tr('Incorrect mail account count or no number!<br />');
}
if (!ispcp_limit_check($ftp, -1)) {
	$ed_error .= tr('Incorrect FTP account count or no number!<br />');
}
if (!ispcp_limit_check($sql_db, -1)) {
	$ed_error .= tr('Incorrect SQL user count or no number!<br />');
}
if (!ispcp_limit_check($sql_user, -1)) {
	$ed_error .= tr('Incorrect SQL database count or no number!<br />');
}
if (!ispcp_limit_check($traff, null)) {
	$ed_error .= tr('Incorrect traffic count or no number!<br />');
}
if (!ispcp_limit_check($disk, null)) {
	$ed_error .= tr('Incorrect disk count or no number!<br />');
}

list ($usub_current, $usub_max,
	$uals_current, $uals_max,
	$umail_current, $umail_max,
	$uftp_current, $uftp_max,
	$usql_db_current, $usql_db_max,
	$usql_user_current, $usql_user_max,
	$utraff_max, $udisk_max) = generate_user_props($dmn_id);

list ($rdmn_current, $rdmn_max,
	$rsub_current, $rsub_max,
	$rals_current, $rals_max,
	$rmail_current, $rmail_max,
	$rftp_current, $rftp_max,
	$rsql_db_current, $rsql_db_max,
	$rsql_user_current, $rsql_user_max,
	$rtraff_current, $rtraff_max,
	$rdisk_current, $rdisk_max
	) = get_reseller_default_props($sql, $reseller_id); //generate_reseller_props($reseller_id);

list ($a, $b, $c, $d, $e, $f, $utraff_current, $udisk_current, $i, $h) = generate_user_traffic($dmn_id);

if (empty($ed_error)) {
	calculate_user_dvals($sub, $usub_current, $usub_max, $rsub_current, $rsub_max, $ed_error, tr('Subdomain'));
	calculate_user_dvals($als, $uals_current, $uals_max, $rals_current, $rals_max, $ed_error, tr('Alias'));
	calculate_user_dvals($mail, $umail_current, $umail_max, $rmail_current, $rmail_max, $ed_error, tr('Mail'));
	calculate_user_dvals($ftp, $uftp_current, $uftp_max, $rftp_current, $rftp_max, $ed_error, tr('FTP'));
	calculate_user_dvals($sql_db, $usql_db_current, $usql_db_max, $rsql_db_current, $rsql_db_max, $ed_error, tr('SQL Database'));
	calculate_user_dvals($sql_user, $usql_user_current, $usql_user_max, $rsql_user_current, $rsql_user_max, $ed_error, tr('SQL User'));
	calculate_user_dvals($traff, $utraff_current / 1024 / 1024 , $utraff_max, $rtraff_current, $rtraff_max, $ed_error, tr('Traffic'));
	calculate_user_dvals($disk, $udisk_current / 1024 / 1024, $udisk_max, $rdisk_current, $rdisk_max, $ed_error, tr('Disk'));
}

if (empty($ed_error)) {
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
	$user_props .= "$domain_cgi";
	update_user_props($dmn_id, $user_props);

	$reseller_props = "$rdmn_current;$rdmn_max;";
	$reseller_props .= "$rsub_current;$rsub_max;";
	$reseller_props .= "$rals_current;$rals_max;";
	$reseller_props .= "$rmail_current;$rmail_max;";
	$reseller_props .= "$rftp_current;$rftp_max;";
	$reseller_props .= "$rsql_db_current;$rsql_db_max;";
	$reseller_props .= "$rsql_user_current;$rsql_user_max;";
	$reseller_props .= "$rtraff_current;$rtraff_max;";
	$reseller_props .= "$rdisk_current;$rdisk_max";

	update_reseller_props($reseller_id, $reseller_props);
	// update the sql quotas too
	$query = "select domain_name from domain where domain_id=?";
	$rs = exec_query($sql, $query, array($dmn_id));
	$temp_dmn_name = $rs->fields['domain_name'];

	$query = "SELECT count(name) as cnt from quotalimits where name=?";
	$rs = exec_query($sql, $query, array($temp_dmn_name));
	if ($rs->fields['cnt'] > 0) {
		// we need to update it
		if ($disk == 0) {
			$dlim = 0;
		} else {
			$dlim = $disk * 1024 * 1024;
		}

		$query = "UPDATE quotalimits SET bytes_in_avail=? WHERE name=?";
		$rs = exec_query($sql, $query, array($dlim, $temp_dmn_name));
	}

	$query = <<<SQL_QUERY
            update
                orders
            set
                status=?
            where
                id=?
SQL_QUERY;
	exec_query($sql, $query, array('added', $order_id));
	set_page_message(tr('Domain properties updated successfully!'));
	header('Location: users.php');
	die();
} else {
	set_page_message($ed_error);
	header('Location: orders.php');
	die();
}

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
				$err .= tr('<b>%s</b> Service can not be disabled! ', $obj) . tr('There are <b>%s</b> records on the system!', $obj);
			} else {
				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('<b>%s</b> Service can not be limited! ', $obj) . tr('Specified number is smaller than <b>%s</b> records, present on the system!', $obj);
			} else {
				$umax = $data;

				$r += $umax;
			}

			return;
		}
	} else if ($rmax == 0 && $umax > 0) {
		if ($data == -1) {
			if ($u > 0) {
				$err .= tr('<b>%s</b> Service can not be disabled! ', $obj) . tr('There are <b>%s</b> records on the system!', $obj);
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
				$err .= tr('<b>%s</b> Service can not be limited! ', $obj) . tr('Specified number is smaller than <b>%s</b> records, present on the system!', $obj);
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
			$err .= tr('<b>%s</b> Service can not be unlimited! ', $obj) . tr('There are reseller limits for the <b>%s</b> service!', $obj);

			return;
		} else if ($data > 0) {
			if ($r + $data > $rmax) {
				$err .= tr('<b>%s</b> Service can not be limited! ', $obj) . tr('You are exceeding reseller limits for the <b>%s</b> service!', $obj);
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
				$err .= tr('<b>%s</b> Service can not be disabled! ', $obj) . tr('There are <b>%s</b> records on the system!', $obj);
			} else {
				$r -= $umax;

				$umax = $data;
			}

			return;
		} else if ($data == 0) {
			$err .= tr('<b>%s</b> Service can not be unlimited! ', $obj) . tr('There are reseller limits for the <b>%s</b> service!', $obj);

			return;
		} else if ($data > 0) {
			if ($u > $data) {
				$err .= tr('<b>%s</b> Service can not be limited! ', $obj) . tr('Specified number is smaller than <b>%s</b> records, present on the system!', $obj);
			} else {
				if ($umax > $data) {
					$data_dec = $umax - $data;

					$r -= $data_dec;
				} else {
					$data_inc = $data - $umax;

					if ($r + $data_inc > $rmax) {
						$err .= tr('<b>%s</b> Service can not be limited! ', $obj) . tr('You are exceeding reseller limits for the <b>%s</b> service!', $obj);

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

?>