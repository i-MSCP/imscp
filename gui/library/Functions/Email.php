<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_Registry as Registry;

/**
 * Gets data for the given email template and the given user
 *
 * @throws iMSCPException
 * @param int $userId User unique identifier
 * @param string $tplName Template name
 * @return array An associative array containing mail data:
 *                - sender_name:  Sender name
 *                - sender_email: Sender email
 *                - subject:      Subject
 *                - message:      Message
 */
function get_email_tpl_data($userId, $tplName)
{
    $stmt = exec_query(
        "
            SELECT admin_name, fname, lname, email, IFNULL(subject, '') AS subject, IFNULL(message, '') AS message
            FROM admin
            LEFT JOIN email_tpls ON(owner_id = IF(admin_type = 'admin', 0, admin_id) AND name = ?)
            WHERE admin_id = ?
        ", [$tplName, $userId]
    );

    if (!$stmt->rowCount()) {
        throw new iMSCPException("Couldn't find user data");
    }
    $row = $stmt->fetchRow();

    if ($row['fname'] != '' && $row['lname'] != '') {
        $data['sender_name'] = $row['fname'] . ' ' . $row['lname'];
    } else if ($row['fname'] != '') {
        $data['sender_name'] = $row['fname'];
    } else if ($row['lname'] != '') {
        $data['sender_name'] = $row['lname'];
    } else {
        $data['sender_name'] = $row['admin_name'];
    }

    $data['sender_email'] = $row['email'];
    $data['subject'] = $row['subject'];
    $data['message'] = $row['message'];
    return $data;
}

