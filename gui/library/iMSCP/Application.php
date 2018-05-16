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

namespace iMSCP;

use iMSCP_Config_Handler_Db as ConfigDb;
use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Events_Event as Event;
use iMSCP_Exception as iMSCPException;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Exception_Handler as ExceptionHandler;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;
use Zend_Cache as Cache;
use Zend_Loader_AutoloaderFactory as AutoloaderFactory;
use Zend_Locale as Locale;
use Zend_Session as Session;
use Zend_Translate as Translator;

//use Zend_Translate_Adapter_Array as TranslatorArray;

/**
 * Class Appplication
 */
class Application
{
    /**
     * @var string Application environment
     */
    protected $environment;

    /**
     * @var \Zend_Loader_StandardAutoloader
     */
    protected $autoloader;

    /**
     * @var EventsManager
     */
    protected $eventsManager;

    /**
     * @var ConfigFile Merged configuration (Main configuration, Database configuration)
     */
    protected $config;

    /**
     * @var ConfigDb
     */
    protected $dbConfig;

    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @static boolean Flag indicating whether application has been bootstrapped
     */
    protected $bootstrapped = false;

    /**
     * Application constructor
     *
     * @param string $environment
     * @throws \Zend_Loader_Exception_InvalidArgumentException
     */
    public function __construct($environment)
    {
        $this->environment = (string)$environment;

        require_once 'Zend/Loader/AutoloaderFactory.php';

        AutoloaderFactory::factory([
            AutoloaderFactory::STANDARD_AUTOLOADER => [
                'autoregister_zf'     => true,
                'fallback_autoloader' => true,
                'namespaces'          => [
                    'iMSCP'           => LIBRARY_PATH . '/iMSCP',
                    'Mso\IdnaConvert' => LIBRARY_PATH . '/vendor/idna_convert/src/Mso/IdnaConvert'
                ],
                'prefixes'            => [
                    'iMSCP' => LIBRARY_PATH . '/iMSCP',
                    'Crypt' => LIBRARY_PATH . '/vendor/phpseclib/Crypt',
                    'File'  => LIBRARY_PATH . '/vendor/phpseclib/File',
                    'Math'  => LIBRARY_PATH . '/vendor/phpseclib/Math',
                    'Net'   => LIBRARY_PATH . '/vendor/Net'
                ]
            ]
        ]);

        // Make application available through registry
        Registry::set('iMSCP_Application', $this);
    }

    /**
     * Retrieve current environment
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Retrieve autoloader instance
     *
     * @return \Zend_Loader_StandardAutoloader
     * @throws \Zend_Loader_Exception_InvalidArgumentException
     */
    public function getAutoloader()
    {
        if (NULL === $this->autoloader) {
            $this->autoloader = AutoloaderFactory::getRegisteredAutoloader(
                AutoloaderFactory::STANDARD_AUTOLOADER
            );
        }

        return $this->autoloader;
    }

    /**
     * Retrieve shared events manager instance
     *
     * @return EventsManager
     */
    public function getEventsManager()
    {
        if (NULL === $this->eventsManager) {
            $this->eventsManager = EventsManager::getInstance();
        }

        return $this->eventsManager;
    }

    /**
     * Retrieve application cache
     *
     * @return \Zend_Cache_Core
     * @throws \Zend_Cache_Exception
     */
    public function getCache()
    {
        if (NULL === $this->cache) {
            $this->cache = Cache::factory(
                'Core',
                # Make use of 'APC' backend if APC(u) is available, else
                # fallback to the 'File' backend
                extension_loaded('apc') && ini_get('apc.enabled') ? 'Apc' : 'File',
                [
                    'caching'                   => (PHP_SAPI != 'cli'),
                    // Cache is never flushed automatically (default)
                    'lifetime'                  => 0,
                    'automatic_serialization'   => true,
                    'automatic_cleaning_factor' => 0,
                    'ignore_user_abort'         => true
                ],
                // Options below are only relevant for the 'File' backend
                // (fallback backend)
                [
                    'file_locking'           => true,
                    'hashed_directory_level' => 0,
                    'cache_dir'              => CACHE_PATH,
                    'read_control'           => true
                ]
            );
        }

        return $this->cache;
    }

