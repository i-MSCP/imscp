<?php
/**
 * gpg_key_functions.php
 * -----------
 * GPG plugin functions file, as defined by the SquirrelMail-1.2 API.
 * Updated for the SM 1.3/1,4 API
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Brian Peterson
 * @author Aaron van Meerten
 *
 * $Id$
 *
 */
/*********************************************************************/

/*********************************************************************/
/**
 * function gpg_list_keys
 * This function is the generic key lister for the plugin
 * it is used for trusted_key operations, as well as key signing
 *
 *
 * Add switches to this function to modify the gpg command line
 *
 * The format of the --with-colons parameter is described in detail in
 * the file named DETAILS in the gpg distribution.
 *
 * @param integer $debug
 * @param string $search_string
 * @param optional boolean $with_colons
 * @param optional enum $keyring_type
 * @param optional boolean $with_fingerprint
 * @return string $msg with output, parse string on the UI.
 *
 * Note the departure from the convention of putting the $debug flag last:
 * $debug is first in this function to allow easy adding of additional switches
 * without breaking things.
 */
function gpg_list_keys($debug, $search_string, $with_colons='false', $keyring_type='public', $with_fingerprint='false'){

  if ($debug) {
      echo "Debug: $debug<br>";
      echo "Search String: $search_string<br>";
      echo "With Colons: $with_colons<br>";
      echo "Keyring Type: $keyring_type<br>";
      echo "With fingerprints: $with_fingerprint<br>";
  }
  /**
   * The long key id is retrieved by using
   * gpg --list-keys --with-colons
   * pub:-:1024:17:257F139F72E1465E:2001-09-27:::-:Brian G. Peterson <brian@braverock.com>::scESC:
   */

  global $gpg_key_file;
  global $gpg_key_dir;
  global $path_to_gpg;
  global $data_dir;
  global $username;
  global $safe_data_dir;
  $safe_data_dir=getHashedDir($username,$data_dir) . DIRECTORY_SEPARATOR;
  $params = "--homedir $gpg_key_dir ";

  /**
   * We will us the $keyring_type parameter to determine
   * what to add the the $command string.
   *
   * Legal Values are 'public', 'private', 'secret', and 'all'
   *
   * From the gpg man page
   *  --list-keys [names]
   *
   *  --list-public-keys [names]
   *        List  all  keys  from  the  public keyrings, or just the ones
   *        given on the command line.
   *
   *  --list-secret-keys [names]
   *        List all keys from the secret  keyrings,  or  just  the  ones
   *        given on the command line.
   */
  switch ($keyring_type) {
    case 'sigs':
        $params .= '--list-sigs';
        break;
    case 'all':
        $params .= '--list-keys ';
        break;
    case 'public':
        $params .= '--list-public-keys ';
        break;
    case 'private':
        $params .= '--list-secret-keys ';
        break;
    case 'secret':
        $params .= '--list-secret-keys ';
        break;
    case 'system':
        {
        $systemkeyring = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'];
        if ($systemkeyring=='true') {
            $system_keyring_file = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
            if ($debug) {
                echo '<br>system_keyring_file = '.$system_keyring_file."<br>\n";
            };
            if (is_file($system_keyring_file)) {
                $system_keyring_file = escapeshellarg($system_keyring_file);
                $params .= " --keyring $system_keyring_file ";
            };
        };
        $params .= ' --list-keys ';
        break;
        }
    case '':
        echo '<br><br><font color=red>'
           . _("Unexpected Error.")
           . '&nbsp;'
           . _("Did not receive valid keyring_type in gpg_list_keys function.")
           . _("Received")."&nbsp;'$keyring_type'&nbsp;"._("Please Notify GPG Plugin Developers")."</font>\n";
        $params .= '--list-public-keys ';
        break;
  };

  if ($with_colons == 'true') {
      $params .= '--with-colons ';
  };

  if ($with_fingerprint == 'true') {
      $params .= '--with-fingerprint ';
  };

  if ($search_string != '') {
      $search_string = escapeshellarg($search_string);
      $params .= "$search_string";
  }

  $return=gpg_execute($debug,$params);
  if ($debug) { print_r($return); }
  $list_text=$return['output'];
  if (!is_array($list_text)) {
	$list_text=explode("\n",$list_text);
	if ($debug) { print_r($list_text); }
  }
  
  //clean this up later, but for now just return the array
  return $list_text;

};

/*********************************************************************/
/**
 * function gpg_format_key_list
 * This function takes the output provided by gpg_list_keys and takes only
 * those keys that a person can use and displays them for the user to pick from
 *
 * TODO Remove 'activity' switch, and devolve code to calling pages
 *
 * @param string $search_string
 * @param string $msg
 * @param boolean $debug
 * @param optional enum $activity
 * @param boolean $radio
 * @param boolean $with_fingerprint
 * @return string with output containing HTML table+form bits
 */

