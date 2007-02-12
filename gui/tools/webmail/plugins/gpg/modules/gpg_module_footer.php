<?php
/**
 * Module Footer page
 *
 * @author Brian Peterson
 *
 * $Id: gpg_module_footer.php,v 1.7 2003/12/27 18:01:04 brian Exp $
 *
 * @todo specify a backlink so that the libk on the bottom of the screen
 *       may be defined by the calling page
 */
echo '<center>'
     . '<table border=0 cellpadding=0 cellspacing=0 width=95% align="center">'
     . '<tr><td align=center>'
     . '<hr />'
     . '<a href="gpg_options.php?MOD=options_main">';

switch ($backlink) {
    case 'smoptions':
        echo '<a href="../../src/options.php">'
            . _("Back to Main Squirrelmail Options").'</a>';
        break;
    case 'main':
        echo '<a href="gpg_options.php">'
            . _("Back to GPG Plugin Options").'</a>';
        break;
    case 'keymgmt':
        echo '<a href="gpg_options.php?MOD=keymgmt">'
            . _("Back to Keyring Management Options").'</a>';
        break;
    break;
}

echo  '</a><p /></td></tr>'
     . '<tr><td bgcolor="'
     . $color[9]
     . '" align="center">';

echo _("GPG Plugin") . '&nbsp;v&nbsp;' . $GPG_VERSION;

echo "\n</td></tr></table></center>\n"
    . '</body></html>';

/* Switch back to the SquirrelMail domain */
bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');

/**
 * $Log: gpg_module_footer.php,v $
 * Revision 1.7  2003/12/27 18:01:04  brian
 * added closing body and html tags
 *
 * Revision 1.6  2003/11/03 19:40:35  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.5  2003/11/01 22:00:43  brian
 * - standardized text across several pages
 * - localized remaining strings
 * - removed $msg strings and Makepage fn
 *
 */
?>