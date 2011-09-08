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
 * @category    	iMSCP
 * @package             iMSCP_PHPini
 * @copyright   	2011 by i-MSCP team
 * @author              Hannes Koschier <hannes@cheat.at>
 * @link                http://www.i-mscp.net i-MSCP Home Site
 * @license             http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * This class provides the functionality needed for gui management of php.ini
 * 
 *
 * @category    i-MSCP
 * @package     iMSCP_PHPini
 */

class iMSCP_PHPini {
	
	/**
         * Assoziative Array with the ini Data
         *
         * @var array
         */
	protected $phpiniData = array();

        /**
         * Assoziative Array with the Reseller permission
         *
         * @var array
         */
	protected $phpiniRePerm = array();

        /**
         * Assoziative Array with the Client permission
         *
         * @var array
         */
	protected $phpiniClPerm = array();

        /**
         *  var for the config object
         */
	protected $cfg; 

	/**
         *  static const for default reseller permission
         */
	const PHPINIDEFAULTPERM = 'no';

	/**
         *  true if an error occur at setData() used for lazy check in Action Script
         */
	public $flagValueError;

        /**
         *  flag to store the status if a custom php.ini is loaded or the default
         */
        public $flagCustomIni;

	/**
         *  Constructer
	 * Load default php.ini values
	 * Load default reseller permission
	 * TODO load default client permission
         */
	public function __construct() {

	        /** @var $cfg iMSCP_Config_Handler_File */
	        $this->cfg = iMSCP_Registry::get('config');

		//load with default phpini Data as default
		$this->loadDefaultData();

                //load $phpiniRePerm with default Data
                $this->loadReDefaultPerm();

		$flagValueError = false;

	}
	

	/**
         * public void
         * Load default php.ini values
         */
	public function loadDefaultData(){
		$this->phpiniData['phpiniSystem'] = 'no';
		$this->phpiniData['phpiniRegisterGlobals'] = $this->cfg->PHPINI_REGISTER_GLOBALS;
		$this->phpiniData['phpiniAllowUrlFopen'] = $this->cfg->PHPINI_ALLOW_URL_FOPEN;
                $this->phpiniData['phpiniDisplayErrors'] = $this->cfg->PHPINI_DISPLAY_ERRORS;
                $this->phpiniData['phpiniErrorReporting'] = $this->cfg->PHPINI_ERROR_REPORTING;
                $this->phpiniData['phpiniDisableFunctions'] = $this->cfg->PHPINI_DISABLE_FUNCTIONS;
                $this->phpiniData['phpiniPostMaxSize'] = $this->cfg->PHPINI_POST_MAX_SIZE;
                $this->phpiniData['phpiniUploadMaxFileSize'] = $this->cfg->PHPINI_UPLOAD_MAX_FILESIZE;
                $this->phpiniData['phpiniMaxExecutionTime'] = $this->cfg->PHPINI_MAX_EXECUTION_TIME;
                $this->phpiniData['phpiniMaxInputTime'] = $this->cfg->PHPINI_MAX_INPUT_TIME;
                $this->phpiniData['phpiniMemoryLimit'] = $this->cfg->PHPINI_MEMORY_LIMIT;
		$this->flagCustomIni = false;
	}

	/**
         * public bool
         * Load custom php.ini values 
	 * Returns false if there no custom.ini
         */
	public function loadCustomPHPini($domainId){
		if($dataset = $this->loadCustomPHPiniFromDb($domainId)){ //if theres a custom php.ini (row in php_ini table with this domain_id)
			$this->phpiniData['phpiniSystem'] = 'yes'; //if custom ini exist than yes
			$this->phpiniData['phpiniRegisterGlobals'] = $dataset->fields('register_globals');
                	$this->phpiniData['phpiniAllowUrlFopen'] = $dataset->fields('allow_url_fopen');
        	        $this->phpiniData['phpiniDisplayErrors'] = $dataset->fields('display_errors');
	                $this->phpiniData['phpiniErrorReporting'] = $dataset->fields('error_reporting');
                	$this->phpiniData['phpiniDisableFunctions'] = $dataset->fields('disable_functions');
        	        $this->phpiniData['phpiniPostMaxSize'] = $dataset->fields('post_max_size');
	                $this->phpiniData['phpiniUploadMaxFileSize'] = $dataset->fields('upload_max_filesize');
                	$this->phpiniData['phpiniMaxExecutionTime'] = $dataset->fields('max_execution_time');
        	        $this->phpiniData['phpiniMaxInputTime'] = $dataset->fields('max_input_time');
	                $this->phpiniData['phpiniMemoryLimit'] = $dataset->fields('memory_limit');
			$this->flagCustomIni = true;
			return true;
		} 
		return false; // if theres no custom php.ini return false	
	}