function gpg_format_key_list ($debug, $search_string, $activity='public', $radio='true'){
  //first decide what the calling function wants
  switch ($activity) {
    case 'all':
        $keyring_type = 'all';
        $matchstring .= "pub:|sub:|uid:|sec:|ssb:";
        break;
    case 'secret':
        $keyring_type = 'private';
        $matchstring .= "sec:|ssb:";
        break;
    case 'private':
        $keyring_type = 'private';
        $matchstring .= "sec:|ssb:";
        break;
    case 'public':
        $keyring_type = 'public';
        $matchstring .= "pub:|sub:|uid:";
        break;
    case 'sign':
        $signing_key_id = getPref ($data_dir, $username, 'signing_key_id');
        $matchstring .= 'sec:';
        $keyring_type = 'private';
        break;
    case 'trust':
        $trusted_key_id = getPref ($data_dir, $username, 'trusted_key_id');
        $matchstring .= 'pub:';
        $keyring_type = 'public';
        break;
    case 'system':
        $keyring_type = 'system';
        $matchstring .= "pub:|sub:|uid:|sec:|ssb:";
        break;
    case '':
        $keyring_type = 'public';
        $matchstring .= "pub:|sub:|uid:";
        break;
  };

   if ($debug) {
       $msg .= "\n<br>Matchstring: $matchstring\n"
            . "<br>Activity Requested: $activity\n"
            . "<br>Keyring Type passed to gpg_list_keys: $keyring_type \n";
   }

   //now display our table header
   $msg .= "\n<table border=1>"
        . '<tr>';
   if ($radio=='true') {
       $msg .= ' <td></td>';
   }
   $msg .=
          ' <td align=center><b>Type</b></td>'
        . ' <td align=center><b>Key Length </b></td>'
        . ' <td align=center><b>Key ID</b></td>'
        . ' <td nowrap align=center><b>Key Date</b></td>'
        . ' <td align=center><b>Algorithm</b></td>'
        . ' <td align=center><b>Name</b></td>'
        . "</tr>\n";

   //now retrieve all the keys of the requested type (public|private)
     $list_text = '';
     $list_text = gpg_list_keys($debug, $search_string, 'true', $keyring_type, $with_fingerprint);
     if ($debug) {
       $msg .= "Type: $type<br>";
       $debug_list_text = implode($list_text,"\n");
       $msg .= "<textarea cols=80 rows=25 name=list_text>$debug_list_text</textarea>";
     }

     foreach ($list_text as $line) {
      $checked = '';
      $doit = 0;
      $signable = 0;
      $sec = 0;
      $bits = explode (":", $line);
      $key_type = $bits[0];
      $key_len = $bits[2];
      $key_alg = $bits[3];
      $key_id = $bits[4];
      $key_date = $bits[5];
      $matches=array();
      eregi(".*(<.*>).*", $bits[9], $matches);
      $email_str = htmlspecialchars($bits[9]);
      $email_addr = htmlspecialchars($matches[1]);

      /* Algorithm
       *  1 = RSA (sign and encrypt)
       * 16 = ElGamal (encrypt only)
       * 17 = DSA (sometimes called DH, sign only)
       * 20 = ElGamal (sign and encrypt)
       */
      switch ($key_alg) {
         case 1:
            $key_alg_str = 'RSA';
            $signable=1;
            break;
         case 16:
            $key_alg_str = 'ElGamal (encrypt only)';
            break;
         case 17:
            $key_alg_str = 'DSA/DH';
            $signable=1;
            break;
         case 20:
            $key_alg_str = 'ElGamal (sign and encrypt)';
            $signable=1;
            break;
      };


      /*
       * Figure out if we need to print
       */
      switch ($activity) {
         case 'sign':
            if ($signable) {
             if ($key_id == $signing_key_id) {
                $checked='checked';
             };
             $doit = 1;
            };
            break;
         case 'trust':
            if ($signable) {
             if ($key_id == $trusted_key_id) {
                $checked='checked';
             };
             $doit = 1;
            };
            break;
         case 'all':
            $doit = 1;
            break;
         case 'secret':
            $doit = 1;
            break;
         case 'private':
            $doit = 1;
            break;
         case 'public':
            $doit = 1;
            break;
         case 'system':
            $doit = 1;
            break;

       };

       if (($doit) and ($key_id != "")) {

         $msg .=   "<tr>";
         if ($radio == 'true') {
               $msg .= " <td><input type=radio $checked name=key_id value=$key_id></td>";
            }
            $msg .=
                " <td align=center>$key_type</td>"
              . " <td align=center>$key_len</td>"
              . " <td align=center>$key_id</td>"
              . " <td nowrap align=center>$key_date</td>"
              . " <td align=center>$key_alg_str</td>"
              . " <td align=center>$email_str</td>"
              . "</tr>\n";
       };

     }; //end inside foreach line processing
     if ($debug) {
      switch ($activity) {
         case 'all':
             break;
         case 'sign':
            $msg .= '<p>'
              . _("Using signing key:")
              . '&nbsp;'
              . getPref ($data_dir, $username, 'use_signing_key_id')
              .'<br>'
              . _("Your current signing key: ")
              . '&nbsp;'
              . $signing_key_id
              . '<br>'
              . _("Key Details:")
              . '<br><br>';
            break;
         case 'trust':
            $msg .= '<p>'
              . _("Using trusted key:")
              . '&nbsp;'
              . getPref ($data_dir, $username, 'use_trusted_key_id')
              . '<br>'
              . _("You currently trust key:")
              . '&nbsp;'
              . $key_id
              . '<br>'
              . _("Key Details:")
              . '<br><br>';
            break;
      };
      $msg .= "\n<br>";
     };

   $msg .= '</table>';

   if ($activity =='trust') {trustedkeysearchtext ();}; //display the trusted key text

   return $msg;
};

