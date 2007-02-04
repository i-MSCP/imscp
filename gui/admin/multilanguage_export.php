<?php
/**
 *  VHCS ω (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		VHCS Team, Benedikt Heintel (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 *
 **/

include '../include/vhcs-lib.php';

// Security
check_login();

if (isset($_GET['export_lang']) && $_GET['export_lang'] !== ''){
  $language_table = $_GET['export_lang'];
  $query = <<<SQL_QUERY
			SELECT
				msgid,
				msgstr
			FROM
				$language_table
SQL_QUERY;

	$rs = exec_query($sql, $query, array());

	if ($rs->RecordCount() == 0) {
		set_page_message( tr("Incorrect data input!"));
		header( "Location: multilanguage.php" );
		die();
	}
	else {
		while (!$rs -> EOF) {
			$msgid = $rs->fields['msgid'];
			$msgstr = $rs->fields['msgstr'];
			if ($msgid !== '' && $msgstr !== '') {
				echo $msgid." = ".$msgstr."\n";
			}
			$rs -> MoveNext();
		}
	}
} else {
	set_page_message(tr("Incorrect data input!"));
	header( "Location: multilanguage.php" );
	die();
}

?>