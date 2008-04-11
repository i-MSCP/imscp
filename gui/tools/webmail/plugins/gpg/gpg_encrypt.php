<?php
/**
 * gpg_encrypt.php
 * --------------------
 * Called from compose to encrypt a message.
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Brian Peterson
 *
 * $Id: gpg_encrypt.php,v 1.80 2005/07/27 14:07:48 brian Exp $
 *
 */
ob_start();
$no_encrypt_on_setup = 0;
$chdir_first = 0;
/**
 * load the functions files or set SM_PATH
 */
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_encrypt_functions.php')) {
        define('SM_PATH', '../../');

    } elseif (file_exists('../plugins/gpg/gpg_encrypt_functions.php')) {
        define('SM_PATH', '../');
    } else {
        echo "<br> unable to define SM_PATH in  gpg_encrypt.php, exiting abnormally\n";
        exit;
    }
}
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_encrypt_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_execute.php');

/*********************************************************************/
/**
 * Let's assume that we get the following from a submit
 *        <INPUT TYPE=text NAME="send_to"
 *        <INPUT TYPE=text NAME="send_to_cc"
 *        <INPUT TYPE=text NAME="send_to_bcc"
 *        <INPUT TYPE=text NAME=subject
 *        <TEXTAREA NAME=body <...>
 *
 * All of these fields are in the compose.php page.
 */
global $path_to_gpg;
global $body;
global $subject;
global $gpg_key_file;
global $gpg_key_dir;
global $draft;
global $attachment_dir;


$body        = (array_key_exists('body',$_POST) ? $_POST['body'] : "");
$encrypt     = (array_key_exists('encrypt',$_POST) ? $_POST['encrypt'] : "");
$sign        = (array_key_exists('gpgsign',$_POST) ? $_POST['gpgsign'] : "");
$send_to     = (array_key_exists('send_to',$_POST) ? $_POST['send_to'] : "");
$send_to_cc  = (array_key_exists('send_to_cc',$_POST) ? $_POST['send_to_cc'] : "");
$send_to_bcc = (array_key_exists('send_to_bcc',$_POST) ? $_POST['send_to_bcc'] : "");
$subject     = (array_key_exists('subject',$_POST) ? $_POST['subject'] : "");
$passphrase  = (array_key_exists('passphrase',$_POST) ? $_POST['passphrase'] : "");
//clear encrypt_on_send if set
//$_POST['encrypt_on_send'] = '';

$return['errors'] = array();
$return['warnings'] = array();
$return['skipped_keys'] = array();

$trimmed['skipped_keys'] = array();
$done=0;

$notclean=0;
$serious=0;

//load the allow partial encryption preference so we know whether to loop.
$allow_partial_encryption=getPref($data_dir, $username, 'allow_partial_encryption');

if (gpg_is_passphrase_cached()) {
    if ($debug) {  echo "<br>Grabbing cached passphrase\n"; }
    $passphrase=gpg_get_cached_passphrase();
}

if ($debug) {
    echo "<br> Entering gpg_encrypt.php processing.\n";
    echo "<br> Encrypt: $encrypt\n";
    echo "<br> Sign:  $sign\n";
}