/*********************************************************************/
/**
 * function trustedkeysearchtext
 *
 * This function sets the search text for the trusted key screen
 *
 * @return $msg
 */
function trustedkeysearchtext () {
    global $msg; //hope this works
    $msg .=
       '<br>'
       . _("If you do not see the key you want to trust in the list above, or if there is no list:")
       . '&nbsp;'
       . _("Enter a search string for the key you would like to trust in the box below.")
       . '&nbsp;'
       . _("This will usually be the email address of the account that you use as your primary signing key.")
       . '<br>'
       . _("Search String:")
       . '&nbsp;'
       . "<input TYPE='text' NAME='key_search_string' SIZE='40' MAXLENGTH='60' VALUE='$search_string'><br>\n"
       . _("Search my keyring for a new trusted key using the search string above:")
       . "&nbsp<input type=checkbox name=searchforkey value='true'><br>\n";
}

/*********************************************************************/
/**
 * function gpg_keyserver_findkey
 *
 * This function will search for keys on a public keyserver
 *
 * Ideally, we would use a command like:
 * gpg --keyserver wwwkeys.pgp.net --search-keys brian@braverock.com
 *
 * BUT: this command is interactive, expecting input, and I can't
 * find anything about using it in batch mode. if you try --batch
 * you get : gpg: Sorry, we are in batchmode - can't get input
 * so we would have to use a cycle to get the output and parse it
 * then use the gpg_recv_key function
 *
 * SO: until we figure out how to parse the HKP output,
 *     or use the LDAP interface
 *
 * We will first take an email address or keyid $search_keyid
 * and look on the keyserver using http, like this:
 *
 * http://pgp.mit.edu:11371/pks/lookup?op=index&search=$search_keyid
 * http://stinkfoot.org:11371/pks/lookup?op=index&search=$search_keyid
 * http://wwwkeys.pgp.net:11371/pks/lookup?op=index&search=$search_keyid
 * http://wwwkeys.eu.pgp.net:11371/pks/lookup?op=index&search=$search_keyid
 *
 * Then, we will parse the output, and place it in an array
 * for display and selection.
 *
 * Information on public keyservers may be found at
 *
 * http://www.vcnet.com/~rossde/pgp_keyserv.html
 *
 * Finally, we will return an array of the key(s) to the interface
 * so the user may select one or more for import.
 *
 * It would be nice to use the HKP or LDAP interfaces too,
 * but this will work for now.
 *
 * @param string  $search_keyid
 * @param integer $debug
 * @return array $returnkeys
 */
function gpg_keyserver_findkey($search_keyid,$debug) {

    //pull in globals to avoid E_ALL errors
    global $data_dir;
    global $username;

    $keyserver = "";
    // get the user's prefered keyserver
    $keyserver = getPref($data_dir,$username,'keyserver');

    // set a default keyserver if we don't pass one in
    if (!$keyserver) {
        $keyserver="pgp.mit.edu:11371";
    };

    //process the search_keyid, hack this for now
    //eventaully, break search_keyid into multiple searches.


    //start our output formatting
    $f = fopen("http://" . escapeshellcmd($keyserver) . "/pks/lookup?op=index&search=" . urlencode($search_keyid), "r");
    //verify that we have a valid file handle and return an error if we don't
    if (!$f) {
        $errstr =
              _("Unable to connect to the keyserver you specified.")
            . '&nbsp;'
            . _("Please try a different keyserver or contact your system administrator.");

        return ($errstr);
    }


    if ($debug) {
        echo "http://" . escapeshellcmd($keyserver) . "/pks/lookup?op=index&search=" . urlencode($search_keyid);
    };
        $lastkey = "";
    // The first 4 lines contain header info, skip these
        fgets($f,512);
        fgets($f,512);
        fgets($f,512);
        fgets($f,512);

        $returnkeys = array();
    $ret = array();

    while($line=fgetss($f,512)) {
        $line=str_replace("&gt;",">",$line);
        $line=str_replace("&lt;","<",$line);
        $line=str_replace("&amp;",'"',$line);
        $line=str_replace("&quot;","&",$line);

        if (ereg("^pub[[:space:]]+([[:digit:]]+[R|D])/([[:alnum:]]+)[[:space:]]+([[:digit:]]+)/([[:digit:]]+)/([[:digit:]]+)[[:space:]]+(.*)", $line, $tmp)) {
        // foreach ($tmp as $key => $value) echo "<br>regex $key = $value";
            $lastkey = $tmp[2];
            $lastdate = $tmp[3] . "-" . $tmp[4] . "-" . $tmp[5];
            $line = $tmp[6]; # fool myself

        }
        if (ereg("(.+)<(.+@.+)>", $line, $t)) {
            $tmpemail = $t[2];
            $tmpname = $t[1];

            $ret['tmpname'] = $tmpname;
            $ret['tmpemail'] = $tmpemail;
            $ret['lastkey'] = $lastkey;
            $ret['lastdate'] = $lastdate;
            $returnkeys[] = $ret;
        }
    }

    if ($debug) {
        foreach ($returnkeys as $key) {
            echo "<br>-";
            foreach ($key as $field => $value) echo "<br>$field=$value";
        };
    };

    return ($returnkeys);

};

