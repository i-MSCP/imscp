<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP team
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
 */

/**
 * Class iMSCP_Update_Database
 */
class iMSCP_Update_Database extends iMSCP_Update
{
    /**
     * @var iMSCP_Update
     */
    protected static $instance;

    /**
     * @var
     */
    protected $config;

    /**
     * @var
     */
    protected $dbConfig;

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
    protected $lastUpdate = 241;

    /**
     * Singleton - Make new unavailable
     */
    protected function __construct()
    {
        $this->config = iMSCP_Registry::get('config');
        $this->dbConfig = iMSCP_Registry::get('dbConfig');
        if (!isset($this->config['DATABASE_NAME'])) {
            throw new iMSCP_Update_Exception('Database name not found.');
        }

        $this->databaseName = $this->config['DATABASE_NAME'];
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
     * Return next database update revision
     *
     * @return int 0 if no update is available
     */
    public function getNextUpdate()
    {
        $lastAvailableUpdateRevision = $this->lastUpdate;
        $nextUpdateRevision = $this->getLastAppliedUpdate();
        if ($nextUpdateRevision < $lastAvailableUpdateRevision) {
            return ++$nextUpdateRevision;
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
     * Apply database updates
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public function applyUpdates()
    {
        ignore_user_abort(true);

        $pdo = iMSCP_Database::getRawInstance();
        while ($this->isAvailableUpdate()) {
            $revision = $this->getNextUpdate();

            try {
                $updateMethod = 'r' . $revision;
                $queries = (array)$this->$updateMethod();

                if (empty($queries)) {
                    $this->dbConfig['DATABASE_REVISION'] = $revision;
                    continue;
                }

                $pdo->beginTransaction();
                foreach ($queries as $query) {
                    if (!empty($query)) {
                        $pdo->query($query);
                    }
                }

                $this->dbConfig['DATABASE_REVISION'] = $revision;
                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $this->setError(sprintf('Database update %s failed: %s', $revision, $e->getMessage()));
                return false;
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

        foreach (range($this->getNextUpdate(), $this->lastUpdate) as $revision) {
            $methodName = "r$revision";

            if (!$reflection->hasMethod($methodName)) {
                continue;
            }

            $method = $reflection->getMethod($methodName);
            $details = explode("\n", $method->getDocComment());
            $normalizedDetails = '';
            array_shift($details);

            foreach ($details as $detail) {
                if (!preg_match('/^(?: |\t)*\*(?: |\t)+([^@]*)$/', $detail, $matches)) {
                    break;
                }

                if (empty($normalizedDetails)) {
                    $normalizedDetails = $matches[1];
                } else {
                    $normalizedDetails .= '<br>' . $matches[1];
                }
            }

            $updatesDetails[$revision] = $normalizedDetails;
        }

        return $updatesDetails;
    }

    /**
     * Returns last applied update
     *
     * @return int Revision number of the last applied database update
     */
    public function getLastAppliedUpdate()
    {
        if (!isset($this->dbConfig['DATABASE_REVISION'])) {
            $this->dbConfig['DATABASE_REVISION'] = 1;
        }

        return $this->dbConfig['DATABASE_REVISION'];
    }

    /**
     * Does the given table is known?
     *
     * @param string $table
     * @return int TRUE if th
     */
    protected function isKnownTable($table)
    {
        return exec_query('SHOW TABLES LIKE ?', $table)->rowCount();
    }

    /**
     * Remove any duplicate rows in the given table for the given column(s)
     *
     * @throws iMSCP_Exception_Database
     * @param string $table Table name
     * @param string|array $columns Column(s)
     * @return array SQL statements to be executed
     */
    protected function removeDuplicateRowsOnColumns($table, $columns)
    {
        $originTable = $table;
        $tableWithDup = $table . '_tmp1';
        $tableWithoutDup = $table . '_tmp2';

        return array(
            sprintf(
                "CREATE TABLE %s LIKE %s; INSERT %s SELECT * FROM %s GROUP BY %s;",
                quoteIdentifier($tableWithoutDup),
                quoteIdentifier($originTable),
                quoteIdentifier($tableWithoutDup),
                quoteIdentifier($originTable),
                implode(',', array_map('quoteIdentifier', (array)$columns))
            ),
            sprintf('ALTER TABLE %s RENAME TO %s', $originTable, quoteIdentifier($tableWithDup)),
            sprintf('ALTER TABLE %s RENAME TO %s', $tableWithoutDup, quoteIdentifier($originTable)),
            $this->dropTable($tableWithDup)
        );
    }

    /**
     * Rename table
     *
     * @param string $table Table name
     * @param string $newTableName New table name
     * @return null|string SQL statement to be executed
     * @throws iMSCP_Exception_Database
     */
    protected function renameTable($table, $newTableName)
    {
        $table = quoteIdentifier($table);
        $stmt = exec_query('SHOW TABLES LIKE ?', $table);
        if ($stmt->rowCount()) {
            return sprintf('ALTER TABLE %s RENAME TO %s', $table, quoteIdentifier($newTableName));
        }

        return null;
    }

    /**
     * Drop table
     *
     * @param string $table Table name
     * @return string SQL statement to be executed
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
            return sprintf('ALTER TABLE %s ADD %s %s', $table, quoteIdentifier($column), $columnDefinition);
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
            return sprintf('ALTER TABLE %s CHANGE %s %s', $table, quoteIdentifier($column), $columnDefinition);
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
            return sprintf('ALTER TABLE %s DROP %s', $table, quoteIdentifier($column));
        }

        return null;
    }

    /**
     * Add index
     *
     * Be aware that no check is made for duplicate rows. Thus, if you want to add an UNIQUE contraint, you must make
     * sure to remove duplicate rows first. We don't make usage of the IGNORE clause for the following reasons:
     *
     * - The IGNORE clause is no standard and do not work with Fast Index Creation (MySQL #Bug #40344)
     * - The IGNORE clause will be removed in MySQL 5.7
     *
     * @param string $table Database table name
     * @param array|string $columns Column name(s) with OPTIONAL key length
     * @param string $indexType Index type (PRIMARY KEY (default), INDEX|KEY, UNIQUE)
     * @param string $indexName Index name (default is autogenerated)
     * @return null|string SQL statement to be executed
     */
    protected function addIndex($table, $columns, $indexType = 'PRIMARY KEY', $indexName = '')
    {
        $table = quoteIdentifier($table);
        $indexType = strtoupper($indexType);
        $columnsTmp = (array)$columns;
        $columns = array();

        // Parse column definitions
        foreach ($columnsTmp as $columnDef) {
            if (preg_match('/^(?P<name>[^(]+)(?P<length>\(\d+\))$/', $columnDef, $matches)) {
                $columns[$matches['name']] = $matches['length'];
            } else {
                $columns[$columnDef] = '';
            }
        }
        unset($columnsTmp);

        $indexName = $indexType == 'PRIMARY KEY' ? 'PRIMARY' : ($indexName == '' ? key($columns) : $indexName);
        $stmt = exec_query("SHOW INDEX FROM $table WHERE KEY_NAME = ?", $indexName);
        if (!$stmt->rowCount()) {
            $columnsStr = '';
            foreach ($columns as $column => $length) {
                $columnsStr .= quoteIdentifier($column) . $length . ',';
            }
            unset($columns);

            $indexName = $indexName == 'PRIMARY' ? '' : quoteIdentifier($indexName);
            return sprintf('ALTER TABLE %s ADD %s %s (%s)', $table, $indexType, $indexName, rtrim($columnsStr, ','));
        }

        return null;
    }

    /**
     * Drop any index which belong to the given column in the given table
     *
     * Be aware that no check is made for duplicate rows. Thus, if by remove an index, this can result to du
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return array SQL statements to be executed
     */
    protected function dropIndexByColumn($table, $column)
    {
        $sqlQueries = array();
        $table = quoteIdentifier($table);
        $stmt = exec_query("SHOW INDEX FROM $table WHERE COLUMN_NAME = ?", $column);
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $row = array_change_key_case($row, CASE_UPPER);
                $sqlQueries[] = sprintf('ALTER TABLE %s DROP INDEX %s', $table, quoteIdentifier($row['KEY_NAME']));
            }
        }

        return $sqlQueries;
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
            return sprintf('ALTER TABLE %s DROP INDEX %s', $table, quoteIdentifier($indexName));
        }

        return null;
    }

    /**
     * Catch any database updates that were removed
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
     * Please, add all the database update methods below. Don't forget to update the `lastUpdate' field above.
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
        $sqlQueries = array(
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
                $id = quoteValue($row['id'], PDO::PARAM_INT);
                $props = explode(';', $row['props']);

                if (count($props) == 12) {
                    $sqlQueries[] = "UPDATE hosting_plans SET props = CONCAT(props,';_no_') WHERE id = $id";
                }
            }
        }

        return $sqlQueries;
    }

    /**
     * Adds i-MSCP daemon service properties in config table
     *
     * @return null
     */
    protected function r50()
    {
        $this->dbConfig['PORT_IMSCP_DAEMON'] = '9876;tcp;i-MSCP-Daemon;1;0;127.0.0.1';
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
        $sqlQueries = array(
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

        return $sqlQueries;
    }

    /**
     * Decrypts email, ftp and SQL users passwords in database
     *
     * @return array SQL statements to be executed
     */
    protected function r53()
    {
        $sqlQueries = array();

        // Decrypt all mail passwords
        $stmt = execute_query(
            "
                SELECT mail_id, mail_pass FROM mail_users
                WHERE mail_type RLIKE '^(normal_mail|alias_mail|subdom_mail|alssub_mail)'
            "
        );

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $password = quoteValue(decryptBlowfishCbcPassword($row['mail_pass']));
                $status = quoteValue('tochange');
                $mailId = quoteValue($row['mail_id'], PDO::PARAM_INT);
                $sqlQueries[] = "UPDATE mail_users SET mail_pass = $password, status = $status WHERE mail_id = $mailId";
            }
        }

        // Decrypt all SQL users passwords
        $stmt = exec_query('SELECT sqlu_id, sqlu_pass FROM sql_user');
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $password = quoteValue(decryptBlowfishCbcPassword($row['sqlu_pass']));
                $id = quoteValue($row['sqlu_id'], PDO::PARAM_INT);
                $sqlQueries[] = "UPDATE sql_user SET sqlu_pass = $password WHERE sqlu_id = $id";
            }
        }

