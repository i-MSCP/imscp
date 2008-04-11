<?php
/**
 * genkey.php
 * ----------------
 * GPG Key Generation page
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Joshua Vermette
 * @author Aaron Van Meerten
 * @author Brian Peterson
 *
 * $Id: genkey.php,v 1.33 2005/07/27 14:07:49 brian Exp $
 */

if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in genkey.php, exiting abnormally";
}

//Cancelling?

if (isset($_POST['can'])) {
    if ($_POST['can']) {
        //Send them back to getting started
        require_once(SM_PATH.'plugins/gpg/modules/keyring_main.php');
        exit;
    }
}

//include the gpg system header, so's everything will be in place.
//Have to chdir so included includes will work.
//chdir("../");
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');
require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');

//Are they on a secure connection?
if (! gpg_https_connection()) {
    $notSecure = true;
    $err[] = _("You are not using a secure connection.")
        . '&nbsp;'
        . _("SSL connection required to generate keypair.");
    require_once(SM_PATH.'plugins/gpg/modules/keyring_main.php');
    exit;
}

//Output any error we found.
require(SM_PATH.'plugins/gpg/modules/gpg_err.php');

//Make the passthrough string for keyring_main.
$thru = ("pos=" . urlencode($_GET["pos"]) .
     "&sort=" . urlencode($_GET["sort"]) .
     "&desc=" . urlencode($_GET["desc"]) .
     "&srch=" . urlencode($_GET["srch"]) .
     "&ring=" . urlencode($_GET["ring"]));


echo '<link rel="STYLESHEET" type="text/css" href="../js/bar-styles.css">';




// ===============================================================
$section_title = _("GPG Options - Create a Personal Keypair");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

echo '<FORM action="genkey_keygen.php" METHOD=POST>';

echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
     . '<tr><td>';

echo  _("To sign outgoing mail and receive encrypted messages, you must have a personal Keypair.")
    . '&nbsp;<br>'
    . _("A Keypair consists of a <b>public key</b>, which people will use to send encrypted messages to you and verify your signature, and a <b>secret key</b>, which will never be shared with others.")
    . '<p>';

echo '<b>'
    . _("Do you already have a personal Keypair?")
    . '</b>';
echo
      '<br>'
    . _("Import keys to your personal keyring via: ")
    . '<a href="import_key_file.php">'
    . _("file")
    . '</a>&nbsp;'
    . _("or")
    . '&nbsp;<a href="import_key_text.php">'
    . _("text")
    . '</a>';

echo '.<br>';

echo '<p>';

echo _("Storing a private key on a remote host can be insecure if you do not trust the host!");

echo '<font size=-1>';
echo '['
    . gpg_add_help_link ( 'disc_keystore.php' );
echo _("disclaimer");
echo  '</a>'
    . ']';
echo '</font>';
echo '<br>';

 //start by searching for the user's information from the options they've entered.
 $email_address = htmlspecialchars(getPref($data_dir, $username, 'email_address'));
 $full_name = htmlspecialchars(getPref($data_dir, $username, 'full_name'));

echo '<hr>';

echo  '<table cellpadding="0" cellspacing="0" border="0" width="60%">'
    . '<tr><td bgcolor="' . $color[0] . '>'
    . '<table cellpadding="3" cellspacing="1" border="0" width="100%">'
    . '<tr><td bgcolor="' . $color[5] . '">';

echo _("Generate New Keypair");

echo '</td></tr>'
     . '<tr><td bgcolor=' . $color[4] . '>'
     . '<table cellspacing="0" cellpadding="0" border="0">'
     . '<tr><td>';

echo '<b>';
echo _("Full Name");
echo ':</b>';

echo   '</td>'
     . '<td width="5"></td><td>';

echo '<input type="text" name="full_name" id="full_name" size="25" value="'
     . $full_name
     . '" required="true" datatype="names" title="Name" >';

echo '</td></tr>';

echo '<tr><td>';

echo '<b>';
echo _("Email Address");
echo ':</b>';

echo   '</td>'
     . '<td></td><td>'
     . '<input type="text" name="email_address" id="email_address" size="25" value="'
     . $email_address
     . '" required="true" datatype="email" title="eMail" >';

