<?php

echo '<ul>';

    echo '<li>'
        . '<a href="modules/getstart.php">'
        . _("Getting Started with the GPG Plugin")
        . "</a></li>\n";

    echo "<p>\n";

    echo '<li>'
        . gpg_add_help_link ( 'what_keystrength.php' )
        . _("What is a good key strength?")
        . "</a></li>\n";

    echo '<li>'
        . gpg_add_help_link ( 'what_passphrase.php' )
        . _("How do I choose a passphrase?")
        . "</a></li>\n";

    echo '<li>'
        . gpg_add_help_link ( 'what_signing.php' )
        . _("What is a 'Signing Key'?")
        . "</a></li>\n";

    echo '<li>'
        . gpg_add_help_link ( 'what_trusted.php' )
        . _("What is a 'Trusted Key'?")
        . "</a></li>\n";

    echo '<li>'
        . gpg_add_help_link ( 'disc_keystore.php' )
        . _("Should I store my private keys on the server?")
        . "</a></li>\n";

    echo '<p><li>'
        . gpg_add_help_link ( 'troubleshooting.php', 'true' )
        . _("Troubleshooting")
        . "</a></li>\n";

    echo '<p><li>'
        . '<a href="README.txt">'
        . _("View README file - includes contact information")
        . "</a></li>\n";

    echo '<li>'
        . '<a href="INSTALL.txt">'
        . _("View INSTALL file")
        . "</a></li>\n";

    echo '<p><li>'
        . gpg_add_help_link ( 'external_resources.php', 'true' )
        . _("External Resources")
        . "</a></li>\n";

    echo '<p><li>'
        . '<a href="http://www.braverock.com/bugzilla/">'
        . _("Report a bug on the GPG Plugin Bugzilla")
        . "</a></li>\n";

echo '</ul>';

/**
 * $Id: base.php,v 1.6 2003/11/22 15:50:36 brian Exp $
 *
 * $Log: base.php,v $
 * Revision 1.6  2003/11/22 15:50:36  brian
 * rearranged options so they make a little more sense
 *
 * Revision 1.5  2003/11/22 15:47:16  brian
 * Changed name to INSTALL.txt so it will displaay better on browsers
 *
 * Revision 1.4  2003/11/22 15:40:04  brian
 * Added links to README, INSTALL, and Bugzilla
 * Bug 70
 *
 * Revision 1.3  2003/11/22 15:10:00  brian
 * - added external resources page
 * Bug 70
 *
 * Revision 1.2  2003/11/22 14:30:48  brian
 * - added back troubleshooting
 * Bug 101
 *
 * Revision 1.1  2003/11/22 14:23:33  brian
 * Initial Revision
 * - moved base.php to an included file
 * Bug 101
 *
 *
 */
?>