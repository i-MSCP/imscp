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
 * @subpackage	Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Functions
 */

/**
 * Generates support questions notice for administrator.
 *
 * Notice administrator about any new support questions and answers.
 *
 * @return void
 */
function admin_generateSupportQuestionsMessage()
{
    $query = "
        SELECT
            COUNT(`ticket_id`) `nbQuestions`
        FROM
            `tickets`
        WHERE
            `ticket_to` = ?
        AND
            `ticket_status` IN (1, 2)
        AND
            `ticket_reply` = 0
    ";
    $stmt = exec_query($query, $_SESSION['user_id']);

    $nbQuestions = $stmt->fields('nbQuestions');

    if ($nbQuestions != 0) {
        set_page_message(
            tr('You have received %d new support questions.', '<span class="bold">' . $nbQuestions . '</span>'), 'info');
    }
}

/**
 * Generates update messages.
 *
 * Generates update messages for both database updates and i-MSCP updates.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function admin_generateUpdateMessages($tpl)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (iMSCP_Update_Database::getInstance()->isAvailableUpdate()) {
		$tpl->assign(
			array(
				'TR_DATABASE_UPDATE' => tr('Database update is available.'),
				'TR_DATABASE_UPDATE_LINK' => 'Click here to process update.'));
    } else {
        $tpl->assign('IMSCP_DATABASE_UPDATE_MESSAGE', '');
    }

    if ($cfg->CHECK_FOR_UPDATES) {
        if (iMSCP_Update_Version::getInstance()->isAvailableUpdate()) {
            $tpl->assign('UPDATE', '<a href="imscp_updates.php" class="link">' .
                                   tr('New i-MSCP update is available') . '</a>');
        } else {
            if (iMSCP_Update_Version::getInstance()->getError() != '') {
                $tpl->assign('UPDATE', iMSCP_Update_Version::getInstance()->getError());
            } else {
                $tpl->assign('IMSCP_UPDATE_MESSAGE', '');
            }
        }
    } else {
        $tpl->assign('UPDATE', tr('Update checking is disabled.'));
    }
}

/**
 * Generates admin general informations.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function admin_getAdminGeneralInfo($tpl)
{
    /** @var $cfg  iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    // If COUNT_DEFAULT_EMAIL_ADDRESSES == false, admin total emails show
    // [total - default_emails]/[total_emails]
    $totalMails = records_count('mail_users', 'mail_type NOT RLIKE \'_catchall\'', '');

    if ($cfg->COUNT_DEFAULT_EMAIL_ADDRESSES) {
        $showTotalMails = $totalMails;
    } else {
        $totalDefaultMails = records_count('mail_users', 'mail_acc', 'abuse');
        $totalDefaultMails += records_count('mail_users', 'mail_acc', 'webmaster');
        $totalDefaultMails += records_count('mail_users', 'mail_acc', 'postmaster');
        $showTotalMails = ($totalMails - $totalDefaultMails) . '/' . $totalMails;
    }

    $tpl->assign(
		array(
			'ACCOUNT_NAME' => tohtml($_SESSION['user_logged']),
			'ADMIN_USERS' => records_count('admin', 'admin_type', 'admin'),
			'RESELLER_USERS' => records_count('admin', 'admin_type', 'reseller'),
			'NORMAL_USERS' => records_count('admin', 'admin_type', 'user'),
			'DOMAINS' => records_count('domain', '', ''),
			'SUBDOMAINS' => records_count('subdomain', '', '') + records_count('subdomain_alias', 'subdomain_alias_id', '', ''),
			'DOMAINS_ALIASES' => records_count('domain_aliasses', '', ''),
			'MAIL_ACCOUNTS' => $showTotalMails,
			'FTP_ACCOUNTS' => records_count('ftp_users', '', ''),
			'SQL_DATABASES' => records_count('sql_database', '', ''),
			'SQL_USERS' => get_sql_user_count()));
}

/**
 * Generates server traffic bar.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function admin_generateServerTrafficInfo($tpl)
{
	// Getting max server traffic per month in mebibytes

	$query = "SELECT IFNULL(`straff_max`, 0) `maxTraffic`, IFNULL(`straff_warn`, 0) `warningTraffic` FROM `straff_settings`";
 	$stmt = exec_query($query);

	if($stmt->rowCount()) {
		// Get bytes value for max server traffic
		$trafficLimitBytes = $stmt->fields['maxTraffic'] * 1048576;
		$trafficWarningBytes = $stmt->fields['warningTraffic'] * 1048576;

		if(!$trafficWarningBytes) {
			$trafficWarningBytes = $trafficLimitBytes;
		}
	} else {
		$trafficLimitBytes = $trafficWarningBytes = 0;
	}

	// Getting server traffic usage value in bytes for the current month
    $query = "
        SELECT
            IFNULL((SUM(`bytes_in`) + SUM(`bytes_out`)), 0) `serverTrafficUsage`
        FROM
            `server_traffic`
        WHERE
            `traff_time` > ?
        AND
            `traff_time` < ?
    ";
    $stmt = exec_query($query, array(getFirstDayOfMonth(), getLastDayOfMonth()));

	if($stmt->rowCount()) {
		$trafficUsageBytes = $stmt->fields['serverTrafficUsage'];
	} else {
		$trafficUsageBytes = 0;
	}

	// Get traffic usage in percent
	$trafficUsagePercent = make_usage_vals($trafficUsageBytes, $trafficLimitBytes);

	if ($trafficLimitBytes) {
		$trafficMessage = tr('%1$s%% [%2$s of %3$s]', $trafficUsagePercent, bytesHuman($trafficUsageBytes), bytesHuman($trafficLimitBytes));
 	} else {
		$trafficMessage = tr('%1$s%% [%2$s of unlimited]', $trafficUsagePercent, bytesHuman($trafficUsageBytes));
 	}

	// Warning message about traffic
	if($trafficUsageBytes) {
		if(($trafficWarningBytes && $trafficUsageBytes > $trafficWarningBytes) ||
			// In any case, display a warning if traffic limit is reached
			($trafficLimitBytes &&  $trafficUsageBytes > $trafficLimitBytes)
		) {
			set_page_message( tr('You are exceeding the server traffic limit.'), 'warning');
		}
	}

	$tpl->assign(
		array(
			'TRAFFIC_WARNING' => $trafficMessage,
			'TRAFFIC_PERCENT' => $trafficUsagePercent
		)
	);
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login(__FILE__, $cfg->PREVENT_EXTERNAL_LOGIN_ADMIN);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/index.tpl',
		'page_message' => 'layout',
		'imscp_update_message' => 'page',
		'imscp_database_update_message' => 'page',
		'traffic_warning_message' => 'page'));

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / General information'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_GENERAL_INFORMATION' => tr('General information'),
		'TR_PROPERTIES' => tr('Properties'),
		'TR_VALUES' => tr('Values'),
		'TR_ACCOUNT_NAME' => tr('Account name'),
		'TR_ADMIN_USERS' => tr('Admin users'),
		'TR_RESELLER_USERS' => tr('Reseller users'),
		'TR_NORMAL_USERS' => tr('Normal users'),
		'TR_DOMAINS' => tr('Domains'),
		'TR_SUBDOMAINS' => tr('Subdomains'),
		'TR_DOMAINS_ALIASES' => tr('Domain aliases'),
		'TR_MAIL_ACCOUNTS' => tr('Mail accounts'),
		'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
		'TR_SQL_DATABASES' => tr('SQL databases'),
		'TR_SQL_USERS' => tr('SQL users'),
		'TR_SERVER_TRAFFIC' => tr('Server traffic')));

generateNavigation($tpl);
admin_generateSupportQuestionsMessage();
admin_generateUpdateMessages($tpl);
admin_getAdminGeneralInfo($tpl);
admin_generateServerTrafficInfo($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
