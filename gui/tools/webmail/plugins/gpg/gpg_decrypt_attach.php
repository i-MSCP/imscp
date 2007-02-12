<?php
/**
 * gpgdecrypt_attach.php
 * -----------
 * This file will be called by the mime attachment handler to
 * decrypt the attachment file and force the download.
 *
 * Copyright (c) 2003 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @todo change the file includes to set SM_PATH
 * @todo modify to work without passphrase caching
 *
 * $Id: gpg_decrypt_attach.php,v 1.12 2003/12/18 19:43:34 ke Exp $
 */
/*********************************************************************/

if (!defined (SM_PATH)){
    if (file_exists('./gpg_functions.php')){
        define (SM_PATH , '../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')) {
        define (SM_PATH, '../');
    } elseif (file_exists('../gpg_functions.php')){
        define (SM_PATH , '../../../');
    } elseif (file_exists('../../plugins/gpg/gpg_functions.php')){
        define (SM_PATH , '../../../../');
    } else echo "unable to define SM_PATH in GPG Plugin gpg_decrypt_attach.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/gpg_config.php');
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_encrypt_functions.php');

if (!function_exists('SendDownloadHeaders')) {
    // define the function SendDownloadHeaders
    // this function exists in newer SM CVS, but not in released SM 1.4.2

    /**
     * function SendDownloadHeaders - send file to the browser
     *
     * Original Source: SM core src/download.php
     * moved here to make it available to other code, and separate
     * front end from back end functionality.
     *
     * @todo  Remove this copied function at some point after SM releases a newer release
     *
     * @param string $type0 first half of mime type
     * @param string $type1 second half of mime type
     * @param string $filename filename to tell the browser for downloaded file
     * @param boolean $force whether to force the download dialog to pop
     * @return void
     */
     function SendDownloadHeaders($type0, $type1, $filename, $force, $filesize=0) {
         global $languages, $squirrelmail_language;
         $isIE = $isIE6 = 0;

         sqgetGlobalVar('HTTP_USER_AGENT', $HTTP_USER_AGENT, SQ_SERVER);

         if (strstr($HTTP_USER_AGENT, 'compatible; MSIE ') !== false &&
             strstr($HTTP_USER_AGENT, 'Opera') === false) {
             $isIE = 1;
         }

         if (strstr($HTTP_USER_AGENT, 'compatible; MSIE 6') !== false &&
             strstr($HTTP_USER_AGENT, 'Opera') === false) {
             $isIE6 = 1;
         }

         if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
             function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
             $filename =
                 $languages[$squirrelmail_language]['XTRA_CODE']('downloadfilename', $filename, $HTTP_USER_AGENT);
         } else {
            $filename = ereg_replace('[\\/:\*\?"<>\|;]', '_', str_replace('&nbsp;', ' ', $filename));
         }

         // A Pox on Microsoft and it's Office!
         if (!$force) {
             // Try to show in browser window
             header("Content-Disposition: inline; filename=\"$filename\"");
             header("Content-Type: $type0/$type1; name=\"$filename\"");
         } else {
             // Try to pop up the "save as" box
             // IE makes this hard.  It pops up 2 save boxes, or none.
             // http://support.microsoft.com/support/kb/articles/Q238/5/88.ASP
             // But, according to Microsoft, it is "RFC compliant but doesn't
             // take into account some deviations that allowed within the
             // specification."  Doesn't that mean RFC non-compliant?
             // http://support.microsoft.com/support/kb/articles/Q258/4/52.ASP
             //
             // The best thing you can do for IE is to upgrade to the latest
             // version
             if ($isIE && !$isIE6) {
                 // http://support.microsoft.com/support/kb/articles/Q182/3/15.asp
                 // Do not have quotes around filename, but that applied to
                 // "attachment"... does it apply to inline too?
                 //
                 // This combination seems to work mostly.  IE 5.5 SP 1 has
                 // known issues (see the Microsoft Knowledge Base)
                 header("Content-Disposition: inline; filename=$filename");
                 // This works for most types, but doesn't work with Word files
                 header("Content-Type: application/download; name=\"$filename\"");

                 // These are spares, just in case.  :-)
                 //header("Content-Type: $type0/$type1; name=\"$filename\"");
                 //header("Content-Type: application/x-msdownload; name=\"$filename\"");
                 //header("Content-Type: application/octet-stream; name=\"$filename\"");
             } else {
                 header("Content-Disposition: attachment; filename=\"$filename\"");
                 // application/octet-stream forces download for Netscape
                 header("Content-Type: application/octet-stream; name=\"$filename\"");
             }
         }
    if ($filesize > 0) {
        header("Content-Length: $filesize");
    }
     } // end fn SendDownloadHeaders

} // end is_defined check

