<?php

/**
 * functions/decode/us_ascii.php
 *
 * This file contains us-ascii decoding function that is needed to read
 * us-ascii encoded mails in non-us-ascii locale.
 *
 * Function replaces all 8bit symbols with '?' marks
 *
 * @copyright &copy; 2004-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: us_ascii.php,v 1.2.2.6 2006/02/03 22:27:47 jervfors Exp $
 * @package squirrelmail
 * @subpackage decode
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

    $string = preg_replace("/([\201-\237])/e","'?'",$string);

    /* I don't want to use 0xA0 (\240) in any ranges. RH73 may dislike it */
    $string = str_replace("\240", '?', $string);

    $string = preg_replace("/([\241-\377])/e","'?'",$string);
    return $string;
}
?>