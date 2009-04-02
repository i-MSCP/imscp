<?php
/**
 * gpg_pref_functions.php
 * -----------
 * GPG plugin preference functions file, as defined by the SquirrelMail-1.2 API.
 * Updated for the SM 1.3/1,4 API
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Vinay
 * @author Brian Peterson
 *
 * $Id$
 *
 */

/*********************************************************************/
/**
 * function load_prefs_from_file
 *
 * This function is called on load to set up the system wide options
 *
 * TO GET AT THESE PREFERENCES:
 *
 * $GLOBALS['GPG_SYSTEM_OPTIONS']['key_you_want']
 *
 * or global $GPG_SYSTEM_OPTIONS
 *
 * then $GPG_SYSTEM_OPTIONS['key_you_want']
 *
 * foreach ($GLOBALS['GPG_SYSTEM_OPTIONS'] as $key => $value)
 *     echo "<br>J $key = $value";
 *
 * @param string   $filename    String with complete path to pref file.
 * @param booolean $debug 0|1
 * @return void (global array set)
 */

function load_prefs_from_file($filename,$debug) {

  // eventually, add the getHashedFile security checks here
  // $filename=getHashedFile($username, $data_dir, $filename);
  // check to see if we are running windows, add LANG variables
  // if we are not running windows

  global $data_dir;
  if (file_exists($filename)){
      $fd = fopen ($filename, "r");
      $contents = fread ($fd, filesize ($filename));
      fclose ($fd);
      $lines = explode ("\n", $contents);
      foreach ($lines as $line) {
          if (substr($line,0,1) == '#') continue;
	  $matches=array();
          eregi("^([[:alnum:]|_]+)=(.*)", $line, $matches);
          $GLOBALS['GPG_SYSTEM_OPTIONS'][trim($matches[1])] = trim($matches[2]);
          if (trim($matches[1])=='systemkeyringfile') {
              //store the full path to the system keyring file, rather than the path relative to the data dir
              $GLOBALS['GPG_SYSTEM_OPTIONS'][trim($matches[1])] = realpath($data_dir . $matches[2]);
          }
          if ($debug) {
              echo "<br>global preferences '$line' gives (", $matches[1], '=', $matches[2], ")";
          };
      }; //end foreach
  } else echo _("No GPG Plugin preferences could be found");
} //end load_prefs_from_file fn

function list_secret_keys($srch='') {
    $ring = initGnuPG();
    $ret = $ring->fetchKeys($srch, 'secret');
    $keys=$ring->keys;
    $return=array();
    foreach ($keys as $fpr => $key) {
	$return[$fpr]=$key->get_email_name() . " <" . $key->get_email_addr() . ">";
    }
    return $return;
}

/*********************************************************************/

/**
 * GPG version. Don't modify, since it identifies the format
 * of the user files and messing with this can do ugly
 * stuff. :)
 */
global $GPG_VERSION;
$GPG_VERSION='2.1';

/**
 * Function for Plugin Updates Compatibility
 *
 * @param void
 * @return $GPG_VERSION
 */
function gpg_version() {
    return ('2.1');
}

/*********************************************************************/
/**
 *
 * $Log: gpg_pref_functions.php,v $
 * Revision 1.28  2006/08/18 21:31:32  brian
 * - set plugin version to 2.1, we're not ready for 3.0
 *
 * Revision 1.27  2005/12/21 02:57:43  ke
 * - removing deprecated environmental variable addition to the path_to_gpg variable, now handleded in gpg.php directly
 * Bug 179
 *
 * Revision 1.26  2005/12/20 21:21:56  ke
 * - fix for safe mode execution of the gpg binary
 *
 * Revision 1.25  2005/11/21 21:02:50  brian
 * - update version to 3.0
 *
 * Revision 1.24  2005/10/09 03:49:18  ke
 * - added function to list secret keys for a user, in a format useful for attaching private keys to identities
 *
 * Revision 1.23  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.22  2004/08/23 07:27:19  ke
 * define matches before using, to fix warning messages
 * bug 177
 *
 * Revision 1.21  2004/08/09 17:57:23  ke
 * -added global definition of GPG_VERSION to allow it to appear everywhere (keyring)
 *
 * Revision 1.20  2004/03/10 21:41:33  brian
 * - removed trailing whitespace
 *
 * Revision 1.19  2004/01/24 17:56:18  brian
 * changed working version to 2.1
 *
 * Revision 1.18  2004/01/09 19:01:47  brian
 * improved indentation and error message on load_prefs_from_file fn
 *
 * Revision 1.17  2004/01/08 04:57:51  brian
 * changed version to 2.0
 *
 *
 * Revision 1.13  2003/12/29 18:12:43  ke
 * -added OS check when adding LANG shell commands to $path_to_gpg variable
 * -hopefully fixes bug reported by Mr. Potato Head
 * bug 140
 *
 * Revision 1.12  2003/12/18 19:47:08  ke
 * -added the full path of the data dir to the beginning of the systemkeyringfile variable, so that relative paths do not break the system keyring functionality
 *
 * Revision 1.11  2003/11/25 18:37:42  ke
 * -changed version from 1.2 to 2.0rc1
 *
 * Revision 1.10  2003/11/22 15:56:03  brian
 * changed version to 2.0rc1
 *
 * Revision 1.9  2003/11/12 23:42:33  ke
 * -forced language to english as part of shell command for gpg
 *
 * Revision 1.8  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.7  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.6  2003/10/13 22:44:31  ke
 * -now load_prefs ignores lines with a # at the beginning
 * -trims whitespace from option names and values
 * bug 86
 *
 * Revision 1.5  2003/06/09 22:08:44  brian
 * - updated version to 1.2 (really this time)
 *
 * Revision 1.4  2003/06/09 22:07:41  brian
 * - updated version to 1.2
 * - added plugin updates compatibility function gpg_version
 *
 * Revision 1.3  2003/04/09 18:04:15  brian
 * moved $GPG_VERSION and fn gpg_version to gpg_pref_functions.php file
 *
 * Revision 1.2  2003/03/12 15:43:50  brian
 * added header and footer blocks to file created by tyler
 *
 *
 */
?>
