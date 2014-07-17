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
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Returns email template data
 *
 * @param int $userId User unique identifier
 * @param string $tplName Template name
 * @return array An array containing email parts (sender_name, sender_name_email, subject, message)
 */
function get_email_tpl_data($userId, $tplName)
{
	$stmt = exec_query("SELECT `fname`, `lname`, `firm`, `email` FROM `admin` WHERE `admin_id` = ?", $userId);

	if ($stmt->rowCount()) {
		$firstname = trim($stmt->fields('fname'));
		$lastname = trim($stmt->fields('lname'));

		if ($firstname != '' && $lastname != '') {
			$data['sender_name'] = $firstname . ' ' . $lastname;
		} else if ($firstname != '') {
			$data['sender_name'] = $firstname;
		} else if ($lastname != '') {
			$data['sender_name'] = $lastname;
		} else {
			$data['sender_name'] = '';
		}

		$firm = trim($stmt->fields('firm'));

		if ($firm != '') {
			if ($data['sender_name'] != '') {
				$data['sender_name'] .= " [$firm]";
			} else {
				$data['sender_name'] = $firm;
			}
		}

		$data['sender_email'] = $stmt->fields('email');

		$query = "SELECT `subject`, `message` FROM `email_tpls` WHERE `owner_id` = ? AND `name` = ?";
		$stmt = exec_query($query, array($userId, $tplName));

		if ($stmt->rowCount()) {
			$data['subject'] = $stmt->fields['subject'];
			$data['message'] = $stmt->fields['message'];
		} else {
			$data['subject'] = '';
			$data['message'] = '';
		}

		return $data;
	} else {
		throw new iMSCP_Exception('Unable to find email template data');
	}
}

/**
 * Sets or updates an email template in database
 *
 * @param int $userId User unique identifier
 * @param string $tplName Template name
 * @param array $data An associative array where each key correspond to a specific email parts: subject, message
 * @return void
 */
function set_email_tpl_data($userId, $tplName, $data)
{
	$query = "SELECT `subject`, `message` FROM `email_tpls` WHERE `owner_id` = ? AND `name` = ?";
	$stmt = exec_query($query, array($userId, $tplName));

	if (!$stmt->rowCount()) {
		$query = "INSERT INTO `email_tpls` (`subject`, `message`, `owner_id`, `name`) VALUES (?, ?, ?, ?)";
	} else {
		$query = "UPDATE `email_tpls` SET `subject` = ?, `message` = ? WHERE `owner_id` = ? AND `name` = ?";
	}

	exec_query($query, array($data['subject'], $data['message'], $userId, $tplName));
}

/**
 * Generates and returns welcome email
 *
 * @see get_email_tpl_data()
 * @param int $userId User unique identifier - Template owner
 * @param string $userType User type
 * @return array An associative array where each key correspond to a specific email parts: sender_name,
 *               sender_name_email, subject, message
 */
function get_welcome_email($userId, $userType = 'user')
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$data = get_email_tpl_data($userId, 'add-user-auto-msg');

	if ($data['subject'] == '') {
		$data['subject'] = tr('Welcome {USERNAME} to i-MSCP', true);
	}

	// No custom template for welcome mail - return the default
	if ($data['message'] == '') {
		if ($userType == 'user' && $cfg->WEBSTATS_PACKAGES != 'No') {
			$data['message'] = tr('
Dear {NAME},

A new account has been created for you.

Your account information:

User type: {USERTYPE}
User name: {USERNAME}
Password: {PASSWORD}

Remember to change your password often and the first time you login.

You can login right now at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}

Statistics: http://{USERNAME}/{WEBSTATS_RPATH}
(Same username and password than above)

Thank you for using our services.
', true);

		} else {
			$data['message'] = tr('
Dear {NAME},

A new account has been created for you.

Your account information:

User type: {USERTYPE}
User name: {USERNAME}
Password: {PASSWORD}

Remember to change your password often and the first time you login.

You can login right now at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}

Thank you for using our services.
', true);
		}
	}

	return $data;
}

/**
 * Sets or updates the welcome mail parts for a specific user
 *
 * @see set_email_tpl_data()
 * @param  int $userId User unique identifier - Template owner
 * @param  array $data An associative array where each key correspond to a specific email parts: subject, message
 * @return void
 */
