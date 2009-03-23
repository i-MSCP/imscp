<?php
/**
 * passpop.mod
 *-----------
 * GPG plugin passphrase submission module file,
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Aaron van Meerten
 *
 * $Id$
 */
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in gpg_pop_init.php, exiting abnormally";
}
global $color, $onload;
global $username;
global $pserr, $hiddenvars;
global $debug;
$use_signing_key_id = getPref ($data_dir, $username, 'use_signing_key_id');

$no_signing_passwd = getPref ($data_dir, $username, 'no_signing_passwd');

sqgetGlobalVar('username',  $username,      SQ_SESSION);
sqgetGlobalVar('psaction', $psaction);
sqgetGlobalVar('addbasepath',$addbasepath);
?>
<script language="javascript">
    niceClose = false;

    function doClose() {
        if (!niceClose) {
            window.opener.sendClicked = false;
        }
    }
</script>

<?php
echo '<script language=JavaScript src=';
if (file_exists('../plugins/gpg/js/gpgsubmitpass.js')){
   echo "../plugins/gpg/js/gpgsubmitpass.js";
}elseif (file_exists('js/gpgsubmitpass.js')){
   echo "js/gpgsubmitpass.js";
} elseif (file_exists('../js/gpgsubmitpass.js')) {
   echo "../js/gpgsubmitpass.js";
} else echo '></script>' . _("script file not found, exiting abnormally.");
echo '></script>';

//Virtual Keyboard plugin support
$vkeyboard=false;
if (file_exists(SM_PATH . 'plugins/vkeyboard/vkeyboard.js')) {
	$vkeyboard=true;
	echo '<script language=JavaScript src=' . SM_PATH . 'plugins/vkeyboard/vkeyboard.js></script>';
}

switch($psaction) {
    case 'sign':
        $title=_("GPG Signing Initiating");
        if (gpg_is_passphrase_cacheable()) {
            $onclick='';
            $inputtype = "'submit'";
        } else {
            $inputtype = "'button'";
            $onclick="gpg_nocache_sign_click(this)";
        }
        break;
    case 'signdraft':
        $title=_("GPG Draft Signing Initiating");
        if (gpg_is_passphrase_cacheable()) {
            $onclick='';
            $inputtype = "'submit'";
        } else {
            $inputtype = "'button'";
            $onclick="gpg_nocache_signdraft_click(this)";
        }
        break;
    case 'encrsign':
        $title=_("GPG Encrypt and Sign Initiating");
        if (gpg_is_passphrase_cacheable()) {
            $onclick='';
            $inputtype = "'submit'";
        } else {
            $inputtype = "'button'";
            $onclick="gpg_encrsign_submit()";
        }
        break;
    case 'decrypt':
        $title=_("GPG Decryption Initiating");
        $inputtype = "'button'";
        $onclick="gpg_decrypt_submit()";
        break;
    case 'delete':
        $title=_("Confirm Delete Key");
        $inputtype="'button'";
        $onclick="gpg_delete_submit()";
        break;
    case 'deletepair':
        $title=_("Confirm Delete Keypair");
        $inputtype="'button'";
        $onclick="gpg_delete_pair_submit()";
        break;
}

if ($debug) {
    echo "use_signing_key_id = $use_signing_key_id<br>";
    echo "no_signing_passwd = $no_signing_passwd<br>";
}

echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" "
    . "vlink=\"$color[7]\" alink=\"$color[7]\" $onload onunload=\"doClose();\">\n";

echo "<table width='100%' border=0 cellpadding=2>\n"
    . '<tr>'
    . "<td bgcolor=\"$color[9]\" align=center>"
    . "<strong>$title</strong>"
    . '</td>'
    . "</tr>\n"
    . '<tr><td><hr></td></tr>'
    . '<tr>'
    . "<td>\n";


echo"<form name=main method='post'";
if ($onclick != '') { echo " onsubmit=\"niceClose = true; $onclick; return false;\""; }
echo ">";

if (gpg_is_passphrase_cacheable() && $psaction!='decrypt' && $psaction!='delete' && $psaction!='deletepair') {
    echo '<input type="hidden" name="MOD" value="cachepass">';
}

