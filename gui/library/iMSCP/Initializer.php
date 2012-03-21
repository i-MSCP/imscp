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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Initializer
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2012 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Initializer class.
 *
 * The initializer is responsible for processing the i-MSCP configuration, such as setting the include_path, database
 * and more.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Initializer
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.1.11
 */
class iMSCP_Initializer
{
	/**
	 * iMSCP_Config_Handler instance used by this class.
	 *
	 * @var iMSCP_Config_Handler_File
	 */
	private $_config;

	/**
	 * Initialization status.
	 *
	 * @staticvar boolean
	 */
	private static $_initialized = false;

	/**
	 * Runs initializer
	 *
	 * By default, this will invoke the {@link _processAll}  or {@link _processCLI} methods, which simply executes all
	 * of the initialization routines for execution context. Alternately, you can specify explicitly which
	 * initialization methods you want:
	 *
	 * <i>Usage example:</i>
	 * <code>
	 *	iMSCP_Initializer::run('_setIncludePath')
	 * </code>
	 *
	 * This is useful if you only want the include_path path initialized, without incurring the overhead of completely
	 * loading the entire environment.
	 *
	 * @throws iMSCP_Exception
	 * @param string|iMSCP_Config_Handler_File $command Initializer method to be executed or an iMSCP_Config_Handler_File
	 *													object
	 * @param iMSCP_Config_Handler_File $config			OPTIONAL iMSCP_Config_Handler_File object
	 * @return iMSCP_Initializer
	 */
	public static function run($command = '_processAll', iMSCP_Config_Handler_File $config = null)
	{
		if (!self::$_initialized) {
			if ($command instanceof iMSCP_Config_Handler_File) {
				$config = $command;
				$command = '_processAll';
			}

			// Overrides _processAll command for CLI interface
			if ($command == '_processAll' && PHP_SAPI == 'cli') {
				$command = '_processCLI';
			}

			$initializer = new self(is_object($config) ? $config : new iMSCP_Config_Handler_File());
			$initializer->$command();

		} else {
			throw new iMSCP_Exception('i-MSCP is already fully initialized.');
		}

		return $initializer;
	}

	/**
	 * Singleton - Make new unavailbale.
	 *
	 * Create a new Initializer instance that references the given {@link iMSCP_Config_Handler_File} instance.
	 *
	 * @param iMSCP_Config_Handler|iMSCP_Config_Handler_File $config
	 * @return iMSCP_Initializer
	 */
	protected function __construct(iMSCP_Config_Handler $config)
	{
		// Register config object in registry for further usaeg.
		$this->_config = iMSCP_Registry::set('config', $config);
	}

	/**
	 * Singleton - Make clone unavailable.
	 */
	protected function __clone()
	{

	}

	/**
	 * Executes all of the available initialization routines.
	 *
	 * @return void
	 */
	protected function _processAll()
	{
		// Set display errors
		$this->_setDisplayErrors();

		// Set additionally iMSCP_Exception_Writer observers
		$this->_setExceptionWriters();

		// Include path
		$this->_setIncludePath();

		// initialize the debug bar
		$this->initializeDebugBar();

		// Sets encryption keys
		$this->_setEncryptionKeys();

		// Establish the connection to the database
		$this->_initializeDatabase();

		// Se encoding (Both PHP and database)
		$this->_setEncoding();

		// Set timezone
		$this->_setTimezone();

		// Load all the configuration parameters from the database and merge
		// it to our basis configuration object
		$this->_processConfiguration();

		// Initialize output buffering
		$this->_initializeOutputBuffering();

		// Create or restore the session
		$this->_initializeSession();

		$this->_checkForDatabaseUpdate();

		// Initialize user's GUI properties
		$this->_initializeUserGuiProperties();

		// Initialize internationalization libraries
		$this->_initializeLocalization();

		// Initialize Layout
		$this->_initializeLayout();

		$this->_initializeNavigation();

		// Initialize plugin (Action)
		$this->_initializeActionPlugins();

		// Run after initialize callbacks (will be changed later)
		$this->_afterInitialize();

		self::$_initialized = true;
	}

