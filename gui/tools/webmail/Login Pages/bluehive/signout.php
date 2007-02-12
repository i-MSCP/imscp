<?php

/**
 * signout.php -- cleans up session and logs the user out
 *
 * Copyright (c) 1999-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 *  Cleans up after the user. Resets cookies and terminates session.
 *
 * @version $Id: signout.php,v 1.66.2.7 2005/05/20 18:43:39 kink Exp $
 * @package squirrelmail
 */

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
if (isset($attachments) && is_array($attachments)
    && sizeof($attachments)){
    $hashed_attachment_dir = getHashedDir($username, $attachment_dir);
    foreach ($attachments as $info) {
        $attached_file = "$hashed_attachment_dir/$info[localfilename]";
        if (file_exists($attached_file)) {
            unlink($attached_file);
        }
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
    header('Status: 303 See Other');
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

	margin: 0;

	padding: 0;

	background-color: #72AAF6;

	font-family: Verdana, Lucida, Helvetica;

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

	background-image: url(../images/<? echo "$org_logo" ?>);

	position: absolute;

	top: 120px;

	left: 40px;

}

h1 {

	text-align: right;

	font-size: 1em;

	color: #FFFFFF;

	width: 375px;

	margin: 65px 20px 0px 0px;
	
	position: absolute;

	top: 0px;

	left: 20px;

}

h2 {

	color: #FFFFFF;
		
	text-align: right;

	font-size: .8em;

	margin: 10px 10px 0px 0px;
	
	position: absolute;

	top: 110px;

	left: 210px;

	width: 200px;

	height: 28px;
}

h3, p {

	font-size: .7em;

	font-weight: bold;

	text-transform: uppercase;

	color: #999999;

	margin: 30px 30px;

	position: absolute;

	top: 150px;

	left: 220px;

}

p {

	margin: 1em 0 0 50%;

}

a {

	text-transform: lowercase;

	text-decoration: none;

	color: #0066CC;

}

a:hover {

	padding-bottom: 2px;

	border-bottom: 1px dashed #B5B9BD;

}

-->
</style>
<?php
    }
$plugin_message = concat_hook_function('logout_above_text');
?>
</head>
<body>

<div id="container"> 
  <h1>WebMail Logout</h1>

  <h2><?php echo _("You have been successfully signed out."); ?></h2>
  <h3><a href="../src/login.php" target="<?php echo $frame_top; ?>"><?php echo _("Click here to log back in."); ?></a></h3>
</div>

</body>
</html>