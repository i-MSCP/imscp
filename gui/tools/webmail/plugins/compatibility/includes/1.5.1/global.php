<?php



//
// taken from functions/global.php on 2006/09/07
// since 1.5.1, but modified in 1.5.2
//
if (!function_exists('sqsession_start'))
{
function sqsession_start() {
    global $base_uri;

    session_start();
    $session_id = session_id();

    // session_starts sets the sessionid cookie buth without the httponly var
    // setting the cookie again sets the httponly cookie attribute

    // disable, @see sqsetcookie and php 5.1.2
    // sqsetcookie(session_name(),session_id(),false,$base_uri);
}
}



//
// taken from functions/plugin.php on 2006/09/07
// since 1.5.1
//
if (!function_exists('is_plugin_enabled'))
{
function is_plugin_enabled($plugin_name) {
  global $plugins;

  /**
   * check if variable is empty. if var is not set, php empty
   * returns true without error notice.
   *
   * then check if it is an array
   */
  if (empty($plugins) || ! is_array($plugins))
    return false;

  if ( in_array($plugin_name,$plugins) ) {
    return true;
  } else {
    return false;
  }
}
}



//
// taken from functions/global.php on 2006/09/07
// since 1.5.1, but modified in 1.5.2
//
if (!function_exists('sqsession_start'))
{
function sqsession_start() {
    global $base_uri;

    session_start();
    $session_id = session_id();

    // session_starts sets the sessionid cookie buth without the httponly var
    // setting the cookie again sets the httponly cookie attribute

    // disable, @see sqsetcookie and php 5.1.2
    // sqsetcookie(session_name(),session_id(),false,$base_uri);
}
}



//
// taken from functions/plugin.php
// from 1.5.2cvs on 2007/01/14
// since 1.5.1
//
// NOTE TO PLUGIN AUTHORS: unfortunately, this function is not very useful
// in coding plugins to work with SquirrelMail versions 1.4.x, because said
// versions store $javascript_on as a user preference (!) and not in the
// user session, thus the first time a plugin uses this under 1.4.x, the 
// value of $javascript_on is not found in session and gets reset to zero (off)
//
// Instead of using this, just check the global value of $javascript_on,
// which should also work under 1.5.x
//
// Note that even putting the following in THIS file will not remedy the
// problem because $javascript_on has not yet been defined when this file
// is included:
//    global $javascript_on;
//    sqsession_register($javascript_on, 'javascript_on');
//
if (!function_exists('checkForJavascript'))
{
function checkForJavascript($reset = FALSE) {
  global $data_dir, $username, $javascript_on, $javascript_setting;

  if ( !$reset && sqGetGlobalVar('javascript_on', $javascript_on, SQ_SESSION) )
    return $javascript_on;

  if ( ( $reset || !isset($javascript_setting) )
    // getPref() not defined (nor is it meaningful) when user not
    // logged in, but that begs the question if $javascript_on is
    // not in the session in that case, where do we get it from?
    && ( sqGetGlobalVar('user_is_logged_in', $user_is_logged_in, SQ_SESSION)
      && $user_is_logged_in) )
    $javascript_setting = getPref($data_dir, $username, 'javascript_setting', SMPREF_JS_AUTODETECT);

  if ( !sqGetGlobalVar('new_js_autodetect_results', $js_autodetect_results) &&
       !sqGetGlobalVar('js_autodetect_results', $js_autodetect_results) )
    $js_autodetect_results = SMPREF_JS_OFF;

  if ( $javascript_setting == SMPREF_JS_AUTODETECT )
    $javascript_on = $js_autodetect_results;
  else
    $javascript_on = $javascript_setting;

  sqsession_register($javascript_on, 'javascript_on');
  return $javascript_on;
}
}



