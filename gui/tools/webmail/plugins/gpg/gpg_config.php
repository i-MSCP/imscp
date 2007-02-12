<?php
/**
 * gpg_config.php -- GPG Configuration file.
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Copyright (c) 2002-2003 Braverock Ventures
 *
 * @package gpg
 * @author Brian Peterson
 *
 * $Id: gpg_config.php,v 1.26 2004/01/05 10:29:10 brian Exp $
 */

// Report all errors except E_NOTICE
// This is the default value set in php.ini
error_reporting(E_ALL ^ E_NOTICE);

/**
 * Load some necessary stuff from squirrelmail.
 *
 * In SM 1.2.x, validate.php and load_prefs.php are in the
 * /src directory.  In 1.4.x, they are in the include directory
 * check to see what version we are running before loading.
 *
 * Also, in SM 1.4, we need to define constant SM_PATH.
 *
 */
/* include what we need */
if (!defined (SM_PATH)){
    if (file_exists('./gpg_functions.php')){
        define (SM_PATH , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define (SM_PATH , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define (SM_PATH , '../');
    } else echo "unable to define SM_PATH in gpg_config.php, exiting abnormally";
}

require_once(SM_PATH.'plugins/gpg/gpg_pref_functions.php');
require_once(SM_PATH.'functions/strings.php');

global $version; //needed for encrypt on send
/* check for SM versions greater than 1.3 */
if (substr($version, 2,4) >= 3.1) {
    require_once(SM_PATH.'include/validate.php');
    require_once(SM_PATH.'include/load_prefs.php');
    require_once(SM_PATH.'functions/prefs.php');
    require_once(SM_PATH.'functions/page_header.php');
    require_once(SM_PATH.'functions/addressbook.php');
    require_once(SM_PATH.'functions/mime.php');
    require_once(SM_PATH.'class/mime/Rfc822Header.class.php');
} else {
    chdir ('../');
    require_once('../src/validate.php');
    require_once('../src/load_prefs.php');
    require_once('../functions/prefs.php');
    require_once('../functions/page_header.php');
    require_once('../functions/smtp.php');
};


$GLOBALS['GPG_SYSTEM_OPTIONS'][$matches[1]]= "";

/* Just for poor wretched souls with E_ALL. :) */
global $data_dir;
global $username;
global $safe_data_dir;
$safe_data_dir = getHashedDir($username, $data_dir) . DIRECTORY_SEPARATOR;
if ($debug) {
    echo "<br>gpg_config.php debug: $safe_data_dir <br>";
};

if ( !check_php_version(4,1) ) {
    global $_SESSION;
}

$username = $_SESSION['username'];

// this stuff should get loaded with the load_prefs.php file, but it isn't,
// so we hack it here so our colors are correct.
// only hack for newer SM versions. older ones work fine
if (substr($version, 2,4) >= 3.1) {
   $chosen_theme = getPref($data_dir, $username, 'chosen_theme');
   $chosen_theme = preg_replace("/(\.\.\/){1,}/", SM_PATH, $chosen_theme);
   if (isset($chosen_theme) && (file_exists($chosen_theme))) {
      @include_once($chosen_theme);
   }
}
// end color hack

/**
 * set $debug to zero so we don't get junk on every page
 * edit this manually if you think you aren't loading
 * the system preferences files
 */
$debug=0;

load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);

/*
if (file_exists('gpg/gpg_system_defaults.txt')){
    if ($debug) { echo '<br>assuming we are in plugins, loading from gpg/<br>';};
    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
    load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);
} elseif (file_exists('gpg_system_defaults.txt')){
    if ($debug) { echo '<br>assuming we are in plugins/gpg, loading from ./<br>';};
    load_prefs_from_file(SM_PATH.'plugins/gpg_system_defaults.txt',$debug);
    load_prefs_from_file(SM_PATH.'plugins/gpg_local_prefs.txt',$debug);
} else {
    echo '<br> GPG Preference Path unknown, halting irregularly.<br>';
    exit;
};
*/

//set the debug level from the globals we just loaded
$debug=$GLOBALS['GPG_SYSTEM_OPTIONS']['debug'];

//set the directory that gpg should use as the --homedir
$gpg_key_dir ="$safe_data_dir$username.gnupg";

//set our umask to 077 so that we create files only owned and readable by apache
umask (077);

if (!is_dir($gpg_key_dir)){
    mkdir ($gpg_key_dir, 01700);
};

/*Set this because it might be useful */

$GPG_SOUP_NAZI = 'Mozilla/3, Mozilla/2, Opera 4, Opera/4, '
   . 'Macintosh, OmniWeb';

/**
 *
 * $Log: gpg_config.php,v $
 * Revision 1.26  2004/01/05 10:29:10  brian
 * added error_reporting directive to eliminate E_NOTICE
 *
 * Revision 1.25  2003/11/25 01:50:27  ke
 * -replaced a / with DIRECTORY_SEPARATOR for cross-compatibility
 * -changed safe_data_dir in getPref back to data_dir
 *
 * Revision 1.24  2003/11/24 20:10:13  ke
 * -added / to end of safe_data_dir
 *
 * Revision 1.23  2003/11/24 19:57:30  ke
 * -set config to use safe_data_dir with getHashedDir
 * bug 116
 *
 * Revision 1.22  2003/11/04 21:38:40  brian
 * change to use SM_PATH
 *
 * Revision 1.21  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.20  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.19  2003/05/13 22:55:41  brian
 * changed file include lines so it would work from hook in compose.php
 * Bug 26
 *
 * Revision 1.18  2003/04/04 04:35:10  brian
 * updated color hack to only execute on SM 1.4 - not needed on earlier versions
 *
 * Revision 1.17  2003/04/04 04:31:13  brian
 * hack color support using chosen_theme from load_prefs
 *
 * Revision 1.16  2003/04/03 20:41:04  brian
 * added mime.php to the include list for SM 1.4 - needed by released SM 1.4.0
 *
 * Revision 1.15  2003/04/01 16:21:04  brian
 * moved SM_PATH declaration to be before the include of strings.php
 *  so SM 1.4 doesn't complain.
 *
 * Revision 1.14  2003/03/25 16:48:56  brian
 * Bug 6
 * Add include for addressbook.php to SM 1.4 parsing code
 *
 * Revision 1.13  2003/03/17 18:57:04  brian
 * - progress towards SM v >=1.3.1 compatibility
 * - path selection for includes now works on both
 *   SM 1.2.x and SM >= 1.3.1
 *
 * Revision 1.12  2003/03/15 22:04:11  brian
 * moved strings.php include to outside of the SM version check
 * strings.php sets the SM version...
 *
 * Revision 1.11  2003/03/15 21:12:38  brian
 * added include for Rfc822Header.class.php in the v 1.4 include code
 *
 * Revision 1.10  2003/03/11 19:23:11  tyler
 * - Modified to use the new gpg_pref_functions.php file
 *
 * Revision 1.9  2003/03/07 17:02:23  brian
 * consolidated includes for the plugin in gpg_config.php
 * added test for SM > 1.3, and changed include syntax accordingly
 *
 * Revision 1.8  2003/01/03 22:29:36  brian
 * changed name of gpg_prefs file to gpg_local_prefs
 *
 * Revision 1.7  2002/12/11 00:16:10  brian
 * changed umask and mkdir to create directories and files that are rwx------
 * to get rid of unsafe permissions errors
 *
 * Revision 1.6  2002/12/10 14:28:14  brian
 * edited to set global \$debug from system prefs files
 *
 * Revision 1.5  2002/12/10 03:52:05  brian
 * added function to establish $data_dir/$username.gnupg as homedir for gpg functions
 *
 * Revision 1.4  2002/12/09 02:02:30  brian
 * added function header and more coments to new functions
 *
 * Revision 1.3  2002/12/09 01:55:19  brian
 * Vinay added functions to load gpg plugin defaults and per-system configs
 *
 */
?>
