<?php

/**
 * gpg.php
 * -----------
 * GPG plugin class file.
 * This file contains the GnuPG class, as  well as the GnuPGKey and GnuPGSig classes 
 *
 * Copyright (c) 2002-2004 GPG Plugin Development Team
 * Licensed under the GNU Lesser GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Brian Peterson
 * @author Ryan
 * @author Aaron van Meerten
 *
 * $Id: gpg.php,v 1.58 2005/12/21 02:55:46 ke Exp $
 *
 */



define( 'GPGSTDIN', 0 );
define( 'GPGSTDOUT', 1 );
define( 'GPGSTDERR', 2 );
define( 'STATUS_FD', 5 );
define( 'PASSPHRASE_FD', 7 );
define( 'CHECKPASSWORD_FD', 3 );

// from gnupg include/cipher.h
define( 'GNUPG_HASH_MD5', 1 );
define( 'GNUPG_HASH_SHA1', 2 );
define( 'GNUPG_HASH_RMD160', 3 );
define( 'GNUPG_HASH_TIGER', 6 );
define( 'GNUPG_HASH_SHA256', 8 );
define( 'GNUPG_HASH_SHA384', 9 );
define( 'GNUPG_HASH_SHA512', 10 );

define( 'GNUPG_PUBKEY_RSA', 1 );
define( 'GNUPG_PUBKEY_RSA_E', 2 );       /* RSA encrypt only */
define( 'GNUPG_PUBKEY_RSA_S', 3 );       /* RSA sign only */
define( 'GNUPG_PUBKEY_ELGAMAL_E', 16 );  /* encrypt only ElGamal (but not for v3)*/
define( 'GNUPG_PUBKEY_DSA', 17);
define( 'GNUPG_PUBKEY_ELGAMAL', 20 );    /* sign and encrypt elgamal */

// Trust Values (from GnuPG's DETAILS docs)
define( 'GNUPG_TRUST_ULTIMATE', 'u' );
define( 'GNUPG_TRUST_FULL', 'f' );
define( 'GNUPG_TRUST_MARGINAL', 'm' );
define( 'GNUPG_TRUST_NONE', 'n' );
define( 'GNUPG_TRUST_EXPIRED', 'e' );
define( 'GNUPG_TRUST_REVOKED', 'r' );
define( 'GNUPG_TRUST_UNKNOWN', '-' );


/*********************************************************************/
/**
 * class GnuPGuid 
 *
 * This class contains user information about a key or signature
 *
 */

class GnuPGuid
{
	var $email_name = '';
	var $email_addr = '';
	var $email_extra = '';
	var $signatures = array();

       /***********************************************/
       /**
        * function GnuPGuid
        *
        * Constructor
        *
        * @param string $uidstring containing userid information
        *
        */
	function GnuPGuid ($uidstring) {
	    $j = substr_count($uidstring, "[User id not found]");
	    if ($j) { $this->email_name='Unknown'; return; }
	    $matches = split("[<>]", $uidstring);
	    switch (count($matches)) {
        	case 1:
        	    //Assume it's just an address.
        	    $this->email_addr = htmlspecialchars(trim($matches[0]));
        	    $this->email_name = htmlspecialchars(trim($matches[0]));
        	    break;
        	case 2:
        	    //Assume it's a name followed by an address.
        	    $this->email_name = htmlspecialchars(trim($matches[0]));
        	    $this->email_addr = htmlspecialchars(trim($matches[1]));
        	    break;
        	default:
        	    //Assume name, address, extra.
        	    $this->email_name = htmlspecialchars(trim($matches[0]));
        	    $this->email_addr = htmlspecialchars(trim($matches[1]));
        	    $this->email_extra = htmlspecialchars(trim(join(array_slice($matches, 2, (count($matches) - 2)), "")));
        	    break;
    	    }
	}

}

/*********************************************************************/
/**
 * class GnuPGsig
 *
 * This class contains information about a signature on data or a file
 *
 */

class GnuPGSignature
{
	var $valid=false;
	var $id=''; //fingerprint in hex
	var $creation_date=''; //sig_creation_date
	var $timestamp='';//sig-timestamp
	var $exp='';//expire-timestamp
	var $version='';//sig-versionreserved
	var $reserved='';//reserved
	var $pubkey_algorithm=0;//pubkey-algo
	var $algorithm = ''; //text of algorithm
	var $hash_algorithm=0;//hash-algo
	var $hash; //text of hash algorithm
	var $sigclass='';//sig-class
	var $fpr=''; //primary-key-fpr
	var $uid = false; //userid
       /***********************************************/
       /**
        * function GnuPGSignature
        *
        * Constructor, takes two types of parameters, either an array or a string
        *
        * @param array $keyInfo containing data about a signature 
        * @param string $keyInfo matching gpg: Signature made DATE by ALG key ID
        */
	
	function GnuPGSignature($keyInfo) {
		if (is_array($keyInfo)) {
			if ($keyInfo[1]=='VALIDSIG') {
				$this->valid=true;
				$this->id=$keyInfo[2];
				$this->creation_date = $keyInfo[3]; //date('Y-m-d',$keyInfo[3]);
				$this->timestamp = $keyInfo[4];
				$this->exp = $keyInfo[5];
				$this->version=$keyInfo[6];
				$this->reserved=$keyInfo[7];
				$this->pubkey_algorithm=$keyInfo[8];
				$this->hash_algorithm=$keyInfo[9];
				$this->sigclass=$keyInfo[10];
				$this->fpr=$keyInfo[11];
			} elseif ($keyInfo[1]=='ERRSIG') {
				$this->valid=false;
				$this->id=$keyInfo[2];
				$this->pubkey_algorithm=$keyInfo[3];
				$this->hash_algorithm=$keyInfo[4];
				$this->sigclass=$keyInfo[5];
				$this->timestamp=$keyInfo[6];
			}
			switch ($this->pubkey_algorithm) {
				case GNUPG_PUBKEY_RSA:
					$this->algorithm='RSA';
					break;
				case GNUPG_PUBKEY_RSA_S:
					$this->algorithm='RSA_S';
					break;
				case GNUPG_PUBKEY_ELGAMAL:
					$this->algorithm='ELGAMAL';
					break;
				case GNUPG_PUBKEY_DSA:
					$this->algorithm='DSA';
					break;
			}
			switch ($this->hash_algorithm) {
				case GNUPG_HASH_MD5: 
					$this->hash='MD5';
					break;
				case GNUPG_HASH_SHA1:
					$this->hash='SHA1';
					break; 
				case GNUPG_HASH_RMD160:
					$this->hash='RMD160';
					break;
				case GNUPG_HASH_TIGER:
					$this->hash='TIGER';
					break;
				case GNUPG_HASH_SHA256:
					$this->hash='SHA256';
					break;
				case GNUPG_HASH_SHA384:
					$this->hash='SHA384';
					break;
				case GNUPG_HASH_SHA512:
					$this->hash='SHA512';
					break;
			}
		} else {
			preg_match('/^gpg: Signature made (.*) using (.*) key ID (.*)/',$keyInfo,$matches);
			$this->id=$matches[3];
			$this->creation_date = $matches[1];
			$this->timestamp = strtotime($matches[1]);
			$this->algorithm=$matches[2];
			switch ($matches[2]) {
				case 'RSA':
					$this->pubkey_algorithm=GNUPG_PUBKEY_RSA;
					break;
				case 'RSA_S':
					$this->pubkey_algorithm= GNUPG_PUBKEY_RSA_S;
					break;
				case  'ELGAMAL':
					$this->pubkey_algorithm= GNUPG_PUBKEY_ELGAMAL;
					break;
				case 'DSA':
					$this->pubkey_algorithm= GNUPG_PUBKEY_DSA;
					break;
			}
		}
	}
}


/*********************************************************************/
/**
 * class GnuPGsig
 *
 * This class contains information about a signature on a key or uid
 *
 */

class GnuPGsig
{
	var $algorithm=0;
	var $id='';
	var $date='';
	var $exp='';
	var $uid = false;
	var $trust='';
       /***********************************************/
       /**
        * function GnuPGsig
        *
        * Constructor
        *
        * @param array $keyInfo containing data about a signature 
        *
        */
	
	function GnuPGsig($keyInfo) {
		$this->algorithm=$keyInfo[3];
		$this->id=$keyInfo[4];
		$this->date = date('Y-m-d',$keyInfo[5]);
		if ($keyInfo[6])
			$this->exp = date('Y-m-d',$keyInfo[6]);
		$this->uid = new GnuPGuid($keyInfo[9]);
		if (strpos ($keyInfo[10], 'l')) 
			$this->trust='local';
		if (strpos ($keyInfo[10],'x'))
			$this->trust='exportable';
	}
}

/*********************************************************************/
/**
 * class GnuPGkey
 *
 * This class contains information about a key and a function to output as an array
 *
 */

class GnuPGkey
{
	var $haveSecret = false;
	var $capabilities = array();
	var $fingerprint = '';
	var $userIDs = array();
	var $subkeys = array();
	var $signatures = array();
	var $userpassphrase = '';
	var $type=0;
	var $len=0;
	var $algorithm=0;
	var $id='';
	var $date='';
	var $exp='';
	// See defines above for possible values
	var $trust = '';

       /***********************************************/
       /**
        * function GnuPGkey 
        *
        * Constructor
        *
        * @param array $keyInfo containing data for the key 
        *
        */
	function GnuPGkey($keyInfo) {
             if( strpos( $keyInfo[11], 'E' ) )
                   $this->capabilities[] = "encrypt";
             if( strpos( $keyInfo[11], 'S' ) )
		  $this->capabilities[] = "sign";

             $this->type = $keyInfo[0];
             $this->trust = $keyInfo[1];
             $this->len = $keyInfo[2];
	     $this->algorithm = $keyInfo[3];
             $this->id = $keyInfo[4];
             $this->date = date('Y-m-d',$keyInfo[5]);
             if ($keyInfo[6])
                  $this->exp = date('Y-m-d',$keyInfo[6]);
	}
       /***********************************************/
       /**
        * function get_email_name 
        *
        * Retrieves name for the first or active uid on the key 
        *
        * @param void 
        *
        */
	function get_email_name() {
		if (count($this->userIDs) > 0) {
			$uid = current($this->userIDs);
			return $uid->email_name;
		} 
		else return '';
	}

       /***********************************************/
       /**
        * function get_email_addr 
        *
        * Retrieves email address for the first or active uid on the key 
        *
        * @param void 
        *
        */
	function get_email_addr() {
		if (count($this->userIDs) > 0) {
			$uid = current($this->userIDs);
			return $uid->email_addr; 
		}
		else return '';
	}

       /***********************************************/
       /**
        * function get_email_extra
        *
        * Retrieves extra information for the first or active uid on the key 
        *
        * @param void 
        *
        */
	function get_email_extra() {
		if (count($this->userIDs) > 0) {
			$uid = current($this->userIDs);
			return $uid->email_extra;
		}
		else return '';
	}

       /***********************************************/
       /**
        * function arrayKey
        *
        * Returns an array with key data indexed by field name 
        *
        * @param void 
	* @return array of particular key data indexed by field
        *
        */
	function arrayKey() {
		return array(
                   'type' => $this->type,
                   'len' => $this->len,
                   'id' => $this->id,
                   'date'=> $this->date,
                   'exp' => $this->exp,
                   'email_name' => $this->get_email_name(),
                   'email_addr' => $this->get_email_addr(),
                   'email_extra' => $this->get_email_extra(),
                   'alg' => $this->algorithm
               );
	}
	
	/***********************************************/
	/**
	* function get_fpr
	*
	* Returns a string containing the formatted fingerprint for the key
	*
	* @param void
	* @return string with lowercase fingerprint, split every 4 characters
	*
	*/
	function get_fpr() {
		$fpr = $this->fingerprint;
        	$pos=0;
        	$return='';
        	while ($pos < strlen($fpr)) {
        	        $return .= substr($fpr,$pos,4) . ' ';
        	        $pos=$pos+4;
        	}
        	return strtolower($return);
	}
}

/*********************************************************************/
/**
 * class GnuPG
 *
 * This is the main class to use for GPG functions and data
 *
 */
class GnuPG
{
	// Turn on some debug output
	var $debug = false;

	var $force_exec=false; //set true to disable proc_open facilities

	var $indata = '', $outdata='';
	var $gpg_pipes = array();
	var $pipeOpen = array();
	var $systemKeyring=false;
	var $alternateKeyring=false;
	var $alternateSecretKeyring=false;
	var $trustedKeys=array();
	var $gpg_exe = "/usr/bin/gpg";
	var $gpg_options = "--no-tty --yes --openpgp";
	var $gpgHomeDir = "";	// if you want to specify some other GnuPG home directory (use full path)
	var $comment = ''; // if you want a comment added to the command line sent to gpg
	var $tempDir = '';	//set this if you want to use a directory other than the standard TEMP dir
	var $tempFileMode = 0600;
	var $passphrase = false;
	var $newpassphrase = false;
	var $statusout = '', $stdout='', $stderr='';
	var $fileDescriptors = array (
		GPGSTDIN  => array( 'pipe', 'r' ),  // this is stdin for the child (We write to this one)
		GPGSTDOUT => array( 'pipe', 'w' ),  // child writes here (stdout)
		GPGSTDERR => array( 'pipe', 'w' ),  // stderr
		STATUS_FD => array( 'pipe', 'w' ),
		PASSPHRASE_FD => array( 'pipe', 'r' )
	);

    //These variables control the environmental variables that we try to set before executing the gpg binary
    //This is used now only for language, but could be extended to add shell environmental values, like gpg-agent strings
    var $lang_env_vars = array('LC_ALL'=>'en_US','LC_LANG'=>'en_US','LC_LANGUAGE'=>'en_US');
    //this var contains the previous values, for use when setting the value back to the original before the gpg binary was called
    var $lang_env_values= array();

	
    //allowEntropyIncrease sets whether or not to spawn extra ls processes to use the disk when generating keys
    var $allowEntropyIncrease=true;

