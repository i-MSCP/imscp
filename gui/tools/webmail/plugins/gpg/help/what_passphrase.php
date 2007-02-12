<?php


echo     '<h2>'
     . _("How do I choose a passphrase?")
     .   '</h2>';

echo    _("Your secret passphrase should be as long as possible and easy for you to remember, but hard for someone else to guess.")
    . '&nbsp;'
    . _("If you forget your passphrase, you will not be able to decrypt messages encrypted with this public key.")
    . '&nbsp;'
    . _("Choosing a good passphrase is one of the most important tasks for secure communications.")
    . '<br>'
    . '<b>'
    . _("Here are a few tips on choosing a good passphrase:")
    . '&nbsp;'
    . '</b>'
    . '<ul>'
    . '  <li>'
    . _("Choose a complete sentence that you will be able to remember.")
    . '<li>'
    . _("You should change the capitalization and punctuation so that others cannot easily guess it, even if they guess the phrase you chose.")
    . ' <li>'
    . _("You may also choose to replace letters with numbers and symbols. For example: 'A dove of peace is here.' where 'e'='3', 'i'='1' and 'o'='0' becomes 'A d0v3 0f p3ac3 1s h3r3.' which becomes 'ad0v30fp3ac31sh3r3.'")
    . ' <li>'
    . _("If you are inventing a new passphrase that you have never used before, you should consider writing it down until you are comfortable that you will not forget it.")
    . '&nbsp;'
    . _("If you do choose to write down your passphrase, be careful about where you store it, and destroy your paper copy as soon as you are sure that you will remember your passphrase.")
    . '</ul>';


/**
 * $Id: what_passphrase.php,v 1.6 2003/11/03 19:46:49 brian Exp $
 *
 * $Log: what_passphrase.php,v $
 * Revision 1.6  2003/11/03 19:46:49  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.5  2003/10/30 20:04:42  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.4  2003/10/28 23:56:27  walter
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