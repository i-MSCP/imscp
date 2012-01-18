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
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link		http://i-mscp.net
 */

/*******************************************************************************
 * Script functions
 */

/**
 * Generates page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param $resellerId Reseller unique identifier
 */
function admin_generatePage($tpl, $resellerId)
{
	$query = "SELECT `domain_id` FROM `domain` WHERE `domain_created_id` = ?";
	$stmt = exec_query($query, $resellerId);

	if($stmt->rowCount()) {
		foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $domainId) {
			_admin_generateDomainStatisticsEntry($tpl, $domainId);
			$tpl->parse('DOMAIN_STATISTICS_ENTRY_BLOCK', 'domain_statistics_entry_block');
		}
	} else {
		$tpl->assign('DOMAIN_STATISTICS_ENTRIES_BLOCK', '');
		set_page_message('No domain statistics to display for this reseller.');
	}
}

/**
 * Genrate statistics entry for the given domain.
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 */
function _admin_generateDomainStatisticsEntry($tpl, $domainId)
{
	list(
		$domain_name, $domainId, $web, $ftp, $smtp, $pop3, $utraff_current, $udisk_current
	) = generate_user_traffic($domainId);

	list(
		$usub_current, $usub_max, $uals_current, $uals_max, $umail_current, $umail_max, $uftp_current, $uftp_max,
		$usql_db_current, $usql_db_max, $usql_user_current, $usql_user_max, $utraff_max, $udisk_max
	) = generate_user_props($domainId);

	$utraff_max = $utraff_max * 1024 * 1024;
	$udisk_max = $udisk_max * 1024 * 1024;

	list($traff_percent) = make_usage_vals($utraff_current, $utraff_max);
	list($disk_percent) = make_usage_vals($udisk_current, $udisk_max);

	if ($traff_percent > 100) {
		$traff_percent = 100;
	}

	if ($disk_percent > 100) {
		$disk_percent = 100;
	}

	$domain_name = decode_idna($domain_name);

	$tpl->assign(
		array(
			'DOMAIN_NAME' => tohtml($domain_name),
			'MONTH' => date('m'),
			'YEAR' => date('y'),
			'DOMAIN_ID' => $domainId,
			'TRAFF_PERCENT' => $traff_percent,
			'TRAFF_MSG' => ($utraff_max)
				? tr('%1$s of %2$s</b>', numberBytesHuman($utraff_current), numberBytesHuman($utraff_max))
				: tr('%s of unlimited</b>', numberBytesHuman($utraff_current)),
			'DISK_PERCENT' => $disk_percent,
			'DISK_MSG' => ($udisk_max)
				? tr('%1$s of %2$s</b>', numberBytesHuman($udisk_current), numberBytesHuman($udisk_max))
				: tr('%s of unlimited</b>', numberBytesHuman($udisk_current)),
			'WEB' => numberBytesHuman($web),
			'FTP' => numberBytesHuman($ftp),
			'SMTP' => numberBytesHuman($smtp),
			'POP3' => numberBytesHuman($pop3),
			'SUB_MSG' => ($usub_max)
				? (($usub_max > 0)
					? tr('%1$d of %2$d</b>', numberBytesHuman($usub_current), $usub_max)
					: tr('disabled</b>'))
				: tr('%d of unlimited</b>', numberBytesHuman($usub_current)),
			'ALS_MSG' => ($uals_max)
				? (($uals_max > 0)
					? tr('%1$d of %2$d</b>', numberBytesHuman($uals_current), $uals_max)
					: tr('disabled</b>'))
				: tr('%d of unlimited</b>', numberBytesHuman($uals_current)),
			'MAIL_MSG' => ($umail_max)
				? (($umail_max > 0)
					? tr('%1$d of %2$d</b>', $umail_current, $umail_max)
					: tr('disabled</b>'))
				: tr('%d of unlimited</b>', $umail_current),
			'FTP_MSG' => ($uftp_max)
				? (($uftp_max > 0)
					? tr('%1$d of %2$d</b>', $uftp_current, $uftp_max)
					: tr('disabled</b>'))
				: tr('%d of unlimited', $uftp_current),
			'SQL_DB_MSG' => ($usql_db_max)
				? (($usql_db_max > 0)
					? tr('%1$d of %2$d', $usql_db_current, $usql_db_max)
					: tr('disabled</b>'))
				: tr('%d of unlimited', $usql_db_current),
			'SQL_USER_MSG' => ($usql_user_max)
				? (($usql_user_max > 0)
					? tr('%1$d of %2$d', $usql_user_current, $usql_user_max)
					: tr('disabled'))
				: tr('%d of unlimited', $usql_user_current)));
}

/*******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

if (!isset($_GET['rid'])) {
	set_page_message(tr('Wrong request.'), 'error');
	redirectTo('reseller_statistics.php');
	exit; // Useless but avoid IDE warning about possible undefined variable
} else {
	$resellerId = intval($_GET['rid']);
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/reseller_user_statistics.tpl',
		'page_message' => 'layout',
		'domain_statistics_entries_block' => 'page',
		'domain_statistics_entry_block' => 'domain_statistics_entries_block',));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Reseller customer Statistics'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_TRAFF' => tr('Traffic usage'),
		'TR_DISK' => tr('Disk usage'),
		'TR_WEB' => tr('Http traffic'),
		'TR_FTP_TRAFF' => tr('FTP traffic'),
		'TR_SMTP' => tr('SMTP traffic'),
		'TR_POP3' => tr('POP3/IMAP traffic'),
		'TR_SUBDOMAIN' => tr('Subdomains'),
		'TR_ALIAS' => tr('Aliases'),
		'TR_MAIL' => tr('Mail accounts'),
		'TR_FTP' => tr('FTP accounts'),
		'TR_SQL_DB' => tr('SQL databases'),
		'TR_SQL_USER' => tr('SQL users'),
		'VALUE_RID' => $resellerId,
		'TR_DOMAIN_TOOLTIP' => tr('Show detailed statistics for this domain'),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()));

generateNavigation($tpl);
admin_generatePage($tpl, $resellerId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
