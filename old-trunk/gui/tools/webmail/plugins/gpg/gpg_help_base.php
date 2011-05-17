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
 * $Id$
 *
 * @todo check help file include for security breach in gpg_help_base.php
 */

if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_help_base.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');

//call the hashed dir functions for SM, jut in case
global $username;
global $data_dir;
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

echo   '<html>'
     . '<head><title>'
     . _("GPG Plugin Help")
     . '</title></head>'
     . '<body>';

// ===============================================================
$section_title = _("GPG Plugin Help");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">';

echo '<tr><td><center>'
    . '<a href="'.SM_PATH.'src/help.php">' . _("Table of Contents") . '</a>'
    . '</center><br></td></tr>';

echo "<tr><td>\n";

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
echo '</td></tr></table>'."\n";

echo '<table width="95%" align="center" border="1" cellpadding="2" cellspacing="0">';

echo '<tr><td bgcolor="' . $color[9] . '" align="left">';

echo '</td></tr></table>'."\n";

echo  '</body></html>';


/**
 * $Log: gpg_help_base.php,v $
 * Revision 1.15  2006/08/18 20:57:25  ke
 * - fixed check for help files to use array_search instead of array_key_exists, to search contents of array
 * - added similar check to gpg_help.php
 *
 * Revision 1.14  2006/08/18 14:58:54  brian
 * - first cut at fix for file inclusion vulnerability
 *
 * Revision 1.13  2005/07/27 14:07:48  brian
 * - update copyright to 2005
 *
 * Revision 1.12  2005/07/27 13:51:32  brian
 * - remove all code to handle SM versions older than SM 1.4.0
 * Bug 262
 *
 * Revision 1.11  2004/04/30 18:01:14  ke
 * -removed new line from end of file
 *
 * Revision 1.10  2004/01/09 18:26:50  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.9  2003/11/25 02:07:08  ke
 * -changed getPref to use data_dir instead of safe_data_dir (prehashed)
 *
 * Revision 1.8  2003/11/24 21:46:29  brian
 * moved the calls to getHashedDir to after the includes, so the functions we need will be defined
 * Bug 116
 *
 * Revision 1.7  2003/11/24 20:52:50  ke
 * -added getHashedDir calls to help_base text
 * bug 116
 *
 * Revision 1.6  2003/11/22 14:36:47  brian
 * - updated default include to base.php
 * Bug 70
 *
 * Revision 1.5  2003/11/22 14:22:05  brian
 * - changed to include help file
 * Bug 101
 *
 * Revision 1.4  2003/11/21 06:41:41  brian
 * - fixed and standardized localization strings
 * - fixed SM_PATH error
 * Bug 101
 *
 * Revision 1.3  2003/11/21 06:10:19  brian
 * -centered link to main SM Help TOC
 * -standardized wording of passphrase Q
 * Bug 101
 *
 * Revision 1.2  2003/11/21 06:06:01  brian
 * - cleaned up formatting
 * - improved wording on FAQ's
 * - added link to 'Getting Started'
 * - added link to main SM Help TOC
 * Bug 101
 *
 * Revision 1.1  2003/11/20 16:41:44  walter
 * - inital insert
 * - new "Master Help Page" framework
 * Bug 101
 **/
?>