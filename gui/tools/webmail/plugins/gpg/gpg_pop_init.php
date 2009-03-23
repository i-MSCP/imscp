<?php
/**
 * gpg_pop_init.php
 * -----------
 * GPG popup window base file
 *
 * Copyright (c) 1999-2005 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 *
 * @package gpg
 *
 * $Id$
 *
 */
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_pop_init.php, exiting abnormally";
}

require_once(SM_PATH.'include/validate.php');
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_pop_functions.php');
// require any other files needed in the pop-up module(s)

/**
 * $MOD is the name of the module to invoke.
 * If $MOD is undefined check for security
 * breaches.
 */
if(isset($_POST['MOD'])) {
    $MOD = $_POST['MOD'];
} elseif (isset($_GET['MOD'])) {
    $MOD = $_GET ['MOD'];
}

/**
 * $MOD is the name of the module to invoke.
 * If $MOD is unspecified, check for security breach attempts.
 */
if (isset($MOD)) {
    gpg_ckMOD($MOD);
}

/**
 * set the localization variables
 * Now tell gettext where the locale directory for your plugin is
 * this is in relation to the src/ directory
 */
bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
/* Switch to your plugin domain so your messages get translated */
textdomain('gpg');

global $color, $theme_css, $onload;
global $title, $scriptsrc;
echo "<html>\n"
    . "<head>\n"
    . "<title>$title</title>\n";
/**
* Check if we have a defined css theme to use.
*/
if ($theme_css != "") {
    echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">\n";
}

/**
* Link in the .js file if needed
*/
if ($scriptsrc!=''){
    echo "<script type=\"text/javascript\" src=\"$scriptsrc\"></script>\n";
    // if no_signing_passwd is set onload()
}
    $no_signing_passwd = getPref ($data_dir, $username, 'no_signing_passwd');
    if ($no_signing_passwd == 'true') {
        $onload = 'onload="gpg_pop_submit(true)"';
    } else {
        $onload = 'onload="gpg_placeFocus()"';
    };

echo "</head>\n";
/*
echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" "
    . "vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n";

echo "<table width='100%' border=0 cellpadding=2>\n"
    . '<tr>'
    . "<td bgcolor=\"$color[9]\" align=center>"
    . "<strong>$title</strong>"
    . '</td>'
    . "</tr>\n"
    . '<tr><td><hr></td></tr>'
    . '<tr>'
    . "<td>\n";
*/
/**
 * Include the module.
 */
require_once("modules/$MOD.mod");

echo
      "</td>\n"
    . "</tr>\n"
    . '<tr><td><hr></td></tr>'
    . '<tr>'
    . "<td bgcolor=\"$color[9]\" align=center>"
    . "</td>\n"
    . '</tr>'
    . '</table>'
    . "</body>\n</html>\n";

/* Switch back to the SquirrelMail domain */
bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');

/************************************************************/
/*
 * $Log: gpg_pop_init.php,v $
 * Revision 1.15  2007/07/07 14:19:04  brian
 * - include vulnerability patch provided by Stefan Esser <sesser@php-security.org>
 *
 * Revision 1.14  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.13  2004/03/29 00:55:02  brian
 * - fixed include path
 *
 * Revision 1.12  2004/03/29 00:42:46  brian
 * - add include for validate.php
 *   patch credit to ebullient at squirrelmail dot org
 *
 * Revision 1.11  2004/01/17 00:26:59  ke
 * -E_ALL fixes
 *
 * Revision 1.10  2004/01/09 18:26:50  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.9  2003/11/17 22:28:17  ke
 * -uploading working gpg_pop_init.php
 *
 * Revision 1.8  2003/11/04 21:04:03  ke
 * -moved body out of this file into passpop.mod
 *
 * Revision 1.7  2003/11/03 18:42:48  ke
 * -moved closed parenthesis to allow the onload variable to be set even though scriptsrc isn't
 * -this fixes the passphrase popup focus problem
 *
 * Revision 1.6  2003/11/03 17:33:38  ke
 * -changed check on scriptsrc to != instead of !==, so it will fail when scriptsrc==''
 *
 * Revision 1.5  2003/11/03 15:52:15  ke
 * -Removed titling code from here, added to passpop.mod
 *
 * Revision 1.4  2003/11/01 22:01:26  brian
 * infrastructure changes to support removal of MakePage functions
 *
 * Revision 1.3  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.2  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.1  2003/03/31 14:02:47  brian
 * Initial Revision
 * Created general file for use by all pop-up windows in the plugin.
 * @todo modify signing and decryption features to use the general init file.
 */
?>
