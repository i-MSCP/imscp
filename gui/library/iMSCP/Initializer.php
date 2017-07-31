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

use iMSCP\Crypt as Crypt;
use iMSCP_Config_Handler_Db as ConfigDb;
use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;
use Zend_Cache as Cache;
use Zend_Locale as Locale;
use Zend_Session as Session;
use Zend_Translate as Translator;
use Zend_Translate_Adapter_Array as TranslatorArray;

/**
 * Class iMSCP_Initializer
 */
class iMSCP_Initializer
{
    /**
     * @var ConfigFile
     */
    protected $config;

    /**
     * @var EventsManager
     */
    protected $eventManager;

    /**
     * @static boolean Flag indicating whether or not initialization has been already processed
     */
    protected static $initialized = false;

    /**
     * Runs initializer
     *
     * @throws iMSCP_Exception
     * @param string|ConfigFile $command Initializer method or an ConfigFile object
     * @param ConfigFile $config OPTIONAL ConfigFile object
     * @return iMSCP_Initializer
     */
    public static function run($command = 'processAll', ConfigFile $config = NULL)
    {
        if (self::$initialized) {
            throw new iMSCPException('Already initialized.');
        }

        if ($command instanceof ConfigFile) {
            $config = $command;
            $command = 'processAll';
        }

        if ($command == 'processAll' && PHP_SAPI == 'cli') {
            $command = 'processCLI';
        } elseif (is_xhr()) {
            $command = 'processAjax';
        }

        $initializer = new self(is_object($config) ? $config : new ConfigFile());
        $initializer->$command();
        return $initializer;
    }

    /**
     * Singleton - Make new unavailable
     *
     * @param ConfigFile $config
     */
    protected function __construct(ConfigFile $config)
    {
        $this->config = Registry::set('config', $config);
        $this->eventManager = EventsManager::getInstance();
    }

    /**
     * Make clone unavailable
     */
    protected function __clone()
    {

    }

    /**
     * Executes all of the available initialization routines for normal context
     *
     * @return void
     */
    protected function processAll()
    {
        $this->setErrorReporting();
        $this->initializeSession();
        $this->initializeDatabase();
        $this->loadConfig();
        $this->setInternalEncoding();
        $this->setCharset();
        $this->setTimezone();
        $this->initializeUserGuiProperties();
        $this->initializeLocalization();
        $this->initializeLayout();
        $this->initializeNavigation();
        $this->initializePlugins();
        $this->eventManager->dispatch(Events::onAfterInitialize, ['context' => $this]);
        self::$initialized = true;
    }

    /**
     * Executes all of the available initialization routines for AJAX context
     *
     * @return void
     */
    protected function processAjax()
    {
        $this->setErrorReporting();
        $this->initializeSession();
        $this->initializeDatabase();
        $this->loadConfig();
        $this->setInternalEncoding();
        $this->setCharset();
        $this->setTimezone();
        $this->initializeUserGuiProperties();
        $this->initializeLocalization();
        $this->initializePlugins();
        $this->eventManager->dispatch(Events::onAfterInitialize, ['context' => $this]);
        self::$initialized = true;
    }

    /**
     * Executes all of the available initialization routines for CLI context
     *
     * @return void
     */
    protected function processCLI()
    {
        $this->setErrorReporting();
        $this->initializeDatabase();
        $this->loadConfig();
        $this->setInternalEncoding();
        $this->setCharset();
        $this->setTimezone();
        $this->initializeLocalization(); // Needed for rebuilt of languages index
        $this->eventManager->dispatch(Events::onAfterInitialize, ['context' => $this]);
        self::$initialized = true;
    }

    /**
     * Set internal encoding
     *
     * @return void
     */
    protected function setInternalEncoding()
    {
        if (!extension_loaded('mbstring')) {
            return;
        }

        mb_internal_encoding('UTF-8');
        @mb_regex_encoding('UTF-8');
    }

