<?php
/**
 * ispCP ω (OMEGA) complete domain backup/restore tool
 * Restore application
 *
 * @copyright 	2010 Thomas Wacker
 * @author 		Thomas Wacker <zuhause@thomaswacker.de>
 * @version 	SVN: $Id$
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
 */

/**
 * ispCP restore packager main class
 */
class RestorePackage_ispCP extends BaseController
{
	/**
	 * Instance of ispCP Database
	 */
	public $db = null;
	/**
	 * domain name (string)
	 */
	protected $domain_name;
	/**
	 * password for archive
	 */
	protected $password;
	/**
	 * IP address or false for default IP address
	 */
	protected $ip;
	/**
	 * ID of server_ips
	 */
	protected $ip_id = -1;
	/**
	 * Reseller name or false for first reseller
	 */
	protected $reseller;
	/**
	 * ID of reseller
	 */
	protected $reseller_id = -1;
	/**
	 * Reseller user properties language
	 */
	protected $reseller_prop_lang = '';
	/**
	 * Reseller user properties template set
	 */
	protected $reseller_prop_layout = '';
	/**
	 * Reseller user properties logo
	 */
	protected $reseller_prop_logo = 0;
	/**
	 * New domain ID
	 */
	protected $domain_id = 0;
	/**
	 * linux user id
	 */
	protected $domain_user_id = 0;
	/**
	 * linux group id
	 */
	protected $domain_group_id = 0;
	/**
	 * file name of gpg encrypted domain package
	 */
	protected $gpg_archive = '';
	/**
	 * file name of tar.gz
	 */
	protected $archive = '';
	/**
	 * array of configuration data (multi associative)
	 */
	protected $configurationData = array();
	/**
	 * database IDs (key = database name)
	 */
	protected $database_ids = array();
	/**
	 * new subdomain IDs (key = old subdomain id)
	 */
	protected $subdomain_ids = array();
	/**
	 * new domain alias IDs (key = old domain alias id)
	 */
	protected $domain_alias_ids = array();
	/**
	 * new web user IDs
	 */
	protected $webuser_ids = array();
	/**
	 * new web group IDs
	 */
	protected $webgroup_ids = array();

	/**
	 * Restore packager
	 * @param string $domain_name
	 * @param string $password password for gpg symmetric decryption
	 * @param mixed $option_ip false or string of target IP address
	 * @param mixed $option_reseller false or string of reseller name
	 */
	public function __construct($domain_name, $password, $option_ip, $option_reseller, $log_level)
	{
		$this->log_level = $log_level;
		$this->password = $password;
		$this->domain_name = $domain_name;
		$this->db = iMSCP_Database::getInstance();
		$this->ip = $option_ip;
		$this->reseller = $option_reseller;
		$this->gpg_archive = ARCHIVE_PATH.'/'.$this->domain_name.'.tar.gz.gpg';
		$this->archive = mb_substr($this->gpg_archive, 0, mb_strlen($this->gpg_archive)-4);
		$this->target_path = ISPCP_VIRTUAL_PATH.'/'.$this->domain_name;

		// untar and mysql can take a lot of time
		set_time_limit(1200);
	}

	/**
	 * Destructor, clean up tmp folder and .tar.gz on exit
	 */
	public function __destruct()
	{
		if (file_exists($this->target_path.'/tmp')) {
			delTree($this->target_path.'/tmp');
		}
		if (file_exists($this->archive)) {
			unlink($this->archive);
		}
	}

	/**
	 * Get ID of first server IP
	 * @return integer ID of IP
	 */
	protected function getDefaultIPID()
	{
		$result = -1;

		$sql = "SELECT `ip_id` FROM `server_ips` ORDER BY `ip_id` LIMIT 0, 1";
		$query = $this->db->prepare($sql);
		$rs = $this->db->execute($query);
		if ($rs && !$rs->EOF) {
			$result = $rs->fields['ip_id'];
		} else {
			$this->logMessage('No IPs found!?', ISPCP_LOG_ERROR);
		}

		return $result;
	}

