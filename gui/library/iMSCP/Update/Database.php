<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
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
 * @category    iMSCP
 * @package     iMSCP_Update
 * @subpackage  Database
 * @copyright   2010-2014 by i-MSCP team
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Update */
require_once 'iMSCP/Update.php';

/**
 * Update Database class
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @subpackage  Database
 */
class iMSCP_Update_Database extends iMSCP_Update
{
	/**
	 * @var iMSCP_Update
	 */
	protected static $instance;

	/**
	 * Database name being updated
	 *
	 * @var string
	 */
	protected $databaseName;

	/**
	 * Tells whether or not a request must be send to the i-MSCP daemon after that
	 * all database updates were applied.
	 *
	 * @var bool
	 */
	protected $_daemonRequest = false;

	/**
	 * @var int Last database update revision
	 */
	protected $lastUpdate = 190;

	/**
	 * Singleton - Make new unavailable
	 */
	protected function __construct()
	{
		if (isset(iMSCP_Registry::get('config')->DATABASE_NAME)) {
			$this->databaseName = iMSCP_Registry::get('config')->DATABASE_NAME;
		} else {
			throw new iMSCP_Update_Exception('Database name not found.');
		}
	}

	/**
	 * Singleton - Make clone unavailable
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements Singleton design pattern
	 *
	 * @return iMSCP_Update_Database
	 */
	public static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Checks for available database update
	 *
	 * @return bool TRUE if a database update is available, FALSE otherwise
	 */
	public function isAvailableUpdate()
	{
		if ($this->getLastAppliedUpdate() < $this->getNextUpdate()) {
			return true;
		}

		return false;
	}

	/**
	 * Apply database updates
	 *
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function applyUpdates()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		/** @var $pdo PDO */
		$pdo = iMSCP_Database::getRawInstance();

		while ($this->isAvailableUpdate()) {
			$revision = $this->getNextUpdate();
			$updateMethod = 'r' . $revision;
			$queries = (array)$this->$updateMethod();

			if (!empty($queries)) {
				try {
					$pdo->beginTransaction();

					foreach ($queries as $query) {
						if (!empty($query)) {
							$pdo->query($query);
						}
					}

					$dbConfig['DATABASE_REVISION'] = $revision;
					$pdo->commit();
				} catch (Exception $e) {
					$pdo->rollBack();
					$this->setError(sprintf('Database update %s failed: %s', $revision, $e->getMessage()));
					return false;
				}
			} else {
				$dbConfig['DATABASE_REVISION'] = $revision;
			}
		}

		if (PHP_SAPI != 'cli' && $this->_daemonRequest) {
			send_request();
		}

