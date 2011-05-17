<?php
/**
 * gpg_execute.php
 *
 * gpg_execute.php contains functions to call the gpg binary directly
 *
 * @author Aaron van Meerten
 */

require_once(SM_PATH.'plugins/gpg/gpg.php');

/**
 * function gpg_execute
 *
 * This function directly execs the gpg binary with parameters passed in
 * all other functions which try to use gpg should call this function
 *
 *
 * @param integer $debug debug flag
 * @param string  $parameterlist list of parameters to be passed to gpg from calling function
 * @param optional string $passphrase
 * @param optional string $body
 * @param optional string $safe_data_dir full path to data directory, useful if chdir'ed while executing
 *
 * @return array $return
 *
 */
function gpg_execute($debug, $parameterlist, $passphrase='',$body='',$safe_data_dir=false,$forceexec=false) {
    global $gpg_key_dir;
    global $path_to_gpg;
    global $username;
    if (!isset($path_to_gpg)) {
        if (!isset($GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'])) {
                load_prefs_from_file(SM_PATH . 'plugins/gpg/gpg_system_defaults.txt',$debug);
                load_prefs_from_file(SM_PATH . 'plugins/gpg/gpg_local_prefs.txt',$debug);
        }
            $path_to_gpg=($GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg']);
        }
    $use_proc_open = $GLOBALS['GPG_SYSTEM_OPTIONS']['use_proc_open'];
    if ($forceexec) {
        $use_proc_open='false';
    }

        $gpgexec = new GnuPG;
        //set debug
    if ($debug) {
            $gpgexec->debug = true;
    }

    if ($safe_data_dir) {
        $gpgHomeDir= "$safe_data_dir$username.gnupg";
    } else {
        $gpgHomeDir = $gpg_key_dir;
    }

    $set_gpgHomeDir=realpath($gpgHomeDir);
    if (!$set_gpgHomeDir) { 
        //error here, gpgHomeDir is not a real path
//        echo "ERROR: failure to get the real path from the gnupg home directory.<br>";
        $set_gpgHomeDir=$gpgHomeDir;
    }
        //set path to gpg executable
        $gpgexec->gpg_exe = ($GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg']);
       $gpgexec->gpgHomeDir=$set_gpgHomeDir;

    if (!check_php_version(4,3) || ($use_proc_open!='true')) {
        if ($debug) { echo "Setting GPG object to force use of exec to communicate with gpg.<br>\n"; }
        $gpgexec->force_exec=true;
    }
    if ($passphrase!='') {
        //pass in passphrase in case it's needed
        $gpgexec->passphrase=$passphrase;
    }
    $return = $gpgexec->execute_gpg($parameterlist,$body,true);
    return $return;
}

/**
 * $Log: gpg_execute.php,v $
 * Revision 1.6  2006/01/10 06:00:53  ke
 * - changed path for homedir to fully resolved path before passing to the GPG object
 *
 * Revision 1.5  2004/03/10 21:40:05  brian
 * - removed trailing whitespace
 * - added closing tag
 * - add log tag
 *
 */
?>