function download_entity($imap_stream, $mailbox, $id, $ent_id, $filename='') {
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
        if ($encoding == 'base64') {
               $body = base64_decode($body);
        }
        if ($filename) { $tempfile = $filename . '.asc'; }
        else { $tempfile = getTempFile(); }
        if (is_file($tempfile)) {
                unlink($tempfile);
        }
        $fhandle = fopen($tempfile, "wb");
        if (!fwrite($fhandle, $body, strlen($body))) {
                $return['errors'][] = _("Could not write to temporary file: ") .  $tempfile;
        } else {
                fclose($fhandle);
                $return['filename'] = $tempfile;
        }
        return $return;
}

function scandir($dirstr) {
    // php.net/scandir (PHP5)
    $files = array();
    $fh = opendir($dirstr);
    while (false !== ($filename = readdir($fh))) {
        array_push($files, $filename);
    }
    closedir($fh);
    return $files;
}

/**
 * Basic format
 *
 * get attachment from $message
 * get $password from session
 *
 * gettempdir
 * gettempfilename
 * gpg_decrypt ($debug, $passphrase, $filename)
 * go get the decrypted attachment from the tempdir
 * force the download
 */
require_once(SM_PATH . 'functions/global.php');

global $imapConnection;
global $gpg_key_dir;
global $data_dir;
global $safe_data_dir;
global $username;
$safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
$gpg_key_dir = realpath($gpg_key_dir);
if (isset($_POST['passed_id'])) {
    $dlfilename = $_POST['dlfilename'];
    $passed_id      = (int) $_POST['passed_id'];
    $passed_ent_id  = (int) $_POST['passed_ent_id'];
    $mailbox    = $_POST['mailbox'];
    $passphrase = $_POST['passphrase'];
} else {
    $dlfilename       = urldecode($_GET['dlfilename']);
    $passed_id      = (int) $_GET['passed_id'];
    $passed_ent_id  = (int) $_GET['passed_ent_id'];
    $mailbox    = $_GET['mailbox'];
    $passphrase = '';
}
$username       = $_SESSION['username'];

$key = $_COOKIE['key'];

$cache_passphrase   = getPref ($data_dir, $username, 'cache_passphrase');
$allowpassphrasecaching   = $GPG_SYSTEM_OPTIONS['allowpassphrasecaching'];
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

$tempdir = createTempDir(false);
$tempfile = getTempFile('GPGPlugin', false, $tempdir);
if ($debug) { echo "Tempfile created as $tempfile<br>\n"; }

$return = download_entity($imapConnection,$mailbox,$passed_id,$passed_ent_id, $tempfile);
if (!$return['filename']) {
    $notclean=1;
    $serious=1;
    if ($debug) { echo "Serious Error<pre>\n"; print_r($return); echo "\n</pre>"; }
} else {
    $filename = $return['filename'];
}
if ($debug) { echo "Checking caching\n"; }
if (gpg_is_passphrase_cached($debug)) {
    if ($debug) { echo "Grabbing passphrase\n"; }
    $passphrase=gpg_get_cached_passphrase();
} else {
    // where should we put the passphrase if we aren't using caching?
   if ($passphrase == '') {
    if ($debug) {
       echo "No passphrase found.<br>";
    }
    echo "<form name=decrypt method=POST>";
    echo "<input type=hidden name=passed_id value='$passed_id'>";
    echo "<input type=hidden name=mailbox value='$mailbox'>";
    echo "<input type=hidden name=passed_ent_id value='$passed_ent_id'>";
    echo "<input type=hidden name=dlfilename value='$dlfilename'>";
    echo _("Enter Passphrase") . " <input type=password name=passphrase><br>";
    echo "<input type=submit>";
        exit();
   }
}
if ($debug) { echo "About to decrypt $filename\n"; }
$oldir = getcwd();
chdir($tempdir);
$return = gpg_decrypt($debug, '', $passphrase, $filename);
$tempfile = substr($filename,0,strpos($filename,'.asc'));
if ($debug) { echo $tempfile; }
if (is_file($tempfile)) {
    unlink($tempfile);
}

