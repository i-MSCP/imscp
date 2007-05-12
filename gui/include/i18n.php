<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

/**
 * 	Function:		tr
 * 	Description:	translates a given string into the selected language, if exists
 *
 * 	@access			public
 * 	@version		2.1
 *  @author			ISPCP Team, Benedikt Heintel (2007)
 *
 * 	@param		$msgid		string to translate
 * 	@param		$js			whether the input string is in javascript or not
 * 	@return					translated or original string
 **/
function tr($msgid, $js = false) {
	global $sql, $default_lang;

	$default_lang = (session_id() && isset($_SESSION['user_def_lang'])) ? $_SESSION['user_def_lang'] : $cfg['USER_INITIAL_LANG'];

	if (!$sql) {
		return ($js ? $msgid : replace_html(htmlentities($msgid, ENT_COMPAT, "UTF-8")));
	}
	else {
		$table 		= $default_lang;
		$encoding 	= $sql->Execute("SELECT `msqstr` FROM `$table` WHERE `msgid` = 'encoding';");
		$res		= $sql->Execute("SELECT `msgstr` FROM `$table` WHERE `msgid` = '$msgid';");

		if (!$encoding) {
			$encoding = "UTF-8";
		}

		if (!$res) {
			return ($js ? $msgid : replace_html(htmlentities($msgid, ENT_COMPAT, $encoding)));
		}
		elseif ($res->RowCount() == 0) {
			return ($js ? $msgid : replace_html(htmlentities($msgid, ENT_COMPAT, $encoding)));
		}
		else {
			$data = $res->FetchRow();
			if ($data['msgstr'] == '') {
				return ($js ? $msgid : replace_html(htmlentities($msgid, ENT_COMPAT, $encoding)));
			}
			else {
				return ($js ? $data['msgstr'] : replace_html(htmlentities($data['msgstr'], ENT_COMPAT, $encoding)));
			}
		}
	}
}

/**
 * 	Function:		replace_html
 * 	Description:	replaces special encoded strings back to their original signs
 *
 * 	@access			public
 * 	@version		1.0
 *  @author			ISPCP Team, Benedikt Heintel (2007)
 *
 * 	@param		$string		string to replace chars
 * 	@return					string with replaced chars
 **/
function replace_html($string) {

	$pattern = array (
						"=&lt;b&gt;=is",
						"=&lt;/b&gt;=is",
						"=&lt;i&gt;=is",
						"=&lt;/i&gt;=is",
						"=&lt;br&gt;=is"
					 );

	$replacement = array (
							"<b>",
							"</b>",
							"<i>",
							"</i>",
							"<br />"
						 );

	$string = preg_replace($pattern, $replacement, $string);

	return $string;
}

?>