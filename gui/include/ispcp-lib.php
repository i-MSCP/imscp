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

	if(file_exists(INCLUDEPATH . "/class.$className.php")) {

		require_once INCLUDEPATH . "/class.$className.php";

	} elseif(file_exists(INCLUDEPATH . "/IspCP/$className.php")) {

		require_once INCLUDEPATH . "/IspCP/$className.php";

	} else {
		$path = str_replace('_', '/', $className);

		if(file_exists(INCLUDEPATH . '/' . $path.'.php')) {
			require_once INCLUDEPATH . '/' . $path.'.php';
		}
	}
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

// Get configuration parameters from ispcp.conf file
$main_cfg =& Config::getInstance();

// Template pathes
$main_cfg['ROOT_TEMPLATE_PATH'] = 'themes/';
$main_cfg['USER_INITIAL_THEME'] = 'omega_original';

// Get template path
$tpl_path = $main_cfg['ROOT_TEMPLATE_PATH'] . $main_cfg['USER_INITIAL_THEME'] ;

// Set the login templates path
$main_cfg['LOGIN_TEMPLATE_PATH'] = $tpl_path;

// Set the users level templates path
$main_cfg['ADMIN_TEMPLATE_PATH'] =  '../' .  $tpl_path . '/admin';
$main_cfg['RESELLER_TEMPLATE_PATH'] = '../' . $tpl_path . '/reseller';
$main_cfg['CLIENT_TEMPLATE_PATH'] = '../' . $tpl_path . '/client';

// Set the isCP logo path
$main_cfg['IPS_LOGO_PATH'] = '../themes/user_logos';

// Set the order panel templates path
$main_cfg['PURCHASE_TEMPLATE_PATH'] = '../' . $tpl_path . '/orderpanel';

// set often used HTML template strings
// RegEx: \s*([a-zA-Z]+)\s*\=\s*([\\]{0,1}[\"\'])\1\2
$main_cfg['HTML_CHECKED'] = ' checked="checked"';
$main_cfg['HTML_DISABLED'] = ' disabled="disabled"';
$main_cfg['HTML_READONLY'] = ' readonly="readonly"';
$main_cfg['HTML_SELECTED'] = ' selected="selected"';

// Standard Language (if not set)
$main_cfg['USER_INITIAL_LANG'] = 'lang_EnglishBritain';

require_once(INCLUDEPATH . '/system-message.php');
require_once(INCLUDEPATH . '/ispcp-db-keys.php');
require_once(INCLUDEPATH . '/sql.php');

// variable for development edition: show all php variables beyond page content
$main_cfg['DUMP_GUI_DEBUG'] = DISABLE;

// show spGZIP compression information in HTML output
$main_cfg['SHOW_COMPRESSION_SIZE'] = ENABLE;

// Session timeout in minutes
$main_cfg['SESSION_TIMEOUT'] = 30;

// Item states
$main_cfg['ITEM_ADD_STATUS'] = 'toadd';
$main_cfg['ITEM_OK_STATUS'] = 'ok';
$main_cfg['ITEM_CHANGE_STATUS'] = 'change';
$main_cfg['ITEM_DELETE_STATUS'] = 'delete';
$main_cfg['ITEM_DISABLED_STATUS'] = 'disabled';
$main_cfg['ITEM_RESTORE_STATUS'] = 'restore';
$main_cfg['ITEM_TOENABLE_STATUS'] = 'toenable';
$main_cfg['ITEM_TODISABLED_STATUS'] = 'todisable';
$main_cfg['ITEM_ORDERED_STATUS'] = 'ordered';
$main_cfg['ITEM_DNSCHANGE_STATUS'] = 'dnschange';

// SQL variables
$main_cfg['MAX_SQL_DATABASE_LENGTH'] = 64;
$main_cfg['MAX_SQL_USER_LENGTH'] = 16;
$main_cfg['MAX_SQL_PASS_LENGTH'] = 32;

/**
 * The following parameters are overwritten via admin cp
 */

// Domain rows pagination
$main_cfg['DOMAIN_ROWS_PER_PAGE'] = 10;

// 'admin': hosting plans are available only in admin level, the reseller
// cannot make custom changes
// 'reseller': hosting plans are available only in reseller level
$main_cfg['HOSTING_PLANS_LEVEL'] = 'reseller';

// TLD strict validation (according IANA database)
$main_cfg['TLD_STRICT_VALIDATION'] = ENABLE;

// SLD strict validation
$main_cfg['SLD_STRICT_VALIDATION'] = ENABLE;

// Maximum number of labels for the domain names
// and subdomains (excluding SLD and TLD)
$main_cfg['MAX_DNAMES_LABELS'] = 1;

// Maximum number of labels for the subdomain names
$main_cfg['MAX_SUBDNAMES_LABELS'] = 1;

// Enable or disable support system
$main_cfg['ISPCP_SUPPORT_SYSTEM'] = ENABLE;

