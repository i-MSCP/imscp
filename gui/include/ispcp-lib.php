<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

define('INCLUDEPATH', realpath(dirname(__FILE__)));

function autoload_class($className) {
	require_once(INCLUDEPATH . "/class.$className.php");
}
spl_autoload_register('autoload_class');

require_once(INCLUDEPATH . '/ispcp-config.php');

session_name('ispCP');

if (!isset($_SESSION)) {
	session_start();
}

// Error handling and debug
//error_reporting(0);
// setting for development edition - see all error messages
error_reporting(E_ALL);

require_once(INCLUDEPATH . '/i18n.php');

// Template pathes
Config::set('ROOT_TEMPLATE_PATH', 'themes/');
Config::set('USER_INITIAL_THEME', 'omega_original');
Config::set('LOGIN_TEMPLATE_PATH', Config::get('ROOT_TEMPLATE_PATH') . Config::get('USER_INITIAL_THEME'));
Config::set('ADMIN_TEMPLATE_PATH', '../' . Config::get('ROOT_TEMPLATE_PATH') . Config::get('USER_INITIAL_THEME') . '/admin');
Config::set('RESELLER_TEMPLATE_PATH', '../' . Config::get('ROOT_TEMPLATE_PATH') . Config::get('USER_INITIAL_THEME') . '/reseller');
Config::set('CLIENT_TEMPLATE_PATH', '../' . Config::get('ROOT_TEMPLATE_PATH') . Config::get('USER_INITIAL_THEME') . '/client');
Config::set('IPS_LOGO_PATH', '../themes/user_logos');
Config::set('PURCHASE_TEMPLATE_PATH', '../' . Config::get('ROOT_TEMPLATE_PATH') . Config::get('USER_INITIAL_THEME') . '/orderpanel');

// Standard Language (if not set)
Config::set('USER_INITIAL_LANG', 'lang_EnglishBritain');

require_once(INCLUDEPATH . '/system-message.php');
require_once(INCLUDEPATH . '/ispcp-db-keys.php');
require_once(INCLUDEPATH . '/sql.php');
define('E_USER_OFF', 0);

// variable for development edition => shows all php variables under the pages
// false = disable, true = enable
Config::set('DUMP_GUI_DEBUG', false);

// show extra (server load) information in HTML as comment
// will get overwritten by db config table entry
// (true = show, false = hide)
Config::set('SHOW_SERVERLOAD', true);


// session timeout in minutes
Config::set('SESSION_TIMEOUT', 30);
// Item states
Config::set('ITEM_ADD_STATUS', 'toadd');
Config::set('ITEM_OK_STATUS', 'ok');
Config::set('ITEM_CHANGE_STATUS', 'change');
Config::set('ITEM_DELETE_STATUS', 'delete');
Config::set('ITEM_DISABLED_STATUS', 'disabled');
Config::set('ITEM_RESTORE_STATUS', 'restore');
Config::set('ITEM_TOENABLE_STATUS', 'toenable');
Config::set('ITEM_TODISABLED_STATUS', 'todisable');
Config::set('ITEM_ORDERED_STATUS', 'ordered');
Config::set('ITEM_DNSCHANGE_STATUS', 'dnschange');
// SQL variables
Config::set('MAX_SQL_DATABASE_LENGTH', 64);
Config::set('MAX_SQL_USER_LENGTH', 16);
Config::set('MAX_SQL_PASS_LENGTH', 32);

// the following variables are overwritten via admin cp
Config::set('DOMAIN_ROWS_PER_PAGE', 10);
// 'admin' => hosting plans are available only in admin level, reseller cannot make custom changes
// 'reseller' => hosting plans are available only in reseller level
Config::set('HOSTING_PLANS_LEVEL', 'reseller');

/**
 * Domain names validation defaults settings - Begin
 */

// TlD strict validation (according Iana database)
Config::set('TLD_STRICT_VALIDATION', true);

// SLD strict validation
Config::set('SLD_STRICT_VALIDATION', true);

// Maximum number of labels for the domain names
// and subdomains (excluding SLD and TLD)
Config::set('MAX_DNAMES_LABELS', 1);

// Maximum number of labels for the subdomain names
Config::set('MAX_SUBDNAMES_LABELS', 1);
/**
 * Domain names validation default settings - End
 */

// enable or disable supportsystem
// false = disable, true = enable
Config::set('ISPCP_SUPPORT_SYSTEM', true);

// enable or disable lostpassword function
// false = disable, true = enable
Config::set('LOSTPASSWORD', true);

// uniqkeytimeout in minutes
Config::set('LOSTPASSWORD_TIMEOUT', 30);
// captcha imagewidth
Config::set('LOSTPASSWORD_CAPTCHA_WIDTH', 280);
// captcha imagehigh
Config::set('LOSTPASSWORD_CAPTCHA_HEIGHT', 70);
// captcha background color
Config::set('LOSTPASSWORD_CAPTCHA_BGCOLOR', array(229,243,252));
// captcha text color
Config::set('LOSTPASSWORD_CAPTCHA_TEXTCOLOR', array(0,53,92));

/**
 * captcha ttf fontfiles (have to be under compatible open source license)
 */
$fonts = array(
	'Essays1743.ttf',
	'Essays1743-Bold.ttf',
	'Essays1743-BoldItalic.ttf',
	'Essays1743-Italic.ttf',
	'StayPuft.ttf'
);
// set random catcha font file
Config::set('LOSTPASSWORD_CAPTCHA_FONT', INCLUDEPATH.'/fonts/' . $fonts[mt_rand(0, count($fonts)-1)]);

