<?php
/**
 * gpg_pop_functions.php
 *-----------
 * GPG plugin popup functions file,
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Copyright (c) 2002-2003 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Tyler Allison
 * @author Brian Peterson
 *
 * $Id: gpg_pop_functions.php,v 1.15 2003/11/01 22:01:26 brian Exp $
 */

/*********************************************************************/
/**
 * function gpg_makePopWindow
 *
 * This function makes our pop-ups for collecting the passphrase.
 * It could be used for things other than the passphrase,
 * but assumes that if there is a password field in the pop-up,
 * that field should get focus.
 *
 * @param  $title     Title of the page.
 * @param  $scriptsrc If defined, link this javascript source page into
 *                    the document using <script src="file.js"> format.
 * @param  $body      The content to include.
 * @return            void
 */
function gpg_makePopWindow($title, $scriptsrc, $body){

    /**
     * set the localization variables
     * Now tell gettext where the locale directory for your plugin is
     * this is in relation to the src/ directory
     */

    bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
    /* Switch to your plugin domain so your messages get translated */
    textdomain('gpg');

    global $color, $theme_css;
    echo "<html>\n"
    . "<head>\n"
    . "<title>$title</title>\n";
    /**
    * Check if we have a defined css theme to use.
    */
    if ($theme_css != "") {
    echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">\n";
    }

    /**
    * Link in the .js file if needed
    */
    if ($scriptsrc!==''){
        echo "<script type=\"text/javascript\" src=\"$scriptsrc\"></script>\n";
        // if no_signing_passwd is set onload()
        $no_signing_passwd = getPref ($data_dir, $username, 'no_signing_passwd');
        if ($no_signing_passwd == 'true') {
          $onload = 'onload="gpg_pop_submit(true)"';
        } else {
          $onload = 'onload="gpg_placeFocus()"';
        };
    };

    echo "</head>\n";

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" "
     . "vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n";

    echo "<table width='100%' border=0 cellpadding=2>\n"
    . '<tr>'
    . "<td bgcolor=\"$color[9]\" align=center>"
    . "<strong>$title</strong>"
    . '</td>'
    . "</tr>\n"
    . '<tr><td><hr></td></tr>'
    . '<tr>'
    . "<td>$body</td>"
    . "</tr>\n"
    . '<tr><td><hr></td></tr>'
    . '<tr>'
    . "<td bgcolor=\"$color[9]\" align=center>"
    . "</td>\n"
    . '</tr>'
    . '</table>'
    . "</body>\n</html>\n";


    /* Switch back to the SquirrelMail domain */
    bindtextdomain('squirrelmail', SM_PATH . 'locale');
    textdomain('squirrelmail');
} //end gpg_makepopwindow fn

/*********************************************************************/
/**
 * $Log: gpg_pop_functions.php,v $
 * Revision 1.15  2003/11/01 22:01:26  brian
 * infrastructure changes to support removal of MakePage functions
 *
 * Revision 1.14  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.13  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.12  2003/04/01 17:42:09  brian
 * updated script section to use gpg_placefocus()
 *
 * Revision 1.11  2003/04/01 17:34:34  brian
 * moved where script is included to remove js error on page
 *
 * Revision 1.10  2003/04/01 17:22:23  brian
 * moved where script is included to remove js error on page
 *
 * Revision 1.9  2003/04/01 16:41:54  brian
 * moved where script is included to remove js error on page
 *
 * Revision 1.8  2003/04/01 07:11:32  brian
 * fixed includes to reflect different calling directories
 *
 * Revision 1.7  2003/03/30 22:23:12  brian
 * - Modified to place onload declarations inside $scriptsrc test.
 *   onload should not be defined without a scriptsrc to display into.
 * - TODO: will need further abstraction to better genericize functionality later.
 * Bug 8
 *
 * Revision 1.6  2003/03/28 13:51:25  brian
 * updated to use gpg_pop_submit to better abstract functionality
 * Bug 8
 *
 * Revision 1.5  2003/03/28 13:49:45  brian
 * added header and Log: blocks
 *
 * Revision : 1.4 2003/3/14 00:54:35 'tyler'
 * - take advantage of the placeFocus() javascript
 *
 * Revision : 1.3 2003/3/13 20:06:02 'tyler'
 * - onload() for key signing was not getting written in
 *   the right spot since <body> is being written in the makeWindow function.
 * - Moved onload() to makeWindow function.
 *
 * Revision : 1.2 2003/3/11 04:05:58 'tyler'
 * - Add more functionality to the sign message stuff
 *
 * Revision : 1.1 2003/3/11 01:28:42 'tyler'
 *  - initial add of the popup window functions
 *
 */

?>
