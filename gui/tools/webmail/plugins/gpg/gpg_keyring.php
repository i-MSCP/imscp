<?php
/**
 * gpg_keyring.php
 * -----------
 * GPG plugin keyring class file.
 * This file contains the keyring class, to extract a keyring from gpg for manipulation.
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Joshua Vermette
 * @author Brian Peterson
 * @author Aaron van Meerten
 *
 * $Id: gpg_keyring.php,v 1.34 2005/07/27 14:07:49 brian Exp $
 *
 */

/*********************************************************************/
/**
 * function array_key_diff
 *
 * This function returns the array elements in the first parameter
 * that do not exist in the second parameter
 *
 * @param array $ar1 containing all elements
 * @param array $ar2 containing elements to eliminate from $ar1
 * @return array $ar1 with elements in $ar2 removed
 */
function array_key_diff($ar1, $ar2) {
    $asubs = array_slice(func_get_args(),1);
    foreach ($ar1 as $key=>$val) {
        foreach ($asubs as $asub) {
            if (array_key_exists($key, $asub)) {
                unset($ar1[$key]);
            }
        }
    }
    return $ar1;
}


/*********************************************************************/
/**
 * function gpg_extract_fpr
 *
 * This function will extract the key fingerpring from an individual
 * key defined in the array $parts
 *
 * @param array $parts containing the broken out key properties
 * @return string $parts[9] (the fingerprint)
 */
function gpg_extract_fpr($parts) {
    return $parts[9];
}

/*********************************************************************/
/**
 * function gpg_extract_pub
 *
 * This function will extract the parts of a public key
 * defined in the array $parts
 *
 * It appears that this function is used to display key details.
 *
 * @param array $parts containing the broken out key properties
 * @return array $ret
 *    $ret['type'] = $parts[0];
 *    $ret['len'] = $parts[2];
 *    $ret['id'] = $parts[4];
 *    $ret['date'] = $parts[5];
 *    $ret['exp'] = $parts[6];
 *
 */
function gpg_extract_pub($parts) {
    //Initialize the return array.
    $ret = array();

    //General info.
    $ret['type'] = $parts[0];
    $ret['len'] = $parts[2];
    $ret['id'] = $parts[4];
    $ret['date'] = $parts[5];
    $ret['exp'] = $parts[6];

    //Email address info.
    $matches = split("[<>]", $parts[9]);
    switch (count($matches)) {
        case 1:
            //Assume it's just an address.
            $ret['email_addr'] = htmlspecialchars(trim($matches[0]));
            $ret['email_name'] = htmlspecialchars(trim($matches[0]));
            break;
        case 2:
            //Assume it's a name followed by an address.
            $ret['email_name'] = htmlspecialchars(trim($matches[0]));
            $ret['email_addr'] = htmlspecialchars(trim($matches[1]));
            break;
        default:
		//Patch to parse user info lines properly provided by Lars Kruse <devel@sumpfralle.de>
	    ereg ("^(.*)<([^>]*)>([^>]*)$", $parts[9], $infos);
	    $ret['email_name'] = $infos[1];
	    $ret['email_addr'] = $infos[2];
	    $ret['email_extra'] = $infos[3];
            //Assume name, address, extra.
            // $ret['email_name'] = htmlspecialchars(trim(join(array_slice($matches, 0, (count($matches) -1)), "")));
            // $ret['email_addr'] = htmlspecialchars(trim($matches[count($matches)-1]));
            // $ret['email_extra'] = htmlspecialchars(trim(join(array_slice($matches, 2, (count($matches) - 2)), "")));
            break;
    }

    //We have to have a name!
    if (! $ret['email_name']) $ret['email_name'] = "UNKNOWN";
    if (! strchr($ret['email_addr'], "@")) unset($ret['email_addr']);

    $ret['email_name'] = str_replace("\\x3a", ":", $ret['email_name']);

    //Algorithm
    switch ($parts[3]) {
        case 1:
            $ret['alg'] = 'RSA';
            $ret['signable'] = true;
            break;
        case 16:
            $ret['alg'] = 'ElGamal (encrypt only)';
            $ret['signable'] = false;
            break;
        case 17:
            $ret['alg'] = 'DSA/DH';
            $ret['signable'] = true;
            break;
        case 20:
            $ret['alg'] = 'ElGamal (sign and encrypt)';
            $ret['signable'] = true;
            break;
        default:
            $ret['alg'] = 'UNKNOWN';
            $ret['signable'] = false;
            break;
    }

    //Return what we got!
    return $ret;
}

