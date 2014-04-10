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
 * @subpackage	Reseller
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
 * Generates support questions notice for reseller.
 *
 * Notice reseller about any new support questions and answers.
 *
 * @return void
 */
function reseller_generateSupportQuestionsMessage()
{
	$query = "
        SELECT
            count(`ticket_id`) `nbQuestions`
        FROM
            `tickets`
        WHERE
            `ticket_to` = ?
        AND
            `ticket_status` IN (1, 4)
        AND
            `ticket_reply` = 0
    ";
	$stmt = exec_query($query, $_SESSION['user_id']);

	$nbQuestions = $stmt->fields['nbQuestions'];

	if ($nbQuestions != 0) {
		set_page_message(tr('You have received %s new support ticket(s).', '<strong>' . $nbQuestions . '</strong>'), 'info');
	}
}

/**
 * Generates message for new domain aliases orders.
 *
 * @return void
 */
function reseller_generateOrdersAliasesMessage()
{
	$query = "
		SELECT
			COUNT(alias_id) AS nbOrdersAliases
		FROM
			domain_aliasses
		INNER JOIN
			domain USING(domain_id)
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			alias_status = ?
		AND
			created_by = ?
	";
	$stmt = exec_query($query, array('ordered', $_SESSION['user_id']));

	$nbOrdersAliases = $stmt->fields['nbOrdersAliases'];

	if ($nbOrdersAliases) {
		set_page_message(
			tr(
				'You have %d new domain alias %s.', $nbOrdersAliases,
				($nbOrdersAliases > 1) ? tr('orders') : tr('order')
			),
			'info'
		);
	}
}

/**
 * Generates traffic usage bar.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $trafficUsageBytes Current traffic usage
 * @param int $trafficLimitBytes Traffic max usage
 * @return void
 */
function reseller_generateTrafficUsageBar($tpl, $trafficUsageBytes, $trafficLimitBytes)
{
	$trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);

	// Is limited traffic usage for reseller ?
	if ($trafficLimitBytes) {
		$trafficUsageData = tr('%1$s%% [%2$s of %3$s]', $trafficUsagePercent, bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes));
	} else {
		$trafficUsageData = tr('%1$s%% [%2$s of unlimited]', $trafficUsagePercent, bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes));
	}

	$tpl->assign(
		array(
			 'TRAFFIC_USAGE_DATA' => $trafficUsageData,
			 'TRAFFIC_PERCENT' => $trafficUsagePercent
		)
	);
}

/**
 * Generates disk usage bar.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $diskspaceUsageBytes Disk usage
 * @param int $diskspaceLimitBytes Max disk usage
 * @return void
 */
function reseller_generateDiskUsageBar($tpl, $diskspaceUsageBytes, $diskspaceLimitBytes)
{
	$diskspaceUsagePercent = make_usage_vals($diskspaceUsageBytes, $diskspaceLimitBytes);

	// is Limited disk usage for reseller ?
	if ($diskspaceLimitBytes) {
		$diskUsageData = tr('%1$s%% [%2$s of %3$s]', $diskspaceUsagePercent, bytesHuman($diskspaceUsageBytes), bytesHuman($diskspaceLimitBytes));
	} else {
		$diskUsageData = tr('%1$s%% [%2$s of unlimited]', $diskspaceUsagePercent, bytesHuman($diskspaceUsageBytes));
	}

	$tpl->assign(
		array(
			 'DISK_USAGE_DATA' => $diskUsageData,
			 'DISK_PERCENT' => $diskspaceUsagePercent
		)
	);
}

/**
 * Generates page data.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $resellerId Reseller unique identifier
 * @param string $resellerName Reseller name
 * @return void
 */
