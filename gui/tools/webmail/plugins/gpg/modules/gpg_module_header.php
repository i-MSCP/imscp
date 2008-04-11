<?php
/**
 * Module Header page
 *
 * @author Brian Peterson
 *
 * $Id: gpg_module_header.php,v 1.10 2004/01/09 18:27:15 brian Exp $
 */
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_module_header.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/gpg_options_header.php');

/*
 * Function for easily bailing out on malformed requests.
 *
 * @param strign $err   Error String
 * @return void
 *
 */
function gpg_bail($err)
{
    echo '<font color=red><b>'
         . _("There was a problem with your request.")
         . _("Please try again.")
         . '<p><pre>';

//  print_r($err);
    echo '</pre><p></b></font>';
  //exit();
}

// call the main Squirrelmail page header function
displayPageHeader($color, 'None');


/**
 * set the localization variables
 * Now tell gettext where the locale directory for your plugin is
 * this is in relation to the src/ directory
 */
bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
/* Switch to your plugin domain so your messages get translated */
textdomain('gpg');

if (! isset($err)) $err = array();

echo '<br>';
/**
 * $Log: gpg_module_header.php,v $
 * Revision 1.10  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.9  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.8  2003/11/01 22:00:43  brian
 * - standardized text across several pages
 * - localized remaining strings
 * - removed $msg strings and Makepage fn
 *
 */
?>