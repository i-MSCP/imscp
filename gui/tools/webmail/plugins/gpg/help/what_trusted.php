<?php

echo     '<h2>'
     . _("What is a Trusted Key?")
     .   '</h2>';

echo   "<p />\n"
     . _("Web-based email programs are very convenient, and using your web email to send encrypted email adds to that convenience.")
     . "&nbsp;"
     . _("It may be dangerous to believe that any web server (like this one) may be presumed secure.")
     . "&nbsp;"
     . _("In fact, most security experts recommend that web servers be presumed to be at risk of compromise.")
     . "&nbsp;"
     . _("Because of this risk of compromise and lack of security, you may not want to store your private key on the web server.");

echo  "<p />\n"
     . _("To allow you to use the GPG plugin optimally without storing your private key(s) on the web server, the GPG Plugin allows you to specify a special option called 'Trusted Key ID'.")
     . "&nbsp;"
     . _("This option allows you to indicate to GPG which key on your public keyring should be treated as a fully trusted key.")
     . "&nbsp;"
     . _("Any key signed with your trusted key ID will be allowed for encryption without generating errors.")
     . "&nbsp;"
     . _("Usually, this will be your primary public key, the key you use to sign other people's keys.");

echo  "<p />\n"
    . _("If you choose a trusted key and store a keypair on the web server, all keys that you wish to encrypt to will need to be signed by a trusted key.")
     . "&nbsp;"
    . _("The signing key may be one of: a key that you have explicitly marked as a trusted key, a keypair that you have on your public and secret keyrings, or a shared system trusted key defined by your system administrator.")
     . "&nbsp;"
    . _("If you try to encrypt to a key not signed by one of these keys, you will not be allowed to do so, because the identity of the recipient is unverifiable.");

echo  "<p />\n"
     . _("Select this option to set the key as your trusted key.")
     . '&nbsp;'
     . _("Setting a new trusted key will replace any previously chosen key.");

echo "<p />\n"
     . _("To disable the trusted key option, select no key as trusted.")
     . '<br>'
     . _("You can only have one trusted key at any given time.");
/**
 * $Id$
 *
 * $Log: what_trusted.php,v $
 * Revision 1.7  2003/11/21 19:20:10  brian
 * - added warning about unsigned keys to trusted key help
 * Bug 70
 *
 * Revision 1.6  2003/11/21 19:02:06  brian
 * - added more decriptive text to trusted key help
 * Bug 70
 *
 * Revision 1.5  2003/10/30 20:04:42  brian
 * modified all localized strings to use double quotes.
 * Bug 35
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
 *
 *
 */

?>