	/**
         * public bool
         * Load reseller permissions and max values
         * Returns false if there no Reseller with this id
         */
	public function loadRePerm($resellerId){
		if($dataset = $this->loadRePermFromDb($resellerId)){ //if the reseller has php.ini permission than load the details of it
			$this->phpiniRePerm['phpiniSystem'] = $dataset->fields('php_ini_system');
                        $this->phpiniRePerm['phpiniRegisterGlobals'] = $dataset->fields('php_ini_al_register_globals');
                        $this->phpiniRePerm['phpiniAllowUrlFopen'] = $dataset->fields('php_ini_al_allow_url_fopen');
                        $this->phpiniRePerm['phpiniDisplayErrors'] = $dataset->fields('php_ini_al_display_errors');
                        $this->phpiniRePerm['phpiniDisableFunctions'] = $dataset->fields('php_ini_al_disable_functions');
                        $this->phpiniRePerm['phpiniPostMaxSize'] = $dataset->fields('php_ini_max_post_max_size');
                        $this->phpiniRePerm['phpiniUploadMaxFileSize'] = $dataset->fields('php_ini_max_upload_max_filesize');
                        $this->phpiniRePerm['phpiniMaxExecutionTime'] = $dataset->fields('php_ini_max_max_execution_time');
                        $this->phpiniRePerm['phpiniMaxInputTime'] = $dataset->fields('php_ini_max_max_input_time');
                        $this->phpiniRePerm['phpiniMemoryLimit'] = $dataset->fields('php_ini_max_memory_limit');
                        return true;
                }
                return false; 
	}
	
        /**
         * public void 
         * Load reseller default permissions and max values
         */
	public function loadReDefaultPerm(){ // Load Default Reseller Perm. (Data from global config)
		$this->phpiniRePerm['phpiniSystem'] = self::PHPINIDEFAULTPERM; //Static no as Default
                $this->phpiniRePerm['phpiniRegisterGlobals'] = self::PHPINIDEFAULTPERM; //Static no as Default
                $this->phpiniRePerm['phpiniAllowUrlFopen'] = self::PHPINIDEFAULTPERM; //Static no as Default 
                $this->phpiniRePerm['phpiniDisplayErrors'] = self::PHPINIDEFAULTPERM; //Static no as Default
                $this->phpiniRePerm['phpiniDisableFunctions'] = self::PHPINIDEFAULTPERM; //Static no as Default
                $this->phpiniRePerm['phpiniPostMaxSize'] = $this->cfg->PHPINI_POST_MAX_SIZE;
                $this->phpiniRePerm['phpiniUploadMaxFileSize'] = $this->cfg->PHPINI_UPLOAD_MAX_FILESIZE;
                $this->phpiniRePerm['phpiniMaxExecutionTime'] = $this->cfg->PHPINI_MAX_EXECUTION_TIME;
                $this->phpiniRePerm['phpiniMaxInputTime'] = $this->cfg->PHPINI_MAX_INPUT_TIME;
                $this->phpiniRePerm['phpiniMemoryLimit'] = $this->cfg->PHPINI_MEMORY_LIMIT;
        }

	/**
         * public bool
         * set phpiniData values with basic data check
         * Returns false if a basic check fails or if $key is unknow
         */	
	public function setData($key, $value){
		if ($this->rawCheckData($key, $value)) {
			$this->phpiniData[$key] = $value;
			return true;
		}
		$this->flagValueError = true;
		return false;
	}

	/**
         * public bool
         * set phpiniData values with basic data check and reseller permission check
         * Returns false if a basic check or/and reseller permission check fail fails or if $key is unknow
         */
        public function setDataWithPermCheck($key, $value){
                if ($this->rawCheckData($key, $value)) {
			if ($this->checkRePerm($key) || $this->checkRePermMax($key, $value)) { // if permission is ok
                        	$this->phpiniData[$key] = $value;
                        	return true;
			}
                }
                $this->flagValueError = true;
                return false;
        }

	/**
         * public bool
         * set phpiniRePerm values with basic data check
         * Returns false if a basic check fails or if $key is unknow
         */
        public function setRePerm($key, $value){
                if ($this->rawCheckRePermData($key, $value)) {
                        $this->phpiniRePerm[$key] = $value;
                        return true;
                }
                $this->flagValueError = true;
                return false;
        }

