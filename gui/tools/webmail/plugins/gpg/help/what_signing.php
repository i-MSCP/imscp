<?php

echo     '<h2>'
    . _("What is a Signing Key?")
    .   '</h2>';

echo "<p />\n"
    . _("Your signing key is the key you use to 'sign' a message you are sending, to prove that it is from you.")
    . '&nbsp;'
    . _("You can only have one primary signing key at any given time.")
    . '&nbsp;'
    . '<br>'
    . _("You must select a signing key to use the GPG Plugin to securely sign messages.")
    . "<p />\n"
    . _("Select this option to set the key as your signing key.")
    . '<br>'
    . _("Setting a new primary signing key will replace any previously chosen key.")
    . '&nbsp;'
    . '<br>'
    . _("Once a primary signing key is selected, a signing key can be selected for each squirrelmail identity.")
    . '&nbsp;'
    . '<br>';

/**
 * $Id$
 *
 * $Log: what_signing.php,v $
 * Revision 1.10  2005/11/11 18:02:14  ke
 * - added notes about using signing key with identities`
 *
 * Revision 1.9  2004/01/14 22:23:26  brian
 * - added more explicit instructions about signing key req.
 * Bug 145
 *
 * Revision 1.8  2003/11/21 19:25:46  brian
 * -fixed typos in signing key help
 * Bug 70
 *
 * Revision 1.7  2003/11/03 19:46:49  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.6  2003/10/30 20:04:42  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.5  2003/10/28 23:56:27  walter
 * - updated structure
 * Bug 79
 *
 * Revision 1.4  2003/10/28 16:45:55  walter
 * - updated structure
 * Bug 79
 *
 * Revision 1.3  2003/10/21 19:31:44  walter
 * - localized text by sentances
 * Bug 35
 * Bug 79
 *
 * Revision 1.2  2003/10/21 19:29:03  walter
 * - converted to PHP
 * Bug 79
 */
?>