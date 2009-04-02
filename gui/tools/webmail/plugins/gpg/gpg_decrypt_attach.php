<?php
/**
 * gpgdecrypt_attach.php
 * -----------
 * This file will be called by the mime attachment handler to
 * do one of two things:
 * 1. decrypt the attachment file and force the download.
 * 2. verify the signature of an encrypted attachment
 *
 * Copyright (c) 2003-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @todo change the file includes to set SM_PATH
 * @todo modify to work without passphrase caching
 *
 * $Id$
 */
/*********************************************************************/

if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , realpath('../../').'/');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')) {
        define ('SM_PATH', realpath('../').'/');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , realpath('../../../').'/');
    } elseif (file_exists('../../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , realpath('../../../../').'/');
    } else echo "unable to define SM_PATH in GPG Plugin gpg_decrypt_attach.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/gpg_config.php');
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_encrypt_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_execute.php');
//require_once(SM_PATH.'plugins/gpg/gpg.php');
global $debug;
$debug=$GLOBALS['GPG_SYSTEM_OPTIONS']['debug'];

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
 * @param optional integer $filesize send the Content-Header and length to the browser
     * @return void
     */
 function SendDownloadHeaders($type0, $type1, $filename, $force, $filesize=0) {
         global $languages, $squirrelmail_language;
         $isIE = $isIE6 = 0;

         sqgetGlobalVar('HTTP_USER_AGENT', $HTTP_USER_AGENT, SQ_SERVER);

         if (strstr($HTTP_USER_AGENT, 'compatible; MSIE ') !== false &&
             strstr($HTTP_USER_AGENT, 'Opera') === false) {
             $isIE = 1;
             // $force=1;
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

     // A Pox on Microsoft and it's Internet Explorer!
     //
     // IE has lots of bugs with file downloads.
     // It also has problems with SSL.  Both of these cause problems
     // for us in this function.
     //
     // See this article on Cache Control headers and SSL
     // http://support.microsoft.com/default.aspx?scid=kb;en-us;323308
     //
     // The best thing you can do for IE is to upgrade to the latest
     // version
     // set all the Cache Control Headers for IE
     if ($isIE) { // && !$isIE6) {
         header ("Pragma: public");
         header ("Cache-Control: no-store, max-age=0, no-cache, must-revalidate"); # HTTP/1.1
         header ("Cache-Control: post-check=0, pre-check=0", false);
         header ("Cache-control: private");

         //set the inline header for IE, we'll add the attachment header later if we need it
         header ("Content-Disposition: inline; filename=$filename");
     }

     if (!$force) {
         // Try to show in browser window
         header ("Content-Disposition: inline; filename=\"$filename\"");
         header ("Content-Type: $type0/$type1; name=\"$filename\"");
     } else {
         // Try to pop up the "save as" box

         // IE makes this hard.  It pops up 2 save boxes, or none.
         // http://support.microsoft.com/support/kb/articles/Q238/5/88.ASP
         // http://support.microsoft.com/default.aspx?scid=kb;EN-US;260519
         // But, according to Microsoft, it is "RFC compliant but doesn't
         // take into account some deviations that allowed within the
         // specification."  Doesn't that mean RFC non-compliant?
         // http://support.microsoft.com/support/kb/articles/Q258/4/52.ASP

         // all browsers need the application/octet-stream header for this
         header ("Content-Type: application/octet-stream; name=\"$filename\"");

         // http://support.microsoft.com/support/kb/articles/Q182/3/15.asp
         // Do not have quotes around filename, but that applied to
         // "attachment"... does it apply to inline too?
         header ("Content-Disposition: attachment; filename=\"$filename\"");

         if ($isIE && !$isIE6) {
             // This combination seems to work mostly.  IE 5.5 SP 1 has
             // known issues (see the Microsoft Knowledge Base)

             // This works for most types, but doesn't work with Word files
             header ("Content-Type: application/download; name=\"$filename\"");

             // These are spares, just in case.  :-)
             //header("Content-Type: $type0/$type1; name=\"$filename\"");
             //header("Content-Type: application/x-msdownload; name=\"$filename\"");
             //header("Content-Type: application/octet-stream; name=\"$filename\"");
         } else {
             // another application/octet-stream forces download for Netscape
             header ("Content-Type: application/octet-stream; name=\"$filename\"");
         }
     }

     //send the content-length header if the calling function provides it
     if ($filesize > 0) {
         header("Content-Length: $filesize");
     }

 }  // end fn SendDownlaodHeaders

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
 * if this is a download
 *   go get the decrypted attachment from the tempdir
 *   force the download
 * if this is a signature verification
 *   display the signature info for this attachment
 */
require_once(SM_PATH . 'functions/global.php');

global $imapConnection;
global $gpg_key_dir;
global $data_dir;
global $safe_data_dir;
global $username;

ob_start();
$safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
$safe_data_dir=realpath($safe_data_dir);
$gpg_key_dir = realpath($gpg_key_dir);
if (isset($_POST['passed_id'])) {
    $dlfilename = $_POST['dlfilename'];
    $passed_id      = (int) $_POST['passed_id'];
    $passed_ent_id  = (int) $_POST['passed_ent_id'];
    $mailbox    = $_POST['mailbox'];
    $verifysig  = (int)$_POST['verifysig'];
    $passphrase = $_POST['passphrase'];
} else {
    $dlfilename       = urldecode($_GET['dlfilename']);
    $passed_id      = (int) $_GET['passed_id'];
    $passed_ent_id  = (int) $_GET['passed_ent_id'];
    $mailbox    = $_GET['mailbox'];
    $verifysig  = (int)$_GET['verifysig'];
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
    echo "<input type=hidden name=verifysig value='$verifysig'>";
    echo _("Enter Passphrase") . " <input type=password name=passphrase><br>";
    echo "<input type=submit>";
        exit();
   }
}
if ($debug) { echo "About to decrypt $filename\n"; }
$oldir = getcwd();
chdir($tempdir);
if ($debug) { echo "Changing dir $tempdir\n"; }
$return = gpg_decrypt($debug, NULL, $passphrase, $filename, NULL, $safe_data_dir);
$tempfile = substr($filename,0,strpos($filename,'.asc'));
if ($debug) { echo "Tempfile: $tempfile"; }
if (is_file($tempfile)) {
    unlink($tempfile);
}

if ($debug) { echo "<pre>\n"; print_r($return); echo "\n</pre>"; }

//Do this before exiting on errors so that if the error is that the public key
//was not found for this signature then we can let the user know what key 
//created this signature
if ($verifysig == 1) { //the user wants to verify the attachment signature
    displayPageHeader($color, 'None');
    /**
     * set the localization variables
     * Now tell gettext where the locale directory for your plugin is
     * this is in relation to the src/ directory
     */
    bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
    /* Switch to your plugin domain so your messages get translated */
    textdomain('gpg');

    echo "<BR><TABLE WIDTH=\"100%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
     "<B><CENTER>".
     _("Viewing signature verification output for attachment: ") . $dlfilename;
    echo "</center></b></td></tr></table><br>\n";

    if (is_array($return['warnings'])) {
        if (count($return['warnings']) > 0) {
            echo "<br><b>" . _("Warnings:") . "</b><ul>\n";
            foreach ($return['warnings'] as $warning) {
                echo htmlspecialchars($warning) . '<br>';
            };
            echo '</ul>';
        }
    }

    if (is_array($return['signature']) && count($return['signature']) > 0) {
        foreach ($return['signature'] as $line) {
            echo htmlspecialchars($line);
            print "<br>";
        }
    } elseif (is_array($return['errors']) && count($return['errors']) > 0) {
        echo _("The signature could not be verified due to errors.");
        echo "<br><b>" . _("Errors:") . "</b><ul>\n";
        foreach ($return['errors'] as $line) {
            echo htmlspecialchars($errors) . '<br>';
        }
        echo '</ul>';
    } else {
        echo _("This encrypted attachment is not signed.") . "\n<br>";
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



    /* Switch back to the SquirrelMail domain */
    bindtextdomain('squirrelmail', SM_PATH . 'locale');
    textdomain('squirrelmail');

}

if ($return['errors']) {
    $err = $return['errors'];
    chdir($oldir);
    include(SM_PATH.'plugins/gpg/modules/gpg_err.php');
    exit();
}

//if the user wants to download an attachment
if ($verifysig == 0 && $return['plaintext']) {
    if ($debug) { echo "Downloading decrypted attachment:"; }
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
    $encheaders = array();
    foreach ($plaintext as $line) {
        if (strlen($line)==0) {
            $startfile=true;
            if ($debug) { echo "Starting File<pre>\n<br>"; }
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
    if (!array_key_exists('filename',$encheaders)) {
    $filecontents=$return['plaintext'];
    if ($dlfilename != '') {
        $pos = strpos($dlfilename,'.asc');
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
    if (array_key_exists('filename',$encheaders) && array_key_exists('Content-Type',$encheaders)) {
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
};
foreach ($return['errors'] as $error) {
    $notclean=1;
    $serious=1;
};
foreach ($return['info'] as $info){
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
//only send download headers if we are not in debug mode and if this is
//not a verify attachment signature request 
if ($verifysig == 0 && !$debug) {
    ob_end_clean();
    SendDownloadHeaders('application','octet-stream',$decryptedfile,true,$filesize);
    @readfile("$tempdir/$decryptedfile");
} else {
    ob_end_flush(); 
    if ($verifysig == 0 && $debug) {
        echo "<pre>FILE size: $filesize:\n";
	@readfile("$tempdir/$decryptedfile");
        echo "\n</pre>";
    } 
}

deleteAtShutdown($tempdir);

exit;

/*********************************************************************/
/**
 * $Log: gpg_decrypt_attach.php,v $
 * Revision 1.28  2005/11/09 18:33:00  jangliss
 * strrpos only matches on single character.  This could result in a file
 * name being corrupted if it has a double extension.
 *
 * Revision 1.27  2005/07/27 14:07:48  brian
 * - update copyright to 2005
 *
 * Revision 1.26  2005/06/08 22:14:49  brian
 * - clean up debug and IE6 checks
 *
 * Revision 1.25  2004/08/11 02:06:40  joelm
 * -fixed a bug where SM_PATH was not valid in functions called indirectly from gpg_decrypt_attach.php since we do a chdir() to a temp dir and the SM_PATH is relative
 * -updated the display of info and warning messages when verifying an encrypted attachment to give complete info
 *
 * Revision 1.24  2004/07/24 12:50:48  brian
 * - fixed array_key_exists syntax
 *   - credit to r2s2@gmx.de for the patch
 * Bug 209
 *
 * Revision 1.23  2004/07/01 17:41:26  joelm
 * Bug 138
 * Improve error handling for encrypted attachment signature verification.
 *
 * Revision 1.22  2004/04/30 17:57:12  ke
 * -removed eol from end of file
 *
 * Revision 1.21  2004/04/09 12:12:01  brian
 * - remove last reference to SendMyDownLoadHeaders
 *
 * Revision 1.20  2004/03/21 21:10:00  joelm
 * Bug 138
 * - added a "Verify Signature" option to check the signature of encrypted attachments
 *
 * Revision 1.19  2004/03/17 20:21:49  brian
 * - fixed SendDownLoadHeaders to solve IE SSL Cache problems
 *
 * Revision 1.18  2004/02/26 20:20:58  ke
 * -added output buffering to avoid any excess output being added to attachment
 * -added extra headers for IE to allow for dialog popup and proper download
 *
 * Revision 1.17  2004/02/03 00:10:19  ke
 * -changed to always use internal headers function
 *
 * Revision 1.16  2004/02/02 23:52:02  ke
 * -removed reference to gpg.php
 *
 * Revision 1.15  2004/02/02 23:39:05  ke
 * -added gpg_execute functionality, uses realpath for redirection
 *
 * Revision 1.14  2004/01/13 20:32:43  ke
 * -added include for gpg_execute centralized functions
 *
 * Revision 1.13  2004/01/09 18:26:50  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
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
