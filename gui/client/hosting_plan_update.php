<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('CLIENT_TEMPLATE_PATH') . '/hosting_plan_update.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('def_language', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('hosting_plans', 'page');
$tpl->define_dynamic('hp_order', 'page');

/*
 *
 * page actions.
 *
 */

function gen_hp(&$tpl, &$sql, $user_id) {
	$availabe_order = 0;
	$hp_title = tr('Hosting plans available for update');
	// let's see if we have an order
	$query = "
		SELECT
			*
		FROM
			`orders`
		WHERE
			`customer_id` = ?
		AND
			`status` <> ?
	";
	$rs = exec_query($sql, $query, array($user_id, 'added'));

	if ($rs->RecordCount() > 0) {
		$availabe_order = 1;
		$availabe_hp_id = $rs->fields['plan_id'];

		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`id` = ?
		";

		$rs = exec_query($sql, $query, array($availabe_hp_id));
		$count = 2;
		$purchase_text = tr('Cancel order');
		$purchase_link = 'delete_id';
		$hp_title = tr('Your order');
	} else {
		// generate all hosting plans available for purchasing
		if (Config::exists('HOSTING_PLANS_LEVEL') && Config::get('HOSTING_PLANS_LEVEL') === 'admin') {
			$query = "
				SELECT
					t1.*,
					t2.`admin_id`, t2.`admin_type`
				FROM
					`hosting_plans` AS t1,
					`admin` AS t2
				WHERE
					t2.`admin_type` = ?
				AND
					t1.`reseller_id` = t2.`admin_id`
				AND
					t1.`status` = '1'
				ORDER BY
					t1.`name`
			";

			$rs = exec_query($sql, $query, array('admin'));

			$count = $rs->RecordCount();
			$count++;
		} else {
			$query = "
				SELECT
					*
				FROM
					`hosting_plans`
				WHERE
					`reseller_id` = ?
				AND
					`status` = '1'
			";

			$count_query = "
				SELECT
					COUNT(`id`) AS cnum
				FROM
					`hosting_plans`
				WHERE
					`reseller_id` = ?
				AND
					`status` = '1'
			";

			$cnt = exec_query($sql, $count_query, array($_SESSION['user_created_by']));
			$rs = exec_query($sql, $query, array($_SESSION['user_created_by']));
			$count = $cnt->fields['cnum'] + 1;
		}

		$purchase_text = tr('Purchase');
		$purchase_link = 'order_id';
	}

	if ($rs->RecordCount() == 0) {
		$tpl->assign(
			array(
				'TR_HOSTING_PLANS'	=> $hp_title,
				'HOSTING_PLANS'		=> '',
				'HP_ORDER'			=> '',
				'COLSPAN'			=> 2
			)
		);

		set_page_message(tr('There are no available updates'));
		return;
	}

	$tpl->assign('COLSPAN', $count);
	$i = 0;
	while (!$rs->EOF) {
		list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk, $hp_dns) = explode(";", $rs->fields['props']);

		$details = '';

		if ($hp_php === '_yes_') {
			$details = tr('PHP Support: enabled') . "<br />";
			$php = "yes";
		} else {
			$details = tr('PHP Support: disabled') . "<br />";
			$php = "no";
		}
		if ($hp_cgi === '_yes_') {
			$cgi = "yes";
			$details .= tr('CGI Support: enabled') . "<br />";
		} else {
			$cgi = "no";
			$details .= tr('CGI Support: disabled') . "<br />";
		}
		if($hp_dns ==='_yes_'){
			$dns = "yes";
			$details .= tr('DNS Support: enabled') . "<br />";
		} else {
			$dns = "no";
			$details .= tr('DNS Support: disabled') . "<br />";
		}
		$hdd_usage = tr('Disk limit') . ": " . translate_limit_value($hp_disk, true) . "<br />";

		$traffic_usage = tr('Traffic limit') . ": " . translate_limit_value($hp_traff, true);

		$details .= tr('Aliases') . ": " . translate_limit_value($hp_als) . "<br />";
		$details .= tr('Subdomains') . ": " . translate_limit_value($hp_sub) . "<br />";
		$details .= tr('Emails') . ": " . translate_limit_value($hp_mail) . "<br />";
		$details .= tr('FTPs') . ": " . translate_limit_value($hp_ftp) . "<br />";
		$details .= tr('SQL Databases') . ": " . translate_limit_value($hp_sql_db) . "<br />";
		$details .= tr('SQL Users') . ": " . translate_limit_value($hp_sql_user) . "<br />";
		$details .= $hdd_usage . $traffic_usage;

		$price = $rs->fields['price'];
		if ($price == 0 || $price == '') {
			$price = tr('free of charge');
		} else {
			$price = $price . " " . $rs->fields['value'] . " " . $rs->fields['payment'];
		}

		$check_query = "
			SELECT
				`domain_id`
			FROM
				`domain`
			WHERE
				`domain_admin_id` = ?
			AND
				`domain_mailacc_limit` = ?
			AND
				`domain_ftpacc_limit` = ?
			AND
				`domain_traffic_limit` = ?
			AND
				`domain_sqld_limit` = ?
			AND
				`domain_sqlu_limit` = ?
			AND
				`domain_alias_limit` = ?
			AND
				`domain_subd_limit` = ?
			AND
				`domain_disk_limit` = ?
			AND
				`domain_php` = ?
			AND
				`domain_cgi` = ?
			AND
				`domain_dns` = ?
		";

		$check = exec_query($sql, $check_query, array($_SESSION['user_id'], $hp_mail, $hp_ftp, $hp_traff, $hp_sql_db, $hp_sql_user, $hp_als, $hp_sub, $hp_disk, $php, $cgi, $dns));
		if ($check->RecordCount() == 0) {
			$tpl->assign(
				array(
					'HP_NAME'			=> stripslashes($rs->fields['name']),
					'HP_DESCRIPTION'	=> stripslashes($rs->fields['description']),
					'HP_DETAILS'		=> stripslashes($details),
					'HP_COSTS'			=> $price,
					'ID'				=> $rs->fields['id'],
					'TR_PURCHASE'		=> $purchase_text,
					'LINK'				=> $purchase_link,
					'TR_HOSTING_PLANS'	=> $hp_title,
					'ITHEM'				=> ($i % 2 == 0) ? 'content' : 'content2'
				)
			);

			$tpl->parse('HOSTING_PLANS', '.hosting_plans');
			$tpl->parse('HP_ORDER', '.hp_order');
			$i++;
		}

		$rs->MoveNext();
	}
	if ($i == 0) {
		$tpl->assign(
			array(
				'HOSTING_PLANS'		=> '',
				'HP_ORDER'			=> '',
				'TR_HOSTING_PLANS'	=> $hp_title,
				'COLSPAN'			=> '2'
			)
		);

		set_page_message(tr('There are no available hosting plans for update'));
	}
}