/*********************************************************************/
/**
 * function gpg_export_key
 * This function exports a GPG
 * ASCII Armored Keys
 *
 * @param string $keystring
 * @param integer $debug
 * @return string $key_import_output_str
 */
function gpg_export_key($debug, $keystring, $export_type='public'){

  //set the keyring file to be $username.gpgpubring
  $username = $_SESSION['username'];
  global $gpg_key_dir;
  global $path_to_gpg;
  global $data_dir;
  global $safe_data_dir;
  $safe_data_dir=getHashedDir($username,$data_dir). DIRECTORY_SEPARATOR;
  switch ($export_type) {
      case 'all':
          $exportstring = '--export-all ';
          break;
      case 'public':
          $exportstring = '--export ';
          break;
      case 'private':
          $exportstring = '--export-secret-keys ';
          break;
      case 'secret':
          $exportstring = '--export-secret-keys ';
          break;
      case 'system':
          {
          $systemkeyring = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'];
          if ($systemkeyring=='true') {
              $system_keyring_file = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
              if ($debug) {
                  echo '<br>system_keyring_file = '.$system_keyring_file."<br>\n";
              };
              if (is_file($system_keyring_file)) {
                  $system_keyring_file = escapeshellarg($system_keyring_file);
                  $exportstring = " --keyring $system_keyring_file ";
              };
          };
          $exportstring .= ' --export ';
          break;
          }
      case '':
          echo '<br><br><font color=red>'
           . _("Unexpected Error.")
           . '&nbsp;'
           . _("Did not receive valid export_type in gpg_export_key function.")
           . _("Received")."&nbsp;'$export_type'&nbsp;"._("Please Notify GPG Plugin Developers")."</font>\n";
          break;
  };

  if ($debug) {
      echo "<br>Username: $username";
      echo "<br>DataDir: $safe_data_dir";
  };

  // clean input
  // make sure there aren't any funny characters in here
  $keystring = escapeshellarg ($keystring);

  $params  = "--pgp7 --homedir $gpg_key_dir --armor $exportstring $keystring";

  $return=gpg_execute($debug,$params);
  $output=$return['output'];
  $returnval = $return['returnval'];

  // make the result a string
  if (is_array($output)) {
      $key_export_output_str = implode($output,"\n");
  } else { $key_export_output_str = $output; }

  if ($debug) {
      echo "<pre> $key_export_output_str </pre>\n";
      echo "returnvalue= $returnval";
      echo "end of gpg_export_key";
  };

  //return the output string to our calling function
  return ($key_export_output_str);
};

/*********************************************************************/
/**
 * function gpg_import_key
 * This function imports PGP or GPG
 * ASCII Armored Keys
 *
 * @param string $keystring
 * @param integer $debug
 * @return string $key_import_output_str
 */
function gpg_import_key($keystring,$debug){

  //set the keyring file to be $username.gpgpubring
  $username = $_SESSION['username'];
  global $gpg_key_dir;
  global $path_to_gpg;

  //$gpg_key_file="$username.gpgpubkeyring";

  if ($debug) {
      echo "<br>Username: $username";
      echo "<br>DataDir: $gpg_key_dir";
      echo "<br>Keyfile: $gpg_key_dir/$gpg_key_file";
  };

  // clean input
  // make sure there aren't any funny characters in here
  $keystring = escapeshellarg ($keystring);

  // import
  // the following line redirects the output to stderr: 2>&1
  // use --import
  //$command = "echo $keystring | $path_to_gpg --import --batch --no-tty --homedir $gpg_key_dir --no-default-keyring --keyring $safe_data_dir/$gpg_key_file 2>&1";

  $params  = "--allow-secret-key-import --import --homedir $gpg_key_dir";

  $return=gpg_execute($debug,$params,NULL,$keystring);
  $output=$return['output'];
  $returnval=$return['returnval'];

  // make the result a string
  if (is_array($output)) {
      $key_import_output_str = implode($output,"\n");
  } else { $key_import_output_str = $output; }

  if ($debug) {
      echo "$key_import_output_str";
      echo " returnvalue= $returnval";
      echo "end of gpg_import_key";
  };

  //return the output string to our calling function
  return ($key_import_output_str);
};

