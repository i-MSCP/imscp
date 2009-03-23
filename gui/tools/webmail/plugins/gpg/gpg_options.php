<?php
/**
 * gpg_options.php
 * --------------------
 * Main wrapper for the options interface.
 *
 * Copyright (c) 1999-2002 The SquirrelMail development team
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 *
 * $Id$
 *
 * @todo modify options_main and gpg_module_footer to accept a backlink defined by the module file that is included
 *
 */
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH', '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_options.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/gpg_options_header.php');

/**
 * $MOD is the name of the module to invoke.
 * If $MOD is unspecified, assign "init" to it. Else check for
 * security breach attempts.
 */

if(isset($_POST['MOD'])) {
  $MOD = $_POST['MOD'];
} elseif (isset($_GET['MOD'])) {
  $MOD = $_GET['MOD'];
}

if(!isset($MOD) || !$MOD) {
  $MOD = 'options_main';
} else {
  gpg_ckMOD($MOD);
}

if ($MOD=='gpgdecrypt') {
	ob_start();
}

/**
 * gpg_page_title - echo the page title
 *
 * Called by the module pages to display the optional title header.
 *
 * @author Brian Peterson
 *
 * @param string $title Localized page title
 */
function gpg_page_title ($title) {
    
    global $color;

        echo '<table width="100%" align="center" border="0" cellpadding="2" '
            . 'cellspacing="0">'
            . '<tr>'
            . '<td bgcolor="'.$color[9].'" align=center>'
            . "<strong>$title</strong></td></tr>"
            . '<TABLE BGCOLOR="'.$color[9].'" WIDTH="100%" CELLPADDING="1"'
            . ' CELLSPACING="0" BORDER="0" ALIIGN="center">'."\n"
            . '<TR><TD HEIGHT="5" COLSPAN="2" BGCOLOR="'
            . $color[4].'"></TD></TR></table>'
            . '</table>'."\n";
}

//set up the page format
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');


/**
 * Load the module file already.
 */
require_once(SM_PATH."plugins/gpg/modules/$MOD.mod");

/**
 * @todo define a backlink variable in gpg_module_footer.php that can be set by the module file
 */
//pull in the footer
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');

/**
 *
 * $Log: gpg_options.php,v $
 * Revision 1.15  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.14  2004/04/30 18:00:06  ke
 * -removed newline from end of file
 *
 * Revision 1.13  2004/03/10 22:06:27  ke
 * -added an ob_start for output buffering in the case of decryption
 *
 * Revision 1.12  2004/01/16 22:58:18  brian
 * E_ALL fixes
 * bug 146
 *
 * Revision 1.11  2004/01/09 18:26:50  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.10  2003/11/04 21:38:41  brian
 * change to use SM_PATH
 *
 * Revision 1.9  2003/11/01 22:01:26  brian
 * infrastructure changes to support removal of MakePage functions
 *
 * Revision 1.8  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.7  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.6  2003/07/09 01:44:27  brian
 * modified require_once directive to reflect different locations we can be called from
 *
 * Revision 1.5  2003/07/08 18:02:51  vermette
 * using new gog_options_header.  No functional change
 *
 * Revision 1.4  2003/03/07 00:12:52  brian
 * Removed superflous includes.
 *
 * Revision 1.3  2002/12/10 03:46:20  brian
 * updated to not require gpg_config, as it is required by gpg_functions
 *
 *
 */
?>