		return true;
	}

	/**
	 * Returns database update(s) details
	 *
	 * @return array
	 */
	public function getDatabaseUpdatesDetails()
	{
		$updatesDetails = array();

		$reflection = new ReflectionClass(__CLASS__);

		foreach (range($this->getNextUpdate(), $this->getLastUpdate()) as $revision) {
			$methodName = "r$revision";

			if ($reflection->hasMethod($methodName)) {
				$method = $reflection->getMethod($methodName);
				$details = explode("\n", $method->getDocComment());
				$normalizedDetails = '';
				array_shift($details);

				foreach ($details as $detail) {
					if (preg_match('/^(?: |\t)*\*(?: |\t)+([^@]*)$/', $detail, $matches)) {
						if (empty($normalizedDetails)) {
							$normalizedDetails = $matches[1];
						} else {
							$normalizedDetails .= '<br />' . $matches[1];
						}
					} else {
						break;
					}
				}

				$updatesDetails[$revision] = $normalizedDetails;
			}
		}

		return $updatesDetails;
	}

	/**
	 * Return next database update revision
	 *
	 * @return int 0 if no update is available
	 */
	protected function getNextUpdate()
	{
		$lastAvailableUpdateRevision = $this->getLastAvailableUpdateRevision();
		$nextUpdateRevision = $this->getLastAppliedUpdate();

		if ($nextUpdateRevision < $lastAvailableUpdateRevision) {
			return $nextUpdateRevision + 1;
		}

		return 0;
	}

	/**
	 * Return last database update revision
	 *
	 * @return int
	 */
	public function getLastUpdate()
	{
		return $this->lastUpdate;
	}

	/**
	 * Returns last database update revision number
	 *
	 * @return int Last database update revision number
	 */
	protected function getLastAvailableUpdateRevision()
	{
		return $this->getLastUpdate();
	}

	/**
	 * Returns the revision number of the last applied database update
	 *
	 * @return int Revision number of the last applied database update
	 */
	protected function getLastAppliedUpdate()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (!isset($dbConfig['DATABASE_REVISION'])) {
			$dbConfig['DATABASE_REVISION'] = 1;
		}

		return $dbConfig['DATABASE_REVISION'];
	}

	/**
	 * Rename table
	 *
	 * @param string $table Table name
	 * @return null|string SQL statement to be executed
	 */
	protected function renameTable($table, $newTableName)
	{
		$table = quoteIdentifier($table);
		$stmt = exec_query('SHOW TABLES LIKE ?', $table);

		if ($stmt->rowCount()) {
			return sprintf('ALTER IGNORE TABLE %s RENAME TO %s', $table, quoteIdentifier($newTableName));
		}

		return null;
	}

	/**
	 * Drop table
	 *
	 * @param string $table Table name
	 */
	public function dropTable($table)
	{
		return sprintf('DROP TABLE IF EXISTS %s', quoteIdentifier($table));
	}

	/**
	 * Add column
	 *
	 * @param string $table Table name
	 * @param string $column Column name
	 * @param string $columnDefinition Column definition
	 * @return null|string SQL statement to be executed
	 */
	protected function addColumn($table, $column, $columnDefinition)
	{
		$table = quoteIdentifier($table);
		$stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", $column);

		if (!$stmt->rowCount()) {
			return sprintf('ALTER IGNORE TABLE %s ADD %s %s', $table, quoteIdentifier($column), $columnDefinition);
		}

		return null;
	}

	/**
	 * Change column
	 *
	 * @param string $table Table name
	 * @param string $column Column name
	 * @param string $columnDefinition Column definition
	 * @return null|string SQL statement to be executed
	 */
	protected function changeColumn($table, $column, $columnDefinition)
	{
		$table = quoteIdentifier($table);
		$stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", $column);

		if ($stmt->rowCount()) {
			return sprintf('ALTER IGNORE TABLE %s CHANGE %s %s', $table, quoteIdentifier($column), $columnDefinition);
		}

		return null;
	}

	/**
	 * Drop column
	 *
	 * @param string $table Table name
	 * @param string $column Column name
	 * @return null|string SQL statement to be executed
	 */
	protected function dropColumn($table, $column)
	{
		$table = quoteIdentifier($table);
		$stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", $column);

		if ($stmt->rowCount()) {
			return sprintf('ALTER IGNORE TABLE %s DROP %s', $table, quoteIdentifier($column));
		}

		return null;
	}

	/**
	 * Add index
	 *
	 * @param string $table Database table name
	 * @param array|string $columns Column name(s)
	 * @param string $indexType Index type (PRIMARY KEY (default), INDEX|KEY, UNIQUE)
	 * @param string $indexName Index name (default is autogenerated)
	 * @return null|string SQL statement to be executed
	 */
	protected function addIndex($table, $columns, $indexType = 'PRIMARY KEY', $indexName = '')
	{
		$table = quoteIdentifier($table);
		$indexType = strtoupper($indexType);

		$indexName = ($indexType == 'PRIMARY KEY')
			? 'PRIMARY'
			: (($indexName == '') ? ((is_array($columns)) ? $columns[0] : $columns) : $indexName);

		$stmt = exec_query("SHOW INDEX FROM $table WHERE KEY_NAME = ?", $indexName);

		if (!$stmt->rowCount()) {
			if (is_array($columns)) {
				$columns = implode(',', array_map('quoteIdentifier', $columns));
			} else {
				$columns = quoteIdentifier($columns);
			}

			return sprintf(
				'ALTER IGNORE TABLE %s ADD %s %s (%s)',
				$table,
				$indexType,
				($indexName == 'PRIMARY') ? '' : quoteIdentifier($indexName),
				$columns
			);
		}

		return null;
	}

	/**
	 * Drop any index which belong to the given column in the given table
	 *
	 * @param string $table Table name
	 * @param string $column Column name
	 * @return array SQL statements to be executed
	 */
	protected function dropIndexByColumn($table, $column)
	{
		$sqlUpd = array();

		$table = quoteIdentifier($table);
		$stmt = exec_query("SHOW INDEX FROM $table WHERE COLUMN_NAME = ?", $column);

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$row = array_change_key_case($row, CASE_UPPER);

				$sqlUpd[] = sprintf(
					'ALTER IGNORE TABLE %s DROP INDEX %s', $table, quoteIdentifier($row['KEY_NAME'])
				);
			}
		}

		return $sqlUpd;
	}

	/**
	 * Drop the given index from the given table
	 *
	 * @param string $table Table name
	 * @param string $indexName Index name
	 * @return null|string SQL statement to be executed
	 */
	protected function dropIndexByName($table, $indexName = 'PRIMARY')
	{
		$table = quoteIdentifier($table);
		$stmt = exec_query("SHOW INDEX FROM $table WHERE KEY_NAME = ?", $indexName);

		if ($stmt->rowCount()) {
			return sprintf('ALTER IGNORE TABLE %s DROP INDEX %s', $table, quoteIdentifier($indexName));
		}

		return null;
	}

	/**
	 * Catch any database updates that were removed
	 *
	 *
	 * @throws iMSCP_Update_Exception
	 * @param  string $updateMethod Database update method name
	 * @param array $params Params
	 * @return null
	 */
	public function __call($updateMethod, $params)
	{
		if (!preg_match('/^r[0-9]+$/', $updateMethod)) {
			throw new iMSCP_Update_Exception(sprintf('%s is not a valid database update method', $updateMethod));
		}

		return null;
	}

	/**
	 * Please, add all the database update methods below. Don't forget to update the lastUpdate field.
	 */

	/**
	 * Fixes some CSRF issues in admin log
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r46()
	{
		return 'TRUNCATE TABLE log';
	}

	/**
	 * Removes useless 'suexec_props' table
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r47()
	{
		return $this->dropTable('suexec_props');
	}

	/**
	 * #14: Adds table for software installer
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r48()
	{
		$sqlUpd = array(
			"
	 			CREATE TABLE IF NOT EXISTS web_software (
					software_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					software_master_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					reseller_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					software_name VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_version VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_language VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_type VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_db TINYINT(1) NOT NULL,
					software_archive VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_installfile VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_prefix VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_link VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_desc MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_active INT(1) NOT NULL,
					software_status VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					rights_add_by INT(10) UNSIGNED NOT NULL DEFAULT '0',
					software_depot VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
	  				PRIMARY KEY  (software_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			",
			"
				CREATE TABLE IF NOT EXISTS web_software_inst (
					domain_id INT(10) UNSIGNED NOT NULL,
					alias_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					subdomain_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					subdomain_alias_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					software_id INT(10) NOT NULL,
					software_master_id INT(10) UNSIGNED NOT NULL DEFAULT '0',
					software_res_del INT(1) NOT NULL DEFAULT '0',
					software_name VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_version VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_language VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					path VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					software_prefix VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					db VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					database_user VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					database_tmp_pwd VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					install_username VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					install_password VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					install_email VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					software_status VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					software_depot VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  					KEY software_id (software_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			",
			$this->addColumn(
				'domain',
				'domain_software_allowed',
				"VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no' AFTER domain_dns"
			),
			$this->addColumn(
				'reseller_props',
				'software_allowed',
				"VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no' AFTER reseller_ips"
			),
			$this->addColumn(
				'reseller_props',
				'softwaredepot_allowed',
				"VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes' AFTER software_allowed"
			)
		);

		$stmt = exec_query('SELECT id, props FROM hosting_plans');

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$props = explode(';', $row['props']);
				$id = quoteValue($row['id'], PDO::PARAM_INT);

				if (count($props) == 12) {
					$sqlUpd[] = "UPDATE hosting_plans SET props = CONCAT(props,';_no_') WHERE id = $id";
				}
			}
		}

		return $sqlUpd;
	}

	/**
	 * Adds i-MSCP daemon service properties in config table
	 *
	 * @return null
	 */
	protected function r50()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$dbConfig['PORT_IMSCP_DAEMON'] = '9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1';

		return null;
	}

	/**
	 * Adds required field for on-click-logon from the ftp-user site.
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r51()
	{
		return $this->addColumn(
			'ftp_users', 'rawpasswd', "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL AFTER passwd"
		);
	}

	/**
	 * Adds new options for applications installer
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r52()
	{
		$sqlUpd = array(
			'
				CREATE TABLE IF NOT EXISTS web_software_depot (
					package_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					package_install_type VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
					package_title VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					package_version VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
					package_language VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
					package_type VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
					package_description MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					package_vendor_hp VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					package_download_link VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					package_signature_link VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (package_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			',
			"
				CREATE TABLE IF NOT EXISTS web_software_options (
					use_webdepot TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
					webdepot_xml_url VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
					webdepot_last_update DATETIME NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			",
			"
				REPLACE INTO web_software_options (
					use_webdepot, webdepot_xml_url, webdepot_last_update
				) VALUES (
					'1', 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00'
				)
			",
			$this->addColumn(
				'web_software',
				'software_installtype',
				'VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT NULL AFTER reseller_id'
			),
			"UPDATE web_software SET software_installtype = 'install'",
			$this->addColumn(
				'reseller_props',
				'websoftwaredepot_allowed',
				"VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT NULL DEFAULT 'yes' AFTER softwaredepot_allowed"
			)
		);

		return $sqlUpd;
	}

	/**
	 * Decrypts email, ftp and SQL users passwords in database
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r53()
	{
		$sqlUpd = array();

		// Decrypt all mail passwords

		$stmt = execute_query(
			"
				SELECT
					mail_id, mail_pass
				FROM
					mail_users
				WHERE
					mail_type RLIKE '^(normal_mail|alias_mail|subdom_mail|alssub_mail)'
			"
		);

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$password = quoteValue(decryptBlowfishCbcPassword($row['mail_pass']));
				$status = quoteValue('tochange');
				$mailId = quoteValue($row['mail_id'], PDO::PARAM_INT);
				$sqlUpd[] = "UPDATE mail_users SET mail_pass = $password, status = $status WHERE mail_id = $mailId";
			}
		}

		// Decrypt all SQL users passwords

		$stmt = exec_query('SELECT sqlu_id, sqlu_pass FROM sql_user');

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$password = quoteValue(decryptBlowfishCbcPassword($row['sqlu_pass']));
				$id = quoteValue($row['sqlu_id'], PDO::PARAM_INT);
				$sqlUpd[] = "UPDATE sql_user SET sqlu_pass = $password WHERE sqlu_id = $id";
			}
		}

		// Decrypt all Ftp users passwords

		$stmt = exec_query('SELECT userid, passwd FROM ftp_users');

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$password = quoteValue(decryptBlowfishCbcPassword($row['passwd']));
				$userId = quoteValue($row['userid']);
				$sqlUpd[] = "UPDATE ftp_users SET rawpasswd = $password WHERE userid = $userId";
			}
		}

		return $sqlUpd;
	}

	/**
	 * Converts all tables to InnoDB engine
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r60()
	{
		$sqlUpd = array();

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		foreach ($db->getTables() as $table) {
			$table = quoteIdentifier($table);
			$sqlUpd[] = "ALTER TABLE $table ENGINE=InnoDB";
		}

		return $sqlUpd;
	}

	/**
	 * Deletes old DUMP_GUI_DEBUG parameter from the config table
	 *
	 * @return null
	 */
	protected function r66()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig->DUMP_GUI_DEBUG)) {
			$dbConfig->del('DUMP_GUI_DEBUG');
		}

		return null;
	}

	/**
	 * #124: Enhancement - Switch to gettext (Machine Object Files)
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r67()
	{
		$sqlUpd = array();

		// First step: Update default language (new naming convention)

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig['USER_INITIAL_LANG'])) {
			$dbConfig['USER_INITIAL_LANG'] = str_replace('lang_', '', $dbConfig['USER_INITIAL_LANG']);
		}

		// Second step: Removing all database languages tables

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		foreach ($db->getTables('lang_%') as $tableName) {
			$sqlUpd[] = $this->dropTable($tableName);
		}

		// Third step: Update users language property

		$languagesMap = array(
			'Arabic' => 'ar', 'Azerbaijani' => 'az_AZ', 'BasqueSpain' => 'eu_ES',
			'Bulgarian' => 'bg_BG', 'Catalan' => 'ca_ES', 'ChineseChina' => 'zh_CN',
			'ChineseHongKong' => 'zh_HK', 'ChineseTaiwan' => 'zh_TW', 'Czech' => 'cs_CZ',
			'Danish' => 'da_DK', 'Dutch' => 'nl_NL', 'EnglishBritain' => 'en_GB',
			'FarsiIran' => 'fa_IR', 'Finnish' => 'fi_FI', 'FrenchFrance' => 'fr_FR',
			'Galego' => 'gl_ES', 'GermanGermany' => 'de_DE', 'GreekGreece' => 'el_GR',
			'Hungarian' => 'hu_HU', 'ItalianItaly' => 'it_IT', 'Japanese' => 'ja_JP',
			'Lithuanian' => 'lt_LT', 'NorwegianNorway' => 'nb_NO', 'Polish' => 'pl_PL',
			'PortugueseBrazil' => 'pt_BR', 'Portuguese' => 'pt_PT', 'Romanian' => 'ro_RO',
			'Russian' => 'ru_RU', 'Slovak' => 'sk_SK', 'SpanishArgentina' => 'es_AR',
			'SpanishSpain' => 'es_ES', 'Swedish' => 'sv_SE', 'Thai' => 'th_TH',
			'Turkish' => 'tr_TR', 'Ukrainian' => 'uk_UA'
		);

		// Updates language property of each users by using new naming convention
		foreach ($languagesMap as $language => $locale) {
			$locale = quoteValue($locale);
			$language = quoteValue("lang_$language");

			$sqlUpd[] = "UPDATE user_gui_props SET lang = $locale WHERE lang = $language";
		}

		return $sqlUpd;
	}

	/**
	 * #119: Defect - Error when adding IP's
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r68()
	{
		$sqlUpd = array();

		$stmt = exec_query("SELECT ip_id, ip_card FROM server_ips");

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$cardname = explode(':', $row['ip_card']);
				$cardname = quoteValue($cardname[0]);
				$ipId = quoteValue($row['ip_id']);

				$sqlUpd[] = "UPDATE server_ips SET ip_card = $cardname WHERE ip_id = $ipId";
			}
		}

		return $sqlUpd;
	}

	/**
	 * Some fixes for the user_gui_props table
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r69()
	{
		return array(
			$this->changeColumn('user_gui_props', 'user_id', 'user_id INT(10) UNSIGNED NOT NULL'),
			$this->changeColumn(
				'user_gui_props',
				'layout',
				'layout VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
			),
			$this->changeColumn(
				'user_gui_props',
				'logo',
				"logo VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
			),
			$this->changeColumn(
				'user_gui_props',
				'lang',
				'lang VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
			),
			"UPDATE user_gui_props SET logo = '' WHERE logo = '0'"
		);
	}

	/**
	 * Changes the log table schema to allow storage of large messages
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r71()
	{
		return $this->changeColumn(
			'log', 'log_message', 'log_message TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
		);
	}

	/**
	 * Adds unique index on the web_software_options.use_webdepot column
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r72()
	{
		return $this->addIndex('web_software_options', 'use_webdepot', 'unique');
	}

	/**
	 * Adds unique index on user_gui_props.user_id column
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r76()
	{
		$sqlUpd = $this->dropIndexByColumn('user_gui_props', 'user_id');
		array_push($sqlUpd, $this->addIndex('user_gui_props', 'user_id', 'unique'));

		return $sqlUpd;
	}

	/**
	 * Drops useless user_gui_props.id column
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r77()
	{
		return $this->dropColumn('user_gui_props', 'id');
	}

	/**
	 * #175: Fix for mail_addr saved in mail_type_forward too
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r78()
	{
		return array(
			"
				REPLACE INTO mail_users (
					mail_id, mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
					mail_auto_respond_text, quota, mail_addr
				) SELECT
					mail_id, mail_acc, mail_pass, mail_forward, t1.domain_id, mail_type, sub_id, status,
					mail_auto_respond, mail_auto_respond_text, quota, CONCAT(mail_acc, '@', domain_name) AS mail_addr
				FROM
					mail_users AS t1
				LEFT JOIN
					domain AS t2 ON (t1.domain_id = t2.domain_id)
				WHERE
					t1.mail_type = 'normal_forward'
				AND
					t1.mail_addr = ''
			",
			"
				REPLACE INTO mail_users(
					mail_id, mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
					mail_auto_respond_text, quota, mail_addr
				) SELECT
					mail_id, mail_acc, mail_pass, mail_forward, t1.domain_id, mail_type, sub_id, status,
					mail_auto_respond, mail_auto_respond_text, quota, CONCAT(mail_acc, '@', alias_name) AS mail_addr
				FROM
					mail_users AS t1
				LEFT JOIN
					domain_aliasses AS t2 ON (t1.sub_id = t2.alias_id)
				WHERE
					t1.mail_type = 'alias_forward'
				AND
					t1.mail_addr = ''
			",
			"
				REPLACE INTO mail_users (
					mail_id, mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
					mail_auto_respond_text, quota, mail_addr
				) SELECT
					mail_id, mail_acc, mail_pass, mail_forward, t1.domain_id, mail_type, sub_id, status,
					mail_auto_respond, mail_auto_respond_text, quota,
					CONCAT(mail_acc, '@', subdomain_alias_name, '.', alias_name) AS mail_addr
				FROM
					mail_users AS t1
				LEFT JOIN
					subdomain_alias AS t2 ON (t1.sub_id = t2.subdomain_alias_id)
				LEFT JOIN
					domain_aliasses AS t3 ON (t2.alias_id = t3.alias_id)
				WHERE
					t1.mail_type = 'alssub_forward'
				AND
					t1.mail_addr = ''
			",
			"
				REPLACE INTO mail_users(
					mail_id, mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
					mail_auto_respond_text, quota, mail_addr
				) SELECT
					mail_id, mail_acc, mail_pass, mail_forward, t1.domain_id, mail_type, sub_id, status,
					mail_auto_respond, mail_auto_respond_text, quota,
					CONCAT(mail_acc, '@', subdomain_name, '.', domain_name) AS mail_addr
				FROM
					mail_users AS t1
				LEFT JOIN
					subdomain AS t2 ON (t1.sub_id = t2.subdomain_id)
				LEFT JOIN
					domain AS t3 ON (t2.domain_id = t3.domain_id)
				WHERE
					t1.mail_type = 'subdom_forward' AND t1.mail_addr = ''
			"
		);
	}

	/**
	 * #15: Feature - PHP Editor -  Add/Update system wide values
	 *
	 * @return null
	 */
	protected function r84()
	{
		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		$dbConfig['PHPINI_ALLOW_URL_FOPEN'] = 'off';
		$dbConfig['PHPINI_DISPLAY_ERRORS'] = 'off';
		$dbConfig['PHPINI_REGISTER_GLOBALS'] = 'off';
		$dbConfig['PHPINI_UPLOAD_MAX_FILESIZE'] = '2';
		$dbConfig['PHPINI_POST_MAX_SIZE'] = '8';
		$dbConfig['PHPINI_MEMORY_LIMIT'] = '128';
		$dbConfig['PHPINI_MAX_INPUT_TIME'] = '60';
		$dbConfig['PHPINI_MAX_EXECUTION_TIME'] = '30';
		$dbConfig['PHPINI_ERROR_REPORTING'] = 'E_ALL & ~E_NOTICE';
		$dbConfig['PHPINI_DISABLE_FUNCTIONS'] = 'show_source,system,shell_exec,passthru,exec,phpinfo,shell,symlink';

		return null;
	}

	/**
	 * #15: Feature - PHP Editor - Add columns for PHP directives
	 * #202: Bug - Unknown column php_ini_al_disable_functions in reseller_props table
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r85()
	{
		return array(
			// Reseller permissions columns for PHP directives
			$this->addColumn(
				'reseller_props',
				'php_ini_system',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER websoftwaredepot_allowed"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_al_disable_functions',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER php_ini_system"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_al_allow_url_fopen',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER php_ini_al_disable_functions"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_al_register_globals',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER php_ini_al_allow_url_fopen"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_al_display_errors',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER php_ini_al_register_globals"
			),

			// Reseller max. allowed values columns for PHP directives
			$this->addColumn(
				'reseller_props',
				'php_ini_max_post_max_size',
				"int(11) NOT NULL DEFAULT '8' AFTER php_ini_al_display_errors"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_max_upload_max_filesize',
				"int(11) NOT NULL DEFAULT '2' AFTER php_ini_max_post_max_size"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_max_max_execution_time',
				"int(11) NOT NULL DEFAULT '30' AFTER php_ini_max_upload_max_filesize"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_max_max_input_time',
				"int(11) NOT NULL DEFAULT '60' AFTER php_ini_max_max_execution_time"
			),
			$this->addColumn(
				'reseller_props',
				'php_ini_max_memory_limit',
				"int(11) NOT NULL DEFAULT '128' AFTER php_ini_max_max_input_time"
			),

			// Domain permissions columns for PHP directives
			$this->addColumn(
				'domain',
				'phpini_perm_system',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER domain_software_allowed"
			),
			$this->addColumn(
				'domain',
				'phpini_perm_register_globals',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER phpini_perm_system"
			),
			$this->addColumn(
				'domain',
				'phpini_perm_allow_url_fopen',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER phpini_perm_register_globals"
			),
			$this->addColumn(
				'domain',
				'phpini_perm_display_errors',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER phpini_perm_allow_url_fopen"
			),
			$this->addColumn(
				'domain',
				'phpini_perm_disable_functions',
				"VARCHAR(15) NOT NULL DEFAULT 'no' AFTER phpini_perm_allow_url_fopen"
			)
		);
	}

	/**
	 * #15: Feature - PHP directives editor: Add php_ini table
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r86()
	{
		return
			// php_ini table for custom PHP directives (per domain)
			"CREATE TABLE IF NOT EXISTS php_ini (
				id INT(11) NOT NULL AUTO_INCREMENT,
				domain_id INT(10) NOT NULL,
				status VARCHAR(55) COLLATE utf8_unicode_ci NOT NULL,
				disable_functions VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'show_source,system,shell_exec,passthru,exec,phpinfo,shell,symlink',
				allow_url_fopen VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
				register_globals VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
				display_errors VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
				error_reporting VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E_ALL & ~E_NOTICE',
				post_max_size INT(11) NOT NULL DEFAULT '8',
				upload_max_filesize INT(11) NOT NULL DEFAULT '2',
				max_execution_time INT(11) NOT NULL DEFAULT '30',
				max_input_time INT(11) NOT NULL DEFAULT '60',
				memory_limit INT(11) NOT NULL DEFAULT '128',
				PRIMARY KEY (ID)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";
	}

	/**
	 * Add hosting plan properties for PHP editor
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r87()
	{
		$sqlUpd = array();

		$stmt = execute_query("SELECT id, props FROM hosting_plans");

		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$props = explode(';', $data['props']);

				if (count($props) == 13) {
					$sqlUpd[] = "
						UPDATE
							hosting_plans
						SET
							props = ';no;no;no;no;no;8;2;30;60;128'
						WHERE
							id = {$data['id']}
					";
				}
			}
		}

		return $sqlUpd;
	}

	/**
	 * Several fixes for the PHP directives editor including issue #195
	 *
	 * Note: For consistency reasons, this update will reset the feature values.
	 *
	 * @return array Stack of SQL statements to be executed
	 */
	protected function r88()
	{
		$sqlUpd = array();

		// Reset reseller permissions
		foreach (
			array(
				'php_ini_system', 'php_ini_al_disable_functions', 'php_ini_al_allow_url_fopen',
				'php_ini_al_register_globals', 'php_ini_al_display_errors'
			) as $permission
		) {
			$sqlUpd[] = "UPDATE reseller_props SET $permission = 'no'";
		}

		// Reset reseller default values for PHP directives (To default system wide value)
		foreach (
			array(
				'post_max_size' => '8',
				'upload_max_filesize' => '2',
				'max_execution_time' => '30',
				'max_input_time' => '60',
				'memory_limit' => '128'
			) as $directive => $defaultValue
		) {
			$sqlUpd[] = "UPDATE reseller_props SET php_ini_max_{$directive} = '$defaultValue'";
		}

		return $sqlUpd;
	}

	/**
	 * Truncate the php_ini table (related to r88)
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r89()
	{
		$sqlupd = 'TRUNCATE TABLE php_ini';

		// Schedule backend process in case user do update from frontend
		$this->_daemonRequest = true;

		return $sqlupd;
	}

	/**
	 * Drop unused table auto_num
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r91()
	{
		return $this->dropTable('auto_num');
	}

	/**
	 * #238: Delete orphan php_ini entries in the php.ini table
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r92()
	{
		return 'DELETE FROM php_ini WHERE domain_id NOT IN (SELECT domain_id FROM domain)';
	}

	/**
	 * Rename php_ini.ID column to php_ini.id
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r93()
	{
		return $this->changeColumn('php_ini', 'ID', 'id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT');
	}

	/**
	 * Database schema update (UNIQUE KEY to PRIMARY KEY for some fields)
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r95()
	{
		return array(
			$this->addIndex('domain', 'domain_id'), // Add primary key
			$this->dropIndexByName('domain', 'domain_id'), // Remove unique index

			$this->addIndex('email_tpls', 'id'), // Add primary key
			$this->dropIndexByName('email_tpls', 'id'), // Remove unique index

			$this->addIndex('hosting_plans', 'id'), // Add primary key
			$this->dropIndexByName('hosting_plans', 'id'), // Remove unique index

			$this->addIndex('htaccess', 'id'), // Add primary key
			$this->dropIndexByName('htaccess', 'id'), // Remove unique index

			$this->addIndex('htaccess_groups', 'id'), // Add primary key
			$this->dropIndexByName('htaccess_groups', 'id'), // Remove unique index

			$this->addIndex('htaccess_users', 'id'), // Add primary key
			$this->dropIndexByName('htaccess_users', 'id'), // Remove unique index

			$this->addIndex('reseller_props', 'id'), // Add primary key
			$this->dropIndexByName('reseller_props', 'id'), // Remove unique index

			$this->addIndex('server_ips', 'ip_id'), // Add primary key
			$this->dropIndexByName('server_ips', 'ip_id'), // Remove unique index

			$this->addIndex('sql_database', 'sqld_id'), // Add primary key
			$this->dropIndexByName('sql_database', 'sqld_id'), // Remove unique index

			$this->addIndex('sql_user', 'sqlu_id'), // Add primary key
			$this->dropIndexByName('sql_user', 'sqlu_id') // Remove unique index
		);
	}

	/**
	 * #292: Feature - Layout color chooser
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r96()
	{
		return $this->addColumn(
			'user_gui_props',
			'layout_color',
			"VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER layout"
		);
	}

	/**
	 * Allow to change SSH port number
	 *
	 * @return null
	 */
	protected function r97()
	{
		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig['PORT_SSH'])) {
			$dbConfig['PORT_SSH'] = '22;tcp;SSH;1;1;';
		}

		return null;
	}

	/**
	 * Update level propertie for custom menus
	 *
	 * @return array SQL statement to be executed
	 */
	protected function r98()
	{
		return array(
			"UPDATE custom_menus SET menu_level = 'A' WHERE menu_level = 'admin'",
			"UPDATE custom_menus SET menu_level = 'R' WHERE menu_level = 'reseller'",
			"UPDATE custom_menus SET menu_level = 'C' WHERE menu_level = 'user'",
			"UPDATE custom_menus SET menu_level = 'RC' WHERE menu_level = 'all'"
		);
	}

	/**
	 * #228: Enhancement - Multiple HTTPS domains on same IP + wildcard SSL
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r100()
	{
		return
			"CREATE TABLE IF NOT EXISTS ssl_certs (
				cert_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				id INT(10) NOT NULL,
				`type` ENUM('dmn','als','sub','alssub') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn',
				password VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`key` TEXT COLLATE utf8_unicode_ci NOT NULL,
				cert TEXT COLLATE utf8_unicode_ci NOT NULL,
				ca_cert TEXT COLLATE utf8_unicode_ci,
				status VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (cert_id),
				KEY id (id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";
	}

	/**
	 * Add order option for custom menus
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r101()
	{
		return $this->addColumn(
			'custom_menus', 'menu_order', 'INT UNSIGNED NULL AFTER menu_level, ADD INDEX (menu_order)'
		);
	}

	/**
	 * Add plugin table for plugins management
	 *
	 * Note: Not used at this moment.
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r103()
	{
		return "
			CREATE TABLE IF NOT EXISTS plugin (
				plugin_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				plugin_name VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				plugin_type VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
				plugin_info TEXT COLLATE utf8_unicode_ci NOT NULL,
				plugin_config TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
				plugin_status VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'disabled',
				PRIMARY KEY (plugin_id),
				UNIQUE KEY name (plugin_name)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";
	}

	/**
	 * Update for the mail_users table structure
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r104()
	{
		return array(
			// change to allows forward mail list
			'
				ALTER IGNORE TABLE
					mail_users
				CHANGE
					mail_acc mail_acc
				TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			',
			// change to fix with RFC
			'
				ALTER IGNORE TABLE
					mail_users
				CHANGE
					mail_addr mail_addr
				VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL
			'
		);
	}

	/**
	 * Added parameter to allow the admin to append some paths to the default PHP open_basedir directive of customers
	 *
	 * @return null
	 */
	protected function r105()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (!isset($dbConfig['PHPINI_OPEN_BASEDIR'])) {
			$dbConfig['PHPINI_OPEN_BASEDIR'] = '';
		}

		return null;
	}

	/**
	 * Database schema update (KEY for some fields)
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r106()
	{
		return array(
			$this->addIndex('admin', 'created_by', 'index'),
			$this->addIndex('domain_aliasses', 'domain_id', 'index'),
			$this->addIndex('mail_users', 'domain_id', 'index'),
			$this->addIndex('reseller_props', 'reseller_id', 'index'),
			$this->addIndex('sql_database', 'domain_id', 'index'),
			$this->addIndex('sql_user', 'sqld_id', 'index'),
			$this->addIndex('subdomain', 'domain_id', 'index'),
			$this->addIndex('subdomain_alias', 'alias_id', 'index')
		);
	}

	/**
	 * #366: Enhancement - Move menu label show/disable option at user profile level
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r107()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig['MAIN_MENU_SHOW_LABELS'])) {
			$dbConfig->del('MAIN_MENU_SHOW_LABELS');
		}

		return $this->addColumn('user_gui_props', 'show_main_menu_labels', "tinyint(1) NOT NULL DEFAULT '1'");
	}

	/**
	 * #157: Enhancement - External Mail server feature
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r109()
	{
		$sqlUpd = array(
			$this->addColumn('domain', 'domain_external_mail', "VARCHAR(15) NOT NULL DEFAULT 'no'"),
			$this->addColumn('domain', 'external_mail', "VARCHAR(15) NOT NULL DEFAULT 'off'"),
			$this->addColumn('domain', 'external_mail_dns_ids', "VARCHAR(255) NOT NULL"),
			$this->addColumn('domain_aliasses', 'external_mail', "VARCHAR(15) NOT NULL DEFAULT 'off'"),
			$this->addColumn('domain_aliasses', 'external_mail_dns_ids', "VARCHAR(255) NOT NULL")
		);

		$stmt = execute_query("SELECT id, props FROM hosting_plans");

		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$props = explode(';', $data['props']);

				if (count($props) == 23) {
					$sqlUpd[] = "
						UPDATE
							hosting_plans
						SET
							props = CONCAT(props, ';_no_')
						WHERE
							id = {$data['id']}
					";
				}
			}
		}

		return $sqlUpd;
	}

	/**
	 * #157: Enhancement - Relaying Domains
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r110()
	{
		return array(
			$this->dropColumn('domain', 'external_mail_status'),
			$this->dropColumn('domain_aliasses', 'external_mail_status'),
		);
	}

	/**
	 * Update for the quotalimits and quotatallies table structure
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r112()
	{
		return array(
			"
				ALTER TABLE
					quotalimits
				CHANGE
					name name
				VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
			",
			"
				ALTER TABLE
					quotatallies
				CHANGE
					name name
				VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
			"
		);
	}

	/**
	 * #433: Defect - register_globals does not exist in php 5.4.0 and above
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r113()
	{
		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig['PHPINI_REGISTER_GLOBALS'])) {
			$dbConfig->del('PHPINI_REGISTER_GLOBALS');
		}

		$sqlUpd = array(
			$this->dropColumn('domain', 'phpini_perm_register_globals'),
			$this->dropColumn('reseller_props', 'php_ini_al_register_globals'),
			$this->dropColumn('php_ini', 'register_globals')
		);

		$stmt = execute_query("SELECT id, props FROM hosting_plans");

		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$props = explode(';', $data['props']);

				if (count($props) == 24) {
					unset($props[15]); // Remove register global properties

					$sqlUpd[] = "
						UPDATE
							hosting_plans
						SET
							props = '" . implode(';', $props) . "'
						WHERE
							id = ''
						{$data['id']}
					";
				}
			}
		}
	}

	/**
	 * #447: External mail server feature is broken
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r114()
	{
		return array(
			// domain_dns.domain_id field should never be set to zero
			"
				UPDATE
					domain_dns AS t1
				SET
					t1.domain_id = (
						SELECT t2.domain_id FROM domain_aliasses AS t2 WHERE t1.alias_id = t2.alias_id
					)
				WHERE
					t1.domain_id = 0
			",
			// domain_dns.domain_dns field should not be empty (domain related entries)
			"
				UPDATE
					domain_dns AS t1
				SET
					t1.domain_dns = CONCAT(
						(SELECT t2.domain_name FROM domain AS t2 WHERE t1.domain_id = t2.domain_id), '.'
					)
				WHERE
					t1.domain_dns = ''
				AND
					t1.protected = 'yes'
			",
			// domain_dns.domain_dns field should not be empty (domain aliases related entries)
			"
				UPDATE
					domain_dns AS t1
				SET
					t1.domain_dns = CONCAT(
						(SELECT t2.alias_name FROM domain_aliasses AS t2 WHERE t1.alias_id = t2.alias_id),
						'.'
					)
				WHERE
					t1.domain_dns = ''
				AND
					t1.protected = 'yes'
			",
			// domain_dns.domain_dns with value * must be completed with the domain name (domain related entries)
			"
				UPDATE
					domain_dns AS t1
				SET
					t1.domain_dns = CONCAT(
						'*.',
						(SELECT t2.domain_name FROM domain AS t2 WHERE t1.domain_id = t2.domain_id),
						'.'
					)
				WHERE
					t1.alias_id = 0
				AND
					t1.domain_dns = '*'
				AND
					t1.protected = 'yes'
			",
			// domain_dns.domain_dns with value * must be completed with the domain name (domain aliases related entries)
			"
				UPDATE
					domain_dns AS t1
				SET
					t1.domain_dns = CONCAT(
						'*.',
						(SELECT t2.alias_name FROM domain_aliasses AS t2 WHERE t1.alias_id = t2.alias_id),
						'.'
					)
				WHERE
					t1.alias_id <> 0
				AND
					t1.domain_dns = '*'
				AND
					t1.protected = 'yes'
			",
			// If a domain has only wildcard MX entries for external servers, update the domain.external_mail field to 'wildcard'
			"
				UPDATE
					domain AS t1
				SET
					t1.external_mail = 'wildcard'
				WHERE
					0 = (
						SELECT
							COUNT(t2.domain_dns_id)
						FROM
							domain_dns AS t2
						WHERE
							t2.domain_id = t1.domain_id
						AND
							t2.alias_id = 0
						AND
							t2.domain_dns NOT LIKE '*.%'
					)
				AND
					t1.external_mail = 'on'
			",
			// If a domain alias has only wildcard MX entries for external servers, update the domain.external_mail field to 'wildcard'
			"
				UPDATE
					domain_aliasses AS t1
				SET
					t1.external_mail = 'wildcard'
				WHERE
					t1.alias_id <> 0
				AND
					0 = (
						SELECT COUNT(
							t2.domain_dns_id)
						FROM
							domain_dns AS t2
						WHERE
							t2.alias_id = t1.alias_id
						AND
							t2.domain_dns NOT LIKE '*.%'
					)
				AND
					t1.external_mail = 'on'
				",
			// Custom DNS CNAME record set via external mail feature are no longer allowed (User will have to re-add them)
			// via the custom DNS interface (easy update way)
			"DELETE FROM domain_dns WHERE domain_type = 'CNAME' AND protected = 'yes'"
		);
	}

	/**
	 * #145: Deletes possible orphan items in many tables
	 *
	 * Moved from database update 70 due to duplicate key in foreign keys map.
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r115()
	{
		$sqlUpd = array();

		$tablesToForeignKey = array(
			'email_tpls' => 'owner_id',
			'hosting_plans' => 'reseller_id',
			'reseller_props' => 'reseller_id',
			'tickets' => array('ticket_to', 'ticket_from'),
			'user_gui_props' => 'user_id',
			'web_software' => 'reseller_id'
		);

		$stmt = execute_query('SELECT admin_id FROM admin');
		$usersIds = implode(',', $stmt->fetchall(PDO::FETCH_COLUMN));

		foreach ($tablesToForeignKey as $table => $foreignKey) {
			if (is_array($foreignKey)) {
				foreach ($foreignKey as $key) {
					$sqlUpd[] = "DELETE FROM $table WHERE $key NOT IN ($usersIds)";
				}
			} else {
				$sqlUpd[] = "DELETE FROM $table WHERE $foreignKey NOT IN ($usersIds)";
			}
		}

		return $sqlUpd;
	}

	/**
	 * Disk detail integration
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r116()
	{
		return array(
			$this->addColumn('domain', 'domain_disk_file', 'bigint(20) unsigned default NULL AFTER domain_disk_usage'),
			$this->addColumn('domain', 'domain_disk_mail', 'bigint(20) unsigned default NULL AFTER domain_disk_file'),
			$this->addColumn('domain', 'domain_disk_sql', 'bigint(20) unsigned default NULL AFTER domain_disk_mail')
		);
	}

	/**
	 * Deletion of useless tables
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r117()
	{
		return array(
			$this->dropTable('roundcube_session'),
			$this->dropTable('roundcube_searches'),
			$this->dropTable('roundcube_identities'),
			$this->dropTable('roundcube_dictionary'),
			$this->dropTable('roundcube_contactgroupmembers'),
			$this->dropTable('roundcube_contacts'),
			$this->dropTable('roundcube_contactgroups'),
			$this->dropTable('roundcube_cache_thread'),
			$this->dropTable('roundcube_cache_messages'),
			$this->dropTable('roundcube_cache_index'),
			$this->dropTable('roundcube_cache'),
			$this->dropTable('roundcube_users'),
		);
	}

	/**
	 * Fix Arabic locale name
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r118()
	{
		return "UPDATE user_gui_props SET lang = 'ar' WHERE lang = 'ar_AE'";
	}

	/**
	 * Lowercase PHP INI boolean
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r119()
	{
		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		$dbConfig['PHPINI_ALLOW_URL_FOPEN'] = 'off';
		$dbConfig['PHPINI_DISPLAY_ERRORS'] = 'off';

		return array(
			"UPDATE php_ini SET allow_url_fopen = 'on' WHERE allow_url_fopen = 'On'",
			"UPDATE php_ini SET allow_url_fopen = 'off' WHERE allow_url_fopen = 'Off'",
			"UPDATE php_ini SET display_errors = 'on' WHERE display_errors = 'On'",
			"UPDATE php_ini SET display_errors = 'off' WHERE display_errors = 'Off'"
		);
	}

	/**
	 * #552: Bug - PHP constants are not recognized outside of PHP (such as in Apache vhost files)
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r120()
	{
		$sqlUpd = array();

		$constantToInteger = array(
			'E_ALL & ~E_NOTICE & ~E_WARNING' => '30711', // Switch to E_ALL & ~E_NOTICE
			'E_ALL & ~E_DEPRECATED' => '22527', // Production
			'E_ALL & ~E_NOTICE' => '30711', // Default
			'E_ALL | E_STRICT' => '32767' // Development
		);

		foreach ($constantToInteger as $c => $i) {
			$sqlUpd[] = "UPDATE config SET `value` = '$i' WHERE name = 'PHPINI_ERROR_REPORTING' AND `value` ='$c'";
			$sqlUpd[] = "UPDATE php_ini SET error_reporting = '$i' WHERE error_reporting = '$c'";
		}

		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$dbConfig->forceReload();

		return $sqlUpd;
	}

	/**
	 * Update for url forward fields
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r122()
	{
		return array(
			"
				ALTER TABLE
					domain_aliasses CHANGE url_forward url_forward
				VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'
			",
			"
				ALTER TABLE
					subdomain CHANGE subdomain_url_forward subdomain_url_forward
				VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'
			",
			"
				ALTER TABLE
					subdomain_alias CHANGE subdomain_alias_url_forward subdomain_alias_url_forward
				VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'
			",
			"
				UPDATE domain_aliasses SET url_forward = 'no' WHERE url_forward IS NULL OR url_forward = ''",
			"
				UPDATE
					subdomain
				SET
					subdomain_url_forward = 'no'
				WHERE
					subdomain_url_forward IS NULL
				OR
					subdomain_url_forward = ''
			",
			"
				UPDATE
					subdomain_alias
				SET
					subdomain_alias_url_forward = 'no'
				WHERE
					subdomain_alias_url_forward IS NULL
				OR
					subdomain_alias_url_forward = ''
			"
		);
	}

	/**
	 * Adds admin.admin_status column
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r123()
	{
		return $this->addColumn(
			'admin',
			'admin_status',
			"VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ok' AFTER uniqkey_time"
		);
	}

	/**
	 * Adds admin.admin_sys_uid and admin.admin_sys_gid columns
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r124()
	{
		return array(
			$this->addColumn('admin', 'admin_sys_uid', "INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER admin_type"),
			$this->addColumn('admin', 'admin_sys_gid', "INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER admin_sys_uid")
		);
	}

	/**
	 * Update admin.admin_sys_uid and admin.admin_sys_gid columns with data from domain table
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r125()
	{
		$sqlUpdt = '';

		$stmt = exec_query("SHOW COLUMNS FROM domain LIKE 'domain_uid'");

		if ($stmt->rowCount()) {
			$sqlUpdt = "
				UPDATE
					admin AS t1
				JOIN
					domain AS t2 ON(t2.domain_admin_id = t1.admin_id)
				SET
					t1.admin_sys_uid = t2.domain_uid,
					t1.admin_sys_gid = t2.domain_gid
			";
		}

		return $sqlUpdt;
	}

	/**
	 * Drop domain.domain_uid and domain.domain_gid columns
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r126()
	{
		return array(
			$this->dropColumn('domain', 'domain_uid'),
			$this->dropColumn('domain', 'domain_gid')
		);
	}

	/**
	 * Add ftp_users.admin_id column (foreign key)
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r127()
	{
		return $this->addColumn(
			'ftp_users', 'admin_id', "INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER userid, ADD INDEX (admin_id)"
		);
	}

	/**
	 * Update ftp_users.admin_id column with data from admin table
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r128()
	{
		return "
			UPDATE
				ftp_users AS t1
			JOIN
				admin AS t2 ON (t2.admin_sys_uid = t1.uid)
			SET
				t1.admin_id = t2.admin_id
		";
	}

	/**
	 * Add web_folder_protection column in domain table
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r129()
	{
		return $this->addColumn(
			'domain',
			'web_folder_protection',
			"VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes' AFTER external_mail_dns_ids"
		);
	}

	/**
	 * Set web folder protection option to 'no' for any existent customer
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r130()
	{
		return "UPDATE domain SET web_folder_protection = 'no'";
	}

	/**
	 * Drop orders and orders_settings tables
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r131()
	{
		return array(
			$this->dropTable('orders'),
			$this->dropTable('orders_settings')
		);
	}

	/**
	 * Drop useless columns in hosting_plan table
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r133()
	{
		return array(
			$this->dropColumn('hosting_plans', 'price'),
			$this->dropColumn('hosting_plans', 'setup_fee'),
			$this->dropColumn('hosting_plans', 'value'),
			$this->dropColumn('hosting_plans', 'vat'),
			$this->dropColumn('hosting_plans', 'payment'),
			$this->dropColumn('hosting_plans', 'tos')
		);
	}

	/**
	 * Delete order component related parameters
	 *
	 * @return null
	 */
	protected function r134()
	{
		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig['CUSTOM_ORDERPANEL_ID'])) {
			$dbConfig->del('CUSTOM_ORDERPANEL_ID');
		}

		if (isset($dbConfig['ORDERS_EXPIRE_TIME'])) {
			$dbConfig->del('ORDERS_EXPIRE_TIME');
		}

		return null;
	}

	/**
	 * Drop straff_settings table
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r135()
	{
		return $this->dropTable('straff_settings');
	}

	/**
	 * Drop useless php_ini.status column
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r136()
	{
		return $this->dropColumn('php_ini', 'status');
	}

	/**
	 * Update plugin.plugin_status column
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r137()
	{
		return '
			ALTER TABLE
				plugin
			CHANGE
				plugin_status plugin_status TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL
		';
	}

	/**
	 * Add plugin_backend column in plugin table
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r138()
	{
		return $this->addColumn(
			'plugin',
			'plugin_backend',
			"VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"
		);
	}

	/**
	 * Update objects status
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r140()
	{
		$map = array(
			'ssl_certs' => 'status',
			'admin' => 'admin_status',
			'domain' => 'domain_status',
			'domain_aliasses' => 'alias_status',
			'subdomain' => 'subdomain_status',
			'subdomain_alias' => 'subdomain_alias_status',
			'mail_users' => 'status',
			'htaccess' => 'status',
			'htaccess_groups' => 'status',
			'htaccess_users' => 'status'
		);

		$sqlUpd = array();

		$tochange = 'tochange';
		$todelete = 'todelete';

		foreach ($map as $table => $field) {
			$sqlUpd[] = "UPDATE $table SET $field = '$tochange' WHERE $field IN('change', 'dnschange')";
			$sqlUpd[] = "UPDATE $table SET $field = '$todelete' WHERE $field = 'delete'";
		}

		return $sqlUpd;
	}

	/**
	 * Add plugin_plugin_error columns
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r141()
	{
		$sqlUdp = array();

		if (
			($q = $this->addColumn(
				'plugin',
				'plugin_error',
				"TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER plugin_status"
			)) != ''
		) {
			$sqlUdp[] = $q;
		}

		if (!empty($sqlUdp)) {
			$enabled = 'enabed';
			$disabled = 'disabled';
			$uninstalled = 'uninstalled';
			$toinstall = 'toinstall';
			$toupdate = 'toupdate';
			$touninstall = 'touninstall';
			$toenable = 'toenable';
			$todisable = 'todisable';
			$todelete = 'todelete';

			$sqlUdp[] = "
				UPDATE
					plugin AS t1
				JOIN
					plugin AS t2 ON (t2.plugin_id = t1.plugin_id)
				SET
					t1.plugin_status = '$toinstall',
					t1.plugin_error = t2.plugin_status
				WHERE
					t1.plugin_status NOT IN(
						'$enabled', '$disabled', '$uninstalled', '$toinstall', '$toupdate', '$touninstall', '$toenable',
						'$todisable', '$todelete'
					)
			";

			$sqlUdp[] = "
				ALTER TABLE
					plugin
				CHANGE
					 plugin_status plugin_status VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci
					 NOT NULL DEFAULT 'uninstalled';
			";
		}

		return $sqlUdp;
	}

	/**
	 * Removes ports entries for unsupported services
	 *
	 * @return null|string
	 */
	protected function r142()
	{
		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig['PORT_AMAVIS'])) {
			$dbConfig->del('PORT_AMAVIS');
		}

		if (isset($dbConfig['PORT_SPAMASSASSIN'])) {
			$dbConfig->del('PORT_SPAMASSASSIN');
		}

		return null;
	}

	/**
	 * Add Web folders protection option propertie to hosting plans
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r143()
	{
		$sqlUpd = array();

		$stmt = execute_query('SELECT id, props FROM hosting_plans');

		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$props = explode(';', $data['props']);

				if (count($props) == 23) {
					$sqlUpd[] = "UPDATE hosting_plans SET props = CONCAT(props,';_no_') WHERE id = {$data['id']}";
				}
			}
		}

		return $sqlUpd;
	}

	/**
	 * Update sql_user.sqlu_name column
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r144()
	{
		return "
			ALTER TABLE
				sql_user
			CHANGE
				sqlu_name sqlu_name VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'n/a'
		";
	}

	/**
	 * Store plugins info and config as json data instead of serialized data
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r145()
	{
		$sqlUdp = array();

		$stmt = execute_query('SELECT plugin_id, plugin_info, plugin_config FROM plugin');

		if ($stmt->rowCount()) {
			$db = iMSCP_Database::getRawInstance();

			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				if (!isJson($row['plugin_info'])) {
					$pluginInfo = $db->quote(json_encode(unserialize($row['plugin_info'])));
				} else {
					$pluginInfo = $db->quote($row['plugin_info']);
				}

				if (!isJson($row['plugin_config'])) {
					$pluginConfig = $db->quote(json_encode(unserialize($row['plugin_config'])));
				} else {
					$pluginConfig = $db->quote($row['plugin_config']);
				}

				$sqlUdp[] = "
					UPDATE
						plugin
					SET
						plugin_info = $pluginInfo, plugin_config = $pluginConfig
					WHERE
						plugin_id = {$row['plugin_id']}
				";
			}
		}

		return $sqlUdp;
	}

	/**
	 * Add unique key for server_ips columns
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r148()
	{
		return $this->addIndex('server_ips', 'ip_number', 'unique');
	}

	/**
	 * Adds unique index for sqld_name columns
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r149()
	{
		$sqlUdp = $this->dropIndexByColumn('sql_user', 'sqlu_name');

		array_unshift($sqlUdp, $this->addIndex('sql_database', 'sqld_name', 'unique'));

		return $sqlUdp;
	}

	/**
	 * Update domain_dns.domain_text column to 255 characters
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r150()
	{
		return '
			ALTER TABLE
				domain_dns
			CHANGE
				domain_text domain_text VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
		';
	}

	/**
	 * Update domain_dns table to allow sharing between several components (core, plugins..)
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r151()
	{
		$sqlUpd = array();

		$stmt = exec_query("SHOW COLUMNS FROM domain_dns LIKE 'protected'");

		if ($stmt->rowCount()) {
			$sqlUpd[] = "
				ALTER TABLE
					domain_dns
				CHANGE
					protected owned_by VARCHAR(255)
				CHARACTER SET
					utf8 COLLATE utf8_unicode_ci
				NOT NULL DEFAULT
					'custom_dns_feature'
			";
		};

		$sqlUpd[] = "UPDATE domain_dns SET owned_by = 'custom_dns_feature' WHERE owned_by = 'no'";
		$sqlUpd[] = "UPDATE domain_dns SET owned_by = 'ext_mail_feature' WHERE domain_type = 'MX' AND owned_by = 'yes'";

		return $sqlUpd;
	}

	/**
	 * Update domain_dns.domain_dns column to 255 characters
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r152()
	{
		return '
			ALTER TABLE
				domain_dns
			CHANGE
				domain_dns domain_dns VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
		';
	}

	/**
	 * Add domain.mail_quota column
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r155()
	{
		return $this->addColumn('domain', 'mail_quota', 'BIGINT(20) UNSIGNED NOT NULL');
	}

	/**
	 * Synchronize mail quota values
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r156()
	{
		$sqlUpd = array();

		$stmt = execute_query('SELECT id, props FROM hosting_plans');

		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$props = explode(';', $data['props']);

				if (count($props) == 24) {
					list(, , , , , , , , , $diskspace) = $props;
					$diskspace = $diskspace * 1048576; // MiB to bytes

					$sqlUpd[] = "
						UPDATE
							hosting_plans
						SET
							props = CONCAT(props, ';$diskspace')
						WHERE
							id = {$data['id']}
					";
				}
			}
		}

		return $sqlUpd;
	}

	/**
	 * Fix possible inconsistencies in hosting plan properties
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r157()
	{
		$sqlUpd = array();

		$stmt = execute_query(
			"
			SELECT
				t1.id, t1.reseller_id, t1.props,
				IFNULL(t2.php_ini_max_post_max_size, '99999999') AS post_max_size,
				IFNULL(t2.php_ini_max_upload_max_filesize, '99999999') AS upload_max_filesize,
				IFNULL(t2.php_ini_max_max_execution_time, '99999999') AS max_execution_time,
				IFNULL(t2.php_ini_max_max_input_time, '99999999') AS max_input_time,
				IFNULL(t2.php_ini_max_memory_limit, '99999999') AS memory_limit
			FROM
				hosting_plans AS t1
			LEFT JOIN
				reseller_props AS t2 ON(t2.reseller_id = t1.reseller_id)
			"
		);

		if ($stmt->rowCount()) {
			/** @var $dbConfig iMSCP_Config_Handler_Db */
			$dbConfig = iMSCP_Registry::get('dbConfig');

			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$propsArr = explode(';', rtrim($row['props'], ';'));

				$hpPropMap = array(
					0 => array('_no_', array('_yes_', '_no_')), // PHP Feature
					1 => array('_no_', array('_yes_', '_no_')), // CGI Feature
					2 => array('-1', 'LIMIT'), // Max Subdomains
					3 => array('-1', 'LIMIT'), // Max Domain Aliases
					4 => array('-1', 'LIMIT'), // Max Mail Accounts
					5 => array('-1', 'LIMIT'), // Max Ftp Accounts
					6 => array('-1', 'LIMIT'), // Max Sql Databases
					7 => array('-1', 'LIMIT'), // Max Sql Users
					8 => array('0', 'NUM'), // Monthly Traffic Limit
					9 => array('0', 'NUM'), // Diskspace limit
					10 => array('_no_', array('_no_', '_dmn_', '_sql_', '_full_')), // Backup feature
					11 => array('_no_', array('_yes_', '_no_')), // Custom DNS feature
					12 => array('_no_', array('_yes_', '_no_')), // Software Installer feature
					13 => array('no', array('yes', 'no')), // Php Editor Feature
					14 => array('no', array('yes', 'no')), // Allow URL fopen
					15 => array('no', array('yes', 'no')), // Display errors
					16 => array('no', array('yes', 'no', 'exec')), // Disable funtions
					17 => array(min($row['post_max_size'], $dbConfig['PHPINI_POST_MAX_SIZE']), 'NUM'),
					18 => array(min($row['upload_max_filesize'], $dbConfig['PHPINI_UPLOAD_MAX_FILESIZE']), 'NUM'),
					19 => array(min($row['max_execution_time'], $dbConfig['PHPINI_MAX_EXECUTION_TIME']), 'NUM'),
					20 => array(min($row['max_input_time'], $dbConfig['PHPINI_MAX_INPUT_TIME']), 'NUM'),
					21 => array(min($row['memory_limit'], $dbConfig['PHPINI_MEMORY_LIMIT']), 'NUM'),
					22 => array('_no_', array('_yes_', '_no_')), // External Mail Server Feature
					23 => array('_no_', array('_yes_', '_no_')), // Web folder protection
					24 => array(is_number($propsArr[9]) ? $propsArr[9] : '0', 'NUM') // Email quota
				);

				foreach ($hpPropMap as $index => $values) {
					if (isset($propsArr[$index])) {
						if ($values[1] == 'LIMIT' && !imscp_limit_check($propsArr[$index])) {
							$propsArr[$index] = $values[0];
						} elseif ($values[1] == 'NUM' && !is_number($propsArr[$index])) {
							$propsArr[$index] = $values[0];
						} elseif (is_array($values[1]) && !in_array($propsArr[$index], $values[1])) {
							$propsArr[$index] = $values[0];
						}
					} else {
						$propsArr[$index] = $values[0];
					}
				}

				$propStr = implode(';', $propsArr);
				$sqlUpd[] = "UPDATE hosting_plans SET props = '$propStr' WHERE id = '{$row['id']}'";
			}
		}

		return $sqlUpd;
	}

	/**
	 * Update mail_users.quota columns
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r159()
	{
		return "ALTER TABLE mail_users CHANGE quota quota BIGINT(20) UNSIGNED NULL DEFAULT NULL";
	}

	/**
	 * Update mail_users.quota columns - Set quota field to NULL for forward only and catchall accounts
	 *
	 * @return string SQL statement to be executed
	 */
	protected function r163()
	{
		return "
			UPDATE
				mail_users
			SET
				quota = NULL
			WHERE
				mail_type NOT RLIKE '^(normal_mail|alias_mail|subdom_mail|alssub_mail)'
		";
	}

	/**
	 * Update domain.mail_quota and domain.domain_disk_limit fields according the number of existent mailboxes for which
	 * a quota is appliable
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r165()
	{
		return array(
			'
				UPDATE
					domain AS t1
				JOIN (
					SELECT
						COUNT(mail_id) AS nb_mailboxes, domain_id
					FROM
						mail_users
					WHERE
						quota IS NOT NULL
				) AS t2 USING(domain_id)
				SET
					t1.domain_disk_limit = t2.nb_mailboxes
				WHERE
					t1.domain_disk_limit <> 0
				AND
					t1.domain_disk_limit < t2.nb_mailboxes
			',
			'
				UPDATE
					domain AS t1
				JOIN (
					SELECT
						COUNT(mail_id) AS nb_mailboxes, domain_id
					FROM
						mail_users
					WHERE
						quota IS NOT NULL
				) AS t2 USING(domain_id)
				SET
					t1.mail_quota = t2.nb_mailboxes
				WHERE
					t1.mail_quota <> 0
				AND
					t1.mail_quota < t2.nb_mailboxes
			'
		);
	}

	/**
	 * Synchronize mailboxes quota
	 *
	 * @return null
	 */
	protected function r166()
	{
		$stmt = exec_query('SELECT domain_id, mail_quota FROM domain');

		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			sync_mailboxes_quota($data['domain_id'], $data['mail_quota']);
		}

		return null;
	}

	/**
	 * #908: Review - Dovecot - Quota - Switch to maildir quota backend
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r167()
	{
		return $this->dropTable('quota_dovecot');
	}

	/**
	 * Remove deprecated Domain name parameters
	 *
	 * @return null
	 */
	protected function r168()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig['TLD_STRICT_VALIDATION'])) {
			unset($dbConfig['TLD_STRICT_VALIDATION']);
		}

		if (isset($dbConfig['SLD_STRICT_VALIDATION'])) {
			unset($dbConfig['SLD_STRICT_VALIDATION']);
		}

		if (isset($dbConfig['MAX_DNAMES_LABELS'])) {
			unset($dbConfig['MAX_DNAMES_LABELS']);
		}

		if (isset($dbConfig['MAX_SUBDNAMES_LABELS'])) {
			unset($dbConfig['MAX_SUBDNAMES_LABELS']);
		}

		return null;
	}

	/**
	 * Update service ports
	 *
	 * @return null
	 */
	protected function r169()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');

		# Retrieve service ports
		$services = array_filter(
			array_keys($dbConfig->toArray()),
			function ($name) {
				return (strlen($name) > 5 && substr($name, 0, 5) == 'PORT_');
			}
		);

		foreach ($services as $name) {
			$values = explode(';', $dbConfig[$name]);

			if(count($values) > 5) { // Handle case where the update is run many time
				if ($values[5] == '') {
					$values[5] = '0.0.0.0';
				}

				unset($values[4]); // All port are now editable - We remove custom port field

				$dbConfig[$name] = implode(';', $values);
			}
		}

		return null;
	}

	/**
	 * Update external mail server parameter
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r170()
	{
		return array(
			"UPDATE domain SET external_mail = 'domain' WHERE external_mail = 'on'",
			"UPDATE domain_aliasses SET external_mail = 'domain' WHERE external_mail = 'on'"
		);
	}

	/**
	 * Delete deprecated plugin.plugin_previous_status field
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r171()
	{
		return $this->dropColumn('plugin', 'plugin_previous_status');
	}

	/**
	 * Add admin.admin_sys_name and admin.admin_sys_gname columns and populate them
	 *
	 * @return array SQL statements to be executed
	 */
	protected function  r172()
	{
		if (getmyuid() === 0) {
			$sqlUdp = array(
				$this->addColumn(
					'admin', 'admin_sys_name', 'varchar(16) collate utf8_unicode_ci DEFAULT NULL AFTER admin_type'
				),
				$this->addColumn(
					'admin', 'admin_sys_gname', 'varchar(32) collate utf8_unicode_ci DEFAULT NULL AFTER admin_sys_uid'
				)
			);

			$stmt = exec_query("SELECT admin_id, admin_sys_uid FROM admin WHERE admin_type in('admin', 'user')");

			while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				if ($data['admin_sys_uid']) {
					$adminSysPwUid = posix_getpwuid($data['admin_sys_uid']);
					$adminSysGrUid = posix_getgrgid($adminSysPwUid['gid']);

					$adminSysName = quoteValue($adminSysPwUid['name']);
					$adminSysGname = quoteValue($adminSysGrUid['name']);

					$sqlUdp[] = "
						UPDATE
							admin
						SET
							admin_sys_name = $adminSysName, admin_sys_gname = $adminSysGname
						WHERE
							admin_id = {$data['admin_id']}
					";
				}
			}

			return $sqlUdp;
		} else {
			throw new iMSCP_Update_Exception(
				'Database update 172 require root user privileges. Please run the i-MSCP installer.'
			);
		}
	}

	/**
	 * Remove useless columns from the server_ips table
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r173()
	{
		return array(
			$this->dropColumn('server_ips', 'ip_domain'),
			$this->dropColumn('server_ips', 'ip_alias'),
			$this->dropColumn('server_ips', 'ip_ssl_domain_id')
		);
	}

	/**
	 * Remove domain.domain_created_id column
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r174()
	{
		return $this->dropColumn('domain', 'domain_created_id');
	}

	/**
	 * Update sql_database and sql_user table structure
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r176()
	{
		return array(
			// sql_database table update
			$this->changeColumn('sql_database', 'domain_id', 'domain_id INT(10) UNSIGNED NOT NULL'),
			$this->changeColumn(
				'sql_database', 'sqld_name', 'sqld_name VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
			),
			// sql_user table update
			$this->changeColumn('sql_user', 'sqld_id', 'sqld_id INT(10) UNSIGNED NOT NULL'),
			$this->changeColumn(
				'sql_user', 'sqlu_name', 'sqlu_name VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
			),
			$this->changeColumn(
				'sql_user', 'sqlu_pass', 'sqlu_pass VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
			),
			$this->addColumn(
				'sql_user',
				'sqlu_host',
				'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER sqlu_name'
			),
			$this->addIndex('sql_user', 'sqlu_name', 'index'),
			$this->addIndex('sql_user', 'sqlu_host', 'index')
		);
	}

	/**
	 * Fix Sql user hosts
	 *
	 * @return array SQL statements to be executed
	 */
	protected function r177()
	{
		$sqlUdp = array();

		$sqlUserHost = iMSCP_Registry::get('config')->DATABASE_USER_HOST;

		if ($sqlUserHost == '127.0.0.1') {
			$sqlUserHost = 'localhost';
		}

		$sqlUserHost = quoteValue($sqlUserHost);

		$stmt = exec_query('SELECT DISTINCT sqlu_name FROM sql_user');

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$sqlUser = quoteValue($row['sqlu_name']);

				$sqlUdp[] = "
					UPDATE
						mysql.user
					SET
						Host = $sqlUserHost
					WHERE
						User = $sqlUser
					AND
						Host NOT IN ($sqlUserHost, '%')
				";

				$sqlUdp[] = "
					UPDATE
						mysql.db
					SET
						Host = $sqlUserHost
					WHERE
						User = $sqlUser
					AND
						Host NOT IN ($sqlUserHost, '%')
				";

				$sqlUdp[] = "
					UPDATE
						sql_user
					SET
						sqlu_host = $sqlUserHost
					WHERE
						sqlu_name = $sqlUser
					AND
						sqlu_host NOT IN ($sqlUserHost, '%')
				";
			}

			$sqlUdp[] = 'FLUSH PRIVILEGES';
		}

		return $sqlUdp;
	}

	/**
	 * Decrypt any SSL private key
	 *
	 * @return array SQL statements to be executed
	 */
	public function r178()
	{
		$sqlUdp = array();

		$stmt = execute_query('SELECT cert_id, password, `key` FROM ssl_certs');

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {

				$certId = quoteValue($row['cert_id'], PDO::PARAM_INT);
				$privateKey = new Crypt_RSA();

				if ($row['password'] != '') {
					$privateKey->setPassword($row['password']);
				}

				if (!$privateKey->loadKey($row['key'], CRYPT_RSA_PRIVATE_FORMAT_PKCS1)) {
					$sqlUdp[] = "DELETE FROM ssl_certs WHERE cert_id = $certId";
					continue;
				}

				// Clear out passphrase
				$privateKey->setPassword();

				// Get unencrypted private key
				$privateKey = $privateKey->getPrivateKey();

				$privateKey = quoteValue($privateKey);
				$sqlUdp[] = "UPDATE ssl_certs SET `key` = $privateKey WHERE cert_id = $certId";
			}
		}

		return $sqlUdp;
	}

	/**
	 * Remove password column from the ssl_certs table
	 *
	 * @return null|string SQL statements to be executed
	 */
	public function r179()
	{
		return $this->dropColumn('ssl_certs', 'password');
	}

	/**
	 * Rename ssl_certs.id column to ssl_certs.domain_id
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r180()
	{
		return $this->changeColumn('ssl_certs', 'id', 'domain_id INT(10) NOT NULL');
	}

	/**
	 * Rename ssl_certs.type column to ssl_certs.domain_type
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r181()
	{
		return $this->changeColumn(
			'ssl_certs',
			'type',
			"domain_type ENUM('dmn','als','sub','alssub') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn'"
		);
	}

	/**
	 * Rename ssl_certs.key column to ssl_certs.private_key
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r182()
	{
		return $this->changeColumn(
			'ssl_certs', 'key', 'private_key TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
		);
	}

	/**
	 * Rename ssl_certs.cert column to ssl_certs.certificate
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r183()
	{
		return $this->changeColumn(
			'ssl_certs', 'cert', 'certificate TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
		);
	}

	/**
	 * Rename ssl_certs.ca_cert column to ssl_certs.ca_bundle
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r184()
	{
		return $this->changeColumn(
			'ssl_certs', 'ca_cert', 'ca_bundle TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
		);
	}

	/**
	 * Drop index id from ssl_certs table
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r185()
	{
		return $this->dropIndexByName('ssl_certs', 'id');
	}

	/**
	 * Add domain_id_domain_type index in ssl_certs table
	 *
	 * @return null|string SQL statement to be executed
	 */
	protected function r186()
	{
		return $this->addIndex('ssl_certs', array('domain_id', 'domain_type'), 'unique', 'domain_id_domain_type');
	}

	/**
	 * SSL certificates normalization
	 *
	 * @return array
	 */
	protected function r189()
	{
		$sqlUdp = array();

		$stmt = execute_query('SELECT cert_id, private_key, certificate, ca_bundle FROM ssl_certs');

		if ($stmt->rowCount()) {
			while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$certificateId = quoteValue($row['cert_id'], PDO::PARAM_INT);

				// Data normalization
				$privateKey = quoteValue(str_replace("\r\n", "\n", trim($row['private_key'])) . PHP_EOL);
				$certificate = quoteValue(str_replace("\r\n", "\n", trim($row['certificate'])) . PHP_EOL);
				$caBundle = quoteValue(str_replace("\r\n", "\n", trim($row['ca_bundle'])));

				$sqlUdp[] = "
					UPDATE
						ssl_certs
					SET
						private_key = $privateKey, certificate = $certificate, ca_bundle = $caBundle
					WHERE
						cert_id = $certificateId
				";
			}
		}

		return $sqlUdp;
	}

	/**
	 * Delete deprecated Web folder protection parameter
	 *
	 * @return null
	 */
	protected function r190()
	{
		/** @var iMSCP_Config_Handler_Db $dbConfig */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if($dbConfig->exists('WEB_FOLDER_PROTECTION')) {
			$dbConfig->del('WEB_FOLDER_PROTECTION');
		}

		return null;
	}
}
