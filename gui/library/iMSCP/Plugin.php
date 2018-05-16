<?php /** @noinspection ALL */
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * iMSCP_Plugin class
 *
 * Please, do not inherit from this class. Instead, inherit from the specialized classes localized into
 * gui/library/iMSCP/Plugin/
 */
abstract class iMSCP_Plugin
{
    /**
     * @var array Plugin configuration parameters
     */
    private $config = [];

    /**
     * @var array Plugin previous configuration parameters
     */
    private $configPrev = [];

    /**
     * @var bool TRUE if plugin configuration is loaded, FALSE otherwise
     */
    private $isLoadedConfig = false;

    /**
     * @var string Plugin name
     */
    private $pluginName;

    /**
     * @var string $Plungin name
     */
    private $pluginType;

    /**
     * @var iMSCP_Plugin_Manager
     */
    private $pluginManager;

    /**
     * Constructor
     *
     * @param iMSCP_Plugin_Manager $pluginManager
     */
    public function __construct(iMSCP_Plugin_Manager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
        $this->init();
    }

    /**
     * Get plugin manager
     *
     * @return iMSCP_Plugin_Manager
     */
    public function getPluginManager()
    {
        return $this->pluginManager;
    }

    /**
     * Returns plugin general information
     *
     * Need return an associative array with the following info:
     *
     * author: Plugin author name(s)
     * email: Plugin author email
     * version: Plugin version
     * require_api: Required i-MSCP plugin API version
     * date: Last modified date of the plugin in YYYY-MM-DD format
     * build: Last build of the plugin in YYYYMMDDNN format
     * name: Plugin name
     * desc: Plugin short description (text only)
     * url: Website in which it's possible to found more information about the plugin
     * priority: OPTIONAL priority which define priority for plugin backend processing
     *
     * A plugin can provide any other info for its own needs. However, the following keywords are reserved for internal
     * use:
     *
     *  __nversion__      : Contain the last available plugin version
     *  __installable__   : Tell the plugin manager whether or not the plugin is installable
     *  __uninstallable__ : Tell the plugin manager whether or not the plugin can be uninstalled
     * __need_change__    : Tell the plugin manager wheter or not the plugin need change
     * db_schema_version  : Contain the last applied plugin database migration
     *
     * @throws iMSCP_Plugin_Exception in case plugin info file cannot be read
     * @return array An array containing information about plugin
     */
    function getInfo()
    {
        $file = $this->getPluginManager()->pluginGetDirectory() . '/' . $this->getName() . '/info.php';
        $info = [];

        if (@is_readable($file)) {
            $info = include($file);
            iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call
        } else {
            if (file_exists($file)) {
                throw new iMSCP_Plugin_Exception(tr("Unable to read the %s file.", $file));
            }

            set_page_message(
                tr(
                    '%s::getInfo() not implemented and %s not found. This is a bug in the %s plugin which must be reported to the author(s).',
                    get_class($this),
                    $file,
                    $this->getName()
                ),
                'warning'
            );

        }

        return array_merge(
            [
                'author'      => tr('Unknown'),
                'email'       => '',
                'version'     => '0.0.0',
                'require_api' => '99.0.0',
                'date'        => '0000-00-00',
                'build'       => '0000000000',
                'name'        => $this->getName(),
                'desc'        => tr('Not provided'),
                'url'         => ''
            ],
            $info
        );
    }

    /**
     * Returns plugin type
     *
     * @return string
     */
    final public function getType()
    {
        if (NULL === $this->pluginType) {
            list(, , $this->pluginType) = explode('_', get_parent_class($this), 3);
        }

        return $this->pluginType;
    }

    /**
     * Returns plugin name
     *
     * @return string
     */
    final public function getName()
    {
        if (NULL === $this->pluginName) {
            list(, , $this->pluginName) = explode('_', get_class($this), 3);
        }

        return $this->pluginName;
    }

