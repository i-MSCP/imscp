<?php

//include the gpg system header, so's everything will be in place.
//Have to chdir so included includes will work.
//chdir("../");

if (!defined (SM_PATH)){
    if (file_exists('./gpg_functions.php')){
        define (SM_PATH , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define (SM_PATH , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define (SM_PATH , '../');
    } else echo "unable to define SM_PATH in keyring_main.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');
require_once(SM_PATH.'plugins/gpg/gpg_config.php');
//Rectify ring.
$ringName = ($_GET["ring"] ? $_GET["ring"] : $_POST["ring"]);
//hack to reset keyring to 'all' if new keys were being viewed.
if ($ringName == 'new') { $ringName = 'all'; }

//Make the passthrough string for keyring_main.
sqgetGlobalVar('pos',$pos);
sqgetGlobalVar('sort',$sort);
sqgetGlobalVar('desc',$desc);
sqgetGlobalVar('srch',$srch);
$thru = ("pos=" . urlencode($pos) .
"&sort=" . urlencode($sort) .
"&desc=" . urlencode($desc) .
"&srch=" . urlencode($srch) .
"&ring=" . urlencode($ringName));


$err = array();

if ($_POST['em'] && $_POST['fpr']) {
    require_once(SM_PATH.'plugins/gpg/gpg_options_header.php');

    //Get the text, set it as the email body.
    $ring = new gpg_keyring();
    $text = $ring->getExportText($_POST['fpr'], $ringName);

    //Go to compose if appropriate
    if ($text) {
    //This appears to be a hack to get around the fact that compose.php
    //doesn't properly specify the target of its forms and links...
    global $gpg_export;
    $gpg_export=1;

    //Set vars for compose
    $_POST['body'] = $text;

    //go there!
    Header("Location: " . get_location() . "/../../../src/compose.php?body=" . urlencode($text));
    exit;
    }

    $err[] = _("Could not export your key.  Please contact gpg development.");
}

if ($_POST['disp'] && $_POST['fpr']) {
    require_once(SM_PATH.'plugins/gpg/gpg_options_header.php');
    require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');

    $section_title = _("GPG Options - Display Key");
    echo gpg_section_header ( $section_title, $color[9] );

    //Get the text of the key
    $ring = new gpg_keyring();
    $text = $ring->getExportText($_POST['fpr'], $ringName);

    if ($text) {
        echo "<pre>$text</pre>";
    }
    echo '<form method="POST" action="keyring_main.php" name=keydisp>';
    echo '<input type="hidden" name="fpr" value="' . urlencode($_POST['fpr']) . '">';
    echo '<input type="hidden" name="pos" value="' . urlencode($_POST["pos"]) . '">';
    echo '<input type="hidden" name="sort" value="' . urlencode($_POST["sort"]) . '">';
    echo '<input type="hidden" name="desc" value="' . urlencode($_POST["desc"]) . '">';
    echo '<input type="hidden" name="srch" value="' . urlencode($_POST["srch"]) . '">';
    echo '<input type="hidden" name="ring" value="' . urlencode($_POST["ring"]) . '">';
    echo '<input type="submit" name="can" value="' . _("Done") . '">';
    echo '</form>';
    require_once (SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');
    exit;
}


//Need to tell them to go secure?
if ($notSecure) {
    $err[] = _("This action can only be performed on a secure connection (https).  Please change the 'http' in your address bar to 'https', and try again.");
}

//user clicked Change Passphrase on secret keyview
if ($_POST["cp"] && $_POST["fpr"]) {
    if ($ringName == "secret") {
    //could call passphrase change rendering function here, but we'll use a location header for now
    Header("Location: changepass.php?" . $thru . '&fpr=' . $_POST['fpr']);
    exit;
    }
    $err[] = _("Cannot change passphrase on key: ") . $_POST['fpr'];
}
if ($_POST["changepass"] && ($_POST["fpr"] or $_GET["fpr"])) {
    sqgetGlobalVar('oldpassphrase',$oldpassphrase);
    sqgetGlobalVar('passphrase',$newpassphrase);
    sqgetGlobalVar('passphrase2',$newpassphrase2);
    sqgetGlobalVar('fpr',$fpr);
    $ring = new gpg_keyring();
    $return = $ring->fetchKeys($fpr, $ringName);
    if ($return['errors'][0]) {
        $err=$return['errors'];
    }
    //Get the key.
    $key = $ring->getKey($fpr);
    if (!$oldpassphrase) {
        $err[] = _("No old passphrase passed.  Possibly blank password?");
    } else {
        if ($debug) { echo "checking passphrase on $fpr: " . $key['id']; }
        $return = gpg_verify_passphrase($oldpassphrase, $key['id']);
        if ($return['verified'] == 'true') {
        include_once(SM_PATH.'plugins/gpg/gpg_key_functions.php');
            if ($debug) { echo "trying to change" . '<P>'; }
            $return = gpg_change_passphrase($key['id'], $oldpassphrase, $newpassphrase);
            if ($return['errors'][0]) {
                $err=array_merge($err, $return['errors']);
            }
            if ($return['output']) {
                print_r($return['output']);
            }
        } else {
            $err[] = _("Bad Passphrase.");
        }
    }
    if ($err) {
      if (!$debug) {
            Header("Location: changepass.php?" . $thru . '&fpr=' . $_POST['fpr'] . '&err=' . urlencode(implode('',$err)));
      }
            exit;
    }
}
//Take any action requested (only one).
if (($_POST["deletekey"]=='true' || $_POST["deletepair"]=='true') && $_POST["fpr"]) {
    //Deleting a key.
    //What type of deletion?
    if (($ringName == "secret") && ($_POST['deletepair']=='false')) $type = "private";
    else $type = "all";

    $ring = new gpg_keyring();
    if ($type == 'private' || $_POST['deletepair']=='true') {
    $passphrase = $_POST['passphrase'];
        $return = $ring->fetchKeys($_POST["fpr"],'secret');
    $key = $ring->getKey($_POST["fpr"]);
    if (!$key) {
        $err[] = _("No secret key found.");
    }
    if ($GLOBALS['GPG_SYSTEM_OPTIONS']['requirepassphraseonkeydelete']=='true') {
    $return = gpg_verify_passphrase($passphrase,$key['id']);
    if ($return['verified'] != 'true') {
        $err[] = _("Bad Passphrase.");
	if ($debug) {
		$err[] = "KeyID: " . $key['id'] ." failed to delete.";
	}
    }
    }
    }
    if ($err[0]) {
    Header("Location: keyview.php?" . $thru . '&fpr=' . $_POST['fpr'] . '&err=' . urlencode(implode('',$err)));
    exit;
    } else {
    //Do it
        $ret = $ring->deleteKey($_POST["fpr"], $type);
        if ($ret) $err[] = $ret;
        unset($_POST["fpr"]);
        unset($ring);
    }
}

else if ($_POST["textadd"] && $_POST["keystring"]) {
    //Importing a key via text.
    $ring = new gpg_keyring();
    $ret = $ring->importKey_text($_POST["keystring"]);
    if ($ret['newkeys']) {
        $ringName = 'new';
    }
    $info = $ret['info'];
    if ($ret['errors'][0]) $err = $ret['errors'];
}

else if ($_POST["fileadd"]) {
    if (is_uploaded_file($_FILES['keyfile']['tmp_name'])) {
    //Importing a key via text.
    $ring = new gpg_keyring();
    $ret = $ring->importKey_file($_FILES['keyfile']['tmp_name']);
    if ($ret['newkeys']) {
        $ringName = 'new';
    }
    $info = $ret['info'];
    if ($ret['errors']) $err = $ret['errors'];
    }
    else if ($_FILES['keyfile']['error']) {
    $ret = _("filename:") . " '" . htmlspecialchars($keyfile) . "'";
    if ($ret) $err[] = $ret;

        $error = $_FILES['keyfile']['error'];
        switch ($error) {
    case '1':
    case '2':
        $err[] = _("This file exceeds the maximum size allowed.");
        break;
    case '3':
        $err[] = _("This file was only partially uploaded.");
        break;
    case '4':
        $err[] = _("No file was uploaded.");
        break;
        }
    }
}

else if ($_POST["keysav"] && $_POST['id']) {
    if ($ringName == "secret") {
        //The secret ring.

        if ($_POST["signing"] == "1") {
            //Set as signing key.
            setPref ($data_dir, $username, 'signing_key_id', $_POST['id']);
            setPref ($data_dir, $username, 'use_signing_key_id', 'true');
        }
        else {
            //Not selected as trusted key now.  Was it?
            if (getPref($data_dir, $username, 'signing_key_id') == $_POST['id']) {
            //This used to be the trusted key.  Erase the record.
            setPref ($data_dir, $username, 'signing_key_id', "");
            setPref ($data_dir, $username, 'use_signing_key_id', 'false');
            }

        }
    } else {
        //Not the secret ring.

        if ($_POST["trust"] == "1") {
            //Set as trusted key.
            setPref($data_dir, $username, 'trusted_key_id', $_POST['id']);
            setPref($data_dir, $username, 'use_trusted_key_id', 'true');
        } else {
            //Not selected as trusted key now.  Was it?
            if (getPref($data_dir, $username, 'trusted_key_id') == $_POST['id']) {
                //This used to be the trusted key.  Erase the record.
                setPref($data_dir, $username, 'trusted_key_id', "");
                setPref($data_dir, $username, 'use_trusted_key_id','false');
            }

        }
    }
}
require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');
//Output any error we found.
require(SM_PATH.'plugins/gpg/modules/gpg_err.php');


// ===============================================================
$section_title = _("GPG Options - Keyring Management");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
     . '<tr><td>';

//Rectify search info.
if ($ringName == "secret") {
    //Don't want srch if viewing the secret ring
    $srch = "";
}
else {
    //Get and fix srch.
    if ($_POST['newsrch']) $srch = $_POST["newsrch"];
    else $srch = ($_GET["srch"] ? $_GET["srch"] : $_POST["srch"]);
    if (strlen($srch) > 30) $srch = "";
}

//Fetch the keys.  If there is no $srch, don't fetch!
//We will then direct the user to specify a search.
//This has to be done because gpg has no options to restrict the number
//of keys returned (other than search).
if (($ringName!='new') and (!$ring->newkeys)) {
    $ring = new gpg_keyring();
    //if ($srch || ($ringName == "secret")) {
    $ret = $ring->fetchKeys($srch, $ringName);
        if ($ret['errors'][0]) { echo _("Error: "); print_r($ret['errors']); }
}

//Rectify the sort info.
$allowedSort = array("email_name", "email_addr", "date");
$sort = ($_GET["sort"] ? $_GET["sort"] : $_POST["sort"]);
if (! ($sort && in_array($sort, $allowedSort))) $sort = "email_name";
$desc = ($_GET["desc"] ? $_GET["desc"] : $_POST["desc"]);

//Sort.
$ring->sortKeys("$sort", ($desc ? false : true));

//default chunk size.
$chunkSize = 10;

//Get the key map as chunks.
$chunkMap = $ring->getKeyMap_chunked($chunkSize);

//Get chunkNum.
//The "+0" converts it to a number.  Otherwise is_integer won't work (looks like a php bug).
$pos = ($_GET["pos"] ? $_GET["pos"] : $_POST["pos"]) + 0;

//Rectify chunkNum
if (! ($pos && is_integer($pos))) $pos = 0;
if (! array_key_exists($pos, $chunkMap)) $pos = (count($chunkMap) - 1);
if ($pos < 0) $pos = 0;

//Get the appropriate chunk.
$keymap = $chunkMap[$pos];

//Make html-safe versions
$htmlSort = htmlspecialchars($sort);
$htmlDesc = htmlspecialchars($desc);
$htmlRing = htmlspecialchars($ringName);
$htmlSrch = htmlspecialchars($srch);
$htmlPos = htmlspecialchars($pos);

//Make url-safe versions
$urlSort = urlencode($sort);
$urlDesc = urlencode($desc);
$urlRing = urlencode($ringName);
$urlSrch = urlencode($srch);
$urlPos = urlencode($pos);

//General pass through info.
//Does not include $pos (it's tricky)
$thru = "sort=$urlSort&desc=$urlDesc&ring=$urlRing&srch=$urlSrch";
$thru_noSrch = "sort=$urlSort&desc=$urlDesc&ring=$urlRing";
$thru_noRing = "sort=$urlSort&desc=$urlDesc&srch=$urlSrch";
$thru_noDesc = "sort=$urlSort&ring=$urlRing&srch=$urlSrch";
$thru_noSort = "desc=$urlDesc&ring=$urlRing&srch=$urlSrch";

//Trusted key?
if ($ringName == "secret") {
    $signingKey = getPref ($data_dir, $username, 'signing_key_id');
}
else {
    $trustedKey = getPref ($data_dir, $username, 'trusted_key_id');
}

echo '<center>';

echo '<table border="1" cellspacing="0" cellpadding="0" width="95%">';
echo '<tr><td>';

echo '<form method="POST" action="keyring_main.php">';

echo '<input type="hidden" name="sort value="';
echo htmlspecialchars($sort);
echo '">';

echo '<input type=hidden name=desc value="' . $htmlDesc. '">';
echo '<input type=hidden name=srch value="' . $htmlSrch. '">';

echo '</td></tr>';

echo '<tr><td colspan="3">';

echo '<b>';
echo _("Showing keys in");
echo ' ';
echo '</b>';

echo  '<select name="ring"><option value="all">';
echo _("Your Public Keyring");
echo  '</option>';

if ($ring->newkeys or $ringName=='new')
{
    echo '<option value="new" selected>';
    echo _("Newly Imported Keys");
    echo '</option>';
}

if ($GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'] == 'true')
{
    echo "<option value='system'";
    if ($ringName == "system")
    {
        echo " selected";
    }
    echo '>';
    echo _("System Keyring");
    echo '</option>';
}

echo '<option value="secret" ';
    if ($ringName == "secret")
    {
        echo " selected";
    }
echo '>';

echo _("Your Secret Keyring");
echo '</option>';

echo '</select>';

echo '<input type="submit" value="';
echo _("Go");
echo '">';

echo '</td></tr>';

echo '<tr><td align="left">';

if ($srch)
{
    echo '<font size="-1">';
    echo '<b>';
    echo _("Search results for");
    echo ' '
          . $htmlSrch
          . '&nbsp;&nbsp;';
    echo '[';
    echo '<a href="keyring_main.php?$thru_noSrch">';
    echo _("New Search");
    echo '</a>';
    echo ']';
}

echo '</td><td align="center">&nbsp;</td>';

echo '<td align="right">';


echo '<b>';
include("keyring_main_chunk.php");
echo '</b>';

echo '</td></tr>';

echo '<tr><td colspan="3" bgcolor="#000000">';
echo '<table border="0" cellspacing="1" cellpadding="3" width="100%">';

echo '<tr bgcolor="'. $color[5] . '">';
echo '<td width="33%">';

echo '<b>';
    if ($sort == "email_name")
    {
        echo _("Key");
        echo '&nbsp;';

        echo '<a href="keyring_main.php?$thru_noDesc"';
        echo ((! $desc) ? "&desc=1" : "");
        echo '">';

        echo '<img src="../img/';
        echo ($desc ? "up" : "down");
        echo '.gif" height="13" width="13" border="0">';
        echo '</a>';
    }
    else
    {
        echo '<a href="keyring_main.php?sort=email_name&$thru_noSort">';
        echo _("Key");
        echo '</a>';
    }
echo '</b>';

echo '</td><td width="33%">';

echo '<b>';
    if ($sort == "email_addr")
    {
        echo _("Email");
        echo '&nbsp;';

        echo '<a href="keyring_main.php?$thru_noDesc';
        echo ((! $desc) ? "&desc=1" : "");
        echo '">';

        echo '<img src="../img/';
        echo ($desc ? "up" : "down");
        echo '.gif" height="13" width="13" border="0">';
        echo '</a>';
    }
    else
    {
        echo '<a href="keyring_main.php?sort=email_addr&$thru_noSort">';
        echo _("Email");
        echo '</a>';
    }
echo '</b>';

echo '</td><td width="33%">';

echo '<b>';
    if ($sort == "date")
    {
        echo _("Generation Date");
        echo '&nbsp;';

        echo '<a href="keyring_main.php?$thru_noDesc';
        echo ((! $desc) ? "&desc=1" : "");
        echo '">';

        echo '<img src="../img/';
        echo ($desc ? "up" : "down");
        echo '.gif" height="13" width="13" border="0">';
        echo '</a>';
    }
    else
    {
        echo '<a href="keyring_main.php?sort=date&$thru_noSort">';
        echo _("Generation Date");
        echo '</a>';
    }
echo '</b>';

echo '</td><td width="33%">';

echo '<b>';
echo _("Extras");
echo '</b>';

echo '</td></tr>';

if (empty($keymap))
{
    echo '<tr bgcolor="#ffffff">';
    echo '<td colspan="4" align="center">';
    echo '&nbsp;';

    echo '<p />';

    echo '<b>';
    if ($srch)
    {
        //Search returned nothing.
        echo _("There are no keys containing the string ");
        echo "'$htmlSrch'";
        echo '.<br>';

        echo _("Please try again.");
    }
    else if ($ringName == "secret")
    {
        //Secret ring, no search, empty.  Just say so.
        echo _("You have no secret keys in your ring");
        echo ". ";
        echo _("Please import a keypair containing a secret key if you wish to continue.");
    }
    else
    {
        //No search.  Ask them to specify one.
        //Note that we don't tell them if their keyring is really empty.
        //This sucks, but it's gpg's fault.  In order to know the number of keys,
        //we have to ask gpg to give us the whole ring.  Which would destroy the
        //effectiveness of this time-saving hack.
        echo _("To locate a key or keys, please enter a search string below.");
    }
    echo '</b>';


    echo '</td></tr>';
}
else
{
    foreach($keymap as $fpr => $data)
    {
        echo '<tr bgcolor="#ffffff"><td>'
             . "<a href='keyview.php?pos=$urlPos&fpr=$fpr&$thru'>"
             . $data['email_name']
             . '</a></td><td>'
             . ($data["email_addr"] ? $data["email_addr"] : "(" ._("None") . ")")
             . '</td><td>'
             . $data['date']
             . '</td><td>';
        if ($trustedKey == $data["id"]) {

            echo  gpg_add_help_link ( 'what_trusted.php' );
            echo _("trusted key");
            echo '</a>';
        }

        if ($signingKey == $data["id"]) {
            echo  gpg_add_help_link ( 'what_signing.php' );
            echo _("signing key");
            echo '</a>';
        }

        echo "</td></tr>";
    }
}

echo '</table>';

echo '</td></tr>';

echo '<tr><td align="left">';

if ($srch)
{
    echo '<font size="-1">';
    echo '<b>';
    echo _("Search results for");
    echo " '$htmlSrch'";
    echo '</b>';
    echo '&nbsp;&nbsp;';

    echo '[';
    echo '<a href="keyring_main.php?$thru_noSrch">';
    echo _("New Search");
    echo '</a>';
    echo ']';
}

echo '</td>';

echo '<td align=center>&nbsp;</td>';

echo '<td align="right">';

echo '<b>';
include("keyring_main_chunk.php");
echo '</b>';

echo '</td></tr>';

echo '</form>';

echo '</table>';

if ($ringName != "secret")
{
    echo '<form method="POST" action="keyring_main.php">';
    echo "<input type=\"hidden\" name=\"sort\" value=\"$htmlSort\">";
    echo "<input type=\"hidden\" name=\"desc\" value=\"$htmlDesc\">";
    echo "<input type=\"hidden\" name=\"ring\" value=\"$htmlRing\">";
    echo "<input type=\"hidden\" name=\"pos\" value=\"$htmlPos\">";
    echo '<input type="text" length="10" maxlength="30" name="newsrch">';
    echo '<input type="submit" value="';
    echo  _("Key Search");
    echo '"></form>';
}

echo '</center>';

echo '</td></tr>';

echo '<tr><td>&nbsp;</td></tr>';

echo '<tr><td>';

if ($ringName != "system")
{
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

    echo '<br>';

    echo "<a href=\"genkey.php?pos=$urlPos&$thru\">";
    echo _("Generate new personal key pair");
    echo '</a>';

    echo '<br>';

    echo
          ' <a href="../gpg_options.php?MOD=keyserver">'
        . _("Look up keys on a public keyserver")
        . '</a>'
        . ', '
        . _("and import them to your keyring.");
}
else
{
    echo '<font color="#cccccc">';
    echo _("Keyring not editable, keyring functions disabled.");
}

echo '</td></tr>';

echo '</table>';

require_once (SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * c-basic-offset: 4
 * End:
 */

/**
 *
 * $Log: keyring_main.php,v $
 * Revision 1.36  2004/01/03 22:03:04  ke
 * -added option to disable the passphrase confirmation on key deletion
 *
 * Revision 1.35  2003/12/23 14:37:49  brian
 * fixed typo
 *
 * Revision 1.34  2003/12/02 00:39:13  ke
 * -changed quoting around so that hidden variables still display properly
 *
 * Revision 1.33  2003/11/28 21:18:25  ke
 * -added double-quote before Done string in key view screen
 * bug 130
 *
 * Revision 1.32  2003/11/25 02:50:42  ke
 * -added a section for viewing the public key, and a cancel button to show keyring again
 * bug 122
 *
 * Revision 1.31  2003/11/22 21:13:10  brian
 * - fixed bug reported by Brad Donison
 * - needed .php after the names of help files in new help infrastructure
 *
 * Revision 1.30  2003/11/19 15:48:11  ke
 * -changed single to double quotes on Generate Keypair link, to allow passthru of variables
 *
 * Revision 1.29  2003/11/11 22:46:08  ke
 * -removed debug statements which had no purpose
 * -moved inclusion of gpg_functions.php to beginning of file, removed other includes of it elsewhere
 *
 * Revision 1.28  2003/11/07 22:15:41  ke
 * -changed include path on email key function
 *
 * Revision 1.27  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.26  2003/11/03 20:18:00  brian
 * minor wording changes in advance of translation.
 * Bug 35
 *
 * Revision 1.25  2003/11/03 18:33:36  brian
 * - removed the options for key without passphrase.
 * - this option is insecure, and only casues confusion
 *
 * Revision 1.24  2003/11/01 19:12:09  brian
 * - fixed trusted key and signing key links in right hand column
 * - fixed key details links
 *
 * Revision 1.23  2003/11/01 18:32:51  brian
 * - cleaned up links on bottom of page
 * - added keyserver lookup link
 *
 * Revision 1.22  2003/10/30 20:19:35  ke
 * -changed single to double quotes in _( internationalized strings
 * bug 35
 *
 * Revision 1.21  2003/10/30 02:17:03  walter
 * - completed localized text by sentences
 * Bug 35
 *
 * Revision 1.20  2003/10/29 00:20:17  walter
 * - localized text by sentences
 * Bug 35
 * -  updated Help structure
 * Bug 79
 *
 * Revision 1.19  2003/10/20 19:13:16  walter
 * added gpg_module_footer.php to page
 *
 * Revision 1.18  2003/10/14 19:58:26  ke
 * Added info variable to show non-error or warning info in return from adding keys
 *
 * Revision 1.17  2003/10/11 21:38:00  ke
 * -moved require files around to allow headers to be sent only if not redirecting
 *
 * Revision 1.16  2003/10/10 19:04:25  ke
 * -added requirement for gpg_config.php at top of keyring_main
 * -added universal $thru variable for easy passing of state
 * -added passphrase change hooks
 * -added passphrase confirmation on deletion of keys
 * bug 27
 *
 * Revision 1.15  2003/10/06 22:39:54  ke
 * -Added viewing options for newly imported keys
 *
 * Revision 1.14  2003/10/04 00:52:43  ke
 * -Added error handler output of errors only
 *
 * Revision 1.13  2003/09/30 23:30:19  ke
 * -added setPref for use_trusted_key_id
 * -changed puncuation in translation info
 *
 * Revision 1.12  2003/09/30 00:40:49  ke
 * -Changed target for Back link to main options page
 *
 * Revision 1.11  2003/09/29 21:11:30  ke
 * -Internationalized all strings in main keyring interface
 * -Removed all <?php chunks, used echos instead
 * -Changed chunk size to max of 10 keys shown
 * -Added message for system keyring lack of editablity instead of simply not showing bottom command links
 * bug 27
 *
 * Revision 1.10  2003/08/14 02:40:36  vermette
 * replaced getstart.mod with getstart.php.
 * Integrated key generation into consolidated UI.
 * Removed unused components.
 *
 * Revision 1.9  2003/08/02 01:54:53  vermette
 * added signing key functionality to consolidated interface.
 * Removed old signing key page from keymgmt.mod, but left file in cvs because it's used in getstart.mod.
 * Added viewing of secret keyring.
 *
 * Revision 1.8  2003/08/01 23:57:25  vermette
 * remove publicring.mod, not used anymore.
 * Removed trustedkey from keymgmt menu, but left file in cvs because it's strill used in getstart.mod.
 * Various minutiae fixed for keyring_main.php
 *
 * Revision 1.7  2003/07/24 06:46:12  vermette
 * folded trusted key UI into consolidated interface.
 * This replaces the current UI, but I haven't removed it from the menus yet.
 *
 * Revision 1.6  2003/07/20 06:44:47  vermette
 * added key emailing.  added click-thru from key view to compose to key owner.
 * Speed enhancements on keyview.
 *
 * Revision 1.5  2003/07/17 07:33:07  vermette
 * time-saving modifications to hack around gpg's lack of ability to restrict output size.
 * Added system keyring into consolidated interface.
 *
 * Revision 1.4  2003/07/11 07:43:12  vermette
 * added search to keyring_main
 *
 * Revision 1.3  2003/07/11 06:54:03  vermette
 * keyring work.  Added chunking, first/prev/next/last, sorting, and ascending v. descending sorted view.  i
 * Also modified key table to give more info.
 *
 * Revision 1.2  2003/07/08 19:10:29  vermette
 * tightening error messaging.
 * UI work on gpg_keyring class.
 * Proper display of empty keyring
 *
 * Revision 1.1  2003/07/08 18:01:31  vermette
 * new keyring view page
 *
 * Revision 1.3  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.
 * The previous 'back' link now only appears if requested (new arg to makePage).
 * This isn't done by any means, but at most it's as broken as it was, so it's an improvement.
 *
 * Revision 1.2  2003/06/13 15:18:01  brian
 * modified to remove $msg parameter to $gpg_format_keylist fn call
 *
 * Revision 1.1  2003/04/11 14:09:10  brian
 * Initial Revision
 * display public keyring with radio 'false'
 * Bug 27
 *
 *
 */

?>
