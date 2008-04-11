<?php
/**
 * keyview.php
 * ----------------
 * GPG Key view page
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @author Joshua Vermette
 * @author Aaron Van Meerten
 * @author Brian Peterson
 *
 * $Id: keyview.php,v 1.54 2005/07/27 14:07:49 brian Exp $
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

require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');

//Get the ringName
sqGetGlobalVar('ring',$ringName);
//if no ring is set yet, use all
if (!$ringName) {
    $ringName='all';
}
//Get the key fingerprint.
sqgetGlobalVar('fpr',$fpr);
if (! $fpr) {
    gpg_bail("keyview no fpr");
}

require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');
require_once(SM_PATH.'plugins/gpg/gpg.php');
global $gpg_key_dir;
global $username;
global $data_dir;

$requirepassphrase = $GLOBALS['GPG_SYSTEM_OPTIONS']['requirepassphraseonkeydelete'];
$use_proc_open=$GLOBALS['GPG_SYSTEM_OPTIONS']['use_proc_open'];
$confirmstr=_("Remove this key from your keyring?  This action cannot be undone!");
$confirmuidstr=_("Remove the selected user identies from your keyring?  This action cannot be undone!");
$confirmsignstr=_("Sign the selected User Identities?");
$confirmsubstr=_("Remove the selected subkey from this private key?  This action cannot be undone!");
$failsubkeystr=_("Please select a SubKey to perform an action on");
$failuidstr=_("Please select a User Identity to perform an action on");
echo '<script language=JavaScript>';
echo "<!--\n";
echo "function deleteConf(deleteUrl) {\n";
echo "	if (confirm(\"" . $confirmstr . "\")) {\n";
echo "		document.location=deleteUrl;\n";
echo "  }\n}\n";
echo "\nfunction emailKey(composeUrl,keyText) {\n";
echo "		document.keyview.action=composeUrl;\n";
echo "		window.document.keyview.submit();\n";
echo "}\n";
echo "\nfunction deleteConfPass(deleteUrl) {\n";
echo "	if (confirm(\"" . $confirmstr . "\")) {\n";
echo "			document.keyview.action=deleteUrl;\n";
echo "		        window.open('../gpg_pop_init.php?MOD=passpop&psaction=deletepair','Confirm_Delete','status=yes,width=300,height=200,resizable=yes');\n";
echo "  }\nreturn false;\n";
echo "}\n";
echo "\nfunction checkSave(mine,saveUrl) {\n";
echo "  if (mine.checked==true) { \n";
echo "		document.location=saveUrl + '&checkVal=1';\n";
echo "  } else {\n";
echo "		document.location=saveUrl + '&checkVal=0';\n";
echo "  }\n}\n";
echo "\nfunction deleteConfSubKey(deleteUrl) {\n";
echo "  j = document.forms.SubKeys.SubKeyNo.length;\n";
echo "  for (i=0; i<j; i++) {\n";
echo "  	if (document.SubKeys.SubKeyNo[i].checked) {\n";
echo "			if (confirm(\"" . $confirmsubstr . "\")) {\n";
echo "                          document.location=deleteUrl+\"&SubKeyNo=\"+document.forms.SubKeys.SubKeyNo[i].value;\n";
echo "                          return;\n";
echo "			}\n";
echo "		}\n";
echo "  }\n";
echo "          alert(\"$failsubkeystr\");\n";
echo "}\n";
echo "\nfunction expireSubKey(expireUrl) {\n";
echo " j = document.forms.SubKeys.SubKeyNo.length;\n";
echo "  for (i=0; i<j; i++) {\n";
echo "          if (document.SubKeys.SubKeyNo[i].checked) {\n";
echo "                          document.location=expireUrl+\"&SubKeyNo=\"+document.forms.SubKeys.SubKeyNo[i].value;\n";
echo "                          return;\n";
echo "          }\n";
echo "  }\n";
echo "          alert(\"$failsubkeystr\");\n";
echo "}\n";
echo "\nfunction ConfUID(confstr,failstr,redirecturl) {\n";
echo "  j = document.forms.UIDs.UidNoLen.value;\n";
echo "  for (i=1; i<=j; i++) {\n";
echo "		if (eval(\"document.UIDs.UidNo\"+i+\".checked\")) {\n";
echo "			if (confirm(confstr)) {\n";
echo "				document.UIDs.action=redirecturl;\n";
echo "				document.UIDs.submit();\n";
echo "				return true;\n";
echo "			} else {\n";
echo "				return false;\n";
echo "			}\n";
echo "		}\n";
echo "  }\n";
echo "  alert(failstr);\n";
echo " return false;\n";
echo "}\n";
echo "//-->\n";
echo "</script>\n";


sqgetGlobalVar('pos',$pos);
sqgetGlobalVar('srch',$srch);
sqgetGlobalVar('desc',$desc);
sqgetGlobalVar('info',$info);
sqgetGlobalVar('errors',$errors);
    if (getPref($data_dir, $username, 'use_signing_key_id')) { 
	$signing_key_id=getPref($data_dir, $username, 'signing_key_id');
    } else { $signing_key_id=false; }

    if (getPref($data_dir, $username, 'use_trusted_key_id')) { 
        $trusted_key_id=getPref($data_dir, $username, 'trusted_key_id');
    } else {$trusted_key_id=false; }
   //Put public key in new window?
    if (getPref($data_dir, $username, 'compose_new_win')) {
        $compose_new_win = true;
    } else { $compose_new_win=false; }


$passThru = "ring=$ringName&pos=$pos&sort=$sort&desc=$desc&srch=$srch";
$keyringUrl="keyring_main.php?selectKey=true&$passThru";
$changePassUrl="changepass.php?$passThru";
$genkeyUrl="keygen.php?$passThru";
$adduidUrl="adduid.php?$passThru";
$signkeyUrl="keysign.php?$passThru";
$uploadUrl="uploadkey.php?$passThru";
$expireKeyUrl="keygen.php?&$passThru";
$composeUrl=get_location() . '/../../../src/compose.php';
$isphp43=(check_php_version(4,3) && ($use_proc_open=='true'));
if ($ringName=='system') { $edit=false; }
else { $edit=true; }
//require_once(SM_PATH.'plugins/gpg/gpg_execute.php');
//Fetch the keys.
/*
$ring = new GnuPG;
//$debug=1;
$ring->gpg_exe = ($GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg']);
 $safe_data_dir = getHashedDir($username, $data_dir) . DIRECTORY_SEPARATOR;
 if ($GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring']=='true') {
       if ($debug) { echo "Setting system keyring file."; }
         $ring->systemKeyring=$GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
 }
 if ($debug) {
         $ring->debug=true;
 }
 $ring->gpgHomeDir = "$safe_data_dir$username.gnupg";
 */
