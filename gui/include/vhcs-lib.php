<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2004 be moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------



session_name("VHCS");

if (!isset($_SESSION)) session_start();

//error_reporting(0);
error_reporting(E_ALL); // setting for development edition - see all error messages

$include_path = realpath(dirname(__FILE__));

include_once (realpath($include_path.'/vhcs-config.php'));

/* session timeout in minutes */
$cfg['SESSION_TIMEOUT'] = 30;

$cfg['ITEM_ADD_STATUS'] = 'toadd';

$cfg['ITEM_OK_STATUS'] = 'ok';

$cfg['ITEM_CHANGE_STATUS'] = 'change';

$cfg['ITEM_DELETE_STATUS'] = 'delete';

$cfg['ITEM_DISABLED_STATUS'] = 'disabled';

$cfg['ITEM_RESTORE_STATUS'] = 'restore';

$cfg['ITEM_TOENABLE_STATUS'] = 'toenable';

$cfg['ITEM_TODISABLED_STATUS'] = 'todisable';

$cfg['MAX_SQL_DATABASE_LENGTH'] = 64;

$cfg['MAX_SQL_USER_LENGTH'] = 16;

$cfg['MAX_SQL_PASS_LENGTH'] = 16;

$cfg['ROOT_TEMPLATE_PATH'] = 'themes/';

$cfg['LOGIN_TEMPLATE_PATH'] = $cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'];

$cfg['ADMIN_TEMPLATE_PATH'] = "../".$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/admin';

$cfg['RESELLER_TEMPLATE_PATH'] = "../".$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/reseller';

$cfg['CLIENT_TEMPLATE_PATH'] = "../".$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/client';

$cfg['IPS_LOGO_PATH'] = "../themes/user_logos";

$cfg['PURCHASE_TEMPLATE_PATH'] = "../".$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/orderpanel';

$cfg['DOMAIN_ROWS_PER_PAGE'] = 10;

$cfg['HOSTING_PLANS_LEVEL'] = 'reseller';
/*
'admin' => hosting plans are available only in admin level, reseller can not make custom changes
'reseller' => hosting plans are available only in reseller level
*/
// is read from vhcs2.conf
//$cfg['VHCS_LICENSE'] = 'VHCS<sup>&reg;</sup> Pro v2.4.7.2-alpha<br>build: 2006-01-03<br>Spartacus';

// variable for developmetn edition => shows all php variables under the pages
//$cfg['DUMP_GUI_DEBUG'] = '_on_';

$cfg['USER_INITIAL_LANG'] = 'lang_English';

/* enable or disable supportsystem */
/* 0 = disable */
/* 1 = enable */
$cfg['VHCS_SUPPORT_SYSTEM'] = 1;

/* enable or disable lostpassword function */
/* 0 = disable */
/* 1 = enable */
$cfg['LOSTPASSWORD'] = 1;

/* uniqkeytimeout in minuntes */
$cfg['LOSTPASSWORD_TIMEOUT'] = 30;

/* captcha imagehigh */
$cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'] = 65;

/* captcha imagewidth */
$cfg['LOSTPASSWORD_CAPTCHA_WIDTH'] = 210;

/* captcha background color */
$cfg['LOSTPASSWORD_CAPTCHA_BGCOLOR'] = array(229,243,252);

/* captcha text color */
$cfg['LOSTPASSWORD_CAPTCHA_TEXTCOLOR'] = array(0,53,92);

/* captcha ttf fontfile */
$cfg['LOSTPASSWORD_CAPTCHA_FONT'] = './cap.ttf';

/* enable or disable bruteforcedetection */
/* 0 = disable */
/* 1 = enable */
$cfg['BRUTEFORCE'] = 1;

/* blocktime in minutes */
$cfg['BRUTEFORCE_BLOCK_TIME'] = 30;

/* max login before block */
$cfg['BRUTEFORCE_MAX_LOGIN'] = 3;

/* enable or disable time between logins */
/* 0 = disable */
/* 1 = enable */
$cfg['BRUTEFORCE_BETWEEN'] = 1;

/* time between logins in seconds */
$cfg['BRUTEFORCE_BETWEEN_TIME'] = 30;

/* enable or disable servicemode */
/* 0 = disable */
/* 1 = enable */
$cfg['SERVICEMODE'] = 0;

/* servicemode message */
$cfg['SERVICEMODE_MESSAGE'] = 'The system currently in servicemode!';

include_once (realpath($include_path.'/spGzip.php'));

include_once (realpath($include_path.'/class.pTemplate.php'));

include_once (realpath($include_path.'/date-functions.php'));

include_once (realpath($include_path.'/system-message.php'));

include_once (realpath($include_path.'/vhcs2-db-keys.php'));

include_once (realpath($include_path.'/input-checks.php'));

include_once (realpath($include_path.'/debug.php'));

include_once (realpath($include_path.'/i18n.php'));

include_once (realpath($include_path.'/system-log.php'));

include_once (realpath($include_path.'/calc-functions.php'));

include_once (realpath($include_path.'/login-functions.php'));

include_once (realpath($include_path.'/login.php'));

include_once (realpath($include_path.'/client-functions.php'));

include_once (realpath($include_path.'/admin-functions.php'));

include_once (realpath($include_path.'/reseller-functions.php'));

include_once (realpath($include_path.'/vhcs-2-0.php'));

include_once (realpath($include_path.'/idna.php'));

include_once (realpath($include_path.'/lostpassword-functions.php'));

include_once (realpath($include_path.'/sql.php'));

// include_once (realpath($include_path.'/vhcs-security.php'));


$query = "SELECT name, value FROM config";

if( !$res = exec_query($sql, $query, array()) ) {

	system_message(tr('Could not get config from database'));

	die();

} else {

	while( $row = $res -> FetchRow() ) {

		$cfg[$row['name']] = $row['value'];

	}

}

include_once (realpath($include_path.'/layout-functions.php'));

?>