    /**
     * Retrieve main configuration
     *
     * @throws iMSCPException if the configuration is not available yet
     * @return ConfigFile
     */
    public function getConfig()
    {
        if (NULL === $this->config) {
            throw new iMSCPException('Main configuration not available yet');
        }

        return $this->config;
    }

    /**
     * Retrieve database configuration
     *
     * @throws iMSCPException if the configuration is not available yet
     * @return ConfigDb
     */
    public function getDbConfig()
    {
        if (NULL === $this->database) {
            throw new iMSCPException('Database configuration not available yet');
        }

        return $this->dbConfig;
    }

    /**
     * Retrieve database instance
     *
     * @throws iMSCPException if the Database instance is not available yet
     * @return Database
     */
    public function getDatabase()
    {
        if (NULL === $this->database) {
            throw new iMSCPException('Database instance not available yet');
        }

        return $this->database;
    }

    /**
     * Retrieve translator instance
     *
     * @throws iMSCPException if the translator instance is not available yet
     * @return Translator
     */
    public function getTranslator()
    {
        if (NULL === $this->translator) {
            throw new iMSCPException('Translator instance not available yet');
        }

        return $this->translator;
    }

    /**
     * Bootstrap application
     *
     * @param string $configFilePath Configuration file path
     * @return self
     * @throws DatabaseException
     * @throws \Zend_Cache_Exception
     * @throws \Zend_Locale_Exception
     * @throws \Zend_Session_Exception
     * @throws \Zend_Translate_Exception
     * @throws \iMSCP_Events_Manager_Exception
     * @throws iMSCPException
     */
    public function bootstrap($configFilePath)
    {
        if ($this->bootstrapped) {
            throw new iMSCPException('Already bootstrapped.');
        }

        $this->setErrorHandling();
        $this->setEncoding();
        $this->startSession();
        $this->loadCoreFunctions();
        $this->loadConfig($configFilePath);
        $this->setTimezone();
        $this->initDatabase();
        $this->mergeConfig();
        $this->setUserGuiProperties();
        $this->initLocalization();
        $this->initLayout();
        $this->loadNavigation();
        $this->loadPlugins();

        $this->getEventsManager()->dispatch(Events::onAfterApplicationBootstrap, ['context' => $this]);
        $this->bootstrapped = true;
        return $this;
    }

    /**
     * Set errors handling
     *
     * @return void
     */
    protected function setErrorHandling()
    {
        // Set default exception handler
        Registry::set('exceptionHandler', new ExceptionHandler());

        ini_set('log_errors', 1);
        //ini_set('error_log', GUI_ROOT_DIR . '/data/logs/errors.log');
        ini_set('display_errors', 1);

        if ($this->getEnvironment() == 'production') {
            error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);
            return;
        }

