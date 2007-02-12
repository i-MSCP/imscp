<?php
/**
 * setup.php
 * -----------
 * GPG plugin setup file, as defined by the SquirrelMail-1.2 API.
 * Updated to account for SM 1.4 pathing issues
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Copyright (c) 2002-2003 Braverock Ventures
 *
 * $Id: setup.php,v 1.67 2003/12/30 19:06:01 ke Exp $
 *
 */
if (!defined (SM_PATH)){
    if (file_exists('./gpg_functions.php')){
        define (SM_PATH , '../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')) {
        define (SM_PATH, '../');
    } elseif (file_exists('../gpg_functions.php')){
        define (SM_PATH , '../../../');
    } elseif (file_exists('../../plugins/gpg/gpg_functions.php')){
        define (SM_PATH , '../../../../');
    } else echo "unable to define SM_PATH in GPG Plugin setup.php, exiting abnormally";
}

include_once(SM_PATH.'plugins/gpg/gpg_pref_functions.php');
//include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');

$GLOBALS['GPG_SYSTEM_OPTIONS'][$matches[1]]= "";

/*********************************************************************/
/**
 * function squirrelmail_plugin_init_gpg
 *
 * Standard squirrelmail plugin initialization API.
 *
 * Called by the SM core on page load to see if there are
 * hooks that need to be registered and displayed.
 *
 * Format for registering a hook:
 *
 * $squirrelmail_plugin_hooks['sm_hook_name']['gpg'] = 'function_to_call';
 *
 * All function_to_call should be within the setup.php file
 * this file is called frequently by SM, so make it as tight
 * as possible.
 *
 * @return void
 */
function squirrelmail_plugin_init_gpg() {
  global $squirrelmail_plugin_hooks;
  $squirrelmail_plugin_hooks['compose_form']['gpg'] =
     'gpg_compose_form';
  $squirrelmail_plugin_hooks['compose_button_row']['gpg'] =
     'gpg_compose_row';
  $squirrelmail_plugin_hooks['compose_bottom']['gpg'] =
     'gpg_compose_bottom';

  $squirrelmail_plugin_hooks['optpage_register_block']['gpg'] =
     'gpg_optpage_register_block';

  $squirrelmail_plugin_hooks['options_link_and_description']['gpg'] =
     'gpg_options';

  $squirrelmail_plugin_hooks['read_body_header']['gpg'] =
     'gpg_read_body_header';

  $squirrelmail_plugin_hooks['compose_send']['gpg'] =
      'gpg_compose_send';

  $squirrelmail_plugin_hooks['help_chapter']['gpg'] =
      'gpg_help_chapter';

  $squirrelmail_plugin_hooks['attachment application/pgp-encrypted']['gpg'] =
      'gpg_decrypt_attachment';

  $squirrelmail_plugin_hooks['attachment application/pgp-signature']['gpg'] =
      'gpg_handle_signature';

  $squirrelmail_plugin_hooks['attachment application/octet-stream']['gpg'] =
      'gpg_handle_octet_stream';

  $squirrelmail_plugin_hooks['attachment text/plain']['gpg'] =
      'gpg_handle_octet_stream';

  $squirrelmail_plugin_hooks['attachment application/pgp']['gpg'] =
      'gpg_handle_octet_stream';

}

function gpg_help_chapter() {
    global $helpdir, $help_info;
    echo "<li><a href='" . SM_PATH . "plugins/gpg/gpg_help_base.php'>"._("GPG Plugin Help")."</a>\n";
    echo '<ul>'
        . _("The GPG Encryption Plugin will allow you to encrypt, sign, and decrypt messages in accordance with the OpenPGP standard for email security and authentication.")
        . "</ul>\n";
}

function gpg_handle_octet_stream(&$attachinfo) {
    $filename = $attachinfo[7];
    if (strrpos($filename,".asc") == (strlen($filename)-4)) {
        include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
        gpg_decrypt_attachment_do($attachinfo);
    }
}

function gpg_handle_signature(&$attachinfo) {
    include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
    gpg_handle_signature_do($attachinfo);
}

function gpg_decrypt_attachment(&$attachinfo) {
        include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
        gpg_decrypt_attachment_do($attachinfo);
}

