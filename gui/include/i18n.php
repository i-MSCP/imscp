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

/**
 * 	Function:		tr
 * 	Description:	translates a given string into the selected language, if exists
 *
 * 	@access			public
 * 	@version		2.0
 *  @author			VHCS Team, Benedikt Heintel (2007)
 **/
function tr($msgid) {
	global $sql, $default_lang;

	$default_lang = $_SESSION['user_def_lang'];

	if (!$sql) {
		return htmlentities($msgid, ENT_COMPAT, "UTF-8");
	}
	else {
		$table 		= $default_lang;
		$encoding 	= $sql->Execute("SELECT `msqstr` FROM `$table` WHERE `msgid` = 'encoding';");
		$res		= $sql->Execute("SELECT `msgstr` FROM `$table` WHERE `msgid` = '$msgid';");

		if (!$encoding) {
			$encoding = "UTF-8";
		}

		if (!$res) {
			return htmlentities($msgid, ENT_COMPAT, $encoding);
		}
		elseif ($res->RowCount() == 0) {
			return htmlentities($msgid, ENT_COMPAT, $encoding);
		}
		else {
			$data = $res->FetchRow();
			if ($data['msgstr'] == '') {
				return htmlentities($msgid, ENT_COMPAT, $encoding);
			}
			else {
				return htmlentities($data['msgstr'], ENT_COMPAT, $encoding);
			}
		}
	}
}

?>