if ($addbasepath) {
    echo "<input type=\"hidden\" name=\"addbasepath\" value=\"$addbasepath\">";
}

if ($no_signing_passwd != 'true') {

    if (gpg_https_connection ()) {
    if ($pserr) {
        echo '<font color=red>' . $pserr . '</font><p>';
    }
       echo
            "<input type='hidden' name='psaction' value='$psaction'>"
          . $hiddenvars
          . _("Enter Passphrase")
          . ':'
          . '<input type="password" name="passphrase" onkeydown="if
(event.keyCode == 13) handled=true" onblur="handled=false">'
          . "<p align='center'>"
          . "<input type=$inputtype value='"._("Submit")."'";
      if ($onclick != '') { echo " onclick='$onclick'"; }
      echo "></p>";
      if ($vkeyboard) {
	      echo '<a href="#" onclick="openwindow(\'passphrase\')">' . _("Virtual Keyboard") . '</a><p>';
      }
    } else {
       echo
            '<p>'
          . _("You are not connected using a secure connection.")
          . '<br>'
          . _("Signing functions not allowed from an insecure connection.")
          . "<p align='center'><input type='button' value='"
          . _("Close Window")
          . "' onclick='window.close()'></p>";
    }
}

echo '</form>';
echo "<script language=JavaScript>\n<!--\n\nvar addbasepath='$addbasepath';\n\n";
echo "\n//-->\n</script>\n";

