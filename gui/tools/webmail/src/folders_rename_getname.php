<?php

/**
 * folders_rename_getname.php
 *
 * Gets folder names and enables renaming
 * Called from folders.php
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: folders_rename_getname.php 12127 2007-01-13 20:07:24Z kink $
 * @package squirrelmail
 */

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/display_messages.php');
require_once(SM_PATH . 'functions/forms.php');

/* get globals we may need */
sqgetGlobalVar('key',       $key,           SQ_COOKIE);
sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('onetimepad',$onetimepad,    SQ_SESSION);
sqgetGlobalVar('delimiter', $delimiter,     SQ_SESSION);
sqgetGlobalVar('old',       $old,           SQ_POST);
/* end of get globals */

if ($old == '') {
    displayPageHeader($color, 'None');

    plain_error_message(_("You have not selected a folder to rename. Please do so.").
        '<br /><a href="../src/folders.php">'._("Click here to go back").'</a>.', $color);
    exit;
}

if (substr($old, strlen($old) - strlen($delimiter)) == $delimiter) {
    $isfolder = TRUE;
    $old = substr($old, 0, strlen($old) - 1);
} else {
    $isfolder = FALSE;
}

$old = imap_utf7_decode_local($old);

if (strpos($old, $delimiter)) {
    $old_name = substr($old, strrpos($old, $delimiter)+1, strlen($old));
    $old_parent = substr($old, 0, strrpos($old, $delimiter));
} else {
    $old_name = $old;
    $old_parent = '';
}


displayPageHeader($color, 'None');
echo '<br />' .
    html_tag( 'table', '', 'center', '', 'width="95%" border="0"' ) .
        html_tag( 'tr',
            html_tag( 'td', '<b>' . _("Rename a folder") . '</b>', 'center', $color[0] )
        ) .
        html_tag( 'tr' ) .
            html_tag( 'td', '', 'center', $color[4] ) .
            addForm('folders_rename_do.php').
     _("New name:").
     '<br /><b>' . htmlspecialchars($old_parent) . ' ' . htmlspecialchars($delimiter) . '</b>' .
     addInput('new_name', $old_name, 25) . '<br />' . "\n";
if ( $isfolder ) {
    echo addHidden('isfolder', 'true');
}
echo addHidden('orig', $old).
     addHidden('old_name', $old_name).
     '<input type="submit" value="'._("Submit")."\" />\n".
     '</form><br /></td></tr></table>';

?>