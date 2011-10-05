<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @version		SVN: $Id$
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


/************************************************************************************
 * Script functions
 */

/**
 *
 * @param $num
 * @param $limit
 * @return string|Translated
 */
function gen_num_limit_msg($num, $limit)
{
	if ($limit == -1) {
		return tr('Disabled');
	} elseif ($limit == 0) {
		return $num . ' / ' . tr('Unlimited');
	} else {
		return $num . ' / ' . $limit;
	}
}

/**
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function gen_system_message($tpl)
{
	$user_id = $_SESSION['user_id'];

	$query = '
		SELECT
			COUNT(`ticket_id`) AS cnum
		FROM
			`tickets`
		WHERE
			(`ticket_to` = ? OR `ticket_from` = ?)
		AND
			`ticket_status` IN (\'2\')
		AND
			`ticket_reply` = 0
	';
	$stmt = exec_query($query, array($user_id, $user_id));

	if ($stmt->fields('cnum') == 0) {
		$tpl->assign(array('MSG_ENTRY' => ''));
	} else {
		$tpl->assign(array(
						  'TR_NEW_MSGS' => tr('You have <b>%d</b> new answer to your support questions', $stmt->fields('cnum')),
						  'TR_VIEW' => tr('View')));

		$tpl->parse('MSG_ENTRY', 'msg_entry');
	}
}

/**
 * Generates traffic usage bar.
 *
 * @param iMSCP_pTemplate $tpl
 * @param $usage
 * @param $max_usage
 * @param $bars_max
 * @return void
 */
function client_generateTrafficUsageBar($tpl, $usage, $max_usage, $bars_max)
{
	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);

	if ($max_usage != 0) {
		$traffic_usage_data = tr('%1$d%% [%2$s of %3$s]',
								 $percent, sizeit($usage),
								 sizeit($max_usage));
	} else {
		$traffic_usage_data = tr('%1$d%% [%2$s of unlimited]',
								 $percent, sizeit($usage));
	}

	$tpl->assign(array(
					  'TRAFFIC_USAGE_DATA' => $traffic_usage_data,
					  'TRAFFIC_BARS' => $bars,
					  'TRAFFIC_PERCENT' => $percent > 100 ? 100 : $percent));

	if ($max_usage != 0 && $usage > $max_usage) {
		$tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your traffic limit.'));
	} else {
		$tpl->assign('TRAFFIC_WARNING', '');
	}
}

/**
 * Generates disk usage bar.
 *
 * @param iMSCP_pTemplate $tpl
 * @param $usage
 * @param $max_usage
 * @param $bars_max
 * @return void
 */
function client_generateDiskUsageBar($tpl, $usage, $max_usage, $bars_max)
{
	list($percent, $bars) = calc_bars($usage, $max_usage, $bars_max);

	if ($max_usage != 0) {
		$traffic_usage_data = tr('%1$s%% [%2$s of %3$s]', $percent, sizeit($usage), sizeit($max_usage));
	} else {
		$traffic_usage_data = tr('%1$s%% [%2$s of unlimited]', $percent, sizeit($usage));
	}

	$tpl->assign(array(
					  'DISK_USAGE_DATA' => $traffic_usage_data,
					  'DISK_BARS' => $bars,
					  'DISK_PERCENT' => $percent > 100 ? 100 : $percent));

	if ($max_usage != 0 && $usage > $max_usage) {
		$tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk limit.'));
	} else {
		$tpl->assign('DISK_WARNING', '');
	}
}

