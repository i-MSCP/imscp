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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage  Config_Handler
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/** @see iMSCP_Config_Handler */
require_once  'iMSCP/Config/Handler.php';

/**
 * Class to handle configuration parameters from a flat file.
 *
 * iMSCP_Config_Handler adapter class to handle configuration parameters that are stored in a flat file where each pair
 * of key-values are separated by the equal sign.
 *
 * @property string MR_LOCK_FILE Path to lock file
 * @property string ROOT_TEMPLATE_PATH Root templates path
 * @property string USER_INITIAL_THEME User initial theme
 * @property string LOGIN_TEMPLATE_PATH Login templates path
 * @property string ADMIN_TEMPLATE_PATH Admin templates path
 * @property string RESELLER_TEMPLATE_PATH Reseller templates path
 * @property string CLIENT_TEMPLATE_PATH Client templates path
 * @property string ISP_LOGO_PATH isp logo path
 * @property string PURCHASE_TEMPLATE_PATH Purchase templates path
 * @property string HTML_CHECKED Html attribute for preselected input element
 * @property string HTML_DISABLED Html attribute for disabled input element
 * @property string HTML_READONLY Html attribute for readonly input element
 * @property string HTML_SELECTED Html attribute for selected input element
 * @property string USER_INITIAL_LANG User initial them
 * @property bool DUMP_GUI_DEBUG If true, display some information for debugging
 * @property bool SHOW_COMPRESSION_SIZE If TRUE, show page compression info
 * @property int SESSION_TIMEOUT Session timeout
 * @property string ITEM_ADD_STATUS Status that reflect cheduled data addition
 * @property string ITEM_OK_STATUS  Status that reflect normal state
 * @property string ITEM_CHANGE_STATUS Status for scheduled data changes
 * @property string ITEM_DELETE_STATUS Status for scheduled data deletion
 * @property string ITEM_DISABLED_STATUS Status for disabled domains
 * @property string ITEM_RESTORE_STATUS Status for scheduled data restoring
 * @property string ITEM_TOENABLE_STATUS Status for scheduled enabling
 * @property string ITEM_TODISABLED_STATUS Status for scheduled disabling
 * @property string ITEM_ORDERED_STATUS Status for domain/alias order
 * @property string ITEM_DNSCHANGE_STATUS Status for scheduled dns change
 * @property string ITEM_ORDER_UNCONFIRMED_STATUS Unconfirmed order
 * @property string ITEM_ORDER_CONFIRMED_STATUS Confirmed order
 * @property string ITEM_ORDER_TREATED_STATUS Order added
 * @property int MAX_SQL_DATABASE_LENGTH Max. length for database name
 * @property int MAX_SQL_USER_LENGTH Max. length for Sql username
 * @property int MAX_SQL_PASS_LENGTH Max. length for Sql password
 * @property int DOMAIN_ROWS_PER_PAGE Number for domain displayed per page
 * @property string HOSTING_PLANS_LEVEL Hosting plan level (admin|reseller)
 * @property bool TLD_STRICT_VALIDATION If TRUE, Only TLD from iana database are usable
 * @property bool SLD_STRICT_VALIDATION If TRUE, only restricted tld list can have sld with a single character
 * @property int MAX_DNAMES_LABELS Maximum number of labels for a domain name
 * @property int MAX_SUBDNAMES_LABELS Maximum number of labels for a subdomain
 * @property bool IMSCP_SUPPORT_SYSTEM If TRUE, support system is available
 * @property bool LOSTPASSWORD If TRUE lost password is available
 * @property int LOSTPASSWORD_TIMEOUT Timeout for lost password
 * @property int LOSTPASSWORD_CAPTCHA_WIDTH Captcha width
 * @property int LOSTPASSWORD_CAPTCHA_HEIGHT Captcha height
 * @property array LOSTPASSWORD_CAPTCHA_BGCOLOR Captcha background color
 * @property array LOSTPASSWORD_CAPTCHA_TEXTCOLOR Captcha text color
 * @property string LOSTPASSWORD_CAPTCHA_FONT Captcha font
 * @property bool BRUTEFORCE If TRUE, brute force detection is enabled
 * @property int BRUTEFORCE_BLOCK_TIME Blocktime after brute force detection,
 * @property int BRUTEFORCE_MAX_LOGIN Max. number of login attemps before brute force block time
 * @property int BRUTEFORCE_MAX_CAPTCHA Max. number of captcha attemps before blocktime
 * @property bool BRUTEFORCE_BETWEEN If true, block time between login is active
 * @property int BRUTEFORCE_BETWEEN_TIME Block time bettwen each login attemps
 * @property bool MAINTENANCEMODE If TRUE, maintenance mode is enabled
 * @property string MAINTENANCEMODE_MESSAGE Message to display during maintenance
 * @property int PASSWD_CHARS Allowed number of chararacterd for passwords
 * @property bool PASSWD_STRONG If TRUE, only strong password are allowed
 * @property string SERVER_VHOST_FILE Server virtualHost file
 * @property string APACHE_SITES_DIR Apache sites directory
 * @property int LOG_LEVEL Log level (only for user errors)
 * @property bool CREATE_DEFAULT_EMAIL_ADDRESSES If TRUE, create default email addresse (abuse, postmaster, webmaster)
 * @property bool COUNT_DEFAULT_EMAIL_ADDRESSES If TRUE, count the default mail addresses
 * @property bool HARD_MAIL_SUSPENSION
 * @property bool PREVENT_EXTERNAL_LOGIN_ADMIN If TRUE, login from external site is prevented for administrators
 * @property bool PREVENT_EXTERNAL_LOGIN_RESELLER If TRUE, login from external site is prevented for resellers
 * @property bool PREVENT_EXTERNAL_LOGIN_CLIENT
 * @property bool CHECK_FOR_UPDATES If TRUE, update cheching is enabled
 * @property string IMSCP_SUPPORT_SYSTEM_TARGET
 * @property string BASE_SERVER_VHOST_PREFIX
 * @property string DATE_FORMAT Date format
 * @property string BASE_SERVER_VHOST Base server vhost
 * @property string GUI_SOFTWARE_DEPOT_DIR Application software repository directory
 * @property string MAX_REMOTE_FILESIZE Max size for remote application package
 * @property int CUSTOM_ORDERPANEL_ID Custom order panel id
 * @property int DATABASE_REVISION Last database revision
 * @property int CRITICAL_UPDATE_REVISION Last critical update revision
 * @property string AWSTATS_GROUP_AUTH Awstats group authentication
 * @property string BASE_SERVER_IP Base server IP
 * @property string PORT_POSTGREY Posgrey port
 * @property int BuildDate i-MSCP package Build date
 * @property string DATABASE_UTF8 tell whether or not use UTF-8 for database communication
 * @property string PHP_TIMEZONE PHP timezone
 * @property int DEBUG Debug mode
 * @property string DEFAULT_ADMIN_ADDRESS Default mail address for administrator
 * @property string GUI_EXCEPTION_WRITERS Exception writer list
 * @property string DATABASE_USER i-MSCP database username
 * @property string DATABASE_PASSWORD i-MSCP database password
 * @property string DATABASE_TYPE Database type
 * @property string DATABASE_HOST Database hostname
 * @property string DATABASE_NAME Database name
 * @property string CMD_IFCONFIG Path to ifconfig
 * @property string CMD_DF Path to df
 * @property string CMD_VMSTAT Path to vmstat
 * @property string CMD_SWAPCTL Path to swapctl
 * @property string CMD_SYSCTL path to sysctl
 * @property string GUI_ROOT_DIR path to GUI
 * @property string CMD_SHELL Path to shell interpreter
 * @property string FTP_HOMEDIR Ftp home directory
 * @property string SERVER_HOSTNAME Server hostname
 * @property string IMSCP_SUPPORT_SYSTEM_PATH
 * @property string Version
 * @property string CodeName
 * @property int ORDERS_EXPIRE_TIME Default expiration time for unconfirmed orders
 * @property string GUI_SOFTWARE_DIR Directory for software repositories
 * @property int COMPRESS_OUTPUT Tells whether or not output must be compressed
 * @property int ENABLE_SSL Tells whether or not SSL feature for customers is enabled
 * @property bool MAIN_MENU_SHOW_LABELS Tells whether or not labels must be showed for main menu links
 * @property array PLUGIN Plugins configuration
 * @property string PHPINI_OPEN_BASEDIR paths appended to the default PHP open_basedir directive of customers
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage  Config_Handler
 * @author		Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.7
 */
