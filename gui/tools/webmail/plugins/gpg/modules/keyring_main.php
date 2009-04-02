<?php

//include the gpg system header, so's everything will be in place.
//Have to chdir so included includes will work.
//chdir("../");

if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in keyring_main.php, exiting abnormally";
}
global $GPG_VERSION;

require_once(SM_PATH.'include/validate.php');
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');
require_once(SM_PATH.'plugins/gpg/gpg_config.php');
require_once(SM_PATH.'plugins/gpg/gpg_execute.php');
require_once(SM_PATH.'plugins/gpg/gpg.php');
require_once(SM_PATH.'plugins/gpg/gpg_pref_functions.php');
load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);

global $debug;
$debug=$GLOBALS['GPG_SYSTEM_OPTIONS']['debug'];

//Rectify ring.
if (isset($_GET['ring'])) { 
	$ringName = $_GET['ring']; 
} else {
  if(isset($_POST['ring'])) {
      $ringName=$_POST['ring']; 
  } else {
       $ringName='public';
  }
}
//$ringName = ($_GET["ring"] ? $_GET["ring"] : $_POST["ring"]);
//hack to reset keyring to 'all' if new keys were being viewed.
if ($ringName == 'new') { $ringName = 'all'; }

//Make the passthrough string for keyring_main.
sqgetGlobalVar('pos',$pos);
sqgetGlobalVar('sort',$sort);
sqgetGlobalVar('desc',$desc);
sqgetGlobalVar('srch',$srch);
sqgetGlobalVar('fpr',$fpr);
sqgetGlobalVar('emailkey',$emailkey);
sqgetGlobalVar('ringaction',$ringaction);
sqgetGlobalVar('selectKey',$selectKey);
sqgetGlobalVar('keyaction',$keyaction);
sqgetGlobalVar('passphrase',$passphrase);
if (array_key_exists('cancelSelect',$_POST)) {
        $ringaction='';
}


$thru = ("pos=" . urlencode($pos) .
"&sort=" . urlencode($sort) .
"&desc=" . urlencode($desc) .
"&srch=" . urlencode($srch) .
"&ring=" . urlencode($ringName) .
"&ringaction=" . urlencode($ringaction));

