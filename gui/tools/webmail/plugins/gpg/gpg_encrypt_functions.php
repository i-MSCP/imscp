<?php
/**
 * gpg_encrypt_functions.php
 * -----------
 * GPG plugin functions file, as defined by the SquirrelMail-1.2 API.
 * Updated for the SM 1.3/1,4 API
 *
 * Copyright (c) 2002-2003 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Brian Peterson
 *
 * $Id: gpg_encrypt_functions.php,v 1.77 2003/12/29 23:52:55 brian Exp $
 *
 */

/*********************************************************************/
/**
 * function gpg_encrypt
 * This function does the encryption
 * This is the workhorse of the encryption side of the plugin
 *
 * Add code here to use user preferences to modify the gpg command line
 *
 * @param integer $debug 0|1
 * @param string  $body         Body text string
 * @param string  $send_to      recipient list separated by '-r '
 * @param optional boolean $sign         (true/false) do we want to sign the message/file
 * @param optional string  $passphrase   passphrase string needed for signing functions
 * @param optional string  $filename     if we are going to encrypt a file
 * @return array with results
 */
function gpg_encrypt($debug, $body,$send_to_list, $sign='false', $passphrase, $filename ='') {

    // set up globals
    global $trusted_key_id;
    global $gpg_key_dir;
    global $path_to_gpg;
    global $username;
    global $data_dir;
    global $safe_data_dir;
    $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;

    $username = $_SESSION['username'];
    if (!isset($gpg_key_dir)) {
        load_prefs_from_file(SM_PATH . 'plugins/gpg/gpg_system_defaults.txt',$debug);
        load_prefs_from_file(SM_PATH . 'plugins/gpg/gpg_local_prefs.txt',$debug);
        $path_to_gpg=($GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg']);
        $gpg_key_dir=($GLOBALS['GPG_SYSTEM_OPTIONS']['gpg_key_dir']);
        $gpg_key_dir ="$safe_data_dir$username.gnupg";
    }

    if ($debug) {
        echo "<br>Global Key Dir: " . $gpg_key_dir;
        echo "<br>Username: $username";
    };

    //initialize our return arrays
    $return['warnings'] = array();
    $return['errors'] = array();
    $return['skipped_keys'] = array();

    //File Test
    if ($filename != '') {
    if (!is_file($filename)) {
        $return ['errors'][]=_("The attachment file was not found").':'.$filename;
    };
    }; // end filename check

    //Signing Test
    if ($sign=='true') {
        /* Check for secure connection.
         *
         * If we don't have a secure connection, return an error
         * string to be displayed by MakePage
         */
        $https_check=0;
        $https_check=gpg_https_connection ();
        if (!$https_check) {
           $return['errors'][] = _("You are not using a secure connection.").'&nbsp;'._("SSL connection required to use passphrase functions.");
           return ($return);
           exit;
        };
        $use_signing_key_id = getPref ($data_dir, $username, 'use_signing_key_id');
        //greb the pasword preference
        $no_signing_passwd = getPref ($data_dir, $username, 'no_signing_passwd');
        if ($debug) echo "\n<br>no_signing_passwd: $no_signing_passwd<br>\n";
        //grab the signing_key_id
        $signing_key_id = getPref ($data_dir, $username, 'signing_key_id');
        if ($debug) echo "<br>Sign = true \n<br>Signing Key ID: $signing_key_id<br>\n";
    };

    // Trusted Key Test
    $trusted_key_id = ''; //initialize empty
    $use_trusted_key_id = getPref($data_dir,$username,'use_trusted_key_id');
    if ($use_trusted_key_id == 'true') {
        $trusted_key_id = escapeshellarg(getPref($data_dir,$username,'trusted_key_id'));
        //check to make sure we didn't cross wires here, and we actually have a value
        if ($trusted_key_id == '') {
            $use_trusted_key_id = 'false';
            if ($debug) echo "\n<br>Use Trusted Key Feature set to true, but no key id was found, so turning off trusted key feature.\n";
            $return ['warnings'][]= _("GPG Plugin: No Trusted Key Selected, but Use Trusted Key Feature Set.");
            $return ['warnings'][]= _("GPG Plugin: Check your preferences from the Trusted Key Screen in Options.");
        }
        if ($debug) {
            echo "<br>Use Trusted Key Feature = $use_trusted_key_id\n";
            echo "<br>Trusted Key ID: $trusted_key_id\n";
            echo '<hr>';
        };
    } else {
        if ($debug) {
            echo '<BR>use_trusted_key_id = false';
        };
    };

    //Encrypt to Self Test
    $encrypt_to_self=getPref($data_dir, $username, 'encrypt_to_self');
    if ($encrypt_to_self=='true') {
        $self_encr_email = escapeshellarg(getPref($data_dir, $username, 'self_encr_email'));
        // add the selected email address to the recipient list
        if ($send_to_list) {
            $send_to_list .= " -r $self_encr_email";
        } else {
            $send_to_list = " $self_encr_email";
        };
        if ($debug) {
             echo '<BR>encrypt_to_self = true';
             echo "<BR>Self Encrypt Email: $self_encr_email";
        };
    } else {
        if ($debug) {
            echo '<BR>encrypt_to_self = false';
        };
    };
    if (!$send_to_list) {
        $return['errors'][] = _("GPG Plugin: Your recipient list is empty. After parsing, there are no valid recipients.");
        return ($return);
        exit;
    };

    // 'Corporate' shared system keyring Test
    $trust_system_keyring = getPref($data_dir, $username, 'trust_system_keyring');
    $systemkeyring = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'];
    if ($systemkeyring=='true' and $trust_system_keyring == 'true') {
        $system_keyring_file = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
        $systemtrustedkey    = escapeshellarg($GLOBALS['GPG_SYSTEM_OPTIONS']['systemtrustedkey']);
        if ($debug) {
            echo '<br>trust_system_keyring = '.$trust_system_keyring;
            echo '<br>systemkeyring = '.$systemkeyring;
            echo '<br>system_keyring_file = '.$system_keyring_file;
            echo '<br>systemtrustedkey = '.$systemtrustedkey;
        };
    };

    /**
     * Plaintext debug box:
     * uncomment if you think the plaintext might
     * not be getting to gpg_encrypt fn
     *
     * if ($debug) {
     *     echo "<br>Original Message Body:<br><textarea cols=80 rows=10 name=plaintext>$body</textarea>";
     *     echo '<hr>';
     * };
     *
     */

    /********* gpg_encrypt Command String *********/
    /**
    * Build the command string in pieces, checking for the
    * existance of various preferences, and modifying the
    * command string accordingly
    */

    //set up the base command
    $command = "$path_to_gpg --batch --no-tty --homedir $gpg_key_dir ";

    // clean the body string that is passed in
    // make sure that funny characters get
    // bracketed by single quotes and backslashes
    $body = escapeshellarg ($body);

    if ($body and $filename=='') {
        $command = "$body |". $command;
    } elseif ($sign=='true' and $use_signing_key_id=='true' and $no_signing_passwd!='true') {
        $command = ' | '.$command;
    };

    //add the signing parameters
    if ($sign=='true' and $use_signing_key_id=='true' and $no_signing_passwd=='true' and $filename!='') {
        $command .= " --sign --default-key $signing_key_id ";
    } elseif ($sign=='true' and $use_signing_key_id=='true' and $no_signing_passwd=='true') {
    $command .= " --output - --sign --default-key $signing_key_id ";
    } elseif ($sign=='true' and $use_signing_key_id=='true' and $filename!='') {
        $passphrase = escapeshellarg($passphrase . "\n");
        $command .= " --passphrase-fd 0 --sign --default-key $signing_key_id ";
        $command  = $passphrase . $command;
    } elseif ($sign=='true' and $use_signing_key_id=='true') {
    $passphrase = escapeshellarg($passphrase . "\n");
    $command .= " --passphrase-fd 0 --output - --sign --default-key $signing_key_id ";
    $command  = $passphrase . $command;
    } elseif ($sign=='true') {
        $return['errors'][] = _("GPG Plugin: You must specify a signing key in the Options screen to sign messages.");
    };

    //add the trusted key parameters if needed
    //if ($sign!='true'){
    if ($use_trusted_key_id == 'true' && $trusted_key_id != '') {
        $command .= " --trusted-key $trusted_key_id ";
    } else {
        $command .= ' --always-trust ';
    };
    //};

    //add the shared system keyring if needed
    if ($systemkeyring=='true' and $trust_system_keyring=='true') {
        if (is_file($system_keyring_file)) {
            $system_keyring_file = escapeshellarg($system_keyring_file);
            $command .= " --keyring $system_keyring_file ";
            if ($systemtrustedkey != '') {
                $command .= " --trusted-key $systemtrustedkey ";
            };
            //add system ADK to recipient list if required
            $use_system_adk=getPref($data_dir, $username, 'use_system_adk');
            if ($use_system_adk=='true') {
                $systemadk= $GLOBALS['GPG_SYSTEM_OPTIONS']['systemadk'];
                if ($systemadk !='') {
                    $systemadk = escapeshellarg($systemadk);
                    if ($send_to_list) {
                        $send_to_list .= " -r $systemadk";
                    } else {
                        $send_to_list = " $systemadk";
                    };
                }
            }; //end system adk
        } elseif ($debug) echo "\n".'<br>system_keyring_file '.$system_keyring_file.' failed is_file test';
    }; //end shared system keyring

    // wrap it up by setting the recipients to the sender list using -r
    // and redirect the output to stderr using 2>&1
    $command .= " -r $send_to_list --force-mdc --armor --encrypt ".escapeshellarg($filename).' 2>&1';

    if ($body or ($sign=='true' and $use_signing_key_id=='true')) {
        $command  = 'echo '.$command;
    }

    if ($debug) {
        /**
         * @todo we should modify this to not show the body and passphrase
         */
        echo "<hr>Command String: $command<hr>";
    };

    exec($command, $cyphertext, $returnval);

    // make the result a string
    if (is_array($cyphertext)) {
        $cyphertext_str = implode($cyphertext,"\n");
    };

    if ($debug) {
        echo "<hr><br>gpg command execution returned:<br>\n";
        echo "<textarea cols=80 rows=25 name=cyphertext>$cyphertext_str</textarea>\n";
        echo "<br>returnvalue= $returnval \n";
    };

    //now parse the return value and return an array with some useful contents.
    $sep = '-----BEGIN PGP MESSAGE-----';

    list ($front, $cyphertext_tail) = explode ($sep, $cyphertext_str);

    if ($debug) {
        echo "<hr>Returned String before the cyphertext: <br><pre>$front</pre>";
    };

    if ($cyphertext_tail) {
        $returntext = "$sep $cyphertext_tail";
    } elseif ($body!='') {
        $return['errors'][] = _("GPG Plugin: No cyphertext was generated.")."\n";
    };

    $return['cyphertext'] = $returntext;

    if ($returnvalue) {
        $return [$errors][] = _("GPG Plugin: gpg returned a non-clean return value of: ").$returnvalue;
    }
    $return = array_merge($return, gpg_parse_output($cyphertext));
    /**
     * Should these be info tagged?
     * gpg: key <...> marked as ultimately trusted
     * gpg: checking the trustdb
     * gpg: public key of ultimately trusted key <...> not found
     * gpg: checking at depth 0 signed=14 ot(-/q/n/m/f/u)=0/0/0/0/0/2
     * gpg: checking at depth 1 signed=1 ot(-/q/n/m/f/u)=14/0/0/0/0/0
     * gpg: next trustdb check due at 2003-06-19
     */

    if ($debug) {
        echo "<hr>";
        foreach ($return['warnings'] as $warn) echo "<br>Warning $warn";
        foreach ($return['errors'] as $error) echo "<br>Error $error";
        foreach ($return['skipped_keys'] as $error) echo "<br>Skipped Keys $error";
    };
    return ($return);
    //should add code to filter out the errors/warnings we expect.

}; //end gpg_encrypt fn

/*********************************************************************/
/**
 * function gpg_decrypt - This function does the decryption.
 *
 * This is the workhorse of the decryption side of the plugin
 *
 * @param integer $debug 0|1
 * @param string $body          Body String to decrypt
 * @param string $passphrase    Passphrase to pass to gpg
 * @param optional string $filename    Filename to decrypt binary file
 * @return array with results
 */
function gpg_decrypt($debug, $body, $passphrase, $filename='', $outfile=''){

    // set up globals
    global $gpg_key_dir;
    global $path_to_gpg;
    global $data_dir;
    global $username;
    global $safe_data_dir;
    $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
    $no_signing_passwd = getPref ($data_dir, $username, 'no_signing_passwd');

    $username = $_SESSION['username'];

    if ($debug) {
        echo "<br>Global Key Dir: " . $gpg_key_dir;
        echo "<br>Username: $username";
    };

    // clean the body string that is passed in
    // make sure that funny characters get
    // bracketed by single quotes and backslashes
    $body = gpg_clean_body($body,$debug);
    $body = escapeshellarg($body);
    /**
     *  patch submitted by Magyar Dénes breaks decrypt from encrypt on send
     * dirty fix for the newline bug
     *
     * $body = ereg_replace('\([a-zA-Z0-9][a-zA-Z0-9]*\)','',$body);
     * $body = ereg_replace('-----BEGIN PGP MESSAGE----- ','-----BEGIN PGP MESSAGE-----\n',$body);
     * $body = ereg_replace(' -----END PGP MESSAGE-----','\n-----END PGP MESSAGE-----\n',$body);
     *
     * $body = ereg_replace('\([a-zA-Z0-9][a-zA-Z0-9]*\)','',$body);
     * $body = ereg_replace('-----BEGIN PGP MESSAGE----- ','-----BEGIN PGP MESSAGE-----\n',$body);
     * $body = ereg_replace(' -----END PGP MESSAGE-----','\n-----END PGP MESSAGE-----\n',$body);
     * removing patch for now - Brian - 17 Sept 2003
     */

    // set up our return error and warning arrays.
    $return['warnings'] = array();
    $return['errors'] = array();

    /* Check for secure connection.
     *
     * If we don't have a secure connection, return an error
     * string to be displayed by MakePage
     */
    $https_check=0;
    $https_check=gpg_https_connection ();
    if (!$https_check) {
       $line = _("You are not using a secure connection.").'&nbsp;'._("SSL connection required to use passphrase functions.");
       $return['errors'][] = $line;
       return ($return);
       exit;
    };

    /********* gpg_decrypt Command String *********/
    /**
     * Build the command string in pieces, checking for the
     * existance of various preferences, and modifying the
     * command string accordingly
     */
    $extra_cmd = '';

    // 'Corporate' shared system keyring setup
    $trust_system_keyring = getPref($data_dir, $username, 'trust_system_keyring');
    $systemkeyring = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'];
    if ($systemkeyring=='true' and $trust_system_keyring == 'true') {
        $system_keyring_file = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
        $systemtrustedkey    = escapeshellarg($GLOBALS['GPG_SYSTEM_OPTIONS']['systemtrustedkey']);
        if ($debug) {
            echo '<br>trust_system_keyring = '.$trust_system_keyring;
            echo '<br>systemkeyring = '.$systemkeyring;
            echo '<br>system_keyring_file = '.$system_keyring_file;
            echo '<br>systemtrustedkey = '.$systemtrustedkey;
        };
        //now add the parameters to $extra_cmd
        if (is_file($system_keyring_file)) {
            $system_keyring_file = escapeshellarg($system_keyring_file);
            $extra_cmd .= " --keyring $system_keyring_file ";
            if ($systemtrustedkey != '') {
                $extra_cmd .= " --trusted-key $systemtrustedkey ";
            };
        } elseif ($debug) echo "\n".'<br>system_keyring_file '.$system_keyring_file.' failed is_file test';
    }; //end shared system keyring

    /**
     * set up the base command
     * build a command without the passphrase in case debug is set so
     * we dont go displaying the passphrase in a debug window
     *
     * modify the command string to work with $filename
     */


    if ($filename == '') {
    $pre_pass =" | $path_to_gpg --passphrase-fd 0 --batch --no-tty --homedir $gpg_key_dir $extra_cmd --decrypt 2>&1";
        $without_pass =" | $path_to_gpg --batch --no-tty --decrypt --homedir $gpg_key_dir $extra_cmd 2>&1";
    } else {
    if ($outfile== '') {
        $pre_pass =" | $path_to_gpg --passphrase-fd 0 --batch --no-tty --use-embedded-filename --homedir $gpg_key_dir $extra_cmd --decrypt-files $filename 2>&1";
    } else {
        $pre_pass =" | $path_to_gpg --passphrase-fd 0 --batch --no-tty --homedir $gpg_key_dir $extra_cmd --output \"$outfile\" --decrypt \"$filename\" 2>&1";
    }
        $without_pass ="$path_to_gpg --batch --no-tty --homedir $gpg_key_dir $extra_cmd --use-embedded-filename --decrypt-files $filename 2>&1";
    }

    //first check to see if they don't need a passphrase and set command accordingly
    if ($no_signing_passwd == 'true') {
         //this will die an ugly death if the message is encrypted to a key
         //that requires a passphrase
         if ($debug) {
            echo "<br>No passphrase provided.\n";
            echo "<br>Command string: echo [Body]$without_pass<br>\n";
         };
         if ($filename = '') {
            $command = "echo $body$without_pass";
         } else {
            $command = "$without_pass";
         }
    }

    //then check and see if they provided a passphrase anyway, and set if they did
    if ($passphrase) {
        if ($debug) {
            echo "<br>Got passphrase.\n";
            echo "<br>Command string: echo [PassPhrase][Body]$pre_pass<br>\n";
        };
        //Make sure we escape naughty characters before passing to the shell
        $passphrase = escapeshellarg($passphrase . "\n");
        $command = "echo $passphrase$body$pre_pass";
    } elseif ($no_signing_passwd == 'false') {
        // we should reopen the window or check for passphrase with javascript
        // instead of just throwing an error here
        $line = _("You did not provide a passphrase!");
        $return['errors'][] = $line;
        return ($return);
        exit;
    }

    exec($command, $plaintext, $returnval);

    // make the result a string
    if (is_array($plaintext)) {
        $plaintext_str = implode($plaintext,"\n");
    };

    if ($debug) {
        echo " <textarea cols=80 rows=25 name=plaintext>$plaintext_str</textarea>";
        echo " returnvalue= $returnval";
    };

    /**
     * now parse the return value and return an array with some useful contents.
     * if there are no errors, and the message is not signed,
     * we will only have the plaintext.
     */

    $return = gpg_parse_output($plaintext);
    if ($return['untrusted'] == 'true') {
          gpg_update_trustdb($debug);
      $plaintext='';
          exec($command, $plaintext, $returnval);
          $return=gpg_parse_output($plaintext);
    }

    $return ['plaintext'] = $return['output'];
    //send it back
    return ($return);

}; //end gpg_decrypt fn

/*********************************************************************/
/**
 * function gpg_clean_body
 *
 * Add another =\n sign when necessary in the ASCII Armor Data to clean
 * up problems sometimes seen in messages from commercial PGP
 *
 * Separates checksum from the rest of the Armored body.
 *
 * @param string $body
 * @param integer $debug 0|1
 * @return string $newbody
 */
function gpg_clean_body($body,$debug) {
    if ($debug) { echo "Body Before: <pre>\n$body\n</pre>"; }
    $body=explode("\n",$body);
    $newbody = "";
    foreach ($body as $line) {
        $pos = strpos($line,"=");
        if ($pos > 0) {
            if (($line{$pos+1}!='=') and ($line{$pos-1}!='=')) {
                if ($debug) { echo "Before: $line<br>"; }
                $line =  $line = substr($line,0,$pos) . "=" . substr($line,$pos,strlen($line));
                if ($debug) { echo "After: $line<br>"; }
            }
        }
        $newbody .= $line . "\n";
    }
    if ($debug) { echo "Body After: <pre>\n$newbody\n</pre>"; }
    return $newbody;
}

/*********************************************************************/
/**
 * function gpg_parse_address
 *
 * This function will parse the address correctly into a
 * recipient list for use by the calling page.
 *
 * Squirrelmail v1.2.x's address parsing functions fail silently on
 * malformed or otherwise obnoxious email addresses.
 * We use them for compatibility and hope they will be upgraded later.
 *
 * Be careful what you wish for.  SM 1.4 has made the address
 * parsing more robust, at the cost of simplicity and backwards compatibility.
 * Check which version we are running on, and call the correct functions.
 *
 * @param string  $send_to
 * @param string  $send_to_cc
 * @param string  $send_to_bcc
 * @param integer $debug 0|1
 * @return array with results
 */

function gpg_parse_address ($send_to, $send_to_cc, $send_to_bcc, $debug){

    if ($debug) {
        echo '<br> Entering Address Parsing:<br>';
    }
    global $version;

    if (substr($version, 2,4) >= 3.1) {
        //parse using SM 1.3.1+ functions from rfc822header

        $valid_addresses = array();

        $abook = addressbook_init(false, true);
        $rfc822_header = new Rfc822Header;
        $rfc822_header->to = $rfc822_header->parseAddress($_POST['send_to'],
               true,array(), ';', $domain, array(&$abook,'lookup'));
        $rfc822_header->cc = $rfc822_header->parseAddress($_POST['send_to_cc'],
               true,array(), ';',$domain,array(&$abook,'lookup'));
        $rfc822_header->bcc = $rfc822_header->parseAddress($_POST['send_to_bcc'],
               true,array(), ';',$domain, array(&$abook,'lookup'));

        $to = array();
        $cc = array();
        $bcc = array();

        foreach (($rfc822_header->to ) as $value) {
          if ($value->host) $to[] = escapeshellarg($value->mailbox . "@" . $value->host);
          else $to[] = escapeshellarg($value->mailbox . "@" . $GLOBALS['domain']);
          }
        foreach (($rfc822_header->cc ) as $value) {
          if ($value->host) escapeshellarg($cc[] = $value->mailbox . "@" . $value->host);
          else $cc[] = escapeshellarg($value->mailbox . "@" . $GLOBALS['domain']);
          }
        foreach (($rfc822_header->bcc ) as $value) {
          if ($value->host) $bcc[] = escapeshellarg($value->mailbox . "@" . $value->host);
          else $bcc[] = escapeshellarg($value->mailbox . "@" . $GLOBALS['domain']);
          }

        $parsed_addr = array_merge($to, $cc, $bcc);

        $valid_addresses = $parsed_addr;

        //fix display under SM 1.4.0
        $send_to_str = htmlspecialchars ($_POST['send_to']);
        $send_to_cc_str = htmlspecialchars ($_POST['send_to_cc']);
        $send_to_bcc_str = htmlspecialchars ($_POST['send_to_bcc']);
        gpg_setglobal ( 'send_to' , $send_to_str );
        gpg_setglobal ( 'send_to_cc' , $send_to_cc_str );
        gpg_setglobal ( 'send_to_bcc' , $send_to_bcc_str );

        //end SM v >=1.3.1 processing
    } else {
        //parse using SM 1.2.x functions

         //call expand and parse for real
        $to_addr = expandRcptAddrs(parseAddrs($send_to));
        $cc_addr = expandRcptAddrs(parseAddrs($send_to_cc));
        $bcc_addr = expandRcptAddrs(parseAddrs($send_to_bcc));
        $parsed_addr = array_merge($to_addr, $cc_addr, $bcc_addr);

        //create the $valid_addresses array.
        foreach ($parsed_addr as $key => $value) {
            if (eregi("<(.+)@(.+)>", $value, $matches)) {
                $valid_addresses[] =
                escapeshellarg($matches[1] . "@" . $matches[2]);
            }; //end eregi processing
        }; //end foreach
        //end SM v 1.2.x processing
    };

    // show the results of the address expansion if debug is on
    if ($debug) {
        echo "\n<p>Parsed Address List:\n";
        foreach ($valid_addresses as $addr) {
            echo "<br>Address: $addr\n";
        }
        echo "<br>End Address Parsing Debug</p>\n";
    };


    return $valid_addresses;

};

/*********************************************************************/
/*
 * $Log: gpg_encrypt_functions.php,v $
 * Revision 1.77  2003/12/29 23:52:55  brian
 * localized strings discoverd by Alex Lemaresquier during French translation
 *
 * Revision 1.76  2003/12/28 15:06:51  brian
 * Tighten handling of parsed addresses even further
 * - added ';' as 4th parameter to parseAddress
 *   - credit to Noam Rathaus of Beyond Security Inc for the code trace
 * - added escapeshellarg around each returned address
 * Bug 139
 *
 * Revision 1.75  2003/12/19 20:52:01  ke
 * -added debug to update_trustdb function call
 *
 * Revision 1.74  2003/12/19 20:46:50  ke
 * -changed update trustdb command to use centralized function
 * -only check trustdb when untrusted keys are found
 *
 * Revision 1.73  2003/12/18 22:39:31  ke
 * -added trustdb check when decrypting messages
 *
 * Revision 1.72  2003/12/18 19:46:21  ke
 * -removed data_dir from system keyring file, since it's added earlier now
 *
 * Revision 1.71  2003/12/16 20:00:03  brian
 * changed instances of $system_keyring_file = $safe_data_dir to
 *    $system_keyring_file = $data_dir becasue $safe_data_dir is a
 *    hashed dir on a per-user basis
 *
 * Revision 1.70  2003/12/11 20:45:05  ke
 * -added extra_cmd to the command line for the case of no file to encrypt
 * bug 28
 *
 * Revision 1.69  2003/12/11 19:58:20  brian
 * - added corporate signature to gpg_decrypt
 * - fixed mis-handling of trusted system key in gpg_decrypt and gpg_encrypt fn
 * Bug 28
 *
 * Revision 1.68  2003/11/25 01:54:25  ke
 * -changed all getPref calls to use data_dir instead of safe_data_dir
 * bug 116
 *
 * Revision 1.67  2003/11/24 20:36:37  ke
 * -added use of getHashedDir to $data_dir in encrypt/decrypt for safely hashed dirs
 * bug 116
 *
 * Revision 1.66  2003/11/11 22:42:03  ke
 * -removed all parsing of gpg output, changed to call gpg_parse_output instead
 * bug 107
 *
 * Revision 1.65  2003/11/07 22:06:59  ke
 * -added ability to decrypt to an output file
 * -adds ability to decrypt attachments from outlook
 * bug 65
 *
 * Revision 1.64  2003/11/05 21:15:24  ke
 * -readded changes to allow decryption of files with embedded filenames
 *
 * Revision 1.63  2003/11/05 20:49:45  ke
 * -moved --decrypt filename to end of commandline, so that gpg will properly decrypt attachments with gpg_decrypt
 *
 * Revision 1.62  2003/11/03 18:21:16  brian
 * changed 'gpg: Oops:' to warning in gpg_decrypt fn
 *
 * Revision 1.61  2003/11/03 15:55:11  brian
 * localized some error strings
 * Bug 35
 *
 * Revision 1.60  2003/10/30 19:40:20  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.59  2003/10/23 19:17:39  brian
 * - first pass at handling $filename in gpg_decrypt fn
 * Bug 56
 *
 * Revision 1.58  2003/10/22 20:56:46  brian
 * - rearranged order of parameters in gpg_decrypt fn
 * Bug 56
 *
 * Revision 1.57  2003/10/17 18:21:58  brian
 * added keydb errors to list in gpg_decrypt fn
 *
 * Revision 1.56  2003/10/17 18:20:39  brian
 * added keydb erros to list in gpg_decrypt fn
 *
 * Revision 1.55  2003/10/17 15:12:55  brian
 * added "Primary key fingerprint:" to info list
 *
 * Revision 1.54  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.53  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.52  2003/10/13 19:39:43  brian
 * - added escapeshellarg to gpg_encrypt command for filenames with spaces
 * - trap for 'usage' syntax error in gpg command output
 * - improve debug error on failed attachment encrypt
 * Bug 74
 *
 * Revision 1.51  2003/10/07 11:56:09  brian
 * added --force-mdc flag to gpg_encrypt fn to deal with:
 *   gpg: WARNING: message was not integrity protected
 * Bug 69
 *
 * Revision 1.50  2003/10/04 17:44:02  brian
 * back out the block that stickas anything else in warnings,
 *   as it produces dups
 *
 * Revision 1.49  2003/10/03 22:35:14  brian
 * re-integrated  patch provided by Magyar Dénes to
 *   strip errors from body in gpg_decrypt
 * Bug 67
 *
 * Revision 1.48  2003/10/03 22:16:30  brian
 * added 'gpg: Oops:' to errors array in gpg_decrypt fn
 * Bug 67
 *
 * Revision 1.47  2003/09/24 18:39:23  ke
 * -Added extra check for existance of file to encrypt in gpg_encrypt
 * -Removed --output - argument to gpg when encrypting a file
 * bug 26
 *
 * Revision 1.46  2003/09/17 18:12:38  brian
 * patch submitted by Magyar Dénes breaks decrypt from encrypt on send
 * - backing out the ereg_replace commands for now
 *
 * Revision 1.45  2003/09/15 21:07:47  brian
 * added patch submitted by Magyar Dénes to work around:
 * - insecure memory warning now controlled by pref file
 * - add \n linefeeds for system OS's that don't process
 *   textarea submits properly in gpg_encrypt
 *
 * Revision 1.44  2003/07/08 01:50:10  brian
 * added 'gpg: Oops:' (trustdb errors) to warnings.
 *
 * Revision 1.43  2003/07/08 01:30:36  brian
 * move location of --armor in gpg_encrypt fn
 *
 * Revision 1.42  2003/06/17 11:16:45  brian
 * - added missing '$' to https_check in gpg_encypt and gpg_decrypt fn's
 * - credit to Joshua Vermette for noticing this bug.
 *
 * Revision 1.41  2003/06/12 21:01:13  brian
 * add ADK check and switches to gpg_encrypt fn
 *
 * Revision 1.40  2003/05/16 13:49:31  brian
 * added tags for phpdoc
 *
 * Revision 1.39  2003/05/15 22:39:56  brian
 * added error check for 'no such file or dir' in gpg_encrypt fn
 *
 * Revision 1.38  2003/05/13 22:55:10  brian
 * gpg_ecrypt fn:
 * - added check to reload 'globals' if we are called without pref vars beign set.
 * - added additional error conditions
 * Bug 26
 *
 * Revision 1.37  2003/05/09 20:03:25  brian
 * trim header information on return from gpg_decrypt fn
 * Bug 38
 *
 * Revision 1.36  2003/05/09 00:46:32  brian
 * - added $filename to gpg_encrypt fn
 * - mangled command line to account for file encryption in gpg_encrypt fn
 * - added additional error handling to gpg_encrypt fn
 * Bug 12
 *
 * Revision 1.35  2003/05/01 19:33:33  brian
 * - Added code to correctly process encrypt to partial recipients preference
 * - cleaned up excessive debug info in gpg_encrypt fn
 * Bug 36
 *
 * Revision 1.34  2003/04/30 18:13:07  brian
 * - added check in gpg_encrypt fn to return error if cyphertext is empty
 * - romoved todo item for same in gpg_encrypt.php
 *
 * Revision 1.33  2003/04/30 17:54:09  brian
 * added common debug output to gpg_parse_address fn
 *
 * Revision 1.32  2003/04/30 17:43:37  vinay
 * Fixed an address parsing bug. The Squirrelmail address look up functions treat a nickname without a match in the address book as an address at domain: i.e. "nick" is seen by squirrelmail as "nick@domain". Our GPG functions misrendered that as "nick@", which meant passing the wrong thing to GPG.
 *
 * New behavior translates "nick", not appearing in address book, as "nick@" . $GLOBALS['domain'].
 *
 * Revision 1.31  2003/04/27 12:13:38  brian
 * fixed indents to remove irregular use of tabstops - no functional change
 *
 * Revision 1.30  2003/04/26 16:53:49  brian
 * added failsafe check to trusted key handling to make sure key is actually set
 *
 * Revision 1.29  2003/04/24 13:59:10  brian
 * Re-enabled shared 'corporate' or 'system' keyring support
 *  - added checks for appropriate preferences to gpg_encrypt fn
 *  - tested all configurations
 *  - added trustedkey id pref for shared keyring, tested same
 *  - significantly improved completeness of error parsing in gpg_encrypt fn
 * Bug 32
 *
 * Revision 1.28  2003/04/20 08:06:58  joelm
 * -removed a small bug where a small was being appended to the body of
 * a message before it was encrypted
 *
 * Revision 1.27  2003/04/20 07:47:56  joelm
 * - make changes to allow encrypt & sign without needing a temp file.
 * It seems that "echo 'passphrase'\n'body'" didn't work but
 * "echo 'passphrase\n''body'" did. I'm not sure why but it works now.
 * Bug 11
 *
 * Revision 1.26  2003/04/18 13:23:16  brian
 *  - integrated Ryan's patch to use temp file
 *  - integrated GetTempFile fn
 *  - cleaned up security settings
 *  - Encrypt&Sign now verified working
 * Bug 11
 *
 * Revision 1.25  2003/04/16 20:24:39  brian
 * updated gpg_encrypt fn to properly support no passphrase on sign
 * Bug 11
 *
 * Revision 1.24  2003/04/16 02:35:23  brian
 * modified gpg_encrypt fn to support 'encrypt & sign'
 * Bug 11
 *
 * Revision 1.23  2003/04/13 16:27:55  brian
 * added command modifications from pre-merge v 1.16 to add signing functionality to gpg_encrypt
 * Bug 11
 *
 * Revision 1.22  2003/04/08 16:26:16  brian
 * added htmlspecialchars to gpg_parse_addr funtion to fix display bug in SM 1.4.0
 *
 * Revision 1.21  2003/04/08 15:16:52  brian
 * - added more debug statements to gpg_decrypt
 * - load no_signing_passwd pref inside the gpg_decrypt fn
 * Bug 21
 *
 * Revision 1.20  2003/04/07 01:16:37  brian
 * - mangled command string in gpg_decrypt to omit --passphrase fd0 if user has
 *   no_signing_passphrase = true
 * Bug 21
 *
 * Revision 1.19  2003/04/04 18:56:41  brian
 * separated out if statement to change command if the user has no_signing_passphrase set to true.
 * Bug 21
 *
 * Revision 1.18  2003/04/04 17:19:03  brian
 * fixed typo in elseif definition in gpg_decrypt function
 *
 * Revision 1.17  2003/04/04 05:22:52  brian
 * - added check for no_signing_passphrase to gpg_decrypt fn.
 * - Should execute without errors if user has no_signing_passphrase turned on.
 * Bug 21
 *
 * Revision 1.16  2003/04/02 18:26:40  brian
 * added check for no_signing_passwd  in fn gpg_decrypt
 *  - the user will not see an error if they have selected
 *    no_signing_passwd and submit without a passphrase
 *
 * Revision 1.15  2003/04/01 15:53:01  brian
 * improved error handling in gpg_encrypt and gpg_decrypt functions by
 * replacing strpos checks with substr_count checks
 * Bug 8
 *
 * Revision 1.14  2003/04/01 05:24:55  brian
 * improved error handling strings
 *
 * Revision 1.13  2003/03/31 15:43:25  brian
 * fixed typo on line 389, missign second right paren before 'break'
 *
 * Revision 1.12  2003/03/30 22:13:59  brian
 * Convert gpg_decrypt fn to use plaintext language instead ofcyphertext lang.
 * Bug 8
 *
 * Revision 1.11  2003/03/29 19:57:58  brian
 * clean up comments in gpg_parse_address fn
 * Bug 3
 *
 * Revision 1.10  2003/03/28 21:09:16  brian
 * address parsing in gpg_parse_address function now works for sm 1.2.x and sm >=1.3.x
 * tested on sm 1.4rc2a
 * fixes bug# 3
 * Bug 3
 *
 * Revision 1.9  2003/03/27 21:03:40  brian
 * fixed syntax error in gpg_decrypt, extra curly brace
 *
 * Revision 1.8  2003/03/27 19:21:11  brian
 * manual fix of $Log: entries
 *----------------------------
 * Revision 1.7 2003/03/27 19:11:38  brian
 * basics of decryption
 * gpg_decrypt function should work when called with body and passphrase
 *
 * Revision 1.6 2003/03/25 16:14:22  brian
 * Add to SM 1.4 parsing code
 *
 * Revision 1.5 2003/03/17 18:58:30  brian
 * - progress towards SM v >=1.3.1 compatibility
 * - path selection for includes now works on both
 *   SM 1.2.x and SM >= 1.3.1
 * - path selection for version check in gpg_parse_address
 *   now correct
 * - fixed skipped_keys bug in fn gpg_encrypt
 *
 * Revision 1.4 2003/03/15 21:04:46  brian
 * added version checknig code to gpg_parse_address
 * we now run code based on SM version
 *
 * Revision 1.3 2003/03/15 20:50:30  brian
 * fixes gpg_parse_address function to only return an array, not process it into a recipient list
 *
 * Revision 1.2 2003/03/15 20:43:16  brian
 * added gpg_parse_address function
 *
 * Revision 1.1 2003/03/11 23:30:30  tyler
 * - Initial breakout of the *_functions.php file
 *
 * This file was broken out from gpg_functions.php by tyler.
 * Early details on these functions may be found in the log
 * entries in gpg_functions.php - Brian
 */

?>