class iMSCP_Config_Handler_File extends iMSCP_Config_Handler
{
	/**
	 * Configuration file path
	 *
	 * @var string
	 */
	protected $_pathFile;

	/**
	 * Loads all configuration parameters from a flat file
	 *
	 * <b>Note:</b> Default file path is set to:
	 * {/usr/local}/etc/imscp/imscp.conf depending of distribution.
	 *
	 * @param string $pathFile Configuration file path
	 */
	public function __construct($pathFile = null)
	{
		if (is_null($pathFile)) {
			if (getenv('IMSCP_CONF')) {
				$pathFile = getEnv('IMSCP_CONF');
			} else {
				switch (PHP_OS) {
					case 'FreeBSD':
					case 'OpenBSD':
					case 'NetBSD':
						$pathFile = '/usr/local/etc/imscp/imscp.conf';
						break;
					default:
						$pathFile = '/etc/imscp/imscp.conf';
				}
			}
		}

		$this->_pathFile = $pathFile;
		$this->_parseFile();
	}

	/**
	 * Opens a configuration file and parses its Key = Value pairs into the
	 * {@link iMSCP_Config_Hangler::parameters} array.
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 */
	protected function _parseFile()
	{
		if (($fd = @file_get_contents($this->_pathFile)) == false) {
			throw new iMSCP_Exception(sprintf('Unable to open the configuration file `%s`', $this->_pathFile));
		}

		foreach (explode(PHP_EOL, $fd) as $line) {
			if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
				list($key, $value) = explode('=', $line, 2);
				$this[trim($key)] = trim($value);
			}
		}
	}
}
