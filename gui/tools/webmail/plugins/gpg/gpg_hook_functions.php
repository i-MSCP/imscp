<?php
/**
 * gpg_hook_functions.php
 * -----------
 * GPG plugin hook functions file, included by setup.php.
 * Updated to account for SM 1.4 pathing issues
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Brian Peterson
 * @author Aaron van Meerten
 *
 *
 * $Id$
 *
 */
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

require_once(SM_PATH.'plugins/gpg/gpg_pref_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');
require_once(SM_PATH.'functions/strings.php');
require_once(SM_PATH.'functions/prefs.php');

if ( !check_php_version(4,1) ) {
    global $_SESSION;
}

/********************************************************************/
/**
 * function gpg_identity_process_hook
 *
 * Function to handle general identity save triggered by options_identity_process hook
 *
 * @param array $args  Arguments passed by options_identities_process
 * @return void
 */
function gpg_identity_process_hook($args) {
    global $username, $data_dir;

    $gpg_ident_map = getPref($username, $data_dir, 'gpg_identity_map');
    $gpg_ident_map = unserialize($gpg_ident_map);

    if (!$gpg_ident_map) {
        $gpg_ident_map = array();
    }

    $sm_id = $args[2];
    $action = $args[1];

    if ($action == 'delete') {
        unset($gpg_ident_map[$id]);

        if (!empty($gpg_ident_map)) {
            $tmp_map = array();

            foreach($gpg_ident_map as $id=>$fpr) {
                if ($sm_id < $id) {
                    $tmp_map[$id-1] = $fpr;
                } else {
                    $tmp_map[$id] = $fpr;
                }
            }

            $gpg_ident_map = $tmp_map;

        }
    } else {

        $newid = $_POST['newidentities'];
        $gpg_ident_map[$sm_id] = $newid[$sm_id]['gpg'];
    }

    setPref($data_dir, $username, 'gpg_identity_map', serialize($gpg_ident_map));

}

/*********************************************************************/
/**
 * function gpg_identity_renumber_hook
 *
 * Function used to re-assign GPG->SM identity maps
 *
 * @param array $args  Arguments passed from option_identities_renumber hook
 * @return void
 */
function gpg_identity_renumber_hook($args) {
    global $username, $data_dir;

    $i = 0;

    $old_id = $args[1];
    $new_id = $args[2];

    if ($new_id == 'default') {
        $new_id = 0;
    }

    $gpg_ident_map = getPref($username, $data_dir, 'gpg_identity_map');
    $gpg_ident_map = unserialize($gpg_ident_map);

    if (!$gpg_ident_map) {
        return false;
    }

    $tmp_fpr = '';
    $tmp_map = array();
    foreach($gpg_ident_map as $sm_id=>$fpr) {
        if ($sm_id < $old_id) {
            $tmp_map[$sm_id + 1] = $fpr;
        } elseif ($sm_id > $old_id) {
            $tmp_map[$sm_id] = $fpr;
        } else {
            $tmp_map[$new_id] = $fpr;
        }
    }

    setPref($data_dir, $username, 'gpg_identity_map', serialize($tmp_map));

}

/*********************************************************************/
/**
 * function gpg_identity_table_hook
 *
 * Function to display possible gpg secret keys for user to select
 *
 * @param  array  $args  Array passed from identities_option_table
 * @return string        String used to output option list
 */
function gpg_identity_table_hook($args) {
    global $username, $data_dir;

    include_once(SM_PATH . 'functions/forms.php');

    $cur_keys = getPref($username, $data_dir, 'gpg_identity_map');
    $cur_keys = unserialize($cur_keys);

    if (!empty($cur_keys) && is_array($cur_keys)) {
        if (!empty($cur_keys[$args[2]])) {
            $match = $cur_keys[$args[2]];
        } else {
            $match = null;
        }
    } else {
        $match = null;
    }


    $res = '';

    $keys = list_secret_keys();

    if (!empty($keys) && is_array($keys)) {

        $res = '<tr' . $args[0] . '>' . "\n" .
               '  <td style="white-space: nowrap;text-align:right;">' . "\n" .
               '    ' . _("GPG Key") . "\n".
               '  </td>' . "\n" .
               '  <td>' . "\n" .
               addSelect('newidentities[' . $args[2] . '][gpg]', $keys, $match, true) .
               '  </td>' . "\n".
               '</tr>';
    }

    return $res;

}

/*********************************************************************/
/**
 * function download_entity
 *
 * Downloads an entity (part of a message) and returns a temporary filename for it
 *
 * @param imapConnection $imap_stream connection to imap server
 * @param mailbox $mailbox
 * @param string $id of the message
 * @param string $ent_id entity id, which part of the message to download
 * @param string $filename optional filename to download to
 * @return array $return with $return['filename'] and $return['errors']
 */

function download_entity($imap_stream, $mailbox, $id, $ent_id, $filename='',$passbody=false,$debug=0) {
        sqGetGlobalVar('messages',$msgcollection, SQ_SESSION);
        $return['errors'] = array();
        $mbx_response =  sqimap_mailbox_select($imap_stream, $mailbox);
        $body = mime_fetch_body($imap_stream, $id, $ent_id);
        $mymsg = sqimap_get_message($imap_stream,$id, $mailbox);
        $mymsg= &$mymsg->getEntity($ent_id);
        $header = $mymsg->header;
        $type0 = $header->type0;
        $type1 = $header->type1;
        $encoding = strtolower($header->encoding);
    if ($debug) { echo "Downloading data encoded $encoding<br>\n"; }
        if ($encoding == 'base64') {
        if ($debug) { echo "Decoding body<br>\n$body<br>\n"; }
               $body = base64_decode($body);
        if ($debug) { echo "Into body<br>\n$body<br>\n"; }
        }
    if ($filename) { $tempfile = $filename; }
        else { $tempfile = getTempFile(false); }
        if (is_file($tempfile)) {
                unlink($tempfile);
        }
        $fhandle = fopen($tempfile, "wb");
        if (!fwrite($fhandle, $body, strlen($body))) {
                $return['errors'][] = _("Could not write to temporary file: ") .  $tempfile;
        } else {
                fclose($fhandle);
                $return['filename'] = $tempfile;
        if ($passbody) { $return['body'] = $body; }
        }
        return $return;
}


/*********************************************************************/
/**
 * function gpg_decrypt_attachment_do
 *
 * Hook function for application/gpg-encrypted attachments
 * This function looks for a -----BEGIN PGP MESSAGE---- and will pop a link if found
 *
 * @param array $attachinfo array passed by squirrelmail to attachment handlers
 * @return void
 */

function gpg_decrypt_attachment_do(&$attachinfo) {
    bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
    /* Switch to your plugin domain so your messages get translated */
    textdomain('gpg');

    global $imapConnection;
        $actionlinks =& $attachinfo[1];
        $startMessage = $attachinfo[2];
        $msgid        = $attachinfo[3];
        $urlMailbox   = $attachinfo[4];
    $mailbox      = urldecode($urlMailbox);
        $entid        = $attachinfo[5];
        $defaulturl  =& $attachinfo[6];
    $dlfilename = urlencode($attachinfo[7]);

    $return = download_entity($imapConnection, $mailbox, $msgid, $entid,'',true);
    if (!(strpos($return['body'],"-----BEGIN PGP MESSAGE-----") === false)) {
            $actionlinks['gpg_decrypt']['href']=SM_PATH . "plugins/gpg/gpg_decrypt_attach.php?passed_id=$msgid&passed_ent_id=$entid&mailbox=$urlMailbox&dlfilename=$dlfilename&verifysig=0";
            $actionlinks['gpg_decrypt']['text']=_("Decrypt Attachment");
            $actionlinks['gpg_verify']['href']=SM_PATH . "plugins/gpg/gpg_decrypt_attach.php?passed_id=$msgid&passed_ent_id=$entid&mailbox=$urlMailbox&dlfilename=$dlfilename&verifysig=1";
            $actionlinks['gpg_verify']['text']=_("Verify Signature");
    }
    bindtextdomain('squirrelmail', SM_PATH . 'locale');
    textdomain('squirrelmail');
}

/*********************************************************************/
/**
 * function gpg_handle_signature_do
 *
 * Hook function for application/gpg-signature attachments
 * This function looks for the previous attachment and checks the signature
 * against it.  The resulting info is also placed in a session variable gpgverifyinfo
 *
 * @param attachinfo array passed by squirrelmail to attachment handlers
 * @return void
 */