echo '</td></tr>';

echo '<tr><td>';

echo '<b>';
echo _("Keystrength");
echo ':</b>';

echo '</td>'
     . '<td></td><td>'
     . '<select name="key_strength">';

 for ($i=0; $i < $GLOBALS['GPG_SYSTEM_OPTIONS']['keystrengths']; $i++) {
    $keystrength=$GLOBALS['GPG_SYSTEM_OPTIONS']['keystrength'.$i];

    echo '<option value="'
         . $keystrength
         . '"';
    if ($keystrength == $GLOBALS['GPG_SYSTEM_OPTIONS']['default_keystrength']) {
        echo ' selected';
    }
    echo ">$keystrength\n";
}

echo '</select>'
     . '<font size=-1>['
     . gpg_add_help_link ( 'what_keystrength.php' );
echo _("What's this?");
echo   '</a>'
     . ']'
     . '</font>';

echo '</td></tr><tr><td>';

echo '<b>';
echo _("Key Expires");
echo ':</b>';

echo '</td><td></td><td>'
     . '<select name="key_expires">';

  $i=0;
  echo '<option value="' . $i . '"';
    if ($GLOBALS['GPG_SYSTEM_OPTIONS']['default_keyexpires'] == $i)
    {
        echo ' selected';
    }

  echo  '>'
        . _("Never");

  $i=30;
  echo '<option value="' . $i . '"';
    if ($GLOBALS['GPG_SYSTEM_OPTIONS']['default_keyexpires'] == $i)
    {
        echo ' selected';
    }

  echo  '>'
        . $i
        . _("Days");

  $i=90;
  echo '<option value="' . $i . '"';
    if ($GLOBALS['GPG_SYSTEM_OPTIONS']['default_keyexpires'] == $i)
    {
        echo ' selected';
    }

  echo  '>'
        . $i
        . _("Days");

  $i=180;
  echo '<option value="' . $i . '"';
    if ($GLOBALS['GPG_SYSTEM_OPTIONS']['default_keyexpires'] == $i)
    {
        echo ' selected';
    }

  echo  '>'
        . $i
        . _("Days");

  $i='1y';
  echo '<option value="' . $i . '"';
    if ($GLOBALS['GPG_SYSTEM_OPTIONS']['default_keyexpires'] == $i)
    {
        echo ' selected';
    }

  echo  '>'
        . _("1 Year");

echo '</select>'
     . '<tr><td></td></tr>';

if ($GLOBALS['GPG_SYSTEM_OPTIONS']['systemrevoker']) {
echo '<tr><td>';
echo '<b>';
echo _("Add Revocation") . ':</b>';
echo '</td><td></td><td>' . "\n";
echo '<select name=usesystemrevoker>';
echo '<option value="true">' . _("Yes, allow the system revocation key to revoke this key") . '</option>';
echo '<option value="false" selected>' . _("No, do not allow the system revocation key to revoke this key") . '</option>';
echo '</select>';
echo '</td></tr>';
}
$comment=$GLOBALS['GPG_SYSTEM_OPTIONS']['default_comment'];
echo '<tr><td>';
echo '<b>';
echo _("Comment");
echo ':</b>';
echo   '</td>'
     . '<td></td><td>'
     . '<input type="text" name="comment" id="comment" size="60" value="'
     . $comment
     . '" required="false" title="Comment" >'
     . '<font size=-1>['
     . gpg_add_help_link ( 'what_keycomment.php' );
echo _("What's this?");
echo   '</a>'
     . ']'
     . '</font>';

echo '</td></tr>';

echo '<tr><td>';

echo '<b>';
echo _("Enter Passphrase");
echo ':</b>';

echo '</td><td></td><td>' . "\n"
     . '<input type="password"' . "\n"
     . '       name="passphrase"' . "\n"
     . '       id="passphrase"' . "\n"
     . '       size="80"' . "\n"
     . '       limit="100"' . "\n"
     . '       progress="true"' . "\n"
     . '       nolimit="true"' . "\n"
     . '       required="true"' . "\n"
     . '       compare="passphrase2"' . "\n"
     . '       title="First Passphrase">' . "\n";