/*********************************************************************/
/**
 * function gpg_extract_sub
 *
 * This function will extract the parts of a sub-key
 * defined in the array $parts
 *
 * This function is only a wrapper for fn gpg_extract_pub
 *
 * @param array $parts containing the broken out key properties
 * @return array $ret
 *    $ret['type'] = $parts[0];
 *    $ret['len'] = $parts[2];
 *    $ret['id'] = $parts[4];
 *    $ret['date'] = $parts[5];
 *    $ret['exp'] = $parts[6];
 */
function gpg_extract_sub($parts) {
    return gpg_extract_pub($parts);
}

/*********************************************************************/
/**
 * function gpg_extract_sec
 *
 * This function will extract the parts of a secret key
 * defined in the array $parts
 *
 * This function is only a wrapper for fn gpg_extract_pub
 *
 * @param array $parts containing the broken out key properties
 * @return array $ret
 *    $ret['type'] = $parts[0];
 *    $ret['len'] = $parts[2];
 *    $ret['id'] = $parts[4];
 *    $ret['date'] = $parts[5];
 *    $ret['exp'] = $parts[6];
 */
function gpg_extract_sec($parts) {
    return gpg_extract_pub($parts);
}

/*********************************************************************/
/**
 * class gpg_keyring
 *
 * This class encapsulates the keyring functions.
 *
 */
class gpg_keyring {
    /***********************************************/
    /**
     * function gpg_keyring
     *
     * Constructor
     *
     * @param void
     *
     */
    function gpg_keyring() {
        $this->keys = array();
    }