function gpg_handle_signature_do(&$attachinfo) {
        global $imapConnection;
    global $debug;
    $debug=$GLOBALS['GPG_SYSTEM_OPTIONS']['debug'];
    bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
    /* Switch to your plugin domain so your messages get translated */
    textdomain('gpg');
        sqgetGlobalVar('gpgverifyinfo',$info,SQ_SESSION);
        $actionlinks =& $attachinfo[1];
        $startMessage = $attachinfo[2];
        $msgid        = $attachinfo[3];
        $urlMailbox   = $attachinfo[4];
        $mailbox      = urldecode($urlMailbox);
        $entid        = $attachinfo[5];
        $defaulturl  =& $attachinfo[6];
        $entparts = explode(".",$entid);
        $entparts[count($entparts)-1]=$entparts[count($entparts)-1]-1;
        $fileent=implode(".",$entparts);
        if ($fileent) {
            $ret = download_entity($imapConnection, $mailbox, $msgid, $fileent,'',$debug,$debug);
            if ($ret['filename']) {
                $dlfile = $ret['filename'];
                $sig = base64_decode(mime_fetch_body($imapConnection, $msgid, $entid));
                include_once(SM_PATH . 'plugins/gpg/gpg_sign_functions.php');
                if ($debug) { echo "Verifiying file $dlfile: <br><pre>\n"; print_r($return['body']); echo "\n Signature: \n"; print_r($sig); echo "\n</pre>"; }
                $ret = gpg_verify_signature($dlfile, $sig, $debug);

                if ($ret['verified'] == 'true') {
                    $actionlinks['gpg']['text'] = _("Good Signature");
                } else {
                    $actionlinks['gpg']['text'] = _("Bad Signature");
                }

                if (count($ret['signature']) > 0) {
                    $info[$entid] = implode("\n",$ret['signature']);
                } else {
                    $info[$entid] = _("Signature verification process failed.");
                }

                sqsession_register($info,'gpgverifyinfo');
                $actionlinks['gpg']['href'] = SM_PATH . "plugins/gpg/gpg_view_verify_text.php?mailbox=$urlMailbox&passed_id=$msgid&sig_ent_id=$entid&ent_id=$fileent";
            }
        }
    bindtextdomain('squirrelmail', SM_PATH . 'locale');
    textdomain('squirrelmail');

}



/*********************************************************************/
/**
 * function gpg_read_body_header_do
 *
 * This function is called by setup.php, within a hook
 * to initialize our read_body_header functions.
 *
 * @return void
 */
function gpg_read_body_header_do () {

    //pull in the globals we need from the read_body.php page
    global $imapConnection, $passed_id, $passed_ent_id, $mailbox;


    bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
    /* Switch to your plugin domain so your messages get translated */
    textdomain('gpg');


    //populate the message body
    $message = sqimap_get_message ($imapConnection, $passed_id, $mailbox);

    if ($message->body_part == "") {
    $body = gpg_fetch_full_body ($imapConnection, $passed_id, $mailbox);
    } else {
    $body = $message->body_part;
    }
    //check to see if we need to change encoding on body
    if (strtolower($message->header->encoding)=='quoted-printable') {
    $body = quoted_printable_decode($body);
    }

    //check to see if we need to verify a gpg signature
    if( $message->header->type0 == "multipart" && $message->header->type1 == "signed"
        && $message->header->getParameter("protocol")=="application/pgp-signature" )
        gpg_check_sign_pgp_mime ($message,$body);
    else
        gpg_check_sign ($body);

    //and then check to see if we should register the decrypt now link
    gpg_decrypt_link ($body);
    //and check to see if we should show the import link
    gpg_import_link ($body);
    //and check to see if there are any openpgp headers
    gpg_openpgp_header($body);
    bindtextdomain('squirrelmail', SM_PATH . 'locale');
    textdomain('squirrelmail');
}

/*********************************************************************/
/**
 * function gpg_openpgp_header
 *
 * This function is called to parse whatever "openpgp:"
 * headers are found in the message and to display add
 * and respond links to those headers.
 *
 * @param string $body
 * @return void
 */
function gpg_openpgp_header($body) {
    global $username;
    global $data_dir;
    if($GLOBALS['GPG_SYSTEM_OPTIONS']['systemparse_openpgp_header'] != 'true' or
       getPref ($data_dir, $username, 'parse_openpgp_header', 'true') != 'true') {
	return;
    }
    include_once(SM_PATH.'plugins/gpg/openpgp_header.php');
    $openpgp_header = new openpgp_header();
    $openpgp_header->parseHeader($body);

    if(!$openpgp_header->strval) {
	return;
    }
    if($openpgp_header->id or $openpgp_header->url or $openpgp_header->fingerprint) {
	$url = '../plugins/gpg/modules/import_key_proxy.php?';
	$first = true;
	foreach(array("id" => $openpgp_header->id, "url" => $openpgp_header->url, "fingerprint" => $openpgp_header->fingerprint) as $key => $val) {
	    if(strlen($val) <= 0) {
		continue;
	    }
	    $url .= $first ? "" : "&amp;";
	    $first = false;
	    $url .= $key . "=" . urlencode($val);
	}
    }

    echo '<tr>';
    echo html_tag('td', '<b>' . _("OpenPGP") . ':&nbsp;&nbsp;</b>',
		  'right', '', 'valign="middle" width="20%"') . "\n";
    echo html_tag('td', htmlspecialchars($openpgp_header->strval) . '<small>' .
		  ($url ? ' <a href=' . $url . '>' . _("Add key") . '</a>' : ''),
		  'left', $color[0], 'valign="middle" width="80%"') . "\n";
    echo '</tr>';
}

/*********************************************************************/
/**
 * function gpg_fetch_full_body
 *
 * This function is called before
 * gpg_check_sign and gpg_decrypt_link
 * to retrieve the messge body for those functions
 *
 * @param string  $mailbox          mailbox
 * @param integer $imapConnection   handle to the IMAP connection
 * @param integer $passed_id        message ID to retreive from the IMAP folder
 * @return string $body as string
 */
function gpg_fetch_full_body ($imapConnection, $passed_id, $mailbox) {
   //retrieve the message body
   global $uid_support;

   $body=sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[]", true, $a, $b,$uid_support);
   if (is_array($body)) {
       $body = implode($body,'');
   };

   return ($body);
}

/*********************************************************************/
/**
 * function gpg_get_message_body_imap
 *
 * get the body of a given part of a message
 *
 * @param class   &$message         Message object to get the body for
 * @param integer $imapConnection   handle to the IMAP connection
 * @param integer $passed_id        message ID to retreive from the IMAP folder
 * @return void (body stuffed into message object)
 *
 */
function gpg_get_message_body_imap( &$message, $imapConnection, $passed_id )
{
    global $uid_support;

    $message->body_part = mime_fetch_body( $imapConnection, $passed_id, $message->entity_id );
    $message->decoded_body = decodeBody( $message->body_part, $message->header->encoding );
}

/*********************************************************************/
/**
 * function gpg_get_mime_header
 *
 * download just the mime header from imap
 * (note: this does not setup a MessageHeader class
 *
 * @param class   $message          Message object to get the entity out of
 * @param integer $imapConnection   handle to the IMAP connection
 * @param integer $passed_id        message ID to retreive from the IMAP folder
 * @return string $header           RFC 822 header to parse for mime type
 *
 */
function gpg_get_mime_header ( $message, $imapConnection, $passed_id )
{
    global $uid_support;
    $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[".$message->entity_id.".MIME]", true, $response, $errmessage, $uid_support);
    if( is_array( $read ) )
    {
        // this loop is stolen from mime_fetch_body(...)
        do
        {
            $topline = trim(array_shift($read));
        } while($topline && ($topline[0] == '*') && !preg_match('/\* [0-9]+ FETCH.*/i', $topline)) ;
        $header = implode('', $read );
    }
    return $header;
}

/*********************************************************************/
/**
 * function gpg_decrypt_link
 *
 * This function is called by the read_body hook (above)
 *
 * Use to see if the message contains an encrypted body
 * if the message contains encrypted text, display a link to the decrypt code.
 *
 * @param string $body
 * @return void
 */
