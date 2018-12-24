<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * @noinspection PhpUnhandledExceptionInspection PhpDocMissingThrowsInspection
 */

/**
 * iMSCP_Plugin class
 *
 * Please, do not inherit from this class. Instead, inherit from the
 * specialized classes localized into gui/library/iMSCP/Plugin/
 */
abstract class iMSCP_Plugin
{
    /**
     * @var string Plugin name
     */
    protected $pluginName;

    /**
     * @var array Plugin info
     */
    protected $info;

    /**
     * @var array Plugin configuration
     */
    protected $config;

    /**
     * @var array Plugin previous configuration
     */
    protected $configPrev;

    /**
     * @var iMSCP_Plugin_Manager
     */
    protected $pm;

    /**
     * Constructor
     *
     * @param iMSCP_Plugin_Manager $pm
     */
    public function __construct(iMSCP_Plugin_Manager $pm)
    {
        $this->pm = $pm;
        $this->init();
    }

    /**
     * Get plugin manager
     *
     * @return iMSCP_Plugin_Manager
     */
    public function getPluginManager()
    {
        return $this->pm;
    }

    /**
     * Returns plugin name
     *
     * @return string
     */
    final public function getName()
    {
        if (NULL === $this->pluginName) {
            $this->pluginName = explode('_', get_class($this), 3)[2];
        }

        return $this->pluginName;
    }

    /**
     * Returns plugin info from plugin info.php file
     *
     * Plugin info.php file need return an associative array with the following
     * info:
     *
     * name: Plugin name
     * desc: Plugin short description (text only)
     * url: Plugin site (default: https://www.i-mscp.net)
     * author: Plugin author name(s) (default: i-MSCP Team)
     * email: Plugin author email (default: team@i-mscp.net)
     * version: Plugin version
     * build: Last build of the plugin in YYYYMMDDNN format
     * require_api: Required i-MSCP plugin API version
     * priority: Plugin processing priority (default: 0)
     *
     * A plugin can provide any other info for its own needs. However, the
     * following keywords are reserved for internal use:
     *
     *  __nversion__      : Plugin newest version
     *  __nbuild__        : Plugin newest build
     *  __installable__   : Whether or not the plugin is installable
     *  __uninstallable__ : Whether or not the plugin can be uninstalled
     *  __migration__     : Last applied database migration if any
     *
     * @return array
     */
    public function getInfoFromFile()
    {
        $file = $this->getPluginManager()->pluginGetRootDir() . '/' . $this->getName() . '/info.php';

        if (!@is_readable($file)) {
            throw new iMSCP_Plugin_Exception(tr("Couldn't read the %s plugin info.php file.", $this->getName()));
        }

        // Be sure to load newest version on next call
        iMSCP_Utility_OpcodeCache::clearAllActive($file);

        return array_merge(
            [
                'url'      => 'https://www.i-mscp.net',
                'author'   => 'i-MSCP Team',
                'email'    => 'team@i-mscp.net',
                'priority' => 0
            ],
            include $file
        );
    }

    /**
     * Return plugin info from database (or from plugin info.php file if new plugin or no data found)
     *
     * @return array
     */
    public function &getInfo()
    {
        if (NULL === $this->info) {
            if (!$this->getPluginManager()->pluginIsKnown($this->getName())) {
                $this->info = $this->getInfoFromFile();
            } else {
                $stmt = exec_query('SELECT plugin_info FROM plugin WHERE plugin_name = ?', $this->getName());
                $this->info = $stmt->rowCount() ? json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true) : $this->getInfoFromFile();
            }
        }