$ring=initGnuPG();
$return = $ring->fetchKeys($fpr, 'all');
$fpr=$ring->getKeyIndexFromFingerprint($fpr);
// ===============================================================
$section_title = _("GPG Options - Display Key");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================

if ($errors!='') {
	echo _("Errors occured") . ":<br>";
	echo $errors . "<p>";
}
if ($info!='') {
	echo _("Your request returned the following information") . ":<br>";
	echo "$info<p>";
}
$key=$ring->keys[$fpr];
if ($key) {

	$fpr=$key->fingerprint;
	$return=$ring->getExportText($fpr);
	$exportText = $return['output'];
	$exportTextURL = urlencode($exportText);

        echo '<form name=keyview method=post>';
	if (!$compose_new_win) {
        	echo "<input type=hidden name=body value=\"$exportText\">";
	}
        echo '<input type=hidden name=passphrase value="">';
        echo '</form>';
        echo '<table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">' . "\n";


        //Begin interface row
        echo "<tr><td colspan=4>\n";
        echo "<br>[";
	$keyactions[]="<a href=\"$keyringUrl&fpr=$fpr&keyaction=dispKey\">" . _("View Key") . '</a>';
        if (!$compose_new_win) {
                $emailstr= "<a href=\"#\" onclick=\"emailKey('$composeUrl');\">";
        } else {
                $emailstr= "<a href=\"#\" onclick='comp_in_new(\"" . get_location() . "/../../../src/compose.php?body=$exportTextURL\");'>";
        }
        $keyactions[]=$emailstr.  _("Email Key") . '</a>';
	$keyactions[]= "<a href=\"$uploadUrl&fpr=$fpr&keyaction=uploadKey\">". _("Upload Key") . '</a> ';
	if ($isphp43 && $edit) {
		$keyactions[]="<a href=\"$signkeyUrl&fpr=$fpr&keyaction=signKey\">". _("Sign Key") . '</a>';
	} 
	if ($key->haveSecret && $edit) {
		if ($isphp43) {
                	$keyactions[]= "<a href=\"$changePassUrl&fpr=$fpr\">" . _("Change Passphrase") . '</a>';
		} 
               	if ($requirepassphrase=='false') {
               	        $deletestr= "<a href=\"#\" onclick=\"deleteConf('$keyringUrl&fpr=$fpr&keyaction=deletePair')\">";
               	} else {
               	        $deletestr= "<a href=\"#\" onclick=\"deleteConfPass('$keyringUrl&fpr=$fpr&keyaction=deletePair');\">";
               	}

               $keyactions[]=$deletestr . _("Delete Keypair") . "</a>";
		if ($isphp43) {
			$keyactions[]= "<a href=\"$expireKeyUrl&keyaction=expireKey&fpr=$fpr\">" . _("Set Expiration")  . '</a>';
		} 
        } else {
		if ($edit) {
                	$keyactions[]=" <a href=\"#\" onclick=\"deleteConf('$keyringUrl&fpr=$fpr&keyaction=deleteKey');\">" . _("Delete Key") . "</a>";
		}
        }
	echo implode(" | ",$keyactions);
        echo "]";
        echo "</td></tr>";




        //main name for the key
        echo "<tr><td colspan=4>\n";
        echo "<h2>" . $key->get_email_name() . "</h2>\n";
        echo "</td></tr>\n";


        //start key information rows
        showKey($key);

        //Show key options
	if ($key->haveSecret && $edit) {
	//Show signing key option
        echo "<tr><td colspan=4>\n";
         echo "<input type=checkbox name=\"signing\" onclick=\"checkSave(this,'$keyringUrl&keyaction=saveKey&fpr=$fpr&signid=" . $key->id . "')\" ";
	 if((strtolower($signing_key_id)==strtolower($key->id )) 
	 	OR (strtolower($signing_key_id)==strtolower($fpr))) echo "checked";
            echo '>' . _("This is my SIGNING key");
            echo '&nbsp;&nbsp;';
            echo '<font size="-1">';

            echo  gpg_add_help_link ( 'what_signing.php' )
                 . _("What's this?")
                 . '</a>';

            echo '</font>';

        echo "</td></tr>";
	}
	else {	
	//Show trusted options
	echo "<tr><td colspan=4>\n";
	echo "<input type=checkbox name=\"trust\" onclick=\"checkSave(this,'$keyringUrl&keyaction=saveKey&fpr=$fpr&trustid=" . $key->id . "')\" ";
	if($trusted_key_id==$key->id) echo "checked";
    	echo '>' . _("This is my TRUSTED key");

    	echo '&nbsp;&nbsp;';
    	echo '<font size="-1">';

    	echo  gpg_add_help_link ( 'what_trusted.php' )
         . _("What's this?")
         . '</a>';

    	echo '</font>';
	echo '</td></tr>';
	}

	//signatures on main key
	if (count($key->signatures)>0) {
		echo "<tr><td colspan=4>";
		echo "&nbsp;<p>";
		echo "<h3>" . _("Signatures");
		echo '<table width="100%" align="left" border="0" cellpadding="2" cellspacing="0">' . "\n";
		showSigs($key->signatures);
		echo '</table>';
		echo "</td></tr>";
	}

	// starting user identities
	echo "<tr><td colspan=4>";
	echo "&nbsp;<p>";
	echo "<h3>" . _("User Identities") . "</h3>\n";
	if ($isphp43 && $edit) {
		echo "[\n";
		echo "<a href=\"#\" onclick=\"ConfUID('$confirmsignstr','$failuidstr','$signkeyUrl&fpr=$fpr&keyaction=signUID');\">";
		echo _("Sign User Identities");
		echo '</a>';
		if ($key->haveSecret) {
			echo ' | ';
			echo "<a href=\"$adduidUrl&fpr=$fpr&keyaction=addUID\">";
			echo _("Add User Identity");
			echo '</a>';
		}
		echo ' | ';
		echo "<a href=\"#\" onclick=\"ConfUID('$confirmuidstr','$failuidstr','$keyringUrl&fpr=$fpr&keyaction=delUID');\">";
		echo  _("Delete User Identities");
		echo "</a>";
		echo "]<br>\n";
	}
	echo '<table width="100%" align="left" border="0" cellpadding="2" cellspacing="0">';
	echo "<form name=UIDs method=POST action=\"$keyringUrl&fpr=$fpr\">";
	$uidcount=0;
	echo "<input type=hidden name=UidNoLen value=" . count($key->userIDs) . ">\n";
	foreach ($key->userIDs as $uid) {
		$uidcount=$uidcount+1;
		echo "<tr><td colspan=4><hr></td></tr>";
		echo "<tr><td width=10>&nbsp;</td><td colspan=2>";
		echo "<li>";
		if ($isphp43 && $edit) { echo "<input type=checkbox name=UidNo$uidcount value=$uidcount checked>"; }
		echo "<b>" . $uid->email_addr . "</b> " . $uid->email_name . ' ' . $uid->email_extra;
		echo "</td></tr>";
		showSigs($uid->signatures);
	}
	echo '</form></table>';
	echo '</td></tr>';
	echo '<tr><td colspan=4>';
	echo "&nbsp;<p>";
	if (count($key->subkeys)>0) {
	echo "<h3>" . _("Subkeys") . "</h3>";
	if ($key->haveSecret && $isphp43 && $edit) {
		//Subkey interface
		echo "[\n";
		echo "<a href='$genkeyUrl&fpr=$fpr&keyaction=AddSubKey'>";
		echo _("Add New SubKey");
		echo '</a>';
		echo ' | ';
		echo _("Revoke SubKey");
		echo ' | ';
		echo "<a href=\"#'\" onclick=\"deleteConfSubKey('$keyringUrl&fpr=$fpr&keyaction=delSubKey');\">";
		echo _("Delete SubKey");
		echo '</a>';
		echo ' | ';
		echo "<a href=\"#'\" onclick=\"expireSubKey('$expireKeyUrl&fpr=$fpr&keyaction=expireSubKey');\">";
		echo _("Expire SubKey");
		echo '</a>';
		echo "]\n";
	}
	echo '<table width="100%" align="left" border="0" cellpadding="2" cellspacing="0">';
	echo "<form name=SubKeys method=POST action=$keyringUrl>";
	$skeycount=0;
	foreach ($key->subkeys as $skeyfpr=>$skey) {
		$skeycount=$skeycount+1;
		echo "<tr><td colspan=5><hr></td></tr>";
		echo "<tr><td>";
		if ($isphp43 && $edit && $key->haveSecret) { echo "<input type=radio name=SubKeyNo value=$skeycount>"; }
		echo _("Subkey") . " $skeycount</td></tr>";
		showKey($skey);
	}
	echo '</form>';
	echo '</table>';
	}
	echo '</td></tr>';

} else { echo _("Error loading key data."); }
		
