<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     i-MSCP_Core
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

// Configuration parameters

/** @var $config iMSCP_Config_Handler_File */
$config = iMSCP_Config::getInstance();

// Template root directory
$config->set('ROOT_TEMPLATE_PATH', dirname(dirname(__FILE__)) . '/themes/' . $config->USER_INITIAL_THEME);

// Set the isp logos path
$config->set('ISP_LOGO_PATH', '/ispLogos');

$config->set('HTML_CHECKED', ' checked="checked"');
$config->set('HTML_DISABLED', ' disabled="disabled"');
$config->set('HTML_READONLY', ' readonly="readonly"');
$config->set('HTML_SELECTED', ' selected="selected"');

// Default Language (if not overriden by admin)
$config->set('USER_INITIAL_LANG', 'en_GB');

// Tell whether or not output must be compressed
$config->set('COMPRESS_OUTPUT', 1);

// show spGZIP compression information in HTML output
$config->set('SHOW_COMPRESSION_SIZE', 1);

// Session timeout in minutes
$config->set('SESSION_TIMEOUT', 30);

// Item status
$config->set('ITEM_OK_STATUS', 'ok');
$config->set('ITEM_ENABLED_STATUS', 'enabled');
$config->set('ITEM_DISABLED_STATUS', 'disabled');
$config->set('ITEM_UNINSTALLED_STATUS', 'uninstalled');
$config->set('ITEM_TOINSTALL_STATUS', 'toinstall');
$config->set('ITEM_TOUPDATE_STATUS', 'toupdate');
$config->set('ITEM_TOUNINSTALL_STATUS', 'touninstall');
$config->set('ITEM_TOADD_STATUS', 'toadd');
$config->set('ITEM_TOCHANGE_STATUS', 'tochange');
$config->set('ITEM_TORESTORE_STATUS', 'torestore');
$config->set('ITEM_TOENABLE_STATUS', 'toenable');
$config->set('ITEM_TODISABLE_STATUS', 'todisable');
$config->set('ITEM_TODELETE_STATUS', 'todelete');

$config->set('ITEM_ORDERED_STATUS', 'ordered');

// SQL variables
$config->set('MAX_SQL_DATABASE_LENGTH', 64);
$config->set('MAX_SQL_USER_LENGTH', 16);
$config->set('MAX_SQL_PASS_LENGTH', 32);

/**
 * The following settings can be overridden via the control panel - (admin/settings.php)
 */

// Domain rows pagination
$config->set('DOMAIN_ROWS_PER_PAGE', 10);

// 'admin': hosting plans are available only in admin level, the
// reseller cannot make custom changes
// 'reseller': hosting plans are available only in reseller level
$config->set('HOSTING_PLANS_LEVEL', 'reseller');

// Enable or disable support system
$config->set('IMSCP_SUPPORT_SYSTEM', 1);

// Enable or disable lost password support
$config->set('LOSTPASSWORD', 1);

// Uniqkeytimeout in minutes
$config->set('LOSTPASSWORD_TIMEOUT', 30);

// Captcha imagewidth
$config->set('LOSTPASSWORD_CAPTCHA_WIDTH', 276);

// Captcha imagehigh
$config->set('LOSTPASSWORD_CAPTCHA_HEIGHT', 30);

// Captcha background color
$config->set('LOSTPASSWORD_CAPTCHA_BGCOLOR', array(176,222,245));

// Captcha text color
$config->set('LOSTPASSWORD_CAPTCHA_TEXTCOLOR', array(1, 53, 920));

/**
 * Captcha ttf fontfiles (have to be under compatible open source license)
 */
$fonts = array(
    'FreeMono.ttf',
    'FreeMonoBold.ttf',
    'FreeMonoBoldOblique.ttf',
    'FreeMonoOblique.ttf',
    'FreeSans.ttf',
    'FreeSansBold.ttf',
    'FreeSansBoldOblique.ttf',
    'FreeSansOblique.ttf',
    'FreeSerif.ttf',
    'FreeSerifBold.ttf',
    'FreeSerifBoldItalic.ttf',
    'FreeSerifItalic.ttf'
);

// Set random captcha font file
$config->set('LOSTPASSWORD_CAPTCHA_FONT', LIBRARY_PATH . '/fonts/' . $fonts[mt_rand(0, count($fonts)-1)]);

