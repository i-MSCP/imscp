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
define('ENABLE', true);
define('DISABLE', false);
// Define Error Level off
define('E_USER_OFF', 0);

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
// Not only in Development E_ALL & E_STRICT should not throw any errors
error_reporting(E_ALL|E_STRICT);

require_once(INCLUDEPATH . '/i18n.php');

// Template pathes
Config::getInstance()->set('ROOT_TEMPLATE_PATH', 'themes/');
Config::getInstance()->set('USER_INITIAL_THEME', 'omega_original');

// Get the root directory templates path
$root_tpl_path = Config::getInstance()->get('ROOT_TEMPLATE_PATH');

// Get user initial theme
$user_initial_theme = Config::getInstance()->get('USER_INITIAL_THEME');

// Set the login templates path
Config::getInstance()->set(
	'LOGIN_TEMPLATE_PATH',
	$root_tpl_path . $user_initial_theme
);

// Set the GUI admin level templates path
Config::getInstance()->set(
	'ADMIN_TEMPLATE_PATH',
	'../' . $root_tpl_path . $user_initial_theme . '/admin'
);

// Set the GUI reseller level templates path
Config::getInstance()->set(
	'RESELLER_TEMPLATE_PATH',
	'../' . $root_tpl_path . $user_initial_theme . '/reseller'
);

// Set the GUI client level templates path
Config::getInstance()->set(
	'CLIENT_TEMPLATE_PATH',
	'../' . $root_tpl_path . $user_initial_theme . '/client'
);

// Set the isCP logo path
Config::getInstance()->set('IPS_LOGO_PATH', '../themes/user_logos');

// Set the order panel templates path
Config::getInstance()->set(
	'PURCHASE_TEMPLATE_PATH',
	'../' . $root_tpl_path . $user_initial_theme . '/orderpanel'
);

// Standard Language (if not set)
Config::getInstance()->set('USER_INITIAL_LANG', 'lang_EnglishBritain');

require_once(INCLUDEPATH . '/system-message.php');
require_once(INCLUDEPATH . '/ispcp-db-keys.php');
require_once(INCLUDEPATH . '/sql.php');

// variable for development edition => shows all php variables t page's end
Config::getInstance()->set('DUMP_GUI_DEBUG', DISABLE);

// show server load information in spGZIP HTML output
Config::getInstance()->set('SHOW_SERVERLOAD', DISABLE);

// Session timeout in minutes
Config::getInstance()->set('SESSION_TIMEOUT', 30);

// Item states
Config::getInstance()->set('ITEM_ADD_STATUS', 'toadd');
Config::getInstance()->set('ITEM_OK_STATUS', 'ok');
Config::getInstance()->set('ITEM_CHANGE_STATUS', 'change');
Config::getInstance()->set('ITEM_DELETE_STATUS', 'delete');
Config::getInstance()->set('ITEM_DISABLED_STATUS', 'disabled');
Config::getInstance()->set('ITEM_RESTORE_STATUS', 'restore');
Config::getInstance()->set('ITEM_TOENABLE_STATUS', 'toenable');
Config::getInstance()->set('ITEM_TODISABLED_STATUS', 'todisable');
Config::getInstance()->set('ITEM_ORDERED_STATUS', 'ordered');
Config::getInstance()->set('ITEM_DNSCHANGE_STATUS', 'dnschange');

// SQL variables
Config::getInstance()->set('MAX_SQL_DATABASE_LENGTH', 64);
Config::getInstance()->set('MAX_SQL_USER_LENGTH', 16);
Config::getInstance()->set('MAX_SQL_PASS_LENGTH', 32);

/**
 * The following parameters are overwritten via admin cp
 */

// Domain rows pagination
Config::getInstance()->set('DOMAIN_ROWS_PER_PAGE', 10);

// 'admin': hosting plans are available only in admin level, the reseller
// cannot make custom changes
// 'reseller': hosting plans are available only in reseller level
Config::getInstance()->set('HOSTING_PLANS_LEVEL', 'reseller');


// TLD strict validation (according IANA database)
Config::getInstance()->set('TLD_STRICT_VALIDATION', ENABLE);

// SLD strict validation
Config::getInstance()->set('SLD_STRICT_VALIDATION', ENABLE);

// Maximum number of labels for the domain names
// and subdomains (excluding SLD and TLD)
Config::getInstance()->set('MAX_DNAMES_LABELS', 1);

// Maximum number of labels for the subdomain names
Config::getInstance()->set('MAX_SUBDNAMES_LABELS', 1);


// Enable or disable support system
Config::getInstance()->set('ISPCP_SUPPORT_SYSTEM', ENABLE);

// Enable or disable lost password support
Config::getInstance()->set('LOSTPASSWORD', ENABLE);

// Uniqkeytimeout in minutes
Config::getInstance()->set('LOSTPASSWORD_TIMEOUT', 30);

// Captcha imagewidth
Config::getInstance()->set('LOSTPASSWORD_CAPTCHA_WIDTH', 280);

// Captcha imagehigh
Config::getInstance()->set('LOSTPASSWORD_CAPTCHA_HEIGHT', 70);

// Captcha background color
Config::getInstance()->set('LOSTPASSWORD_CAPTCHA_BGCOLOR', array(229,243,252));

// Captcha text color
Config::getInstance()->set('LOSTPASSWORD_CAPTCHA_TEXTCOLOR', array(0,53,92));

