<?php
/**
 * options_main.mod
 * ----------------
 * GPG module
 * Copyright (c) 2002-2005 Baverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Default page called when accessing GPG plugin options.
 *
 * $Id: options_main.mod,v 1.27 2005/07/27 14:07:49 brian Exp $
 */

/**
 * assume that system prefs and user prefs have already been loaded
 * we'll use variables from these to set the defaults
 *
 */

// ===============================================================
$section_title = _("GPG Options - Main Options");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

echo
     '<p>'
   . _("From this screen, you may select options for setting up the GPG Plugin.")
   . '<br>'
   . _("The GPG Encryption Plugin will allow you to encrypt, sign, and decrypt messages in accordance with the OpenPGP standard for email security and authentication.")
   . '</p><p>'
   . _("Please choose the GPG Encryption Plugin options you wish to set up:")
   . '</p>'
   . '<ul>'
   . '<li><a href="modules/getstart.php">'
   . _("Getting Started with the GPG Plugin") . '</a></li>'
   . '<li><a href="gpg_options.php?MOD=general">'
   . _("General Options about how the plugin works") . '</a></li>'
   . '<li><a href="modules/keyring_main.php">'
   . _("Keyring Management Functions") . '</a></li>';

echo '<p>'
  . '<li><a href="gpg_help_base.php">'
  . _("GPG Plugin Help") . '</a></li>';

echo "</ul>\n";

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 */

/**
 *
 * $Log: options_main.mod,v $
 * Revision 1.27  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.26  2003/11/22 15:56:55  brian
 * changed README link to link to GPG Plugin help
 * Bug 70
 *
 * Revision 1.25  2003/11/01 22:00:43  brian
 * - standardized text across several pages
 * - localized remaining strings
 * - removed $msg strings and Makepage fn
 *
 * Revision 1.24  2003/10/30 20:20:37  ke
 * -changed single quotes to double quotes in internationalized strings
 * bug 35
 *
 * Revision 1.23  2003/09/29 20:45:04  brian
 * repointed key management options to keyring_main.php after removal of keymgmt.mod
 * Bug 34
 *
 * Revision 1.22  2003/08/14 02:35:51  vermette
 * replaced getstart.mod with getstart.php
 *
 * Revision 1.21  2003/07/21 14:28:19  brian
 * - minor wording changes per J Nanninga request
 * - localization of all strings
 * Bug 35
 *
 * Revision 1.20  2003/07/08 13:23:48  brian
 * added smoptions target to backlink in gpg_makepage fn
 *
 * Revision 1.19  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.  The previous 'back' link now only appears if requested (new arg to makePage).  This isn't done by any means, but at most it's as broken as it was, so it's an improvement.
 *
 * Revision 1.18  2003/05/09 20:30:00  brian
 * Rearranged to streamline documentation
 * Bug 34
 *
 * Revision 1.17  2003/04/11 03:21:16  brian
 * added selection for keypair generation
 * Bug 14
 *
 * Revision 1.16  2003/04/11 02:43:01  tyler
 * - added initial support for keyring management and modified key display
 *
 * Revision 1.15  2003/04/02 22:12:23  brian
 * fixed grammer error
 * Bug 18
 *
 * Revision 1.14  2003/04/02 20:48:39  brian
 * fixed spelling errors using aspell
 * TODO - check grammer and sentence structure manually
 * Bug 18
 *
 * Revision 1.13  2003/04/02 15:03:36  brian
 * fixed typo and removed public key reference from key upload link
 *
 * Revision 1.12  2003/04/02 14:39:02  brian
 * added README link to options screen
 *
 * Revision 1.11  2003/03/12 18:26:04  brian
 * rearranged and reworded items
 *
 * Revision 1.10  2003/03/12 03:57:58  tyler
 * - added signingkey.mod
 * - add the signingkey.mod to the options window
 * - converted gpgsign.mod to honor the preferences
 *
 * Revision 1.9  2003/02/22 20:11:40  brian
 * Fixed typo in option list
 *
 * Revision 1.8  2003/02/20 00:24:27  brian
 * added module link for trusted key id
 *
 * Revision 1.7  2003/01/24 16:49:22  brian
 * Fixed Typos
 *
 * Revision 1.6  2003/01/22 23:17:33  brian
 * Added general options link to main options  page.
 *
 * Revision 1.5  2002/12/06 19:43:18  brian
 * added keyring link to options page
 *
 * Revision 1.4  2002/12/05 21:11:33  brian
 * cleaned up template display and fixed $log entry, manually appended log history
 *
 *
 * ----------------------------
 * revision 1.3
 * date: 2002/12/05 20:28:09;  author: brian;  state: Exp;  lines: +26 -6
 * added comments to make more clear what the next steps are, cleaned up the formatting
 * ----------------------------
 * revision 1.2
 * date: 2002/12/05 19:51:48;  author: brian;  state: Exp;  lines: +10 -3
 * added log tag
 * ----------------------------
 * revision 1.1
 * date: 2002/12/05 16:47:55;  author: brian;  state: Exp;
 * branches:  1.1.1;
 * Initial revision
 * ----------------------------
 *
 */

?>
