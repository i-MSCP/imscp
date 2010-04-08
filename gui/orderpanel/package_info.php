<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

require '../include/ispcp-lib.php';

$tpl = new pTemplate();

$tpl->define_dynamic('page', Config::getInstance()->get('PURCHASE_TEMPLATE_PATH') . '/package_info.tpl');
$tpl->define_dynamic('purchase_list', 'page');
$tpl->define_dynamic('purchase_message', 'page');
$tpl->define_dynamic('purchase_header', 'page');
$tpl->define_dynamic('purchase_footer', 'page');
$tpl->define_dynamic('isenabled', 'page');

/*
 * functions start
 */

function translate_sse($value) {
	if ($value == '_yes_') {
		return tr('Yes');
	} else if ($value == '_no_') {
		return tr('No');
	} else if ($value == '_sql_') {
		return tr('SQL');
	} else if ($value == '_full_') {
		return tr('Full');
	} else if ($value == '_dmn_') {
		return tr('Domain');
	} else {
		return $value;
	}
}

function gen_plan_details(&$tpl, &$sql, $user_id, $plan_id) {
	if (Config::getInstance()->exists('HOSTING_PLANS_LEVEL')
		&& Config::getInstance()->get('HOSTING_PLANS_LEVEL') === 'admin') {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($plan_id));
	} else {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`reseller_id` = ?
			AND
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($user_id, $plan_id));
	}

	if ($rs->RecordCount() == 0) {
		user_goto('index.php?user_id=' . $user_id);
	} else {
		$props = $rs->fields['props'];
		list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk, $hp_backup, $hp_dns) = explode(";", $props);

		$price = $rs->fields['price'];
		$setup_fee = $rs->fields['setup_fee'];

		if ($price == 0 || $price == '') {
			$price = tr('free of charge');
		} else {
			$price .= ' ' . $rs->fields['value'] . ' ' . $rs->fields['payment'];
		}

		if ($setup_fee == 0 || $setup_fee == '') {
			$setup_fee = tr('free of charge');
		} else {
			$setup_fee .= ' ' . $rs->fields['value'];
		}
		$description = $rs->fields['description'];

		$hp_disk = translate_limit_value($hp_disk, true) . "<br />";

		$hp_traff = translate_limit_value($hp_traff, true);

		$coid = Config::getInstance()->exists('CUSTOM_ORDERPANEL_ID') ? Config::getInstance()->get('CUSTOM_ORDERPANEL_ID'): '';

		$tpl->assign(
			array(
				'PACK_NAME'		=> $rs->fields['name'],
				'DESCRIPTION'	=> $description,
				'PACK_ID'		=> $rs->fields['id'],
				'USER_ID'		=> $user_id,
				'PURCHASE'		=> tr('Purchase'),
				'ALIAS'			=> translate_limit_value($hp_als),
				'SUBDOMAIN'		=> translate_limit_value($hp_sub),
				'HDD'			=> $hp_disk,
				'TRAFFIC'		=> $hp_traff,
				'PHP'			=> translate_sse($hp_php),
				'CGI'			=> translate_sse($hp_cgi),
				'DNS'			=> translate_sse($hp_dns),
				'BACKUP'		=> translate_sse($hp_backup),
				'MAIL'			=> translate_limit_value($hp_mail),
				'FTP'			=> translate_limit_value($hp_ftp),
				'SQL_DB'		=> translate_limit_value($hp_sql_db),
				'SQL_USR'		=> translate_limit_value($hp_sql_user),
				'PRICE'			=> $price,
				'SETUP'			=> $setup_fee,
				'CUSTOM_ORDERPANEL_ID'	=> $coid
			)
		);

		if ($rs->fields['status'] != 1) {
			$tpl->assign('ISENABLED', '');
		}
	}
}

/*
 * functions end
 */

/*
 *
 * static page messages.
 *
 */

$coid = Config::getInstance()->exists('CUSTOM_ORDERPANEL_ID') ? Config::getInstance()->get('CUSTOM_ORDERPANEL_ID'): '';
$bcoid = (empty($coid) || (isset($_GET['coid']) && $_GET['coid'] == $coid));

if (isset($_GET['id']) && $bcoid) {
	$plan_id = $_GET['id'];
	$_SESSION['plan_id'] = $plan_id;
	if (isset($_SESSION['user_id'])) {
		$user_id = $_SESSION['user_id'];
	} else if (isset($_GET['user_id'])) {
		$user_id = $_GET['user_id'];
		$_SESSION['user_id'] = $user_id;
	} else {
		system_message(tr('You do not have permission to access this interface!'));
	}
} else {
	system_message(tr('You do not have permission to access this interface!'));
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_plan_details($tpl, $sql, $user_id, $plan_id);

gen_page_message($tpl);

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
	)
);

$tpl->assign(
	array(
		'TR_DOMAINS'			=> tr('Domains'),
		'TR_WEBSPACE'			=> tr('Webspace'),
		'TR_HDD'				=> tr('Disk limit'),
		'TR_TRAFFIC'			=> tr('Traffic limit'),
		'TR_FEATURES'			=> tr('Domain Features'),
		'TR_STANDARD_FEATURES'	=> tr('Package Features'),
		'TR_WEBMAIL'			=> tr('Webmail'),
		'TR_FILEMANAGER'		=> tr('Filemanager'),
		'TR_BACKUP'				=> tr('Backup and Restore'),
		'TR_ERROR_PAGES'		=> tr('Custom Error Pages'),
		'TR_HTACCESS'			=> tr('Protected Areas'),
		'TR_PHP_SUPPORT'		=> tr('PHP support'),
		'TR_CGI_SUPPORT'		=> tr('CGI support'),
		'TR_DNS_SUPPORT'		=> tr('Manual DNS support'),
		'TR_MYSQL_SUPPORT'		=> tr('SQL support'),
		'TR_SUBDOMAINS'			=> tr('Subdomains'),
		'TR_DOMAIN_ALIAS'		=> tr('Domain aliases'),
		'TR_MAIL_ACCOUNTS'		=> tr('Mail accounts'),
		'TR_FTP_ACCOUNTS'		=> tr('FTP accounts'),
		'TR_SQL_DATABASES'		=> tr('SQL databases'),
		'TR_SQL_USERS'			=> tr('SQL users'),
		'TR_STATISTICS'			=> tr('Statistics'),
		'TR_CUSTOM_LOGS'		=> tr('Custom Apache Logs'),
		'TR_ONLINE_SUPPORT'		=> tr('Web & E-Mail Support'),
		'TR_OWN_DOMAIN'			=> tr('Your Own Domain'),
		'TR_ISPCP'				=> tr('ispCP Control Panel'),
		'TR_UPDATES'			=> tr('Automatic Updates'),
		'TR_PRICE'				=> tr('Price'),
		'TRR_PRICE'				=> tr('Package Price'),
		'TR_SETUP_FEE'			=> tr('Setup Fee'),
		'TR_PERFORMANCE'		=> tr('Performance'),
		'TR_PURCHASE'			=> tr('Purchase'),
		'TR_BACK'				=> tr('Back'),
		'YES'					=> tr('Yes')
	)
);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}

unset_messages();
