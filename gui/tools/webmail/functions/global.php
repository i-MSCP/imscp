<?php

/**
 * global.php
 *
 * This includes code to update < 4.1.0 globals to the newer format
 * It also has some session register functions that work across various
 * php versions.
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: global.php,v 1.27.2.19 2006/07/29 08:57:52 tokul Exp $
 * @package squirrelmail
 */

/**
 * Set constants
 */
define('SQ_INORDER',0);
define('SQ_GET',1);
define('SQ_POST',2);
define('SQ_SESSION',3);
define('SQ_COOKIE',4);
define('SQ_SERVER',5);
define('SQ_FORM',6);

/** First code that should be executed before other files are loaded */

/**
 * Must be executed before any other scripts are loaded.
 *
 * If register_globals are on, unregister globals.
 * Second test covers boolean set as string (php_value register_globals off).
 */
if ((bool) ini_get('register_globals') &&
    strtolower(ini_get('register_globals'))!='off') {
    /**
     * Remove all globals that are not reserved by PHP
     * 'value' and 'key' are used by foreach. Don't unset them inside foreach.
     */
    foreach ($GLOBALS as $key => $value) {
        switch($key) {
        case 'HTTP_POST_VARS':
        case '_POST':
        case 'HTTP_GET_VARS':
        case '_GET':
        case 'HTTP_COOKIE_VARS':
        case '_COOKIE':
        case 'HTTP_SERVER_VARS':
        case '_SERVER':
        case 'HTTP_ENV_VARS':
        case '_ENV':
        case 'HTTP_POST_FILES':
        case '_FILES':
        case '_REQUEST':
        case 'HTTP_SESSION_VARS':
        case '_SESSION':
        case 'GLOBALS':
        case 'key':
        case 'value':
            break;
        default:
            unset($GLOBALS[$key]);
        }
    }
    // Unset variables used in foreach
    unset($GLOBALS['key']);
    unset($GLOBALS['value']);
}

/*
 * strip any tags added to the url from PHP_SELF.
 * This fixes hand crafted url XXS expoits for any
 * page that uses PHP_SELF as the FORM action
 * Must be executed before strings.php is loaded (php_self() call in strings.php).
 */
if (isset($_SERVER['PHP_SELF'])) {
    // php 4.1+
    $_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);
} elseif (isset($HTTP_SERVER_VARS['PHP_SELF'])) {
    // php 4.0.6
    $HTTP_SERVER_VARS['PHP_SELF'] = strip_tags($HTTP_SERVER_VARS['PHP_SELF']);
}

/**
 * Bring in the config file
 * We need $session_name
 * config.php $version depends on strings.php.
 * strings.php sets $PHP_SELF.
 */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');

/** set the name of the session cookie */
if(isset($session_name) && $session_name) {
    ini_set('session.name' , $session_name);
} else {
    ini_set('session.name' , 'SQMSESSID');
}

/**
 * If magic_quotes_runtime is on, SquirrelMail breaks in new and creative ways.
 * Force magic_quotes_runtime off.
 * tassium@squirrelmail.org - I put it here in the hopes that all SM code includes this.
 * If there's a better place, please let me know.
 */
ini_set('magic_quotes_runtime','0');

/**
 * [#1518885] session.use_cookies = off breaks SquirrelMail
 *
 * When session cookies are not used, all http redirects, meta refreshes,
 * src/download.php and javascript URLs are broken. Setting must be set
 * before session is started.
 */
if (!(bool)ini_get('session.use_cookies') ||
    ini_get('session.use_cookies') == 'off') {
    ini_set('session.use_cookies','1');
}

/* convert old-style superglobals to current method
 * this is executed if you are running PHP 4.0.x.
 * it is run via a require_once directive in validate.php
 * and redirect.php. Patch submitted by Ray Black.
 */
