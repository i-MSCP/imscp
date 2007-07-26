<?php
/**
 *  ispCP ω (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 *
 **/

define('INCLUDEPATH', realpath(dirname(__FILE__)));
require_once(INCLUDEPATH.'/ispcp-config.php');

session_name('ispCP');

if (!isset($_SESSION))
	session_start();

// Error handling and debug
//error_reporting(0);
// setting for development edition - see all error messages
error_reporting(E_ALL);

// variable for developmetn edition => shows all php variables under the pages
// false = disable, true = enable
$cfg['DUMP_GUI_DEBUG'] = false;

// session timeout in minutes
$cfg['SESSION_TIMEOUT'] = 30;
// Item states
$cfg['ITEM_ADD_STATUS'] = 'toadd';
$cfg['ITEM_OK_STATUS'] = 'ok';
$cfg['ITEM_CHANGE_STATUS'] = 'change';
$cfg['ITEM_DELETE_STATUS'] = 'delete';
$cfg['ITEM_DISABLED_STATUS'] = 'disabled';
$cfg['ITEM_RESTORE_STATUS'] = 'restore';
$cfg['ITEM_TOENABLE_STATUS'] = 'toenable';
$cfg['ITEM_TODISABLED_STATUS'] = 'todisable';
// SQL variables
$cfg['MAX_SQL_DATABASE_LENGTH'] = 64;
$cfg['MAX_SQL_USER_LENGTH'] = 16;
$cfg['MAX_SQL_PASS_LENGTH'] = 16;
// Template pathes
$cfg['ROOT_TEMPLATE_PATH'] = 'themes/';
$cfg['LOGIN_TEMPLATE_PATH'] = $cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'];
$cfg['ADMIN_TEMPLATE_PATH'] = '../'.$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/admin';
$cfg['RESELLER_TEMPLATE_PATH'] = '../'.$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/reseller';
$cfg['CLIENT_TEMPLATE_PATH'] = '../'.$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/client';
$cfg['IPS_LOGO_PATH'] = '../themes/user_logos';
$cfg['PURCHASE_TEMPLATE_PATH'] = '../'.$cfg['ROOT_TEMPLATE_PATH'].$cfg['USER_INITIAL_THEME'].'/orderpanel';

// the following variables are overriden via admin cp
$cfg['DOMAIN_ROWS_PER_PAGE'] = 10;
// 'admin' => hosting plans are available only in admin level, reseller can not make custom changes
// 'reseller' => hosting plans are available only in reseller level
$cfg['HOSTING_PLANS_LEVEL'] = 'reseller';
$cfg['USER_INITIAL_LANG'] = 'lang_English';

// enable or disable supportsystem
// false = disable, true = enable
$cfg['ISPCP_SUPPORT_SYSTEM'] = true;

// enable or disable lostpassword function
// false = disable, true = enable
$cfg['LOSTPASSWORD'] = true;

// uniqkeytimeout in minuntes
$cfg['LOSTPASSWORD_TIMEOUT'] = 30;
// captcha imagehigh
$cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'] = 65;
// captcha imagewidth
$cfg['LOSTPASSWORD_CAPTCHA_WIDTH'] = 210;
// captcha background color
$cfg['LOSTPASSWORD_CAPTCHA_BGCOLOR'] = array(229,243,252);
// captcha text color
$cfg['LOSTPASSWORD_CAPTCHA_TEXTCOLOR'] = array(0,53,92);
// captcha ttf fontfile
$cfg['LOSTPASSWORD_CAPTCHA_FONT'] = $cfg['LOGIN_TEMPLATE_PATH'].'/font/cap.ttf';

// enable or disable bruteforcedetection
// false = disable, true = enable
$cfg['BRUTEFORCE'] = true;
// blocktime in minutes
$cfg['BRUTEFORCE_BLOCK_TIME'] = 30;
// max login before block
$cfg['BRUTEFORCE_MAX_LOGIN'] = 3;
// max captcha failed attempts before block
$cfg['BRUTEFORCE_MAX_CAPTCHA'] = 5;
// enable or disable time between logins
// true = disable, false = enable
$cfg['BRUTEFORCE_BETWEEN'] = true;
// time between logins in seconds
$cfg['BRUTEFORCE_BETWEEN_TIME'] = 30;

// enable or disable servicemode
// true = disable, false = enable
$cfg['SERVICEMODE'] = false;
// servicemode message
$cfg['SERVICEMODE_MESSAGE'] = 'The system currently in servicemode!';

// password chars
$cfg['PASSWD_CHARS'] = 6;
// enable or disable strong passwords
// false = disable, true = enable
$cfg['PASSWD_STRONG'] = true;

/* security level
 * none     - Default behavior in earlier X-Panel versions
 * easy     - some simple checks, e.g. PHP_SELF secured, regex-check on IP no, hostname
 * medium   - checks on request method and server protocol (HTTP/x.y)
 * high     - checks through $_GET, $_COOKIE for dangerous data and strips it, this may drive some programs in trouble
 * paranoid - additional checks against the /etc/apache2/sites-available/ispcp.conf if SERVER_NAME is in the file or not
 */
// Choose one here and comment out the other. Your choice, your destiny!
//$cfg['SECURITY_LEVEL'] = 'none';     // Never ever use this unless you have a good reason to do so...
//$cfg['SECURITY_LEVEL'] = 'easy';     // As easy as for the attackers... not recommended!
//$cfg['SECURITY_LEVEl'] = 'medium';   // for normal users (recommended)
//$cfg['SECURITY_LEVEL'] = 'high';     // For experienced users only
$cfg['SECURITY_LEVEl'] = 'paranoid';   // developers may choose this for testing (nope, we are not paranoid... ;) )

// The virtual host file from Apache which contains our virtual host entries
$cfg['SERVER_VHOST_FILE'] = '/etc/apache2/sites-available/ispcp.conf';

require_once(INCLUDEPATH.'/spGzip.php');
require_once(INCLUDEPATH.'/class.pTemplate.php');
require_once(INCLUDEPATH.'/date-functions.php');
require_once(INCLUDEPATH.'/system-message.php');
require_once(INCLUDEPATH.'/ispcp-db-keys.php');
require_once(INCLUDEPATH.'/input-checks.php');
require_once(INCLUDEPATH.'/debug.php');
require_once(INCLUDEPATH.'/i18n.php');
require_once(INCLUDEPATH.'/system-log.php');
require_once(INCLUDEPATH.'/calc-functions.php');
require_once(INCLUDEPATH.'/login-functions.php');
require_once(INCLUDEPATH.'/login.php');
require_once(INCLUDEPATH.'/client-functions.php');
require_once(INCLUDEPATH.'/admin-functions.php');
require_once(INCLUDEPATH.'/reseller-functions.php');
require_once(INCLUDEPATH.'/ispcp-functions.php');
require_once(INCLUDEPATH.'/idna.php');
require_once(INCLUDEPATH.'/lostpassword-functions.php');
require_once(INCLUDEPATH.'/sql.php');
require_once(INCLUDEPATH.'/emailtpl-functions.php');
require_once(INCLUDEPATH.'/layout-functions.php');

if ($_SERVER['SCRIPT_NAME'] != "/client/sql_execute_query.php") {
	check_query();
}

$query = <<<SQL
	SELECT
		name, value
	FROM
		config
SQL;

if(!$res = exec_query($sql, $query, array())) {
	system_message(tr('Could not get config from database'));
}
else {
	while($row = $res -> FetchRow()) {
		$cfg[$row['name']] = $row['value'];
	}
}

?>