        error_reporting(E_ALL);
    }

    /**
     * Set internal encoding
     *
     * @throws iMSCPException if mbstring extension is not available
     * @return void
     */
    protected function setEncoding()
    {
        ini_set('default_charset', 'UTF-8');

        if (!extension_loaded('mbstring')) {
            throw new iMSCPException('mbstring extension not available');
        }

        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
    }

    /**
     * Load core functions
     *
     * @return void
     */
    public function loadCoreFunctions()
    {
        require_once LIBRARY_PATH . '/Functions/Admin.php';
        require_once LIBRARY_PATH . '/Functions/Client.php';
        require_once LIBRARY_PATH . '/Functions/Counting.php';
        require_once LIBRARY_PATH . '/Functions/Email.php';
        require_once LIBRARY_PATH . '/Functions/Input.php';
        require_once LIBRARY_PATH . '/Functions/Intl.php';
        require_once LIBRARY_PATH . '/Functions/Layout.php';
        require_once LIBRARY_PATH . '/Functions/Login.php';
        require_once LIBRARY_PATH . '/Functions/Reseller.php';
        require_once LIBRARY_PATH . '/Functions/Shared.php';
        require_once LIBRARY_PATH . '/Functions/SoftwareInstaller.php';
        require_once LIBRARY_PATH . '/Functions/Statistics.php';
        require_once LIBRARY_PATH . '/Functions/View.php';
    }

    /**
     * Load config
     *
     * @param string $configFilePath Main configuration file path
     * @return void
     * @throws \Zend_Cache_Exception
     * @throws iMSCPException
     */
    protected function loadConfig($configFilePath)
    {
        if ($this->config = $this->getCache()->load('iMSCP_Config')) {
            clearstatcache(true, $configFilePath);

            if (filemtime($configFilePath) == $this->config['__filemtime__']) {
                // Make main configuration available through registry (bc)
                Registry::set('config', $this->config);
                return;
            }

            // Remove all cache entries
            $this->getCache()->clean(Cache::CLEANING_MODE_ALL);
        } else {
            // Remove all cache entries
            $this->getCache()->clean(Cache::CLEANING_MODE_ALL);
        }

        $this->config = new ConfigFile($configFilePath);

        // Template root directory
        $this->config['ROOT_TEMPLATE_PATH'] = GUI_ROOT_DIR . '/themes/' . $this->config['USER_INITIAL_THEME'];

        // Set the isp logos path
        $this->config['ISP_LOGO_PATH'] = '/ispLogos';

        // FIXME to be removed
        $this->config['HTML_CHECKED'] = ' checked';
        $this->config['HTML_DISABLED'] = ' disabled';
        $this->config['HTML_READONLY'] = ' readonly';
        $this->config['HTML_SELECTED'] = ' selected';

        // Default Language (if not overriden by admin)
        $this->config['USER_INITIAL_LANG'] = Locale::BROWSER;

        // Session timeout in minutes
        $this->config['SESSION_TIMEOUT'] = 30;

        // SQL variables
        $this->config['MAX_SQL_DATABASE_LENGTH'] = 64;
        $this->config['MAX_SQL_USER_LENGTH'] = 16;
        $this->config['MAX_SQL_PASS_LENGTH'] = 32;

        // Captcha background color
        $this->config['LOSTPASSWORD_CAPTCHA_BGCOLOR'] = [176, 222, 245];
        // Captcha text color
        $this->config['LOSTPASSWORD_CAPTCHA_TEXTCOLOR'] = [1, 53, 920];
        // Captcha imagewidth
        $this->config['LOSTPASSWORD_CAPTCHA_WIDTH'] = 276;
        // Captcha imagehigh
        $this->config['LOSTPASSWORD_CAPTCHA_HEIGHT'] = 30;

        /**
         * Captcha ttf fontfiles (have to be under compatible open source license)
         */
        $this->config['LOSTPASSWORD_CAPTCHA_FONTS'] = [
            'FreeMono.ttf', 'FreeMonoBold.ttf', 'FreeMonoBoldOblique.ttf', 'FreeMonoOblique.ttf', 'FreeSans.ttf',
            'FreeSansBold.ttf', 'FreeSansBoldOblique.ttf', 'FreeSansOblique.ttf', 'FreeSerif.ttf',
            'FreeSerifBold.ttf', 'FreeSerifBoldItalic.ttf', 'FreeSerifItalic.ttf'
        ];

        /**
         * The following settings can be overridden via the control panel - (admin/settings.php)
         */

        // Domain rows pagination
        $this->config['DOMAIN_ROWS_PER_PAGE'] = 10;

        // Enable or disable support system
        $this->config['IMSCP_SUPPORT_SYSTEM'] = 1;

        // Enable or disable lost password support
        $this->config['LOSTPASSWORD'] = 1;

        // Uniq keys timeout in minutes
        $this->config['LOSTPASSWORD_TIMEOUT'] = 30;

        // Enable/disable countermeasures for bruteforce and dictionary attacks
        $this->config['BRUTEFORCE'] = 1;

        // Enable/disable waiting time between login/captcha attempts
        $this->config['BRUTEFORCE_BETWEEN'] = 1;

        // Max login/captcha attempts before waiting time
        $this->config['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'] = 2;

        // Waiting time between login/captcha attempts
        $this->config['BRUTEFORCE_BETWEEN_TIME'] = 30;

        // Blocking time in minutes
        $this->config['BRUTEFORCE_BLOCK_TIME'] = 15;

        // Max login attempts before blocking time
        $this->config['BRUTEFORCE_MAX_LOGIN'] = 5;

        // Max captcha attempts before blocking time
        $this->config['BRUTEFORCE_MAX_CAPTCHA'] = 5;

        // Enable or disable maintenance mode
        // 1: Maintenance mode enabled
        // 0: Maintenance mode disabled
        $this->config['MAINTENANCEMODE'] = 0;

        // Minimum password chars
        $this->config['PASSWD_CHARS'] = 6;

        // Enable or disable strong passwords
        // 1: Strong password enabled
        // 0: Strong password disabled
        $this->config['PASSWD_STRONG'] = 1;

        /**
         * Logging Mailer default level (messages sent to DEFAULT_ADMIN_ADDRESS)
         *
         * 0                    : No logging
         * E_USER_ERROR (256)   : errors are logged
         * E_USER_WARNING (512) : Warnings and errors are logged
         * E_USER_NOTICE (1024) : Notice, warnings and errors are logged
         *
         * Note: PHP's E_USER_* constants are used for simplicity.
         */
        $this->config['LOG_LEVEL'] = E_USER_ERROR;

        // Creation of abuse, hostmaster, postmaster and webmaster defaut mail account
        $this->config['CREATE_DEFAULT_EMAIL_ADDRESSES'] = 1;

        // Count default abuse, hostmaster, postmaster and webmaster mail accounts
        // in user mail accounts limit
        // 1: default mail accounts are counted
        // 0: default mail accounts are NOT counted
        $this->config['COUNT_DEFAULT_EMAIL_ADDRESSES'] = 0;

        // Protectdefault abuse, hostmaster, postmaster and webmaster mail accounts
        // against change and deletion
        $this->config['PROTECT_DEFAULT_EMAIL_ADDRESSES'] = 1;

        // Use hard mail suspension when suspending a domain:
        // 1: mail accounts are hard suspended (completely unreachable)
        // 0: mail accounts are soft suspended (passwords are modified so user can't access the accounts)
        $this->config['HARD_MAIL_SUSPENSION'] = 1;

        // Prevent external login (i.e. check for valid local referer) separated in admin, reseller and client.
        // This option allows to use external login scripts
        //
        // 1: prevent external login, check for referer, more secure
        // 0: allow external login, do not check for referer, less security (risky)
        $this->config['PREVENT_EXTERNAL_LOGIN_ADMIN'] = 1;
        $this->config['PREVENT_EXTERNAL_LOGIN_RESELLER'] = 1;
        $this->config['PREVENT_EXTERNAL_LOGIN_CLIENT'] = 1;

        // Automatic search for new version
        $this->config['CHECK_FOR_UPDATES'] = 0;
        $this->config['ENABLE_SSL'] = 1;

        // Converting some possible IDN to ACE
        $this->config['DEFAULT_ADMIN_ADDRESS'] = encode_idna($this->config->get('DEFAULT_ADMIN_ADDRESS'));
        $this->config['SERVER_HOSTNAME'] = encode_idna($this->config->get('SERVER_HOSTNAME'));
        $this->config['BASE_SERVER_VHOST'] = encode_idna($this->config->get('BASE_SERVER_VHOST'));
        $this->config['DATABASE_HOST'] = encode_idna($this->config->get('DATABASE_HOST'));

        // Server traffic settings
        $this->config['SERVER_TRAFFIC_LIMIT'] = 0;
        $this->config['SERVER_TRAFFIC_WARN'] = 0;

        // Store file last modification time to force reloading of configuration file if needed
        $this->config['__filemtime__'] = filemtime($configFilePath);

        if ($this->config['DEBUG']) {
            // Prevent caching when DEBUG mode is enabled
            $this->getCache()->setOption('caching', false);

            // Warn administrator that DEBUG mode is enabled and that resources caching isn't available
            $this->getEventsManager()->registerListener([
                Events::onAdminScriptStart, Events::onResellerScriptStart, Events::onClientScriptStart
            ], function () {
                if (is_xhr()
                    || ($_SESSION['user_type'] != 'admin'
                        && (!isset($_SESSION['logged_from_type']) || $_SESSION['logged_from_type'] != 'admin')
                    )
                ) {
                    return;
                }

                $this->getEventsManager()->registerListener(Events::onGeneratePageMessages, function (Event $e) {
                    /** @var \Zend_Controller_Action_Helper_FlashMessenger $flashMessenger */
                    $flashMessenger = $e->getParam('flashMessenger');
                    $flashMessenger->addMessage(
                        tr("The DEBUG mode is currently enabled, making resources caching unavailable."), 'static_warning'
                    );
                    $flashMessenger->addMessage(
                        tr("You can disable the DEBUG mode in the /etc/imscp/imscp.conf file."), 'static_warning'
                    );
                });
            });
        }

        // Make main configuration available through registry (bc)
        Registry::set('config', $this->config);
    }

    /**
     * Sets timezone
     *
     * @throws iMSCPException
     * @return void
     */
    protected function setTimezone()
    {
        $config = $this->getConfig();
        $timezone = $config['TIMEZONE'] != '' ? $config['TIMEZONE'] : 'UTC';

        if (!@date_default_timezone_set($timezone)) {
            @date_default_timezone_set('UTC');
        }
    }

    /**
     * Establishes the connection to the database
     *
     * @return void
     * @throws DatabaseException
     * @throws \Zend_Cache_Exception
     * @throws iMSCPException if connection to the database cannot be established
     */
    protected function initDatabase()
    {
        try {
            $cache = $this->getCache();
            $config = $this->getConfig();
            $db_pass_key = $cache->load('iMSCP_DATABASE_KEY');
            $db_pass_iv = $cache->load('iMSCP_DATABASE_IV');

            if (empty($db_pass_key) || empty($db_pass_iv)) {
                eval(@file_get_contents($this->getConfig()['CONF_DIR'] . '/imscp-db-keys'));

                if (empty($db_pass_key) || empty($db_pass_iv)) {
                    throw new iMSCPException('Missing encryption key and/or initialization vector.');
                }

                $cache->save($db_pass_key, 'iMSCP_DATABASE_KEY');
                $cache->save($db_pass_iv, 'iMSCP_DATABASE_IV');
            }

            if (!($plainPasswd = $cache->load('DATABASE_PASSWORD_PLAIN'))) {
                $plainPasswd = Crypt::decryptRijndaelCBC($db_pass_key, $db_pass_iv, $config['DATABASE_PASSWORD']);
                $cache->save($plainPasswd, 'DATABASE_PASSWORD_PLAIN');
            }

            $this->database = Database::connect(
                $config['DATABASE_USER'], $plainPasswd, $config['DATABASE_TYPE'], $config['DATABASE_HOST'],
                $config['DATABASE_NAME']
            );
        } catch (\PDOException $e) {
            throw new DatabaseException(
                sprintf("Couldn't establish connection to the database: %s", $e->getMessage()), NULL, $e->getCode(), $e
            );
        }

        // Make the database instance available through registry (bc)
        Registry::set('db', $this->database);
    }

    /**
     * Merge configuration from database with main configuration
     *
     * Retrieves configuration from the database and merge it with the main
     * configuration. Database configuration parameters have higher precedence
     * over those from the main configuration.
     *
     * Resulting merge is put in cache unless DEBUG mode is enabled.
     *
     * @return void
     * @throws \Zend_Cache_Exception
     * @throws iMSCPException
     */
    protected function mergeConfig()
    {
        $cache = $this->getCache();

        if (!($this->dbConfig = $cache->load('iMSCP_DbConfig'))) {
            $this->dbConfig = new ConfigDb($this->getDatabase());
            $config = $this->getConfig();
            $config->merge($this->dbConfig);

            if (!$config['DEBUG']) {
                $cache->save($this->dbConfig, 'iMSCP_DbConfig');
                $cache->save($config, 'iMSCP_Config');
            }
        } else {
            $this->dbConfig->setDb($this->getDatabase());
        }

        // Make database configuration available through registry (bc)
        Registry::set('dbConfig', $this->dbConfig);
    }

    /**
     * Start the session
     *
     * @return void
     * @throws \Zend_Session_Exception
     * @throws iMSCPException if session directory is not writable
     */
    protected function startSession()
    {
        if (PHP_SAPI == 'cli') {
            return;
        }

        if (!is_writable(GUI_ROOT_DIR . '/data/sessions')) {
            throw new iMSCPException('The gui/data/sessions directory must be writable.');
        }

        Session::setOptions([
            'use_cookies'         => 'on',
            'use_only_cookies'    => 'on',
            'use_trans_sid'       => 'off',
            'strict'              => false,
            'remember_me_seconds' => 0,
            'name'                => 'iMSCP_Session',
            'gc_divisor'          => 100,
            'gc_maxlifetime'      => 1440,
            'gc_probability'      => 1,
            'save_path'           => GUI_ROOT_DIR . '/data/sessions'
        ]);

        Session::start();
    }

    /**
     * Set user's GUI properties
     *
     * @return void
     * @throws DatabaseException
     * @throws iMSCPException
     */
    protected function setUserGuiProperties()
    {
        if (PHP_SAPI == 'cli'
            || !isset($_SESSION['user_id'])
            || isset($_SESSION['logged_from'])
            || isset($_SESSION['logged_from_id'])
            || (isset($_SESSION['user_def_lang']) && isset($_SESSION['user_theme']))
        ) {
            return;
        }

        $config = $this->getConfig();
        $stmt = exec_query('SELECT lang, layout FROM user_gui_props WHERE user_id = ?', $_SESSION['user_id']);

        if ($stmt->rowCount()) {
            $row = $stmt->fetchRow();

            if ((empty($row['lang']) && empty($row['layout']))) {
                list($lang, $theme) = [$config['USER_INITIAL_LANG'], $config['USER_INITIAL_THEME']];
            } elseif (empty($row['lang'])) {
                list($lang, $theme) = [$config['USER_INITIAL_LANG'], $row['layout']];
            } elseif (empty($row['layout'])) {
                list($lang, $theme) = [$row['lang'], $config['USER_INITIAL_THEME']];
            } else {
                list($lang, $theme) = [$row['lang'], $row['layout']];
            }
        } else {
            list($lang, $theme) = [$config['USER_INITIAL_LANG'], $config['USER_INITIAL_THEME']];
        }

        $_SESSION['user_def_lang'] = $lang;
        $_SESSION['user_theme'] = $theme;
    }

    /**
     * Initialize localization
     *
     * @return void
     * @throws \Zend_Locale_Exception
     * @throws \Zend_Translate_Exception
     */
    protected function initLocalization()
    {
        if (PHP_SAPI == 'cli') {
            $locale = new Locale('en_GB');
        } else {
            try {
                $cache = $this->getCache();

                Locale::setCache($cache);
                Translator::setCache($cache);

                $locale = new Locale(Registry::set(
                    'user_def_lang', isset($_SESSION['user_def_lang']) ? $_SESSION['user_def_lang'] : Locale::BROWSER
                ));

                if ($locale == 'root') {
                    # Handle case where value from $_SESSION['user_def_lang']
                    # is erroneous and lead to root locale
                    $locale->setLocale('en_GB');
                }
            } catch (\Exception $e) {
                $locale = new Locale('en_GB');
            }
        }

        $localesRouting = [
            'bg' => 'bg_BG',
            'ca' => 'ca_es',
            'cs' => 'cs_CZ',
            'da' => 'da_DK',
            'de' => 'de_DE',
            'en' => 'en_GB',
            'es' => 'es_ES',
            'eu' => 'eu_ES',
            'fa' => 'fa_IR',
            'fi' => 'fi_FI',
            'fr' => 'fr_FR',
            'gl' => 'gl_ES',
            'hu' => 'hu_HU',
            'it' => 'it_IT',
            'ja' => 'ja_JP',
            'lt' => 'lt_LT',
            'nb' => 'nb_NO',
            'nl' => 'nl_NL',
            'pl' => 'pl_PL',
            'pt' => 'pt_PT',
            'ro' => 'ro_RO',
            'ru' => 'ru_RU',
            'sk' => 'sk_SK',
            'sv' => 'sv_SE',
            'th' => 'th_TH',
            'tr' => 'tr_TR',
            'uk' => 'uk_UA',
            'zh' => 'zh_CN'
        ];

        // Setup translator
        $this->translator = new Translator([
            'adapter' => 'gettext',
            'locale' => $locale,
            'content' => GUI_ROOT_DIR . '/i18n/locales',
            'disableNotices' => true,
            'scan' => Translator::LOCALE_DIRECTORY,
            # Fallbacks for languages without territory information
            # (eg: 'de' will be routed to 'de_DE')
            'route' => $localesRouting
        ]);

        // Locale fallbacks
        /** @noinspection PhpUndefinedMethodInspection */
        if (!$this->translator->isAvailable($locale->getLanguage()) && !$this->translator->isAvailable($locale)) {
            if (in_array($locale->getLanguage(), array_keys($localesRouting))) {
                $this->translator->getAdapter()->setLocale($localesRouting[$locale->getLanguage()]);
            } else {
                $this->translator->getAdapter()->setLocale('en_GB');
            }
        }

        // Make Zend_Locale and Zend_Translate available for i-MSCP core,
        // i-MSCP plugins and Zend libraries
        Registry::set('Zend_Locale', $locale);
        Registry::set('translator', Registry::set('Zend_Translate', $this->translator));
    }

    /**
     * Initialize layout
     *
     * @return void
     */
    protected function initLayout()
    {
        if (PHP_SAPI == 'cli' || is_xhr()) {
            return;
        }

        // Set layout color for the current environment (Must be donne as late as possible)
        $this->getEventsManager()->registerListener(
            [
                Events::onLoginScriptEnd,
                Events::onLostPasswordScriptEnd,
                Events::onAdminScriptEnd,
                Events::onResellerScriptEnd,
                Events::onClientScriptEnd
            ],
            'layout_init'
        );

        if (isset($_SESSION['user_logged'])) {
            return;
        }

        $this->getEventsManager()->registerListener(Events::onAfterSetIdentity, function () {
            unset($_SESSION['user_theme_color']);
        });
    }

    /**
     * Register callback to load navigation file
     *
     * @return void
     */
    protected function loadNavigation()
    {
        if (PHP_SAPI == 'cli' || is_xhr()) {
            return;
        }

        $this->getEventsManager()->registerListener(
            [
                Events::onAdminScriptStart,
                Events::onResellerScriptStart,
                Events::onClientScriptStart
            ],
            'layout_loadNavigation'
        );
    }

    /**
     * Load plugins
     *
     * @throws iMSCPException When a plugin cannot be loaded
     * @return void
     */
    protected function loadPlugins()
    {
        if (PHP_SAPI == 'cli') {
            return;
        }

        /** @var \iMSCP_Plugin_Manager $pluginManager */
        $pluginManager = Registry::set('pluginManager', new PluginManager($this->getConfig()['PLUGINS_DIR']));

        foreach ($pluginManager->pluginGetList() as $pluginName) {
            if ($pluginManager->pluginHasError($pluginName)) {
                continue;
            }

            if (!$pluginManager->pluginLoad($pluginName)) {
                throw new iMSCPException(sprintf("Couldn't load plugin: %s", $pluginName));
            }
        }
    }
}