echo '</td></tr><tr><td>';

echo '<b>';
echo _("Retype Passphrase");
echo ':</b>';

echo '</td><td></td><td>' . "\n"
     . '<input type="password"' . "\n"
     . '       name="passphrase2"' . "\n"
     . '       id="passphrase2"' . "\n"
     . '       size="80"' . "\n"
     . '       limit="100"' . "\n"
     . '       progress="true"' . "\n"
     . '       nolimit="true"' . "\n"
     . '       required="true"' . "\n"
     . '       title="Second Passphrase">' . "\n";

echo '</td></tr><tr><td></table>'
     . '<br>';

echo     '<input type=submit value="'
     . _("Create New Personal Keypair")
     .   '" Xonclick="return checkpassphrase(this.form);"> ';

echo     '<input type=submit name=can value="'
     . _("Cancel")
     .   '">';

echo   '</td></tr></table>'
     . '</td></tr></table>';


echo '</td></tr></table>';


echo '<input type="hidden" name="pos" value="'
    . htmlspecialchars($_GET["pos"])
    . '">'
    . '<input type="hidden" name="sort" value="'
    . htmlspecialchars($_GET["sort"])
    . '">'
    . '<input type="hidden" name="desc" value="'
    . htmlspecialchars($_GET["desc"])
    . '">'
    . '<input type="hidden" name="srch" value="'
    . htmlspecialchars($_GET["srch"])
    . '">'
    . '<input type="hidden" name="ring" value="'
    . htmlspecialchars($_GET["ring"])
    . '">';

echo '</form>';


