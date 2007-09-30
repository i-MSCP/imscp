<?php

/**
 * mailto.php -- mailto: url handler
 *
 * This checks to see if we're logged in.  If we are we open up a new
 * compose window for this email, otherwise we go to login.php
 * (the above functionality has been disabled, by default you are required to
 *  login first)
 *
 * Use the following url to use mailto:
 * http://<your server>/<squirrelmail base dir>/src/mailto.php?emailaddress=%1
 * see ../contrib/squirrelmail.mailto.reg for a Windows Registry file
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: mailto.php 12537 2007-07-14 18:34:04Z kink $
 * @package squirrelmail
 */

/** This is the mailto page */
define('PAGE_NAME', 'mailto');

/**
 * Path for SquirrelMail required files.
 * @ignore
 */
define('SM_PATH','../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'config/config.php');

/* Force users to login each time? */
$force_login  = true;
/* Open only the compose window, meaningless if $force_login is true */
$compose_only = false;

header('Pragma: no-cache');

$trtable = array('cc'           => 'send_to_cc',
                 'bcc'          => 'send_to_bcc',
                 'body'         => 'body',
                 'subject'      => 'subject');
$url = '';

$data = array();

if(sqgetGlobalVar('emailaddress', $emailaddress)) {
    $emailaddress = trim($emailaddress);
    if(stristr($emailaddress, 'mailto:')) {
        $emailaddress = substr($emailaddress, 7);
    }
    if(strpos($emailaddress, '?') !== false) {
        list($emailaddress, $a) = explode('?', $emailaddress, 2);
        if(strlen(trim($a)) > 0) {
            $a = explode('=', $a, 2);
            $data[strtolower($a[0])] = $a[1];
        }
    }
    $data['to'] = $emailaddress;

    /* CC, BCC, etc could be any case, so we'll fix them here */
    foreach($_GET as $k=>$g) {
        $k = strtolower($k);
        if(isset($trtable[$k])) {
            $k = $trtable[$k];
            $data[$k] = $g;
        }
    }
}
sqsession_is_active();

if($force_login == false && sqsession_is_registered('user_is_logged_in')) {
    if($compose_only == true) {
        $redirect = 'compose.php?mailtodata=' . urlencode(serialize($data));
    } else {
        $redirect = 'webmail.php?mailtodata=' . urlencode(serialize($data));
    }
} else {
    $redirect = 'login.php?mailtodata=' . urlencode(serialize($data));
}

session_write_close();
header('Location: ' . get_location() . '/' . $redirect);
