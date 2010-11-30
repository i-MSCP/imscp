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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		i-MSCP
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author		ispCP Team
 * @author		i-MSCP Team
 * @version		SVN: $Id$
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

// Boot i-MSCP
iMSCP_Bootstrap::boot();

// Get a reference to a iMSCP_Config_Handler_File instance
$config = new iMSCP_Config_Handler_File();

// Set some configuration parameters

// Template paths
$config->ROOT_TEMPLATE_PATH = 'themes/';

// Get template path
$tpl_path = $config->ROOT_TEMPLATE_PATH . $config->USER_INITIAL_THEME;

// Set the login templates path
$config->LOGIN_TEMPLATE_PATH = $tpl_path;

// Set the users level templates path
$config->ADMIN_TEMPLATE_PATH =  "../$tpl_path/admin";
$config->RESELLER_TEMPLATE_PATH = "../$tpl_path/reseller";
$config->CLIENT_TEMPLATE_PATH = "../$tpl_path/client";

// Set the isCP logo path
$config->IPS_LOGO_PATH = '../themes/user_logos';

// Set the order panel templates path
$config->PURCHASE_TEMPLATE_PATH = "../$tpl_path/orderpanel";

$config->HTML_CHECKED = ' checked="checked"';
$config->HTML_DISABLED = ' disabled="disabled"';
$config->HTML_READONLY = ' readonly="readonly"';
$config->HTML_SELECTED = ' selected="selected"';

// Standard Language (if not set)
$config->USER_INITIAL_LANG = 'lang_EnglishBritain';

// variable for development edition: show all php variables beyond page content
$config->DUMP_GUI_DEBUG = false;

// show spGZIP compression information in HTML output
$config->SHOW_COMPRESSION_SIZE = true;

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

// SQL variables
$config->MAX_SQL_DATABASE_LENGTH = 64;
$config->MAX_SQL_USER_LENGTH = 16;
$config->MAX_SQL_PASS_LENGTH = 32;

/**
 * The following parameters are overwritten via admin cp
 */

// Domain rows pagination
$config->DOMAIN_ROWS_PER_PAGE = 10;

// 'admin': hosting plans are available only in admin level, the
// reseller cannot make custom changes
// 'reseller': hosting plans are available only in reseller level
$config->HOSTING_PLANS_LEVEL = 'reseller';

// TLD strict validation (according IANA database)
$config->TLD_STRICT_VALIDATION = true;

// SLD strict validation
$config->SLD_STRICT_VALIDATION = true;

// Maximum number of labels for the domain names
// and subdomains (excluding SLD and TLD)
$config->MAX_DNAMES_LABELS = 1;

// Maximum number of labels for the subdomain names
$config->MAX_SUBDNAMES_LABELS = 1;

// Enable or disable support system
$config->IMSCP_SUPPORT_SYSTEM = true;

// Enable or disable lost password support
$config->LOSTPASSWORD = true;

// Uniqkeytimeout in minutes
$config->LOSTPASSWORD_TIMEOUT = 30;

// Captcha imagewidth
$config->LOSTPASSWORD_CAPTCHA_WIDTH = 280;

// Captcha imagehigh
$config->LOSTPASSWORD_CAPTCHA_HEIGHT = 70;

// Captcha background color
$config->LOSTPASSWORD_CAPTCHA_BGCOLOR = array(176,222,245);

// Captcha text color
$config->LOSTPASSWORD_CAPTCHA_TEXTCOLOR = array(1, 53, 920);

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
$config->LOSTPASSWORD_CAPTCHA_FONT = INCLUDEPATH . '/fonts/' .
	$fonts[mt_rand(0, count($fonts)-1)];

// Enable or disable bruteforcedetection
$config->BRUTEFORCE = true;

// Blocktime in minutes
$config->BRUTEFORCE_BLOCK_TIME = 30;

// Max login before block
$config->BRUTEFORCE_MAX_LOGIN = 3;

// Max captcha failed attempts before block
$config->BRUTEFORCE_MAX_CAPTCHA = 5;

// Enable or disable time between logins
$config->BRUTEFORCE_BETWEEN = true;

// Time between logins in seconds
$config->BRUTEFORCE_BETWEEN_TIME = 30;

// Enable or disable maintenance mode
$config->MAINTENANCEMODE = false;

// Servicemode message
// Please: Leave the comment for 'tr'
$config->MAINTENANCEMODE_MESSAGE =
	/*tr*/("We are sorry, but the system is currently under maintenance.\nPlease try again later.");

// Minimum password chars
$config->PASSWD_CHARS = 6;

// Enable or disable strong passwords
$config->PASSWD_STRONG = true;

// The virtual host file from Apache which contains our virtual host
// entries
$config->SERVER_VHOST_FILE = $config->APACHE_SITES_DIR . '/imscp.conf';

// The minimum level for a message to be sent to DEFAULT_ADMIN_ADDRESS
// PHP's E_USER_* values are used for simplicity:
// E_USER_NOTICE: logins, and all info that isn't very relevant
// E_USER_WARNING: switching to an other account, etc
// E_USER_ERROR: "admin MUST know" messages
$config->LOG_LEVEL = E_USER_NOTICE;

// Creation of webmaster, postmaster and abuse forwarders when
// domain/alias/ subdomain is created
$config->CREATE_DEFAULT_EMAIL_ADDRESSES = true;

// Count default e-mail (abuse,postmaster,webmaster) in user limit
// true: default e-mail are counted
// false: default e-mail are NOT counted
$config->COUNT_DEFAULT_EMAIL_ADDRESSES = true;

// Use hard mail suspension when suspending a domain:
// true: email accounts are hard suspended (completely unreachable)
// false: email accounts are soft suspended (passwords are modified so
// user can't access the accounts)
$config->HARD_MAIL_SUSPENSION = true;

// Prevent external login (i.e. check for valid local referer)
// separated in admin, reseller and client
// This option allows to use external login scripts
// true: prevent external login, check for referer, more secure
// false: allow external login, do not check for referer, less
// security (risky)
$config->PREVENT_EXTERNAL_LOGIN_ADMIN = true;
$config->PREVENT_EXTERNAL_LOGIN_RESELLER = true;
$config->PREVENT_EXTERNAL_LOGIN_CLIENT = true;

// Automatic search for new version
$config->CHECK_FOR_UPDATES = true;

if(!$config->IMSCP_SUPPORT_SYSTEM_TARGET) {
	$config->IMSCP_SUPPORT_SYSTEM_TARGET = '_self';
}

# Converting some possible IDN to ACE (see #2476)
$config->DEFAULT_ADMIN_ADDRESS = encode_idna($config->DEFAULT_ADMIN_ADDRESS);
$config->SERVER_HOSTNAME = encode_idna($config->SERVER_HOSTNAME);
$config->BASE_SERVER_VHOST = encode_idna($config->BASE_SERVER_VHOST);
$config->DATABASE_HOST = encode_idna($config->DATABASE_HOST);

// Initialize the application
iMSCP_Initializer::run($config);

// Remove useless variable
unset($config);