    //sets the key preferences when generating new keys.  This is a sensible default
    var $defaultKeyPrefs="S2 S7 S3 H2 H3 Z2 Z1";

   // Temporary directory code
    var $tmpdir='/tmp';
    var $tmp_locations = array('/dev/shm', '/dev/mfs', '/tmp', '/var/tmp', 'c:\temp', 'c:\windows\temp', 'c:\winnt\temp');

	// Signature stuff
	var $signHashAlgo = 0, $signPubKeyAlgo = 0;

	// Keys stuff
	var $keys = false;
	var $arraykeys = false;
	var $defaultKeyFingerprint = '';

	//stateful information
	var $action=false;	// main way of keeping track of our state
	var $activeKey=false; //what key does gpg want the passphrase
	var $interactionData = array();
	var $newKeys = array(); // new keys created
	var $encryptKeys = array(); //keys used to encrypt message

	// error stuff
	var $error = false;
	var $errorCode = '';
	var $errorDescription = '';

	// Verify Stuff
	// Whenever we check something that's signed, the fingerprint and userid of the signed key goes here
	var $signedKeyFingerprint = '';
	var $signedKeyUserID = false;
	// can be "GOOD", "MISSING PUBLIC KEY", "EXPIRED", "BAD", "ERROR"
	var $verifyStatus = '';
	var $verifiedSignature=false;
	var $verifiedUserID=false;	
	// Encrypt Stuff
	var $invalidRecipients = array();	// array of recipients who didn't get processed properly

      /***********************************************/
       /**
        * function setHomeDir 
        *
	* set the GnuPG home directory
        *
        * @param string $newHomeDir containing path to gnupg home directory 
        *
        */

	function setHomeDir( $newHomeDir )
	{
		$this->gpgHomeDir = $newHomeDir;
	}
	    /***********************************************/
    /**
    * Adds a directory to the end of the list of directories used when attempting to find a writeable temp directory
    *
    * @param string $newTempDir containing path to gnupg home directory 
    *
    */

    function addTempDir($newTempDir) {
        if ($newTempDir) {
            $this->tmp_locations[]=$newTempDir;
        }
        $this->tmp_locations=array_unique($this->tmp_locations);
    }
    
    /***********************************************/
    /**
    * set the temporary directory (for very temporary output of files, which are securely overwritten)
    *
    * @param string $newTempDir containing path to gnupg home directory 
    *
    */

    function setTempDir($newTempDir) {
        $this->tmp_dir=$newTempDir;
    }

	function verifyDetachedSignature( $data=false, $signature )
	{
		$this->clearError();
		$this->verified = false;
		$this->verifyStatus = '';

		// first, we put the detached signature in a file
		// then we send the signed data as GPGSTDIN and tell GnuPG to get the 
		// signature from the file
		$detachedSignatureFilename = tempnam( $this->tempDir, "GPG" );
		if( $detachedSignatureFilename != "FALSE" )
		{
			chmod( $detachedSignatureFilename, $this->tempFileMode );
			
			$detachSigFile = fopen($detachedSignatureFilename,'w');
			fwrite( $detachSigFile, $signature );
			fclose( $detachSigFile );
		
			$this->printDebug( "executing verify detach signature" );
			$command = "--verify ".$detachedSignatureFilename." - ";

			$return = $this->execute_gpg( $command, $data );
			
			// delete our temp file
			unlink( $detachedSignatureFilename );
		}

		return $this->verified;
	}
	function verifyFileSignature($filename, $signature) {
		$this->action='verify';
		$params = "--verify - $filename";
		$return = $this->execute_gpg($params, $signature);

		return $return;
	}


	// verify an inline signature (clear-text signature)
	function verify( $data, $type="clear" )
	{
		$this->clearError();
		$this->verified = false;
		$this->verifyStatus = '';
		if ($type=="notclear") {
			$command = "--decrypt";
		} else {
			$command = "--verify";
		}

		$return = $this->execute_gpg( $command, $data );
		$return['GnuPGSignature'] = $this->verifiedSignature;
		$return['output'] = $this->stdout;
		return $return;
	}

	/***********************************************/
	/**
	 * lists the encryption keys used for encrypted data
	 *
	 * @param string $data containing encrypted data to find keys for
	 * @return array $return containing $return['encryptKeys'] with key_ids of recipients,
	 *	                         also contains $return['missingSecretKeys'] with key_ids of secret keys not on the included keyrings
	 **/
	function list_encrypt_keys( $data )
	{
		$this->clearError();
		$command = "--decrypt --list-only";
		$return = $this->execute_gpg( $command, $data );
		$return['encryptKeys'] = $this->encryptKeys;
		$return['missingSecretKeys'] = $this->interactionData['missingSecretKeys'];
		return $return;
	}
       /***********************************************/
       /**
        * function verifyPassphrase
        *
        * Verifies a passphrase for a secret key
	* The default key is used if no secret key is specified 
        *
        * @param string $passphrase containing passphrase to verify
	* @param string $keyfpr containing fingerprint of secret key 
	* @param array $return containing $return['verified'] equal to 'true'
	*		if passphrase is verified or 'false' if not
        *
        */
	function verifyPassphrase( $passphrase, $keyfpr=false) {
            if ($passphrase)
		  $this->passphrase=$passphrase;
                  
		$return = $this->sign('Authenticated',$keyfpr);
		$return['verified'] = 'false';
		if ($return['output']) {
			$sep = '-----BEGIN PGP SIGNED MESSAGE-----';
			list ($front, $cyphertext_tail) = explode ($sep, $return['output']);
			if ($cyphertext_tail) {
			        $return['verified'] = 'true';
			} else {
				$return['errors'][] = $return['output'];
			}
		} //else { $return['errors'][] = _("Error: no output received, cannot verify passphrase"); }
		return $return;
	}
	/*********************************************************************/
	/**
	* function decrypt - This function does the decryption.
	*
	* This is the workhorse of decryption 
	*
	* @param string $body          Body String to decrypt
	* @param string $passphrase    Passphrase to pass to gpg
	* @param optional string $filename    Filename to decrypt binary file
	* @return array with results
	*/
	function decrypt($body, $passphrase, $filename='', $outfile='')
	{
		$params='';
		$this->action="decrypt";
		
		if ($filename=='') {
			$params.= " --decrypt";
		} else {
			if ($outfile== '') {
				$params.=" --use-embedded-filename --decrypt-files $filename";
			} else { $params .=" --output \"$outfile\" --decrypt \"$filename\""; }
		}
                if ($passphrase) {
        		$this->passphrase= $passphrase;
                }
		$return=$this->execute_gpg($params, $body);
		$return['plaintext'] = $return['output'];
		if ($this->verifiedSignature) {
			$return['GnuPGSignature'] = $this->verifiedSignature;
		}
		return $return;
		
	}
       /***********************************************/
       /**
        * function signFile
        *
        * Clearsigns and ascii-armors a signature on an external file
        *
        * @param string $filename containing path of file to sign
        * @param string $passphrase containing passphrase to secret key
        * @param string $signingKey containing fingerprint of key to use for signing
        * @return array $return containing $return['output'] with clearsigned data
        *
        */
	function signFile($filename=false, $passphrase=false, $signingKey=false)
	{
		if (!$filename) { return false; }
		if (!$signingKey) {
			$this->printDebug("No key specified for siging, using default: " . $this->defaultKeyFingerprint);
			$signingKey=$this->defaultKeyFingerprint;
		}
		$sigfilename=$filename.".asc";
		if (file_exists($sigfilename)) {
			unlink($sigfilename);
		}
		$params = "--armor";
		if (!$this->keys[$signingKey]) {
			$this->refreshKeys($signingKey);
		}
		$signingKey=$this->getKeyIndexFromFingerprint($signingKey);
		$this->action="sign";
		if ($signingKey) {
			$params.= " --default-key $signingKey";
                        if ($passphrase) {
         			$this->keys[$signingKey]->passphrase=$passphrase;
                        }
		} else {
                    if ($passphrase) {
			$this->passphrase = $passphrase;
                    }
		}
		$params .=" --detach-sign $filename";
		$return = $this->execute_gpg($params);
		if (is_file($sigfilename)) {
			$return['filename']  = $sigfilename;
		}
		return $return;
	}
			
       /***********************************************/
       /**
        * function sign 
        *
        * Clearsigns and ascii-armors passed data with a secret key 
        *
        * @param string $data containing information to sign
	* @param string $signingKey containing fingerprint of key to use for signing
	* @return array $return containing $return['output'] with clearsigned data
        *
        */
	function sign( $data = false, $signingKey = false, $passphrase='', $type='clear')
	{
		$this->clearError();
		$commandExtras='';
//		if( $this->defaultKeyFingerprint != '' )
//			$commandExtras = '--default-key '.$defaultKeyFingerprint.' ';
		if (!$signingKey) {
			$this->printDebug("No key specified, going with default key: " . $this->defaultKeyFingerprint);
			$signingKey=$this->defaultKeyFingerprint;
		}
		if ($signingKey) {
			if (!$this->keys[$signingKey]) {
				$this->refreshKeys($signingKey);
				$signingKey=$this->getKeyIndexFromFingerprint($signingKey);
                                $signingKey=strtoupper($signingKey);
			}
                        if ($passphrase)
			$this->keys[$signingKey]->passphrase=$passphrase;
		} else { if ($passphrase) $this->passphrase=$passphrase; }

		$this->action="sign";
		if ($signingKey) {
			$commandExtras = '--default-key '.$signingKey.' ';
		}
			$command = $commandExtras."--armor";
			switch ($type) {
				case 'notclear':
					$command .= " --sign";
					break;
				case 'clear':
				default:
					$command .= " --clearsign";
					break;
			}
		

		$return = $this->execute_gpg( $command, $data );

		return $return;
	}
	/*********************************************************************/
	/**
	*
	* function update_trustdb()
	*
	* This function will update the gpg trustdb for the current user
	* including a scan of the system keyring if enabled
	*
	* @return array $return containing info, warnings, etc
	*/
	function update_trustdb() 
	{
	   $params = '';
	   
	    
	    //now add our check trustdb command
	    $params .= ' --check-trustdb';
	    return $this->execute_gpg($params); 
	}
	
	/*********************************************************************/
	/**
	 * function encrypt
	 * This function does the encryption
	 * This is the workhorse of the encryption side of the plugin
	 *
	 * Add code here to use user preferences to modify the gpg command line
	 *
	 * @param string  $body         Body text string
	 * @param array  $send_to      containing recipient list
	 * @param optional boolean $sign         (true/false) do we want to sign the message/file
	 * @param optional string  $passphrase   passphrase string needed for signing functions
	 * @param optional string  $filename     if we are going to encrypt a file
	 * @return array with results
	 */
	function encrypt($body,$send_to_list, $sign='false', $passphrase=false, $filename ='',$signingkeyfpr=false) 
	{
	    if (!$signingkeyfpr) {
		$signingkeyfpr=$this->defaultKeyFingerprint;
	    }
            if ($signingkeyfpr && $sign) {
	        if (!$this->keys[$signingkeyfpr]) {
			$this->refreshKeys($signingkeyfpr);
			$signing_key_id=$this->getKeyIndexFromFingerprint($signingkeyfpr);
		}
		$this->keys[$signing_key_id]->passphrase = $passphrase;
	    }
	    $this->interactionData['encrypt_to_list']=$send_to_list;
	    $send_to_list_param = implode(' -r ',$send_to_list);
	    $this->printDebug("Encrypting to: $send_to_list_param");
            $params='';
	    //add the signing parameters
	    if ($sign=='true' and $signing_key_id and $filename!='') {
	    	$params  .= "  --sign --default-key $signing_key_id ";
	    } elseif ($sign=='true' and $signing_key_id) {
		$params  .= "  --output - --sign --default-key $signing_key_id ";
	    } elseif ($sign=='true') {
		$return['errors'][] = _("GPG Plugin: You must specify a signing key in the Options screen to sign messages.");
	    };
	    //add the trusted key parameters if needed
	    if (!(count($this->trustedKeys)>0)) {
	    	$params  .= ' --always-trust ';
	    };

	    // wrap it up by setting the recipients to the sender list
	    // and redirect the output to stderr using 2>&1
	    $params  .= " --force-mdc --armor --encrypt -r $send_to_list_param ".escapeshellarg($filename);
	    $this->action="encrypt";
            if ($passphrase)
    	       $this->passphrase=$passphrase;
               
	    $return=$this->execute_gpg($params,$body);
	    $return['cyphertext']=$return['output'];
	    return $return;
	}
    /***********************************************/
    /**
     * function deleteKey
     *
     * This function deletes a key from the keyring in the homedir
     * It will not remove the key from the keys list, so a refreshkeys is need to reflect the new status of the keyring
     *
     * @param string $fpr
     * @param string $type
     * @return array $return containing errors, warnings, etc 
     */
    function deleteKey($fpr, $type="all") 
    {
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
	$params = "--yes --batch $flag $fpr";
	return $this->execute_gpg($params);
    }

