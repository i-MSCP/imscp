<?php


echo     '<h2>'
    . _("What is a good key strength?")
    .   '</h2>';

echo  "<p \>\n"
    . _("Key strength is a measure of how difficult it is for an eavesdropper to compromise the security of your encrypted messages.")
    . '&nbsp;'
    . _("Higher strength keys (those with more 'bits') are harder to compromise, but they also take more time to use.  It will take longer to decrypt and sign with a stronger key.")
    . "<p \>\n"
    . _("A 1024 bit key is often acceptable, 2048 is generally seen as a good compromise between security and usability, and 4096 is very strong and can be noticeably slower.");


/**
 * $Id$
 *
 * $Log: what_keystrength.php,v $
 * Revision 1.7  2004/01/18 15:25:05  brian
 * - standardized strings to minimize translation
 *
 * Revision 1.6  2003/11/21 19:27:47  brian
 * -fixed typos in keystrength help
 * Bug 70
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
 **/
?>