/*********************************************************************/
/**
 * function gpg_recv_key
 * This function imports PGP or GPG keys from a public keyserver
 *
 * @param string  $searchkeyid
 * @param integer $debug
 * @param string  $keyserver
 * @return $recv_key_output_str
 */
function gpg_recv_key($searchkeyid,$debug,$keyserver){


  //set the keyring file to be $username.gpgpubring
  $username = $_SESSION['username'];
  global $gpg_key_dir;
  global $path_to_gpg;

  //$gpg_key_file="$username.gpgpubkeyring";

  if ($debug) {
      echo "<br>Username: $username";
      echo "<br>DataDir: $gpg_key_dir";
      echo "<br>Keyfile: $gpg_key_dir/$gpg_key_file";
  };

  // clean input
  // make sure there aren't any funny characters in here
  $searchkeyid = escapeshellarg ($searchkeyid);
  if (!$keyserver) {
      $keyserver = getPref($data_dir,$username,'keyserver');
  }
  // import
  // the following line redirects the output to stderr: 2>&1
  // use --import

  $params  = "--homedir $gpg_key_dir --keyserver hkp://$keyserver --recv-key $searchkeyid";

  $return=gpg_execute($debug,$params);
  $output=$return['output'];
  $returnval=$return['returnval'];
  // make the result a string
  if (is_array($output)) {
      $recv_key_output_str = implode($output,"\n");
  } else { $recv_key_output_str=$output; }

  if ($debug) {
      echo "$recv_key_output_str";
      echo " returnvalue= $returnval";
      echo "end of gpg_recv_key";
  };

  //return the output string to our calling function
  return $return;
};

/*********************************************************************/
/**
 * function gpg_generate_keypair
 * This function generates a keypair
 *
 * Unattended key generation
 * =========================
 * This feature allows unattended generation of keys controlled by a
 * parameter file.  To use this feature, you use --gen-key together with
 * --batch and feed the parameters either from stdin or from a file given
 * on the commandline.
 *
 * The format of this file is as follows:
 *   o Text only, line length is limited to about 1000 chars.
 *   o You must use UTF-8 encoding to specify non-ascii characters.
 *   o Empty lines are ignored.
 *   o Leading and trailing spaces are ignored.
 *   o A hash sign as the first non white space character indicates a comment line.
 *   o Control statements are indicated by a leading percent sign, the
 *     arguments are separated by white space from the keyword.
 *   o Parameters are specified by a keyword, followed by a colon.  Arguments
 *     are separated by white space.
 *   o The first parameter must be "Key-Type", control statements
 *     may be placed anywhere.
 *   o Key generation takes place when either the end of the parameter file
 *     is reached, the next "Key-Type" parameter is encountered or at the
 *     control statement "%commit"
 *   o Control statements:
 *     %echo <text>
 *  Print <text>.
 *     %dry-run
 *  Suppress actual key generation (useful for syntax checking).
 *     %commit
 *  Perform the key generation.  An implicit commit is done
 *  at the next "Key-Type" parameter.
 *     %pubring <filename>
 *     %secring <filename>
 *  Do not write the key to the default or commandline given
 *  keyring but to <filename>.  This must be given before the first
 *  commit to take place, duplicate specification of the same filename
 *  is ignored, the last filename before a commit is used.
 *  The filename is used until a new filename is used (at commit points)
 *  and all keys are written to that file.  If a new filename is given,
 *  this file is created (and overwrites an existing one).
 *  Both control statements must be given.
 *    o The order of the parameters does not matter except for "Key-Type"
 *      which must be the first parameter.  The parameters are only for the
 *      generated keyblock and parameters from previous key generations are not
 *      used. Some syntactically checks may be performed.
 *      The currently defined parameters are:
 *      Key-Type: <algo-number>|<algo-string>
 *  Starts a new parameter block by giving the type of the
 *  primary key. The algorithm must be capable of signing.
 *  This is a required parameter.
 *      Key-Length: <length-in-bits>
 *  Length of the key in bits.  Default is 1024.
 *      Key-Usage: <usage-list>
 *         Space or comma delimited list of key usage, allowed values are
 *         "encrypt" and "sign".  This is used to generate the key flags.
 *         Please make sure that the algorithm is capable of this usage.
 *      Subkey-Type: <algo-number>|<algo-string>
 *  This generates a secondary key.  Currently only one subkey
 *  can be handled.
 *      Subkey-Length: <length-in-bits>
 *  Length of the subkey in bits.  Default is 1024.
 *      Subkey-Usage: <usage-list>
 *         Similar to Key-Usage.
 *      Passphrase: <string>
 *  If you want to specify a passphrase for the secret key,
 *  enter it here.  Default is not to use any passphrase.
 *      Name-Real: <string>
 *      Name-Comment: <string>
 *      Name-Email: <string>
 *  The 3 parts of a key. Remember to use UTF-8 here.
 *  If you don't give any of them, no user ID is created.
 *      Expire-Date: <iso-date>|(<number>[d|w|m|y])
 *  Set the expiration date for the key (and the subkey).  It
 *  may either be entered in ISO date format (2000-08-15) or as
 *  number of days, weeks, month or years. Without a letter days
 *  are assumed.
 *      Preferences: <string>
 *         Set the cipher, hash, and compression preference values for
 *  this key.  This expects the same type of string as "setpref"
 *  in the --edit menu.
 *
 * Here is an example:
 * $ cat >foo <<EOF
 *      %echo Generating a standard key
 *      Key-Type: DSA
 *      Key-Length: 1024
 *      Subkey-Type: ELG-E
 *      Subkey-Length: 1024
 *      Name-Real: Joe Tester
 *      Name-Comment: with stupid passphrase
 *      Name-Email: joe@foo.bar
 *      Expire-Date: 0
 *      Passphrase: abc
 *      %pubring foo.pub
 *      %secring foo.sec
 *      # Do a commit here, so that we can later print "done" :-)
 *      %commit
 *      %echo done
 * EOF
 * $ gpg --batch --gen-key -a foo
 *  [...]
 *
 *
 * echo "Key-Type: DSA Key-Length: 1024 Subkey-Type: ELG-E Subkey-Length: 2048 \
 *      Name-Real: Joe Tester Name-Email: joe@foo.bar Passphrase: abc" \
 *      | gpg --batch --gen-key --armor --homedir $gpg_key_dir
 *
 * Keys created with the option set below are PGP compatible
 * Key-Type: DSA
 * Key-Length: 1024
 * Subkey-Type: ELG-E
 * Preferences: S2 S7 S3 H2 H3 Z2 Z1
 * (don't forget to put in the other options needed for actual key creation)
 *
 * @param integer $debug
 * @param string $real_name Full Name for the uid
 * @param string $email     Email address to be oput in the uid
 * @param string $passphrase Passphrase to protect te secret key
 * @param optional string $comment Comment to be appended to the default comment
 * @param optional integer $keylength Length of key to generate
 * @param optional date $expiredate when should this key expire?
 * @param optional string $revoker fingerprint of key to set as revoker
 * @return array $return with output we were able to retrieve from the gpg command
 *
 */