/**
 * Generates feature status.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function client_generateFeatureStatus($tpl)
{
	$domainProperties = get_domain_default_props($_SESSION['user_id'], true);

	$trYes = tr('Enabled');
	$trNo = tr('Disbled');

	$tpl->assign(
		array(
			 'PHP_FEATURE_STATUS' => ($domainProperties['domain_php'] == 'yes')
				 ? $trYes : $trNo,
			 'PHP_DIRECTIVES_EDITOR_STATUS' => ($domainProperties['phpini_perm_system'] == 'yes')
				 ? $trYes : $trNo,
			 'CGI_FEATURE_STATUS' => ($domainProperties['domain_cgi'] != 'no')
				 ? $trYes : $trNo,
			 'CUSTOM_DNS_RECORDS_FEATURE_STATUS' => ($domainProperties['domain_dns'] == 'yes')
				 ? $trYes : $trNo,
			 'APP_INSTALLER_FEATURE_STATUS' => ($domainProperties['domain_software_allowed'] == 'yes')
				 ? $trYes : $trNo,
		));

	// Check if backup support is available
	switch ($domainProperties['allowbackup']) {
		case 'full':
			$tpl->assign('BACKUP_FEATURE_STATUS', tr('Enabled for domain data and databases'));
			break;
		case 'sql':
			$tpl->assign('BACKUP_FEATURE_STATUS', tr('Enabled for SQL databases'));
			break;
		case 'dmn':
			$tpl->assign('BACKUP_FEATURE_STATUS', tr('Enabled for domain data'));
			break;
		default:
			$tpl->assign('BACKUP_FEATURE_STATUS', $trNo);
	}
}

/**
 * Calculate the usage traffic/ return array (persent/value)
 *
 * @param	int $domainId Domain unique identifier
 * @return array An where that contain traffic information
 */
function make_traff_usage($domainId)
{
	/*
	$query = 'SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?';
	$stmt = exec_query($query, $domain_id);

	$domain_id = $stmt->fields('domain_id');
	*/

	$query = 'SELECT `domain_traffic_limit` FROM `domain` WHERE `domain_id` = ?';
	$stmt = exec_query($query, $domainId);

	$data1 = $stmt->fetchRow();

	$fdofmnth = mktime(0, 0, 0, date('m'), 1, date('Y'));
	$ldofmnth = mktime(1, 0, 0, date('m') + 1, 0, date('Y'));

	$query = '
		SELECT
			IFNULL(SUM(`dtraff_web`) + SUM(`dtraff_ftp`) + SUM(`dtraff_mail`) +
			SUM(`dtraff_pop`), 0) AS traffic
		FROM
			`domain_traffic`
		WHERE
			`domain_id` = ?
		AND
			`dtraff_time` > ?
		AND
			`dtraff_time` < ?
	';
	$stmt = exec_query($query, array($domainId, $fdofmnth, $ldofmnth));

	$traffic = ($stmt->fields['traffic'] / 1024) / 1024;

	if ($data1['domain_traffic_limit'] == 0) {
		$percent = 0;
	} else {
		$percent = ($traffic / $data1['domain_traffic_limit']) * 100;
		$percent = sprintf('%.2f', $percent);
	}

	return array($percent, $traffic);
}

/**
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param $user_id User unique identifier
 * @return void
 */
function gen_user_messages_label($tpl, $user_id)
{
	$query = '
		SELECT
			COUNT(`ticket_id`) AS `cnum`
		FROM
			`tickets`
		WHERE
			`ticket_from` = ?
		AND
			`ticket_status` = 2
	';
	$stmt = exec_query($query, $user_id);
	$num_question = $stmt->fields('cnum');

	if ($num_question == 0) {
		$tpl->assign(array(
						  'TR_NO_NEW_MESSAGES' => tr('You have no new support questions.'),
						  'MSG_ENTRY' => ''));
	} else {
		$tpl->assign(array(
						  'NO_MESSAGES' => '',
						  'TR_NEW_MSGS' => tr('You have <b>%d</b> new support questions', $num_question),
						  'TR_VIEW' => tr('View')));

		$tpl->parse('MSG_ENTRY', '.msg_entry');
	}
}

/**
 * Returns domain remaining time before expire.
 *
 * @param $domainExpireDate
 * @return array
 */
