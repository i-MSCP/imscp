<?php
/**
 * gpg_sign_functions.php
 * -----------
 * GPG plugin functions as defined by the SquirrelMail-1.2 API.
 * Updated for the SM 1.3/1,4 API
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Tyler Allison
 * @author Brian Peterson
 * @author Aaron van Meerten
 *
 * $Id: gpg_sign_functions.php,v 1.43 2005/10/09 07:12:11 ke Exp $
 *
 */
/*********************************************************************/

require_once(SM_PATH.'plugins/gpg/gpg_execute.php');

/**
 * function gpg_sign_attachment
 *
 * @param $body, $passphrase, $debug, $signingkey
 * @return $return : An array that contains the signature filename as well as
 *       warnings and error messages
 *
 * If $passphrase is not passed in then we have to assume either a problem
 * or that the user wants to sign the message with a key that has had the
 * passphrase removed, also known as a signing key. See README.txt
 *
 */

function gpg_sign_attachment($filename,$passphrase,$debug=0,$signingkey=''){
  global $trusted_key_id;
  global $gpg_key_dir;
  global $path_to_gpg;
  global $username;
  global $data_dir;
  global $safe_data_dir;
  $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
  $return['errors'] = array();
  $return['warnings'] = array();

  if ($path_to_gpg=='') {
    load_prefs_from_file('../plugins/gpg/gpg_system_defaults.txt',$debug);
    load_prefs_from_file('../plugins/gpg/gpg_local_prefs.txt',$debug);
        $path_to_gpg = $GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
    $gpg_key_dir ="$safe_data_dir$username.gnupg";
  }
  $username = $_SESSION['username'];

  $key_id = gpg_get_signing_key_id();

  if (file_exists($filename . ".asc")) {
    unlink($filename . ".asc");
  }


   $params = " --armor --detach-sign --default-key $key_id --homedir $gpg_key_dir  $filename";
  $return=gpg_execute($debug,$params,$passphrase,'');
  $output=$return['rawoutput'];
  if ($output) {
    foreach($output as $line) {
        $return['errors'][] = $line;
    }
  }
  if (is_file($filename.".asc")) {
    $return['filename'] = $filename . ".asc";
  }
  return $return;
}

/**
 * function gpg_verify_signature
 *
 * @param $filename, $signature, $debug
 * @return $return : An array that contains ['verified'] as 'true' or 'false' of gpg as well as
 *       warnings and error messages
 *
 * If $passphrase is not passed in then we have to assume either a problem
 * or that the user wants to sign the message with a key that has had the
 * passphrase removed, also known as a signing key. See README.txt
 *
 */


function gpg_verify_signature($filename,$signature,$debug=0){

    global $gpg_key_dir;
    global $path_to_gpg;
    global $username;
    global $data_dir;
    global $safe_data_dir;
    $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
    $extra_cmd = '';

    $return['errors'] = array();
    $return['warnings'] = array();
    $return['verified'] = 'false';
    $return['info'] = '';
    if ($path_to_gpg=='') {
        load_prefs_from_file('../plugins/gpg/gpg_system_defaults.txt',$debug);
        load_prefs_from_file('../plugins/gpg/gpg_local_prefs.txt',$debug);
        $path_to_gpg = $GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
    }
    if ($gpg_key_dir=='') {
		$gpg_key_dir ="$safe_data_dir$username.gnupg";
    }
    if (is_array($signature)) {
    $newsig='';
    foreach ($signature as $line) {
    	$newsig .= trim($line) . "\n";
    }
    $signature=$newsig;
    }
    // 'Corporate' shared system keyring setup
    $trust_system_keyring = getPref($data_dir, $username, 'trust_system_keyring');
    $systemkeyring = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'];
    if ($systemkeyring=='true' and $trust_system_keyring == 'true') {
        $system_keyring_file = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
        $systemtrustedkey    = escapeshellarg($GLOBALS['GPG_SYSTEM_OPTIONS']['systemtrustedkey']);
        //now add the parameters to $extra_cmd
        if (is_file($system_keyring_file)) {
            $system_keyring_file = escapeshellarg($system_keyring_file);
            $extra_cmd .= " --keyring $system_keyring_file ";
            if ($systemtrustedkey != '') {
                $extra_cmd .= " --trusted-key $systemtrustedkey ";
            };
        } elseif ($debug) echo "\n".'<br>system_keyring_file '.$system_keyring_file.' failed is_file test';
    }; //end shared system keyring

    $params = "--homedir $gpg_key_dir $extra_cmd --verify - $filename";

    $return=gpg_execute($debug,$params,NULL,$signature);

    if ($return['untrusted'] == 'true') {
        gpg_update_trustdb($debug);
	$return=gpg_execute($debug,$params,NULL,$signature);
    }

    return $return;

} //end gpg_verify_signature function

/**
 * function gpg_sign_message
 *
 * @param $body, $passphrase, $debug,$signingkey
 * @return $return : An array that contains the cyphertext as well as
 *       warnings and error messages
 *
 * If $passphrase is not passed in then we have to assume either a problem
 * or that the user wants to sign the message with a key that has had the
 * passphrase removed, also known as a signing key. See README.txt
 *
 */
