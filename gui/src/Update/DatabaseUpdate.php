<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/** @noinspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection 
 * PhpUnused
 */

declare(strict_types=1);

namespace iMSCP\Update;

use iMSCP\Config\DbConfig;
use iMSCP\Config\FileConfig;
use iMSCP\Crypt;
use iMSCP\Database\DatabaseMySQL;
use iMSCP\PhpEditor;
use iMSCP\Registry;
use iMSCP\Uri\UriRedirect;
use PDO;
use phpseclib\Crypt\RSA;
use Throwable;

/**
 * Class DatabaseUpdate
 * @package iMSCP\Update
 */
class DatabaseUpdate extends AbstractUpdate
{
    /**
     * @var DatabaseUpdate
     */
    protected static $instance;

    /**
     * @var FileConfig
     */
    protected $config;

    /**
     * @var DbConfig
     */
    protected $dbConfig;

    /**
     * Database name being updated
     *
     * @var string
     */
    protected $databaseName;

    /**
     * Tells whether or not a request must be send to the i-MSCP daemon after
     * that all database updates were applied.
     *
     * @var bool
     */
    protected $_daemonRequest = false;

    /**
     * @var int Last database update revision
     */
    protected $lastUpdate = 287;

    /**
     * Singleton - Make new unavailable
     *
     * @throws UpdateException
     */
    protected function __construct()
    {
        ignore_user_abort(true);

        $this->config = Registry::get('config');
        $this->dbConfig = Registry::get('dbConfig');

        if (!isset($this->config['DATABASE_NAME'])) {
            throw new UpdateException('Database name not found.');
        }

        $this->databaseName = $this->config['DATABASE_NAME'];
    }