global $compose_messages;
global $session;
if ($debug) {
    echo "<br>Session: $session\n";
    echo "<br>Compose Messages:$compose_messages\n";
}
if (!isset($compose_messages[$session])){
    if ($debug) {
        echo "<br> Pulling $compose_messages from sqgetGlobalVar.\n";
    }
    sqgetGlobalVar('compose_messages',  $compose_messages,  SQ_SESSION);
    sqgetGlobalVar('session',$session);
} elseif ($debug) {
    echo "<br>Got $compose_messages as an object (prexisting global var).\n";
}
if (array_key_exists('encrypt_on_send_error',$_GET)) {
    echo '<br>'._("Your Request to Encrypt on Send encountered a problem, details below:")."<br>\n";
    if (!isset($session)){$session=$_GET['session'];};
    sqgetGlobalVar('encrypt_error', $encrypt_error, SQ_SESSION);
if ($debug) {
    echo 'Encrypt_error:<br><pre>'; print_r($encrypt_error); echo '</pre>';
}
    $subject                 = $encrypt_error['subject'];
    $send_to                 = $encrypt_error['send_to'];
    $send_to_cc              = $encrypt_error['send_to_cc'];
    $send_to_bcc             = $encrypt_error['send_to_bcc'];
    $body                    = $encrypt_error['body'];
    $return['errors']        = $encrypt_error['errors'];
    $return['warnings']      = $encrypt_error['warnings'];
    $trimmed['skipped_keys'] = $encrypt_error['skipped_keys'];
    //now clear our data out of the session variables..
    $encrypt_error = '';
    $_GET['encrypt_on_send_error']=0;
    sqsession_register($encrypt_error , 'encrypt_error');
    global $debug;
    $done=1;
    $notclean=1;
};

$cyphertext=false;
//call the address parsing function to return an array of addresses
$valid_addresses = gpg_parse_address ($send_to, $send_to_cc, $send_to_bcc, $debug);
$working_addresses = $valid_addresses;
//loop until we don't have anything else to do: no more skipped keys, no other errors, etc.
while (!$done) {
    // create the recipientlist string from the $working_addresses array
    $recipientlist = join (" -r ", $working_addresses);

    if ($debug){
        echo "<br>Parsed Recipient List";
        echo "<br>Recipient List: $recipientlist";
    };
    // now check to see how this page was called, and
    // call gpg_encrypt with the appropriate flags
    if (($encrypt) && !($sign=='true')) {
        if ($debug) echo "<br>Entering Encrypt Function\n";
        //call gpg_encrypt with the recipient list
        $return = gpg_encrypt($debug, $body, $recipientlist, false, '');
        $cyphertext=$return['cyphertext'];
    };
    if ($sign=='true') {
        if ($debug) echo "<br>Entering Encrypt and Sign Function\n";
        //call gpg_encrypt with the recipient list, and sign='true'
        $return = gpg_encrypt($debug, $body, $recipientlist, $sign , $passphrase);
        $cyphertext=$return['cyphertext'];
    };
    $done = 1;
    foreach ($return['skipped_keys'] as $skipped_key) {
        //add the missing key to the error list to be output to the browser
        $trimmed['skipped_keys'][] = $skipped_key;
        //find the key int he recipient list and remove it from the array
        foreach ($working_addresses as $key => $email) {
            //unquote the email address
            $email = str_replace ( "'", '', $email);
            if ($debug) echo "<br>Checking for: '$email' in '$skipped_key'\n";
            //remove them from the working_addresses array
            if (substr_count($skipped_key, $email)) {
                unset($working_addresses[$key]);
                if ($debug) echo "<br>Deleting this Recipient: $email\n";
                //set $done=0 so we try again
                $done = 0;
            };
        };
        //Allow Partial Encryption Test
        if ($allow_partial_encryption != 'true') {
            $serious=1;
            //force to return the plaintext.
        };
    };
};

//display the cyphertext in debug mode
if ($debug) {
    echo '<hr><br>Cyphertext after return from gpg_encrypt function';
    echo '<pre>'.$cyphertext.'</pre>';
};

/************************************************************/
// parse and display our errors
// echo the errors and warning to this page before continuing.

//check the warnings array
if (is_array($return['warnings'])) {
   if (count($return['warnings']) > 0) {
    echo "<br><b>" . _("Warnings:") . "</b><ul>\n";
    foreach ($return['warnings'] as $warning) {
        $notclean=1;
        echo htmlspecialchars($warning) . '<br>';
    };
        echo '</ul>';
    }
}

