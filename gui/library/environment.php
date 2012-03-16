<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		i-MSCP_Core
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

// Boot i-MSCP
iMSCP_Bootstrap::boot();

// Configuration parameters

/** @var $config iMSCP_Config_Handler_File */
$config = iMSCP_Config::getInstance();

// Template root directory
$config->ROOT_TEMPLATE_PATH = dirname(dirname(__FILE__)) . '/themes/' . $config->USER_INITIAL_THEME;

// Set the isp logos path
$config->ISP_LOGO_PATH = '/ispLogos';

$config->HTML_CHECKED = ' checked="checked"';
$config->HTML_DISABLED = ' disabled="disabled"';
$config->HTML_READONLY = ' readonly="readonly"';
$config->HTML_SELECTED = ' selected="selected"';

// Default Language (if not overriden by admin)
$config->USER_INITIAL_LANG = 'en_GB';

// Tell whether or not output must be compressed
$config->COMPRESS_OUTPUT = 1;

// show spGZIP compression information in HTML output
$config->SHOW_COMPRESSION_SIZE = 1;

// Session timeout in minutes
$config->SESSION_TIMEOUT = 30;

// Item states
$config->ITEM_ADD_STATUS = 'toadd';
$config->ITEM_OK_STATUS = 'ok';
$config->ITEM_CHANGE_STATUS = 'change';
$config->ITEM_DELETE_STATUS = 'delete';
$config->ITEM_DISABLED_STATUS = 'disabled';
$config->ITEM_RESTORE_STATUS = 'restore';
$config->ITEM_TOENABLE_STATUS = 'toenable';
$config->ITEM_TODISABLED_STATUS = 'todisable';
$config->ITEM_ORDERED_STATUS = 'ordered';
$config->ITEM_DNSCHANGE_STATUS = 'dnschange';

// Orders status
$config->ITEM_ORDER_UNCONFIRMED_STATUS = 'unconfirmed';
$config->ITEM_ORDER_CONFIRMED_STATUS = 'confirmed';
$config->ITEM_ORDER_TREATED_STATUS = 'added';

// SQL variables
$config->MAX_SQL_DATABASE_LENGTH = 64;
$config->MAX_SQL_USER_LENGTH = 16;
$config->MAX_SQL_PASS_LENGTH = 32;

/**
 * The following settings can be overridden via the control panel - (admin/settings.php)
 */

// Domain rows pagination
$config->DOMAIN_ROWS_PER_PAGE = 10;

// 'admin': hosting plans are available only in admin level, the
// reseller cannot make custom changes
// 'reseller': hosting plans are available only in reseller level
$config->HOSTING_PLANS_LEVEL = 'reseller';

// TLD strict validation (according IANA database)
$config->TLD_STRICT_VALIDATION = 1;

// SLD strict validation (according IANA database)
$config->SLD_STRICT_VALIDATION = 1;

// Maximum number of labels for the domain names
// and subdomains (excluding SLD and TLD)
$config->MAX_DNAMES_LABELS = 1;

// Maximum number of labels for the subdomain names
$config->MAX_SUBDNAMES_LABELS = 1;

// Enable or disable support system
$config->IMSCP_SUPPORT_SYSTEM = 1;

// Enable or disable lost password support
$config->LOSTPASSWORD = 1;

// Uniqkeytimeout in minutes
$config->LOSTPASSWORD_TIMEOUT = 30;

// Captcha imagewidth
$config->LOSTPASSWORD_CAPTCHA_WIDTH = 276;

// Captcha imagehigh
$config->LOSTPASSWORD_CAPTCHA_HEIGHT = 30;

// Captcha background color
$config->LOSTPASSWORD_CAPTCHA_BGCOLOR = array(176,222,245);

// Captcha text color
$config->LOSTPASSWORD_CAPTCHA_TEXTCOLOR = array(1, 53, 920);

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
$config->LOSTPASSWORD_CAPTCHA_FONT = LIBRARY_PATH . '/fonts/' . $fonts[mt_rand(0, count($fonts)-1)];

