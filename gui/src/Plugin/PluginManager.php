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
 *
 * @noinspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 * PhpIncludeInspection
 * PhpUnused
 */

declare(strict_types=1);

namespace iMSCP\Plugin;

use DirectoryIterator;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Json\LazyDecoder;
use iMSCP\Plugin\Filter\PluginArchive as PluginArchiveFilter;
use iMSCP\Plugin\Validate\PluginArchive as PluginArchiveValidator;
use iMSCP\Registry;
use iMSCP\Utility\OpcodeCache;
use PDO;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Throwable;
use Zend_File_Transfer;
use Zend_File_Transfer_Adapter_Abstract;

/**
 * Class PluginManager
 * @package iMSCP\Plugin
 */
class PluginManager
{
    /**
     * @var array Events triggered by this object
     */
    protected $listenEvents = [
        Events::onBeforeSyncPluginData,
        Events::onAfterSyncPluginData,
        Events::onBeforeInstallPlugin,
        Events::onAfterInstallPlugin,
        Events::onBeforeUpdatePlugin,
        Events::onAfterUpdatePlugin,
        Events::onBeforeEnablePlugin,
        Events::onAfterEnablePlugin,
        Events::onBeforeDisablePlugin,
        Events::onAfterDisablePlugin,
        Events::onBeforeUninstallPlugin,
        Events::onAfterUninstallPlugin,
        Events::onBeforeDeletePlugin,
        Events::onAfterDeletePlugin,
        Events::onBeforeLockPlugin,
        Events::onAfterLockPlugin,
        Events::onBeforeUnlockPlugin,
        Events::onAfterUnlockPlugin
    ];

    /**
     * @var string Plugins root directory
     */
    protected $pluginsRootDir;

    /**
     * @var string Plugins persistent data directory
     */
    protected $pluginPersistentDataDir;

    /**
     * @var array[][LazyDecoder] Keys are plugin names and values are array
     *                           containing plugin data
     */
    protected $pluginData = [];

    /**
     * @var array List of protected plugins
     */
    protected $protectedPlugins;

    /**
     * @var AbstractPlugin[] Plugin instances
     */
    protected $plugins = [];

    /**
     * @var bool Whether or not a backend request should be sent
     */
    protected $backendRequest = false;

    /**
     * @var EventAggregator
     */
    protected $events;

    /** @var ContainerInterface */
    protected $container;

    /**
     * PluginManager constructor.
     *
     * @param $container ContainerInterface
     * @param $events EventAggregator
     * @return void
     */
    public function __construct(
        ContainerInterface $container, EventAggregator $events
    )
    {
        $this->container = $container;
        $this->events = $events;
        $this->events->addEvents(__CLASS__, $this->listenEvents);
        spl_autoload_register([$this, 'autoload']);
        $this->pluginLoadDataFromDatabase();
    }

    /**
     * Load plugin data from database.
     *
     * @return void
     */
    protected function pluginLoadDataFromDatabase(): void
    {
        $this->pluginData = [];
        $stmt = execute_query(
            '
                SELECT `plugin_name`, `plugin_status`, `plugin_error`,
                    `plugin_backend`, `plugin_lockers`
                FROM `plugin`
                ORDER BY `plugin_priority` DESC
            '
        );
        while ($plugin = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $this->pluginData[$plugin['plugin_name']] = [
                'status'  => $plugin['plugin_status'],
                'error'   => $plugin['plugin_error'],
                'backend' => $plugin['plugin_backend'],
                'lockers' => new LazyDecoder($plugin['plugin_lockers'])
            ];
        }
    }

    /**
     * Send backend request if scheduled.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->backendRequest) {
            send_request();
        }
    }

    /**
     * Autoloader for plugin classes.
     *
     * @param string $class Plugin class to load
     * @return void
     */
    public function autoload(string $class): void
    {
        if (strpos($class, 'iMSCP_Plugin_', 0) !== 0) {
            return;
        }

        $basename = substr($class, 13);

        @include_once $this->pluginGetRootDir()
            . DIRECTORY_SEPARATOR . $basename . DIRECTORY_SEPARATOR
            . "$basename.php";
    }

    /**
     * Get plugins root directory.
     *
     * @return string Plugin directory
     */
    public function pluginGetRootDir(): string
    {
        if (NULL === $this->pluginsRootDir) {
            $this->pluginsRootDir = utils_normalizePath(Registry::get('config')['PLUGINS_DIR']);
        }

        return $this->pluginsRootDir;
    }

    /**
     * Sets plugins root directory.
     *
     * @param string $rootDir Plugin directory path
     * @return void
     */
    public function pluginSetRootDir(string $rootDir): void
    {
        $rootDir = utils_normalizePath((string)$rootDir);

        if (!@is_dir($rootDir) || !@is_writable($rootDir)) {
            write_log(sprintf(
                "Directory '%s' doesn't exist or is not writable", $rootDir
            ), E_USER_ERROR);

            throw new PluginException(tr(
                "Directory '%s' doesn't exist or is not writable", $rootDir
            ));
        }

        $this->pluginsRootDir = $rootDir;
    }

    /**
     * Get plugins root directory.
     *
     * @return string
     * @deprecated Replaced by pluginGetRootDir()
     */
    public function pluginGetDirectory(): string
    {
        return $this->pluginGetRootDir();
    }