$theme_color = Config::get('USER_INITIAL_THEME');
$tpl->assign(
	array(
		'TR_CLIENT_UPDATE_HP'	=> tr('ispCP - Update hosting plan'),
		'THEME_COLOR_PATH'		=> "../themes/$theme_color",
		'THEME_CHARSET'			=> tr('encoding'),
		'ISP_LOGO'				=> get_logo($_SESSION['user_id'])
	)
);

/**
 * @todo the 2nd query has 2 identical tables in FROM-clause, is this OK?
 */
function add_new_order(&$tpl, &$sql, $order_id, $user_id) {
	$date = time();
	$status = "update";
	$query = "
		INSERT INTO `orders`
			(`user_id`,
			`plan_id`,
			`date`,
			`domain_name`,
			`customer_id`,
			`fname`,
			`lname`,
			`firm`,
			`zip`,
			`city`,
			`state`,
			`country`,
			`email`,
			`phone`,
			`fax`,
			`street1`,
			`street2`,
			`status`)
		VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	";

	$rs = exec_query($sql, $query, array(
		$_SESSION['user_created_by'], $order_id, $date, $_SESSION['user_logged'],
		$user_id, '', '', '', '', '', '', '', '', '', '', '', '', $status
	));
	set_page_message(tr('Your request for hosting pack update was added successfully'));

	$query = "
		SELECT
			t1.`email` AS reseller_mail,
			t2.`email` AS user_mail
		FROM
			`admin` AS t1,
			`admin` AS t2
		WHERE
			t1.`admin_id` = ?
		AND
			t2.`admin_id` = ?
	";

	$rs = exec_query($sql, $query, array($_SESSION['user_created_by'], $_SESSION['user_id']));

	$to = $rs->fields['reseller_mail'];
	$from = $rs->fields['user_mail'];

	$headers = "From: " . $from . "\n";
	$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";
	$headers .= "X-Mailer: ispCP auto mailer";

	$subject = tr("[ispCP OrderPanel] - You have an update order", true);

	$message = tr('You have an update order for the account {ACCOUNT}


Please login into your ispCP control panel for more details', true);

	$message = str_replace('{ACCOUNT}', $_SESSION['user_logged'], $message);

	$mail_result = mail($to, $subject, $message, $headers);
}

function del_order(&$tpl, &$sql, $order_id, $user_id) {
	$query = "
		DELETE FROM
			`orders`
		WHERE
			`user_id` = ?
		AND
			`customer_id` = ?
	";

	$rs = exec_query($sql, $query, array($_SESSION['user_created_by'], $user_id));
	set_page_message(tr('Your request for hosting pack update was removed successfully'));
}

/*
 *
 * static page messages.
 *
 */

if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
	del_order($tpl, $sql, $_GET['delete_id'], $_SESSION['user_id']);
}

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
	add_new_order($tpl, $sql, $_GET['order_id'], $_SESSION['user_id']);
}

gen_hp($tpl, $sql, $_SESSION['user_id']);

gen_client_mainmenu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/main_menu_general_information.tpl');
gen_client_menu($tpl, Config::get('CLIENT_TEMPLATE_PATH') . '/menu_general_information.tpl');

gen_logged_from($tpl);

check_permissions($tpl);

$tpl->assign(
	array(
		'TR_LANGUAGE'	=> tr('Language'),
		'TR_SAVE'		=> tr('Save'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>