function gpg_decrypt_link($body) {
    global $debug;
    global $passed_ent_id, $passed_id, $mailbox,$mbx_response;
    global $data_dir;
    global $safe_data_dir;
    global $username;
    global $startMessage, $sort;
    global $prev, $next;
    $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
    // set this to 1 if you want to double check the prefs loading
    // $debug=0;

    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);

    $gpg_key_dir ="$safe_data_dir$username.gnupg";
    $secring="$gpg_key_dir/secring.gpg";

    require_once(SM_PATH . 'plugins/gpg/gpg_functions.php');
    $allowprivatekeys = $GLOBALS['GPG_SYSTEM_OPTIONS']['allowprivatekeys'];

    $passphrase_cached = 'false';

    if (gpg_is_passphrase_cached()) {
            $passphrase_cached = 'true';
    }; //end passphrase caching check

    //get the next and previous links
    if (!$prev) {
        $prev = findPreviousMessage($mbx_response['EXISTS'], $passed_id);
        $next = findNextMessage($passed_id);
    }
    $messageblock = '';
    //double check for a an encrypted body before displaying
    $sep = '-----BEGIN PGP MESSAGE-----';
    $end = '-----END PGP MESSAGE-----';
    $pos = strpos($body,$sep);
    if ($pos !== false) {
        $epos = strpos($body,$end,$pos);
    $epos = $epos + strlen($end);
        $messageblock = substr($body,$pos,$epos-$pos);
    sqsession_register($messageblock,'gpg_encrypted_message');
    }
    if ($pos !== false) {
        //system admin turned off private keys
        if ($allowprivatekeys =='false') {
            echo '<p align=center>'
            . _("The System Administrator has disabled private key functions.")
            . "</p>\n";
            return;
        };

        //we don't have a secret keyring
        if ((!file_exists($secring)) or (filesize($secring)==0)) {
            echo '<p align=center>'
                . _("This message contains encrypted content.")
                . "<br>\n"
                . _("To decrypt this message inside Squirrelmail:")
                . '&nbsp;'
                . "<a href='../plugins/gpg/gpg_options.php'>"
                . _("Setup the GPG Plugin with a secret key.")
                .'</a>'
                ."\n";

            return;
        };
        //display the Decrypt Now link
        echo "<tr>\n";
    if (isset($_SESSION['gpgerror'])) {
        $errors=$_SESSION['gpgerror'];
        unset($_SESSION['gpgerror']);
        echo _("There was a problem with your request") . ':<br>';
        foreach ($errors as $err) {
            echo "<li>$err\n";
        }
    }
    echo "<form name='decrypt' action='" . SM_PATH . "plugins/gpg/gpg_options.php' method='post'>";
        echo<<<TILLEND
             <input type=hidden name=MOD value='gpgdecrypt'>
             <input type=hidden name=passphrase value ='$passphrase_cached'>
             <input type=hidden name=passed_ent_id value='$passed_ent_id'>
             <input type=hidden name=passed_id value='$passed_id'>
             <input type=hidden name=mailbox   value='$mailbox'>
             <input type=hidden name=prevmsg   value='$prev'>
             <input type=hidden name=nextmsg   value='$next'>
         <input type=hidden name=startMessage value='$startMessage'>
         <input type=hidden name=sort   value='$sort'>

        <p align=center>
        <script type='text/javascript'>
        <!--
        function gpg_decrypt_check(objform){
           if (objform.form.passphrase.value=='true') {
              objform.form.submit();
           } else {
TILLEND;
echo "\twindow.open('" . SM_PATH . "plugins/gpg/gpg_pop_init.php?MOD=passpop&psaction=decrypt','Decrypt_Now','status=yes,width=300,height=200,resizable=yes,scrollbars=yes');\n}\n}";
        echo 'document.write("<input type=\'button\' value=\'' . _("Decrypt Message Now") . '\' onclick=\'gpg_decrypt_check(this)\'>");';
echo <<<TILLEND
        //-->
        </script>
        </p>
        </form>
    </tr>
TILLEND;
    }; //end double check for encrypted body
};

/*********************************************************************/
/**
 * function gpg_import_link
 *
 * This function is called by the read_body hook (above)
 *
 * Use to see if the message contains an encrypted body
 * if the message contains encrypted text, display a link to the decrypt code.
 *
 * @param string $body from gpg_fetch_full_body fn
 * @return void
 */
function gpg_import_link($body) {
    global $passed_id, $mailbox;
    global $data_dir;
    global $safe_data_dir;
    global $username;
    $import_body = '';
    $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
    $gpg_key_dir ="$safe_data_dir$username.gnupg";
    $sep = '-----BEGIN PGP PUBLIC KEY BLOCK-----';
    $end = '-----END PGP PUBLIC KEY BLOCK-----';
    $pos = strpos($body,$sep);
    if ($pos !== false) {
        while ($pos !== false) {
            $epos = strpos($body,$end,$pos);
            $keyblock = substr($body,$pos,($epos+34)-$pos);
            $import_body .= $keyblock . "\n";
            $pos = strpos($body,$sep,$epos);
        }
          // show some details about this key

          // display the Import Now link
        echo '<form action="' . SM_PATH . '/plugins/gpg/modules/keyring_main.php" method="POST">';
        echo<<<TILLEND
          <p align=center>
            <input type=hidden name=keystring value="$import_body">
            <input type=submit name=textadd value="
TILLEND;
        echo _("Import Key contained in this email now");
        echo '"></p></form>'; //'
    }
}

/*********************************************************************/
/**
 * function gpg_check_sign
 *
 * This function is called by the read_body hook (above)
 *
 * @param string $body
 * @return integer 0|1
 */
function gpg_check_sign($body,$encrypted=false) {
  $return=0;
  $hasGPGSig = 1;
  // attempt to check the signature if it looks like it has one
  global $data_dir;
  global $username;
  global $safe_data_dir;
  global $debug;
  $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
  $gpg_key_dir ="$safe_data_dir$username.gnupg";
  $sep = '-----BEGIN PGP SIGNED MESSAGE-----';
  $esep = '-----END PGP SIGNATURE-----';

  $gpgHeaderLine = "<tr><td align=\"right\" VALIGN=\"TOP\" WIDTH=\"20%\"><b>" .
                   _("Signature") . ":&nbsp;&nbsp;</b></td>\n";
  $gpgHeaderLine .= "<td align=\"left\" VALIGN=\"TOP\" WIDTH=\"80%\">\n";

  $pos = strpos($body,$sep);
  if ($pos !== false) {
    $return = 1;
    $epos = strpos($body,$esep,$pos);
    if ($epos !== false) {
    $signedbody = substr($body,$pos,$epos+strlen($esep));
        $body = $signedbody;
        // set this to 1 if you want to double check the prefs loading
        $debug=0;
        load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
        load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);
    require_once(SM_PATH.'plugins/gpg/gpg_execute.php');
    $gpg=initGnuPG();
    $return=$gpg->verify($body);

        if ($return['untrusted'] == 'true') {
            $gpg->update_trustdb();
            $return=$gpg->verify($body);
        }

        if (is_array($return['signature'])) {
            foreach ($return['signature'] as $line) {
                    $gpgHeaderLine .= htmlspecialchars($line);
                    $gpgHeaderLine .= "<br>";
            }
        }
        $gpgHeaderLine .= "</SMALL></TD></TR>";
    } else {
       $gpgHeaderLine .= _("Unsigned");
       $hasGPGSig = 0;
    }
  } else {
    $encrypted = strpos($body,'-----BEGIN PGP MESSAGE-----');
    if (!$encrypted) {
        $gpgHeaderLine .= _("Unsigned");
        $hasGPGSig = 0;
    } else {
        $gpgHeaderLine .= _("Encrypted");
    }
  }
  $gpgHeaderLine .= "</SMALL></TD></TR>";
  if ($hasGPGSig) { echo $gpgHeaderLine; }
  return ($return);
}

/*********************************************************************/
/**
 * function gpg_strip_sign
 *
 * This function strips a gpg signature from the text
 *
 * @param string $signedtext
 * @return string unsigned body
 */
function gpg_strip_sign($signedtext) {
    $sep = "-----BEGIN PGP SIGNED MESSAGE-----";
    $sig = "-----BEGIN PGP SIGNATURE-----";
    $end = "-----END PGP SIGNATURE-----";
    if (!(strpos($signedtext,$sep) == 0)) { return $signedtext; }
    $exploded = explode("\n",$signedtext);
    $startbody=true;
    $skipnextline=false;
    $return='';
    foreach ($exploded as $line) {
        if ($line == $sep) {
            $startbody=true;
            $skipnextline=true;
            continue;
        }
        if ($line == $sig) {
            $startbody=false;
            continue;
        }
        if ($line == $end) {
            $startbody=true;
            continue;
        }
        if ($startbody && !$skipnextline) {
            $return = $return . $line . "\n";
            continue;
        } else { $skipnextline=false; continue; }
    }
    return $return;
}

/*********************************************************************/
/**
 * function gpg_check_sign_pgp_mime
 *
 * Used to read a detached signature file of type applicatiion/pgp-signature
 *
 * This function is called by the read_body hook (above)
 *
 * @param object $message  Message object as retrieves by sqimap__get_message
 * @param string $fullbodytext Body text string as retrieved directly from IMAP
 * @return integer $return
 */