	/**
	 * Executes all of the available initialization routines for CLI interface.
	 *
	 * @return void
	 */
	protected function _processCLI()
	{
		// Sets encryption keys
		$this->_setEncryptionKeys();

		// Establish the connection to the database
		$this->_initializeDatabase();

		// Sets encoding (Both PHP and database)
		$this->_setEncoding();

		// Load all the configuration parameters from the database and merge it to our basis configuration object
		$this->_processConfiguration();

		self::$_initialized = true;
	}

	/**
	 * Sets the PHP display_errors parameter.
	 *
	 * @return void
	 */
	protected function _setDisplayErrors()
	{
		$this->_config->DEBUG ? ini_set('display_errors', 1) : ini_set('display_errors', 0);
	}

	/**
	 * Sets additional writers or exception handler
	 *
	 * @return void
	 * @todo Automatic detection of new writers based on the namespace
	 */
	protected function _setExceptionWriters()
	{
		$exceptionHandler = iMSCP_Exception_Handler::getInstance();

		$writerObservers = explode(',', $this->_config->GUI_EXCEPTION_WRITERS);
		$writerObservers = array_map('trim', $writerObservers);
		$writerObservers = array_map('strtolower', $writerObservers);

		if (in_array('mail', $writerObservers)) {
			$admin_email = $this->_config->DEFAULT_ADMIN_ADDRESS;

			if (!empty($admin_email)) {
				$exceptionHandler->attach(new iMSCP_Exception_Writer_Mail($admin_email));
			}
		}
	}

	/**
	 * Sets include path.
	 *
	 * Sets the PHP include_path. Duplicates entries are removed.
	 *
	 * @return void
	 */
	protected function _setIncludePath()
	{
		// Ensure library/ and vendor/ are on include_path
		set_include_path(
			implode(
				PATH_SEPARATOR,
				array_unique(
					array(
						LIBRARY_PATH,
						LIBRARY_PATH . '/vendor',
						DEFAULT_INCLUDE_PATH)
				)
			)
		);
	}

	/**
	 * Initialize the session.
	 *
	 * @return void
	 */
	protected function _initializeSession()
	{
		if (!is_writable($this->_config->GUI_ROOT_DIR . '/data/sessions')) {
			throw new iMSCP_Exception('The GUI `gui/data/sessions` directory must be writable.');
		}

		require_once 'Zend/Session.php';

		Zend_Session::setOptions(
			array(
				'use_cookies' => 'on',
				'use_only_cookies' => 'on',
				'use_trans_sid' => 'off',
				'strict' => false,
				'remember_me_seconds' => 0,
				'name' => 'iMSCP_Session',
				'gc_divisor' => 100,
				'gc_maxlifetime' => 1440,
				'gc_probability' => 1,
				'save_path' => $this->_config->GUI_ROOT_DIR . '/data/sessions')
		);

		Zend_Session::start();
	}

	/**
	 * Load user's GUI properties in session.
	 *
	 * @return void
	 * @todo quick fix that will be improved later (see #156 on i-MSCP)
	 */
	protected function _initializeUserGuiProperties()
	{
		if (isset($_SESSION['user_id']) && !isset($_SESSION['logged_from']) && !isset($_SESSION['logged_from_id'])) {
			$query = "SELECT `lang`, `layout` FROM `user_gui_props` WHERE `user_id` = ?";
			$stmt = exec_query($query, $_SESSION['user_id']);

			if (!$stmt->rowCount() || (empty($stmt->fields['lang']) && empty($stmt->fields['layout']))) {
				$properties = array($this->_config->USER_INITIAL_LANG, $this->_config->USER_INITIAL_THEME);
			} elseif (empty($stmt->fields['lang'])) {
				$properties = array($this->_config->USER_INITIAL_LANG, $stmt->fields['layout']);
			} elseif (empty($stmt->fields['layout'])) {
				$properties = array($stmt->fields['lang'], $this->_config->USER_INITIAL_THEME);
			} else {
				$properties = array($stmt->fields['lang'], $stmt->fields['layout']);
			}

			$_SESSION['user_def_lang'] = $properties[0];
			$_SESSION['user_theme'] = $properties[1];
		}
	}

