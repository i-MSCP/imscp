<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP team
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
 * @copyright   2010-2013 by i-MSCP team
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Update */
require_once 'iMSCP/Update.php';

/**
 * Update Database class.
 *
 * Class to handled database updates for i-MSCP.
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @subpackage  Database
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Update_Database extends iMSCP_Update
{
	/**
	 * @var iMSCP_Update
	 */
	protected static $_instance;

	/**
	 * Database name being updated.
	 *
	 * @var string
	 */
	protected $_databaseName;

	/**
	 * Tells whether or not a request must be send to the i-MSCP daemon after that
	 * all database updates were applied.
	 *
	 * @var bool
	 */
	protected $_daemonRequest = false;

	/**
	 * Singleton - Make new unavailable.
	 */
	protected function __construct()
	{
		if (isset(iMSCP_Registry::get('config')->DATABASE_NAME)) {
			$this->_databaseName = iMSCP_Registry::get('config')->DATABASE_NAME;
		} else {
			throw new iMSCP_Update_Exception('Database name not found.');
		}
	}

	/**
	 * Singleton - Make clone unavailable.
	 *
	 * @return void
	 */
	protected function __clone()
	{

	}

	/**
	 * Implements Singleton design pattern.
	 *
	 * @return iMSCP_Update_Database
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Checks for available database update.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return bool TRUE if a database update is available, FALSE otherwise
	 */
	public function isAvailableUpdate()
	{
		if ($this->_getLastAppliedUpdate() < $this->_getNextUpdate()) {
			return true;
		}

		return false;
	}

	/**
	 * Apply all available database updates.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function applyUpdates()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		/** @var $pdo PDO */
		$pdo = iMSCP_Database::getRawInstance();

		while ($this->isAvailableUpdate()) {
			$databaseUpdateRevision = $this->_getNextUpdate();

			// Get the database update method name
			$databaseUpdateMethod = '_databaseUpdate_' . $databaseUpdateRevision;

			// Gets the queries from the database update method.
			// A database update method can return void, an array (stack of SQL
			// statements) or a string (SQL statement)
			$queryStack = $this->$databaseUpdateMethod();

			if (!empty($queryStack)) {
				try {
					// One transaction per database update
					// If a query from a database update fail, all queries from it
					// are canceled. It's only valid for database updates that are
					// free of any statements causing an implicit commit
					$pdo->beginTransaction();

					foreach ((array)$queryStack as $query) {
						if (!empty($query)) {
							$pdo->query($query);
						}
					}

					$dbConfig->set('DATABASE_REVISION', $databaseUpdateRevision);

					$pdo->commit();

				} catch (Exception $e) {

					$pdo->rollBack();

					// Prepare error message
					$errorMessage = sprintf(
						'Database update %s failed.', $databaseUpdateRevision);

					// Extended error message
					$errorMessage .=
						'<br /><br /><strong>Exception message was:</strong><br />' .
							$e->getMessage() . (isset($query)
							? "<br /><strong>Query was:</strong><br />$query" : '');

					if (PHP_SAPI == 'cli') {
						$errorMessage = str_replace(
							array('<br />', '<strong>', '</strong>'),
							array("\n", '', ''), $errorMessage);
					}

					$this->_lastError = $errorMessage;

					return false;
				}
			} else {
				$dbConfig->set('DATABASE_REVISION', $databaseUpdateRevision);
			}
		}

		// We must never run the backend scripts from the CLI update script
		if (PHP_SAPI != 'cli' && $this->_daemonRequest) {
			send_request();
		}

		return true;
	}

	/**
	 * Returns database update(s) details.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array
	 */
	public function getDatabaseUpdatesDetails()
	{
		$reflectionStart = $this->_getNextUpdate();

		$reflection = new ReflectionClass(__CLASS__);
		$databaseUpdatesDetails = array();

		/** @var $method ReflectionMethod */
		foreach ($reflection->getMethods() as $method) {
			$methodName = $method->name;

			if (strpos($methodName, '_databaseUpdate_') !== false) {
				$revision = (int)substr($methodName, strrpos($methodName, '_') + 1);

				if ($revision >= $reflectionStart) {
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

					$databaseUpdatesDetails[$revision] = $normalizedDetails;
				}
			}
		}

		return $databaseUpdatesDetails;
	}

	/**
	 * Return next database update revision.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return int 0 if no update is available
	 */
	protected function _getNextUpdate()
	{
		$lastAvailableUpdateRevision = $this->_getLastAvailableUpdateRevision();
		$nextUpdateRevision = $this->_getLastAppliedUpdate();

		if ($nextUpdateRevision < $lastAvailableUpdateRevision) {
			return $nextUpdateRevision + 1;
		}

		return 0;
	}

	/**
	 * Returns last database update revision number.
	 *
	 * Note: For performances reasons, the revision is retrieved once per process.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return int Last database update revision number
	 */
	protected function _getLastAvailableUpdateRevision()
	{
		static $lastAvailableUpdateRevision = null;

		if (null === $lastAvailableUpdateRevision) {
			$reflection = new ReflectionClass(__CLASS__);
			$databaseUpdateMethods = array();

			foreach ($reflection->getMethods() as $method) {
				if (strpos($method->name, '_databaseUpdate_') !== false) {
					$databaseUpdateMethods[] = $method->name;
				}
			}

			$databaseUpdateMethod = (string)end($databaseUpdateMethods);
			$lastAvailableUpdateRevision = (int)substr(
				$databaseUpdateMethod, strrpos($databaseUpdateMethod, '_') + 1
			);
		}

		return $lastAvailableUpdateRevision;
	}

	/**
	 * Returns the revision number of the last applied database update.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return int Revision number of the last applied database update
	 */
	protected function _getLastAppliedUpdate()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (!isset($dbConfig->DATABASE_REVISION)) {
			$dbConfig->DATABASE_REVISION = 1;
		}

		return (int)$dbConfig->DATABASE_REVISION;
	}

	/**
	 * Checks if a column exists in a database table and if not, return query to add it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @param string $table Database table name to operate on
	 * @param string $column Column to be added in the database table
	 * @param string $columnDefinition Column definition including the optional
	 *                                    (but recommended) positional statement
	 *                                    ([FIRST | AFTER col_name ]
	 * @return string Query to be executed
	 */
	protected function _addColumn($table, $column, $columnDefinition)
	{
		$query = "
			SELECT
				COLUMN_NAME
			FROM
				`information_schema`.`COLUMNS`
			WHERE
				COLUMN_NAME = ?
			AND
				TABLE_NAME = ?
			AND
				`TABLE_SCHEMA` = ?
		";
		$stmt = exec_query($query, array($column, $table, $this->_databaseName));

		if ($stmt->rowCount() == 0) {
			return "ALTER TABLE `$table` ADD `$column` $columnDefinition;";
		} else {
			return '';
		}
	}

	/**
	 * Checks if a column exists in a database table and if yes, return a query to drop it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @since r4509
	 * @param string $table Database table from where the column must be dropped
	 * @param string $column Column to be dropped from $table
	 * @return string Query to be executed
	 */
	protected function _dropColumn($table, $column)
	{
		$query = "
			SELECT
				`COLUMN_NAME`
			FROM
				`information_schema`.`COLUMNS`
			WHERE
				`COLUMN_NAME` = ?
			AND
				`TABLE_NAME` = ?
			AND
				`TABLE_SCHEMA` = ?
		";
		$stmt = exec_query($query, array($column, $table, $this->_databaseName));

		if ($stmt->rowCount()) {
			return "ALTER TABLE `$table` DROP COLUMN `$column`";
		} else {
			return '';
		}
	}

	/**
	 * Checks if a database table have an index and if yes, return a query to drop it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @param string $table Database table from where the column must be dropped
	 * @param string $indexName Index name
	 * @param string $columnName Column to which index belong to
	 * @return string Query to be executed
	 */
	protected function _dropIndex($table, $indexName = 'PRIMARY', $columnName = null)
	{
		if (is_null($columnName)) {
			$columnName = $indexName;
		}

		$query = "
			SHOW INDEX FROM
				`$this->_databaseName`.`$table`
			WHERE
				`KEY_NAME` = ?
			AND
				`COLUMN_NAME` = ?
		";
		$stmt = exec_query($query, array($indexName, $columnName));

		if ($stmt->rowCount()) {
			return "ALTER IGNORE TABLE `$this->_databaseName`.`$table` DROP INDEX `$indexName`";
		} else {
			return '';
		}
	}

	/**
	 * Checks if a database table have an index and if no, return a query to add it.
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @param string $table Database table from where the column must be dropped
	 * @param string $columnName Column to which index belong to
	 * @param string $indexType Index type (Primary Unique)
	 * @param string $indexName Index name
	 * @return string Query to be executed
	 */
	protected function _addIndex($table, $columnName, $indexType = 'PRIMARY KEY', $indexName = null)
	{
		if (is_null($indexName)) {
			$indexName = $indexType == 'PRIMARY KEY' ? 'PRIMARY' : $columnName;
		}

		$query = "
			SHOW INDEX FROM
				`$this->_databaseName`.`$table`
			WHERE
				`KEY_NAME` = ?
			AND
				`COLUMN_NAME` = ?
		";
		$stmt = exec_query($query, array($indexName, $columnName));

		if ($stmt->rowCount()) {
			return '';
		} else {
			return "
				ALTER IGNORE TABLE
					`$this->_databaseName`.`$table`
				ADD
					$indexType " . ($indexType == 'PRIMARY KEY' ? '' : $indexName) . " (`$columnName`)
			";
		}
	}

	/**
	 * Catch any database updates that were removed.
	 *
	 * @throws iMSCP_Update_Exception
	 * @param  string $updateMethod Database update method name
	 * @param  array $param
	 * @return void
	 */
	public function __call($updateMethod, $param)
	{
		if (strpos($updateMethod, '_databaseUpdate') === false) {
			throw new iMSCP_Update_Exception(sprintf('%s is not a valid database update method', $updateMethod));
		}
	}

	/**
	 * Please, add all the database update methods below. Don't forgot to add the doc and revision (@since rxxx). Also,
	 * when you add a ticket reference in a databaseUpdate_XX method, place it at begin to allow link generation on GUI.
	 */

	/**
	 * Fixes some CSRF issues in admin log
	 *
	 * @author Thomas Wacker <thomas.wacker@ispcp.net>
	 * @return array SQL Statement
	 */
	protected function _databaseUpdate_46()
	{
		return 'TRUNCATE TABLE `log`;';
	}

	/**
	 * Removes useless 'suexec_props' table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array SQL Statement
	 */
	protected function _databaseUpdate_47()
	{
		return 'DROP TABLE IF EXISTS `suexec_props`';
	}

	/**
	 * #14: Adds table for software installer
	 *
	 * @author Sascha Bay <worst.case@gmx.de>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_48()
	{
		$sqlUpd = array();

		$sqlUpd[] = "
	 		CREATE TABLE IF NOT EXISTS
	 			`web_software` (
					`software_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`software_master_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`reseller_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`software_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_type` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_db` TINYINT(1) NOT NULL,
					`software_archive` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_installfile` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_prefix` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_link` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_desc` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_active` INT(1) NOT NULL,
					`software_status` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`rights_add_by` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`software_depot` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
	  				PRIMARY KEY  (`software_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_inst` (
					`domain_id` INT(10) UNSIGNED NOT NULL,
					`alias_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`subdomain_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`subdomain_alias_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`software_id` INT(10) NOT NULL,
					`software_master_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
					`software_res_del` INT(1) NOT NULL DEFAULT '0',
					`software_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`software_prefix` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`db` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`database_user` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`database_tmp_pwd` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`install_username` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`install_password` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`install_email` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
					`software_status` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_depot` VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  					KEY `software_id` (`software_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		$sqlUpd[] = $this->_addColumn(
			'domain',
			'domain_software_allowed',
			"VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no' AFTER `domain_dns`"
		);

		$sqlUpd[] = $this->_addColumn(
			'reseller_props',
			'software_allowed',
			"VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no' AFTER `reseller_ips`"
		);

		$sqlUpd[] = $this->_addColumn(
			'reseller_props',
			'softwaredepot_allowed',
			"VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes' AFTER `software_allowed`"
		);

		$sqlUpd[] = "UPDATE `hosting_plans` SET `props` = CONCAT(`props`,';_no_');";

		return $sqlUpd;
	}

	/**
	 * Adds i-MSCP daemon service properties in config table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return void
	 */
	protected function _databaseUpdate_50()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$dbConfig->PORT_IMSCP_DAEMON = "9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1";
	}

	/**
	 * Adds required field for on-click-logon from the ftp-user site.
	 *
	 * @author William Lightning <kassah@gmail.com>
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_51()
	{
		return $this->_addColumn(
			'ftp_users',
			'rawpasswd',
			"varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `passwd`"
		);
	}

	/**
	 * Adds new options for applications installer
	 *
	 * @author Sascha Bay <worst.case@gmx.de>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_52()
	{
		$sqlUpd = array();

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_depot` (
					`package_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`package_install_type` VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_title` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_version` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_language` VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_type` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_description` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`package_vendor_hp` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_download_link` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_signature_link` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`package_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		$sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_options` (
					`use_webdepot` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
					`webdepot_xml_url` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
					`webdepot_last_update` DATETIME NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		$sqlUpd[] = "
			REPLACE INTO
				`web_software_options` (`use_webdepot`, `webdepot_xml_url`, `webdepot_last_update`)
			VALUES
				('1', 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00')
			;
		";

		$sqlUpd[] = $this->_addColumn(
			'web_software',
			'software_installtype',
			"VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `reseller_id`"
		);

		$sqlUpd[] = " UPDATE `web_software` SET `software_installtype` = 'install'";

		$sqlUpd[] = $this->_addColumn(
			'reseller_props',
			'websoftwaredepot_allowed',
			"VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT NULL DEFAULT 'yes' AFTER `softwaredepot_allowed`"
		);

		return $sqlUpd;
	}

	/**
	 * Decrypts email, ftp and SQL users passwords in database
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_53()
	{
		$sqlUpd = array();

		$status = iMSCP_Registry::get('config')->ITEM_CHANGE_STATUS;

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		// Mail accounts passwords

		$query = "
			SELECT
				`mail_id`, `mail_pass`
			FROM
				`mail_users`
			WHERE
				`mail_type` RLIKE '^normal_mail'
			OR
				`mail_type` RLIKE '^alias_mail'
			OR
				`mail_type` RLIKE '^subdom_mail'
		";

		$stmt = execute_query($query);

		if ($stmt->rowCount()) {
			while (!$stmt->EOF) {
				$sqlUpd[] = "
					UPDATE
						`mail_users`
					SET
						`mail_pass`= " . $db->quote(decrypt_db_password($stmt->fields['mail_pass'])) . ",
						`status` = '$status' WHERE `mail_id` = '" . $stmt->fields['mail_id'] . "'
				";

				$stmt->moveNext();
			}
		}

		// SQL users passwords

		$stmt = exec_query("SELECT `sqlu_id`, `sqlu_pass` FROM `sql_user`");

		if ($stmt->rowCount()) {
			while (!$stmt->EOF) {
				$sqlUpd[] = "
					UPDATE
						`sql_user`
					SET
						`sqlu_pass` = " . $db->quote(decrypt_db_password($stmt->fields['sqlu_pass'])) . "
					WHERE
						`sqlu_id` = '" . $stmt->fields['sqlu_id'] . "'
				";

				$stmt->moveNext();
			}
		}

		// Ftp users passwords

		$stmt = exec_query("SELECT `userid`, `passwd` FROM `ftp_users`");

		if ($stmt->rowCount()) {
			while (!$stmt->EOF) {
				$sqlUpd[] = "
					UPDATE
						`ftp_users`
					SET
						`rawpasswd` = " . $db->quote(decrypt_db_password($stmt->fields['passwd'])) . "
					WHERE
						`userid` = '" . $stmt->fields['userid'] . "'
				";

				$stmt->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Converts all tables to InnoDB engine
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_54()
	{
		$sqlUpd = array();

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		$tables = $db->metaTables();

		foreach ($tables as $table) {
			$sqlUpd[] = "ALTER TABLE `$table` ENGINE=InnoDB";
		}

		return $sqlUpd;
	}

	/**
	 * Converts the autoreplies_log table to InnoDB engine
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_60()
	{
		return 'ALTER TABLE `autoreplies_log` ENGINE=InnoDB';
	}

	/**
	 * Deletes old DUMP_GUI_DEBUG parameter from the config table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return void
	 */
	protected function _databaseUpdate_66()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig->DUMP_GUI_DEBUG)) {
			$dbConfig->del('DUMP_GUI_DEBUG');
		}
	}

	/**
	 * #124: Enhancement - Switch to gettext (Machine Object Files)
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_67()
	{
		$sqlUpd = array();

		// First step: Update default language (new naming convention)

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig->USER_INITIAL_LANG)) {
			$dbConfig->USER_INITIAL_LANG = str_replace('lang_', '', $dbConfig->USER_INITIAL_LANG);
		}

		// second step: Removing all database languages tables

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		foreach ($db->metaTables() as $tableName) {
			if (strpos($tableName, 'lang_') !== false) {
				$sqlUpd[] = "DROP TABLE `$tableName`";
			}
		}

		// third step: Update users language property

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
			'Turkish' => 'tr_TR', 'Ukrainian' => 'uk_UA');

		// Updates language property of each users by using new naming convention
		// Thanks to Marc Pujol for idea
		foreach ($languagesMap as $language => $locale) {
			$sqlUpd[] = "
				UPDATE
					`user_gui_props`
				SET
					`lang` = '$locale'
				WHERE
					`lang` = 'lang_{$language}'
			";
		}

		return $sqlUpd;
	}

	/**
	 * #119: Defect - Error when adding IP's
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_68()
	{
		$sqlUpd = array();

		/** @var $db iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		$stmt = exec_query("SELECT `ip_id`, `ip_card` FROM `server_ips`");

		if ($stmt->rowCount()) {
			while (!$stmt->EOF) {
				$cardname = explode(':', $stmt->fields['ip_card']);
				$cardname = $cardname[0];
				$sqlUpd[] = "
					UPDATE
						`server_ips`
					SET
						`ip_card` = " . $db->quote($cardname) . "
					WHERE
						`ip_id` = '" . $stmt->fields['ip_id'] . "'
				";

				$stmt->moveNext();
			}
		}

		return $sqlUpd;
	}

	/**
	 * Some fixes for the user_gui_props table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_69()
	{
		return array(
			"ALTER TABLE `user_gui_props` CHANGE `user_id` `user_id` INT( 10 ) UNSIGNED NOT NULL",
			"ALTER TABLE `user_gui_props` CHANGE `layout` `layout`
				VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL",
			"ALTER TABLE `user_gui_props` CHANGE `logo` `logo`
				VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''",
			"ALTER TABLE `user_gui_props` CHANGE `lang` `lang`
				VARCHAR( 5 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL",
			"UPDATE `user_gui_props` SET `logo` = '' WHERE `logo` = 0");
	}

	/**
	 * Changes the log table schema to allow storage of large messages
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_71()
	{
		return 'ALTER TABLE `log` CHANGE `log_message` `log_message`
			TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL';
	}

	/**
	 * Adds unique index on the web_software_options.use_webdepot column
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_72()
	{
		return 'ALTER IGNORE TABLE `web_software_options` ADD UNIQUE (`use_webdepot`)';
	}

	/**
	 * #166: Adds dovecot quota table
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_73()
	{
		return "
			CREATE TABLE IF NOT EXISTS `quota_dovecot` (
				`username` VARCHAR(200) COLLATE utf8_unicode_ci NOT NULL,
				`bytes` BIGINT(20) NOT NULL DEFAULT '0',
				`messages` INT(11) NOT NULL DEFAULT '0',
				PRIMARY KEY (`username`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";
	}

	/**
	 * #58: Increases mail quota value from 10 Mio to 100 Mio
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_75()
	{
		return "UPDATE `mail_users` SET `quota` = '104857600' WHERE `quota` = '10485760'";
	}

	/**
	 * Adds unique index on user_gui_props.user_id column
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_76()
	{

		$sqlUpd = array();

		$query = "
			SELECT
				`CONSTRAINT_NAME`
			FROM
				`information_schema`.`KEY_COLUMN_USAGE`
			WHERE
				`TABLE_NAME` = ?
			AND
				`CONSTRAINT_NAME` = ?
			AND
				`TABLE_SCHEMA` =?
		";
		$stmt = exec_query($query, array('user_gui_props', 'user_id', $this->_databaseName));

		if ($stmt->rowCount()) {
			$sqlUpd[] = "ALTER IGNORE TABLE `user_gui_props` DROP INDEX `user_id`";
		}

		$sqlUpd[] = "ALTER TABLE `user_gui_props` ADD UNIQUE (`user_id`)";

		return $sqlUpd;
	}

	/**
	 * Drops useless user_gui_props.id column
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_77()
	{
		return $this->_dropColumn('user_gui_props', 'id');
	}

	/**
	 * #175: Fix for mail_addr saved in mail_type_forward too
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_78()
	{
		return array(
			"
				REPLACE INTO `mail_users`(`mail_id`, `mail_acc`, `mail_pass`,
				`mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
				`mail_auto_respond`, `mail_auto_respond_text`, `quota`, `mail_addr`)

				SELECT `mail_id`, `mail_acc`, `mail_pass`, `mail_forward`,
				`t1`.`domain_id`, `mail_type`, `sub_id`, `status`, `mail_auto_respond`,
				`mail_auto_respond_text`, `quota`,
				CONCAT(`mail_acc`, '@', `domain_name`) AS `mail_addr`
				FROM `mail_users` AS `t1`
				LEFT JOIN `domain` AS `t2` ON `t1`.`domain_id` = `t2`.`domain_id`
				WHERE `t1`.`mail_type` = 'normal_forward' AND `t1`.`mail_addr` = ''
			",
			"
				REPLACE INTO `mail_users`(`mail_id`, `mail_acc`, `mail_pass`,
				`mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
				`mail_auto_respond`, `mail_auto_respond_text`, `quota`, `mail_addr`)

				SELECT `mail_id`, `mail_acc`, `mail_pass`, `mail_forward`,
				`t1`.`domain_id`, `mail_type`, `sub_id`, `status`, `mail_auto_respond`,
				`mail_auto_respond_text`, `quota`,
				CONCAT(`mail_acc`, '@', `alias_name`) AS `mail_addr`
				FROM `mail_users` AS `t1`
				LEFT JOIN `domain_aliasses` AS `t2` ON `t1`.`sub_id` = `t2`.`alias_id`
				WHERE `t1`.`mail_type` = 'alias_forward' AND `t1`.`mail_addr` = ''
			",
			"
				REPLACE INTO `mail_users`(`mail_id`, `mail_acc`, `mail_pass`,
				`mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
				`mail_auto_respond`, `mail_auto_respond_text`, `quota`, `mail_addr`)

				SELECT `mail_id`, `mail_acc`, `mail_pass`, `mail_forward`,
				`t1`.`domain_id`, `mail_type`, `sub_id`, `status`,
				`mail_auto_respond`, `mail_auto_respond_text`, `quota`,
				CONCAT(`mail_acc`, '@', `subdomain_alias_name`, '.', `alias_name`) AS `mail_addr`
				FROM `mail_users` AS `t1`
				LEFT JOIN `subdomain_alias` AS `t2` ON `t1`.`sub_id` = `t2`.`subdomain_alias_id`
				LEFT JOIN `domain_aliasses` AS `t3` ON `t2`.`alias_id` = `t3`.`alias_id`
				WHERE `t1`.`mail_type` = 'alssub_forward' AND `t1`.`mail_addr` = ''
			",
			"
				REPLACE INTO `mail_users`(`mail_id`, `mail_acc`, `mail_pass`,
				`mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
				`mail_auto_respond`, `mail_auto_respond_text`, `quota`, `mail_addr`)

				SELECT `mail_id`, `mail_acc`, `mail_pass`, `mail_forward`,
				`t1`.`domain_id`, `mail_type`, `sub_id`, `status`,
				`mail_auto_respond`, `mail_auto_respond_text`, `quota`,
				CONCAT(`mail_acc`, '@', `subdomain_name`, '.', `domain_name`) AS `mail_addr`
				FROM `mail_users` AS `t1`
				LEFT JOIN `subdomain` AS `t2` ON `t1`.`sub_id` = `t2`.`subdomain_id`
				LEFT JOIN `domain` AS `t3` ON `t2`.`domain_id` = `t3`.`domain_id`
				WHERE `t1`.`mail_type` = 'subdom_forward' AND `t1`.`mail_addr` = ''
			"
		);
	}

	/**
	 * #188: Defect - Table quota_dovecot is still myisam than innoDB
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_80()
	{
		return 'ALTER TABLE `quota_dovecot` ENGINE=InnoDB';
	}

	/**
	 * #15: Feature - PHP directives editor: Add/Update system wide values for PHP directives
	 *
	 * @author Hannes Koschier <hannes@cheat.at>
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_84()
	{
		return array(
			// System wide PHP directives values
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_ALLOW_URL_FOPEN', 'off')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_DISPLAY_ERRORS', 'off')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_REGISTER_GLOBALS', 'off')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_UPLOAD_MAX_FILESIZE', '2')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_POST_MAX_SIZE', '8')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_MEMORY_LIMIT', '128')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_MAX_INPUT_TIME', '60')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_MAX_EXECUTION_TIME', '30')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_ERROR_REPORTING', 'E_ALL & ~E_NOTICE')",
			"REPLACE INTO `config` (`name`,`value`) VALUES ('PHPINI_DISABLE_FUNCTIONS', 'show_source,system,shell_exec,passthru,exec,phpinfo,shell,symlink')"
		);
	}

	/**
	 * #15: Feature - PHP directives editor: Add columns for PHP directives
	 * #202: Bug - Unknown column php_ini_al_disable_functions in reseller_props table
	 *
	 * @author Hannes Koschier <hannes@cheat.at>
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_85()
	{
		return array(
			// Reseller permissions columns for PHP directives
			$this->_addColumn('reseller_props', 'php_ini_system', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `websoftwaredepot_allowed`"),
			$this->_addColumn('reseller_props', 'php_ini_al_disable_functions', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `php_ini_system`"),
			$this->_addColumn('reseller_props', 'php_ini_al_allow_url_fopen', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `php_ini_al_disable_functions`"),
			$this->_addColumn('reseller_props', 'php_ini_al_register_globals', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `php_ini_al_allow_url_fopen`"),
			$this->_addColumn('reseller_props', 'php_ini_al_display_errors', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `php_ini_al_register_globals`"),

			// Reseller max. allowed values columns for PHP directives
			$this->_addColumn('reseller_props', 'php_ini_max_post_max_size', "int(11) NOT NULL DEFAULT '8' AFTER `php_ini_al_display_errors`"),
			$this->_addColumn('reseller_props', 'php_ini_max_upload_max_filesize', "int(11) NOT NULL DEFAULT '2' AFTER `php_ini_max_post_max_size`"),
			$this->_addColumn('reseller_props', 'php_ini_max_max_execution_time', "int(11) NOT NULL DEFAULT '30' AFTER `php_ini_max_upload_max_filesize`"),
			$this->_addColumn('reseller_props', 'php_ini_max_max_input_time', "int(11) NOT NULL DEFAULT '60' AFTER `php_ini_max_max_execution_time`"),
			$this->_addColumn('reseller_props', 'php_ini_max_memory_limit', "int(11) NOT NULL DEFAULT '128' AFTER `php_ini_max_max_input_time`"),

			// Domain permissions columns for PHP directives
			$this->_addColumn('domain', 'phpini_perm_system', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `domain_software_allowed`"),
			$this->_addColumn('domain', 'phpini_perm_register_globals', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `phpini_perm_system`"),
			$this->_addColumn('domain', 'phpini_perm_allow_url_fopen', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `phpini_perm_register_globals`"),
			$this->_addColumn('domain', 'phpini_perm_display_errors', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `phpini_perm_allow_url_fopen`"),
			$this->_addColumn('domain', 'phpini_perm_disable_functions', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER `phpini_perm_allow_url_fopen`")
		);
	}

	/**
	 * #15: Feature - PHP directives editor: Add php_ini table
	 *
	 * @author Hannes Koschier <hannes@cheat.at>
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_86()
	{
		return
			// php_ini table for custom PHP directives (per domain)
			"CREATE TABLE IF NOT EXISTS `php_ini` (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`domain_id` INT(10) NOT NULL,
				`status` VARCHAR(55) COLLATE utf8_unicode_ci NOT NULL,
				`disable_functions` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'show_source,system,shell_exec,passthru,exec,phpinfo,shell,symlink',
				`allow_url_fopen` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
				`register_globals` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
				`display_errors` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off',
				`error_reporting` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E_ALL & ~E_NOTICE',
				`post_max_size` INT(11) NOT NULL DEFAULT '8',
				`upload_max_filesize` INT(11) NOT NULL DEFAULT '2',
				`max_execution_time` INT(11) NOT NULL DEFAULT '30',
				`max_input_time` INT(11) NOT NULL DEFAULT '60',
				`memory_limit` INT(11) NOT NULL DEFAULT '128',
				PRIMARY KEY (`ID`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	}

	/**
	 * Several fixes for the PHP directives editor including issue #195
	 *
	 * Note: For consistency reasons, this update will reset the feature values.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_88()
	{
		$sqlUpd = array();

		// Reset reseller permissions
		foreach (array(
			         'php_ini_system', 'php_ini_al_disable_functions', 'php_ini_al_allow_url_fopen',
			         'php_ini_al_register_globals', 'php_ini_al_display_errors') as $permission
		) {
			$sqlUpd[] = "UPDATE `reseller_props` SET `$permission` = 'no'";
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
			$sqlUpd[] = "UPDATE `reseller_props` SET `php_ini_max_{$directive}` = '$defaultValue'";
		}

		return $sqlUpd;
	}

	/**
	 * Truncate the php_ini table (related to _databaseUpdate_88)
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_89()
	{
		$sqlupd = 'TRUNCATE TABLE `php_ini`';

		// Schedule backend process in case user do update from frontend
		$this->_daemonRequest = true;

		return $sqlupd;
	}

	/**
	 * Drop unused table auto_num
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_91()
	{
		return 'DROP TABLE IF EXISTS `auto_num`';
	}

	/**
	 * #238: Delete orphan php_ini entries in the php.ini table
	 *
	 * @author Sascha Bay <thecry@i-mscp.net>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_92()
	{
		return 'DELETE FROM `php_ini` WHERE `domain_id` NOT IN (SELECT `domain_id` FROM `domain`)';
	}

	/**
	 * Rename php_ini.ID column to php_ini.id
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_93()
	{
		return 'ALTER TABLE `php_ini` CHANGE `ID` `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT';
	}

	/**
	 * Database schema update (UNIQUE KEY to PRIMARY KEY for some fields)
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_95()
	{
		return array(
			$this->_addIndex('domain', 'domain_id'),
			$this->_dropIndex('domain', 'domain_id'),

			$this->_addIndex('email_tpls', 'id'),
			$this->_dropIndex('email_tpls', 'id'),

			$this->_addIndex('hosting_plans', 'id'),
			$this->_dropIndex('hosting_plans', 'id'),

			$this->_addIndex('htaccess', 'id'),
			$this->_dropIndex('htaccess', 'id'),

			$this->_addIndex('htaccess_groups', 'id'),
			$this->_dropIndex('htaccess_groups', 'id'),

			$this->_addIndex('htaccess_users', 'id'),
			$this->_dropIndex('htaccess_users', 'id'),

			$this->_addIndex('reseller_props', 'id'),
			$this->_dropIndex('reseller_props', 'id'),

			$this->_addIndex('server_ips', 'ip_id'),
			$this->_dropIndex('server_ips', 'ip_id'),

			$this->_addIndex('sql_database', 'sqld_id'),
			$this->_dropIndex('sql_database', 'sqld_id'),

			$this->_addIndex('sql_user', 'sqlu_id'),
			$this->_dropIndex('sql_user', 'sqlu_id')
		);
	}

	/**
	 * #292: Feature - Layout color chooser
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_96()
	{
		return $this->_addColumn(
			'user_gui_props',
			'layout_color',
			"VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `layout`"
		);
	}

	/**
	 * Allow to change SSH port number
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return void
	 */
	protected function _databaseUpdate_97()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig->PORT_SSH)) {
			$dbConfig->PORT_SSH = '22;tcp;SSH;1;1;';
		}
	}

	/**
	 * Update level propertie for custom menus
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_98()
	{
		return array(
			"UPDATE `custom_menus` SET `menu_level` = 'A' WHERE `menu_level` = 'admin'",
			"UPDATE `custom_menus` SET `menu_level` = 'R' WHERE `menu_level` = 'reseller'",
			"UPDATE `custom_menus` SET `menu_level` = 'C' WHERE `menu_level` = 'user'",
			"UPDATE `custom_menus` SET `menu_level` = 'RC' WHERE `menu_level` = 'all'" // rc for backward compatibility
		);
	}

	/**
	 * #228: Enhancement - Multiple HTTPS domains on same IP + wildcard SSL
	 *
	 * @author Daniel Andreca<sci2tech@gmail.com>
	 * @return string SQL Statement
	 */
	protected function _databaseUpdate_100()
	{
		return
			"CREATE TABLE IF NOT EXISTS `ssl_certs` (
				`cert_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				`id` INT(10) NOT NULL,
				`type` ENUM('dmn','als','sub','alssub') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn',
				`password` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				`key` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`cert` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`ca_cert` TEXT COLLATE utf8_unicode_ci,
				`status` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`cert_id`),
				KEY `id` (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		";
	}

	/**
	 * Add order option for custom menus
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_101()
	{
		return $this->_addColumn(
			'custom_menus', 'menu_order', 'INT UNSIGNED NULL AFTER `menu_level`, ADD INDEX (`menu_order`)'
		);
	}

	/**
	 * Add plugin table for plugins management
	 *
	 * Note: Not used at this moment.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL Statement to be executed
	 */
	protected function _databaseUpdate_103()
	{
		return "
			CREATE TABLE IF NOT EXISTS `plugin` (
				`plugin_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`plugin_name` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL,
				`plugin_type` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
				`plugin_info` TEXT COLLATE utf8_unicode_ci NOT NULL,
				`plugin_config` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
				`plugin_status` VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'disabled',
				PRIMARY KEY (`plugin_id`),
				UNIQUE KEY `name` (`plugin_name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		";
	}

	/**
	 * Update for the `mail_users` table structure
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_104()
	{
		return array(
			// change to allows forward mail list
			'ALTER IGNORE TABLE `mail_users` CHANGE `mail_acc` `mail_acc` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL',
			// change to fix with RFC
			'ALTER IGNORE TABLE `mail_users` CHANGE `mail_addr` `mail_addr` VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
		);
	}

	/**
	 * Added parameter to allow the admin to append some paths to the default PHP open_basedir directive of customers
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return void
	 */
	protected function _databaseUpdate_105()
	{
		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig->PHPINI_OPEN_BASEDIR)) {
			$dbConfig->PHPINI_OPEN_BASEDIR = '';
		}
	}

	/**
	 * Database schema update (KEY for some fields)
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_106()
	{
		return array(
			$this->_addIndex('admin', 'created_by', '', 'INDEX'),
			$this->_addIndex('domain_aliasses', 'domain_id', '', 'INDEX'),
			$this->_addIndex('mail_users', 'domain_id', '', 'INDEX'),
			$this->_addIndex('reseller_props', 'reseller_id', '', 'INDEX'),
			$this->_addIndex('sql_database', 'domain_id', '', 'INDEX'),
			$this->_addIndex('sql_user', 'sqld_id', '', 'INDEX'),
			$this->_addIndex('subdomain', 'domain_id', '', 'INDEX'),
			$this->_addIndex('subdomain_alias', 'alias_id', '', 'INDEX')
		);
	}

	/**
	 * #366: Enhancement - Move menu label show/disable option at user profile level
	 *
	 * @author Paweł Iwanowski <kontakt@raisen.pl>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_107()
	{
		return array(
			$this->_addColumn('user_gui_props', 'show_main_menu_labels', "tinyint(1) NOT NULL DEFAULT '1'"),
			"DELETE FROM `config` WHERE `name` = 'MAIN_MENU_SHOW_LABELS'"
		);
	}

	/**
	 * #157: Enhancement - Relaying Domains
	 *
	 * @author Sascha Bay <worst.case@gmx.de>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_109()
	{
		return array(
			$this->_addColumn('domain', 'domain_external_mail', "VARCHAR(15) NOT NULL DEFAULT 'no'"),
			$this->_addColumn('domain', 'external_mail', "VARCHAR(15) NOT NULL DEFAULT 'off'"),
			$this->_addColumn('domain', 'external_mail_dns_ids', "VARCHAR(255) NOT NULL"),
			$this->_addColumn('domain_aliasses', 'external_mail', "VARCHAR(15) NOT NULL DEFAULT 'off'"),
			$this->_addColumn('domain_aliasses', 'external_mail_dns_ids', "VARCHAR(255) NOT NULL"),
			"UPDATE `hosting_plans` SET `props` = CONCAT(`props`, ';_no_')"
		);
	}

	/**
	 * #157: Enhancement - Relaying Domains
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_110()
	{
		return array(
			$this->_dropColumn('domain', 'external_mail_status'),
			$this->_dropColumn('domain_aliasses', 'external_mail_status'),
		);
	}

	/**
	 * #470: Default mail_quota is still 10485760 bytes
	 *
	 * @author Sascha Bay <worst.case@gmx.de>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_111()
	{
		return "ALTER TABLE `mail_users` CHANGE `quota` `quota` INT( 10 ) NULL DEFAULT '104857600'";
	}


	/**
	 * Update for the `quotalimits` and `quotatallies` table structure
	 *
	 * @author Daniel Andreca <sci2tech@gmail.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_112()
	{
		return array(
			'ALTER TABLE `quotalimits` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\'',
			'ALTER TABLE `quotatallies` CHANGE `name` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\''
		);
	}

	/**
	 * #433: Defect - register_globals does not exist in php 5.4.0 and above
	 *
	 * @author Sascha Bay <worst.case@gmx.de>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_113()
	{
		return array(
			"DELETE FROM `config` WHERE `name` = 'PHPINI_REGISTER_GLOBALS'",
			$this->_dropColumn('domain', 'phpini_perm_register_globals'),
			$this->_dropColumn('reseller_props', 'php_ini_al_register_globals'),
			$this->_dropColumn('php_ini', 'register_globals'),
			"UPDATE `hosting_plans` SET `props` = CONCAT(SUBSTRING_INDEX(`props`,';',14),';',SUBSTRING(`props` FROM LENGTH(SUBSTRING_INDEX(`props`, ';', 15))+2))"
		);
	}

	/**
	 * #447: External mail server feature is broken
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_114()
	{
		return array(
			// domain_dns.domain_id field should never be set to zero
			"UPDATE
				`domain_dns` AS `t1`
			SET
				`t1`.`domain_id` = (SELECT `t2`.`domain_id` FROM `domain_aliasses` AS `t2` WHERE `t1`.`alias_id` = `t2`.`alias_id`)
			WHERE
				`t1`.`domain_id` = 0",
			// domain_dns.domain_dns field should not be empty (domain related entries)
			"UPDATE
				`domain_dns` AS `t1`
			SET
				`t1`.`domain_dns` = CONCAT((SELECT `t2`.`domain_name` FROM `domain` AS `t2` WHERE `t1`.`domain_id` = `t2`.`domain_id`), '.')
			WHERE
				`t1`.`domain_dns` = ''
			AND
				`t1`.`protected` = 'yes'",
			// domain_dns.domain_dns field should not be empty (domain aliases related entries)
			"UPDATE
				`domain_dns` AS `t1`
			SET
				`t1`.`domain_dns` = CONCAT((SELECT `t2`.`alias_name` FROM `domain_aliasses` AS `t2` WHERE `t1`.`alias_id` = `t2`.`alias_id`), '.')
			WHERE
				`t1`.`domain_dns` = ''
			AND
				`t1`.`protected` = 'yes'",
			// domain_dns.domain_dns with value * must be completed with the domain name (domain related entries)
			"UPDATE
				`domain_dns` AS `t1`
			SET
				`t1`.`domain_dns` = CONCAT('*.', (SELECT `t2`.`domain_name` FROM `domain` AS `t2` WHERE `t1`.`domain_id` = `t2`.`domain_id`), '.')
			WHERE
				`t1`.`alias_id` = 0
			AND
				`t1`.`domain_dns` = '*'
			AND
				`t1`.`protected` = 'yes'",
			// domain_dns.domain_dns with value * must be completed with the domain name (domain aliases related entries)
			"UPDATE
				`domain_dns` AS `t1`
			SET
				`t1`.`domain_dns` = CONCAT('*.', (SELECT `t2`.`alias_name` FROM `domain_aliasses` AS `t2` WHERE `t1`.`alias_id` = `t2`.`alias_id`), '.')
			WHERE
				`t1`.`alias_id` <> 0
			AND
				`t1`.`domain_dns` = '*'
			AND
				`t1`.`protected` = 'yes'",
			// If a domain has only wildcard MX entries for external servers, update the domain.external_mail field to 'wildcard'
			"UPDATE
				`domain` AS `t1`
			SET
				`t1`.`external_mail` = 'wildcard'
			WHERE
				0 = (SELECT COUNT(`t2`.`domain_dns_id`) FROM `domain_dns` AS `t2` WHERE `t2`.`domain_id` = `t1`.`domain_id` AND `t2`.`alias_id` = 0 AND `t2`.`domain_dns` NOT LIKE '*.%')
			AND
				`t1`.external_mail = 'on'",
			// If a domain alias has only wildcard MX entries for external servers, update the domain.external_mail field to 'wildcard'
			"UPDATE
				`domain_aliasses` AS `t1`
			SET
				`t1`.`external_mail` = 'wildcard'
			WHERE
				`t1`.`alias_id` <> 0
			AND
				0 = (SELECT COUNT(`t2`.`domain_dns_id`) FROM `domain_dns` AS `t2` WHERE `t2`.`alias_id` = `t1`.`alias_id` AND `t2`.`domain_dns` NOT LIKE '*.%')
			AND
				`t1`.`external_mail` = 'on'",
			// Custom DNS CNAME record set via external mail feature are no longer allowed (User will have to re-add them)
			// via the custom DNS interface (easy update way)
			"DELETE FROM `domain_dns` WHERE `domain_type` = 'CNAME' AND `protected` = 'yes'"
		);
	}

	/**
	 * #145: Deletes possible orphan items in many tables
	 *
	 * Moved from database update 70 due to duplicate key in foreign keys map.
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_115()
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

		$stmt = execute_query('SELECT `admin_id` FROM `admin`');
		$usersIds = implode(',', $stmt->fetchall(PDO::FETCH_COLUMN));

		foreach ($tablesToForeignKey as $table => $foreignKey) {
			if (is_array($foreignKey)) {
				foreach ($foreignKey as $key) {
					$sqlUpd[] = "DELETE FROM `$table` WHERE `$key` NOT IN ($usersIds)";
				}
			} else {
				$sqlUpd[] = "DELETE FROM `$table` WHERE `$foreignKey` NOT IN ($usersIds)";
			}
		}

		return $sqlUpd;
	}

	/**
	 * Disk detail integration
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_116()
	{
		return array(
			$this->_addColumn('domain', 'domain_disk_file', 'bigint(20) unsigned default NULL AFTER `domain_disk_usage`'),
			$this->_addColumn('domain', 'domain_disk_mail', 'bigint(20) unsigned default NULL AFTER `domain_disk_file`'),
			$this->_addColumn('domain', 'domain_disk_sql', 'bigint(20) unsigned default NULL AFTER `domain_disk_mail`')
		);
	}

	/**
	 * Deletion of useless tables
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_117()
	{
		return array(
			'DROP TABLE IF EXISTS
				`roundcube_session`, `roundcube_searches`, `roundcube_identities`, `roundcube_dictionary`,
				`roundcube_contactgroupmembers`, `roundcube_contacts`, `roundcube_contactgroups`,
				`roundcube_cache_thread`, `roundcube_cache_messages`, `roundcube_cache_index`, `roundcube_cache`,
				`roundcube_users`
			'
		);
	}

	/**
	 * Fix Arabic locale name
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_118()
	{
		return "UPDATE `user_gui_props` SET `lang` = 'ar' WHERE `lang` = 'ar_AE'";
	}

	/**
	 * Lowercase PHP INI boolean
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_119()
	{
		return array(
			// System wide PHP directives values
			"REPLACE INTO `config` (`name`, `value`) VALUES ('PHPINI_ALLOW_URL_FOPEN', 'off')",
			"REPLACE INTO `config` (`name`, `value`) VALUES ('PHPINI_DISPLAY_ERRORS', 'off')",
			"UPDATE `php_ini` SET `allow_url_fopen` = 'on' WHERE `allow_url_fopen` = 'On'",
			"UPDATE `php_ini` SET `allow_url_fopen` = 'off' WHERE `allow_url_fopen` = 'Off'",
			"UPDATE `php_ini` SET `display_errors` = 'on' WHERE `display_errors` = 'On'",
			"UPDATE `php_ini` SET `display_errors` = 'off' WHERE `display_errors` = 'Off'"
		);
	}

	/**
	 * #552: Bug - PHP constants are not recognized outside of PHP (such as in Apache vhost files)
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_120()
	{
		$sqlQueries = array();

		$constantToInteger = array(
			'E_ALL & ~E_NOTICE & ~E_WARNING' => '30711', // Switch to E_ALL & ~E_NOTICE
			'E_ALL & ~E_DEPRECATED' => '22527', // Production
			'E_ALL & ~E_NOTICE' => '30711', // Default
			'E_ALL | E_STRICT' => '32767' // Development
		);

		foreach ($constantToInteger as $c => $i) {
			$sqlQueries[] = "UPDATE `config` SET `value` = '$i' WHERE `name` = 'PHPINI_ERROR_REPORTING' AND `value` ='$c'";
			$sqlQueries[] = "UPDATE `php_ini` SET `error_reporting` = '$i' WHERE `error_reporting` = '$c'";
		}

		return $sqlQueries;
	}

	/**
	 * Update for url forward fields
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_122()
	{
		return array(
			"ALTER TABLE `domain_aliasses` CHANGE `url_forward` `url_forward` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'",
			"ALTER TABLE `subdomain` CHANGE `subdomain_url_forward` `subdomain_url_forward` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'",
			"ALTER TABLE `subdomain_alias` CHANGE `subdomain_alias_url_forward` `subdomain_alias_url_forward` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'",
			"UPDATE `domain_aliasses` SET `url_forward` = 'no' WHERE `url_forward` IS NULL OR `url_forward` = ''",
			"UPDATE `subdomain` SET `subdomain_url_forward` = 'no' WHERE `subdomain_url_forward` IS NULL OR `subdomain_url_forward` = ''",
			"UPDATE `subdomain_alias` SET `subdomain_alias_url_forward` = 'no' WHERE `subdomain_alias_url_forward` IS NULL OR `subdomain_alias_url_forward` = ''"
		);
	}

	/**
	 * Adds admin.admin_status column
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_123()
	{
		return $this->_addColumn(
			'admin',
			'admin_status',
			"VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ok' AFTER `uniqkey_time`"
		);
	}

	/**
	 * Adds admin.admin_sys_uid and admin.admin_sys_gid columns
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array Stack of SQL statements to be executed
	 */
	protected function _databaseUpdate_124()
	{
		return array(
			$this->_addColumn('admin', 'admin_sys_uid', "INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `admin_type`"),
			$this->_addColumn('admin', 'admin_sys_gid', "INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `admin_sys_uid`")
		);
	}

	/**
	 * Update admin.admin_sys_uid and admin.admin_sys_gid columns with data from domain table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statements to be executed
	 */
	protected function _databaseUpdate_125()
	{
		$sqlUpdt = '';

		$stmt = exec_query("SHOW COLUMNS FROM `domain` LIKE 'domain_uid'");

		if($stmt->rowCount()) {
			$sqlUpdt = "
				UPDATE
					`admin` AS `t1`
				JOIN
					`domain` AS `t2` ON(`t2`.`domain_admin_id` = `t1`.`admin_id`)
				SET
					`t1`.`admin_sys_uid` = `t2`.`domain_uid`,
					`t1`.`admin_sys_gid` = `t2`.`domain_gid`
			";
		}

		return $sqlUpdt;
	}

	/**
	 * Drop domain.domain_uid and domain.domain_gid columns
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array SQL statetments to be executed
	 */
	protected function _databaseUpdate_126()
	{
		return array(
			$this->_dropColumn('domain', 'domain_uid'),
			$this->_dropColumn('domain', 'domain_gid')
		);
	}

	/**
	 * Add ftp_users.admin_id column (foreign key)
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_127()
	{
		return $this->_addColumn(
			'ftp_users',
			'admin_id',
			"INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `userid`, ADD INDEX (`admin_id`)"
		);
	}

	/**
	 * Update ftp_users.admin_id column with data from admin table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_128()
	{
		return "
			UPDATE
				`ftp_users` AS `t1`
			JOIN
				`admin` AS `t2` ON (`t2`.`admin_sys_uid` = `t1`.`uid`)
			SET
				`t1`.`admin_id` = `t2`.`admin_id`
		";
	}

	/**
	 * Add web_folder_protection column in domain table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_129()
	{
		return $this->_addColumn(
			'domain',
			'web_folder_protection',
			"VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes' AFTER `external_mail_dns_ids`"
		);
	}

	/**
	 * Set web folder protection option to 'no' for any existent customer
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be executed
	 */
	protected function _databaseUpdate_130()
	{
		return "UPDATE `domain` SET `web_folder_protection` = 'no'";
	}

	/**
	 * Drop orders and orders_settings tables
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be e executed
	 */
	protected function _databaseUpdate_131()
	{
		return "DROP TABLES IF EXISTS `orders`, `orders_settings`";
	}

	/**
	 * Drop useless columns in hosting_plan table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return array SQL statetments to be executed
	 */
	protected function _databaseUpdate_133()
	{
		return array(
			$this->_dropColumn('hosting_plans', 'price'),
			$this->_dropColumn('hosting_plans', 'setup_fee'),
			$this->_dropColumn('hosting_plans', 'value'),
			$this->_dropColumn('hosting_plans', 'vat'),
			$this->_dropColumn('hosting_plans', 'payment'),
			$this->_dropColumn('hosting_plans', 'tos')
		);
	}

	/**
	 * Delete order component related parameters
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be e executed
	 */
	protected function _databaseUpdate_134()
	{
		return "DELETE FROM `config` WHERE `name` = 'CUSTOM_ORDERPANEL_ID' OR `name` = 'ORDERS_EXPIRE_TIME'";
	}

	/**
	 * Drop straff_settings table
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be e executed
	 */
	protected function _databaseUpdate_135()
	{
		return "DROP TABLES IF EXISTS `straff_settings`";
	}

	/**
	 * Drop useless php_ini.status column
	 *
	 * @author Laurent Declercq <l.declercq@nuxwin.com>
	 * @return string SQL statement to be e executed
	 */
	protected function _databaseUpdate_136()
	{
		return $this->_dropColumn('php_ini', 'status');
	}
}
