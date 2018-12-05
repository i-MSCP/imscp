<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Config_Handler as ConfigHandler;
use iMSCP_Exception as iMSCPException;

/**
 * Class to handle configuration parameters from a flat file.
 *
 * ConfigHandler adapter class to handle configuration parameters that are
 * stored in a flat file where each pair of key-values are separated by the
 * equal sign.
 *
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
 * @property int SESSION_TIMEOUT Session timeout
 * @property int DOMAIN_ROWS_PER_PAGE Number for domain displayed per page
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
 * @property int LOG_LEVEL Log level (only for user errors)
 * @property bool CREATE_DEFAULT_EMAIL_ADDRESSES If TRUE, create default email addresse (abuse, postmaster, webmaster)
 * @property bool COUNT_DEFAULT_EMAIL_ADDRESSES If TRUE, count the default mail addresses
 * @property bool HARD_MAIL_SUSPENSION
 * @property bool PREVENT_EXTERNAL_LOGIN_ADMIN If TRUE, login from external site is prevented for administrators
 * @property bool PREVENT_EXTERNAL_LOGIN_RESELLER If TRUE, login from external site is prevented for resellers
 * @property bool PREVENT_EXTERNAL_LOGIN_CLIENT
 * @property bool CHECK_FOR_UPDATES If TRUE, update cheching is enabled
 * @property string BASE_SERVER_VHOST_PREFIX
 * @property string DATE_FORMAT Date format
 * @property string BASE_SERVER_VHOST Base server vhost
 * @property string GUI_APS_DEPOT_DIR Application software repository directory
 * @property string APS_MAX_REMOTE_FILESIZE Max size for remote application package
 * @property int DATABASE_REVISION Last database revision
 * @property int CRITICAL_UPDATE_REVISION Last critical update revision
 * @property string BASE_SERVER_IP Base server IP
 * @property int Build i-MSCP package Build date
 * @property string TIMEZONE timezone
 * @property int DEBUG Debug mode
 * @property string DEFAULT_ADMIN_ADDRESS Default mail address for administrator
 * @property string GUI_EXCEPTION_WRITERS Exception writer list
 * @property string DATABASE_USER i-MSCP database username
 * @property string DATABASE_PASSWORD i-MSCP database password
 * @property string DATABASE_TYPE Database type
 * @property string DATABASE_HOST Database hostname
 * @property string DATABASE_NAME Database name
 * @property string GUI_ROOT_DIR path to GUI
 * @property string SERVER_HOSTNAME Server hostname
 * @property string Version
 * @property string CodeName
 * @property string GUI_APS_DIR Directory for software repositories
 * @property int ENABLE_SSL Tells whether or not SSL feature for customers is enabled
 * @property bool MAIN_MENU_SHOW_LABELS Tells whether or not labels must be showed for main menu links
 * @property string FTP_USERNAME_SEPARATOR Ftp username separator
 * @property string BACKUP_DOMAINS (yes|no)
 * @property string WEBSTATS_PACKAGES (No|<webstats_package_name>)
 * @property string CONF_DIR i-MSCP configuration directory (eg. /etc/imscp)
 * @property string USER_WEB_DIR Directory which holds i-MSCP customer user Web directories
 * @property int THEME_ASSETS_VERSION unique string used for assets cache busting
 * @property string PANEL_SSL_ENABLED Whether or not SSL is enabled for the panel
 * @property int EMAIL_QUOTA_SYNC_MODE
 */
class iMSCP_Config_Handler_File extends ConfigHandler
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
     * Default file path is set to {/usr/local}/etc/imscp/imscp.conf depending
     * of distribution.
     *
     * @noinspection PhpMissingParentConstructorInspection
     * 
     * @param string $pathFile Configuration file path
     * @throws iMSCP_Exception
     */
    public function __construct($pathFile = NULL)
    {
        if (is_null($pathFile)) {
            if (getenv('IMSCP_CONF')) {
                $pathFile = getenv('IMSCP_CONF');
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
     * Opens a configuration file and parses its Key = Value pairs
     *
     * @throws iMSCPException
     * @return void
     */
    protected function _parseFile()
    {
        if (($fd = @file_get_contents($this->_pathFile)) == false) {
            throw new iMSCPException(sprintf("Couldn't open the %s configuration file", $this->_pathFile));
        }

        foreach (explode(PHP_EOL, $fd) as $line) {
            if (!empty($line) && $line[0] != '#' && strpos($line, '=')) {
                list($key, $value) = explode('=', $line, 2);
                $this[trim($key)] = trim($value);
            }
        }
    }
}
