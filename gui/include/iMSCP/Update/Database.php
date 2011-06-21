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
 * @category    iMSCP
 * @package     iMSCP_Update
 * @subpackage  Database
 * @copyright   2010-2011 by i-MSCP team
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Update */
require_once 'iMSCP/Update.php';

/**
 * Update version class.
 *
 * Checks if an update is available for i-MSCP.
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @subpackage  Database
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Update_Database extends iMSCP_Update
{
    /**
     * @var iMSCP_Update
     */
    protected static $_instance;

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
     * @return iMSCP_Update
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
     * @return bool TRUE if an update is available, FALSE otherwise
     */
    public function isAvailableUpdate()
    {
        if ($this->getLastAppliedUpdate() < $this->getNextUpdate()) {
            return true;
        }

        return false;
    }

    /**
     * Apply all available database updates.
     *
     * @return bool TRUE on success, FALSE othewise
     */
    public function applyUpdates()
    {
        /** @var $dbConfig iMSCP_Config_Handler_Db */
        $dbConfig = iMSCP_Registry::get('dbConfig');

        /** @var $pdo PDO */
        $pdo = iMSCP_Database::getRawInstance();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        while ($this->isAvailableUpdate()) {
            $databaseUpdateRevision = $this->getNextUpdate();

            // Get the database update method name
            $databaseUpdateMethod = '_databaseUpdate_' . $databaseUpdateRevision;

            // Gets the querie(s) from the databse update method
            // A database update can return void, an array or a string
            $queryStack = $this->$databaseUpdateMethod();

            if (!empty($queryStack)) {
                // Checks if the current database update was already executed with a
                // failed status
                if (isset($dbConfig->FAILED_UPDATE)) {
                    list($failedUpdate, $failedQueryIndex) = $dbConfig->FAILED_UPDATE;
                } else {
                    $failedUpdate = '';
                    $failedQueryIndex = -1;
                }

                // Execute all queries from the queries stack returned by the database
                // update method
                foreach ((array)$queryStack as $index => $query)
                {
                    // Query was already applied with success ?
                    if ($databaseUpdateMethod == $failedUpdate &&
                        $index < $failedQueryIndex
                    ) {
                        continue;
                    }

                    try {
                        // Execute query
                        $pdo->query($query);
                    } catch (PDOException $e) {
                        // Store the query index that failed and the database update
                        // method that wrap it
                        $dbConfig->FAILED_UPDATE = "$databaseUpdateMethod;$index";

                        // Prepare error message
                        $errorMessage = sprintf(
                            'Database update %s failed', $databaseUpdateRevision);

                        // Extended error message
                        if (PHP_SAPI != 'cli') {
                            $errorMessage .= ':<br /><br />' . $e->getMessage() .
                                             '<br /><br />Query: ' . trim($query);
                        } else {
                            $errorMessage .= ":\n\n" . $e->getMessage() .
                                             "\nQuery: " . trim($query);
                        }

                        $this->_lastError = $errorMessage;

                        return false;
                    }
                }
            }

            // Database update was successfully applied - updating revision number
            // in the database
            $dbConfig->set('DATABASE_REVISION', $databaseUpdateRevision);
        }

        // We should never run the backend scripts from the CLI update script
        if (PHP_SAPI != 'cli' && $this->_daemonRequest) {
            send_request();
        }

        return true;
    }

    /**
     * Return next database update revision.
     *
     * @return int 0 if no update available
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
     * Returns the revision of the last available datababse update.
     *
     * Note: For performances reasons, the revision is retrieved once.
     *
     * @return int The  revision of the last available database update
     */
    protected function getLastAvailableUpdateRevision()
    {
        static $lastAvailableUpdateRevision = null;

        if (null === $lastAvailableUpdateRevision) {
            $reflection = new ReflectionClass(__CLASS__);
            $databaseUpdateMethods = array();

            foreach ($reflection->getMethods() as $method)
            {
                if (strpos($method->name, '_databaseUpdate_') !== false) {
                    $databaseUpdateMethods[] = $method->name;
                }
            }

            $databaseUpdateMethod = (string)end($databaseUpdateMethods);
            $lastAvailableUpdateRevision = (int)substr(
                $databaseUpdateMethod, strrpos($databaseUpdateMethod, '_') + 1);
        }

        return $lastAvailableUpdateRevision;
    }

    /**
     * Returns revision of the last applied database update.
     *
     * @return int Revision of the last applied database update
     */
    protected function getLastAppliedUpdate()
    {
        /** @var $dbConfig iMSCP_Config_Handler_Db */
        $dbConfig = iMSCP_Registry::get('dbConfig');

        if (!isset($dbConfig->DATABASE_REVISION)) {
            $dbConfig->DATABASE_REVISION = 1;
        }

        return (int)$dbConfig->DATABASE_REVISION;
    }

    /**
     * Checks if a column exists in a database table and if not, execute a query to
     * add that column.
     *
     * @author Daniel Andreca <sci2tech@gmail.com>
     * @since r4509
     * @param string $table Database table name
     * @param string $column Column to be added in the database table
     * @param string $query Query to create column
     * @return string Query to be executed
     */
    protected function secureAddColumnTable($table, $column, $query)
    {
        $dbName = iMSCP_Registry::get('config')->DATABASE_NAME;

        return "
			DROP PROCEDURE IF EXISTS test;
			CREATE PROCEDURE test()
			BEGIN
				if not exists(
					SELECT
					    *
					FROM
					    information_schema.COLUMNS
					WHERE
					    column_name='$column'
					AND
					    table_name='$table'
					AND
					    table_schema='$dbName'
				) THEN
					$query;
				END IF;
			END;
			CALL test();
			DROP PROCEDURE IF EXISTS test;
		";
    }

    /**
     * Catch any database update that were removed.
     *
     * @param  string $updateMethod Database method name
     * @param  array $param $parameter
     * @return void
     */
    public function __call($updateMethod, $param) {}

    /**
     * Fixes some CSRF issues in admin log.
     *
     * @author Thomas Wacker <thomas.wacker@ispcp.net>
     * @since r3695
     * @return array SQL Statement
     */
    protected function _databaseUpdate_46()
    {
        return 'TRUNCATE TABLE `log`;';
    }

    /**
     * Removes useless 'suexec_props' table.
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r3709
     * @return array SQL Statement
     */
    protected function _databaseUpdate_47()
    {
        return 'DROP TABLE IF EXISTS `suexec_props`;';
    }

    /**
     * Adds table for software installer (ticket #14).
     *
     * @author Sascha Bay <worst.case@gmx.de>
     * @since  r3695
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_48()
    {
        $sqlUpd = array();
        $sqlUpd[] = "
	 		CREATE TABLE IF NOT EXISTS
	 			`web_software` (
					`software_id` int(10) unsigned NOT NULL auto_increment,
					`software_master_id` int(10) unsigned NOT NULL default '0',
					`reseller_id` int(10) unsigned NOT NULL default '0',
					`software_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_type` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_db` tinyint(1) NOT NULL,
					`software_archive` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_installfile` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_prefix` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_link` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_desc` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_active` int(1) NOT NULL,
					`software_status` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`rights_add_by` int(10) unsigned NOT NULL default '0',
					`software_depot` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
	  				PRIMARY KEY  (`software_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

        $sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_inst` (
					`domain_id` int(10) unsigned NOT NULL,
					`alias_id` int(10) unsigned NOT NULL default '0',
					`subdomain_id` int(10) unsigned NOT NULL default '0',
					`subdomain_alias_id` int(10) unsigned NOT NULL default '0',
					`software_id` int(10) NOT NULL,
					`software_master_id` int(10) unsigned NOT NULL default '0',
					`software_res_del` int(1) NOT NULL default '0',
					`software_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_version` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_language` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`path` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`software_prefix` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`db` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`database_user` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`database_tmp_pwd` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_username` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_password` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`install_email` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL default '0',
					`software_status` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`software_depot` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL NOT NULL DEFAULT 'no',
  					KEY `software_id` (`software_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

        $sqlUpd[] = self::secureAddColumnTable(
            'domain', 'domain_software_allowed',
            "
                ALTER TABLE
                    `domain`
                ADD
                    `domain_software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no'
            "
        );

        $sqlUpd[] = self::secureAddColumnTable(
            'reseller_props', 'software_allowed',
            "
                ALTER TABLE
                    `reseller_props`
                ADD
                    `software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no'
            "
        );

        $sqlUpd[] = self::secureAddColumnTable(
            'reseller_props', 'softwaredepot_allowed',
            "
                ALTER TABLE
                    `reseller_props`
                ADD
                    `softwaredepot_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'yes'
            "
        );

        $sqlUpd[] = "UPDATE `hosting_plans` SET `props` = CONCAT(`props`,';_no_');";

        return $sqlUpd;
    }

    /**
     * Adds i-MSCP daemon service properties.
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r4004
     * @return void
     */
    protected function _databaseUpdate_50()
    {
        /** @var $dbConfig iMSCP_Config_Handler_Db */
        $dbConfig = iMSCP_Registry::get('dbConfig');
        $dbConfig->PORT_IMSCP_DAEMON = "9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1";
    }

    /**
     * Adds field for on-click-logon from the ftp-user site(such as PMA).
     *
     * @author William Lightning <kassah@gmail.com>
     * @return string SQL Statement
     */
    protected function _databaseUpdate_51()
    {
        $query = "
			ALTER IGNORE TABLE
				`ftp_users`
			ADD
				`rawpasswd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
			AFTER
				`passwd`
		";

        return self::secureAddColumnTable('ftp_users', 'rawpasswd', $query);
    }

    /**
     * Adds new options for applications instller.
     *
     * @author Sascha Bay <worst.case@gmx.de>
     * @since  r4036
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_52()
    {
        $sqlUpd = array();

        $sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_depot` (
					`package_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`package_install_type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_version` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_language` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
					`package_type` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
					`package_description` mediumtext character set utf8 collate utf8_unicode_ci NOT NULL,
					`package_vendor_hp` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_download_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					`package_signature_link` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`package_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

        $sqlUpd[] = "
			CREATE TABLE IF NOT EXISTS
				`web_software_options` (
					`use_webdepot` tinyint(1) unsigned NOT NULL DEFAULT '1',
					`webdepot_xml_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					`webdepot_last_update` datetime NOT NULL
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
			;
		";

        $sqlUpd[] = "
			REPLACE INTO
				`web_software_options` (`use_webdepot`, `webdepot_xml_url`, `webdepot_last_update`)
			VALUES
				('1', 'http://app-pkg.i-mscp.net/imscp_webdepot_list.xml', '0000-00-00 00:00:00')
			;
		";

        $sqlUpd[] = self::secureAddColumnTable(
            'web_software',
            'software_installtype',
            "
				ALTER IGNORE TABLE
					`web_software`
				ADD
					`software_installtype` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL
				AFTER
					`reseller_id`
			"
        );

        $sqlUpd[] = " UPDATE `web_software` SET `software_installtype` = 'install';";

        $sqlUpd[] = self::secureAddColumnTable(
            'reseller_props',
            'websoftwaredepot_allowed',
            "
                ALTER IGNORE TABLE
                    `reseller_props`
                ADD
                    `websoftwaredepot_allowed` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL DEFAULT 'yes'
            "
        );

        return $sqlUpd;
    }

    /**
     * Decrypt email, ftp and sql users password in database.
     *
     * @author Daniel Andreca <sci2tech@gmail.com>
     * @since r4509
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_53()
    {
        $sqlUpd = array();

        $status = iMSCP_Registry::get('config')->ITEM_CHANGE_STATUS;

        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');

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
			;
		";

        $stmt = exec_query($query);

        if ($stmt->recordCount() != 0) {
            while (!$stmt->EOF) {
                $sqlUpd[] = "
					UPDATE
						`mail_users`
					SET
						`mail_pass`= " . $db->quote(decrypt_db_password($stmt->fields['mail_pass'])) . ",
						`status` = '$status' WHERE `mail_id` = '" . $stmt->fields['mail_id'] . "'
					;
				";

                $stmt->moveNext();
            }
        }

        $stmt = exec_query("SELECT `sqlu_id`, `sqlu_pass` FROM `sql_user`;");

        if ($stmt->recordCount() != 0) {
            while (!$stmt->EOF) {
                $sqlUpd[] = "
					UPDATE
						`sql_user`
					SET
						`sqlu_pass` = " . $db->quote(decrypt_db_password($stmt->fields['sqlu_pass'])) . "
					WHERE `sqlu_id` = '" . $stmt->fields['sqlu_id'] . "'
					;
				";

                $stmt->moveNext();
            }
        }

        $stmt = exec_query("SELECT `userid`, `rawpasswd` FROM `ftp_users`;");

        if ($stmt->recordCount() != 0) {
            while (!$stmt->EOF) {
                $sqlUpd[] = "
					UPDATE
						`ftp_users`
					SET
						`rawpasswd` = " . $db->quote(decrypt_db_password($stmt->fields['rawpasswd'])) . "
					WHERE
					    `userid` = '" . $stmt->fields['userid'] . "'
					;
				";

                $stmt->moveNext();
            }
        }

        return $sqlUpd;
    }

    /**
     * Convert tables to InnoDB.
     *
     * @author Daniel Andreca <sci2tech@gmail.com>
     * @since r4509
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_54()
    {
        $sqlUpd = array();

        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');

        $tables = $db->metaTables();

        foreach ($tables as $table) {
            $sqlUpd[] = "ALTER TABLE `$table` ENGINE=InnoDB;";
        }

        return $sqlUpd;
    }

    /**
     * Adds unique index on user_id column from the user_gui_props table.
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r4592
     * @return array SQL Statement
     */
    protected function _databaseUpdate_56()
    {
        return 'ALTER IGNORE TABLE `user_gui_props` ADD UNIQUE (`user_id`);';
    }

    /**
     * Remove all parentheses from language database tables.
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r4592
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_57()
    {
        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');

        $sqlUpd = $queryParts = array();

        foreach ($db->metaTables() as $tableName) {
            // Is language database table ?
            if (strpos($tableName, 'lang_') === false ||
                (strpos($tableName, '(') === false &&
                 strpos($tableName, ')') === false)
            ) {
                continue;
            }

            $newTableName = str_replace(array('(', ')'), '', $tableName);
            $queryParts[] = "`$tableName` TO `$newTableName`";
        }

        if (!empty($queryParts)) {
            $sqlUpd[] = 'RENAME TABLE ' . implode(', ', $queryParts) . ';';

            foreach ($queryParts as $queryPart) {
                $table = substr($queryPart, strrpos($queryPart, 'TO ') + 3);
                $sqlUpd[] = "UPDATE $table SET `msgstr` = " .
                            str_replace(array('lang_', '`'), array('', '\''), $table) .
                            " WHERE `msgid` = 'imscp_table';";
                $sqlUpd[] = "ALTER TABLE $table ENGINE=InnoDB;";
            }
        }

        $sqlUpd[] = "
            UPDATE
                `config`
            SET
                `value` = 'lang_EnglishBritain'
            WHERE
                `name` = 'USER_INITIAL_LANG'
            ;
        ";

        // Will reset the language property for all users (expected behavior) to
        // ensure compatibility with the fix. So then each user will have to set
        // (again) his own language if he want use an other language than the default.
        $sqlUpd[] = "UPDATE `user_gui_props` SET `lang` = 'lang_EnglishBritain';";

        return $sqlUpd;
    }

    /**
     * Drop useless column in user_gui_props table.
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r4644
     * @return string SQL Statement
     */
    protected function _databaseUpdate_59()
    {
        return "
            DROP PROCEDURE IF EXISTS schema_change;
                CREATE PROCEDURE schema_change()
                BEGIN
                    IF EXISTS (
		                SELECT
			                *
		                FROM
			                information_schema.COLUMNS
		                WHERE
			                table_name = 'user_gui_props'
		                AND
			                column_name = 'id'
	                ) THEN
		                ALTER TABLE `user_gui_props` DROP column `id`;
                    END IF;
                END;
                CALL schema_change();
            DROP PROCEDURE IF EXITST schema_change;
        ";
    }

    /**
     * Convert tables to InnoDB.
     *
     * @author Daniel Andreca <sci2tech@gmail.com>
     * @since r4650
     * @return string SQL Statement
     */
    protected function _databaseUpdate_60()
    {
        return 'ALTER TABLE `autoreplies_log` ENGINE=InnoDB';
    }

    /**
     * Fix for #102 - Changes naming convention for database language tables
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r4644
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_61()
    {
        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');

        $sqlUpd = array();

        // Drop all old database language tables excepted the lang_en_GB that is
        // created by engine on setup / install
        foreach ($db->metaTables() as $tableName) {
            if (strpos($tableName, 'lang_') !== false &&
                $tableName != 'lang_en_GB'
            ) {
                $sqlUpd[] = "DROP TABLE `$tableName`";
            }
        }

        // Will reset the language property for all users (expected behavior) to
        // ensure compatibility with the fix. So then each user will have to set
        // (again) his own language if he want use an other language than the default.
        $sqlUpd[] = "UPDATE `user_gui_props` SET `lang` = 'lang_en_GB'";

        return $sqlUpd;
    }
}