        return $this->info;
    }

    /**
     * Return plugin configuration from plugin config.php file (and local configuration file if any)
     *
     * @return array
     */
    public function getConfigFromFile()
    {
        $pm = $this->getPluginManager();
        $pluginName = $this->getName();
        $file = $this->getPluginManager()->pluginGetRootDir() . "/$pluginName/config.php";
        $config = [];

        if (!file_exists($file)) {
            return $config;
        }

        if (!@is_readable($file)) {
            throw new iMSCP_Plugin_Exception(tr('Unable to read the plugin %s file. Please check file permissions', $file));
        }

        $config = include $file;
        iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call

        # See https://wiki.i-mscp.net/doku.php?id=plugins:configuration
        $file = $pm->pluginGetPersistentDataDir() . "/plugins/$pluginName.php";
        if (@is_readable($file)) {
            $localConfig = include $file;
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
     * Return plugin config from database (or from plugin config.php file if new plugin or no data found)
     *
     * @return array
     */
    public function &getConfig()
    {
        if (NULL === $this->config) {
            if (!$this->getPluginManager()->pluginIsKnown($this->getName())) {
                $this->config = $this->getConfigFromFile();
            } else {
                $stmt = exec_query('SELECT plugin_config FROM plugin WHERE plugin_name = ?', $this->getName());
                $this->config = $stmt->rowCount() ? json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true) : $this->getConfigFromFile();
            }
        }

        return $this->config;
    }

    /**
     * Return plugin previous config from database (or plugin config if new plugin or no data found)
     *
     * @return array
     */
    public function &getConfigPrev()
    {
        if (NULL === $this->configPrev) {
            if (!$this->getPluginManager()->pluginIsKnown($this->getName())) {
                $this->configPrev = $this->getConfig();
            } else {
                $stmt = exec_query('SELECT plugin_config_prev FROM plugin WHERE plugin_name = ?', $this->getName());
                $this->configPrev = $stmt->rowCount() ? json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true) : $this->getConfig();
            }
        }

        return $this->configPrev;
    }

    /**
     * Returns the given plugin configuration
     *
     * @param string $param Configuration parameter name
     * @param mixed $default Default value returned in case $paramName is not found
     * @return mixed Configuration parameter value or $default if $paramName not found
     */
    public function getConfigParam($param, $default = NULL)
    {
        $config =& $this->getConfig();
        return isset($config[$param]) ? $config[$param] : $default;
    }

    /**
     * Returns the given previous plugin configuration
     *
     * @param string $param Configuration parameter name
     * @param mixed $default Default value returned if $paramName is not found
     * @return mixed Configuration parameter value
     */
    public function getConfigPrevParam($param, $default = NULL)
    {
        $configPrev =& $this->getConfigPrev();
        return isset($configPrev[$param]) ? $configPrev[$param] : $default;
    }

    /**
     * Allow plugin initialization
     *
     * This method allows to do some initialization tasks without
     * overriding the constructor.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Plugin installation
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being installed.
     *
     * @param iMSCP_Plugin_Manager $pm
     * @return void
     */
    public function install(iMSCP_Plugin_Manager $pm)
    {
    }

    /**
     * Plugin activation
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being enabled (activated).
     *
     * @param iMSCP_Plugin_Manager $pm
     * @return void
     */
    public function enable(iMSCP_Plugin_Manager $pm)
    {
    }

    /**
     * Plugin deactivation
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being disabled (deactivated).
     *
     * @param iMSCP_Plugin_Manager $pm
     * @return void
     */
    public function disable(iMSCP_Plugin_Manager $pm)
    {
    }

    /**
     * Plugin update
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being updated.
     *
     * @param iMSCP_Plugin_Manager $pm
     * @param string $fromVersion Version from which plugin update is initiated
     * @param string $toVersion Version to which plugin is updated
     * @return void
     */
    public function update(iMSCP_Plugin_Manager $pm, $fromVersion, $toVersion)
    {
    }

    /**
     * Plugin uninstallation
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being uninstalled.
     *
     * @param iMSCP_Plugin_Manager $pm
     * @return void
     */
    public function uninstall(iMSCP_Plugin_Manager $pm)
    {
    }

    /**
     * Plugin deletion
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being deleted.
     *
     * @param iMSCP_Plugin_Manager $pm
     * @return void
     */
    public function delete(iMSCP_Plugin_Manager $pm)
    {
    }

    /**
     * Get plugin item with error status
     *
     * This method is called by the i-MSCP debugger.
     *
     * Note: *SHOULD* be implemented by plugins that manage their own items.
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
     * Note: *SHOULD* be implemented by plugins that manage their own items.
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
     * Note: *SHOULD* be implemented by plugins that manage own items.
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
     * This method provide a convenient way to alter plugins's database schema
     * over the time in a consistent and easy way.
     *
     * This method considers each migration as being a new 'version' of the
     * database schema. A schema starts off with nothing in it, and each
     * migration modifies it to add or remove tables, columns, or entries. Each
     * time a new migration is applied, the '__migration__' info field is
     * updated. This allow to keep track of the last applied database
     * migration.
     *
     * This method can work in both senses update (up) and downgrade (down)
     * modes.
     *
     * USAGE:
     *
     * Any plugin which uses this method *MUST* provide an 'sql' directory at
     * the root of its directory, which contain all migration files.
     *
     * Migration file naming convention:
     *
     * Each migration file must be named using the following naming convention:
     *
     * <version>_<description>.php where:
     *
     * - <version> is the migration version
     * - <description> is the migration description
     *
     * Note: version of first migration file *MUST* start to 001 and not 000.
     *
     * Migration file structure:
     *
     * A migration file is a simple PHP file which return an associative array
     * containing exactly two pairs of key/value:
     *
     * - The 'up' key for which the value must be the SQL statement to be
     *   executed in the 'up' mode
     * - The 'down' key for which the value must be the SQL statement to be
     *   executed in the 'down' mode
     *
     * If one of these keys is missing, the migrateDb method won't complain and
     * will simply continue its work normally.
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
     * @throws iMSCP_Plugin_Exception on error
     * @param string $mode Migration mode (up|down)
     * @return void
     */
    public function migrateDb($mode = 'up')
    {
        if (!in_array($mode, ['up', 'down'])) {
            throw new iMSCP_Plugin_Exception('Unknown migration mode');
        }

        $pluginName = $this->getName();
        $pm = $this->getPluginManager();
        $sqlDir = $pm->pluginGetRootDir() . '/' . $pluginName . '/sql';

        if (!@is_dir($sqlDir)) {
            throw new iMSCP_Plugin_Exception(tr("Directory %s doesn't exists.", $sqlDir));
        }

        $pluginInfo = $this->getInfo();
        $migration = isset($pluginInfo['__migration__']) ? $pluginInfo['__migration__'] : '000';
        $migrations = [];

        foreach (new RegexIterator(new DirectoryIterator($sqlDir), '/(\d+)_.+\.php$/i', RegexIterator::GET_MATCH) as $v) {
            $migrations[$v[1]] = $sqlDir . '/' . $v[0];
        }
        natsort($migrations);
        $migrations = array_filter($migrations, function ($v) use ($mode, $migration) {
            return $mode == 'up' ? $v > $migration : $v < $migration;
        }, ARRAY_FILTER_USE_KEY);

        if ($mode == 'down') {
            $migrations = array_reverse($migrations);
        }

        $db = iMSCP_Database::getInstance();

        try {
            ignore_user_abort(true);

            foreach ($migrations as $migration => $file) {
                if (!@is_readable($file)) {
                    throw new iMSCP_Plugin_Exception(tr('Migration file %s is not readable.', $file));
                }

                $data = include $file;

                if (!isset($data[$mode])) {
                    continue;
                }

                $stmt = $db->prepare($data[$mode]);
                $db->execute($stmt);
                /** @noinspection PhpStatementHasEmptyBodyInspection */
                while ($stmt->nextRowset()) {
                    /* https://bugs.php.net/bug.php?id=61613 */
                };
            }

            if ($mode == 'up') {
                $pluginInfo['__migration__'] = $migration;
            } else {
                unset($pluginInfo['__migration__']);
            }

            $pm->pluginUpdateInfo($pluginName, $pluginInfo);
        } catch (Exception $e) {
            // On error set __migration__ field to last successfully applied migration
            $pluginInfo['__migration__'] = printf("%'.03d", $mode == 'up' ? --$migration : ++$migration);
            $pm->pluginUpdateInfo($pluginName, $pluginInfo);
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}