function client_getDomainRemainingTime($domainExpireDate)
{
	// needed for calculation
	$mi = 60;
	$h = $mi * $mi;
	$d = $h * 24;
	$mo = $d * 30;
	$y = $d * 365;

	// calculation of: years, month, days, hours, minutes, seconds
	$difftime = $domainExpireDate - time();
	$years = floor($difftime / $y);
	$difftime = $difftime % $y;
	$month = floor($difftime / $mo);
	$difftime = $difftime % $mo;
	$days = floor($difftime / $d);
	$difftime = $difftime % $d;
	$hours = floor($difftime / $h);
	$difftime = $difftime % $h;
	$minutes = floor($difftime / $mi);
	$difftime = $difftime % $mi;
	$seconds = $difftime;

	return array($years, $month, $days, $hours, $minutes, $seconds);
}

/************************************************************************************
 * Main script
 */
// Include core libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login(__FILE__, $cfg->PREVENT_EXTERNAL_LOGIN_CLIENT);

$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(array(
						  'page' => $cfg->CLIENT_TEMPLATE_PATH . '/index.tpl',
						  'logged_from' => 'page',
						  'page_message' => 'page',
						  'msg_entry' => 'page',
						  'alternative_domain_url' => 'page',
						  'traffic_warning' => 'page',
						  'disk_warning' => 'page'));

$tpl->assign(array(
				  'TR_PAGE_TITLE' => tr('i-MSCP - Client/Main Index'),
				  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
				  'THEME_CHARSET' => tr('encoding'),
				  'ISP_LOGO' => layout_getUserLogo(),
				  'TR_TITLE_GENERAL_INFORMATION' => tr('General information')));


gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_general_information.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_general_information.tpl');
gen_logged_from($tpl);
gen_system_message($tpl);


$domainProperties = get_domain_default_props($_SESSION['user_id'], true);

/*
list($dmn_id, $dmn_name, $dmn_gid, $dmn_uid, $dmn_created_id, $dmn_created,
	$dmn_expires, $dmn_last_modified, $dmn_mailacc_limit, $dmn_ftpacc_limit,
	$dmn_traff_limit, $dmn_sqld_limit, $dmn_sqlu_limit, $dmn_status, $dmn_als_limit,
	$dmn_subd_limit, $dmn_ip_id, $dmn_disk_limit, $dmn_disk_usage, $dmn_php, $dmn_cgi,
	$backup, $dns, $dmn_software_allowed
) = get_domain_default_props($_SESSION['user_id']);
*/

list(
	$sub_cnt, $als_cnt, $mail_acc_cnt, $ftp_acc_cnt, $sqld_acc_cnt, $sqlu_acc_cnt
) = get_domain_running_props_cnt($domainProperties['domain_id']);

$dtraff_pr = 0;
$domainTrafficUsage = 0;
$domainTrafficLimit = $domainProperties['domain_traffic_limit'] * 1024 * 1024;

list($dtraff_pr, $domainTrafficUsage) = make_traff_usage($domainProperties['domain_id']);

$domainDiskLimit = $domainProperties['domain_disk_limit'] * 1024 * 1024;

client_generateTrafficUsageBar($tpl, $domainTrafficUsage * 1024 * 1024, $domainTrafficLimit, 400);
client_generateDiskUsageBar($tpl, $domainProperties['domain_disk_usage'], $domainDiskLimit, 400);
gen_user_messages_label($tpl, $_SESSION['user_id']);
client_generateFeatureStatus($tpl);

$account_name = decode_idna($_SESSION['user_logged']);

if ($domainProperties['domain_expires'] == 0) {
	$dmn_expires_date = tr('No set');
} else {
	$date_formt = $cfg->DATE_FORMAT;
	$dmn_expires_date =
		'( <strong style="text-decoration:underline;">' .
		date($date_formt, $domainProperties['domain_expires']) . '</strong> )';
}

list(
	$years, $month, $days, $hours, $minutes, $seconds
) = client_getDomainRemainingTime($domainProperties['domain_expires']);