    /***********************************************/
    /**
     * function gpg_keyring
     *
     * Constructor
     *
     * @param string $search_string
     * @param string $keyring_type
     * @return null or error string array
     */
    function fetchKeys($search_string="", $keyring_type="all") {
        global $gpg_key_file;
        global $gpg_key_dir;
        global $path_to_gpg;
    global $data_dir;
        global $safe_data_dir;
    global $username;
    global $debug;
    $safe_data_dir = getHashedDir($username, $data_dir) . DIRECTORY_SEPARATOR;
        if (!$path_to_gpg) {
                load_prefs_from_file('gpg_system_defaults.txt',$debug);
                load_prefs_from_file('gpg_local_prefs.txt',$debug);
                $path_to_gpg=$GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
                $gpg_key_dir ="$safe_data_dir$username.gnupg";
        }

        //Reset the array, for the new list of keys we're getting.
        $this->keys = array();
    $return['errors'] = array();
    $return['warnings'] = array();
	$params = "--homedir $gpg_key_dir ";

        /**
         * We will use the $keyring_type parameter to determine
         * what to add the the $params string.
         * Legal Values are 'public', 'private', 'secret', and 'all'
         */
        switch ($keyring_type) {
        case 'sigs':
	    $params  .= '--list-sigs';
            break;
        case 'all':
    case 'new':
        case '':
	    $params  .= '--list-keys ';
            break;
        case 'public':
	    $params  .= '--list-public-keys ';
            break;
        case 'secret':
	    $params  .= '--list-secret-keys ';
            break;
        case 'system':
            {
            $systemkeyring = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'];
            if ($systemkeyring=='true') {
                $system_keyring_file = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
                if (is_file($system_keyring_file)) {
                $system_keyring_file = escapeshellarg($system_keyring_file);
		$params  .= " --keyring $system_keyring_file ";
                }
            }

	    $params  .= ' --list-keys ';
            break;
            }
        };

        //We always want colons and fingerprints.
        //Yes, add --with-fingerprint twice, so it will fingerprint subkeys.
	$params  .= '--batch --with-colons --with-fingerprint --with-fingerprint ';

        if ($search_string) {
            $search_string = escapeshellarg($search_string);
	    $params  .= "$search_string";
        }

        //Do it!  True $returnVal means error, in classic system call style.
	$return=gpg_execute($debug,$params,NULL,NULL,false,false);
	$returnval=$return['returnval'];
	$list_text=$return['rawoutput'];
        if ($returnval) {
	        if ($return['errors']) return $return;
        }
	if (!is_array($list_text)) { $list_text = explode("\n",$return['rawoutput']); }
        $data = NULL;
        $currKeyType = NULL;
	if (!$list_text) { $list_text = array(); }
        foreach ($list_text as $line) {
            //Break it up.
            $parts = explode (":", $line);
            //Is it a fingerprint?
            if ($parts[0] == "fpr") {
            //Fingerprint for the public key that just went by.  Extract it.
            $fpr = gpg_extract_fpr($parts);

            //Sanity.
            if (! $fpr) {
        $return['errors'][] = "ERROR (fetchKeys): cannot parse keyring.  Missing fingerprint.";
                return $return;
            }

            //Take action based on the type of key this fingerprint comes after.
            if ($currKeyType == "p") {
                //A pbulic key.

                //Record the key data.
                $this->keys[$fpr] = $data;

                //Save the fingerprint so we can
                //record sub-keys under that fingerprint.
                $currPubFpr = $fpr;
            }
            else if ($currKeyType == "su") {
                //A subkey.

                //Sanity.
                if (! $currPubFpr) {
                //An orphaned subkey?
                //This shouldn't happen.
                //We need to display it, if only so it can be deleted.
                echo "<!-- WARNING: Orphaned subkey '$fpr' -->";
                $currPubFpr = $fpr;
                }

                //Add this to the subkeys record of the current public key.
                $this->keys[$currPubFpr]["sub"][$fpr] = $data;
            }
            else if ($currKeyType == "se") {
                //A secret key.  If we have any of these, we can assume they're all secret.
                //That's just how gpg works.
                $this->keys[$fpr] = $data;
            }
            else {
                //XXX - For now we go on faith, and assume that if we don't
                //know about it, it's not important.

                //What??
                //return "ERROR (fetchKeys): cannot parse keyring.  Unknown currKeyType '$currKeyType'.";
            }

            //Null out the data, we're done with this record.
            $data = NULL;
            $currKeyType = "";

            continue;
            }

            //Is it a public key?
            if ($parts[0] == "pub") {
            //Sanity.
            if ($data) {
                //If there's data, it means the previous fingerprint was missing.
                //That shouldn't ever happen.
        $return['errors'][] = "ERROR (fetchKeys): cannot parse keyring.  Fingerprint for previous key missing. (pub)";
                return $return;
            }

            //This is a new public key, so NULL out $currPubFpr.
            //We probably don't need to do this, but it's safe.
            $currPubFpr = NULL;

            //Break out the key info and save it in $data.
            $data = gpg_extract_pub($parts);
            $currKeyType = "p";

            continue;
            }

            //Is it a public key?
            if ($parts[0] == "sub") {
            //Sanity.
            if ($data) {
                //If there's data, it means the previous fingerprint was missing.
                //That shouldn't ever happen.
        $return['errors'][] = "ERROR (fetchKeys): cannot parse keyring.  Fingerprint for previous key missing. (sub)";
        return $return;
            }

            //New key.
            $data = gpg_extract_sub($parts);
            $currKeyType = "su";

            continue;
            }

            //Is it a public key?
            if ($parts[0] == "sec") {
            //Sanity.
            if ($data) {
                //If there's data, it means the previous fingerprint was missing.
                //That shouldn't ever happen.
        $return['errors'][] = "ERROR (fetchKeys): cannot parse keyring.  Fingerprint for previous key missing. (sub)";
                return $return;
            }

            //New key.
            $data = gpg_extract_sec($parts);
            $currKeyType = "se";

            continue;
            }
        }

        //All is good!
        return $return;
    }

    /***********************************************/
    /**
     * function deleteKey
     *
     * This function returns NULL on success, a list of error return lines on error.
     * Static function, requires no object.
     * In fact, if you call it with an object, it doesn't remove the key from the map!
     *
     * @param string $fpr
     * @param string $type
     * @return null or error string array
     */
    function deleteKey($fpr, $type="all") {
        global $gpg_key_dir;
        global $path_to_gpg;
        global $data_dir;
    global $safe_data_dir;
        global $username;
	global $debug;
    $safe_data_dir=getHashedDir($username,$data_dir). DIRECTORY_SEPARATOR;
        if (!$path_to_gpg) {
                load_prefs_from_file('gpg_system_defaults.txt',$debug);
                load_prefs_from_file('gpg_local_prefs.txt',$debug);
                $path_to_gpg=$GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
                $gpg_key_dir ="$safe_data_dir$username.gnupg";
        }

        //Choose a flag.
        switch ($type) {
        case "private":
            $flag = "--delete-secret-key";
            break;
        default:
        case "all":
            $flag = "--delete-secret-and-public-key";
            break;
        }

        //Make and execute the delete command
	$params = "--yes --homedir $gpg_key_dir $flag $fpr";
	$return=gpg_execute($debug,$params);
	$returnval=$return['returnval'];
	$output = $return['output'];
        //Success?  (system command returns 0 on success)
        if (! $returnval) return NULL;

	//otherwise error, return error lines

    if (is_array($output)) {
        $output = implode("\n",$output);
    }
        return $output;
    }

