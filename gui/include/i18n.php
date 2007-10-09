<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
 * @link 		http://isp-control.net
 * @author 		ispCP Team (2007)
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

/**
 * 	Function:		tr
 * 	Description:	translates a given string into the selected language, if exists
 *
 * 	@access			public
 * 	@version		2.2
 *  @author			ispCP Team, Benedikt Heintel (2007), 2007 Raphael Geissert
 *
 * 	@param		$msgid		string to translate
 * 	@param		$js			whether the input string is in javascript or not
 * 	@return					translated or original string
 **/
function tr($msgid, $as_is = false) {
    global $sql, $cfg;
    static $cache = array();

    if (!is_bool($as_is)) {
        $as_is = false;
    }

    $lang = curlang();
    $encoding = 'UTF-8';

    if (isset($cache[$lang][$msgid])) {
        $msgstr = $cache[$lang][$msgid];
    } else {
        $msgstr = $msgid;

        if ($sql) {
            if (!$as_is) {
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
    if ($msgid == $msgstr && substr($msgid, 0, 3) == '_: ' && count($l = explode("\n", $msgid)) > 1) {
        unset($l[0]);
        $msgstr = implode("\n", $l);
    }

    $cache[$lang][$msgid] = $msgstr;

    // Replace values
    if (func_num_args() > 1) {
        $argv = func_get_args();
        unset($argv[0]); //msgid

        if (is_bool($argv[1])) {
            unset($argv[1]); //as_is
        }
        $msgstr = vsprintf($msgstr, $argv);
    }

    if (!$as_is) {
        $msgstr = replace_html(htmlentities($msgstr, ENT_COMPAT, $encoding));
    }

    return $msgstr;
}

/**
 * 	Function:		replace_html
 * 	Description:	replaces special encoded strings back to their original signs
 *
 * 	@access			public
 * 	@version		1.0
 *  @author			ispCP Team, Benedikt Heintel (2007)
 *
 * 	@param		$string		string to replace chars
 * 	@return					string with replaced chars
 **/
function replace_html($string) {
    $pattern = array (
                        '#&lt;[ ]*b[ ]*&gt;#i',
                        '#&lt;[ ]*/[ ]*b[ ]*&gt;#i',
                        '#&lt;[ ]*em[ ]*&gt;#i',
                        '#&lt;[ ]*/[ ]*em[ ]*&gt;#i',
                        '#&lt;[ ]*i[ ]*&gt;#i',
                        '#&lt;[ ]*/[ ]*i[ ]*&gt;#i',
                        '#&lt;[ ]*small[ ]*&gt;#i',
                        '#&lt;[ ]*/[ ]*small[ ]*&gt;#i',
                        '#&lt;[ ]*br[ ]*(/|)[ ]*&gt;#i'
                     );

    $replacement = array (
                            '<b>',
                            '</b>',
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

// Dirty hack to make gettext add this entry to the .pot file
if (false) {
    tr('_: Localised language');
}

?>
