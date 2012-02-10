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
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/circular.tpl',
		'page_message' => 'layout'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP Reseller/Circular'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),));

/**
 * @param $tpl
 */
function gen_page_data($tpl) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'send_circular') {
		$tpl->assign(
			array(
				'MESSAGE_SUBJECT' => clean_input($_POST['msg_subject'], true),
				'MESSAGE_TEXT' => clean_input($_POST['msg_text'], true),
				'SENDER_EMAIL' => clean_input($_POST['sender_email'], true),
				'SENDER_NAME' => clean_input($_POST['sender_name'], true)));
	} else {
		$user_id = $_SESSION['user_id'];

		$query = "
			SELECT
				`fname`, `lname`, `email`
			FROM
				`admin`
			WHERE
				`admin_id` = ?
			GROUP BY
				`email`
		";

		$rs = exec_query($query, $user_id);

		if (isset($rs->fields['fname']) && isset($rs->fields['lname'])) {
			$sender_name = $rs->fields['fname'] . ' ' . $rs->fields['lname'];
		} elseif (isset($rs->fields['fname']) && !isset($rs->fields['lname'])) {
			$sender_name = $rs->fields['fname'];
		} elseif (!isset($rs->fields['fname']) && isset($rs->fields['lname'])) {
			$sender_name = $rs->fields['lname'];
		} else {
			$sender_name = '';
		}

		$tpl->assign(
			array(
				'MESSAGE_SUBJECT' => '',
				'MESSAGE_TEXT' => '',
				'SENDER_EMAIL' => tohtml($rs->fields['email']),
				'SENDER_NAME' => tohtml($sender_name)));
	}
}

/**
 * @param $tpl
 * @return bool
 */
function check_user_data($tpl) {
	global $msg_subject, $msg_text, $sender_email, $sender_name;

	$msg_subject = clean_input($_POST['msg_subject'], false);
	$msg_text = clean_input($_POST['msg_text'], false);
	$sender_email = clean_input($_POST['sender_email'], false);
	$sender_name = clean_input($_POST['sender_name'], false);

	if (empty($msg_subject)) {
		set_page_message(tr('Please specify a message subject.'), 'error');
	}

	if (empty($msg_text)) {
		set_page_message(tr('Please specify a message content.'), 'error');
	}

	if (empty($sender_name)) {
		set_page_message(tr('Please specify a sender name.'), 'error');
	}

	if (empty($sender_email)) {
		set_page_message(tr('Please specify a sender email.'), 'error');
	} else if (!chk_email($sender_email)) {
		set_page_message(tr('Incorrect email length or syntax.'), 'error');
	}

	if(Zend_Session::namespaceIsset('pageMessages')) {
		return false;
	} else {
		return true;
	}
}

/**
 * @param $tpl
 */
function send_circular($tpl) {
	if (isset($_POST['uaction']) && $_POST['uaction'] === 'send_circular') {
		if (check_user_data($tpl)) {
			send_reseller_users_message($_SESSION['user_id']);
			unset($_POST['uaction']);
			gen_page_data($tpl);
		}
	}
}

/**
 * @param $admin_id
 */
function send_reseller_users_message($admin_id) {

	$msg_subject = clean_input($_POST['msg_subject'], false);
	$msg_text = clean_input($_POST['msg_text'], false);
	$sender_email = clean_input($_POST['sender_email'], false);
	$sender_name = clean_input($_POST['sender_name'], false);

	$query = "
		SELECT
			`fname`, `lname`, `email`
		FROM
			`admin`
		WHERE
			`admin_type` = 'user' AND `created_by` = ?
		GROUP BY
			`email`
	";
	$rs = exec_query($query, $admin_id);

	while (!$rs->EOF) {
		$to = "\"" . encode($rs->fields['fname'] . " " . $rs->fields['lname']) . "\" <" . $rs->fields['email'] . ">";

		send_circular_email($to, "\"" . encode($sender_name) . "\" <" . $sender_email . ">", $msg_subject, $msg_text);

		$rs->moveNext();
	}
	
	$sender_name = tohtml($sender_name);
	set_page_message(tr('Mail successfully sent to your users.'), 'success');
	write_log("Mass email was sent from Reseller " . $sender_name . " <" . $sender_email . ">", E_USER_NOTICE);
}

/**
 * @param $to
 * @param $from
 * @param $subject
 * @param $message
 */
function send_circular_email($to, $from, $subject, $message) {
	$subject = encode($subject);
	$headers = "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit\n";
	$headers .= "From: " . $from . "\n";
	$headers .= "X-Mailer: i-MSCP marketing mailer";

	mail($to, $subject, $message, $headers);
}

generateNavigation($tpl);

$tpl->assign(
	array(
		'TR_CIRCULAR' => tr('Circular'),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_SEND_TO' => tr('Send message to'),
		'TR_ALL_USERS' => tr('All users'),
		'TR_ALL_RESELLERS' => tr('All resellers'),
		'TR_ALL_USERS_AND_RESELLERS' => tr('All users & resellers'),
		'TR_MESSAGE_SUBJECT' => tr('Message subject'),
		'TR_MESSAGE_TEXT' => tr('Message'),
		'TR_ADDITIONAL_DATA' => tr('Additional data'),
		'TR_SENDER_EMAIL' => tr('Senders email'),
		'TR_SENDER_NAME' => tr('Senders name'),
		'TR_SEND_MESSAGE' => tr('Send message'),
		'TR_SENDER_NAME' => tr('Senders name')));

send_circular($tpl);
gen_page_data($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
