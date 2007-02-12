<?php
/**
 * genkey_keygen.php
 * ----------------
 * GPG Key Generation page
 * Copyright (c) 2002-2003 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Joshua Vermette
 * @author Aaron Van Meerten
 * @author Brian Peterson
 *
 * $Id: genkey_keygen.php,v 1.13 2003/11/18 18:45:42 ke Exp $
 */

if (!defined (SM_PATH)){
    if (file_exists('./gpg_functions.php')){
        define (SM_PATH , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define (SM_PATH , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define (SM_PATH , '../');
    } else echo "unable to define SM_PATH in genkey_keygen.php, exiting abnormally";
}

//Cancelling?
if ($_POST['can']) {
    //Send them back to getting started
    require_once(SM_PATH.'plugins/gpg/modules/keyring_main.php');
    return;
}

//Check passphrases.
$passphrase = $_POST['passphrase'];
$passphrase2 = $_POST['passphrase2'];
if ($passphrase != $passphrase2) {
  $err[] = _("Your passphrases do not match.")
         . '&nbsp;'
         . _("Please try again.");
  require_once(SM_PATH.'plugins/gpg/modules/genkey.php');
  return;
}

//include the gpg system header, so's everything will be in place.
//Have to chdir so included includes will work.
//chdir("../");
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');
require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');

//Are they on a secure connection?
if (! gpg_https_connection()) {
    $notSecure = true;
    $err[] = _("You are not using a secure connection.") . '&nbsp; ' . _("SSL connection required to generate keypair.");
    require_once(SM_PATH.'plugins/gpg/modules/keyring_main.php');
    exit;
}

//Make the passthrough string for keyring_main.
$thru = ("pos=" . urlencode($_GET["pos"]) .
     "&sort=" . urlencode($_GET["sort"]) .
     "&desc=" . urlencode($_GET["desc"]) .
     "&srch=" . urlencode($_GET["srch"]));

$ringThru = "ring=" . urlencode($_GET["ring"]);

include(SM_PATH.'plugins/gpg/gpg_key_functions.php');

$email_address = $_POST ['email_address'];
$full_name = $_POST ['full_name'];
$key_strength = $_POST ['key_strength'];
$key_expires = $_POST ['key_expires'];

ob_end_flush();
ob_start();
// ===============================================================
$section_title = _("GPG Options - Create a Personal Keypair");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================


echo '<table width="95%" align="center" border="1" cellpadding="2" cellspacing="0">'
     . '<tr><td>';

echo '<b>';
echo _("Generating Keypair based on:");
echo '</b>';

echo '</td></tr>';

echo '<tr><td>';

echo '<ul>';

echo '<li>';

echo '<b>';
echo _("Email Address");
echo ':</b> ';

if ($email_address) {
    echo '"'
         . htmlspecialchars($email_address)
         . '"';
} else {
    echo '<font color="red">';
    echo _("Unassigned");
    echo '</font>';
}

echo '<li>';

echo '<b>';
echo _("Full Name");
echo ':</b> ';

if ($full_name) {
    echo '"'
         . htmlspecialchars($full_name)
         . '"';
} else {
    echo '<font color="red">';
    echo _("Unassigned");
    echo '</font>';
}

echo '<li>';

echo '<b>';
echo _("Passphrase");
echo ':</b> ';

if ($passphrase && $passphrase2){
    echo 'Assigned';
} else {
    echo '<font color="red">';
    echo _("Unassigned");
    echo '</font>';
}

echo '<li>';

echo '<b>';
echo _("Key Strength");
echo ':</b> ';

echo $key_strength;

echo '<li>';

echo '<b>';
echo _("Key Expires");
echo ':</b> ';


if ($key_expires == 0) {
    echo _("Never");
} elseif ($key_expires == '1y') {
    echo _("One Year");
} else {
    echo '$key_expires';
    echo _("Days");
}

echo '</ul>';

echo '</td></tr>';

echo '<tr><td>';

echo '<h3 id="gennote">';
echo _("Your key is being generated... please be patient!");
echo '</h3>';
echo '<p />';

$comment=$GLOBALS['GPG_SYSTEM_OPTIONS']['default_comment'];
//Generate the keypair.
ob_end_flush();
ob_start();
$ret = gpg_generate_keypair($debug, $full_name, $email_address, $passphrase, $comment, $key_strength, $key_expires);

/*
echo <<<TILLEND
<script language="javascript">
<!--
gennote.style.display = "none";
-->
</script>
TILLEND;
*/

echo '</td></tr>';

echo '<tr><td>';

//Errors?
if (count($ret['errors']))
{
    //Set the flag.
    $keyErr = true;

    //If any of these fields is missing, we can guess at a more informative error message.
    //In any case, it might as well be the error, since nothing can work without it.
    //So only print the error(s) if they're all there.
    if ($email_address && $full_name && $passphrase && $passphrase2)
    {
        //Print the errror(s).
        foreach ($ret['errors'] as $error)
        {
            $err[] = _("Error: ") . htmlspecialchars($error);
        }
    }
        else
    {
        $err[] =   _("Required data missing.")
                 . _("Please")
                 . '&nbsp;'
                 . '<a href="genkey.php">'
                 . _("try again")
                 . '</a>.';
    }

    //Output any error we found.
    require_once(SM_PATH.'plugins/gpg/modules/gpg_err.php');
}
else
{

    //Messages?
    foreach ($ret['messages'] as $thing)
    {
        //echo ("<br>" . htmlspecialchars($thing));
        if (ereg("sec[[:space:]]+([[:digit:]]+[R|D|G])/([[:alnum:]]+)[[:space:]]+(.*)", $thing, $tmp))
            $key_id = $tmp[2];
    }

    //Get the key.
    //XXX - This seems hacky... isn't there a way to get the fpr back from a creation?
    $ring = new gpg_keyring();
    $err = $ring->fetchKeys($key_id, "");
    if (! $err['errors'][0])
    {
        $fList = array_keys($ring->keys);
        $fpr = $fList[0];
        $key = $ring->keys[$fpr];
    }

    echo '<font size="+1">';
    echo '<b>';
    echo _("Success!")
       . _("A new personal Keypair has been generated.");
    echo '</b>';
    echo '</font>';

    if ($key)
    {
        echo '[';
        echo "<a href=\"keyview.php?fpr=$fpr&$thru&ring=secret\">";
        echo _("View Key");
        echo '</a>';
        echo ']';
    }

}

echo '</td></tr>';

echo '<tr><td align="center">';

echo '<b>';
echo '<a href="keyring_main.php?' . $thru . '&' . $ringThru . '">';
echo _("Back to Keyring Management");
echo '</a>';
echo '</b>';

echo '</td></tr></table>';
ob_end_flush();
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');

/**
 * $Log: genkey_keygen.php,v $
 * Revision 1.13  2003/11/18 18:45:42  ke
 * -fixed link to View Key (double quotes instead of single quotes)
 * -fixed equality of != rather than !==
 * -fixed friendly message during key generation
 * bug 84
 *
 * Revision 1.12  2003/11/12 16:51:58  brian
 * syntax fixes of if/else blocks
 *
 * Revision 1.11  2003/11/06 18:15:05  brian
 * fixed syntax error on line 53
 *
 * Revision 1.10  2003/11/06 18:13:48  brian
 * fixed syntax error on line 53
 *
 * Revision 1.9  2003/11/06 18:10:58  brian
 * Changed gpg_err string in line 39
 *
 * Revision 1.8  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * ----------------------------
 * Manually added Log entries
 *
 * revision 1.7
 * date: 2003/11/03 19:40:35;  author: brian;  state: Exp;  lines: +1 -1
 * minor wording changes in advance of translation.
 * Bug 35
 * ----------------------------
 * revision 1.6
 * date: 2003/10/30 20:38:21;  author: brian;  state: Exp;  lines: +104 -102
 * modified all localized strings to use double quotes.
 * Bug 35
 * ----------------------------
 * revision 1.5
 * date: 2003/10/30 02:17:03;  author: walter;  state: Exp;  lines: +11 -1
 * - completed localized text by sentences
 * Bug 35
 * ----------------------------
 * revision 1.4
 * date: 2003/10/29 00:20:17;  author: walter;  state: Exp;  lines: +165 -106
 * - localized text by sentences
 * Bug 35
 * -  updated Help structure
 * Bug 79
 * ----------------------------
 * revision 1.3
 * date: 2003/10/04 00:57:39;  author: ke;  state: Exp;  lines: +1 -1
 * -Added check for contents of error array in $err instead of only return from keyring function
 * ----------------------------
 * revision 1.2
 * date: 2003/10/01 19:56:40;  author: ke;  state: Exp;  lines: +14 -4
 * -added expiration date to fields displayed after generation
 * -Changed call to gpg_generate_keypair to include expiration date and system default_comment
 * bug 61
 * ----------------------------
 * revision 1.1
 * date: 2003/08/13 07:34:05;  author: vermette;  state: Exp;
 * new key generation suite
 */
?>
