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
 * $Id: gpg_help.php,v 1.16 2006/08/18 20:57:25 ke Exp $
 */
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_help.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/modules/gpg_popup_header.php');
require_once(SM_PATH.'plugins/gpg/gpg_config.php');

global $data_dir;
global $username;
global $safe_data_dir;
$safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;

if (!isset($_GET['help']))
{
    $_GET['help'] = 'base.php';
}

//do we need to include any SM files here to get the defaults set up?
//probably gpg_config.php or strings.php


// this stuff should get loaded with the load_prefs.php file, but it isn't,
// so we hack it here so our colors are correct.
$chosen_theme = getPref($data_dir, $username, 'chosen_theme');
$chosen_theme = preg_replace("/(\.\.\/){1,}/", SM_PATH, $chosen_theme);
if (isset($chosen_theme) && (file_exists($chosen_theme))) {
    @include_once($chosen_theme);
}
// end color hack

echo  '<html>'
    . '<head><title>'
    . _("GPG Plugin Help")
    . '</title></head>'
    . '<body>';

// ===============================================================
$section_title = _("GPG Plugin Help");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
     . '<tr><td>';

// Help body text is inserted here via GET parameter
// Help body text is inserted here via GET parameter
$entries=array();
$d = dir(SM_PATH.'plugins/gpg/help/');
while (false !== ($entry = $d->read())) {
   $entries[]=$entry;
}
$d->close();
if (array_search($_GET['help'],$entries)!==false) {
    require_once (SM_PATH.'plugins/gpg/help/' . $_GET['help'] );
} else {
    echo _("Help file not found.").'<br>';
    echo _("You searched for:").' '.htmlspecialchars($_GET['help']).'<br>'."\n";
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
 * $Log: gpg_help.php,v $
 * Revision 1.16  2006/08/18 20:57:25  ke
 * - fixed check for help files to use array_search instead of array_key_exists, to search contents of array
 * - added similar check to gpg_help.php
 *
 * Revision 1.15  2005/07/27 13:51:32  brian
 * - remove all code to handle SM versions older than SM 1.4.0
 * Bug 262
 *
 * Revision 1.14  2004/04/30 18:00:46  ke
 * -removed newline from end of file
 *
 * Revision 1.13  2004/01/09 18:26:50  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.12  2003/12/30 19:03:47  ke
 * -changed single to double quotes for translation purposes.
 *
 * Revision 1.11  2003/11/25 01:55:18  ke
 * changed $safe_data_dir to $data_dir
 *
 * Revision 1.10  2003/11/24 22:44:10  brian
 * added include for gpg_config.php
 * Bug 116
 *
 * Revision 1.9  2003/11/24 21:49:00  brian
 * moved the calls to getHashedDir to after the includes, so the functions we need will be defined
 * Bug 116
 *
 * Revision 1.8  2003/11/24 20:53:44  ke
 * -added safe_data_dir and getHashedDir calls to gpg_help
 * bug 116
 *
 * Revision 1.7  2003/11/22 14:36:31  brian
 * - updated default include to base.php
 * Bug 70
 *
 * Revision 1.6  2003/11/21 06:41:40  brian
 * - fixed and standardized localization strings
 * - fixed SM_PATH error
 * Bug 101
 *
 * Revision 1.5  2003/11/20 16:39:54  walter
 * - removed SM header bar
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