/**
 * Captcha ttf fontfiles (have to be under compatible open source license)
 */
$fonts = array(
	'Essays1743.ttf',
	'Essays1743-Bold.ttf',
	'Essays1743-BoldItalic.ttf',
	'Essays1743-Italic.ttf',
	'StayPuft.ttf'
);

// Set random catcha font file
Config::getInstance()->set(
	'LOSTPASSWORD_CAPTCHA_FONT',
	INCLUDEPATH.'/fonts/' . $fonts[mt_rand(0, count($fonts)-1)]
);

// Enable or disable bruteforcedetection
Config::getInstance()->set('BRUTEFORCE', ENABLE);

// Blocktime in minutes
Config::getInstance()->set('BRUTEFORCE_BLOCK_TIME', 30);

// Max login before block
Config::getInstance()->set('BRUTEFORCE_MAX_LOGIN', 3);

// Max captcha failed attempts before block
Config::getInstance()->set('BRUTEFORCE_MAX_CAPTCHA', 5);

// Enable or disable time between logins
Config::getInstance()->set('BRUTEFORCE_BETWEEN', ENABLE);

// Time between logins in seconds
Config::getInstance()->set('BRUTEFORCE_BETWEEN_TIME', 30);

// Enable or disable maintenance mode
Config::getInstance()->set('MAINTENANCEMODE', DISABLE);

// Servicemode message
Config::getInstance()->set(
	'MAINTENANCEMODE_MESSAGE',
	tr("We are sorry, but the system is currently under maintenance.\nPlease try again later.")
);

// Restore language auto detection
curlang(null, true);

// Minimum password chars
Config::getInstance()->set('PASSWD_CHARS', 6);

// Enable or disable strong passwords
Config::getInstance()->set('PASSWD_STRONG', ENABLE);

// The virtual host file from Apache which contains our virtual host entries
Config::getInstance()->set(
	'SERVER_VHOST_FILE',
	Config::getInstance()->get('APACHE_SITES_DIR') . '/ispcp.conf'
);

// The minimum level for a message to be sent to DEFAULT_ADMIN_ADDRESS
// PHP's E_USER_* values are used for simplicity:
// E_USER_NOTICE: logins, and all info that isn't very relevant
// E_USER_WARNING: switching to an other account, etc
// E_USER_ERROR: "admin MUST know" messages
Config::getInstance()->set('LOG_LEVEL', E_USER_NOTICE);

// Creation of webmaster, postmaster and abuse forwarders when domain/alias/
// subdomain is created
Config::getInstance()->set('CREATE_DEFAULT_EMAIL_ADDRESSES', ENABLE);

// Count default e-mail addresses (abuse,postmaster,webmaster) in user limit
// ENABLE: default e-mail are counted
// DISABLE: default e-mail are NOT counted
Config::getInstance()->set('COUNT_DEFAULT_EMAIL_ADDRESSES', ENABLE);

// Use hard mail suspension when suspending a domain:
// ENABLE: email accounts are hard suspended (completely unreachable)
// DISABLE: email accounts are soft suspended (passwords are modified so user
// can't access the accounts)
Config::getInstance()->set('HARD_MAIL_SUSPENSION', ENABLE);

// Prevent external login (i.e. check for valid local referer)
// separated in admin, reseller and client
// This option allows to use external login scripts
// ENABLE: prevent external login, check for referer, more secure
// DISABLE: allow external login, do not check for referere, less security (risky)
Config::getInstance()->set('PREVENT_EXTERNAL_LOGIN_ADMIN', ENABLE);
Config::getInstance()->set('PREVENT_EXTERNAL_LOGIN_RESELLER', ENABLE);
Config::getInstance()->set('PREVENT_EXTERNAL_LOGIN_CLIENT', ENABLE);

// Automatic search for new version
Config::getInstance()->set('CHECK_FOR_UPDATES', ENABLE);

Config::getInstance()->set('CRITICAL_UPDATE_REVISION', 0);

if (!Config::getInstance()->get('ISPCP_SUPPORT_SYSTEM_TARGET')) {
	Config::getInstance()->set('ISPCP_SUPPORT_SYSTEM_TARGET', '_self');
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

// Use HTMLPurifier on every request, if OVERRIDE_PURIFIER is not defined
if ($_REQUEST && !defined('OVERRIDE_PURIFIER')) {
	$config = HTMLPurifier_Config::createDefault();

	// XSS cleaning
	$config->set('HTML.TidyLevel', 'none');

	$purifier = new HTMLPurifier($config);

	$_GET = $purifier->purifyArray($_GET);
	$_POST = $purifier->purifyArray($_POST);
}

$query = "SELECT `name`, `value` FROM `config`";

if (!$res = exec_query($sql, $query, array())) {
	system_message(tr('Could not get config from database'));
} else {
	while ($row = $res->FetchRow()) {
		Config::getInstance()->set($row['name'], $row['value']);
	}
}

// Compress/gzip Class
require_once(INCLUDEPATH . '/class.spGzip.php');
// Check if server load information is enabled
$showSize = (Config::getInstance()->get('SHOW_SERVERLOAD')) ? true : false;
// construct the object
$GLOBALS['class']['output'] = new spOutput('auto', false, $showSize);
// Start the output buffering
ob_start(array($GLOBALS['class']['output'], 'output'));
