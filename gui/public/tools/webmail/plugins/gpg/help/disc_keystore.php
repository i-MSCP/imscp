<?php

echo  '<h2>'
    . _("Should I store my private keys on the server?")
    . '</h2>';

echo  _("Public web servers are not always secure!")
    . _("Web-based email programs are very convenient, and using your web email to send encrypted emails adds to that convenience.")
    . '&nbsp;'
    . _("It would be dangerous to simply assume that any web server (like this one) is secure.")
    . '&nbsp;'
    . _("If it is not secure, your identity can be stolen!")
    . '<p />'
    . _("If you do not trust the people that are hosting this email service it is recommended that you do not upload your existing private key until trust can be established.")
    . '&nbsp;'
    . _("A new Keypair can be generated for this account on the webserver for use here.");


/**
 * $Id$
 *
 * $Log: disc_keystore.php,v $
 * Revision 1.8  2004/01/18 15:25:17  brian
 * - standardized strings to minimize translation
 *
 * Revision 1.7  2003/12/29 23:52:39  brian
 * localized strings discoverd by Alex Lemaresquier during French translation
 *
 * Revision 1.6  2003/12/28 16:37:14  brian
 * updated string to match another help file to ease translation
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
 **/
?>