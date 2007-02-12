<?php
/**
* importkey.mod
* -----------
*
* This code imports a key from the body of an email.
*
* Copyright (c) 2003 Braverock Ventures
* Licensed under the GNU GPL. For full terms see the file COPYING.
*
*
* $Id: importkey.mod,v 1.5 2003/11/04 21:41:01 brian Exp $
*/
/*********************************************************************/

require_once(SM_PATH.'plugins/gpg/gpg_key_functions.php');
require_once(SM_PATH.'functions/imap.php');
require_once(SM_PATH.'config/config.php');
require_once(SM_PATH.'plugins/gpg/gpg_keyring.php');
$redirect_path=SM_PATH.'plugins/gpg/modules/';

global $debug;
//pull the variables we need from the submitted data
if (isset($_GET['passed_id'])) {
    $passed_id = (int) $_GET['passed_id'];
} elseif (isset($_POST['passed_id'])) {
    $passed_id = (int) $_POST['passed_id'];
}
if (isset($_GET['mailbox'])) {
    $mailbox = $_GET['mailbox'];
} elseif (isset($_POST['mailbox'])) {
    $mailbox = $_POST['mailbox'];
}

$username   = $_SESSION['username'];
$key        = $_COOKIE['key'];
$onetimepad = $_SESSION['onetimepad'];
$base_uri   = $_SESSION['base_uri'];
$delimiter  = $_SESSION['delimiter'];
if ($debug) {
    echo "<br>username = $username<br>"
         . "passed_id = $passed_id<br>"
         . "mailbox = $mailbox";
}

//resize the window to display the plaintext in it.
echo '<script language="javascript1.2" type="text/javascript1.2">' . "\n"
    . "<!--\n"
    . 'self.resizeTo(600, 400)'
    . "\n//-->\n"
    . '</script>';

//get the body text
global $uid_support;
$imapConnection = sqimap_login($username, $key, $imapServerAddress,
                               $imapPort, 0);
if ($imapConnection==false){
    echo 'Connection to Imap Server to retrieve message body failed<br>';
};

$read = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);
if ($debug) {
    echo "<br>Mailbox Select returned:$read<br>";
}
$body_text=sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[]",
                                true, $a, $b,$uid_support);

if (is_array($body_text)) {
     $body_text = implode($body_text,'');
};

/*
//now parse the return value to strip off the email headers
$sep = '-----BEGIN PGP PUBLIC KEY BLOCK-----';

list ($header, $cyphertext_tail) = explode ($sep, $body_text);

    if ($debug) {
        echo "<hr>Message Header: $header";
        echo "<br>Message Body: $cyphertext_tail";
    };

//$body_text = "$sep$cyphertext_tail";
*/

sqimap_logout($imapConnection);

if ($debug) {
    echo '<br>Body Text<br><pre>'
         .  "$body_text"
         .  '</pre>'
         .  '<br> Now calling gpg_import_key function <br>';

}

//now call import
$ring = new gpg_keyring();
$return = $ring->importKey_text($body_text);

/* old version of the import  */
//$return = gpg_import_key($body_text,$debug);


header("Location: " . $redirect_path . "keyring_main.php");

/*
* $Log: importkey.mod,v $
* Revision 1.5  2003/11/04 21:41:01  brian
* change to use SM_PATH
*
* Revision 1.4  2003/11/01 21:56:52  brian
* - removed $msg strings and gpg_Makepage functionality
* - localized remaining strings
*
* Revision 1.3  2003/09/19 18:27:08  ke
* Brought back importkey.mod in modules/ directory, added functionality to call keyring management after import.
*
* Revision 1.2  2003/08/14 02:39:30  vermette
* replaced with UI in consolidated interface
*
* Revision 1.1  2003/07/07 20:34:59  brian
* Initial Revision
* - code to import ASCII Armored keys from email body
* Bug 46
*
*/
?>