// Enable or disable bruteforcedetection
$config->BRUTEFORCE = 1;

// Blocktime in minutes
$config->BRUTEFORCE_BLOCK_TIME = 30;

// Max login before block
$config->BRUTEFORCE_MAX_LOGIN = 3;

// Max captcha failed attempts before block
$config->BRUTEFORCE_MAX_CAPTCHA = 5;

// Enable or disable time between logins
$config->BRUTEFORCE_BETWEEN = 1;

// Time between logins in seconds
$config->BRUTEFORCE_BETWEEN_TIME = 30;

// Enable or disable maintenance mode
// 1: Maintenance mode enabled
// 0: Maintenance mode disabled
$config->MAINTENANCEMODE = 0;

// Minimum password chars
$config->PASSWD_CHARS = 6;

// Enable or disable strong passwords
// 1: Strong password not allowed
// 0: Strong password allowed
$config->PASSWD_STRONG = 1;

// The virtual host file from Apache which contains our virtual host
// entries
#$config->SERVER_VHOST_FILE = $config->APACHE_SITES_DIR . '/imscp.conf';

/**
 * Logging Mailer default level (messages sent to DEFAULT_ADMIN_ADDRESS)
 *
 * E_USER_NOTICE: common operations (normal work flow)
 * E_USER_WARNING: Operations that may be related to a problem
 * E_USER_ERROR: Errors for which the admin should pay attention
 *
 * Note: PHP's E_USER_* constants are used for simplicity.
 */
$config->LOG_LEVEL = E_USER_WARNING;

// Creation of webmaster, postmaster and abuse forwarders when
$config->CREATE_DEFAULT_EMAIL_ADDRESSES = 1;

// Count default e-mail (abuse, postmaster, webmaster) in user limit
// 1: default e-mail are counted
// 0: default e-mail are NOT counted
$config->COUNT_DEFAULT_EMAIL_ADDRESSES = 1;

// Use hard mail suspension when suspending a domain:
// 1: email accounts are hard suspended (completely unreachable)
// 0: email accounts are soft suspended (passwords are modified so
// user can't access the accounts)
$config->HARD_MAIL_SUSPENSION = 1;

// Prevent external login (i.e. check for valid local referer)
// separated in admin, reseller and client
// This option allows to use external login scripts
// 1: prevent external login, check for referer, more secure
// 0: allow external login, do not check for referer, less security (risky)
$config->PREVENT_EXTERNAL_LOGIN_ADMIN = 1;
$config->PREVENT_EXTERNAL_LOGIN_RESELLER = 1;
$config->PREVENT_EXTERNAL_LOGIN_CLIENT = 1;

// Automatic search for new version
$config->CHECK_FOR_UPDATES = true;
$config->ENABLE_SSL = false;

if(!$config->IMSCP_SUPPORT_SYSTEM_TARGET) {
	$config->IMSCP_SUPPORT_SYSTEM_TARGET = '_self';
}

# Converting some possible IDN to ACE
$config->DEFAULT_ADMIN_ADDRESS = encode_idna($config->DEFAULT_ADMIN_ADDRESS);
$config->SERVER_HOSTNAME = encode_idna($config->SERVER_HOSTNAME);
$config->BASE_SERVER_VHOST = encode_idna($config->BASE_SERVER_VHOST);
$config->DATABASE_HOST = encode_idna($config->DATABASE_HOST);

// Default expiration time for unconfirmed orders  - defaulted to one week
$config->ORDERS_EXPIRE_TIME = 604800;

// Plugins config namespace
$config->PLUGIN = array();

// Paths appended to the default PHP open_basedir directive of customers
$config->PHPINI_OPEN_BASEDIR = '';

// Initialize the application
iMSCP_Initializer::run($config);

// Please: Don't move this statement before the initialization process
if(PHP_SAPI != 'cli') {
    $config->MAINTENANCEMODE_MESSAGE =
        tr("We are sorry, but the system is currently under maintenance.\nPlease try again later.");
}

// Removing useless variable
unset($config);
