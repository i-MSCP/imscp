<?php
/**
 * gpg_functions.php
 *
 * GPG plugin functions file, as defined by the SquirrelMail-1.2 API.
 * Updated for the SM 1.3/1,4 API
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * portions of this file Copyright (c) 1999-2002 the Squirrelmail Development Team
 *
 * @package gpg
 * @author Brian Peterson
 *
 * Copyright (c) 2002-2003 Braverock Ventures
 *
 * $Id: gpg_functions.php,v 1.106 2004/01/03 22:28:29 ke Exp $
 *
 */
/*********************************************************************/

/**
 * Set a couple of constants and defaults. Don't change these,
 * the configurable stuff is in gpg_config.php
 */
$GPG_DIR='gpg';
$GPG_CRYPTO=TRUE;

/**
 * Load our config file.
 *
 * place all common load files in gpg_config.php
 * as well as checks for 1.2 vs 1.3 or greater
 *
 * Updated to use SM_PATH
 */
require_once (SM_PATH.'plugins/gpg/gpg_config.php');


//seed the random number generator once for this load
//not necessary if we are using php 4.2 or higher
if ( !check_php_version(4,2) ) {
     mt_srand(make_seed());
}

/*
 * Let's assume that we get the following from a submit
 *        <INPUT TYPE=text NAME="send_to"
 *        <INPUT TYPE=text NAME="send_to_cc"
 *        <INPUT TYPE=text NAME="send_to_bcc"
 *        <INPUT TYPE=text NAME=subject
 *        <TEXTAREA NAME=body
 *
 */

/* Just for poor wretched souls with E_ALL. :) */
global $data_dir;
global $username;
global $safe_data_dir;
$safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;

/* Load the path to the gpg binary from our preferences */