    /**
     * Sets persistent data directory.
     *
     * @param string $persistentDataDir
     * @return void
     */
    public function pluginSetPersistentDataDir(string $persistentDataDir): void
    {
        $persistentDataDir = utils_normalizePath((string)$persistentDataDir);

        if (!@is_dir($persistentDataDir) || !@is_writable($persistentDataDir)) {
            write_log(sprintf(
                "Directory '%s' doesn't exist or is not writable",
                $persistentDataDir
            ), E_USER_ERROR);

            throw new PluginException(tr(
                "Directory '%s' doesn't exist or is not writable",
                $persistentDataDir
            ));
        }

        $this->pluginPersistentDataDir = $persistentDataDir;
    }

    /**
     * Returns list of plugins.
     *
     * @param bool $enabledOnly Flag indicating if only the list of enabled
     *                          plugins must be returned
     * @return array List of plugin names
     */
    public function pluginGetList(bool $enabledOnly = true): array
    {
        if (!$enabledOnly) {
            return array_keys($this->pluginData);
        }

        return array_filter(
            array_keys($this->pluginData),
            function ($pluginName) {
                return $this->pluginData[$pluginName]['status'] == 'enabled';
            }
        );
    }

    /**
     * Get list of loaded plugins.
     *
     * @return AbstractPlugin[] Array containing plugin instances
     */
    public function pluginGetLoaded(): array
    {
        return $this->plugins;
    }

    /**
     * Returns plugin info.
     *
     * @param string $pluginName Plugin name
     * @return array
     * @deprecated Deprecated. Make use of the getInfo() method on plugin
     *                         instance instead.
     */
    public function pluginGetInfo($pluginName): array
    {
        return $this->pluginGet($pluginName)->getInfo();
    }

    /**
     * Get instance of the given plugin.
     *
     * @param string $pluginName Plugin name
     * @return AbstractPlugin
     */
    public function pluginGet(string $pluginName): AbstractPlugin
    {
        try {
            if ($this->pluginIsLoaded($pluginName)) {
                return $this->plugins[$pluginName];
            }

            $pluginClass = "iMSCP\\Plugin\\$pluginName\\$pluginName";

            if (!class_exists($pluginClass, true)) {
                $pluginClass = "iMSCP_Plugin_$pluginName";
                if (!class_exists($pluginClass, true)) {
                    throw new PluginException(tr(
                        "Plugin entry point (plugin class) not found.",
                        $pluginName
                    ));
                }
            }

            if (!is_subclass_of($pluginClass, AbstractPlugin::class)) {
                throw new PluginException(tr(
                    "The %s plugin class must extend the %s plugin base class",
                    $pluginName,
                    AbstractPlugin::class
                ));
            }

            $this->plugins[$pluginName] = new $pluginClass($this);

            // FIXME: Why core service container should be aware of plugin services?
            //if($pluginServiceProvider = $this->plugins[$plugin]->getServiceProvider()) {
            //    // Register plugin services into application container
            //    $pluginServiceProvider->register($this->getContainer());
            //}

            // Register plugin event listeners
            $this->plugins[$pluginName]->register($this->getEventManager());
        } catch (Throwable $e) {
            write_log(sprintf(
                "Couldn't load the %s plugin: %s", $e->getMessage(), $pluginName
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                "Couldn't load the %s plugin: %s", $pluginName, $e->getMessage()
            ));
        }

        return $this->plugins[$pluginName];
    }

    /**
     * Is the given plugin loaded?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is loaded, FALSE otherwise
     */
    public function pluginIsLoaded(string $pluginName): bool
    {
        return isset($this->plugins[$pluginName]);
    }

    /**
     * Get service container.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get event manager.
     *
     * @return EventAggregator
     */
    public function getEventManager(): EventAggregator
    {
        return $this->events;
    }

    /**
     * Plugin upload.
     *
     * @return void
     */
    public function pluginUpload(): void
    {
        /** @var Zend_File_Transfer_Adapter_Abstract $fileTransfer */
        $fileTransfer = new Zend_File_Transfer();
        $fileTransfer->setTranslator(Registry::get('translator'));
        // We don't accept more than one plugin archive at a time
        $fileTransfer->addValidator('Count', true, 1);
        // We want restrict size of accepted plugin archives
        $fileTransfer->addValidator('Size', true, utils_getMaxFileUpload());
        // Add plugin archive validator
        $fileTransfer->addValidator(new PluginArchiveValidator($this), true);
        // Add plugin archive filter
        $fileTransfer->addFilter(new PluginArchiveFilter(
            $this->pluginGetRootDir()
        ));

        if (!$fileTransfer->receive()) {
            throw new PluginException(implode(
                "<br>", $fileTransfer->getMessages()
            ));
        }

        $info = include $fileTransfer->getFileName() . DIRECTORY_SEPARATOR
            . 'info.php';

        $this->pluginUpdateData($info['name']);
    }

    /**
     * Synchronize all plugins data, executing update/change actions when
     * needed.
     *
     * @return void
     */
    public function pluginSyncData(): void
    {
        try {
            $responses = $this->events->dispatch(
                Events::onBeforeSyncPluginData, ['pluginManager' => $this]
            );

            if ($responses->isStopped()) {
                return;
            }

            foreach (new DirectoryIterator($this->pluginGetRootDir()) as $dentry) {
                if ($dentry->isDot() || !$dentry->isDir()) {
                    continue;
                }

                $info = include $dentry->getPathname() . DIRECTORY_SEPARATOR
                    . 'info.php';

                $this->pluginUpdateData($info['name']);
            }

            $this->events->dispatch(
                Events::onAfterSyncPluginData, ['pluginManager' => $this]
            );
        } catch (Throwable $e) {
            throw new PluginException(sprintf(
                "Couldn't synchronize plugin data: %s", $e->getMessage()
            ), $e->getCode(), $e);
        }
    }

