<?php
/**
 * gpg_test_class.php - PHPUnit test framework for GPG object
 *
 * Copyright (c) 2005 GPG Plugin Development Team
 * All Rights Reserved.
 *
 *
 * @author Aaron van Meerten
 * $Id: gpg_test_class.php,v 1.1 2005/11/11 07:25:15 ke Exp $
*/
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATH in keyring_main.php, exiting abnormally";
}
global $GPG_VERSION;

require_once("PHPUnit.php");

require_once (SM_PATH.'plugins/gpg/gpg_config.php');
require_once(SM_PATH.'plugins/gpg/gpg.php');
require_once(SM_PATH.'plugins/gpg/gpg_pref_functions.php');
load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_system_defaults.txt',$debug);
load_prefs_from_file(SM_PATH.'plugins/gpg/gpg_local_prefs.txt',$debug);

Class PHPUnit_GnuPGTestCase extends PHPUnit_TestCase {

    //variable to store the directory for key tests
    var $test_gpg_key_dir=NULL;

    function PHPUnit_GnuPGTestCase ($name = "PHPUnit_GnuPGTestCase") {
        global $data_dir;
        
        $this->gpg_key_dir=$data_dir.'PHPUnitTest.gnupg';

        $this->PHPUnit_TestCase( $name );
    }
    
    function _GPGUnit_TestCase() {
    }

    function setup_keydir() {
        //make sure we only write files apache can read
        umask (077);
        if (!is_dir($this->gpg_key_dir)){
            mkdir ($this->gpg_key_dir, 01700);
        };
    }

    function teardown_keydir() {
        $dir=$this->gpg_key_dir;
         $dir_class = dir($dir);
        while (false !== ($entry = $dir_class->read())) {
            if ($entry != '.' && $entry != '..') {
                unlink($dir.'/'.$entry);
            }
        }
        $dir_class->close();
        @rmdir($dir);
    }

    function setUp() {
            global $gpg_key_dir;
            global $safe_data_dir;
            global $username;
            $this->setup_keydir();
            $gpgexec = new GnuPG;
            //set debug
            if ($debug) {
                    $gpgexec->debug = true;
            }

        //don't let this process spawn entropy
        $gpgexec->allowEntropyIncrease=false;

        
        $this->email="PHPUnitTest@GPG_TEST_CASE.ws";
        $this->passphrase="t3stc@s3";


        $test_gpg_key_dir=$this->gpg_key_dir;

            //set path to gpg executable
            $gpgexec->gpg_exe = ($GLOBALS['GPG_SYSTEM_OPTIONS']['path_to_gpg']);
    
            //set path to temporary directory
            $gpgexec->setTempDir($GLOBALS['GPG_SYSTEM_OPTIONS']['tmp_dir']);
    
        $gpgexec->gpgHomeDir = $test_gpg_key_dir;
    
    $this->gpg=$gpgexec;

    }

    function tearDown() {
        $this->teardown_keydir();
        //do something here
    }

    function test_truth() {
        $this->assertTrue(true, "Should never failed, since true==true");
    }

    function test_createTempFIle() {
        $test_str="I AM A LEAF ON THE WIND\n";
        $test_size=strlen($test_str);
        $tmpfile=$this->gpg->getTempFile();
        $this->assertTrue($tmpfile, "Failed to get a tempfile from getTempFile");
        $this->assertTrue(!$this->gpg->isError(), "Got an error, no error expected");
        if ($this->gpg->isError()) {
            $this->fail($this->gpg->getErrorCode() . ": " . $this->gpg->getErrorDescription());
        }
        $this->assertTrue(is_file($tmpfile), "Failed to find file $tmpfile, which getTempFIle claims is our new temp file");
        $this->assertTrue(is_writeable($tmpfile), "Failed to have write access to temp file $tmpfile");
        $pret=file_put_contents($tmpfile, $test_str);
        $this->assertTrue($pret==$test_size, "Failed to match while writing to file: tried $test_size bytes, wrote $pret");
        return $tmpfile;
    }

    function test_secure_unlink() {
        $tmpfile=$this->test_createTempFile();
        $ret=$this->gpg->secure_unlink($tmpfile);
        $this->assertTrue($ret, "Secure unlink function failed to return success for unlinking file");
        $this->assertTrue(!is_file($tmpfile), "Found file $tmpfile still in existance, failed to securely unlink file");
        return $ret;
    }

    function test_fetch_keys($search='', $keyring='public',$count=0) {
        $ret=$this->gpg->fetchKeys($search, $keyring);
        $this->assertTrue($ret, "Failed to fetch keys from gpg");
        $keys=$this->gpg->keys;
        $this->assertTrue(is_array($keys), "Failed to find array of keys from gpg");
        $keycount=count($keys);
        $this->assertTrue($keycount==$count, "Intended to find $count keys, found $keycount instead");
        return $ret;
    }


    function test_generateKey($name="PHPUnitTest",$email=false, $passphrase=false,$comment='Disregard this test case', $keylength=1024, $expiredate=0, $algo=1, $prefs=false, $delete_keypair=true) {
        if ($passphrase===false) { $passphrase=$this->passphrase; }
        if ($email===false) { $email=$this->email; }

        $ret=$this->gpg->generateKey($name, $email, $passphrase, $comment, $keylength, $expiredate,$algo,$prefs);        
        $this->assertTrue($ret, "false returned from generateKey, serious problem occured");

        $errorcount=count($ret['errors']);
        $this->assertTrue($errorcount==0,"$errorcount errors while generating key with name $name, email $email, passphrase [pass] comment  $comment for length $keylength, expires $expiredate, algorithm $algo, with prefs $prefs");
        if ($errorcount) {
            foreach ($ret['errors'] as $gpg_err) {
                $this->fail($gpg_err);
            }
        }
        $newkeys=$ret['newkeys'];
        $this->assertTrue(is_array($newkeys), "New keys return is not an array");

        if ($newkeys AND is_array($newkeys)) {
            $gendate=date("Y-m-d");

            $newkeycount=count($newkeys);
            $this->assertTrue(count($newkeys)==1, "Failed to generate 1 key, generated $newkeycount instead");
            reset($newkeys);
            $newfpr=current($newkeys);
            
            //make sure this info is current
            $this->gpg->refreshKeys();

            $key = $this->gpg->getKey($newfpr);
            $this->assertTrue($key, "Failed to fetch key for newly created key $newfpr");
            $this->assertTrue($key->date==$gendate, "Failed to match creation date $gendate to key's creation {$key->date}");
            $this->assertTrue($key->exp==$expiredate, "Failed to match expiration $expiredate with key's expiration {$key->exp}");

            //SUBKEY TESTS
            $subkey = current($key->subkeys);
            $this->assertTrue($subkey->len==$keylength, "Failed to match subkey length $keylength with key's subkey length {$subkey->len}");
            $this->assertTrue($subkey->exp==$expiredate, "Failed to match subkey expiration $expiredate with key's subkey expiration {$subkey->exp}");


            //UID TESTS
            $uid=current($key->userIDs);
            $this->assertTrue($uid->email_addr==$email, "Failed to match email $email to uid's email_addr {$uid->email_addr}");

            $comparename=$name;
            if ($comment) $comparename.=" ($comment)";
            $this->assertTrue($uid->email_name==$comparename, "Failed to match name $comparename to uid's email_name {$uid->email_name}");
    //skipping these tests, instead using name + comment for test
//            $this->assertTrue($uid->email_extra==$comment, "Failed to match comment $comment to uid's email_addr {$uid->email_extra}");
//            $this->assertTrue($uid->email_name==$name, "Failed to match name $name to uid's email_name {$uid->email_name}");
            
            $uid_sig=current($uid->signatures);
            $this->assertTrue($uid_sig,"Failed to get self-signature on UID");
            $this->assertTrue($uid_sig->id==$key->id, "Failed to match self-signature on uid, UID {$uid_sig->id} does not match key {$key->id}");


            //if we set the option, delete the keypair now that we've run the test
            if ($delete_keypair) {
                $ret=$this->gpg->deleteKey($newfpr);
            }

        } else {
            $this->fail("Failed to find new keys, failing further tests");
        }

        return $ret;
    }

    function test_verifyPassphrase($passphrase=false, $keyfpr=false) {
        if ($passphrase) $this->passphrase=$passphrase;
        else $passphrase=$this->passphrase;

        $genned_key=false;
        if ($keyfpr===false) {
            $ret=$this->test_generateKey('VerifyTest', false, false,false, 1024, 0, 1, false, false);
            $this->assertTrue(is_array($ret['newkeys']), "No new keys generated for verify passphrase, failing rest of test");
            if (!is_array($ret['newkeys'])) return false;
            $keyfpr=current($ret['newkeys']);
            if (!$keyfpr) { $this->fail("Failed to get a key for passphrase verification, failing"); return false; }
            $genned_key=true;
        }
        $return=$this->gpg->verifyPassphrase($passphrase,$keyfpr);
        $this->assertTrue($return['verified']=='true', "Verification of passphrase [pass] on key $keyfpr failed");
        if ($genned_key AND $keyfpr) {
            $this->gpg->deleteKey($keyfpr);
        }
        return $return;
    }

    function test_gpg_nopipes() {
        //set exec execution to true, to use nopipes function for sure
        $this->gpg->force_exec=true;
        //easy test, fetch keys should not be affected much by nopipes
        $ret=$this->test_fetch_keys();
        $this->assertTrue($ret, "Failed fetch key gpg test for nopipes");

        $ret=$this->test_verifyPassphrase();
        $this->assertTrue($ret, "Failed verify passphrase for nopipes");
        $this->assertTrue($ret['verified']=='true', "Failed verify passphrase for nopipes");

    }


}


?>
