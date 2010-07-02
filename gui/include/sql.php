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


$sql = ispCP_Registry::get('Db');

/**
 * @throws ispCP_Exception
 * @param  $sql
 * @param  $query
 * @return 
 */
function execute_query(&$sql, $query) {

	$rs = $sql->execute($query);
	
	if (!$rs)
		throw new ispCP_Exception($sql->errorMsg());

	return $rs;
}

/**
 * @throws ispCP_Exception
 * @param  $sql
 * @param  $query
 * @param mixed $data
 * @param bool $failDie
 * @return
 * @todo Please describe this function!
 */
function exec_query(&$sql, $query, $data = array(), $failDie = true) {
	$query = $sql->prepare($query);
	$rs = $sql->execute($query, $data);

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

		throw new ispCP_Exception(isset($msg[2]) ? $msg[2] : $msg);
	}

	return $rs;
}

/**
 * Function quoteIdentifier
 * @todo document this function
 */
function quoteIdentifier($identifier) {
	$sql = ispCP_Registry::get('Db');

	$identifier = str_replace($sql->nameQuote, '\\' . $sql->nameQuote, $identifier);

	return $sql->nameQuote . $identifier . $sql->nameQuote;
}