	/***********************************************/
       /**
        * function deleteUID
        *
        * Deletes a UID from a key
        *
        * @param string $fpr containing fingerprint or key id of the key with the uid to delete
        * @param string $uidnos containing the uid number to delete 
        * @return array $return containing errors and output
        *
        */
	function deleteUID($fpr, $uidnos)
	{
		$this->action="delUID";
		$params="--edit-key $fpr $uidnos deluid";
		$return=$this->execute_gpg($params);
		return $return;
	}
       /***********************************************/
       /**
        * function addUID
        *
        * Adds a UID to a key 
        *
        * @param string $fpr containing fingerprint or key id of the private key for which to add a uid 
	* @param string $passphrase containing passphrase to the secret key
	* @param string $uidname containing the name associated with this uid
	* @param string $uidemail containing the email address associated with this uid
	* @param string $comment containing a comment or extra information about the uid
        * @return array $return containing errors and output
        *
        */
	function addUID($fpr, $passphrase, $uidname, $uidemail, $comment)
	{
		if (!$this->keys[$fpr]) {
			$this->refreshKeys($fpr);
			$fpr = $this->getKeyIndexFromFingerprint($fpr);
		}
		$this->keys[$fpr]->passphrase=$passphrase;
		$this->action='addUID';
		$this->interactionData['name'] = $uidname;
		$this->interactionData['email'] = $uidemail;
		$this->interactionData['comment'] = $comment;
		$params = "--edit-key $fpr adduid";
		$return = $this->execute_gpg($params);
		return $return;
	}
	/*******************************************************/
	/**
	* function expireKey
	*
	* Sets the expiration on a primary key
	*
	* @param string $fpr containing fingerprint or key id of the primary key to set expiration date on
	* @param string $passphrase containing passphrase for the secret key
	* @param string $expiration containing string of either # for days or #w for weeks or #y for years
	* @return array $return containing info, errors, etc
	*/
	function expireKey($fpr, $passphrase, $expiration)	
	{
		if (!$this->keys[$fpr]) {
			$this->refreshKeys($fpr);
			$fpr = $this->getKeyIndexFromFingerprint($fpr);
		}
		$this->keys[$fpr]->passphrase=$passphrase;
		$this->action='expireKey';
		$this->interactionData['valid'] = $expiration;
		return $this->execute_gpg("--edit-key $fpr");
	}
        /*******************************************************/
        /**
        * function expireSubKey
        *
        * Sets the expiration on a secondary key
        *
        * @param string $fpr containing fingerprint or key id of the primary key to set expiration date on
	* @param string $subkey containing index of subkey to expire
        * @param string $passphrase containing passphrase for the secret key
        * @param string $expiration containing string of either # for days or #w for weeks or #y for years
        * @return array $return containing info, errors, etc
        */
	function expireSubKey($fpr, $subkey, $passphrase, $expiration)
	{
		if (!$this->keys[$fpr]) {
			$this->refreshKeys($fpr);
			$fpr = $this->getKeyIndexFromFingerprint($fpr);
		}
		$this->keys[$fpr]->passphrase=$passphrase;
		$this->action='selectSubKey';
		$this->interactionData['action'] = 'expireKey';
		$this->interactionData['subkeyno'] = $subkey;
		$this->interactionData['valid'] = $expiration;
		return $this->execute_gpg("--edit-key $fpr");
	}
       /***********************************************/
       /**
        * function deleteSubKey
        *
        * Deletes a subkey pair from a main keypair
        *
        * @param string $fpr containing fingerprint or key id of the private key from which to delete a subkey
        * @param integer $subkeyno containing the offset of subkey to delete (1 is the first); 
        * @return array $return containing errors and output
        *
        */

        function deleteSubKey($fpr, $subkeyno)
	{
		$this->action="selectSubKey";
		$this->interactionData['action']='deleteSubKey';
		$this->interactionData['subkeyno'] = $subkeyno;
		$params = "--edit-key $fpr";
		$return = $this->execute_gpg($params);
		return $return;
	}

	/*********************************************************************/
	/**
	 * function generateKey
	 * This function generates a keypair
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
	 * @return array $return with output we were able to retrieve from the gpg command including $return['newkeys'] first element being fingerprint of new key generated, if available
	 *
	 */

	function generateKey($name, $email, $passphrase, $comment = '', $keylength = 1024, $expiredate=0,$algo=1,$prefs=false)
	{
        if ($this->force_exec) {
            //use alternate method if we aren't using pipes
            return $this->generateKey_nopipes($name, $email, $passphrase, $comment, $keylength, $expiredate, $prefs);
        }
		$this->action="generateKey";
		$this->newpassphrase=$passphrase;
		$this->interactionData['name']=$name;
		$this->interactionData['email']=$email;
		$this->interactionData['comment']=$comment;
		$this->interactionData['algo']=$algo;
		$this->interactionData['size']=$keylength;
		$this->interactionData['valid']= $expiredate;
		$return= $this->execute_gpg('--gen-key');
		$return['newkeys'] = $this->newKeys;
		$fpr=$this->newKeys[0];
		$this->setKeyPrefs($fpr, $prefs, $passphrase);
		return $return;
	}

    function generateKey_nopipes($name, $email, $passphrase, $comment, $keylength=1024, $expiredate=0, $prefs=false)
    {

        $this->refreshKeys('','secret');
        $lastkeys=array_keys($this->keys);

        $this->action="generateKey_nopipes";
        $this->newpassphrase=$passphrase;
        if (!$prefs) $prefs=$this->defaultKeyPrefs;
        $data='';
        $data.="Key-Type: DSA\n";
        $data.="Key-Length: 1024\n";
        $data.="Subkey-Type: ELG-E\n";
        $data.="Subkey-Length: " . $keylength . "\n";
        $data.="Name-Real: " . $name . "\n";
        if ($comment)
            $data.="Name-Comment: " . $comment . "\n";
        $data.="Name-Email: " . $email . "\n";
        $data.="Expire-Date: ". $expiredate ."\n";
        $data.="Passphrase: " . $passphrase . "\n";
        $data.="Preferences: $prefs\n";
        $data.="%commit\n";

        $cmd="--gen-key --batch --armor";
        $return= $this->execute_gpg($cmd, $data);

        //check for new keys
        $this->refreshKeys('','secret');

        $diff=array_diff(array_keys($this->keys), $lastkeys);
        if (count($diff)==0) { $return['errors']=_("Keys did not generate.  Please contact your system administrator for assistance debugging this."); }

        foreach ($diff as $newfpr) {
            $this->newKeys[]=$newfpr;
        }

        $return['newkeys'] = $this->newKeys;
        $fpr=$this->newKeys[0];
        return $return;
    }

	/*********************************************************************/
	/**
	 * function gpg_generate_keypair
	 * This function generates a keypair
	 *
	 * @param string $fpr containing fingerprint of key to change preferences on
	 * @param string $prefs containing space seperated list of parameters, defaults to S2 S7 S3 H2 H3 Z2 Z1
	 * @param string $passphrase containing passphrase to decrypt and change key with
	 * @return @array $return containing output, erros, etc
	 *
	 */
	function setKeyPrefs($fpr, $prefs=false,$passphrase) 
	{
		if (!$prefs) {
            $prefs=$this->defaultKeyPrefs;
		}
		if (!$this->keys[$fpr]) {
			$this->refreshKeys($fpr);
			$fpr=$this->getKeyIndexFromFingerprint($fpr);
		}
		$this->action="setKeyPref";
		$this->keys[$fpr]->passphrase=$passphrase;
		$this->interactionData['keyprefs'] = $prefs;
		return $this->execute_gpg("--edit-key $fpr");
	}
       /***********************************************/
       /**
        * function addSubKey
        *
        * Adds a subkey pair to a main keypair
        *
        * @param string $privatekey containing fingerprint or key id of the private key for which to add a subkey
        * @param string $passphrase containing the passphrase to the secret key
	* @param integer $type containing what type of key to create (default of (3) ElGamal (encrypt only))
	* @param integer $keysize containing size of key to create
	* @param string $valid containing length of time the key is valid, # of days or 1y for 1 year 
        * @return array $return containing errors and output, $return['newkeys'] array of new key fingerprints
        *
        */
	function addSubKey($privatekey, $passphrase, $algo=3, $keysize, $expiredate) 
	{
		if (!$this->keys[$privatekey]) {
			$this->refreshKeys($privatekey);
			$privatekey=$this->getKeyIndexFromFingerprint($privatekey);
		}
		if ($this->keys[$privatekey]->haveSecret) {
			$this->action="addSubKey";
			$this->keys[$privatekey]->passphrase=$passphrase;
			$this->interactionData['algo'] = $algo;
			$this->interactionData['size'] = $keysize;
			$this->interactionData['valid'] = $expiredate;
			$params = "--edit-key $privatekey addkey";
			$return= $this->execute_gpg($params);
			$return['newkeys'] = $this->newKeys;
			return $return;
		} else {
			$this->printDebug("No secret key available");
			return array('errors'=>array('No secret key available'));
		}
	}

       /***********************************************/
       /**
        * function addRevoker 
        *
        * Adds a revoking key to a private key 
        *
        * @param string $privatekey containing fingerprint or key id of the private key for which to set a revoker 
        * @param string $revokingkey containing fingerprint or key id of the key to use as revoker 
        * @param string $passphrase containing the passphrase to the secret key
        * @return array $return containing errors and output 
        *
        */
	function addRevoker($privatekey, $revokingkey, $passphrase) 
	{
		$this->refreshKeys('','all');
		$privatekey=$this->getKeyIndexFromFingerprint($privatekey);
		$revokingkey=$this->getKeyIndexFromFingerprint($revokingkey);
		$this->action="addRevoker";
		$this->keys[$privatekey]->passphrase=$passphrase;
		$this->activeKey = $revokingkey;
		$params = "--edit-key $privatekey addrevoker";
		return $this->execute_gpg($params);	

	}
        /**
        * function setPrimaryUID
        *
        * Sets the uid number specified as the primary UID
        *
        * @param string $keyfpr
        * @param integer $uid
	* @param string $passphrase
        *
        * @return array $return with errors, warnings, output
        */
	function setPrimaryUID($keyfpr, $uid, $passphrase)
	{
		if (!$this->keys[$keyfpr]) {
			$this->refreshKeys($keyfpr,'all');

			$keyfpr=$this->getKeyIndexFromFingerprint($keyfpr);
		}
//		if ($this->keys[$keyfpr]->haveSecret) {
		$this->action="setPrimaryUID";
		$this->keys[$keyfpr]->passphrase=$passphrase;
		$params = "--edit-key $keyfpr $uid primary";
		return $this->execute_gpg($params);
//		} else return array ( 'errors'=>array(0=>"No secret key available for $keyfpr") );
	}
	
	/**
	* function getExportText
	*
	* Exports a key defined by $fpr to ASCII Armored text.
	*
	* @param string $fpr
	* @param enum $ringname
	*
	* @return array $return with $return['output'] containing ascii-armored key
	*/
	function getExportText($fpr) {
		$exportstring='';

	        // make sure there aren't any funny characters in fingerprint
	        $fpr = escapeshellarg($fpr);

	        //Make the command and execute.
	        $params  = "--armor $exportstring --export $fpr";
	        $return=$this->execute_gpg($params);
		return $return;
	}	
       /***********************************************/
       /**
        * function uploadKey 
        *
        * Uploads a public key to a keyserver
        *
        * @param string $fpr containing fingerprint or keyid to upload
	* @param string $keyserver containing name of server to upload to
	* @return array $return containing errors, info, etc
	*/
	function uploadKey($fpr,  $keyserver) 
	{
		if (!$this->keys[$fpr]) {
			$this->refreshKeys($fpr);
			$fpr=$this->getKeyIndexFromFingerprint($fpr);
		}
		$this->action="uploadKey";
		$cmd = "--keyserver hkp://$keyserver --send-keys $fpr";
		return $this->execute_gpg($cmd);
	}
       /***********************************************/
       /**
        * function signUID
        *
        * Adds a signature to a specific or multiple uids on  a public key
        * The key is signed by the default secret key if no secret key is specified
        * By default the signature is exportable and revokable
        *
        * @param string $signedKeyfpr containing fingerprint of the key to sign
	* @param string $uids containing space seperated list of uids
        * @param string $signingKeyfpr containing fingerprint of secret key to use for signing
        * @param string $passphrase containing the passphrase to the secret key
        * @param bool $exportsig flags the signature exportable, true by default
        * @param bool $revokable flags the signature revokable, true by default
        * @return array $return containing errors and output
        *
        */
	function signUID($signedKeyfpr,$uids='',$signingKeyfpr=false,$passphrase=false,$exportsig=true,$revokable=true) {
		return $this->signKey($signedKeyfpr,$signingKeyfpr,$passphrase,$exportsig,$revokable,$uids);
	}

       /***********************************************/
       /**
        * function signKey 
        *
        * Adds a signature to a public key
	* The key is signed by the default secret key if no secret key is specified
	* By default the signature is exportable and revokable
        *
        * @param string $signedKeyfpr containing fingerprint of the key to sign
	* @param string $signingKeyfpr containing fingerprint of secret key to use for signing
	* @param string $passphrase containing the passphrase to the secret key
	* @param bool $exportsig flags the signature exportable, true by default
	* @param bool $revokable flags the signature revokable, true by default
	* @return array $return containing errors and output 
        *
        */
	function signKey($signedKeyfpr,$signingKeyfpr=false,$passphrase=false,$exportsig=true,$revokable=true,$uids='') {
		$signtype='sign';
		if (!$revokable) $signtype = 'nr' . $signtype;
		if (!$exportsig) $signtype = 'l' . $signtype;

		$this->printDebug("signing started, refreshing...");
		// read keys from gpg 
		$this->refreshKeys(); //$signingKeyfpr . ' ' . $signedKeyfpr);
		if (!$signingKeyfpr) {
			// use default key fingerprint if none is specified
			$this->printDebug("Going with default " . $this->defaultKeyFingerprint);
			$signingKeyfpr = $this->defaultKeyFingerprint;
		}
		//ensure we have the secret key to sign with
		if ($this->keys[$signingKeyfpr]->haveSecret) {
			if ($passphrase) {
				//set passphrase on the secret key
				$this->keys[$signingKeyfpr]->passphrase=$passphrase;
			}
			$command = " -u " . $this->keys[$signingKeyfpr]->id . ' --edit-key ' . $this->keys[$signedKeyfpr]->id . ' ' . $uids . $signtype;
			$this->printDebug("Going with the signing command $command.");
			//run command into gpg
			$this->action="signKey";
			$return = $this->execute_gpg($command );
		}
		else { $return['errors'][] = _("Key with Fingerprint ") . $this->keys[$signingKeyfpr] . _(" has no secret key associated with it."); 
			$this->printDebug("No secret key found, ending without signing.");	
		}
		return $return;
	}
       /***********************************************/
       /**
        * function changePassphrase 
        *
        * Changes the passphrase on a secret key 
        *
        * @param string $keyfpr containing fingerprint of the secret key to changne
	* @param string $passphrase containing the current passphrase to the secret key
	* @param string $newpassphrase containing the new passphrase to the secret key
	* @return array $return containing $return['verified'] equal to 'true' if successful or 'false' if not
        *
        */
	function changePassphrase($keyfpr,$passphrase,$newpassphrase) {
		//load key information
		if (!$this->keys[$keyfpr]) {
			$this->refreshKeys($keyfpr);
		}

		$this->printDebug("Change Passphrase: verifying");
		//verify that the current passphrase is correct
		$return=$this->verifyPassphrase($passphrase,$keyfpr);
		$this->printDebug("Verification: " . $return['verified']);
		//if not correct, return an error
		if ($return['verified']!='true') { $return['errors'][] = _("Bad Passphrase"); return $return; }
		//set our intended action.  Set after refresh keys so it isn't reset
		$this->action='changePassphrase';
		//if the key fingerprint sent isn't the size of a fingerprint, find the fingerprint
		if (!(strlen($keyfpr)>16)) { $keyfpr = $this->getKeyIndexFromFingerprint($keyfpr); }
		//ensure we have the secret key
		if ($this->keys[$keyfpr]->haveSecret) {
			//set passphrase on the secret key
			$this->keys[$keyfpr]->passphrase=$passphrase;
			$this->newpassphrase=$newpassphrase;
			$command = "--edit-key $keyfpr passwd";

			$return=$this->execute_gpg($command);
		} else { $this->printDebug("No secret key found for $keyfpr"); }

		//verify that the new passphrase is now correct for the key
		$return=$this->verifyPassphrase($newpassphrase,$keyfpr);
		return $return;
	}
		
