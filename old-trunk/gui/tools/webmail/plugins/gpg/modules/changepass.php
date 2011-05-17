<?php

//include the gpg system header, so's everything will be in place.
//Have to chdir so included includes will work.
chdir("../");
require_once("gpg_module_header.php");
require_once('gpg_keyring.php');

$fpr = $_GET['fpr'];
if (array_key_exists('err',$_GET)) {
	$err = $_GET['err'];
} else { $err = false; }

echo '<link rel="STYLESHEET" type="text/css" href="../js/bar-styles.css">';
// ===============================================================
$section_title = _("Change Passphrase");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================


echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
     . '<tr><td>';

echo _("You can change your passphrase from this screen.  More help will be available in the future.");

if (! gpg_https_connection()) {
    $notSecure = true;
    $err[] = _("You are not using a secure connection.")
        . '&nbsp;'
        . _("SSL connection required to generate keypair.");
    require_once(SM_PATH.'plugins/gpg/modules/keyring_main.php');
    exit;
}

if ($err)
{
    echo '<p>'
         . '<font color="red">';
    echo _("Error: ");
    echo '<br>'
         . $err
         . '</font><br>';
}

echo '</td></tr>';

echo '<tr><td>&nbsp;</td></tr>';

echo '<FORM METHOD="POST" action="keyring_main.php">';

echo '<input type="hidden" name="fpr" value="'  . $fpr . '">';
echo '<input type="hidden" name="pos" value="'  . htmlspecialchars($_GET["pos"]) . '">';
echo '<input type="hidden" name="sort" value="' . htmlspecialchars($_GET["sort"]) .'">';
echo '<input type="hidden" name="desc" value="' . htmlspecialchars($_GET["desc"]) . '">';
echo '<input type="hidden" name="srch" value="' . htmlspecialchars($_GET["srch"]) . '">';
echo '<input type="hidden" name="ring" value="' . htmlspecialchars($_GET["ring"]) . '">';

echo '</table>';

echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">';
echo '<tr><td>';

echo '<b>';
echo _("Please type your old passphrase: ");
echo '</b>';

echo '</td><td>&nbsp;</td>';

echo '<td>';

echo '<input type="password" name="oldpassphrase" title="Old Passphrase" required="true" id="oldpw" size="50">'; // id="oldpw" size="50" limit="100" progress="false" nolimit="true">';

echo '</td></tr>';

echo '<tr><td>';

echo '<b>';
echo _("Enter New Passphrase:");
echo '</b>';

echo '</td>';

echo '<td>&nbsp;</td>';
echo '<td>';

echo '<input type="password" title="New Passphrase" name="passphrase" id="passphrase" size="50" limit="100" compare="passphrase2" required="true" progress="true" nolimit="true">';

echo '</td></tr>';

echo '<tr>';

echo '<td>';

echo '<b>';
echo _("Retype New Passphrase:");
echo '</b>';

echo '</td>';

echo '<td>&nbsp;</td>';

echo '<td>';

echo '<input type="password" title="Second New Passphrase" name="passphrase2" id="passphrase2" size="50" limit="100" required="true" progress="true" nolimit="true">';

echo '</td></tr>';
echo '<tr><td colspan="2">';



echo '<input type=submit name="changepass" value="' . _("Change Passphrase") . '" Xonclick="return checkpassphrase(this.form);">'
   . '<input type=submit name="can" value="' . _("Cancel") . '">';

echo '</td></tr></table>';

require_once('gpg_module_footer.php');

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
 * $Log: changepass.php,v $
 * Revision 1.6  2005/06/09 14:58:05  ke
 * - added needed stylesheet for status bar on new passphrase
 * - changed IDs on passphrases to look like genkey's passphrases
 *
 * Revision 1.5  2004/02/17 22:46:36  ke
 * -added javascript headers for progress bars on change passphrase
 * bug 64
 *
 * Revision 1.4  2003/11/03 19:57:09  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.3  2003/10/30 20:28:59  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.2  2003/10/29 00:20:17  walter
 * - localized text by sentences
 * Bug 35
 * -  updated Help structure
 * Bug 79
 *
 * Revision 1.1  2003/10/10 19:08:17  ke
 * -adding module for change passphrase interface
 *
 * Revision 1.6  2003/08/13 06:49:42  vermette
 * minor fix
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
