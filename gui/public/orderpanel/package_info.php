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
 * @subpackage	Orderpanel
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/************************************************************************************
 * Script functions
 */

/**
 * Translates a string.
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
 * Generates hosting plan details.
 *
 * @param iMSCP_pTemplate $tpl Tempalte engine.
 * @param int $user_id User unique identifier
 * @param int $plan_id Plan unique identifier
 * @return void
 */
function gen_plan_details($tpl, $user_id, $plan_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "SELECT * FROM `hosting_plans` WHERE `id` = ?";
		$stmt = exec_query($query, $plan_id);
	} else {
		$query = "SELECT * FROM `hosting_plans` WHERE `reseller_id` = ? AND `id` = ?";
		$stmt = exec_query($query, array($user_id, $plan_id));
	}

	if ($stmt->recordCount() == 0) {
		redirectTo('index.php?user_id=' . $user_id);
	} else {
		$props = $stmt->fields['props'];

		list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db,
			$hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns, $hp_allowsoftware
		) = explode(';', $props);

		$price = $stmt->fields['price'];
		$setup_fee = $stmt->fields['setup_fee'];

		if ($price == 0 || $price == '') {
			$price = tr('free of charge');
		} else {
			$price .= ' ' . tohtml($stmt->fields['value']) . ' ' . tohtml($stmt->fields['payment']);
		}

		if ($setup_fee == 0 || $setup_fee == '') {
			$setup_fee = tr('free of charge');
		} else {
			$setup_fee .= ' ' . $stmt->fields['value'];
		}

		$description = $stmt->fields['description'];
		$hp_disk = translate_limit_value($hp_disk, true) . "<br />";
		$hp_traff = translate_limit_value($hp_traff, true);
		$coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';

		$tpl->assign(
			array(
				'PACK_NAME' => $stmt->fields['name'],
				'DESCRIPTION' => tohtml($description),
				'PACK_ID' => $stmt->fields['id'],
				'USER_ID' => $user_id,
				'PURCHASE' => tr('Purchase'),
				'ALIAS' => translate_limit_value($hp_als),
				'SUBDOMAIN' => translate_limit_value($hp_sub),
				'HDD' => $hp_disk,
				'TRAFFIC' => $hp_traff,
				'PHP' => translate_sse($hp_php),
				'SOFTWARE' => translate_sse($hp_allowsoftware),
				'CGI' => translate_sse($hp_cgi),
				'DNS' => translate_sse($hp_dns),
				'BACKUP' => translate_sse($hp_backup),
				'MAIL' => translate_limit_value($hp_mail),
				'FTP' => translate_limit_value($hp_ftp),
				'SQL_DB' => translate_limit_value($hp_sql_db),
				'SQL_USR' => translate_limit_value($hp_sql_user),
				'PRICE' => $price,
				'SETUP' => $setup_fee,
				'CUSTOM_ORDERPANEL_ID' => $coid));

		if ($stmt->fields['status'] != 1) {
			$tpl->assign('ISENABLED', '');
		}
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
	$plan_id = $_GET['id'];
	$_SESSION['order_panel_plan_id'] = $plan_id;
	if (isset($_SESSION['order_panel_user_id'])) {
		$user_id = $_SESSION['order_panel_user_id'];
	} else if (isset($_GET['user_id'])) {
		$user_id = $_GET['user_id'];
		$_SESSION['order_panel_user_id'] = $user_id;
	} else {
		throw new iMSCP_Exception_Production(tr('You do not have permission to access this interface.'));
	}
} else {
	throw new iMSCP_Exception_Production(tr('You do not have permission to access this interface.'));
}

$tpl = new iMSCP_pTemplate();
$tpl->define_no_file('layout', implode('', gen_purchase_haf($user_id)));
$tpl->define_dynamic(
	array(
		'page' => 'orderpanel/package_info.tpl',
		'page_message' => 'page', // Must be in page here
		't_software_support' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => 'Order Panel / Package Info',
		'THEME_CHARSET' => tr('encoding'),
		'TR_DOMAINS' => tr('Domains'),
		'TR_WEBSPACE' => tr('Webspace'),
		'TR_HDD' => tr('Disk limit'),
		'TR_TRAFFIC' => tr('Traffic limit'),
		'TR_FEATURES' => tr('Domain Features'),
		'TR_STANDARD_FEATURES' => tr('Package features'),
		'TR_WEBMAIL' => tr('Webmail'),
		'TR_FILEMANAGER' => tr('Filemanager'),
		'TR_BACKUP' => tr('Backup and restore'),
		'TR_ERROR_PAGES' => tr('Custom error pages'),
		'TR_HTACCESS' => tr('Protected Areas'),
		'TR_PHP_SUPPORT' => tr('PHP'),
		'TR_SOFTWARE_SUPPORT' => tr('Softwares installer'),
		'TR_CGI_SUPPORT' => tr('CGI'),
		'TR_DNS_SUPPORT' => tr('Custom DNS records'),
		'TR_MYSQL_SUPPORT' => tr('SQL support'),
		'TR_SUBDOMAINS' => tr('Subdomains'),
		'TR_DOMAIN_ALIAS' => tr('Domain aliases'),
		'TR_MAIL_ACCOUNTS' => tr('Mail accounts'),
		'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
		'TR_SQL_DATABASES' => tr('SQL databases'),
		'TR_SQL_USERS' => tr('SQL users'),
		'TR_STATISTICS' => tr('Statistics'),
		'TR_CUSTOM_LOGS' => tr('Custom apache logs'),
		'TR_ONLINE_SUPPORT' => tr('Web & E-Mail Support'),
		'TR_OWN_DOMAIN' => tr('Your own domain'),
		'TR_IMSCP' => tr('i-MSCP Control Panel'),
		'TR_UPDATES' => tr('Automatic updates'),
		'TR_PRICE' => tr('Price'),
		'TRR_PRICE' => tr('Package price'),
		'TR_SETUP_FEE' => tr('Setup fee'),
		'TR_PERFORMANCE' => tr('Performance'),
		'TR_PURCHASE' => tr('Purchase'),
		'TR_BACK' => tr('Back'),
		'YES' => tr('Yes')));

gen_plan_details($tpl, $user_id, $plan_id);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
