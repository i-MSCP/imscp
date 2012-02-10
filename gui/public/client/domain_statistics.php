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
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/domain_statistics.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('month_item', 'page');
$tpl->define_dynamic('year_item', 'page');
$tpl->define_dynamic('traff_list', 'page');
$tpl->define_dynamic('traff_item', 'traff_list');

/**
 * @param $tpl
 * @param $month
 * @param $year
 * @return void
 */
function gen_page_date($tpl, $month, $year) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	for ($i = 1; $i <= 12; $i++) {
		$tpl->assign(
			array(
				'MONTH_SELECTED' => ($i == $month) ? $cfg->HTML_SELECTED : '',
				'MONTH' => $i));

		$tpl->parse('MONTH_ITEM', '.month_item');
	}

	for ($i = $year - 1; $i <= $year + 1; $i++) {
		$tpl->assign(
			array(
				'YEAR_SELECTED' => ($i == $year) ? $cfg->HTML_SELECTED : '',
				'YEAR' => $i));

		$tpl->parse('YEAR_ITEM', '.year_item');
	}
}

/**
 * @param $tpl
 * @param $current_month
 * @param $current_year
 * @return array
 */
function gen_page_post_data($tpl, $current_month, $current_year) {

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'show_traff') {
		$current_month = $_POST['month'];
		$current_year = $_POST['year'];
	}

	gen_page_date($tpl, $current_month, $current_year);
	return array($current_month, $current_year);
}

/**
 * @param $from
 * @param $to
 * @param $domain_id
 * @return array
 */
function get_domain_trafic($from, $to, $domain_id) {

	$query = "
		SELECT
			IFNULL(SUM(`dtraff_web`), 0) AS web_dr,
			IFNULL(SUM(`dtraff_ftp`), 0) AS ftp_dr,
			IFNULL(SUM(`dtraff_mail`), 0) AS mail_dr,
			IFNULL(SUM(`dtraff_pop`), 0) AS pop_dr
		FROM
			`domain_traffic`
		WHERE
			`domain_id` = ?
		AND
			`dtraff_time` >= ?
		AND
			`dtraff_time` <= ?
	";

	$rs = exec_query($query, array($domain_id, $from, $to));

	if ($rs->recordCount() == 0) {
		return array(0, 0, 0, 0);
	} else {
		return array(
			$rs->fields['web_dr'],
			$rs->fields['ftp_dr'],
			$rs->fields['pop_dr'],
			$rs->fields['mail_dr']
		);
	}
}

/**
 * @todo Check the out commented code at the end of this function, can we remove it?
 */
function gen_dmn_traff_list($tpl, $month, $year, $user_id) {

	global $web_trf, $ftp_trf, $smtp_trf, $pop_trf,
	$sum_web, $sum_ftp, $sum_mail, $sum_pop;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domain_admin_id = $_SESSION['user_id'];
	$query = "
		SELECT
			`domain_id`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";

	$rs = exec_query($query, $domain_admin_id);
	$domain_id = $rs->fields('domain_id');
	$fdofmnth = mktime(0, 0, 0, $month, 1, $year);
	$ldofmnth = mktime(1, 0, 0, $month + 1, 0, $year);

	if ($month == date('m') && $year == date('Y')) {
		$curday = date('j');
	} else {
		$tmp = mktime(1, 0, 0, $month + 1, 0, $year);
		$curday = date('j', $tmp);
	}

	$curtimestamp = time();
	$firsttimestamp = mktime(0, 0, 0, $month, 1, $year);
	$all[0] = 0;
	$all[1] = 0;
	$all[2] = 0;
	$all[3] = 0;
	$all[4] = 0;
	$all[5] = 0;
	$all[6] = 0;
	$all[7] = 0;
	$counter = 0;

	for ($i = 1; $i <= $curday; $i++) {
		$ftm = mktime(0, 0, 0, $month, $i, $year);
		$ltm = mktime(23, 59, 59, $month, $i, $year);
		$query = "
			SELECT
				`dtraff_web`, `dtraff_ftp`, `dtraff_mail`, `dtraff_pop`, `dtraff_time`
			FROM
				`domain_traffic`
			WHERE
				`domain_id` = ?
			AND
				`dtraff_time` >= ?
			AND
				`dtraff_time` <= ?
		";

		$rs = exec_query($query, array($domain_id, $ftm, $ltm));

		$has_data = false;
		list($web_trf,
			$ftp_trf,
			$pop_trf,
			$smtp_trf) = get_domain_trafic($ftm, $ltm, $domain_id);

		$tpl->assign('ITEM_CLASS', ($counter % 2 == 0) ? 'content' : 'content2');

		$sum_web += $web_trf;
		$sum_ftp += $ftp_trf;
		$sum_mail += $smtp_trf;
		$sum_pop += $pop_trf;

		$date_formt = $cfg->DATE_FORMAT;

		$tpl->assign(
			array(
				'DATE' => date($date_formt, strtotime($year . "-" . $month . "-" . $i)),
				'WEB_TRAFFIC' => sizeit($web_trf),
				'FTP_TRAFFIC' => sizeit($ftp_trf),
				'SMTP_TRAFFIC' => sizeit($smtp_trf),
				'POP3_TRAFFIC' => sizeit($pop_trf),
				'ALL_TRAFFIC' => sizeit($web_trf + $ftp_trf + $smtp_trf + $pop_trf),
				'WEB_TRAFF' => sizeit($web_trf),
				'FTP_TRAFF' => sizeit($ftp_trf),
				'SMTP_TRAFF' => sizeit($smtp_trf),
				'POP_TRAFF' => sizeit($pop_trf),
				'SUM_TRAFF' => sizeit($web_trf + $ftp_trf + $smtp_trf + $pop_trf)));

		$tpl->assign(
			array(
				'MONTH' => $month,
				'YEAR' => $year,
				'DOMAIN_ID' => $domain_id,
				'WEB_ALL' => sizeit($sum_web),
				'FTP_ALL' => sizeit($sum_ftp),
				'SMTP_ALL' => sizeit($sum_mail),
				'POP_ALL' => sizeit($sum_pop),
				'SUM_ALL' => sizeit($sum_web + $sum_ftp + $sum_mail + $sum_pop)));

		$tpl->parse('TRAFF_ITEM', '.traff_item');
		$counter++;
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Client/Domain Statistics'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

$current_month = date("m", time());
$current_year = date("Y", time());

list($current_month, $current_year) = gen_page_post_data($tpl, $current_month, $current_year);

gen_dmn_traff_list($tpl, $current_month, $current_year, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_DOMAIN_STATISTICS' => tr('Domain statistics'),
		'DOMAIN_URL' => 'http://' . $_SESSION['user_logged'] . '/stats/',
		'TR_AWSTATS' => tr('Web Stats'),
		'TR_MONTH' => tr('Month'),
		'TR_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show'),
		'TR_DATE' => tr('Date'),
		'TR_WEB_TRAFF' => tr('WEB'),
		'TR_FTP_TRAFF' => tr('FTP'),
		'TR_SMTP_TRAFF' => tr('SMTP'),
		'TR_POP_TRAFF' => tr('POP3/IMAP'),
		'TR_SUM' => tr('Sum'),
		'TR_ALL' => tr('Total')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
