<?php
/**
 * Module Footer page
 *
 * @author Brian Peterson
 *
 * $Id: gpg_module_footer.php,v 1.9 2004/08/09 18:00:24 ke Exp $
 *
 * @todo specify a backlink so that the libk on the bottom of the screen
 *       may be defined by the calling page
 */
echo '<center>'
     . '<table border=0 cellpadding=0 cellspacing=0 width=95% align="center">'
     . '<tr><td align=center>'
     . '<hr />'
     . '<a href="gpg_options.php?MOD=options_main">';

if (isset ($backlink)) {
    switch ($backlink) {
        case 'smoptions':
            echo '<a href="../../src/options.php">'
                . _("Back to Main Squirrelmail Options").'</a>';
            break;
        case 'main':
            echo '<a href="' . $backpath . 'gpg_options.php">'
               . _("Back to GPG Plugin Options").'</a>';
            break;
        case 'keymgmt':
            echo '<a href="' . $backpath . 'keyring_main.php">'
                . _("Back to Keyring Management Options").'</a>';
            break;
        break;
    }
}

echo  '</a><p /></td></tr>'
     . '<tr><td bgcolor="'
     . $color[9]
     . '" align="center">';
global $GPG_VERSION;
echo _("GPG Plugin") . '&nbsp;v&nbsp;' . $GPG_VERSION;

echo "\n</td></tr></table></center>\n"
    . '</body></html>';

/* Switch back to the SquirrelMail domain */
bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');

/**
 * $Log: gpg_module_footer.php,v $
 * Revision 1.9  2004/08/09 18:00:24  ke
 * added option to provide path for back link
 *
 * Revision 1.8  2004/01/16 22:38:00  brian
 * E_ALL fixes
 * bug 146
 *
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
