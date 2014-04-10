<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Script functions
 */


/**
 * Generates limit.
 *
 * @param $num
 * @param $limit
 * @return string
 */
function gen_num_limit_msg($num, $limit)
{
	if ($limit == -1) {
		return '<span style="color: red;">' . tr('Disabled') . '</span>';
	} elseif ($limit == 0) {
		return $num . ' / ' . tr('Unlimited');
	} else {
		return $num . ' / ' . $limit;
	}
}

/**
 * Generate mail quota limit msg
 *
 * @return string
 */
function gen_mail_quota_limit_mgs()
{
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

	$stmt = exec_query(
		'SELECT SUM(`quota`) AS `quota` FROM `mail_users` WHERE `domain_id` = ? AND `quota` IS NOT NULL',
		$mainDmnProps['domain_id']
	);

	if ($mainDmnProps['mail_quota'] == 0) {
		return bytesHuman($stmt->fields['quota']) . ' / ' . tr('Unlimited');
	} else {
		return bytesHuman($stmt->fields['quota']) . ' / ' . bytesHuman($mainDmnProps['mail_quota']);
	}
}

/**
 * Generates notice for support system.
 *
 * @return void
 */
function client_generateSupportSystemNotices()
{
	$userId = $_SESSION['user_id'];

	// Check for support question answers
	$query = "
		SELECT
			COUNT(`ticket_id`) `cnt`
		FROM
			`tickets`
		WHERE
			`ticket_from` = ?
		AND
			`ticket_status` = '2'
		AND
			`ticket_reply` = '0'
	";
	$stmt = exec_query($query, $userId);

	if ($stmt->fields('cnt')) {
		set_page_message(tr('You received %d new answer(s) to your support questions.', '<b>' . $stmt->fields('cnt') . '</b>'), 'info');
	}
}

/**
 * Generates traffic usage bar.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param $usage
 * @param $maxUsage
 * @param $barMax
 * @return void
 */
function client_generateTrafficUsageBar($tpl, $usage, $maxUsage, $barMax)
{
	list($percent, $bars) = calc_bars($usage, $maxUsage, $barMax);

	if ($maxUsage != 0) {
		$traffic_usage_data = tr('%1$d%% [%2$s of %3$s]', $percent, bytesHuman($usage),
								 bytesHuman($maxUsage));
	} else {
		$traffic_usage_data = tr('%1$d%% [%2$s of unlimited]', $percent, bytesHuman($usage));
	}

	$tpl->assign(
		array(
			 'TRAFFIC_USAGE_DATA' => $traffic_usage_data,
			 'TRAFFIC_BARS' => $bars,
			 'TRAFFIC_PERCENT' => $percent > 100 ? 100 : $percent));

	if ($maxUsage != 0 && $usage > $maxUsage) {
		$tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your monthly traffic limit.'));
	} else {
		$tpl->assign('TRAFFIC_WARNING', '');
	}
}

/**
 * Generates disk usage bar.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param $usage
 * @param $maxUsage
 * @param $barMax
 * @return void
 */
function client_generateDiskUsageBar($tpl, $usage, $maxUsage, $barMax)
{
	list($percent, $bars) = calc_bars($usage, $maxUsage, $barMax);

	if ($maxUsage != 0) {
		$traffic_usage_data = tr(
			'%1$s%% [%2$s of %3$s]', $percent, bytesHuman($usage), bytesHuman($maxUsage));
	} else {
		$traffic_usage_data = tr(
			'%1$s%% [%2$s of unlimited]', $percent, bytesHuman($usage));
	}

	$tpl->assign(
		array(
			 'DISK_USAGE_DATA' => $traffic_usage_data,
			 'DISK_BARS' => $bars,
			 'DISK_PERCENT' => $percent > 100 ? 100 : $percent));

	if ($maxUsage != 0 && $usage > $maxUsage) {
		$tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk space limit.'));
	} else {
		$tpl->assign('DISK_WARNING', '');
	}
}

/**
 * Generates feature status.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 * @todo hide features that are not available for reseller
 */
