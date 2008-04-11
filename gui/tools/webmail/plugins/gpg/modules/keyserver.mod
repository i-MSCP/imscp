<?php
/**
 * keyserver.mod
 * ----------------
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Key Server lookup screen
 *
 * Unfortunately, we use HTTP screen scraping to look up keys.
 * Ideally, we would use HKS or LDAP to look up keys.
 *
 * $Id: keyserver.mod,v 1.28 2006/01/08 02:47:20 ke Exp $
 *
 * $Author: ke $
 * $Date: 2006/01/08 02:47:20 $
 *
 * @todo - Fix includes to use SM_PATH
 * @todo - Add support for HKS or LDAP keyservers.
 *       Ref: http://www.braverock.com/bugzilla/show_bug.cgi?id=43
 */

//make the global $debug setting available here
global $debug;

if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in genkey.php, exiting abnormally";
}
require_once(SM_PATH.'plugins/gpg/gpg_key_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_execute.php');

//extract necessary variables from the $_POST
if (array_key_exists("search", $_POST)) {
    $search    = $_POST['search'];
} else { $search = 0; }

if (array_key_exists("keyID", $_POST)) {
    $keyID     = $_POST['keyID'];
} else { $keyID = 0; }

if (!$search and !$keyID) {
    $id=0;

    gpg_page_title ( _("Keyserver Options"));

    echo  '<p>'
      . '<FORM METHOD="POST">'
      . '<input type="hidden" name="MAX_FILE_SIZE" value="100000">'
      . '<input type="hidden" name="MOD" value="keyserver" >'
      . _("Choose your Default Keyserver")
      . '<select name=keyserver>';

        $defaultkeyserver = getPref($data_dir,$username,'keyserver');
        if ($defaultkeyserver)
        echo "<option value=$defaultkeyserver>$defaultkeyserver</option>\n";

        for ($i = 0; $i < $GLOBALS['GPG_SYSTEM_OPTIONS']['keyservers']; $i++) {
                $keyserver = $GLOBALS['GPG_SYSTEM_OPTIONS']['keyserver' . $i];
            echo "<option value=$keyserver>$keyserver</option>\n";
        };

    echo '</select>'
      . '<input type=checkbox name=default value=1>'
      . _("Make this my default keyserver")
      . '<hr>'
      . _("Enter Search text below to search for a key to import")
      . '<br>'
      . _("Search text may be an email address or an OpenPGP(PGP/GPG) Key ID")
      . '<BR><INPUT TYPE=TEXT NAME="search_keyid" size=60>'
      . '<input type=hidden name=search value=1>'
      . '<br><input name=search type=submit value="'._("Search for Key").'">'
      . '</form>';
}

if ($search) {
    //extract necessary variables from the $_POST
    if (array_key_exists("default", $_POST)) {
        $default      = $_POST['default'];
    } else { $default = 0; }
    if (array_key_exists("keyserver", $_POST)) {
        $keyserver    = $_POST['keyserver'];
    } else { $keyserver = ''; }
    if (array_key_exists("search_keyid", $_POST)) {
        $search_keyid = $_POST['search_keyid'];
    } else { $search_keyid = ''; }

    gpg_page_title ( _("Choose a Key to Import"));

    if ($default) {
        setPref($data_dir,$username,'keyserver',$keyserver);
        echo _("Default Keyserver set to:")." $keyserver";
    };

    if(!$keyserver) {
	$keyserver = getPref($data_dir,$username,'keyserver', $GLOBALS['GPG_SYSTEM_OPTIONS']['keyserver0']);
    }

    $default_keyserver = getPref($data_dir,$username,'keyserver',$keyserver);
    if ($debug) {
        echo _("Using Keyserver: ")."$keyserver<br>";
    };
    echo '<p>'
       . '<input type="hidden" name="MAX_FILE_SIZE" value="100000">'
       . '<input type="hidden" name="MOD" value="keyserver" >'
       . '<br>'
       . _("Default Keyserver: ")
       . "$default_keyserver"
       . '<br>'
       . _("Using Keyserver: ")
       . "$keyserver<hr>";

    if ($search_keyid!="") {
        $foundkeys = array();
        $foundkeys = gpg_keyserver_findkey($search_keyid,$debug);
        //ob_start();
        echo
              '<FORM METHOD="POST" action="modules/keyring_main.php">'
	    . '<input type="hidden" name="keyaction" value="recvKey">'
	    . '<input type="hidden" name="selectKey" value="true">'
            . "<input type='hidden' name='keyserver' value='$keyserver'>"
            . '<table border=0> ';

        if (is_array($foundkeys)) {
            echo  "\n<p>"
                . _("Select a key to import from the list below.")
                . '<br>'
                . _("If you do not see the key you wish to import, you may go back and enter different search terms or select a different keyserver.")
                . '</p>';
            //set up the table headers
            echo
                  "\n<tr>"
                . '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'. _("Key ID") .'</b></td>'
                . '<td><b>'. _("Name") .'</b></td>'
                . '<td><b>'. _("Email Address") .'</b></td>'
                . '<td><b>'. _("Key Date") .'</b></td>'
                . '</tr>';
            foreach ($foundkeys as $key) {
                echo
                  "\n<tr>"
                . '<td><input type=radio name=keyID value="'. $key['lastkey'] . '">'. $key['lastkey']. '&nbsp;&nbsp;</td>'
                . '<td>'. $key['tmpname'] . '&nbsp;&nbsp;</td>'
                . '<td>'. $key['tmpemail'] . '&nbsp;&nbsp;</td>'
                . '<td>'. $key['lastdate']. '&nbsp;&nbsp;</td>'
                . '</tr>';
            }; //end foreach
            echo '</table>'
                . '<input  name=loadkey type=submit value="'._("Import Selected Key").'">';
        } else {
            // if we didn't get an array, $foundkeys should have an error string
            echo '<tr><td>'
                . $foundkeys
                . '</td></tr>';
        };

        echo "</form>";
        //echo ob_get_contents();
        //ob_end_clean();
    };
}; //end search