//check the errors array
if (is_array($return['errors'])) {
    if (count($return['errors']) > 0) {
    echo "<br><b>" . _("Errors:") . "</b><ul>\n";
    foreach ($return['errors'] as $errors) {
        $notclean=1;
        $serious=1;
        echo htmlspecialchars($errors) . '<br>';
    };
    echo '</ul>';
    }
}

//check the complete skipped_keys array
if (is_array($trimmed['skipped_keys'])) {
    if (count($trimmed['skipped_keys']) > 0) {
    echo '<br>'._("You do not have a public key for the following email addresses and so your message will NOT be readable by these recipients.");
    echo '<br>'._("Here are the keys which GPG reported as missing:") . "<ul>\n";
    foreach ($trimmed['skipped_keys'] as $skipped_key) {
        $notclean=1;
        echo htmlspecialchars($skipped_key)."<br>\n";
    };
    echo '</ul>';
    }
}

//check the info array
if (array_key_exists('info',$return)) {
    if (count($return['info']) > 0) {
        echo "<br><b>" ._("Info:") . "</b><ul>\n";
    foreach ($return['info'] as $info) {
        echo htmlspecialchars($info) . '<br>';
    };
        echo '</ul>';
    }
}

//make sure we have cyphertext
if (!$cyphertext) {
    $serious = 1;
    if ($debug){
        echo "<br>Cyphertext is empty, setting serious to 1 to preserve plaintext";
    };
};

