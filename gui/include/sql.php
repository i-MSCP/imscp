<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 2001-2006 by moleSoftware GmbH
 * @copyright	2006-2008 by ispCP | http://isp-control.net
 * @version SVN: $ID$
 * @link http://isp-control.net
 * @author ispCP Team
 * @license This program is free software; you can redistribute it and/or modify it under
 *    the terms of the MPL General Public License as published by the Free Software
 *    Foundation; either version 1.1 of the License, or (at your option) any later
 *    version.
 *    You should have received a copy of the MPL Mozilla Public License along with
 *    this program; if not, write to the Open Source Initiative (OSI)
 *    http://opensource.org | osi@opensource.org
 */

require (INCLUDEPATH . '/adodb/adodb.inc.php');
require (INCLUDEPATH . '/adodb/adodb-pager.inc.php');

$cfg['DB_TYPE'] = $cfg['DATABASE_TYPE'];
$cfg['DB_HOST'] = $cfg['DATABASE_HOST'];
$cfg['DB_USER'] = $cfg['DATABASE_USER'];
$cfg['DB_PASS'] = decrypt_db_password($cfg['DATABASE_PASSWORD']);
$cfg['DB_NAME'] = $cfg['DATABASE_NAME'];

$sql = &ADONewConnection($cfg['DB_TYPE']);

@$sql->Connect($cfg['DB_HOST'], $cfg['DB_USER'], $cfg['DB_PASS'], $cfg['DB_NAME']) or
	system_message('ERROR: Unable to connect to SQL server !<br>SQL returned: ' . $sql->ErrorMsg());

// switch optionally to utf8 based communication with the database
if (isset($cfg['DATABASE_UTF8']) && $cfg['DATABASE_UTF8'] == 'yes') {
	@$sql->Execute("SET NAMES 'utf8'");
}

// No longer needed - unset for safety
unset($cfg['DB_USER']);
unset($cfg['DB_PASS']);

function execute_query (&$sql, $query) {
	$rs = $sql->Execute($query);
	if (!$rs) system_message($sql->ErrorMsg());
	return $rs;
}

function exec_query(&$sql, $query, $data = array(), $failDie = true) {
	$query = $sql->Prepare($query);
	$rs = $sql->Execute($query, $data);

	if (!$rs && $failDie) {
		system_message($sql->ErrorMsg());
	}

	return $rs;
}

function quoteIdentifier($identifier) {
	global $sql;

	$identifier = str_replace($sql->nameQuote, '\\' . $sql->nameQuote, $identifier);

	return $sql->nameQuote . $identifier . $sql->nameQuote;
}

function match_sqlinjection($value, &$matches) {
	$matches = array();
	return (preg_match("/((DELETE)|(INSERT)|(UPDATE)|(ALTER)|(CREATE)|( TABLE)|(DROP))\s[A-Za-z0-9 ]{0,200}(\s(FROM)|(INTO)|(TABLE)\s)/i", $value, $matches) > 0);
}

function check_query($exclude = array()) {
	$matches = null;

	if (phpversion() <= '4.2.2') {
		$message = "Your PHP version is older than 4.2.2!";
		write_log($message);
		system_message($message);
		die('ERROR: Your PHP version is older than 4.2.2!');
	}

	if (!is_array($exclude)) {
		$exclude = array($exclude);
	}

	foreach($_REQUEST as $key => $value) {
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
			foreach($value as $skey => $svalue) {
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

?>