if (time() < $domainProperties['domain_expires']) {
	if (($years > 0) && ($month > 0) && ($days <= 14)) {
		$tpl->assign(
			'DMN_EXPIRES', $years . ' Years, ' . $month . ' Month, ' . $days . ' Days');
	} else {
		$tpl->assign(
			'DMN_EXPIRES', '<span style="color:red">' . $years . ' Years, ' .
						   $month . ' Month, ' . $days . ' Days</span>');
	}
} elseif ($domainProperties['domain_expires'] != 0) {
	$tpl->assign('DMN_EXPIRES', '<span style="color:red">' . tr('Domain is expired') . '</span> ');
} else {
	$tpl->assign('DMN_EXPIRES', '');
}

if ($domainProperties['domain_status'] == $cfg->ITEM_OK_STATUS) {
	$tpl->assign('DOMAIN_ALS_URL',
				 "http://{$cfg->SYSTEM_USER_PREFIX}" .
				 ($cfg->SYSTEM_USER_MIN_UID + $_SESSION['user_id']) .
				 ".{$_SERVER['SERVER_NAME']}");
} else {
	$tpl->assign('ALTERNATIVE_DOMAIN_URL', '');
}

$tpl->assign(array(
				  'TR_DOMAIN_DATA' => tr('Domain data'),
				  'TR_ACCOUNT_NAME' => tr('Account name'),
				  'DOMAIN_NAME' => tohtml($domainProperties['domain_name']),
				  'TR_DOMAIN_NAME' => tr('Domain name'),
				  'TR_DMN_TMP_ACCESS' => tr('Alternative URL to reach your website'),
				  'TR_DOMAIN_EXPIRES_DATE' => tr('Domain expire date'),
				  'DOMAIN_EXPIRES_DATE' => $dmn_expires_date,

				  'TR_FEATURE_NAME' => tr('Feature name'),
				  'TR_FEATURE_STATUS' => tr('Status'),

				  'TR_DOMAIN_ALIASES_FEATURE' => tr('Domain aliases'),
				  'DOMAIN_ALIASES_FEATURE_STATUS' => gen_num_limit_msg($als_cnt, 0),

				  'SUBDOMAINS_FEATURE_STATUS' => gen_num_limit_msg($sub_cnt, 0),
				  'TR_SUBDOMAINS_FEATURE' => tr('Subdomains') . (($domainProperties['domain_alias_limit'] != -1) ? '<br />(<small>' . tr('Including domain aliases subdomains') . '</small>)' : ''),

				  'TR_FTP_ACCOUNTS_FEATURE' => tr('FTP accounts'),
				  'FTP_ACCOUNTS_FEATURE_STATUS' => gen_num_limit_msg($ftp_acc_cnt, 0),

				  'TR_MAIL_ACCOUNTS_FEATURE' => tr('Mail accounts'),
				  'MAIL_ACCOUNTS_FEATURE_STATUS' => gen_num_limit_msg($mail_acc_cnt, 0),

				  'TR_SQL_DATABASES_FEATURE' => tr('SQL databases'),
				  'SQL_DATABASE_FEATURE_STATUS' => gen_num_limit_msg($sqld_acc_cnt, 0),

				  'TR_SQL_USERS_FEATURE' => tr('SQL users'),
				  'SQL_USERS_FEATURE_STATUS' => gen_num_limit_msg($sqlu_acc_cnt, 0),

				  'TR_PHP_SUPPORT_FEATURE' => tr('PHP support'),
				  'TR_PHP_DIRECTIVES_EDITOR_SUPPORT_FEATURE' => tr('PHP directives editor'),
				  'TR_CGI_SUPPORT_FEATURE' => tr('CGI support'),
				  'TR_CUSTOM_DNS_RECORDS_FEATURE' => tr('Custom DNS records support'),
				  'TR_APP_INSTALLER_FEATURE' => tr('Application installer'),
				  'TR_BACKUP_FEATURE' => tr('Backup support'),

				  'TR_MESSAGES' => tr('Support system'),
				  'TR_TRAFFIC_USAGE' => tr('Traffic usage'),
				  'TR_DISK_USAGE' => tr('Disk usage')));

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd,
											  new iMSCP_Events_Response($tpl));

$tpl->prnt();
