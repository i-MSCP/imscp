<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
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
 * @copyright	2001-2011 by i-MSCP team
 * @author		Hannes Koschier <hannes@cheat.at>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 * @Version		SVN: $Id$
 */

/**
 * Class to manage php.ini files.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	PHPini
 * @author		Hannes Koschier <hannes@cheat.at>
 * @version		0.0.1
 */
class iMSCP_PHPini
{
	/**
	 * Associative array that contains php.ini data.
	 *
	 * @var array
	 */
	protected $_phpiniData = array();

	/**
	 * Associative array that contains reseller permissions.
	 *
	 * @var array
	 */
	protected $_phpiniRePerm = array();

	/**
	 * Associative Array that containclient permissions.
	 *
	 * @var array
	 */
	protected $_phpiniClPerm = array();

	/**
	 *  @var iMSCP_Config_Handler_File
	 */
	protected $_cfg;

	/**
	 *  @const Default reseller permission
	 */
	const PHPINIDEFAULTPERM = 'no';

	/**
	 * true if an error occur at {link setData()} used for lazy check in action script.
	 *
	 * @var bool
	 */
	public $flagValueError = false;

	/**
	 * Flag to store the status if a custom php.ini is loaded or the default.
	 *
	 * @var bool
	 */
	public $flagCustomIni;

	/**
	 *  TRUE if an error occur at setClPerm() used for lazy check in action script.
	 *
	 * @var bool
	 */
	public $flagValueClError = false;


	/**
	 * Constructor.
	 */
	public function __construct()
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$this->_cfg = iMSCP_Registry::get('config');

		// Load with default phpini Data as default
		$this->loadDefaultData();

		// Load $phpiniRePerm with default Data
		$this->loadReDefaultPerm();