    /***********************************************/
    /**
     * function importKey_text
     *
     * This function returns NULL on success, a list of error return lines on error.
     * Static function, requires no object.
     * In fact, if you call it with an object, it doesn't remove the key from the map!
     *
     * @param string $keystring
     * @return null or error string array
     */
    function importKey_text($keystring,$debug=0) {
        global $gpg_key_dir;
        global $path_to_gpg;
        global $data_dir;
        global $username;
    global $safe_data_dir;
    $debug=$GLOBALS['GPG_SYSTEM_OPTIONS']['debug'];
    $safe_data_dir=getHashedDir($username,$data_dir). DIRECTORY_SEPARATOR;
        if (!$path_to_gpg) {
                load_prefs_from_file('gpg_system_defaults.txt',$debug);
                load_prefs_from_file('gpg_local_prefs.txt',$debug);
                $path_to_gpg=$GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
                $gpg_key_dir ="$safe_data_dir$username.gnupg";
        }

    $return['errors'] = array();
    $return['warnings'] = array();
    $return['info'] = array();
    $this->fetchKeys();
    $oldkeys = $this->keys;

        //Try to import.
	$params = "--allow-secret-key-import --import --homedir $gpg_key_dir";
	$return=gpg_execute($debug,$params,NULL,$keystring);
    $this->fetchKeys();
    $newkeys = array_key_diff($this->keys, $oldkeys);
    $this->keys = $newkeys;
    $this->newkeys = true;
    $return['newkeys'] = $newkeys;
    //return all values collected, trapped or not
    return $return;
    }

    /***********************************************/
    /**
     * function importKey_file
     *
     * This function returns NULL on success, a list of error return lines on error.
     * Static function, requires no object.
     * In fact, if you call it with an object, it doesn't remove the key from the map!
     *
     * @param string $fname
     * @return null or error string array
     */
    function importKey_file($fname,$debug=0){
        global $gpg_key_dir;
        global $path_to_gpg;
        global $data_dir;
    global $safe_data_dir;
        global $username;
    $safe_data_dir = getHashedDir($username, $data_dir) . DIRECTORY_SEPARATOR;
    if (!$path_to_gpg) {
        load_prefs_from_file('gpg_system_defaults.txt',$debug);
        load_prefs_from_file('gpg_local_prefs.txt',$debug);
        $path_to_gpg=$GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg'];
        $gpg_key_dir ="$safe_data_dir$username.gnupg";
    }
    $return['errors']=array();
    $return['warnings'] = array();
    $return['info'] = array();
        // make sure there aren't any funny characters in imp_keyting.
        $fname = escapeshellarg($fname);
    $this->fetchKeys();
    $oldkeys = $this->keys;
    if ($debug) {
        echo "OldKeys<pre>\n"; print_r($oldkeys); echo "\n</pre>";
    }
        //Try to import
	$params = " --homedir $gpg_key_dir --allow-secret-key-import --import $fname";
	$return=gpg_execute($debug,$params);
    $this->fetchKeys();
    if ($debug) { echo "NewKeys<pre>\n"; print_r($this->keys); echo "\n</pre>"; }
    $newkeys = array_key_diff($this->keys, $oldkeys);
    $return['newkeys'] = $newkeys;
    $this->keys = $newkeys;
    $this->newkeys = true;
        //return all values collected, trapped or not
    return $return;

    }

    /***********************************************/
    /**
     * function getKey
     *
     * This function returns a key that matchs the fingerprint $fpr
     *
     * @param string $fpr
     *
     * @return key
     */
    function getKey($fpr) {
        return $this->keys[$fpr];
    }

    /***********************************************/
    /**
     * function getKeymap
     *
     * This function returns a key that matchs the fingerprint $fpr
     *
     * @param void
     *
     * @return array keys
     */
    function getKeyMap() {
        return $this->keys;
    }

    /***********************************************/
    /**
     * function getKeymap_chunked
     *
     * Returns the map of keys, chunked in to chunks of size <= $len
     *
     * @param integer $len
     *
     * @return array keys
     */
    function getKeyMap_chunked($len) {
        if (function_exists('array_chunk')){
            return array_chunk($this->keys, $len, true);
        } else {
            if ($debug) {
                echo "<br>Your PHP version does not support te array_chunk function.  Returning entire array instead.\n";
            }

            $return = array();
            $return [] = $this->keys;
            return  $return;
        } //end check for array_chunk

    }

