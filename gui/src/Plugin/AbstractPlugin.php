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
 * PhpIncludeInspection
 * PhpUnused
 */

declare(strict_types=1);

namespace iMSCP\Plugin;

use DirectoryIterator;
use iMSCP\Database\DatabaseMySQL;
use iMSCP\Event\EventManagerInterface;
use iMSCP\ServiceProviderInterface;
use iMSCP\Utility\OpcodeCache;
use PDO;
use Throwable;

/**
 * Class AbstractPlugin
 *
 * New base class for i-MSCP plugins.
 *
 * @package iMSCP\Plugin
 */
abstract class AbstractPlugin
{
    /**
     * @var string Plugin name
     */
    protected $pluginName;

    /**
     * @var array Plugin info
     */
    protected $pluginInfo;

    /**
     * @var array Plugin configuration
     */
    protected $pluginConfig;

    /**
     * @var array Plugin previous configuration
     */
    protected $pluginConfigPrev;

    /**
     * @var PluginManager
     */
    protected $pluginManager;

    /**
     * Constructor
     *
     * @param PluginManager $pluginManager
     */
    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
        $this->init();
    }

    /**
     * Plugin initialization tasks.
     *
     * This method allows to perform additional initialization tasks without
     * overriding the constructor.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Returns plugin name
     *
     * @return string
     * @throws PluginException on failure
     */
    public function getName()
    {
        if (NULL === $this->pluginName) {
            $class = get_class($this);

            if ((false !== $pos = strrpos($class, '\\'))
                || (false !== $pos = strrpos($class, '_'))
            ) {
                $this->pluginName = substr($class, $pos + 1);
            } else {
                throw new PluginException(
                    "Couldn't retrieve plugin name from the plugin class name."
                );
            }
        }

        return $this->pluginName;
    }

    /**
     * Return plugin info from the database, or from the plugin info.php file if
     * no data were found in database.
     *
     * @return array
     */
    public function getInfo()
    {
        if (NULL === $this->pluginInfo) {
            if (!$this->getPluginManager()->pluginIsKnown($this->getName())) {
                $this->pluginInfo = $this->getInfoFromFile();
            } else {
                $stmt = exec_query(
                    '
                        SELECT `plugin_info`
                        FROM `plugin`
                        WHERE `plugin_name` = ?
                    ',
                    [$this->getName()]
                );
                $this->pluginInfo = $stmt->rowCount()
                    ? json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true)
                    : $this->getInfoFromFile();
            }
        }

        return $this->pluginInfo;
    }

    /**
     * Returns plugin info from the plugin info.php file.
     *
     * Plugin info.php file need to return an associative array with the
     * following key-value pairs:
     *  - name        : Plugin name (required)
     *  - desc        : Plugin short description (required)
     *  - url         : Plugin site (default: https://www.i-mscp.net)
     *  - author      : Plugin author name(s) (default: i-MSCP Team)
     *  - email       : Plugin author email (default: team@i-mscp.net)
     *  - version     : Plugin version (required)
     *  - build       : Last build of the plugin in YYYYMMDDNN format (required)
     *  - require_api : Required i-MSCP plugin API version (required)
     *  - priority    : Plugin processing priority (default: 0)
     *
     * A plugin can also provide other info for its own needs. However, the
     * following keys are reserved for internal use:
     *   - __nversion__      : Plugin newest version
     *   - __nbuild__        : Plugin newest build
     *   - __installable__   : Whether or not the plugin is installable
     *   - __uninstallable__ : Whether or not the plugin can be uninstalled
     *   - __migration__     : Last applied database migration if any
     *
     * @return array
     */
    public function getInfoFromFile()
    {
        $file = $this->getPluginManager()->pluginGetRootDir()
            . DIRECTORY_SEPARATOR . $this->getName() . DIRECTORY_SEPARATOR
            . 'info.php';

        if (!@is_readable($file)) {
            throw new PluginException(sprintf(
                "Couldn't read the %s plugin info.php file.", $this->getName()
            ));
        }

        // Be sure to load newest version on next call
        OpcodeCache::clearAllActive($file);

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
     * Return the plugin configuration from the plugin config.php file, and
     * local configuration file if any.
     *
     * @return array
     */
    public function getConfigFromFile()
    {
        $pluginManager = $this->getPluginManager();
        $pluginName = $this->getName();
        $pluginConfigFile = $this->getPluginManager()->pluginGetRootDir()
            . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR
            . 'config.php';
        $pluginConfig = [];

        if (!file_exists($pluginConfigFile)) {
            return $pluginConfig;
        }

        if (!@is_readable($pluginConfigFile)) {
            throw new PluginException(sprintf(
                "Couldn't read read the plugin %s file. Please fix the file permissions",
                $pluginConfigFile
            ));
        }

        $pluginConfig = include $pluginConfigFile;

        // Be sure to load newest version on next call
        OpcodeCache::clearAllActive($pluginConfigFile);

        # See https://wiki.i-mscp.net/doku.php?id=plugins:configuration
        $file = $pluginManager->pluginGetPersistentDataDir()
            . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR
            . "$pluginName.php";

        if (@is_readable($file)) {
            $localConfig = include $file;
            // Be sure to load newest version on next call
            OpcodeCache::clearAllActive($file);

            // Remove item(s) first (if needed)
            if (array_key_exists('__REMOVE__', $localConfig)) {
                if (is_array($localConfig['__REMOVE__'])) {
                    $pluginConfig = utils_arrayDiffRecursive(
                        $pluginConfig, $localConfig['__REMOVE__']
                    );
                }

                unset($localConfig['__REMOVE__']);
            }

            $pluginConfig = utils_arrayMergeRecursive($pluginConfig, $localConfig);
        }

        return $pluginConfig;
    }

    /**
     * Return the plugin configuration from the database, or from the plugin
     * config.php file if no data were found in the database.
     *
     * @return array
     * @throws PluginException on failure
     */
    public function getConfig()
    {

        if (NULL === $this->pluginConfig) {
            try {
                if ($this->getPluginManager()->pluginIsKnown($this->getName())) {
                    $stmt = exec_query(
                        'SELECT plugin_config FROM plugin WHERE plugin_name = ?',
                        $this->getName()
                    );
                    $this->pluginConfig = $stmt->rowCount()
                        ? json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true)
                        : $this->getConfigFromFile();
                } else {
                    $this->pluginConfig = $this->getConfigFromFile();
                }
            } catch (Throwable $e) {
                throw new PluginException(sprintf(
                    "Couldn't get plugin configuration: %s", $e->getMessage()
                ));
            }
        }
        return $this->pluginConfig;
    }

    /**
     * Returns the given plugin configuration parameter.
     *
     * @param string $param Configuration parameter name
     * @param mixed $default Default value returned if $param is not found
     * @return mixed Configuration parameter value or $default if $param not
     *               found
     * @throws PluginException on failure
     */
    public function getConfigParam(string $param, $default = NULL)
    {
        $pluginConfig = $this->getConfig();

        return isset($pluginConfig[$param]) ? $pluginConfig[$param] : $default;
    }

    /**
     * Return the plugin previous configuration from the database, or from the
     * plugin configuration file if no data were found in the database.
     *
     * @return array
     * @throws PluginException on failure
     */
    public function getConfigPrev()
    {
        if (NULL === $this->pluginConfigPrev) {
            try {
                if ($this->getPluginManager()->pluginIsKnown($this->getName())) {
                    $stmt = exec_query(
                        '
                        SELECT `plugin_config_prev`
                        FROM `plugin`
                        WHERE `plugin_name` = ?
                    ',
                        [$this->getName()]
                    );
                    $this->pluginConfigPrev = $stmt->rowCount()
                        ? json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true)
                        : $this->getConfig();
                } else {
                    $this->pluginConfigPrev = $this->getConfig();
                }
            } catch (Throwable $e) {
                throw new PluginException(sprintf(
                    "Couldn't get plugin previous configuration: %s",
                    $e->getMessage()
                ));
            }
        }

        return $this->pluginConfigPrev;
    }

    /**
     * Returns the given previous plugin configuration.
     *
     * @param string $param Configuration parameter name
     * @param mixed $default Default value returned if $param is not found
     * @return mixed Configuration parameter value
     * @throws PluginException on failure
     */
    public function getConfigPrevParam(string $param, $default = NULL)
    {
        $pluginConfigPrev = $this->getConfigPrev();

        return isset($pluginConfigPrev[$param])
            ? $pluginConfigPrev[$param] : $default;
    }

    /**
     * Get plugin manager
     *
     * @return PluginManager
     */
    final public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    /**
     * Register the plugin event listeners
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function register(EventManagerInterface $events)
    {
    }

    /**
     * Return the plugin routes.
     *
     * Old (deprecated) way (prior plugin API 1.5.1)
     * <code>
     * return [
     *  // Plugin API < 1.5.1 (deprecated)
     *
     *  // This is the former and the very basic method to add plugin routes.
     *  //
     *  // The key represents the route path, and the value, the route
     *  // handler, that is, the PHP script that is responsible to handle
     *  // the request. Note that named placeholders are not supported.
     *  //
     *  // This method will be removed in next i-MSCP minor version (1.5.4).
     *  '/client/SamplePlugin/HelloWorld' => __DIR__ . '/frontend/hello_world.php',
     *
     *   //// Plugin API >= 1.5.1
     *
     *   // Array defining a single route
     *   [
     *     // Route URI pattern (REQUIRED)
     *     'pattern'    => '/route/uri/pattern',
     *     // List of allowed HTTP methods for this route (OPTIONAL)
     *     // If not provided, all HTTP methods will be allowed.
     *     // Allowed methods: GET, POST, PUT, DELETE, PATCH
     *     'method'     => ['GET'],
     *     // The route (request) handler (REQUIRED).
     *     'handler'    => RequestHandler::class,
     *     // List of route middleware (OPTIONAL)
     *     'middleware' => [
     *        FirstMiddleware::class,
     *        SecondMiddleware:class
     *     ],
     *     // The route name (OPTIONAL)
     *     'name'       => 'route_name'
     *   ],
     *   ...
     *   // Array defining a route group
     *   [
     *     'pattern' => '/group/route/uri/pattern',
     *     // List of arrays defining single route
     *     'routes' [
     *       ...
     *     ]
     *   ]
     *   ...
     * ];
     * </code>
     *
     * See also:
     * - http://www.slimframework.com/docs/v3/objects/router.html
     * - http://www.slimframework.com/docs/v3/concepts/middleware.html
     *
     * @return array An array containing action script paths
     */
    public function getRoutes()
    {
        return [];
    }

    /**
     * Return the plugin service provider (since plugin API 1.5.1)
     *
     * A plugin service provider make it possible to prepare, manage and
     * inject the plugin dependencies.
     *
     * See also: http://www.slimframework.com/docs/v3/concepts/di.html
     *
     * @return ServiceProviderInterface|null
     */
    public function getServiceProvider(): ?ServiceProviderInterface
    {
        return NULL;
    }

    /**
     * Route an URL
     *
     * This method allow the plugin to provide its own routing logic. If a
     * route match the given URL, this method MUST return a string representing
     * the action script to load, else, NULL must be returned. For instance:
     *
     * <code>
     * if (strpos($urlComponents['path'], '/mydns/api/') === 0) {
     *  return $this->getPluginManager()->pluginGetDirectory() . '/'
     *   . $this->getName() . '/api.php';
     * }
     *
     * return null;
     * </code>
     *
     * @param array $urlComponents Associative array containing URL components
     * @return string|null Either a string representing an action script path
     *                     or null if not route match the URL
     * @deprecated since v1.5.3 (build 2019*) - backward compatibility ensured
     *             through duck-typing in the iMSCP\Plugin\PluginRoutesInjector
     */
    /*
    public function route( $urlComponents)
    {
        return NULL;
    }
    */

    /**
     * Plugin installation tasks.
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being installed.
     *
     * On failure, this method *MUST* throw an iMSCP\Plugin\PluginException
     *
     * @param PluginManager $pluginManager
     * @return void
     */
    public function install(PluginManager $pluginManager)
    {
    }

    /**
     * Plugin uninstallation tasks.
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being uninstalled.
     *
     * On failure, this method *MUST* throw an iMSCP\Plugin\PluginException
     *
     * @param PluginManager $pluginManager
     * @return void
     */
    public function uninstall(PluginManager $pluginManager)
    {
    }

    /**
     * Plugin deletion tasks.
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being deleted.
     *
     * On failure, this method *MUST* throw an iMSCP\Plugin\PluginException
     *
     * @param PluginManager $pluginManager
     * @return void
     */
    public function delete(PluginManager $pluginManager)
    {
    }

    /**
     * Plugin update tasks.
     *
     * This method is automatically called by the plugin manager when
     * the plugin is being updated.
     *
     * On failure, this method *MUST* throw an iMSCP\Plugin\PluginException
     *
     * @param PluginManager $pluginManager
     * @param string $fromVersion Version from which plugin update is initiated
     * @param string $toVersion Version to which plugin is updated
     * @return void
     */
    public function update(
        PluginManager $pluginManager, string $fromVersion, string $toVersion
    )
    {
    }

    /**
     * Plugin activation tasks.
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being enabled (activated).
     *
     * On failure, this method *MUST* throw an iMSCP\Plugin\PluginException
     *
     * @param PluginManager $pm
     * @return void
     */
    public function enable(PluginManager $pm)
    {
    }

    /**
     * Plugin deactivation tasks.
     *
     * This method is automatically called by the plugin manager when the
     * plugin is being disabled (deactivated).
     *
     * On failure, this method *MUST* throw an iMSCP\Plugin\PluginException
     *
     * @param PluginManager $pm
     * @return void
     */
    public function disable(PluginManager $pm)
    {
    }

    /**
     * Get plugin item with error status.
     *
     * This method is called by the i-MSCP debugger and *MUST* be implemented
     * by any plugin which manage its own items.
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
     * @param string $itemId item unique identifier
     * @return void
     */
    public function changeItemStatus(
        string $table, string $field, string $itemId
    )
    {
    }

    /**
     * Return count of request in progress.
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
     * Migrate plugin database schema.
     *
     * This method provide a convenient way to alter plugins's database schema
     * over the time in a consistent and easy way.
     *
     * This method considers each migration as being a new 'version' of the
     * database schema. A schema starts off with nothing in it, and each
     * migration modifies it to add or remove tables, columns, or entries. Each
     * time a new migration is applied, the 'db_schema_version' info field is
     * updated. This allow to keep track of the last applied database
     * migration.
     *
     * This method can work in both senses update (up) and downgrade (down) modes.
     *
     * USAGE:
     *
     * Any plugin which uses this method *MUST* provide an sql directory at the
     * root of its directory, which contain all migration files.
     *
     * Migration file naming convention:
     *
     * Each migration file must be named using the following naming convention:
     *
     * <version>_<description>.php where:
     *
     * - <version> migration version number such as 003
     * - <description> migration description such as create_xxx_table
     *
     * Resulting to the following migration file:
     *
     * 003_create_xxx_table.php
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
     * If one of these keys is missing, the migrateDb method won't complain
     * and will simply continue its work normally. However, it's greatly
     * recommended to always provide both SQL statements as described above.
     *
     * Sample:
     *
     * <code>
     * return array(
     *     'up' => 'ALTER TABLE <table> ADD <column_def> AFTER <column>',
     *     'down' => 'ALTER TABLE <table> DROP COLUMN <column>'
     * );
     * </code>
     *
     * Finally, when a plugin doesn't longer provide migration files, it should
     * call this method in down mode through its update() method to remove the
     * 'db_schema_version' info field. For instance:
     *
     * <code>
     * public function update (
     *  iMSCP\Plugin\PluginManager $pm, $fromVersion, $toVersion
     * )
     * {
     *      try {
     *          # Migrations no longer provided since version x.x.x
     *          if( version_compare($fromVersion, 'x.x.x', '<')) {
     *              $this->migrateDb('down');
     *          }
     *      } catch(Exception $e) {
     *          throw new iMSCP\Plugin\PluginException(
     *              $e->getMessage(), $e->getCode(), $e
     *          );
     *      }
     * }
     * </code>
     *
     * @param string $migrationMode Migration mode (up|down)
     * @param string $migrationDir (default to <plugin>/sql directory)
     * @return void
     */
    final protected function migrateDb(
        $migrationMode = 'up', $migrationDir = NULL
    )
    {
        $pluginName = $this->getName();
        $pluginManager = $this->getPluginManager();
        $migrationDir = is_string($migrationDir)
            ? $migrationDir
            : $pluginManager->pluginGetRootDir() . '/' . $pluginName . '/sql';
        $pluginInfo = $this->getInfo();
        $dbSchemaVersion = isset($pluginInfo['db_schema_version'])
            ? $pluginInfo['db_schema_version'] : '000';
        $migrationFiles = [];

        try {
            if (!@is_dir($migrationDir)) {
                // Cover case where there are no longer migration files provided
                // by the plugin. In such a case, we need remove the
                // db_schema_version field from the plugin info.
                if ($migrationMode == 'down') {
                    unset($pluginInfo['db_schema_version']);
                    $pluginManager->pluginUpdateInfo($pluginName, $pluginInfo);
                    return;
                }

                throw new PluginException(sprintf(
                    "Directory %s doesn't exists.", $migrationDir
                ));
            }

            /** @var $migrationFileInfo DirectoryIterator */
            foreach (
                new DirectoryIterator($migrationDir) as $migrationFileInfo
            ) {
                if (!$migrationFileInfo->isDot()) {
                    $migrationFiles[] = $migrationFileInfo->getRealPath();
                }
            }

            natsort($migrationFiles);

            if ($migrationMode == 'down') {
                $migrationFiles = array_reverse($migrationFiles);
            }

            ignore_user_abort(true);
            $db = DatabaseMySQL::getInstance();

            foreach ($migrationFiles as $migrationFile) {
                if (!@is_readable($migrationFile)) {
                    throw new PluginException(tohtml(sprintf(
                        'Migration file %s is not readable.', $migrationFile
                    )));
                }

                if (!preg_match(
                    '/(\d+)_[^\/]+\.php$/i', $migrationFile, $version
                )) {
                    throw new PluginException(tohtml(sprintf(
                        "File %s doesn't look like a migration file.",
                        $migrationFile
                    )));
                }

                if (($migrationMode == 'up' && $version[1] > $dbSchemaVersion)
                    || ($migrationMode == 'down'
                        && $version[1] <= $dbSchemaVersion
                    )
                ) {
                    $migrationFilesContent = include($migrationFile);
                    if (isset($migrationFilesContent[$migrationMode])) {
                        $stmt = $db->prepare(
                            $migrationFilesContent[$migrationMode]
                        );
                        $db->execute($stmt);

                        /** @noinspection PhpStatementHasEmptyBodyInspection */
                        while ($stmt->nextRowset()) {
                            /* https://bugs.php.net/bug.php?id=61613 */
                        };
                    }

                    $dbSchemaVersion = $version[1];
                }
            }

            $pluginInfo['db_schema_version'] = ($migrationMode == 'up')
                ? $dbSchemaVersion : '000';
            $pluginManager->pluginUpdateInfo($pluginName, $pluginInfo);
        } catch (PluginException $e) {
            $pluginInfo['db_schema_version'] = $dbSchemaVersion;
            $pluginManager->pluginUpdateInfo($pluginName, $pluginInfo);

            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }


    /**
     * Make sure that new plugin information and parameters will be reloaded
     * when the object get cloned.
     *
     * @return void
     */
    final public function __clone()
    {
        $this->pluginInfo = null;
        $this->pluginConfig = null;
        $this->pluginConfigPrev = null;
    }
}
