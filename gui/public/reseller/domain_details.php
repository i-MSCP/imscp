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

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/domain_details.tpl',
		'page_message' => 'layout',
		't_software_support' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Domain/Details'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

$tpl->assign(
	array(
		'TR_DOMAIN_DETAILS' => tr('Domain details'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_DOMAIN_IP' => tr('Domain IP'),
		'TR_STATUS' => tr('Status'),
		'TR_PHP_SUPP' => tr('PHP support'),
		'TR_CGI_SUPP' => tr('CGI support'),
		'TR_BACKUP_SUPPORT' => tr('Backup support'),
		'TR_DNS_SUPP' => tr('Manual DNS support (EXPERIMENTAL)'),
		'TR_MYSQL_SUPP' => tr('MySQL support'),
		'TR_TRAFFIC' => tr('Traffic in MB'),
		'TR_DISK' => tr('Disk in MB'),
		'TR_FEATURE' => tr('Feature'),
		'TR_USED' => tr('Used'),
		'TR_LIMIT' => tr('Limit'),
		'TR_MAIL_ACCOUNTS' => tr('Mail accounts'),
		'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
		'TR_SQL_DB_ACCOUNTS' => tr('SQL databases'),
		'TR_SQL_USER_ACCOUNTS' => tr('SQL users'),
		'TR_SUBDOM_ACCOUNTS' => tr('Subdomains'),
		'TR_DOMALIAS_ACCOUNTS' => tr('Domain aliases'),
		'TR_UPDATE_DATA' => tr('Submit changes'),
		'TR_BACK' => tr('Back'),
		'TR_EDIT' => tr('Edit'),
		'TR_SOFTWARE_SUPP' => tr('i-MSCP application installer')));

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL === 'admin') {
	$tpl->assign('EDIT_OPTION', '');
}

