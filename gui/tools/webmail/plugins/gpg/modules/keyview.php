<?php
/**
 * keyview.php
 * ----------------
 * GPG Key view page
 * Copyright (c) 2002-2003 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Joshua Vermette
 * @author Aaron Van Meerten
 * @author Brian Peterson
 *
 * $Id: keyview.php,v 1.28 2004/01/03 22:04:23 ke Exp $
 */

if (!defined (SM_PATH)){
    if (file_exists('./gpg_functions.php')){
        define (SM_PATH , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define (SM_PATH , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define (SM_PATH , '../');
    } else echo "unable to define SM_PATH in genkey.php, exiting abnormally";
}

require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');

//Get the ringName
$ringName = ($_GET['ring'] ? $_GET['ring'] : $_POST['ring']);
//if no ring is set yet, use all
if (!$ringName) {
    $ringName='all';
}
//Get the key fingerprint.
$fpr = ($_GET['fpr'] ? $_GET['fpr'] : $_POST['fpr']);
if (! $fpr) {
    gpg_bail("keyview no fpr");
}

require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');

//Fetch the keys.
$ring = new gpg_keyring();
$err = $ring->fetchKeys($fpr, $ringName);
if ($err['errors']) gpg_bail($err['errors']);

//Get the key.
$key = $ring->getKey($fpr);
if (! $key) gpg_bail("keyview bad key ('$ringName', '$fpr')");

//Check to see if this key is part of a keypair on the ring
if ($ringName == 'all') {
    $err = $ring->fetchKeys($fpr,'secret');
    if ($ring->numKeys() > 0) {
        $secretKey = true;
    } else {
        $secretKey = false;
    }
}
//Map of useful field names to readable names.
$fieldNames = array('email_addr' => _("Owner Email"),
            'date' => _("Generation Date"),
            'exp' => _("Expiration Date"),
            'len' => _("Key Length"),
            'id' => _("ID"),
            'alg' => _("Algorithm"));

if ($ringName == "secret") {
    //Secret ring.
    //Signing key?
    if (getPref($data_dir, $username, 'use_signing_key_id') &&
        getPref($data_dir, $username, 'signing_key_id') == $key['id']) {
        $signing = true;
    }

} else {
    //Regular ring.
    //Trusted key?
    if (getPref($data_dir, $username, 'use_trusted_key_id') &&
        getPref($data_dir, $username, 'trusted_key_id') == $key['id']) {
        $trusted = true;
    }
   //Put public key in new window?
    if (getPref($data_dir, $username, 'compose_new_win')) {
	$compose_new_win = true;
	//fetch ascii-armored public key text
	$text = $ring->getExportText($fpr, $ringName);
    }
}
$requirepassphrase = $GLOBALS['GPG_SYSTEM_OPTIONS']['requirepassphraseonkeydelete'];
$confirmstr=_("Remove this key from your keyring?  This action cannot be undone!");
echo <<<TILLEND
<script language=javascript>
<!--
function delConf()
{
    if (confirm("$confirmstr"))
    {
        document.keyview.deletekey.value="true";
    document.keyview.submit();
        return true;
    }
    else {
        return false;
    }
}

function delpConf()
{
    if (confirm("$confirmstr")) {
TILLEND;
if ($requirepassphrase=='true') {
        echo "window.open('../gpg_pop_init.php?MOD=passpop&psaction=deletepair','Confirm_Delete','status=yes,width=300,height=200,resizable=yes');\n}\nreturn false;\n";
} else {
	echo "}\ndocument.keyview.deletepair.value=\"true\";\n return true;\n";
}
echo <<<TILLEND
}

function delsConf()
{
    if (confirm("$confirmstr")) {
TILLEND;
if ($requirepassphrase=='true') {
        echo "window.open('../gpg_pop_init.php?MOD=passpop&psaction=delete','Confirm_Delete','status=yes,width=300,height=200,resizable=yes');\n}\nreturn false;\n";
} else {
	echo "}\ndocument.keyview.deletekey.value=\"true\";\n return true;\n";
}
echo <<<TILLEND
}
//-->
</script>
TILLEND;


// ===============================================================
$section_title = _("GPG Options - Display Key");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">'
     . '<tr><td>';

if ($_GET['err']) {
    echo '<font color=red>' . _("Error: ") . $_GET['err'] . '</font><br>';
}
echo '<table width=95% align="center" border="0" cellpadding="2" cellspacing="0">'
   . '<form method="POST" action="keyring_main.php" name=keyview>';

echo '<input type="hidden" name="id" value="' . urlencode($key['id']) . '">';
echo '<input type="hidden" name="fpr" value="' . urlencode($fpr) . '">';
echo '<input type="hidden" name="pos" value="' . urlencode($_GET["pos"]) . '">';
echo '<input type="hidden" name="sort" value="' . urlencode($_GET["sort"]) . '">';
echo '<input type="hidden" name="desc" value="' . urlencode($_GET["desc"]) . '">';
echo '<input type="hidden" name="srch" value="' . urlencode($_GET["srch"]) . '">';
echo '<input type="hidden" name="ring" value="' . urlencode($_GET["ring"]) . '">';
echo '<input type="hidden" name="passphrase" value="">';
echo '<input type="hidden" name="deletekey" value="false">';
echo '<input type="hidden" name="deletepair" value="false">';

function showkeyButtonRow() {
global $color;
global $secretKey;
global $ringName;
global $compose_new_win;
global $text;

echo "<tr bgcolor=\"$color[9]\">\n<td width=33%>\n<input type=submit name=\"keysav\" value=\"" . _("Save") . "\">\n"
   . "<input type=submit name=\"can\" value=\"" . _("Cancel") . "\">\n</td>\n<td align=center width=33%>\n";

if ($ringName == "secret") {
//    echo "<input type=submit name='cp' value='" ._("Change Passphrase") . "'>";
} else {
   if ($compose_new_win) {
	echo "<input type=button name='em' value='" . _("Email Key") . "' onclick='comp_in_new(\"" . get_location() . "/../../../src/compose.php?body=" . urlencode($text) . "\");'>";
   } else {
    echo "<input type=submit name='em' value='" . _("Email Key") . "'>";
   }
    echo "<input type=submit name='disp' value='" . _("View Key") . "'>";
}
echo '</td><td align=right width=33%>';

if ($ringName == "all") {
    //check to see if this is part of a keypair
    if ($secretKey) {
       echo "<input type=button name='delp' value='" ._("Delete Keypair") . "' onclick='return delpConf()'>";
    } else {
    echo "<input type=button name='del' value='" . _("Delete") . "' onclick='return delConf()'>";
    }
    echo "</td>";
} elseif ($ringName == "secret") {
   echo "<input type=submit name='del' value='" . _("Delete") . "' onclick='return delsConf()'>";
   echo "<input type=submit name='delp' value='" . _("Delete Keypair") . "' onclick='return delpConf()'></td>";
}
echo "</td>\n</tr>";
}
showkeyButtonrow();
echo "\n\n<tr><td colspan=3>\n<dl>\n<dt><h2>\n";

if ($ringName == "secret") echo _("Secret Key: ");
echo $key['email_name'];

echo "</h2></dt>\n<dd><table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n<tr><td colspan=3><b>\n";

if ($ringName == "secret") {
    echo '<input type=checkbox name="signing" value="1" ';
    if($signing) echo "checked";
    echo '>' . _("This is my SIGNING key");
    echo '&nbsp;&nbsp;';
    echo '<font size="-1">';

    echo  gpg_add_help_link ( 'what_signing.php' )
         . _("What's this?")
         . '</a>';

    echo '</font>';
    echo '<br>';
} else {
    echo '<input type=checkbox name="trust" value="1" ';
    if($trusted) echo "checked";
    echo '>' . _("This is my TRUSTED key");

    echo '&nbsp;&nbsp;';
    echo '<font size="-1">';

    echo  gpg_add_help_link ( 'what_trusted.php' )
         . _("What's this?")
         . '</a>';

    echo '</font>';
    echo '<br>';

}
echo '</b></td></tr><tr><td colspan=2><hr width=100% size=1 noshade color=#000000</td></tr>';

foreach($fieldNames as $field => $name) {
    if ($key[$field]) {
    echo "<tr>\n<td width=1%><b> $name:</b></td>\n";
    if ($field == "email_addr") {
	$url='../../../src/compose.php?send_to=' .urlencode($key[$field]); 
	echo '<td><a href=';
	if ($compose_new_win) {
		echo '"javascript:void(0);" onclick="comp_in_new(\''. $url . '\')"';
	} else {
		echo "'$url'";
	}
        echo  "'>" . $key[$field] . "</a></td>";
    }
    else {
        echo "<td>", $key[$field], "</td>";
    }
    echo '</tr>';
    }
}

echo '</table>';

if ($key["sub"]) {
    foreach($key["sub"] as $sId => $sKey) {
    echo "<br>\n<dl>\n\t<dt>\n\t<font size=+1><b><u>" . _("Subkey") . " '" . $sKey['id']. "'</u></b></font>\n\t</dt>\n";
    echo '<dd><table border="0" cellpadding="2" cellspacing="0">';
     foreach($fieldNames as $field => $name) {
     if ($field == "id") {
         continue;
     }

     if ($sKey[$field]) {
         echo "<tr><td width=5%><b>$name:</b></td>\n";
         echo "<td width=95%>$sKey[$field]</td>\n</tr>";
     }
     }
     echo "\n\t</table></dd>\n\t</dl>\n";
       }
}

echo <<<TILLEND
</dd>

</dl>

</td></tr>

<form method="POST" action="keyring_main.php">
TILLEND;
showkeyButtonrow();

//commented bottom button row and use showkeyButtonrow function
/*<tr bgcolor="$color[9]">
<td width=33%>
TILLEND;
echo '<input type=submit name="keysav" value="' . _("Save") . '">';
echo '<input type=submit name="can" value="' . _("Cancel") . '">';
echo "</td>\n<td align=center width=33%>";

if ($ringName == "secret") {
    echo "&nbsp;";
} else {
    echo "<input type=submit name='em' value='"  . _("Email Key") . "'>";
}
echo '</td><td align=right width=33%>';
if (($ringName == "all") || ($ringName == "secret")) {
    echo "<input type=submit name='del' value='" . _("Delete") . "' onclick='return delConf()'></td>";
}
echo <<<TILLEND
</td>
</tr>
*/
echo <<<TILLEND

</form>

TILLEND;

echo '</td></tr></table>';

require_once (SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * c-basic-offset: 4
 * End:
 */

/**
 * $Log
 * Revision 1.12  2003/10/06 22:49:33  ke
 * -Added keyring to default to all if none specified
 *
 * Revision 1.11  2003/10/04 00:56:42  ke
 * -added check for error array in return from keyring functions
 *
 * Revision 1.10  2003/08/14 02:37:33  vermette
 * minor fix, and add deletion for secret keys
 *
 * Revision 1.9  2003/08/02 01:54:53  vermette
 * added signing key functionality to consolidated interface.  Removed old signing key page from keymgmt.mod, but left file in cvs because it's used in getstart.mod.  Added viewing of secret keyring.
 *
 * Revision 1.8  2003/07/24 06:46:12  vermette
 * folded trusted key UI into consolidated interface.  This replaces the current UI, but I haven't removed it from the menus yet.
 *
 * Revision 1.7  2003/07/20 06:44:47  vermette
 * added key emailing.  added click-thru from key view to compose to key owner.  Speed enhancements on keyview.
 *
 * Revision 1.6  2003/07/17 07:33:07  vermette
 * time-saving modifications to hack around gpg's lack of ability to restrict output size.  Added system keyring into consolidated interface.
 *
 * Revision 1.5  2003/07/11 07:43:12  vermette
 * added search to keyring_main
 *
 * Revision 1.4  2003/07/11 06:54:03  vermette
 * keyring work.  Added chunking, first/prev/next/last, sorting, and ascending v. descending sorted view.  Also modified key table to give more info.
 *
 * Revision 1.3  2003/07/08 19:10:29  vermette
 * tightening error messaging.  UI work on gpg_keyring class.  Proper display of empty keyring
 *
 * Revision 1.2  2003/07/08 18:01:51  vermette
 * rename publicring.php to keyring_main.php
 *
 * Revision 1.1  2003/07/08 17:56:32  vermette
 * new key detail page
 *
 *
 */

?>