	/**
	 * Get ID of first reseller
	 * @return integer ID of reseller
	 */
	protected function getDefaultResellerID()
	{
		$result = -1;

		$sql = "SELECT `admin_id` FROM `admin` WHERE `admin_type` = :admin_type" .
			   " ORDER BY `admin_id` LIMIT 0, 1";
		$query = $this->db->prepare($sql);
		$rs = $this->db->execute($query, array(':admin_type'=>'reseller'));
		if ($rs && !$rs->EOF) {
			$result = $rs->fields['admin_id'];
		} else {
			$this->logMessage('No resellers found!?', ISPCP_LOG_ERROR);
		}

		return $result;
	}

	/**
	 * Get ID of reseller name
	 * @param string $reseller name of reseller (e.g. 'res1')
	 * @return integer ispCP database ID of reseller
	 */
	protected function getResellerID($reseller)
	{
		$result = -1;

		$sql = "SELECT `admin_id` FROM `admin` WHERE `admin_type` = :admin_type" .
			   " AND `admin_name` = :name" .
			   " ORDER BY `admin_id` LIMIT 0, 1";
		$query = $this->db->prepare($sql);
		$rs = $this->db->execute(
			$query, array(
				':admin_type'	=> 'reseller',
				':name'			=> $reseller
			)
		);
		if ($rs && !$rs->EOF) {
			$result = $rs->fields['admin_id'];
		} else {
			$this->logMessage('Reseller not found: '.$reseller, ISPCP_LOG_ERROR);
		}

		return $result;
	}

	/**
	 * Get reseller user properties (language, theme)
	 * @return void
	 */
	protected function getResellerUserProps()
	{
		$sql = "SELECT * FROM `user_gui_props` WHERE `user_id`=:user_id";

		$query = $this->db->prepare($sql);
		$rs = $this->db->execute(
			$query, array(
				':user_id'	=> $this->reseller_id
			)
		);
		if ($rs && !$rs->EOF) {
			$this->reseller_prop_lang = $rs->fields['lang'];
			$this->reseller_prop_layout = $rs->fields['layout'];
			$this->reseller_prop_logo = $rs->fields['logo'];
		} else {
			$this->logMessage('Reseller user properties not found: '.$this->reseller_id, ISPCP_LOG_ERROR);
			$this->reseller_prop_lang = 'lang_EnglishBritain';
			$this->reseller_prop_layout = 'omega_original';
			$this->reseller_prop_logo = 0;
		}
	}

	/**
	 * Get ID of IP
	 * @param string $ip IP address
	 * @return integer ispCP database ID of IP
	 */
	protected function getIPID($ip)
	{
		$result = -1;

		$sql = "SELECT `ip_id` FROM `server_ips` WHERE `ip_number`=:ip LIMIT 0, 1";
		$query = $this->db->prepare($sql);
		$rs = $this->db->execute($query, array(':ip' => $ip));
		if ($rs && !$rs->EOF) {
			$result = $rs->fields['ip_id'];
		} else {
			$this->logMessage('IP not found: '.$ip, ISPCP_LOG_ERROR);
		}

		return $result;
	}