       /***********************************************/
       /**
        * function readStatus 
        *
        * Reads and loops on the status pipe from gpg.
	* Contains the main loop for interaction with gpg with pipes 
        *
        * @param void 
        *
        */
	function readStatus( )
	{
		
		$this->printDebug( "readStatus()");
		$this->statusout = '';
		while( $this->pipeOpen[ STATUS_FD ] == true && !feof( $this->gpg_pipes[ STATUS_FD ] ) ) {
			$this->printDebug("Starting read status loop.");
			//create array of pipes to check for data
			$readArray = array($this->gpg_pipes[STATUS_FD], $this->gpg_pipes[GPGSTDOUT]);
			//check pipes for data, readArray is changed to include only pipes which have data
			$this->printDebug("Checking if read would block on pipes");
			$numRead = stream_select($readArray, $write=NULL, $except=NULL, 10);
			$this->printDebug("Streams read for reading: $numRead");
			if ($numRead !== false) {
				foreach ($readArray as $pipe) {
					//Do not block when reading these pipes
					stream_set_blocking($this->gpg_pipes[STATUS_FD],FALSE);
					if ($this->gpg_pipes[STATUS_FD] == $pipe) {
						$this->printDebug("Reading Status");
						stream_set_blocking($this->gpg_pipes[STATUS_FD],FALSE);
						$thisStatusLine = fgets( $this->gpg_pipes[ STATUS_FD ] );
						$this->printDebug( "readStatus: ".$thisStatusLine );
						$this->parseStatusLine( $thisStatusLine );
						//append status output to record
						$this->statusout .= $thisStatusLine;
					}
					if ($this->gpg_pipes[GPGSTDOUT] == $pipe) {
//						$this->printDebug("GPGSTDOUT ready for reading.");
						stream_set_blocking($this->gpg_pipes[GPGSTDOUT],FALSE);
						while (!feof($this->gpg_pipes[ GPGSTDOUT ])) {
							$line=fgets( $this->gpg_pipes[ GPGSTDOUT ]);
							if (strlen($line)>0) { $this->printDebug("GPGSTDOUT: $line"); }
							$this->stdout .= $line;

//							if (strlen(trim($line))==0) break;
						}
						//$this->stdout .= $this->readData(GPGSTDOUT);
					}
				}
			}
			if ($this->writingData && ($numRead==false)) break;
		}
	}
	/**********************************************/
	/**
	* function increaseEntropy
	*
	* Runs commands to increase entropy for gpg
	* definitely needs to be made more robust, currently lists filesystem
	*
	*
	*/
	function increaseEntropy()
	{
        if ($this->allowEntropyIncrease) {
            $this->printDebug("Increasing Entropy");
            //maybe change /usr to something else?
            exec("ls -lR /usr > /dev/null &");
        }
	}

       /***********************************************/
       /**
        * function parseStatusLine 
        *
        * Parse a line from GnuPG's status file descriptor and act on it 
        *
        * @param string $line containing status output from GnuPG 
        *
        */
	function parseStatusLine( $line )
	{
		$line = trim( $line );
		$param = preg_split("/[\s]+/", $line );
		if( $param[0] == "[GNUPG:]" )
		{
			// parse it!
			switch ($param[1])
			{
				case "PROGRESS":
					if (($param[2]=='primegen') && ($param[3]=='X') && ($param[4]=='100') && ($param[5]=='100')) {
						$this->increaseEntropy();
					}
					break;
				case "KEY_CREATED":
					$this->newKeys[] = $param[3];
					break;
				case "ENC_TO":
					$this->encryptKeys[] = $param[2];
					break;
				case "NO_SECKEY":
					$this->interactionData['missingSecretKeys'][]=$param[2];
					break;
				case "IMPORT_OK":
					$this->newKeys[] = $param[3];
					break;
				case "IMPORT_RES":
					$count = $param[2];
					$no_user_id = $param[3];
					$imported = $param[4];
					$imported_rsa = $param[5];
					$unchanged = $param[6];
					$n_uids=$param[7];
					$n_subks=$param[8];
					$n_sigs=$param[9];
					$n_revoc=$param[10];
					$sec_read=$param[11];
					$sec_imported=$param[12];
					$sec_dups=$param[13];
					$not_imported=$param[14];
					break;
				case "IMPORTED":
					break;	
				case "END_DECRYPTION":
					//encryption ended, read data from stdout
			//		$this->stdout .= $this->readData(GPGSTDOUT);
					break;
				case "BEGIN_ENCRYPTION":
					//beginning encryption, start reading data from stdout
			//		$this->stdout .= $this->readData(GPGSTDOUT);
                $this->printDebug("Encryption begun.");
                break;
				case "BEGIN_DECRYPTION":
				//	$this->stdout .= $this->readData(GPGSTDOUT);
					$this->printDebug("Decryption begun.");
					break;
				case "NEED_PASSPHRASE":
					//passphrase requested by gpg, store keyID requested
					$this->activeKey=$this->getKeyIndexFromFingerprint($param[2]);
					$this->printDebug("GPG requests passphrase for key " . $param[2] . " setting as active Key");
					break;
				case "NEED_PASSPHRASE_SYM":
					//PASSPHRASE_SYM is requested when changing the passphrase
					switch ($this->action) {
						case "changePassphrase":
						case "generateKey":
							// Need a new passphrase
							$this->writeNewPassword();
							break;
					}
					break;
				case "GET_HIDDEN":
					switch($param[2])
					{
						case "passphrase.enter":
							//passphrase prompt
							switch($this->action) {
								case 'changePassphrase':
								case 'updatedKeyPref':
								case 'expiredKey':
								case 'sign':
								case 'encrypt':
								case 'signKey':
								case 'addRevoker':
								case 'generateKey':
								case 'setPrimaryUID':
								case 'addUID':
								case 'delUID':
								case 'addSubKey':
								case 'decrypt':
									$this->printDebug("GPG requests passphrase, sending active key " . $this->activeKey . " .");
									//send the passphrase
									$this->writePassword($this->activeKey);
									break;
								default:
									$this->printDebug("This action: " . $this->action . " isn't written to allow passing of the passphrase.");
									$this->closePipe(PASSPHRASE_FD);
									$this->setError("NOSTATUS","Couldn't handle the status");
									break;
							}
							break;
					}
                break;
				case "GET_LINE":
					switch($param[2])
					{
						case "pklist.user_id.enter":
							 if (count($this->interactionData['encrypt_to_list'])>0)  {
								$this->writeData(array_shift($this->interactionData['encrypt_to_list']) . "\n",PASSPHRASE_FD);
							} else {
								$this->writeData("\n",PASSPHRASE_FD);
							}
							break;
						case "keygen.name":
							$this->writeData($this->interactionData['name'] . "\n",PASSPHRASE_FD);
							break;
						case "keygen.email":
							$this->writeData($this->interactionData['email'] . "\n",PASSPHRASE_FD);
							break;
						case "keygen.comment":
							$this->writeData($this->interactionData['comment'] . "\n",PASSPHRASE_FD);
							break;
						case "keygen.algo":
							$this->writeData($this->interactionData['algo']."\n",PASSPHRASE_FD);
							break;
						case "keygen.size":
							$this->writeData($this->interactionData['size'] . "\n",PASSPHRASE_FD);
							break;
						case "keygen.valid":
							$this->writeData($this->interactionData['valid'] . "\n",PASSPHRASE_FD);
							break;
						case "keyedit.prompt":
						// keyedit prompt, save and quit
							switch($this->action) {
								case "setKeyPref":
									$this->writeData("setpref ". $this->interactionData['keyprefs'] . "\n",PASSPHRASE_FD);
									$this->action="updateKeyPref";
									break;
								case "updateKeyPref":
									$this->writeData("updpref\n", PASSPHRASE_FD);
									$this->action="updatedKeyPref";
									break;
								case "expireKey":
									$this->writeData("expire\n", PASSPHRASE_FD);
									$this->action="expiredKey";
									break;
								case "deleteSubKey":
									$this->writeData("delkey\n", PASSPHRASE_FD);
									$this->action="deletedSubKey";
									break;
								case "selectSubKey":
									if ($this->interactionData['subkeyno']) {
										$this->writeData('key ' . $this->interactionData['subkeyno'] . "\n",PASSPHRASE_FD);
										$this->action = $this->interactionData['action'];
										break;
									}
								default:
									$this->confirmSave();
									break;
							}
							break;
						case "sign_uid.expire":
							//signature expired, confirm ok
							$this->writeOkay();
							break;
						case "ask_revocation_reason.code":
							$this->writeData($this->interactionData['ask_revocation_reason.code']."\n",PASSPHRASE_FD);
							break;
						case "ask_revocation_reason.text":
							//if we still have lines left in our reason array, shift off the top one and write it to gpg
							if (count($this->interactionData['ask_revocation_reason.text'])>0) {
								$this->writeData(array_shift($this->interactionData['ask_revocation_reason.text'])."\n",PASSPHRASE_FD);
							} else { 
								//otherwise write last empty line to stop loop
								$this->writeData("\n",PASSPHRASE_FD); 
							}	
							break;
						case "sign_uid.class":
							//request for what level of confirmation done on the identity of the user
							$this->writeCheckLevel();
							break;
						case "sign_uid.okay":
							//confirm to sign a uid on a key
							$this->writeOkay();
							break;
						case "keyedit.sign_all.okay":
							//confirm to sign all uids on a key
							$this->writeOkay();
							break;
						case "keyedit.add_revoker":
							$this->printDebug("Writing revoking key: " . $this->activeKey . " email address: " .  $this->keys[$this->activeKey]->get_email_addr());
							$this->writeData($this->keys[$this->activeKey]->get_email_addr() . "\n",PASSPHRASE_FD);
							break;
					}
                break;
				case "GET_BOOL":
					switch($param[2])
					{
						case "delete_key.secret.okay":
							//confirm to delete a secret key
							$this->writeOkay();
							break;
						case "delete_key.okay":
							//confirm to delete a public key
							$this->writeOkay();
							break;
						case "untrusted_key.override":
							//confirm to use an untrusted key
							$this->writeOkay();
							break;
						case "keyedit.sign_all.okay":
							$this->writeOkay();
							break;
						case "sign_uid.okay":
							//confirm to sign a uid
							$this->writeOkay();
							break;
						case "delete_uid.okay":
							$this->writeOkay();
							break;
						case "keyedit.updpref.okay":
							$this->writeOkay();
							break;
                        case "keyedit.setpref.okay":
                            $this->writeOkay();
                        break;
						case "keyedit.add_revoker.okay":
							$this->writeOkay();
							break;
						case "keyedit.remove.subkey.okay":
							$this->writeOkay();
							break;
						case "keyedit.remove.uid.okay":
							$this->writeOkay();
							break;
						case "ask_revocation_reason.okay":
							$this->writeOkay();
							break;
						case "keyedit.revoke.subkey.okay":
							$this->writeOkay();
							break;
					}
					continue;
				case "KEYEXPIRED":	
//					$this->stdout .= $this->readData(GPGSTDOUT);
					$this->printDebug("Key expired with number " . $param[2]);
					break;
				case "SIGEXPIRED":
					$this->printDebug("Sig expired: deprecated");
					break;
				case "GOOD_PASSPHRASE":
					$this->printDebug( "Password accepted!" );
					break;
				case "BAD_PASSPHRASE":
					$this->setError("BAD PASSWORD", "Incorrect or missing password!" );
					break;
				case "ALREADY_SIGNED":
					$this->setError("SIGNATURE FAILED","Key is already signed." );
					$this->printDebug("Signature already present by key " . $param[2]);
					break;
				case "GOODSIG":
					// The signature has been verified and is good
					$this->verified = true;
					$this->verifyStatus = "GOOD";
					$this->signedKeyFingerprint = $param[2];
					
					// Get the UserID of the user who signed this text
					$this->verifiedUserID = new GnuPGuid(implode(' ', array_slice($param,3)));
					break;
				case "VALIDSIG":
					$this->verifiedSignature = new GnuPGSignature($param);
					$this->verifiedSignature->uid=$this->verifiedUserID;
					$this->verifiedKeyIndex = $param[11];
					break;
				case "SIG_CREATED":
					// GnuPG has successfully signed something
					$this->signPubKeyAlgo = $param[3];
					$this->signHashAlgo = $param[4];
					break;
				case "BADSIG":
					// GnuPG could not verify the signature
					$this->signedKeyFingerprint = $param[2];
					$tempUserID = array();
					$this->verifiedUserID = new GnuPGuid(implode(' ', array_slice($param,3)));
					$this->verifyStatus = "BAD";
					break;
				case "EXPKEYSIG":
					// The Key used to sign this message has expired
					$this->signedKeyFingerprint = $param[2];
					$tempUserID = array();
					for( $index = 3; $index < count( $param ); $index++ )
						$tempUserID[] = $param[$index];
					$this->signedKeyUserID = implode(' ', $tempUserID );
					$this->verifyStatus = "EXPIRED";
					break;
				case "EXPSIG":
					// The signature has expired
					$this->signedKeyFingerprint = $param[2];
					$tempUserID = array();
					for( $index = 3; $index < count( $param ); $index++ )
						$tempUserID[] = $param[$index];
					$this->signedKeyUserID = implode(' ', $tempUserID );
					$this->verifyStatus = "EXPIRED";
					break;
				case "ERRSIG":
					$this->verifiedSignature=new GnuPGSignature($param);
					if ($this->verifiedUserID) {
						$this->verifiedSignature->uid=$this->verifiedUserID;
					} 
					// Something is wrong with this signature...
					if( $param[7] == "9" )
					{
						// 9 means we don't have the public key
						$this->signedKeyFingerprint = $param[2];
						$this->verifyStatus = "MISSING PUBLIC KEY";
					}
					else
					{
						$this->setError("VERIFY ERROR", "Error while verifying signature!" );
						$this->verifyStatus = "ERROR";
					}
					break;
				case "INV_RECP":
					// Uh oh... trying to send something encrypted to someone that we don't know
					$this->setError("INVALID RECIPIENT", "Don't have any information about a recipient!" );
					$tempUserID = array();
					for( $index = 3; $index < count( $param ); $index++ )
						$tempUserID[] = $param[$index];
					$userID = implode(' ', $tempUserID );
					switch( $param[2] )
					{
						case "1":
							$reason = "Missing Key";
							break;
						case "3":
							$reason = "Incorrect Key Use";
							break;
						case "4":
							$reason = "Key Revoked";
							break;
						case "5":
							$reason = "Key Expired";
							break;
						case "10":
							$reason = "Key not trusted";
							break;
						default:
							$reason = "";
							break;
					}
					$this->invalidRecipient[] = array( "userid" => $userID, "reason" => $reason );
					break;
				case "BEGIN ENCRYPTION":
					$this->printDebug("Encryption begun.");
					$this->closePipe( PASSPHRASE_FD );
					break;
			}
		} //end [GNUPG]: if
		else {
			$this->printDebug("Caught default case of $line");
		} // end [GNUPG]: else
	}