function gpg_generate_keypair($debug, $real_name, $email, $passphrase, $comment = '',
                         $keylength = 1024, $expiredate=0)
{

    /* Check for secure connection.
     *
     * If we don't have a secure connection, return an error
     * string to be displayed by MakePage
     */
    if (!gpg_https_connection ()) {
        $line = _("You are not using a secure connection.").'&nbsp;'._("SSL connection required to generate keypair.");
	$return['errors']=array();
        $return['errors'][] = $line;
        return ($return);
        exit;
    };
    if (($GLOBALS['GPG_SYSTEM_OPTIONS']['use_proc_open']=='true') and check_php_version(4,3)) {
    	$gpg = initGnuPG();
	$return=$gpg->generateKey($real_name, $email, $passphrase, $comment, $keylength, $expiredate);
        $newfpr=$return['newkeys'][0];
	if ($newfpr) {
		$return['messages'][] = _("Key generated");
	} else {
		$return['errors'][] = _("Keys did not generate.  Please contact your system administrator for assistance debugging this.");
		return $return;
	}
	return $return;
    } else {

    global $gpg_key_file;
    global $gpg_key_dir;
    global $path_to_gpg;
   // if no comment specified, set a default
    if (!$comment) {
    $comment = _("Key generated on public webmail server");
    }
    if ($debug) {
        echo "Start function gpg_generate_keypair...\n";
        echo "<br>Email Address Submitted:". htmlspecialchars($email) ."\n";
        echo "<br>Full Name Submitted:".htmlspecialchars($real_name)."\n";
        echo "<br>about to run command<br>$path_to_gpg --gen-key --batch --armor --homedir $gpg_key_dir <br>\n";
    }
    /* Create the config file necessary for GnuPG to run in batch mode.
     * use 'w', becasue bidirectional 'r' or 'w+' are not reliable
     * and can fail horribly
     */

    $previous_keys = implode('', gpg_list_keys($debug, '', 'false', 'private', 'false'));

    $fp = popen($path_to_gpg . " --gen-key --batch --armor --homedir $gpg_key_dir", 'w');
    if (!$fp) { $return['errors'][] = _("Unable to open command pipe."); };
    /* Key-Type 20 = El Gamal + DSA Key */
    fputs($fp, "Key-Type: DSA\n");
    fputs($fp, "Key-Length: 1024\n");
    fputs($fp, "Subkey-Type: ELG-E\n");
    fputs($fp, "Subkey-Length: " . $keylength . "\n");
    fputs($fp, "Name-Real: " . $real_name . "\n");
    fputs($fp, "Name-Comment: " . $comment . "\n");
    fputs($fp, "Name-Email: " . $email . "\n");
    fputs($fp, "Expire-Date: ". $expiredate ."\n");
    fputs($fp, "Passphrase: " . $passphrase . "\n");
    fputs($fp, "Preferences: S2 S7 S3 H2 H3 Z2 Z1\n");
    fputs($fp, "%commit\n");
    pclose($fp);

    $current_keys = implode('', gpg_list_keys($debug, '', 'false', 'private', 'false'));

    if ($debug) echo "<hr>Previous Keyring State<br>$previous_keys<br><br>Current Keyring State $current_keys<hr>";

    if ($current_keys == $previous_keys) {
        $return['errors'][] = _("Keys did not generate.  Please contact your system administrator for assistance debugging this.");
    } else {
        $return['messages'][] = _("Key generated");
        $newstring = str_replace($previous_keys, "", $current_keys);
        $return ['messages'][] = $newstring;
    };

    //return an array that will display what information we have to the user.
    return ($return);
    }
};