echo '</table>';
global $backlink;
$backlink="keymgmt";
require_once (SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');


function showSigs($sigArray) {
	global $passThru;
	foreach($sigArray as $sig) {
                        echo "<tr><td width=10>&nbsp;</td><td width=10>&nbsp;</td><td>";
                        echo _("Signed by") . " "; // . $sig->trust . ' ' ;
                        if ($sig->uid->email_name!='Unknown') {
                                echo "<a href=\"keyview.php?fpr=" . $sig->id . "&$passThru" . "\">";
                                echo $sig->uid->email_name . ' ' . $sig->uid->email_addr;
                                echo "</a>";
                        } else {
                                echo _("unknown key") . " " . $sig->id;
                        }
                        echo ' ' . _("on") . ' ' . $sig->date;
                        echo '</td></tr>';
        }
}

function showKey($key) {
	global $passThru;
        echo "<tr><td><b>";
        echo _("Type") . ":</b></td><td>";
        switch($key->type) {
                case 'pub':
                        echo _("Public");
                        break;
                case 'sub':
                        echo _("SubKey");
                        break;
                case 'sec':
                        echo _("Secret");
                        break;
                default:
                        echo $key->type;
                        break;
        }
        echo "</td><td><b>";
        echo _("Algorithm") . ":</b></td><td> ";
        switch($key->algorithm) {
                case 1:
                    echo _("RSA");
                    break;
                case 16:
                    echo _("ElGamal (encrypt only)");
                    break;
                case 17:
                    echo _("DSA/DH");
                    break;
                case 20:
                    echo _("ElGamal (sign and encrypt)");
                    break;
                default:
                    echo _("UNKNOWN");
                    break;
        }
        echo "</td></tr>\n";
        echo "<tr><td><b>";
        echo _("Length") . ":</b></td><td> " . $key->len;
        echo "</td><td><b>";
        echo _("Key ID") . ":</b></td><td> " . $key->id;
        echo "</td></tr>\n";
        echo "<tr><td><b>";
        echo _("Created") . ":</b></td><td> " . $key->date;
        echo "</td><td><b>";
        echo _("Expires") . ":</b></td><td> " . $key->exp;
        echo "</td></tr>\n";
        echo "<tr><td><b>";
        echo _("Fingerprint") . ":</b></td><td colspan=3> " . $key->get_fpr();
        echo "</td></tr>\n";
}

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
