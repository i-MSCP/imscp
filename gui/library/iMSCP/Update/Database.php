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
    protected $lastUpdate = 250;

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
     * @param string $table Table name
     * @return bool TRUE if the given table is know, FALSE otherwise
     */
    protected function isKnownTable($table)
    {
        return (bool)exec_query('SHOW TABLES LIKE ?', $table)->rowCount();
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
     * Prohibit upgrade from i-MSCP versions older than 1.1.x
     *
     * @throws iMSCP_Exception
     */
    protected function r173()
    {
        throw new iMSCP_Exception('Upgrade support for i-MSCP versions older than 1.1.0 has been removed. You must first upgrade to i-MSCP version 1.3.8, then upgrade to this newest version.');
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
     * @return null|array SQL statements to be executed or null
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
        $cfg = iMSCP_Registry::get('config');
        $stmt = execute_query('SELECT ip_id, ip_number, ip_netmask FROM server_ips');
        while ($row = $stmt->fetchRow()) {
            if ($cfg['BASE_SERVER_IP'] === $row['ip_number'] || $row['ip_netmask'] !== NULL) {
                continue;
            }

            if (strpos($row['ip_number'], ':') !== false) {
                $netmask = '64';
            } else {
                $netmask = '32';
            }

            exec_query("UPDATE server_ips SET ip_netmask = ? WHERE ip_id = ?", array($netmask, $row['ip_id']));
        }

        return null;
    }

    /**
     * Renamed plugin.plugin_lock table to plugin.plugin_lockers and set default value
     * 
     * @return array SQL statements to be executed
     */
    protected function r244()
    {
        return array(
            "
                ALTER TABLE plugin CHANGE plugin_locked plugin_lockers
                TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;
            ",
            "UPDATE plugin SET plugin_lockers = '{}'"
        );
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
        return array(
            $this->addColumn(
                'domain',
                'document_root',
                "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER mail_quota"
            ),
            $this->addColumn(
                'subdomain',
                'subdomain_document_root',
                "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER subdomain_mount"
            ),
            $this->addColumn(
                'domain_aliasses',
                'alias_document_root',
                "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER alias_mount"
            ),
            $this->addColumn(
                'subdomain_alias',
                'subdomain_alias_document_root',
                "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '/htdocs' AFTER subdomain_alias_mount"
            ),
        );
    }

    /**
     * Drop ftp_users.rawpasswd column
     *
     * @return null|string
     */
    protected function r246()
    {
        return $this->dropColumn('ftp_users', 'rawpasswd');
    }

    /**
     * Drop sql_user.sqlu_pass column
     *
     * @return null|string
     */
    protected function r247()
    {
        return $this->dropColumn('sql_user', 'sqlu_pass');
    }

    /**
     * Update mail_users.mail_pass columns length
     *
     * @return null|string
     */
    protected function r248()
    {
        return $this->changeColumn(
            'mail_users', 'mail_pass', "mail_pass varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '_no_'"
        );
    }

    /**
     * Store all mail account passwords using SHA512-crypt scheme
     *
     * @return void
     */
    protected function r249()
    {
        $stmt = exec_query('SELECT mail_id, mail_pass FROM mail_users WHERE mail_pass <> ? AND mail_pass NOT LIKE ?',
            array('_no_', '$6$%')
        );
        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            exec_query('UPDATE mail_users SET mail_pass = ? WHERE mail_id = ?', array(
                iMSCP\Crypt::sha512($row['mail_pass']), $row['mail_id']
            ));
        }
    }

    /**
     * Change server_ips.ip_number column length
     *
     * @return null|string
     */
    protected function r250()
    {
        return $this->changeColumn(
            'server_ips', 'ip_number', 'ip_number VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
        );
    }
}