$path_to_gpg=($GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg']);

/*********************************************************************/

/**
 * function gpg_https_connection
 *
 * This function is caled to check whether the user has an SSL connection
 * Returns 0 for false and 1 for true (SSL)
 *
 * Use whenever you are expecting input from the user (passphrase, etc.)
 *
 * @param void
 * @return int 0|1
 */
function gpg_https_connection () {
  // does this definitely work on Windows etc?
  if ($_SERVER['HTTPS'])
      return (1);
  else return (0);
}


/*********************************************************************/
/**
 * function gpg_setglobal ($label, $value)
 *
 * This function will set a global variable that we want to be available
 * to Squirrelmail in a fassion that is usable by all of the SM getglobal
 * or other get functions.  Place the variable in _SESSION, _POST, and _GET
 *
 * @param string $name         The name of the global variable
 * @param string $value        The value to set it to.
 * @return true|false
 */
function gpg_setglobal ($name, $value ) {
    if ( !check_php_version(4,1) ) {
        global $_SESSION, $_GET, $_POST, $_COOKIE, $_SERVER;
    }
    if( isset($_SESSION[$name] ) ) {
        $_SESSION[$name] = $value;
    }
    if( isset($_POST[$name]) ) {
        $_POST[$name] = $value;
    }
    if ( isset($_GET[$name]) ) {
        $_GET[$name] = $value;
    }
    if ( isset($_COOKIE[$name]) ) {
        $_COOKIE[$name] = $value;
    }
    if ( isset($_SERVER[$name]) ) {
        $_SERVER[$name] = $value;
    }

    //should decide what to put in here as debug, if anything
    /* uncomment this if you want lots of marginally useful debug output
    if ($debug) {
       echo "<br> Setting global variables $name == $value";
       echo '<br> Session value = '. $_SESSION[$name];
       echo '<br> POST value = '. $_POST[$name];
       echo '<br> GET value = '. $_GET[$name];
       echo '<br> COOKIE value = '. $_COOKIE[$name];
       echo '<br> SERVER value = '. $_SERVER[$name];
    }
    */ //end debug
    //under what circumstances would we return false?

}

/*********************************************************************/
/**
 * function gpg_ckMOD($rMOD)
 * This function checks for security attacks. A $MOD variable is
 * provided in the QUERY_STRING and includes one of the files from the
 * modules directory ($MOD.mod). See if someone is trying to get out
 * of the modules directory by providing dots, unicode strings, or
 * slashes.
 *
 * @param  string $rMOD the name of the module requested to include.
 * @return void since it bails out with an access error if needed.
 */
function gpg_ckMOD($rMOD){
  if (strstr($rMOD, '.')
      || strstr($rMOD, '/')
      || strstr($rMOD, '%')
      || strstr($rMOD, "\\")){
    echo _("Cute.");
    exit;
  }
};

/*********************************************************************/
/**
 * function getTempDir()
 *
 * Determine the location of the system temporary directory.
 * If a specific setting cannot be found, it defaults to /tmp
 *
 * Original Source: Horde.php (class Horde)
 *
 * @return string  A directory name which can be used for temp files.
 *                 Returns false if one could not be found.
 */
function getTempDir()
{
    $tmp_locations = array('/dev/shm', '/dev/mfs', '/tmp', '/var/tmp', 'c:\temp', 'c:\windows\temp', 'c:\winnt\temp');

    /* If one has been specifically set, then use that */
    if (@is_dir($GLOBALS['GPG_SYSTEM_OPTIONS']['tmp_dir'])) {
        $tmp_check = $GLOBALS['GPG_SYSTEM_OPTIONS']['tmp_dir'];
        if (is_dir($tmp_check) and is_writable($tmp_check)) {
            $tmp = $tmp_check;
            break;
        } else {
            if ($debug) {
                echo '<br> GPG Plugin option directory tmp_dir: '.$tmp." is not writable.\n";
            }
        }
    }

    /* If we haven't set a value, then cycle through a
     * list of preset possibilities. */
    while (empty($tmp) && sizeof($tmp_locations)) {
        $tmp_check = array_shift($tmp_locations);
        if (@is_dir($tmp_check)) {
            if (is_writable ($tmp_check)) {
                $tmp = $tmp_check;
            } else {
                if ($debug) {
                    echo '<br> GPG Plugin directory tmp_dir: '.$tmp." is not writable.\n";
                }
            }
        }
    }

    /* Next, try PHP's upload_tmp_dir directive. */
    if (empty($tmp)) {
        $tmp_check = ini_get('upload_tmp_dir');
        if (is_dir ($tmp_check) and is_writable ($tmp_check)) {
            $tmp = $tmp_check;
            break;
        } else {
            if ($debug) {
                echo '<br> GPG Plugin option PHP upload directory upload_tmp_dir: '.$tmp." is not writable.\n";
            }
        }
    }

    /* Otherwise, try to determine the system
       temporary directory environment variable. */
    if (empty($tmp)) {
        $tmp = getenv('TMPDIR');
    }
    if (empty($tmp)) {
        $tmp = getenv('TEMP');
    }
    if (empty($tmp)) {
        $tmp = getenv('TMP');
    }

    /* If it is still empty, we have failed, so return false;
     * otherwise return the directory determined. */
    return empty($tmp) ? false : $tmp;
}

/**
 * function getTempFile()
 *
 * Create a temporary filename for the lifetime of the script, and
 * (optionally) register it to be deleted at request shutdown.
 *
 * Original Source: Horde.php (class Horde)
 *
 * @access public
 *
 * @param string $prefix            Prefix to make the temporary name more
 *                                  recognizable.
 * @param optional boolean $delete  Delete the file at the end of the
 *                                  request?
 * @param optional string $dir      Directory to create the temporary file
 *                                  in.
 *
 * @return string   Returns the full path-name to the temporary file.
 *                  Returns false if a temp file could not be created.
 */
function getTempFile($prefix = 'GPGPlugin', $delete = true, $dir = '')
{
    if (empty($dir) || !is_dir($dir)) {
        $tmp_dir = getTempDir();
    } else {
        $tmp_dir = $dir;
    }

    if (empty($tmp_dir)) {
        return false;
    }

    $tmp_file = tempnam($tmp_dir, $prefix);

    /* If the file was created, then register it for deletion and return */
    if (empty($tmp_file)) {
        return false;
    } else {
        if ($delete) {
            deleteAtShutdown($tmp_file);
        }
        return $tmp_file;
    }
}

/**
 * function deleteTempFile
 *
 * Securely delte a temporary file
 * Should be redundant, as the deleteat shutdown functions should work
 * but just to be sure, and to minimize the time the file is in existence
 *
 * @param string $filename
 *
 * @return void
 */
 function deleteTempFile ($filename) {
    if (@file_exists($filename)) {
        filesize ($filename); //get the size
        $fp = fopen ($filename, 'r+'); //open the file and set the pointer to the beginning
        $randstring = rand_string ($size); //get a random string of the right size
        fwrite ($fp, $randstring); //overwrite the file contents
        fclose ($fp);
        @unlink($filename);
    }

 }

/**
 * function createTempDir
 *
 * Create a temporary directory in the system's temporary directory.
 *
 * Original Source: Horde.php (class Horde)
 *
 * @param optional boolean $delete  Delete the temporary directory at the
 *                                  end of the request?
 *
 * @return string       The pathname to the new temporary directory.
 *                      Returns false if directory not created.
 */
function createTempDir($delete = true)
{
    $temp_dir = getTempDir();
    if (empty($temp_dir)) return false;

    /* Get the first 8 characters of a random string to use as a temporary
       directory name. */
    do {
        $temp_dir .= '/' . substr(md5(mt_rand()), 0, 8);
    } while (file_exists($temp_dir));

    $old_umask = umask(0000);
    if (!mkdir($temp_dir, 0700)) {
        $temp_dir = false;
    } else {
        if ($delete) {
            deleteAtShutdown($temp_dir);
        }
    }
    umask($old_umask);

    return $temp_dir;
}

/**
 * function deleteAtShutdown
 *
 * Original Source: Horde.php (class Horde)
 *
 * Removes given elements at request shutdown.
 *
 * If called with a filename will delete that file at request
 * shutdown; if called with a directory will remove that directory
 * and all files in that directory at request shutdown.
 *
 * If called with no arguments, return all elements to be deleted
 * (this should only be done by _deleteAtShutdown).
 *
 * The first time it is called, it initializes the array and
 * registers _deleteAtShutdown() as a shutdown function -
 * no need to do so manually.
 *
 * The second parameter allows the unregistering of previously
 * registered elements.
 *
 * @access public
 *
 * @param optional string $filename   The filename to be deleted at the end of
 *                                    the request.
 * @param optional boolean $register  If true, then register the element for
 *                                    deletion, otherwise, unregister it.
 */
function deleteAtShutdown($filename = false, $register = true)
{
    static $dirs, $files;

    /* Initialization of variables and shutdown functions. */
    if (is_null($dirs)){
        $dirs = array();
        $files = array();
        register_shutdown_function('_deleteAtShutdown');
    }

    if ($filename) {
        if ($register) {
            if (@is_dir($filename)) {
                $dirs[$filename] = true;
            } else {
                $files[$filename] = true;
            }
        } else {
            unset($dirs[$filename]);
            unset($files[$filename]);
        }
    } else {
        return array($dirs, $files);
    }
}

/**
 * function _deleteAtShutdown
 *
 * Original Source: Horde.php (class Horde)
 *
 * Delete registered files at request shutdown.
 *
 * This function should never be called manually; it is registered
 * as a shutdown function by deleteAtShutdown() and called
 * automatically at the end of the request. It will retrieve the
 * list of folders and files to delete from
 * deleteAtShutdown()'s static array, and then iterate
 * through, deleting folders recursively.
 *
 * @access private
 *
 * @param void
 *
 * @return void
 */
function _deleteAtShutdown()
{
    $registered = deleteAtShutdown();
    $dirs = $registered[0];
    $files = $registered[1];

    foreach ($files as $file => $val) {
        /* Delete files */
        if ($val && @file_exists($file)) {
            $size = filesize ($file); //get the size
            $fp = fopen ($file, 'r+');
            $randstring = rand_string ($size); //get a random string of the right size
            fwrite ($fp, $randstring); //overwrite the file contents
            fclose ($fp);
            @unlink($file);
        }
    }

    foreach ($dirs as $dir => $val) {
        /* Delete directories */
        if ($val && @file_exists($dir)) {
            /* Make sure directory is empty. */
            $dir_class = dir($dir);
            while (false !== ($entry = $dir_class->read())) {
                if ($entry != '.' && $entry != '..') {
                    $size = filesize ($entry); //get the size
                    $fp = fopen ($entry, 'r+');
                    $randstring = rand_string ($size); //get a random string of the right size
                    fwrite ($fp, $randstring); //overwrite the file contents
                    fclose ($fp);
                    @unlink($dir . '/' . $entry);
                }
            }
            $dir_class->close();
            @rmdir($dir);
        }
    }
}

/*********************************************************************/
/**
 * function make_seed
 *
 * Create the seed for the random functions.
 *
 * make_seed will only be called for older versions of PHP
 *
 * @param void
 * @return float Seed value
 *
 */

function make_seed() {
    list($usec, $sec) = explode(' ', microtime());
    return (float) $sec + ((float) $usec * 100000);
}

/**
 * function rand_string
 *
 * Function to ease the creation of random strings for
 * overwriting temp files or memory buffers.
 *
 * make_seed will only be called for older versions of PHP
 *
 * @param integer $length  The length of the random string to generate
 * @return string $ret      The Random String generated.
 *
 */
function rand_string ($length) {
    mt_srand(make_seed());
    $ret = "";
    for ($i = 0; $i < $length; $i++) {
        $ret .= chr (mt_rand(0,255));
    };
    return $ret;
}

/*********************************************************************/
/**
 * function gpg_set_cached_passphrase
 *
 * Set the Cached Passphrase, storing it in secure storage
 *
 *
 * @param string passphrase
 * @return boolean
 */
function gpg_set_cached_passphrase ($passphrase) {
    //declare passphrase as a global, per the plugin spec
    global $cached_passphrase;
    global $gpg_onetimepad;

    if (sqsession_is_registered('gpg_onetimepad')){
        gpg_clear_cached_passphrase ();
    } else {
        $gpg_onetimepad = rand_string (1024); //create a 1024 byte pseudo-random string
        sqsession_register ($gpg_onetimepad, 'gpg_onetimepad');
    };

    //now encrypt the passphrase to the session one time pad
    $cached_passphrase = OneTimePadEncrypt ($passphrase, $gpg_onetimepad);

    //register the passphrase with the session
    sqsession_register($cached_passphrase, 'cached_passphrase');

    return 'true';

    die ('Improper termination of function gpg_set_cached_passphrase. Contact your system administrator.');

};

/*********************************************************************/
/**
 * function gpg_get_cached_passphrase
 *
 * Get/retrieve the Cached Passphrase from secure storage
 *
 *
 * @return string $passphrase or 'false'
 */
function gpg_get_cached_passphrase () {
    //declare passphrase as a global, per the plugin spec
    global $cached_passphrase;
    global $gpg_onetimepad;

    //get the passphrase from the session
    if (sqsession_is_registered('cached_passphrase')){
        sqgetGlobalVar('cached_passphrase', $cached_passphrase);
        sqgetGlobalVar('gpg_onetimepad', $gpg_onetimepad);

        //now decrypt the passphrase using the session one time pad
        $passphrase = OneTimePadDecrypt ($cached_passphrase, $gpg_onetimepad);

        //and return the passphrase to the calling function
        return $passphrase;

    } else {
        return 'false';
    };

    die ('Improper termination of function gpg_get_cached_passphrase. Contact your system administrator.');

};

/*********************************************************************/
/**
 * function gpg_clear_cached_passphrase
 *
 * Clear the Cached Passphrase from secure storage
 * Overwrite first with gibberish, just in case.
 *
 * @return boolean string true if cleared, false or error
 */
function gpg_clear_cached_passphrase () {
    //declare passphrase as a global, per the plugin spec
    global $cached_passphrase;
    global $gpg_onetimepad;

    //now clear the passphrase by overwriting first with junk
    if (sqsession_is_registered('cached_passphrase')){
        sqgetGlobalVar('cached_passphrase', $passphrase);
        sqgetGlobalVar('gpg_onetimepad', $gpg_onetimepad);
        $size = strlen ($cached_passphrase); //get the size
        $randstring = rand_string ($size); //get a random string of the right size
        $cached_passphrase = $randstring; //overwrite the passphrase in memory
        $gpg_onetimepad = rand_string (1024); //overwrite the one time pad

        //register the (now junk) passphrase with the session
        sqsession_register($cached_passphrase, 'cached_passphrase');
        sqsession_register($gpg_onetimepad, 'gpg_onetimepad');

        //un-register the passphrase with the session
        sqsession_unregister('cached_passphrase');
        sqsession_unregister('gpg_onetimepad');

        return 'true';

    } else {
        return 'false';
        //@todo put in better error reporting here
    };

    die ('Improper termination of function gpg_clear_cached_passphrase. Contact your system administrator.');
};

/*********************************************************************/
/**
 *
 * function gpg_is_passphrase_cacheable
 *
 * This function checks all the options to see if passphrase caching
 * is enabled.  Returns true if system allows passphrase caching and
 * the user has selected to cache their passphrase
 * Otherwise returns false.
 *
 * @param integer $debug to pass through to the loading preferences function call
 * @return boolean true if caching is enabled, false if not
 */
function gpg_is_passphrase_cacheable($debug=0) {
    global $username;
    global $safe_data_dir;
    $cache_passphrase   = getPref ($data_dir, $username, 'cache_passphrase');
    load_prefs_from_file(SM_PATH .'plugins/gpg/gpg_system_defaults.txt',$debug);
    load_prefs_from_file(SM_PATH .'plugins/gpg/gpg_local_prefs.txt',$debug);
    $allowpassphrasecaching   = $GLOBALS['GPG_SYSTEM_OPTIONS']['allowpassphrasecaching'];
    if ($allowpassphrasecaching=='true' and $cache_passphrase=='true') {
        if ($debug) {
            echo "\n<br>gpg_is_passphrase_cacheable returned true<br>\n";
        }
        return true;
    } else {
        if ($debug) {
            echo "\n<br>gpg_is_passphrase_cacheable returned false<br>\n";
        }
        return false;
    }
}

/*********************************************************************/
/**
 *
 * function gpg_is_passphrase_cached
 *
 * This function checks all the options to see if a cached passphrase should
 * be retrieved.  Returns true if system allows passphrase caching and
 * the user has selected to cache their passphrase, and there is a cached passphrase
 * Otherwise returns false.
 *
 * @param integer $debug to pass through to the loading preferences function call
 * @return boolean true if passphrase is cached, false if not
 */
function gpg_is_passphrase_cached($debug=0) {
    if (gpg_get_cached_passphrase()!='false' and gpg_is_passphrase_cacheable($debug)) {
        if ($debug) {
            echo "\n<br>gpg_is_passphrase_cached returned true<br>\n";
        }
        return true;
    } else {
        if ($debug) {
            echo "\n<br>gpg_is_passphrase_cached returned false<br>\n";
        }
        return false;
    }
}

/*********************************************************************/
/**
 *
 * function gpg_verify_passphrase
 *
 * Checks if the passphrase for the given key is valid
 *
 * @param $passphrase is the passphrase to verify
 * @param $key_id is the secret key ID to check the passphrase on
 * @return $return['verified'] boolean string true if valid, false if not valid
 */
function gpg_verify_passphrase($passphrase, $key_id='') {
    global $username;
    global $safe_data_dir;
    $debug=0;
    //include signing functions
    require_once(SM_PATH . 'plugins/gpg/gpg_sign_functions.php');
    $return = gpg_sign_message('Authenticate',$passphrase,$debug,$key_id);
    $return['verified'] = 'false';
    if (!$return['errors'][0]) {
      $sep = '-----BEGIN PGP SIGNED MESSAGE-----';
      list ($front, $cyphertext_tail) = explode ($sep, $return['cyphertext']);
      if ($cyphertext_tail) {
        $return['verified'] = 'true';
      } else {
        $return['errors'][] = $return['cyphertext'];
        unset ($return['cyphertext']);
      }
        }
    return $return;
}


/*********************************************************************/
/**
 * function gpg_section_header()
 *
 * Create HTML Table for GPG Plugin Header for each page
 *
 * @param  string $_section_title localized Section Title string
 *
 * @return string HTML table structure
 */
function gpg_section_header ( $_section_title = '', $_color )
{
    $_table  = '<table width="100%" align="center" border="0" cellpadding="2" cellspacing="0">';
    $_table .= '<tr><td bgcolor="';
    $_table .= $_color;
    $_table .= '" align="center">';
    $_table .= '<b>';
    $_table .= $_section_title;
    $_table .= '</b>';
    $_table .= '</td></tr></table>';

    return $_table;
}

/*********************************************************************/
/**
 * function gpg_add_help_link()
 *
 * create an HTML TAG Link to display help files
 * This will only create the first half of the A Tag pair
 *
 * @param  string $_help_file name of help file to load
 *
 * @return string HTML link Tag prefix
 *
 * @todo we should check for attempted security breach here, stripping . and /'s, etc
 *       and making sure that the file actually exists, otherwise go to base.php
 */
function gpg_add_help_link ( $_help_file = 'base' , $fullscreen= 'false')
{

    if ($fullscreen == 'true') {

        $_link =   '<a href="'.SM_PATH.'plugins/gpg/gpg_help_base.php?help='.$_help_file.'">';

    } else {

        $_link =   '<a href="'.SM_PATH.'plugins/gpg/gpg_help.php?help='
                  . $_help_file
                  . '" target="_blank"'
                  . ' onclick="window.open(this.href,\'Help\', \'width=500,height=400,top=10,left=10,menubar=no,toolbar=no,location=no,status=no,resizable=yes\');'
                  . ' return false;">';
    };
    return $_link;
}

/*********************************************************************/
/**
 * function gpg_stripstr()
 *
 * function to strip a gpg: from the beginning of a string, if it exists
 *
 * @param  string $inline line of output from gpg
 *
 * @return string $line of output with gpg: stripped off
 */

function gpg_stripstr($inline,$stripstr='gpg:') {
    $pos = strpos($inline,$stripstr);
        if ($pos !== false) {
                $pos = $pos + strlen($stripstr);
                $inline=substr($inline,$pos,strlen($inline)-$pos);
        }
    return $inline;
}

/*********************************************************************/
/**
 *
 * function gpg_update_trustdb()
 *
 * This function will update the gpg trustdb for the current user
 * including a scan of the system keyring if enabled
 *
 * @param integer $debug 
 * 
 * @return none
 */
function gpg_update_trustdb($debug) {

  global $data_dir;
  global $username;
  global $safe_data_dir;
  $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
  $gpg_key_dir ="$safe_data_dir$username.gnupg";

        load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
        load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);

        $path_to_gpg = $GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];

        $extra_cmd = '';
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

   
   $trustdbcmd = "$path_to_gpg --no-tty --homedir $gpg_key_dir $extra_cmd --check-trustdb 2>&1 ";
   exec($trustdbcmd, $result, $returnval);
   if ($debug) { echo "<br>TrustDB Update Results from command $trustdbcmd:<pre>"; print_r($result); echo "</pre> Returnval: $returnval<br>"; }
}