    /**
     * Sets PHP error reporting
     *
     * @return void
     */
    protected function setErrorReporting()
    {
        if (!$this->config['DEBUG']) {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        return;

        #ini_set('display_errors', 0);

        // In any case, write error logs in data/logs/errors.log
        // FIXME Disabled as long file is not rotated
        //ini_set('log_errors', 1);
        //ini_set('error_log', $this->_config->GUI_ROOT_DIR . '/data/logs/errors.log');
    }

    /**
     * Initialize layout
     *
     * @return void
     */
    protected function initializeLayout()
    {
        // Set layout color for the current environment (Must be donne at end)
        $this->eventManager->registerListener(
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

        $this->eventManager->registerListener(
            Events::onAfterSetIdentity, function () {
            unset($_SESSION['user_theme_color']);
        });
    }

    /**
     * Initialize the session
     *
     * @throws iMSCP_Exception in case session directory is not writable
     * @return void
     */
    protected function initializeSession()
    {
        $sessionDir = utils_normalizePath($this->config['GUI_ROOT_DIR'] . '/data/sessions');

        if (!is_writable($sessionDir)) {
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
            'save_path'           => $sessionDir
        ]);

        Session::start();
    }

    /**
     * Establishes the connection to the database server
     *
     * This methods establishes the default connection to the database server by using configuration parameters that
     * come from the basis configuration object and then, register the {@link iMSCP_Database} instance in the
     * {@link iMSCP_Registry} for further usage.
     *
     * A PDO instance is also registered in the registry for further usage.
     *
     * @throws iMSCP_Exception_Database|iMSCP_Exception
     * @return void
     */
    protected function initializeDatabase()
    {
        try {
            $db_pass_key = $db_pass_iv = '';
            eval(@file_get_contents($this->config['CONF_DIR'] . '/imscp-db-keys'));

            if (empty($db_pass_key) || empty($db_pass_iv)) {
                throw new iMSCPException('Missing encryption key and/or initialization vector.');
            }

            $connection = Database::connect(
                $this->config['DATABASE_USER'],
                Crypt::decryptRijndaelCBC($db_pass_key, $db_pass_iv, $this->config['DATABASE_PASSWORD']),
                $this->config['DATABASE_TYPE'],
                $this->config['DATABASE_HOST'],
                $this->config['DATABASE_NAME']
            );
        } catch (PDOException $e) {
            throw new DatabaseException(
                sprintf("Couldn't establish connection to the database: %s", $e->getMessage()), NULL, $e->getCode(), $e
            );
        }

        // Register Database instance in registry for further usage.
        Registry::set('db', $connection);
    }

    /**
     * Sets default charset
     *
     * @return void
     */
    protected function setCharset()
    {
        ini_set('default_charset', 'UTF-8');
    }

    /**
     * Sets timezone
     *
     * @throws iMSCP_Exception
     * @return void
     */
    protected function setTimezone()
    {
        $timezone = $this->config['TIMEZONE'] != '' ? $this->config['TIMEZONE'] : 'UTC';

        if (!@date_default_timezone_set($timezone)) {
            @date_default_timezone_set('UTC');
        }
    }

    /**
     * Load configuration parameters from the database
     *
     * Retrieves all the parameters from the database and merge them with the
     * main configuration object. Parameters that exists in the main
     * configuration object are replaced by those that come from the database.
     *
     * The main configuration object contains parameters that come from the
     * i-mscp.conf configuration file or any parameter defined in the
     * {@link environment.php} file.
     *
     * @throws iMSCP_Exception
     * @return void
     */
    protected function loadConfig()
    {
        $pdo = Database::getRawInstance();

        if (is_readable(DBCONFIG_CACHE_FILE_PATH)) {
            if (!$this->config['DEBUG']) {
                $dbConfig = unserialize(file_get_contents(DBCONFIG_CACHE_FILE_PATH));
                $dbConfig->setDb($pdo);
            } else {
                @unlink(DBCONFIG_CACHE_FILE_PATH);
                goto FORCE_DBCONFIG_RELOAD;
            }
        } else {
            FORCE_DBCONFIG_RELOAD:
            $dbConfig = new ConfigDb($pdo);
            if (!$this->config['DEBUG'] && PHP_SAPI != 'cli') {
                @file_put_contents(DBCONFIG_CACHE_FILE_PATH, serialize($dbConfig), LOCK_EX);
            }
        }

        $this->config->merge($dbConfig);
        Registry::set('dbConfig', $dbConfig);
    }

    /**
     * Load user's GUI properties in session
     *
     * @return void
     */
    protected function initializeUserGuiProperties()
    {
        if (!isset($_SESSION['user_id'])
            || isset($_SESSION['logged_from'])
            || isset($_SESSION['logged_from_id'])
            || (isset($_SESSION['user_def_lang']) && isset($_SESSION['user_theme']))
        ) {
            return;
        }

        $stmt = exec_query('SELECT lang, layout FROM user_gui_props WHERE user_id = ?', $_SESSION['user_id']);

        if ($stmt->rowCount()) {
            $row = $stmt->fetchRow();

            if ((empty($row['lang']) && empty($row['layout']))) {
                list($lang, $theme) = [$this->config['USER_INITIAL_LANG'], $this->config['USER_INITIAL_THEME']];
            } elseif (empty($row['lang'])) {
                list($lang, $theme) = [$this->config['USER_INITIAL_LANG'], $row['layout']];
            } elseif (empty($row['layout'])) {
                list($lang, $theme) = [$row['lang'], $this->config['USER_INITIAL_THEME']];
            } else {
                list($lang, $theme) = [$row['lang'], $row['layout']];
            }
        } else {
            list($lang, $theme) = [$this->config['USER_INITIAL_LANG'], $this->config['USER_INITIAL_THEME']];
        }

        $_SESSION['user_def_lang'] = $lang;
        $_SESSION['user_theme'] = $theme;
    }

    /**
     * Initialize localization
     *
     * FIXME:  Remove registry 'translator' item; It is currently kept for backward compatibility with plugins
     *
     * @return void
     */
    protected function initializeLocalization()
    {
        if (PHP_SAPI == 'cli') {
            $locale = new Locale('en_GB');
        } else {
            try {
                // Setup cache for localization and translation
                $cache = Cache::factory(
                    'Core',
                    # Make use of 'APC' backend if APC(U) is available, else fallback to 'File' backend
                    extension_loaded('apc') ? 'Apc' : 'File',
                    [
                        'caching'                   => !$this->config['DEBUG'],
                        'lifetime'                  => 0, // Translation cache is never flushed automatically
                        'automatic_serialization'   => true,
                        'automatic_cleaning_factor' => 0,
                        'ignore_user_abort'         => true
                    ],
                    // Only for 'File' backend
                    [
                        'file_locking'           => false,
                        'hashed_directory_level' => 2,
                        'cache_dir'              => CACHE_PATH . '/translations',
                        'read_control'           => false
                    ]
                );

                Locale::setCache($cache);
                Translator::setCache($cache);

                $locale = new Locale(Registry::set(
                    'user_def_lang',
                    isset($_SESSION['user_def_lang']) ? $_SESSION['user_def_lang'] : Zend_Locale::BROWSER
                ));
                
                if($locale == 'root') {
                    # Handle case where value from $_SESSION['user_def_lang'] is erronous and lead to root locale
                    $locale->setLocale('en_GB');
                }
            } catch (Exception $e) {
                $locale = new Locale('en_GB');
            }
        }

        // Setup translator
        $translator = new Translator([
            'adapter'        => 'gettext',
            'locale'         => $locale,
            'content'        => $this->config['GUI_ROOT_DIR'] . '/i18n/locales',
            'disableNotices' => true,
            'scan'           => Translator::LOCALE_DIRECTORY,
            # Fallbacks for languages without territory information (eg: 'de' will be routed to 'de_DE')
            'route'          => [
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
            ]
        ]);

        // Setup additional translator for Zend_Validate
        $zendTranslator = new TranslatorArray([
            'content'        => LIBRARY_PATH . '/vendor/Zend/resources/languages',
            'disableNotices' => true,
            'locale'         => $locale,
            'scan'           => Translator::LOCALE_DIRECTORY
        ]);

        if ($zendTranslator->isAvailable($locale->getLanguage()) || $zendTranslator->isAvailable($locale)) {
            $translator->getAdapter()->addTranslation(['content' => $zendTranslator]);
        }

        // Make Zend_Locale and Zend_Translate available for i-MSCP core, i-MSCP plugins and Zend libraries
        Registry::set('Zend_Locale', $locale);
        Registry::set('translator', Registry::set('Zend_Translate', $translator));
    }

    /**
     * Register callback to load navigation file
     *
     * @return void
     */
    protected function initializeNavigation()
    {
        $this->eventManager->registerListener(
            [
                Events::onAdminScriptStart,
                Events::onResellerScriptStart,
                Events::onClientScriptStart
            ],
            'layout_loadNavigation'
        );
    }

    /**
     * Initialize plugins
     *
     * @throws iMSCP_Exception When a plugin cannot be loaded
     * @return void
     */
    protected function initializePlugins()
    {
        $pluginManager = Registry::set('pluginManager', new PluginManager($this->config['PLUGINS_DIR']));

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
