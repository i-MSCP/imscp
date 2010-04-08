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

/**
 * false: don't set (not even auto),
 * null: set if missing,
 * true: force update from session/default, anything else: set it as a language
 */
function curlang($newlang = null, $force = false) {
	static $language = null;

	// we store old value so if $language is changed old value is returned
	$_language = $language;

	// forcibly set $language to $newlang (use with CARE!)
	if ($force) {
		$language = $newlang;
		return $_language;
	}

	if ($language === null || ($newlang !== null && $newlang !== false)) {

		if ($newlang === true
			|| (($newlang === null || $newlang === false) && $language === null)
			) {
			$newlang = (isset($_SESSION['user_def_lang']))
				? $_SESSION['user_def_lang']
				: Config::getInstance()->get('USER_INITIAL_LANG');
		}

		if ($newlang !== false) {
			$language = $newlang;
		}
	}

	return ($_language !== null) ? $_language : $language;
}

/**
 * translates a given string into the selected language, if exists
 *
 * @access		public
 * @version		2.2
 * @author		ispCP Team, Benedikt Heintel (2007), Raphael Geissert (2007)
 *
 * @param		String	$msgid			string to translate
 * @param		Mixed	$substitution	prevent the returned string from being replaced with html entities
 * @return		String					translated or original string
 * @todo use db prepared statements
 */
function tr($msgid, $substitution = false) {
	$sql = Database::getInstance();
	static $cache = array();

	// detect whether $substitution is really $substitution or just a value to be replaced in $msgstr
	if (!is_bool($substitution)) {
		$substitution = false;
	}

	$lang = curlang();
	$encoding = 'UTF-8';

	if (isset($cache[$lang][$msgid])) {
		$msgstr = $cache[$lang][$msgid];
	} else {
		$msgstr = $msgid;

		if ($sql) {
			if (!$substitution) {
				// $substitution is true in this call because we need it that way and to prevent an infinite loop
				$encoding = tr('encoding', true);
			}
			$rs = exec_query($sql, "SELECT `msgstr` FROM " . quoteIdentifier($lang) . " WHERE `msgid` = ?;", array($msgid), false);

			if ($rs && $rs->RowCount() > 0 && $rs->fields['msgstr'] != '') {
				$msgstr = $rs->fields['msgstr'];
			}
		}
	}

	if ($msgid == 'encoding' && $msgstr == 'encoding') {
		$msgstr = $encoding;
	}

	// Detect comments and strip them if $msgid == $msgstr
	// e.g.
	// tr('_: This is just a comment\nReal message to translate here')
	if ($msgid == $msgstr
		&& substr($msgid, 0, 3) == '_: '
		&& count($l = explode("\n", $msgid)) > 1) {
		unset($l[0]);
		$msgstr = implode("\n", $l);
	}

	$cache[$lang][$msgid] = $msgstr;

	// Replace values
	if (func_num_args() > 1) {
		$argv = func_get_args();
		unset($argv[0]); //msgid

		if (is_bool($argv[1])) {
			unset($argv[1]);
		}
		$msgstr = vsprintf($msgstr, $argv);
	}

	if (!$substitution) {
		$msgstr = replace_html(htmlentities($msgstr, ENT_COMPAT, $encoding));
	}

	return $msgstr;
}

/**
 * replaces special encoded strings back to their original signs
 *
 * @access		public
 * @version		1.0
 * @author		ispCP Team, Benedikt Heintel (2007)
 *
 * @param		string	$string	string to replace chars
 * @return		string	string with replaced chars
 */
function replace_html($string) {
	$pattern = array(
		'#&lt;[ ]*b[ ]*&gt;#i',
		'#&lt;[ ]*/[ ]*b[ ]*&gt;#i',
		'#&lt;[ ]*strong[ ]*&gt;#i',
		'#&lt;[ ]*/[ ]*strong[ ]*&gt;#i',
		'#&lt;[ ]*em[ ]*&gt;#i',
		'#&lt;[ ]*/[ ]*em[ ]*&gt;#i',
		'#&lt;[ ]*i[ ]*&gt;#i',
		'#&lt;[ ]*/[ ]*i[ ]*&gt;#i',
		'#&lt;[ ]*small[ ]*&gt;#i',
		'#&lt;[ ]*/[ ]*small[ ]*&gt;#i',
		'#&lt;[ ]*br[ ]*(/|)[ ]*&gt;#i'
	);

	$replacement = array(
		'<b>',
		'</b>',
		'<strong>',
		'</strong>',
		'<em>',
		'</em>',
		'<i>',
		'</i>',
		'<small>',
		'</small>',
		'<br />'
	);

	$string = preg_replace($pattern, $replacement, $string);

	return $string;
}

/**
 * @todo check dirty hack
 */
// Dirty hack to make gettext add this entry to the .pot file
if (false) {
	tr('_: Localised language');
}