/*********************************************************************/
/**
 * function gpg_parse_output()
 *
 * This will parse the string that gpg returns for info, warnings, errors
 * and return them in arrays.  This function also returns any other output seperately
 *
 * @param  string $gpg_output text output from gpg
 *
 * @return array $return ['errors'],['warnings'],['info'] contain gpg messages ['output'] contains the rest of the output
 */
function gpg_parse_output( $gpg_output )
{
global $insecure_mem_warning;
$insecure_mem_warning = $GLOBALS['GPG_SYSTEM_OPTIONS']['insecure_mem_warning'];
$return['errors'] = array();
$return['warnings'] = array();
$return['info'] = array();
$return['signature'] = array();
$return['verified'] = array();
$return['skipped_keys'] = array();
$trimmed = array();
$return['output'] = '';

if (!is_array($gpg_output)) {
    $gpg_output = explode("\n",$gpg_output);
}
foreach ($gpg_output as $line) {
    $j = 0;
        $j = substr_count ($line, 'Signature Status');
        if ($j) {
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: encrypted with');
        if ($j) {
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'Primary key fingerprint:');
        if ($j) {
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: Signature made');
        if ($j) {
            $return['signature'][] = gpg_stripstr($line);
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: Good signature');
        if ($j) {
	    $return['verified'] = 'true';
            $return['signature'][] = gpg_stripstr($line);
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg:                 aka');
        if ($j) {
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, '      "');
        if ($j) {
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: WARNING: message was not integrity protected');
        if ($j) {
            $line = gpg_stripstr($line);
            $return['warnings'][] = gpg_stripstr($line, 'WARNING:');
            continue;
        };
        $j = substr_count($line, 'gpg: WARNING: This key is not certified with a trusted signature!');
        if ($j) {
            $line = gpg_stripstr($line);
	    $return['signature'][] = $line;
            $return['warnings'][] = gpg_stripstr($line, 'WARNING:');
	    $return['untrusted'] = 'true';
            continue;
        };
        $j = substr_count ($line, 'gpg: Bad signature');
        if ($j) {
            $return['signature'][] = gpg_stripstr($line);
            $return['warnings'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: BAD signature');
        if ($j) {
            $return['signature'][] = gpg_stripstr($line);
            $return['warnings'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, "gpg: can't open");
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: keydb_search failed');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: key ');
        if ($j) {
            $return['info'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'usage: gpg');
        if ($j) {
          $return['errors'][] = _("Problem with command syntax. Check Debug Output");
        };
        $j = substr_count ($line, 'decryption failed');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        }
        $j = substr_count ($line, 'gpg: Warning:');
        if ($j) {
            $return['warnings'][] = gpg_stripstr($line);
            continue;
        };
	$j = substr_count($line, "gpg: Can't check signature: public key not found");
	if ($j) {
		$return['errors'][] = gpg_stripstr($line);
		continue;
	};
        $j = substr_count ($line, 'gpg: Error:');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: no valid OpenPGP data found.');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: decrypt_message failed');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: invalid radix64 character');
        if ($j) {
            $return['warnings'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: CRC error');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'invalid packet');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: out of secure memory while allocating');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: (this may be caused by too many');
        if ($j) {
            $return['errors'][] = gpg_stripstr($line);
            continue;
        };
        $j = substr_count ($line, 'gpg: Oops:');
        if ($j) {
            $return['warnings'][] = gpg_stripstr($line);
        };
        $j = substr_count ($line, 'gpg: WARNING: using');
        if ($j) {
	    if ($insecure_mem_warning) {
                $return['warnings'][] = gpg_stripstr($line);
	    } 
            continue;
        };
        $j = substr_count ($line, 'gpg: please see http://');
        if ($j) { 
	    if ($insecure_mem_warning) {
                $return['warnings'][] = gpg_stripstr($line);
	    } 
            continue;
        };
        $j = substr_count ($line, 'encryption failed');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        }
        $j = substr_count ($line, 'gpg: keyblock resource');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        }
        $j = substr_count ($line, 'No such file or directory');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        }
        $j = substr_count ($line, 'gpg: Warning:');
        if ($j) {
          $return['warnings'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: WARNING:');
        if ($j) {
          $return['warnings'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'usage: gpg');
        if ($j) {
          $return['errors'][] = _("Problem with command syntax. Check Debug Output");
      continue;
        };
        $j = substr_count ($line, 'gpg: Error:');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: Oops:');
        if ($j) {
          $return['warnings'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: ERROR:');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        };
	$j = substr_count ($line, 'skipped: unusable public key');
        if ($j) {
	  $return['errors'][] = gpg_stripstr($line);
	}
        $j = substr_count ($line, 'skipped: public key not found');
        if ($j) {
          $return['skipped_keys'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: Missing argument for option');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: NOTE: secret key');
	if ($j) {
	  $return['warnings'][]=gpg_stripstr($line);
        }
        $j = substr_count ($line, 'gpg: no default secret key: bad passphrase');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: no default secret key');
        if ($j) {
           $return['errors'][] = gpg_stripstr($line);
           continue;
        };
        $j = substr_count ($line, 'gpg: [stdin]: sign+encrypt failed: bad passphrase');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: [stdin]: clearsign failed: bad passphrase');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
          continue;
        };
        $j = substr_count ($line, 'gpg: protection algorithm 1 (IDEA) is not supported');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
          continue;
        };
        $j = substr_count ($line, 'gpg: protection algorithm');
        if ($j) {
           $return['errors'][] = gpg_stripstr($line);
           continue;
        };
        $j = substr_count ($line, 'invalid packet');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, 'gpg: Invalid option');
        if ($j) {
          $return['errors'][] = gpg_stripstr($line);
      continue;
        };
        $j = substr_count ($line, ': There is no indication that this key really belongs to the owner');
        if ($j) {
	  $return['signature'][] = gpg_stripstr($line);
          $return['warnings'][] = _("There is no indication that this key really belongs to the owner");
          $return['warnings'][] = _("This error usually occurs because you have not set a trusted key, or because you have not signed the key you are trying to encrypt to.");
      continue;
        };
    $j = substr_count($line, 'gpg:          There is no indication that the signature belongs to the owner');
        if ($j) {
	  $return['signature'][] = gpg_stripstr($line);
          $return['warnings'][] = _("There is no indication that this key really belongs to the owner");
          $return['warnings'][] = _("This error usually occurs because you have not set a trusted key, or because you have not signed the key you are trying to encrypt to.");
          continue;
        };
        $j = substr_count ($line, "gpg: checking the trustdb");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count($line, "gpg: error reading key: public key not found");
        if ($j) {
      $return['errors'] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg: next trustdb check due at");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg: checking at depth");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg: public key of ultimately trusted key 00000000 not found");
        if ($j) {
      $return['warnings'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg: Oops: keyid_from_fingerprint: no pubkey");
        if ($j) {
      $return['warnings'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg: Oops: keyid_from_fingerprint: no pubkey");
        if ($j) {
      $return['warnings'][] = gpg_stripstr($line);
      continue;
    }
        //some kind of key message, trap 'em all for now
        $j = substr_count ($line, "gpg: key");
        if ($j) {
      $return['info'] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg: Total number processed:");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }
        //@todo add some info about how many imported to $return
        $j = substr_count ($line, "gpg:               imported:");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg:              unchanged:");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg:           new user IDs:");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }
        $j = substr_count ($line, "gpg:         new signatures:");
        if ($j) {
      $return['info'][] = gpg_stripstr($line);
      continue;
    }

    $trimmed[] = $line;
}

$return['output'] = implode($trimmed,"\n");

return $return;

}

/*********************************************************************/
/**
 *
 * $Log: gpg_functions.php,v $
 * Revision 1.106  2004/01/03 22:28:29  ke
 * -added note about secret key expiration as well as trapped public key unusable errors in gpg_parse_output
 *
 * Revision 1.105  2003/12/19 21:17:35  ke
 * -added redirection of stderr in gpg command for update trustdb
 * -added more debug output to update trustdb function
 *
 * Revision 1.104  2003/12/19 20:52:27  ke
 * -added output of gpg results if debug is on in gpg_update_trustdb
 *
 * Revision 1.103  2003/12/19 20:46:19  ke
 * -added gpg_update_trustdb function for updating the trust database
 * -added $return['untrusted'] flag when parsing gpg output, to tell if a key is untrusted
 *
 * Revision 1.102  2003/12/17 20:05:15  ke
 * -added array dimensioning at the beginning of parse gpg output function
 *
 * Revision 1.101  2003/12/11 20:46:53  ke
 * -added trap for public key not found error in the gpg_parse_output function
 * bug 28
 *
 * Revision 1.100  2003/12/02 20:59:42  ke
 * -added check for BAD signature as well as Bad
 *
 * Revision 1.99  2003/12/02 04:12:30  ke
 * -added verified line to gpg_parse_output to allow operation within verification of signatures on attachments
 *
 * Revision 1.98  2003/12/02 02:59:59  ke
 * -added certain trusted key warnings to signature line so that they appear after the signature
 * when reading a message
 *
 * Revision 1.97  2003/11/25 21:53:49  ke
 * -added lookup for insecure_mem_warning to parse_output
 * -moved check for insecure_mem_warning after catching the error, so it can be properly ignored
 * bug 113
 *
 * Revision 1.96  2003/11/25 01:54:50  ke
 * -changed getPref to use $data_dir instead of $safe_data_dir
 *
 * Revision 1.95  2003/11/24 20:44:29  ke
 * -added safe_data_dir and getHashedDir calls to gpg_functions.php
 * bug 116
 *
 * Revision 1.94  2003/11/24 19:09:07  brian
 * changed parameters for pop-up window
 * - now allows scollbars and resizing
 * Bug 120
 *
 * Revision 1.93  2003/11/22 14:52:25  brian
 * - added security breach trodo to gpg_add_help_link fn
 * Bug 70
 *
 * Revision 1.92  2003/11/22 14:42:26  brian
 * added missing " in gpg_add_help_link fn
 * Bug 70
 *
 * Revision 1.91  2003/11/22 14:31:31  brian
 * - added fullscreen option to gpg_add_help_link fn
 * Bug 70
 *
 * Revision 1.90  2003/11/20 22:26:52  walter
 * - updated help popup window attributes
 *
 * Revision 1.89  2003/11/20 21:26:11  ke
 * -moved trustdb messages to info
 * -added checking trustdb to info
 *
 * Revision 1.88  2003/11/20 20:21:31  ke
 * -removed WARNING: line from message was not integrity protected message
 *
 * Revision 1.87  2003/11/20 17:22:09  brian
 * -added window properties to gpg_help_link pop-up
 * Bug 101
 *
 * Revision 1.86  2003/11/20 16:39:22  walter
 * - updated help file path to use SM_PATH constant
 *
 * Revision 1.85  2003/11/12 20:41:54  ke
 * -extended gpg_stripstr to possibly strip another string than gpg:
 * -added error messages for key signature warnings
 * -made all key signature warnings into warnings instead of errors
 *
 * Revision 1.84  2003/11/11 22:41:38  ke
 * -added gpg_parse_output function with all gpg messages consolidated into one place
 * bug 107
 *
 * Revision 1.83  2003/11/06 23:31:19  ke
 * -added debug flag passing within cacheable/cached functions
 *
 * Revision 1.82  2003/11/04 21:38:40  brian
 * change to use SM_PATH
 *
 * Revision 1.81  2003/11/03 21:41:18  brian
 * added debug text to new passphrase caching functions
 *
 * Revision 1.80  2003/11/03 18:55:23  brian
 * removed gpg_makePage function, as it is no longer used
 *
 * Revision 1.79  2003/11/03 17:31:48  ke
 * -Added functions to check for options to cache passphrase
 * -Added function to check for a cached passphrase (with all options correctly set).
 * bug 66
 *
 * Revision 1.78  2003/11/01 22:27:30  brian
 * minor mod to gpg_section_header fn
 *
 * Revision 1.77  2003/11/01 22:01:26  brian
 * infrastructure changes to support removal of MakePage functions
 *
 * Revision 1.76  2003/10/30 20:53:55  brian
 * fixed problems apparent in the xgettext index
 * Bug 35
 *
 * Revision 1.75  2003/10/29 17:26:26  brian
 * added better directory tests to getTempDir fn
 *
 * Revision 1.74  2003/10/28 23:58:43  walter
 * - added 2 new functions:
 *   - gpg_section_header() - displays a consistant header
 *   - gpg_add_help_link() - generates the Help link HTML
 *
 * Revision 1.73  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.72  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.71  2003/10/07 19:26:39  ke
 * -Added gpg_verify_passphrase function
 * -Uses signing code to check if a passphrase for a particular key is correct
 *
 * Revision 1.70  2003/10/06 17:49:43  brian
 * minor changes to HTML rendering
 * Bug 65
 *
 * Revision 1.69  2003/10/04 19:21:07  brian
 * cleaned up some of the HTML in gpg_makePage
 *
 * Revision 1.68  2003/09/26 02:29:01  ke
 * -changed call to mt_rand to use 2 parameters
 * -modified all caching functions to use $cached_passphrase instead of $passphrase
 * bug 40
 *
 * Revision 1.67  2003/09/19 18:37:38  brian
 * - fixed syntax error in SQGetGlobalVar in cache fn's
 *
 * Revision 1.66  2003/09/19 13:27:15  brian
 * Fixed cut and paste error in the die command of the cache fn's
 *
 * Revision 1.65  2003/09/19 11:08:07  brian
 * inital checkin of passphrase caching functions:
 * - gpg_set_cached_passphrase
 * - gpg_get_cached_passphrase
 * - gpg_clear_cached_passphrase
 * Bug 40
 *
 * Revision 1.64  2003/08/14 02:34:18  vermette
 * removed references to getstart.mod
 *
 * Revision 1.63  2003/07/08 13:25:02  brian
 * added missing :
 *
 * Revision 1.62  2003/07/08 13:23:30  brian
 * added smoptions target to backlink in gpg_makepage fn
 *
 * Revision 1.61  2003/07/07 18:31:48  brian
 * - modified to use backlink option on gpg_makepage fn
 * - modified gpg_makepage fn to be ready for gettext localization
 * Bug 35
 *
 * Revision 1.60  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.  The previous 'back' link now only appears if requested (new arg to makePage).  This isn't done by any means, but at most it's as broken as it was, so it's an improvement.
 *
 * Revision 1.59  2003/05/28 11:35:42  brian
 * added code to better select temporary directory in gettempdir fn
 *
 * Revision 1.58  2003/05/16 13:13:52  brian
 * added tags for phpdoc
 *
 * Revision 1.57  2003/05/13 22:53:23  brian
 * changed file include lines so it would work from hook in compose.php
 * Bug 26
 *
 * Revision 1.56  2003/05/08 22:34:13  brian
 * fixed cut and paste typo error in fn deleteTempFile
 *
 * Revision 1.55  2003/05/08 18:41:09  brian
 * cleaned up comments
 *
 * Revision 1.54  2003/04/27 12:16:22  brian
 * fixed indents to remove irregular use of tabstops - no functional change
 *
 * Revision 1.53  2003/04/23 20:26:33  brian
 * fixed typo in editor highlighing in line 467
 *
 * Revision 1.52  2003/04/23 01:36:13  brian
 * Updated temp file functions to allow for secure deletion of temp files
 * - added make_seed fn
 * - added rand_string fn
 * - added file overwrite calls to _deleteAtShutdown fn
 * - added deleteTempFile fn
 *
 * Revision 1.51  2003/04/18 12:41:05  brian
 * added functions for temporary files and directories cribbed from Horde code
 *
 * Revision 1.50  2003/04/11 05:08:11  brian
 * fixed <script> tag syntax in gpgMakePage to correct for strict tag checking in Opera
 *
 * Revision 1.49  2003/04/09 18:04:15  brian
 * moved $GPG_VERSION and fn gpg_version to gpg_pref_functions.php file
 *
 * Revision 1.48  2003/04/09 15:32:21  brian
 * add pupdate_version function for compatibiltiy to Plugin Updates plugin
 *
 * Revision 1.47  2003/04/06 23:46:47  brian
 * comment out debug in gpg_setglobal by default.  it is too voluminous to be of much use.
 *
 * Revision 1.46  2003/04/06 21:28:00  brian
 * fixed typos in debug
 *
 * Revision 1.45  2003/04/06 21:26:24  brian
 * added debug to gpg_setglobal
 *
 * Revision 1.44  2003/04/06 21:19:25  brian
 * fixed $name, $value mappings
 *
 * Revision 1.43  2003/04/06 20:50:34  brian
 * fixed typo on line 151
 *
 * Revision 1.42  2003/04/06 20:47:47  brian
 * - create gpg_setglobal to get around SM's getglobal function
 * - update gpg_encrypt to use ne gpg_setglobal function
 * Bug 22
 *
 * Revision 1.41  2003/04/01 19:36:28  brian
 * updated GPG_VERSION to 1.1
 *
 * Revision 1.40  2003/03/31 14:23:37  brian
 * fixed comment typos -non-functional change
 *
 * Revision 1.39  2003/03/12 14:08:12  brian
 * - updated gpg version to 1.0.2
 * - added function header comment blocks to all functions
 *
 * Revision 1.38  2003/03/11 23:29:15  tyler
 * - modified to use the new broken out *_function.php files
 *
 * Revision 1.37  2003/03/11 22:14:02  tyler
 * - Bug fixing in the accept passphrase section of _sign_message
 *
 * Revision 1.36  2003/03/11 21:58:27  tyler
 * - Initial work to accept passphrase in popup window and use for signing
 *
 * Revision 1.35  2003/03/11 18:28:55  tyler
 * - Added comment to the sign_message function where the hardcoded keyID is.
 *   Need to fix this soon.
 *
 * Revision 1.34  2003/03/11 18:05:57  tyler
 * - modified the sign_message function to not accept rcpt list, not used
 *
 * Revision 1.33  2003/03/11 16:55:24  brian
 * Added Joel's fix for the extra line after the GPG MESSAGE header
 *
 * Revision 1.32  2003/03/11 01:09:28  brian
 * Fixed handling of missing keys which cause gpg to not encrypte the message - "encryption failed" warnings
 *
 * Revision 1.31  2003/03/09 21:39:25  brian
 * Added check for https connection
 *
 * Revision 1.30  2003/03/09 14:33:36  brian
 * Added Tyler's gpg_sign_message function
 *
 * Revision 1.29  2003/03/08 19:30:56  brian
 * Added missing bracket at line 188 - credit Tyler
 *
 * Revision 1.28  2003/03/07 16:38:42  brian
 * Patched to fix broken if staetments and debug tests - credit Tyler
 * Removed Superflous includes
 * fixed typo on line 579c590 >global $path_to_gpg
 *
 * Revision 1.27  2003/03/06 01:57:56  brian
 * minor syntax issues resolved
 *
 * Revision 1.26  2003/03/05 14:56:03  brian
 * Final Edits for Release 1.0
 *
 * Revision 1.25  2003/02/22 20:09:59  brian
 * Added gpg_list_keys function and updated gpg_encrypt to use trusted_key preference again.
 *
 * Revision 1.24  2003/02/19 23:52:29  brian
 * updates to use more prefs selections
 *
 * Revision 1.23  2003/01/06 13:09:07  brian
 * Changed all command code to utilize global $path_to_gpg loaded from preferences file
 *
 * Revision 1.22  2003/01/05 15:05:37  brian
 * Added infrastructure function gpg_generate_keypair and fixed strpos usage in gpg_encrypt.
 *
 * Revision 1.21  2003/01/04 11:43:29  brian
 * added vinay's changes for encrypt
 *
 * Revision 1.20  2003/01/03 23:32:06  brian
 * Bracketed remaining echo statement in if $debug in function gpg_encrypt
 *
 * Revision 1.19  2003/01/03 23:10:00  brian
 * removed escapeshellarg for returntext in function gpg_encrypt since we use the --armor command in gpg.
 *
 * Revision 1.18  2003/01/03 23:01:18  brian
 * changed back to escapeshellarg for body only in function gpg_encrypt. we will rely on SM to scrub email addresses.
 *
 * Revision 1.17  2003/01/03 22:45:12  brian
 * Merged vinay's changes.
 *
 * Revision 1.16  2003/01/03 22:40:35  brian
 * Fixed escapeshellcmd to escape the entire gpg command string in function gpg_encrypt.
 *
 * Revision 1.15  2003/01/03 21:59:10  brian
 * Changed a comment to test checkin from laptop.
 *
 * Revision 1.14  2002/12/10 21:52:29  brian
 * fixed syntax errors in gpg_recv_key fuction after integrating to keyserver.mod
 *
 * Revision 1.13  2002/12/10 03:24:36  brian
 * added gpg_recv_key function
 *
 * Revision 1.12  2002/12/10 03:23:54  brian
 * updated all functions to user system $data_dir for prefs and $data_dir/$username.gnupg as the --homedir for gpg
 *
 * Revision 1.11  2002/12/09 03:48:35  vinay
 * added support for system preferences
 *
 * Revision 1.10  2002/12/08 19:51:26  brian
 * added beginnings of keyserver functions
 *
 * Revision 1.9  2002/12/06 21:02:11  brian
 * updates to functions to prepare for keyserver lookup
 *
 * Revision 1.8  2002/12/06 02:18:15  brian
 * edited key/ring import functions to echo nothing unless $debug is set and return the resultstring from the gpg comand as a string
 *
 * Revision 1.7  2002/12/05 23:14:53  brian
 * fixed function gpg_import_key to use user keyring rather than system keyring
 *
 * Revision 1.6  2002/12/05 22:45:55  brian
 * added comments and worked around hack code
 *
 * Revision 1.5  2002/12/05 21:06:16  brian
 * mannually appended log entries
 *
 * Revision 1.4  2002/12/05 21:02:02  brian
 * fixed $Log
 *
 * Revision 1.3  2002/12/05 21:01:09  brian
 * changed gpg_MakePage to point to correct gpg plugin functions instead of sqspell functions
 *
 * Revision 1.2  2002/12/05 19:28:11  brian
 * Added ID and Log tags
 *
 * Revision 1.1  2002/12/05 16:47:55  brian
 * Initial revision
 *
 */

?>