// Enable or disable lost password support
$main_cfg['LOSTPASSWORD'] = ENABLE;

// Uniqkeytimeout in minutes
$main_cfg['LOSTPASSWORD_TIMEOUT'] = 30;

// Captcha imagewidth
$main_cfg['LOSTPASSWORD_CAPTCHA_WIDTH'] = 280;

// Captcha imagehigh
$main_cfg['LOSTPASSWORD_CAPTCHA_HEIGHT'] = 70;

// Captcha background color
$main_cfg['LOSTPASSWORD_CAPTCHA_BGCOLOR'] =  array(229, 243, 252);

// Captcha text color
$main_cfg['LOSTPASSWORD_CAPTCHA_TEXTCOLOR'] =  array(0, 53, 92);

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
$main_cfg['LOSTPASSWORD_CAPTCHA_FONT'] = INCLUDEPATH . '/fonts/' .
	$fonts[mt_rand(0, count($fonts)-1)];

// Enable or disable bruteforcedetection
$main_cfg['BRUTEFORCE'] = ENABLE;

// Blocktime in minutes
$main_cfg['BRUTEFORCE_BLOCK_TIME'] = 30;

// Max login before block
$main_cfg['BRUTEFORCE_MAX_LOGIN'] = 3;

// Max captcha failed attempts before block
$main_cfg['BRUTEFORCE_MAX_CAPTCHA'] = 5;

// Enable or disable time between logins
$main_cfg['BRUTEFORCE_BETWEEN'] = ENABLE;

// Time between logins in seconds
$main_cfg['BRUTEFORCE_BETWEEN_TIME'] = 30;

// Enable or disable maintenance mode
$main_cfg['MAINTENANCEMODE'] = DISABLE;

// Servicemode message
$main_cfg['MAINTENANCEMODE_MESSAGE'] =
	tr("We are sorry, but the system is currently under maintenance.\nPlease try again later.");

// Restore language auto detection
curlang(null, true);

// Minimum password chars
$main_cfg['PASSWD_CHARS'] = 6;

// Enable or disable strong passwords
$main_cfg['PASSWD_STRONG'] = ENABLE;

// The virtual host file from Apache which contains our virtual host entries
$main_cfg['SERVER_VHOST_FILE'] = $main_cfg['APACHE_SITES_DIR'] . '/ispcp.conf';

// The minimum level for a message to be sent to DEFAULT_ADMIN_ADDRESS
// PHP's E_USER_* values are used for simplicity:
// E_USER_NOTICE: logins, and all info that isn't very relevant
// E_USER_WARNING: switching to an other account, etc
// E_USER_ERROR: "admin MUST know" messages
$main_cfg['LOG_LEVEL'] = E_USER_NOTICE;

// Creation of webmaster, postmaster and abuse forwarders when domain/alias/
// subdomain is created
$main_cfg['CREATE_DEFAULT_EMAIL_ADDRESSES'] = ENABLE;

// Count default e-mail addresses (abuse,postmaster,webmaster) in user limit
// ENABLE: default e-mail are counted
// DISABLE: default e-mail are NOT counted
$main_cfg['COUNT_DEFAULT_EMAIL_ADDRESSES'] = ENABLE;

// Use hard mail suspension when suspending a domain:
// ENABLE: email accounts are hard suspended (completely unreachable)
// DISABLE: email accounts are soft suspended (passwords are modified so user
// can't access the accounts)
$main_cfg['HARD_MAIL_SUSPENSION'] = ENABLE;

// Prevent external login (i.e. check for valid local referer)
// separated in admin, reseller and client
// This option allows to use external login scripts
// ENABLE: prevent external login, check for referer, more secure
// DISABLE: allow external login, do not check for referere, less security (risky)
$main_cfg['PREVENT_EXTERNAL_LOGIN_ADMIN'] = ENABLE;
$main_cfg['PREVENT_EXTERNAL_LOGIN_RESELLER'] = ENABLE;
$main_cfg['PREVENT_EXTERNAL_LOGIN_CLIENT'] = ENABLE;

// Automatic search for new version
$main_cfg['CHECK_FOR_UPDATES'] = ENABLE;

if(!$main_cfg['ISPCP_SUPPORT_SYSTEM_TARGET']) {
	$main_cfg['ISPCP_SUPPORT_SYSTEM_TARGET'] = '_self';
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

// Override all the default parameters with the user defined parameters that are
// stored in the database
$main_cfg->replace_with(
	Config::getInstance(Config::DB, Database::getRawInstance())
);

// Compress/gzip Class
require_once(INCLUDEPATH . '/class.spGzip.php');

// Check if server information is enabled
// Note: If we receive a xhr request, the value must be forced to FALSE
$showCompression = $main_cfg['SHOW_COMPRESSION_SIZE'] ? !is_xhr() : false;

// construct the object
$GLOBALS['class']['output'] = new spOutput('auto', false, $showCompression);

// Start the output buffering
ob_start(array($GLOBALS['class']['output'], 'output'));
