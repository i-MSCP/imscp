<?php
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')) {
        define ('SM_PATH', '../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../../../../');
    } else echo "unable to define SM_PATH in GPG Plugin setup.php, exiting abnormally";
}
if ($_GET["url"]) {
    //Importing a key via url.
    require(SM_PATH."plugins/gpg/modules/keyring_main.php");
} else {
    $_POST["search"] = "1";
    $_GET["MOD"] = "keyserver";
    if ($_GET["id"]) {
	if(!strpos($_GET["id"], "0x")) {
	    $_GET["id"] = "0x" . $_GET["id"];
	}
	//Importing a key via id.
	$_POST["search_keyid"] = $_GET["id"];
    } else if ($_GET["fingerprint"]) {
	//Importing a key via fingerprint.
	//(We can try...)
	if(!strpos($_GET["fingerprint"], "0x")) {
	    $_POST["search_keyid"] = "0x" . $_GET["fingerprint"];
	} else {
	    $_POST["search_keyid"] = $_GET["fingerprint"];
	}
    } else {
	// error; no input.  Let lower layer figure it out, though...
    }
    require(SM_PATH."plugins/gpg/gpg_options.php");
}

?>