function gpg_check_sign_pgp_mime(&$message,$fullbodytext) {
    $return=1;
    // attempt to check the signature if it looks like it has one
    global $data_dir;
    global $safe_data_dir;
    global $username;
    global $imapConnection, $passed_id;
    $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;

    $gpg_key_dir ="$safe_data_dir$username.gnupg";

    $messageData = &$message->entities[0];
    $messageSignature = &$message->entities[1];

    gpg_get_message_body_imap( $messageData, $imapConnection, $passed_id );
    gpg_get_message_body_imap( $messageSignature, $imapConnection, $passed_id );

    // PGP/MIME messages have the signature signed over the MIME headers as well
    $mimeHeader = gpg_get_mime_header( $messageData, $imapConnection, $passed_id );

    // the signed data is the contents between the mime boundary
    // RFC3156 section 5
    //get regular expression

    preg_match("/^.*boundary=\"?(.+(?=\")|.+).*/im",$fullbodytext,$reg);
    $regstr = '--'. $reg[1];
    $begin = strpos($fullbodytext,$regstr);

    //begin after /r/n
    $begin = $begin + strlen($regstr) + 2;

    //end before final /r/n
    $end = strpos($fullbodytext,$regstr,$begin) - 2;

    $messageSignedText = substr($fullbodytext,$begin,$end-$begin);
/*
commented until errors can be worked out
    if ($messageData->header->encoding == 'quoted-printable') {
    $messageSignedText = quoted_printable_decode($messageSignedText);
    }
*/
    // THIS IS VERY IMPORTANT. Must make sure all the lines end in \r\n
    // see RFC3156 section 5
    $messageSignedText = ereg_replace("\r\n", "\n", $messageSignedText );
    $messageSignedText = ereg_replace("\r", "\n", $messageSignedText );
    $messageSignedText = ereg_replace("\n", "\r\n", $messageSignedText );

    //$messageSignedText = escapeshellarg($messageSignedText);
    $messageSignedText = ereg_replace("\"", "\\\"", $messageSignedText );


    // first, we put the detached signature in a file
    // then we send the signed data as STDIN and tell GnuPG to get the
    // signature from the file
    $detachedSignatureFilename = tempnam( '', "GPG" );
    if( $detachedSignatureFilename != "FALSE" )
    {
        chmod( $detachedSignatureFilename, 0600 );

        $detachSigFile = fopen($detachedSignatureFilename,'w');
        fwrite( $detachSigFile, $messageSignature->decoded_body );
        fclose( $detachSigFile );

        // set this to 1 if you want to double check the prefs loading
        $debug=0;
        load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
        load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);
        $path_to_gpg = $GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
    require_once(SM_PATH.'plugins/gpg/gpg_execute.php');
    $params = "--homedir $gpg_key_dir --verify ".$detachedSignatureFilename." -";
        if ($debug) {
        echo "Signed Message: $messageSignedText<p>";
        echo "Signature: $messageSignature->decoded_body<p>";
            echo "gpg command: ".$command."<br>\n";
    }
        $return=gpg_execute($debug,$params,NULL,"-n \"$messageSignedText\"");
        $results = $return['signature'];
        echo "<tr><td align=\"right\" VALIGN=\"TOP\" WIDTH=\"20%\"><b>" . _("Signature") . ":&nbsp;&nbsp;</b></td>\n";
        echo "<td align=\"left\" VALIGN=\"TOP\" WIDTH=\"80%\">\n";

        if (is_array($results)) {
            foreach ($results as $result) {
                    print htmlspecialchars($result);
                    print "<br>";
            }
        print "</SMALL></TD></TR>";
        } else { echo _("Unsigned"); }
        print "</SMALL></TD></TR>";

        // delete our temp file
        unlink( $detachedSignatureFilename );
    }

    return ($return);
}

/*********************************************************************/
/**
 * function gpg_compose_send_do
 *
 * This function is called by the main SM plugin_init (above)
 * to initialize our compose_send hook functions
 *
 * This is where the Encrypt on Send and
 * Encrypt and Sign on Send functions go.
 *
 * @return void
 */
function gpg_compose_send_do (&$composeMessage) {
    global $version;
    global $debug;
    global $session;
    global $compose_messages;
    global $data_dir;
    global $username;
    $debug=$GLOBALS['GPG_SYSTEM_OPTIONS']['debug'];
    //set debug=1 if you need to see what is going on in here
    //$debug=1;

    if (!sqgetGlobalVar('base_uri',$base_uri)) {
    $base_uri = get_location() . SM_PATH;
    }
    if ($debug) { echo "base_uri: $base_uri<p>"; }

    require_once(SM_PATH . 'plugins/gpg/gpg_functions.php');
    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);

    //openpgp_header
    add_openpgp_header($composeMessage);

    $cache_passphrase   = getPref ($data_dir, $username, 'cache_passphrase');
    $allowpassphrasecaching   = $GLOBALS['GPG_SYSTEM_OPTIONS']['allowpassphrasecaching'];

    if (array_key_exists ('encrypt_on_send', $_POST)) {
        $encrypt_on_send = $_POST['encrypt_on_send'];
    } else {
        $encrypt_on_send = '';
    };

    if (array_key_exists ('sign_on_send', $_POST)) {
        $sign_on_send = $_POST['sign_on_send'];
    } else {
        $sign_on_send = '';
    };

    if (array_key_exists ('passphrase', $_POST)) {
        $passphrase = $_POST['passphrase'];
    };

    //we need to pull these in as globals from SM for scoping reasons.

    sqgetGlobalVar('username',$username);
    sqgetGlobalVar('data_dir',$data_dir);

    if (($encrypt_on_send=='encrypt') or ($sign_on_send=='sign')){
    if ($debug) { echo "<p>GPG Action needed: $encrypt_on_send $sign_on_send.<br>"; }
        if ($encrypt_on_send=='encrypt'){
            $_POST['encrypt'] = 'true';
        } else { $_POST['encrypt'] = ''; }
        if ($sign_on_send=='sign') {
            $_POST['gpgsign'] = 'true';
        } else { $_POST['gpgsign'] = ''; }

    if (!$debug) {
            ob_start();
        }
        if (array_key_exists('gpgsign',$_POST) and $_POST['gpgsign'] == 'true' and array_key_exists('encrypt',$_POST) and $_POST['encrypt']!='true') {
            if ($debug) {
                echo "Signing only!<br>";
            }

            //get the passphrase
            if ($passphrase != '') {
                if ($passphrase=='true' and gpg_is_passphrase_cached()) {
                    if ($debug) { echo "<br>Grabbing cached passphrase!\n"; }
                    $passphrase=gpg_get_cached_passphrase();
                }
            }  else {
                if ($debug) { echo "No passphrase found.<br>"; }
            }
            require_once(SM_PATH . 'plugins/gpg/gpg_sign_functions.php');
            $messageAttachments = $composeMessage[1]->getAttachments ();
            $checkattachment=strcmp(strtolower($messageAttachments[1]->mime_header->disposition->name),'attachment');

            if (!$checkattachment) {
                $return = gpg_sign_message($composeMessage[1]->entities[0]->body_part,$passphrase,$debug);
                if ((!$return['errors'][0]) && $return['cyphertext']) {
                    $cyphertext = $return['cyphertext'];
                    $composeMessage[1]->entities[0]->body_part = $cyphertext;
                } else {
            if ($debug) { echo "Error in signing, storing variables for reuse<br>"; }
                    sqgetGlobalVar('subject',$subject);
                    sqgetGlobalVar('send_to',$send_to);
                    sqgetGlobalVar('send_to_cc',$send_to_cc);
                    sqgetGlobalVar('send_to_bcc',$send_to_bcc);
                    sqgetGlobalVar('body',$body);
                    $sign_error['subject']=$subject;
                    $sign_error['send_to'] = $send_to;
                    $sign_error['send_to_cc'] = $send_to_cc;
                    $sign_error['send_to_bcc'] = $send_to_bcc;
                    $sign_error['body'] = $body;
                    $sign_error['errors'] = $return['errors'];
                    $sign_error['warnings'] = $return['warnings'];
                    sqsession_register($sign_error,'encrypt_error');
            if ($debug) { echo "Error array:<br><pre>"; print_r($_SESSION['encrypt_error']); echo "</pre>"; }
                }
                foreach($messageAttachments as $key=>$attachment) {
                  if ($key > 0) {
            $signmessage =& $composeMessage[1]->entities[$key];
                    $signmessage = new Message();
                    $signmessage->entities[0] = $attachment;
            if ($signmessage->entities[0]->mime_header->type0=='text') {
            $signmessage->entities[0]->mime_header->type0='application';
            $signmessage->entities[0]->mime_header->type1='octet-stream';
            }
                    $signmessage->mime_header= new MessageHeader();
                    $signmessage->mime_header->type0='multipart';
                    $signmessage->mime_header->type1='signed';
            $signmessage->mime_header->parameters['protocol'] = 'application/pgp-signature';
                    $signmessage->entities[1]= new Message();
                    $signmessage->entities[1]->mime_header = new MessageHeader();
                    $signmessage->entities[1]->mime_header->type0='application';
                    $signmessage->entities[1]->mime_header->type1='pgp-signature';
            $filename = $attachment->att_local_name;
            $ret = gpg_sign_attachment($filename, $passphrase, $debug);
            $filename = $attachment->mime_header->disposition->properties['filename'];
            $signmessage->entities[1]->att_local_name=$ret['filename'];
            $signmessage->entities[1]->mime_header->parameters['name'] = $filename . ".sig.asc";
            $signmessage->entities[1]->mime_header->disposition = new Disposition('attachment');
            $signmessage->entities[1]->mime_header->disposition->properties['filename']=$filename . ".sig.asc";
                  }
                }
        $composeMessage[1]->setEntIds($composeMessage[1]);
            } //end check attachment if
            else {
                $return = gpg_sign_message($composeMessage[1]->body_part,$passphrase,$debug);
            if ((!$return['errors'][0]) && $return['cyphertext']) {
                $cyphertext = $return['cyphertext'];
                $composeMessage[1]->body_part = $cyphertext;
            } else {
                /**
                 * currently the error handling for sign on send errors
                 * is done below, with the include to gpg_encrypt.php
                 * so now we grab our variables and stuff 'em into an
                 * array in the session for gpg_encrypt.php to grab
                 */
        if ($debug) { echo "Error in signing, storing variables for reuse<br>"; }
                sqgetGlobalVar('subject',$subject);
                sqgetGlobalVar('send_to',$send_to);
                sqgetGlobalVar('send_to_cc',$send_to_cc);
                sqgetGlobalVar('send_to_bcc',$send_to_bcc);
                sqgetGlobalVar('body',$body);
                $sign_error['subject']=$subject;
                $sign_error['send_to'] = $send_to;
                $sign_error['send_to_cc'] = $send_to_cc;
                $sign_error['send_to_bcc'] = $send_to_bcc;
                $sign_error['body'] = $body;
                $sign_error['errors'] = $return['errors'];
                $sign_error['warnings'] = $return['warnings'];
                sqsession_register($sign_error,'encrypt_error');
        if ($debug) { echo "Error array:<br><pre>"; print_r($_SESSION['encrypt_error']); echo "</pre>"; }
                //print errors here sometime
            }
      } //end check attachment else
        } else {
        //set $cyphertext to be the return from gpg_encrypt.php
            $cyphertext = include ('../plugins/gpg/gpg_encrypt.php');
        }
        if (is_array($cyphertext)) { $cyphertext=implode($cyphertext,"\n"); }
            if (!$cyphertext) {
        echo _("Problems with the cyphertext output:") . '<pre>';
                echo $cyphertext;
                echo "</pre>End.\n";
            }

            if (!$debug) {
                ob_end_clean();
            } else {
                //if $debug
                echo '<br> Squirrelmail will now complain that the headers have already been set.';
                echo '<br> Your message has been sent. ';
                echo '<br> Turn off debug (debug=0) to get rid of the header error.';
            }

            if ($cyphertext) {
                // set the body to be the cyphertext
                //  $composeMessage[1]->body_part = $cyphertext;
                //  $cyphertext = str_replace("\r\n","\n",$cyphertext);
                //  $cyphertext = str_replace("\r","\n",$cyphertext);
                //  $composeMessage[1]->setBody($cyphertext);

                //this line should ensure backward compatibility with SM 1.4.0
                $body = $cyphertext;
            } else {
                //send us to gpg_encrypt.php again with the error array set so we can display errors.
                header ('Location: ' . $base_uri . 'plugins/gpg/gpg_encrypt.php?encrypt_on_send_error=1&session=' . $session);
                die(_("evil_muppets killed your encryption"));
            };
        }; //end check for execute of 'on send' functionality

    return $composeMessage;
}; //end compose_send function

