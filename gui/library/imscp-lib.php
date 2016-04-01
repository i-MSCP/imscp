<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Set default error reporting level
error_reporting(E_ALL | E_STRICT);

// Sets to TRUE here to ensure displaying of the base core errors
// Will be overwritten during initialization process
// @see iMSCP_Initializer::_setDisplayErrors()
ini_set('display_errors', 1);

// Define paths
define('GUI_ROOT_DIR', dirname(__DIR__));
define('LIBRARY_PATH', GUI_ROOT_DIR . '/library');
define('CACHE_PATH', GUI_ROOT_DIR . '/data/cache');
define('PERSISTENT_PATH', GUI_ROOT_DIR . '/data/persistent');
define('CONFIG_FILE_PATH', getenv('IMSCP_CONF') ?: '/etc/imscp/imscp.conf');
define('CONFIG_CACHE_FILE_PATH', CACHE_PATH . '/imscp_config.conf');
define('DBCONFIG_CACHE_FILE_PATH', CACHE_PATH . '/imscp_dbconfig.conf');

// Setup include path
set_include_path(implode(PATH_SEPARATOR, array_unique(
	array_merge(array(LIBRARY_PATH, LIBRARY_PATH . '/vendor'), explode(PATH_SEPARATOR, get_include_path()))
)));

// Setup autoloader
require_once LIBRARY_PATH . '/vendor/Zend/Loader/AutoloaderFactory.php';

Zend_Loader_AutoloaderFactory::factory(array(
	'Zend_Loader_StandardAutoloader' => array(
		'autoregister_zf' => true,
		'namespaces' => array(
			'iMSCP' => LIBRARY_PATH . '/iMSCP'
		),
		'prefixes' => array(
			'iMSCP_' => LIBRARY_PATH . '/iMSCP',
			'Crypt_' => LIBRARY_PATH . '/vendor/phpseclib/Crypt',
			'File_' => LIBRARY_PATH . '/vendor/phpseclib/File',
			'Math_' => LIBRARY_PATH . '/vendor/phpseclib/Math',
			'Net_' => LIBRARY_PATH . '/vendor/Net'
		)
	)
));

// Set handler for uncaught exceptions
iMSCP_Registry::set('exceptionHandler', new iMSCP_Exception_Handler());

/**
 * Include core functions
 */

require_once LIBRARY_PATH . '/Functions/Admin.php';
require_once LIBRARY_PATH . '/Functions/Client.php';
require_once LIBRARY_PATH . '/Functions/Email.php';
require_once LIBRARY_PATH . '/Functions/Input.php';
require_once LIBRARY_PATH . '/Functions/Intl.php';
require_once LIBRARY_PATH . '/Functions/Layout.php';
require_once LIBRARY_PATH . '/Functions/Login.php';
require_once LIBRARY_PATH . '/Functions/Shared.php';
require_once LIBRARY_PATH . '/Functions/SoftwareInstaller.php';
require_once LIBRARY_PATH . '/Functions/Reseller.php';
require_once LIBRARY_PATH . '/Functions/View.php';
require_once LIBRARY_PATH . '/vendor/idna_convert/idna_convert.class.php';