	/**
	 * Validate presence of gpg encrypted archive, get ID of IP and reseller
	 */
	protected function initRestore()
	{
		$result = false;

		if (!file_exists($this->gpg_archive)) {
			$this->logMessage('Domain backup package file not found: '.$this->gpg_archive, ISPCP_LOG_ERROR);
		} else {
			// IP detection
			if ($this->ip === false) {
				$this->ip_id = $this->getDefaultIPID();
			} else {
				$this->ip_id = $this->getIPID($this->ip);
			}

			// Reseller detection
			if ($this->reseller === false) {
				$this->reseller_id = $this->getDefaultResellerID();
			} else {
				$this->reseller_id = $this->getResellerID($this->reseller);
			}

			$this->getResellerUserProps();

			if ($this->ip_id != -1 && $this->reseller_id != -1) {
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Unprotected gpg, untar package file
	 * @return boolean true = untared, configuration file found
	 */
	protected function unpackDomainPackage()
	{
		$result = false;

		// unprotect via gpg -c --passphrase ... file
		$cmd = 'gpg --passphrase '.$this->password.' '.$this->gpg_archive;
		$a = array();
		$this->shellExecute($cmd, $a);

		if (file_exists($this->archive)) {

			if (!file_exists($this->target_path)) {
				mkdir($this->target_path, 0770, true);
			}

			$cmd = 'tar xzf '.$this->archive.' -C '.ISPCP_VIRTUAL_PATH;
			$this->shellExecute($cmd, $a);

			if (file_exists($this->target_path.'/tmp/config.ser')) {
				$result = true;
			} else {
				$this->logMessage('File not found: '.$this->target_path.'/tmp/config.ser', ISPCP_LOG_ERROR);
			}

			$result = true;
		} else {
			$this->logMessage('File not found: '.$this->archive.' - incorrect password?', ISPCP_LOG_ERROR);
		}

		return $result;
	}

	/**
	 * Read configuration data of domain package
	 * @return boolean true = configuration file intact
	 */
	protected function getConfigData()
	{
		$result = false;

		$this->logMessage('getConfigData', ISPCP_LOG_INFO);

		$config_file = $this->target_path.'/tmp/config.ser';

		$fp = fopen($config_file, 'r');
		if ($fp) {
			$s = fread($fp, filesize($config_file));
			$this->configurationData = unserialize($s);
			fclose($fp);

			if (is_array($this->configurationData) && count($this->configurationData) > 0) {

				$this->logMessage('configurationData OK', ISPCP_LOG_INFO);

				$result = true;
			} else {
				$this->logMessage('Broken file '.$config_file, ISPCP_LOG_ERROR);
			}

		} else {
			$this->logMessage('Could not open file '.$config_file, ISPCP_LOG_ERROR);
		}

		return $result;
	}

	/**
	 * Start ispCP daemon
	 */
	protected function startDaemon()
	{
		send_request();
	}

	/**
	 * Create all databases
	 */
	protected function createDatabases()
	{
		foreach ($this->configurationData['db'] as $db) {
			$this->logMessage('createDatabases: '.$db['sqld_name'], ISPCP_LOG_INFO);

			$this->importMySQLDatabase($db['sqld_name']);

			// Insert database row into ispCP database
			$query = $this->db->prepare(
				"INSERT INTO `sql_database`".
				" (`domain_id`, `sqld_name`)".
				" VALUES".
				" (:domain_id, :sqld_name)"
			);
			$this->db->execute(
				$query, array(
					':domain_id'	=> $this->domain_id,
					':sqld_name'	=> $db['sqld_name']
				)
			);
			$this->database_ids[$db['sqld_name']] = $this->db->insertId();
		}
	}

	/**
	 * Import single mysql database
	 * @param string $dbname
	 */
	private function importMySQLDatabase($dbname)
	{
		$query = $this->db->prepare("CREATE DATABASE " . quoteIdentifier($dbname));
		$this->db->execute($query);

		$filename = $this->target_path.'/tmp/'.$dbname.'.sql';
		$cmd = 'mysql --user '.iMSCP_Config::get('DB_USER').' --password='.iMSCP_Config::get('DB_PASS').
			   ' '.$dbname.
			   ' <'.$filename;
		// TODO: Error handling
		$a = array();
		$this->shellExecute($cmd, $a);

		return true;
	}

	/**
	 * Create MySQL database users
	 */
	protected function createDatabaseUsers()
	{
		$query = $this->db->prepare(
			"INSERT INTO `sql_user`".
			" (`sqld_id`, `sqlu_name`, `sqlu_pass`)".
			" VALUES".
			" (:sqld_id, :sqlu_name, :sqlu_pass)"
		);

		foreach ($this->configurationData['dbuser'] as $dbuser) {
			$this->createDatabaseUser($dbuser['database'], $dbuser['sqlu_name'], $dbuser['sqlu_pass']);

			// Insert database row into ispCP database
			$this->db->execute(
				$query, array(
					':sqld_id'		=> $this->database_ids[$dbuser['database']],
					':sqlu_name'	=> $dbuser['sqlu_name'],
					':sqlu_pass'	=> encrypt_db_password($dbuser['sqlu_pass'])
				)
			);
		}
	}

	/**
	 * Create database user with all privileges to database
	 * @param string $db_name database name
	 * @param string $db_user database user name
	 * @param string $user_pass password of database user
	 */
	private function createDatabaseUser($db_name, $db_user, $user_pass)
	{
		$this->logMessage('createDatabaseUser: '.$db_user, ISPCP_LOG_INFO);

		$query = $this->db->prepare(
			"GRANT ALL PRIVILEGES ON ". quoteIdentifier($db_name) .
			".* TO ?@? IDENTIFIED BY ?"
		);
		$this->db->execute($query, array($db_user, 'localhost', $user_pass));
		$this->db->execute($query, array($db_user, '%', $user_pass));
	}

	/**
	 * Create domain, sets domain_user_id, domain_group_id and domain_id
	 * @return boolean true = creation successful
	 */
	protected function createDomain()
	{
		$result = false;

		$this->logMessage('createDomain', ISPCP_LOG_INFO);

		$query = $this->db->prepare(
			"INSERT INTO `admin`".
			" (`admin_name`, `admin_pass`, `admin_type`, `domain_created`, `customer_id`, `created_by`, ".
			"  `fname`, `lname`, `gender`, `firm`, `zip`, `city`, `state`, `country`, `email`, `phone`, `fax`, ".
			"  `street1`, `street2`)".
			" VALUES".
			" (:admin_name, :admin_pass, :admin_type, :domain_created, :customer_id, :created_by, ".
			"  :fname, :lname, :gender, :firm, :zip, :city, :state, :country, :email, :phone, :fax, ".
			"  :street1, :street2)"
		);

		$default_values = array(
			'admin_pass'		=> '',
			'domain_created'	=> 0,
			'customer_id'		=> '',
			'fname'				=> '',
			'lname'				=> '',
			'gender'			=> '',
			'firm'				=> '',
			'zip'				=> '',
			'city'				=> '',
			'state'				=> '',
			'country'			=> '',
			'email'				=> '',
			'phone'				=> '',
			'fax'				=> '',
			'street1'			=> '',
			'street2'			=> ''
		);

		$params = $this->paramDBArray($this->configurationData['domain'], $default_values);

		$params[':admin_name'] = $this->domain_name;
		$params[':admin_type'] = 'user';
		$params[':created_by'] = $this->reseller_id;

		if (!$this->db->execute($query, $params)) {
			$this->logMessage('Cannot insert admin database entry!', ISPCP_LOG_ERROR);
			return false;
		}
		$domain_admin_id = $this->db->insertId();
		$this->logMessage('Domain Admin ID: '.$domain_admin_id, ISPCP_LOG_DEBUG);

		// create record for user_gui_props
		$query = $this->db->prepare(
			"INSERT INTO `user_gui_props`".
			" (`user_id`, `layout`, `lang`, `logo`)".
			" VALUES".
			" (:user_id, :layout, :lang, :logo)"
		);
		$params = array(
			':user_id'	=> $domain_admin_id,
			':layout'	=> $this->reseller_prop_layout,
			':lang'		=> $this->reseller_prop_lang,
			':logo'		=> $this->reseller_prop_logo
		);
		if (!$this->db->execute($query, $params)) {
			$this->logMessage('Cannot insert domain database entry!', ISPCP_LOG_ERROR);
			return false;
		}

		// create the domain, record set domain_id
		$query = $this->db->prepare(
			"INSERT INTO `domain`".
			" (`domain_name`, `domain_created`, `domain_expires`, `domain_mailacc_limit`, `domain_ftpacc_limit`, ".
			"  `domain_traffic_limit`, `domain_sqld_limit`, `domain_sqlu_limit`, `domain_alias_limit`, ".
			"  `domain_subd_limit`, `domain_disk_limit`, `domain_php`, `domain_cgi`, `domain_dns`, `allowbackup`, ".
			"  `domain_status`, `domain_created_id`, `domain_admin_id`, `domain_ip_id`)".
			" VALUES".
			" (:domain_name, :domain_created, :domain_expires, :domain_mailacc_limit, :domain_ftpacc_limit, ".
			"  :domain_traffic_limit, :domain_sqld_limit, :domain_sqlu_limit, :domain_alias_limit, ".
			"  :domain_subd_limit, :domain_disk_limit, :domain_php, :domain_cgi, :domain_dns, :allowbackup, ".
			"  :domain_status, :domain_created_id, :domain_admin_id, :domain_ip_id)"
		);

		$default_values = array(
			'domain_name'			=> $this->domain_name,
			'domain_created'		=> 0,
			'domain_expires'		=> 0,
			'domain_mailacc_limit'	=> 0,
			'domain_ftpacc_limit'	=> 0,
			'domain_traffic_limit'	=> 0,
			'domain_sqld_limit'		=> 0,
			'domain_sqlu_limit'		=> 0,
			'domain_alias_limit'	=> 0,
			'domain_subd_limit'		=> 0,
			'domain_disk_limit'		=> 0,
			'domain_php'			=> 'no',
			'domain_cgi'			=> 'no',
			'domain_dns'			=> 'no',
			'allowbackup'			=> 'no'
		);

		$params = $this->paramDBArray($this->configurationData['domain'], $default_values);
		$params[':domain_admin_id']		= $domain_admin_id;
		$params[':domain_created_id']	= $this->reseller_id;
		$params[':domain_status']		= 'toadd';
		$params[':domain_ip_id']		= $this->ip_id;

		if (!$this->db->execute($query, $params)) {
			$this->logMessage('Cannot insert domain database entry!', ISPCP_LOG_ERROR);
			return false;
		}
		$this->domain_id = $this->db->insertId();

		$this->createDomainAliases();
		$this->createSubDomains();

		$this->startDaemon();

		// wait until daemon is ready
		$query = $this->db->prepare(
			"SELECT `domain_gid`, `domain_uid`".
			" FROM `domain`".
			" WHERE `domain_id`=:domain_id"
		);

		$tries = 0;
		$daemon_ready = false;
		do {
			sleep(1);
			$rs = $this->db->execute($query, array(':domain_id'=>$this->domain_id));
			if ($rs && $rs->recordCount() > 0) {
				$row = $rs->fetchRow();
				if (!empty($row['domain_uid'])) {
					// set domain_user_id and domain_group_id
					$this->domain_user_id = $row['domain_uid'];
					$this->domain_group_id = $row['domain_gid'];
					$daemon_ready = true;
					$result = true;
				}
			}
			$tries++;
			if ($tries > 60) {
				$this->logMessage('Error executing domain creation request!', ISPCP_LOG_ERROR);
				$result = false;
				break;
			}
		} while (!$daemon_ready);

		return $result;
	}

	/**
	 * Create domain aliases with subdomains
	 */
	protected function createDomainAliases()
	{
		$querya = $this->db->prepare(
			"INSERT INTO `domain_aliasses`".
			" (`domain_id`, `alias_name`, `alias_status`, `alias_mount`, `alias_ip_id`, `url_forward`)".
			" VALUES".
			" (:domain_id, :name, :status, :mount, :ip_id, :url_forward)"
		);

		$querys = $this->db->prepare(
			"INSERT INTO `subdomain_aliasses`".
			" (`alias_id`, `subdomain_alias_name`, `subdomain_alias_status`, `subdomain_alias_mount`)".
			" VALUES".
			" (:alias_id, :name, :status, :mount)"
		);

		foreach ($this->configurationData['alias'] as $alias) {
			$this->logMessage('Create alias '.$alias['alias_name'], ISPCP_LOG_INFO);

			$this->db->execute(
				$querya, array(
					':domain_id'	=> $this->domain_id,
					':name'			=> $alias['alias_name'],
					':status'		=> 'toadd',
					':mount'		=> $alias['alias_mount'],
					':ip_id'		=> $this->ip_id,
					':url_forward'	=> $alias['url_forward']
				)
			);

			$this->domain_alias_ids[$alias['alias_id']] = $alias_id = $this->db->insertId();
			$this->logMessage(' old alias id='.$alias['alias_id'].' new alias id='.$alias_id, ISPCP_LOG_DEBUG);

			foreach ($alias['subdomain'] as $subdomain) {
				$this->db->execute(
					$querys, array(
						':alias_id'	=> $alias_id,
						':name'		=> $subdomain['subdomain_alias_name'],
						':status'	=> 'toadd',
						':mount'	=> $subdomain['subdomain_alias_mount']
					)
				);
			}
		}
	}

	/**
	 * Create domain subdomains
	 */
	protected function createSubDomains()
	{
		$query = $this->db->prepare(
			"INSERT INTO `subdomain`".
			" (`domain_id`, `subdomain_name`, `subdomain_mount`, `subdomain_status`)".
			" VALUES ".
			" (:domain_id, :name, :mount, :status)"
		);

		foreach ($this->configurationData['subdomain'] as $subdomain) {
			$this->logMessage('Create subdomain '.$subdomain['subdomain_name'], ISPCP_LOG_INFO);

			$this->db->execute(
				$query, array(
					':domain_id'	=> $this->domain_id,
					':name'			=> $subdomain['subdomain_name'],
					':status'		=> 'toadd',
					':mount'		=> $subdomain['subdomain_mount']
				)
			);

			$this->subdomain_ids[$subdomain['subdomain_id']] = $this->db->insertId();
			$this->logMessage(
				' old subdomain id='.$subdomain['subdomain_id'].' new subdomain id='.
				$this->subdomain_ids[$subdomain['subdomain_id']],
				ISPCP_LOG_DEBUG
			);
		}
	}

	/**
	 * Create all E-Mail accounts for domain
	 */
	protected function createEMailAccounts()
	{
		$query = $this->db->prepare(
			"INSERT INTO `mail_users`".
			" (`domain_id`, `mail_acc`, `mail_pass`, `mail_forward`, `mail_type`, `sub_id`, `status`, ".
			"  `mail_auto_respond`, `mail_auto_respond_text`, `quota`, `mail_addr`)".
			" VALUES ".
			" (:domain_id, :mail_acc, :mail_pass, :mail_forward, :mail_type, :sub_id, :status, ".
			"  :mail_auto_respond, :mail_auto_respond_text, :quota, :mail_addr)"
		);

		foreach ($this->configurationData['email'] as $email) {
			$this->createEMailAccount($query, $email);
		}
	}

	/**
	 * Create an E-Mail account
	 * @param object $query prepared query
	 * @param array $email mail_users row
	 */
	protected function createEMailAccount($query, array $email)
	{
		$this->logMessage('createEMailAccount: '.$email['mail_acc'], ISPCP_LOG_INFO);

		// Set sub_id to either subdomain or alias id
		$sub_id = 0;
		if (isset($email['sub_id']) && !empty($email['sub_id']) && isset($email['mail_type'])) {
			$n = strpos($email['mail_type'], 'subdom');
			if ($n !== false) {
				// subdomain
				if (!isset($this->subdomain_ids[$email['sub_id']])) {
					$this->logMessage('domain subdomain id not found: '.$email['sub_id'], ISPCP_LOG_INFO);
					return;
				} else {
					$sub_id = $this->subdomain_ids[$email['sub_id']];
				}
			} else {
				// alias
				if (!isset($this->domain_alias_ids[$email['sub_id']])) {
					$this->logMessage('domain alias id not found: '.$email['sub_id'], ISPCP_LOG_INFO);
					return;
				} else {
					$sub_id = $this->domain_alias_ids[$email['sub_id']];
				}
			}
		}

		$default_values = array(
			'mail_acc'				=> '',
			'mail_forward'			=> '',
			'mail_type'				=> 'normal_mail',
			'mail_auto_respond'		=> 0,
			'mail_auto_respond_text'=> '_no_',
			'quota'					=> 10485760,
			'mail_addr'				=> ''
		);
		$params = $this->paramDBArray($email, $default_values);

		$params[':domain_id'] 	= $this->domain_id;
		$params[':status'] 		= 'toadd';
		$params[':sub_id'] 		= $sub_id;
		if ($email['mail_pass'] != '_no_') {
			$params[':mail_pass'] = encrypt_db_password($email['mail_pass']);
		} else {
			$params[':mail_pass'] = '_no_';
		}

		$this->db->execute($query, $params);
	}

	/**
	 * Create FTP accounts of domain and FTP group record
	 */
	protected function createFTPAccounts()
	{
		$members = '';

		$query = $this->db->prepare(
			"INSERT INTO `ftp_users`".
			" (`userid`, `passwd`, `uid`, `gid`, `shell`, `homedir`)".
			" VALUES ".
			" (:userid, :passwd, :uid, :gid, :shell, :homedir)"
		);

		foreach ($this->configurationData['ftp'] as $ftp) {

			$this->logMessage('createFTPAccounts: '.$ftp['userid'], ISPCP_LOG_INFO);

			$default_values = array(
				'userid'	=> '',
				'passwd'	=> '',
				'shell'		=> '/bin/bash',
				'homedir'	=> ''
			);
			$params = $this->paramDBArray($ftp, $default_values);
			$params[':uid'] = $this->domain_user_id;
			$params[':gid'] = $this->domain_group_id;

			$this->db->execute($query, $params);

			if (!empty($members)) $members .= ',';
			$members .= $ftp['userid'];
		}

		if (!empty($members)) {
			$query = $this->db->prepare(
				"INSERT INTO `ftp_group`".
				" (`groupname`, `gid`, `members`)".
				" VALUES ".
				" (:groupname, :gid, :members)"
			);

			$this->db->execute(
				$query, array(
					':groupname'	=> $this->domain_name,
					':gid'			=> $this->domain_group_id,
					':members'		=> $members
				)
			);
		}
	}

	/**
	 * Create web users (htaccess_users)
	 */
	protected function createWebUsers()
	{
		$query = $this->db->prepare(
			"INSERT INTO `htaccess_users`".
			" (`dmn_id`, `uname`, `upass`, `status`)".
			" VALUES ".
			" (:dmn_id, :uname, :upass, :status)"
		);

		foreach ($this->configurationData['webuser'] as $webuser) {

			$this->logMessage('createWebUsers: '.$webuser['uname'], ISPCP_LOG_INFO);

			$this->db->execute(
				$query, array(
					':dmn_id'		=> $this->domain_id,
					':uname'		=> $webuser['uname'],
					':upass'		=> encrypt_db_password($webuser['upass']),
					':status'		=> 'toadd'
				)
			);

			$this->webuser_ids[$webuser['id']] = $this->db->insertId();
		}
	}

	/**
	 * Create web groups (htaccess_groups)
	 */
	protected function createWebGroups()
	{
		$query = $this->db->prepare(
			"INSERT INTO `htaccess_groups`".
			" (`dmn_id`, `ugroup`, `members`, `status`)".
			" VALUES ".
			" (:dmn_id, :ugroup, :members, :status)"
		);

		foreach ($this->configurationData['webgroup'] as $webgroup) {

			$this->logMessage('createWebGroups: '.$webgroup['ugroup'], ISPCP_LOG_INFO);

			$old_members = explode(',', $webgroup['members']);
			$new_members = array();
			foreach ($old_members as $member_id) {
				if (isset($this->webuser_ids[$member_id])) {
					$new_members[] = $this->webuser_ids[$member_id];
				}
			}

			$this->db->execute(
				$query, array(
					':dmn_id'		=> $this->domain_id,
					':ugroup'		=> $webgroup['ugroup'],
					':members'		=> implode(',', $new_members),
					':status'		=> 'toadd'
				)
			);

			$this->webgroup_ids[$webgroup['id']] = $this->db->insertId();
		}
	}

	/**
	 * Create web access records (htaccess)
	 */
	protected function createWebAccess()
	{
		foreach ($this->configurationData['webaccess'] as $webaccess) {

			$this->logMessage('createWebAccess: '.$webaccess['path'], ISPCP_LOG_INFO);

			$query = $this->db->prepare(
				"INSERT INTO `htaccess`".
				" (`dmn_id`, `user_id`, `group_id`, `auth_type`, `auth_name`, `path`, `status`)".
				" VALUES ".
				" (:dmn_id, :user_id, :group_id, :auth_type, :auth_name, :path, :status)"
			);

			if (!empty($webaccess['user_id'])) {
				if (isset($this->webuser_ids[$webaccess['user_id']])) {
					$webaccess['user_id'] = $this->webuser_ids[$webaccess['user_id']];
				} else {
					$this->logMessage('createWebAccess: missing user_id', ISPCP_LOG_DEBUG);
				}
			}
			if (!empty($webaccess['group_id'])) {
				if (isset($this->webgroup_ids[$webaccess['group_id']])) {
					$webaccess['group_id'] = $this->webgroup_ids[$webaccess['group_id']];
				} else {
					$this->logMessage('createWebAccess: missing group_id', ISPCP_LOG_DEBUG);
				}
			}

			$default_values = array(
				'auth_type'	=> 'Basic',
				'auth_name'	=> '',
				'path'		=> '',
				'user_id'	=> 0,
				'group_id'	=> 0
			);
			$params = $this->paramDBArray($webaccess, $default_values);
			$params[':status'] = 'toadd';
			$params[':dmn_id'] = $this->domain_id;

			$this->db->execute($query, $params);
		}
	}

	/**
	 * Set permissions to vhost directory
	 */
	protected function setDomainPermissions()
	{
		$user = sprintf('vu%04d', $this->domain_user_id);
		$group = sprintf('vu%04d', $this->domain_group_id);

		$cmd = 'chown -R '.$user.':www-data'.' '.$this->target_path;
		$a = array();
		$this->shellExecute($cmd, $a);

		$cmd = 'chown -R '.$user.':'.$group.' '.$this->target_path.'/htdocs';
		$a = array();
		$this->shellExecute($cmd, $a);

		$cmd = 'chown -R '.$user.':'.$group.' '.$this->target_path.'/cgi-bin';
		$a = array();
		$this->shellExecute($cmd, $a);

		// TODO: Set permissions for subdomains
		foreach ($this->configurationData['alias'] as $alias) {
			$cmd = 'chown -R '.$user.':www-data '.$this->target_path.$alias['alias_mount'];
			$a = array();
			$this->shellExecute($cmd, $a);

			$cmd = 'chown -R '.$user.':'.$group.' '.$this->target_path.$alias['alias_mount'].'/htdocs';
			$a = array();
			$this->shellExecute($cmd, $a);

			$cmd = 'chown '.$user.':www-data '.$this->target_path.$alias['alias_mount'].'/htdocs';
			$a = array();
			$this->shellExecute($cmd, $a);
		}
	}

	protected function createDNSEntries()
	{
		// TODO: createDNSEntries
		$this->logMessage('createDNSEntries: currently not supported', ISPCP_LOG_DEBUG);
	}

	/**
	 * Run the restore, main method
	 * @return boolean true = restore successful, false = see error messages
	 */
	public function runRestore()
	{
		$result = false;

		if ($this->initRestore()) {
			if ($this->unpackDomainPackage()) {
				if ($this->getConfigData()) {

					if ($this->createDomain()) {
						$this->createDNSEntries();
						$this->createDatabases();
						$this->createDatabaseUsers();
						$this->createEMailAccounts();
						$this->createWebUsers();
						$this->createWebGroups();
						$this->createWebAccess();
						$this->createFTPAccounts();
						$this->setDomainPermissions();
						$this->startDaemon();

						$result = ($this->errorCount == 0);
					}
				}
			}
		}

		return $result;
	}
}