sqsession_is_active();
if ( !check_php_version(4,1) ) {
  global $_COOKIE, $_ENV, $_FILES, $_GET, $_POST, $_SERVER, $_SESSION;
  global $HTTP_COOKIE_VARS, $HTTP_ENV_VARS, $HTTP_POST_FILES, $HTTP_GET_VARS,
         $HTTP_POST_VARS, $HTTP_SERVER_VARS, $HTTP_SESSION_VARS, $PHP_SELF;
  $_COOKIE  =& $HTTP_COOKIE_VARS;
  $_ENV     =& $HTTP_ENV_VARS;
  $_FILES   =& $HTTP_POST_FILES;
  $_GET     =& $HTTP_GET_VARS;
  $_POST    =& $HTTP_POST_VARS;
  $_SERVER  =& $HTTP_SERVER_VARS;
  $_SESSION =& $HTTP_SESSION_VARS;
  if (!isset($PHP_SELF) || empty($PHP_SELF)) {
     $PHP_SELF =  $HTTP_SERVER_VARS['PHP_SELF'];
  }
}

/* if running with magic_quotes_gpc then strip the slashes
   from POST and GET global arrays */

if (get_magic_quotes_gpc()) {
    sqstripslashes($_GET);
    sqstripslashes($_POST);
}

/**
 * returns true if current php version is at mimimum a.b.c
 *
 * Called: check_php_version(4,1)
 * @param int a major version number
 * @param int b minor version number
 * @param int c release number
 * @return bool
 */
function check_php_version ($a = '0', $b = '0', $c = '0')
{
    global $SQ_PHP_VERSION;

    if(!isset($SQ_PHP_VERSION))
        $SQ_PHP_VERSION = substr( str_pad( preg_replace('/\D/','', PHP_VERSION), 3, '0'), 0, 3);

    return $SQ_PHP_VERSION >= ($a.$b.$c);
}

/**
 * returns true if the current internal SM version is at minimum a.b.c
 * These are plain integer comparisons, as our internal version is
 * constructed by us, as an array of 3 ints.
 *
 * Called: check_sm_version(1,3,3)
 * @param int a major version number
 * @param int b minor version number
 * @param int c release number
 * @return bool
 */
function check_sm_version($a = 0, $b = 0, $c = 0)
{
    global $SQM_INTERNAL_VERSION;
    if ( !isset($SQM_INTERNAL_VERSION) ||
         $SQM_INTERNAL_VERSION[0] < $a ||
         ( $SQM_INTERNAL_VERSION[0] == $a &&
           $SQM_INTERNAL_VERSION[1] < $b) ||
         ( $SQM_INTERNAL_VERSION[0] == $a &&
           $SQM_INTERNAL_VERSION[1] == $b &&
           $SQM_INTERNAL_VERSION[2] < $c ) ) {
        return FALSE;
    }
    return TRUE;
}


/**
 * Recursively strip slashes from the values of an array.
 * @param array array the array to strip, passed by reference
 * @return void
 */
function sqstripslashes(&$array) {
    if(count($array) > 0) {
        foreach ($array as $index=>$value) {
            if (is_array($array[$index])) {
                sqstripslashes($array[$index]);
            }
            else {
                $array[$index] = stripslashes($value);
            }
        }
    }
}

/**
 * Add a variable to the session.
 * @param mixed $var the variable to register
 * @param string $name the name to refer to this variable
 * @return void
 */
function sqsession_register ($var, $name) {

    sqsession_is_active();

    if ( !check_php_version(4,1) ) {
        global $HTTP_SESSION_VARS;
        $HTTP_SESSION_VARS[$name] = $var;
    } else {
        $_SESSION["$name"] = $var;
    }
    session_register("$name");
}

/**
 * Delete a variable from the session.
 * @param string $name the name of the var to delete
 * @return void
 */
function sqsession_unregister ($name) {

    sqsession_is_active();

    if ( !check_php_version(4,1) ) {
        global $HTTP_SESSION_VARS;
        unset($HTTP_SESSION_VARS[$name]);
    } else {
        unset($_SESSION[$name]);
    }
    session_unregister("$name");
}

/**
 * Checks to see if a variable has already been registered
 * in the session.
 * @param string $name the name of the var to check
 * @return bool whether the var has been registered
 */
function sqsession_is_registered ($name) {
    $test_name = &$name;
    $result = false;
    if ( !check_php_version(4,1) ) {
        global $HTTP_SESSION_VARS;
        if (isset($HTTP_SESSION_VARS[$test_name])) {
            $result = true;
        }
    } else {
        if (isset($_SESSION[$test_name])) {
            $result = true;
        }
    }

    return $result;
}