    /**
     * Update data for the given plugin, executing update/change actions when
     * needed.
     *
     * @param string $pluginName Plugin name
     * @return void
     */
    protected function pluginUpdateData(string $pluginName)
    {
        $plugin = $this->pluginGet($pluginName);

        $pluginInfoNew = $plugin->getInfoFromFile();
        $pluginConfigNew = $plugin->getConfigFromFile();
        $pluginIsKnown = $this->pluginIsKnown($pluginName);

        if($pluginIsKnown) {
            $pluginInfoOld = $plugin->getInfo();
            $pluginConfigOld = $plugin->getConfig();
        } else {
            $pluginInfoOld = $pluginInfoNew;
            $pluginConfigOld = $pluginConfigNew;
        }

        $pluginInfoNew['__nversion__'] = $pluginInfoNew['version'];
        $pluginInfoNew['version'] = $pluginInfoOld['version'];

        $pluginInfoNew['__nbuild__'] = isset($pluginInfoNew['build']) ? $pluginInfoNew['build'] : '0000000000';
        $pluginInfoNew['build'] = isset($pluginInfoOld['build']) ? $pluginInfoOld['build'] : '0000000000';

        $validator = new PluginArchiveValidator($this);
        if (!$validator->_isValidPlugin($pluginInfoNew)) {
            throw new PluginException(implode(
                "<br>", $validator->getMessages()
            ));
        }

        $fullVersionNew = $pluginInfoNew['__nversion__'] . '.' . $pluginInfoNew['__nbuild__'];
        $fullVersionOld = $pluginInfoNew['version'] . '.' . $pluginInfoNew['build'];

        if (version_compare($fullVersionNew, $fullVersionOld, '<')) {
            set_page_message(tr(
                "Downgrade of the '%s' plugin to version '%s' (build '%s') is forbidden.",
                $pluginName,
                $pluginInfoNew['version'],
                $pluginInfoNew['build'],
            ), 'static_error');
            return;
        }

        if (isset($pluginInfoOld['__migration__'])) {
            $pluginInfoNew['__migration__'] = $pluginInfoOld['__migration__'];
        }

        /*$pluginConfigNew = $plugin->getConfigFromFile();
        if ($this->pluginIsKnown($pluginName)) {
            $pluginConfigOld =& $plugin->getConfig();
        } else {
            $pluginConfigOld =& $pluginConfigNew;
        }
        */

        $reflectionMethod = new ReflectionMethod($plugin, 'install');
        $pluginInfoNew['__installable__'] = AbstractPlugin::class !== $reflectionMethod->getDeclaringClass()->getName();
        $reflectionMethod = new ReflectionMethod($plugin, 'uninstall');
        $pluginInfoNew['__uninstallable__'] = AbstractPlugin::class !== $reflectionMethod->getDeclaringClass()->getName();
        $pluginAction = 'none';

        if ($pluginIsKnown) {
            $pluginStatus = $this->pluginGetStatus($pluginName);
            $pluginLockers = $this->pluginData[$pluginName]['lockers'];

            // Plugin has changes, either info or config
            if (!$this->pluginCompareData($pluginInfoNew, $pluginInfoOld)
                || !$this->pluginCompareData($pluginConfigNew, $pluginConfigOld)
            ) {
                // Plugin is protected
                if ($this->pluginIsProtected($pluginName)) {
                    set_page_message(tr(
                        'The %s plugin changes were ignored as this one is protected.',
                        $pluginName
                    ), 'static_warning');
                    return;
                }

                // No error but pending task
                if (!$this->pluginHasError($pluginName)
                    && !in_array($pluginStatus, [
                        'uninstalled', 'enabled', 'disabled'
                    ])
                ) {
                    set_page_message(tr(
                        'Changes for the %s plugin were ignored as there is a pending task for this one. Please retry once the task is completed.',
                        $pluginName
                    ), 'static_warning');
                    return;
                }

                if ($pluginStatus != 'enabled'
                    || $this->pluginHasError($pluginName)
                ) {
                    $pluginAction = 'store';
                } elseif (version_compare(
                    $fullVersionNew, $fullVersionOld, '>')
                ) {
                    $pluginAction = 'toupdate';
                } else {
                    $pluginAction = 'tochange';
                }
            }

            // We clone the plugin to make the that next call to the self::pluginGet()
            // method will return plugin instance with newest info/parameters
            $this->plugins[$pluginName] = clone $plugin;
        } else {
            $pluginStatus = $pluginInfoNew['__installable__']
                ? 'uninstalled' : 'disabled';
            $pluginLockers = new LazyDecoder('{}');
            $pluginAction = 'store';
        }

        if ($pluginAction == 'none') {
            set_page_message(
                tr("No changes were detected for the %s plugin.", $pluginName),
                'success'
            );
            return;
        }

        $this->pluginStoreDataInDatabase([
            'name'          => $pluginName,
            'info'          => json_encode($pluginInfoNew),
            'config'        => json_encode($pluginConfigNew),
            // On plugin change/update, make sure that config_prev also contains
            // new parameters
            'config_prev'   => json_encode($pluginIsKnown
                ? array_merge_recursive($pluginConfigNew, $pluginConfigOld)
                : $pluginConfigNew),
            'priority'      => $pluginInfoNew['priority'],
            'status'        => $pluginStatus,
            'backend'       => file_exists($this->pluginGetRootDir()
                . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR
                . 'backend' . DIRECTORY_SEPARATOR . "$pluginName.pm"
            ) ? 'yes' : 'no',
            'lockers' => json_encode(
                $pluginLockers->toArray(), JSON_FORCE_OBJECT
            )
        ]);

        switch ($pluginAction) {
            case 'toupdate':
                $this->pluginUpdate($pluginName);
                break;
            case 'tochange':
                $this->pluginChange($pluginName);
                break;
            default:
                set_page_message(tr(
                    'New %s plugin data were successfully stored.',
                    $pluginName
                ), 'success');
                return;
        }

        if ($this->pluginHasBackend($pluginName)) {
            set_page_message(tr(
                "Action '%s' successfully scheduled for the %s plugin.",
                $pluginAction,
                $pluginName
            ), 'success');
            return;
        }

        set_page_message(tr(
            "Action '%s' successfully executed for the %s plugin.",
            $pluginAction,
            $pluginName
        ), 'success');
    }