       /***********************************************/
       /**
        * function writePassword 
        *
        * Writes a password to the passphrase file descriptor 
	* By default writes the passphrase set in the object ($this->passphrase)
	* If not available, writes the passphrase for the fingerprint specified
	* or the passphrase to the default key if no fingerprint if specified
        *
        * @param string $fingerprint containing fingerprint of the key with the passphrase 
        *
        */
	// write the password for the given key to the password input
	function writePassword( $fingerprint=false )
	{
		if ($this->passphrase) {
			$this->printDebug( "Sending Passphrase" );
			$this->writeData( $this->passphrase."\n", PASSPHRASE_FD );
			$this->printDebug( "Done Sending Passphrase" );
		} else {
		$keyIndex = $this->getKeyIndexFromFingerprint( $fingerprint );
//		$this->printDebug( "writePassword() [".$keyIndex."]-[".$this->keys[ $keyIndex ]->passphrase."]");
		if( $keyIndex !== false && $this->keys[ $keyIndex ]->passphrase!='' )
		{
			$this->printDebug( "Sending Passphrase" );
			$this->writeData( $this->keys[ $keyIndex]->passphrase."\n", PASSPHRASE_FD );
			$this->printDebug( "Done Sending Passphrase" );
		}
		else {
			$this->printDebug( "No Password: " . $this->keys[ $keyIndex ]->passphrase . '?' );
			$this->writeData("\n", PASSPHRASE_FD );
		}
		}
//		$this->closePipe( PASSPHRASE_FD );
	}

       /***********************************************/
       /**
        * function writeNewPassword
        *
        * Writes the new passphrase to the passphrase file descriptor, twice.
        *
        * @param void 
        *
        */
	function writeNewPassword()
	{
		if ($this->newpassphrase) {
			$this->printDebug("Writing new passphrase." );
			$this->writeData($this->newpassphrase."\n",PASSPHRASE_FD );
			$this->writeData($this->newpassphrase."\n",PASSPHRASE_FD );
		} else {
			$this->printDebug("Failed to find new passphrase." );
		}
//		$this->closePipe( PASSPHRASE_FD );
	}
       /***********************************************/
       /**
        * function writeOkay
        *
        * Writes a confirmation to the GPG command file descriptor
	* Used to confirm choices during interaction with the status pipe
        *
        * @param void 
        *
        */
	function writeOkay()
	{
		$this->printDebug("Answering Y to gpg.");
		$this->writeData("Y\n",PASSPHRASE_FD);
	}
       /***********************************************/
       /**
        * function writeCheckLevel
        *
        * Writes a confirmation level for signature uid verification query
        * Used during keys signing.  Defaults to 0 (Not specified) 
        *
        * @param int $checkLevel indicating level of confirmation
        *
        */
	function writeCheckLevel($checkLevel=0)
	{
		$this->printDebug("Writing a checkLevel of $checkLevel");
		$this->writeData("$checkLevel\n",PASSPHRASE_FD);
	}

       /***********************************************/
       /**
        * function confirmSave 
        *
        * Writes a save command to the GPG command file descriptor 
        * Used during key edit actions
        *
        * @param void
        *
        */
	function confirmSave()
	{
		$this->printDebug("Saving changes.");
		$this->writeData("save\n", PASSPHRASE_FD);
//		$this->closePipe( PASSPHRASE_FD );
	}

       /***********************************************/
       /**
        * function getKeys 
        *
        * Retreives keys from gpg if no keys have been loaded 
        * Does not force a refresh of the key information 
        *
        * @param void
        *
        */
	function getKeys($fpr=false )
	{
		if (!$fpr) {
			if( $this->keys == false )
				$this->refreshKeys( );
		} else {
			if (!$this->keys[$fpr]) 
				$this->refreshKeys($fpr);
		}
	}

       /***********************************************/
       /**
        * function getKey
        *
        * Retreives a key from the keyring 
        *
        * @param string $keyid containing fingerprint or key id of the key to retrieve 
	* @return GnuPGKey $key corresponding to id or fingerprint
        *
        */
	function getKey( $keyid )
	{
		//call this function to ensure that this key exists in the list
		$this->getKeys($keyid);
		if (strlen($keyid) > 16) {
			if (array_key_exists($keyid,$this->keys)) {
				return $this->keys[$keyid];
			} else { return array('errors'=>array(0=>_("No Key Found"))); }
		} else {
			$fpr = $this->getKeyIndexFromFingerprint($keyid);
			if (array_key_exists($fpr,$this->keys)) {
				return $this->keys[$fpr];
			} else { return array('errors'=>array(0=>_("No Key Found"))); }
		}
	}

       /***********************************************/
       /**
        * function makearrayKeys 
        *
        * Creates an array of keys on the keyring, making use of the arrayKey function
	* Each key creates an array of data, indexed by fieldname
	* This function is used for keyring sorting and viewing 
        *
        * @param void
	* @return array of array of keys, including subkeys 
        *
        */		
	function makearrayKeys()
	{
		$return=array();
		foreach( $this->keys as $lkey) {
			$return[$lkey->fingerprint] = $lkey->arrayKey(); 
			if (count($lkey->subkeys) > 0) {
				foreach ($lkey->subkeys as $lsubkey) {
					$return[$lkey->fingerprint]['sub'][$lsubkey->fingerprint] = $lsubkey->arrayKey();
				}
			}
		}
		$this->arraykeys = $return;
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
	if (!$this->arraykeys) {
		$this->printDebug("No array made to sort, running makearrayKeys");
		$this->makearrayKeys();
	}
       	if (function_exists('array_chunk')){
       	     return array_chunk($this->arraykeys, $len, true);
       	 } else {
            $this->printDebug("<br>Your PHP version does not support te array_chunk function.  Returning entire array instead.\n");
            $return = array();
            $return [] = $this->arraykeys;
            return  $return;
         } //end check for array_chunk

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
	if (!$this->arraykeys) {
		$this->printDebug("No array made to sort, running makearrayKeys");
		$this->makearrayKeys();
	}
        //Determine ascending v. descending.
        if ($asc) $op = ">";
        else $op = "<";

        //Form the body of the lambda function.
        $code =
            "if (strtolower(\$key1['$dataName']) $op strtolower(\$key2['$dataName'])) { return 1; } " .
            "else if (strtolower(\$key1['$dataName']) == strtolower(\$key2['$dataName'])) { return 0; } " .
            "else return -1;";

        //Create the function and sort.
        $lambda = create_function('$key1,$key2', $code);
        uasort($this->arraykeys, $lambda);
     }

