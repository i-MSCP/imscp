<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP team
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

use iMSCP\Crypt as Crypt;
use iMSCP_Config_Handler_Db as ConfigDb;
use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_PHPini as PhpIni;
use iMSCP_Registry as Registry;
use iMSCP_Update_Exception as UpdateException;
use iMSCP_Uri_Redirect as UriRedirect;

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
     * @var ConfigFile
     */
    protected $config;

    /**
     * @var ConfigDb
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
    protected $lastUpdate = 273;

    /**
     * Singleton - Make new unavailable
     *
     * @throws Zend_Exception
     * @throws iMSCP_Update_Exception
     */
    protected function __construct()
    {
        $this->config = Registry::get('config');
        $this->dbConfig = Registry::get('dbConfig');

        if (!isset($this->config['DATABASE_NAME'])) {
            throw new UpdateException('Database name not found.');
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
     */
    public static function getInstance()
    {
        if (NULL === self::$instance) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
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
     * @throws Zend_Exception
     * @throws iMSCP_Exception
     * @throws iMSCP_Exception_Database
     */
    public function applyUpdates()
    {
        ignore_user_abort(true);
        $db = iMSCP_Database::getInstance();

        while ($this->isAvailableUpdate()) {
            $revision = $this->getNextUpdate();

            try {
                $updateMethod = 'r' . $revision;
                $queries = (array)$this->$updateMethod();

                if (empty($queries)) {
                    $this->dbConfig['DATABASE_REVISION'] = $revision;
                    continue;
                }

                $db->beginTransaction();

                foreach ($queries as $query) {
                    if (empty($query)) {
                        continue;
                    }

                    $stmt = $db->prepare($query);
                    $db->execute($stmt);
                    /** @noinspection PhpStatementHasEmptyBodyInspection */
                    while ($stmt->nextRowset()) {
                        /* https://bugs.php.net/bug.php?id=61613 */
                    };

                }

                $this->dbConfig['DATABASE_REVISION'] = $revision;

                # Make sure that we are still in transaction due to possible implicite commit
                # See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html
                if ($db->inTransaction()) {
                    $db->commit();
                }
            } catch (Exception $e) {
                # Make sure that we are still in transaction due to possible implicite commit
                # See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html
                if ($db->inTransaction()) {
                    $db->rollBack();
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
     * Optimize all tables inside database
     *
     * return void
     */
    public function optimizeTables()
    {
        try {
            $stmt = execute_query('SHOW TABLES');
            while ($table = $stmt->fetchRow(PDO::FETCH_COLUMN)) {
                execute_query(sprintf('OPTIMIZE LOCAL TABLE %s', quoteIdentifier($table)));
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Does the given table is known?
     *
     * @param string $table Table name
     * @return bool TRUE if the given table is know, FALSE otherwise
     */
    protected function isTable($table)
    {
        try {
            return (bool)exec_query('SHOW TABLES LIKE ?', $table)->rowCount();
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Rename a table
     *
     * @param string $oTable Old table name
     * @param string $nTable New table name
     * @return string SQL statement to be executed
     */
    protected function renameTable($oTable, $nTable)
    {
        try {
            return sprintf('ALTER TABLE %s RENAME TO %s', quoteIdentifier($oTable), quoteIdentifier($nTable));
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Drop a table
     *
     * @param string $table Table name
     * @return string SQL statement to be executed
     */
    protected function dropTable($table)
    {
        try {
            return sprintf('DROP TABLE IF EXISTS %s', quoteIdentifier($table));
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
        try {
            $table = quoteIdentifier($table);
            $stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", $column);

            if (!$stmt->rowCount()) {
                return sprintf('ALTER TABLE %s ADD %s %s', $table, quoteIdentifier($column), $columnDefinition);
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
        try {
            $table = quoteIdentifier($table);
            $stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", $column);

            if ($stmt->rowCount()) {
                return sprintf('ALTER TABLE %s CHANGE %s %s', $table, quoteIdentifier($column), $columnDefinition);
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
        try {
            $table = quoteIdentifier($table);
            $stmt = exec_query("SHOW COLUMNS FROM $table LIKE ?", $column);

            if ($stmt->rowCount()) {
                return sprintf('ALTER TABLE %s DROP %s', $table, quoteIdentifier($column));
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Add index
     *
     * Be aware that no check is made for duplicate rows. Thus, if you want to
     * add an UNIQUE constraint, you must make sure to remove duplicate rows
     * first. We don't make use of the IGNORE clause for the following reasons:
     *
     * - The IGNORE clause is no standard and do not work with Fast Index Creation (MySQL Bug #40344)
     * - The IGNORE clause has been removed in MySQL 5.7
     *
     * @param string $table Database table name
     * @param array|string $columns Column name(s) with OPTIONAL key length
     * @param string $indexType Index type (PRIMARY KEY (default), INDEX|KEY, UNIQUE)
     * @param string $indexName Index name (default is autogenerated)
     * @return null|string SQL statement to be executed
     */
    protected function addIndex($table, $columns, $indexType = 'PRIMARY KEY', $indexName = '')
    {
        try {
            $table = quoteIdentifier($table);
            $indexType = strtoupper($indexType);
            $columnsTmp = (array)$columns;
            $columns = [];

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

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
        try {
            $sqlStatements = [];
            $table = quoteIdentifier($table);
            $stmt = exec_query("SHOW INDEX FROM $table WHERE COLUMN_NAME = ?", $column);

            if ($stmt->rowCount()) {
                while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                    $row = array_change_key_case($row, CASE_UPPER);
                    $sqlStatements[] = sprintf('ALTER TABLE %s DROP INDEX %s', $table, quoteIdentifier($row['KEY_NAME']));
                }
            }

            return $sqlStatements;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
        try {
            $table = quoteIdentifier($table);
            $stmt = exec_query("SHOW INDEX FROM $table WHERE KEY_NAME = ?", $indexName);

            if ($stmt->rowCount()) {
                return sprintf('ALTER TABLE %s DROP INDEX %s', $table, quoteIdentifier($indexName));
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Catch any database updates that were removed
     *
     * @throws UpdateException
     * @param  string $updateMethod Database update method name
     * @param array $params Params
     * @return null
     */
    public function __call($updateMethod, $params)
    {
        if (!preg_match('/^r[0-9]+$/', $updateMethod)) {
            throw new UpdateException(sprintf('%s is not a valid database update method', $updateMethod));
        }

        return NULL;
    }

    /**
     * Please, add all the database update methods below. Don't forget to update the `lastUpdate' field above.
     */

    /**
     * Prohibit upgrade from i-MSCP versions older than 1.1.x
     *
     * @throws iMSCP_Exception
     */
    protected function r173()
    {
        throw new iMSCP_Exception('Upgrade support for i-MSCP versions older than 1.1.0 has been removed. You must first upgrade to i-MSCP version 1.3.8.');
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
        return [
            // sql_database table update
            $this->changeColumn('sql_database', 'domain_id', 'domain_id INT(10) UNSIGNED NOT NULL'),
            $this->changeColumn('sql_database', 'sqld_name', 'sqld_name VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'),
            // sql_user table update
            $this->changeColumn('sql_user', 'sqld_id', 'sqld_id INT(10) UNSIGNED NOT NULL'),
            $this->changeColumn('sql_user', 'sqlu_name', 'sqlu_name VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'),
            $this->changeColumn('sql_user', 'sqlu_pass', 'sqlu_pass VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'),
            $this->addColumn('sql_user', 'sqlu_host', 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER sqlu_name'),
            $this->addIndex('sql_user', 'sqlu_name', 'INDEX', 'sqlu_name'),
            $this->addIndex('sql_user', 'sqlu_host', 'INDEX', 'sqlu_host')
        ];
    }

    /**
     * Fix SQL user hosts
     *
     * @return array SQL statements to be executed
     */
    protected function r177()
    {
        try {
            $sqlStatements = [];
            $sqlUserHost = Registry::get('config')['DATABASE_USER_HOST'];

            if ($sqlUserHost == '127.0.0.1') {
                $sqlUserHost = 'localhost';
            }

            $sqlUserHost = quoteValue($sqlUserHost);
            $stmt = execute_query('SELECT DISTINCT sqlu_name FROM sql_user');

            if ($stmt->rowCount()) {
                while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                    $sqlUser = quoteValue($row['sqlu_name']);
                    $sqlStatements[] = "UPDATE IGNORE mysql.user SET Host = $sqlUserHost WHERE User = $sqlUser AND Host NOT IN ($sqlUserHost, '%')";
                    $sqlStatements[] = "UPDATE IGNORE mysql.db SET Host = $sqlUserHost WHERE User = $sqlUser AND Host NOT IN ($sqlUserHost, '%')";
                    $sqlStatements[] = "UPDATE sql_user SET sqlu_host = $sqlUserHost WHERE sqlu_name = $sqlUser AND sqlu_host NOT IN ($sqlUserHost, '%')";
                }

                $sqlStatements[] = 'FLUSH PRIVILEGES';
            }
            return $sqlStatements;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Decrypt any SSL private key
     *
     * @return array|null SQL statements to be executed
     */
    public function r178()
    {
        try {
            $sqlStatements = [];
            $stmt = execute_query('SELECT cert_id, password, `key` FROM ssl_certs');

            if (!$stmt->rowCount()) {
                return NULL;
            }

            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $certId = quoteValue($row['cert_id'], PDO::PARAM_INT);
                $privateKey = new Crypt_RSA();

                if ($row['password'] != '') {
                    $privateKey->setPassword($row['password']);
                }

                if (!$privateKey->loadKey($row['key'], CRYPT_RSA_PRIVATE_FORMAT_PKCS1)) {
                    $sqlStatements[] = "DELETE FROM ssl_certs WHERE cert_id = $certId";
                    continue;
                }

                // Clear out passphrase
                $privateKey->setPassword();
                // Get unencrypted private key
                $privateKey = $privateKey->getPrivateKey();
                $privateKey = quoteValue($privateKey);
                $sqlStatements[] = "UPDATE ssl_certs SET `key` = $privateKey WHERE cert_id = $certId";
            }

            return $sqlStatements;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
            'ssl_certs', 'type', "domain_type ENUM('dmn','als','sub','alssub') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn'"
        );
    }

    /**
     * Rename ssl_certs.key column to ssl_certs.private_key
     *
     * @return null|string SQL statement to be executed
     */
    protected function r182()
    {
        return $this->changeColumn('ssl_certs', 'key', 'private_key TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL');
    }

    /**
     * Rename ssl_certs.cert column to ssl_certs.certificate
     *
     * @return null|string SQL statement to be executed
     */
    protected function r183()
    {
        return $this->changeColumn('ssl_certs', 'cert', 'certificate TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL');
    }

    /**
     * Rename ssl_certs.ca_cert column to ssl_certs.ca_bundle
     *
     * @return null|string SQL statement to be executed
     */
    protected function r184()
    {
        return $this->changeColumn('ssl_certs', 'ca_cert', 'ca_bundle TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL');
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
        return $this->addIndex('ssl_certs', ['domain_id', 'domain_type'], 'UNIQUE', 'domain_id_domain_type');
    }

    /**
     * SSL certificates normalization
     *
     * @return array|null SQL statements to be executed
     */
    protected function r189()
    {
        try {
            $sqlStatements = [];
            $stmt = execute_query('SELECT cert_id, private_key, certificate, ca_bundle FROM ssl_certs');

            if (!$stmt->rowCount()) {
                return NULL;
            }

            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $certificateId = quoteValue($row['cert_id'], PDO::PARAM_INT);
                // Data normalization
                $privateKey = quoteValue(str_replace("\r\n", "\n", trim($row['private_key'])) . PHP_EOL);
                $certificate = quoteValue(str_replace("\r\n", "\n", trim($row['certificate'])) . PHP_EOL);
                $caBundle = quoteValue(str_replace("\r\n", "\n", trim($row['ca_bundle'])));
                $sqlStatements[] = "
                    UPDATE ssl_certs SET private_key = $privateKey, certificate = $certificate, ca_bundle = $caBundle
                    WHERE cert_id = $certificateId
                ";
            }

            return $sqlStatements;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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

        return NULL;
    }

    /**
     * #1143: Add po_active column (mail_users table)
     *
     * @return null|string SQL statement to be executed
     */
    protected function r191()
    {
        return $this->addColumn('mail_users', 'po_active', "VARCHAR(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes' AFTER status");
    }

    /**
     * #1143: Remove any mail_users.password prefix
     *
     * @return string SQL statement to be executed
     */
    protected function r192()
    {
        return "UPDATE mail_users SET mail_pass = SUBSTRING(mail_pass, 4), po_active = 'no' WHERE mail_pass <> '_no_' AND status = 'disabled'";
    }

    /**
     * #1143: Add status and po_active columns index (mail_users table)
     *
     * @return array SQL statements to be executed
     */
    protected function r193()
    {
        return [
            $this->addIndex('mail_users', 'mail_addr', 'INDEX', 'mail_addr'),
            $this->addIndex('mail_users', 'status', 'INDEX', 'status'),
            $this->addIndex('mail_users', 'po_active', 'INDEX', 'po_active')
        ];
    }

    /**
     * Added plugin_priority column in plugin table
     *
     * @return array SQL statements to be executed
     */
    protected function r194()
    {
        return [
            $this->addColumn('plugin', 'plugin_priority', "INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER plugin_config"),
            $this->addIndex('plugin', 'plugin_priority', 'INDEX', 'plugin_priority')
        ];
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

        return NULL;
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

        return NULL;
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

        return NULL;
    }

    /**
     * Add domain_dns.domain_dns_status column
     *
     * @return string SQL statement to be executed
     */
    protected function r199()
    {
        return $this->addColumn('domain_dns', 'domain_dns_status', "VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ok'");
    }

    /**
     * Add plugin.plugin_config_prev column
     *
     * @return array|null SQL statements to be executed
     */
    protected function r200()
    {
        $sql = $this->addColumn(
            'plugin', 'plugin_config_prev', "VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL AFTER plugin_config"
        );

        if ($sql !== NULL) {
            return [$sql, 'UPDATE plugin SET plugin_config_prev = plugin_config'];
        }

        return NULL;
    }

    /**
     * Fixed: Wrong field type for the plugin.plugin_config_prev column
     *
     * @return array SQL statements to be executed
     */
    protected function r201()
    {
        return [
            $this->changeColumn(
                'plugin', 'plugin_config_prev', 'plugin_config_prev TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
            ),
            'UPDATE plugin SET plugin_config_prev = plugin_config'
        ];
    }

    /**
     * Change domain.allowbackup column length and update values for backup feature
     *
     * @return array SQL statements to be executed
     */
    protected function r203()
    {
        return [
            $this->changeColumn('domain', 'allowbackup', "allowbackup varchar(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn|sql|mail'"),
            "UPDATE domain SET allowbackup = REPLACE(allowbackup, 'full', 'dmn|sql|mail')",
            "UPDATE domain SET allowbackup = REPLACE(allowbackup, 'no', '')"
        ];
    }

    /**
     * Updated hosting_plans.props values for backup feature
     *
     * @return array|null SQL statements to be executed
     */
    protected function r204()
    {
        try {
            $sqlStatements = [];
            $stmt = exec_query('SELECT id, props FROM hosting_plans');

            if (!$stmt->rowCount()) {
                return NULL;
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
                    $sqlStatements[] = "UPDATE hosting_plans SET props = $props WHERE id = $id";
                }
            }

            return $sqlStatements;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
     * #IP-582 PHP editor - PHP configuration levels (per_user, per_domain and per_site) are ignored
     * - Adds php_ini.admin_id and php_ini.domain_type columns
     * - Adds admin_id, domain_id and domain_type indexes
     * - Populates the php_ini.admin_id column for existent records
     *
     * @return array SQL statements to be executed
     */
    protected function r211()
    {
        return [
            $this->addColumn('php_ini', 'admin_id', 'INT(10) NOT NULL AFTER `id`'),
            $this->addColumn(
                'php_ini', 'domain_type', "VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn' AFTER `domain_id`"
            ),
            $this->addIndex('php_ini', 'admin_id', 'KEY'),
            $this->addIndex('php_ini', 'domain_id', 'KEY'),
            $this->addIndex('php_ini', 'domain_type', 'KEY'),
            "UPDATE php_ini JOIN domain USING(domain_id) SET admin_id = domain_admin_id WHERE domain_type = 'dmn'"
        ];
    }

    /**
     * Makes the PHP mail function disableable
     * - Adds reseller_props.php_ini_al_mail_function permission column
     * - Adds domain.phpini_perm_mail_function permission column
     * - Adds PHP mail permission property in hosting plans if any
     *
     * @return array SQL statements to be executed
     */
    protected function r212()
    {
        try {
            $sqlStatements = [];

            // Add permission column for resellers
            $sqlStatements[] = $this->addColumn(
                'reseller_props', 'php_ini_al_mail_function', "VARCHAR(15) NOT NULL DEFAULT 'yes' AFTER `php_ini_al_disable_functions`"
            );
            # Add permission column for clients
            $sqlStatements[] = $this->addColumn(
                'domain', 'phpini_perm_mail_function', "VARCHAR(20) NOT NULL DEFAULT 'yes' AFTER `phpini_perm_disable_functions`"
            );

            // Add PHP mail permission property in hosting plans if any
            $stmt = execute_query('SELECT id, props FROM hosting_plans');
            while ($row = $stmt->fetchRow()) {
                $id = quoteValue($row['id'], PDO::PARAM_INT);
                $props = explode(';', $row['props']);

                if (sizeof($props) < 26) {
                    array_splice($props, 18, 0, 'yes'); // Insert new property at position 18
                    $sqlStatements[] = 'UPDATE hosting_plans SET props = ' . quoteValue(implode(';', $props)) . 'WHERE id = ' . $id;
                }
            }

            return $sqlStatements;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
            "error_reporting VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E_ALL & ~E_DEPRECATED & ~E_STRICT'"
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
        return "DELETE FROM hosting_plans WHERE reseller_id NOT IN(SELECT admin_id FROM admin WHERE admin_type = 'reseller')";
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
        return [
            $this->changeColumn(
                'domain', 'external_mail_dns_ids', "external_mail_dns_ids VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
            ),
            $this->changeColumn(
                'domain_aliasses',
                'external_mail_dns_ids',
                "external_mail_dns_ids VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
            )
        ];
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
                    'A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX','NAPTR','NSAP','NS','NXT','PTR','PX','SIG','SRV','TXT','SPF'
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
        return [
            $this->changeColumn('domain_dns', 'domain_dns', "`domain_dns` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"),
            $this->changeColumn('domain_dns', 'domain_text', "`domain_text` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"),
            $this->addIndex('domain_dns', ['domain_id', 'alias_id', 'domain_dns(255)', 'domain_class', 'domain_type', 'domain_text(255)'], 'UNIQUE')
        ];
    }

    /**
     * Convert FTP usernames, groups and members to IDNA form
     *
     * @return null
     */
    protected function r222()
    {
        try {
            $stmt = exec_query('SELECT userid FROM ftp_users');
            while ($row = $stmt->fetchRow()) {
                exec_query('UPDATE ftp_users SET userid = ? WHERE userid = ?', [encode_idna($row['userid']), $row['userid']]);
            }

            $stmt = exec_query('SELECT groupname, members FROM ftp_group');
            while ($row = $stmt->fetchRow()) {
                $members = implode(',', array_map('encode_idna', explode(',', $row['members'])));
                exec_query('UPDATE ftp_group SET groupname = ?, members = ? WHERE groupname = ?', [
                    encode_idna($row['groupname']), $members, $row['groupname']
                ]);
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Wrong value for LOG_LEVEL configuration parameter
     *
     * @return null
     */
    protected function r223()
    {
        if (isset($this->dbConfig['LOG_LEVEL']) && preg_match('/\D/', $this->dbConfig['LOG_LEVEL'])) {
            $this->dbConfig['LOG_LEVEL'] = defined($this->dbConfig['LOG_LEVEL']) ? constant($this->dbConfig['LOG_LEVEL']) : E_USER_ERROR;
        }

        return NULL;
    }

    /**
     * Add column for HSTS feature
     *
     * @return null|string SQL statement to be executed
     */
    protected function r224()
    {
        return $this->addColumn('ssl_certs', 'allow_hsts', "VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off' AFTER ca_bundle");
    }

    /**
     * Add columns for forward type feature
     *
     * @return array SQL statements to be executed
     */
    protected function r225()
    {
        $sqlStatements = [];

        $sql = $this->addColumn('domain_aliasses', 'type_forward', "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER url_forward");

        if ($sql !== NULL) {
            $sqlStatements[] = $sql;
            $sqlStatements[] = "UPDATE domain_aliasses SET type_forward = '302' WHERE url_forward <> 'no'";
        }

        $sql = $this->addColumn('subdomain', 'subdomain_type_forward', "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER subdomain_url_forward");

        if ($sql !== NULL) {
            $sqlStatements[] = $sql;
            $sqlStatements[] = "UPDATE subdomain SET subdomain_type_forward = '302' WHERE subdomain_url_forward <> 'no'";
        }

        $sql = $this->addColumn(
            'subdomain_alias', 'subdomain_alias_type_forward', "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER subdomain_alias_url_forward"
        );

        if ($sql !== NULL) {
            $sqlStatements[] = $sql;
            $sqlStatements[] = "UPDATE subdomain_alias SET subdomain_alias_type_forward = '302' WHERE subdomain_alias_url_forward <> 'no'";
        }

        return $sqlStatements;
    }

    /**
     * #IP-1395: Domain redirect feature - Missing URL path separator
     *
     * @return null
     */
    protected function r226()
    {
        try {
            $stmt = exec_query("SELECT alias_id, url_forward FROM domain_aliasses WHERE url_forward <> 'no'");

            while ($row = $stmt->fetchRow()) {
                $uri = UriRedirect::fromString($row['url_forward']);
                $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/';
                $uri->setPath($uriPath);
                exec_query('UPDATE domain_aliasses SET url_forward = ? WHERE alias_id = ?', [$uri->getUri(), $row['alias_id']]);
            }

            $stmt = exec_query("SELECT subdomain_id, subdomain_url_forward FROM subdomain WHERE subdomain_url_forward <> 'no'");

            while ($row = $stmt->fetchRow()) {
                $uri = UriRedirect::fromString($row['subdomain_url_forward']);
                $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/';
                $uri->setPath($uriPath);
                exec_query('UPDATE subdomain SET subdomain_url_forward = ? WHERE subdomain_id = ?', [$uri->getUri(), $row['subdomain_id']]);
            }

            $stmt = exec_query("SELECT subdomain_alias_id, subdomain_alias_url_forward FROM subdomain_alias WHERE subdomain_alias_url_forward <> 'no'");
            while ($row = $stmt->fetchRow()) {
                $uri = UriRedirect::fromString($row['subdomain_alias_url_forward']);
                $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/';
                $uri->setPath($uriPath);
                exec_query('UPDATE subdomain_alias SET subdomain_alias_url_forward = ? WHERE subdomain_alias_id = ?', [
                    $uri->getUri(), $row['subdomain_alias_id']
                ]);
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Add column for HSTS options
     *
     * @return array SQL statements to be executed
     */
    protected function r227()
    {
        return [
            $this->addColumn('ssl_certs', 'hsts_max_age', "int(11) NOT NULL DEFAULT '31536000' AFTER allow_hsts"),
            $this->addColumn('ssl_certs', 'hsts_include_subdomains', "VARCHAR(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'off' AFTER hsts_max_age")
        ];
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
        return $sqlQueries = [
            $this->dropColumn('domain', 'external_mail_dns_ids'),
            $this->dropColumn('domain_aliasses', 'external_mail_dns_ids'),
            "DELETE FROM domain_dns WHERE owned_by = 'ext_mail_feature'",
            "UPDATE domain_aliasses SET external_mail = 'off'",
            "UPDATE domain SET external_mail = 'off'"
        ];
    }

    /**
     * #IP-1581 Allow to disable auto-configuration of network interfaces
     * - Add server_ips.ip_config_mode column
     *
     * @return null|string SQL statement to be executed
     */
    protected function r231()
    {
        return $this->addColumn('server_ips', 'ip_config_mode', "VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT 'auto' AFTER ip_card");
    }

    /**
     * Set configuration mode to `manual' for the server's primary IP
     *
     * @return string SQL statement to be executed
     * @throws Zend_Exception
     * @throws iMSCP_Exception_Database
     */
    protected function r232()
    {
        $primaryIP = quoteValue(Registry::get('config')['BASE_SERVER_IP']);
        return "UPDATE server_ips SET ip_config_mode = 'manual' WHERE ip_number = $primaryIP";
    }

    /**
     * Creates missing entries in the php_ini table (one for each domain)
     *
     * @return null
     */
    protected function r233()
    {
        try {
            $phpini = PhpIni::getInstance();

            // For each reseller
            $resellers = execute_query("SELECT admin_id FROM admin WHERE admin_type = 'reseller'");
            while ($reseller = $resellers->fetchRow()) {
                $phpini->loadResellerPermissions($reseller['admin_id']);

                // For each client of the reseller
                $clients = exec_query("SELECT admin_id FROM admin WHERE created_by = {$reseller['admin_id']}");
                while ($client = $clients->fetchRow()) {
                    $phpini->loadClientPermissions($client['admin_id']);
                    $domain = exec_query(
                        "SELECT domain_id FROM domain WHERE domain_admin_id = ? AND domain_status <> ?", [$client['admin_id'], 'todelete']
                    );

                    if (!$domain->rowCount()) {
                        continue;
                    }

                    $domain = $domain->fetchRow();
                    $phpini->loadDomainIni($client['admin_id'], $domain['domain_id'], 'dmn');
                    if ($phpini->isDefaultDomainIni()) {
                        $phpini->saveDomainIni($client['admin_id'], $domain['domain_id'], 'dmn');
                    }

                    $subdomains = exec_query('SELECT subdomain_id FROM subdomain WHERE domain_id = ? AND subdomain_status <> ?', [
                        $domain['domain_id'], 'todelete'
                    ]);
                    while ($subdomain = $subdomains->fetchRow()) {
                        $phpini->loadDomainIni($client['admin_id'], $subdomain['subdomain_id'], 'sub');
                        if ($phpini->isDefaultDomainIni()) {
                            $phpini->saveDomainIni($client['admin_id'], $subdomain['subdomain_id'], 'sub');
                        }
                    }
                    unset($subdomains);

                    $domainAliases = exec_query('SELECT alias_id FROM domain_aliasses WHERE domain_id = ? AND alias_status <> ?', [
                        $domain['domain_id'], 'todelete'
                    ]);
                    while ($domainAlias = $domainAliases->fetchRow()) {
                        $phpini->loadDomainIni($client['admin_id'], $domainAlias['alias_id'], 'als');
                        if ($phpini->isDefaultDomainIni()) {
                            $phpini->saveDomainIni($client['admin_id'], $domainAlias['alias_id'], 'als');
                        }
                    }
                    unset($domainAliases);

                    $subdomainAliases = exec_query(
                        '
                            SELECT subdomain_alias_id FROM subdomain_alias JOIN domain_aliasses USING(alias_id)
                            WHERE domain_id = ? AND subdomain_alias_status <> ?
                        ',
                        [$domain['domain_id'], 'todelete']
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

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
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
        return [
            $this->addColumn('domain', 'url_forward', "VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"),
            $this->addColumn('domain', 'type_forward', "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL"),
            $this->addColumn('domain', 'host_forward', "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off'"),
            $this->addColumn('domain_aliasses', 'host_forward', "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off' AFTER type_forward"),
            $this->addColumn('subdomain', 'subdomain_host_forward', "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off' AFTER subdomain_type_forward"),
            $this->addColumn(
                'subdomain_alias',
                'subdomain_alias_host_forward',
                "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off' AFTER subdomain_alias_type_forward"
            ),
        ];
    }

    /**
     * Remove support for ftp URL redirects
     *
     * @return array SQL statements to be executed
     */
    protected function r236()
    {
        return [
            "UPDATE domain_aliasses SET url_forward = 'no', type_forward = NULL WHERE url_forward LIKE 'ftp://%'",
            "UPDATE subdomain SET subdomain_url_forward = 'no', subdomain_type_forward = NULL WHERE subdomain_url_forward LIKE 'ftp://%'",
            "
                UPDATE subdomain_alias SET subdomain_alias_url_forward = 'no', subdomain_alias_type_forward = NULL
                WHERE subdomain_alias_url_forward LIKE 'ftp://%'
            "
        ];
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
     * Delete deprecated `statistics` group for AWStats
     *
     * @return string SQL statement to be executed
     */
    protected function r241()
    {
        return "DELETE FROM htaccess_groups WHERE ugroup = 'statistics'";
    }

    /**
     * Add servers_ips.ip_netmask column
     *
     * @return null|string SQL statement to be executed
     */
    protected function r242()
    {
        return $this->addColumn('server_ips', 'ip_netmask', 'TINYINT(1) UNSIGNED DEFAULT NULL AFTER ip_number');
    }

    /**
     * Populate servers_ips.ip_netmask column
     *
     * @return null
     */
    protected function r243()
    {
        try {
            $stmt = execute_query('SELECT ip_id, ip_number, ip_netmask FROM server_ips');
            while ($row = $stmt->fetchRow()) {
                if ($this->config['BASE_SERVER_IP'] === $row['ip_number'] || $row['ip_netmask'] !== NULL) {
                    continue;
                }

                if (strpos($row['ip_number'], ':') !== false) {
                    $netmask = '64';
                } else {
                    $netmask = '32';
                }

                exec_query('UPDATE server_ips SET ip_netmask = ? WHERE ip_id = ?', [$netmask, $row['ip_id']]);
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Renamed plugin.plugin_lock table to plugin.plugin_lockers and set default value
     *
     * @return array SQL statements to be executed
     */
    protected function r244()
    {
        return [
            "ALTER TABLE plugin CHANGE plugin_locked plugin_lockers TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;",
            "UPDATE plugin SET plugin_lockers = '{}'"
        ];
    }

    /**
     * Add columns for alternative document root feature
     * - Add the domain.document_root column
     * - Add the subdomain.subdomain_document_root column
     * - Add the domain_aliasses.alias_document_root column
     * - Add the subdomain_alias.subdomain_alias_document_root column
     *
     * @return array SQL statements to be executed
     */
    protected function r245()
    {
        return [
            $this->addColumn('domain', 'document_root', "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER mail_quota"),
            $this->addColumn(
                'subdomain', 'subdomain_document_root', "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER subdomain_mount"
            ),
            $this->addColumn(
                'domain_aliasses', 'alias_document_root', "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER alias_mount"
            ),
            $this->addColumn(
                'subdomain_alias',
                'subdomain_alias_document_root',
                "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER subdomain_alias_mount"
            ),
        ];
    }

    /**
     * Drop ftp_users.rawpasswd column
     *
     * @return null|string SQL statement to be executed or NULL
     */
    protected function r246()
    {
        return $this->dropColumn('ftp_users', 'rawpasswd');
    }

    /**
     * Drop sql_user.sqlu_pass column
     *
     * @return null|string SQL statement to be executed or NULL
     */
    protected function r247()
    {
        return $this->dropColumn('sql_user', 'sqlu_pass');
    }

    /**
     * Update mail_users.mail_pass columns length
     *
     * @return null|string SQL statement to be executed or NULL
     */
    protected function r248()
    {
        return $this->changeColumn('mail_users', 'mail_pass', "mail_pass varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '_no_'");
    }

    /**
     * Store all mail account passwords using SHA512-crypt scheme
     *
     * @return void
     */
    protected function r249()
    {
        try {
            $stmt = exec_query('SELECT mail_id, mail_pass FROM mail_users WHERE mail_pass <> ? AND mail_pass NOT LIKE ?', ['_no_', '$6$%']);
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                exec_query('UPDATE mail_users SET mail_pass = ? WHERE mail_id = ?', [Crypt::sha512($row['mail_pass']), $row['mail_id']]);
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Change server_ips.ip_number column length
     *
     * @return null|string SQL statement to be executed or NULL
     */
    protected function r250()
    {
        return $this->changeColumn('server_ips', 'ip_number', 'ip_number VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL');
    }

    /**
     * Delete invalid default mail accounts
     *
     * @return string SQL statement to be executed
     */
    protected function r251()
    {
        return "DELETE FROM mail_users WHERE mail_acc RLIKE '^abuse|hostmaster|postmaster|webmaster\\$' AND mail_forward IS NULL";
    }

    /**
     * Fix value for the plugin.plugin_lockers field
     *
     * @return string SQL statement to be executed
     */
    protected function r252()
    {
        return "UPDATE plugin SET plugin_lockers = '{}' WHERE plugin_lockers = 'null'";
    }

    /**
     * Change domain_dns.domain_dns_status column length
     *
     * @return null|string SQL statement to be executed or NULL
     */
    protected function r253()
    {
        return $this->changeColumn('domain_dns', 'domain_dns_status', "domain_dns_status TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL");
    }

    /**
     * Remove any virtual mailbox that was added for Postfix canonical domain (SERVER_HOSTNAME)
     *
     * SERVER_HOSTNAME is a Postfix canonical domain (local domain) which
     * cannot be listed in both `mydestination' and `virtual_mailbox_domains'
     * Postfix parameters. This necessarily means that Postfix canonical
     * domains cannot have virtual mailboxes, hence their deletion.
     *
     * See http://www.postfix.org/VIRTUAL_README.html#canonical
     *
     * @return null
     */
    protected function r254()
    {
        try {
            $stmt = exec_query(
                "SELECT mail_id, mail_type FROM mail_users WHERE mail_type LIKE '%_mail%' AND SUBSTRING(mail_addr, LOCATE('@', mail_addr)+1) = ?",
                Registry::get('config')['SERVER_HOSTNAME']
            );

            while ($row = $stmt->fetchRow()) {
                if (strpos($row['mail_type'], '_forward') !== FALSE) {
                    # Turn normal+forward account into forward only account
                    exec_query("UPDATE mail_users SET mail_pass = '_no_', mail_type = ?, quota = NULL WHERE mail_id = ?", [
                        preg_replace('/,?\b\.*_mail\b,?/', '', $row['mail_type']), $row['mail_id']
                    ]);
                } else {
                    # Schedule deletion of the mail account as virtual mailboxes
                    # are prohibited for Postfix canonical domains.
                    exec_query("UPDATE mail_users SET status = 'todelete' WHERE mail_id = ?", $row['mail_id']);
                }
            }

            return NULL;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Fixed: mail_users.po_active column of forward only and catch-all accounts must be set to 'no'
     *
     * @return string SQL statement to be executed
     */
    protected function r255()
    {
        return "UPDATE mail_users SET po_active = 'no' WHERE mail_type NOT LIKE '%_mail%'";
    }

    /**
     * Remove output compression related parameters
     *
     * @return null
     */
    protected function r256()
    {
        if (isset($this->dbConfig['COMPRESS_OUTPUT'])) {
            unset($this->dbConfig['COMPRESS_OUTPUT']);
        }

        if (isset($this->dbConfig['SHOW_COMPRESSION_SIZE'])) {
            unset($this->dbConfig['SHOW_COMPRESSION_SIZE']);
        }

        return NULL;
    }

    /**
     * Update user_gui_props table
     *
     * @return array SQL statements to be executed
     */
    protected function r257()
    {
        return [
            $this->changeColumn('user_gui_props', 'lang', "lang varchar(15) collate utf8_unicode_ci DEFAULT 'browser'"),
            "UPDATE user_gui_props SET lang = 'browser' WHERE lang = 'auto'",
            $this->changeColumn('user_gui_props', 'layout', "layout varchar(100) collate utf8_unicode_ci NOT NULL DEFAULT 'default'"),
            $this->changeColumn('user_gui_props', 'layout_color', "layout_color varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'black'"),
            $this->changeColumn('user_gui_props', 'show_main_menu_labels', "show_main_menu_labels tinyint(1) NOT NULL DEFAULT '0'")
        ];
    }

    /**
     * Remove possible orphaned PHP ini entries that belong to subdomains of domain aliases
     *
     * @return string SQL statement to be executed
     */
    protected function r258()
    {
        return "
            DELETE FROM php_ini
            WHERE domain_id NOT IN(SELECT subdomain_alias_id FROM subdomain_alias WHERE subdomain_alias_status <> 'todelete')
            AND domain_type = 'subals'
        ";
    }

    /**
     * Fix erroneous ftp_group.members fields (missing subsequent FTP account members)
     *
     * @return string SQL statement to be executed
     */
    protected function r259()
    {
        return "
            UPDATE ftp_group AS t1,
            (SELECT gid, group_concat(userid SEPARATOR ',') AS members FROM ftp_users GROUP BY gid) AS t2
            SET t1.members = t2.members
            WHERE t1.gid = t2.gid
        ";
    }

    /**
     * Adds unique constraint for mail user entities
     *
     * @return array SQL statements to be executed
     */
    protected function r265()
    {
        try {
            if (!$this->isTable('old_mail_users')) {
                exec_query($this->renameTable('mail_users', 'old_mail_users'));
            }

            exec_query($this->dropTable('mail_users')); // Cover possible failure
            exec_query('CREATE TABLE mail_users LIKE old_mail_users');

            if (NULL !== ($statement = $this->dropIndexByName('mail_users', 'mail_addr'))) {
                exec_query($statement);
            }

            return [
                $this->addIndex('mail_users', 'mail_addr', 'UNIQUE', 'mail_addr'),
                'INSERT IGNORE INTO mail_users SELECT * FROM old_mail_users',
                $this->dropTable('old_mail_users')
            ];
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Add unique constraint on server_traffic.traff_time column to avoid duplicate time periods
     *
     * @return array SQL statements to be executed
     */
    protected function r266()
    {
        try {
            if (!$this->isTable('old_server_traffic')) {
                exec_query($this->renameTable('server_traffic', 'old_server_traffic'));
            }

            exec_query($this->dropTable('server_traffic')); // Cover possible failure
            exec_query('CREATE TABLE server_traffic LIKE old_server_traffic');

            if (NULL !== ($statement = $this->dropIndexByName('server_traffic', 'traff_time'))) {
                exec_query($statement);
            }

            return [
                $this->addIndex('server_traffic', 'traff_time', 'UNIQUE', 'traff_time'),
                'INSERT IGNORE INTO server_traffic SELECT * FROM old_server_traffic',
                $this->dropTable('old_server_traffic')
            ];
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * #IP-1587 Slow query on domain_traffic table when admin or reseller want to login into customer's area
     * - Add compound unique index on the domain_traffic table to avoid slow query and duplicate entries
     *
     * @return array SQL statements to be executed
     */
    protected function r268()
    {
        try {
            if (!$this->isTable('old_domain_traffic')) {
                exec_query($this->renameTable('domain_traffic', 'old_domain_traffic'));
            }

            exec_query($this->dropTable('domain_traffic')); // Cover possible failure
            exec_query('CREATE TABLE domain_traffic LIKE old_domain_traffic');

            if (NULL !== ($statement = $this->dropIndexByName('domain_traffic', 'i_unique_timestamp'))) {
                exec_query($statement);
            }

            return [
                $this->addIndex('domain_traffic', ['domain_id', 'dtraff_time'], 'UNIQUE', 'i_unique_timestamp'),
                'INSERT IGNORE INTO domain_traffic SELECT * FROM old_domain_traffic',
                $this->dropTable('old_domain_traffic')
            ];
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Add missing primary key on httpd_vlogger table
     *
     * @return null|array SQL statements to be executed or null
     */
    protected function r269()
    {
        try {
            if (!$this->isTable('old_httpd_vlogger')) {
                exec_query($this->renameTable('httpd_vlogger', 'old_httpd_vlogger'));
            }

            exec_query($this->dropTable('httpd_vlogger')); // Cover possible failure
            exec_query('CREATE TABLE httpd_vlogger LIKE old_httpd_vlogger');

            if (NULL !== ($statement = $this->dropIndexByName('httpd_vlogger', 'PRIMARY'))) {
                exec_query($statement);
            }

            return [
                $this->addIndex('httpd_vlogger', ['vhost', 'ldate']),
                'INSERT IGNORE INTO httpd_vlogger SELECT * FROM old_httpd_vlogger',
                $this->dropTable('old_httpd_vlogger')
            ];
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Adds compound unique key on the php_ini table
     *
     * @return array SQL statement to be executed
     */
    protected function r271()
    {
        try {
            if (!$this->isTable('old_php_ini')) {
                exec_query($this->renameTable('php_ini', 'old_php_ini'));
            }

            exec_query($this->dropTable('php_ini')); // Cover possible failure
            exec_query('CREATE TABLE php_ini LIKE old_php_ini');

            if (NULL !== ($statements = $this->dropIndexByColumn('php_ini', 'admin_id'))) {
                foreach ($statements as $statement) {
                    exec_query($statement);
                }
            }

            if (NULL !== ($statements = $this->dropIndexByColumn('php_ini', 'domain_id'))) {
                foreach ($statements as $statement) {
                    exec_query($statement);
                }
            }

            if (NULL !== ($statements = $this->dropIndexByColumn('php_ini', 'domain_type'))) {
                foreach ($statements as $statement) {
                    exec_query($statement);
                }
            }

            return [
                $this->addIndex('php_ini', ['admin_id', 'domain_id', 'domain_type'], 'UNIQUE', 'unique_php_ini'),
                'INSERT IGNORE INTO php_ini SELECT * FROM old_php_ini',
                $this->dropTable('old_php_ini')
            ];
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Schema review (domain_traffic table):
     *  - Fix for #IP-1756:
     *   - Remove PK (dtraff_id)
     *   - Remove UK (domain_id, dtraff_time)
     *   - Add compound PK (domain_id, dtraff_time)
     *
     * @return null|string SQL statement to be executed
     */
    protected function r272()
    {
        try {
            if (NULL !== ($statement = $this->dropColumn('domain_traffic', 'dtraff_id'))) {
                exec_query($statement);
            }

            if (NULL !== ($statement = $this->dropIndexByName('domain_traffic', 'i_unique_timestamp'))) {
                exec_query($statement);
            }

            return $this->addIndex('domain_traffic', ['domain_id', 'dtraff_time']);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Schema review (server_traffic table):
     *  - Remove PK (dtraff_id)
     *  - Remove UK (traff_time)
     *  - Add PK (traff_time)
     *
     * @return array SQL statements to be executed
     */
    protected function r273()
    {
        try {
            if (NULL !== ($statement = $this->dropColumn('server_traffic', 'straff_id'))) {
                exec_query($statement);
            }

            if ($statements = $this->dropIndexByColumn('server_traffic', 'traff_time')) {
                foreach ($statements as $statement) {
                    exec_query($statement);
                }
            }

            return [
                // All parts of a PRIMARY KEY must be NOT NULL
                'ALTER TABLE server_traffic MODIFY `traff_time` INT(10) UNSIGNED NOT NULL',
                $this->addIndex('server_traffic', 'traff_time')
            ];
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