function reseller_generatePageData($tpl, $resellerId, $resellerName)
{
	$resellerProperties = imscp_getResellerProperties($resellerId);

	list(
		$udmnCurrent, , , $usubCurrent, , , $ualsCurrent, , , $umailCurrent, , ,
		$uftpCurrent, , , $usqlDbCurrent, , , $usqlUserCurrent, , , $utraffCurrent, , ,
		$udiskCurrent
		) = generate_reseller_user_props($resellerId);

	// Convert into Mib values
	$rtraffMax = $resellerProperties['max_traff_amnt'] * 1024 * 1024;
	$rdiskMax = $resellerProperties['max_disk_amnt'] * 1024 * 1024;

	reseller_generateTrafficUsageBar($tpl, $utraffCurrent, $rtraffMax);
	reseller_generateDiskUsageBar($tpl, $udiskCurrent, $rdiskMax);

	if ($rtraffMax > 0 && $utraffCurrent > $rtraffMax) {
		$tpl->assign('TR_TRAFFIC_WARNING', tr('You are exceeding your monthly traffic limit.'));
	} else {
		$tpl->assign('TRAFFIC_WARNING_MESSAGE', '');
	}

	if ($rdiskMax > 0 && $udiskCurrent > $rdiskMax) {
		$tpl->assign('TR_DISK_WARNING', tr('You are exceeding your disk space limit.'));
	} else {
		$tpl->assign('DISK_WARNING_MESSAGE', '');
	}

	$tpl->assign(
		array(
			 'TR_ACCOUNT_OVERVIEW' => tr('Account overview'),
			 'TR_ACCOUNT_LIMITS' => tr('Account limits'),
			 'TR_FEATURES' => tr('Features'),
			 'ACCOUNT_NAME' => tr('Account name'),
			 'GENERAL_INFO' => tr('General information'),
			 'DOMAINS' => tr('Domain accounts'),
			 'SUBDOMAINS' => tr('Subdomains'),
			 'ALIASES' => tr('Aliases'),
			 'MAIL_ACCOUNTS' => tr('Email accounts'),
			 'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
			 'SQL_DATABASES' => tr('SQL databases'),
			 'SQL_USERS' => tr('SQL users'),
			 'TRAFFIC' => tr("Traffic"),
			 'DISK' => tr('Disk'),
			 'RESELLER_NAME' => tohtml($resellerName),
			 'DMN_MSG' => ($resellerProperties['max_dmn_cnt'])
				 ? tr('%1$d / %2$d of %3$d', $udmnCurrent, $resellerProperties['current_dmn_cnt'], $resellerProperties['max_dmn_cnt'])
				 : tr('%1$d / %2$d of unlimited', $udmnCurrent, $resellerProperties['current_dmn_cnt']),
			 'SUB_MSG' => ($resellerProperties['max_sub_cnt'] > 0)
				 ? tr('%1$d / %2$d of %3$d</b>', $usubCurrent, $resellerProperties['current_sub_cnt'], $resellerProperties['max_sub_cnt'])
				 : (($resellerProperties['max_sub_cnt'] == '-1') ? tr('disabled')
					 : tr('%1$d / %2$d of unlimited', $usubCurrent, $resellerProperties['current_sub_cnt'])),
			 'ALS_MSG' => ($resellerProperties['max_als_cnt'] > 0)
				 ? tr('%1$d / %2$d of %3$d', $ualsCurrent, $resellerProperties['current_als_cnt'], $resellerProperties['max_als_cnt'])
				 : (($resellerProperties['max_als_cnt'] == '-1') ? tr('disabled')
					 : tr('%1$d / %2$d of unlimited', $ualsCurrent, $resellerProperties['current_als_cnt'])),
			 'MAIL_MSG' => ($resellerProperties['max_mail_cnt'] > 0)
				 ? tr('%1$d / %2$d of %3$d', $umailCurrent, $resellerProperties['current_mail_cnt'], $resellerProperties['max_mail_cnt'])
				 : (($resellerProperties['max_mail_cnt'] == '-1') ? tr('disabled')
					 : tr('%1$d / %2$d of unlimited', $umailCurrent, $resellerProperties['current_mail_cnt'])),
			 'FTP_MSG' => ($resellerProperties['max_ftp_cnt'] > 0)
				 ? tr('%1$d / %2$d of %3$d', $uftpCurrent, $resellerProperties['current_ftp_cnt'], $resellerProperties['max_ftp_cnt'])
				 : (($resellerProperties['max_ftp_cnt'] == '-1') ? tr('disabled')
					 : tr('%1$d / %2$d of unlimited', $uftpCurrent, $resellerProperties['current_ftp_cnt'])),
			 'SQL_DB_MSG' => ($resellerProperties['max_sql_db_cnt'] > 0)
				 ? tr('%1$d / %2$d of %3$d', $usqlDbCurrent, $resellerProperties['current_sql_db_cnt'], $resellerProperties['max_sql_db_cnt'])
				 : (($resellerProperties['max_sql_db_cnt'] == '-1') ? tr('disabled')
					 : tr('%1$d / %2$d of unlimited', $usqlDbCurrent, $resellerProperties['current_sql_db_cnt'])),
			 'SQL_USER_MSG' => ($resellerProperties['max_sql_db_cnt'] > 0)
				 ? tr('%1$d / %2$d of %3$d', $usqlUserCurrent, $resellerProperties['current_sql_user_cnt'], $resellerProperties['max_sql_user_cnt'])
				 : (($resellerProperties['max_sql_user_cnt'] == '-1') ? tr('disabled')
					 : tr('%1$d / %2$d of unlimited', $usqlUserCurrent, $resellerProperties['current_sql_user_cnt'])),
			 'TR_SUPPORT' => tr('Support system'),
			 'SUPPORT_STATUS' => ($resellerProperties['support_system'] == 'yes')
				 ? '<span style="color:green;">' . tr('Enabled') . '</span>'
				 : '<span style="color:red;">' . tr('Disabled') . '</span>',
			 'TR_PHP_EDITOR' => tr('PHP Editor'),
			 'PHP_EDITOR_STATUS' => ($resellerProperties['php_ini_system'] == 'yes')
				 ? '<span style="color:green;">' . tr('Enabled') . '</span>'
				 : '<span style="color:red;">' . tr('Disabled') . '</span>',
			 'TR_APS' => tr('Software installer'),
			 'APS_STATUS' => ($resellerProperties['software_allowed'] == 'yes')
				 ? '<span style="color:green;">' . tr('Enabled') . '</span>'
				 : '<span style="color:red;">' . tr('Disabled') . '</span>'));
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('reseller', $cfg->PREVENT_EXTERNAL_LOGIN_RESELLER);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/index.tpl',
		'page_message' => 'layout',
		'traffic_warning_message' => 'page',
		'disk_warning_message' => 'page'));

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('Reseller / General / Overview'),
		 'ISP_LOGO' => layout_getUserLogo(),
		 'TR_SAVE' => tr('Save'),
		 'TR_TRAFFIC_USAGE' => tr('Traffic usage'),
		 'TR_DISK_USAGE' => tr('Disk usage')));

generateNavigation($tpl);
reseller_generateSupportQuestionsMessage();
reseller_generateOrdersAliasesMessage();
reseller_generatePageData($tpl, $_SESSION['user_id'], $_SESSION['user_logged']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