	/**
	 * Sets encryption keys.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4405 (on svn repository)
	 * @throws iMSCP_Exception When key and/or initialization vector was not generated
	 * @return void
	 */
	protected function _setEncryptionKeys()
	{
		$db_pass_key = $db_pass_iv = '';

		eval(@file_get_contents($this->_config->CONF_DIR . '/imscp-db-keys'));

		if (!empty($db_pass_key) && !empty($db_pass_iv)) {
			iMSCP_Registry::set('MCRYPT_KEY', $db_pass_key);
			iMSCP_Registry::set('MCRYPT_IV', $db_pass_iv);
		} else {
			throw new iMSCP_Exception('Database key and/or initialization vector was not generated.');
		}
	}

	/**
	 * Establishes the connection to the database server.
	 *
	 * This methods establishes the default connection to the database server by using configuration parameters that
	 * come from the basis configuration object and then, register the {@link iMSCP_Database} instance in the
	 * {@link iMSCP_Registry} for further usage.
	 *
	 * A PDO instance is also registered in the registry for further usage.
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 * @todo Remove global variable
	 */
	protected function _initializeDatabase()
	{
		try {
			$connection = iMSCP_Database::connect(
				$this->_config->DATABASE_USER,
				decrypt_db_password($this->_config->DATABASE_PASSWORD),
				$this->_config->DATABASE_TYPE,
				$this->_config->DATABASE_HOST, $this->_config->DATABASE_NAME);

		} catch (PDOException $e) {
			throw new iMSCP_Exception_Database('Unable to establish the connection to the database. ' .
				'SQL returned: ' . $e->getMessage());
		}

		// Register Database instance in registry for further usage.
		iMSCP_Registry::set('db', $connection);
	}

	/**
	 * Sets encoding.
	 *
	 * This methods set encoding for both communication database and PHP.
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 * @todo add a specific listener that will operate on the 'onAfterConnection'
	 * event of the database component and that will set the charset.
	 */
	protected function _setEncoding()
	{
		// Always send the following header:
		// Content-type: text/html; charset=UTF-8'
		// Note: This header can be overrided by calling the header() function
		ini_set('default_charset', 'UTF-8');

		// Switch optionally to utf8 based communication with the database
		if (isset($this->_config->DATABASE_UTF8) && $this->_config->DATABASE_UTF8 == 'yes') {
			/** @var $db iMSCP_Database */
			$db = iMSCP_Registry::get('db');

			if (!$db->execute('SET NAMES `utf8`')) {
				throw new iMSCP_Exception(
					'Error: Unable to set charset for database communication. SQL returned: ' . $db->errorMsg());
			}
		}
	}

	/**
	 * Sets timezone.
	 *
	 * This method ensures that the timezone is set to avoid any error with PHP versions equal or later than version 5.3.x
	 *
	 * This method acts by checking the `date.timezone` value, and sets it to the value from the i-MSCP PHP_TIMEZONE
	 * parameter if exists and if it not empty or to 'UTC' otherwise. If the timezone identifier is invalid, an
	 * {@link iMSCP_Exception} exception is raised.
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 */
	protected function _setTimezone()
	{
		// Timezone is not set in the php.ini file ?
		if (ini_get('date.timezone') == '') {
			$timezone = (isset($this->_config->PHP_TIMEZONE) && $this->_config->PHP_TIMEZONE != '')
				? $this->_config->PHP_TIMEZONE : 'UTC';

			if (!date_default_timezone_set($timezone)) {
				throw new iMSCP_Exception(
					'Invalid timezone identifier set in your imscp.conf file. Please fix this error and re-run the ' .
						'imscp-setup script to fix the value in all your customers\' php.ini files. The list of valid ' .
						'identifiers is available at the <a href="http://www.php.net/manual/en/timezones.php" ' .
						'target="_blank">PHP Homepage</a> .');
			}
		}
	}