		// Load $phpiniClPerm with default Data
		$this->_loadClDefaultPerm();
	}

	/**
	 * Load default php.ini values.
	 *
	 * @return void
	 */
	public function loadDefaultData()
	{
		$this->_phpiniData['phpiniSystem'] = 'no';
		$this->_phpiniData['phpiniRegisterGlobals'] = $this->_cfg->PHPINI_REGISTER_GLOBALS;
		$this->_phpiniData['phpiniAllowUrlFopen'] = $this->_cfg->PHPINI_ALLOW_URL_FOPEN;
		$this->_phpiniData['phpiniDisplayErrors'] = $this->_cfg->PHPINI_DISPLAY_ERRORS;
		$this->_phpiniData['phpiniErrorReporting'] = $this->_cfg->PHPINI_ERROR_REPORTING;
		$this->_phpiniData['phpiniDisableFunctions'] = $this->_cfg->PHPINI_DISABLE_FUNCTIONS;
		$this->_phpiniData['phpiniPostMaxSize'] = $this->_cfg->PHPINI_POST_MAX_SIZE;
		$this->_phpiniData['phpiniUploadMaxFileSize'] = $this->_cfg->PHPINI_UPLOAD_MAX_FILESIZE;
		$this->_phpiniData['phpiniMaxExecutionTime'] = $this->_cfg->PHPINI_MAX_EXECUTION_TIME;
		$this->_phpiniData['phpiniMaxInputTime'] = $this->_cfg->PHPINI_MAX_INPUT_TIME;
		$this->_phpiniData['phpiniMemoryLimit'] = $this->_cfg->PHPINI_MEMORY_LIMIT;
		$this->flagCustomIni = false;
	}

	/**
	 * Load custom php.ini values.
	 *
	 * @param bool $domainId Domaion unique identifier
	 * @return bool FALSE if there no custom.ini, TRUE otherwise
	 */
	public function loadCustomPHPini($domainId)
	{
		// if theres a custom php.ini (row in php_ini table with this domain_id)
		if ($dataset = $this->_loadCustomPHPiniFromDb($domainId)) {
			$this->_phpiniData['phpiniSystem'] = 'yes'; // if custom ini exist than yes
			$this->_phpiniData['phpiniRegisterGlobals'] = $dataset->fields('register_globals');
			$this->_phpiniData['phpiniAllowUrlFopen'] = $dataset->fields('allow_url_fopen');
			$this->_phpiniData['phpiniDisplayErrors'] = $dataset->fields('display_errors');
			$this->_phpiniData['phpiniErrorReporting'] = $dataset->fields('error_reporting');
			$this->_phpiniData['phpiniDisableFunctions'] = $dataset->fields('disable_functions');
			$this->_phpiniData['phpiniPostMaxSize'] = $dataset->fields('post_max_size');
			$this->_phpiniData['phpiniUploadMaxFileSize'] = $dataset->fields('upload_max_filesize');
			$this->_phpiniData['phpiniMaxExecutionTime'] = $dataset->fields('max_execution_time');
			$this->_phpiniData['phpiniMaxInputTime'] = $dataset->fields('max_input_time');
			$this->_phpiniData['phpiniMemoryLimit'] = $dataset->fields('memory_limit');
			$this->flagCustomIni = true;

			return true;
		}

		// if theres no custom php.ini return FALSE
		return false;
	}

	/**
	 * Load reseller permissions and max values.
	 *
	 * @param $resellerId Reseller unique identifier
	 * @return bool FALSE if there no Reseller with this $resellerId, TRUE otherwise
	 */
	public function loadRePerm($resellerId)
	{
		//i f the reseller has php.ini permission than load the details of it
		if ($dataset = $this->_loadRePermFromDb($resellerId)) {
			$this->_phpiniRePerm['phpiniSystem'] = $dataset->fields('php_ini_system');
			$this->_phpiniRePerm['phpiniRegisterGlobals'] = $dataset->fields('php_ini_al_register_globals');
			$this->_phpiniRePerm['phpiniAllowUrlFopen'] = $dataset->fields('php_ini_al_allow_url_fopen');
			$this->_phpiniRePerm['phpiniDisplayErrors'] = $dataset->fields('php_ini_al_display_errors');
			$this->_phpiniRePerm['phpiniDisableFunctions'] = $dataset->fields('php_ini_al_disable_functions');
			$this->_phpiniRePerm['phpiniPostMaxSize'] = $dataset->fields('php_ini_max_post_max_size');
			$this->_phpiniRePerm['phpiniUploadMaxFileSize'] = $dataset->fields('php_ini_max_upload_max_filesize');
			$this->_phpiniRePerm['phpiniMaxExecutionTime'] = $dataset->fields('php_ini_max_max_execution_time');
			$this->_phpiniRePerm['phpiniMaxInputTime'] = $dataset->fields('php_ini_max_max_input_time');
			$this->_phpiniRePerm['phpiniMemoryLimit'] = $dataset->fields('php_ini_max_memory_limit');
			return true;
		}

		// if there no Reseller with this $resellerId return FALSE
		return false;
	}

	/**
	 * Load reseller default permissions and max values.
	 *
	 * @return void
	 */
	public function loadReDefaultPerm()
	{
		// Load Default Reseller Perm. (Data from global config)
		$this->_phpiniRePerm['phpiniSystem'] = self::PHPINIDEFAULTPERM; // Static no as Default
		$this->_phpiniRePerm['phpiniRegisterGlobals'] = self::PHPINIDEFAULTPERM; // Static no as Default
		$this->_phpiniRePerm['phpiniAllowUrlFopen'] = self::PHPINIDEFAULTPERM; // Static no as Default
		$this->_phpiniRePerm['phpiniDisplayErrors'] = self::PHPINIDEFAULTPERM; // Static no as Default
		$this->_phpiniRePerm['phpiniDisableFunctions'] = self::PHPINIDEFAULTPERM; // Static no as Default
		$this->_phpiniRePerm['phpiniPostMaxSize'] = $this->_cfg->PHPINI_POST_MAX_SIZE;
		$this->_phpiniRePerm['phpiniUploadMaxFileSize'] = $this->_cfg->PHPINI_UPLOAD_MAX_FILESIZE;
		$this->_phpiniRePerm['phpiniMaxExecutionTime'] = $this->_cfg->PHPINI_MAX_EXECUTION_TIME;
		$this->_phpiniRePerm['phpiniMaxInputTime'] = $this->_cfg->PHPINI_MAX_INPUT_TIME;
		$this->_phpiniRePerm['phpiniMemoryLimit'] = $this->_cfg->PHPINI_MEMORY_LIMIT;
	}

	/**
	 * Sets php.ini data values with basic data check.
	 *
	 * @param string $key php.ini key
	 * @param string $value php.ini key value
	 * @return bool FALSE if a basic check fails or if $key is unknown
	 */
	public function setData($key, $value)
	{
		if ($this->_rawCheckData($key, $value)) {
			$this->_phpiniData[$key] = $value;
			return true;
		}

		$this->flagValueError = true;

		return false;
	}

	/**
	 * Sets php.ini client permissions values with basic data check.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool FALSE if a basic check fails or if $key is unknow
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
	 * Sets php.ini data values with basic data check and reseller permission check.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool FALSE if basic check or/and reseller permission check fails or if $key is unknown
	 */
	public function setDataWithPermCheck($key, $value)
	{
		if ($this->_rawCheckData($key, $value)) {
			// if permission is ok
			if ($this->checkRePerm($key) || $this->checkRePermMax($key, $value)) {
				$this->_phpiniData[$key] = $value;
				return true;
			}
		}

		$this->flagValueError = true;

		return false;
	}

	/**
	 * Sets php.ini reseller permissions values with basic data check.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool FALSE if basic check fails or if $key is unknown.
	 */
	public function setRePerm($key, $value)
	{
		if ($this->_rawCheckRePermData($key, $value)) {
			$this->_phpiniRePerm[$key] = $value;
			return true;
		}

		$this->flagValueError = true;

		return false;
	}

	/**
	 * Checks reseller permission vor one item mostly for short/fast check in Action script.
	 *
	 * @param string $key
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function checkRePerm($key)
	{
		if ($this->_phpiniRePerm['phpiniSystem'] == 'no') {
			return false;
		}

		// if phpiniSystem is no than all is no regardless what asked
		if ($key == 'phpiniSystem' && $this->_phpiniRePerm['phpiniSystem'] == 'yes') {
			return true;
		}

		if ($key == 'phpiniRegisterGlobals' && $this->_phpiniRePerm['phpiniRegisterGlobals'] == 'yes') {
			return true;
		}

		if ($key == 'phpiniAllowUrlFopen' && $this->_phpiniRePerm['phpiniAllowUrlFopen'] == 'yes') {
			return true;
		}

		if ($key == 'phpiniDisplayErrors' && $this->_phpiniRePerm['phpiniDisplayErrors'] == 'yes') {
			return true;
		}


		if ($key == 'phpiniDisableFunctions' && $this->_phpiniRePerm['phpiniDisableFunctions'] == 'yes') {
			return true;
		}

		return false;
	}

	/**
	 * Checks reseller MAX permission vor one item mostly for short/fast check in Action script.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	public function checkRePermMax($key, $value)
	{
		if ($this->_phpiniRePerm['phpiniSystem'] == 'no') {
			return false;
		}

		// if phpiniSystem is no than all is no regardless what asked
		if ($key == 'phpiniPostMaxSize' && $value <= $this->_phpiniRePerm['phpiniPostMaxSize']) {
			return true;
		}

		if ($key == 'phpiniUploadMaxFileSize' && $value <= $this->_phpiniRePerm['phpiniUploadMaxFileSize']) {
			return true;
		}

		if ($key == 'phpiniMaxExecutionTime' && $value <= $this->_phpiniRePerm['phpiniMaxExecutionTime']) {
			return true;
		}

		if ($key == 'phpiniMaxInputTime' && $value <= $this->_phpiniRePerm['phpiniMaxInputTime']) {
			return true;
		}

		if ($key == 'phpiniMemoryLimit' && $value <= $this->_phpiniRePerm['phpiniMemoryLimit']) {
			return true;
		}

		return false;
	}

	/**
	 * Assemble disable_functions parameter from its parts.
	 *
	 * @param array $arrDfitem
	 * @return string
	 */
	public function assembleDisableFunctions($arrDfitem)
	{
		if (count($arrDfitem)) {
			$phpiniDisableFunctions = implode(',', $arrDfitem);
		} else {
			$phpiniDisableFunctions = '';
		}

		return $phpiniDisableFunctions;
	}

	/**
	 * Saves custom php.ini to php_ini table.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return void
	 */
	public function saveCustomPHPiniIntoDb($domainId)
	{
		if ($this->checkExistCustomPHPini($domainId)) { //if custom ini exist than only update it
			$query = "
				UPDATE
					`php_ini`
				SET
					`status` = ?,
					`disable_functions` = ?,
					`allow_url_fopen` = ?,
					`register_globals` = ?,
					`display_errors` = ?,
					`error_reporting` = ?,
					`post_max_size` = ?,
					`upload_max_filesize` = ?,
					`max_execution_time` = ?,
					`max_input_time` = ?,
					`memory_limit` = ?
				WHERE
					`domain_id` = ?
			";
			exec_query($query, array(
									$this->_cfg->ITEM_CHANGE_STATUS,
									$this->_phpiniData['phpiniDisableFunctions'],
									$this->_phpiniData['phpiniAllowUrlFopen'],
									$this->_phpiniData['phpiniRegisterGlobals'],
									$this->_phpiniData['phpiniDisplayErrors'],
									$this->_phpiniData['phpiniErrorReporting'],
									$this->_phpiniData['phpiniPostMaxSize'],
									$this->_phpiniData['phpiniUploadMaxFileSize'],
									$this->_phpiniData['phpiniMaxExecutionTime'],
									$this->_phpiniData['phpiniMaxInputTime'],
									$this->_phpiniData['phpiniMemoryLimit'],
									$domainId));
		} else {
			$query = "
				INSERT INTO
					`php_ini` (
						`status`, `disable_functions`, `allow_url_fopen`,
						`register_globals`, `display_errors`, `error_reporting`,
						`post_max_size`, `upload_max_filesize`, `max_execution_time`,
						`max_input_time`, `memory_limit`, `domain_id`
				 ) VALUES (
					'new', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			";
			exec_query($query, array(
									$this->_phpiniData['phpiniDisableFunctions'],
									$this->_phpiniData['phpiniAllowUrlFopen'],
									$this->_phpiniData['phpiniRegisterGlobals'],
									$this->_phpiniData['phpiniDisplayErrors'],
									$this->_phpiniData['phpiniErrorReporting'],
									$this->_phpiniData['phpiniPostMaxSize'],
									$this->_phpiniData['phpiniUploadMaxFileSize'],
									$this->_phpiniData['phpiniMaxExecutionTime'],
									$this->_phpiniData['phpiniMaxInputTime'],
									$this->_phpiniData['phpiniMemoryLimit'],
									$domainId));
		}

	}

	/**
	 * Changes domain table to update and send_request to engine.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return void
	 */
	public function sendToEngine($domainId)
	{
		$query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?";
		exec_query($query, array($this->_cfg->ITEM_CHANGE_STATUS, $domainId));

		// Send a request to the daemon for backend process
		send_request();
	}

	/**
	 * public
	 * delete custom php.ini from table php_ini
	 */

	/**
	 * Delete custom php.ini from table php_ini.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return void
	 */
	public function delCustomPHPiniFromDb($domainId)
	{
		if ($this->checkExistCustomPHPini($domainId)) {
			$query = "DELETE FROM `php_ini` WHERE `domain_id` = ? ";
			exec_query($query, $domainId);
		}
	}

	/**
	 * Saves client php.ini permisson to table domain.
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
				`phpini_perm_system` = ?,
				`phpini_perm_register_globals` = ?,
				`phpini_perm_allow_url_fopen` = ?,
				`phpini_perm_display_errors` = ?,
				`phpini_perm_disable_functions` = ?
			WHERE
				`domain_id` = ?
		";
		exec_query($query, array(
								$this->_phpiniClPerm['phpiniSystem'],
								$this->_phpiniClPerm['phpiniRegisterGlobals'],
								$this->_phpiniClPerm['phpiniAllowUrlFopen'],
								$this->_phpiniClPerm['phpiniDisplayErrors'],
								$this->_phpiniClPerm['phpiniDisableFunctions'],
								$domainId));
	}

	/**
	 * Checks if custom php.ini exist.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return bool TRUE if custom php.ini exist, FALSE otherwise
	 */
	public function checkExistCustomPHPini($domainId)
	{
		if ($this->_loadCustomPHPiniFromDb($domainId)) {
			return true;
		}

		return false;
	}

	/**
	 * Returns array tha contains phpi.ini data.
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->_phpiniData;
	}

	/**
	 * Returns array that contains php.ini reseller permissions.
	 *
	 * @return array
	 */
	public function getRePerm()
	{
		return $this->_phpiniRePerm;
	}

	/**
	 * Returns specific reseller permissions value.
	 *
	 * @param string $key Reseller permission key
	 * @return string Reseller permissions value
	 */
	public function getRePermVal($key)
	{
		return $this->_phpiniRePerm[$key];
	}

	/**
	 * Returns specific client permission value.
	 *
	 * @param string $key Client permissions key
	 * @return string
	 */
	public function getClPermVal($key)
	{
		return $this->_phpiniClPerm[$key];
	}

	/**
	 * Returns a php.ini parameter value.
	 *
	 * @param string $key Php.ini parameter key
	 * @return string Php.ini parameter value
	 */
	public function getDataVal($key)
	{
		return $this->_phpiniData[$key];
	}

	/**
	 * Returns specific value from default php.ini values without load them into {link _phpiniData}
	 *
	 * @param string $key Php.ini parameter key
	 * @returns string Php.ini parameter default value
	 */
	public function getDataDefaultVal($key)
	{
		$phpiniDatatmp['phpiniSystem'] = 'no';
		$phpiniDatatmp['phpiniRegisterGlobals'] = $this->_cfg->PHPINI_REGISTER_GLOBALS;
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
	 * Load client permissions.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return bool FALSE if there no domain with $domainId
	 */
	public function loadClPerm($domainId)
	{
		if ($dataset = $this->_loadClPermFromDb($domainId)) { //load the perm from domain table
			$this->_phpiniClPerm['phpiniSystem'] = $dataset->fields('phpini_perm_system');
			$this->_phpiniClPerm['phpiniRegisterGlobals'] = $dataset->fields('phpini_perm_register_globals');
			$this->_phpiniClPerm['phpiniAllowUrlFopen'] = $dataset->fields('phpini_perm_allow_url_fopen');
			$this->_phpiniClPerm['phpiniDisplayErrors'] = $dataset->fields('phpini_perm_display_errors');
			$this->_phpiniClPerm['phpiniDisableFunctions'] = $dataset->fields('phpini_perm_disable_functions');

			return true;
		}

		return false;
	}

	/**
	 * Returns domain id from given user.
	 *
	 * @param $userId User unique identifier
	 * @return mixed
	 */
	public function getDomId($userId)
	{
		$query = "SELECT `domain_id` FROM `domain` WHERE `domain_admin_id` = ?";
		$stmt = exec_query($query, $userId);

		return $stmt->fields('domain_id');
	}

	/**
	 * Tells whether or not domain status is ok.
	 *
	 * @param $domainId Domain unique identifier
	 * @return bool
	 */
	public function getDomStatus($domainId)
	{
		$query = "SELECT `domain_status` FROM `domain` WHERE `domain_id` = ?";
		$stmt = exec_query($query, $domainId);

		if ($stmt->fields('domain_status') == $this->_cfg->ITEM_OK_STATUS) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks data.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	protected function _rawCheckData($key, $value)
	{
		// Basic Check against possible values
		if ($key == 'phpiniSystem' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniRegisterGlobals' && ($value == 'On' || $value == 'Off')) {
			return true;
		}

		if ($key == 'phpiniAllowUrlFopen' && ($value == 'On' || $value == 'Off')) {
			return true;
		}

		if ($key == 'phpiniDisplayErrors' && ($value == 'On' || $value == 'Off')) {
			return true;
		}

		if ($key == 'phpiniErrorReporting' &&
			('E_ALL & ~E_NOTICE' || $value == 'E_ALL | E_STRICT' ||
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
	 * Checks disable_funcitons syntax.
	 *
	 * @param array $df
	 * @return bool
	 */
	protected function _checkDisableFunctionsSyntax($df)
	{
		$phpiniDfAll = array(
			'show_source', 'system', 'shell_exec', 'passthru', 'exec', 'shell',
			'symlink', 'phpinfo');

		$arrhelper = explode(',', $df);

		foreach ($arrhelper as $item) {
			// if isn't one of the pieces fo $df one of the $phpiniDfAll
			// (all possibles) than theres something wrong with the input data
			if (!in_array($item, $phpiniDfAll)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks reseller permission data.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	protected function _rawCheckRePermData($key, $value)
	{
		//Basic Check against possible values
		if ($key == 'phpiniSystem' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniRegisterGlobals' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniAllowUrlFopen' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniDisplayErrors' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniDisableFunctions' && ($value == 'yes' || $value == 'no')) {
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
	 * Checks client permission data.
	 *
	 * @param string $key Client permission key
	 * @param string $value Client permission value
	 * @return bool
	 */
	protected function _rawCheckClPermData($key, $value)
	{
		// Basic Check against possible values
		if ($key == 'phpiniSystem' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniRegisterGlobals' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniAllowUrlFopen' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniDisplayErrors' && ($value == 'yes' || $value == 'no')) {
			return true;
		}

		if ($key == 'phpiniDisableFunctions' && ($value == 'yes' || $value == 'no' || $value == 'exec')) {
			return true;
		}

		return false;
	}

	/**
	 * Loads reseller permission from table reseller_props.
	 *
	 * @param int $resellerId Reseller unique identifier
	 * @return bool|iMSCP_Database_ResultSet Returns iMSCP_Database_ResultSet object
	 * 										 with details if reseller has php.ini
	 * 										 permission, FALSE otherwise
	 */
	protected function _loadRePermFromDb($resellerId)
	{
		// Load default reseller's php.ini permissions from database
		$query = "
			SELECT
				`php_ini_system`,`php_ini_al_disable_functions`, `php_ini_al_allow_url_fopen`,
				`php_ini_al_register_globals`, `php_ini_al_display_errors`, `php_ini_max_post_max_size`,
				`php_ini_max_upload_max_filesize`, `php_ini_max_max_execution_time`,
				`php_ini_max_max_input_time`, `php_ini_max_memory_limit`
			FROM
				`reseller_props`
			WHERE
				`reseller_id` = ?
		";
		$stmt = exec_query($query, $resellerId);

		// If there custom php.ini allowed at all
		if ($stmt->fields('php_ini_system') == 'yes') {
			return $stmt;
		}

		return false;
	}

	/**
	 * Loads php.ini from php_ini database table.
	 *
	 * @param int $domainId Domain unique identifier
	 * @return bool|iMSCP_Database_ResultSet Returns iMSCP_Database_ResultSet with
	 * 										 details if custom php.ini exist, FALSE
	 * 										 otherwise
	 */
	protected function _loadCustomPHPiniFromDb($domainId)
	{
		$query = "SELECT * FROM `php_ini` WHERE `domain_id` = ?";
		$stmt = exec_query($query, $domainId);

		if ($stmt->recordCount()) {
			return $stmt;
		}

		return false;
	}

	/**
	 * Loads client permissions from domains database table.
	 *
	 * @param $domainId Domain unique identifier
	 * @return iMSCP_Database_ResultSet
	 */
	protected function _loadClPermFromDb($domainId)
	{
		// Loads the client php.ini perm from db
		$query = "
			SELECT
				`phpini_perm_system`, `phpini_perm_register_globals`,
				`phpini_perm_allow_url_fopen`, `phpini_perm_display_errors`,
				`phpini_perm_disable_functions`
			FROM
				`domain`
			WHERE
				`domain_id` = ?
		";

		return exec_query($query, $domainId);
	}

	/**
	 * Load default client permissions.
	 *
	 * @return void
	 */
	protected function _loadClDefaultPerm()
	{
		$this->_phpiniClPerm['phpiniSystem'] = 'no';
		$this->_phpiniClPerm['phpiniRegisterGlobals'] = 'no';
		$this->_phpiniClPerm['phpiniAllowUrlFopen'] = 'no';
		$this->_phpiniClPerm['phpiniDisplayErrors'] = 'no';
		$this->_phpiniClPerm['phpiniDisableFunctions'] = 'no';
	}
}