        /**
         * public bool
         * check reseller permission vor one item mostly for short/fast check in Action script 
         */
	public function checkRePerm($key) {
		if ($this->phpiniRePerm['phpiniSystem'] == "no") { return false; }; // if phpiniSystem is no than all is no regardless what asked
		if ($key == 'phpiniSystem' && $this->phpiniRePerm['phpiniSystem'] == 'yes') { return true ; };
		if ($key == 'phpiniRegisterGlobals' && $this->phpiniRePerm['phpiniRegisterGlobals'] == 'yes') { return true ; };
		if ($key == 'phpiniAllowUrlFopen' && $this->phpiniRePerm['phpiniAllowUrlFopen'] == 'yes') { return true ; };
                if ($key == 'phpiniDisplayErrors' && $this->phpiniRePerm['phpiniDisplayErrors'] == 'yes') { return true ; };
                if ($key == 'phpiniDisableFunctions' && $this->phpiniRePerm['phpiniDisableFunctions'] == 'yes') { return true ; };
		return false;
	}

	/**
         * public bool
         * check reseller MAX permission vor one item mostly for short/fast check in Action script 
         */
	public function checkRePermMax($key,$value) {
                if ($this->phpiniRePerm['phpiniSystem'] == "no") { return false; }; // if phpiniSystem is no than all is no regardless what asked
		if ($key == 'phpiniPostMaxSize' && $value <= $this->phpiniRePerm['phpiniPostMaxSize']) { return true ; };
		if ($key == 'phpiniUploadMaxFileSize' && $value <= $this->phpiniRePerm['phpiniUploadMaxFileSize']) { return true ; };
                if ($key == 'phpiniMaxExecutionTime' && $value <= $this->phpiniRePerm['phpiniMaxExecutionTime']) { return true ; };
                if ($key == 'phpiniMaxInputTime' && $value <= $this->phpiniRePerm['phpiniMaxInputTime']) { return true ; };
                if ($key == 'phpiniMemoryLimit' && $value <= $this->phpiniRePerm['phpiniMemoryLimit']) { return true ; };
                return false;
        }	

        /**
         * protected bool
         * helper method - checks data  
         */
	protected function rawCheckData($key, $value){ //Basic Check against possible values
		if ($key == 'phpiniSystem' &&  ($value == 'yes' || $value == 'no')) { return true ; };
		if ($key == 'phpiniRegisterGlobals' && ($value == 'on' || $value == 'off')) { return true ; }; 
	        if ($key == 'phpiniAllowUrlFopen' && ($value == 'on' || $value == 'off')) { return true ; };
                if ($key == 'phpiniDisplayErrors' && ($value == 'on' || $value == 'off')) { return true ; };
                if ($key == 'phpiniErrorReporting' &&  ($value == '0' || 'E_ALL' 
							|| $value == 'E_ALL ^ (E_NOTICE | E_WARNING)' 
							|| $value == 'E_ALL ^ E_NOTICE '))  { return true ; };
                if ($key == 'phpiniDisableFunctions' && $this->checkDisableFunctionsSyntax($value)) { return true ; };
                if ($key == 'phpiniPostMaxSize' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniUploadMaxFileSize' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniMaxExecutionTime' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniMaxInputTime' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniMemoryLimit' && $value > 0 && $value < 10000 && is_numeric($value)) { return true ; };
		return false;
	}

	/**
         * protected bool
         * helper method - checks disable_funcitons syntax  
         */
	protected function checkDisableFunctionsSyntax($df){
		$phpiniDfAll = array('show_source','system','shell_exec','passthru','exec','shell','symlink','phpinfo');
		$arrhelper = explode(',',$df);
		foreach ($arrhelper as $item){
			if (!in_array($item, $phpiniDfAll)){  // if isnt one of the pieces fo $df one of the $phpiniDfAll (all possibles) than theres something wrong with the input data
				return false;
			}
		}
		return true;
	}

