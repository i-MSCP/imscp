<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	PHPini
 * @copyright	2010-2014 by i-MSCP team
 * @author		Hannes Koschier <hannes@cheat.at>
 * @contributor	Laurent Declercq <l.declercq@nuxwin.com>
 * @contributor Paweł Iwanowski <kontakt@raisen.pl>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Class to manage php.ini files.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	PHPini
 * @author		Hannes Koschier <hannes@cheat.at>
 * @contributor	Laurent Declercq <l.declercq@nuxwin.com>
 * @contributor Paweł Iwanowski <kontakt@raisen.pl>
 * @version		0.0.8
 */
class iMSCP_PHPini
{
	/**
	 * iMSCP_PHPini instance.
	 *
	 * @var iMSCP_PHPini
	 */
	static protected $_instance;

	/**
	 * Associative array that contains php.ini data.
	 *
	 * @var array
	 */
	protected $_phpiniData = array();

	/**
	 * Associative array that contains the reseller's permissions, including its max values for PHP directives.
	 *
	 * @var array
	 */
	protected $_phpiniRePerm = array();

	/**
	 * Associative array that contains client permissions.
	 *
	 * @var array
	 */
	protected $_phpiniClPerm = array();

	/**
	 *  @var iMSCP_Config_Handler_File
	 */
	protected $_cfg;

	/**
	 * Flag that is set to TRUE if an error occurs at {link setData()}.
	 *
	 * @var bool
	 */
	public $flagValueError = false;

	/**
	 * Flag that is sets to TRUE if the loaded data are customized
	 *
	 * @var bool
	 */
	public $flagCustomIni;

	/**
	 *  Flag that is sets to TRUE if an error occurs at setClPerm().
	 *
	 * @var bool
	 */
	public $flagValueClError = false;

	/**
	 * Singleton object - Make new unavailable.
	 */
	private function __construct()
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$this->_cfg = iMSCP_Registry::get('config');

		// Populate $_phpiniData with default data.
		// Default data are those set by admin via the admin/settings.php page
		$this->loadDefaultData();

		// Populate $_phpiniRePerm with default reseller permissions, including
		// its max values for the PHP directives. Max values are those set by admin via the admin/settings.php page
		$this->loadReDefaultPerm();

