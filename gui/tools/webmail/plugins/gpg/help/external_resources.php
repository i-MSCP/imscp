<?php

echo     '<h2>'
     . _("External Resources")
     .   '</h2>';

echo    _("This page provides links to external resources about the technologies used by the GPG Plugin.")
    . "<br><ul>\n";

echo "<li>\n"
    . '<a href="http://www.gnupg.org/">The GnuPG Home Page</a>';

echo "<li>\n"
    . '<a href="http://www.openpgp.org/">The OpenPGP Alliance</a>';

echo "<li>\n"
    . '<a href="http://docs.sun.com/source/816-6154-10/">Introduction to Public Key Cryptography</a>';

echo "<li>\n"
    . '<a href="http://www.faqs.org/rfcs/rfc2440.html">RFC 2440 - OpenPGP Message Format</a>';

echo "<li>\n"
    . '<a href="http://www.ietf.org/internet-drafts/draft-ietf-openpgp-rfc2440bis-09.txt">RFC 2440 updates draft - October 2003</a>';

echo "<li>\n"
    . '<a href="http://www.faqs.org/rfcs/rfc3156.html">RFC 3156 - MIME Security with OpenPGP</a>';

echo "<li>\n"
    . '<a href="http://www.faqs.org/rfcs/rfc1847.html">RFC 1847 - Security Multiparts for MIME: Multipart/Signed and Multipart/Encrypted</a>';

echo "<li>\n"
    . '<a href="http://www.faqs.org/rfcs/rfc822.html">RFC 2822 - Standard for Internet Message Format - replaces RFC 822</a>';

echo "<li>\n"
    . '<a href="http://www.faqs.org/rfcs/rfc2015.html">RFC 2015 - MIME Security with Pretty Good Privacy (PGP) (deprecated)</a>';

echo "<li>\n"
    . '<a href="http://cert.uni-stuttgart.de/archive/ietf-openpgp/2001/11/msg00034.html">IETF Archive - MIME Polymorphism problems</a>';

echo "\n</ul>";


/**
 * $Id: external_resources.php,v 1.3 2003/12/03 17:30:31 brian Exp $
 *
 * $Log: external_resources.php,v $
 * Revision 1.3  2003/12/03 17:30:31  brian
 * changed RFC 822 to RFC 2822
 *
 * Revision 1.2  2003/11/22 15:25:41  brian
 * - added OpenPGP Alliance to external resources page
 * Bug 70
 *
 * Revision 1.1  2003/11/22 15:09:01  brian
 * - added external resources page
 * Bug 70
 *
 */
?>