/***************************************************/
/*
 * $Log: gpg_key_functions.php,v $
 * Revision 1.63  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.62  2004/08/23 07:40:57  ke
 * -added use of GnuPG object for key generation in the case of the user having PHP 4.3 and proc_open
 * enabled.
 * -defined $matches before use, to avoid error warnings
 * -internationalized some error strings
 * Bug 29
 *
 * Revision 1.61  2004/04/30 17:59:34  ke
 * -removed newline from end of file
 *
 * Revision 1.60  2004/02/17 22:41:01  ke
 * -proc_open additions
 * bug 29
 *
 * Revision 1.59  2004/01/19 18:28:30  brian
 * E_ALL fixes
 *
 * Revision 1.58  2004/01/19 00:53:41  brian
 * - replaced DIR_SEPARATOR with DIRECTORY_SEPARATOR
 *
 * Revision 1.57  2004/01/15 18:27:07  ke
 * -changed all functions to use gpg_execute for execution
 * -changed recv_key function to return entire $return
 *
 * Revision 1.56  2004/01/12 06:13:15  brian
 * - removed $output check from keygen, as that code is never called
 *
 * Revision 1.55  2003/12/29 23:54:04  brian
 * localized strings discovered by Alex Lemaresquier during French translation
 *
 * Revision 1.54  2003/12/18 19:47:50  ke
 * -removed reference to data_dir in system keyring, since it's now a full path
 *
 * Revision 1.53  2003/12/16 20:00:03  brian
 * changed instances of $system_keyring_file = $safe_data_dir to
 *    $system_keyring_file = $data_dir becasue $safe_data_dir is a
 *    hashed dir on a per-user basis
 *
 * Revision 1.52  2003/11/26 21:38:36  ke
 * -removed fread in keygeneration, will potentially block if bidir pipes are enabled (freebsd)
 *
 * Revision 1.51  2003/11/25 02:05:36  ke
 * -changed code accessing keyring files or calling gpg to use safe_data_dir
 * bug 116
 *
 * Revision 1.50  2003/10/30 20:43:56  ke
 * -removed multiline in quoted echo
 *
 * Revision 1.49  2003/10/30 19:40:20  brian
 * modified all localized strings to use double quotes.
 * Bug 35
 *
 * Revision 1.48  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.47  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.46  2003/10/11 20:32:12  brian
 * added test for valid file handle in gpg_keyserver_findkey fn
 * Bug 80
 *
 * Revision 1.45  2003/10/03 17:01:55  ke
 * -Changed subkey type to ELG-E, for encrypt only.
 *  This allows PGP compliance, and forces gpg to sign with the DSA key.
 * -Added preference line in key creation code to force cypher to TripleDES.
 * bug 54
 *
 * Revision 1.44  2003/10/01 19:45:24  ke
 * -added passthrough for $comment into key generation code
 * -goes along with addition of system default default_comment, passed in by keygen UI code
 *
 * Revision 1.43  2003/08/12 22:11:56  ke
 * -Changed key generation to use DH/DSA with ElGamal subkey
 * Bug 54
 *
 * Revision 1.42  2003/07/08 18:41:27  vermette
 * function gpg_delete_key replaced by function in gpg_keyring
 *
 * Revision 1.41  2003/07/08 18:34:34  vermette
 * function gpg_import_keyring replaced by function in gpg_keyring
 *
 * Revision 1.40  2003/07/08 01:54:27  ke
 * - fixed small errors in gpg_export_key to make functional
 * Bug 41
 *
 * Revision 1.39  2003/07/07 20:32:50  brian
 * - modified code to support import from email
 * Bug 46
 *
 * Revision 1.38  2003/07/07 18:33:31  brian
 * - modified gpg_format_key_list fn to be ready for gettext localization
 * - cleaned up the activity switch in gpg_format_keylist fn (still needs work)
 * Bug 35
 *
 * Revision 1.37  2003/06/13 21:29:20  brian
 * added debug code to gpg_format_key_list
 *
 * Revision 1.36  2003/06/13 21:20:45  brian
 * removed array from $keyring_type
 *
 * Revision 1.35  2003/06/13 21:16:28  brian
 * - removed array usage and loop for calling gpg_list_keys fn
 *   from within gpg_format_keylist fn.
 * - replaced with proper types.
 *
 * Revision 1.34  2003/06/13 20:59:08  brian
 * changed exit on error to break on error.
 *
 * Revision 1.33  2003/06/13 16:25:44  brian
 * add gpg_export_key fn
 * Bug 41
 *
 * Revision 1.32  2003/06/13 15:22:22  brian
 * - modified to remove $msg parameter in $gpg_format_keylist fn
 * - modified 'system' case in gpg_list_keys fn to fix display bug
 *
 * Revision 1.31  2003/06/12 20:53:47  brian
 * add case 'system' to gpg_list_keys fn
 *
 * Revision 1.30  2003/06/09 22:19:42  brian
 * moved @param declarations for gpg_generate_keypair fn
 *
 * Revision 1.29  2003/05/29 14:35:56  brian
 * fixed @param and @return declarations for doc generators
 *
 * Revision 1.28  2003/04/28 18:51:51  brian
 * added expiredate parameter and default to gpg_generate_keypair function
 * Bug 14
 *
 * Revision 1.27  2003/04/27 12:29:49  brian
 * fixed indents to remove irregular use of tabstops - no functional change
 *
 * Revision 1.26  2003/04/16 02:34:22  brian
 * fixed order of operators in gpg_list_keys fn calls in gpg_generate_keypair fn
 *
 * Revision 1.25  2003/04/15 17:04:29  brian
 * set defaults on parameters in gpg_list_keys fn
 *
 * Revision 1.24  2003/04/12 03:30:07  brian
 * modified gpg_generate_keypair to correctly return details of new generated key
 * Bug 14
 *
 * Revision 1.23  2003/04/12 02:25:52  tyler
 * - basic delete ability for keyring management
 *
 * Revision 1.22  2003/04/11 18:59:08  brian
 * fixed typos
 *
 * Revision 1.21  2003/04/11 18:53:35  brian
 * improved return output
 * Bug 14
 *
 * Revision 1.20  2003/04/11 17:37:56  brian
 * first hack at checking for success
 * Bug 14
 *
 * Revision 1.19  2003/04/11 15:17:01  brian
 * pulled globals into gpg_generate_keypair function
 * function works without error handling
 * Bug 14
 *
 * Revision 1.18  2003/04/11 15:00:17  brian
 * updates to gpg_gerneate_keypair function
 * Bug 14
 *
 * Revision 1.17  2003/04/11 14:32:36  brian
 * fn gpg_format_ky_list
 * - make header check for $radio check to make sure readio==true
 * Bug 23
 *
 * Revision 1.16  2003/04/11 14:12:57  brian
 * added check for $radio to table header
 * Bug 23
 *
 * Revision 1.15  2003/04/11 13:44:10  brian
 * -added types of 'public' 'private' and  'secret'
 * -cleaned up rendered html formatting
 * -added $radio flag to determine whether to display a radio list
 * Bug 23
 *
 * Revision 1.14  2003/04/11 13:36:20  brian
 * -added types of 'public' 'private' and  'secret'
 * -cleaned up rendered html formatting
 * -added $radio flag to determine whether to display a radio list
 * Bug 23
 *
 * Revision 1.13  2003/04/11 04:54:27  brian
 * fixed logic bugs between public/private key display for sign/trust
 * cleaned up formatting
 * Bug 23
 *
 * Revision 1.12  2003/04/11 02:52:11  tyler
 * - improved formating of the UI in format_key_list
 *
 * Revision 1.11  2003/04/11 02:45:13  tyler
 * - added support to format_list function for keyring management
 * - modified format_list code to be more modular
 *
 * Revision 1.10  2003/04/09 23:39:19  tyler
 * - abstracted the gpg_format_key_list function
 * Bug 23
 *
 * Revision 1.9  2003/04/02 21:52:19  brian
 * - applied patch to use --allow-secret-key-import in functions
 *   - gpg_import_key
 *   - gpg_import_keyring
 * - patch tested under gpg v 1.0.7 and 1.2.1 before cvs commit
 *   - no errors or warnings
 *   - import of secret keys worked as expected
 *   - patch deemed safe for inclusion in main code trunk
 * Bug 16
 *
 * Revision 1.8  2003/03/27 20:56:32  brian
 * added https check in key generation function
 *
 * Revision 1.7  2003/03/27 12:53:24  brian
 * updates to key generation function
 *
 * Revision 1.6  2003/03/25 16:12:44  brian
 * updates to key generation functions.
 *
 * Revision 1.5  2003/03/12 23:46:27  brian
 * fixed --list-secret-keys
 * from  --list-private-keys (bug)
 *
 * Revision 1.4  2003/03/12 22:16:45  tyler
 * - gpg_recv_key function modified to honor the chosen keyserver
 *
 * Revision 1.3  2003/03/12 17:07:55  brian
 * added optional $keyring_type parameter to gpg__list_keys function
 *
 * Revision 1.2  2003/03/12 16:27:12  brian
 * added $Log directive to broken out functions file.
 *
 */
?>