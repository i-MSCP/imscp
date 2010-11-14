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

require_once dirname(__FILE__).'/iBackupPackage.php';
require_once dirname(__FILE__).'/BackupPackage.php';

class BackupPackage_iMSCP extends BackupPackage implements iBackupPackage
{
	/**
	 * Instance of i-MSCP Database
	 */
	public $db = null;
	/**
	 * i-MSCP domain ID
	 */
	public $domain_id = 0;
	/**
	 * i-MSCP domain user ID
	 */
	public $domain_user_id = 0;
	/**
	 * i-MSCP database IDs (name => id)
	 */
	protected $db_ids = array();

	public function __construct($domain_name, $password, $log_level)
	{
		parent::__construct($domain_name, $password, $log_level);
		$this->db = iMSCP_Database::getInstance();
	}

	/**
	 * Get domain database id, validate if vhost path exists
	 * @return bool true = init ok, false = see error message
	 */
	protected function initDomain()
	{
		$result = false;

		if (!file_exists(IMSCP_VIRTUAL_PATH.'/'.$this->domain_name)) {
			$this->logMessage('Domain not found in '.IMSCP_VIRTUAL_PATH.'/'.$this->domain_name, IMSCP_LOG_ERROR);
		} else {
			$test = $this->getDomainID($this->domain_name);
			if ($test != -1) {
				$result = true;
			} else {
				$this->logMessage('Domain not in database: '.$this->domain_name, IMSCP_LOG_ERROR);
			}
		}

		return $result;
	}

	/**
	 * Get i-MSCP domain id of domain name
	 * @param string $domainname name of domain
	 * @return integer domain id, -1 if not present
	 */
	protected function getDomainID($domain_name)
	{
		$this->domain_id = -1;

		$query = "SELECT `domain_id`, `domain_uid` FROM `domain`".
				 " WHERE `domain_name` = :domain_name";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':domain_name'=>$domain_name));
		if ($rs->recordCount() > 0) {
			$this->domain_id = $rs->fields['domain_id'];
			$this->domain_user_id = $rs->fields['domain_uid'];
		}

		return $this->domain_id;
	}

	public function getDomainConfig()
	{
		$result = array();

		$fields = "`domain`.*, `admin`.*";

		$sql = "SELECT ".$fields." FROM `domain`, `admin`".
			   " WHERE `domain`.`domain_id` = :id AND `admin`.`admin_id` = `domain`.`domain_admin_id`";

		$query = $this->db->prepare($sql);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		if ($rs && $rs->recordCount() > 0) {
			$result = $rs->fields;
			unset($result['domain_id']);
			unset($result['domain_gid']);
			unset($result['domain_uid']);
		} else {
			$this->logMessage('Error reading domain configuration from database!', IMSCP_LOG_ERROR);
		}

		return $result;
	}

	public function getEMailConfig()
	{
		$result = array();

		$fields = "`mail_users`.*";

		$query = "SELECT ".$fields." FROM `mail_users`".
				 " WHERE `mail_users`.`domain_id` = :id";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		while (($row = $rs->fetchRow())) {

			if ($row['mail_pass'] != '_no_') {
				$row['mail_pass'] = decrypt_db_password($row['mail_pass']);
			}
			$result[] = $row;
		}

		return $result;
	}

	public function getFTPConfig()
	{
		$result = array();

		$fields = "`ftp_users`.*";

		$query = "SELECT ".$fields." FROM `ftp_users`".
				 " WHERE `ftp_users`.`uid` = :uid";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':uid'=>$this->domain_user_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$result[] = $row;
		}

		return $result;
	}

	public function getDomainAliasConfig()
	{
		$result = array();

		$fields = "`domain_aliasses`.*";

		$query = "SELECT ".$fields." FROM `domain_aliasses`".
				 " WHERE `domain_aliasses`.`domain_id` = :id";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$row['subdomain'] = $this->getSubdomainAliasConfig($row['alias_id']);
			$result[] = $row;
		}

		return $result;
	}

	protected function getSubdomainAliasConfig($alias_id)
	{
		$result = array();

		$fields = "`subdomain_alias`.*";

		$query = "SELECT ".$fields." FROM `subdomain_alias`".
				 " WHERE `subdomain_alias`.`alias_id` = :aid";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':aid'=>$alias_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$result[] = $row;
		}

		return $result;
	}

	public function getSubDomainConfig()
	{
		$result = array();

		$fields = "`subdomain`.*";

		$query = "SELECT ".$fields." FROM `subdomain`".
				 " WHERE `subdomain`.`domain_id` = :id";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$result[] = $row;
		}

		return $result;
	}

	public function getWebUserConfig()
	{
		$result = array();

		$fields = "`htaccess_users`.*";

		$query = "SELECT ".$fields." FROM `htaccess_users`".
				 " WHERE `htaccess_users`.`dmn_id` = :id";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$result[] = $row;
		}

		return $result;
	}

	public function getWebGroupConfig()
	{
		$result = array();

		$fields = "`htaccess_groups`.*";

		$query = "SELECT ".$fields." FROM `htaccess_groups`".
				 " WHERE `htaccess_groups`.`dmn_id` = :id";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$result[] = $row;
		}

		return $result;
	}

	public function getWebAccessConfig()
	{
		$result = array();

		$fields = "`htaccess`.*";

		$query = "SELECT ".$fields." FROM `htaccess`".
				 " WHERE `htaccess`.`dmn_id` = :id";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$result[] = $row;
		}

		return $result;
	}

	public function getDNSConfig()
	{
		$result = array();

		$rs = $this->db->execute("SHOW TABLES LIKE 'domain_dns'");
		if ($rs && !$rs->EOF) {
			$fields = "`domain_dns`.`alias_id`".
					  ", `domain_aliasses`.`domain_dns`".
					  ", `domain_aliasses`.`domain_class`".
					  ", `domain_aliasses`.`domain_type`".
					  ", `domain_aliasses`.`domain_text`";

			$query = $this->db->prepare(
				"SELECT ".$fields." FROM `domain_dns`".
				" WHERE `domain_dns`.`domain_id` = :id"
			);
			$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
			while ($rs && ($row = $rs->fetchRow())) {
				$result[] = $row;
			}
		}

		return $result;
	}

	public function getDBConfig()
	{
		$result = array();

		$fields = "`sql_database`.`sqld_name`".
				  ", `sql_database`.`sqld_id`";

		$query = "SELECT ".$fields." FROM `sql_database`".
				 " WHERE `sql_database`.`domain_id` = :id";

		$query = $this->db->prepare($query);
		$rs = $this->db->execute($query, array(':id'=>$this->domain_id));
		while ($rs && ($row = $rs->fetchRow())) {
			$this->db_ids[$row['sqld_name']] = $row['sqld_id'];
			$this->addDatabase('mysql', $row['sqld_name']);
			$result[] = $row;
		}

		return $result;
	}

	public function getDBUserConfig()
	{
		$result = array();

		$fields = "`sql_user`.`sqlu_name`".
				  ", `sql_user`.`sqlu_pass`";

		$query = "SELECT ".$fields." FROM `sql_user`".
				 " WHERE `sql_user`.`sqld_id` = :sqld_id";
		$query = $this->db->prepare($query);

		foreach ($this->db_ids as $dbname => $sqld_id) {
			$rs = $this->db->execute($query, array(':sqld_id'=>$sqld_id));
			while ($rs && ($row = $rs->fetchRow())) {
				$row['database'] = $dbname;
				$row['sqlu_pass'] = decrypt_db_password($row['sqlu_pass']);
				$result[] = $row;
			}
		}

		return $result;
	}
}