/*********************************************************************/
/**
 * function add_openpgp_header ()
 *
 * This function adds the openpgp header to the outgoing message.
 *
 * @return void
 */

function add_openpgp_header(&$composeMessage) {
    global $data_dir;
    global $username;
    if($GLOBALS['GPG_SYSTEM_OPTIONS']['systemgenerate_openpgp_header'] != 'true' or
        getPref ($data_dir, $username, 'generate_openpgp_header', 'true') != 'true') {
	return;
    }
    $my_id = gpg_get_signing_key_id();
// getPref ($data_dir, $username, 'signing_key_id');
    $my_url = getPref ($data_dir, $username, 'openpgp_header_url');
    if($my_id) {
    	$my_id=substr($my_id, -16, 16);
	$composeMessage[1]->rfc822_header->more_headers['OpenPGP'] = "id=" . $my_id . ($my_url ? "; url=$my_url" : "");
    }
}

/*********************************************************************/
/**
 * function gpg_optpage_register_block_do ()
 *
 * This function formats and adds the plugin and its description to the
 * Options screen.
 *
 * @return void
 */
function gpg_optpage_register_block_do() {
    global $debug;
    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);
    $expose_options_link=$GLOBALS['GPG_SYSTEM_OPTIONS']['expose_options_link'];
    if ($expose_options_link=='true') {
        global $optpage_blocks;
        $gpg_js_warning = '';
        if (soupNazi()) {
            $gpg_js_warning = '<br>'._("Some functions of the GPG Plugin require Javascript to be enabled in your browser.  Some functions may not work correctly without Javascript.");
        }
        //removed SoupNazi check from stopping display becasue it wasn't relevant to the option block
        bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
        textdomain('gpg');
        $optpage_blocks[] =
           array(
              'name' => _("GPG Plugin Options"),
              'url'  => '../plugins/gpg/gpg_options.php',
              'desc' => _("The GPG Encryption Plugin will allow you to encrypt, sign, and decrypt messages in accordance with the OpenPGP standard for email security and authentication.").$gpg_js_warning,
              'js'   => TRUE);
        bindtextdomain('squirrelmail', SM_PATH . 'locale');
        textdomain('squirrelmail');
    }
}

/*********************************************************************/
/**
 * function gpg_compose_row_do ()
 *
 * This function adds a "Encrypt Now" and "GPG Sign" link to the
 * "Compose" row during message composition.
 *
 * @return void
 */
