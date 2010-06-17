<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * Class IspCP_Initializer
 *
 * The initializer is responsible for processing the IspCP configuration,
 * such as setting the include_path, initializing logging, database and
 * more.
 *
 * Note: Not Yet Used, testing in progress
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since 1.0.6
 * @version 1.0.0
 */
class IspCP_Initializer {

	/**
	 * IspCP_ConfigHandler instance used by this class
	 *
	 * @var IspCP_ConfigHandler
	 */
	private $_config;

	/**
	 * Initialization status
	 *
	 * @staticvar boolean
	 */
	private static $_initialized = false;

	/**
	 * Runs the initializer
	 *
	 * By default, this will invoke the {@link process_all} methods, which
	 * simply executes all of the initialization methods. Alternately, you can
	 * specify explicitly which initialization methods you want:
	 *
	 * <samp>
	 *	IspCP_Initializer::run('set_include_path')
	 * <samp>
	 *
	 * This is useful if you only want the include_path path initialized,
	 * without incurring the overhead of completely loading the entire
	 * environment.
	 *
	 * Note: Concept borrowed to the RoR framework
	 *
	 * @throw Exception
	 * @param string|IspCP_ConfigHandler $command Initializer method to be
	 * executed or an IspCP_ConfigHandler object
	 * @param IspCP_ConfigHandler $config Optional IspCP_ConfigHandler object
	 * @return The IspCP_Initializer instance
	 */
	public static function run($command = 'process_all',
		IspCP_ConfigHandler $config = null) {

		if(!self::$_initialized) {

			if($command instanceof IspCP_ConfigHandler) {
				$config = $command;
				$command = 'process_all';
			}

			$initializer = new self(
				is_object($config) ? $config : Config::getInstance()
			);

			$initializer->$command();

		} else {
			throw new Exception('Error: IspCP is already fully initialized!');
		}

		return $initializer;
	}

	/**
	 * Create a new Initializer instance that references the given
	 * {@link IspCP_ConfigHandler} instance
	 *
	 * @param IspCP_ConfigHandler IspCP_ConfigHandler instance
	 * @return void
	 */
	private function __construct($config) {

		$this->_config = IspCP_Registry::set('Config', $config);
	}

	/**
	 * Object of this class shouldn't be cloned
	 */
	private function __clone() {}

	/**
	 * Execute all of the available initialization routines
	 *
	 * @return void
	 */
	 protected function process_all() {

		// Check php version and availability of the Php Standard Library
		$this->check_php();

		// Initialize output buffering
		$this->initialize_output_buffering();

		// Include path
		$this->set_include_path();

		// Create or restore the session
		$this->initialize_session();

		// Establish the connection to the database
		$this->initialize_database();

		// Initialize logger
		$this->initialize_logger();

		// Load all the configuration parameters from the database
		$this->process_configuration();

		// Se encodage
		$this->set_encodage();

		// Set timezone
		$this->set_timezone();

		$this->initialize_i18n();

		// Not yet fully integrated - (testing in progress)
		// $this->load_plugins();

      	// Trigger the 'OnAfterInitialize' action hook
		// (will be activated later)
		//IspCP_Registry::get('Hook')->OnAfterInitialize();

		self::$_initialized = true;
	}

	/**
	 * Check for PHP version and Standard PHP library availability
	 *
	 * IspCP uses interfaces and classes that come from the Standard Php library
	 * under PHP version 5.1.4. This methods ensures that the PHP version used
	 * is more recent or equal to the PHP version 5.1.4 and that the SPL is
	 * loaded.
	 *
	 * Note: IspCP requires PHP 5.1.4 or later because some SPL interfaces were
	 * not stable in earlier versions of PHP.
	 *
	 * @return void
	 */
	protected function check_php() {

		// MAJOR . MINOR . TINY
		$php_version = substr(phpversion(), 0, 5);

		if(!version_compare($php_version, '5.1.4', '>=')) {
			$err_msg = sprintf(
				'Error: PHP version is %s. Version 5.1.4 or later is required!',
				$php_version
			);

		// We will use SPL interfaces like SplObserver, SplSubject
		// Note: Both ArrayAccess and Iterator interfaces are part of PHP core,
		// so, we can do the checking here without any problem.
		} elseif($php_version < '5.3.0' && !extension_loaded('SPL')) {
			$err_msg = 
				'Error: Standard PHP Library (SPL) was not detected !<br />' .
				'See http://php.net/manual/en/book.spl.php for more information!';
		} else {
			return;
		}

		system_message($err_msg);
	}

	/**
	 * Initialize the PHP output buffering / spGzip filter
	 *
	 * The buffer must be started at the earliest opportunity to avoid any
	 * encoding error (eg. during development phase where the developers uses
	 * some statements like echo, print in the code for debugging)
	 *
	 * Note: The hight level (like 8, 9) for compression are not recommended for
	 * performances reasons. The obtained gain with these levels is very small
	 * compared to the intermediate level like 6,7
	 *
	 * Note: ShowCompression option and checking for XmlHttpRequet will be done
	 * by a filter hooked on the 'OnBeforeOutput' action hook.
	 *
	 * @return void
	 */
	protected function initialize_output_buffering() {

		IspCP_spGzip::ob_start(7, false, true, true);
	}