/**
 * Begin Attachment Handling
 *
 * Marc indicates that this won't work under SM 1.2.x
 */
    if ($debug) {
        echo '<hr><b>Begin Attachment Processing</b>';
    };
    if (!isset($composeMessage)) {
    global $composeMessage;
    }
    $newMessage = $composeMessage[1];
    //set $compose message to be a reference to the correct $message in $compose_messages
    if (!is_object ($newMessage)){
        $newMessage =& $compose_messages[$session];
    };
    //check here to see if we need to use restoremessages
    if (!is_object($compose_messages[$session])) {
        if ($debug){
            echo "<br> no session data found, restoring from restoremessages\n";
        };
        $restoremessages = $_POST['restoremessages'];
        $compose_messages = unserialize(urldecode($restoremessages));
        $newMessage =& $compose_messages[$session];
        /**
         * @todo probably need to do a check in here for
         * session_expired_post, like in compose.php
         */

    };
    if ($debug) {
        echo '<br><b>Contents of Message object</b><br><pre>';
        print_r ($newMessage);
        echo "</pre>\n";
    }
    /**
     * use the built-in function message->getAttachments to pull out the atachments
     *
     * each attachment returned by this function is a an object of class message
     *
     * Marc made this copy, not pass by reference.
     */
    $messageAttachments = $newMessage->getAttachments ();

    /**
     * id for the first attachment will be 0, unless we are called from the
     * compose_send hook, then it could be 1
     */
    $id=0;
    $path = '../';
    if (isset($encrypt_on_send)) {
        $path = '';
        //compose_send hook moved to after the message body has been inserted at $id 0
        $id=1;
    };

    /**
     * $messageAttachments[0]->mime_header->disposition->name =='attachment'
     * if getAttachments found an attachment, otherwise this will be null
     * if it is null, we skip all the attachment processing
     */
    $checkattachment=strcmp(strtolower($messageAttachments[$id]->mime_header->disposition->name),'attachment');
    if (!$checkattachment) {
        if ($debug) {
            echo '<br> Found Attachments.';
        };
        foreach ($messageAttachments as $key => $attachment) {
            if (!(($id==1) && ($key==0))) {
                if ($debug) {
                    echo '<BR>Attachment Name: '
                        . $attachment->mime_header->disposition->properties['filename'] ."\n";
                    echo '<BR>Local File Name: '. $attachment->att_local_name ."\n";
                    echo '<BR>Original Mime Type:'. $attachment->mime_header->type0 .'/'
                                                . $attachment->mime_header->type1 ."\n";
                };
                //rename the file to it's correct public file name
                    $safe_attachment_dir = getHashedDir($username, $attachment_dir);
                    $tempfile = $path.$attachment->att_local_name;
                    $filename = $safe_attachment_dir.'/'.$attachment->mime_header->disposition->properties['filename'];
                    if (copy( $tempfile, $filename) ) {
                        deleteTempFile($tempfile);
                    } else {
                        echo _("Unable to rename temporary file");
                    }
                //call gpg_encrypt fn to encrypt the attachment
                    $attreturn = gpg_encrypt($debug, '', $recipientlist, $sign , $passphrase, $filename);
                //delete the plaintext attachment, and set all the correct mime types
                if (!count($attreturn['errors'])) {
                    //delete the tempfile
                    deleteTempFile($filename);
                    //rename the asc file to the temp name
                    if (copy( $filename.'.asc', $tempfile.'.asc')) {
                        deleteTempFile($filename.'.asc');
                    } else {
                        echo _("Unable to rename encrypted file");
                    }
                    $entity_id = (int)$key;
                    $entity =& $newMessage->entities[$entity_id];
                    if (trim($entity->att_local_name) == trim($attachment->att_local_name)) {
                        //set the mime type
                        $entity->mime_header->type0 = 'application';
                        $entity->mime_header->type1 = 'pgp-encrypted';
                        $newfilename = $attachment->mime_header->disposition->properties['filename'].'.asc';
                        $entity->mime_header->disposition->properties['name'] = $newfilename;
                        $entity->mime_header->disposition->properties['filename'] = $newfilename;
                        $entity->mime_header->parameters['name'] = $newfilename;
                        $entity->mime_header->parameters['filename'] = $newfilename;
                        //set the name of the attachment to be the .asc file
                        $entity->att_local_name = $attachment->att_local_name . '.asc';
                        if ($debug) {
                            echo '<BR>New Attachment Name: '
                                . $entity->mime_header->disposition->properties['filename'] ."\n";
                            echo '<BR>New Local File Name: '. $entity->att_local_name ."\n";
                            echo '<BR>New Mime Type: '
                                . $entity->mime_header->type0 .'/'
                                . $entity->mime_header->type1 ."\n";
                        };
                    } elseif ($debug) {
                        echo "<br>GPG Plugin: File name in entity did not match\n";
                        echo '<BR>Local File Name: '. $attachment->att_local_name ."\n";
                        echo '<BR>Entity File Name: '. $entity->att_local_name ."\n";
                    }


                } else {
                    echo '<br>'._("GPG Plugin: Plain-text Attachment file not deleted due to error in Encrypt.")."\n";
                    if (copy( $filename, $tempfile )) {
                        deleteTempFile($filename);
                    } else {
                        echo _("Unable to rename temporary file after failed encrypt");
                    }
                    $notclean=1;
                    $serious=1;
                }

            } //end if key==1 id==1
            else {
                // stuff cyphertext into first attachment (body_part)
                if (!$serious) {
                    $entity =& $newMessage->entities[0];
                    $entity->body_part = $cyphertext;
                }
            } // end if key==1 id==1
        } //end foreach
    sqsession_register($compose_messages , 'compose_messages');
    $restoremessages = urlencode(serialize($compose_messages));
    sqsession_register($restoremessages , 'restoremessages');
    //stuff everything back in $compose_messages
    $compose_messages[$session] = $newMessage;
    $composeMessage[1] = $newMessage;
    if ($debug) {
        echo 'Final contents of composeMessage[1]:<br><pre>';
        print_r($composeMessage[1]);
        echo '</pre>';
    }
   //end if
   } else {
        // not attachments, stuff back into main body
        if ($debug) { echo "<br> No attachments found. \n";};
        if (!$serious) {
                if ($debug) { echo "<br>Setting composeMessage[1]->body_part equal to cyphertext"; }
                //$composeMessage[1]->body_part = $cyphertext;
            $newMessage->body_part = $cyphertext;
            if ($debug) { echo "NewMessage:<pre>"; print_r($newMessage->body_part); echo "</pre>"; }
            $composeMessage[1] = $newMessage;
        }
   }
