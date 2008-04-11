<?php
/**
 * GPG Plugin help file framework. Creates the HTML structure,
 * sets the theme, and includes the help article file.
 *
 * Copyright (c) 2003-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Walter Torres
 *
 * $Id: gpg_recipientlist.php,v 1.5 2005/07/27 14:07:49 brian Exp $
 *
 * @todo check help file include for security breach in gpg_help.php
 */
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_decrypt_attach.php, exiting abnormally";
}

//require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');
require_once(SM_PATH.'plugins/gpg/gpg_options_header.php');

if (!isset($_GET['help']))
{
    $_GET['help'] = 'base';
}

//do we need to include any SM files here to get the defaults set up?
//probably gpg_config.php or strings.php

echo   '<html>'
     . '<head><title>GPG Help</title></head>';

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" "
     . "vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n";


// this stuff should get loaded with the load_prefs.php file, but it isn't,
// so we hack it here so our colors are correct.
   $chosen_theme = getPref($data_dir, $username, 'chosen_theme');
   $chosen_theme = preg_replace("/(\.\.\/){1,}/", SM_PATH, $chosen_theme);
   if (isset($chosen_theme) && (file_exists($chosen_theme))) {
      @include_once($chosen_theme);
   }
// end color hack

echo   '<html>'
     . '<head><title>GPG Help</title></head>'
     . '<body>';

// ===============================================================
$section_title = _("GPG Recipient List");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
     . '<tr><td>';

$recipients = $_SESSION ['recipients'];

foreach($recipients as $r)
{
    echo decodeHeader($r->getAddress(true));
    echo '<br />';
}

echo '</td></tr></table>';


echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
     . '<tr><td bgcolor="' . $color[9] . '" align="left">';

// Javascript method to close child window
echo '<button onclick="javascript:self.window.close();">'
     . _("Done")
     . '</button>';

echo '</td></tr></table>';

echo  '</body></html>';


/**
 * $Log: gpg_recipientlist.php,v $
 * Revision 1.5  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.4  2005/07/27 13:51:32  brian
 * - remove all code to handle SM versions older than SM 1.4.0
 * Bug 262
 *
 * Revision 1.3  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.2  2003/12/30 19:04:29  ke
 * -changed single to double quotes for translation purposes
 *
 * Revision 1.1  2003/11/18 15:32:04  walter
 * - initial submition
 * - framework to display recipient list from decrypted messages
 * Bug 71
 *
 * Revision 1.4  2003/11/04 21:38:40  brian
 * change to use SM_PATH
 *
 * Revision 1.3  2003/10/28 23:56:40  walter
 * - updated structure
 * Bug 79
 *
 * Revision 1.2  2003/10/22 15:25:18  brian
 * - added page header block
 * - removed trailing LF's after closing ?>
 *
 * Revision 1.1  2003/10/21 22:11:37  walter
 * - linitial entry
 * - page structure set
 * Bug 79
 **/
?>
