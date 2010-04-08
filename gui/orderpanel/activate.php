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

/**
 * Validate activation parameters
 * @param integer $order_id ID in table orders
 * @param string $key hash value to compare with
 * @return boolean true - validation correct
 */
function validate_order_key($order_id, $key) {
	$result = false;
	$sql = Database::getInstance();
	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`orders`
		WHERE
			`id` = ?
		AND
			`status` = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($order_id, 'unconfirmed'));
	if ($rs->RecordCount() == 1) {
		$domain_name 	= $rs->fields['domain_name'];
		$admin_id 		= $rs->fields['user_id'];
		$coid = Config::getInstance()->exists('CUSTOM_ORDERPANEL_ID') ? Config::getInstance()->get('CUSTOM_ORDERPANEL_ID'): '';
		$ckey = sha1($order_id.'-'.$domain_name.'-'.$admin_id.'-'.$coid);
		if ($ckey == $key)
			$result = true;
	}
	return $result;
}

/**
 * Set order to confirmed so that reseller can activate this
 * @param integer $order_id
 */
function confirm_order($order_id) {

	$sql = Database::getInstance();
	$query = <<<SQL_QUERY
		SELECT
			*
		FROM
			`orders`
		WHERE
			`id` = ?
SQL_QUERY;
	$rs = exec_query($sql, $query, array($order_id));
	if ($rs->RecordCount() == 1) {

		$query = <<<SQL_QUERY
		UPDATE `orders` SET `status`=? WHERE `id`=?
SQL_QUERY;
		exec_query($sql, $query, array('new', $order_id));

		$admin_id 		= $rs->fields['user_id'];
		$domain_name 	= $rs->fields['domain_name'];
		$ufname			= $rs->fields['fname'];
		$ulname			= $rs->fields['lname'];
		$uemail			= $rs->fields['email'];
		$name = trim($ufname.' '.$ulname);

		$data = get_order_email($admin_id);

		$from_name = $data['sender_name'];
		$from_email = $data['sender_email'];

		$search [] = '{DOMAIN}';
		$replace[] = $domain_name;
		$search [] = '{MAIL}';
		$replace[] = $uemail;
		$search [] = '{NAME}';
		$replace[] = $name;

		if ($from_name) {
			$from = '"' . encode($from_name) . "\" <" . $from_email . ">";
		} else {
			$from = $from_email;
		}

		// moved from reseller-functions.php:
		// let's send mail to the reseller => new order
		$subject = encode(tr("You have a new order"));

		$message = tr('

Dear {RESELLER},
you have a new order from {NAME} <{MAIL}> for domain {DOMAIN}

Please login into your ispCP control panel for more details.

'); // Please, do not put tab here - i18n issue

		$search [] = '{RESELLER}';
		$replace[] = $from_name;
		$message = str_replace($search, $replace, $message);
		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		$headers = "From: ". $from . "\n";
		$headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" . "Content-Transfer-Encoding: 8bit\n" . "X-Mailer: ispCP " . Config::getInstance()->get('Version') . " Service Mailer";

		mail($from, $subject, $message, $headers);
	}
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['k'])) {
	system_message(tr('You do not have permission to access this interface!'));
}


$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::getInstance()->get('PURCHASE_TEMPLATE_PATH') . '/activate.tpl');
$tpl->define_dynamic('page_message', 'page');

$theme_color = isset($_SESSION['user_theme'])
	? $_SESSION['user_theme']
	: Config::getInstance()->get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'THEME_COLOR_PATH' => '../themes/' . $theme_color,
		'THEME_CHARSET' => tr('encoding')
	)
);


if (validate_order_key($_GET['id'], $_GET['k'])) {
	confirm_order($_GET['id']);
	$msg = tr('Your order has been successfully created.');
} else {
	$msg = tr('Error creating order! Perhaps already activated?');
}

$tpl->assign('ORDER_STATUS_MESSAGE', $msg);
$tpl->assign('PAGE_TITLE', tr('Order confirmation'));

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::getInstance()->get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}