if ($selectKey=='true') {
	switch($keyaction) {
		case "dispKey":
			$_POST['disp']='true';
			break;
		case "signKey":
			sqgetGlobalVar('secretfpr',$signfpr);
			if (array_key_exists('signkey',$_POST) && ($signfpr != '') && ($keyaction=='signKey')) {
				sqgetGlobalVar('exportable',$exportable);
				sqgetGlobalVar('revokable',$revokable);
				$revoke=($revokable=='true');
				$export=($exportable=='true');
				$ring=initGnuPG();
				$return=$ring->signKey($fpr,$signfpr,$passphrase,$export,$revoke);
				$err=$return['errors'];
			}
		case "signUID":
			sqgetGlobalVar('secretfpr',$signfpr);
			sqgetGlobalVar('UidNoLen',$uidlen);
			if (array_key_exists('signkey',$_POST) && ($signfpr != '') && ($uidlen>0) && ($keyaction=='signUID')) {
				sqgetGlobalVar('exportable',$exportable);
				sqgetGlobalVar('revokable',$revokable);
				$revoke=($revokable=='true');
				$export=($exportable=='true');
                                $uids='';
                                for ($i=1;$i<$uidlen;$i++) {
					if (array_key_exists('UidNo'.$i,$_POST)) {
                                        	$uids = $_POST['UidNo'.$i] . ' ' . $uids;
					}
                                }

//				$debug=1;
				$ring=initGnuPG();
				$return=$ring->signUID($fpr,$uids,$signfpr,$passphrase,$export,$revoke);
				$err=$return['errors'];
			}
		case "addUID":
			if (array_key_exists('adduid',$_POST)) {
				sqgetGlobalVar('full_name',$name);
				sqgetGlobalVar('email_address',$addr);
				sqgetGlobalVar('comment',$comment);
				$ring = initGnuPG();
				$return=$ring->addUID($fpr,$passphrase,$name,$addr,$comment);
				$err=$return['errors'];
			}
		case "delUID":
			sqgetGlobalVar('UidNoLen',$uidlen);
			if ($uidlen>0 && ($keyaction=='delUID')) {
				$uids='';
				for ($i=1;$i<=$uidlen;$i++) {
					if (array_key_exists('UidNo'.$i,$_POST)) {
						if ($debug) echo "Adding uid $i";
						$uids = "$uids " . $_POST['UidNo'.$i];
					}
				}
//				$debug=1;
				if  ($debug)  { print_r($_POST); echo "Deleting $uidlen UIDS  $uids from key $fpr\n"; }
				$ring=initGnuPG();
				$return=$ring->deleteUID($fpr,$uids);
				$err = $return['errors'];
				if ($debug) { echo "<br>Delete action completed.<br>"; }
			}
		case "addSubKey":
			if (array_key_exists('gensubkey',$_POST)) {
				sqgetGlobalVar('algorithm',$algo);
				sqgetGlobalVar('key_strength',$keysize);
				sqgetGlobalVar('key_expires',$key_expires);
				$ring = initGnuPG();
				$ring->refreshKeys($fpr);
				if ($ring->keys[$fpr]->haveSecret) {
					$return=$ring->addSubKey($fpr,$passphrase,$algo,$keysize,$key_expires);
					if (array_key_exists('newkeys',$return)) {
						$newkeys=$return['newkeys'];
						$info[] = _("New SubKey added with fingerprint ") . $return['newkeys'][0];
					} else {
						$err = $return['errors'];
						$err[] = _("Error adding new SubKey");
					}
				} else { $err[] = _("No Secret Key available to add a subkey to"); }
			}
		case "delSubKey":
			sqgetGlobalVar('SubKeyNo',$subkeyno); 
			if ($subkeyno!='' && ($keyaction=='delSubKey')) {
				$ring=initGnuPG();
				$return=$ring->deleteSubKey($fpr,$subkeyno);
				$err = $return['errors'];
			}
		case "expireKey":
			sqgetGlobalVar('key_expires',$expiration);
			if ($keyaction=='expireKey') {
//				$debug=1;
				$ring=initGnuPG();
				$return=$ring->expireKey($fpr,$passphrase,$expiration);
				$err = $return['errors'];
				$info = $return['info'];
			}
		case "expireSubKey":
			sqgetGlobalVar('SubKeyNo',$subkeyno);
			sqgetGlobalVar('key_expires',$expiration);
			if ($subkeyno!='' && ($keyaction=='expireSubKey')) {
//				$debug=1;
				$ring=initGnuPG();
				$return=$ring->expireSubKey($fpr,$subkeyno,$passphrase,$expiration);
				$err = $return['errors'];
				$info = $return['info'];
			}
		case "uploadKey":
			sqgetGlobalVar('keyserver',$keyserver);
			if ($keyserver!='' && array_key_exists('uploadkey',$_POST)) {
				$ring=initGnuPG();
				$return=$ring->uploadKey($fpr,$keyserver);
				$err = $return['errors'];
				$info = $return['info'];
			}
		case "saveKey":
			if (array_key_exists('trustid',$_GET)) {
				if ($_GET["checkVal"] == "1") {
		                        //Set as trusted key.
                    			setPref($data_dir, $username, 'trusted_key_id', $_GET['trustid']);
                    			setPref($data_dir, $username, 'use_trusted_key_id', 'true');
                		} else {
                    			//Not selected as trusted key now.  Was it?
                    			if (getPref($data_dir, $username, 'trusted_key_id') == $_GET['trustid']) {
                        			//This used to be the trusted key.  Erase the record.
                        			setPref($data_dir, $username, 'trusted_key_id', "");
                        			setPref($data_dir, $username, 'use_trusted_key_id','false');
                    			}
                		}

			}
			if (array_key_exists('signid',$_GET)) {
				if ($_GET["checkVal"] == "1") {
                    			//Set as signing key.
                    			setPref ($data_dir, $username, 'signing_key_id', $_GET['signid']);
                    			setPref ($data_dir, $username, 'use_signing_key_id', 'true');
                		}
                		else {
                    			//Not selected as trusted key now.  Was it?
                    			if (getPref($data_dir, $username, 'signing_key_id') == $_GET['signid']) {
                    				//This used to be the trusted key.  Erase the record.
                    				setPref ($data_dir, $username, 'signing_key_id', "");
                    				setPref ($data_dir, $username, 'use_signing_key_id', 'false');
                    			}
                		}
			}
                case "viewKey":
                default:
			if ($debug) echo "Enacting default action.<br>";
			if (!$debug) {
			if ($err) {
				if (is_array($err)) {
					$err=implode("<li>",$err);
				} else $err='<li>'.$err;
				$err=urlencode($err);
				Header("Location: keyview.php?$thru&fpr=$fpr&errors=$err");
				exit();
			}
			if (is_array($info)) {
				$info=implode("<li>",$info);
				$info=urlencode($info);
			} else { $info = urlencode($info); }
                        Header("Location: keyview.php?$thru&fpr=$fpr&info=$info");
                        exit();
			} else { echo "Debug on, so not returning to keyview by normal means.  Including instead:"; include_once(SM_PATH .'plugins/gpg/modules/keyview.php'); exit; }
			break;
		case "deletePair":
			$_POST['deletekey']='false';
			$_POST['deletepair']='true';
			break;
		case "deleteKey":
			$_POST['deletekey']='true';
			$_POST['deletepair'] = 'false';
			break;
		case "changePassUI":
			$_POST['cp']='true';
			break;
		case "changePass":
			$_POST['changepass'] = 'true';
			break;
		case "recvKey":
			$keyserver = $_POST['keyserver'];
			$keyID     = $_POST['keyID'];
			if ($debug) echo "Going to recieve key $keyid from $keyserver<br>";
			$ring = initGnuPG();
			$return = $ring->importKey_server($keyID,"hkp://$keyserver");
			$err=$return['errors'];
			$info=$return['info'];
			if ($return['newkeys']) {
				$newkeys = $return['newkeys'];
				$ringName = 'new';
			}
			break;
	}
}