        /**
         * protected bool
         * helper method - checks reseller permission data  
         */
        protected function rawCheckRePermData($key, $value){ //Basic Check against possible values
                if ($key == 'phpiniSystem' &&  ($value == 'yes' || $value == 'no')) { return true ; };
                if ($key == 'phpiniRegisterGlobals' && ($value == 'yes' || $value == 'no')) { return true ; };
                if ($key == 'phpiniAllowUrlFopen' && ($value == 'yes' || $value == 'no')) { return true ; };
                if ($key == 'phpiniDisplayErrors' && ($value == 'yes' || $value == 'no')) { return true ; };
                if ($key == 'phpiniDisableFunctions' && ($value == 'yes' || $value == 'no')) { return true ; };
                if ($key == 'phpiniPostMaxSize' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniUploadMaxFileSize' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniMaxExecutionTime' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniMaxInputTime' && $value >= 0 && $value < 10000 && is_numeric($value)) { return true ; };
                if ($key == 'phpiniMemoryLimit' && $value > 0 && $value < 10000 && is_numeric($value)) { return true ; };
                return false;
        }


	/**
         * public string
         * helper method - assemble disable_funcitons from its parts
	 * $arrDfitem - array
         */
	public function assembleDisableFunctions($arrDfitem){
		if (count($arrDfitem)){
			$phpiniDisableFunctions = implode(',',$arrDfitem);
		} else {
			$phpiniDisableFunctions = '';
		}
		return $phpiniDisableFunctions;
	}

        /**
         * protected 
         * helper method - Load reseller permission from table reseller_props
         * returns DB object with details if reseller has phpini permission else false
         */
	protected function loadRePermFromDb($resellerId){ // Load the default reseller php.ini perm from db
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
    		$rs = exec_query($query, array($resellerId));

		if ($rs->fields('php_ini_system') == 'yes') { // If there custom php.ini allowed at all
        		return $rs;
    		}
   		return false;
	}

        /**
         * protected 
         * helper method - Load php.ini from table php_ini
         * returns DB object with details if custom php.ini exist else false
         */
	protected function loadCustomPHPiniFromDb($domainId){
    		$query = "
                	SELECT
                        	*
	                FROM
        	                `php_ini`
                	WHERE
                        	`domain_id` = ?
        	";
    		$rs = exec_query($query, array($domainId));

    		if ($rs->recordCount()) { // If a Entry found
		        return $rs;
		} 
		return false;
	}
	
	/**
         * public 
         * save custom php.ini to table php_ini
         */
        public function saveCustomPHPiniIntoDb($domainId) {
		if ($this->checkExistCustomPHPini($domainId)) { //if custom ini exist than only update it
                       $query = "UPDATE 
                                        `php_ini` 
                                SET 
                                        `status` = 'change',
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
                        exec_query($query, array($this->phpiniData['phpiniDisableFunctions'],
                                        	$this->phpiniData['phpiniAllowUrlFopen'],
						$this->phpiniData['phpiniRegisterGlobals'],
						$this->phpiniData['phpiniDisplayErrors'],
						$this->phpiniData['phpiniErrorReporting'],
						$this->phpiniData['phpiniPostMaxSize'],
						$this->phpiniData['phpiniUploadMaxFileSize'],
						$this->phpiniData['phpiniMaxExecutionTime'],
						$this->phpiniData['phpiniMaxInputTime'],
						$this->phpiniData['phpiniMemoryLimit'],
						$domainId));
		} else {
                        $query = "INSERT INTO
                                        `php_ini` (
                                                `status`,
                                                `disable_functions`,
                                                `allow_url_fopen`,
                                                `register_globals`,
                                                `display_errors`,
                                                `error_reporting`,
                                                `post_max_size`,
                                                `upload_max_filesize`,
                                                `max_execution_time`,
                                                `max_input_time`,
                                                `memory_limit`,
                                                `domain_id`
                                        ) VALUES (
                                                'new', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                                        )
		               ";
			exec_query($query, array($this->phpiniData['phpiniDisableFunctions'],
                                                $this->phpiniData['phpiniAllowUrlFopen'],
                                                $this->phpiniData['phpiniRegisterGlobals'],
                                                $this->phpiniData['phpiniDisplayErrors'],
                                                $this->phpiniData['phpiniErrorReporting'],
                                                $this->phpiniData['phpiniPostMaxSize'],
                                                $this->phpiniData['phpiniUploadMaxFileSize'],
                                                $this->phpiniData['phpiniMaxExecutionTime'],
                                                $this->phpiniData['phpiniMaxInputTime'],
                                                $this->phpiniData['phpiniMemoryLimit'],
                                                $domainId));
		}

        }
        /**
         * public 
         * delete custom php.ini from table php_ini
         */

        public function delCustomPHPiniFromDb($domainId) {
                if ($this->checkExistCustomPHPini($domainId)) { //if custom ini exist
                        $query = "DELETE FROM 
                                                `php_ini` 
                                        WHERE   
                                                `domain_id` = ?
                                        ";
                        exec_query($query, $domainId);
		}
	}

        /**
         * public bool
         * checks if custom php.ini exist
         */
        public function checkExistCustomPHPini($domainId){
		if ($this->loadCustomPHPiniFromDb($domainId)){
			return true;
		}
		return false;
        }


        /**
         * public array
         * return the phpiniData array
         */
        public function getData(){
                return $this->phpiniData;
        }

	/**
         * public array 
         * return the phpiniData array
         */
	public function getRePerm(){
                return $this->phpiniRePerm;
        }

        /**
         * public string 
         * return 1 value from phpiniRePerm 
         */
        public function getRePermVal($key){
                return $this->phpiniRePerm[$key];
        }

        /**
         * public string
         * get 1 value from $phpiniData for fast/short access in Action Script
         * Returns false if a basic check fails or if $key is unknow
         */
        public function getDataVal($key){
		return $this->phpiniData[$key];
        }

} //End iMSCP_PHPini