//
// taken from functions/global.php
// from 1.5.2cvs on 2007/06/30
// since 1.5.1 I think, but substantially modified in 1.5.2
//
if (!function_exists('sqsetcookie'))
{
function sqsetcookie($sName,$sValue='deleted',$iExpire=0,$sPath="",$sDomain="",$bSecure=false,$bHttpOnly=true) {
    // if we have a secure connection then limit the cookies to https only.
    if ($sName && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
        $bSecure = true;
    }

    // admin config can override the restriction of secure-only cookies
/* Compatibility plugin modification; this will always be false when used in lesser
   SM versions (this variable is not defined before 1.5.2), however, due to the code
   immediately above, plugins like Secure Login and Show SSL Link won't work if you
   ONLY try to do HTTPS for the login and not the rest of the SquirrelMail session......
    global $only_secure_cookies;
    if (!$only_secure_cookies)
        $bSecure = false;
*/

    if (false && check_php_version(5,2)) {
       // php 5 supports the httponly attribute in setcookie, but because setcookie seems a bit
       // broken we use the header function for php 5.2 as well. We might change that later.
       //setcookie($sName,$sValue,(int) $iExpire,$sPath,$sDomain,$bSecure,$bHttpOnly);
    } else {
        if (!empty($sDomain)) {
            // Fix the domain to accept domains with and without 'www.'.
            if (strtolower(substr($sDomain, 0, 4)) == 'www.')  $sDomain = substr($sDomain, 4);
            $sDomain = '.' . $sDomain;

            // Remove port information.
            $Port = strpos($sDomain, ':');
            if ($Port !== false)  $sDomain = substr($sDomain, 0, $Port);
        }
        if (!$sValue) $sValue = 'deleted';
        header('Set-Cookie: ' . rawurlencode($sName) . '=' . rawurlencode($sValue)
                            . (empty($iExpire) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s', $iExpire) . ' GMT')
                            . (empty($sPath) ? '' : '; path=' . $sPath)
                            . (empty($sDomain) ? '' : '; domain=' . $sDomain)
                            . (!$bSecure ? '' : '; secure')
                            . (!$bHttpOnly ? '' : '; HttpOnly'), false);
    }
}
}



//
// taken from functions/auth.php
// from 1.5.2cvs on 2007/01/24
// since 1.5.1 and 1.4.11
//
if ((!compatibility_check_sm_version(1, 4, 11)
 || (compatibility_check_sm_version(1, 5, 0) && !compatibility_check_sm_version(1, 5, 1)))
 && !function_exists('sqauth_read_password'))
{
function sqauth_read_password() {
    sqgetGlobalVar('key',         $key,       SQ_COOKIE);
    sqgetGlobalVar('onetimepad',  $onetimepad,SQ_SESSION);

    return OneTimePadDecrypt($key, $onetimepad);
}
}



//
// taken from functions/auth.php
// from 1.5.2cvs on 2007/01/24
// since 1.5.1 and 1.4.16
//
if ((!compatibility_check_sm_version(1, 4, 16)
 || (compatibility_check_sm_version(1, 5, 0) && !compatibility_check_sm_version(1, 5, 1)))
 && !function_exists('sqauth_save_password'))
{
function sqauth_save_password($pass) {
    sqgetGlobalVar('base_uri',    $base_uri,   SQ_SESSION);

    $onetimepad = OneTimePadCreate(strlen($pass));
    sqsession_register($onetimepad,'onetimepad');
    $key = OneTimePadEncrypt($pass, $onetimepad);
    sqsetcookie('key', $key, false, $base_uri);
    return $key;
}
}



//
// taken from 1.4.10-svn functions/i18n.php on 2007/04/01
// since 1.5.1 and 1.4.10
//
// This code was taken from 1.4.10, because it will work with
// all 1.4.x and 1.5.x code, but does not support ngettext
// compatibility that was added to this function in 1.5.x...
// lowest common denominator...  if you want and need ngettext
// support, just upgrade to 1.5.1+ or settle for the crude
// ngettext replacement herein (which won't provide correct
// translations in some languages)
//
if ((!compatibility_check_sm_version(1, 4, 10)
 || (compatibility_check_sm_version(1, 5, 0) && !compatibility_check_sm_version(1, 5, 1)))
 && !function_exists('sq_bindtextdomain'))
{
function sq_bindtextdomain($domain,$dir='') {

    global $languages, $sm_notAlias;

    if (empty($dir)) $dir = SM_PATH . 'locale/';

    $dir = bindtextdomain($domain, $dir);

    // set codeset in order to avoid gettext charset conversions
    //
    if (function_exists('bind_textdomain_codeset')
     && isset($languages[$sm_notAlias]['CHARSET'])) {

        // Japanese translation uses different internal charset
        //
        if ($sm_notAlias == 'ja_JP') {
            bind_textdomain_codeset($domain, 'EUC-JP');
        } else {
            bind_textdomain_codeset($domain, $languages[$sm_notAlias]['CHARSET']);
        }

    }

    return $dir;
}
}



//
// This is a poor quality replacement for ngettext().  It
// does not include correct plural form support and merely
// returns the (translated) singluar form string.  This is
// only here to provide a baseline/fallback ngettext stand-in
// for systems not running at least SquirrelMail 1.5.1 OR PHP
// 4.2.0 or better with the gettext extension compiled in.
//
// If someone has a clever idea about how to get a translated
// version of the plural form from the gettext catalog, this
// function could support at least translated Germanic plural
// forms, but I couldn't figure out a way to do that.  It'd
// also have to be compatible with the internal SquirrelMail
// gettext implementation (replacement).  Really, this is
// only an issue when in use under SquirrelMail versions 
// before 1.5.1 and PHP versions before 4.2.0 or any PHP
// version without the gettext extension.  Users who need
// correct ngettext() support should simply upgrade to
// SquirrelMail 1.5.1+ or PHP 4.2.0+ with the gettext
// extension, as this function is just provided so plugins
// using ngettext() don't break completely when it's not
// available.
//
if (!function_exists('ngettext'))
{
function ngettext($single, $plural, $number)
{
   return _($single);
}
}