	/**
	 * Load configuration parameters from the database.
	 *
	 * This function retrieves all the parameters from the database and merge them with the basis configuration object.
	 *
	 * Parameters that exists in the basis configuration object will be replaced by them that come from the database.
	 * The basis configuration object contains parameters that come from the i-mscp.conf configuration file or any
	 * parameter defined in the {@link environment.php} file.
	 *
	 * @return void
	 */
	protected function _processConfiguration()
	{
		/** @var $pdo iMSCP_Database */
		$pdo = iMSCP_Database::getRawInstance();

		// Creating new Db configuration handler.
		$dbConfig = new iMSCP_Config_Handler_Db($pdo);

		// Now, we can override our basis configuration object with parameter that come from the database
		$this->_config->replaceWith($dbConfig);

		// Finally, we register the iMSCP_Config_Handler_Db for shared access
		iMSCP_Registry::set('dbConfig', $dbConfig);
	}

	/**
	 * Initialize the PHP output buffering / spGzip filter.
	 *
	 * Note: The hight level such as 8 and 9 for compression are not recommended for performances reasons. The obtained
	 * gain with these levels is very small compared to the intermediate level such as 6 or 7.
	 *
	 * @return void
	 */
	protected function _initializeOutputBuffering()
	{
		if (isset($this->_config->COMPRESS_OUTPUT) && $this->_config->COMPRESS_OUTPUT) {
			// Create a new filter that will be applyed on the buffer output
			/** @var $filter iMSCP_Filter_Compress_Gzip*/
			$filter = iMSCP_Registry::set(
				'bufferFilter',
				new iMSCP_Filter_Compress_Gzip(iMSCP_Filter_Compress_Gzip::FILTER_BUFFER));

			// Show compression information in HTML comment ?
			if (isset($this->_config->SHOW_COMPRESSION_SIZE) && !$this->_config->SHOW_COMPRESSION_SIZE) {
				$filter->compressionInformation = false;
			}

			// Start the buffer and attach the filter to him
			ob_start(array($filter, iMSCP_Filter_Compress_Gzip::CALLBACK_NAME));
		}
	}

	/**
	 * Initialize localization.
	 *
	 * Note: We are using the PHP-gettext library as gettext wrapper to be able to use all locales same if they are not
	 * installed on the server. In case the current locale is installed on the server, the navive gettext functions used.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return void
	 */
	protected function _initializeLocalization()
	{
		require_once 'vendor/php-gettext/gettext.inc';

		$locale = isset($_SESSION['user_def_lang']) ? $_SESSION['user_def_lang'] : $this->_config->USER_INITIAL_LANG;

		// Small fix for ar_AE locale
		if ($locale == 'ar') {
			$locale = 'ar_AE';
		}

		$checkedLocale = setlocale(
			LC_MESSAGES,
			array($locale . '.utf8', $locale . '.utf-8', $locale . '.UTF8', $locale . '.UTF-8')
		);

		$checkedLocale = (empty($checkedLocale)) ? $locale . '.utf8' : $checkedLocale;

		T_setlocale(LC_MESSAGES, $checkedLocale);

		if (locale_emulation()) {
			$domain = $locale;
		} else { // Small workaround related to #130
			$domain = i18n_getDomain($locale);
		}

		T_bindtextdomain($domain, $this->_config->GUI_ROOT_DIR . '/i18n/locales');
		T_bind_textdomain_codeset($domain, 'UTF-8');
		T_textdomain($domain);
	}