/**
 * Search for the var $name in $_SESSION, $_POST, $_GET,
 * $_COOKIE, or $_SERVER and set it in provided var.
 *
 * If $search is not provided,  or == SQ_INORDER, it will search
 * $_SESSION, then $_POST, then $_GET. Otherwise,
 * use one of the defined constants to look for
 * a var in one place specifically.
 *
 * Note: $search is an int value equal to one of the
 * constants defined above.
 *
 * example:
 *    sqgetGlobalVar('username',$username,SQ_SESSION);
 *  -- no quotes around last param!
 *
 * @param string name the name of the var to search
 * @param mixed value the variable to return
 * @param int search constant defining where to look
 * @return bool whether variable is found.
 */
function sqgetGlobalVar($name, &$value, $search = SQ_INORDER) {

    if ( !check_php_version(4,1) ) {
        global $HTTP_COOKIE_VARS, $HTTP_GET_VARS, $HTTP_POST_VARS,
               $HTTP_SERVER_VARS, $HTTP_SESSION_VARS;

        $_COOKIE  =& $HTTP_COOKIE_VARS;
        $_GET     =& $HTTP_GET_VARS;
        $_POST    =& $HTTP_POST_VARS;
        $_SERVER  =& $HTTP_SERVER_VARS;
        $_SESSION =& $HTTP_SESSION_VARS;
    }

    /* NOTE: DO NOT enclose the constants in the switch
       statement with quotes. They are constant values,
       enclosing them in quotes will cause them to evaluate
       as strings. */
    switch ($search) {
        /* we want the default case to be first here,
           so that if a valid value isn't specified,
           all three arrays will be searched. */
      default:
      case SQ_INORDER: // check session, post, get
      case SQ_SESSION:
        if( isset($_SESSION[$name]) ) {
            $value = $_SESSION[$name];
            return TRUE;
        } elseif ( $search == SQ_SESSION ) {
            break;
        }
      case SQ_FORM:   // check post, get
      case SQ_POST:
        if( isset($_POST[$name]) ) {
            $value = $_POST[$name];
            return TRUE;
        } elseif ( $search == SQ_POST ) {
          break;
        }
      case SQ_GET:
        if ( isset($_GET[$name]) ) {
            $value = $_GET[$name];
            return TRUE;
        }
        /* NO IF HERE. FOR SQ_INORDER CASE, EXIT after GET */
        break;
      case SQ_COOKIE:
        if ( isset($_COOKIE[$name]) ) {
            $value = $_COOKIE[$name];
            return TRUE;
        }
        break;
      case SQ_SERVER:
        if ( isset($_SERVER[$name]) ) {
            $value = $_SERVER[$name];
            return TRUE;
        }
        break;
    }
    /* if not found, return false */
    return FALSE;
}

/**
 * Deletes an existing session, more advanced than the standard PHP
 * session_destroy(), it explicitly deletes the cookies and global vars.
 */
function sqsession_destroy() {

    /*
     * php.net says we can kill the cookie by setting just the name:
     * http://www.php.net/manual/en/function.setcookie.php
     * maybe this will help fix the session merging again.
     *
     * Changed the theory on this to kill the cookies first starting
     * a new session will provide a new session for all instances of
     * the browser, we don't want that, as that is what is causing the
     * merging of sessions.
     */

    global $base_uri;

    if (isset($_COOKIE[session_name()])) setcookie(session_name(), '', 0, $base_uri);
    if (isset($_COOKIE['username'])) setcookie('username', '', 0, $base_uri);
    if (isset($_COOKIE['key'])) setcookie('key', '', 0, $base_uri);

    $sessid = session_id();
    if (!empty( $sessid )) {
        if ( !check_php_version(4,1) ) {
            global $HTTP_SESSION_VARS;
            $HTTP_SESSION_VARS = array();
        } else {
            $_SESSION = array();
        }
        @session_destroy();
    }

}

/**
 * Function to verify a session has been started.  If it hasn't
 * start a session up.  php.net doesn't tell you that $_SESSION
 * (even though autoglobal), is not created unless a session is
 * started, unlike $_POST, $_GET and such
 */

function sqsession_is_active() {

    $sessid = session_id();
    if ( empty( $sessid ) ) {
        session_start();
    }
}

// vim: et ts=4
?>
