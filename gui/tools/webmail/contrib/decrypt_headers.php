<?php

/**
 * Script provides form to decode encrypted header information.
 *
 * @copyright &copy; 2005-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: decrypt_headers.php,v 1.1.2.5 2006/10/07 11:58:42 tokul Exp $
 * @package squirrelmail
 */

/**
 * Set constant to path of your SquirrelMail install. 
 * @ignore
 */
define('SM_PATH','../');

/**
 * include SquirrelMail string functions
 * script needs OneTimePadDecrypt() (functions/strings.php)
 * and sqgetGlobalVar() (functions/global.php)
 */
include_once(SM_PATH.'functions/global.php');
include_once(SM_PATH.'functions/strings.php');

/**
 * converts hex string to ip address
 * @param string $hex hexadecimal string created with squirrelmail ip2hex 
 *  function in delivery class.
 * @return string ip address
 * @since 1.5.1 and 1.4.5
 */
function hex2ip($hex) {
    if (strlen($hex)==8) {
        $ret=hexdec(substr($hex,0,2)).'.'
            .hexdec(substr($hex,2,2)).'.'
            .hexdec(substr($hex,4,2)).'.'
            .hexdec(substr($hex,6,2));
    } elseif (strlen($hex)==32) {
        $ret=hexdec(substr($hex,0,4)).':'
            .hexdec(substr($hex,4,4)).':'
            .hexdec(substr($hex,8,4)).':'
            .hexdec(substr($hex,12,4)).':'
            .hexdec(substr($hex,16,4)).':'
            .hexdec(substr($hex,20,4)).':'
            .hexdec(substr($hex,24,4)).':'
            .hexdec(substr($hex,28,4));
    } else {
        $ret=$hex;
    }
    return $ret;
}

/** create page headers */
header('Content-Type: text/html');

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'
    ."\n<head>\n<meta name=\"robots\" content=\"noindex,nofollow\">\n"
    ."</head><body>";

if (sqgetGlobalVar('submit',$submit,SQ_POST)) {
    if (! sqgetGlobalVar('secret',$secret,SQ_POST) ||
        empty($secret))
        echo "<p>You must enter encryption key.</p>\n";
    if (! sqgetGlobalVar('enc_string',$enc_string,SQ_POST) ||
        empty($enc_string))
        echo "<p>You must enter encrypted string.</p>\n";

    if (isset($enc_string) && ! base64_decode($enc_string)) {
        echo "<p>Encrypted string should be BASE64 encoded.<br />\n"
            ."Please enter all characters that are listed after header name.</p>\n";
    } elseif (isset($secret)) {
        $string=OneTimePadDecrypt($enc_string,base64_encode($secret));

        if (sqgetGlobalVar('ip_addr',$is_addr,SQ_POST)) {
            $string=hex2ip($string);
        }
        echo "<p>Decoded string: ".$string."</p>\n";
    }
    echo "<hr />";
}
?>
<form action="<?php echo $PHP_SELF ?>" method="post" >
<p>
Secret key: <input type="password" name="secret"><br />
Encrypted string: <input type="text" name="enc_string"><br />
Check, if it is an address string: <input type="checkbox" name="ip_addr" /><br />
<button type="submit" name="submit" value="submit">Submit</button>
</p>
</form>
</body></html>