// Enable or disable bruteforcedetection
$config->set('BRUTEFORCE', 1);

// Blocktime in minutes
$config->set('BRUTEFORCE_BLOCK_TIME', 30);

// Max login before block
$config->set('BRUTEFORCE_MAX_LOGIN', 3);

// Max login attempts before forced to wait
$config->set('BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT', 2);

// Max captcha failed attempts before block
$config->set('BRUTEFORCE_MAX_CAPTCHA', 5);

// Enable or disable time between logins
$config->set('BRUTEFORCE_BETWEEN', 1);

// Time between logins in seconds
$config->set('BRUTEFORCE_BETWEEN_TIME', 30);

// Enable or disable maintenance mode
// 1: Maintenance mode enabled
// 0: Maintenance mode disabled
$config->set('MAINTENANCEMODE', 0);

// Minimum password chars
$config->set('PASSWD_CHARS', 6);

// Enable or disable strong passwords
// 1: Strong password not allowed
// 0: Strong password allowed
$config->set('PASSWD_STRONG', 1);

/**
 * Logging Mailer default level (messages sent to DEFAULT_ADMIN_ADDRESS)
 *
 * E_USER_NOTICE: common operations (normal work flow)
 * E_USER_WARNING: Operations that may be related to a problem
 * E_USER_ERROR: Errors for which the admin should pay attention
 *
 * Note: PHP's E_USER_* constants are used for simplicity.
 */
$config->set('LOG_LEVEL', E_USER_WARNING);

// Creation of webmaster, postmaster and abuse forwarders when
$config->set('CREATE_DEFAULT_EMAIL_ADDRESSES', 1);

// Count default email accounts (abuse, postmaster, webmaster) in user limit
// 1: default email accounts are counted
// 0: default email accounts are NOT counted
$config->set('COUNT_DEFAULT_EMAIL_ADDRESSES', 1);

// Use hard mail suspension when suspending a domain:
// 1: email accounts are hard suspended (completely unreachable)
// 0: email accounts are soft suspended (passwords are modified so
// user can't access the accounts)
$config->set('HARD_MAIL_SUSPENSION', 1);

// Prevent external login (i.e. check for valid local referer)
// separated in admin, reseller and client
// This option allows to use external login scripts
// 1: prevent external login, check for referer, more secure
// 0: allow external login, do not check for referer, less security (risky)
$config->set('PREVENT_EXTERNAL_LOGIN_ADMIN', 1);
$config->set('PREVENT_EXTERNAL_LOGIN_RESELLER', 1);
$config->set('PREVENT_EXTERNAL_LOGIN_CLIENT', 1);

// Automatic search for new version
$config->set('CHECK_FOR_UPDATES', false);
$config->set('ENABLE_SSL', false);

if(!$config->get('IMSCP_SUPPORT_SYSTEM_TARGET')) {
	$config->set('IMSCP_SUPPORT_SYSTEM_TARGET', '_self');
}

// Converting some possible IDN to ACE
$config->set('DEFAULT_ADMIN_ADDRESS', encode_idna($config->get('DEFAULT_ADMIN_ADDRESS')));
$config->set('SERVER_HOSTNAME', encode_idna($config->get('SERVER_HOSTNAME')));
$config->set('BASE_SERVER_VHOST', encode_idna($config->get('BASE_SERVER_VHOST')));
$config->set('DATABASE_HOST', encode_idna($config->get('DATABASE_HOST')));

// Server traffic settings
$config->set('SERVER_TRAFFIC_LIMIT', 0);
$config->set('SERVER_TRAFFIC_WARN', 0);

// Paths appended to the default PHP open_basedir directive of customers
$config->set('PHPINI_OPEN_BASEDIR', '');

// Initialize the application
iMSCP_Initializer::run($config);

// Please: Don't move this statement before the initialization process
if(PHP_SAPI != 'cli' && !isset(iMSCP_Registry::get('dbConfig')->MAINTENANCEMODE_MESSAGE)) {
    $config->set(
		'MAINTENANCEMODE_MESSAGE',
        tr("We are sorry, but the system is currently under maintenance.\nPlease try again later.")
	);
}

// Removing useless variable
unset($config);
