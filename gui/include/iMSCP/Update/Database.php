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

            // Gets the stack of queries from the databse update method
            $queryStack = $this->$databaseUpdateMethod();

            // Checks if the current database update was already executed with a failed
            // status
            if (isset($dbConfig->FAILED_UPDATE)) {
                list($failedUpdate, $failedQueryIndex) = $dbConfig->FAILED_UPDATE;
            } else {
                $failedUpdate = '';
                $failedQueryIndex = -1;
            }

            // Execute all queries from the queries stack returned by the database
            // update method
            foreach ($queryStack as $index => $query)
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
                    // Store the query index that was failed and the database update
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
     * Check if a column exists in a database table and if not execute query to add
     * that column.
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

    // Implement all database update methods below

    /**
     * Catch all database updates methods (2 to 45) that were removed.
     *
     * Note: Database update 1 is now useless.
     *
     * @param  string $updateMethod Database method name
     * @param  array $param $parameter
     * @return array Stack of SQL statements to be applied
     */
    public function __call($updateMethod, $param)
    {
        return array();
    }

    /**
     * Fixed some CSRF issues in admin log.
     *
     * @author Thomas Wacker <thomas.wacker@ispcp.net>
     * @since r3695
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_46()
    {
        $sqlUpd = array();

        $sqlUpd[] = "TRUNCATE TABLE `log`;";

        return $sqlUpd;
    }

    /**
     * Removed unused 'suexec_props' table.
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r3709
     * @return array Stack of SQL statements to be applied
     */
    protected function _databaseUpdate_47()
    {
        return array("DROP TABLE IF EXISTS `suexec_props`;");
    }

    /**
     * Adding apps-installer ticket #14.
     *
     * @author  Sascha Bay <worst.case@gmx.de>
     * @since   r3695
     * @return  array Stack of SQL statements to be executed
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
                ;
            "
        );

        $sqlUpd[] = self::secureAddColumnTable(
            'reseller_props', 'software_allowed',
            "
                ALTER TABLE
                    `reseller_props`
                ADD
                    `software_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'no'
                ;
            "
        );

        $sqlUpd[] = self::secureAddColumnTable(
            'reseller_props', 'softwaredepot_allowed',
            "
                ALTER TABLE
                    `reseller_props`
                ADD
                    `softwaredepot_allowed` VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL default 'yes'
                ;
            "
        );

        $sqlUpd[] = "UPDATE `hosting_plans` SET `props` = CONCAT(`props`,';_no_');";

        return $sqlUpd;
    }

    /**
     * Add i-MSCP daemon service properties (moved to 50).
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r3985
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_49()
    {
        return array();
    }

    /**
     * Add i-MSCP daemon service properties
     *
     * @author Laurent Declercq <l.declercq@nuxwin.com>
     * @since r4004
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_50()
    {
        /** @var $dbConfig iMSCP_Config_Handler_Db */
        $dbConfig = iMSCP_Registry::get('dbConfig');
        $dbConfig->PORT_IMSCP_DAEMON = "9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1";

        return array();
    }

    /**
     * Added field for on-click-logon from the ftp-user site(such as PMA).
     *
     * @author William Lightning <kassah@gmail.com>
     * @return array Stack of SQL statements to be executed
     */
    protected function _databaseUpdate_51()
    {
        $sqlUpd = array();

        $query = "
			ALTER IGNORE TABLE
				`ftp_users`
			ADD
				`rawpasswd` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
			AFTER
				`passwd`
			;
		";

        $sqlUpd[] = self::secureAddColumnTable('ftp_users', 'rawpasswd', $query);

        return $sqlUpd;
    }

    /**
     * Adding apps-installer new options.
     *
     * @author  Sascha Bay (TheCry) <worst.case@gmx.de>
     * @since   r4036
     * @return  array Stack of SQL statements to be executed
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
			    ;
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
                ;
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
						`mail_pass`= '" . decrypt_db_password($stmt->fields['mail_pass']) . "',
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
						`sqlu_pass` = '" . decrypt_db_password($stmt->fields['sqlu_pass']) . "'
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
						`rawpasswd` = '" . decrypt_db_password($stmt->fields['rawpasswd']) . "'
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
            $sqlUpd[] = "ALTER TABLE $table ENGINE=InnoDB;";
        }

        return $sqlUpd;
    }
}