function gpg_sign_message($body,$passphrase,$debug,$signingkey=''){
  global $trusted_key_id;
  global $gpg_key_dir;
  global $path_to_gpg;
  global $username;
  global $data_dir;
  global $safe_data_dir;
  $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;

  $return['errors'] = array();
  $return['warnings'] = array();
  if ($path_to_gpg=='') {
    load_prefs_from_file('../plugins/gpg/gpg_system_defaults.txt',$debug);
    load_prefs_from_file('../plugins/gpg/gpg_local_prefs.txt',$debug);
        $path_to_gpg = $GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
    $gpg_key_dir ="$safe_data_dir$username.gnupg";
  }
  $username = $_SESSION['username'];

  if ($debug) {
    echo "<br>Global Key Dir: " . $gpg_key_dir;
    echo "<br>Username: $username";
  };

  // check to see if user wants to autosign
  $key_id = gpg_get_signing_key_id();

  $auto_sign = getPref ($data_dir, $username, 'no_signing_passwd');
  $use_signing_key_id = getPref ($data_dir, $username, 'use_signing_key_id');

  $no_signing_passwd = getPref ($data_dir, $username, 'no_signing_passwd');

  if ($debug) {
      echo "<br>Use Signing Feature: $use_signing_key_id";
      echo "<br>Using autosign: $auto_sign";
      echo "<br>Using keyID: $key_id";
  }


  if ($debug) {
      echo "<br>Original Body Text<br><textarea cols=80 rows=5 name=plaintext>$body</textarea>";
      echo '<hr>';
  };

  if (!$body or $body=='') {
      $unclean=1;
      $return['errors'][]= _("GPG Plugin Error:No body text received from Compose page");
      return $return;
  }
  // clean the body string that is passed in
  // make sure that funny characters get
  // bracketed by single quotes and backslashes
  // done in gpgexecute now
//  $body = escapeshellarg ($body);

  /********* gpg_sign_message Command String *********/
  /**
   * Build the command string in pieces, checking for the
   * existance of various preferences, and modifying the
   * command string accordingly
   */
   //set up the base command

   if (($auto_sign=='true') and ($key_id)) {
      if ($debug) {
          echo "<br>Caught auto_sign AND key_id";
      };
      // user has asked to autosign and we have a keyID to use
	$params = "--clearsign --default-key $key_id --homedir $gpg_key_dir";
   } else {
      // autosign is not on so we should be expecting a passphrase
      if ($passphrase) {
         if ($debug) {
            echo "<br>Got passphrase\n";
         };
         //Make sure we escape naughty characters before passing to the shell
          // we have a passphrase so attempt to sign with phrase
          // and we dont need a default key then

          //build a command without the passphrase incase debug is set so
          // we dont go displaying the passphrase in a debug window
	  $params = " --clearsign --default-key $key_id --homedir $gpg_key_dir";
      } else {
         // we should reopen the window or check for passphrase with javascript
         // instead of just throwing an error here
         $return['errors'][] = _("You did not provide a passphrase! Please right-click and select 'Back' and try again");
         return $return;
      };
   };



  if ($debug) {
     if ($auto_sign=='true') {
        echo "<hr>Command String: $params<hr>";
     } else {
        echo "<hr>Command String: echo [passphrase stripped for security] $params<hr>";
     }
  };
  $return=gpg_execute($debug,$params,$passphrase,$body);
  $returnval=$return['returnval'];
  $cyphertext=$return['output'];
  
  if ($returnval) { 
      $return ['errors'][] = _("GPG Plugin: gpg returned a non-clean return value of: ").$returnval;
  }

  // make the result a string
  if (is_array($cyphertext)) {
      $cyphertext = implode($cyphertext,"\n");
  }

  if ($debug) {
    echo "<br>New body text<br><textarea cols=80 rows=5 name=cyphertext>$cyphertext</textarea>";
    echo "<br> returnvalue= $returnval";
  };

  $sep = '-----BEGIN PGP SIGNED MESSAGE-----';
  if ($cyphertext) {
  list ($front, $cyphertext_tail) = explode ($sep, $cyphertext);
  } else { $front=""; $cyphertext_tail=""; }

  if (!$cyphertext_tail) {
    if ($returnval) {
	$return ['errors'][] = _("GPG Plugin: gpg returned a non-clean return value of: ").$returnval;
    }
  }

  $return['cyphertext'] = $cyphertext;
  if ($debug) {
        echo "<hr>";
        foreach ($return['errors'] as $error) echo "<br>Error $error";
  };

  return ($return);

};

