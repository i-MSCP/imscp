<?php

// include compatibility plugin
//
if (defined('SM_PATH'))
   include_once(SM_PATH . 'plugins/compatibility/functions.php');
else if (file_exists('../plugins/compatibility/functions.php'))
   include_once('../plugins/compatibility/functions.php');
else if (file_exists('./plugins/compatibility/functions.php'))
   include_once('./plugins/compatibility/functions.php');

   /*
    *  Login:Auto Plugin
    *  By Jay Guerette <JayGuerette@pobox.com>
    *  (c) 2001 (GNU GPL - see ../../COPYING)
    *
    *  If you need help with this, or see improvements that can be made, please
    *  email the SquirrelMail Plugins mailing list or try contacting me at
    *  the address above (note as of May 2003, Jay does not seem to be maintaining
    *  this plugin).  I definately welcome suggestions and comments.  This plugin,
    *  as is the case with all SquirrelMail plugins, is not directly supported
    *  by the developers.
    *
    *  View the INSTALL document for information on installing this.  Also view
    *  the README document and plugins/README.plugins for more information.
    *
    */


   // called in login.php
   function login_auto_get_user_do() {

      global $auto_user, $auto_pass, $auto_key, $base_uri, $user, $loginname;

      if (compatibility_check_sm_version(1, 3))
         include_once (SM_PATH . 'plugins/login_auto/config.php');
      else
         include_once ('../plugins/login_auto/config.php');

      // get global variable for versions of PHP < 4.1
      //
      if (!compatibility_check_php_version(4,1)) {
         global $HTTP_COOKIE_VARS;
         $_COOKIE = $HTTP_COOKIE_VARS;
      }

      if (isset($_COOKIE['user'])) {
         $user = $_COOKIE['user'];
      }
      
      if (isset($_COOKIE['pass'])) {
         $pass = $_COOKIE['pass'];
      }

      if (!$auto_pass) setcookie('pass', '', time()-3600, $base_uri);
      if (!$auto_user) setcookie('user', '', time()-3600, $base_uri);
      if ($auto_user && (isset($user) && $user)) {
         $loginname=MD5Decrypt(base64_decode($user),$auto_key);
         compatibility_sqsession_register($loginname, 'loginname');
         if ($auto_pass && (isset($pass) && $pass)) {
            header("Location: redirect.php?login_username=$loginname");
         }
      }

   }


   // called in login.php
   function login_auto_set_login_do() {

      global $auto_pass, $auto_user, $user, $login_doc, $login_link;

      // note that we get $user as well as all config 
      // variables from the hook before this

      // get global variable for versions of PHP < 4.1
      //
      if (!compatibility_check_php_version(4,1)) {
         global $HTTP_POST_VARS;
         $_POST = $HTTP_POST_VARS;
      }

      $cb_auto_user = (isset($_POST['cb_auto_user'])?$_POST['cb_auto_user']:'');
      $cb_auto_pass = (isset($_POST['cb_auto_pass'])?$_POST['cb_auto_pass']:'');

      echo "<center><table border=0>\n";

      if ($auto_pass) {
         echo "<tr><td valign=center>\n";
         echo "<input type=checkbox name=cb_auto_pass value=true>\n";
         echo "</td><td valign=center nowrap>\n";
         echo "<small>" . _("Remember my Name & Password") . "<small>\n";
         echo "</td></tr>\n";
      }
      else {
         if ($auto_user && !isset($user)) {
            echo "<tr><td valign=center>\n";
            echo "<input type=checkbox name=cb_auto_user value=true>\n";
            echo "</td><td valign=center nowrap>\n";
            echo "<small>" . _("Remember my Name") . "</small>\n";
            echo "</td></tr>\n";
         }
      }

      if (($auto_user || $auto_pass) && $login_doc!='') {
         if (!isset($login_link) || $login_link=='') 
            $login_link=_("What's this?");
         echo "<tr><td colspan='2' valign=center align=center><a href=$login_doc>$login_link</a></td></tr>\n";
      }

      echo "</table></center>\n";

   }


   // called in redirect.php
   function login_auto_get_pass_do() {

      global $auto_pass, $auto_key, $secretkey, $just_logged_in, 
             $user, $pass, $login_username;

      if (compatibility_check_sm_version(1, 3))
         include_once (SM_PATH . 'plugins/login_auto/config.php');
      else
         include_once ('../plugins/login_auto/config.php');

      // get global variable for versions of PHP < 4.1
      //
      if (!compatibility_check_php_version(4,1)) {
         global $HTTP_COOKIE_VARS, $HTTP_GET_VARS;
         $_GET = $HTTP_GET_VARS;
         $_POST = $HTTP_POST_VARS;
      }

      if (isset($_COOKIE['user'])) {
         $user = $_COOKIE['user'];
      }

      if (isset($_COOKIE['pass'])) {
         $pass = $_COOKIE['pass'];
      }

//      if (isset($_GET['login_username'])) {
//         $login_username = $_GET['login_username'];
//      }

      if ($auto_pass && isset($pass) && isset($user)) {
         $login_username = MD5Decrypt(base64_decode($user),$auto_key);
         $secretkey = MD5Decrypt(base64_decode($pass),$auto_key);
         $just_logged_in=1;
      }

      login_auto_clear_cookies();

   }


   // called in redirect.php
   function login_auto_set_cookies_do() {

      global $auto_user, $auto_pass, $auto_key, $user, $login_username, 
             $pass, $auto_expire_days, $auto_expire_hours, $auto_expire_minutes,  
             $base_uri, $secretkey;
 
      // note that we get $user and $pass as well as all config
      // variables from the hook before this

      $auto_expire = $auto_expire_days*86400 + $auto_expire_hours*3600 
                   + $auto_expire_minutes*60;

      // get global variable for versions of PHP < 4.1
      //
      if (!compatibility_check_php_version(4,1)) {
         global $HTTP_POST_VARS;
         $_POST = $HTTP_POST_VARS;
      }

      $cb_auto_user = (isset($_POST['cb_auto_user'])?$_POST['cb_auto_user']:'');
      $cb_auto_pass = (isset($_POST['cb_auto_pass'])?$_POST['cb_auto_pass']:'');

      if ($auto_user && !$auto_pass && !isset($cb_auto_user)) return;

      if ((isset($cb_auto_user) && $cb_auto_user) || (isset($cb_auto_pass) && $cb_auto_pass) ||
         ($auto_user && ($user || $login_username)) || ($auto_pass && $pass)) {
         setcookie('user', base64_encode(MD5Encrypt($login_username, $auto_key)), time()+$auto_expire, $base_uri);
      }
      if ((isset($cb_auto_pass) && $cb_auto_pass) || ($auto_pass && $pass && $user)) {
         setcookie('pass', base64_encode(MD5Encrypt($secretkey, $auto_key)), time()+$auto_expire, $base_uri);
      }

   }


   // called in signout.php
   function login_auto_clear_cookies_do() {

      global $base_uri;

      setcookie('user', '', time()-3600, $base_uri);
      setcookie('pass', '', time()-3600, $base_uri);

   }


   /*
    * The algorithm is a double XOR. MD5Encrypt XORs the plaintext with a random number.
    * The number is interleaved with the XOR output so it can retreived for decryption.
    * MD5Keycrypt XORs this string with your encryption key. Techincally, MD5 is not used
    * for the actual encryption, just to stengthen it.
    *
    */

   function MD5Keycrypt($txt,$key) 
   { 
    for ($i=0,$j=0,$val='',$key=md5($key),$keylen=strlen($key),$txtlen=strlen($txt);$i<$txtlen;$i++) {
        $val.=substr($txt,$i,1)^substr($key,($j==$keylen)?$j*=0:$j++,1);
    } 
    return $val; 
   } 

   function MD5Encrypt($txt,$key) 
   { 
    if (is_array($key)) {
        for ($i=0,$alen=sizeof($key);$i<$alen;$i++) {
            $txt=($i%2)?MD5Encrypt($txt,$key[$i]):MD5Keycrypt($txt,$key[$i]);
        }
        return $txt;
    }
    srand((double)microtime()*1000000); 
    $cryptkey=md5(rand(0,32000));
    for ($i=0,$j=0,$val='',$keylen=strlen($cryptkey),$txtlen=strlen($txt);$i<$txtlen;$i++) {
        $val.=substr($cryptkey,$j,1).(substr($txt,$i,1)^substr($cryptkey,($j==$keylen)?$j*=0:$j++,1));
    } 
    return MD5Keycrypt($val,$key); 
   } 

   function MD5Decrypt($txt,$key) 
   { 
    if (is_array($key)) {
        for ($i=sizeof($key)-1;$i>=0;$i--) {
            $txt=($i%2)?MD5Decrypt($txt,$key[$i]):MD5Keycrypt($txt,$key[$i]);
        }
        return $txt;
    }
    for ($i=0,$val='',$txt=MD5Keycrypt($txt,$key),$txtlen=strlen($txt);$i<$txtlen;$i++) {
        $val.=(substr($txt,$i+1,1)^substr($txt,$i++,1));
    } 
    return $val; 
   } 

?>