	/**
	 * Set the include path
	 *
	 * Add the ispCP ./include directory to the include_path
	 *
	 * Note: Will be completed later with other paths (MVC switching).
	 *
	 * @return void
	 */
	protected function set_include_path() {

		$include_path = dirname(dirname(__FILE__));
		set_include_path(get_include_path() . PATH_SEPARATOR . $include_path);
	}

	/**
	 * Create/restore the session
	 *
	 * @return void
	 */
	protected function initialize_session() {

		session_name('ispCP');

		if (!isset($_SESSION))
			session_start();
	}

	/**
	 * Establishes the connection to the database
	 *
	 * This methods establishes the default connection to the database by using
	 * configuration parameters that come from the basis configuration object and
	 * then, register the database instance in the registry for shared access
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function initialize_database() {

		// @todo Add a specific test to check if the db keys were generated and
		// throws an exception if its not the case - Don't use global
		global $ispcp_db_pass_key, $ispcp_db_pass_iv;

		// Include needed db keys
		require_once 'ispcp-db-keys.php';
		
		try {

			$connection = Database::connect(
				$this->_config->DATABASE_USER,
				decrypt_db_password($this->_config->DATABASE_PASSWORD),
				$this->_config->DATABASE_TYPE,
				$this->_config->DATABASE_HOST,
				$this->_config->DATABASE_NAME
			);

		} catch(PDOException $e) {

			// Here, any SQL error information are showed only if the DEBUG
			// parameter value is set to a positive value in the ispcp.conf file.
			system_message(
				$this->_config->DEBUG ?
					'Error: Unable to establish connection to the database!<br />'.
						'SQL returned: ' . $e->getMessage() :
					'Error: An error occurred! Please, contact your administrator!'
			);

		}

		// Register the Db instance for shared access
		IspCP_Registry::set('Db', $connection);
	}

	/**
	 * Not Yet Implemented
	 *
	 * Not used at this moment (testing in progress)
	 */
	protected function initialize_logger() {}

	/**
	 * Load configuration parameters from database
	 *
	 * This function retrieves all the parameters from the database and merge
	 * them with the basis configuration object.
	 *
	 * Parameters that exists in the basis configuration object will replaced
	 * by them that come from the database. The basis configuration object
	 * contains parameters that come from the ispcp.conf configuration file or
	 * any parameter defined in the {@link environment.php} file.
	 *
	 * @return void
	 */
	protected function process_configuration() {

		// We get an IspCP_ConfigHandler_Db object
		$db_cfg = Config::getInstance(
			Config::DB,
			IspCP_Registry::get('Db')->getRawInstance()
		);

		// Now, we can override our base configuration object with parameter
		// that come from the database
		$this->_config->replace_with($db_cfg);

		// Finally, we register the IspCP_ConfigHandler_Db for shared access
		IspCP_Registry::set('Db_Config', $db_cfg);
	}

	/**
	 * Set encodage
	 *
	 * This methods set encodage for both communication database and PHP.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function set_encodage() {

		// Always send the following header:
		// Content-type: text/html; charset=UTF-8'
		// Note: This header can be overrided by calling the header() function
		ini_set('default_charset', 'UTF-8');

		// Switch optionally to utf8 based communication with the database
		if (isset($this->_config->DATABASE_UTF8) &&
			$this->_config->DATABASE_UTF8 == 'yes') {

			$db = IspCP_Registry::get('Db');

			try {
				if($db->Execute('SET NAMES `utf8`;') === false)
					throw new Exception($db->ErrorMsg());
			} catch(Exception $e) {
				system_message(
					$this->_config->DEBUG ?
						'Error: Unable to set charset for database communication!<br />' .
							$e->getMessage() :
						'Error: An error occurred! Please, contact your administrator!'
				);
			}
		}
	}

	/**
	 * Set timezone
	 *
	 * This method ensures that the timezone is set to avoid any error with PHP
	 * versions equal or later than version 5.3.x
	 *
	 * This method acts by checking the `date.timezone` value, and sets it to
	 * the value from the ispCP PHP_TIMEZONE parameter if exists and if it not
	 * empty or to 'UTC' otherwise.
	 *
	 * Note : This method don't check if the timezone defined by the
	 * ispCP PHP_TIMEZONE parameter is valid.
	 *
	 * @return void
	 */
	protected function set_timezone() {

		// Timezone is not set in the php.ini file ?
		if(ini_get('date.timezone') == '') {

			$timezone = (isset($this->_config->PHP_TIMEZONE) &&
				$this->_config->PHP_TIMEZONE != '')
					? $this->_config->PHP_TIMEZONE : 'UTC';

			ini_set('date.timezone', $timezone);
		}
	}

	/**
	 * Not Yet Implemented
	 *
	 * @todo Ask Jochen for the new i18n library and initilization processing
	 */
	protected function initialize_i18n() {}

	/**
	 * Not yet implemented
	 *
	 * Not used at this moment because we have only one theme.
	 */
	protected function initialize_layout() {}

	/**
	 * Load all plugins
	 *
	 * This method loads all the active plugins. Only plugins for the current
	 * execution context are loaded.
	 *
	 * Note: Not used at this moment (testing in progress...)
	 *
	 * @return void
	 */
	protected function load_plugins() {

		// Load all the available plugins for the current execution context
		IspCP_Plugin_Helpers::getPlugins();

		// Register an IspCP_Plugin_ActionsHooks for shared access
		IspCP_Registry::set('Hook', IspCP_Plugin_ActionsHooks::getInstance());
	}
}
