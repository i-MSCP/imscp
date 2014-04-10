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
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Returns traffic information for the given domain and period
 *
 * @access private
 * @param int $domainId domain unique identifier
 * @param int $beginTime An UNIX timestamp representing a begin time period
 * @param int $endTime An UNIX timestamp representing an end time period
 * @return array
 */
function _reseller_getDomainTraffic($domainId, $beginTime, $endTime)
{
	$query = "
		SELECT
			IFNULL(SUM(dtraff_web), 0) AS web_dr, IFNULL(SUM(dtraff_ftp), 0) AS ftp_dr,
			IFNULL(SUM(dtraff_mail), 0) AS mail_dr, IFNULL(SUM(dtraff_pop), 0) AS pop_dr
		FROM
			domain_traffic
		WHERE
			domain_id = ?
		AND
			dtraff_time BETWEEN ? AND ?
	";
	$stmt = exec_query($query, array($domainId, $beginTime, $endTime));

	if (!$stmt->rowCount()) {
		return array(0, 0, 0, 0);
	} else {
		return array(
			$stmt->fields['web_dr'], $stmt->fields['ftp_dr'], $stmt->fields['mail_dr'], $stmt->fields['pop_dr']
		);
	}
}

/**
 * Generate domain statistics for the given period
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @param int $month Month of the period for which statistics are requested
 * @param int $year Year of the period for which statistics are requested
 * @return void
 */
function reseller_generatePage($tpl, $domainId, $month, $year)
{
	// Let see if the domain exists
	$stmt = exec_query('SELECT domain_id, domain_name FROM domain WHERE domain_id = ?', $domainId);

	if (!$stmt->rowCount()) {
		set_page_message(tr('Domain not found.'), 'error');
		redirectTo('user_statistics.php');
	} else {
		$tpl->assign('DOMAIN_NAME', decode_idna($stmt->fields['domain_name']));
	}

	// Let see if we have any statistics available for the given periode
	$query = "SELECT domain_id FROM domain_traffic WHERE dtraff_time BETWEEN ? AND ? LIMIT 1";
	$stmt = exec_query($query, array(getFirstDayOfMonth($month, $year), getLastDayOfMonth($month, $year)));

	$tpl->assign('DOMAIN_ID', $domainId);

	if ($stmt->rowCount()) {
		$requestedPeriod = getLastDayOfMonth($month, $year);
		$toDay = ($requestedPeriod < time()) ? date('j', $requestedPeriod) : date('j');
		$all = array_fill(0, 8, 0);

		$dateFormat = iMSCP_Registry::get('config')->DATE_FORMAT;

		for ($fromDay = 1; $fromDay <= $toDay; $fromDay++) {
			$beginTime = mktime(0, 0, 0, $month, $fromDay, $year);
			$endTime = mktime(23, 59, 59, $month, $fromDay, $year);

			list(
				$webTraffic, $ftpTraffic, $smtpTraffic, $popTraffic
			) = _reseller_getDomainTraffic($domainId, $beginTime, $endTime);

			$tpl->assign(
				array(
					'DATE' => date($dateFormat, strtotime($year . '-' . $month . '-' . $fromDay)),
					'WEB_TRAFFIC' => bytesHuman($webTraffic),
					'FTP_TRAFFIC' => bytesHuman($ftpTraffic),
					'SMTP_TRAFFIC' => bytesHuman($smtpTraffic),
					'POP3_TRAFFIC' => bytesHuman($popTraffic),
					'ALL_TRAFFIC' => bytesHuman($webTraffic + $ftpTraffic + $smtpTraffic + $popTraffic),
				)
			);

			$all[0] = $all[0] + $webTraffic;
			$all[1] = $all[1] + $ftpTraffic;
			$all[2] = $all[2] + $smtpTraffic;
			$all[3] = $all[3] + $popTraffic;

			$tpl->parse('TRAFFIC_TABLE_ITEM', '.traffic_table_item');
		}

		$tpl->assign(
			array(
				'MONTH' => $month,
				'YEAR' => $year,
				'DOMAIN_ID' => $domainId,
				'ALL_WEB_TRAFFIC' => bytesHuman($all[0]),
				'ALL_FTP_TRAFFIC' => bytesHuman($all[1]),
				'ALL_SMTP_TRAFFIC' => bytesHuman($all[2]),
				'ALL_POP3_TRAFFIC' => bytesHuman($all[3]),
				'ALL_ALL_TRAFFIC' => bytesHuman($all[0] + $all[1] + $all[2] + $all[3]),
			)
		);
	} else {
		set_page_message(tr('No statistics found for the given period. Try another period.'), 'info');
		$tpl->assign('DOMAIN_STATISTICS_BLOCK', '');
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/domain_statistics.tpl',
		'page_message' => 'layout',
		'month_list' => 'page',
		'year_list' => 'page',
		'domain_statistics_block' => 'page',
		'traffic_table_item' => 'domain_statistics_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Statistics / Overview / Domain Statistics - {DOMAIN_NAME}'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_WEB_TRAFFIC' => tr('Web traffic'),
		'TR_FTP_TRAFFIC' => tr('FTP traffic'),
		'TR_SMTP_TRAFFIC' => tr('SMTP traffic'),
		'TR_POP3_TRAFFIC' => tr('POP3/IMAP traffic'),
		'TR_ALL_TRAFFIC' => tr('All traffic'),
		'TR_ALL' => tr('All'),
		'TR_DAY' => tr('Day')
	)
);

if (isset($_POST['domain_id'])) {
	$domainId = $_POST['domain_id'];
} else if (isset($_GET['domain_id'])) {
	$domainId = $_GET['domain_id'];
}

if (isset($_POST['month']) && isset($_POST['year'])) {
	$year = intval($_POST['year']);
	$month = intval($_POST['month']);
} else {
	$month = date('m');
	$year = date('Y');
}

if (!isset($domainId)) {
	showBadRequestErrorPage();
}

// Retrieve smaller timestamp to define max number of years to show in select element
$stmt = exec_query(
	'SELECT dtraff_time FROM domain_traffic WHERE domain_id = ? ORDER BY dtraff_time ASC LIMIT 1', $domainId
);

if ($stmt->rowCount()) {
	$numberYears = date('y') - date('y', $stmt->fields['dtraff_time']);
	$numberYears =  $numberYears ? $numberYears + 1: 1;
} else {
	$numberYears = 1;
}

generateNavigation($tpl);
generateSelectListForMonthsAndYears($tpl, $month, $year, $numberYears);
reseller_generatePage($tpl, $domainId, $month, $year);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