function client_generateFeatureStatus($tpl)
{
	$trYes = '<span style="color: green;">' . tr('Enabled') . '</span>';
	$trNo = '<span style="color: red;">' . tr('Disabled') . '</span>';;

	$tpl->assign(
		array(
			 'DOMAIN_FEATURE_STATUS' =>  customerHasFeature('domain') ? $trYes : $trNo,
			 'PHP_FEATURE_STATUS' => customerHasFeature('php') ? $trYes : $trNo,
			 'PHP_DIRECTIVES_EDITOR_STATUS' => customerHasFeature('php_editor') ? $trYes : $trNo,
			 'CGI_FEATURE_STATUS' => customerHasFeature('cgi') ? $trYes : $trNo,
			 'CUSTOM_DNS_RECORDS_FEATURE_STATUS' => customerHasFeature('custom_dns_records') ? $trYes : $trNo,
             'EXTERNAL_MAIL_SERVERS_FEATURE_STATUS' => customerHasFeature('external_mail') ? $trYes : $trNo,
			 'APP_INSTALLER_FEATURE_STATUS' => customerHasFeature('aps') ? $trYes : $trNo,
             'WEBSTATS_FEATURE_STATUS' => customerHasFeature('webstats') ? $trYes : $trNo
        )
    );

	if (customerHasFeature('backup')) {
		$domainProperties = get_domain_default_props($_SESSION['user_id']);

		// Backup feature for customer can also be disabled by reseller via GUI
		switch ($domainProperties['allowbackup']) {
			case 'full':
				$tpl->assign(
					'BACKUP_FEATURE_STATUS', '<span style="color: green;">' . tr('Enabled for domain data and databases') . '</span>');
				break;
			case 'sql':
				$tpl->assign(
					'BACKUP_FEATURE_STATUS', '<span style="color: green;">' . tr('Enabled for SQL databases') . '</span>');
				break;
			case 'dmn':
				$tpl->assign(
					'BACKUP_FEATURE_STATUS', '<span style="color: green;">' . tr('Enabled for domain data') . '</span>');
				break;
			default:
				$tpl->assign('BACKUP_FEATURE_STATUS', $trNo);
		}
	} else {
		$tpl->assign('BACKUP_FEATURE_STATUS', $trNo);
	}
}

/**
 * Calculate traffic usage.
 *
 * @param int $domainId Domain unique identifier
 * @return array An array that contain traffic information
 */
function client_makeTrafficUsage($domainId)
{
	$domainProperties = get_domain_default_props($_SESSION['user_id']);
	$fdofmnth = mktime(0, 0, 0, date('m'), 1, date('Y'));
	$ldofmnth = mktime(1, 0, 0, date('m') + 1, 0, date('Y'));

	$query = '
		SELECT
			IFNULL(SUM(`dtraff_web`) + SUM(`dtraff_ftp`) + SUM(`dtraff_mail`) +
			SUM(`dtraff_pop`), 0) `traffic`
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

	if ($domainProperties['domain_traffic_limit'] == 0) {
		$percent = 0;
	} else {
		$percent = ($traffic / $domainProperties['domain_traffic_limit']) * 100;
		$percent = sprintf('%.2f', $percent);
	}

	return array($percent, $traffic);
}

/**
 * Returns domain remaining time before expire.
 *
 * @access private
 * @param $domainExpireDate
 * @return array
 */
function _client_getDomainRemainingTime($domainExpireDate)
{
	$mi = 60;
	$h = $mi * $mi;
	$d = $h * 24;
	$mo = $d * 30;
	$y = $d * 365;

	$difftime = $domainExpireDate - time();
	$years = floor($difftime / $y);

	$difftime = $difftime % $y;
	$month = floor($difftime / $mo);

	$difftime = $difftime % $mo;
	$days = floor($difftime / $d);

	return array($years, $month, $days);
}

/**
 * Generates domain expires information.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function client_generateDomainExpiresInformation($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$domainProperties = get_domain_default_props($_SESSION['user_id']);

	if ($domainProperties['domain_expires'] != 0) {
		$domainRemainingTime = '';
		$domainExpiresDate = date($cfg->DATE_FORMAT, $domainProperties['domain_expires']);

		if (time() < $domainProperties['domain_expires']) {
			list($years, $month, $days) = _client_getDomainRemainingTime($domainProperties['domain_expires']);

			if ($years == 0 && $month == 0 && $days <= 14) {
				$domainRemainingTime = '<span style="color:red">' . tr('%d %s remaining until account expiration', $days, ($days > 1) ? tr('days') : tr('day')) . '</span>';
				$domainExpiresDate = '<strong>(' . $domainExpiresDate . ')</strong>';
			}
		} else {
			$domainExpiresDate = '<strong>(' . $domainExpiresDate . ')</strong>';
			$domainRemainingTime = '<span style="color:red">' . tr('Domain account expired.') . '</span>';
			set_page_message(tr('Your account has expired. Please renew your subscription.'), 'warning');
		}

		$tpl->assign(
			array(
				 'DOMAIN_REMAINING_TIME' => $domainRemainingTime,
				 'DOMAIN_EXPIRES_DATE' => $domainExpiresDate));
	} else {
		$tpl->assign(
			array(
				 'DOMAIN_REMAINING_TIME' => '',
				 'DOMAIN_EXPIRES_DATE' => tr('Never')));
	}
}

/************************************************************************************
 * Main script
 */

// Include core libraries
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('user', $cfg->PREVENT_EXTERNAL_LOGIN_CLIENT);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		 'layout' => 'shared/layouts/ui.tpl',
		 'page' => 'client/index.tpl',
		 'page_message' => 'layout',
		 'alternative_domain_url' => 'page',
		 'backup_domain_feature' => 'page',
		 'traffic_warning' => 'page',
		 'disk_warning' => 'page'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Client / General / Overview'),
		 'ISP_LOGO' => layout_getUserLogo()
	)
);

generateNavigation($tpl);

client_generateSupportSystemNotices();
client_generateDomainExpiresInformation($tpl);
client_generateFeatureStatus($tpl);

$domainProperties = get_domain_default_props($_SESSION['user_id']);