    /***********************************************/
    /**
     * function numKeys
     *
     * Returns the total number of keys in the object,
     * as determined by options passed to fetchKeys().
     *
     * @param void
     *
     * @return array keys
     */
    function numKeys() {
        return count($this->keys);
    }

    /***********************************************/
    /**
     * function sortKeys
     *
     * Sorts the keys in order of key data name $dataName (e.g. "email_addr", "date", etc)
     * if $asc is true, sorts in ascending order.
     *
     * @param string $dataName
     * @param boolean $asc value either '<' or '>'
     *
     * @return array keys
     */
    function sortKeys($dataName, $asc) {
        //Determine ascending v. descending.
        if ($asc) $op = ">";
        else $op = "<";

        //Form the body of the lambda function.
        $code =
            "if (\$key1['$dataName'] $op \$key2['$dataName']) { return 1; } " .
            "else if (\$key1['$dataName'] == \$key2['$dataName']) { return 0; } " .
            "else return -1;";

        //Create the function and sort.
        $lambda = create_function('$key1,$key2', $code);
        uasort($this->keys, $lambda);
    }

    /***********************************************/
    /**
     * function getExportText
     *
     * Exports a key defined by $fpr to ASCII Armored text.
     *
     * @param string $fpr
     * @param enum $ringname
     *
     * @return string $exportstring
     */
    function getExportText($fpr, $ringName) {
        global $gpg_key_dir;
        global $path_to_gpg;
        global $data_dir, $username;
    global $safe_data_dir;
    global $debug;
        $exportstring = "";
    $safe_data_dir = getHashedDir($username, $data_dir) . DIRECTORY_SEPARATOR;
        //get it from the appropriate keyring.
        switch ($ringName) {
            case 'system':
                if ($GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyring'] == 'true') {
                $system_keyring_file = $GLOBALS['GPG_SYSTEM_OPTIONS']['systemkeyringfile'];
                if (is_file($system_keyring_file)) {
                    $system_keyring_file = escapeshellarg($system_keyring_file);
                    $exportstring .= " --keyring $system_keyring_file ";
                }
                }
        }

        //Add the export command.
        $exportstring .= ' --export ';

        // make sure there aren't any funny characters in fingerprint
        $fpr = escapeshellarg($fpr);

        //Make the command and execute.
	$params  = "--homedir $gpg_key_dir --armor $exportstring $fpr";
	$return=gpg_execute($debug,$params);
	$returnval=$return['returnval'];
	$output = $return['output'];
        //Errir?
        if ($returnval) return NULL;

        //Return, making sure it's a string.
        if (is_array($output)) return implode($output,"\n");
        return $output;
    }
}

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * c-basic-offset: 4
 * End:
 */