if ($debug) { echo "<pre>\n"; print_r($return); echo "\n</pre>"; }
if ($return['errors']) {
    $err = $return['errors'];
    chdir($oldir);
    include(SM_PATH.'plugins/gpg/modules/gpg_err.php');
    exit();
}
if ($return['plaintext']) {
//  $debug=1;
    if (is_array($return['plaintext'])) {
    if ($debug) { echo "Imploding plaintext array<br>\n"; }
        $plaintext = implode('',$return['plaintext']);
    } else {
    $plaintext = $return['plaintext'];
    }
//  $plaintext = trim(str_replace(array("\r\n\t", "\r\n "),array('', ''), $return['plaintext']));
    $plaintext = explode("\n",$plaintext);
//    if ($debug) { echo "Plaintext returned, parsing $plaintext"; }
    $startfile=false;
    $filecontents='';
    foreach ($plaintext as $line) {
        if (strlen($line)==0) {
            $startfile=true;
            if ($debug) { echo "Starting File<pre>\n"; }
            continue;
        }
        if ($startfile) {
            $filecontents.=$line . "\n";
            if ($debug) { echo "$line\n"; }
            continue;
        }
        $pos = strpos($line, ':');
        if ($pos>0) {
            $field = substr($line, 0, $pos);
            if (!strstr($field,' ')) { /* valid field */
                $value = trim(substr($line, $pos+1));
                $encheaders[$field]=$value;
                if ($debug) { echo "$field is $value<br>"; }
            }
        }
        $pos = strpos($line,'=');
        if ($pos>0) {
            $field = substr($line, 0, $pos);
            $field=trim($field);
            if ($field) { /* valid field */
                $value = trim(substr($line, $pos+1));
                $encheaders[$field]=$value;
                if ($debug) { echo "$field is equal to $value<br>"; }
            }
        }
    } if ($debug) {echo "\n</pre>"; }
    if ($filecontents == '') { $filecontents=$return['plaintext']; }
    if (!$encheaders['filename']) {
    $filecontents=$return['plaintext'];
    if ($dlfilename != '') {
        $pos = strrpos($dlfilename,'.asc');
        if ($pos!==false) {
            $dlfilename=substr($dlfilename,0,$pos);
        }
        $return=gpg_decrypt($debug, '', $passphrase, $filename, $dlfilename);
        $tempfile = substr($filename,0,strpos($filename,'.asc'));
            if ($debug) { echo $tempfile; }
            if (is_file($tempfile)) {
                unlink($tempfile);
            }
        if ($debug) { print_r($return); }
    } else {
        $err[] = _("Error: No filename found to save attachment");
        chdir($oldir);
        include(SM_PATH . "plugins/gpg/modules/gpg_err.php");
        exit();
    }
    }
    if ($encheaders['filename'] && $encheaders['Content-Type']) {
        if ($debug) {
            echo "Saving file " . $encheaders['filename'] . ' as type ' . $encheaders['Content-Type'];
            echo " after with encoding " . $encheaders['Content-Transfer-Encoding'];
        }
        if ($encheaders['Content-Transfer-Encoding'] == 'base64') {
            $filecontents = base64_decode($filecontents);
        }
        if ($encheaders['Content-Transfer-Encoding'] == 'quoted-printable') {
            $filecontents = quoted_printable_decode($filecontents);
        }
        $encheaders['filename']=str_replace('"','',$encheaders['filename']);
        $outfile=$tempdir . '/' . $encheaders['filename'];
        $outhandle = fopen($outfile,"wb");
            if (!fwrite($outhandle, $filecontents, strlen($filecontents))) {
            $return['errors'][] = _("Could not write to temporary file: ") .  $outfile;
            } else {
                    fclose($outhandle);
            $tempfile = substr($filename,0,strpos($filename,'.asc'));
            if ($debug) { echo $tempfile; }
            if (is_file($tempfile)) {
                unlink($tempfile);
            }
            }
    } else { if ($debug) { echo "err: <pre>\n"; print_r($encheaders); echo "\n</pre>";} $serious=1;}
}
foreach ($return['warnings'] as $warning) {
    $notclean=1;
    //$msg .= '<br>'._('Warning: '). htmlspecialchars($warning);
};
foreach ($return['errors'] as $error) {
    $notclean=1;
    $serious=1;
    //$msg .= '<br>'._('Error: '). htmlspecialchars($error);
};
foreach ($return['info'] as $info){
    //$msg .= '<br>'._('Info: '). htmlspecialchars($info);
};
$decryptedfile = '';
$return = scandir($tempdir);
if ($debug) { echo "Directory Contents<br><pre>\n"; print_r($return); echo "\n</pre>"; }
while (($decryptedfile == '') or ($decryptedfile == basename($filename)) or ($decryptedfile == '.') or ($decryptedfile == '..')) {
    $decryptedfile=array_pop($return);
}
if ($debug) { echo "Decrypted Filename: $decryptedfile vs. $filename<p>"; }
$old_error_reporting_level = error_reporting(E_ERROR | E_PARSE);
$filesize = filesize("$tempdir/$decryptedfile");
if (!$debug) {
    SendDownloadHeaders('application','octet-stream',$decryptedfile,true,$filesize);
} else { echo "<pre>FILE:\n";}
@readfile("$tempdir/$decryptedfile");
if ($debug) { echo "\n</pre>"; }
deleteAtShutdown($tempdir);