function gpg_compose_row_do() {
  /**
   * soupNazi checks if this browser is capable of using Javascript with the GPG Plugin
   */
  if (!soupNazi()) {
    /**
     * The browser checks out.
     * Diasplay GPG Plugin Compose page Button Row.
     */

    bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
    /* Switch to your plugin domain so your messages get translated */
    textdomain('gpg');

    /**
     * Check to see if the user has a key directory (and presumably keys)
     * before displaying the "Encrypt Now" button.
     * Assume that if the user has a .gnupg directory, they have a keyring.
     * This assumption is not foolproof, but should work
     * Extend this to include signing check once secret keys are supported.
     */
     global $data_dir;
     global $username;
     global $version;
     global $mailbox;
     global $body;
     global $safe_data_dir;
     global $debug;
    $safe_data_dir = getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
     if (isset($_SESSION['gpg_dbody']) and isset($_GET['gpgreply'])) {
        $body=$_SESSION['gpg_dbody'];
     }
     unset($_SESSION['gpg_dbody']);
     if (substr_count($mailbox, "Drafts")>0) {
        $body = gpg_strip_sign($body);
     }
     $gpg_key_dir ="$safe_data_dir$username.gnupg";
     $pubring="$gpg_key_dir/pubring.gpg";
     $secring="$gpg_key_dir/secring.gpg";
     global $no_encrypt_on_setup;
     global $gpg_export;
     $use_signing_key_id = getPref ($data_dir, $username, 'use_signing_key_id');
     $auto_encrypt       = getPref ($data_dir, $username, 'auto_encrypt');
     $auto_sign          = getPref ($data_dir, $username, 'auto_sign');
     $no_signing_passwd  = getPref ($data_dir, $username, 'no_signing_passwd');
     $cache_passphrase   = getPref ($data_dir, $username, 'cache_passphrase');
     $allowpassphrasecaching   = $GLOBALS['GPG_SYSTEM_OPTIONS']['allowpassphrasecaching'];
     sqgetGlobalVar('gpgreply',$encrypt_reply);
     $passphrase_cached = 'false';
//     if ($allowpassphrasecaching == 'true' and $cache_passphrase=='true') {
    if (gpg_get_cached_passphrase() != 'false') {
        $passphrase_cached = 'true';
    };
//     }; //end passphrase caching check

// set this to 1 if you want to double check the prefs loading
//$debug=0;

//load our system and local prefs files so we can figure out where things are
load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);

$force_sign_on_send=$GLOBALS['GPG_SYSTEM_OPTIONS']['force_sign_on_send'];
$force_encrypt_on_send=$GLOBALS['GPG_SYSTEM_OPTIONS']['force_encrypt_on_send'];

     if (($auto_encrypt == 'true') or( $encrypt_reply==1)) { $aechecked='checked=true'; } else {$aechecked = '';};
     if ($auto_sign == 'true') { $aschecked='checked=true'; } else {$aschecked ='';};
     if (array_key_exists('encrypt_on_send',$_POST) && ($_POST['encrypt_on_send'] == 'encrypt') ||
       $force_encrypt_on_send == 'true') { $aechecked='checked=true'; }
     if (array_key_exists('sign_on_send',$_POST) || $force_sign_on_send == 'true') { $aschecked='checked=true'; }

$debug=$GLOBALS['GPG_SYSTEM_OPTIONS']['debug'];
$expose_compose_buttons=$GLOBALS['GPG_SYSTEM_OPTIONS']['expose_compose_buttons'];
$expose_encrypt_now_button=$expose_compose_buttons;
$expose_encrypt_sign_now_button=$expose_compose_buttons;
//If we are forcing the user to sign then remove the option to just encrypt
if ($force_sign_on_send == 'true') {
    $expose_encrypt_now_button = 'false';
}
if ($debug) { echo "<p>no_encrypt_on_setup: $no_encrypt_on_setup<br>gpg_export: $gpg_export<br>pubring: $pubring<br>secring: $secring<br>"; }


/**
 * Some people may choose to disable javascript even though their
 * browser is capable of using it. So these folks don't complain,
 * use document.write() so the "Encrypt Now" and "GPG Sign"
 * buttons are not displayed if js is off in the browser.
 *
 * Also, check to make sure that the user has keyrings on the system before
 * displaying buttons for features they can't use.
 *
 * $no_encrypt_on_setup is a variable that is set to make sure that the
 * buttons don't display after we have already encrypted the message.
 * there is no point in displaying them after successful encryption.
 */
if (isset($chdir_first)) if ($chdir_first) {
    chdir ('../');
};
if ($gpg_export) {
    $addbasepath = '../';
} else {
    $addbasepath = '';
}
$urlbasepath=urlencode($addbasepath);
if ($urlbasepath) { $urlbasepath = urlencode($addbasepath . '../src/'); }
//add the "Encrypt Now" button
     if ((file_exists($pubring)) and (filesize($pubring)>0)){
         if (!$no_encrypt_on_setup) {
          echo "<br>"; //put the GPG controls on a new line
      if (($expose_encrypt_now_button=='true') && ($force_encrypt_send!='true')) {
            echo <<<TILLEND
<script type='text/javascript'>
<!--

TILLEND;
echo 'document.write("<input type=\'submit\' name=\'encrypt\' value=\'' . addslashes(_("Encrypt Now")) . '\' onclick=\"this.form.action=\'' . $addbasepath . '../plugins/gpg/gpg_encrypt.php\'\"> ");';
echo <<<TILLEND
//-->
</script>
TILLEND;
          }
         }; //end no_encrypt_on_setup else clause
//if gpg_export is set, we also need to change the target
        if ($gpg_export) {
            echo <<<TILLEND
        <script type='text/javascript'>
        <!--
        document.compose.action= '../../src/compose.php';
        //-->
        </script>
TILLEND;
         }; //end gpg_export clause

    }; //end Encrypt button setup

// add the "GPG Sign" Buttons
    if ((file_exists($secring)) and
        (filesize($secring)>0) and
        ($GLOBALS['GPG_SYSTEM_OPTIONS']['allowprivatekeys']=='true') and
        ($use_signing_key_id=='true')){
        if (!$no_encrypt_on_setup) {
            echo <<<TILLEND
<script type='text/javascript'>
<!--
var sendClicked=false;
var draftClicked=false;
function gpg_encrsign(objform){
    document.forms[0].gpgsign.value='true';
    objform.form.action='$addbasepath../plugins/gpg/gpg_encrypt.php';
   if (objform.form.passphrase.value=='true') {
    objform.form.submit();
   } else {
        window.open('$addbasepath../plugins/gpg/gpg_pop_init.php?MOD=passpop&psaction=encrsign&addbasepath=$urlbasepath','Secure_GPG_Signing','status=yes,width=300,height=200,resizable=yes');
  }
}
function gpg_sendClick() {
    sendClicked=true;
}
function gpg_draftClick() {
   draftClicked=true;
}
function gpg_composeSubmit(objform) {
   if (objform.passphrase.value!='true') {
    if (objform.sign_on_send.checked && sendClicked) {
        window.open('$addbasepath../plugins/gpg/gpg_pop_init.php?MOD=passpop&psaction=sign&addbasepath=$urlbasepath','Secure_GPG_Signing','status=yes,width=300,height=200,resizable=yes');
        return false;
    } else {
    if (objform.sign_on_send.checked && draftClicked) {
        window.open('$addbasepath../plugins/gpg/gpg_pop_init.php?MOD=passpop&psaction=signdraft&addbasepath=$urlbasepath','Secure_GPG_Signing','status=yes,width=300,height=200,resizable=yes');
        return false;
    }
    }
   }
   return true;
}
document.compose.send.onclick=gpg_sendClick;
document.compose.draft.onclick=gpg_draftClick;
TILLEND;

if (($expose_encrypt_sign_now_button=='true') && ($force_sign_on_send!='true')) {
    echo "\ndocument.write(\"<input type='button' name='gpgs' value='";
    echo addslashes(_("Encrypt&Sign Now")) . '\' onclick=\'gpg_encrsign(this)\'> ");';
} else { echo "\ndocument.write(\"<br>\");\n"; }
if ($force_sign_on_send == 'true') {
    echo "\ndocument.write(\"<input type=checkbox $aschecked onchange='this.checked=true' name=sign_on_send value='sign'>" . addslashes(_("Always Sign on Send")) . '&nbsp;");';
} else {
    echo "\ndocument.write(\"<input type=checkbox $aschecked name=sign_on_send value='sign'>" . addslashes(_("Sign on Send")) . '&nbsp;");';
}
echo <<<TILLEND

//-->
</script>
<input type=hidden name=gpgsign value=''>
<input type=hidden name=passphrase value='$passphrase_cached'>

TILLEND;
        }; //end no_encrypt_on_setup
     }; //end GPG Sign and Encrypt&Sign setup
    if ((file_exists($pubring)) and (filesize($pubring)>0)){

    if (!$no_encrypt_on_setup) {
        if ($force_encrypt_on_send == 'true') {
            echo "<script type='text/javascript'>\n<!--\n" . "document.write(\"<input type=checkbox $aechecked name=encrypt_on_send onchange='this.checked=true' value='encrypt'>";
            $strEncryptOnSend = addslashes(_("Always Encrypt on Send"));
        } else {
            echo "<script type='text/javascript'>\n<!--\n" . "document.write(\"<input type=checkbox $aechecked name=encrypt_on_send value='encrypt'>";
            $strEncryptOnSend = addslashes(_("Encrypt on Send"));
        }
        echo $strEncryptOnSend . "&nbsp;\");\n//-->\n</script>";
    } //end no_encrypt_on_setup check
    } // end pubring existence check
    bindtextdomain('squirrelmail', SM_PATH . 'locale');
    textdomain('squirrelmail');
  }; //end soupNazi check
}; //end compose_row_do function