list(
	$domainTrafficPercent, $domainTrafficUsage
) = client_makeTrafficUsage($domainProperties['domain_id']);

client_generateTrafficUsageBar(
	$tpl, $domainTrafficUsage * 1024 * 1024,
	$domainProperties['domain_traffic_limit'] * 1024 * 1024, 400);

client_generateDiskUsageBar(
	$tpl, $domainProperties['domain_disk_usage'],
	$domainProperties['domain_disk_limit'] * 1024 * 1024, 400);


if ($domainProperties['domain_status'] == 'ok') {
	$tpl->assign(
		'HREF_DOMAIN_ALTERNATIVE_URL', "http://{$cfg->SYSTEM_USER_PREFIX}" . ($cfg->SYSTEM_USER_MIN_UID + $_SESSION['user_id']) . ".{$cfg->BASE_SERVER_VHOST}");
} else {
	$tpl->assign('DOMAIN_ALTERNATIVE_URL', '');
}

list(
	$subdomainCount, $domainAliasCount, $mailAccountsCount, $ftpAccountsCount,
	$sqlDatabasesCount, $sqlUsersCount
) = get_domain_running_props_cnt($domainProperties['domain_id']);

$tpl->assign(
	array(
		 'TR_DOMAIN_ACCOUNT' => tr('Domain account'),
		 'TR_ACCOUNT_NAME' => tr('Account name'),
		 'TR_DOMAIN_NAME' => tr('Domain name'),
		 'DOMAIN_NAME' => tohtml(decode_idna($domainProperties['domain_name'])),
		 'TR_DOMAIN_ALTERNATIVE_URL' => tr('Alternative URL to reach your website'),
		 'TR_DOMAIN_EXPIRES_DATE' => tr('Domain expiration date'),
		 'TR_FEATURE' => tr('Feature'),
		 'TR_FEATURE_STATUS' => tr('Status'),
		 'TR_DOMAIN_FEATURE' => tr('Domain'),
		 'TR_DOMAIN_ALIASES_FEATURE' => tr('Domain aliases'),
		 'DOMAIN_ALIASES_FEATURE_STATUS' => gen_num_limit_msg($domainAliasCount, $domainProperties['domain_alias_limit']),
		 'SUBDOMAINS_FEATURE_STATUS' => gen_num_limit_msg($subdomainCount, $domainProperties['domain_subd_limit']),
		 'TR_SUBDOMAINS_FEATURE' => tr('Subdomains'),
		 'TR_FTP_ACCOUNTS_FEATURE' => tr('FTP accounts'),
		 'FTP_ACCOUNTS_FEATURE_STATUS' => gen_num_limit_msg($ftpAccountsCount, $domainProperties['domain_ftpacc_limit']),
		 'TR_MAIL_ACCOUNTS_FEATURE' => tr('Email accounts'),
		 'MAIL_ACCOUNTS_FEATURE_STATUS' => gen_num_limit_msg($mailAccountsCount, $domainProperties['domain_mailacc_limit']),
		 'TR_MAIL_QUOTA' => tr('Email quota'),
		 'EMAIL_QUOTA_STATUS' => gen_mail_quota_limit_mgs(),
		 'TR_SQL_DATABASES_FEATURE' => tr('SQL databases'),
		 'SQL_DATABASE_FEATURE_STATUS' => gen_num_limit_msg($sqlDatabasesCount, $domainProperties['domain_sqld_limit']),
		 'TR_SQL_USERS_FEATURE' => tr('SQL users'),
		 'SQL_USERS_FEATURE_STATUS' => gen_num_limit_msg($sqlUsersCount, $domainProperties['domain_sqlu_limit']),
		 'TR_PHP_SUPPORT_FEATURE' => tr('PHP'),
		 'TR_PHP_DIRECTIVES_EDITOR_SUPPORT_FEATURE' => tr('PHP Editor'),
		 'TR_CGI_SUPPORT_FEATURE' => tr('CGI'),
		 'TR_CUSTOM_DNS_RECORDS_FEATURE' => tr('Custom DNS records'),
         'TR_EXTERNAL_MAIL_SERVER_FEATURE' => tr('External mail servers'),
		 'TR_APP_INSTALLER_FEATURE' => tr('Software installer'),
		 'TR_BACKUP_FEATURE' => tr('Backup'),
		 'TR_WEBSTATS_FEATURE' => tr('Web statistics'),
		 'TR_TRAFFIC_USAGE' => tr('Traffic usage'),
		 'TR_DISK_USAGE' => tr('Disk usage'),
         'TR_DISK_USAGE_DETAIL' => tr('Disk usage detail'),
         'TR_DISK_FILE_USAGE' => tr('File usage'),
         'DISK_FILESIZE' => bytesHuman($domainProperties['domain_disk_file']),
         'TR_DISK_DATABASE_USAGE' => tr('Database usage'),
         'DISK_SQLSIZE' => bytesHuman($domainProperties['domain_disk_sql']),
         'TR_DISK_MAIL_USAGE' => tr('Mail usage'),
         'DISK_MAILSIZE' => bytesHuman($domainProperties['domain_disk_mail'])
    )
);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