/*********************************************************************/
/**
 * function gpg_compose_bottom
 *
 * This function is called by the main SM plugin_init (above)
 * at the end of the compose page.  This is here because the second
 * send button is not in existance during the button row calls
 * which contain most of the javascript for the compose page
 *
 * @return void
 */


function gpg_compose_bottom() {
    echo "<script language=javascript>\n<!--\n\n";
        echo "function gpg_sendbottomClick() {\n";
            echo "sendClicked=true;\n";
        echo "}\n";
    echo  "\ndocument.compose.send[1].onclick=gpg_sendbottomClick;\n\n//-->\n</script>";
}

/*********************************************************************/
/**
 * function gpg_compose_form
 *
 * This function is called by the main SM plugin_init (above)
 * to initialize our read_body_header functions.
 *
 * @return void
 */
function gpg_compose_form () {
    echo 'onsubmit="return gpg_composeSubmit(this);"';
}


/*********************************************************************/
/**
 * function gpg_read_body_header
 *
 * This function is called by the main SM plugin_init (above)
 * to initialize our read_body_header functions.
 *
 * @return void
 */
function gpg_read_body_header() {
   include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
   gpg_read_body_header_do();
}

/*********************************************************************/
/**
 * function gpg_compose_row ()
 *
 * This function adds a "Encrypt Now" and "GPG Sign" link to the
 * "Compose" row during message composition.
 *
 * @return void
 */
function gpg_compose_row() {
   include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
   gpg_compose_row_do();
}

/*********************************************************************/
/**
 * function gpg_optpage_register_block ()
 *
 * This function formats and adds the plugin and its description to the
 * Options screen.
 *
 * @return void
 */
function gpg_optpage_register_block() {
   include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
   gpg_optpage_register_block_do();
}

/*********************************************************************/
/**
 * function gpg_compose_send
 *
 * This function is called by the main SM plugin_init (above)
 * to initialize our compose_send hook functions
 *
 * This is where the Encrypt on Send and
 * Encrypt and Sign on Send functions go.
 *
 * @return void
 */
function gpg_compose_send(&$composeMessage) {
   include_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
   gpg_compose_send_do($composeMessage);
   return $composeMessage;
}