/**
 * Sets data for the given email template and the given user using the given
 * data
 *
 * @param int $userId User unique identifier
 * @param string $tplName Template name
 * @param array $data An associative array containing mail data:
 *                     - subject: Subject
 *                     - message: Message
 * @return void
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function set_email_tpl_data($userId, $tplName, $data)
{
    $stmt = exec_query('SELECT subject, message FROM email_tpls WHERE owner_id = ? AND name = ?', [$userId, $tplName]);

    if ($stmt->rowCount()) {
        $query = 'UPDATE email_tpls SET subject = ?, message = ? WHERE owner_id = ? AND name = ?';
    } else {
        $query = 'INSERT INTO email_tpls (subject, message, owner_id, name) VALUES (?, ?, ?, ?)';
    }

    exec_query($query, [$data['subject'], $data['message'], $userId, $tplName]);
}

/**
 * Gets welcome email data for the given user
 *
 * @see get_email_tpl_data()
 * @param int $userId User unique identifier - Template owner
 * @return array An associative array containing mail data:
 *                - sender_name:  Sender name
 *                - sender_email: Sender email
 *                - subject:      Subject
 *                - message:      Message
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function get_welcome_email($userId)
{
    $data = get_email_tpl_data($userId, 'add-user-auto-msg');

    if ($data['subject'] == '') {
        $data['subject'] = tr('Welcome {USERNAME} to i-MSCP');
    }
    if ($data['message'] == '') {
        $data['message'] = tr('Dear {NAME},

A new account has been created for you.

Your account information:

Account type: {USERTYPE}
User name: {USERNAME}
Password: {PASSWORD}

Remember to change your password often and the first time you login.

You can login at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}{BASE_SERVER_VHOST_PORT}

Please do not reply to this email.

___________________________
i-MSCP Mailer');
    }

    return $data;
}

/**
 * Sets welcome email data for the given user using the given data
 *
 * @see set_email_tpl_data()
 * @param  int $userId Template owner unique identifier (0 for administrators)
 * @param array $data An associative array containing mail data:
 *                     - subject: Subject
 *                     - message: Message
 * @return void
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function set_welcome_email($userId, $data)
{
    set_email_tpl_data($userId, 'add-user-auto-msg', $data);
}

/**
 * Gets lostpassword activation email data for the given user
 *
 * @see get_email_tpl_data()
 * @param int $userId User unique identifier - Template owner
 * @return array An associative array containing mail data:
 *                - sender_name:  Sender name
 *                - sender_email: Sender email
 *                - subject:      Subject
 *                - message:      Message
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function get_lostpassword_activation_email($userId)
{
    $data = get_email_tpl_data($userId, 'lostpw-msg-1');

    if ($data['subject'] == '') {
        $data['subject'] = tr('Please activate your new i-MSCP password');
    }

    if ($data['message'] == '') {
        $data['message'] = tr('Dear {NAME},

Please click on the link below to renew your password:

{LINK}

Note: If you do not have requested the renewal of your password, you can ignore this email.

Please do not reply to this email.

___________________________
i-MSCP Mailer');
    }

    return $data;
}

/**
 * Sets lostpassword activation email template data for the given user, using
 * given data
 *
 * @see set_email_tpl_data()
 * @param int $adminId User unique identifier
 * @param array $data An associative array containing mail data:
 *                     - subject: Subject
 *                     - message: Message
 * @return void
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function set_lostpassword_activation_email($adminId, $data)
{
    set_email_tpl_data($adminId, 'lostpw-msg-1', $data);
}

/**
 * Get lostpassword password email for the given user
 *
 * @see get_email_tpl_data()
 * @param int $userId User uniqaue identifier - Template owner
 * @return array An associative array containing mail data:
 *                - sender_name:  Sender name
 *                - sender_email: Sender email
 *                - subject:      Subject
 *                - message:      Message
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function get_lostpassword_password_email($userId)
{
    $data = get_email_tpl_data($userId, 'lostpw-msg-2');

    if ($data['subject'] == '') {
        $data['subject'] = tr('Your new i-MSCP login');
    }

    if ($data['message'] == '') {
        $data['message'] = tr('Dear {NAME},

Your password has been successfully renewed.

Your new password is: {PASSWORD}

You can login at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}{BASE_SERVER_VHOST_PORT}

Please do not reply to this email.

___________________________
i-MSCP Mailer');
    }

    return $data;
}

/**
 * Sets lostpassword password email template data for the given user, using
 * given data
 *
 * @see set_email_tpl_data()
 * @param int $userId User unique identifier - Template owner
 * @param array $data An associative array containing mail data:
 *                     - subject: Subject
 *                     - message: Message
 * @return void
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function set_lostpassword_password_email($userId, $data)
{
    set_email_tpl_data($userId, 'lostpw-msg-2', $data);
}

/**
 * Get alias order email for the given reseller
 *
 * @see get_email_tpl_data()
 * @param int $resellerId Reseller User unique identifier
 * @return array An associative array containing mail data:
 *                - sender_name:  Sender name
 *                - sender_email: Sender email
 *                - subject:      Subject
 *                - message:      Message
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function get_alias_order_email($resellerId)
{
    $data = get_email_tpl_data($resellerId, 'alias-order-msg');

    if ($data['subject'] == '') {
        $data['subject'] = tr('New alias order for {CUSTOMER}');
    }

    if ($data['message'] == '') {
        $data['message'] = tr('Dear {NAME},

Your customer {CUSTOMER} is awaiting for approval of a new domain alias:

{ALIAS}

Login at {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}{BASE_SERVER_VHOST_PORT}/reseller/alias.php to activate
this domain alias.

Please do not reply to this email.

___________________________
i-MSCP Mailer');
    }

    return $data;
}

/**
 * Encode a string to be valid as mail header
 *
 * @source php.net/manual/en/function.mail.php
 *
 * @param string $string String to be encoded [should be in the $charset charset]
 * @param string $charset OPTIONAL charset in that string will be encoded
 * @return string encoded string
 */
function encode_mime_header($string, $charset = 'UTF-8')
{
    if (!$string || !$charset) {
        return $string;
    }

    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($string, $charset, 'Q', "\r\n", 8);
    }

    // define start delimiter, end delimiter and spacer
    $end = '?=';
    $start = '=?' . $charset . '?B?';
    $spacer = $end . "\r\n " . $start;

    // Determine length of encoded text withing chunks and ensure length is even
    $length = 75 - strlen($start) - strlen($end);
    $length = floor($length / 4) * 4;

    // Encode the string and split it into chunks with spacers after each chunk
    $string = base64_encode($string);
    $string = chunk_split($string, $length, $spacer);

    // Remove trailing spacer and add start and end delimiters
    $spacer = preg_quote($spacer);
    $string = preg_replace('/' . $spacer . '$/', '', $string);

    return $start . $string . $end;
}