/*
 * $Log: passpop.mod,v $
 * Revision 1.23  2005/11/10 16:36:22  ke
 * - patch to cleanly close out of passphrase prompt page provided by Jonathan Angliss
 *
 * Revision 1.22  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.21  2004/08/23 00:16:31  ke
 * -applied fix for javascript close missing parenthesis (Thanks to Kevin Semande)
 * bug 217
 *
 * Revision 1.20  2004/04/21 17:57:04  ke
 * -added check for virtual keyboard plugin (vkeyboard)
 * -added link to pop up the virtual keyboard
 * -still must click "Close" instead of "Login", and then submit on our dialog
 * bug 168
 *
 * Revision 1.19  2004/02/17 22:53:57  ke
 * -fixed typo bug
 *
 * Revision 1.18  2004/01/17 00:28:06  ke
 * -E_ALL fixes
 *
 * Revision 1.17  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.16  2003/11/18 14:45:48  ke
 * -removed hardcoded debug
 *
 * Revision 1.15  2003/11/17 23:27:01  ke
 * -changed no_signing_pass check to != 'true' instead of =='false'
 * -makes no_signing_pass option no longer needed in preferences
 * bug 110
 *
 * Revision 1.14  2003/11/12 17:36:58  ke
 * -added code to allow submission correctly when enter key is pressed
 * bug 108
 *
 * Revision 1.13  2003/11/04 21:03:43  ke
 * -moved body tag into this file, out of gpg_pop_init
 *
 * Revision 1.12  2003/11/03 18:40:35  ke
 * -fixed passpop to allow gpgdecrypt to cache its own passphrase
 *
 * Revision 1.11  2003/11/03 17:34:03  ke
 * -changed to use abstracted functions for checking cached passphrase
 * bug 66
 *
 * Revision 1.10  2003/11/03 15:52:37  ke
 * -added titling HTML to this module, removed from gpg_pop_init
 *
 * Revision 1.9  2003/11/01 21:48:05  brian
 * Multiple Changes:
 * - removed $msg strings and gpg_Makepage functionality
 * - localized remaining strings
 * @todo fix page header for passphrase pop-up
 *
 * Revision 1.8  2003/10/30 21:12:56  brian
 * fixed problems apparent in the xgettext index
 * Bug 35
 *
 * Revision 1.7  2003/10/30 21:10:57  brian
 * fixed problems apparent in the xgettext index
 * Bug 35
 *
 * Revision 1.6  2003/10/30 20:28:28  ke
 * -changed single to double quotes in internationalized strings
 * Bug 35
 *
 * Revision 1.5  2003/10/27 19:33:30  ke
 * -added addbasepath javascript and hidden variable to allow submission to proper place
 * bug 82
 *
 * Revision 1.4  2003/10/11 21:35:48  ke
 * -Added signdraft case for running save draft javascript
 *
 * Revision 1.3  2003/10/10 19:01:46  ke
 * -Added ability to be called from keyview for deletion of keys
 *
 * Revision 1.2  2003/10/07 23:58:38  ke
 * -Changed dialog prompts depending on where it's called from
 *
 * Revision 1.1  2003/10/07 22:19:37  ke
 * -consolidated passphrase popup dialog
 * -created from sign_init.mod
 * -can be called from decrypt, encrypt&sign now, sign on send
 *
 * Revision 1.21  2003/10/07 19:25:03  ke
 * -Added error message option above the passphrase textbox
 *
 * Revision 1.20  2003/09/30 00:34:49  ke
 * -Internationalized strings in sign_init.mod
 * bug 35
 *
 * Revision 1.19  2003/09/29 16:12:26  ke
 * -Removed sign_functions includes (no longer needed in this module)
 * -Added preference check to change behavior if user is not caching passphrase
 *
 * Revision 1.18  2003/09/26 11:02:58  brian
 * added back in log comments that were removed
 *  by rolling back, for completeness
 *
 * Revision 1.17  2003/09/26 01:57:47  ke
 * - Rolled sign_init.mod back interface changes,
 *   turned back into a popup window.
 *   Still need to handle non-caching options
 * bug 40
 *
 * Revision 1.16  2003/09/24 18:42:16  ke
 * -added initial hooks to handle attachments in sign_init, mainly for debugging purposes
 *
 * Revision 1.15  2003/09/23 22:49:55  ke
 * - Set submission for encrypt&sign back to compose.php
 * - Places body text in signbody session variable
 * Bug 55
 *
 * Revision 1.14  2003/09/18 22:23:32  ke
 * -changed sign_init module to take submission of compose.php page
 * -added hidden variables to pass through submitted variables
 * -added submission to compose.php (for encrypt&sign) or gpgsign module (for sign only)
 * Bug 55
 *
 * Revision 1.13  2003/04/07 17:42:31  brian
 * added https test for signing with passphrase
 * bug 7 fixed
 *
 * Revision 1.12  2003/04/02 20:48:39  brian
 * fixed spelling errors using aspell
 * TODO - check grammer and sentence structure manually
 * Bug 18
 *
 * Revision 1.11  2003/04/01 17:45:02  brian
 * updated to remove the onsubmitp'return false' in the form tag
 *
 * Revision 1.10  2003/04/01 07:11:42  brian
 * fixed includes to reflect different calling directories
 *
 * Revision 1.9  2003/03/31 14:12:00  brian
 * added require_once statement to require signing functions file.
 * change needed to use generalized pop-up window function
 *
 * Revision 1.8  2003/03/28 13:29:34  brian
 * - changed functions from gpgsignsubmit and gpgsigninit to
 *   gpg_pop_init and gpg_pop_submit
 *   to better abstract them for use in decryption
 * - manually added header block and Log: entries to file
 * Bug 8
 *
 * Revision : 1.7 2003/3/13 20:29:25 'tyler'
 * - disabled the use of 'enter' or 'return' when entering passphrase. This
 * - follows the coding style of Squirrelspell and ensures an onclick()
 *
 * Revision : 1.6 2003/3/13 20:7:18 'tyler'
 * - building the <body> tag for onload() needs to be done in the makeWindow function otherwise we get two body tags and the onload() version doesnt work
 *
 * Revision : 1.5 2003/3/13 15:41:11 'tyler'
 * - onload() is for auto_sign...onclick() is for no auto_sign
 *
 * Revision : 1.4 2003/3/13 4:6:6 'brian'
 * - modified to correctly accept passphrase for message signing
 * - added checks for using the signing feature and
 *   needing passphrase for secret key
 *
 * Revision : 1.3 2003/3/12 4:53:53 'tyler'
 * - if autosign preference is set true then don't ask for passphrase
 *
 * Revision : 1.2 2003/3/11 22:0:40 'tyler'
 * - initial work to accept passphrase in the pop-up window
 *
 * Revision : 1.1 2003/3/11 4:10:34 'tyler'
 * - Main window we pop to grab the passphrase before signing
 */
?>