/*********************************************************************/
/**
 *
 * $Log: setup.php,v $
 * Revision 1.67  2003/12/30 19:06:01  ke
 * changed single to double quote for translations
 *
 * Revision 1.66  2003/12/05 12:27:39  brian
 * added application/pgp handler to look for .asc files
 *
 * Revision 1.65  2003/12/05 12:18:42  brian
 * added text/plain handler to look for .asc files
 *
 * Revision 1.64  2003/11/21 18:38:00  brian
 * - add description of GPG Plugin to help_chapter hook
 * - localize strings in help_chapter
 * Bug 101
 *
 * Revision 1.63  2003/11/20 16:40:48  walter
 * - modified path file name to master help page
 * Bug 101
 *
 * Revision 1.62  2003/11/20 12:52:25  brian
 * register help_chapter hook to call the gpg_help.php page
 *
 * Revision 1.61  2003/11/06 03:13:54  brian
 * added another possible path for SM_PATH
 *
 * Revision 1.60  2003/11/06 03:00:20  brian
 * - added additional check for SM_PATH to account
 *   for inclusion from a different plugin
 *   - problem was evident in SquirrelSpell
 *
 * Revision 1.59  2003/11/04 21:38:41  brian
 * change to use SM_PATH
 *
 * Revision 1.58  2003/10/30 19:40:20  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.57  2003/10/27 18:18:45  ke
 * -added attachment handler for application/pgp-signature (fully implemented)
 * -added attachment handler for application/pgp-encrypted (not implemented)
 *
 * Revision 1.56  2003/10/14 21:02:48  ke
 * -fixed broken javascript is plugin compose row is not loaded
 *
 * Revision 1.55  2003/10/08 20:46:35  ke
 * -Added a compose_bottom hook to include javascript for assigning the onclick function
 * -the second send button is not in existance when the javascript in the button row runs
 * -so this function assigns an onlick function to the second send button after it's there
 * -we might want to consider moving more javascript into this hook
 *
 * Revision 1.54  2003/09/26 10:55:29  brian
 * added compose_form hook
 *
 * Revision 1.53  2003/09/17 18:18:23  ke
 * -Altered the way the compose_send hook is called, allowing the message object to be passed
 * in by reference, and altered directly
 * -Part of the fix of encrypt on send
 * Bug 53
 *
 * Revision 1.52  2003/09/16 00:02:55  ke
 * Added back in plugin_init function, to register hooks
 * Added wrapper functions to include files when needed, then call needed function
 *
 * Revision 1.51  2003/09/15 23:23:49  ke
 * Removed all functions, include_once'd setup_hook_functions.php
 * This should speed loading and hooks
 *
 * Revision 1.50  2003/07/14 15:34:06  brian
 * changed main options block to title: Encryption Options (GPG/OpenPGP)
 *
 * Revision 1.49  2003/07/09 13:53:58  brian
 * added processing of $gpg_export flag to change compose page target
 * Bug 41
 *
 * Revision 1.48  2003/07/07 20:33:58  brian
 * - modified code to support import from email
 * - added gpg_import_link fn
 * Bug 46
 *
 * Revision 1.47  2003/07/02 22:45:42  brian
 * pulled out the 'Encrypt&Sign on Send' code entirely for now.
 *
 * Revision 1.46  2003/07/02 22:39:53  brian
 * moved closing coment bracket
 *
 * Revision 1.45  2003/07/02 22:36:54  brian
 * comment out encrypt&sign on send, since it doesn't work anyway
 *
 * Revision 1.44  2003/07/02 22:28:36  brian
 * fixed inadvertent bug that broke Encrypt&Sign
 *
 * Revision 1.43  2003/05/28 19:17:12  brian
 * modified to fix non-working encryptcapability (broken during work on encrypt&sign on send)
 *
 * Revision 1.42  2003/05/19 17:51:05  brian
 * added 'global $version' to compose_row fn
 *
 * Revision 1.41  2003/05/16 16:29:15  brian
 * added version check to disable encrypt on send
 * (in compose_body_row hook) in SM < 1.4.0
 *
 * Revision 1.40  2003/05/16 16:20:11  brian
 * added version check to disable encrypt on send (in compose_end hook) in SM < 1.4.0
 *
 * Revision 1.39  2003/05/16 13:44:39  brian
 * added tags for phpdoc
 *
 * Revision 1.38  2003/05/15 22:47:21  brian
 * - improved handlnig in compose_send
 * - added $debug flags
 * - added comments to explain use of globals
 *
 * Revision 1.37  2003/05/14 01:34:24  vinay
 * *** empty log message ***
 *
* Revision 1.36  2003/05/13 22:52:23  brian
 * added compose_send hook functionality for encrypt on send
 * Bug 26
 *
 * Revision 1.35  2003/05/10 12:23:59  brian
 * added function header blocks to functions that were missing them
 *
 * Revision 1.34  2003/05/02 15:37:06  brian
 * checked in Ryan's code for detached signatures after minor mods
 * Bug 33
 *
 * Revision 1.33  2003/04/16 02:33:32  brian
 * added 'Encrypt&Sign' button
 * Bug 11
 *
 * Revision 1.32  2003/04/09 12:59:20  brian
 * single quote values in 'Encrypt Now' button
 *
 * Revision 1.31  2003/04/04 03:05:49  brian
 * added global $passed_id, $mailbox back into decrypt_link function -- needed for document.write
 *
 * Revision 1.30  2003/04/04 01:56:11  tyler
 * - added $uid_support to the sqimap_run_command query
 *
 * Revision 1.29  2003/04/04 00:09:48  brian
 * changed so that fetch_body function is called only once, so we only connect once to the imap stream
 *
 * Revision 1.28  2003/04/03 23:45:35  brian
 * changed check_sign and decrypt_link to use the standard SM core command of sqimap_run_command, rather that sqimap_read_data, becasue read_data didn't work the same under SM 1.4
 *
 * Revision 1.27  2003/04/03 02:32:22  brian
 * Decoupled signature verification and display of the decryption button.
 *
 * Revision 1.26  2003/04/02 12:25:42  brian
 * modified window.open function for decrypt to:
 * - make passphrase dialog smaller
 * - allow scrollbars
 *
 * Revision 1.25  2003/04/01 07:58:18  brian
 * corrected document .write link to work in SM 1.4
 *
 * Revision 1.24  2003/03/31 22:02:45  brian
 * modified _GET parameters for decrypt now button
 *
 * Revision 1.23  2003/03/31 21:57:31  brian
 * modified to add passed_value and mailbox to submit of decrypt now button
 *
 * Revision 1.22  2003/03/31 15:18:25  brian
 * modified gpg_decrypt_link function to use document,.write and window.open functions to create popup.
 *
 * Revision 1.21  2003/03/31 15:03:41  brian
 * - modified to remnove double declaration of gpg_check_sign
* - file now correctly declares gpg_decrypt_link
 *
 * Revision 1.20  2003/03/31 14:57:38  brian
 * - modified signing link to use new gpg_pop_init.php file
 * - added link for decrypt now
 * - placed signing and decryption functions under read_body_header
 *   initialization function
 * Bug 8
 *
 * Revision 1.19  2003/03/25 21:43:23  brian
 * Bug 6
 * Slightly better handling of whether to display the buttons or not after encrypt.
 *
 * Revision 1.18  2003/03/17 18:55:41  brian
 * - progress towards SM v >=1.3.1 compatibility
 * - path selection for includes now works on both
 *   SM 1.2.x and SM >= 1.3.1
 *
 * Revision 1.17  2003/03/15 22:03:32  brian
 * moved strings.php include to outside of the SM version check
 * strings.php sets the SM version...
 *
 * Revision 1.16  2003/03/13 04:04:16  brian
 * modified GPG Sign button calling code.
 *
 * Revision 1.15  2003/03/12 15:00:16  brian
 * - added document action to change the document action after encrypting an email
 * - TODO make sure that it works even after unsuccessful submit
 * - TODO make syntax cross browser compatible
 *
 * Revision 1.14  2003/03/12 14:34:40  brian
 * - added function header comment blocks to all functions
 *
 * Revision 1.13  2003/03/12 05:02:58  tyler
 * - reduced the size of the message_sign popup window
 *
 * Revision 1.12  2003/03/12 01:43:58  tyler
 * - test for secring file now checks for zero length file
 *
 * Revision 1.11  2003/03/12 01:36:28  tyler
 * - Initial attempt at signature verification on read. New hook added.
 *
 * Revision 1.10  2003/03/11 21:25:18  tyler
 * - helps if you define $privatekeysallowed otherwise it's always 0 :)
 *
 * Revision 1.9  2003/03/11 19:22:08  tyler
 * - Modified to use $allowprivatekeys preference to decide if Sign button should be displayed
 *
 * Revision 1.8  2003/03/11 02:45:24  tyler
 * - modified code to only exclude encrypt now button after encryption routine
 *
 * Revision 1.7  2003/03/11 01:29:51  brian
 * fixed bug with pressing button twice by not showing buttons after the encryption routine has been run correctly
 *
 * Revision 1.6  2003/03/11 01:06:48  tyler
 * - renamed filename variable to pubring
 * - added secring variable
 * - rewrote the button building code to use document.write so folks with
 *   javascript turned off wont catch errors, buttons just wont get displayed
 * - rewrote the button building code to only display if ring file available
 * - converted 'Sign Now' button to 'GPG Sign' so as not to be confused with
 *   other "sign" buttons
 * - converted 'Encrypt Now' button to 'GPG Encrypt'
 * - converted code called by 'Sign Now' to pop a window
 *
 * Revision 1.5  2003/03/09 14:35:54  brian
 * Added Tyler's "Sign Now" button
 * TODO - only show button if user has a secret key
 *
 * Revision 1.4  2003/03/06 23:42:48  brian
 * Added check for SM ver > 1.3
 *
 * Revision 1.3  2003/02/26 17:07:55  brian
 * Added check so that the Encrypt Now button will only be displayed if the user has a keyring directory.
 *
 * Revision 1.2  2002/12/05 19:25:46  brian
 * Added ID and Log tags
 *
 *
 */

?>
