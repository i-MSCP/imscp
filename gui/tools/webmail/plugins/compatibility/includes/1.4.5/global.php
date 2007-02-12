<?php



//
// actually from functions/plugin.php
// since 1.5.1cvs on 2005/7/14
//
if (!function_exists('is_plugin_enabled'))
{
/**
 * Check if plugin is enabled
 * @param string $plugin_name plugin name
 * @since 1.5.1
 * @return boolean
 */
function is_plugin_enabled($plugin_name) {
  global $plugins;

  if (! isset($plugins) || ! is_array($plugins) || empty($plugins))
    return false;

  if ( in_array($plugin_name,$plugins) ) {
    return true;
  } else {
    return false;
  }
}
}



//if (!function_exists('sqsession_is_active'))
//{
//function sqsession_is_active() {
//    $sessid = session_id();
//    if ( empty( $sessid ) ) {
//        sqsession_start();
//    }
//}
//}



if (!function_exists('sqsession_start'))
{
function sqsession_start() {
    global $PHP_SELF;

    $dirs = array('|src/.*|', '|plugins/.*|', '|functions/.*|');
    $repl = array('', '', '');
    $base_uri = preg_replace($dirs, $repl, $PHP_SELF);

    session_start();
    $sessid = session_id();
    // session_starts sets the sessionid cookie buth without the httponly var
    // setting the cookie again sets the httponly cookie attribute
    sqsetcookie(session_name(),$sessid,false,$base_uri);
}
}



if (!function_exists('sqsetcookie'))
{
function sqsetcookie($sName,$sValue,$iExpire=false,$sPath="",$sDomain="",$bSecure=false,$bHttpOnly=true) {
    $sHeader = "Set-Cookie: $sName=$sValue";
    if ($sPath) {
        $sHeader .= "; Path=\"$sPath\"";
    }
    if ($iExpire !==false) {
        $sHeader .= "; Max-Age=$iExpire";
    }
    if ($sPath) {
        $sHeader .= "; Path=$sPath";
    }
    if ($sDomain) {
        $sHeader .= "; Domain=$sDomain";
    }
    if ($bSecure) {
        $sHeader .= "; Secure";
    }
    if ($bHttpOnly) {
        $sHeader .= "; HttpOnly";
    }
    $sHeader .= "; Version=1";

    header($sHeader);
}
}



//
// actually from functions/prefs.php
// since 1.5.1cvs on 2004/??
//
if (!function_exists('checkForJavascript'))
{
function checkForJavascript($reset = FALSE) {
  global $data_dir, $username, $javascript_on, $javascript_setting;

  if ( !$reset && sqGetGlobalVar('javascript_on', $javascript_on, SQ_SESSION) )
    return $javascript_on;

  if ( $reset || !isset($javascript_setting) )
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



?>