/*********************************************************************/
/**
 * $Log: gpg_sign_functions.php,v $
 * Revision 1.43  2005/10/09 07:12:11  ke
 * - changed to use centralized function for determining which signing key to use
 *
 * Revision 1.42  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.41  2004/04/30 18:02:34  ke
 * -removed newline from end of file
 *
 * Revision 1.40  2004/03/03 19:44:14  ke
 * -added definition of key dir if it's missing
 *
 * Revision 1.39  2004/02/17 22:42:58  ke
 * -changed function calls to operate properly with proc_open code
 * bug 29
 *
 * Revision 1.38  2004/01/17 00:27:27  ke
 * -E_ALL fixes, removing deprecated option gpg_key_file
 *
 * Revision 1.37  2004/01/14 23:50:26  ke
 * -removed extraneous command strings
 * -removed gpg_parse_output calls, since it's centralized in gpg_execute now
 *
 * Revision 1.36  2004/01/13 20:26:48  ke
 * -changed to use centralized gpg_execute function
 *
 * Revision 1.35  2004/01/09 20:36:48  ke
 * -added check of the return value of gpg as a possible error
 *
 * Revision 1.34  2003/12/19 20:53:00  ke
 * -changed to use centralized update trustdb function
 * -only update trustdb when untrusted keys are found
 *
 * Revision 1.33  2003/12/18 22:20:36  ke
 * -added trustdb check to sign functions
 *
 * Revision 1.32  2003/12/18 19:43:55  ke
 * -changed to no longer add data_dir to the beginning of system keyring
 *
 * Revision 1.31  2003/12/16 20:00:03  brian
 * changed instances of $system_keyring_file = $safe_data_dir to
 *    $system_keyring_file = $data_dir becasue $safe_data_dir is a
 *    hashed dir on a per-user basis
 *
 * Revision 1.30  2003/12/11 19:56:41  brian
 * added shared corporate keyring support to  detached sig verification
 * Bug 28
 *
 * Revision 1.29  2003/12/02 04:11:22  ke
 * -changed verification function to use gpg_parse_output
 *
 * Revision 1.28  2003/11/25 21:55:37  ke
 * -added a parse_output command even if there are no errors, to trap warnings
 * bug 113
 *
 * Revision 1.27  2003/11/25 01:31:47  ke
 * -added getHasheddir and used safe_data_dir safely
 * bug 116
 *
 * Revision 1.26  2003/11/11 22:44:34  ke
 * -removed all gpg output parsing, uses gpg_parse_output instead
 * bug 107
 *
 * Revision 1.25  2003/10/30 19:40:20  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.24  2003/10/27 18:22:07  ke
 * -added gpg_verify_signature for verification of detached signatures
 *
 * Revision 1.23  2003/10/20 22:49:31  ke
 * -Added function for creating detached signatures for attachments
 *
 * Revision 1.22  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.21  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.20  2003/10/07 19:25:51  ke
 * -Added ability to select which key to sign with
 *
 * Revision 1.19  2003/10/07 18:36:21  ke
 * -removed all echos, instead returns $return['errors']
 *
 * Revision 1.18  2003/09/30 01:34:53  ke
 * -Internationalized all output in gpg_sign_functions
 * we might want to consider better or more friendly error handling in this file
 * bug 35
 *
 * Revision 1.17  2003/09/26 02:33:19  ke
 * -added check to see if global variables are already set, loads them if they are not
 *
 * Revision 1.16  2003/05/07 21:53:37  brian
 * - added check for empty body in fn gpg_sign
 *
 * Revision 1.15  2003/04/14 14:19:06  brian
 *  - Added error handling to catch uncupported algorithm or corrupt key error.
 * @todo make sure error handling fires any time gpg returns something nasty.
 * Bug 4
 *
 * Revision 1.14  2003/04/03 21:46:56  brian
 * Fixed extra } from line 186 by:
 * - moving other code into the proper brackets
 * - removing extra brace
 * - fixing indents so they lined up
 *
 * Revision 1.13  2003/04/03 12:28:03  brian
 * removed superfluous " " . in substr_count lines
 *
 * Revision 1.12  2003/04/03 00:28:07  tyler
 * - converted error handling to the gpg_encrypt_function new method
 *
 * Revision 1.11  2003/04/03 00:04:59  tyler
 * - squashed bug 17, incorrect handling of signing when message being signed already has a signature
 *
 * Revision 1.10  2003/03/31 14:23:37  brian
 * fixed comment typos -non-functional change
 *
 * Revision 1.9  2003/03/20 00:45:36  tyler
 * - First attempt at better error parsing -tyler
 *
 * Revision 1.8  2003/03/14 06:30:01  joelm
 * Discription: Make sure the passphrase has special chars escaped before we put
 * in on the command line.
 *
 * Revision 1.7  2003/03/13 19:48:52  tyler
 * - fixed a bug that put a \n in the wrong spot and thus invalidated some signed messages
 *
 * Revision 1.6  2003/03/13 04:05:49  brian
 * - modified to correctly accept passphrase for message signing
 * - added checks for using the signing feature and
 *   needing passphrase for secret key
 *
 * Revision 1.5  2003/03/12 19:18:05  tyler
 * - fixed a bug with one of the debug sections and added function comments
 *
 * Revision 1.4  2003/03/12 15:45:22  brian
 * added header and footer blocks to file created by tyler
 *
 * Revision 1.1  2003/03/11 tyler
 * initial revision
 *
 */
?>