$err = array();

if (array_key_exists('em',$_POST) && $_POST['fpr']) {
    require_once(SM_PATH.'plugins/gpg/gpg_options_header.php');

    //Get the text, set it as the email body.
    $ring = initGnuPG();
    $return = $ring->getExportText($_POST['fpr'], $ringName);
    $text=$return['output'];
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

if (array_key_exists('disp',$_POST) && $fpr) {
    global $GPG_VERSION;
    require_once(SM_PATH.'plugins/gpg/gpg_options_header.php');
    require_once(SM_PATH.'plugins/gpg/modules/gpg_module_header.php');

    $section_title = _("GPG Options - Display Key");
    echo gpg_section_header ( $section_title, $color[9] );

    //Get the text of the key
    $ring = initGnuPG();
    $text = $ring->getExportText($fpr, $ringName);
    $outtext=$text['output'];
    if ($outtext) {
        echo "<pre>$outtext</pre>";
    }
    echo '<form method="POST" action="keyring_main.php" name=keydisp>';
    echo '<input type="hidden" name="fpr" value="' . urlencode($fpr) . '">';
    echo '<input type="hidden" name="pos" value="' . urlencode($pos) . '">';
    echo '<input type="hidden" name="sort" value="' . urlencode($sort) . '">';
    echo '<input type="hidden" name="desc" value="' . urlencode($desc) . '">';
    echo '<input type="hidden" name="srch" value="' . urlencode($srch) . '">';
    echo '<input type="hidden" name="ring" value="' . urlencode($ringName) . '">';
    echo '<input type="hidden" name="selectKey" value="true">';
    echo '<input type="submit" name="can" value="' . _("Done") . '">';
    echo '</form>';
    $backlink="keymgmt";
    require_once (SM_PATH.'plugins/gpg/modules/gpg_module_footer.php');
    exit;
}


//Need to tell them to go secure?
global $notSecure;
if ($notSecure) {
    $err[] = _("This action can only be performed on a secure connection (https).  Please change the 'http' in your address bar to 'https', and try again.");
}

//user clicked Change Passphrase on secret keyview
if (array_key_exists('cp',$_POST) && $fpr) {
    if ($ringName == "secret") {
    //could call passphrase change rendering function here, but we'll use a location header for now
    Header("Location: changepass.php?" . $thru . '&fpr=' . $fpr);
    exit;
    }
    $err[] = _("Cannot change passphrase on key: ") . $fpr;
}
if (array_key_exists("changepass",$_POST) && ($fpr)) {
    sqgetGlobalVar('oldpassphrase',$oldpassphrase);
    sqgetGlobalVar('passphrase',$newpassphrase);
    sqgetGlobalVar('passphrase2',$newpassphrase2);
    sqgetGlobalVar('fpr',$fpr);

    if ($newpassphrase != $newpassphrase2) {
	$err[] = ("Your passphrases do not match.") . ' ' . _("Please try again.");
    } else {
	$ring = initGnuPG();

 //   if ($return['errors'][0]) {
//        $err=$return['errors'];
//    }
    //Get the key.
        if (!$ring->keys[$fpr]) {
            $return = $ring->refreshKeys($fpr);
	}
        if (!$oldpassphrase) {
            $err[] = _("No old passphrase passed.  Possibly blank password?");
        } else {
            if ($debug) { echo "trying to change" . '<P>'; }

            $return = $ring->changePassphrase($fpr, $oldpassphrase, $newpassphrase);

	    if ($return['verified'] == 'true') {
                $err=array_merge($err, $return['errors']);
                if ($return['output']) {
                    if ($debug) { print_r($return['output']); }
                }
            } else {
                $err[] = _("Bad Passphrase.");
            }
        }
    }
    if ($err) {
      if (!$debug) {
            Header("Location: changepass.php?" . $thru . '&fpr=' . $_POST['fpr'] . '&err=' . urlencode(implode('',$err)));
	exit;
      } else {
	   echo "Errors changing passphrase: <pre>"; print_r($return); print_r($err); echo "</pre><p>";
      }
    } else {
	$info[]= _("Passphrase change successful.");
    }
}
//Take any action requested (only one).
if (((array_key_exists("deletekey",$_POST) && $_POST["deletekey"]=='true') || (array_key_exists("deletepair",$_POST) && $_POST["deletepair"]=='true')) && $fpr) {
    //Deleting a key.
    //What type of deletion?
    if (($ringName == "secret") && ($_POST['deletepair']=='false')) $type = "private";
    else $type = "all";

    $ring = initGnuPG();
    $return = $ring->fetchKeys($fpr);
    $key=$ring->getKey($fpr);
    if ($type == 'private' || $_POST['deletepair']=='true') {
    	if (!$key->haveSecret) {
    	    $err[] = _("No secret key found.");
    	}
	if ($GLOBALS['GPG_SYSTEM_OPTIONS']['requirepassphraseonkeydelete']=='true') {
		$return = $ring->verifyPassphrase($passphrase,$fpr);
		if ($return['verified'] != 'true') {
			$err[] = _("Bad Passphrase.");
		    	if ($debug) {
		        	$err[] = "KeyID: " . $fpr ." failed to delete.";
		    	}
	        }
       }
    }
    if (array_key_exists(0,$err)) {
    Header("Location: keyview.php?" . $thru . '&fpr=' . $fpr . '&errors=' . urlencode(implode('',$err)));
    exit;
    } else {
    //Do it
        //If we are deleting the public key
        if($type == 'all') {
            //Not selected as trusted key now.  Was it?
	    if (getPref($data_dir, $username, 'trusted_key_id') == $key->id) {
                //This used to be the trusted key.  Erase the record.
                setPref ($data_dir, $username, 'trusted_key_id', "");
                setPref ($data_dir, $username, 'use_trusted_key_id', 'false');
            }
        }
        //Not selected as signing key now.  Was it?
	if (getPref($data_dir, $username, 'signing_key_id') == $key->id) {
            //This used to be the signing key.  Erase the record.
            setPref ($data_dir, $username, 'signing_key_id', "");
            setPref ($data_dir, $username, 'use_signing_key_id', 'false');
        }
    
        $ret = $ring->deleteKey($fpr, $type);
        if (array_key_exists('errors',$ret)) { if (count($ret['errors'])>0) $err.= implode('',$ret['errors']); }
//        unset($_POST["fpr"]);
        unset($ring);
    }
}

//Check for url import
else if($_GET["url"]) {
    while(1) {
	// Hack for errors.
	// Damn PHP and its lack of goto or labelled "last" or "break" for "if"!
	//sanitize url:
	if(preg_match("%^(https?)://([a-zA-Z0-9-\.]+)(?:/([^/]*))*$%", $_GET["url"], $matches)) {
	    array_shift($matches);
	    $keyurl = array_shift($matches) . '://' . array_shift($matches);
	    foreach($matches as $part) {
		$keyurl .= "/" . urlencode(urldecode($part));
	    }
	}
	if(!$keyurl) {
	    $err[] = $_GET["url"] . ": " . _("Unsupported url or unable to parse url");
	    break;
	}
	$f = fopen($keyurl, "r");
	if (!$f) {
	    $err[] = "$keyurl: " . _("Unable to connect to the server to retrieve key");
	    break;
	}
	for($i = 0; $i < 250; $i++) {
	    $rawkeytext .= fread($f, 1024);
	    if(feof($f)) break;
	}
	if(preg_match_all("/-----BEGIN PGP PUBLIC KEY BLOCK-----.*?-----END PGP PUBLIC KEY BLOCK-----/s", $rawkeytext, $matches)) {
	    $keytext = implode("\n", $matches[0]);
	    $keytext .= "\n";
	} else {
	    $err[] = "$keyurl: " . _("Could not find key in data");
	    break;
	}
	// taken from else if below
	$ring = initGnuPG();
	$ret = $ring->importKey_text($keytext);
	$info = $ret['info'];
	if (array_key_exists('errors',$ret)) $err = $ret['errors'];
	if ($ret['newkeys']) {
	    $newkeys=$ret['newkeys'];
	    $ringName = 'new';
	} else {
	    break;
	}
	if ($_GET["fingerprint"]) {
	    // verify the fingerprint
	    $fpr_ver = $_GET["fingerprint"];
	    foreach($newkeys as $fpr => $other) {
		if($fpr != $fpr_ver) {
		    $ring->deleteKey($fpr);
		    unset($newkeys[$fpr]);
		}
	    }
	    $ring->refreshKeys();
	    if(count($newkeys) <= 0) {
		$ringName = 'all';
		array_push($err, "$keyurl: " . _("No key with specified fingerprint found at address"));
		$info = array();
		break;
	    }
	} else if ($_GET["id"]) {
	    // I have a sinking feeling the following is wrong in some special cases (version??)...
	    $id_ver = $_GET["id"];
	    $len = strlen($id_ver);
	    foreach($newkeys as $fpr => $other) {
		if(substr($other['id'], -$len) != $id_ver) {
		    $ring->deleteKey($fpr);
		    unset($newkeys[$fpr]);
		}
	    }
	    $ring->refreshKeys();
	    if(count($newkeys) <= 0) {
		$ringName = 'all';
		array_push($err, "$keyurl: " . _("No key with specified id found at address"));
		$info = array();
		break;
	    }
	}
	break;
    }
}

else if (array_key_exists("textadd",$_POST) && $_POST["keystring"]) {
    //Importing a key via text.
    $ring = initGnuPG();
    $ret = $ring->importKey_text($_POST["keystring"]);
    if ($ret['newkeys']) {
    	$newkeys=$ret['newkeys'];
        $ringName = 'new';
    }
    $info = $ret['info'];
    if (array_key_exists('errors',$ret)) $err = $ret['errors'];
}

else if (array_key_exists("fileadd",$_POST)) {
    if (is_uploaded_file($_FILES['keyfile']['tmp_name'])) {
    //Importing a key via text.
//    $debug=1;
    $ring = initGnuPG();
    $ret = $ring->importKey_file($_FILES['keyfile']['tmp_name']);
    if ($ret['newkeys']) {
    	$newkeys=$ret['newkeys'];
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

else if (array_key_exists("keysav",$_POST) && $_POST['id']) {
        if (array_key_exists('signing',$_POST)) {
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
	}
	if (array_key_exists('trust',$_POST)) {
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

@ob_end_flush();
ob_start();


//Rectify search info.
if ($ringName == "secret") {
    //Don't want srch if viewing the secret ring
    $srch = "";
}
else {
    //Get and fix srch.
    if (array_key_exists('newsrch',$_POST)) { $srch = $_POST["newsrch"]; }
    else  {
	if (array_key_exists("srch", $_GET)) { 
    	   $srch = $_GET["srch"]; 
	} else { 
	   if(array_key_exists("srch",$_POST)) { 
		$srch=$_POST["srch"];
	   } else { $srch=""; }
        }
    }
    if (strlen($srch) > 30) $srch = "";
}
//Fetch the keys.  If there is no $srch, don't fetch!
//We will then direct the user to specify a search.
//This has to be done because gpg has no options to restrict the number
//of keys returned (other than search).
if (!isset($ring)) { $ring = initGnuPG(); }
if (($ringName!='new') and (!isset($newkeys))) {


	$ring = initGnuPG();

    //if ($srch || ($ringName == "secret")) {
    $ret = $ring->fetchKeys($srch, $ringName);
//        if (array_key_exists(0,$ret['errors'])) { echo _("Error: "); print_r($ret['errors']); }
} else { $newkeystr=''; foreach ($newkeys as $nkey) { $newkeystr.= " '$nkey'"; } $ret=$ring->fetchKeys($newkeystr,$ringName); }
//Rectify the sort info.
$allowedSort = array("email_name", "email_addr", "date");
$sort = (array_key_exists('sort',$_GET) ? $_GET["sort"] : (array_key_exists('sort',$_POST) ? $_POST["sort"] : ""));
if (! ($sort && in_array($sort, $allowedSort))) $sort = "email_name";
$desc = (array_key_exists('desc',$_GET) ? $_GET["desc"] : (array_key_exists('desc',$_POST) ? $_POST["desc"] : ""));
//$ring->makearrayKeys();
//if ($debug) { echo "Diff: <pre>"; print_r($ring->arraykeys); echo "\nNowOldOne:\n"; print_r($oldring->keys); echo "</pre><p>"; }
//Sort.
if ($debug) { echo "Sorting now.<br>"; }
$ring->sortKeys("$sort", ($desc ? false : true));
//if ($debug) { echo "Diff: <pre>"; print_r($ring->arraykeys); echo "\nNowOldOne:\n"; print_r($oldring->keys); echo "</pre><p>"; }
//default chunk size.
$chunkSize = 10;

//Get the key map as chunks.
$chunkMap = $ring->getKeyMap_chunked($chunkSize);

//if ($debug) { echo "Diff: <pre>"; print_r($chunkMap); echo "\nNowOldOne:\n"; print_r($oldchunkMap); echo "</pre><p>"; }

//if ($debug) { print_r($chunkMap); }
//Get chunkNum.
//The "+0" converts it to a number.  Otherwise is_integer won't work (looks like a php bug).
$pos = (array_key_exists('pos',$_GET) ? $_GET["pos"] : (array_key_exists('pos',$_POST) ? $_POST["pos"] : "")) + 0;

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
$thru = "sort=$urlSort&desc=$urlDesc&ring=$urlRing&ringaction=$ringaction&srch=$urlSrch";
$thru_noSrch = "sort=$urlSort&desc=$urlDesc&ring=$urlRing&ringaction=$ringaction";
$thru_noRing = "sort=$urlSort&desc=$urlDesc&srch=$urlSrch&ringaction=$ringaction";
$thru_noDesc = "sort=$urlSort&ring=$urlRing&srch=$urlSrch&ringaction=$ringaction";
$thru_noSort = "desc=$urlDesc&ring=$urlRing&srch=$urlSrch&ringaction=$ringaction";

//Trusted key?
if ($ringName == "secret") {
    $signingKey = getPref ($data_dir, $username, 'signing_key_id');
    $trustedKey = '';
}
else {
    $signingKey ='';
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
echo '<input type=hidden name=ringaction value="' . $ringaction . '">';

echo '</td></tr>';

echo '<tr><td colspan="3">';

echo '<b>';
echo _("Showing keys in");
echo ' ';
echo '</b>';

echo  '<select name="ring"><option value="public">';
echo _("Your Public Keyring");
echo  '</option>';

if ($newkeys and $ringName=='new')
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

echo '<input type="submit" name="SearchButton" value="';
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
    echo "<a href=\"keyring_main.php?$thru_noSrch\">";
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
if ($ringaction=='selectKey') {
	echo '<td>&nbsp;</td>';
}
echo '<td width="33%">';

echo '<b>';
    if ($sort == "email_name")
    {
        echo _("Key");
        echo '&nbsp;';

        echo "<a href=\"keyring_main.php?$thru_noDesc\"";
        echo ((! $desc) ? "&desc=1" : "");
        echo '">';

        echo '<img src="../img/';
        echo ($desc ? "up" : "down");
        echo '.gif" height="13" width="13" border="0">';
        echo '</a>';
    }
    else
    {
        echo "<a href=\"keyring_main.php?sort=email_name&$thru_noSort\">";
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

        echo "<a href=\"keyring_main.php?$thru_noDesc";
        echo ((! $desc) ? "&desc=1" : "");
        echo '">';

        echo '<img src="../img/';
        echo ($desc ? "up" : "down");
        echo '.gif" height="13" width="13" border="0">';
        echo '</a>';
    }
    else
    {
        echo "<a href=\"keyring_main.php?sort=email_addr&$thru_noSort\">";
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

        echo "<a href=\"keyring_main.php?$thru_noDesc";
        echo ((! $desc) ? "&desc=1" : "");
        echo '">';

        echo '<img src="../img/';
        echo ($desc ? "up" : "down");
        echo '.gif" height="13" width="13" border="0">';
        echo '</a>';
    }
    else
    {
        echo "<a href=\"keyring_main.php?sort=date&$thru_noSort\">";
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
    echo '<tr bgcolor="' . $color[0] . '">';
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
        echo '<tr bgcolor="' . $color[0] . '">';
	if ($ringaction=='selectKey') { echo '<td><input type=radio name=fpr value="' . $fpr  . '"></td>'; }
	echo '<td>'
             . "<a href='keyview.php?pos=$urlPos&fpr=$fpr&$thru'>"
             . $data['email_name'] 
             . '</a></td><td>'
             . (array_key_exists('email_addr',$data) ? $data["email_addr"] : "(" ._("None") . ")")
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
    echo "<a href=\"keyring_main.php?$thru_noSrch\">";
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
if ($ringaction=="selectKey") {
	echo '<tr><td><input type=submit name=selectKey value="' . _("Select Key") . '"><input type=submit name=cancelSelect value="' . _("Cancel") . '"></td></tr>';
}
echo '</form>';

echo '</table>';

if ($ringName != "secret")
{
    echo '<form method="POST" action="keyring_main.php">';
    echo "<input type=\"hidden\" name=\"sort\" value=\"$htmlSort\">";
    echo "<input type=\"hidden\" name=\"desc\" value=\"$htmlDesc\">";
    echo "<input type=\"hidden\" name=\"ring\" value=\"$htmlRing\">";
    echo "<input type=\"hidden\" name=\"pos\" value=\"$htmlPos\">";
    echo "<input type=\"hidden\" name=\"ringaction\" value=\"$ringaction\">";
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
echo '<br>'
     . ' <a href="../gpg_options.php">'
     . _("GPG Plugin Options")
     . '</a>';

echo '</td></tr>';

echo '</table>';
$backlink="main";
$backpath="../";
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
 * Revision 1.66  2006/01/08 02:47:20  ke
 * - committed patch from Evan <umul@riseup.net> for OpenPGP header support in squirrelmail
 * - adds system preferences and user options to control parsing and adding of OpenPGP Headers on emails
 * - slightly tweaked to use the key associated with the identity, when identities with signing keys are enabled
 *
 * Revision 1.65  2005/11/10 06:57:06  ke
 * - patch to add PHP 4.1 compatibility
 * - thanks to Thijs Kinkhorst, who found and fixed the issue
 * Bug 251
 *
 * Revision 1.64  2005/06/09 14:32:32  ke
 * - changed to reference object instead of array for key information
 *
 * Revision 1.63  2004/08/23 09:20:00  ke
 * store new keys seperately from keyring for future searches anytime a key could have been added
 * added handler for keyserver downloads and display
 * Bug 29
 *
 * Revision 1.62  2004/08/23 08:12:57  ke
 * -fixed redirection to keyview for key based actions
 *
 * Revision 1.61  2004/08/23 07:47:46  ke
 * only refresh keys when key is not available
 * fix where errors were being passed as an array
 *
 * Revision 1.60  2004/08/22 23:30:21  ke
 * -removed html nbsp's from error messages
 * bug 202
 *
 * Revision 1.59  2004/08/09 17:58:53  ke
 * -added backlink to gpg options page
 * -changed last functions to use to GnuPG object
 * -include files and set options for GPG_VERSION to appear properly
 *
 * Revision 1.58  2004/06/23 14:20:26  ke
 * -hiding debug output for certain keyring actions
 *
 * Revision 1.57  2004/03/30 01:51:06  ke
 * -added expiration of subkeys, keys to main keyring
 * -changed to use GnuPG object to confirm passphrase and delete keys
 *
 * Revision 1.56  2004/03/16 20:29:18  ke
 * -changed loop for info to work properly, reporting back on keyview  screen
 * -added uploadkey  case to handle submission and run upload code
 * bug 27
 *
 * Revision 1.55  2004/03/11 21:42:44  ke
 * -changed error handling routine to correctly handle array
 *
 * Revision 1.54  2004/03/11 21:33:12  ke
 * -removed debug on sign key action
 *
 * Revision 1.53  2004/03/09 18:44:43  ke
 * -changed error string to show properly in keyview when deleting a keypair with a bad passphrase
 *
 * Revision 1.52  2004/03/09 18:11:10  ke
 * -added cases for new key functions
 *
 * Revision 1.51  2004/03/04 01:49:12  ke
 * -added options to handle new keyview features
 *
 * Revision 1.50  2004/03/03 19:48:54  ke
 * -added options to show keyring with select options.
 * -othter misc fixes
 *
 * Revision 1.49  2004/02/17 22:52:11  ke
 * -E_ALL changes
 * -changed to directly call GnuPG object for searches
 * bug 29
 *
 * Revision 1.48  2004/01/30 21:36:58  ke
 * -more string fixes to allow pass through of variables
 *
 * Revision 1.47  2004/01/30 21:21:55  ke
 * --changed quotes to allow new search link to function
 *
 * Revision 1.46  2004/01/30 21:13:16  ke
 * -changed search to operate properly again
 * -removed simplistic error handling
 *
 * Revision 1.45  2004/01/25 19:05:49  joelm
 * -added a check when deleting a key to see if we are deleting either the signing key or the trusted key. If we are, unset the appropriate option(s) in the users prefs.
 * Bug 67
 *
 * Revision 1.44  2004/01/19 19:03:02  ke
 * -E_ALL fixes
 *
 * Revision 1.43  2004/01/19 18:54:28  ke
 * -E_ALL fixes
 *
 * Revision 1.42  2004/01/17 00:26:11  ke
 * -E_ALL fixes
 *
 * Revision 1.41  2004/01/15 20:40:48  ke
 * -changed link to go to default options page instead of general options
 *
 * Revision 1.40  2004/01/15 18:42:21  ke
 * -added link to GPG Options from the keyring
 *
 * Revision 1.39  2004/01/14 23:35:59  ke
 * -added include for gpg_execute.php
 *
 * Revision 1.38  2004/01/13 01:48:32  brian
 * fixed colors to use SM color array, per patch from Chris Wood
 *
 * Revision 1.37  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
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