	/**
	 * Check for database update.
	 *
	 * @return void
	 */
	protected function _checkForDatabaseUpdate()
	{
		iMSCP_Events_Manager::getInstance()->registerListener(
			array(
				iMSCP_Events::onLoginScriptStart,
				iMSCP_Events::onBeforeSetIdentity
			)
			,
			function($event)
			{
				if (iMSCP_Update_Database::getInstance()->isAvailableUpdate()) {
					iMSCP_Registry::get('config')->MAINTENANCEMODE = true;

					/** @var $event iMSCP_Events_Event */
					if (($identity = $event->getParam('identity', null))) {
						if ($identity->admin_type != 'admin' &&
							(!isset($_SESSION['logged_from_type']) || $_SESSION['logged_from_type'] != 'admin')
						) {
							set_page_message(tr('Only administrators can login when maintenance mode is activated.'), 'error');
							redirectTo('index.php?admin=1');
						}
					}
				}
			}
		);
	}

	/**
	 * Initialize layout.
	 *
	 * @return void
	 */
	protected function _initializeLayout()
	{
		// Set template root directory
		iMSCP_pTemplate::setRootDir($this->_config->ROOT_TEMPLATE_PATH);

		$eventManager = iMSCP_Events_Manager::getInstance();

		// Set layout color for the current environment (Must be donne at end
		$eventManager->registerListener(
			array(
				iMSCP_Events::onLoginScriptEnd,
				iMSCP_Events::onLostPasswordScriptEnd,
				iMSCP_Events::onAdminScriptEnd,
				iMSCP_Events::onResellerScriptEnd,
				iMSCP_Events::onClientScriptEnd,
				iMSCP_Events::onOrderPanelScriptEnd,
				iMSCP_Events::onExceptionToBrowserEnd
			),
			'layout_setColor'
		);

		if (!isset($_SESSION['user_logged'])) {
			$callback = function($event)
			{
				unset($_SESSION['user_theme_color']);
			};

			$eventManager->registerListener(iMSCP_Events::onAfterSetIdentity, $callback);
		}
	}

	/**
	 * Register callback to load navigation file.
	 *
	 * @return void
	 */
	protected function _initializeNavigation()
	{
		iMSCP_Events_Manager::getInstance()->registerListener(
			array(
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart,
			),
			'layout_loadNavigation'
		);
	}

	/**
	 * Initialize Debug bar.
	 *
	 * Note: Each Debug bar plugin listens specfics events. They will auto-registered on the events manager by the debug
	 * bar component.
	 *
	 * @return void
	 */
	public function initializeDebugBar()
	{
		if (isset($this->_config->DEBUG) && intval($this->_config->DEBUG)) {
			iMSCP_Registry::set('debugBar', new iMSCP_Debug_Bar(iMSCP_Events_Manager::getInstance(),
				array(
					// Debug information about variables such as $_GET, $_POST...
					new iMSCP_Debug_Bar_Plugin_Variables(),
					// Debug information about script execution time
					new iMSCP_Debug_Bar_Plugin_Timer(),
					// Debug information about memory consumption
					new iMSCP_Debug_Bar_Plugin_Memory(),
					// Debug information about all included files
					new iMSCP_Debug_Bar_Plugin_Files(),
					// Debug information about all queries made during a script exection
					// and their execution time.
					new iMSCP_Debug_Bar_Plugin_Database())));
		}
	}

	/**
	 * Initialize action plugins.
	 *
	 * @return void
	 */
	protected function _initializeActionPlugins()
	{
		$pluginManager = new iMSCP_Plugin_Manager(PLUGINS_PATH);
		$pluginList = $pluginManager->getPluginList('Action');

		if (!empty($pluginList)) {
			$eventManager = iMSCP_Events_Manager::getInstance();

			foreach ($pluginList as $pluginName) {
				/** @var $plugin iMSCP_Plugin_Action */
				$plugin = $pluginManager->load('Action', $pluginName);
				if (null === $plugin) continue;
				$plugin->register($eventManager);
			}
		}

		// Register the plugin manager for further usage
		iMSCP_Registry::set('pluginManager', $pluginManager);
	}

	/**
	 * Fires the afterInitialize callbacks.
	 *
	 * @return void
	 */
	protected function _afterInitialize()
	{
		iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterInitialize, array('context' => $this));
	}
}