/*end attachment handling*/


/* if we have an error, gpg probably didn't create the cyphertext.
 * we need to return gracefully back to the compose page with the plaintext
 * intact.
 */
if (!$cyphertext) {
    $serious = 1;
    if ($debug){
        echo "<br>Cyphertext is empty, setting serious to 1 to preserve plaintext";
    };
};
if ($debug){
    echo "<br>Clean Flag   =" .$notclean;
    echo "<br>Serious Flag =" .$serious;
};
//set our variables before returning
//gpg_setglobal ('label', $value);
gpg_setglobal ('subject' ,$subject);
gpg_setglobal ('action' ,'');
gpg_setglobal ('smaction' ,'');
gpg_setglobal ('passed_id' ,'');
gpg_setglobal ('reply_id' ,'');
//set the body to be the cyphertext
gpg_setglobal ('body' ,$cyphertext);

if (isset($encrypt_on_send)) {
    if (!$serious) {
        return ($cyphertext);
        /**
         * we should consider whether we will always return cyphertext
         * if it exists, rather than doing more careful parsing here
         * (in the if blocks below, for instance)
         */
    } else {
        //create our restore array
        $encrypt_error = array();
        $encrypt_error['subject']     =$subject;
        $encrypt_error['send_to']     =$send_to;
        $encrypt_error['send_to_cc']  =$send_to_cc;
        $encrypt_error['send_to_bcc'] =$send_to_bcc;
        $encrypt_error['body']        =$body;
        $encrypt_error['errors']      =$return['errors'];
        $encrypt_error['warnings']    =$return['warnings'];
        $encrypt_error['skipped_keys']=$trimmed['skipped_keys'];

        sqsession_register($encrypt_error , 'encrypt_error');

        return;
    };
};

if (!$debug) {
    $old_error_reporting_level = error_reporting(E_ERROR | E_PARSE);
    /**
     * @todo Ideally, use set_error_handler here, which checks for
     * the stat file error to ignore, but displays all others.
     *
     * Unfortunately, PHP makes that more difficult than it needs
     * to be, so for now, we'll turn off the entire class of errors
     * that give rise to the cannot stat file error, and perhaps
     * come back to it later.
     *
     * For now, just turn those errors back on if debug is on,
     * to avoid silent failures that are impossible to debug.
     *
     * http://www.braverock.com/bugzilla/show_bug.cgi?id=39
     */
}

if ($notclean){
    if ($serious){
        //make sure the buttons will display on the compose page.
        //$no_encrypt_on_setup = 1;
        $chdir_first = 0;
    $gpg_export=1;
    if (ob_get_level()>0) {
        ob_end_flush();
    }
        echo "<br>Cyphertext not generated due to errors. Your plaintext has been preserved.<br>";
        //preserve the plaintext
        gpg_setglobal ('body' ,$body);
        //then include compose.php
        if (file_exists('../src/compose.php')){
            include('../src/compose.php');  exit;
        } elseif (file_exists('../../src/compose.php')) {
            include('../../src/compose.php'); exit;
        };
    } else {
    $gpg_export=1;
        //turn off the encrypt and sign buttons.
        $no_encrypt_on_setup = 1;
        //then include compose.php
        if (file_exists('../src/compose.php')){
            include('../src/compose.php'); exit;
        } elseif (file_exists('../../src/compose.php')) {
            include('../../src/compose.php'); exit;
        };
    };
};

/*
 * If the $notclean flag isn't set, we should return directly
 * to the compose page with the cyphertext.
 */