		// Populate $_phpiniClPerm with default customer permissions
		$this->loadClDefaultPerm();
	}

	/**
	 * Singleton obect - Make clone unavailable.
	 *
	 * @return void
	 */
	private function __clone()
	{

	}

	/**
	 * Implements singleton design pattern.
	 * 
	 * @static
	 * @return iMSCP_PHPini
	 */
	static public function getInstance()
	{
		if(null === self::$_instance)
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Load default PHP directive values (like set at system wide).
	 *
	 * @return void
	 * @TODO do not use system wide values as default values if reseller values are smaller
	 */
	public function loadDefaultData()
	{
		$this->_phpiniData['phpiniSystem'] = 'no';

		// Default permissions on PHP directives
		$this->_phpiniData['phpiniAllowUrlFopen'] = $this->_cfg->PHPINI_ALLOW_URL_FOPEN;
		$this->_phpiniData['phpiniDisplayErrors'] = $this->_cfg->PHPINI_DISPLAY_ERRORS;
		$this->_phpiniData['phpiniErrorReporting'] = $this->_cfg->PHPINI_ERROR_REPORTING;
		$this->_phpiniData['phpiniDisableFunctions'] = $this->_cfg->PHPINI_DISABLE_FUNCTIONS;

		// Default value for PHP directives
		$this->_phpiniData['phpiniPostMaxSize'] = $this->_cfg->PHPINI_POST_MAX_SIZE;
		$this->_phpiniData['phpiniUploadMaxFileSize'] = $this->_cfg->PHPINI_UPLOAD_MAX_FILESIZE;
		$this->_phpiniData['phpiniMaxExecutionTime'] = $this->_cfg->PHPINI_MAX_EXECUTION_TIME;
		$this->_phpiniData['phpiniMaxInputTime'] = $this->_cfg->PHPINI_MAX_INPUT_TIME;
		$this->_phpiniData['phpiniMemoryLimit'] = $this->_cfg->PHPINI_MEMORY_LIMIT;

		$this->flagCustomIni = false;
	}

	/**
	 * Load custom PHP directive values for the given domain (customer).
	 *
	 * @param int $domainId Domain unique identifier
	 * @return bool FALSE if data are not found, TRUE otherwise
	 */
	public function loadCustomPHPini($domainId)
	{
		$query = "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
		$stmt = exec_query($query, (int) $domainId);

		if ($stmt->recordCount()) {
			$this->_phpiniData['phpiniSystem'] = 'yes';

			$this->_phpiniData['phpiniAllowUrlFopen'] = $stmt->fields('allow_url_fopen');
			$this->_phpiniData['phpiniDisplayErrors'] = $stmt->fields('display_errors');
			$this->_phpiniData['phpiniErrorReporting'] = $stmt->fields('error_reporting');
			$this->_phpiniData['phpiniDisableFunctions'] = $stmt->fields('disable_functions');
			$this->_phpiniData['phpiniPostMaxSize'] = $stmt->fields('post_max_size');
			$this->_phpiniData['phpiniUploadMaxFileSize'] = $stmt->fields('upload_max_filesize');
			$this->_phpiniData['phpiniMaxExecutionTime'] = $stmt->fields('max_execution_time');
			$this->_phpiniData['phpiniMaxInputTime'] = $stmt->fields('max_input_time');
			$this->_phpiniData['phpiniMemoryLimit'] = $stmt->fields('memory_limit');

			$this->flagCustomIni = true;
		}

		return false;
	}

	/**
	 * Load permissions and max PHP directive values for the given reseller.
	 *
	 * @param int $resellerId Reseller unique identifier
	 * @return bool FALSE if $resellerId doesn't exist, TRUE otherwise
	 */
	public function loadRePerm($resellerId)
	{
		$resellerId = (int) $resellerId;

		$query = "
			SELECT
				`php_ini_system`, `php_ini_al_disable_functions`, `php_ini_al_allow_url_fopen`,
				`php_ini_al_display_errors`, `php_ini_max_post_max_size`, `php_ini_max_upload_max_filesize`,
				`php_ini_max_max_execution_time`, `php_ini_max_max_input_time`, `php_ini_max_memory_limit`
			FROM
				`reseller_props`
			WHERE
				`reseller_id` = ?
		";
		$stmt = exec_query($query, $resellerId);

		if($stmt->rowCount() && $stmt->fields('php_ini_system') == 'yes') {
			// Permissions on PHP directives
			$this->_phpiniRePerm['phpiniSystem'] = 'yes';
			$this->_phpiniRePerm['phpiniAllowUrlFopen'] = $stmt->fields('php_ini_al_allow_url_fopen');
			$this->_phpiniRePerm['phpiniDisplayErrors'] = $stmt->fields('php_ini_al_display_errors');
			$this->_phpiniRePerm['phpiniDisableFunctions'] = $stmt->fields('php_ini_al_disable_functions');

			// Max values for PHP directives
			$this->_phpiniRePerm['phpiniPostMaxSize'] = $stmt->fields('php_ini_max_post_max_size');
			$this->_phpiniRePerm['phpiniUploadMaxFileSize'] = $stmt->fields('php_ini_max_upload_max_filesize');
			$this->_phpiniRePerm['phpiniMaxExecutionTime'] = $stmt->fields('php_ini_max_max_execution_time');
			$this->_phpiniRePerm['phpiniMaxInputTime'] = $stmt->fields('php_ini_max_max_input_time');
			$this->_phpiniRePerm['phpiniMemoryLimit'] = $stmt->fields('php_ini_max_memory_limit');

			return true;
		}

		return false;
	}

	/**
	 * Load default permissions and max values for reseller.
	 *
	 * @return void
	 */
	public function loadReDefaultPerm()
	{
		// Default permissions on PHP directives
		$this->_phpiniRePerm['phpiniSystem'] = 'no';
		$this->_phpiniRePerm['phpiniAllowUrlFopen'] = 'no';
		$this->_phpiniRePerm['phpiniDisplayErrors'] = 'no';
		$this->_phpiniRePerm['phpiniDisableFunctions'] = 'no';

		// Default reseller max value for PHP directives (based on system wide values)
		$this->_phpiniRePerm['phpiniPostMaxSize'] = $this->_cfg->PHPINI_POST_MAX_SIZE;
		$this->_phpiniRePerm['phpiniUploadMaxFileSize'] = $this->_cfg->PHPINI_UPLOAD_MAX_FILESIZE;
		$this->_phpiniRePerm['phpiniMaxExecutionTime'] = $this->_cfg->PHPINI_MAX_EXECUTION_TIME;
		$this->_phpiniRePerm['phpiniMaxInputTime'] = $this->_cfg->PHPINI_MAX_INPUT_TIME;
		$this->_phpiniRePerm['phpiniMemoryLimit'] = $this->_cfg->PHPINI_MEMORY_LIMIT;
	}

	/**
	 * Sets value for the given PHP directive.
	 *
	 * @see _rawCheckData()
	 * @param string $key PHP data key name
	 * @param string $value PHP data value
	 * @param bool $withCheck Tells whether or not the value must be checked
	 * @return bool FALSE if $withCheck is set to TRUE and $value is not valid or if $keys is unknown, TRUE otherwise
	 */
	public function setData($key, $value, $withCheck = true)
	{
		if(! $withCheck) {
			if($key == 'phpiniErrorReporting') {
				$this->_phpiniData[$key] = $this->errorReportingToInteger($value);
			} else {
				$this->_phpiniData[$key] = $value;
			}

			return true;
		} elseif($this->_rawCheckData($key, $value)) {
			if($key == 'phpiniErrorReporting') {
				$this->_phpiniData[$key] = $this->errorReportingToInteger($value);
			} else {
				$this->_phpiniData[$key] = $value;
			}

			return true;
		}

		$this->flagValueError = true;

		return false;
	}

	/**
	 * Sets value for the given customer permission.
	 *
	 * @param string $key Permission key name
	 * @param string $value Permission value (yes|no)
	 * @return bool FALSE if $value is not valid or if $keys is unknown, TRUE otherwise
	 */
	public function setClPerm($key, $value)
	{
		if ($this->_rawCheckClPermData($key, $value)) {
			$this->_phpiniClPerm[$key] = $value;
			return true;
		}

		$this->flagValueClError = true;

		return false;
	}

	/**
	 * Sets a PHP data.
	 *
	 * @param string $key PHP data key name
	 * @param string $value PHP data value
	 * @return bool FALSE if basic check or/and reseller permission check fails or if $key is unknown
	 */
	public function setDataWithPermCheck($key, $value)
	{
		if ($this->_rawCheckData($key, $value)) { // Value is not out of range
			// Either, the reseller has permissions on $key or $value is not greater than reseller max value for $key
			if ($this->checkRePerm($key) || $this->checkRePermMax($key, $value)) {
				$this->_phpiniData[$key] = $value;
				return true;
			}
		}

		$this->flagValueError = true;

		return false;
	}

	/**
	 * Sets value for the given reseller permission.
	 *
	 * @param string $key Permission key name
	 * @param string $value Permission value
	 * @param bool $withCheck Tells whether or not the value must be checked
	 * @return bool FALSE if $value is not valid or $key is unknown, TRUE otherwise.
	 */
	public function setRePerm($key, $value, $withCheck = true)
	{
		if(!$withCheck) {
			$this->_phpiniRePerm[$key] = $value;
			return true;
		} elseif($this->_rawCheckRePermData($key, $value)) {
			$this->_phpiniRePerm[$key] = $value;
			return true;
		}

		$this->flagValueError = true;

		return false;
	}

	/**
	 * Checks if a reseller has permission on the given item.
	 *
	 * @param string $key Permission key name
	 * @return bool TRUE if $key is a known item and reseller has permission on it.
	 */
	public function checkRePerm($key)
	{
		if ($this->_phpiniRePerm['phpiniSystem'] == 'yes') {
			if($key == 'phpiniSystem' ||
			   in_array($key, array('phpiniAllowUrlFopen', 'phpiniDisplayErrors', 'phpiniDisableFunctions')
			   ) && $this->_phpiniRePerm[$key] == 'yes'
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks value for the given customer PHP directive against the Max value allowed for reseller
	 *
	 * @param string $key Permission key name
	 * @param string $value PHP directive value
	 * @return bool TRUE if $value is valid max value, FALSE otherwise
	 */
	public function checkRePermMax($key, $value)
	{
		if($this->_phpiniRePerm['phpiniSystem'] == 'yes') {
			if(in_array($key, array(
								   'phpiniPostMaxSize', 'phpiniUploadMaxFileSize',
								   'phpiniMaxExecutionTime', 'phpiniMaxInputTime',
								   'phpiniMemoryLimit', '')) && $value <= $this->_phpiniRePerm[$key]
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Assemble disable_functions parameter from its parts.
	 *
	 * @param array $disabledFunctions
	 * @return string
	 */
	public function assembleDisableFunctions($disabledFunctions)
	{
		if (!empty($disabledFunctions)) {
			$disabledFunctions = implode(',', array_unique($disabledFunctions));
		} else {
			$disabledFunctions = '';
		}

		return $disabledFunctions;
	}

	/**
	 * Saves custom PHP directives values into database.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return void
	 */
	public function saveCustomPHPiniIntoDb($domainId)
	{
		if ($this->checkExistCustomPHPini($domainId)) {
			$query = "
				UPDATE
					`php_ini`
				SET
					`disable_functions` = ?, `allow_url_fopen` = ?, `display_errors` = ?,
					`error_reporting` = ?, `post_max_size` = ?, `upload_max_filesize` = ?, `max_execution_time` = ?,
					`max_input_time` = ?, `memory_limit` = ?
				WHERE
					`domain_id` = ?
			";
			exec_query(
				$query,
				array(
					$this->_phpiniData['phpiniDisableFunctions'], $this->_phpiniData['phpiniAllowUrlFopen'],
					$this->_phpiniData['phpiniDisplayErrors'], $this->_phpiniData['phpiniErrorReporting'],
					$this->_phpiniData['phpiniPostMaxSize'], $this->_phpiniData['phpiniUploadMaxFileSize'],
					$this->_phpiniData['phpiniMaxExecutionTime'], $this->_phpiniData['phpiniMaxInputTime'],
					$this->_phpiniData['phpiniMemoryLimit'], $domainId
				)
			);
		} else {
			$query = "
				INSERT INTO `php_ini` (
					`disable_functions`, `allow_url_fopen`, `display_errors`, `error_reporting`, `post_max_size`,
					`upload_max_filesize`, `max_execution_time`, `max_input_time`, `memory_limit`, `domain_id`
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			";
			exec_query(
				$query,
				array(
					$this->_phpiniData['phpiniDisableFunctions'], $this->_phpiniData['phpiniAllowUrlFopen'],
					$this->_phpiniData['phpiniDisplayErrors'], $this->_phpiniData['phpiniErrorReporting'],
					$this->_phpiniData['phpiniPostMaxSize'], $this->_phpiniData['phpiniUploadMaxFileSize'],
					$this->_phpiniData['phpiniMaxExecutionTime'], $this->_phpiniData['phpiniMaxInputTime'],
					$this->_phpiniData['phpiniMemoryLimit'], $domainId
				)
			);
		}
	}

	/**
	 * Update domain table status and send request to the daemon.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return void
	 */
	public function sendToEngine($domainId)
	{
		# TODO changing only the domain status is not sufficicent since
		# we are providing many level for php.ini file
		$query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?";
		exec_query($query, array('tochange', $domainId));
		send_request();
	}

	/**
	 * Deletes custom PHP directive values for the given domain (customer).
	 *
	 * @param int $domainId Domain unique identifier
	 * @return void
	 */
	public function delCustomPHPiniFromDb($domainId)
	{
		if ($this->checkExistCustomPHPini($domainId)) {
			$query = "DELETE FROM `php_ini` WHERE `domain_id` = ?";
			exec_query($query, $domainId);
		}
	}

	/**
	 * Saves PHP editor permissions for the given (customer).
	 *
	 * @param int $domainId Domain unique identifier
	 * @return void
	 */
	public function saveClPermIntoDb($domainId)
	{
		$query = "
			UPDATE
				`domain`
			SET
				`phpini_perm_system` = ?, `phpini_perm_allow_url_fopen` = ?, `phpini_perm_display_errors` = ?,
				`phpini_perm_disable_functions` = ?
			WHERE
				`domain_id` = ?
		";
		exec_query(
			$query,
			array(
				$this->_phpiniClPerm['phpiniSystem'], $this->_phpiniClPerm['phpiniAllowUrlFopen'],
				$this->_phpiniClPerm['phpiniDisplayErrors'], $this->_phpiniClPerm['phpiniDisableFunctions'],
				$domainId
			)
		);
	}

	/**
	 * Checks if custom PHP directives exists for the given domain (customer).
	 *
	 * @param int $domainId Domain unique identifier
	 * @return bool TRUE custom PHP directive are found for $domainId, FALSE otherwise
	 */
	public function checkExistCustomPHPini($domainId)
	{
		$query = 'SELECT COUNT(`domain_id`) `cnt` FROM `php_ini` WHERE `domain_id` = ?';
		$stmt = exec_query($query, (int) $domainId);

		if ($stmt->fields['cnt'] > 0) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the PHP data as currently set.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->_phpiniData;
	}

	/**
	 * Returns reseller permissions like currently set in this object.
	 *
	 * @return array
	 */
	public function getRePerm()
	{
		return $this->_phpiniRePerm;
	}

	/**
	 * Returns value for the given reseller permission.
	 *
	 * @param string $key Permission key name
	 * @return string Permissions value
	 */
	public function getRePermVal($key)
	{
		return $this->_phpiniRePerm[$key];
	}

    /**
     * Returns default value for the giver reseller permission.
     *
     * @param string $key Permissions key name
     * @return string Permissions value
     */
	public function getReDefaultPermVal($key)
	{
		return min($this->getRePermVal($key), $this->getDataVal($key));
	}

	/**
	 * Returns customer permissions like currently set in this object.
	 *
	 * @return array
	 */
	public function getClPerm()
	{
		return $this->_phpiniClPerm;
	}

	/**
	 * Returns value for the given customer permission.
	 *
	 * @param string $key Permissions key name
	 * @return string Permission value
	 */
	public function getClPermVal($key)
	{
		return $this->_phpiniClPerm[$key];
	}

	/**
	 * Returns value for the given PHP data.
	 *
	 * @param string $key PHP data key name
	 * @return string PHP data value
	 */
	public function getDataVal($key)
	{
		return $this->_phpiniData[$key];
	}

	/**
	 * Returns default value for the given PHP directive.
	 *
	 * @param string $key PHP data key name
	 * @returns string PHP data value
	 */
	public function getDataDefaultVal($key)
	{
		$phpiniDatatmp['phpiniSystem'] = 'no';
		$phpiniDatatmp['phpiniAllowUrlFopen'] = $this->_cfg->PHPINI_ALLOW_URL_FOPEN;
		$phpiniDatatmp['phpiniDisplayErrors'] = $this->_cfg->PHPINI_DISPLAY_ERRORS;
		$phpiniDatatmp['phpiniErrorReporting'] = $this->_cfg->PHPINI_ERROR_REPORTING;
		$phpiniDatatmp['phpiniDisableFunctions'] = $this->_cfg->PHPINI_DISABLE_FUNCTIONS;
		$phpiniDatatmp['phpiniPostMaxSize'] = $this->_cfg->PHPINI_POST_MAX_SIZE;
		$phpiniDatatmp['phpiniUploadMaxFileSize'] = $this->_cfg->PHPINI_UPLOAD_MAX_FILESIZE;
		$phpiniDatatmp['phpiniMaxExecutionTime'] = $this->_cfg->PHPINI_MAX_EXECUTION_TIME;
		$phpiniDatatmp['phpiniMaxInputTime'] = $this->_cfg->PHPINI_MAX_INPUT_TIME;
		$phpiniDatatmp['phpiniMemoryLimit'] = $this->_cfg->PHPINI_MEMORY_LIMIT;

		return $phpiniDatatmp[$key];
	}

	/**
	 * Load default PHP editor permissions.
	 *
	 * @return void
	 */
	public function loadClDefaultPerm()
	{
		$this->_phpiniClPerm['phpiniSystem'] = 'no';
		$this->_phpiniClPerm['phpiniAllowUrlFopen'] = 'no';
		$this->_phpiniClPerm['phpiniDisplayErrors'] = 'no';
		$this->_phpiniClPerm['phpiniDisableFunctions'] = 'no';
	}

	/**
	 * Load PHP editor permissions for the given domain (customer).
	 *
	 * @param int $domainId Domain unique identifier
	 * @return bool FALSE if there no data for $domainId
	 */
	public function loadClPerm($domainId)
	{
		$query = "
			SELECT
				`phpini_perm_system`, `phpini_perm_allow_url_fopen`, `phpini_perm_display_errors`,
				`phpini_perm_disable_functions`
			FROM
				`domain`
			WHERE
				`domain_id` = ?
		";
		$stmt =  exec_query($query, (int) $domainId);

		if ($stmt->rowCount()) {
			$this->_phpiniClPerm['phpiniSystem'] = $stmt->fields('phpini_perm_system');
			$this->_phpiniClPerm['phpiniAllowUrlFopen'] = $stmt->fields('phpini_perm_allow_url_fopen');
			$this->_phpiniClPerm['phpiniDisplayErrors'] = $stmt->fields('phpini_perm_display_errors');
			$this->_phpiniClPerm['phpiniDisableFunctions'] = $stmt->fields('phpini_perm_disable_functions');

			return true;
		}

		return false;
	}

	/**
	 * Returns domain unique identifier for the given customer identifier.
	 *
	 * @param int $customerId Customer unique identifier
	 * @return mixed
	 */
	public function getDomId($customerId)
	{
		$query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";
		$stmt = exec_query($query, $customerId);

		return $stmt->fields('domain_id');
	}

	/**
	 * Tells whether or not the status is ok for the given domain.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return bool TRUE if domain status is 'ok', FALSE otherwise
	 */
	public function getDomStatus($domainId)
	{
		$query = "SELECT `domain_status` FROM `domain` WHERE `domain_id` = ?";
		$stmt = exec_query($query, $domainId);

		if ($stmt->fields('domain_status') == 'ok') {
			return true;
		}

		return false;
	}

	/**
	 * Returns error reporting integer value
	 *
	 * @param string $value Litteral error reporting value such as 'E_ALL & ~E_NOTICE'
	 * @return int error reporing integer value
	 */
	public function errorReportingToInteger($value)
	{
		switch($value) {
			case 'E_ALL & ~E_NOTICE':
				$int = E_ALL & ~E_NOTICE;
				break;
			case 'E_ALL | E_STRICT':
				$int = E_ALL | E_STRICT;
				break;
			case 'E_ALL & ~E_DEPRECATED':
				$int = E_ALL & ~E_DEPRECATED;
				break;
			default:
				$int = 0;
		}

		return $int;
	}

	/**
	 * Returns error reporting litteral value
	 *
	 * @param int $value integer error reporting value
	 * @return int error reporing litteral value
	 */
	public function errorReportingToLitteral($value)
	{
		switch($value) {
			case '30711':
			case '32759':
				$litteral = 'E_ALL & ~E_NOTICE';
				break;
			case '32767':
				$litteral = 'E_ALL | E_STRICT';
				break;
			case '22527':
			case '24575':
				$litteral = 'E_ALL & ~E_DEPRECATED';
				break;
			default:
				$litteral = 0;
		}

		return $litteral;
	}

	/**
	 * Checks value for the given PHP data.
	 *
	 * @param string $key PHP data key name
	 * @param string $value PHP data value
	 * @return bool TRUE if $key is known and $value is valid, FALSE otherwise
	 */
	protected function _rawCheckData($key, $value)
	{
		if ($key == 'phpiniSystem' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniAllowUrlFopen' && ($value == 'on' || $value == 'off')) {
			return true;
		}

		if ($key == 'phpiniDisplayErrors' && ($value == 'on' || $value == 'off')) {
			return true;
		}

		if ($key == 'phpiniErrorReporting' && ($value == 'E_ALL & ~E_NOTICE' || $value == 'E_ALL | E_STRICT' ||
			$value == 'E_ALL & ~E_DEPRECATED' || $value == '0')
		) {
			return true;
		}

		if ($key == 'phpiniDisableFunctions' && $this->_checkDisableFunctionsSyntax($value)) {
			return true;
		}

		if ($key == 'phpiniPostMaxSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniUploadMaxFileSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniMaxExecutionTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniMaxInputTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniMemoryLimit' && $value > 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		return false;
	}

	/**
	 * Checks value for the PHP disable_functions directive.
	 *
	 * Note: $disabledFunctions can be an array where each value is a function name, or a string where function names
	 * are comma separated. An empty array or an empty string is also valid.
	 *
	 * @param array|string $disabledFunctions PHP function to be disabled
	 * @return bool True if the $disabledFunctions contains only functions that can be disabled, FALSE otherwise
	 */
	protected function _checkDisableFunctionsSyntax($disabledFunctions)
	{
		$defaultDisabledFunctions = array(
			'show_source', 'system', 'shell_exec', 'passthru', 'exec', 'shell', 'symlink', 'phpinfo', 'proc_open',
			'popen'
		);

		if (!empty($disabledFunctions)) {
			if (is_string($disabledFunctions)) {
				$disabledFunctions = explode(',', $disabledFunctions);
			}

			foreach ($disabledFunctions as $function) {
				if (!in_array($function, $defaultDisabledFunctions)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Checks value for the given reseller PHP data.
	 *
	 * @param string $key PHP data key name
	 * @param string $value PHP data value
	 * @return bool TRUE if $key is known and $value is valid, FALSE otherwise
	 */
	protected function _rawCheckRePermData($key, $value)
	{
		if ($key == 'phpiniSystem' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniAllowUrlFopen' && ($value === 'yes' || $value === 'no')) {
			return true;
		}

		if ($key == 'phpiniDisplayErrors' && ($value === 'yes' || $value === 'no')) {
			return true;
		}

		if ($key == 'phpiniDisableFunctions' && ($value === 'yes' || $value === 'no')) {
			return true;
		}

		// TODO review all min. values below

		if ($key == 'phpiniPostMaxSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniUploadMaxFileSize' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniMaxExecutionTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniMaxInputTime' && $value >= 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		if ($key == 'phpiniMemoryLimit' && $value > 0 && $value < 10000 && is_numeric($value)) {
			return true;
		}

		return false;
	}

	/**
	 * Checks value for the given customer permission.
	 *
	 * @param string $key Permission key name
	 * @param string $value Permission value
	 * @return bool TRUE if $key is a known permission and $value is valid, FALSE otherwise
	 */
	protected function _rawCheckClPermData($key, $value)
	{
		if ($key == 'phpiniSystem' && ($value === 'yes' || $value === 'no')) {
			return true;
		}

		if ($key == 'phpiniAllowUrlFopen' && ($value === 'yes' || $value === 'no')) {
			return true;
		}

		if ($key == 'phpiniDisplayErrors' && ($value === 'yes' || $value === 'no')) {
			return true;
		}

		if ($key == 'phpiniDisableFunctions' && ($value === 'yes' || $value === 'no' || $value === 'exec')) {
			return true;
		}

		return false;
	}
}