    /**
     * Implements Singleton design pattern
     *
     */
    public static function getInstance()
    {
        if (NULL === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
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
        $db = DatabaseMySQL::getInstance();

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
                $this->executeSqlStatements($queries);
                $this->dbConfig['DATABASE_REVISION'] = $revision;

                # Make sure that we are still in transaction due to possible
                # implicit commit.
                # See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html
                if ($db->inTransaction()) {
                    $db->commit();
                }
            } catch (Throwable $e) {
                # Make sure that we are still in transaction due to possible
                # implicit commit.
                # See https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $this->setError(sprintf(
                    'Database update %s failed: %s', $revision, $e->getMessage()
                ));
                return false;
            }
        }

        if (PHP_SAPI != 'cli' && $this->_daemonRequest) {
            send_request();
        }

        return true;
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
     * Execute the given SQL statements
     *
     * @param array $queries
     * @return void
     */
    protected function executeSqlStatements(array $queries)
    {
        $db = DatabaseMySQL::getInstance();

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
    }

    /**
     * Optimize all tables inside database
     *
     * return void
     */
    public function optimizeTables()
    {

        $stmt = execute_query('SHOW TABLES');
        while ($table = $stmt->fetchRow(PDO::FETCH_COLUMN)) {
            execute_query(sprintf(
                'OPTIMIZE LOCAL TABLE %s', quoteIdentifier($table)
            ));
        }

    }

    /**
     * Catch any database updates that were removed
     *
     * @param string $updateMethod Database update method name
     * @param array $params Params
     * @return null
     * @throws UpdateException
     */
    public function __call($updateMethod, $params)
    {
        if (!preg_match('/^r[0-9]+$/', $updateMethod)) {
            throw new UpdateException(sprintf(
                '%s is not a valid database update method', $updateMethod
            ));
        }

        return NULL;
    }

    /**
     * Decrypt any SSL private key
     *
     * @return array|null SQL statements to be executed
     */
    public function r178()
    {

        $statements = [];
        $stmt = execute_query(
            'SELECT cert_id, password, `key` FROM ssl_certs'
        );

        if (!$stmt->rowCount()) {
            return NULL;
        }

        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $certId = quoteValue($row['cert_id'], PDO::PARAM_INT);
            $privateKey = new RSA();

            if ($row['password'] != '') {
                $privateKey->setPassword($row['password']);
            }

            if (!$privateKey->loadKey(
                $row['key'], RSA::PRIVATE_FORMAT_PKCS1
            )) {
                $statements[] = "
                        DELETE FROM ssl_certs
                        WHERE cert_id = $certId
                    ";
                continue;
            }

            // Clear out passphrase
            $privateKey->setPassword();
            // Get unencrypted private key
            $privateKey = $privateKey->getPrivateKey();
            $privateKey = quoteValue($privateKey);
            $statements[] = "
                    UPDATE ssl_certs
                    SET `key` = $privateKey
                    WHERE cert_id = $certId
                ";
        }

        return $statements;

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
     * Singleton - Make clone unavailable
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Prohibit upgrade from i-MSCP versions older than 1.1.x
     *
     */
    protected function r173()
    {
        throw new UpdateException(
            'Upgrade support for i-MSCP versions older than 1.1.0 has been removed. You must first upgrade to i-MSCP version 1.3.8.'
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
            return sprintf(
                'ALTER TABLE %s DROP %s',
                $table,
                quoteIdentifier($column)
            );
        }

        return NULL;
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
            $this->changeColumn(
                'sql_database',
                'domain_id',
                'domain_id INT(10) UNSIGNED NOT NULL'
            ),
            $this->changeColumn(
                'sql_database',
                'sqld_name',
                'sqld_name VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
            ),
            // sql_user table update
            $this->changeColumn(
                'sql_user', 'sqld_id', 'sqld_id INT(10) UNSIGNED NOT NULL'
            ),
            $this->changeColumn(
                'sql_user',
                'sqlu_name',
                'sqlu_name VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
            ),
            $this->changeColumn(
                'sql_user',
                'sqlu_pass',
                'sqlu_pass VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
            ),
            $this->addColumn(
                'sql_user',
                'sqlu_host',
                'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER sqlu_name'
            ),
            $this->addIndex('sql_user', 'sqlu_name', 'INDEX', 'sqlu_name'),
            $this->addIndex('sql_user', 'sqlu_host', 'INDEX', 'sqlu_host')
        ];
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
            return sprintf(
                'ALTER TABLE %s CHANGE %s %s',
                $table,
                quoteIdentifier($column),
                $columnDefinition
            );
        }

        return NULL;
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
            return sprintf(
                'ALTER TABLE %s ADD %s %s',
                $table,
                quoteIdentifier($column),
                $columnDefinition
            );
        }

        return NULL;

    }

    /**
     * Add index
     *
     * Be aware that no check is made for duplicate rows. Thus, if you want to
     * add an UNIQUE constraint, you must make sure to remove duplicate rows
     * first. We don't make use of the IGNORE clause for the following reasons:
     *
     * - The IGNORE clause is no standard and do not work with Fast Index
     *   Creation (MySQL Bug #40344)
     * - The IGNORE clause has been removed in MySQL 5.7
     *
     * @param string $table Database table name
     * @param array|string $columns Column name(s) with OPTIONAL key length
     * @param string $indexType Index type (PRIMARY KEY, INDEX|KEY, UNIQUE)
     * @param string $indexName Index name (default is autogenerated)
     * @return null|string SQL statement to be executed
     */
    protected function addIndex(
        $table, $columns, $indexType = 'PRIMARY KEY', $indexName = ''
    )
    {

        $table = quoteIdentifier($table);
        $indexType = strtoupper($indexType);
        $columnsTmp = (array)$columns;
        $columns = [];

        // Parse column definitions
        foreach ($columnsTmp as $columnDef) {
            if (preg_match(
                '/^(?P<name>[^(]+)(?P<length>\(\d+\))$/',
                $columnDef,
                $matches
            )) {
                $columns[$matches['name']] = $matches['length'];
            } else {
                $columns[$columnDef] = '';
            }
        }
        unset($columnsTmp);

        $indexName = $indexType == 'PRIMARY KEY'
            ? 'PRIMARY'
            : ($indexName == '' ? key($columns) : $indexName);
        $stmt = exec_query(
            "SHOW INDEX FROM $table WHERE KEY_NAME = ?", [$indexName]
        );

        if (!$stmt->rowCount()) {
            $columnsStr = '';
            foreach ($columns as $column => $length) {
                $columnsStr .= quoteIdentifier($column) . $length . ',';
            }

            unset($columns);

            $indexName = $indexName == 'PRIMARY'
                ? ''
                : quoteIdentifier($indexName);

            return sprintf(
                'ALTER TABLE %s ADD %s %s (%s)',
                $table,
                $indexType,
                $indexName,
                rtrim($columnsStr, ',')
            );
        }

        return NULL;
    }

    /**
     * Please, add all the database update methods below. Don't forget to
     * update the `lastUpdate' field above.
     */

    /**
     * Fix SQL user hosts
     *
     * @return array SQL statements to be executed
     */
    protected function r177()
    {

        $statements = [];
        $sqlUserHost = Registry::get('config')['DATABASE_USER_HOST'];

        if ($sqlUserHost == '127.0.0.1') {
            $sqlUserHost = 'localhost';
        }

        $sqlUserHost = quoteValue($sqlUserHost);
        $stmt = execute_query('SELECT DISTINCT sqlu_name FROM sql_user');

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $sqlUser = quoteValue($row['sqlu_name']);
                $statements[] = "
                        UPDATE IGNORE mysql.user
                        SET Host = $sqlUserHost
                        WHERE User = $sqlUser
                        AND Host NOT IN ($sqlUserHost, '%')
                    ";
                $statements[] = "
                        UPDATE IGNORE mysql.db
                        SET Host = $sqlUserHost
                        WHERE User = $sqlUser
                        AND Host NOT IN ($sqlUserHost, '%')
                    ";
                $statements[] = "
                        UPDATE sql_user
                        SET sqlu_host = $sqlUserHost
                        WHERE sqlu_name = $sqlUser
                        AND sqlu_host NOT IN ($sqlUserHost, '%')
                    ";
            }

            $statements[] = 'FLUSH PRIVILEGES';
        }
        return $statements;

    }

    /**
     * Rename ssl_certs.id column to ssl_certs.domain_id
     *
     * @return null|string SQL statement to be executed
     */
    protected function r180()
    {
        return $this->changeColumn(
            'ssl_certs', 'id', 'domain_id INT(10) NOT NULL'
        );
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
            'ssl_certs',
            'key',
            'private_key TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
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
            'ssl_certs',
            'cert',
            'certificate TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL'
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
            'ssl_certs',
            'ca_cert',
            'ca_bundle TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
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
     * Drop the given index from the given table
     *
     * @param string $table Table name
     * @param string $indexName Index name
     * @return null|string SQL statement to be executed
     */
    protected function dropIndexByName($table, $indexName = 'PRIMARY')
    {

        $table = quoteIdentifier($table);
        $stmt = exec_query(
            "SHOW INDEX FROM $table WHERE KEY_NAME = ?", $indexName
        );

        if ($stmt->rowCount()) {
            return sprintf(
                'ALTER TABLE %s DROP INDEX %s',
                $table,
                quoteIdentifier($indexName)
            );
        }

        return NULL;

    }

    /**
     * Add domain_id_domain_type index in ssl_certs table
     *
     * @return null|string SQL statement to be executed
     */
    protected function r186()
    {
        return $this->addIndex(
            'ssl_certs',
            ['domain_id', 'domain_type'],
            'UNIQUE',
            'domain_id_domain_type'
        );
    }

    /**
     * SSL certificates normalization
     *
     * @return array|null SQL statements to be executed
     */
    protected function r189()
    {

        $statements = [];
        $stmt = execute_query(
            '
                    SELECT cert_id, private_key, certificate, ca_bundle
                    FROM ssl_certs
                '
        );

        if (!$stmt->rowCount()) {
            return NULL;
        }

        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $certificateId = quoteValue($row['cert_id'], PDO::PARAM_INT);
            // Data normalization
            $privateKey = quoteValue(
                str_replace(
                    "\r\n", "\n", trim($row['private_key'])
                ) . PHP_EOL
            );
            $certificate = quoteValue(
                str_replace(
                    "\r\n", "\n", trim($row['certificate'])
                ) . PHP_EOL
            );
            $caBundle = quoteValue(
                str_replace("\r\n", "\n", trim($row['ca_bundle']))
            );
            $statements[] = "
                    UPDATE ssl_certs SET private_key = $privateKey,
                        certificate = $certificate, ca_bundle = $caBundle
                    WHERE cert_id = $certificateId
                ";
        }

        return $statements;

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
        return $this->addColumn(
            'mail_users',
            'po_active',
            "VARCHAR(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes' AFTER status"
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
            UPDATE mail_users
            SET mail_pass = SUBSTRING(mail_pass, 4), po_active = 'no'
            WHERE mail_pass <> '_no_'
            AND status = 'disabled'
        ";
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
            $this->addColumn(
                'plugin',
                'plugin_priority',
                "INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER plugin_config"
            ),
            $this->addIndex(
                'plugin', 'plugin_priority', 'INDEX', 'plugin_priority'
            )
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

        if ($sql !== NULL) {
            return [
                $sql,
                'UPDATE plugin SET plugin_config_prev = plugin_config'
            ];
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
                'plugin',
                'plugin_config_prev',
                'plugin_config_prev TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
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
            $this->changeColumn(
                'domain',
                'allowbackup',
                "allowbackup varchar(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn|sql|mail'"
            ),
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

        $statements = [];
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
                $statements[] = "
                    UPDATE hosting_plans SET props = $props WHERE id = $id
                ";
            }
        }

        return $statements;

    }

    /**
     * Add plugin.plugin_lock field
     *
     * @return string SQL statement to be executed
     */
    protected function r206()
    {
        return $this->addColumn(
            'plugin',
            'plugin_locked',
            "TINYINT UNSIGNED NOT NULL DEFAULT '0'"
        );
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
     * #IP-582 PHP editor - PHP configuration levels (per_user, per_domain and
     *         per_site) are ignored
     * - Adds php_ini.admin_id and php_ini.domain_type columns
     * - Adds admin_id, domain_id and domain_type indexes
     * - Populates the php_ini.admin_id column for existent records
     *
     * @return array SQL statements to be executed
     */
    protected function r211()
    {
        return [
            $this->addColumn(
                'php_ini', 'admin_id', 'INT(10) NOT NULL AFTER `id`'
            ),
            $this->addColumn(
                'php_ini',
                'domain_type',
                "VARCHAR(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'dmn' AFTER `domain_id`"
            ),
            $this->addIndex('php_ini', 'admin_id', 'KEY'),
            $this->addIndex('php_ini', 'domain_id', 'KEY'),
            $this->addIndex('php_ini', 'domain_type', 'KEY'),
            "
                UPDATE php_ini
                JOIN domain USING(domain_id)
                SET admin_id = domain_admin_id
                WHERE domain_type = 'dmn'
            "
        ];
    }

    /**
     * Makes the PHP mail function disablable
     * - Adds reseller_props.php_ini_al_mail_function permission column
     * - Adds domain.phpini_perm_mail_function permission column
     * - Adds PHP mail permission property in hosting plans if any
     *
     * @return array SQL statements to be executed
     */
    protected function r212()
    {

        // Add permission column for resellers
        $statements = [
            $this->addColumn(
                'reseller_props',
                'php_ini_al_mail_function',
                "VARCHAR(15) NOT NULL DEFAULT 'yes' AFTER `php_ini_al_disable_functions`"
            ),
            # Add permission column for clients
            $this->addColumn(
                'domain',
                'phpini_perm_mail_function',
                "VARCHAR(20) NOT NULL DEFAULT 'yes' AFTER `phpini_perm_disable_functions`"
            )
        ];

        // Add PHP mail permission property in hosting plans if any
        $stmt = execute_query('SELECT id, props FROM hosting_plans');
        while ($row = $stmt->fetchRow()) {
            $id = quoteValue($row['id'], PDO::PARAM_INT);
            $props = explode(';', $row['props']);

            if (sizeof($props) < 26) {
                // Insert new property at position 18
                array_splice($props, 18, 0, 'yes');
                $statements[] = 'UPDATE hosting_plans SET props = '
                    . quoteValue(implode(';', $props)) . 'WHERE id = '
                    . $id;
            }
        }

        return $statements;

    }

    /**
     * Deletes obsolete PHP editor configuration options
     * PHP configuration options defined at administrator level are not longer
     * supported
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
        return "
            DELETE FROM hosting_plans
            WHERE reseller_id NOT IN(SELECT admin_id FROM admin WHERE admin_type = 'reseller')
        ";
    }

    /**
     * Add status column in ftp_users table
     *
     * @return string SQL statements to be executed
     */
    protected function r217()
    {
        return $this->addColumn(
            'ftp_users',
            'status',
            "varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT 'ok'"
        );
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
                'domain',
                'external_mail_dns_ids',
                "external_mail_dns_ids VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT ''"
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
                    'A','AAAA','CERT','CNAME','DNAME','GPOS','KEY','KX','MX',
                    'NAPTR','NSAP','NS','NXT','PTR','PX','SIG','SRV','TXT','SPF'
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
            $this->changeColumn(
                'domain_dns',
                'domain_dns', "`domain_dns` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
            ),
            $this->changeColumn(
                'domain_dns',
                'domain_text', "`domain_text` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
            ),
            $this->addIndex(
                'domain_dns',
                [
                    'domain_id', 'alias_id', 'domain_dns(255)', 'domain_class',
                    'domain_type', 'domain_text(255)'
                ],
                'UNIQUE'
            )
        ];
    }

    /**
     * Convert FTP usernames, groups and members to IDNA form
     *
     * @return null
     */
    protected function r222()
    {
        $stmt = exec_query('SELECT userid FROM ftp_users');
        while ($row = $stmt->fetchRow()) {
            exec_query(
                'UPDATE ftp_users SET userid = ? WHERE userid = ?',
                [encode_idna($row['userid']), $row['userid']]
            );
        }

        $stmt = exec_query('SELECT groupname, members FROM ftp_group');
        while ($row = $stmt->fetchRow()) {
            $members = implode(
                ',', array_map('encode_idna', explode(',', $row['members']))
            );
            exec_query(
                '
                        UPDATE ftp_group
                        SET groupname = ?, members = ?
                        WHERE groupname = ?
                    ',
                [
                    encode_idna($row['groupname']),
                    $members,
                    $row['groupname']
                ]
            );
        }

        return NULL;
    }

    /**
     * Wrong value for LOG_LEVEL configuration parameter
     *
     * @return null
     */
    protected function r223()
    {
        if (isset($this->dbConfig['LOG_LEVEL'])
            && preg_match('/\D/', $this->dbConfig['LOG_LEVEL'])
        ) {
            $this->dbConfig['LOG_LEVEL'] = defined(
                $this->dbConfig['LOG_LEVEL']
            ) ? constant($this->dbConfig['LOG_LEVEL']) : E_USER_ERROR;
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
        $statements = [];

        $sql = $this->addColumn(
            'domain_aliasses',
            'type_forward',
            "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER url_forward"
        );

        if ($sql !== NULL) {
            $statements[] = $sql;
            $statements[] = "
                UPDATE domain_aliasses
                SET type_forward = '302'
                WHERE url_forward <> 'no'
            ";
        }

        $sql = $this->addColumn(
            'subdomain',
            'subdomain_type_forward',
            "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER subdomain_url_forward"
        );

        if ($sql !== NULL) {
            $statements[] = $sql;
            $statements[] = "
                UPDATE subdomain
                SET subdomain_type_forward = '302'
                WHERE subdomain_url_forward <> 'no'
            ";
        }

        $sql = $this->addColumn(
            'subdomain_alias',
            'subdomain_alias_type_forward',
            "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL AFTER subdomain_alias_url_forward"
        );

        if ($sql !== NULL) {
            $statements[] = $sql;
            $statements[] = "
                UPDATE subdomain_alias
                SET subdomain_alias_type_forward = '302'
                WHERE subdomain_alias_url_forward <> 'no'
            ";
        }

        return $statements;
    }

    /**
     * #IP-1395: Domain redirect feature - Missing URL path separator
     *
     * @return null
     */
    protected function r226()
    {
        $stmt = exec_query(
            "
                    SELECT alias_id, url_forward
                    FROM domain_aliasses
                    WHERE url_forward <> 'no'
                "
        );

        while ($row = $stmt->fetchRow()) {
            $uri = UriRedirect::fromString($row['url_forward']);
            $uriPath = rtrim(preg_replace(
                    '#/+#', '/', $uri->getPath()), '/') . '/';
            $uri->setPath($uriPath);
            exec_query(
                '
                        UPDATE domain_aliasses
                        SET url_forward = ?
                        WHERE alias_id = ?
                    ',
                [$uri->getUri(), $row['alias_id']]
            );
        }

        $stmt = exec_query(
            "
                    SELECT subdomain_id, subdomain_url_forward
                    FROM subdomain
                    WHERE subdomain_url_forward <> 'no'
                "
        );

        while ($row = $stmt->fetchRow()) {
            $uri = UriRedirect::fromString($row['subdomain_url_forward']);
            $uriPath = rtrim(preg_replace(
                    '#/+#', '/', $uri->getPath()), '/') . '/';
            $uri->setPath($uriPath);
            exec_query(
                '
                        UPDATE subdomain
                        SET subdomain_url_forward = ?
                        WHERE subdomain_id = ?
                    ',
                [$uri->getUri(), $row['subdomain_id']]
            );
        }

        $stmt = exec_query(
            "
                    SELECT subdomain_alias_id, subdomain_alias_url_forward
                    FROM subdomain_alias
                    WHERE subdomain_alias_url_forward <> 'no'"
        );
        while ($row = $stmt->fetchRow()) {
            $uri = UriRedirect::fromString(
                $row['subdomain_alias_url_forward']
            );
            $uriPath = rtrim(preg_replace(
                    '#/+#', '/', $uri->getPath()), '/') . '/';
            $uri->setPath($uriPath);
            exec_query(
                '
                        UPDATE subdomain_alias
                        SET subdomain_alias_url_forward = ?
                        WHERE subdomain_alias_id = ?
                    ',
                [$uri->getUri(), $row['subdomain_alias_id']]
            );
        }

        return NULL;

    }

    /**
     * Add column for HSTS options
     *
     * @return array SQL statements to be executed
     */
    protected function r227()
    {
        return [
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
        return $statements = [
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
        $primaryIP = quoteValue(Registry::get('config')['BASE_SERVER_IP']);

        return "
            UPDATE server_ips
            SET ip_config_mode = 'manual'
            WHERE ip_number = $primaryIP
        ";
    }

    /**
     * Creates missing entries in the php_ini table (one for each domain)
     *
     * @return null
     */
    protected function r233()
    {
        $phpini = PhpEditor::getInstance();

        // For each reseller
        $resellers = execute_query(
            "SELECT admin_id FROM admin WHERE admin_type = 'reseller'"
        );
        while ($reseller = $resellers->fetchRow()) {
            $phpini->loadResellerPermissions($reseller['admin_id']);

            // For each client of the reseller
            $clients = exec_query(
                "
                        SELECT admin_id
                        FROM admin
                        WHERE created_by = {$reseller['admin_id']}
                    "
            );
            while ($client = $clients->fetchRow()) {
                $phpini->loadClientPermissions($client['admin_id']);
                $domain = exec_query(
                    "
                            SELECT domain_id
                            FROM domain
                            WHERE domain_admin_id = ?
                            AND domain_status <> ?
                        ",
                    [$client['admin_id'], 'todelete']
                );

                if (!$domain->rowCount()) {
                    continue;
                }

                $domain = $domain->fetchRow();
                $phpini->loadDomainIni(
                    $client['admin_id'], $domain['domain_id'], 'dmn'
                );
                if ($phpini->isDefaultDomainIni()) {
                    $phpini->saveDomainIni(
                        $client['admin_id'], $domain['domain_id'], 'dmn'
                    );
                }

                $subdomains = exec_query(
                    '
                            SELECT subdomain_id
                            FROM subdomain
                            WHERE domain_id = ?
                            AND subdomain_status <> ?
                        ',
                    [$domain['domain_id'], 'todelete']
                );
                while ($subdomain = $subdomains->fetchRow()) {
                    $phpini->loadDomainIni(
                        $client['admin_id'],
                        $subdomain['subdomain_id'],
                        'sub'
                    );
                    if ($phpini->isDefaultDomainIni()) {
                        $phpini->saveDomainIni(
                            $client['admin_id'],
                            $subdomain['subdomain_id'],
                            'sub'
                        );
                    }
                }
                unset($subdomains);

                $domainAliases = exec_query(
                    '
                            SELECT alias_id
                            FROM domain_aliasses
                            WHERE domain_id = ?
                            AND alias_status <> ?
                        ',
                    [$domain['domain_id'], 'todelete']
                );
                while ($domainAlias = $domainAliases->fetchRow()) {
                    $phpini->loadDomainIni(
                        $client['admin_id'],
                        $domainAlias['alias_id'],
                        'als'
                    );
                    if ($phpini->isDefaultDomainIni()) {
                        $phpini->saveDomainIni(
                            $client['admin_id'],
                            $domainAlias['alias_id'],
                            'als'
                        );
                    }
                }
                unset($domainAliases);

                $subdomainAliases = exec_query(
                    '
                            SELECT subdomain_alias_id
                            FROM subdomain_alias
                            JOIN domain_aliasses USING(alias_id)
                            WHERE domain_id = ?
                            AND subdomain_alias_status <> ?
                        ',
                    [$domain['domain_id'], 'todelete']
                );
                while ($subdomainAlias = $subdomainAliases->fetchRow()) {
                    $phpini->loadDomainIni(
                        $client['admin_id'],
                        $subdomainAlias['subdomain_alias_id'],
                        'subals'
                    );
                    if ($phpini->isDefaultDomainIni()) {
                        $phpini->saveDomainIni(
                            $client['admin_id'],
                            $subdomainAlias['subdomain_alias_id'],
                            'subals'
                        );
                    }
                }
                unset($subdomainAliases);
            }
        }

        return NULL;
    }

    /**
     * #IP-1429 Make main domains forwardable
     * - Add domain.url_forward, domain.type_forward and
     *   domain.host_forward columns
     * - Add domain_aliasses.host_forward column
     * - Add subdomain.subdomain_host_forward column
     * - Add subdomain_alias.subdomain_alias_host_forward column
     *
     * @return array SQL statements to be executed
     */
    protected function r235()
    {
        return [
            $this->addColumn(
                'domain',
                'url_forward',
                "VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no'"
            ),
            $this->addColumn(
                'domain',
                'type_forward', "VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL"
            ),
            $this->addColumn(
                'domain',
                'host_forward', "VARCHAR(3) COLLATE utf8_unicode_ci DEFAULT 'Off'"
            ),
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
            "
                UPDATE domain_aliasses
                SET url_forward = 'no', type_forward = NULL
                WHERE url_forward LIKE 'ftp://%'
            ",
            "   UPDATE subdomain
                SET subdomain_url_forward = 'no', subdomain_type_forward = NULL
                WHERE subdomain_url_forward LIKE 'ftp://%'
            ",
            "
                UPDATE subdomain_alias
                SET subdomain_alias_url_forward = 'no',
                    subdomain_alias_type_forward = NULL
                WHERE subdomain_alias_url_forward LIKE 'ftp://%'
            "
        ];
    }

    /**
     * Update domain_traffic table schema
     * - Disallow NULL value on domain_id and dtraff_time columns
     * - Change default value for dtraff_web, dtraff_ftp, dtraff_mail
     *   and domain_traffic columns (NULL to 0)
     *
     * @return array SQL statements to be executed
     */
    protected function r238()
    {
        return [
            $this->changeColumn(
                'domain_traffic',
                'domain_id',
                '`domain_id` INT(10) UNSIGNED NOT NULL'
            ),
            $this->changeColumn(
                'dtraff_time',
                'dtraff_time',
                '`dtraff_time` BIGINT(20) UNSIGNED NOT NULL'
            ),
            $this->changeColumn(
                'dtraff_web',
                'dtraff_web',
                "`dtraff_web` BIGINT(20) UNSIGNED NULL DEFAULT '0'"
            ),
            $this->changeColumn(
                'dtraff_ftp',
                'dtraff_ftp',
                "`dtraff_ftp` BIGINT(20) UNSIGNED NULL DEFAULT '0'"
            ),
            $this->changeColumn(
                'dtraff_mail',
                'dtraff_mail',
                "`dtraff_mail` BIGINT(20) UNSIGNED NULL DEFAULT '0'"
            ),
            $this->changeColumn(
                'dtraff_pop',
                'dtraff_pop',
                "`dtraff_pop` BIGINT(20) UNSIGNED NULL DEFAULT '0'"
            ),
        ];
    }

    /**
     * Drop monthly_domain_traffic view which was added in update r238 and
     * removed later on
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
        return $this->addColumn(
            'server_ips',
            'ip_netmask',
            'TINYINT(1) UNSIGNED DEFAULT NULL AFTER ip_number'
        );
    }

    /**
     * Populate servers_ips.ip_netmask column
     *
     * @return null
     */
    protected function r243()
    {

        $stmt = execute_query(
            'SELECT ip_id, ip_number, ip_netmask FROM server_ips'
        );
        while ($row = $stmt->fetchRow()) {
            if ($this->config['BASE_SERVER_IP'] === $row['ip_number']
                || $row['ip_netmask'] !== NULL
            ) {
                continue;
            }

            if (strpos($row['ip_number'], ':') !== false) {
                $netmask = '64';
            } else {
                $netmask = '32';
            }

            exec_query(
                'UPDATE server_ips SET ip_netmask = ? WHERE ip_id = ?',
                [$netmask, $row['ip_id']]
            );
        }

        return NULL;
    }

    /**
     * Renamed plugin.plugin_lock table to plugin.plugin_lockers and set
     * default value
     *
     * @return array SQL statements to be executed
     */
    protected function r244()
    {
        return [
            $this->changeColumn(
                'plugin',
                'plugin_locked',
                '`plugin_lockers` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL'
            ),
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
        return $this->changeColumn(
            'mail_users',
            'mail_pass',
            "mail_pass varchar(255) collate utf8_unicode_ci NOT NULL DEFAULT '_no_'"
        );
    }

    /**
     * Store all mail account passwords using SHA512-crypt scheme
     *
     * @return void
     */
    protected function r249()
    {
        $stmt = exec_query(
            '
                    SELECT mail_id, mail_pass
                    FROM mail_users
                    WHERE mail_pass <> ?
                    AND mail_pass NOT LIKE ?
                ',
            ['_no_', '$6$%']
        );
        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            exec_query(
                'UPDATE mail_users SET mail_pass = ? WHERE mail_id = ?',
                [Crypt::sha512($row['mail_pass']), $row['mail_id']]
            );
        }
    }

    /**
     * Change server_ips.ip_number column length
     *
     * @return null|string SQL statement to be executed or NULL
     */
    protected function r250()
    {
        return $this->changeColumn(
            'server_ips',
            'ip_number',
            'ip_number VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL'
        );
    }

    /**
     * Delete invalid default mail accounts
     *
     * @return string SQL statement to be executed
     */
    protected function r251()
    {
        return "
            DELETE FROM mail_users
            WHERE mail_acc RLIKE '^abuse|hostmaster|postmaster|webmaster\\$'
            AND mail_forward IS NULL
        ";
    }

    /**
     * Fix value for the plugin.plugin_lockers field
     *
     * @return string SQL statement to be executed
     */
    protected function r252()
    {
        return "
            UPDATE plugin
            SET plugin_lockers = '{}'
            WHERE plugin_lockers = 'null'
        ";
    }

    /**
     * Change domain_dns.domain_dns_status column length
     *
     * @return null|string SQL statement to be executed or NULL
     */
    protected function r253()
    {
        return $this->changeColumn(
            'domain_dns',
            'domain_dns_status',
            "domain_dns_status TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL"
        );
    }

    /**
     * Remove any virtual mailbox that was added for Postfix canonical domain
     * (SERVER_HOSTNAME)
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
        $stmt = exec_query(
            "
                    SELECT mail_id, mail_type
                    FROM mail_users
                    WHERE mail_type LIKE '%_mail%'
                    AND SUBSTRING(mail_addr, LOCATE('@', mail_addr)+1) = ?
                ",
            Registry::get('config')['SERVER_HOSTNAME']
        );

        while ($row = $stmt->fetchRow()) {
            if (strpos($row['mail_type'], '_forward') !== FALSE) {
                # Turn normal+forward account into forward only account
                exec_query(
                    "
                            UPDATE mail_users
                            SET mail_pass = '_no_', mail_type = ?, quota = NULL
                            WHERE mail_id = ?
                        ",
                    [
                        preg_replace(
                            '/,?\b\.*_mail\b,?/', '', $row['mail_type']
                        ),
                        $row['mail_id']
                    ]
                );
            } else {
                # Schedule deletion of the mail account as virtual
                # mailboxes are prohibited for Postfix canonical domains.
                exec_query(
                    "
                            UPDATE mail_users
                            SET status = 'todelete'
                            WHERE mail_id = ?
                        ",
                    [$row['mail_id']]
                );
            }
        }

        return NULL;
    }

    /**
     * Fixed: mail_users.po_active column of forward only and catch-all
     * accounts must be set to 'no'
     *
     * @return string SQL statement to be executed
     */
    protected function r255()
    {
        return "
            UPDATE mail_users
            SET po_active = 'no'
            WHERE mail_type NOT LIKE '%_mail%'
        ";
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
            $this->changeColumn(
                'user_gui_props',
                'lang',
                "lang varchar(15) collate utf8_unicode_ci DEFAULT 'browser'"
            ),
            "UPDATE user_gui_props SET lang = 'browser' WHERE lang = 'auto'",
            $this->changeColumn(
                'user_gui_props',
                'layout',
                "layout varchar(100) collate utf8_unicode_ci NOT NULL DEFAULT 'default'"
            ),
            $this->changeColumn(
                'user_gui_props',
                'layout_color',
                "layout_color varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'black'"
            ),
            $this->changeColumn(
                'user_gui_props',
                'show_main_menu_labels',
                "show_main_menu_labels tinyint(1) NOT NULL DEFAULT '0'"
            )
        ];
    }

    /**
     * Remove possible orphaned PHP ini entries that belong to subdomains of
     * domain aliases
     *
     * @return string SQL statement to be executed
     */
    protected function r258()
    {
        return "
            DELETE FROM php_ini
            WHERE domain_id NOT IN(
                SELECT subdomain_alias_id
                FROM subdomain_alias
                WHERE subdomain_alias_status <> 'todelete'
            )
            AND domain_type = 'subals'
        ";
    }

    /**
     * Fix erroneous ftp_group.members fields (missing subsequent FTP account
     * members)
     *
     * @return string SQL statement to be executed
     */
    protected function r259()
    {
        return "
            UPDATE ftp_group AS t1, (
                SELECT gid, group_concat(userid SEPARATOR ',') AS members
                FROM ftp_users GROUP BY gid
            ) AS t2
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

        if (!$this->isTable('old_mail_users')) {
            exec_query($this->renameTable('mail_users', 'old_mail_users'));
        }

        exec_query($this->dropTable('mail_users')); // Cover possible failure
        exec_query('CREATE TABLE mail_users LIKE old_mail_users');

        if (NULL !== ($statement = $this->dropIndexByName(
                'mail_users', 'mail_addr'))
        ) {
            exec_query($statement);
        }

        return [
            $this->addIndex(
                'mail_users', 'mail_addr', 'UNIQUE', 'mail_addr'
            ),
            'INSERT IGNORE INTO mail_users SELECT * FROM old_mail_users',
            $this->dropTable('old_mail_users')
        ];
    }

    /**
     * Does the given table is known?
     *
     * @param string $table Table name
     * @return bool TRUE if the given table is know, FALSE otherwise
     */
    protected function isTable($table)
    {
        return (bool)exec_query('SHOW TABLES LIKE ?', $table)->rowCount();
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

        return sprintf(
            'ALTER TABLE %s RENAME TO %s',
            quoteIdentifier($oTable),
            quoteIdentifier($nTable)
        );

    }

    /**
     * Drop a table
     *
     * @param string $table Table name
     * @return string SQL statement to be executed
     */
    protected function dropTable($table)
    {
        return sprintf('DROP TABLE IF EXISTS %s', quoteIdentifier($table));
    }

    /**
     * Add unique constraint on server_traffic.traff_time column to avoid
     * duplicate time periods
     *
     * @return array SQL statements to be executed
     */
    protected function r266()
    {
        if (!$this->isTable('old_server_traffic')) {
            exec_query($this->renameTable(
                'server_traffic', 'old_server_traffic')
            );
        }

        // Cover possible failure
        exec_query($this->dropTable('server_traffic'));
        exec_query('CREATE TABLE server_traffic LIKE old_server_traffic');

        if (NULL !== ($statement = $this->dropIndexByName(
                'server_traffic', 'traff_time'))
        ) {
            exec_query($statement);
        }

        return [
            $this->addIndex(
                'server_traffic', 'traff_time', 'UNIQUE', 'traff_time'
            ),
            '
                    INSERT IGNORE INTO server_traffic
                    SELECT *
                    FROM old_server_traffic
                ',
            $this->dropTable('old_server_traffic')
        ];
    }

    /**
     * #IP-1587 Slow query on domain_traffic table when admin or reseller want
     *          to login into customer's area
     * - Add compound unique index on the domain_traffic table to avoid slow
     *   query and duplicate entries
     *
     * @return array SQL statements to be executed
     */
    protected function r268()
    {
        if (!$this->isTable('old_domain_traffic')) {
            exec_query($this->renameTable(
                'domain_traffic', 'old_domain_traffic'
            ));
        }

        // Cover possible failure
        exec_query($this->dropTable('domain_traffic'));
        exec_query('CREATE TABLE domain_traffic LIKE old_domain_traffic');

        if (NULL !== ($statement = $this->dropIndexByName(
                'domain_traffic', 'i_unique_timestamp'))
        ) {
            exec_query($statement);
        }

        return [
            $this->addIndex(
                'domain_traffic',
                ['domain_id', 'dtraff_time'],
                'UNIQUE',
                'i_unique_timestamp'
            ),
            '
                    INSERT IGNORE INTO domain_traffic
                    SELECT *
                    FROM old_domain_traffic
                ',
            $this->dropTable('old_domain_traffic')
        ];
    }

    /**
     * Add missing primary key on httpd_vlogger table
     *
     * @return null|array SQL statements to be executed or null
     */
    protected function r269()
    {
        if (!$this->isTable('old_httpd_vlogger')) {
            exec_query($this->renameTable(
                'httpd_vlogger', 'old_httpd_vlogger'
            ));
        }

        // Cover possible failure
        exec_query($this->dropTable('httpd_vlogger'));
        exec_query('CREATE TABLE httpd_vlogger LIKE old_httpd_vlogger');

        if (NULL !== ($statement = $this->dropIndexByName(
                'httpd_vlogger', 'PRIMARY'))
        ) {
            exec_query($statement);
        }

        return [
            $this->addIndex('httpd_vlogger', ['vhost', 'ldate']),
            '
                    INSERT IGNORE INTO httpd_vlogger
                    SELECT *
                    FROM old_httpd_vlogger
                ',
            $this->dropTable('old_httpd_vlogger')
        ];
    }

    /**
     * Adds compound unique key on the php_ini table
     *
     * @return array SQL statement to be executed
     */
    protected function r271()
    {
        if (!$this->isTable('old_php_ini')) {
            exec_query($this->renameTable('php_ini', 'old_php_ini'));
        }

        // Cover possible failure
        exec_query($this->dropTable('php_ini'));
        exec_query('CREATE TABLE php_ini LIKE old_php_ini');

        if (NULL !== ($statements = $this->dropIndexByColumn(
                'php_ini', 'admin_id'))
        ) {
            foreach ($statements as $statement) {
                exec_query($statement);
            }
        }

        if (NULL !== ($statements = $this->dropIndexByColumn(
                'php_ini', 'domain_id'))
        ) {
            foreach ($statements as $statement) {
                exec_query($statement);
            }
        }

        if (NULL !== ($statements = $this->dropIndexByColumn(
                'php_ini', 'domain_type'))
        ) {
            foreach ($statements as $statement) {
                exec_query($statement);
            }
        }

        return [
            $this->addIndex(
                'php_ini',
                ['admin_id', 'domain_id', 'domain_type'],
                'UNIQUE',
                'unique_php_ini'
            ),
            'INSERT IGNORE INTO php_ini SELECT * FROM old_php_ini',
            $this->dropTable('old_php_ini')
        ];
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

        $statements = [];
        $table = quoteIdentifier($table);
        $stmt = exec_query(
            "SHOW INDEX FROM $table WHERE COLUMN_NAME = ?", [$column]
        );

        if ($stmt->rowCount()) {
            while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                $row = array_change_key_case($row, CASE_UPPER);
                $statements[] = sprintf(
                    'ALTER TABLE %s DROP INDEX %s',
                    $table,
                    quoteIdentifier($row['KEY_NAME'])
                );
            }
        }

        return $statements;

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
        if (NULL !== ($statement = $this->dropColumn(
                'domain_traffic', 'dtraff_id'))
        ) {
            exec_query($statement);
        }

        if (NULL !== ($statement = $this->dropIndexByName(
                'domain_traffic', 'i_unique_timestamp'))
        ) {
            exec_query($statement);
        }

        return $this->addIndex(
            'domain_traffic', ['domain_id', 'dtraff_time']
        );
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
        if (NULL !== ($statement = $this->dropColumn(
                'server_traffic', 'straff_id'))
        ) {
            exec_query($statement);
        }

        if ($statements = $this->dropIndexByColumn(
            'server_traffic', 'traff_time')
        ) {
            foreach ($statements as $statement) {
                exec_query($statement);
            }
        }

        return [
            // All parts of a PRIMARY KEY must be NOT NULL
            $this->changeColumn(
                'server_traffic',
                'traff_time',
                '`traff_time` INT(10) UNSIGNED NOT NULL'
            ),
            $this->addIndex('server_traffic', 'traff_time')
        ];
    }

    /**
     * Add columns for the Apache2 wildcard alias feature
     *
     * @return array SQL statements to be executed
     */
    protected function r274()
    {
        return [
            $this->addColumn(
                'domain',
                'wildcard_alias',
                "enum('yes', 'no') NOT NULL DEFAULT 'no' AFTER `host_forward`"
            ),
            $this->addColumn(
                'subdomain',
                'subdomain_wildcard_alias',
                "enum('yes', 'no') NOT NULL DEFAULT 'no' AFTER `subdomain_host_forward`"
            ),
            $this->addColumn(
                'domain_aliasses',
                'wildcard_alias',
                "enum('yes', 'no') NOT NULL DEFAULT 'no' AFTER `host_forward`"
            ),
            $this->addColumn(
                'subdomain_alias',
                'subdomain_alias_wildcard_alias',
                "enum('yes', 'no') NOT NULL DEFAULT 'no' AFTER `subdomain_alias_host_forward`"
            )
        ];
    }

    /**
     * Update database schema (unwanted default values)
     *
     * @return array SQL statements to be executed
     */
    protected function r275()
    {
        return [
            $this->changeColumn(
                'admin', 'admin_name', "`admin_name` varchar(200) not null"
            ),
            $this->changeColumn(
                'admin', 'admin_pass', "`admin_pass` varchar(200) not null"
            ),
            $this->changeColumn(
                'admin', 'admin_type', "`admin_type` varchar(200) not null"
            ),
            $this->changeColumn(
                'config', 'name', '`name` varchar(255) not null'
            ),
            $this->changeColumn(
                'custom_menus',
                'menu_level',
                "`menu_level` varchar(10) not null"
            ),
            $this->changeColumn(
                'custom_menus',
                'menu_order',
                "`menu_order` varchar(10) not null default '0'"
            ),
            $this->changeColumn(
                'custom_menus',
                'menu_name',
                "`menu_name` varchar(255) not null"
            ),
            $this->changeColumn(
                'custom_menus',
                'menu_link',
                "`menu_link` varchar(255) not null"
            ),
            $this->changeColumn(
                'domain', 'domain_name', "`domain_name` varchar(200) not null"
            ),
            $this->changeColumn(
                'domain',
                'domain_admin_id',
                "`domain_admin_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'domain',
                'domain_created',
                "`domain_created` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'domain',
                'domain_mailacc_limit',
                "`domain_mailacc_limit` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_ftpacc_limit',
                "`domain_ftpacc_limit` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_traffic_limit',
                "`domain_traffic_limit` bigint(20) not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_sqld_limit',
                "`domain_sqld_limit` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_sqlu_limit',
                "`domain_sqlu_limit` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_status',
                "`domain_status` varchar(255) not null"
            ),
            $this->changeColumn(
                'domain',
                'domain_alias_limit',
                "`domain_alias_limit` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_subd_limit',
                "`domain_subd_limit` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_ip_id',
                "`domain_ip_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'domain', 'domain_disk_limit',
                "`domain_disk_limit` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_disk_usage',
                "`domain_disk_usage` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_disk_file',
                "`domain_disk_file` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'domain',
                'domain_disk_mail',
                "`domain_disk_mail` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'domain', 'domain_disk_sql',
                "`domain_disk_sql` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'domain', 'domain_php', "`domain_php` varchar(15) not null"),
            $this->changeColumn(
                'domain', 'domain_cgi', "`domain_cgi` varchar(15) not null"),
            $this->changeColumn(
                'domain', 'mail_quota', "`mail_quota` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'domain_aliasses',
                'domain_id',
                "`domain_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'domain_aliasses',
                'alias_name',
                "`alias_name` varchar(200) not null"
            ),
            $this->changeColumn(
                'domain_aliasses',
                'alias_status',
                "`alias_status` varchar(255) not null"
            ),
            $this->changeColumn(
                'domain_aliasses',
                'alias_mount',
                "`alias_mount` varchar(200) not null"
            ),
            $this->changeColumn(
                'domain_aliasses',
                'alias_ip_id',
                "`alias_ip_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'ftp_group',
                'groupname',
                "`groupname` varchar(255) not null"
            ),
            $this->changeColumn(
                'ftp_users', 'userid', "`userid` varchar(255) not null"
            ),
            $this->changeColumn(
                'ftp_users', 'passwd', "`passwd` varchar(255) not null"
            ),
            $this->changeColumn(
                'ftp_users', 'shell', "`shell` varchar(255) not null"
            ),
            $this->changeColumn(
                'ftp_users', 'homedir', "`homedir` varchar(255) not null"
            ),
            $this->changeColumn(
                'hosting_plans',
                'reseller_id',
                "`reseller_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'hosting_plans', 'name', "`name` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess', 'dmn_id', "`dmn_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'htaccess', 'user_id', "`user_id` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess', 'auth_type', "`auth_type` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess', 'auth_name', "`auth_name` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess', 'path', "`path` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess', 'status', "`status` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess_groups',
                'dmn_id',
                "`dmn_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'htaccess_groups', 'status', "`status` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess_users',
                'dmn_id',
                "`dmn_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'htaccess_users', 'uname', "`uname` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess_users', 'upass', "`upass` varchar(255) not null"
            ),
            $this->changeColumn(
                'htaccess_users', 'status', "`status` varchar(255) not null"
            ),
            'TRUNCATE `login`',
            $this->changeColumn(
                'login', 'session_id', "`session_id` varchar(255) not null"
            ),
            $this->changeColumn(
                'login', 'ipaddr', "`ipaddr` varchar(40) not null"
            ),
            $this->changeColumn(
                'login', 'lastaccess', "`lastaccess` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'login', 'user_name', "`user_name` varchar(255)  not null"
            ),
            $this->changeColumn(
                'mail_users', 'mail_acc', "`mail_acc` text  not null"
            ),
            $this->changeColumn(
                'mail_users',
                'domain_id',
                "`domain_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'mail_users', 'mail_type', "`mail_type` varchar(30) not null"
            ),
            $this->changeColumn(
                'mail_users', 'status', "`status` varchar(255) not null"
            ),
            $this->changeColumn(
                'mail_users',
                'quota',
                "`quota` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'mail_users',
                'mail_addr', "`mail_addr` varchar(254) not null"
            ),
            $this->changeColumn(
                'plugin',
                'plugin_status',
                "`plugin_status` varchar(255) not null"
            ),
            $this->changeColumn(
                'quotalimits', 'name', "`name` varchar(255) not null"
            ),
            $this->changeColumn(
                'quotatallies', 'name', "`name` varchar(255) not null"
            ),
            // reseller_props table
            $this->changeColumn(
                'reseller_props',
                'reseller_id',
                "`reseller_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_dmn_cnt',
                "`current_dmn_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_dmn_cnt',
                "`max_dmn_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_sub_cnt',
                "`current_sub_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_sub_cnt',
                "`max_sub_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_als_cnt',
                "`current_als_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_als_cnt',
                "`max_als_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_mail_cnt',
                "`current_mail_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_mail_cnt',
                "`max_mail_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_ftp_cnt',
                "`current_ftp_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_ftp_cnt',
                "`max_ftp_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_sql_db_cnt',
                "`current_sql_db_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_sql_db_cnt',
                "`max_sql_db_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_sql_user_cnt',
                "`current_sql_user_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_sql_user_cnt',
                "`max_sql_user_cnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_disk_amnt',
                "`current_disk_amnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_disk_amnt',
                "`max_disk_amnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'current_traff_amnt',
                "`current_traff_amnt` int(11) not null default '0'"
            ),
            $this->changeColumn(
                'reseller_props',
                'max_traff_amnt',
                "`max_traff_amnt` int(11) not null default '0'"
            ),

            // server_ips table
            $this->changeColumn(
                'server_ips', 'ip_number', "`ip_number` varchar(45) not null"
            ),
            $this->changeColumn(
                'server_ips',
                'ip_netmask',
                "`ip_netmask` tinyint(1) unsigned not null"
            ),
            $this->changeColumn(
                'server_ips', 'ip_card', "`ip_card` varchar(255) not null"
            ),
            $this->changeColumn(
                'server_ips', 'ip_status', "`ip_status` varchar(255) not null"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_in',
                "`bytes_in` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_out',
                "`bytes_out` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_mail_in',
                "`bytes_mail_in` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_mail_out',
                "`bytes_mail_out` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_pop_in',
                "`bytes_pop_in` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_pop_out',
                "`bytes_pop_out` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_web_in',
                "`bytes_web_in` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'server_traffic',
                'bytes_web_out',
                "`bytes_web_out` bigint(20) unsigned not null default '0'"
            ),
            $this->changeColumn(
                'subdomain',
                'domain_id',
                "`domain_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'subdomain',
                'subdomain_name',
                "`subdomain_name` varchar(200) not null"
            ),
            $this->changeColumn(
                'subdomain',
                'subdomain_mount',
                "`subdomain_mount` varchar(200) not null"
            ),
            $this->changeColumn(
                'subdomain',
                'subdomain_status',
                "`subdomain_status` varchar(255) not null"
            ),
            $this->changeColumn(
                'subdomain_alias',
                'alias_id',
                "`alias_id` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'subdomain_alias',
                'subdomain_alias_name',
                "`subdomain_alias_name` varchar(200) not null"
            ),
            $this->changeColumn(
                'subdomain_alias',
                'subdomain_alias_mount',
                "`subdomain_alias_mount` varchar(200) not null"
            ),
            $this->changeColumn(
                'subdomain_alias',
                'subdomain_alias_status',
                "`subdomain_alias_status` varchar(255) not null"
            ),
            $this->changeColumn(
                'tickets', 'ticket_level', "`ticket_level` int(10) not null"
            ),
            $this->changeColumn(
                'tickets',
                'ticket_from',
                "`ticket_from` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'tickets', 'ticket_to', "`ticket_to` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'tickets',
                'ticket_status',
                "`ticket_status` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'tickets',
                'ticket_reply',
                "`ticket_reply` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'tickets',
                'ticket_urgency',
                "`ticket_urgency` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'tickets',
                'ticket_date',
                "`ticket_date` int(10) unsigned not null"
            ),
            $this->changeColumn(
                'tickets',
                'ticket_subject',
                "`ticket_subject` varchar(255) not null"
            ),
        ];
    }

    /**
     * Switch from utf8 to utf8mb4 (character set and collation)
     *
     * @return array SQL statements to be executed
     */
    protected function r281()
    {
        // Drop all indexes for which the key prefix length would be bigger
        // than 767 bytes when converting from utf8 to utf8mb4.
        $this->executeSqlStatements([
            $this->dropIndexByName('admin', 'admin_name'),
            $this->dropIndexByName('domain', 'domain_name'),
            $this->dropIndexByName('domain_aliasses', 'alias_name'),
            $this->dropIndexByName('domain_dns', 'domain_id'),
            $this->dropIndexByName('ftp_group', 'groupname'),
            $this->dropIndexByName('ftp_users', 'userid'),
            $this->dropIndexByName('mail_users', 'mail_addr'),
            $this->dropIndexByName('sql_user', 'sqlu_host')
        ]);

        $statements = [
            // Change column size for which primary key prefix length would be
            // bigger than 767 bytes when converting from utf8 to utf8mb4.
            $this->changeColumn('config', 'name', '`name` varchar(191) not null'),
            $this->changeColumn('login', 'session_id', '`session_id` varchar(191) not null'),
            $this->changeColumn('quotalimits', 'name', '`name` varchar(191) not null'),
            $this->changeColumn('quotatallies', 'name', '`name` varchar(191) not null'),

            // Re-create indexes with correct key prefix length.
            $this->addIndex('admin', 'admin_name(191)', 'unique', 'admin_name'),
            $this->addIndex('domain', 'domain_name(191)', 'unique'),
            $this->addIndex(
                'domain_aliasses', 'alias_name(191)', 'unique', 'alias_name'
            ),
            $this->addIndex(
                'domain_dns',
                [
                    'domain_id', 'alias_id', 'domain_dns(191)', 'domain_class',
                    'domain_type', 'domain_text(191)'
                ],
                'unique',
                'domain_dns'
            ),
            $this->addIndex('ftp_group', 'groupname(191)', 'unique', 'groupname'),
            $this->addIndex('ftp_users', 'userid(191)', 'unique', 'userid'),
            $this->addIndex('mail_users', 'mail_addr(191)', 'unique', 'mail_addr'),
            $this->addIndex('sql_user', 'sqlu_host(191)', 'index', 'sqlu_host'),

            // Change database character set and collation from utf8 to utf8mb4
            "
                ALTER DATABASE `{$this->databaseName}`
                CHARACTER SET utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];

        // Convert tables character set and collation from utf8 to utf8mb4
        foreach (
            [
                'admin', 'autoreplies_log', 'config', 'custom_menus', 'domain',
                'domain_aliasses', 'domain_dns', 'domain_traffic',
                'email_tpls', 'error_pages', 'ftp_group', 'ftp_users',
                'hosting_plans', 'htaccess', 'htaccess_groups',
                'htaccess_users', 'log', 'login', 'mail_users', 'php_ini',
                'plugin', 'quotalimits', 'quotatallies', 'reseller_props',
                'server_ips', 'server_traffic', 'sql_database', 'sql_user',
                'ssl_certs', 'subdomain', 'subdomain_alias', 'tickets',
                'user_gui_props', 'web_software', 'web_software_inst',
                'web_software_depot',
                'web_software_options'
            ] AS $table
        ) {
            $statements[] = "
                ALTER TABLE `$table`
                CONVERT TO CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci
            ";
        }

        return $statements;
    }

    /**
     * Add more indexes
     *
     * @çeturn SQL statements to be executed
     */
    protected function r282()
    {
        return [
            $this->addIndex(
                'admin', 'admin_status(15)', 'index', 'admin_status'
            ),
            $this->addIndex('autoreplies_log', 'from(191)', 'index', 'from'),
            $this->addIndex('autoreplies_log', 'to(191)', 'index', 'to'),
            $this->addIndex(
                'custom_menus', 'menu_level', 'index', 'menu_level'
            ),
            $this->addIndex(
                'domain_aliasses', 'alias_status(15)', 'index', 'alias_status'
            ),
            $this->addIndex(
                'domain_dns',
                'domain_dns_status(15)',
                'index',
                'domain_dns_status'
            ),
            $this->addIndex('email_tpls', 'owner_id', 'index', 'owner_id'),
            $this->addIndex('error_pages', 'user_id', 'index', 'user_id'),
            $this->addIndex('ftp_users', 'status(15)', 'index', 'status'),
            $this->addIndex(
                'hosting_plans', 'reseller_id', 'index', 'reseller_id'
            ),
            $this->addIndex('hosting_plans', 'status', 'index', 'status'),
            $this->addIndex('htaccess', 'dmn_id', 'index', 'dmn_id'),
            $this->addIndex('htaccess', 'status(15)', 'index', 'status'),
            $this->addIndex('htaccess_groups', 'dmn_id', 'index', 'dmn_id'),
            $this->addIndex(
                'htaccess_groups', 'status(15)', 'index', 'status'
            ),
            $this->addIndex('htaccess_users', 'dmn_id', 'index', 'dmn_id'),
            $this->addIndex('htaccess_users', 'status(15)', 'index', 'status'),
            $this->addIndex('login', 'lastaccess', 'index', 'lastaccess'),
            $this->addIndex('login', 'user_name(191)', 'index', 'user_name'),
            $this->addIndex(
                'plugin', 'plugin_status(15)', 'index', 'plugin_status'
            ),
            $this->addIndex(
                'plugin', 'plugin_error(15)', 'index', 'plugin_error'
            ),
            $this->addIndex(
                'server_ips', 'ip_status(15)', 'index', 'ip_status'
            ),
            $this->addIndex('ssl_certs', 'status(15)', 'index', 'status'),
            $this->addIndex(
                'subdomain',
                'subdomain_status(15)',
                'index',
                'subdomain_status'
            ),
            $this->addIndex(
                'subdomain_alias',
                'subdomain_alias_status(15)',
                'index',
                'subdomain_alias_status'
            ),
            $this->addIndex('tickets', 'ticket_from', 'index', 'ticket_from'),
            $this->addIndex('tickets', 'ticket_to', 'index', 'ticket_to'),
            $this->addIndex(
                'tickets', 'ticket_status', 'index', 'ticket_status'
            ),
            $this->addIndex(
                'web_software',
                'software_master_id',
                'index',
                'software_master_id'
            ),
            $this->addIndex(
                'web_software', 'reseller_id', 'index', 'reseller_id'
            ),
            $this->addIndex(
                'web_software_inst', 'domain_id', 'index', 'domain_id'
            ),
            $this->addIndex(
                'web_software_inst', 'alias_id', 'index', 'alias_id'
            ),
            $this->addIndex(
                'web_software_inst', 'subdomain_id', 'index', 'subdomain_id'
            ),
            $this->addIndex(
                'web_software_inst',
                'subdomain_alias_id',
                'index',
                'subdomain_alias_id'
            ),
            $this->addIndex(
                'web_software_inst',
                'software_master_id',
                'index',
                'software_master_id'
            )
        ];
    }

    /**
     * Add log.log_time index
     *
     * @return string|null SQL statement to be executed
     */
    protected function r283()
    {
        return $this->addIndex('log', 'log_time', 'index', 'log_time');
    }

    /**
     * Add login.ipaddr index
     *
     * @return string|null SQL statement to be executed
     */
    protected function r284()
    {
        return $this->addIndex('login', 'ipaddr', 'index', 'ipaddr');
    }

    /**
     * Delete TELNET service port
     *
     * return void
     */
    protected function r285()
    {
        if (isset($this->dbConfig['PORT_TELNET'])) {
            unset($this->dbConfig['PORT_TELNET']);
        }

        return NULL;
    }

    /**
     * Drop obsolete plugin.plugin_type column
     *
     * @return string|null
     */
    protected function r286()
    {
        return $this->dropColumn('plugin', 'plugin_type');
    }
    
    /**
     * Drop software installer
     * 
     * return array SQL statements to be executed
     */
    protected function r287()
    {
        $statements = [
            $this->dropColumn('domain', 'domain_software_allowed'),
            $this->dropColumn('reseller_props', 'software_allowed'),
            $this->dropColumn('reseller_props', 'softwaredepot_allowed'),
            $this->dropColumn('reseller_props', 'websoftwaredepot_allowed'),
            $this->dropTable('web_software'),
            $this->dropTable('web_software_inst'),
            $this->dropTable('web_software_depot'),
            $this->dropTable('web_software_options')
        ];

        $stmt = execute_query('SELECT id, props FROM hosting_plans');
        while ($row = $stmt->fetchRow()) {
            $row['props'] = explode(';', $row['props']);

            if (sizeof($row['props']) < 26) {
                continue;
            }

            unset($row['props'][12]);

            $statements[] = 'UPDATE hosting_plans'
                . ' SET props = ' . quoteValue(implode(';', $row['props']))
                . ' WHERE id = ' . quoteValue($row['id'], PDO::PARAM_INT);
        }

        return $statements;
    }
}
