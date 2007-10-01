<?php

/**
 * login.php -- simple login screen
 *
 * This a simple login screen. Some housekeeping is done to clean
 * cookies and find language.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: login.php 12614 2007-08-28 20:30:29Z pdontthink $
 * @package squirrelmail
 */

/** This is the login page */
define('PAGE_NAME', 'login');

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');
require_once(SM_PATH . 'functions/i18n.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/constants.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/forms.php');

/**
 * $squirrelmail_language is set by a cookie when the user selects
 * language and logs out
 */
set_up_language($squirrelmail_language, TRUE, TRUE);

$base_uri = sqm_baseuri();

/**
 * In case the last session was not terminated properly, make sure
 * we get a new one, but make sure we preserve session_expired_*
 */
$sep = '';
$sel = '';
sqGetGlobalVar('session_expired_post', $sep, SQ_SESSION);
sqGetGlobalVar('session_expired_location', $sel, SQ_SESSION);

/* blow away session */
sqsession_destroy();

/**
 * in some rare instances, the session seems to stick
 * around even after destroying it (!!), so if it does,
 * we'll manually flatten the $_SESSION data
 */
if (!empty($_SESSION)) {
    $_SESSION = array();
}

/* put session_expired_* variables back in session */
sqsession_is_active();
if (!empty($sel)) {
    sqsession_register($sel, 'session_expired_location');
    if (!empty($sep))
        sqsession_register($sep, 'session_expired_post');
}

header('Pragma: no-cache');

do_hook('login_cookie');

$loginname_value = (sqGetGlobalVar('loginname', $loginname) ? htmlspecialchars($loginname) : '');

/* Output the javascript onload function. */

$header = "<script language=\"JavaScript\" type=\"text/javascript\">\n" .
          "<!--\n".
          "  function squirrelmail_loginpage_onload() {\n".
          "    document.forms[0].js_autodetect_results.value = '" . SMPREF_JS_ON . "';\n".
          "    var textElements = 0;\n".
          "    for (i = 0; i < document.forms[0].elements.length; i++) {\n".
          "      if (document.forms[0].elements[i].type == \"text\" || document.forms[0].elements[i].type == \"password\") {\n".
          "        textElements++;\n".
          "        if (textElements == " . (isset($loginname) ? 2 : 1) . ") {\n".
          "          document.forms[0].elements[i].focus();\n".
          "          break;\n".
          "        }\n".
          "      }\n".
          "    }\n".
          "  }\n".
          "// -->\n".
          "</script>\n";
$custom_css = 'none';

// Load default theme if possible
if (@file_exists($theme[$theme_default]['PATH']))
   @include ($theme[$theme_default]['PATH']);

if (! isset($color) || ! is_array($color)) {
    // Add default color theme, if theme loading fails
    $color = array();
    $color[0]  = '#dcdcdc';  /* light gray    TitleBar               */
    $color[1]  = '#800000';  /* red                                  */
    $color[2]  = '#cc0000';  /* light red     Warning/Error Messages */
    $color[4]  = '#ffffff';  /* white         Normal Background      */
    $color[7]  = '#0000cc';  /* blue          Links                  */
    $color[8]  = '#000000';  /* black         Normal text            */
}

displayHtmlHeader( "$org_name - " . _("Login"), $header, FALSE );

//echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" onLoad=\"squirrelmail_loginpage_onload();\">" .
echo '<body text="#000000" bgcolor="#FFFFFF" link="#0000CC" vlink="#0000CC" alink="#0000CC" onload="squirrelmail_loginpage_onload();">' .
     "\n" . addForm('redirect.php', 'post');

$username_form_name = 'login_username';
$password_form_name = 'secretkey';
do_hook('login_top');


if(sqgetGlobalVar('mailtodata', $mailtodata)) {
    $mailtofield = addHidden('mailtodata', $mailtodata);
} else {
    $mailtofield = '';
}

/* If they don't have a logo, don't bother.. */
if (isset($org_logo) && $org_logo) {
    /* Display width and height like good little people */
    $width_and_height = '';
    if (isset($org_logo_width) && is_numeric($org_logo_width) &&
     $org_logo_width>0) {
        $width_and_height = " width=\"$org_logo_width\"";
    }
    if (isset($org_logo_height) && is_numeric($org_logo_height) &&
     $org_logo_height>0) {
        $width_and_height .= " height=\"$org_logo_height\"";
    }
}

<style type="text/css">
<!--
body {
	text-align: center;
	margin: 0;
	padding: 0;
	background-color: #c0c0c0;
	font-family:Geneva, Arial, Helvetica, sans-serif;
}

#container {
	background-image:    url(../images/bg.jpg);
	background-repeat: no-repeat;
	background-attachment: scroll;
	background-position: center left;
	margin: 8em auto;
	width: 474px;
	height: 273px;
	position: relative;
}

#logo {
	width: 140px;
	height: 78px;
	background-image: url(<? echo "$org_logo" ?>);
	position: absolute;
	top: 120px;
	left: 40px;
}

#copyright {
	color: #97E4F7;
	width: 290px;
	height: 77px;
	position: absolute;
	top: 155px;
	left: -200px;
}

h1 {
	text-align: right;
	font-size: 1em;
	color: #FFFFFF;
	width: 375px;
	margin: 65px 20px 0px 0px;
	position: absolute;
	top: -10px;
	left: 25px;
}

h2 {
	color: #666666;
	text-align: right;
	font-size: .6em;
	margin: 15px 30px;
	position: absolute;
	top: 90px;
	left: 170px;
}

fieldset {
	width: 200px;
	margin: 0 auto auto 190px;
	text-align: right;
	border: none;
	position: absolute;
	top: 100px;
	left: 20px;
}

p.1, p.2, p.3 {
	font-size: .7em;
	color: #6D6D6D;
	vertical-align: super;
}

p.1 {
	color: #666666;
	margin: 0px 0 0 0;
}

p.2, p.3 {
	margin: 3px 0 0 0;
	color: #6D6D6D;
}

p.4 {
	margin: 3px 0px 0px 0px;
	text-decoration: none;
}

.input {
	margin: 3px 12px 0px 7px;
	background-color: #FFFFFF;
	height: 16px;
	width: 10em;
	border: 1px solid #c0c0c0;
	font-size: 11px;
	vertical-align: middle;
}

.button {
	width: 93px;
	height: 20px;
	background-color: #202020;
	color: #f4dc6b;
	border: none;
	font-weight: bold;
	font-size: 9px;
	margin: -10px 12px 0 0 !important;
	margin: 5px 12px 0 0;
}

p.logout {
	font-size: .7em;
	font-weight: bold;
	text-transform: uppercase;
	color: #0066CC;
	margin: 3em 2em 0 15em;
}
-->
</style>

<div id="container">
  <h1>WebMail Login</h1>

  <fieldset>
  <font size="2" color="#FFFFFF">Username:</font><?php echo "<input type=\"text\" name=\"$username_form_name\" value=\"\" tabindex=\"1\" class=\"input\">"; ?>
  <font size="2" color="#FFFFFF">Password:</font><input name="<?php echo $password_form_name; ?>" type="password" class="input" tabindex=\"2\">
  <input type=hidden name="js_autodetect_results" value="SMPREF_JS_OFF">
  <input type=hidden name="just_logged_in" value=1>
  <p class="3">
    <input name="button" type="submit" value="login" class="button">
  </p>
  <p class="3">
  <?php do_hook('login_form'); ?>
  </p>
  </fieldset>
</div>
</form>

<?php
 do_hook('login_bottom');
?>
</body></html>