/**
 * Send a mail using given data
 *
 * @param array $data An associative array containing mail data:
 *  - mail_id      : Email identifier
 *  - fname        : OPTIONAL Receiver firstname
 *  - lname        : OPTIONAL Receiver lastname
 *  - username     : Receiver username
 *  - email        : Receiver email
 *  - sender_name  : OPTIONAL sender name (if present, passed through `Reply-To' header)
 *  - sender_email : OPTIONAL Sender email (if present, passed through `Reply-To' header)
 *  - subject      : Subject of the email to be sent
 *  - message      : Message to be sent
 *  - placeholders : OPTIONAL An array where keys are placeholders to replace
 *                   and values, the replacement values. Those placeholders
 *                   take precedence on the default placeholders.
 * @return bool TRUE on success, FALSE on failure
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function send_mail($data)
{
    $data = new ArrayObject($data);
    $response = EventsManager::getInstance()->dispatch(Events::onSendMail, ['mail_data' => new ArrayObject($data)]);

    if ($response->isStopped()) { // Allow third-party components to short-circuit this event.
        return true;
    }

    foreach (['mail_id', 'username', 'email', 'subject', 'message'] as $parameter) {
        if (!isset($data[$parameter]) || !is_string($data[$parameter])) {
            throw new  iMSCPException(sprintf("`%s' parameter is not defined or not a string", $parameter));
        }
    }

    if (isset($data['placeholders']) && !is_array($data['placeholders'])) {
        throw new  iMSCPException("`placeholders' parameter must be an array of placeholders/replacements");
    }

    $username = decode_idna($data['username']);

    if (isset($data['fname']) && $data['fname'] != '' && isset($data['lname']) && $data['lname'] != '') {
        $name = $data['fname'] . ' ' . $data['lname'];
    } else if (isset($data['fname']) && $data['fname'] != '') {
        $name = $data['fname'];
    } else if (isset($data['lname']) && $data['lname'] != '') {
        $name = $data['lname'];
    } else {
        $name = $username;
    }

    $cfg = Registry::get('config');
    $scheme = $cfg['BASE_SERVER_VHOST_PREFIX'];
    $host = $cfg['BASE_SERVER_VHOST'];
    $port = $scheme == 'http://' ? $cfg['BASE_SERVER_VHOST_HTTP_PORT'] : $cfg['BASE_SERVER_VHOST_HTTPS_PORT'];

    # Prepare default replacements
    $replacements = [
        '{NAME}'                     => $name,
        '{USERNAME}'                 => $username,
        '{BASE_SERVER_VHOST_PREFIX}' => $scheme,
        '{BASE_SERVER_VHOST}'        => $host,
        '{BASE_SERVER_VHOST_PORT}'   => ":$port"
    ];

    if (isset($data['placeholders'])) {
        # Merge user defined replacements if any (those replacements take precedence on default replacements)
        $replacements = array_merge($replacements, $data['placeholders']);
    }

    $replacements["\n"] = "\r\n";
    $search = array_keys($replacements);
    $replace = array_values($replacements);
    unset($replacements);

    # Process replacements in placeholder replacement values
    foreach ($replace as &$value) {
        $value = str_replace($search, $replace, $value);
    }

    # Prepare sender
    $from = "noreply@$host";

    # Prepare recipient
    $to = encode_mime_header($name) . ' <' . $data['email'] . '>';

    # Prepare subject
    $subject = encode_mime_header(str_replace($search, $replace, $data['subject']));

    # Prepare message
    $message = wordwrap(str_replace($search, $replace, $data['message']), 75, "\r\n");

    # Prepare headers
    $headers[] = 'From: ' . encode_mime_header("i-MSCP ($host)") . " <$from>";
    if (isset($data['sender_email'])) {
        # Note: We cannot use the real sender email address in the FROM header because the email's domain could be
        # hosted on external server, meaning that if the domain implements SPF, the mail could be rejected. However we
        # pass the real sender email through the `Reply-To' header
        if (isset($data['sender_name'])) {
            $headers[] = 'Reply-To: ' . encode_mime_header($data['sender_name']) . ' <' . $data['sender_email'] . '>';
        } else {
            $headers[] = 'Reply-To: ' . $data['sender_email'];
        }
    } else {
        $headers[] = 'Reply-To: ' . $cfg['DEFAULT_ADMIN_ADDRESS'];
    }

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=utf-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    $headers[] = 'X-Mailer: i-MSCP Mailer (frontEnd)';

    return mail($to, $subject, $message, implode("\r\n", $headers), "-f $from");
}
