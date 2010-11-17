<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

function get_email_tpl_data($admin_id, $tpl_name) {

	$sql = iMSCP_Registry::get('Db');

	$query = "
		SELECT
			`fname`, `lname`, `firm`, `email`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";

	$rs = exec_query($sql, $query, $admin_id);

	if ((trim($rs->fields('fname')) != '') && (trim($rs->fields('lname')) != '')) {
		$data['sender_name'] = $rs->fields('fname') . ' ' . $rs->fields('lname');
	} else if (trim($rs->fields('fname')) != '') {
		$data['sender_name'] = $rs->fields('fname');
	} else if (trim($rs->fields('lname')) != '') {
		$data['sender_name'] = $rs->fields('lname');
	} else {
		$data['sender_name'] = '';
	}

	if ($rs->fields('firm') != '') {
		if ($data['sender_name'] != '') {
			$data['sender_name'] .= ' ' . '[' . $rs->fields('firm') . ']';
		} else {
			$data['sender_name'] = $rs->fields('firm');
		}
	}

	$data['sender_email'] = $rs->fields('email');

	$query = "
		SELECT
			`subject`, `message`
		FROM
			`email_tpls`
		WHERE
			`owner_id` = ?
		AND
			`name` = ?
	";

	$rs = exec_query($sql, $query, array($admin_id, $tpl_name));

	if ($rs->rowCount() == 1) {
		$data['subject'] = $rs->fields['subject'];
		$data['message'] = $rs->fields['message'];
	} else {
		$data['subject'] = '';
		$data['message'] = '';
	}

	return $data;
}

function set_email_tpl_data($admin_id, $tpl_name, $data) {

	$sql = iMSCP_Registry::get('Db');

	$query = "
		SELECT
			`subject`, `message`
		FROM
			`email_tpls`
		WHERE
			`owner_id` = ?
		AND
			`name` = ?
	";

	$rs = exec_query($sql, $query, array($admin_id, $tpl_name));

	if ($rs->rowCount() == 0) {

		$query = "
			INSERT INTO `email_tpls`
				(`subject`, `message`, `owner_id`, `name`)
			VALUES
				(?, ?, ?, ?)
		";

	} else {

		$query = "
			UPDATE
				`email_tpls`
			SET
				`subject` = ?,
				`message` = ?
			WHERE
				`owner_id` = ?
			AND
				`name` = ?
		";

	}

	exec_query($sql, $query, array($data['subject'], $data['message'], $admin_id, $tpl_name));
}

function get_welcome_email($admin_id, $admin_type='user') {

	$data = get_email_tpl_data($admin_id, 'add-user-auto-msg');

	if (!$data['subject']) {
		$data['subject'] = tr('Welcome {USERNAME} to i-MSCP!', true);
	}

	if (!$data['message']) {
        if ($admin_type == 'user') {
            $data['message'] = tr('

Hello {NAME}!

A new i-MSCP account has been created for you.
Your account information:

User type: {USERTYPE}
User name: {USERNAME}
Password: {PASSWORD}

Remember to change your password often and the first time you login.

You can login right now at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}

Statistics: {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/{USERNAME}/stats/
User name: {USERNAME}
Password: {PASSWORD}

Best wishes with i-MSCP!
The i-MSCP Team.

', true);
        } else {
            $data['message'] = tr('

Hello {NAME}!

A new i-MSCP account has been created for you.
Your account information:

User type: {USERTYPE}
User name: {USERNAME}
Password: {PASSWORD}

Remember to change your password often and the first time you login.

You can login right now at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}

User name: {USERNAME}
Password: {PASSWORD}

Best wishes with i-MSCP!
The i-MSCP Team.

', true);
        }

	}

	return $data;
}

function set_welcome_email($admin_id, $data) {
	set_email_tpl_data($admin_id, 'add-user-auto-msg', $data);
}

function get_lostpassword_activation_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'lostpw-msg-1');

	if (!$data['subject']) {
		$data['subject'] = tr('Please activate your new i-MSCP password!', true);
	}

	if (!$data['message']) {
		$data['message'] = tr('

Hello {NAME}!
Use this link to activate your new i-MSCP password:

{LINK}

Good Luck with the i-MSCP System
The i-MSCP Team

', true);

	}

	return $data;
}

function set_lostpassword_activation_email($admin_id, $data) {
	set_email_tpl_data($admin_id, 'lostpw-msg-1', $data);
}

function get_lostpassword_password_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'lostpw-msg-2');

	if (!$data['subject']) {
		$data['subject'] = tr('Your new i-MSCP login!', true);
	}

	if (!$data['message']) {
		$data['message'] = tr('

Hello {NAME}!

Your user name is: {USERNAME}
Your password is: {PASSWORD}

You can login at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}

Best wishes with i-MSCP!
The i-MSCP Team

', true);

	}

	return $data;
}

function set_lostpassword_password_email($admin_id, $data) {
	set_email_tpl_data($admin_id, 'lostpw-msg-2', $data);
}

function get_order_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'after-order-msg');

	if (!$data['subject']) {
		$data['subject'] = tr('Confirmation for domain order {DOMAIN}!', true);
	}

	if (!$data['message']) {
		$data['message'] = tr('

Dear {NAME},
This is an automatic confirmation for the order of the domain:

{DOMAIN}

You have to click the following link to continue the domain creation process.

{ACTIVATE_LINK}

Thank you for using i-MSCP services.
The i-MSCP Team

', true);

	}

	return $data;
}

function set_order_email($admin_id, $data) {
	set_email_tpl_data($admin_id, 'after-order-msg', $data);
}

function get_alias_order_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'alias-order-msg');

	if (!$data['subject']) {
		$data['subject'] = tr('New alias order for {CUSTOMER}!', true);
	}

	if (!$data['message']) {
		$data['message'] = tr('

Dear {RESELLER},
Your customer {CUSTOMER} is awaiting for the approval of his new alias:

{ALIAS}

Once logged in, you can activate his new alias at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/reseller/alias.php

Thank you for using i-MSCP services.
The i-MSCP Team

', true);

	}

	return $data;
}