    /**
     * Get status of the given plugin.
     *
     * @param string $pluginName Plugin name
     * @return string Plugin status
     */
    public function pluginGetStatus($pluginName): string
    {
        if (!$this->pluginIsKnown($pluginName)) {
            throw new PluginException(tr('Unknown plugin: %s', $pluginName));
        }

        return $this->pluginData[$pluginName]['status'];
    }

    /**
     * is the given plugin protected?
     *
     * @param string $pluginName Plugin name
     * @return bool
     */
    public function pluginIsProtected(string $pluginName): bool
    {
        if (!$this->pluginIsKnown($pluginName)) {
            throw new PluginException(tr('Unknown plugin: %s', $pluginName));
        }

        if (NULL == $this->protectedPlugins) {
            $this->protectedPlugins = [];
            $file = $this->pluginGetPersistentDataDir() . DIRECTORY_SEPARATOR
                . 'protected_plugins.php';

            if (is_readable($file)) {
                $this->protectedPlugins = include $file;
            }
        }

        return in_array($pluginName, $this->protectedPlugins);
    }

    /**
     * Get persistent data directory.
     *
     * @return string
     */
    public function pluginGetPersistentDataDir(): string
    {
        if (NULL === $this->pluginPersistentDataDir) {
            return $this->pluginPersistentDataDir = utils_normalizePath(
                PERSISTENT_PATH
            );
        }

        return $this->pluginPersistentDataDir;
    }

    /**
     * Does the given plugin has error?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin has error, FALSE otherwise
     */
    public function pluginHasError(string $pluginName): bool
    {
        return NULL !== $this->pluginGetError($pluginName);
    }

    /**
     * Get plugin error.
     *
     * @param null|string $pluginName Plugin name
     * @return string|null Plugin error string or NULL if no error
     */
    public function pluginGetError(string $pluginName): ?string
    {
        if (!$this->pluginIsKnown($pluginName)) {
            throw new PluginException(tr('Unknown plugin: %s', $pluginName));
        }

        return $this->pluginData[$pluginName]['error'];
    }