if ($keyID) {
  $keyserver    = $_POST['keyserver'];

  gpg_page_title( _("Import Key"));

  if ($debug){
     echo _("Using Keyserver: ").$keyserver;
  };
  echo '<br>'._("Importing key with KeyID: ") . $keyID;
  $gpg=initGnuPG();
  $return = $gpg->importKey_server($keyID,$keyserver);
  foreach($return['info'] as $line) {
    echo "<BR>" . htmlspecialchars($line);
  }
}; //end import key

/**
 *
 * $Log: keyserver.mod,v $
 * Revision 1.28  2006/01/08 02:47:20  ke
 * - committed patch from Evan <umul@riseup.net> for OpenPGP header support in squirrelmail
 * - adds system preferences and user options to control parsing and adding of OpenPGP Headers on emails
 * - slightly tweaked to use the key associated with the identity, when identities with signing keys are enabled
 *
 * Revision 1.27  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.26  2004/08/23 09:24:52  ke
 * -changed target of final submission of keyserver request back to keyring_main.php
 * -also added ability to use GnuPG object directly from keyserver.mod, but never gets there because of
 * above redirect
 *
 * Revision 1.25  2004/01/19 18:36:05  brian
 * E_ALL fixes
 *
 * Revision 1.24  2004/01/19 18:27:55  brian
 * E_ALL fixes
 *
 * Revision 1.23  2004/01/15 18:27:37  ke
 * -added include for gpg_execute
 * -added output of $return['info'] for key import
 *
 * Revision 1.22  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.21  2003/12/30 19:05:16  ke
 * -changed single to double quotes for translation purposes
 *
 * Revision 1.20  2003/12/29 23:54:16  brian
 * localized strings discovered by Alex Lemaresquier during French translation
 *
 * Revision 1.19  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.18  2003/11/01 21:47:00  brian
 * Multiple Changes:
 * - removed $msg strings and gpg_Makepage functionality
 * - fixed multiple small bugs
 * - localized remaining strings
 * - improved formatting
 *
 * Revision 1.17  2003/10/11 20:32:45  brian
 * check for error string in return from gpg_keyserver_findkey fn
 * Bug 80
 *
 * Revision 1.16  2003/07/01 06:21:46  vermette
 * adding escape routes to options suite.  The previous 'back' link now only appears if requested (new arg to makePage).  This isn't done by any means, but at most it's as broken as it was, so it's an improvement.
 *
 * Revision 1.15  2003/06/27 21:18:22  brian
 * move keyserver extract to top of file so it will actually work
 *
 * Revision 1.14  2003/04/07 22:21:59  brian
 * modified files to not use extract $_POST
 * Bug 5
 *
 * Revision 1.13  2003/04/02 20:48:39  brian
 * fixed spelling errors using aspell
 * TODO - check grammer and sentence structure manually
 * Bug 18
 *
 * Revision 1.12  2003/04/01 18:04:06  brian
 * fixed includes to reflect different calling directories
 *
 * Revision 1.11  2003/03/12 22:14:12  tyler
 * - default keyserver now only used to pre-pick your preferred keyserver
 * - you can pick a different keyserver to search on the fly
 *
 * Revision 1.10  2003/03/12 21:59:26  tyler
 * - Fixed a bug where you could not have a default keyserver set yet still
 * - be able to search, but results would be unusable on later screens for
 * - input. Now you can search without a default and still import.
 *
 * Revision 1.9  2003/03/12 02:01:18  tyler
 * - $key looks to be set as a global so changed it to $keyID
 *
 * Revision 1.8  2003/03/11 23:29:15  tyler
 * - modified to use the new broken out *_function.php files
 *
 * Revision 1.7  2003/03/07 15:54:40  brian
 * Removed enctype=multipart/form-data - not needed for this form
 *
 * Revision 1.6  2003/03/07 00:10:39  brian
 * Removed superfluous includes.
 *
 * Revision 1.5  2002/12/10 21:52:27  vinay
 * now can fetch keys from keyservers
 *
 * Revision 1.4  2002/12/10 14:33:37  brian
 * edited to use global \$debug, rather than a local code
 *
 * Revision 1.3  2002/12/09 03:48:59  vinay
 * added support for system preferences
 *
 * Revision 1.2  2002/12/06 22:32:13  vinay
 * initial revision +1
 *
 * Revision 1.1  2002/12/06 22:30:20  vinay
 * initial revision
 *
 *
 *
 */

?>