/***************************************************/
/*
 * $Log: gpg_keyring.php,v $
 * Revision 1.34  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.33  2005/03/19 18:03:41  ke
 * -applied patch to fix uid's with <>'s in them (thanks to Lars Kruse <devel@sumpfralle.de>)
 * Bug 245
 *
 * Revision 1.32  2004/04/30 17:58:58  ke
 * -removed newline from end of file
 *
 * Revision 1.31  2004/02/17 22:41:56  ke
 * -changed gpg calls to work properly with new proc_open additions
 * bug 29
 *
 * Revision 1.30  2004/01/19 20:59:28  ke
 * -E_ALL fixes
 *
 * Revision 1.29  2004/01/19 00:52:21  brian
 * - converted three DIR_SEPARATOR to DIRECTORY_SEPARATOR
 *
 * Revision 1.28  2004/01/17 00:26:33  ke
 * -E_ALL fixes
 *
 * Revision 1.27  2004/01/14 23:35:43  ke
 * -changed to use gpg_execute centralized functionality
 *
 * Revision 1.26  2003/12/18 19:48:14  ke
 * -removed data_dir from system keyring path, since it's a full path now
 *
 * Revision 1.25  2003/12/16 20:00:03  brian
 * changed instances of $system_keyring_file = $safe_data_dir to
 *    $system_keyring_file = $data_dir becasue $safe_data_dir is a
 *    hashed dir on a per-user basis
 *
 * Revision 1.24  2003/12/02 15:42:42  brian
 * - added function_exists check for array_chunk function
 * - return entire array if array_chunk doesn't exist
 * Bug 110
 *
 * Revision 1.23  2003/11/25 02:06:23  ke
 * removed '/' from call to systemkeyring file
 *
 * Revision 1.22  2003/11/25 01:41:46  ke
 * -caught more missing DIRECTORY_SEPARATOR's
 *
 * Revision 1.21  2003/11/25 01:26:50  ke
 * -added DIRECTORY_SEPARATOR to end of safe_data_dir so that paths come out correctly
 *
 * Revision 1.20  2003/11/24 20:01:00  ke
 * -changed the system keyring to be found in $data_dir instead of $safe_data_dir, so it can be placed without worry for username hashes
 *
 * Revision 1.19  2003/11/24 19:58:04  ke
 * -uses $safe_data_dir with getHashedDir for finding files
 * bug 116
 *
 * Revision 1.18  2003/11/11 22:42:21  ke
 * -removed most parsing of gpg output, using gpg_parse_output instead
 * bug 107
 *
 * Revision 1.17  2003/11/06 20:03:12  ke
 * -Added error handling to deletekey function
 *
 * Revision 1.16  2003/11/06 00:44:35  ke
 * -added defaults for path_to_gpg and gpg_key_dir in fetchkeys
 *
 * Revision 1.15  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.14  2003/10/17 12:50:20  brian
 * added package and author tags
 *
 * Revision 1.13  2003/10/14 19:59:31  ke
 * -Fixed broken add and delete functions by loading globals
 * -added return of extra information during adding of keys
 *
 * Revision 1.12  2003/10/06 22:47:25  ke
 * -Added error handling to import key functions
 * -Added ability to return new keys
 * -Set keyring to contain new keys upon import
 *
 * Revision 1.11  2003/10/04 00:50:44  ke
 * -Added basic error handling to fetchKeys() function
 *
 * Revision 1.10  2003/09/29 19:53:41  brian
 * - added page header
 * - manually added all previous commit log entries
 * - added prototype document blocks to all functions
 * - normalized code indentation to match coding standards
 *
 *
 * Manually added log entried below this line - Brian
 * ----------------------------
 *  revision 1.9
 *  date: 2003/08/14 02:40:36;  author: vermette;  state: Exp;  lines: +21 -6
 *  - replaced getstart.mod with getstart.php.
 *  - Integrated key generation into consolidated UI.
 *  - Removed unused components.
 *
 *  ----------------------------
 *  revision 1.8
 *  date: 2003/08/02 01:54:53;  author: vermette;  state: Exp;  lines: +30 -41
 *  - added signing key functionality to consolidated interface.
 *  - Removed old signing key page from keymgmt.mod,
 *     but left file in cvs because it's used in getstart.mod.
 *  - Added viewing of secret keyring.
 *
 *  ----------------------------
 *  revision 1.7
 *  date: 2003/07/20 06:44:47;  author: vermette;  state: Exp;  lines: +37 -1
 *  - added key emailing.
 *  - added click-thru from key view to compose to key owner.
 *  - Speed enhancements on keyview.
 *
 *  ----------------------------
 *  revision 1.6
 *  date: 2003/07/17 07:33:07;  author: vermette;  state: Exp;  lines: +7 -3
 *  - time-saving modifications to hack around gpg's lack of ability to restrict output size.
 *  - Added system keyring into consolidated interface.
 *
 *  ----------------------------
 *  revision 1.5
 *  date: 2003/07/11 07:43:12;  author: vermette;  state: Exp;  lines: +10 -1
 *  added search to keyring_main
 *
 *  ----------------------------
 *  revision 1.4
 *  date: 2003/07/11 06:54:03;  author: vermette;  state: Exp;  lines: +29 -0
 *  Keyring work, Added:
 *  - chunking,
 *  - first/prev/next/last,
 *  - sorting, and
 *  - ascending v. descending sorted view.
 *  - Also modified key table to give more info.
 *
 *  ----------------------------
 *  revision 1.3
 *  date: 2003/07/08 19:10:29;  author: vermette;  state: Exp;  lines: +24 -28
 *  - tightening error messaging.
 *  - UI work on gpg_keyring class.
 *  - Proper display of empty keyring
 *
 *  ----------------------------
 *  revision 1.2
 *  date: 2003/07/08 18:03:44;  author: vermette;  state: Exp;  lines: +263 -63
 *  - adding new functionality,
 *  - minor reorg
 *
 *  ----------------------------
 *  revision 1.1
 *  date: 2003/07/01 06:22:24;  author: vermette;  state: Exp;
 *  Initial Revision
 *  new file for up-and-coming keyring class
 */
?>