    /**
     * Set error for the given plugin.
     *
     * @param string $pluginName Plugin name
     * @param null|string $error Plugin error string or NULL if no error
     * @return void
     */
    public function pluginSetError(string $pluginName, ?string $error): void
    {
        if ($error === $this->pluginGetError($pluginName)) {
            return;
        }

        try {
            exec_query(
                'UPDATE `plugin` SET `plugin_error` = ? WHERE `plugin_name` = ?',
                [$error, $pluginName]
            );
            $this->pluginData[$pluginName]['error'] = $error;
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Is the given plugin known?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is known , FALSE otherwise
     */
    public function pluginIsKnown(string $pluginName): bool
    {
        return isset($this->pluginData[$pluginName]);
    }

    /**
     * Store plugin data in database.
     *
     * @param array $pluginData Plugin data
     * @return void
     */
    protected function pluginStoreDataInDatabase(array $pluginData): void
    {
        try {
            exec_query(
                '
                    INSERT INTO plugin (
                        `plugin_name`,
                        `plugin_info`,
                        `plugin_config`,
                        `plugin_config_prev`,
                        `plugin_priority`,
                        `plugin_status`,
                        `plugin_backend`,
                        `plugin_lockers`
                    ) VALUE ( ?, ?, ?, ?, ?, ?, ?, ? ) ON DUPLICATE KEY UPDATE
                        `plugin_info` = ?,
                        `plugin_config` = ?,
                        `plugin_config_prev` = ?,
                        `plugin_priority` = ?,
                        `plugin_status` = ?,
                        `plugin_backend` = ?,
                        `plugin_lockers` = ?
                ',
                [
                    // Insert data
                    $pluginData['name'],
                    $pluginData['info'],
                    $pluginData['config'],
                    $pluginData['config_prev'],
                    $pluginData['priority'],
                    $pluginData['status'],
                    $pluginData['backend'],
                    $pluginData['lockers'],
                    // Update data
                    $pluginData['info'],
                    $pluginData['config'],
                    $pluginData['config_prev'],
                    $pluginData['priority'],
                    $pluginData['status'],
                    $pluginData['backend'],
                    $pluginData['lockers']
                ]
            );
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update the given plugin.
     *
     * @param string $pluginName Plugin name
     * @return void
     */
    public function pluginUpdate(string $pluginName): void
    {
        try {
            $pluginStatus = $this->pluginGetStatus($pluginName);

            if (!in_array($pluginStatus, ['toupdate', 'enabled'])) {
                throw new PluginException(tr(
                    "The '%s' action is forbidden for the %s plugin.",
                    'update',
                    $pluginName
                ));
            }

            $plugin = $this->pluginGet($pluginName);

            $this->pluginSetStatus($pluginName, 'toupdate');
            $this->pluginDisable($pluginName, true);

            $pluginInfo = $plugin->getInfo();
            $fullVersionNew = $pluginInfo['__nversion__'] . '.'
                . $pluginInfo['__nbuild__'];
            $fullVersionOld = $pluginInfo['version'] . '.'
                . $pluginInfo['build'];

            $responses = $this->events->dispatch(Events::onBeforeUpdatePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName,
                'fromVersion'   => $fullVersionOld,
                'toVersion'     => $fullVersionNew
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);

                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'update',
                    $pluginName
                ));
            }

            $plugin->update($this, $fullVersionOld, $fullVersionNew);

            if (!$this->pluginHasBackend($pluginName)) {
                $pluginInfo['version'] = $pluginInfo['__nversion__'];
                $pluginInfo['build'] = $pluginInfo['__nbuild__'];

                $this->pluginUpdateInfo($pluginName, $pluginInfo);
            }

            $this->events->dispatch(Events::onAfterUpdatePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName,
                'fromVersion'   => $fullVersionOld,
                'toVersion'     => $fullVersionNew
            ]);

            $this->pluginEnable($pluginName, true);

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($pluginName, 'enabled');
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin update has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin update has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Set status for the given plugin.
     *
     * @param string $pluginName Plugin name
     * @param string $status New plugin status
     * @return void
     */
    public function pluginSetStatus(string $pluginName, string $status): void
    {
        if ($status === $this->pluginGetStatus($pluginName)
            && NULL === $this->pluginGetError($pluginName)
        ) {
            return;
        }

        try {
            exec_query(
                '
                    UPDATE `plugin`
                    SET `plugin_status` = ?, `plugin_error` = NULL
                    WHERE `plugin_name` = ?
                ',
                [$status, $pluginName]
            );
            $this->pluginData[$pluginName]['status'] = $status;
            $this->pluginData[$pluginName]['error'] = NULL;
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Disable the given plugin.
     *
     * @param string $pluginName Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is
     *                          called in context of the install update or
     *                          change action
     * @return void
     */
    public function pluginDisable(
        string $pluginName, bool $isSubAction = false
    ): void
    {
        try {
            $pluginStatus = $this->pluginGetStatus($pluginName);

            if (!$isSubAction && !in_array($pluginStatus, ['todisable', 'enabled'])) {
                throw new PluginException(tr(
                    "The '%s' action is forbidden for the %s plugin.",
                    'disable',
                    $pluginName
                ));
            }

            $plugin = $this->pluginGet($pluginName);

            if (!$isSubAction) {
                $this->pluginSetStatus($pluginName, 'todisable');
            }

            $responses = $this->events->dispatch(Events::onBeforeDisablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if (!$isSubAction && $responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);

                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'disable',
                    $pluginName
                ));
            }

            $plugin->disable($this);

            $this->events->dispatch(Events::onAfterDisablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($isSubAction) {
                return;
            }

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($pluginName, 'disabled');
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin deactivation has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin deactivation has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Does the given plugin provides a backend side?
     *
     * @param string $pluginName Plugin name
     * @return boolean TRUE if the given plugin provide backend part, FALSE
     *                 otherwise
     */
    public function pluginHasBackend(string $pluginName): bool
    {
        if (!$this->pluginIsKnown($pluginName)) {
            throw new PluginException(tr('Unknown plugin: %s', $pluginName));
        }

        return $this->pluginData[$pluginName]['backend'] == 'yes';
    }

    /**
     * Update plugin info.
     *
     * @param string $pluginName Plugin Name
     * @param array $infoNew New plugin info
     * @return void
     */
    public function pluginUpdateInfo(string $pluginName, array $infoNew): void
    {
        $oldInfo =& $this->pluginGet($pluginName)->getInfo();

        if ($this->pluginCompareData($infoNew, $oldInfo)) {
            return;
        }

        try {
            exec_query(
                'UPDATE `plugin` SET `plugin_info` = ? WHERE `plugin_name` = ?',
                [json_encode($infoNew), $pluginName]
            );
            $oldInfo = $infoNew;
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Compare the given plugin data.
     *
     * @param array $aPluginData
     * @param array $bPluginData
     * @return bool TRUE if data are identical (order doesn't matter), FALSE
     *              otherwise
     */
    protected function pluginCompareData(
        array &$aPluginData, array &$bPluginData
    ): bool
    {
        if (count($aPluginData) != count($bPluginData)) {
            return false;
        }

        foreach ($aPluginData as $k => $v) {
            if (!array_key_exists($k, $bPluginData)) {
                return false;
            }

            if (is_array($v) && is_array($bPluginData[$k])) {
                if (!$this->pluginCompareData($v, $bPluginData[$k])) {
                    return false;
                }
            } elseif ($v !== $bPluginData[$k]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enable the given plugin.
     *
     * @param string $pluginName Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is
     *                          called in context of the install update or
     *                          change action
     * @return void
     */
    public function pluginEnable(
        string $pluginName, bool $isSubAction = false
    ): void
    {
        try {
            $pluginStatus = $this->pluginGetStatus($pluginName);

            if (!$isSubAction && !in_array($pluginStatus, ['toenable', 'disabled'])) {
                throw new PluginException(tr(
                    "The '%s' action is forbidden for the %s plugin.",
                    'enable',
                    $pluginName
                ));
            }

            $plugin = $this->pluginGet($pluginName);

            if (!$isSubAction) {
                if ($this->pluginRequireUpdate($pluginName)) {
                    $this->pluginSetStatus($pluginName, 'toupdate');
                    $this->pluginUpdate($pluginName);
                    return;
                }

                if ($this->pluginRequireChange($pluginName)) {
                    $this->pluginSetStatus($pluginName, 'tochange');
                    $this->pluginChange($pluginName);
                    return;
                }

                $this->pluginSetStatus($pluginName, 'toenable');
            }

            $responses = $this->events->dispatch(Events::onBeforeEnablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if (!$isSubAction && $responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);

                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'enable',
                    $pluginName
                ));
            }

            $plugin->enable($this);
            $this->events->dispatch(Events::onAfterEnablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($isSubAction) {
                return;
            }

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($pluginName, 'enabled');
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin activation has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin activation has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Does the given plugin requires update.
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin requires change, FALSE otherwise
     */
    protected function pluginRequireUpdate(string $pluginName): bool
    {
        $info = $this->pluginGet($pluginName)->getInfo();

        return version_compare(
            $info['nversion'] . '.' . $info['nbuild'],
            $info['version'] . '.' . $info['build'],
            '>'
        );
    }

    /**
     * Does the given plugin requires change.
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin requires change, FALSE otherwise
     */
    protected function pluginRequireChange(string $pluginName): bool
    {
        $inst = $this->pluginGet($pluginName);

        return !$this->pluginCompareData(
            $inst->getConfig(), $inst->getConfigPrev()
        );
    }

    /**
     * Change (reconfigure) the given plugin.
     *
     * @param string $pluginName Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is
     *                          called in context of the update action
     * @return void
     */
    public function pluginChange(
        string $pluginName, bool $isSubAction = false
    ): void
    {
        try {
            $pluginStatus = $this->pluginGetStatus($pluginName);

            if (!$isSubAction && !in_array($pluginStatus, ['tochange', 'enabled'])) {
                throw new PluginException(tr(
                    "The '%s' action is forbidden for the %s plugin.",
                    'change', $pluginName
                ));
            }

            $this->pluginSetStatus($pluginName, 'tochange');

            if (!$isSubAction) {
                $this->pluginDisable($pluginName, true);
                $this->pluginEnable($pluginName, true);
            }

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
                return;
            }

            try {
                exec_query(
                    '
                        UPDATE `plugin`
                        SET `plugin_config_prev` = `plugin_config`
                        WHERE `plugin_name` = ?
                     ',
                    $pluginName
                );
                $this->pluginSetStatus($pluginName, 'enabled');
            } catch (Throwable $e) {
                throw new PluginException($e->getMessage(), $e->getCode(), $e);
            }
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin reconfiguration has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin reconfiguration has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Lock the given plugin.
     *
     * @param string $pluginName Plugin name
     * @param string $lockerName Locker name
     * @return void
     */
    public function pluginLock(string $pluginName, string $lockerName): void
    {
        try {
            if ($this->pluginIsLocked($pluginName, $lockerName)) {
                return;
            }

            $responses = $this->events->dispatch(Events::onBeforeLockPlugin, [
                'pluginName'   => $pluginName,
                'pluginLocker' => $lockerName
            ]);

            if ($responses->isStopped()) {
                return;
            }

            $pluginLockers = $this->pluginData[$pluginName]['lockers'];
            $pluginLockers[$lockerName] = 1;

            exec_query(
                '
                    UPDATE `plugin`
                    SET `plugin_lockers` = ?
                    WHERE `plugin_name` = ?
                ',
                [json_encode($pluginLockers->toArray(), JSON_FORCE_OBJECT), $pluginName]
            );

            $this->events->dispatch(Events::onAfterLockPlugin, [
                'pluginName'   => $pluginName,
                'pluginLocker' => $lockerName
            ]);
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * is the given plugin locked?
     *
     * @param string $pluginName Plugin name
     * @param string|null $lockerName OPTIONAL Locker name (default any locker)
     * @return bool TRUE if the given plugin is locked, false otherwise
     */
    public function pluginIsLocked(
        string $pluginName, ? string $lockerName = NULL
    )
    {
        if (!$this->pluginIsKnown($pluginName)) {
            throw new PluginException(tr('Unknown plugin: %s', $pluginName));
        }

        if (NULL === $lockerName) {
            return count($this->pluginData[$pluginName]['lockers']) > 0;
        }

        return isset($this->pluginData[$pluginName]['lockers'][$lockerName]);
    }

    /**
     * Unlock the given plugin.
     *
     * @param string $pluginName Plugin name
     * @param string $lockerName Locker name
     * @return void
     */
    public function pluginUnlock(string $pluginName, string $lockerName): void
    {
        try {
            if (!$this->pluginIsLocked($pluginName, $lockerName)) {
                return;
            }

            $responses = $this->events->dispatch(Events::onBeforeUnlockPlugin, [
                'pluginName'   => $pluginName,
                'pluginLocker' => $lockerName
            ]);

            if ($responses->isStopped()) {
                return;
            }

            /** @var LazyDecoder $pluginLockers */
            $pluginLockers = $this->pluginData[$pluginName]['lockers'];
            unset($pluginLockers[$lockerName]);

            exec_query(
                'UPDATE `plugin` SET `plugin_lockers` = ? WHERE `plugin_name` = ?',
                [json_encode($pluginLockers->toArray(), JSON_FORCE_OBJECT), $pluginName]
            );

            $this->events->dispatch(Events::onAfterUnlockPlugin, [
                'pluginName'   => $pluginName,
                'pluginLocker' => $lockerName
            ]);
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Is the given plugin is installed?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is installed FALSE otherwise
     */
    public function pluginIsInstalled(string $pluginName): bool
    {
        return !in_array(
            $this->pluginGetStatus($pluginName), ['toinstall', 'uninstalled']
        );
    }

    /**
     * Install the given plugin.
     *
     * @param string $pluginName Plugin name
     * @return void
     */
    public function pluginInstall(string $pluginName): void
    {
        try {
            $pluginStatus = $this->pluginGetStatus($pluginName);

            if (!in_array($pluginStatus, ['toinstall', 'uninstalled'])) {
                throw new PluginException(tr(
                    "The '%s' action is forbidden for the %s plugin.", 'install',
                    $pluginName
                ));
            }

            $plugin = $this->pluginGet($pluginName);
            $this->pluginSetStatus($pluginName, 'toinstall');

            try {
                $responses = $this->events->dispatch(Events::onBeforeInstallPlugin, [
                    'pluginManager' => $this,
                    'pluginName'    => $pluginName
                ]);

                if ($responses->isStopped()) {
                    throw new PluginActionStoppedException(tr(
                        "The '%s' action has been stopped for the %s plugin.",
                        'install',
                        $pluginName
                    ));
                }
            } catch (Throwable $e) {
                $this->pluginSetStatus($pluginName, $pluginStatus);
                throw $e;
            }

            $plugin->install($this);

            $this->events->dispatch(Events::onAfterInstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            $this->pluginEnable($pluginName, true);

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($pluginName, 'enabled');
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin installation has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin installation has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Is the given plugin uninstalled?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is uninstalled FALSE otherwise
     */
    public function pluginIsUninstalled(string $pluginName): bool
    {
        return $this->pluginGetStatus($pluginName) == 'uninstalled';
    }

    /**
     * Uninstall the given plugin.
     *
     * @param string $pluginName Plugin name
     * @return void
     */
    public function pluginUninstall(string $pluginName): void
    {
        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!in_array($pluginStatus, ['touninstall', 'disabled'])
            || !$this->pluginIsUninstallable($pluginName)
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'uninstall',
                $pluginName
            ));
        }

        try {
            $plugin = $this->pluginGet($pluginName);
            $this->pluginSetStatus($pluginName, 'touninstall');

            $responses = $this->events->dispatch(Events::onBeforeUninstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);

                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'uninstall',
                    $pluginName
                ));
            }

            $plugin->uninstall($this);

            $this->events->dispatch(Events::onAfterUninstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus(
                $pluginName,
                $this->pluginIsInstallable($pluginName)
                    ? 'uninstalled' : 'disabled'
            );
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin uninstallation has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin uninstallation has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Is the given plugin uninstallable?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin can be uninstalled, FALSE otherwise
     */
    public function pluginIsUninstallable(string $pluginName): bool
    {
        $plugin = $this->pluginGet($pluginName);
        $pluginInfo = $plugin->getInfo();

        if (isset($pluginInfo['__uninstallable__'])) {
            return $pluginInfo['__uninstallable__'];
        }

        $reflectionMethod = new ReflectionMethod($plugin, 'uninstall');

        return AbstractPlugin::class !==
            $reflectionMethod->getDeclaringClass()->getName();
    }

    /**
     * Is the given plugin installable?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is installable, FALSE otherwise
     */
    public function pluginIsInstallable(string $pluginName): bool
    {
        $plugin = $this->pluginGet($pluginName);
        $pluginInfo = $plugin->getInfo();

        if (isset($pluginInfo['__installable__'])) {
            return $pluginInfo['__installable__'];
        }

        $reflectionMethod = new ReflectionMethod($plugin, 'install');

        return AbstractPlugin::class !==
            $reflectionMethod->getDeclaringClass()->getName();
    }

    /**
     * Is the given plugin disabled?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given is disabled, FALSE otherwise
     */
    public function pluginIsDisabled(string $pluginName): bool
    {
        return $this->pluginGetStatus($pluginName) == 'disabled';
    }

    /**
     * Delete the given plugin.
     *
     * @param string $pluginName Plugin name
     * @return void
     */
    public function pluginDelete(string $pluginName): void
    {
        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!in_array($pluginStatus, ['todelete', 'uninstalled', 'disabled'])
            || $this->pluginIsLocked($pluginName)
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'delete',
                $pluginName
            ));
        }

        try {
            $plugin = $this->pluginGet($pluginName);
            $this->pluginSetStatus($pluginName, 'todelete');

            $responses = $this->events->dispatch(Events::onBeforeDeletePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);

                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'delete',
                    $pluginName
                ));
            }

            $plugin->delete($this);

            if (!utils_removeDir(utils_normalizePath(
                $this->pluginsRootDir . DIRECTORY_SEPARATOR . $pluginName
            ))) {
                throw new PluginException(tr(
                    "Couldn't delete the %s plugin. You should fix the file permissions and try again.",
                    $pluginName
                ));
            }

            try {
                exec_query('DELETE FROM `plugin` WHERE `plugin_name` = ?', [
                    $pluginName
                ]);
            } catch (Throwable $e) {
                throw new PluginException($e->getMessage(), $e->getCode(), $e);
            }

            $this->events->dispatch(Events::onAfterDeletePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin deletion has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin deletion has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Protect the given plugin.
     *
     * @param string $pluginName Name of the plugin to protect
     * @return void
     */
    public function pluginProtect(string $pluginName): void
    {
        if (!$this->pluginIsEnabled($pluginName)
            || $this->pluginIsProtected($pluginName)
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'protect',
                $pluginName
            ));
        }

        try {
            $responses = $this->events->dispatch(Events::onBeforeProtectPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'protect',
                    $pluginName
                ));
            }

            $this->protectedPlugins[] = $pluginName;

            $file = utils_normalizePath(
                $this->pluginGetPersistentDataDir() . DIRECTORY_SEPARATOR
                . 'protected_plugins.php'
            );
            $content = sprintf(
                "<?php\n/**\n * Protected plugin list\n * Auto-generated on %s\n */\n\n",
                date('Y-m-d H:i:s', time())
            );
            $content .= 'return '
                . var_export($this->protectedPlugins, true) . ";\n";

            if (@file_put_contents($file, $content, LOCK_EX) === false) {
                write_log(sprintf("Couldn't write the %s file.", $file));
                throw new PluginException(tr("Couldn't write the %s file.", $file));
            }

            OpcodeCache::clearAllActive($file);

            $this->events->dispatch(Events::onAfterProtectPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);
        } catch (Throwable $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($pluginName, $e->getMessage());
            }

            write_log(sprintf(
                'The %s plugin protection has failed: %s',
                $pluginName,
                $e->getMessage()
            ), E_USER_ERROR);

            throw new PluginException(sprintf(
                'The %s plugin protection has failed.', $pluginName
            ), $e->getCode(), $e);
        }
    }

    /**
     * Is the given plugin enabled?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is enabled, FALSE otherwise
     */
    public function pluginIsEnabled(string $pluginName): bool
    {
        return $this->pluginGetStatus($pluginName) == 'enabled';
    }

    /**
     * Check plugin compatibility.
     *
     * @param string $pluginName Plugin name
     * @param array $pluginInfoNew New plugin info
     * @return void
     */
    public function pluginCheckCompat(
        string $pluginName, array $pluginInfoNew
    ): void
    {
        if (!isset($pluginInfoNew['require_api'])
            || version_compare(
                $this->pluginGetApiVersion(), $pluginInfoNew['require_api'], '<'
            )
        ) {
            throw new PluginException(tr(
                'The %s plugin version %s (build %d) is not compatible with your i-MSCP version.',
                $pluginName,
                $pluginInfoNew['version'],
                $pluginInfoNew['build']
            ));
        }

        if (!$this->pluginIsKnown($pluginName)) {
            return;
        }

        $infoOld =& $this->pluginGet($pluginName)->getInfo();
        if (version_compare(
            $infoOld['version'] . '.' . $infoOld['build'],
            $pluginInfoNew['version'] . '.' . $pluginInfoNew['build'],
            '>'
        )) {
            throw new PluginException(tr(
                "Downgrade of the '%s' plugin to version '%s' (build '%s') is forbidden.",
                $pluginName,
                $pluginInfoNew['version'],
                $pluginInfoNew['build']
            ));
        }
    }

    /**
     * Returns plugin API version.
     *
     * @return string Plugin API version
     */
    public function pluginGetApiVersion(): string
    {
        return Registry::get('config')['PluginApi'];
    }

    /**
     * Guess action to execute for the given plugin according its current
     * status.
     *
     * @param string $pluginName Plugin name
     * @return string Action to be executed for the given plugin
     */
    public function pluginGuessAction(string $pluginName): string
    {
        $pluginStatus = $this->pluginGetStatus($pluginName);

        switch ($pluginStatus) {
            case 'uninstalled':
            case 'toinstall':
                return 'install';
            case 'touninstall':
                return 'uninstall';
            case 'toupdate':
                return 'update';
            case 'enabled':
                return 'disable';
            case 'disabled':
                return 'enable';
            case 'tochange':
                return 'change';
            default:
                throw new PluginException(tr(
                    "Unknown pluginStatus '%s' for the %s plugin",
                    $pluginStatus,
                    $pluginName
                ));
        }
    }

    /**
     * Translate the given plugin status.
     *
     * @param string $pluginStatus Plugin status
     * @return string
     */
    public function pluginTranslateStatus(string $pluginStatus): string
    {
        switch ($pluginStatus) {
            case 'uninstalled':
                return tr('Uninstalled');
            case 'toinstall':
                return tr('Installation in progress...');
            case 'touninstall':
                return tr('Uninstallation in progress...');
            case 'toupdate':
                return tr('Update in progress...');
            case 'tochange':
                return tr('Reconfiguration in progress...');
            case 'toenable':
                return tr('Activation in progress...');
            case 'todisable':
                return tr('Deactivation in progress...');
            case 'enabled':
                return tr('Activated');
            case 'disabled':
                return tr('Deactivated');
            default:
                return tr('Unknown status');
        }
    }
}