exit;
// if ($serious ==1 ) it didn't work
// now go look in the $tempdir for a new file that isn't a
// .asc or .pgp or .dat file or the $tempfile

//force the download
// SendDownloadHeaders blah blah blah

/*********************************************************************/
/**
 * $Log: gpg_decrypt_attach.php,v $
 * Revision 1.12  2003/12/18 19:43:34  ke
 * -removed commented debug
 *
 * Revision 1.11  2003/12/05 12:19:17  brian
 * added todo item to remove SendDownloadHeader fn after SM release
 *
 * Revision 1.10  2003/11/25 20:03:09  ke
 * -moved inclusion of files to before definition of functions
 *
 * Revision 1.9  2003/11/25 01:52:42  ke
 * -added DIRECTORY_SEPARATOR to the end of safe_data_dir for use in paths
 * -changed getPref to use data_dir instead of safe_data_dir
 *
 * Revision 1.8  2003/11/24 20:02:37  ke
 * -changed to use safe_data_dir
 * bug 116
 *
 * Revision 1.7  2003/11/11 22:43:00  ke
 * -added unlinking code for temporary download, after decryption is complete
 *
 * Revision 1.6  2003/11/11 17:36:50  ke
 * -added removal of temporary file before redecrypting to an output file
 *
 * Revision 1.5  2003/11/07 22:06:27  ke
 * -added content-length for downloaded files
 * -added ability to decrypt files from outlook
 *
 * Revision 1.4  2003/11/06 23:29:54  ke
 * -removed chdir which was causing SM_PATH to be incorrect
 * -committing brian's added SM_PATH possibilities
 * -added basic error handling by including gpg_err.php on errors
 *
 * Revision 1.3  2003/11/05 21:20:47  ke
 * -readded fixes for downloading attachments properly
 * -added ability to query for passphrase when not cached
 *
 * Revision 1.2  2003/11/04 21:38:40  brian
 * change to use SM_PATH
 *
 * Revision 1.1  2003/10/30 19:43:57  brian
 * Initial Revision
 *
 */
?>