    /**
     * Return plugin configuration
     *
     * @return array An associative array which contain plugin configuration
     */
    final public function getConfig()
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return $this->config;
    }

    /**
     * Return previous plugin configuration
     *
     * @return array An associative array which contain plugin previous configuration
     */
    final public function getConfigPrev()
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return $this->configPrev;
    }

    /**
     * Return plugin configuration from file
     *
     * @throws iMSCP_Plugin_Exception in case plugin configuration file is not readable
     * @return array
     */
    final public function getConfigFromFile()
    {
        $this->isLoadedConfig = false;
        $pluginName = $this->getName();
        $file = $this->getPluginManager()->pluginGetDirectory() . "/$pluginName/config.php";
        $config = [];

        if (!file_exists($file)) {
            return $config;
        }

        if (!@is_readable($file)) {
            throw new iMSCP_Plugin_Exception(
                tr('Unable to read the plugin %s file. Please check file permissions', $file)
            );
        }

        $config = include($file);
        iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call

        # See https://wiki.i-mscp.net/doku.php?id=plugins:configuration

        $file = PERSISTENT_PATH . "/plugins/$pluginName.php";

        if (@is_readable($file)) {
            $localConfig = include($file);
            iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call

            // Remove item(s) first (if needed)
            if (array_key_exists('__REMOVE__', $localConfig)) {
                if (is_array($localConfig['__REMOVE__'])) {
                    $config = utils_arrayDiffRecursive($config, $localConfig['__REMOVE__']);
                }

                unset($localConfig['__REMOVE__']);
            }

            $config = utils_arrayMergeRecursive($config, $localConfig);
        }

        return $config;
    }

    /**
     * Returns the given plugin configuration
     *
     * @param string $paramName Configuration parameter name
     * @param mixed $default Default value returned in case $paramName is not found
     * @return mixed Configuration parameter value or $default if $paramName not found
     */
    final public function getConfigParam($paramName, $default = NULL)
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return isset($this->config[$paramName]) ? $this->config[$paramName] : $default;
    }

    /**
     * Returns the given previous plugin configuration
     *
     * @param string $paramName Configuration parameter name
     * @param mixed $default Default value returned in case $paramName is not found
     * @return mixed Configuration parameter value or $default if $paramName not found
     */
    final public function getConfigPrevParam($paramName, $default = NULL)
    {
        if (!$this->isLoadedConfig) {
            $this->loadConfig();
        }

        return isset($this->configPrev[$paramName]) ? $this->configPrev[$paramName] : $default;
    }

    /**
     * Load plugin configuration from database
     *
     * @return void
     */
    final protected function loadConfig()
    {
        $stmt = exec_query(
            'SELECT plugin_config, plugin_config_prev FROM plugin WHERE plugin_name = ?', $this->getName()
        );

        if ($stmt->rowCount()) {
            $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
            $this->config = json_decode($row['plugin_config'], true);
            $this->configPrev = json_decode($row['plugin_config_prev'], true);
            $this->isLoadedConfig = true;
            return;
        }

        $this->config = [];
        $this->configPrev = [];
    }

    /**
     * Allow plugin initialization
     *
     * This method allow to do some initialization tasks without overriding the constructor.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Plugin installation
     *
     * This method is automatically called by the plugin manager when the plugin is being installed.
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function install(iMSCP_Plugin_Manager $pluginManager)
    {
    }

    /**
     * Plugin activation
     *
     * This method is automatically called by the plugin manager when the plugin is being enabled (activated).
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function enable(iMSCP_Plugin_Manager $pluginManager)
    {
    }

    /**
     * Plugin deactivation
     *
     * This method is automatically called by the plugin manager when the plugin is being disabled (deactivated).
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function disable(iMSCP_Plugin_Manager $pluginManager)
    {
    }

    /**
     * Plugin update
     *
     * This method is automatically called by the plugin manager when the plugin is being updated.
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @param string $fromVersion Version from which plugin update is initiated
     * @param string $toVersion Version to which plugin is updated
     * @return void
     */
    public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
    {
    }

    /**
     * Plugin uninstallation
     *
     * This method is automatically called by the plugin manager when the plugin is being uninstalled.
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function uninstall(iMSCP_Plugin_Manager $pluginManager)
    {
    }

    /**
     * Plugin deletion
     *
     * This method is automatically called by the plugin manager when the plugin is being deleted.
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function delete(iMSCP_Plugin_Manager $pluginManager)
    {
    }

    /**
     * Get plugin item with error status
     *
     * This method is called by the i-MSCP debugger.
     *
     * Note: *MUST* be implemented by any plugin which manage its own items.
     *
     * @return array
     */
    public function getItemWithErrorStatus()
    {
        return [];
    }

    /**
     * Set status of the given plugin item to 'tochange'
     *
     * This method is called by the i-MSCP debugger.
     *
     * Note: *MUST* be implemented by any plugin which manage its own items.
     *
     * @param string $table Table name
     * @param string $field Status field name
     * @param int $itemId item unique identifier
     * @return void
     */
    public function changeItemStatus($table, $field, $itemId)
    {
    }

    /**
     * Return count of request in progress
     *
     * This method is called by the i-MSCP debugger.
     *
     * Note: *MUST* be implemented by any plugin which manage its own items.
     *
     * @return int
     */
    public function getCountRequests()
    {
        return 0;
    }

    /**
     * Migrate plugin database schema
     *
     * This method provide a convenient way to alter plugins's database schema over the time in a consistent and easy
     * way.
     *
     * This method considers each migration as being a new 'version' of the database schema. A schema starts off with
     * nothing in it, and each migation modifies it to add or remove tables, columns, or entries. Each time a new
     * migration is applied, the 'db_schema_version' info field is updated. This allow to keep track of the last applied
     * database migration.
     *
     * This method can work in both senses update (up) and downgrade (down) modes.
     *
     * USAGE:
     *
     * Any plugin which uses this method *MUST* provide an sql directory at the root of its directory, which contain all
     * migration files.
     *
     * Migration file naming convention:
     *
     * Each migration file must be named using the following naming convention:
     *
     * <version>_<description>.php where:
     *
     * - <version> is the migration version number such as 003
     * - <description> is the migration description such as add_version_confdir_path_prev
     *
     * Resulting to the following migration file:
     *
     * 003_add_version_confdir_path_prev.php
     *
     * Note: version of first migration file *MUST* start to 001 and not 000.
     *
     * Migration file structure:
     *
     * A migration file is a simple PHP file which return an associative array containing exactly two pairs of key/value:
     *
     * - The 'up' key for which the value must be the SQL statement to be executed in the 'up' mode
     * - The 'down' key for which the value must be the SQL statement to be executed in the 'down' mode
     *
     * If one of these keys is missing, the migrateDb method won't complain and will simply continue its work normally.
     * However, it's greatly recommended to always provide both SQL statements as described above.
     *
     * Sample:
     *
     * <code>
     * return array(
     *     'up' => '
     *         ALTER TABLE
     *             php_switcher_version
     *         ADD
     *             version_confdir_path_prev varchar(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL
     *         AFTER
     *             version_binary_path
     *      ',
     *      'down' => '
     *          ALTER TABLE php_switcher_version DROP COLUMN version_confdir_path_prev
     *      '
     * );
     * </code>
     *
     * @throws iMSCP_Plugin_Exception When an error occurs
     * @param string $migrationMode Migration mode (up|down)
     * @return void
     */
    protected function migrateDb($migrationMode = 'up')
    {
        ignore_user_abort(true);

        $pluginName = $this->getName();
        $pluginManager = $this->getPluginManager();
        $sqlDir = $pluginManager->pluginGetDirectory() . '/' . $pluginName . '/sql';

        if (!@is_dir($sqlDir)) {
            throw new iMSCP_Plugin_Exception(tr("Directory %s doesn't exists.", $sqlDir));
        }

        $pluginInfo = $pluginManager->pluginGetInfo($pluginName);
        $dbSchemaVersion = isset($pluginInfo['db_schema_version']) ? $pluginInfo['db_schema_version'] : '000';
        $migrationFiles = [];

        /** @var $migrationFileInfo DirectoryIterator */
        foreach (new DirectoryIterator($sqlDir) as $migrationFileInfo) {
            if (!$migrationFileInfo->isDot()) {
                $migrationFiles[] = $migrationFileInfo->getRealPath();
            }
        }

        natsort($migrationFiles);

        if ($migrationMode == 'down') {
            $migrationFiles = array_reverse($migrationFiles);
        }

        $db = iMSCP_Database::getInstance();

        try {
            foreach ($migrationFiles as $migrationFile) {
                if (!@is_readable($migrationFile)) {
                    throw new iMSCP_Plugin_Exception(tr('Migration file %s is not readable.', $migrationFile));
                }

                if (!preg_match('/(\d+)_[^\/]+\.php$/i', $migrationFile, $version)) {
                    throw new iMSCP_Plugin_Exception(tr("File %s doesn't look like a migration file.", $migrationFile));
                }

                if (($migrationMode == 'up' && $version[1] > $dbSchemaVersion)
                    || ($migrationMode == 'down' && $version[1] <= $dbSchemaVersion)
                ) {
                    $migrationFilesContent = include($migrationFile);
                    if (isset($migrationFilesContent[$migrationMode])) {
                        $stmt = $db->prepare($migrationFilesContent[$migrationMode]);
                        $db->execute($stmt);
                        while ($stmt->nextRowset()) {
                            /* https://bugs.php.net/bug.php?id=61613 */
                        };
                    }

                    $dbSchemaVersion = $version[1];
                }
            }

            $pluginInfo['db_schema_version'] = ($migrationMode == 'up') ? $dbSchemaVersion : '000';
            $pluginManager->pluginUpdateInfo($pluginName, $pluginInfo->toArray());
        } catch (Exception $e) {
            $pluginInfo['db_schema_version'] = $dbSchemaVersion;
            $pluginManager->pluginUpdateInfo($pluginName, $pluginInfo->toArray());
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}
