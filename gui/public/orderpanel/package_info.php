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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package        iMSCP_Core
 * @subpackage    Orderpanel
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Script functions
 */

/**
 * Translates a string
 *
 * @param string $string String to translate
 * @return string Translated string
 */
function translate_sse($string)
{
	if ($string == '_yes_') {
		return tr('Yes');
	} else if ($string == '_no_') {
		return tr('No');
	} else if ($string == '_sql_') {
		return tr('SQL');
	} else if ($string == '_full_') {
		return tr('Full');
	} else if ($string == '_dmn_') {
		return tr('Domain');
	} else {
		return $string;
	}
}

/**
 * Translate payment period
 *
 * @param string $paymentPeriod
 * @return string
 */
function translatePaymentPeriod($paymentPeriod)
{
	switch ($paymentPeriod) {
		case 'monthly':
			return tr('Monthly');
			break;
		case 'annually':
			return tr('Annually');
			break;
		case 'biennially':
			return tr('Biennially');
			break;
		case 'triennially';
			return tr('Triennially');
			break;
		default:
			return tr('Unknown');
	}
}

/**
 * Generates hosting plan details.
 *
 * @param iMSCP_pTemplate $tpl Tempalte engine.
 * @param int $userId User unique identifier
 * @param int $planId Plan unique identifier
 * @return void
 */
function gen_plan_details($tpl, $userId, $planId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";
		$stmt = exec_query($query, $planId);
	} else {
		$query = "SELECT * FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";
		$stmt = exec_query($query, array($userId, $planId));
	}

	if (!$stmt->recordCount()) {
		redirectTo('index.php?user_id=' . $userId);
	} else {
		$props = $stmt->fields['props'];

		list(
			$hpPhp, $hpCgi, $hpSub, $hpAls, $hpMail, $hpFtp, $hpSqlDb, $hpSqlUsers, $hpTraffic, $hpDiskspace,
			$hpBackup, $hpDns, $hpSoftwaresInstaller, $hpPhpEditor, , , , , , , , , $hpExtMailServer
		) = explode(';', $props);

		$price = $stmt->fields['price'];
		$setup_fee = $stmt->fields['setup_fee'];
		$currency = $stmt->fields['value'];
		$vat = $stmt->fields['vat'];
		$subtotal = 0;
		$totalRecurring = 0;

		if ($price == 0 || $price == '') {
			$price = tr('Free of charge');
			$paymenPeriod = 'Free of charge';
		} else {
			$subtotal += $price;
			$totalRecurring = sprintf('%.02f', round($subtotal * (1 + $vat / 100), 2));
			$price .= ' ' . $currency . ' (<small>' . tr('Excl. tax') . '</small>)';
			$paymenPeriod = tohtml(translatePaymentPeriod($stmt->fields['payment']));
		}

		if ($setup_fee == 0 || $setup_fee == '') {
			$setup_fee = tr('Free of charge');
		} else {
			$subtotal += $setup_fee;
			$setup_fee .= $currency . ' (<small>' . tr('Excl. tax') . '</small>)';
		}

		$totalVat = sprintf('%.02f', round(round($subtotal * (1 + $vat / 100), 2) - $subtotal, 2));
		$totalDueToday = sprintf('%.02f', round($subtotal + $totalVat, 2));

		$description = $stmt->fields['description'];
		$hpDiskspace = translate_limit_value($hpDiskspace, true) . "<br />";
		$hpTraffic = translate_limit_value($hpTraffic, true);
		$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';

		$programming = array();

		foreach (array('CGI' => $hpCgi, 'PHP' => $hpPhp) as $k => $v) {
			if ($v == '_yes_') {
				$programming[] = $k;
			}
		}

		$tpl->assign(
			array(
				'TR_HOSTING_PLAN' => tr('Hosting Plan'),
				'HOSTING_PLAN_NAME' => tohtml(ucwords($stmt->fields['name'])),
				'TR_DESCRIPTION' => tr('Description'),
				'DESCRIPTION' => tohtml($description),
				'PACK_ID' => $stmt->fields['id'],
				'USER_ID' => $userId,
				'PURCHASE' => tr('Purchase'),
				'ALIAS' => translate_limit_value($hpAls),
				'SUBDOMAIN' => translate_limit_value($hpSub),
				'HDD' => $hpDiskspace,
				'TRAFFIC' => $hpTraffic,
				'PROGRAMMING' => ($programming) ? : tr('No'),
				'PHP_EDITOR' => ($hpPhpEditor == 'yes') ? tr('Yes') : tr('No'),
				'SOFTWARE' => translate_sse($hpSoftwaresInstaller),
				'DNS' => translate_sse($hpDns),
				'BACKUP' => translate_sse($hpBackup),
				'MAIL' => translate_limit_value($hpMail),
				'EXT_MAIL_SERVER' => translate_sse($hpExtMailServer),
				'FTP' => translate_limit_value($hpFtp),
				'SQL_DATABASES' => translate_limit_value($hpSqlDb),
				'SQL_USERS' => translate_limit_value($hpSqlUsers),
				'PRICE' => $price,
				'SETUP_FEE' => $setup_fee,
				'SUBTOTAL' => sprintf('%.02f', $subtotal) . ' ' . $currency,
				'VAT' => $vat,
				'TOTAL_VAT' => $totalVat . ' ' . $currency,
				'TOTAL_DUE_TODAY' => $totalDueToday . ' ' . $currency,
				'TOTAL_RECURRING' => $totalRecurring . ' ' . $currency,
				'PAYMENT_PERIOD' => $paymenPeriod,
				'CUSTOM_ORDERPANEL_ID' => $coid
			)
		);
	}
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';
$bcoid = (empty($coid) || (isset($_GET['coid']) && $_GET['coid'] == $coid));