// enable or disable bruteforcedetection
// false = disable, true = enable
Config::set('BRUTEFORCE', true);
// blocktime in minutes
Config::set('BRUTEFORCE_BLOCK_TIME', 30);
// max login before block
Config::set('BRUTEFORCE_MAX_LOGIN', 3);
// max captcha failed attempts before block
Config::set('BRUTEFORCE_MAX_CAPTCHA', 5);
// enable or disable time between logins
// true = disable, false = enable
Config::set('BRUTEFORCE_BETWEEN', true);
// time between logins in seconds
Config::set('BRUTEFORCE_BETWEEN_TIME', 30);

// enable or disable maintenance mode
// true = disable, false = enable
Config::set('MAINTENANCEMODE', false);
// servicemode message
Config::set('MAINTENANCEMODE_MESSAGE', tr("We are sorry, but the system is currently under maintenance.\nPlease try again later."));
curlang(null, true); // restore language auto detection

// minimum password chars
Config::set('PASSWD_CHARS', 6);
// enable or disable strong passwords
// false = disable, true = enable
Config::set('PASSWD_STRONG', true);

// The virtual host file from Apache which contains our virtual host entries
Config::set('SERVER_VHOST_FILE', Config::get('APACHE_SITES_DIR') . '/ispcp.conf');

// The minimum level for a message to be sent to DEFAULT_ADMIN_ADDRESS
// PHP's E_USER_* values are used for simplicity:
// E_USER_NOTICE: logins, and all info that isn't very relevant
// E_USER_WARNING: switching to an other account, etc
// E_USER_ERROR: "admin MUST know" messages
Config::set('LOG_LEVEL', E_USER_NOTICE);

// Set to false to disable creation of webmaster, postmaster and abuse forwarders when domain/alias/subdomain is created
Config::set('CREATE_DEFAULT_EMAIL_ADDRESSES', true);

//Count default e-mail addresses (abuse,postmaster,webmaster) in user limit
//true: default e-mail are counted
//false: default e-mail are NOT counted
Config::set('COUNT_DEFAULT_EMAIL_ADDRESSES', false);

// Use hard mail suspension when suspending a domain:
// true: email accounts are hard suspended (completely unreachable)
// false: email accounts are soft suspended (passwords are modified so user can't access the accounts)
Config::set('HARD_MAIL_SUSPENSION', true);

// prevent external login (ie. check for valid local referer)
// separated in admin, reseller and client
// true = prevent external login, check for referer, more secure
// false = allow external login, do not check for referere, less security (risky)
Config::set('PREVENT_EXTERNAL_LOGIN_ADMIN', true);
Config::set('PREVENT_EXTERNAL_LOGIN_RESELLER', true);
Config::set('PREVENT_EXTERNAL_LOGIN_CLIENT', true);

// false: disable automatic search for new version
Config::set('CHECK_FOR_UPDATES', true);

Config::set('CRITICAL_UPDATE_REVISION', 0);

if (!Config::get('ISPCP_SUPPORT_SYSTEM_TARGET')) {
	Config::set('ISPCP_SUPPORT_SYSTEM_TARGET', '_self');
}

require_once(INCLUDEPATH . '/date-functions.php');
require_once(INCLUDEPATH . '/input-checks.php');
require_once(INCLUDEPATH . '/debug.php');
require_once(INCLUDEPATH . '/calc-functions.php');
require_once(INCLUDEPATH . '/login-functions.php');
require_once(INCLUDEPATH . '/login.php');
require_once(INCLUDEPATH . '/client-functions.php');
require_once(INCLUDEPATH . '/admin-functions.php');
require_once(INCLUDEPATH . '/reseller-functions.php');
require_once(INCLUDEPATH . '/ispcp-functions.php');
require_once(INCLUDEPATH . '/net_idna/idna_convert.class.php');
require_once(INCLUDEPATH . '/lostpassword-functions.php');
require_once(INCLUDEPATH . '/emailtpl-functions.php');
require_once(INCLUDEPATH . '/layout-functions.php');
require_once(INCLUDEPATH . '/functions.ticket_system.php');
require_once(INCLUDEPATH . '/htmlpurifier/HTMLPurifier.auto.php');
//require_once(INCLUDEPATH . '/htmlpurifier/HTMLPurifier.func.php');

// Use HTMLPurifier on every request, if OVERRIDE_PURIFIER is not defined
if ($_REQUEST && !defined('OVERRIDE_PURIFIER')) {
	$config = HTMLPurifier_Config::createDefault();
	$config->set('HTML.TidyLevel', 'none'); // XSS cleaning

	$purifier = new HTMLPurifier($config);
	//$purifier = HTMLPurifier::getInstance();

	$_GET	 = $purifier->purifyArray($_GET);
	$_POST	 = $purifier->purifyArray($_POST);
	//$_COOKIE = $purifier->purifyArray($_COOKIE);
}

$query = "SELECT `name`, `value` FROM `config`";

if (!$res = exec_query($sql, $query, array())) {
	system_message(tr('Could not get config from database'));
} else {
	while ($row = $res->FetchRow()) {
		Config::set($row['name'], $row['value']);
	}
}

// compress/gzip output for less traffic
require_once(INCLUDEPATH . '/spGzip.php');