        // Decrypt all Ftp users passwords
        $stmt = exec_query('SELECT userid, passwd FROM ftp_users');
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $password = quoteValue(decryptBlowfishCbcPassword($row['passwd']));
                $userId = quoteValue($row['userid']);
                $sqlQueries[] = "UPDATE ftp_users SET rawpasswd = $password WHERE userid = $userId";
            }
        }

        return $sqlQueries;
    }

    /**
     * Converts all tables to InnoDB engine
     *
     * @return array SQL statements to be executed
     */
    protected function r60()
    {
        $sqlQueries = array();
        foreach (iMSCP_Database::getInstance()->getTables() as $table) {
            $table = quoteIdentifier($table);
            $sqlQueries[] = "ALTER TABLE $table ENGINE=InnoDB";
        }

        return $sqlQueries;
    }

    /**
     * Deletes old DUMP_GUI_DEBUG parameter from the config table
     *
     * @return null
     */
    protected function r66()
    {
        if (isset($this->dbConfig['DUMP_GUI_DEBUG'])) {
            unset($this->dbConfig['DUMP_GUI_DEBUG']);
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
        $sqlQueries = array();

        // First step: Update default language (new naming convention)
        if (isset($this->dbConfig['USER_INITIAL_LANG'])) {
            $this->dbConfig['USER_INITIAL_LANG'] = str_replace('lang_', '', $this->dbConfig['USER_INITIAL_LANG']);
        }

        // Second step: Removing all database languages tables
        foreach (iMSCP_Database::getInstance()->getTables('lang_%') as $tableName) {
            $sqlQueries[] = $this->dropTable($tableName);
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
            $sqlQueries[] = "UPDATE user_gui_props SET lang = $locale WHERE lang = $language";
        }

        return $sqlQueries;
    }

    /**
     * #119: Defect - Error when adding IP's
     *
     * @return array SQL statements to be executed
     */
    protected function r68()
    {
        $sqlQueries = array();
        $stmt = execute_query('SELECT ip_id, ip_card FROM server_ips');
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                list($cardname) = explode(':', $row['ip_card']);
                $cardname = quoteValue($cardname);
                $ipId = quoteValue($row['ip_id']);
                $sqlQueries[] = "UPDATE server_ips SET ip_card = $cardname WHERE ip_id = $ipId";
            }
        }

        return $sqlQueries;
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
                'user_gui_props', 'lang', 'lang VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
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
     * @return array SQL statements to be executed
     */
    protected function r72()
    {
        $sqlQueries = $this->removeDuplicateRowsOnColumns('web_software_options', 'use_webdepot');
        $sqlQueries[] = $this->addIndex('web_software_options', 'use_webdepot', 'UNIQUE', 'use_webdepot');
        return $sqlQueries;
    }

    /**
     * Adds unique index on user_gui_props.user_id column
     *
     * @return array SQL statements to be executed
     */
    protected function r76()
    {
        $sqlQueries = $this->removeDuplicateRowsOnColumns('user_gui_props', 'user_id');
        $sqlQueries = array_merge($sqlQueries, $this->dropIndexByColumn('user_gui_props', 'user_id'));
        $sqlQueries[] = $this->addIndex('user_gui_props', 'user_id', 'UNIQUE', 'user_id');
        return $sqlQueries;
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
                FROM mail_users AS t1
                LEFT JOIN domain_aliasses AS t2 ON (t1.sub_id = t2.alias_id)
                WHERE t1.mail_type = 'alias_forward'
                AND t1.mail_addr = ''
            ",
            "
                REPLACE INTO mail_users (
                    mail_id, mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
                    mail_auto_respond_text, quota, mail_addr
                ) SELECT
                    mail_id, mail_acc, mail_pass, mail_forward, t1.domain_id, mail_type, sub_id, status,
                    mail_auto_respond, mail_auto_respond_text, quota,
                    CONCAT(mail_acc, '@', subdomain_alias_name, '.', alias_name) AS mail_addr
                FROM mail_users AS t1
                LEFT JOIN subdomain_alias AS t2 ON (t1.sub_id = t2.subdomain_alias_id)
                LEFT JOIN domain_aliasses AS t3 ON (t2.alias_id = t3.alias_id)
                WHERE t1.mail_type = 'alssub_forward' AND t1.mail_addr = ''
            ",
            "
                REPLACE INTO mail_users(
                    mail_id, mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
                    mail_auto_respond_text, quota, mail_addr
                ) SELECT
                    mail_id, mail_acc, mail_pass, mail_forward, t1.domain_id, mail_type, sub_id, status,
                    mail_auto_respond, mail_auto_respond_text, quota,
                    CONCAT(mail_acc, '@', subdomain_name, '.', domain_name) AS mail_addr
                FROM mail_users AS t1
                LEFT JOIN subdomain AS t2 ON (t1.sub_id = t2.subdomain_id)
                LEFT JOIN domain AS t3 ON (t2.domain_id = t3.domain_id)
                WHERE t1.mail_type = 'subdom_forward' AND t1.mail_addr = ''
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
        $this->dbConfig['PHPINI_ALLOW_URL_FOPEN'] = 'off';
        $this->dbConfig['PHPINI_DISPLAY_ERRORS'] = 'off';
        $this->dbConfig['PHPINI_REGISTER_GLOBALS'] = 'off';
        $this->dbConfig['PHPINI_UPLOAD_MAX_FILESIZE'] = '2';
        $this->dbConfig['PHPINI_POST_MAX_SIZE'] = '8';
        $this->dbConfig['PHPINI_MEMORY_LIMIT'] = '64';
        $this->dbConfig['PHPINI_MAX_INPUT_TIME'] = '60';
        $this->dbConfig['PHPINI_MAX_EXECUTION_TIME'] = '30';
        $this->dbConfig['PHPINI_ERROR_REPORTING'] = 'E_ALL & ~E_NOTICE';
        $this->dbConfig['PHPINI_DISABLE_FUNCTIONS'] = 'show_source,system,shell_exec,passthru,exec,phpinfo,shell,symlink';
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
                'reseller_props', 'php_ini_system', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER websoftwaredepot_allowed"
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
                "int(11) NOT NULL DEFAULT '64' AFTER php_ini_max_max_input_time"
            ),

            // Domain permissions columns for PHP directives
            $this->addColumn(
                'domain', 'phpini_perm_system', "VARCHAR(15) NOT NULL DEFAULT 'no' AFTER domain_software_allowed"
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
                memory_limit INT(11) NOT NULL DEFAULT '64',
                PRIMARY KEY (ID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ";
    }

    /**
     * Add hosting plan properties for PHP editor
     *
     * @return array SQL statements to be executed
     */
    protected function r87()
    {
        $sqlQueries = array();

        $stmt = execute_query("SELECT id, props FROM hosting_plans");
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $id = quoteValue($row['id'], PDO::PARAM_INT);
                $props = explode(';', $row['props']);
                if (count($props) == 13) {
                    $sqlQueries[] = "UPDATE hosting_plans SET props = ';no;no;no;no;no;8;2;30;60;64' WHERE id = $id";
                }
            }
        }

        return $sqlQueries;
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
        $sqlQueries = array();

        // Reset reseller permissions
        foreach (
            array(
                'php_ini_system', 'php_ini_al_disable_functions', 'php_ini_al_allow_url_fopen',
                'php_ini_al_register_globals', 'php_ini_al_display_errors'
            ) as $permission
        ) {
            $sqlQueries[] = "UPDATE reseller_props SET $permission = 'no'";
        }

        // Reset reseller default values for PHP directives (To default system wide value)
        foreach (
            array(
                'post_max_size' => '8',
                'upload_max_filesize' => '2',
                'max_execution_time' => '30',
                'max_input_time' => '60',
                'memory_limit' => '64'
            ) as $directive => $defaultValue
        ) {
            $sqlQueries[] = "UPDATE reseller_props SET php_ini_max_{$directive} = '$defaultValue'";
        }

        return $sqlQueries;
    }

    /**
     * Truncate the php_ini table (related to r88)
     *
     * @return string SQL statement to be executed
     */
    protected function r89()
    {
        $this->_daemonRequest = true;
        return 'TRUNCATE TABLE php_ini';
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
            $this->addIndex('domain', 'domain_id'), // Add PRIMARY KEY
            $this->dropIndexByName('domain', 'domain_id'), // Remove UNIQUE index
            $this->addIndex('email_tpls', 'id'), // Add PRIMARY KEY
            $this->dropIndexByName('email_tpls', 'id'), // Remove UNIQUE index
            $this->addIndex('hosting_plans', 'id'), // Add PRIMARY KEY
            $this->dropIndexByName('hosting_plans', 'id'), // Remove UNIQUE index
            $this->addIndex('htaccess', 'id'), // Add PRIMARY KEY
            $this->dropIndexByName('htaccess', 'id'), // Remove UNIQUE index
            $this->addIndex('htaccess_groups', 'id'), // Add PRIMARY KEY
            $this->dropIndexByName('htaccess_groups', 'id'), // Remove UNIQUE index
            $this->addIndex('htaccess_users', 'id'), // Add PRIMARY KEY
            $this->dropIndexByName('htaccess_users', 'id'), // Remove UNIQUE index
            $this->addIndex('reseller_props', 'id'), // Add PRIMARY KEY
            $this->dropIndexByName('reseller_props', 'id'), // Remove UNIQUE index
            $this->addIndex('server_ips', 'ip_id'), // Add PRIMARY KEY
            $this->dropIndexByName('server_ips', 'ip_id'), // Remove UNIQUE index
            $this->addIndex('sql_database', 'sqld_id'), // Add PRIMARY KEY
            $this->dropIndexByName('sql_database', 'sqld_id'), // Remove UNIQUE index
            $this->addIndex('sql_user', 'sqlu_id'), // Add PRIMARY KEY
            $this->dropIndexByName('sql_user', 'sqlu_id') // Remove UNIQUE index
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
            'VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER layout'
        );
    }

    /**
     * Allow to change SSH port number
     *
     * @return null
     */
    protected function r97()
    {
        if (isset($this->dbConfig['PORT_SSH'])) {
            $this->dbConfig['PORT_SSH'] = '22;tcp;SSH;1;1;';
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
        return "
            CREATE TABLE IF NOT EXISTS ssl_certs (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
     * Update mail_users table structure
     *
     * @return array SQL statements to be executed
     */
    protected function r104()
    {
        return array(
            // change to allows forward mail list
            $this->changeColumn(
                'mail_users', 'mail_acc', 'mail_acc TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
            ),
            $this->changeColumn(
                'mail_users',
                'mail_addr',
                'mail_addr VARCHAR(254) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
            )
        );
    }

    /**
     * Database schema update (KEY for some fields)
     *
     * @return array SQL statements to be executed
     */
    protected function r106()
    {
        return array(
            $this->addIndex('admin', 'created_by', 'INDEX', 'created_by'),
            $this->addIndex('domain_aliasses', 'domain_id', 'INDEX', 'domain_id'),
            $this->addIndex('mail_users', 'domain_id', 'INDEX', 'domain_id'),
            $this->addIndex('reseller_props', 'reseller_id', 'INDEX', 'reseller_id'),
            $this->addIndex('sql_database', 'domain_id', 'INDEX', 'domain_id'),
            $this->addIndex('sql_user', 'sqld_id', 'INDEX', 'sqld_id'),
            $this->addIndex('subdomain', 'domain_id', 'INDEX', 'domain_id'),
            $this->addIndex('subdomain_alias', 'alias_id', 'INDEX', 'alias_id')
        );
    }

    /**
     * #366: Enhancement - Move menu label show/disable option at user profile level
     *
     * @return null|string SQL statement to be executed
     */
    protected function r107()
    {
        if (isset($this->dbConfig['MAIN_MENU_SHOW_LABELS'])) {
            unset($this->dbConfig['MAIN_MENU_SHOW_LABELS']);
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
        $sqlQueries = array(
            $this->addColumn('domain', 'domain_external_mail', "VARCHAR(15) NOT NULL DEFAULT 'no'"),
            $this->addColumn('domain', 'external_mail', "VARCHAR(15) NOT NULL DEFAULT 'off'"),
            $this->addColumn('domain', 'external_mail_dns_ids', "VARCHAR(255) NOT NULL"),
            $this->addColumn('domain_aliasses', 'external_mail', "VARCHAR(15) NOT NULL DEFAULT 'off'"),
            $this->addColumn('domain_aliasses', 'external_mail_dns_ids', "VARCHAR(255) NOT NULL")
        );

        $stmt = execute_query('SELECT id, props FROM hosting_plans');
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $id = quoteValue($row['id'], PDO::PARAM_INT);
                $props = explode(';', $row['props']);
                if (count($props) == 23) {
                    $sqlQueries[] = "UPDATE hosting_plans SET props = CONCAT(props, ';_no_') WHERE id = $id";
                }
            }
        }

        return $sqlQueries;
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
            $this->changeColumn(
                'quotalimits',
                'name',
                "name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
            ),
            $this->changeColumn(
                'quotatallies',
                'name',
                "name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
            )
        );
    }

    /**
     * #433: Defect - register_globals does not exist in php 5.4.0 and above
     *
     * @return array SQL statements to be executed
     */
    protected function r113()
    {
        if (isset($this->dbConfig['PHPINI_REGISTER_GLOBALS'])) {
            unset($this->dbConfig['PHPINI_REGISTER_GLOBALS']);
        }

        $sqlQueries = array(
            $this->dropColumn('domain', 'phpini_perm_register_globals'),
            $this->dropColumn('reseller_props', 'php_ini_al_register_globals'),
            $this->dropColumn('php_ini', 'register_globals')
        );

        $stmt = execute_query("SELECT id, props FROM hosting_plans");

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $id = quoteValue($row['id'], PDO::PARAM_INT);
                $props = explode(';', $row['props']);

                if (count($props) == 24) {
                    unset($props[15]); // Remove register_globals properties
                    $sqlQueries[] = 'UPDATE hosting_plans SET props = ' . quoteValue(implode(';', $props)) . "WHERE id = $id";
                }
            }
        }

        return $sqlQueries;
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
                UPDATE domain_dns AS t1 SET t1.domain_id = (
                    SELECT t2.domain_id FROM domain_aliasses AS t2 WHERE t1.alias_id = t2.alias_id
                ) WHERE t1.domain_id = 0
            ",
            // domain_dns.domain_dns field should not be empty (domain related entries)
            "
                UPDATE domain_dns AS t1 SET t1.domain_dns = CONCAT(
                    (SELECT t2.domain_name FROM domain AS t2 WHERE t1.domain_id = t2.domain_id), '.'
                ) WHERE t1.domain_dns = '' AND t1.protected = 'yes'
            ",
            // domain_dns.domain_dns field should not be empty (domain aliases related entries)
            "
                UPDATE domain_dns AS t1 SET t1.domain_dns = CONCAT(
                    (SELECT t2.alias_name FROM domain_aliasses AS t2 WHERE t1.alias_id = t2.alias_id), '.'
                ) WHERE t1.domain_dns = '' AND t1.protected = 'yes'
            ",
            // domain_dns.domain_dns with value * must be completed with the domain name (domain related entries)
            "
                UPDATE domain_dns AS t1 SET t1.domain_dns = CONCAT(
                    '*.', (SELECT t2.domain_name FROM domain AS t2 WHERE t1.domain_id = t2.domain_id), '.'
                ) WHERE t1.alias_id = 0 AND t1.domain_dns = '*' AND t1.protected = 'yes'
            ",
            // domain_dns.domain_dns with value * must be completed with the domain name (domain aliases related entries)
            "
                UPDATE domain_dns AS t1 SET t1.domain_dns = CONCAT(
                    '*.', (SELECT t2.alias_name FROM domain_aliasses AS t2 WHERE t1.alias_id = t2.alias_id), '.'
                ) WHERE t1.alias_id <> 0 AND t1.domain_dns = '*' AND t1.protected = 'yes'
            ",
            // If a domain has only wildcard MX entries for external servers, update the domain.external_mail field to
            // 'wildcard'
            "
                UPDATE domain AS t1 SET t1.external_mail = 'wildcard' WHERE 0 = (
                    SELECT COUNT(t2.domain_dns_id) FROM domain_dns AS t2
                    WHERE t2.domain_id = t1.domain_id AND t2.alias_id = 0 AND t2.domain_dns NOT LIKE '*.%'
                ) AND t1.external_mail = 'on'
            ",
            // If a domain alias has only wildcard MX entries for external servers, update the domain.external_mail
            // field to 'wildcard'
            "
                UPDATE domain_aliasses AS t1 SET t1.external_mail = 'wildcard' WHERE t1.alias_id <> 0 AND 0 = (
                    SELECT COUNT(t2.domain_dns_id) FROM domain_dns AS t2
                    WHERE t2.alias_id = t1.alias_id AND t2.domain_dns NOT LIKE '*.%'
                ) AND t1.external_mail = 'on'
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
        $sqlQueries = array();
        $tablesToForeignKey = array(
            'email_tpls' => 'owner_id',
            'hosting_plans' => 'reseller_id',
            'reseller_props' => 'reseller_id',
            'tickets' => array('ticket_to', 'ticket_from'),
            'user_gui_props' => 'user_id',
            'web_software' => 'reseller_id'
        );

        $stmt = execute_query('SELECT admin_id FROM admin');
        $usersIds = implode(',', $stmt->fetchAll(PDO::FETCH_COLUMN));
        foreach ($tablesToForeignKey as $table => $foreignKey) {
            if (is_array($foreignKey)) {
                foreach ($foreignKey as $key) {
                    $sqlQueries[] = "DELETE FROM $table WHERE $key NOT IN ($usersIds)";
                }
            } else {
                $sqlQueries[] = "DELETE FROM $table WHERE $foreignKey NOT IN ($usersIds)";
            }
        }

        return $sqlQueries;
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
            $this->dropTable('roundcube_users')
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
        $this->dbConfig['PHPINI_ALLOW_URL_FOPEN'] = 'off';
        $this->dbConfig['PHPINI_DISPLAY_ERRORS'] = 'off';
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
        $sqlQueries = array();
        $constantToInteger = array(
            'E_ALL & ~E_NOTICE & ~E_WARNING' => '30711', // Switch to E_ALL & ~E_NOTICE
            'E_ALL & ~E_DEPRECATED' => '22527', // Production
            'E_ALL & ~E_NOTICE' => '30711', // Default
            'E_ALL | E_STRICT' => '32767' // Development
        );

        foreach ($constantToInteger as $c => $i) {
            $sqlQueries[] = "UPDATE config SET `value` = '$i' WHERE name = 'PHPINI_ERROR_REPORTING' AND `value` ='$c'";
            $sqlQueries[] = "UPDATE php_ini SET error_reporting = '$i' WHERE error_reporting = '$c'";
        }

        $this->dbConfig->forceReload();
        return $sqlQueries;
    }

    /**
     * Update for url forward fields
     *
     * @return array SQL statements to be executed
     */
    protected function r122()
    {
        return array(
            $this->changeColumn(
                'domain_aliasses',
                'url_forward',
                "url_forward VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"
            ),
            "UPDATE domain_aliasses SET url_forward = 'no' WHERE url_forward IS NULL OR url_forward = ''",
            $this->changeColumn(
                'subdomain',
                'subdomain_url_forward',
                "subdomain_url_forward VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"
            ),
            "
                UPDATE subdomain SET subdomain_url_forward = 'no'
                WHERE subdomain_url_forward IS NULL OR subdomain_url_forward = ''
            ",
            $this->changeColumn(
                'subdomain_alias',
                'subdomain_alias_url_forward',
                "subdomain_alias_url_forward VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"
            ),
            "
                UPDATE subdomain_alias SET subdomain_alias_url_forward = 'no'
                WHERE subdomain_alias_url_forward IS NULL OR subdomain_alias_url_forward = ''
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
        $stmt = execute_query("SHOW COLUMNS FROM domain LIKE 'domain_uid'");
        if ($stmt->rowCount()) {
            return "
                UPDATE admin AS t1 JOIN domain AS t2 ON(t2.domain_admin_id = t1.admin_id)
                SET t1.admin_sys_uid = t2.domain_uid, t1.admin_sys_gid = t2.domain_gid
            ";
        }

        return null;
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
        return "UPDATE ftp_users AS t1 JOIN admin AS t2 ON (t2.admin_sys_uid = t1.uid) SET t1.admin_id = t2.admin_id";
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
        if (isset($this->dbConfig['CUSTOM_ORDERPANEL_ID'])) {
            unset($this->dbConfig['CUSTOM_ORDERPANEL_ID']);
        }

        if (isset($this->dbConfig['ORDERS_EXPIRE_TIME'])) {
            unset($this->dbConfig['ORDERS_EXPIRE_TIME']);
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
        return $this->changeColumn(
            'plugin', 'plugin_status', 'plugin_status TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL'
        );
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
        $sqlQueries = array();
        $tochange = 'tochange';
        $todelete = 'todelete';

        foreach ($map as $table => $field) {
            $sqlQueries[] = "UPDATE $table SET $field = '$tochange' WHERE $field IN('change', 'dnschange')";
            $sqlQueries[] = "UPDATE $table SET $field = '$todelete' WHERE $field = 'delete'";
        }

        return $sqlQueries;
    }

    /**
     * Add plugin_plugin_error columns
     *
     * @return array SQL statements to be executed
     */
    protected function r141()
    {
        $sqlQueries = array();

        if (
            ($q = $this->addColumn(
                'plugin',
                'plugin_error',
                "TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER plugin_status"
            )) != ''
        ) {
            $sqlQueries[] = $q;
        }

        if (!empty($sqlQueries)) {
            $sqlQueries[] = "
                UPDATE plugin AS t1 JOIN plugin AS t2 ON (t2.plugin_id = t1.plugin_id)
                SET t1.plugin_status = 'toinstall', t1.plugin_error = t2.plugin_status
                WHERE t1.plugin_status NOT IN(
                    'enabled', 'disabled', 'uninstalled', 'toinstall', 'toupdate', 'touninstall', 'toenable',
                    'todisable', 'todelete'
                )
            ";
            $sqlQueries[] = "
                ALTER TABLE plugin
                CHANGE plugin_status plugin_status VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci
                NOT NULL DEFAULT 'uninstalled';
            ";
        }

        return $sqlQueries;
    }

    /**
     * Removes ports entries for unsupported services
     *
     * @return null|string
     */
    protected function r142()
    {
        if (isset($this->dbConfig['PORT_AMAVIS'])) {
            unset($this->dbConfig['PORT_AMAVIS']);
        }

        if (isset($this->dbConfig['PORT_SPAMASSASSIN'])) {
            unset($this->dbConfig['PORT_SPAMASSASSIN']);
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
        $sqlQueries = array();

        $stmt = execute_query('SELECT id, props FROM hosting_plans');
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $id = quoteValue($row['id'], PDO::PARAM_INT);
                $props = explode(';', $row['props']);
                if (count($props) == 23) {
                    $sqlQueries[] = "UPDATE hosting_plans SET props = CONCAT(props,';_no_') WHERE id = $id";
                }
            }
        }

        return $sqlQueries;
    }

    /**
     * Update sql_user.sqlu_name column
     *
     * @return string SQL statement to be executed
     */
    protected function r144()
    {
        return $this->changeColumn(
            'sql_user',
            'sqlu_name',
            "sqlu_name VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'n/a'"
        );
    }

    /**
     * Store plugins info and config as json data instead of serialized data
     *
     * @return array SQL statements to be executed
     */
    protected function r145()
    {
        $sqlQueries = array();

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

                $sqlQueries[] = "
                    UPDATE plugin SET plugin_info = $pluginInfo, plugin_config = $pluginConfig
                    WHERE plugin_id = {$row['plugin_id']}
                ";
            }
        }

        return $sqlQueries;
    }

    /**
     * Add unique key for server_ips columns
     *
     * @return array SQL statements to be executed
     */
    protected function r148()
    {
        $sqlQueries = $this->removeDuplicateRowsOnColumns('server_ips', 'ip_number');
        $sqlQueries[] = $this->addIndex('server_ips', 'ip_number', 'UNIQUE', 'ip_number');
        return $sqlQueries;
    }

    /**
     * Adds unique index for sql_user.sqld_name column
     *
     * @return array SQL statements to be executed
     */
    protected function r149()
    {
        $sqlQueries = $this->dropIndexByColumn('sql_user', 'sqlu_name');
        $sqlQueries = array_merge($sqlQueries, $this->removeDuplicateRowsOnColumns('sql_database', 'sqld_name'));
        $sqlQueries[] = $this->addIndex('sql_database', 'sqld_name', 'UNIQUE', 'sqld_name');
        return $sqlQueries;
    }

    /**
     * Update domain_dns.domain_text column to 255 characters
     *
     * @return string SQL statement to be executed
     */
    protected function r150()
    {
        return $this->changeColumn(
            'domain_dns', 'domain_text', "domain_text VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
        );
    }

    /**
     * Update domain_dns table to allow sharing between several components (core, plugins..)
     *
     * @return array SQL statements to be executed
     */
    protected function r151()
    {
        return array(
            $this->changeColumn(
                'domain_dns',
                'protected',
                "owned_by VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'custom_dns_feature'"
            ),
            "UPDATE domain_dns SET owned_by = 'custom_dns_feature' WHERE owned_by = 'no'",
            "UPDATE domain_dns SET owned_by = 'ext_mail_feature' WHERE domain_type = 'MX' AND owned_by = 'yes'"
        );
    }

    /**
     * Update domain_dns.domain_dns column to 255 characters
     *
     * @return string SQL statement to be executed
     */
    protected function r152()
    {
        return $this->changeColumn(
            'domain_dns', 'domain_dns', 'domain_dns VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
        );
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
        $sqlQueries = array();

        $stmt = execute_query('SELECT id, props FROM hosting_plans');
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $id = quoteValue($row['id'], PDO::PARAM_INT);
                $props = explode(';', $row['props']);

                if (count($props) == 24) {
                    $props[9] = (int)$props[9];
                    $quota = $props[9] * 1048576; // MiB to bytes
                    $sqlQueries[] = "UPDATE hosting_plans SET props = CONCAT(props, ';$quota') WHERE id = $id";
                }
            }
        }

        return $sqlQueries;
    }

    /**
     * Fix possible inconsistencies in hosting plan properties
     *
     * @return array|null SQL statements to be executed
     */
    protected function r157()
    {
        $sqlQueries = array();

        $stmt = execute_query(
            "
                SELECT t1.id, t1.reseller_id, t1.props,
                    IFNULL(t2.php_ini_max_post_max_size, '99999999') AS post_max_size,
                    IFNULL(t2.php_ini_max_upload_max_filesize, '99999999') AS upload_max_filesize,
                    IFNULL(t2.php_ini_max_max_execution_time, '99999999') AS max_execution_time,
                    IFNULL(t2.php_ini_max_max_input_time, '99999999') AS max_input_time,
                    IFNULL(t2.php_ini_max_memory_limit, '99999999') AS memory_limit
                FROM hosting_plans AS t1 LEFT JOIN reseller_props AS t2 ON(t2.reseller_id = t1.reseller_id)
            "
        );

        if (!$stmt->rowCount()) {
            return null;
        }

        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $id = quoteValue($row['id'], PDO::PARAM_INT);
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
                17 => array(min($row['post_max_size'], $this->dbConfig['PHPINI_POST_MAX_SIZE']), 'NUM'),
                18 => array(min($row['upload_max_filesize'], $this->dbConfig['PHPINI_UPLOAD_MAX_FILESIZE']), 'NUM'),
                19 => array(min($row['max_execution_time'], $this->dbConfig['PHPINI_MAX_EXECUTION_TIME']), 'NUM'),
                20 => array(min($row['max_input_time'], $this->dbConfig['PHPINI_MAX_INPUT_TIME']), 'NUM'),
                21 => array(min($row['memory_limit'], $this->dbConfig['PHPINI_MEMORY_LIMIT']), 'NUM'),
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
            $sqlQueries[] = 'UPDATE hosting_plans SET props = ' . quoteValue($propStr) . " WHERE id = $id";
        }

        return $sqlQueries;
    }

    /**
     * Update mail_users.quota columns
     *
     * @return string SQL statement to be executed
     */
    protected function r159()
    {
        return $this->changeColumn('mail_users', 'quota', 'quota BIGINT(20) UNSIGNED NULL DEFAULT NULL');
    }

    /**
     * Update mail_users.quota columns - Set quota field to NULL for forward only and catchall accounts
     *
     * @return string SQL statement to be executed
     */
    protected function r163()
    {
        return "
            UPDATE mail_users SET quota = NULL
            WHERE mail_type NOT RLIKE '^(normal_mail|alias_mail|subdom_mail|alssub_mail)'
        ";
    }

    /**
     * Ensure that there is at least one 1 MiB given per mailboxes
     *
     * @return array SQL statements to be executed
     */
    protected function r165()
    {
        return array(
            '
                UPDATE domain AS t1 JOIN (
                    SELECT COUNT(mail_id) AS nb_mailboxes, domain_id
                    FROM mail_users
                    WHERE quota IS NOT NULL
                    GROUP BY domain_id
                ) AS t2 USING(domain_id)
                SET t1.domain_disk_limit = t2.nb_mailboxes
                WHERE t1.domain_disk_limit <> 0 AND t1.domain_disk_limit < t2.nb_mailboxes
            ',
            '
                UPDATE domain AS t1 JOIN (
                    SELECT COUNT(mail_id) AS nb_mailboxes, domain_id
                    FROM mail_users
                    WHERE quota IS NOT NULL
                    GROUP BY domain_id
                ) AS t2 USING(domain_id)
                SET t1.mail_quota = t2.nb_mailboxes
                WHERE t1.mail_quota <> 0 AND t1.mail_quota < t2.nb_mailboxes
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
        $stmt = execute_query('SELECT domain_id, mail_quota FROM domain');
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
        if (isset($this->dbConfig['TLD_STRICT_VALIDATION'])) {
            unset($this->dbConfig['TLD_STRICT_VALIDATION']);
        }

        if (isset($this->dbConfig['SLD_STRICT_VALIDATION'])) {
            unset($this->dbConfig['SLD_STRICT_VALIDATION']);
        }

        if (isset($this->dbConfig['MAX_DNAMES_LABELS'])) {
            unset($this->dbConfig['MAX_DNAMES_LABELS']);
        }

        if (isset($this->dbConfig['MAX_SUBDNAMES_LABELS'])) {
            unset($this->dbConfig['MAX_SUBDNAMES_LABELS']);
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
        # Retrieve service ports
        $services = array_filter(
            array_keys($this->dbConfig->toArray()),
            function ($name) {
                return (strlen($name) > 5 && substr($name, 0, 5) == 'PORT_');
            }
        );

        foreach ($services as $name) {
            $values = explode(';', $this->dbConfig[$name]);
            if (count($values) > 5) { // Handle case where the update is run many time
                if ($values[5] == '') {
                    $values[5] = '0.0.0.0';
                }

                unset($values[4]); // All port are now editable - We remove custom port field
                $this->dbConfig[$name] = implode(';', $values);
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
     * @throws iMSCP_Exception_Database
     * @throws iMSCP_Update_Exception
     * @return array SQL statements to be executed
     */
    protected function r172()
    {
        if (getmyuid() === 0) {
            $sqlQueries = array(
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
                    $sqlQueries[] = "
                        UPDATE admin SET admin_sys_name = $adminSysName, admin_sys_gname = $adminSysGname
                        WHERE admin_id = {$data['admin_id']}
                    ";
                }
            }

            return $sqlQueries;
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
            $this->addIndex('sql_user', 'sqlu_name', 'INDEX', 'sqlu_name'),
            $this->addIndex('sql_user', 'sqlu_host', 'INDEX', 'sqlu_host')
        );
    }

    /**
     * Fix SQL user hosts
     *
     * @return array SQL statements to be executed
     */
    protected function r177()
    {
        $sqlQueries = array();
        $sqlUserHost = iMSCP_Registry::get('config')->DATABASE_USER_HOST;

        if ($sqlUserHost == '127.0.0.1') {
            $sqlUserHost = 'localhost';
        }

        $sqlUserHost = quoteValue($sqlUserHost);
        $stmt = execute_query('SELECT DISTINCT sqlu_name FROM sql_user');

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $sqlUser = quoteValue($row['sqlu_name']);

                $sqlQueries[] = "
                    UPDATE IGNORE mysql.user SET Host = $sqlUserHost WHERE User = $sqlUser AND Host NOT IN ($sqlUserHost, '%')
                ";

                $sqlQueries[] = "
                    UPDATE IGNORE mysql.db SET Host = $sqlUserHost WHERE User = $sqlUser AND Host NOT IN ($sqlUserHost, '%')
                ";

                $sqlQueries[] = "
                    UPDATE sql_user SET sqlu_host = $sqlUserHost
                    WHERE sqlu_name = $sqlUser AND sqlu_host NOT IN ($sqlUserHost, '%')
                ";
            }

            $sqlQueries[] = 'FLUSH PRIVILEGES';
        }

        return $sqlQueries;
    }

    /**
     * Decrypt any SSL private key
     *
     * @return array|null SQL statements to be executed
     */
    public function r178()
    {
        $sqlQueries = array();
        $stmt = execute_query('SELECT cert_id, password, `key` FROM ssl_certs');
        if (!$stmt->rowCount()) {
            return null;
        }

        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $certId = quoteValue($row['cert_id'], PDO::PARAM_INT);
            $privateKey = new Crypt_RSA();

            if ($row['password'] != '') {
                $privateKey->setPassword($row['password']);
            }

            if (!$privateKey->loadKey($row['key'], CRYPT_RSA_PRIVATE_FORMAT_PKCS1)) {
                $sqlQueries[] = "DELETE FROM ssl_certs WHERE cert_id = $certId";
                continue;
            }

            // Clear out passphrase
            $privateKey->setPassword();
            // Get unencrypted private key
            $privateKey = $privateKey->getPrivateKey();
            $privateKey = quoteValue($privateKey);
            $sqlQueries[] = "UPDATE ssl_certs SET `key` = $privateKey WHERE cert_id = $certId";
        }


        return $sqlQueries;
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
            "
                domain_type ENUM('dmn','als','sub','alssub')
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn'
            "
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
        return $this->addIndex('ssl_certs', array('domain_id', 'domain_type'), 'UNIQUE', 'domain_id_domain_type');
    }

    /**
     * SSL certificates normalization
     *
     * @return array|null SQL statements to be executed
     */
    protected function r189()
    {
        $sqlQueries = array();
        $stmt = execute_query('SELECT cert_id, private_key, certificate, ca_bundle FROM ssl_certs');

        if (!$stmt->rowCount()) {
            return null;
        }

        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $certificateId = quoteValue($row['cert_id'], PDO::PARAM_INT);
            // Data normalization
            $privateKey = quoteValue(str_replace("\r\n", "\n", trim($row['private_key'])) . PHP_EOL);
            $certificate = quoteValue(str_replace("\r\n", "\n", trim($row['certificate'])) . PHP_EOL);
            $caBundle = quoteValue(str_replace("\r\n", "\n", trim($row['ca_bundle'])));
            $sqlQueries[] = "
                UPDATE ssl_certs SET private_key = $privateKey, certificate = $certificate, ca_bundle = $caBundle
                WHERE cert_id = $certificateId
            ";
        }

        return $sqlQueries;
    }

    /**
     * Delete deprecated Web folder protection parameter
     *
     * @return null
     */
    protected function r190()
    {
        if (isset($this->dbConfig['WEB_FOLDER_PROTECTION'])) {
            unset($this->dbConfig['WEB_FOLDER_PROTECTION']);
        }

        return null;
    }

    /**
     * #1143: Add po_active column (mail_users table)
     *
     * @return null|string SQL statement to be executed
     */
    protected function r191()
    {
        return $this->addColumn(
            'mail_users', 'po_active', "VARCHAR(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes' AFTER status"
        );
    }

    /**
     * #1143: Remove any mail_users.password prefix
     *
     * @return string SQL statement to be executed
     */
    protected function r192()
    {
        return "
            UPDATE mail_users SET mail_pass = SUBSTRING(mail_pass, 4), po_active = 'no'
            WHERE mail_pass <> '_no_' AND status = 'disabled'
        ";
    }

    /**
     * #1143: Add status and po_active columns index (mail_users table)
     *
     * @return array SQL statements to be executed
     */
    protected function r193()
    {
        return array(
            $this->addIndex('mail_users', 'mail_addr', 'INDEX', 'mail_addr'),
            $this->addIndex('mail_users', 'status', 'INDEX', 'status'),
            $this->addIndex('mail_users', 'po_active', 'INDEX', 'po_active')
        );
    }

    /**
     * Added plugin_priority column in plugin table
     *
     * @return array SQL statements to be executed
     */
    protected function r194()
    {
        return array(
            $this->addColumn('plugin', 'plugin_priority', "INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER plugin_config"),
            $this->addIndex('plugin', 'plugin_priority', 'INDEX', 'plugin_priority')
        );
    }

    /**
     * Remove deprecated MAIL_WRITER_EXPIRY_TIME configuration parameter
     *
     * @return null
     */
    protected function r195()
    {
        if (isset($this->dbConfig['MAIL_WRITER_EXPIRY_TIME'])) {
            unset($this->dbConfig['MAIL_WRITER_EXPIRY_TIME']);
        }

        return null;
    }

    /**
     * Remove deprecated MAIL_BODY_FOOTPRINTS configuration parameter
     *
     * @return null
     */
    protected function r196()
    {
        if (isset($this->dbConfig['MAIL_BODY_FOOTPRINTS'])) {
            unset($this->dbConfig['MAIL_BODY_FOOTPRINTS']);
        }

        return null;
    }

    /**
     * Remove postgrey and policyd-weight ports
     *
     * @return null
     */
    protected function r198()
    {
        if (isset($this->dbConfig['PORT_POSTGREY'])) {
            unset($this->dbConfig['PORT_POSTGREY']);
        }

        if (isset($this->dbConfig['PORT_POLICYD-WEIGHT'])) {
            unset($this->dbConfig['PORT_POLICYD-WEIGHT']);
        }

        return null;
    }

    /**
     * Add domain_dns.domain_dns_status column
     *
     * @return string SQL statement to be executed
     */
    protected function r199()
    {
        return $this->addColumn(
            'domain_dns',
            'domain_dns_status',
            "VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ok'"
        );
    }

    /**
     * Add plugin.plugin_config_prev column
     *
     * @return array|null SQL statements to be executed
     */
    protected function r200()
    {
        $sql = $this->addColumn(
            'plugin',
            'plugin_config_prev',
            "VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL AFTER plugin_config"
        );

        if ($sql !== null) {
            return array($sql, 'UPDATE plugin SET plugin_config_prev = plugin_config');
        }

        return null;
    }

    /**
     * Fixed: Wrong field type for the plugin.plugin_config_prev column
     *
     * @return array SQL statements to be executed
     */
    protected function r201()
    {
        return array(
            $this->changeColumn(
                'plugin',
                'plugin_config_prev',
                'plugin_config_prev TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
            ),
            'UPDATE plugin SET plugin_config_prev = plugin_config'
        );
    }

    /**
     * Adds unique constraint for mail user entities
     *
     * @return array SQL statements to be executed
     */
    protected function r202()
    {

        $sqlQueries = $this->removeDuplicateRowsOnColumns('mail_users', 'mail_addr');
        $sqlQueries[] = $this->dropIndexByName('mail_users', 'mail_addr');
        $sqlQueries[] = $this->addIndex('mail_users', 'mail_addr', 'UNIQUE', 'mail_addr');
        return $sqlQueries;
    }

    /**
     * Change domain.allowbackup column length and update values for backup feature
     *
     * @return array SQL statements to be executed
     */
    protected function r203()
    {
        return array(
            $this->changeColumn(
                'domain',
                'allowbackup',
                "allowbackup varchar(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn|sql|mail'"
            ),
            "UPDATE domain SET allowbackup = REPLACE(allowbackup, 'full', 'dmn|sql|mail')",
            "UPDATE domain SET allowbackup = REPLACE(allowbackup, 'no', '')"
        );
    }

    /**
     * Updated hosting_plans.props values for backup feature
     *
     * @return array|null SQL statements to be executed
     */
    protected function r204()
    {
        $sqlQueries = array();
        $stmt = exec_query('SELECT id, props FROM hosting_plans');

        if (!$stmt->rowCount()) {
            return null;
        }
        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $needUpdate = true;
            $id = quoteValue($row['id'], PDO::PARAM_INT);
            $props = explode(';', $row['props']);

            switch ($props[10]) {
                case '_full_':
                    $props[10] = '_dmn_|_sql_|_mail_';
                    break;
                case '_no_':
                    $props[10] = '';
                    break;
                default:
                    $needUpdate = false;
            }

            if ($needUpdate) {
                $props = quoteValue(implode(';', $props));
                $sqlQueries[] = "UPDATE hosting_plans SET props = $props WHERE id = $id";
            }
        }

        return $sqlQueries;
    }

    /**
     * Add plugin.plugin_lock field
     *
     * @return string SQL statement to be executed
     */
    protected function r206()
    {
        return $this->addColumn('plugin', 'plugin_locked', "TINYINT UNSIGNED NOT NULL DEFAULT '0'");
    }

    /**
     * Remove index on server_traffic.traff_time column if any
     *
     * @return string SQL statement to be executed
     */
    protected function r208()
    {
        return $this->dropIndexByName('server_traffic', 'traff_time');
    }

    /**
     * Add unique constraint on server_traffic.traff_time column to avoid duplicate time periods
     *
     * @return array SQL statements to be executed
     */
    protected function r210()
    {
        $sqlQueries = $this->removeDuplicateRowsOnColumns('server_traffic', 'traff_time');
        $sqlQueries[] = $this->addIndex('server_traffic', 'traff_time', 'UNIQUE', 'traff_time');
        return $sqlQueries;
    }

    /**
     * #IP-582 PHP editor - PHP configuration levels (per_user, per_domain and per_site) are ignored
     * - Adds php_ini.admin_id and php_ini.domain_type columns
     * - Adds admin_id, domain_id and domain_type indexes
     * - Populates the php_ini.admin_id column for existent records
     *
     * @return array SQL statements to be executed
     */
    protected function r211()
    {
        return array(
            $this->addColumn('php_ini', 'admin_id', 'INT(10) NOT NULL AFTER `id`'),
            $this->addColumn(
                'php_ini',
                'domain_type',
                "VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn' AFTER `domain_id`"
            ),
            $this->addIndex('php_ini', 'admin_id', 'KEY'),
            $this->addIndex('php_ini', 'domain_id', 'KEY'),
            $this->addIndex('php_ini', 'domain_type', 'KEY'),
            "UPDATE php_ini JOIN domain USING(domain_id) SET admin_id = domain_admin_id WHERE domain_type = 'dmn'"
        );
    }

    /**
     * Makes the PHP mail function disableable
     * - Adds reseller_props.php_ini_al_mail_function permission column
     * - Adds domain.phpini_perm_mail_function permission column
     * - Adds PHP mail permission property in hosting plans if any
     *
     * @throws iMSCP_Exception
     * @return array SQL statements to be executed
     */
    protected function r212()
    {
        $sqlQueries = array();

        // Add permission column for resellers
        $sqlQueries[] = $this->addColumn(
            'reseller_props',
            'php_ini_al_mail_function',
            "VARCHAR(15) NOT NULL DEFAULT 'yes' AFTER `php_ini_al_disable_functions`"
        );
        # Add permission column for clients
        $sqlQueries[] = $this->addColumn(
            'domain',
            'phpini_perm_mail_function',
            "VARCHAR(20) NOT NULL DEFAULT 'yes' AFTER `phpini_perm_disable_functions`"
        );

        // Add PHP mail permission property in hosting plans if any
        $stmt = execute_query('SELECT id, props FROM hosting_plans');
        while ($row = $stmt->fetchRow()) {
            $id = quoteValue($row['id'], PDO::PARAM_INT);
            $props = explode(';', $row['props']);

            if (sizeof($props) < 26) {
                array_splice($props, 18, 0, 'yes'); // Insert new property at position 18
                $sqlQueries[] = 'UPDATE hosting_plans SET props = ' . quoteValue(implode(';', $props)) . 'WHERE id = ' . $id;
            }
        }

        return $sqlQueries;
    }

    /**
     * Deletes obsolete PHP editor configuration options
     * PHP configuration options defined at administrator level are no longer supported
     *
     * @return string SQL statement to be executed
     */
    protected function r213()
    {
        return "DELETE FROM config WHERE name LIKE 'PHPINI_%'";
    }

    /**
     * Update default value for the php_ini.error_reporting column
     *
     * @return string SQL statement to be executed
     */
    protected function r214()
    {
        return $this->changeColumn(
            'php_ini',
            'error_reporting',
            "
                error_reporting VARCHAR(255)
                CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E_ALL & ~E_DEPRECATED & ~E_STRICT'
            "
        );
    }

    /**
     * Deletes obsolete hosting plans
     * Hosting plans defined at administrator level are no longer supported
     *
     * @return string SQL statement to be executed
     */
    protected function r216()
    {
        return "
            DELETE FROM hosting_plans WHERE reseller_id NOT IN(SELECT admin_id FROM admin WHERE admin_type = 'reseller')
        ";
    }

    /**
     * Add status column in ftp_users table
     *
     * @return string SQL statements to be executed
     */
    protected function r217()
    {
        return $this->addColumn('ftp_users', 'status', "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'ok'");
    }

    /**
     * Add default value for the domain.external_mail_dns_ids field
     * Add default value for the domain_aliasses.external_mail_dns_ids field
     *
     * @return array SQL statements to be executed
     */
    protected function r218()
    {
        return array(
            $this->changeColumn(
                'domain',
                'external_mail_dns_ids',
                "external_mail_dns_ids VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
            ),
            $this->changeColumn(
                'domain_aliasses',
                'external_mail_dns_ids',
                "external_mail_dns_ids VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
            )
        );
    }

    /**
     * Add SPF custom DNS record type
     *
     * @return string SQL statements to be executed
     */
    protected function r219()
    {
        return $this->changeColumn(
            'domain_dns',
            'domain_type',
            "
                `domain_type` ENUM(
                    'A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS','NXT','PTR','PX','SIG',
                    'SRV','TXT','SPF'
                 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A'
            "
        );
    }

    /**
     * Drop domain_id index on domain_dns table (needed for update r221)
     *
     * @return string SQL statements to be executed
     */
    protected function r220()
    {
        return $this->dropIndexByName('domain_dns', 'domain_id');
    }

    /**
     * Change domain_dns.domain_dns and domain_dns.domain_text column types from varchar to text
     * Create domain_id index on domain_dns table (with expected index length)
     *
     * @return array SQL statements to be executed
     */
    protected function r221()
    {
        return array(
            $this->changeColumn(
                'domain_dns', 'domain_dns', "`domain_dns` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
            ),
            $this->changeColumn(
                'domain_dns', 'domain_text', "`domain_text` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
            ),
            $this->addIndex(
                'domain_dns',
                array('domain_id', 'alias_id', 'domain_dns(255)', 'domain_class', 'domain_type', 'domain_text(255)'),
                'UNIQUE'
            )
        );
    }

    /**
     * Convert FTP usernames, groups and members to IDNA form
     *
     * @throws iMSCP_Exception_Database
     * @return null
     */
    protected function r222()
    {
        $stmt = exec_query('SELECT userid FROM ftp_users');
        while ($row = $stmt->fetchRow()) {
            exec_query('UPDATE ftp_users SET userid = ? WHERE userid = ?', array(
                encode_idna($row['userid']), $row['userid']
            ));
        }

        $stmt = exec_query('SELECT groupname, members FROM ftp_group');
        while ($row = $stmt->fetchRow()) {
            $members = implode(',', array_map('encode_idna', explode(',', $row['members'])));
            exec_query('UPDATE ftp_group SET groupname = ?, members = ? WHERE groupname = ?', array(
                encode_idna($row['groupname']), $members, $row['groupname']
            ));
        }

        return null;
    }

    /**
     * Wrong value for LOG_LEVEL configuration parameter
     *
     * @return null
     */
    protected function r223()
    {
        if (isset($this->dbConfig['LOG_LEVEL']) && preg_match('/\D/', $this->dbConfig['LOG_LEVEL'])) {
            $this->dbConfig['LOG_LEVEL'] = defined($this->dbConfig['LOG_LEVEL'])
                ? constant($this->dbConfig['LOG_LEVEL']) : E_USER_ERROR;
        }

        return null;
    }

    /**
     * Add column for HSTS feature
     *
     * @return null|string SQL statement to be executed
     */
    protected function r224()
    {
        return $this->addColumn(
            'ssl_certs',
            'allow_hsts',
            "VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off' AFTER ca_bundle"
        );
    }

    /**
     * Add columns for forward type feature
     *
     * @return array SQL statements to be executed
     */
    protected function r225()
    {
        $sqlQueries = array();

        $sql = $this->addColumn(
            'domain_aliasses',
            'type_forward',
            "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER url_forward"
        );

        if ($sql !== null) {
            $sqlQueries[] = $sql;
            $sqlQueries[] = "UPDATE domain_aliasses SET type_forward = '302' WHERE url_forward <> 'no'";
        }

        $sql = $this->addColumn(
            'subdomain',
            'subdomain_type_forward',
            "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER subdomain_url_forward"
        );

        if ($sql !== null) {
            $sqlQueries[] = $sql;
            $sqlQueries[] = "UPDATE subdomain SET subdomain_type_forward = '302' WHERE subdomain_url_forward <> 'no'";
        }

        $sql = $this->addColumn(
            'subdomain_alias',
            'subdomain_alias_type_forward',
            "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER subdomain_alias_url_forward"
        );

        if ($sql !== null) {
            $sqlQueries[] = $sql;
            $sqlQueries[] = "UPDATE subdomain_alias SET subdomain_alias_type_forward = '302' WHERE subdomain_alias_url_forward <> 'no'";
        }

        return $sqlQueries;
    }

    /**
     * #IP-1395: Domain redirect feature - Missing URL path separator
     *
     * @throws Zend_Uri_Exception
     * @throws iMSCP_Exception_Database
     * @throws iMSCP_Uri_Exception
     */
    protected function r226()
    {
        $stmt = exec_query("SELECT alias_id, url_forward FROM domain_aliasses WHERE url_forward <> 'no'");

        while ($row = $stmt->fetchRow()) {
            $uri = iMSCP_Uri_Redirect::fromString($row['url_forward']);
            $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/';
            $uri->setPath($uriPath);
            exec_query(
                'UPDATE domain_aliasses SET url_forward = ? WHERE alias_id = ?', array($uri->getUri(), $row['alias_id'])
            );
        }

        $stmt = exec_query(
            "SELECT subdomain_id, subdomain_url_forward FROM subdomain WHERE subdomain_url_forward <> 'no'"
        );

        while ($row = $stmt->fetchRow()) {
            $uri = iMSCP_Uri_Redirect::fromString($row['subdomain_url_forward']);
            $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/';
            $uri->setPath($uriPath);
            exec_query('UPDATE subdomain SET subdomain_url_forward = ? WHERE subdomain_id = ?', array(
                $uri->getUri(), $row['subdomain_id']
            ));
        }

        $stmt = exec_query(
            "
                SELECT subdomain_alias_id, subdomain_alias_url_forward FROM subdomain_alias
                WHERE subdomain_alias_url_forward <> 'no'
            "
        );
        while ($row = $stmt->fetchRow()) {
            $uri = iMSCP_Uri_Redirect::fromString($row['subdomain_alias_url_forward']);
            $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/';
            $uri->setPath($uriPath);
            exec_query('UPDATE subdomain_alias SET subdomain_alias_url_forward = ? WHERE subdomain_alias_id = ?', array(
                $uri->getUri(), $row['subdomain_alias_id']
            ));
        }
    }

    /**
     * Add column for HSTS options
     *
     * @return array SQL statements to be executed
     */
    protected function r227()
    {
        return array(
            $this->addColumn(
                'ssl_certs',
                'hsts_max_age',
                "int(11) NOT NULL DEFAULT '31536000' AFTER allow_hsts"
            ),
            $this->addColumn(
                'ssl_certs',
                'hsts_include_subdomains',
                "VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off' AFTER hsts_max_age"
            )
        );
    }

    /**
     * Reset all mail templates according changes made in 1.3.0
     *
     * @return string SQL statement to be executed
     */
    protected function r228()
    {
        return 'TRUNCATE email_tpls';
    }

    /**
     * Add index for mail_users.sub_id column
     *
     * @return string SQL statement to be executed
     */
    protected function r229()
    {
        return $this->addIndex('mail_users', 'sub_id', 'INDEX');
    }

    /**
     * Ext. mail feature - Remove deprecated columns and reset values
     *
     * @return array SQL statements to be executed
     */
    protected function r230()
    {
        return $sqlQueries = array(
            $this->dropColumn('domain', 'external_mail_dns_ids'),
            $this->dropColumn('domain_aliasses', 'external_mail_dns_ids'),
            "DELETE FROM domain_dns WHERE owned_by = 'ext_mail_feature'",
            "UPDATE domain_aliasses SET external_mail = 'off'",
            "UPDATE domain SET external_mail = 'off'"
        );
    }

    /**
     * #IP-1581 Allow to disable auto-configuration of network interfaces
     * - Add server_ips.ip_config_mode column
     *
     * @return null|string SQL statement to be executed
     */
    protected function r231()
    {
        return $this->addColumn(
            'server_ips',
            'ip_config_mode',
            "VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT 'auto' AFTER ip_card"
        );
    }

    /**
     * Set configuration mode to `manual' for the server's primary IP
     *
     * @return string SQL statement to be executed
     */
    protected function r232()
    {
        $primaryIP = quoteValue(iMSCP_Registry::get('config')->BASE_SERVER_IP);
        return "UPDATE server_ips SET ip_config_mode = 'manual' WHERE ip_number = $primaryIP";
    }

    /**
     * Creates missing entries in the php_ini table (one for each domain)
     *
     * @throws iMSCP_Exception
     * @throws iMSCP_Exception_Database
     * @return null
     */
    protected function r233()
    {
        $phpini = iMSCP_PHPini::getInstance();

        // For each reseller
        $resellers = execute_query("SELECT admin_id FROM admin WHERE admin_type = 'reseller'");
        while ($reseller = $resellers->fetchRow()) {
            $phpini->loadResellerPermissions($reseller['admin_id']);

            // For each client of the reseller
            $clients = exec_query("SELECT admin_id FROM admin WHERE created_by = {$reseller['admin_id']}");
            while ($client = $clients->fetchRow()) {
                $phpini->loadClientPermissions($client['admin_id']);

                $domain = exec_query(
                    "SELECT domain_id FROM domain WHERE domain_admin_id = ? AND domain_status <> ?",
                    array($client['admin_id'], 'todelete')
                );

                if (!$domain->rowCount()) {
                    continue;
                }

                $domain = $domain->fetchRow();
                $phpini->loadDomainIni($client['admin_id'], $domain['domain_id'], 'dmn');
                if ($phpini->isDefaultDomainIni()) {
                    $phpini->saveDomainIni($client['admin_id'], $domain['domain_id'], 'dmn');
                }

                $subdomains = exec_query(
                    'SELECT subdomain_id FROM subdomain WHERE domain_id = ? AND subdomain_status <> ?',
                    array($domain['domain_id'], 'todelete')
                );
                while ($subdomain = $subdomains->fetchRow()) {
                    $phpini->loadDomainIni($client['admin_id'], $subdomain['subdomain_id'], 'sub');
                    if ($phpini->isDefaultDomainIni()) {
                        $phpini->saveDomainIni($client['admin_id'], $subdomain['subdomain_id'], 'sub');
                    }
                }
                unset($subdomains);

                $domainAliases = exec_query(
                    'SELECT alias_id FROM domain_aliasses WHERE domain_id = ? AND alias_status <> ?',
                    array($domain['domain_id'], 'todelete')
                );
                while ($domainAlias = $domainAliases->fetchRow()) {
                    $phpini->loadDomainIni($client['admin_id'], $domainAlias['alias_id'], 'als');
                    if ($phpini->isDefaultDomainIni()) {
                        $phpini->saveDomainIni($client['admin_id'], $domainAlias['alias_id'], 'als');
                    }
                }
                unset($domainAliases);

                $subdomainAliases = exec_query(
                    '
                        SELECT subdomain_alias_id FROM subdomain_alias INNER JOIN domain_aliasses USING(alias_id)
                        WHERE domain_id = ? AND subdomain_alias_status <> ?
                    ',
                    array($domain['domain_id'], 'todelete')
                );
                while ($subdomainAlias = $subdomainAliases->fetchRow()) {
                    $phpini->loadDomainIni($client['admin_id'], $subdomainAlias['subdomain_alias_id'], 'subals');
                    if ($phpini->isDefaultDomainIni()) {
                        $phpini->saveDomainIni($client['admin_id'], $subdomainAlias['subdomain_alias_id'], 'subals');
                    }
                }
                unset($subdomainAliases);
            }
        }

        return null;
    }

    /**
     * Adds compound unique key on the php_ini table
     *
     * @return array SQL statement to be executed
     */
    protected function r234()
    {
        $sqlQueries = $this->removeDuplicateRowsOnColumns('php_ini', array('admin_id', 'domain_id', 'domain_type'));
        $sqlQueries = array_merge($sqlQueries, $this->dropIndexByColumn('php_ini', 'admin_id'));
        $sqlQueries = array_merge($sqlQueries, $this->dropIndexByColumn('php_ini', 'domain_id'));
        $sqlQueries = array_merge($sqlQueries, $this->dropIndexByColumn('php_ini', 'domain_type'));
        $sqlQueries[] = $this->addIndex('php_ini', array('admin_id', 'domain_id', 'domain_type'), 'UNIQUE', 'unique_php_ini');
        return $sqlQueries;
    }

    /**
     * #IP-1429 Make main domains forwardable
     * - Add domain.url_forward, domain.type_forward and domain.host_forward columns
     * - Add domain_aliasses.host_forward column
     * - Add subdomain.subdomain_host_forward column
     * - Add subdomain_alias.subdomain_alias_host_forward column
     *
     * @return array SQL statements to be executed
     */
    protected function r235()
    {
        return array(
            $this->addColumn('domain', 'url_forward', "VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"),
            $this->addColumn('domain', 'type_forward', "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL"),
            $this->addColumn('domain', 'host_forward', "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off'"),
            $this->addColumn(
                'domain_aliasses',
                'host_forward',
                "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off' AFTER type_forward"
            ),
            $this->addColumn(
                'subdomain',
                'subdomain_host_forward',
                "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off' AFTER subdomain_type_forward"
            ),
            $this->addColumn(
                'subdomain_alias',
                'subdomain_alias_host_forward',
                "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off' AFTER subdomain_alias_type_forward"
            ),
        );
    }

    /**
     * Remove support for ftp URL redirects
     *
     * @return array SQL statements to be executed
     */
    protected function r236()
    {
        return array(
            "UPDATE domain_aliasses SET url_forward = 'no', type_forward = NULL WHERE url_forward LIKE 'ftp://%'",
            "
                UPDATE subdomain SET subdomain_url_forward = 'no', subdomain_type_forward = NULL
                WHERE subdomain_url_forward LIKE 'ftp://%'
            ",
            "
                UPDATE subdomain_alias SET subdomain_alias_url_forward = 'no', subdomain_alias_type_forward = NULL
                WHERE subdomain_alias_url_forward LIKE 'ftp://%'
            "
        );
    }

    /**
     * #IP-1587 Slow query on domain_traffic table when admin or reseller want to login into customer's area
     * - Add compound unique index on the domain_traffic table to avoid slow query and duplicate entries
     *
     * @return array SQL statements to be executed
     */
    protected function r237()
    {
        $sqlQueries = $this->removeDuplicateRowsOnColumns('domain_traffic', array('domain_id', 'dtraff_time'));
        $sqlQueries[] = $this->addIndex('domain_traffic', array('domain_id', 'dtraff_time'), 'UNIQUE', 'i_unique_timestamp');
        return $sqlQueries;
    }

    /**
     * Update domain_traffic table schema
     * - Disallow NULL value on domain_id and dtraff_time columns
     * - Change default value for dtraff_web, dtraff_ftp, dtraff_mail domain_traffic columns (NULL to 0)
     *
     * @return string SQL statement to be executed
     */
    protected function r238()
    {
        return "
          ALTER TABLE `domain_traffic`
            CHANGE `domain_id` `domain_id` INT(10) UNSIGNED NOT NULL,
            CHANGE `dtraff_time` `dtraff_time` BIGINT(20) UNSIGNED NOT NULL,
            CHANGE `dtraff_web` `dtraff_web` BIGINT(20) UNSIGNED NULL DEFAULT '0',
            CHANGE `dtraff_ftp` `dtraff_ftp` BIGINT(20) UNSIGNED NULL DEFAULT '0',
            CHANGE `dtraff_mail` `dtraff_mail` BIGINT(20) UNSIGNED NULL DEFAULT '0',
            CHANGE `dtraff_pop` `dtraff_pop` BIGINT(20) UNSIGNED NULL DEFAULT '0'
        ";
    }

    /**
     * Drop monthly_domain_traffic view which was added in update r238 and removed later on
     *
     * @return string SQL statement to be executed
     */
    protected function r239()
    {
        return 'DROP VIEW IF EXISTS monthly_domain_traffic';
    }

    /**
     * Add missing primary key on httpd_vlogger table
     *
     * @return array SQL statements to be executed;
     */
    protected function r240()
    {
        if (!$this->isKnownTable('httpd_vlogger')) {
            return null;
        }

        $sqlQueries = $this->removeDuplicateRowsOnColumns('httpd_vlogger', array('vhost', 'ldate'));
        $sqlQueries[] = $this->addIndex('httpd_vlogger', array('vhost', 'ldate'));
        return $sqlQueries;
    }

    /**
     * Delete deprecated `statistics` group for AWStats
     *
     * @return string SQL statement to be executed
     */
    protected function r241()
    {
        return "DELETE FROM htaccess_groups WHERE ugroup = 'statistics'";
    }
}