function set_welcome_email($userId, $data)
{
	set_email_tpl_data($userId, 'add-user-auto-msg', $data);
}

/**
 * Generates and returns lostpassword activation email
 *
 * @see get_email_tpl_data
 * @param int $adminId User unique identifier - Template owner
 * @return array An associative array where each key correspond to a specific email parts: sender_name,
 *               sender_name_email, subject, message
 */
function get_lostpassword_activation_email($adminId)
{
	$data = get_email_tpl_data($adminId, 'lostpw-msg-1');

	if (!$data['subject']) {
		$data['subject'] = tr('Please activate your new i-MSCP password', true);
	}

	if (!$data['message']) {
		$data['message'] = tr('

Dear {NAME},

Use this link to activate your new i-MSCP password:

{LINK}

Thank you for using i-MSCP services.

___________________________
The i-MSCP Team

', true);
	}

	return $data;
}

/**
 * Sets or updates lostpassword activation email parts.
 *
 * @see set_email_tpl_data()
 * @param int $adminId User unique identifier
 * @param array $data An associative array where each key correspond to a specific email parts: subject, message
 * @return void
 */
function set_lostpassword_activation_email($adminId, $data)
{
	set_email_tpl_data($adminId, 'lostpw-msg-1', $data);
}

/**
 * Generate and returns lostpassword email parts.
 *
 * @see get_email_tpl_data()
 * @param int $userId User uniqaue identifier - Template owner
 * @return array An associative array where each key correspond to a specific email parts sender_name, sender_name_email,
 *               subject, message
 */
function get_lostpassword_password_email($userId)
{
	$data = get_email_tpl_data($userId, 'lostpw-msg-2');

	if (!$data['subject']) {
		$data['subject'] = tr('Your new i-MSCP login', true);
	}

	if (!$data['message']) {
		$data['message'] = tr('

Hello {NAME},

Your user name is: {USERNAME}
Your password is: {PASSWORD}

You can login at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}

Thank you for using i-MSCP services.

___________________________
The i-MSCP Team

', true);
	}

	return $data;
}

/**
 * Sets or updates lostpassword email parts.
 *
 * @see set_email_tpl_data()
 * @param int $userId User unique identifier - Template owner
 * @param array $data An associative array where each key correspond to a specific email parts: subject, message
 * @return void
 */
function set_lostpassword_password_email($userId, $data)
{
	set_email_tpl_data($userId, 'lostpw-msg-2', $data);
}

/**
 * Generates and returns order email parts.
 *
 * @see get_email_tpl_data()
 * @param int $userId User unique identifier - template owner
 * @return array An associative array where each key correspond to a specific email part: sender_name, sender_name_email,
 *               subject, message
 */
function get_order_email($userId)
{
	$data = get_email_tpl_data($userId, 'after-order-msg');

	if (!$data['subject']) {
		$data['subject'] = tr('Confirmation for domain order {DOMAIN}', true);
	}

	if (!$data['message']) {
		$data['message'] = tr("

Dear {NAME},

This is an automatic confirmation for the order of the domain:

{DOMAIN}

You have to click the following link to confirm your order.

{ACTIVATION_LINK}

If you do not confirm it before the {EXPIRE_DATE}, it will be
automatically canceled.

Thank you for using i-MSCP services.

___________________________
The i-MSCP Team

", true);
	}

	return $data;
}

/**
 * Generates and returns alias order email.
 *
 * @see get_email_tpl_data()
 * @param int $userId User unique identifier - Template owner
 * @return Array An associative array where each key correspond to a specific email parts: sender_name,
 *               sender_name_email, subject, message
 */
function get_alias_order_email($userId)
{
	$data = get_email_tpl_data($userId, 'alias-order-msg');

	if (!$data['subject']) {
		$data['subject'] = tr('New alias order for {CUSTOMER}', true);
	}

	if (!$data['message']) {
		$data['message'] = tr('

Dear {RESELLER},

Your customer {CUSTOMER} is awaiting for the approval of his new domain alias:

{ALIAS}

Once logged in, you can activate his new alias at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/reseller/alias.php

Thank you for using i-MSCP services.

___________________________
The i-MSCP Team

', true);
	}

	return $data;
}
