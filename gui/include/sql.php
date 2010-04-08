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

// Get an Database instance
@$sql = Database::connect(
	Config::getInstance()->get('DATABASE_USER'),
	ecrypt_db_password(Config::getInstance()->get('DATABASE_PASSWORD')),
	Config::getInstance()->get('DATABASE_TYPE'),
	Config::getInstance()->get('DATABASE_HOST'),
	Config::getInstance()->get('DATABASE_NAME')
) or system_message('ERROR: Unable to connect to SQL server !<br />SQL returned: ' . $sql->ErrorMsg());

// switch optionally to utf8 based communication with the database
if (Config::getInstance()->exists('DATABASE_UTF8') && Config::getInstance()->get('DATABASE_UTF8') == 'yes') {
	@$sql->Execute("SET NAMES 'utf8'");
}

/**
 * @todo Please describe this function!
 */
function execute_query(&$sql, $query) {
	$rs = $sql->Execute($query);
	if (!$rs) system_message($sql->ErrorMsg());

	return $rs;
}

/**
 * @todo Please describe this function!
 */
function exec_query(&$sql, $query, $data = array(), $failDie = true) {
	$query = $sql->Prepare($query);
	$rs = $sql->Execute($query, $data);

	if (!$rs && $failDie) {
		$msg = ($query instanceof PDOStatement) ? $query->errorInfo() : $sql->errorInfo();
		$backtrace = debug_backtrace();
		$output = isset($msg[2]) ? $msg[2] : $msg;
		$output .= "\n";

		foreach ($backtrace as $entry) {
			$output .= "File: ".$entry['file']." (Line: ".$entry['line'].")";
			$output .= " Function: ".$entry['function']."\n";
		}

		// Send error output via email to admin
		$admin_email = Config::getInstance()->get('DEFAULT_ADMIN_ADDRESS');

		if (!empty($admin_email)) {
			$default_hostname = Config::getInstance()->get('SERVER_HOSTNAME');
			$default_base_server_ip = Config::getInstance()->get('BASE_SERVER_IP');
			$Version = Config::getInstance()->get('Version');
			$headers = "From: \"ispCP Logging Daemon\" <" . $admin_email . ">\n";
			$headers .= "MIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 7bit\n";
			$headers .= "X-Mailer: ispCP $Version Logging Mailer";
			$subject = "ispCP $Version on $default_hostname ($default_base_server_ip)";
			$mail_result = mail($admin_email, $subject, $output, $headers);
		}

		system_message(isset($msg[2]) ? $msg[2] : $msg);
	}

	return $rs;
}

/**
 * Function quoteIdentifier
 * @todo document this function
 */
function quoteIdentifier($identifier) {
	$sql = Database::getInstance();

	$identifier = str_replace($sql->nameQuote, '\\' . $sql->nameQuote, $identifier);

	return $sql->nameQuote . $identifier . $sql->nameQuote;
}

/**
 * Function match_sqlinjection
 * @todo document this function
 */
function match_sqlinjection($value, &$matches) {
	$matches = array();
	return (preg_match("/((DELETE)|(INSERT)|(UPDATE)|(ALTER)|(CREATE)|( TABLE)|(DROP))\s[A-Za-z0-9 ]{0,200}(\s(FROM)|(INTO)|(TABLE)\s)/i", $value, $matches) > 0);
}

/**
 * @todo remove check for PHP <= 4.2.2, this produces unmantainable code
 */
function check_query($exclude = array()) {
	$matches = null;

	if (phpversion() <= '4.2.2') {
		$message = "Your PHP version is older than 4.2.2!";
		write_log($message);
		system_message($message);
		die('ERROR: ' . $message);
	}

	if (!is_array($exclude)) {
		$exclude = array($exclude);
	}

	foreach ($_REQUEST as $key => $value) {
		if (in_array($key, $exclude)) {
			continue;
		}

		if (!is_array($value)) {
			if (match_sqlinjection($value, $matches)) {
				$message = "Possible SQL injection detected: $key=>$value. <b>${matches[0]}</b>. Script terminated.";
				write_log($message);
				system_message($message);
				die('<b>WARNING</b>: Possible SQL injection detected. Script terminated.');
			}
		} else {
			foreach ($value as $skey => $svalue) {
				if (!is_array($svalue)) {
					if (match_sqlinjection($svalue, $matches)) {
						$message = "Possible SQL injection detected: $skey=>$svalue <b>${matches[0]}</b>. Script terminated.";
						write_log($message);
						system_message($message);
						die('<b>WARNING</b>: Possible SQL injection detected. Script terminated.');
					}
				}
			}
		}
	}
}