generateNavigation($tpl);
get_reseller_software_permission ($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

// Get user id that comes for manage domain
if (!isset($_GET['domain_id'])) {
	set_page_message(tr('Domain not found.'), 'error');
	redirectTo('users.php?psi=last');
}

$editid = $_GET['domain_id'];
gen_detaildom_page($tpl, $_SESSION['user_id'], $editid);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();

// Begin function block

/**
 * @param $tpl
 * @param $user_id
 * @param $domain_id
 */
function gen_detaildom_page($tpl, $user_id, $domain_id) {

	$cfg = iMSCP_Registry::get('config');

	// Get domain data
	$query = "
		SELECT
			*,
			IFNULL(`domain_disk_usage`, 0) AS domain_disk_usage
		FROM
			`domain`
		WHERE
			`domain_id` = ?
	";

	$res = exec_query($query, $domain_id);

	$data = $res->fetchRow();

	if ($res->recordCount() <= 0) {
		redirectTo('users.php?psi=last');
	}
	// Get admin data
	$created_by = $_SESSION['user_id'];
	$query = "SELECT `admin_name` FROM `admin` WHERE `admin_id` = ? AND `created_by` = ?";
	$res1 = exec_query($query, array($data['domain_admin_id'], $created_by));

	$res1->fetchRow();
	if ($res1->recordCount() <= 0) {
		redirectTo('users.php?psi=last');
	}
	// Get IP info
	$query = "SELECT * FROM `server_ips` WHERE `ip_id` = ?";
	$ipres = exec_query($query, $data['domain_ip_id']);
	$ipdat = $ipres->fetchRow();
	// Get staus name
	$dstatus = translate_dmn_status($data['domain_status']);

	// Traffic diagram
	$fdofmnth = mktime(0, 0, 0, date("m"), 1, date("Y"));
	$ldofmnth = mktime(1, 0, 0, date("m") + 1, 0, date("Y"));
	$query = "
        SELECT
			IFNULL(SUM(`dtraff_web`), 0) AS dtraff_web,
			IFNULL(SUM(`dtraff_ftp`), 0) AS dtraff_ftp,
			IFNULL(SUM(`dtraff_mail`), 0) AS dtraff_mail,
			IFNULL(SUM(`dtraff_pop`),0) AS dtraff_pop
		FROM
			`domain_traffic`
		WHERE
			`domain_id` = ?
		AND
			`dtraff_time` > ?
		AND
			`dtraff_time` < ?
	";
	$res7 = exec_query($query, array($data['domain_id'], $fdofmnth, $ldofmnth));
	$dtraff = $res7->fetchRow();

	$sumtraff = $dtraff['dtraff_web'] + $dtraff['dtraff_ftp'] + $dtraff['dtraff_mail'] + $dtraff['dtraff_pop'];

	$query = "SELECT * FROM `server_ips` WHERE `ip_id` = ?";
	$res8 = exec_query($query, $data['domain_ip_id']);
	$ipdat = $res8->fetchRow();

	$domain_traffic_limit = $data['domain_traffic_limit'];
	$domain_all_traffic = $sumtraff;

	$traff = ($domain_all_traffic / 1024) / 1024;

	if ($domain_traffic_limit == 0) {
		$pr = 0;
	} else {
		$pr = ($traff / $domain_traffic_limit) * 100;
		$pr = sprintf("%.2f", $pr);
	}

	list($traffic_percent) = make_usage_vals(
		$domain_all_traffic, $domain_traffic_limit * 1024 * 1024
	);

	// Get disk status
	$domdu = $data['domain_disk_usage'];
	$domdl = $data['domain_disk_limit'];

	$tmp = ($domdu / 1024) / 1024;

	if ($domdu == 0) {
		$dpr = 0;
	} else if ($domdl == 0) {
		$dpr = 0;
	} else {
		$dpr = ($tmp / $domdl) * 100;
		$dpr = sprintf("%.2f", $dpr);
	}

	$domduh = sizeit($domdu);

	list($disk_percent) = make_usage_vals($domdu, $domdl * 1024 * 1024);

	// Get current mail count
	$query = "SELECT COUNT(`mail_id`) AS mcnt "
			. "FROM `mail_users` "
			. "WHERE `domain_id` = ? "
			. "AND `mail_type` NOT RLIKE '_catchall' ";
	if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES == 0) {
		$query .= "AND `mail_acc` != 'abuse' "
				. "AND `mail_acc` != 'postmaster' "
				. "AND `mail_acc` != 'webmaster'";
	}
	$res6 = exec_query($query, $data['domain_id']);

	$dat3 = $res6->fetchRow();
	$mail_limit = translate_limit_value($data['domain_mailacc_limit']);
	// FTP stat
	$query = "SELECT `gid` FROM `ftp_group` WHERE `groupname` = ?";
	$res4 = exec_query($query, $data['domain_name']);
	$ftp_gnum = $res4->rowCount();
	if ($ftp_gnum == 0) {
		$used_ftp_acc = 0;
	} else {
		$dat1 = $res4->fetchRow();
		$query = "SELECT COUNT(*) AS ftp_cnt FROM `ftp_users` WHERE `gid` = ?";
		$res5 = exec_query($query, $dat1['gid']);
		$dat2 = $res5->fetchRow();

		$used_ftp_acc = $dat2['ftp_cnt'];
	}
	$ftp_limit = translate_limit_value($data['domain_ftpacc_limit']);
	// Get sql database count
	$query = "SELECT COUNT(*) AS dnum FROM `sql_database` WHERE `domain_id` = ?";
	$res = exec_query($query, $data['domain_id']);
	$dat5 = $res->fetchRow();
	$sql_db = translate_limit_value($data['domain_sqld_limit']);
	// Get sql users count
	$query = "SELECT COUNT(u.`sqlu_id`) AS ucnt FROM sql_user u, sql_database d WHERE u.`sqld_id` = d.`sqld_id` AND d.`domain_id` = ?";
	$res = exec_query($query, $data['domain_id']);
	$dat6 = $res->fetchRow();
	$sql_users = translate_limit_value($data['domain_sqlu_limit']);
	// Get subdomain
	$query = "SELECT COUNT(`subdomain_id`) AS sub_num FROM `subdomain` WHERE `domain_id` = ?";
	$res1 = exec_query($query, $domain_id);
	$sub_num_data = $res1->fetchRow();
	$query = "SELECT COUNT(`subdomain_alias_id`) AS sub_num FROM `subdomain_alias` WHERE `alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)";
	$res1 = exec_query($query, $domain_id);
	$alssub_num_data = $res1->fetchRow();
	$sub_dom = translate_limit_value($data['domain_subd_limit']);
	// Get domain aliases
	$query = "SELECT COUNT(*) AS alias_num FROM `domain_aliasses` WHERE `domain_id` = ?";
	$res1 = exec_query($query, $domain_id);
	$alias_num_data = $res1->fetchRow();

	// Check if Backup support is available for this user
	switch($data['allowbackup']){
    case "full":
        $tpl->assign( array('VL_BACKUP_SUPPORT' => tr('Full')));
        break;
    case "sql":
        $tpl->assign( array('VL_BACKUP_SUPPORT' => tr('SQL')));
        break;
    case "dmn":
        $tpl->assign( array('VL_BACKUP_SUPPORT' => tr('Domain')));
        break;
    default:
        $tpl->assign( array('VL_BACKUP_SUPPORT' => tr('No')));
    }

	$dom_alias = translate_limit_value($data['domain_alias_limit']);
	// Fill in the fields
	$tpl->assign(
		array(
			'DOMAIN_ID' => $data['domain_id'],
			'VL_DOMAIN_NAME' => tohtml(decode_idna($data['domain_name'])),
			'VL_DOMAIN_IP' => tohtml($ipdat['ip_number'] . ' (' . $ipdat['ip_alias'] . ')'),
			'VL_STATUS' => $dstatus,
			'VL_PHP_SUPP' => ($data['domain_php'] == 'yes') ? tr('Enabled') : tr('Disabled'),
			'VL_CGI_SUPP' => ($data['domain_cgi'] == 'yes') ? tr('Enabled') : tr('Disabled'),
			'VL_DNS_SUPP' => ($data['domain_dns'] == 'yes') ? tr('Enabled') : tr('Disabled'),
			'VL_MYSQL_SUPP' => ($data['domain_sqld_limit'] >= 0) ? tr('Enabled') : tr('Disabled'),
			'VL_TRAFFIC_PERCENT' => $traffic_percent > 100 ? 100 : $traffic_percent,
			'VL_TRAFFIC_USED' => sizeit($domain_all_traffic),
			'VL_TRAFFIC_LIMIT' => sizeit($domain_traffic_limit, 'MB'),
			'VL_DISK_PERCENT' => $disk_percent > 100 ? 100 : $disk_percent,
			'VL_DISK_USED' => $domduh,
			'VL_DISK_LIMIT' => sizeit($data['domain_disk_limit'], 'MB'),
			'VL_MAIL_ACCOUNTS_USED' => $dat3['mcnt'],
			'VL_MAIL_ACCOUNTS_LIIT' => $mail_limit,
			'VL_FTP_ACCOUNTS_USED' => $used_ftp_acc,
			'VL_FTP_ACCOUNTS_LIIT' => $ftp_limit,
			'VL_SQL_DB_ACCOUNTS_USED' => $dat5['dnum'],
			'VL_SQL_DB_ACCOUNTS_LIIT' => $sql_db,
			'VL_SQL_USER_ACCOUNTS_USED' => $dat6['ucnt'],
			'VL_SQL_USER_ACCOUNTS_LIIT' => $sql_users,
			'VL_SUBDOM_ACCOUNTS_USED' => $sub_num_data['sub_num'] + $alssub_num_data['sub_num'],
			'VL_SUBDOM_ACCOUNTS_LIIT' => $sub_dom,
			'VL_DOMALIAS_ACCOUNTS_USED' => $alias_num_data['alias_num'],
			'VL_DOMALIAS_ACCOUNTS_LIIT' => $dom_alias,
			'VL_SOFTWARE_SUPP' => ($data['domain_software_allowed'] == 'yes') ? tr('Enabled') : tr('Disabled')));
} // end of load_user_data();