// turn off the encrypt and sign buttons on the compose page.
$no_encrypt_on_setup = 1;
$gpg_export = 1;
if ($debug) {
    echo "<br>Returning to Compose in Clean state. (no errors)\n";
}
//then include compose.php
if (file_exists('../src/compose.php')){
    include('../src/compose.php'); exit;
} elseif (file_exists('../../src/compose.php')) {
    include('../../src/compose.php'); exit;
} else echo '<br>Unable to include compose page, Exiting Abnormally.';
exit;
/*********************************************************************/
/**
 *
 * $Log: gpg_encrypt.php,v $
 * Revision 1.80  2005/07/27 14:07:48  brian
 * - update copyright to 2005
 *
 * Revision 1.79  2005/07/27 13:51:32  brian
 * - remove all code to handle SM versions older than SM 1.4.0
 * Bug 262
 *
 * Revision 1.78  2004/08/08 03:59:31  ke
 * -skip encrypt only step if we are signing and encrypting
 *
 * Revision 1.77  2004/06/22 21:59:24  ke
 * -moved include of squirrelmail functions before gpg object
 *
 * Revision 1.76  2004/04/30 17:56:38  ke
 * -removed newline from end of file
 *
 * Revision 1.75  2004/03/15 22:28:45  brian
 * - Allow multiple recipients in Encrypt to Self list.
 * - Test System ADK for missing key error
 * - Test Encrypt to Self recipient(s) for missing key error
 * - cleaned up indents and comments
 * Bug 173
 *
 * Revision 1.74  2004/02/17 22:32:15  ke
 * -new update since proc_open code added
 * -E_ALL fixes
 *
 * Revision 1.73  2004/01/20 04:36:41  ke
 * -removed global so that composeMessage isn't reset
 *
 * Revision 1.72  2004/01/19 18:32:26  ke
 * -E_ALL changes
 *
 * Revision 1.71  2004/01/13 20:20:01  ke
 * -added include of gpg execution file
 *
 * Revision 1.70  2004/01/09 18:26:50  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.69  2003/11/26 22:07:26  ke
 * -added gpg_export=1 to the case of warnings but no critical errors, so send works properly
 *
 * Revision 1.68  2003/11/20 21:27:06  ke
 * -changed formatting for error/warning/skipped keys output on encryption problems
 * -added info section for encryption
 * bug 107
 *
 * Revision 1.67  2003/11/11 22:43:44  ke
 * -added check for arrays before outputting errors
 *
 * Revision 1.66  2003/11/04 21:38:40  brian
 * change to use SM_PATH
 *
 * Revision 1.65  2003/11/03 17:32:34  ke
 * -changed encrypt&sign to use abstracted caching check functions before retreiving cached passphrase
 * bug 66
 *
 * Revision 1.64  2003/10/30 19:40:19  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.63  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.62  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.61  2003/10/16 20:23:15  ke
 * -added ob_start and ob_end_flush to fix javascript errors when including compose.php
 * bug 82
 *
 * Revision 1.60  2003/10/13 21:00:06  brian
 * localized all non-debug strings
 * Bug35
 *
 * Revision 1.59  2003/10/13 19:41:32  brian
 * - copy tempfile to actual filename beofre encrypt
 * - securely remove tempfile with DeleteTempFile fn
 * - securely remove encrpyted file when done
 * - improve debug error on failed attachment encrypt
 * Bug 74
 *
 * Revision 1.58  2003/10/07 22:03:38  ke
 * -removed code to cache passphrase from gpg_encrypt.php
 * -functionality now exists in cachepass.mod
 *
 * Revision 1.57  2003/10/03 22:48:28  ke
 * -Removed possiblity of caching incorrect passphrase, only sets it if everything goes smoothly
 *
 * Revision 1.56  2003/09/29 16:11:42  brian
 * - strengthened checks for cached passphrase top account for system and user preferences
 * - added debug output
 * Bug 40
 *
 * Revision 1.55  2003/09/26 02:26:53  ke
 * -Removed signbody function, no longer storing body in session, no need for plugin storage of body
 * -Added check for cached passphrase
 * -Still need to add logic for system and user options
 *
 * Revision 1.54  2003/09/23 22:46:50  ke
 * -fixed logic error bug in signbody check
 *
 * Revision 1.53  2003/09/23 22:20:06  ke
 * -Added check for existance of session variable signbody, to use for body if it exists
 * -Removes the need for body to be placed in a hidden variable
 * Bug 55
 *
 * Revision 1.52  2003/09/23 15:55:31  ke
 * -removed debug flag
 *
 * Revision 1.51  2003/09/20 01:30:53  ke
 * -fixed encrypt-on-send attachment handling
 *
 * Revision 1.50  2003/09/17 18:23:31  ke
 * -Changed interaction with message object, to allow direct manipulation before send
 * -part of the encrypt on send fix
 * Bug 53
 *
 * Revision 1.49  2003/05/31 20:22:51  brian
 * added strtolower and strcmp in attachment processing to correctly handle 'attachment' or 'ATTACHMENT'
 * Bug 26
 *
 * Revision 1.48  2003/05/29 15:05:52  brian
 * updated to correctly display all relevant errors on 'Encrypt on Send'
 * Bug 26
 *
 * Revision 1.47  2003/05/28 19:17:05  brian
 * modified to fix non-working encryptcapability (broken during work on encrypt&sign on send)
 *
 * Revision 1.46  2003/05/17 22:17:21  brian
 * - Improved handling of errors on 'encrypt on send'
 * - added $encrypt_error array to pass data forward
 * Bug 26
 *
 * Revision 1.45  2003/05/16 16:22:47  brian
 * added code to set $id in attachment handling
 * - based on how we are called, and from which SM verison
 * - SM 1.4.1 moves compose_send hook to after
 *   $body has been inserted at $id= 0
 *
 * Revision 1.44  2003/05/15 23:27:10  brian
 * cleaned up typos and formatting
 *
 * Revision 1.43  2003/05/15 23:17:54  brian
 * multiple changes to support attachment encryption with "Encrypt on Send"
 * - credit to Marc Groot Koerkamp of SM core team for
 *   lots of help on variable scoping in compose_send hook
 * - added global declarations to bring variables in scope
 * - changed handling of $message object to make all
 *   manipulation by reference
 * - added comments and debug code so you can trace execution
 * Bug 26
 *
 * Revision 1.42  2003/05/14 19:53:10  brian
 * - finess the error reporting code that Vinay wrote
 *   so that it wil not fire if debug is on
 * - improve comments in newer parts of the code
 * Bug 39
 *
 * Revision 1.41  2003/05/14 01:32:25  vinay
 * - add rudimentary error suppression
 * - set up for setting the $message class if $_GET from error on
 *   Encrypt on Send
 *
 * Revision 1.40  2003/05/13 22:56:36  brian
 * - changed file include lines so it would work from hook in compose.php
 * - added code to return cyphertext to compose_send hook
 * Bug 26
 *
 * Revision 1.39  2003/05/09 01:15:14  brian
 * disable attachment code on SM < 1.4.0
 * Bug 12
 *
 * Revision 1.38  2003/05/09 01:00:16  brian
 * - added attachment handling section
 * - added additional error handling for attachments
 * Bug 12
 *
 * Revision 1.34  2003/05/01 20:03:09  brian
 * removed CVS flakiness from 1.30 commit
 *
 *
 * Revision 1.29  2003/05/01 19:32:44  brian
 * Added code to correctly process encrypt to partial recipients preference
 * Bug 36
 *
 * Revision 1.28  2003/04/30 18:13:07  brian
 * - added check in gpg_encrypt fn to return error if cyphertext is empty
 * - romoved todo item for same in gpg_encrypt.php
 *
 * Revision 1.27  2003/04/30 15:50:40  vinay
 * Test commit
 *
 * Revision 1.26  2003/04/30 12:42:00  brian
 * Improved comments and standardized tabstops - not a functional change
 *
 * Revision 1.25  2003/04/16 02:35:04  brian
 * modified to support 'encrypt & sign' by adding if blocks for options
 * Bug 11
 *
 * Revision 1.24  2003/04/13 16:28:45  brian
 * modified function call to gpg_encrypt function to use extended parameters
 * Bug 11
 *
 * Revision 1.23  2003/04/07 01:42:57  brian
 * modified to clear sm global variable reply_id for SM 1.2.x compatibility on Encrypt on Reply
 * Bug 22
 *
 * Revision 1.22  2003/04/06 23:32:45  brian
 * added gpg_setglobal to clear passed_id
 * Bug 21
 *
 * Revision 1.21  2003/04/06 20:47:47  brian
 * - create gpg_setglobal to get around SM's getglobal function
 * - update gpg_encrypt to use the gpg_setglobal function
 * Bug 22
 *
 * Revision 1.20  2003/04/06 18:37:01  brian
 * - set to modify global $body, $subject
 *
 * Revision 1.19  2003/04/06 17:58:33  brian
 * - improve $debug formatting
 * - set _POST action and smaction to '' (null) to clear compose.php special processing.
 *
 * Revision 1.18  2003/04/06 17:53:25  brian
 * - improve $debug formatting
 * - set _POST action and smaction to '' (null) to clear compose.php special processing.
 *
 * Revision 1.17  2003/04/02 13:31:00  brian
 * modified to extract only the variables we need from $_POST
 * Bug 5
 *
 * Revision 1.16  2003/03/25 17:57:53  brian
 * Bug 6
 * Slightly better handling of whether to display the buttons or not after encrypt.
 *
 * Revision 1.15  2003/03/17 18:56:56  brian
 * - progress towards SM v >=1.3.1 compatibility
 * - path selection for includes now works on both
 *   SM 1.2.x and SM >= 1.3.1
 *
 * Revision 1.14  2003/03/15 20:51:04  brian
 * changed to call gpg_parse_address function
 *
 * Revision 1.13  2003/03/11 23:29:15  tyler
 * - modified to use the new broken out *_function.php files
 *
 * Revision 1.12  2003/03/11 02:45:50  tyler
 * - modified code to only exclude encrypt now button after encryption routine
 *
 * Revision 1.11  2003/03/11 01:14:25  brian
 * Fixed inclusion of compose.php so that it:
 *  - correctly sets the $body variable in $_POST before including
 *  - chooses correctly between plaintext and cyphertext return
 *  - sets up to handle button display correctly
 *
 * Revision 1.10  2003/03/10 23:01:34  brian
 * Fixed comments to be more descriptive.
 *
 * Revision 1.9  2003/03/09 17:02:04  brian
 * Reintegrate code to include compose php page
 * Removes problems with long cyphertext.
 * Still needs to be cleaned up to improve integration.
 *
 * Revision 1.8  2003/01/07 12:52:07  brian
 * Updated errors handling if encrypt generates warnings or errors to give the user more choices.
 *
 * Revision 1.7  2003/01/05 21:53:58  brian
 * tweaked redirect to compose without creating intermediary page if there are no errors.
 *
 * Revision 1.6  2003/01/05 18:46:46  brian
 * Changed to redirect to compose without creating intermediary page if there are no errors.
 *
 * Revision 1.5  2003/01/05 15:07:23  brian
 * fixed usage of the error arrays
 *
 * Revision 1.4  2002/12/10 03:31:40  brian
 * added file header block and cvs log tag
 *
 * Revision 1.2  2002/12/10 02:29:08  brian
 * removed references to gpg_set variables, it has been deprecated
 *
 * Revision 1.1  2002/12/05 16:47:55  brian
 * Initial revision
 *
 */
?>