<?php

/**
 * functions/decode/us_ascii.php
 *
 * This file contains us-ascii decoding function that is needed to read
 * us-ascii encoded mails in non-us-ascii locale.
 *
 * Function replaces all 8bit symbols with '?' marks
 *
 * @copyright 2004-2011 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: us_ascii.php 14084 2011-01-06 02:44:03Z pdontthink $
 * @package squirrelmail
 * @subpackage decode
 * 
 * @author ispCP Team May 2010 based on a patch of Benny Baumann
 */

/**
 * us-ascii decoding function.
 *
 * @param string $string string that has to be cleaned
 * @return string cleaned string
 */
function charset_decode_us_ascii ($string) {
    // don't do decoding when there are no 8bit symbols
    if (! sq_is8bit($string,'us-ascii'))
        return $string;

    $string = preg_replace("/([\201-\377])/","?",$string);

    return $string;
}
