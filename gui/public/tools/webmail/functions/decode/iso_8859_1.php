<?php

/**
 * decode/iso8859-1.php
 *
 * This file contains iso-8859-1 decoding function that is needed to read
 * iso-8859-1 encoded mails in non-iso-8859-1 locale.
 *
 * @copyright 2003-2011 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: iso_8859_1.php 14084 2011-01-06 02:44:03Z pdontthink $
 * @package squirrelmail
 * @subpackage decode
 * 
 * @author ispCP Team May 2010 based on a patch of Benny Baumann
 */

/**
 * Decode iso8859-1 string
 * @param string $string Encoded string
 * @return string $string Decoded string
 */
function charset_decode_iso_8859_1 ($string) {
    // don't do decoding when there are no 8bit symbols
    if (! sq_is8bit($string,'iso-8859-1'))
        return $string;

    $string = preg_replace_callback("/([\201-\377])/",'charset_decode_iso_8859_1_helper',$string);

	return $string;
}

function charset_decode_iso_8859_1_helper ($m) {
    return '&#' . ord($m[1]) . ';';
}