    function fetchKeys($search, $ring='public') {
	return $this->refreshKeys($search,$ring);
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
        * function refreshKeys 
        *
        * Main function for retreiving key and signature information from gpg
	* Parses output from gpg and creates the array of GnuPGKey objects in $this->keys 
        *
        * @param string $search containing string to limit keys shown, default '' to load all keys
	* @param string $ring containing the name of the keyring to search, default 'all' for public
        *
        */
	// Load up all the information about the keys in the key ring from GnuPG
	function refreshKeys($search='', $ring='all' )
	{

	    $params='';
	    $addHomeDir=true;
	    $addSystemRing=true;
	    $this->action='listKeys';
	    switch ($ring) {
		case 'new':
		case 'public':
		case  '':
			$addSystemRing=false;
		default:
		case 'all':
			$params .= '--list-sigs';
		        break;
		case 'secret':
		    $params  = '--list-secret-keys ';
		    break;
		case 'system':
	            {
	              if ($this->systemKeyring) {
	                $system_keyring_file = $this->systemKeyring;
	                if (is_file($system_keyring_file)) {
	                    $this->alternateKeyring = $system_keyring_file;
			    $system_trustdb_file = escapeshellarg(dirname($system_keyring_file) . DIRECTORY_SEPARATOR . 'trustdb.gpg');
	                    $params  = "--trustdb $system_trustdb_file --list-sigs";
			    $addHomeDir = false;
			    $addSystemRing = false;
	                }
	              }
	     	    }
	  }	
		$this->keys = array();
//		$search=escapeshellarg($search);
		if ($search) if (strpos($search,"'")===false) { $search = "'$search'"; }
		$command = "--fixed-list-mode --with-colons $params --with-fingerprint --with-fingerprint $search";

		$return=$this->execute_gpg( $command, false, $addHomeDir, $addSystemRing );
		
		$stdoutLines = explode( "\n", $return['output'] );
		$currentKey = false;
		$currentsubKey = false;
		$currentUid = false;
		$lastKeyRecordRead = '';
		$this->keys = array();
		foreach( $stdoutLines as $line )
		{
			if( strpos( $line, ':' ) )
			{
				// make sure this is a key line
				$keyInfo = explode( ':', $line );
				switch( $keyInfo[0] )
				{
					case 'sec':
					case 'pub':
						// save the current key
						if( $currentKey ) {
								if ($currentsubKey) {
									if ($currentsubKey->fingerprint) {
										$currentKey->subkeys[$currentsubKey->fingerprint] = $currentsubKey;
									}
									else { $currentKey->subkeys[] = $currentsubKey; }
									$currentsubKey=false;
								}
								if ($currentUid) {
									$currentKey->userIDs[]=$currentUid;
									$currentUid=false;
								}
								if ($currentKey->fingerprint) {
									$this->keys[$currentKey->fingerprint] = $currentKey;
								}
								else { $this->keys[] = $currentKey; }
						}
						$currentKey = new GnuPGkey($keyInfo);
						$lastKeyRecordRead = 'pub';
						break;
					case 'ssb':
					case 'sub':
						$lastKeyRecordRead = 'sub';
						if ($currentsubKey) {
							if ($currentsubKey->fingerprint) {
								$currentKey->subkeys[$currentsubKey->fingerprint] = $currentsubKey;
							}
							else { $currentKey->subkeys[] = $currentsubKey; }
						}
						$currentsubKey = new GnuPGkey($keyInfo);
						break;
					case 'uid':
						if ($currentUid) {
							$currentKey->userIDs[]=$currentUid;
						}
						$currentUid = new GnuPGuid($keyInfo[9]);
						break;
					case 'sig':
						if ( $lastKeyRecordRead == 'sub')
							break;
						if ($currentUid) {
							$currentUid->signatures[] = new GnuPGsig($keyInfo);
						} else {
							 $currentKey->signatures[] = new GnuPGsig($keyInfo);
						}
						break;
					case 'fpr':
						if( $lastKeyRecordRead == 'pub' )
							$currentKey->fingerprint = $keyInfo[9];
						if( $lastKeyRecordRead == 'sub' )
							$currentsubKey->fingerprint = $keyInfo[9];
				}
			}
		}
		// save the last key
		if( $currentKey ) {
			if ($currentsubKey) {
				if ($currentsubKey->fingerprint) {
                                         $currentKey->subkeys[$currentsubKey->fingerprint] = $currentsubKey;
                                } 
                                else { $currentKey->subkeys[] = $currentsubKey; }
                                $currentsubKey=false;
                        } 
                        if ($currentUid) {
                                $currentKey->userIDs[]=$currentUid;
                        } 
                        if ($currentKey->fingerprint) {
                                $this->keys[$currentKey->fingerprint] = $currentKey;
                        }
                        else { $this->keys[] = $currentKey; }
		}
		$this->printDebug("Number of keys: " . $this->numKeys());
		// now get which keys we have secret keys for
		$command = "--fixed-list-mode --with-colons --list-secret-keys --with-fingerprint --with-fingerprint";

		$return=$this->execute_gpg( $command );
		$stdoutLines = explode( "\n", $return['output'] );

		$lastKeyRecordRead = '';

		foreach( $stdoutLines as $line )
		{
			if( strpos( $line, ':' ) )
			{
				// make sure this is a key line
				$keyInfo = explode( ':', $line );
				switch( $keyInfo[0] )
				{
					case 'sec':
						$lastKeyRecordRead = 'sec';
						break;
					case 'ssb':
						$lastKeyRecordRead = 'ssb';
						break;
					case 'fpr':
						if( $lastKeyRecordRead == 'sec' )
						{
							foreach( array_keys( $this->keys ) as $keyIndex )
							{
								if( $this->keys[ $keyIndex ]->fingerprint == $keyInfo[9] )
									$this->keys[ $keyIndex ]->haveSecret = true;
							}
						}
						break;
				} // end key info switch
			}
		} // end secret key loop
        $return['keys']=$this->keys;
		return $return;
	} //end function refreshKeys


/*********************************************************************/
/**
 * function parse_output()
 *
 * This will parse the string that gpg returns for info, warnings, errors
 * and return them in arrays.  This function also returns any other output seperately
 *
 * @param  string $gpg_output text output from gpg
 *
 * @return array $return ['errors'],['warnings'],['info'] contain gpg messages ['output'] contains the rest of the output
 */
	function parse_output( $gpg_output)
	{
	    global $insecure_mem_warning;
	    $insecure_mem_warning = $GLOBALS['GPG_SYSTEM_OPTIONS']['insecure_mem_warning'];
	    $return['errors'] = array();
	    $return['warnings'] = array();
	    $return['info'] = array();
	    $return['signature'] = array();
	    $return['verified'] = array();
	    $return['skipped_keys'] = array();
	    if (count($this->invalidRecipients) > 0) {
		$return['skipped_keys']=$this->invalidRecipients;
	    }
	    $return['output'] = '';
	    $return['untrusted'] = '';
	    $return['verified'] = '';
	    $trimmed = array();
	
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
		$j = substr_count ($line, "couldn't set locale correctly");
		if ($j) {
		    $return['info'][] = $line;
		    continue;
		}
		$j = substr_count ($line, 'gpg: success sending to');
		if ($j) {
		    $return['info'][] = gpg_stripstr($line);
		    continue;
		}
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
		$j = substr_count ($line, 'gpg: keyring ');
		if ($j) {
		    $return['info'][] = gpg_stripstr($line);
		};
	        $j = substr_count ($line, 'gpg: Signature made');
	        if ($j) {
	            $return['signature'][] = gpg_stripstr($line);
		    if (!$this->verifiedSignature) {
		    	$this->verifiedSignature=new GnuPGSignature($line);
		    }
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, 'gpg: Good signature');
	        if ($j) {
	            $return['verified'] = 'true';
	            $return['signature'][] = gpg_stripstr($line);
		    if (($this->verifiedSignature) && (!$this->verifiedSignature->uid)) {
			if ($this->verifiedUserID) {
				$this->verifiedSignature->uid=$this->verifiedUserID;
			} else {
		    		$uidstr = preg_match('/^gpg: Good signature from \"(.*)\"/',$line,$matches);
		    		$this->verifiedSignature->uid = new GnuPGuid($matches[1]);
				$this->verifiedSignature->valid=true;
			}
		    }
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
		    if (($this->verifiedSignature) && (!$this->verifiedSignature->uid)) {
		    	   $uidstr = preg_match('/^gpg: Bad signature from \"(.*)\"/',$line,$matches);
			   $this->verifiedSignature->uid = new GnuPGuid($matches[1]);
			   $this->verifiedSignature->valid=false;
		    }
	            $return['signature'][] = gpg_stripstr($line);
	            $return['warnings'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, 'gpg: BAD signature');
	        if ($j) {
		    if (($this->verifiedSignature) && (!$this->verifiedSignature->uid)) {
		    	   $uidstr = preg_match('/^gpg: BAD signature from \"(.*)\"/',$line,$matches);
			   $this->verifiedSignature->uid = new GnuPGuid($matches[1]);
			   $this->verifiedSignature->valid=false;
		    }
		    $return['signature'][] = gpg_stripstr($line);
	            $return['warnings'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg: can't open");
	        if ($j) {
	            $return['errors'][] = gpg_stripstr($line);
	            continue;
	        };
            $j = substr_count($line, 'gpg: Sorry, no terminal');
            if ($j) {
                $return['errors'][] = gpg_stripstr($line);
                $return['errors'][] = _("Problem with interaction with GPG, probably trying to execute a command with no pipes that requires pipes for interaction");
                continue;
            }
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
	        $j = substr_count ($line, "gpg: Can't check signature: public key not found");
	        if ($j) {
	            $return['warnings'][] = gpg_stripstr($line);
	            continue;
	        };
		$j = substr_count ($line, 'gpg: some signal caught ... exiting');
		if ($j) {
		    $return['errors'][] = gpg_stripstr($line);
		    continue;
		}
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
		    continue;
	        };
		$j = substr_count ($line, 'gpg: WARNING: Using untrusted key!');
		if ($j) {
		    $line = gpg_stripstr($line);
		    $return['signature'][] = _("WARNING: This key is not certified with a trusted signature!");
		    $return['signature'][] = _("There is no indication that this key really belongs to the owner");
		    $return['warnings'][] = gpg_stripstr($line,'WARNING:');
		    $return['untrusted'] = 'true';
		    continue;
		}
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
	            };
	            continue;
	        };
	        $j = substr_count ($line, 'encryption failed');
	        if ($j) {
	            $return['errors'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, 'gpg: keyblock resource');
	        if ($j) {
	            $return['errors'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, 'No such file or directory');
	        if ($j) {
	            $return['errors'][] = gpg_stripstr($line);
	            continue;
	        };
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
	            continue;
	        };
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
	            continue;
	        };
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
	        };
	        $j = substr_count($line, "gpg: error reading key: public key not found");
	        if ($j) {
	            $return['errors'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count($line, "gpg: protection algorithm");
	        if ($j) {
	            $return['errors'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg: next trustdb check due at");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg: checking at depth");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg: public key of ultimately trusted key 00000000 not found");
	        if ($j) {
	            $return['warnings'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg: Oops: keyid_from_fingerprint: no pubkey");
	        if ($j) {
	            $return['warnings'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg: Oops: keyid_from_fingerprint: no pubkey");
	        if ($j) {
	            $return['warnings'][] = gpg_stripstr($line);
	            continue;
	        };
	        //some kind of key message, trap 'em all for now
	        $j = substr_count ($line, "gpg: key");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg: Total number processed:");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        //@todo add some info about how many imported to $return
	       	$j = substr_count ($line, "gpg:               imported:");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg:              unchanged:");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg:           new user IDs:");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };
	        $j = substr_count ($line, "gpg:         new signatures:");
	        if ($j) {
	            $return['info'][] = gpg_stripstr($line);
	            continue;
	        };

	        $trimmed[] = $line;
//	        $this->printDebug("Line passed on: $line<br>"); 
	    }

	    $return['output'] = implode($trimmed,"\n");

	    return $return;

	} // end parse_output fn

	/***********************************************/
	/**
	* function importKey_file
	*
	*  import keys from a file into the keyring.
	*  Sets the keys in the keyring to the newly imported/updated keys
	*
	* @param string $fname containing the path to the file to import
	* @return array $return with element $return['newkeys'] array of affected keys fingerprints 
	*/
	function importKey_file($fname)
	{
		$this->action='importKey';
		$params = " --allow-secret-key-import --import $fname";	
		$return=$this->execute_gpg($params);
		$return['newkeys']=$this->newKeys;
		$newkeystr=''; foreach ($this->newKeys as $nkey) { $newkeystr.= " '$nkey'"; }
		$this->refreshKeys($newkeystr);
		return $return;
	}
	
	/***********************************************/
	/**
	 *
	 * function importKey_text
	 * 
	 * Imports a key from ascii-armored keyblock text
	 * @param string $keytext containing the ascii-armored key information
	 * @return array $return with element $return['newkeys'] array of affected keys fingerprints
	*/
	function importKey_text($keystring) {
		$this->action='importKey';
		$params = " --allow-secret-key-import --import";
		$return=$this->execute_gpg($params, $keystring);
		$return['newkeys'] =$this->newKeys;
		$newkeystr=''; foreach ($this->newKeys as $nkey) { $newkeystr.= " '$nkey'"; }
		$this->refreshKeys($newkeystr);
		return $return;
	}

	/***********************************************/
	/**
	*
	* function importKey_server
	*
	* Imports a key from ascii-armored keyblock text
	* @param string $keytext containing the id of the key to look up
	* @param string $keyserver containing the url of the keyserver (i.e. hkp://pgp.mit.edu:11371)
	* @return array $return with element $return['newkeys'] array of affected keys fingerprints
	*/

	function importKey_server($keystring, $keyserver) {
		$this->action='importKey';
		$params = " --keyserver $keyserver --recv-key $keystring";
		$return=$this->execute_gpg($params );
		$return['newkeys'] =$this->newKeys;
		$this->refreshKeys(implode(' ',$this->newKeys));
		return $return;
	}
	

       /***********************************************/
       /**
        * function getKeyIndexFromFingerprint
        *
        * Retreives a fingerprint based on a key id 
        *
        * @param string $fingerprint containing fingerprint or key id of the key to retrieve 
	* @return string fingerprint of the key, if found
        *
        */
	function getKeyIndexFromFingerprint( $fingerprint )
	{
		$lookForFingerprintLength = strlen( $fingerprint );
		if ($lookForFingerprintLength> 16) { return $fingerprint; }
		$this->printDebug( "Looking for key fingerprint: ".$fingerprint );

		// loop over all the keys looking for the one with the given fingerprint
		if (is_array($this->keys)) {
		foreach( array_keys( $this->keys ) as $keyIndex )
		{
			// Because we want to deal with fingerprints smaller than the one from gnupg, we must do some trivial math
			$fingerprintLength = strlen( $this->keys[ $keyIndex ]->id );
			if( $fingerprintLength>=$lookForFingerprintLength )
			{
				// we just want to match the right-most characters of the fingerprint
				$shrunkFingerprint = substr( $this->keys[ $keyIndex ]->id,
							$fingerprintLength - $lookForFingerprintLength );

				$this->printDebug( "Matching [".$fingerprint."] against [".$shrunkFingerprint."] which is the shorter "
						."version of ".$this->keys[ $keyIndex ]->id );
				if( $fingerprint == $shrunkFingerprint )
					return $keyIndex;
			}
		}
		}
		$this->printDebug( "Couldn't match key!" );
		return false;
	}

       /***********************************************/
       /**
        * function setKeyPassphrase
        *
        * Sets the passphrase for a secret key identified by its fingerprint or key id 
        *
        * @param string $fingerprint containing fingerprint or key id of the secret key
	* @param string $passphrase containing passphrase for the secret key
        *
        */
	function setKeyPassphrase( $fingerprint, $passphrase )
	{
		$keyIndex = $this->getKeyIndexFromFingerprint( $fingerprint );
		$this->printDebug("setKeyPassphrase: ".$keyIndex);

		// Do we have a valid key?
		if( $keyIndex!==false )
		{
			$this->keys[ $keyIndex ]->passphrase = $passphrase;
			$this->printDebug( "writePassword() [".$keyIndex."]-[".$this->keys[ $keyIndex ]->passphrase."][".$passphrase."]");
		}
	}

       /***********************************************/
       /**
        * function guessAction 
        *
        * Guess the current action being execute, based on parameters sent 
	* Used by execute functions when action does not come from inside the object
        *
        * @param string $options containing parameters sent to gpg 
        *
        */
	function guessAction($options) {
		$j=0;
		$j=substr_count($options,'--verify');
		if ($j) {
			$this->action='verify';
		}
		$j=substr_count($options,'--detach-sign');
		if ($j) {
			$this->action='sign';
		}
		$j=substr_count($options,'--decrypt');
		if ($j) {
			$this->action='decrypt';
		}
		$j=substr_count($options,'--encrypt');
		if ($j) {
			$this->action='encrypt';
		}
		$j=substr_count($options,'--sign');
		if ($j) {
			$this->action='sign';
		}
	        $j=substr_count($options,'--clearsign');
                if ($j) {
                        $this->action='sign';
                }
		$j=substr_count($options,'--list');
		if ($j) {
			$this->action='listKeys';
		}
        $j=substr_count($options,'--genkey');
        if ($j) {
            $this->action='generateKey_nopipes';
        }
	}

       /***********************************************/
       /**
        * function execute_gpg 
        *
        * Main function for execute commands with gpg
	* All calls to gpg should be made using this function 
        *
        * @param string $options containing parameters to pass to gpg 
        * @param string $data containing data to pass to gpg
	* @param bool $addHomeDir specifies if the home directory should be appended to the command.  True by default
        *
        */
	function execute_gpg( $options, $data = false, $addHomeDir = true,$addSystemRing=true )
	{	
		if (!$this->action) { $this->guessAction($options); }

		$lastSecondCommands='';
                if( ($this->gpgHomeDir != '') && $addHomeDir ) {
			$this->printDebug("Adding homedir to parameter list.  " . $this->gpgHomeDir);
                        $lastSecondCommands .= ' --homedir '.$this->gpgHomeDir . ' ';
		}
		if ($this->alternateKeyring) {
			$lastSecondCommands .= ' --no-default-keyring --keyring ' . escapeshellarg($this->alternateKeyring) . ' ';
			if ($this->alternateSecretKeyring) {
				$lastSecondCommands .= ' --secret-keyring ' . escapeshellarg($this->alternateSecretKeyring) . ' ';
			}
		} else {
			if ($this->alternateSecretKeyring) {
				$lastSecondCommands .= ' --no-default-keyring --secret-keyring ' . escapeshellarg($this->alternateSecretKeyring) . ' ';
			}
		}
		if ($this->systemKeyring && ($this->systemKeyring!=$this->alternateKeyring) &&$addSystemRing)  {
			if (is_file($this->systemKeyring)) {
				$lastSecondCommands .= ' --keyring '  .  escapeshellarg($this->systemKeyring) . ' ';
			} else { $this->printDebug("System keyring " . $this->systemKeyring . " failed is_file test."); }
		}
		if (count($this->trustedKeys) > 0) {
			foreach($this->trustedKeys  as $trusted_key_id) {
				if ($trusted_key_id) {
					$trusted_key_id=escapeshellarg($trusted_key_id);
				        $lastSecondCommands  .= " --trusted-key $trusted_key_id ";
			        }
			}
		}
		if (!check_php_version(4,3)) {
			$this->force_exec=true;
		}
		$this->printDebug("Executing action ". $this->action ." with GnuPG Object.");
		if ($this->force_exec) { 
			$this->printDebug("Forcing exec functionality, some functions may not function properly.");
			return $this->execute_gpg_nopipes($lastSecondCommands.$options, $this->passphrase, $data);
		} else {
			switch ($this->action) {
				case 'changePassphrase':
				case 'addRevoker':
				case 'addUID':
				case 'delUID':
				case 'addSubKey':
				case 'deleteSubKey':
				case 'selectSubKey':
				case 'setKeyPref':
				case 'setPrimaryUID':
				case 'generateKey':
				case 'expireKey':
				case 'signKey':
					if (!check_php_version(4,3)) {
						return array( 'errors' => array( 0 => _("proc_open is not available in this environment.  PHP 4.3 or greater is require for this functionality.")));
					}
				case 'encrypt':
				case 'decrypt':
				case 'sign':
				case 'verify':
				case 'listKeys':
				case 'importKey':
					$this->printDebug("Using pipes to communicate with gpg.");
					return $this->execute_gpg_pipes($lastSecondCommands.$options, $data );
					break;
				default:
					$this->printDebug("Using exec to communicate with gpg by default.");
					return $this->execute_gpg_nopipes($lastSecondCommands.$options, false, $data);
			}
		}
		$this->action=false;
		$this->printDebug("GPGSTDOUT:<pre>" . $this->stdout . "</pre><p>GPGSTDERR:<pre>" . $this->stderr . "</pre>");
	}

       /***********************************************/
       /**
        * function execute_gpg_pipes
        *
        * Execute commands using proc_open method 
        *
        * @param string $options containing parameters to pass to gpg 
        * @param string $data containing data to pass to gpg
	* @return array containing errors, output, etc
        *
        */
	function execute_gpg_pipes($options, $data )
	{
		$this->opengpg( $options, true);
		$this->printDebug("proc_open commandline executed, pipes open");
//		$this->statusout = $this->readData(STATUS_FD);
		// Send the user supplied data to GPGSTDIN
		if ($data) {
			$this->printDebug("Data available, sending to gpg: <pre>$data</pre>");
//			stream_set_blocking($this->gpg_pipes[GPGSTDIN],FALSE);
			$this->writeData( $data );
		}
		$this->closePipe( GPGSTDIN );
//		$this->stdout .= $this->readData( GPGSTDOUT );
		// Parse the status stuff
		$this->readStatus( );

		// Get the output
//		$this->stdout .= $this->readData( GPGSTDOUT );
		$this->stderr = $this->readData( GPGSTDERR );
		// Terminate the pipes to GnuPG
		$retValue = $this->closegpg();
		$this->printDebug( "gpg return status: ".$retValue );
		if ($this->stderr == '') {
			$return=$this->parse_output($this->stdout);
		} else {
			$return=$this->parse_output($this->stderr);
		}	
		$return['returnval'] = $retValue;
		$return['output'] = $this->stdout;
		$return['rawoutput'] = $this->stdout;
		if ($this->error) { $return['errors'][] = $this->errorCode . ": " . $this->errorDescription; }
		return $return;
	}

       /***********************************************/
       /**
        * function execute_gpg_nopipes 
        *
        * Function for executing gpg using the exec method 
        *
        * @param string $parameterlist containing parameters to pass to gpg
        * @param string $passphrase containing passphrase for operation, if needed
    * @param string $data containing data to send to gpg, if needed 
    * @return array $rerturn containing errors, warnings, etc
        *
        */
    function execute_gpg_nopipes( $parameterlist, $passphrase=false, $data=false)
    {
            $this->printDebug("Path to GPG: " . $this->gpg_exe . "<p>");
            $this->printDebug("Parameter List: $parameterlist<p>");
            if ($data) $this->printDebug("Data: $data<p>");

          $options =  ' --no-tty ';

            if ($passphrase) {
                $options .= ' --passphrase-fd 0 ';
                //we do need to still add the newline between passphrase and anything else
                $passphrase.="\n";
//                    $passphrase = escapeshellarg($passphrase . "\n");
            }

            $cmd = $this->gpg_exe . $options . $parameterlist;
            $nopass = $cmd;

//don't need to escapeshellarg if we are passing with fwrite to popen
            if ($data) {
//                $data = escapeshellarg($data);
            } else $data='';

            if ($passphrase!='') {
                $data = $passphrase . $data;
            }

            $cmd .= " 2>&1";
            $nopass .= " 2>&1";
            $this->printDebug("Command: $nopass<p>");

            //set language environmental variables
            $this->setEnvVars();

            if ($data) {
                //since we are passing data, let us use the temporary file magic
                //thanks to Robert Peake for this one

                $temp = $this->getTempFile();
                $this->printDebug("Accessing Temp FIle: $temp<p>");

                //add pipe to temp file for output from GPG, and popen the process for writing
                $cmd = $cmd." > $temp";
                $pw = popen($cmd,'w');

                //write data to the process
                fwrite($pw,$data);

                //get return value from the process
                $returnval=pclose($pw);

                //open and retrieve output from the process, ensuring that filesize is up to date
                clearstatcache();
                $fsize=filesize($temp);
                if ($fsize) {
                    $fr = fopen($temp,'r');
                    $output = fread($fr,$fsize);
                    //close the file again
                    fclose($fr);
                } else {
                    $output=''; //something went wrong
                }

                //immediately securely unlink this file
                $this->secure_unlink($temp);
            } else {
                //no data, so we can just exec as usual
                exec($cmd,$output,$returnval);
            }

            //reset environmental variables
            $this->setEnvVars(true);

            if ($debug) {
                $this->printDebug("Output:<br><textarea cols=80 rows=25 name=output>" .  print_r($output, true) . "</textarea><p>");
            }
            $return=$this->parse_output($output);

            $return['rawoutput'] = $output;
            $return['returnval'] = $returnval;

            return $return;
    }

       /***********************************************/
       /**
        * function opengpg 
        *
        * Open link to gnupg and sets up various pipes
	* Used during the proc_open interactions with gpg 
        *
        * @param string $command_options containing parameters to pass to gpg 
        * @param bool $usePassphrase specifies if the command is interactive or batch 
        *
        */
	function opengpg( $command_options, $usePassphrase = false )
	{
		$this->stdout = '';
		$this->stderr = '';
		$this->statusout = '';
		$lastSecondCommands='';

		if( $usePassphrase )
			$lastSecondCommands .= ' --command-fd '.PASSPHRASE_FD;
		else
			$lastSecondCommands .= ' --batch';

		// Add a comment line to signatures
		if( $this->comment != '' )
			$lastSecondCommands .= " --comment \"".$this->comment."\"";

        $this->setEnvVars();
		$gpg_execute_string = $this->gpg_exe.' '.$lastSecondCommands.
			' --status-fd '.STATUS_FD.' '.$this->gpg_options.' '.$command_options;

		$this->printDebug( "opengpg - executing \"".$gpg_execute_string."\"" );
		$this->gpg_resource = proc_open( $gpg_execute_string, $this->fileDescriptors, $this->gpg_pipes );
		if( (!is_resource( $this->gpg_resource )) or (!is_resource( $this->gpg_pipes[GPGSTDOUT])) ) {
	        	$this->setError( "GPG_OPEN_FAILED", "proc_open error!" );
		}
		else
		{
			foreach( $this->gpg_pipes as $pipeIndex => $pipe )
			{
				stream_set_write_buffer($pipe, 0);
//				stream_set_blocking($pipe,FALSE);
	
				// Mark the pipe as open
				$this->pipeOpen[ $pipeIndex ] = true;
			}
		}
        //set environmental variables back to previous values
        $this->setEnvVars(true);
	}

    //Function to set and reset shell environmental variables before executing the GPG binary.  This is currently used only for languages.
    //This could could be easily extended to setting other environmental variables, like the gpg-agent environmental vars
    function setEnvVars($reset_vars=false) {
        $values=$this->lang_env_values;
        $lang_vars=$this->lang_env_vars;
        if ($lang_vars) {
            foreach ($lang_vars as $lkey=>$lval) {
                if ($reset_vars) {
                    if ($values[$lkey]) {
                        $this->printDebug("Resetting environmental variable $lkey={$values[$lkey]}");
                        putenv("$lkey={$values[$lkey]}");
                    }
                } else {
                    $cval=getenv($lkey);
                    if ($cval) {
                        $values[$lkey]=$cval;
                        $this->printDebug("Saving previously set environmental variable $lkey=$cval");
                    }
                    $this->printDebug("Setting environmental variable $lkey=$lval");
                    putenv("$lkey=$lval");
                }
            }
        }
        $this->lang_env_values=$values;
   }

	// read data (until EOF) from the given pipe index
	function readData( $pipeIndex )
	{
		$data = "";
		$readPipes = array( $this->gpg_pipes[ $pipeIndex ] );
		if( $this->pipeOpen[ $pipeIndex ] == true )
		{
			$numRead = stream_select($readPipes,$write=NULL,$except=NULL,5);
			if ($numRead !==false ) {
				$this->printDebug("Reading data from pipe $pipeIndex. ");
				while( !feof( $this->gpg_pipes[ $pipeIndex ] ) )
					$data .= fgets( $this->gpg_pipes[ $pipeIndex ]);
			} else { $this->printDebug("Pipe $pipeIndex not ready for reading"); }
		}
		return $data;
	}

	// write data to the given pipe index
	function writeData( $data = false, $pipeIndex = GPGSTDIN )
	{
	        $this->printDebug("entering writeData");
		if( $data == false ) {
			$this->printDebug("No data passed, grabbing from indata (" . strlen($this->indata) . " bytes) ");
			$data = $this->indata;
		}
		$writePipes=array($this->gpg_pipes[$pipeIndex]);
		if( $this->pipeOpen[$pipeIndex] == true )
		{
			$this->printDebug("Pipe $pipeIndex open for writing, checking to see if it would block on write");
			$numWrite=stream_select($read=NULL,$writePipes,$except=NULL,5);
			if ($numWrite !==false) {
			switch($this->action) {
			  case 'sign':
			  case 'encrypt':
			  	$this->printDebug("Setting write pipe $pipeIndex to not block");
				stream_set_blocking($this->gpg_pipes[$pipeIndex],FALSE);
				break;
			  case 'verify':
			  default:
				break;
			}
			$this->printDebug( "Sending Data!... (".strlen($data).")" );
			$datalen=strlen($data);
			if  ($datalen>4096) {
				$this->writingData=true;
				$this->printDebug("Data bigger than 4096, chunking");
				$pos=0;
				$nowritecount=0;
				while (($pos<$datalen)) {
					$writePipes=array($this->gpg_pipes[$pipeIndex]);
					if ( stream_select($read=NULL,$writePipes,$except=NULL,5)!==false) {
					$this->printDebug("Writing next chunk");
					$outstr=substr($data, $pos, 4096);
					$numwrote=fwrite( $this->gpg_pipes[$pipeIndex], $outstr);
					if ($numwrote>0) {
						$nowritecount=0;
						$pos+=4096;
						fflush($this->gpg_pipes[$pipeIndex]);
						$this->printDebug("Wrote chunk size $numwrote");
					} 
					else { 
						$nowritecount=$nowritecount+1; 
						if (($nowritecount>2) or $this->error) break; 
					}
					$this->readStatus();
					} else { $this->printDebug("Pipe not ready for writing"); }
				} //end while for writing data in chunks to pipe
				$this->writingData=false;
			} else { // data is not greater than 4096 bytes, just write it all
				if( ($numwrote=fwrite( $this->gpg_pipes[$pipeIndex], $data,$datalen )) === false )
					$this->setError( "WRITE ERROR", "Can't write to process' pipe: ".$pipeIndex );
				else $this->printDebug("Data written $numwrote of ".strlen($data));
			}
				} else $this->printDebug("Pipe $pipeIndex not writeable.");
		}
		else
			$this->setError( "PIPE CLOSED", "PIPE ".$pipeIndex." isn't open! Make sure the process is open" );
	}

	// close the given pipe
	function closePipe( $whichPipe )
	{
		if( $this->pipeOpen[ $whichPipe ] == true )
		{
			$this->printDebug( "Closing Pipe ".$whichPipe );
			fclose( $this->gpg_pipes[ $whichPipe ] );
			$this->pipeOpen[ $whichPipe ] = false;
		}
	}

	// close the link to gpg and all of the associated resources (i.e. pipes)
	function closegpg( )
	{
		// close any open pipes
		foreach( $this->gpg_pipes as $pipeIndex => $value )
			$this->closePipe( $pipeIndex );

		// Close the resource
		if( is_resource( $this->gpg_resource ) )
    		return proc_close( $this->gpg_resource );
		else
			return false;
	}

       /***********************************************/
       /**
        * function clearError 
        *
        * Clears last error 
        *
        * @param void
        *
        */
	function clearError( )
	{
		$this->error = false;
		$this->errorCode = '';
		$this->errorDescription = '';
	}

       /***********************************************/
       /**
        * function isError 
        *
        * Check to see if last action returned an error 
        *
        * @param void 
	* @return bool if error occured
        *
        */
	function isError( )
	{
		return $this->error;
	}

       /***********************************************/
       /**
        * function printDebug
        *
        * Prints debug strings 
        *
        * @param string $code of error 
	* @param string $description containing error description
        *
        */
	function setError( $code, $description )
	{
		$this->errorCode = $code;
		$this->errorDescription = $description;
		$this->error = true;

		$this->printDebug( "ERROR: ".$this->errorDescription );
	}

       /***********************************************/
       /**
        * function getErrorCode 
        *
        * Returns code of last error 
        *
	* @param void
	* @return error code
	*
	*/
	function getErrorCode( )
	{
		return $this->errorCode;
	}

       /***********************************************/
       /**
        * function getErrorDescription 
        *
        * Returns description of most recent error 
        *
        * @param void
	* @return string with error description
        *
        */
	function getErrorDescription( )
	{
		return $this->errorDescription;
	}

       /***********************************************/
       /**
        * function printDebug
        *
        * Prints debug strings 
        *
        * @param string $string to print 
        *
        */
	function printDebug( $string )
	{
		if( $this->debug )
		{
			echo $string."<br>\n";
			flush();
			if (ob_get_level() > 0) {
				ob_end_flush();
			}
		}
	}

    /*** TEMP DIRECTORY FUNCTIONS **/

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
            /* If one has been specifically set, then use that */
            if (@is_dir($this->tmp_dir)) {
                $tmp_check = $this->tmp_dir;
                if (is_dir($tmp_check) and is_writable($tmp_check)) {
                    $tmp = $tmp_check;
                    break;
                } else {
                    $this->setError( "NO_WRITE", 'GPG Plugin option directory tmp_dir: '.$tmp." is not writable.\n" );
                }
            }
        
            /* If we haven't set a value, then cycle through a
            * list of preset possibilities. */
            $tmp_locations=$this->tmp_locations;
            while (empty($tmp) && sizeof($tmp_locations)) {
                $tmp_check = array_shift($tmp_locations);
                if (@is_dir($tmp_check)) {
                    if (is_writable ($tmp_check)) {
                        $tmp = $tmp_check;
                    } else {
                        $this->setError( "NO_WRITE", 'GPG Plugin directory tmp_dir: '.$tmp." is not writable.\n" );
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
                        $this->setError( "NO_WRITE", 'GPG Plugin option PHP upload directory upload_tmp_dir: '.$tmp." is not writable.\n" );
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
                $tmp_dir = $this->getTempDir();
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
                    $this->deleteAtShutdown($tmp_file);
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
            $temp_dir = $this->getTempDir();
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
                    $this->deleteAtShutdown($temp_dir);
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
                register_shutdown_function(array(&$this, '_deleteAtShutdown'));
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
        *
        *  Securely unlink a file, by opening it, writing random characters to it, and then close and unlinking it
        *
        * @param $file with string path to file that should be securely unliked
        *
        */
        function secure_unlink($file) {
            if (@file_exists($file)) {
                    $size = filesize ($file); //get the size
                    $fp = fopen ($file, 'r+');
                    $randstring = $this->rand_string ($size); //get a random string of the right size
                    fwrite ($fp, $randstring); //overwrite the file contents
                    fclose ($fp);
                    $ret=@unlink($file);
                    return $ret;
            }
            return false;
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
            $registered = $this->deleteAtShutdown();
            $dirs = $registered[0];
            $files = $registered[1];
        
            foreach ($files as $file => $val) {
                /* Delete files */
                if ($val) {
                    $this->secure_unlink($file);
                }
            }
        
            foreach ($dirs as $dir => $val) {
                /* Delete directories */
                if ($val && @file_exists($dir)) {
                    /* Make sure directory is empty. */
                    $dir_class = dir($dir);
                    while (false !== ($entry = $dir_class->read())) {
                        if ($entry != '.' && $entry != '..') {
                            $this->secure_unlink($dir.'/'.$entry);
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
            mt_srand($this->make_seed());
            $ret = "";
            for ($i = 0; $i < $length; $i++) {
                $ret .= chr (mt_rand(0,255));
            };
            return $ret;
        }



}
/**********************************************************************/
/*
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

if (!function_exists('check_php_version')) {
function check_php_version ($a = '0', $b = '0', $c = '0')
{   
    global $SQ_PHP_VERSION;
 
    if(!isset($SQ_PHP_VERSION))
        $SQ_PHP_VERSION = substr( str_pad( preg_replace('/\D/','', PHP_VERSION), 3, '0'), 0, 3);

    return $SQ_PHP_VERSION >= ($a.$b.$c);
}
}


/*********************************************************************?
/*
 * $Log: gpg.php,v $
 * Revision 1.58  2005/12/21 02:55:46  ke
 * - added code to set environmental variables before executing GPG
 * - used to set the default language to english, because our string parser only traps for known english strings
 *
 * Revision 1.57  2005/11/11 07:31:22  ke
 * - added option to skip entropy creation on key creation
 * - added functions to handle temporary files/directories, and secure delete of these with register_shutdown_function
 * - altered the method of calling GPG when passing data, now uses popen with temp files for output
 * - Thanks to Robert Peake for the above idea
 * - added alternate generateKey function for use with no pipes
 *
 * Revision 1.56  2005/06/08 23:16:16  ke
 * - added function to discover encryption keys on a message directly
 *
 * Revision 1.55  2005/02/02 06:54:48  ke
 * - added needed space for options
 *
 * Revision 1.54  2005/02/02 06:53:08  ke
 * - added --yes and --openpgp to list of default options
 * - set nopipes execution function to set global options first
 *
 * Revision 1.53  2005/01/26 20:21:46  ke
 * - added check for provided passphrase in functions, otherwise use already set passphrase
 *
 * Revision 1.52  2004/10/11 19:52:12  ke
 * -make incoming fingerprint capitalized
 *
 * Revision 1.51  2004/08/26 23:59:03  ke
 * -no longer require passphrase as a parameter for encryption
 *
 * Revision 1.50  2004/08/25 09:58:55  ke
 * changed signKey to search for all keys on keyring before signing
 *
 * Revision 1.49  2004/08/23 09:25:55  ke
 * -fix of variable case
 * -fix of nasty parse error
 *
 * Revision 1.48  2004/08/23 09:12:27  ke
 * -added function to import key from keyserver
 * -removed escapeshellarg on search term, breaks searches for multiple keys
 * Bug 29
 *
 * Revision 1.47  2004/08/23 07:43:36  ke
 * -fixed definition of GPGSTDIN to operate properly
 * -check every time before running refreshKeys if the key data is already loaded
 * -read stdout all the way through without breaking, just don't output
 * -fixes to timeout on stream_select
 * Bug 29
 *
 * Revision 1.46  2004/08/22 23:21:24  ke
 * -added code to send longer than 4096 byte messages
 * -changed definitions of STDOUT,STDIN,STDERR to allow object to operate properly on the shell
 * -should solve large message hangs in GPG, also shell operations
 * bug 206
 *
 * Revision 1.45  2004/08/19 19:14:35  ke
 * -trap keyring creation message to ensure it won't appear in an emailed or sent key
 *
 * Revision 1.44  2004/08/09 21:56:00  ke
 * -changed to use fgets for reading again, but with a while(!feof) loop to read everything
 * -removed limit on fgets size for status line
 *
 * Revision 1.43  2004/08/09 01:31:21  ke
 * -changed read parameters
 *
 * Revision 1.42  2004/08/09 01:15:40  ke
 * -changed fread in status line to fgets
 *
 * Revision 1.41  2004/08/09 00:42:25  ke
 * -status pipe now reads up to 4096 bytes at a time
 * -read $return['output'] rather than stdout for secret keys
 *
 * Revision 1.40  2004/08/08 05:27:21  ke
 * -added check in signing to allow passphrase to be passed even without pipes or known fpr
 *
 * Revision 1.39  2004/08/08 05:22:08  ke
 * -fixed header block on deleteUID function
 *
 * Revision 1.38  2004/08/08 03:58:38  ke
 * -added code to break if data writing fails.
 * -fixes hang in encryption with last changes
 * Bug 206
 *
 * Revision 1.37  2004/08/06 20:26:16  ke
 * -Added function setPrimaryUID to set the primary UID on a key
 * -added extra section when writing data that is >4096 bytes, breaks it up into a loop, reads between every write
 * -changed fgets calls to fread call, should solve issue with missing keys or data from GPG
 * Bug 206
 *
 * Revision 1.36  2004/07/30 04:14:36  joelm
 * print_r() with two params is only available in PHP > 4.3. Wrapped a two-param print_r with if($debug) so that users with PHP < 4.3 will not see an error messageeven when they aren't in debug mode.
 *
 * Revision 1.35  2004/07/07 18:57:43  ke
 * -added checking for action, and set GPGSTDOUT blocking off on stream for encrypt/sign
 * -hopefully fixes stalling interaction with gpg, might have to add other cases for other actions that stall
 * Bug 206
 *
 * Revision 1.34  2004/06/28 21:55:26  ke
 * -added space aftterr passphrase fd to allow for parameters added after without spaces
 *
 * Revision 1.33  2004/06/28 21:51:41  ke
 * -added functions to verify signature data on a detached file
 * -added function to sign detached file
 * -removed all reads on GPGSTDOUT that are not in the main I/O loop
 *
 * Revision 1.32  2004/06/23 15:11:52  ke
 * -added \n even if passphrase is not provided, so gpg does not hang
 * -added strtolower for sort of keyring, to allow upper/lowercase to sort together
 *
 * Revision 1.31  2004/06/18 16:36:38  ke
 * -consolidated all system keyring and trusted key operations into execute code
 * -removed all system keyring additions from external functions
 *
 * Revision 1.30  2004/06/17 19:48:19  ke
 * -Added alternate keyring option to use instead of the default keyrings
 * -Added alternate secret keyring
 *
 * Revision 1.29  2004/05/20 00:31:00  ke
 * -added function to check php version, if not defined by squirrelmail
 *
 * Revision 1.28  2004/05/11 23:31:06  ke
 * -added function to import ascii-armored keyblocks
 *
 * Revision 1.27  2004/05/11 23:10:03  ke
 * -added option to explicitly set a secret keyring
 *
 * Revision 1.26  2004/04/30 17:53:54  ke
 * -removed newline from end of file
 *
 * Revision 1.25  2004/04/13 15:10:40  ke
 * -changed phpdoc comments for addUID to correctly reflect variable names
 *
 * Revision 1.24  2004/04/09 16:43:50  ke
 * -added check for contents of trusted key, ignore if blank
 * -added increaseEntropy function and a trigger for it from the status pipe
 * -fixed encryption parameter line
 *
 * Revision 1.23  2004/04/06 00:18:50  ke
 * -added a check to ensure that select_stream did not return false
 * -changed final NULL value to 0, as per php.net's suggestion
 * done to attempt to solve problem in PHP for windows
 *
 * Revision 1.22  2004/03/31 21:08:48  ke
 * -small typo fixed to allow signature object creation on ERRSIG
 *
 * Revision 1.21  2004/03/30 19:45:37  ke
 * Added functions for decrypt, sign, verify signatures
 * Added object for signatures on data
 * Signature object created with combination of pipes/text parsing
 *
 * Revision 1.20  2004/03/30 02:11:49  ke
 * -changed to reflect current licensing (LGPL, copyright GPG Plugin Development Team)
 *
 * Revision 1.19  2004/03/30 01:35:08  ke
 * -added generate key function
 * -added set key preferences function (goes with generate key, can be used standalone too)
 * -added encrypt function with sign capabilities
 * -added trustedKeys array to append to command line in signing/encryption operations
 * -added pipes interaction code to allow above functions to operate properly
 *
 * Revision 1.18  2004/03/24 17:46:43  ke
 * -changed cannot confirm signature: missing public key message to be a warning instead of an error
 *
 * Revision 1.17  2004/03/23 20:58:16  ke
 * -added cases for subkey revocation interactions
 * -removed --batch from non-pipe commands, to allow for more operations to function properly
 *
 * Revision 1.16  2004/03/22 22:31:50  ke
 * -added function to change expiration on keys or subkeys
 * -added debug output of stdout and stderr upon completion of a command
 *
 * Revision 1.15  2004/03/16 20:25:58  ke
 * -added upload key function to GnuPG object
 * -trap upload key success string with parse_output
 * bug 27
 *
 * Revision 1.14  2004/03/15 22:15:19  ke
 * -added new key functionality to import
 * -fixed bug with flag sent to gpg
 *
 * Revision 1.13  2004/03/11 23:33:05  ke
 * -removed deprecated --always-trust option from main command line
 *
 * Revision 1.12  2004/03/11 21:49:39  ke
 * -added case for signing all uids on a key
 *
 * Revision 1.11  2004/03/09 18:12:29  ke
 * -added functions for add/delete subkey
 * -added functions for add/delete uid on a key
 * -added proc_open interactions to actually add/delete subkeys&uids
 *
 * Revision 1.10  2004/03/04 01:50:41  ke
 * -added function to output ascii armored keys
 * -added signature array on main key object
 * -added signatures to main keys in refreshKeys
 *
 * Revision 1.9  2004/03/03 20:38:25  ke
 * -changed default case and all case of list keys to include system keyring
 *
 * Revision 1.8  2004/03/03 19:42:27  ke
 * -rewrote loop to no longer block on pipes
 * -system keyring view no longer includes keys in homedir
 * -added fingerprint formatting output to key object
 *
 * Revision 1.7  2004/02/27 01:43:09  ke
 * -added system key to refresh on addRevoker
 * -added code to use system keyring when adding revoker
 *
 * Revision 1.6  2004/02/26 23:11:40  ke
 * -added addRevoker function and neccessary interactions with gpg
 * -successfully adds a revoker, but gpg is lacking any way to immediately determine this fact
 * -public key contains revoker information successfully, and can be exported by email/view key
 * bug 52
 *
 * Revision 1.5  2004/02/26 18:17:12  ke
 * -added listener for GPG signal BEGIN_DECRYPTION to read data
 * -more debug output
 * -added patch to ignore locale not being set message from Chuck Foster
 * bug 161
 *
 * Revision 1.4  2004/02/19 21:33:37  ke
 * -added real check for php version in time to not run proc_open
 *
 * Revision 1.3  2004/02/19 18:08:39  ke
 * -added function line back in to fix parse error
 *
 * Revision 1.2  2004/02/19 00:37:19  ke
 * -added function and class headers for phpdoc
 *
 *
 */
?>