<?php
/**
 * getstart.php
 * -----------
 * Page displayed when the "Getting Started" link is clicked.
 *
 * This is the Getting Started Page, which should hold a user's hand
 * through setting up the plugin.
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Brian Peterson
 *
 * @todo modify getstart.php to use SM_PATH
 * @todo modify getstart.php to use new module interface
 *
 * $Id: getstart.php,v 1.14 2005/07/27 14:07:49 brian Exp $
 */
/*********************************************************************/

//include the gpg system header, so's everything will be in place.
//Have to chdir so included includes will work.
//chdir("../");

if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_config.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');
require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');


// ===============================================================
$section_title = _("Encryption Options - Getting Started");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================


echo
      '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
    . '<tr><td>'

    . _("Welcome to the GPG Encryption Plugin for Squirrelmail! ")
    . '<p>'
    . _("This screen will help you set up the various options you need to get started using encryption. ")
    . '<p>'
    . _("With this interface, you will be able to encrypt, decrypt, and sign your email and attachments from the comfort of the Squirrelmail web mail interface.")

    . '<p />'

    . '<b>'
    . _("The following is a list of suggested steps you can take to get started:")
    . '</b>'

    . '<ol>';

echo
      '<p><li>'
    . '<b>'
    . _("Import external keys")
    . '</b><br>'
    . _("Do you already have a keyring, or individual keys you would like to use?")
    . '<br>'
    . _("Import keys to your personal keyring via: ")
    . '<a href="import_key_file.php">'
    . _("file")
    . ' </a>'
    . _("or" )
    . ' <a href="import_key_text.php">'
    . _("text")
    . '</a>'
    . '<br>'
    . _("If not, skip this step.");

echo
      '<p><li>'
    . '<b>'
    . _("Your personal Keypair")
    . '</b>'
    . '<br>'
    . _("Did you import a personal Keypair in Step 1?")
    .  '&nbsp;'
    . _("If not, you should")
    . ' <a href="genkey.php">'
    . _("generate a personal Keypair")
    . '</a>.';

echo
      '<p><li>'
    . '<b>'
    . _("Configure a Trusted Key")
    . '</b>'
    . '<br>'
    . _("Any key signed with your trusted key ID will be allowed for encryption without generating errors.")
    . '&nbsp;'
    . _("You can only have one trusted key at any given time.")
    . '<br>'
    . _("To set your trusted key, find the desired key in the 'public' or 'system' ring of the")
    . ' <a href="keyring_main.php">'
    . _("keyring")
    . '</a>'
    . ', '
    . _("click on it to view the details,")
    . '&nbsp;'
    . _("check 'This is my trusted key', and save.");

echo
      '<p><li>'
    . '<b>'
    . _("Select a Signing Key")
    . '</b>'
    . '<br>'
    . _("Your signing key is the key you use to 'sign' a message you are sending, to prove that is from you.")
    . '&nbsp;'
    . _("You can only have one signing key at any given time.")
    . '<br>'
    . _("You must select a signing key to use the GPG Plugin to securely sign messages.")
    . '<br>'
    . _("To set your signing key, find the desired key in the 'secret' ring of the")
    . ' <a href="keyring_main.php">'
    . _("keyring")
    . '</a>'
    . ', '
    . _("click on it to view the details,")
    . '&nbsp;'
    . _("check 'This is my signing key' and save.")
    . '&nbsp;'
    . _("If you generated a personal Keypair in step 2, this is probably the key you want.")
    . '<br>';

echo
      '<p><li>'
    . '<b>'
    . _("Find the public keys of people you want to communicate securely with")
    . '</b>'
    . '<br>'
    . _("You can")
    . ' <a href="../gpg_options.php?MOD=keyserver">'
    . _("Look up keys on a public keyserver")
    . '</a>'
    . ', '
    . _("and import them to your keyring.");

echo '</ol>';

echo '</td></tr></table>';

require_once(SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');

/**
* For Emacs weenies:
* Local variables:
* mode: php
* End:
*/

/*******************************************************/
/*
 * $Log: getstart.php,v $
 * Revision 1.14  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.13  2004/01/14 22:23:11  brian
 * - added more explicit instructions about signing key req.
 * Bug 145
 *
 * Revision 1.12  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.11  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.10  2003/11/03 19:40:35  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.9  2003/11/03 18:35:09  brian
 * changed wording of secret key text for consistency
 *
 * Revision 1.8  2003/11/01 21:55:08  brian
 * - cleaned up echoes for readability
 * - localized remaining strings
 *
 * Revision 1.7  2003/10/30 20:37:14  ke
 * -changed single to double quotes in interationalized strings
 * Bug 35
 *
 * Revision 1.6  2003/10/30 02:17:03  walter
 * - completed localized text by sentences
 * Bug 35
 *
 * Revision 1.5  2003/10/29 00:20:17  walter
 * - localized text by sentences
 * Bug 35
 * -  updated Help structure
 * Bug 79
 *
 * Revision 1.4  2003/10/20 19:13:16  walter
 * added gpg_module_footer.php to page
 *
 * Revision 1.3  2003/10/10 02:27:26  brian
 * - localised file
 * - converted to pure php
 * - reconstituted page header and log comment blocks
 * Bug 35
 *
 *
 * ------------------------------------
 * Manually added log items below this line - brian
 *
 * Revision : 1.2 2003/9/6 14:36:53 'brian'
 * - fixed typos and page header.
 * @todo add comment header block and footer block
 * @todo internationalize all the code
 *
 * Revision : 1.1 2003/8/14 2:33:21 'vermette'
 * replacement for getstart.mod
 *
 * ------------------------------------
 * Log for getstart.mod below this line
 *
 * Revision 1.6  2003/08/14 02:40:36  vermette
 * replaced getstart.mod with getstart.php.
 * Removed unused components.
 *
 * Revision 1.5  2003/07/21 14:26:51  brian
 * - minor wording changes per J Nanninga request
 * - localization of all strings
 * Bug 35
 *
 * Revision 1.4  2003/07/08 13:39:26  brian
 * added backlink to gpg_makepage fn call
 *
 * Revision 1.3  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.
 * The previous 'back' link now only appears if requested (new arg to makePage).
 * This isn't done by any means, but at most it's
 * as broken as it was, so it's an improvement.
 *
 * Revision 1.2  2003/06/12 21:08:47  brian
 * removed extra comments in $Log
 *
 * Revision 1.1  2003/05/09 20:29:23  brian
 * Initial Revision
 * Bug 34
 */
?>