require_once(SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');

echo<<<TILLEND

<script src='../js/events.js'
        language='JavaScript'
        type='text/javascript'>
<!--     //
        //   Custom methods for Event handling
       //
      //
     //   Hide JavaScript Code from Browser.
    //    Do not remove these lines of code.
   //     The code will be 'INCLUDED' at run time.
  //      Create another <SCRIPT> block if you want
 //       to use additional code.
//  -->
</script>

<script src='../js/statusBar.js'
        language='JavaScript'
        type='text/javascript'>
      <!-- //
          //   Custom methods for control and display
         //    of status bar for TEXTAREA
        //
       //     walter@torres.ws      web.torres.ws
      //
     //   Hide JavaScript Code from Browser.
    //    Do not remove these lines of code.
   //     The code will be 'INCLUDED' at run time.
  //      Create another <SCRIPT> block if you want
 //       to use additional code.
//  -->
</script>

<script src='../js/formValidation.js'
        language='JavaScript'
        type='text/javascript'>
<!--     //
        //   Self-contained form validation methods
       //    walter@torres.ws     web.torres.ws/dev
      //
     //   Hide JavaScript Code from Browser.
    //    Do not remove these lines of code.
   //     The code will be 'INCLUDED' at run time.
  //      Create another <SCRIPT> block if you want
 //       to use additional code.
//  -->
</script>
TILLEND;


/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * c-basic-offset: 4
 * End:
 */

/**
 *
 * $Log: genkey.php,v $
 * Revision 1.33  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.32  2004/06/25 17:18:36  ke
 * -fixed strings which were incorrectly internationalized (thanks gforte@udel.edu)
 * Bug 201
 *
 * Revision 1.31  2004/03/09 18:09:26  ke
 * -added Comment field with default value populated automatically
 *
 * Revision 1.30  2004/03/03 19:46:53  ke
 * -changed terminology to system revocation key
 *
 * Revision 1.29  2004/02/27 01:40:49  ke
 * -added option for default system key revocation in key gen interface
 *
 * Revision 1.28  2004/01/13 01:48:32  brian
 * fixed colors to use SM color array, per patch from Chris Wood
 *
 * Revision 1.27  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.26  2003/12/27 18:01:25  brian
 * added Walter's improved formatting of html tags
 *
 * Revision 1.25  2003/12/23 12:47:56  brian
 * removed superfluous error message
 *
 * Revision 1.24  2003/12/05 20:38:09  ke
 * -untranslated "selected" term as it is a code word in HTML, not translated text
 *
 * Revision 1.23  2003/12/05 20:34:46  ke
 * -changed &nbsp to space to allow default keystrength to be set properly
 *
 * Revision 1.22  2003/12/03 00:24:48  brian
 * added .php to disc_keystore link
 *
 * Revision 1.21  2003/12/01 16:24:59  walter
 * - modified default error message for NAME
 * Bug 119
 *
 * Revision 1.20  2003/11/22 21:13:10  brian
 * - fixed bug reported by Brad Donison
 * - needed .php after the names of help files in new help infrastructure
 *
 * Revision 1.19  2003/11/19 16:17:03  walter
 * - updated for new datatype - 'is_names'
 *
 * Revision 1.18  2003/11/08 21:41:06  walter
 * - corrected form validation for password varification
 * - corrected display of status bars
 *
 * Revision 1.17  2003/11/07 20:57:01  walter
 * - removed in script javascript for passphrase compare
 * - added INCLUDE code for passphrase compare
 *
 * Revision 1.16  2003/11/06 17:18:05  walter
 * - moved FORM tags outside TABLE
 * - corrected status bar issues
 * BUG 73
 * - added field validation
 *
 * Revision 1.15  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.14  2003/11/03 19:40:35  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.13  2003/11/01 21:59:10  brian
 * - standardized text across several pages
 * - localized remaining strings
 *
 * Revision 1.12  2003/10/30 21:05:22  brian
 * fixed problems apparent in the xgettext index
 * Bug 35
 *
 * Revision 1.11  2003/10/30 20:28:59  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.10  2003/10/30 02:17:03  walter
 * - completed localized text by sentences
 * Bug 35
 *
 * Revision 1.9  2003/10/29 00:20:17  walter
 * - localized text by sentences
 * Bug 35
 * -  updated Help structure
 * Bug 79
 *
 * Revision 1.8  2003/10/15 18:42:17  walter
 * localized button text
 * Bug 35
 *
 * Revision 1.7  2003/10/14 20:23:22  walter
 * localized text
 * Bug 35
 *
 * Revision 1.6  2003/10/07 17:31:23  walter
 * Bug 62
 *
 * Revision 1.5  2003/10/07 17:29:54  walter
 * - added external javascript method to display a status bar indicating strength of encryption passphrase.
 * - Bug 62
 *
 * Revision 1.4  2003/10/01 19:53:46  ke
 * -Added keystrengths count and keystrength0-count available keystrengths
 * -Added default_keystrength option
 * -this takes care of min, max and default
 * -Added expiration timespans back into interface
 * -Added default_keyexpires timespan, specified in gpg timespan format (# for days or 1y for year)
 * -Options of 0 (never) 30 90 180 days 1 year
 * bug 61
 *
 * Revision 1.3  2003/09/10 16:06:27  ke
 * Added 3072 bit keysize option
 *
 * Revision 1.2  2003/08/14 02:38:04  vermette
 * minor html fix
 *
 * Revision 1.1  2003/08/13 07:34:05  vermette
 * new key generation suite
 *
 * Revision 1.5  2003/07/17 07:33:07  vermette
 * time-saving modifications to hack around gpg's lack of ability to restrict output size.  Added system keyring into consolidated interface.
 *
 * Revision 1.4  2003/07/11 07:43:12  vermette
 * added search to keyring_main
 *
 * Revision 1.3  2003/07/11 06:54:03  vermette
 * keyring work.  Added chunking, first/prev/next/last, sorting, and ascending v. descending sorted view.  Also modified key table to give more info.
 *
 * Revision 1.2  2003/07/08 18:01:51  vermette
 * rename publicring.php to keyring_main.php
 *
 * Revision 1.1  2003/07/08 17:55:34  vermette
 * new pages for import functionality
 *
 * Revision 1.3  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.  The previous 'back' link now only appears if requested (new arg to makePage).  This isn't done by any means, but at most it's as broken as it was, so it's an improvement.
 *
 * Revision 1.2  2003/06/13 15:18:01  brian
 * modified to remove $msg parameter to $gpg_format_keylist fn call
 *
 * Revision 1.1  2003/04/11 14:09:10  brian
 * nitial Revision
 * display public keyring with radio 'false'
 * Bug 27
 * .
 *
 */
?>