/** @var $config iMSCP_Config_Handler_File */
if(is_readable(CONFIG_CACHE_FILE_PATH)) {
	$config = unserialize(file_get_contents(CONFIG_CACHE_FILE_PATH));

	clearstatcache(true, CONFIG_FILE_PATH);

	if($config['DEBUG'] || filemtime(CONFIG_FILE_PATH) !== $config['__filemtime__']) {
		@unlink(CONFIG_CACHE_FILE_PATH);
		goto FORCE_CONFIG_RELOAD;
	}
} else {
	FORCE_CONFIG_RELOAD:

	$config = new iMSCP_Config_Handler_File(CONFIG_FILE_PATH);

	// Template root directory
	$config['ROOT_TEMPLATE_PATH'] = dirname(__DIR__) . '/themes/' . $config['USER_INITIAL_THEME'];

	// Set the isp logos path
	$config['ISP_LOGO_PATH'] = '/ispLogos';

	$config['HTML_CHECKED'] = ' checked';
	$config['HTML_DISABLED'] = ' disabled';
	$config['HTML_READONLY'] = ' readonly';
	$config['HTML_SELECTED'] = ' selected';

	// Default Language (if not overriden by admin)
	$config['USER_INITIAL_LANG'] = 'auto';

	// Tell whether or not output must be compressed
	$config['COMPRESS_OUTPUT'] = 1;

	// show GZIP compression information in HTML output
	$config['SHOW_COMPRESSION_SIZE'] = 1;

	// Session timeout in minutes
	$config['SESSION_TIMEOUT'] = 30;

	// SQL variables
	$config['MAX_SQL_DATABASE_LENGTH'] = 64;
	$config['MAX_SQL_USER_LENGTH'] = 16;
	$config['MAX_SQL_PASS_LENGTH'] = 32;

	// Captcha background color
	$config['LOSTPASSWORD_CAPTCHA_BGCOLOR'] = array(176, 222, 245);

	// Captcha text color
	$config['LOSTPASSWORD_CAPTCHA_TEXTCOLOR'] = array(1, 53, 920);

	// Captcha imagewidth
	$config['LOSTPASSWORD_CAPTCHA_WIDTH'] = 276;

	// Captcha imagehigh
	$config['LOSTPASSWORD_CAPTCHA_HEIGHT'] = 30;

	/**
	 * Captcha ttf fontfiles (have to be under compatible open source license)
	 */
	$config['LOSTPASSWORD_CAPTCHA_FONTS'] = array(
		'FreeMono.ttf',  'FreeMonoBold.ttf',  'FreeMonoBoldOblique.ttf', 'FreeMonoOblique.ttf', 'FreeSans.ttf',
		'FreeSansBold.ttf', 'FreeSansBoldOblique.ttf', 'FreeSansOblique.ttf', 'FreeSerif.ttf', 'FreeSerifBold.ttf',
		'FreeSerifBoldItalic.ttf', 'FreeSerifItalic.ttf'
	);

	/**
	 * The following settings can be overridden via the control panel - (admin/settings.php)
	 */

	// Domain rows pagination
	$config['DOMAIN_ROWS_PER_PAGE'] = 10;

	// Enable or disable support system
	$config['IMSCP_SUPPORT_SYSTEM'] = 1;

	// Enable or disable lost password support
	$config['LOSTPASSWORD'] = 1;

	// Uniqkeytimeout in minutes
	$config['LOSTPASSWORD_TIMEOUT'] = 30;

	// Enable or disable bruteforcedetection
	$config['BRUTEFORCE'] = 1;

	// Blocktime in minutes
	$config['BRUTEFORCE_BLOCK_TIME'] = 30;

	// Max login before block
	$config['BRUTEFORCE_MAX_LOGIN'] = 3;

	// Max login attempts before forced to wait
	$config['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'] = 2;

	// Max captcha failed attempts before block
	$config['BRUTEFORCE_MAX_CAPTCHA'] = 5;

	// Enable or disable time between logins
	$config['BRUTEFORCE_BETWEEN'] = 1;

	// Time between logins in seconds
	$config['BRUTEFORCE_BETWEEN_TIME'] = 30;

	// Enable or disable maintenance mode
	// 1: Maintenance mode enabled
	// 0: Maintenance mode disabled
	$config['MAINTENANCEMODE'] = 0;

	// Minimum password chars
	$config['PASSWD_CHARS'] = 6;

	// Enable or disable strong passwords
	// 1: Strong password not allowed
	// 0: Strong password allowed
	$config['PASSWD_STRONG'] = 1;

	/**
	 * Logging Mailer default level (messages sent to DEFAULT_ADMIN_ADDRESS)
	 *
	 * E_USER_NOTICE: common operations (normal work flow)
	 * E_USER_WARNING: Operations that may be related to a problem
	 * E_USER_ERROR: Errors for which the admin should pay attention
	 *
	 * Note: PHP's E_USER_* constants are used for simplicity.
	 */
	$config['LOG_LEVEL'] = E_USER_WARNING;

	// Creation of webmaster, postmaster and abuse forwarders when
	$config['CREATE_DEFAULT_EMAIL_ADDRESSES'] = 1;

	// Count default email accounts (abuse, postmaster, webmaster) in user limit
	// 1: default email accounts are counted
	// 0: default email accounts are NOT counted
	$config['COUNT_DEFAULT_EMAIL_ADDRESSES'] = 1;

	// Use hard mail suspension when suspending a domain:
	// 1: email accounts are hard suspended (completely unreachable)
	// 0: email accounts are soft suspended (passwords are modified so user can't access the accounts)
	$config['HARD_MAIL_SUSPENSION'] = 1;

	// Prevent external login (i.e. check for valid local referer) separated in admin, reseller and client.
	// This option allows to use external login scripts
	//
	// 1: prevent external login, check for referer, more secure
	// 0: allow external login, do not check for referer, less security (risky)
	$config['PREVENT_EXTERNAL_LOGIN_ADMIN'] = 1;
	$config['PREVENT_EXTERNAL_LOGIN_RESELLER'] = 1;
	$config['PREVENT_EXTERNAL_LOGIN_CLIENT'] = 1;

	// Automatic search for new version
	$config['CHECK_FOR_UPDATES'] = 0;
	$config['ENABLE_SSL'] = 1;

	// Converting some possible IDN to ACE
	$config['DEFAULT_ADMIN_ADDRESS'] = encode_idna($config->get('DEFAULT_ADMIN_ADDRESS'));
	$config['SERVER_HOSTNAME'] = encode_idna($config->get('SERVER_HOSTNAME'));
	$config['BASE_SERVER_VHOST'] = encode_idna($config->get('BASE_SERVER_VHOST'));
	$config['DATABASE_HOST'] = encode_idna($config->get('DATABASE_HOST'));

	// Server traffic settings
	$config['SERVER_TRAFFIC_LIMIT'] = 0;
	$config['SERVER_TRAFFIC_WARN'] = 0;

	// Store file last modification time to force reloading of configuration file if needed
	$config['__filemtime__'] = filemtime(CONFIG_FILE_PATH);

	if(!$config['DEBUG']) {
		@file_put_contents(CONFIG_CACHE_FILE_PATH, serialize($config), LOCK_EX);
	}
}

// Initialize application
iMSCP_Initializer::run($config);

// Remove useless variable
unset($configFilePath, $cachedConfigFilePath, $config);