if (isset($_GET['id']) && $bcoid) {
	$planId = $_GET['id'];
	$_SESSION['order_panel_plan_id'] = $planId;

	if (isset($_SESSION['order_panel_user_id'])) {
		$userId = $_SESSION['order_panel_user_id'];
	} else if (isset($_GET['user_id'])) {
		$userId = $_GET['user_id'];
		$_SESSION['order_panel_user_id'] = $userId;
	} else {
		showBadRequestErrorPage();
	}
} else {
	showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($userId)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/package_info.tpl',
		'page_message' => 'page',
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => 'Order Panel / Hosting plan details',
		'THEME_CHARSET' => tr('encoding'),
		'TR_DOMAINS' => tr('Domains'),
		'TR_WEBSPACE' => tr('WebSpace'),
		'TR_HDD' => tr('Disk space limit'),
		'TR_TRAFFIC' => tr('Monthly Traffic limit'),
		'TR_SQL_MANAGER' => tr('Sql Manager'),
		'TR_HTTP_REDIRECTIONS' => tr('Http Redirections'),
		'TR_PROGRAMMING' => tr('Programming'),
		'TR_PHP_EDITOR' => tr('PHP Editor'),
		'TR_FEATURES' => tr('Domain Features'),
		'TR_AUTORESPONDERS' => tr('Autoresponders'),
		'TR_EMAIL_ALIASES' => tr('E-Mail Aliases'),
		'TR_EMAIL_FORWARDING' => tr('E-Mail Forwarding'),
		'TR_EMAIL_FEATURES' => tr('E-Mail Features'),
		'TR_CATCH_ALL_EMAIL_ADDRESSES' => tr('Catch-All E-Mail Addresses'),
		'TR_EXTERNAL_MAIL_SERVER' => tr('External Mail Server'),
		'TR_WEBSPACE_FEATURES' => tr('WebSpace Features'),
		'TR_STANDARD_FEATURES' => tr('Standard Features'),
		'TR_WEBMAIL' => tr('Webmail'),
		'TR_FILEMANAGER' => tr('FileManager'),
		'TR_BACKUP' => tr('Backup and restore'),
		'TR_ERROR_PAGES' => tr('Custom Error Pages'),
		'TR_HTACCESS' => tr('Protected Areas'),
		'TR_SOFTWARE_SUPPORT' => tr('Web Software Installer'),
		'TR_DNS_SUPPORT' => tr('Custom DNS Records'),
		'TR_MYSQL_SUPPORT' => tr('SQL Support'),
		'TR_SUBDOMAINS' => tr('Subdomains'),
		'TR_DOMAIN_ALIAS' => tr('Domain Aliases'),
		'TR_IMAP_POP3_EMAIL_ACCOUNTS' => tr('IMAP/POP3 E-Mail Accounts'),
		'TR_FTP_ACCOUNTS' => tr('FTP Accounts'),
		'TR_SQL_DATABASES' => tr('SQL databases'),
		'TR_SQL_USERS' => tr('SQL Users'),
		'TR_STATISTICS' => tr('Web Statistics'),
		'TR_CUSTOM_LOGS' => tr('Custom apache logs'),
		'TR_ONLINE_SUPPORT' => tr('Web & E-Mail Support'),
		'TR_IMSCP' => tr('Control Panel'),
		'TR_UPDATES' => tr('Automatic Updates'),
		'TR_PRICE' => tr('Price'),
		'TR_SETUP_FEE' => tr('Setup Fee'),
		'TR_SUBTOTAL' => tr('Subtotal'),
		'TR_VAT' => tr('Vat'),
		'TR_TOTAL_DUE_TODAY' => tr('Total Due Today'),
		'TR_TOTAL_RECURRING' => tr('Total Recurring'),
		'TR_PERFORMANCE' => tr('Performance'),
		'TR_PURCHASE' => tr('Purchase'),
		'TR_BACK' => tr('Back'),
		'YES' => tr('Yes')
	)
);

gen_plan_details($tpl, $userId, $planId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
