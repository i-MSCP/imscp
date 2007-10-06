<?php

/**
 * signout.php -- cleans up session and logs the user out
 *
 *  Cleans up after the user. Resets cookies and terminates session.
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: signout.php 12537 2007-07-14 18:34:04Z kink $
 * @package squirrelmail
 */

/** This is the signout page */
define('PAGE_NAME', 'signout');

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/prefs.php');
require_once(SM_PATH . 'functions/plugin.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');

/* Erase any lingering attachments */
sqgetGlobalVar('compose_messages',  $compose_messages,  SQ_SESSION);
if (!empty($compose_messages) && is_array($compose_messages)) {
    foreach($compose_messages as $composeMessage) {
        $composeMessage->purgeAttachments();
    }
}

if (!isset($frame_top)) {
    $frame_top = '_top';
}

/* If a user hits reload on the last page, $base_uri isn't set
 * because it was deleted with the session. */
if (! sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
    require_once(SM_PATH . 'functions/display_messages.php');
    $base_uri = sqm_baseuri();
}

do_hook('logout');

sqsession_destroy();

if ($signout_page) {
    // Status 303 header is disabled. PHP fastcgi bug. See 1.91 changelog.
    //header('Status: 303 See Other');
    header("Location: $signout_page");
    exit; /* we send no content if we're redirecting. */
}

/* internal gettext functions will fail, if language is not set */
set_up_language($squirrelmail_language, true, true);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
   <meta name="robots" content="noindex,nofollow">
<?php
    if ($theme_css != '') {
?>
<style type="text/css">
<!--
body {
	text-align: center;
	background-repeat: no-repeat;
	margin: 0;
	padding: 0;
	background-color: #ffffff;
	font-family: Geneva, Arial, Helvetica, sans-serif;
}

#container {
	background-image: url(../images/bg.jpg);
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
	background-image: url(<?php echo "$org_logo" ?>);
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
//-->
</style>
<?php
    }
$plugin_message = concat_hook_function('logout_above_text');
?>
   <title><?php echo $org_title . ' - ' . _("Signout"); ?></title>
</head>
<body>

<div id="container">
  <h1>WebMail Logout</h1>

  <h2><?php echo _("You have been successfully signed out."); ?></h2>
  <h3><a href="../src/login.php" target="<?php echo $frame_top; ?>"><?php echo _("Click here to log back in."); ?></a></h3>
</div>

</body>
</html>