/*********************************************************************/
/**
 *
 * $Log: gpg_hook_functions.php,v $
 * Revision 1.88  2007/07/07 20:02:53  brian
 * - remove call time pass by reference
 *
 * Revision 1.87  2006/01/08 02:47:20  ke
 * - committed patch from Evan <umul@riseup.net> for OpenPGP header support in squirrelmail
 * - adds system preferences and user options to control parsing and adding of OpenPGP Headers on emails
 * - slightly tweaked to use the key associated with the identity, when identities with signing keys are enabled
 *
 * Revision 1.86  2005/10/09 17:05:19  jangliss
 * Fixed bad call to setPref.  Order doesn't match getPref.
 *
 * Revision 1.85  2005/10/09 07:10:44  ke
 * - added new hook functions to handle squirrelmail identity/GPG key link
 * - thanks to Valcor (Jonathon Angliss) for this patch
 *
 * Revision 1.84  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.83  2005/07/27 13:51:32  brian
 * - remove all code to handle SM versions older than SM 1.4.0
 * Bug 262
 *
 * Revision 1.82  2004/08/23 07:35:37  ke
 * -applying patch to hide Signature: line for messages which contain no PGP data (thanks to Brad
 * Donison)
 * Bug 182
 *
 * Revision 1.81  2004/08/23 06:53:38  ke
 * -changed language for forced options from Mandatory to Always
 * -make sure not to show buttons for encrypt/sign when mandatory flags are on
 * bug 83
 *
 * Revision 1.80  2004/08/16 13:44:29  joelm
 * -added two config options to allow a sys admin to force users to always sign
 * or encrypt email
 * Bug 83
 *
 * Revision 1.79  2004/07/07 18:59:26  ke
 * -changed signature verification code to use GnuPG object
 * -added automatic checking of the Encrypt on Send checkbox when replying from the decrypion screen
 *
 * Revision 1.78  2004/06/22 21:58:34  ke
 * -added option check to show encrypt and encrypt&sign buttons
 * -added option check to show gpg options link
 *
 * Revision 1.77  2004/04/30 17:55:22  ke
 * -removed newline at end of file
 *
 * Revision 1.76  2004/03/21 21:10:00  joelm
 * Bug 138
 * - added a "Verify Signature" option to check the signature of encrypted attachments
 *
 * Revision 1.75  2004/03/10 21:40:57  brian
 * - removed trailing whitespace
 *
 * Revision 1.74  2004/03/09 21:42:55  ke
 * -added error output on decryption failure
 * bug 166
 *
 * Revision 1.73  2004/02/24 09:33:21  brian
 * - refined SoupNazi Javascript check in option block display
 *   - added warning if SoupNazi thinks that JS is off or incompatible
 * - refined soupNazi check for compose row display
 * - credit to pdontthink and andrew bolander and dirk for assistance
 * Bug 110
 *
 * Revision 1.72  2004/02/17 22:40:13  ke
 * -added state to on send checkboxes, so adding attachments won't clear them
 * -proc_open additions
 * bug 29
 *
 * Revision 1.71  2004/02/10 19:34:21  ke
 * -added addslashes to text within document.write in order to avoid any breakage of javascript
 *
 * Revision 1.70  2004/01/17 23:18:05  brian
 * initialize import_body to eliminate an E_ALL error.
 *
 * Revision 1.69  2004/01/17 00:25:38  ke
 * -E_ALL fixes
 * bug 146
 *
 * Revision 1.68  2004/01/15 17:38:21  ke
 * -added -n to echo statement for pgp signed message signature verification
 * -fixes pgp-mime signature confirmation
 *
 * Revision 1.67  2004/01/15 00:22:43  ke
 * -changed to use centralized gpg_execute function
 * -removed gpg_parse_output calls, as that is also centralized
 * -added debug output to pgp_mime check signature code (still somewhat broken)
 *
 * Revision 1.66  2004/01/09 22:09:40  ke
 * -removed leading / in relocation path on compose_send error
 * -this solves problem of redirection to plugin.com when squirrelmail is in the base directory
 * bug 114
 *
 * Revision 1.65  2004/01/09 20:38:47  ke
 * -added debug output for sign on send
 * -added check for existence of cyphertext after signing, error if nonexistant
 *
 * Revision 1.64  2004/01/09 18:26:50  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.63  2004/01/09 17:21:44  brian
 * - moved call by reference to gpg_check_sign_pgp_mime function def
 * - fixes allow_call_time_pass_reference warning
 *
 * Revision 1.62  2003/12/19 21:18:05  ke
 * -added debug output to update_trustdb command
 *
 * Revision 1.61  2003/12/19 20:44:59  ke
 * -changed trustdb update code to use centralized gpg_update_trustdb function
 * -only update trustdb when untrusted keys are found
 *
 * Revision 1.60  2003/12/18 22:18:05  ke
 * -added a trustdb check before confirming a clearsigned signature
 *
 * Revision 1.59  2003/12/18 19:44:14  ke
 * -changed to no longer use data_dir in system keyring path
 *
 * Revision 1.58  2003/12/16 20:00:03  brian
 * changed instances of $system_keyring_file = $safe_data_dir to
 *    $system_keyring_file = $data_dir becasue $safe_data_dir is a
 *    hashed dir on a per-user basis
 *
 * Revision 1.57  2003/12/16 19:28:45  brian
 * fixed indents in the gpg_handle_signature_do fn
 *
 * Revision 1.56  2003/12/16 19:27:07  brian
 * fixed indents in the gpg_handle_signature_do fn
 *
 * Revision 1.55  2003/12/11 20:42:32  ke
 * -added $extra_cmd to the command string, to include the system keyring
 *
 * Revision 1.54  2003/12/11 19:56:17  brian
 * added shared corporate keyring support to plainsigned verification
 *
 * Revision 1.53  2003/12/08 21:10:36  ke
 * -added translation section to options block text
 *
 * Revision 1.52  2003/12/02 15:26:14  ke
 * -added check for definition of base_uri, reset to get_location . SM_PATH if not set
 *
 * Revision 1.51  2003/12/02 04:11:53  ke
 * -added error check on signature data and display error message if no verification can be done on an attachment
 *
 * Revision 1.50  2003/12/02 03:18:20  ke
 * -changed to check for a body in the message object before downloading the full imap body
 *
 * Revision 1.49  2003/12/02 02:59:13  ke
 * -added calls to gpg_parse_output for signature lines on signature attachments
 * -removed text stripping code which is already done in gpg_parse_output
 * -added htmlspecialchars call to see email address in <>'s
 *
 * Revision 1.48  2003/11/25 21:54:59  ke
 * -translated Signature field header in read_body hook
 * -changed check_sign function to use gpg_parse_output to trap errors
 * -added htmlspecialchars call to show email address in signature information
 * bug 113
 *
 * Revision 1.47  2003/11/25 02:17:30  ke
 * -added missing getHashedDir definition for safe_data_dir in compose_row function
 *
 * Revision 1.46  2003/11/25 02:04:02  ke
 * -added safe_data_dir to code accessing the keyring files
 * bug 116
 *
 * Revision 1.45  2003/11/20 17:36:07  ke
 * -removed text binding code at the end of decrypt_link function, to allow it to be called from elsewhere
 *
 * Revision 1.44  2003/11/18 19:44:33  ke
 * -removed autosetting locale to gpg when requiring gpg_hook_functions.php
 *
 * Revision 1.43  2003/11/18 19:33:21  ke
 * -removed auto-setting text domain to squirrelmail at end of file
 * -this allows gpg_hook_functions to be included in other places without breaking localization strings
 *
 * Revision 1.42  2003/11/17 19:24:41  ke
 * -added check for quoted-printable in the hook where the body is grabbed from the imap server
 * -added code to strip out encryped message in the body, store in a session variable
 * -changed window.open and form action to use SM_PATH
 * bug 105
 *
 * Revision 1.41  2003/11/13 19:34:31  ke
 * -added SM_PATH redirection to import key function to allow calling from anywhere
 * bug 105
 *
 * Revision 1.40  2003/11/13 18:30:11  ke
 * -added missing closed paren from my last commit
 *
 * Revision 1.39  2003/11/13 18:24:46  ke
 * -internationalized Decrypt Attachment statement
 * -added bindtextdomain calls to the attachment handlers
 *
 * Revision 1.38  2003/11/10 19:53:51  ke
 * -added bindtextdomain calls within hook functions, for translation purposes
 *
 * Revision 1.37  2003/11/07 22:09:14  ke
 * -added dlfilename being passed to decrypt_attachment php file
 *
 * Revision 1.36  2003/11/07 19:53:18  ke
 * -added encryption check for signature header
 *
 * Revision 1.35  2003/11/07 18:46:56  ke
 * -fixed bug in confirmation of attached signatures function
 * -added Signature: header for signed message output
 *
 * Revision 1.34  2003/11/07 16:42:50  ke
 * -added numerous debug statements in signing and downloading attachment code
 * -committing brian's SM_PATH additions in different hook functions
 * -now sets any text files being signed to be application/octet-stream for proper encoding
 *
 * Revision 1.33  2003/11/04 21:38:40  brian
 * change to use SM_PATH
 *
 * Revision 1.32  2003/11/03 18:41:32  ke
 * -added startMessage and sort variables submitted to gpgdecrypt
 *
 * Revision 1.31  2003/11/03 17:32:59  ke
 * -changed to use abstracted checking functions before retreiving cached passphrase
 * bug 66
 *
 * Revision 1.30  2003/11/01 22:01:26  brian
 * infrastructure changes to support removal of MakePage functions
 *
 * Revision 1.29  2003/10/30 19:40:20  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.28  2003/10/27 19:31:01  ke
 * -added urlpath to pass to popup, includes proper path to submit
 * bug 82
 *
 * Revision 1.27  2003/10/27 18:19:34  ke
 * -added functions to verify detached signatures
 * -added function to download attachment to a tempfile
 *
 * Revision 1.26  2003/10/20 22:54:05  ke
 * -added compose hook for sending detached signatures for attachments
 * -had to patch squirrelmail to handle these, hopefully to be committed soon
 *
 * Revision 1.25  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.24  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.23  2003/10/16 20:36:49  ke
 * -fixed bug when signing a message with an attachment
 * -no signing of attachments yet, but at least the body will be signed.
 *
 * Revision 1.22  2003/10/16 20:21:48  ke
 * -added variable addbasepath to smooth operation when errors cause compose.php to be re-included
 * -fixes encryption and signing buttons
 * -does NOT fix popups
 * bug 82
 *
 * Revision 1.21  2003/10/16 13:55:00  brian
 * improved function headers
 *
 * Revision 1.20  2003/10/15 19:22:14  ke
 * -added code to verify detached signatures using all contents between mime boundaries
 * bug 33
 *
 * Revision 1.19  2003/10/13 22:25:46  brian
 * - added checks for secring and allowprivatekeys to gpg_decrypt_link fn
 * - display errors if those test fail rather than the decrypt button.
 * Bug 85
 *
 * Revision 1.18  2003/10/11 21:33:38  ke
 * -Added javascript to allow Save Draft to prompt for passphrase
 * -Added gpg_strip_sign function to return body of signed text
 * -Use above function to show only body when returning to compose from a draft
 * bug 77
 *
 * Revision 1.17  2003/10/07 22:22:10  ke
 * -changed javascript in compose hooks to call consolidated passphrase interface
 * bug 13
 *
 * Revision 1.16  2003/10/07 18:34:35  ke
 * -Added error handling within sign on send code
 *
 * Revision 1.15  2003/10/07 17:10:39  brian
 * added calls to get next and prev message ids to gpg_decrypt_link fn
 * Bug 65
 *
 * Revision 1.14  2003/10/06 22:51:48  ke
 * -changed import_link function to import keys direction into keyring_main.php
 * -hides any keys found in the body in a hidden variable, posts directly to keyring_main
 *
 * Revision 1.13  2003/10/03 22:43:34  ke
 * -Removed set_cached_passphrase call from sign_on_send handling of compose_send hook
 * -Passphrase is cached in cachepass.mod before this is ever called
 * -All compose.php buttons work properly, no longer pop up passphrase request
 *
 * Revision 1.12  2003/10/01 23:09:42  brian
 * added support for reply from decrypt
 * - extra variables passed in gpg_decrypt_link fn
 * - gpg_dbody flag processed in gpg_compose_row fn
 * Bug 65
 *
 * Revision 1.11  2003/09/30 01:23:11  ke
 * -Added internationalization to all messages and buttons in gpg_hook_functions.
 * -Ignored the titles of popup windows for now, maybe we'll want to work on those later?
 * bug 35
 *
 * Revision 1.10  2003/09/29 16:15:01  brian
 * added check for system and user prefs for passphrase cachign to functions:
 * - gpg_decrypt_link
 * - gpg_compose_row_do
 * - gpg_compose_send_do
 * Bug 40
 *
 * Revision 1.9  2003/09/26 19:24:50  brian
 * adjust javascript to actually work
 * Bug 40
 *
 * Revision 1.8  2003/09/26 15:42:13  brian
 * Changes to gpg_decrypt_link fn to support caching:
 * - add a form and scripting to control action
 * Bug 40
 *
 * Revision 1.7  2003/09/26 02:31:56  ke
 * -Added gpg_functions.php requirement for caching
 * -Removed unneccessary javaascript from compose_row_do
 * -Changed operation of compose_row_do to use popups or submit depending on status of cached passphrase
 *
 * Revision 1.6  2003/09/23 22:10:38  ke
 * Changed redirection Location: header sent to use global $base_uri
 * This allows the redirection to operate properly when called from different locations.
 *
 * Revision 1.5  2003/09/20 01:33:33  brian
 * moved body_part 'stuffing to gpg_encrypt.php
 *
 * Revision 1.4  2003/09/18 22:14:32  ke
 * -Added javascript and interface changes for sign-on-send
 * -Moved encrypt-on-send checkbox to appear after signing widgets
 * -Completed sign-on-send, encrypt-on-send and combinations functionality
 * Bug 55
 *
 * Revision 1.3  2003/09/17 18:22:36  ke
 * -Altered functional calls for compose_send_do, ensured no name collision with message object
 * -part of the encrypt on send fix
 * Bug 53
 *
 * Revision 1.2  2003/09/16 00:03:28  ke
 * Added _do to end of hook function names, to allow wrapper functions to call them
 *
 * Revision 1.1  2003/09/15 23:18:11  ke
 * Newly created hook functions file
 * -Split to speed calls to setup.php
 *
 * Revision 1.50  2003/07/14 15:34:06  brian
 * changed main options block to title: Encryption Options (GPG/OpenPGP)
 *
 * Revision 1.49  2003/07/09 13:53:58  brian
 * added processing of $gpg_export flag to change compose page target
 * Bug 41
 *
 * Revision 1.48  2003/07/07 20:33:58  brian
 * - modified code to support import from email
 * - added gpg_import_link fn
 * Bug 46
 *
 * Revision 1.47  2003/07/02 22:45:42  brian
 * pulled out the 'Encrypt&Sign on Send' code entirely for now.
 *
 * Revision 1.46  2003/07/02 22:39:53  brian
 * moved closing coment bracket
 *
 * Revision 1.45  2003/07/02 22:36:54  brian
 * comment out encrypt&sign on send, since it doesn't work anyway
 *
 * Revision 1.44  2003/07/02 22:28:36  brian
 * fixed inadvertent bug that broke Encrypt&Sign
 *
 * Revision 1.43  2003/05/28 19:17:12  brian
 * modified to fix non-working encryptcapability (broken during work on encrypt&sign on send)
 *
 * Revision 1.42  2003/05/19 17:51:05  brian
 * added 'global $version' to compose_row fn
 *
 * Revision 1.41  2003/05/16 16:29:15  brian
 * added version check to disable encrypt on send
 * (in compose_body_row hook) in SM < 1.4.0
 *
 * Revision 1.40  2003/05/16 16:20:11  brian
 * added version check to disable encrypt on send (in compose_end hook) in SM < 1.4.0
 *
 * Revision 1.39  2003/05/16 13:44:39  brian
 * added tags for phpdoc
 *
 * Revision 1.38  2003/05/15 22:47:21  brian
 * - improved handlnig in compose_send
 * - added $debug flags
 * - added comments to explain use of globals
 *
 * Revision 1.37  2003/05/14 01:34:24  vinay
 * *** empty log message ***
 *
 * Revision 1.36  2003/05/13 22:52:23  brian
 * added compose_send hook functionality for encrypt on send
 * Bug 26
 *
 * Revision 1.35  2003/05/10 12:23:59  brian
 * added function header blocks to functions that were missing them
 *
 * Revision 1.34  2003/05/02 15:37:06  brian
 * checked in Ryan's code for detached signatures after minor mods
 * Bug 33
 *
 * Revision 1.33  2003/04/16 02:33:32  brian
 * added 'Encrypt&Sign' button
 * Bug 11
 *
 * Revision 1.32  2003/04/09 12:59:20  brian
 * single quote values in 'Encrypt Now' button
 *
 * Revision 1.31  2003/04/04 03:05:49  brian
 * added global $passed_id, $mailbox back into decrypt_link function -- needed for document.write
 *
 * Revision 1.30  2003/04/04 01:56:11  tyler
 * - added $uid_support to the sqimap_run_command query
 *
 * Revision 1.29  2003/04/04 00:09:48  brian
 * changed so that fetch_body function is called only once, so we only connect once to the imap stream
 *
 * Revision 1.28  2003/04/03 23:45:35  brian
 * changed check_sign and decrypt_link to use the standard SM core command of sqimap_run_command, rather that sqimap_read_data, becasue read_data didn't work the same under SM 1.4
 *
 * Revision 1.27  2003/04/03 02:32:22  brian
 * Decoupled signature verification and display of the decryption button.
 *
 * Revision 1.26  2003/04/02 12:25:42  brian
 * modified window.open function for decrypt to:
 * - make passphrase dialog smaller
 * - allow scrollbars
 *
 * Revision 1.25  2003/04/01 07:58:18  brian
 * corrected document .write link to work in SM 1.4
 *
 * Revision 1.24  2003/03/31 22:02:45  brian
 * modified _GET parameters for decrypt now button
 *
 * Revision 1.23  2003/03/31 21:57:31  brian
 * modified to add passed_value and mailbox to submit of decrypt now button
 *
 * Revision 1.22  2003/03/31 15:18:25  brian
 * modified gpg_decrypt_link function to use document,.write and window.open functions to create popup.
 *
 * Revision 1.21  2003/03/31 15:03:41  brian
 * - modified to remnove double declaration of gpg_check_sign
 * - file now correctly declares gpg_decrypt_link
 *
 * Revision 1.20  2003/03/31 14:57:38  brian
 * - modified signing link to use new gpg_pop_init.php file
 * - added link for decrypt now
 * - placed signing and decryption functions under read_body_header
 *   initialization function
 * Bug 8
 *
 * Revision 1.19  2003/03/25 21:43:23  brian
 * Bug 6
 * Slightly better handling of whether to display the buttons or not after encrypt.
 *
 * Revision 1.18  2003/03/17 18:55:41  brian
 * - progress towards SM v >=1.3.1 compatibility
 * - path selection for includes now works on both
 *   SM 1.2.x and SM >= 1.3.1
 *
 * Revision 1.17  2003/03/15 22:03:32  brian
 * moved strings.php include to outside of the SM version check
 * strings.php sets the SM version...
 *
 * Revision 1.16  2003/03/13 04:04:16  brian
 * modified GPG Sign button calling code.
 *
 * Revision 1.15  2003/03/12 15:00:16  brian
 * - added document action to change the document action after encrypting an email
 * - @todo make sure that it works even after unsuccessful submit
 * - @todo make syntax cross browser compatible
 *
 * Revision 1.14  2003/03/12 14:34:40  brian
 * - added function header comment blocks to all functions
 *
 * Revision 1.13  2003/03/12 05:02:58  tyler
 * - reduced the size of the message_sign popup window
 *
 * Revision 1.12  2003/03/12 01:43:58  tyler
 * - test for secring file now checks for zero length file
 *
 * Revision 1.11  2003/03/12 01:36:28  tyler
 * - Initial attempt at signature verification on read. New hook added.
 *
 * Revision 1.10  2003/03/11 21:25:18  tyler
 * - helps if you define $privatekeysallowed otherwise it's always 0 :)
 *
 * Revision 1.9  2003/03/11 19:22:08  tyler
 * - Modified to use $allowprivatekeys preference to decide if Sign button should be displayed
 *
 * Revision 1.8  2003/03/11 02:45:24  tyler
 * - modified code to only exclude encrypt now button after encryption routine
 *
 * Revision 1.7  2003/03/11 01:29:51  brian
 * fixed bug with pressing button twice by not showing buttons after the encryption routine has been run correctly
 *
 * Revision 1.6  2003/03/11 01:06:48  tyler
 * - renamed filename variable to pubring
 * - added secring variable
 * - rewrote the button building code to use document.write so folks with
 *   javascript turned off wont catch errors, buttons just wont get displayed
 * - rewrote the button building code to only display if ring file available
 * - converted 'Sign Now' button to 'GPG Sign' so as not to be confused with
 *   other "sign" buttons
 * - converted 'Encrypt Now' button to 'GPG Encrypt'
 * - converted code called by 'Sign Now' to pop a window
 *
 * Revision 1.5  2003/03/09 14:35:54  brian
 * Added Tyler's "Sign Now" button
 * todo - only show button if user has a secret key
 *
 * Revision 1.4  2003/03/06 23:42:48  brian
 * Added check for SM ver > 1.3
 *
 * Revision 1.3  2003/02/26 17:07:55  brian
 * Added check so that the Encrypt Now button will only be displayed if the user has a keyring directory.
 *
 * Revision 1.2  2002/12/05 19:25:46  brian
 * Added ID and Log tags
 *
 *
 */

?>
